<?php
include 'connect.php';
require_once 'ai_client.php';
$item_id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$item_id || $item_id < 1) { http_response_code(404); exit('Activity not found.'); }
$statement = $conn->prepare('SELECT * FROM items WHERE id = ?');
$statement->bind_param('i', $item_id); $statement->execute();
$item = $statement->get_result()->fetch_assoc();
if (!$item) { http_response_code(404); exit('Activity not found.'); }
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    try {
        $username = input_text('username', 100);
        $comment = input_text('comment', 2000);
        $liked = isset($_POST['liked']) ? 1 : 0;
        $rating = filter_var($_POST['rating'] ?? null, FILTER_VALIDATE_INT);
        if (!$rating || $rating < 1 || $rating > 5) throw new RuntimeException('Invalid rating.');
        $statement = $conn->prepare('INSERT INTO reviews (item_id, username, rating, comment, liked) VALUES (?, ?, ?, ?, ?)');
        $statement->bind_param('isisi', $item_id, $username, $rating, $comment, $liked); $statement->execute();
        $success = 'تم إرسال تعليقك.';
    } catch (RuntimeException $exception) { $error = 'Check your name, rating (1–5), and comment (up to 2000 characters).'; }
}
$statement = $conn->prepare('SELECT AVG(rating) AS avg_rating, COUNT(*) AS count_reviews FROM reviews WHERE item_id = ?');
$statement->bind_param('i', $item_id); $statement->execute();
$avg_data = $statement->get_result()->fetch_assoc();
$reviews_count = (int)$avg_data['count_reviews'];
// Retain the original ceiling-based star calculation.
$avg_rating = $avg_data['avg_rating'] === null ? 0 : (int)ceil($avg_data['avg_rating']);
$statement = $conn->prepare('UPDATE items SET rating = ? WHERE id = ?');
$statement->bind_param('ii', $avg_rating, $item_id); $statement->execute();
$statement = $conn->prepare('SELECT * FROM reviews WHERE item_id = ? ORDER BY id DESC');
$statement->bind_param('i', $item_id); $statement->execute();
$reviews = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
$ai = ai_analyze(array_column($reviews, 'comment'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= escape_html($item['name']) ?> – Riyadh Season Guide</title>
<link rel="stylesheet" href="style_item.css">
<link rel="stylesheet" href="style_ai.css">
<link rel="stylesheet" href="header_footer.css">

</head>
<body>

<!-- HEADER -->
<header class="header">
    <div class="logo">Riyadh Season Guide</div>
    <nav>
        <a href="index.php">Home</a>
        <a href="about.php">About</a>
        <a href="login.php">Admin Login</a>
    </nav>
</header>

<!-- HERO SECTION -->
<section class="hero-section">
    <div class="hero-left">
        <h1>
            <span class="green">Riyadh</span><br>
            <span class="blue">season</span><br>
            <span class="white">guide</span>
        </h1>
    </div>

    <div class="hero-right">
        <img src="images/logo.png" class="hero-logo">
    </div>
</section>

<!-- MAIN ITEM BOX -->
<div class="item-box">

    <img src="images/<?= escape_html($item['image']) ?>" class="item-img">

    <h1 class="item-title"><?= escape_html($item['name']) ?></h1>

    <div class="rating-line">
        <?php
        // عرض النجوم حسب المتوسط الجديد
        for ($i = 1; $i <= 5; $i++) {
            echo ($i <= $avg_rating) ? "⭐" : "☆";
        }
        ?>
        <span class="review-count">(<?= $reviews_count ?> Reviews)</span>
    </div>

    <p class="item-desc"><?= escape_html($item['description']) ?></p>
</div>


<!-- ADD REVIEW FORM -->
<div class="review-box" dir="rtl">

    <h2>أضف تعليقك</h2>
<?php if ($error): ?><p class="ai-error" role="alert"><?= escape_html($error) ?></p><?php endif; ?>

    <?php if ($success != "") { ?>
        <p class="success-msg"><?= escape_html($success) ?></p>
    <?php } ?>

    <form method="POST">
<?= csrf_input() ?>

        <label for="username">الاسم</label>
        <input id="username" type="text" name="username" maxlength="100" required>

        <label class="like-option"><input type="checkbox" name="liked" value="1"> <span>👍 أعجبتني الفعالية</span></label>

        <label for="rating">التقييم</label>
        <select id="rating" name="rating" required>
            <option value="1">⭐ 1</option>
            <option value="2">⭐⭐ 2</option>
            <option value="3">⭐⭐⭐ 3</option>
            <option value="4">⭐⭐⭐⭐ 4</option>
            <option value="5">⭐⭐⭐⭐⭐ 5</option>
        </select>

        <label for="comment">التعليق</label>
        <textarea id="comment" name="comment" maxlength="2000" dir="auto" required></textarea>

        <button type="submit" class="btn">إرسال التعليق</button>

    </form>
</div>

<!-- REVIEWS LIST -->
<div class="reviews-list" dir="rtl">
    <h2>تعليقات الزوار</h2>
    <?php if (!$reviews): ?><p>لا توجد تعليقات بعد.</p><?php endif; ?>

    <?php foreach ($reviews as $review_index => $r) { ?>
        <div class="review-item">
            <div class="review-stars">
                <?php
                for ($i = 1; $i <= 5; $i++) {
         echo ($i <= $r['rating']) ? "⭐" : "☆";
                }
                ?>
            </div>
            <p class="review-user"><?= escape_html($r['username']) ?></p>
            <?php if ($r['liked']): ?><span class="review-like" aria-label="أعجبت الزائر الفعالية">👍 أعجبتني الفعالية</span><?php endif; ?>
            <div class="review-comment-line">
            <p class="review-text" dir="auto"><?= escape_html($r['comment']) ?></p>
            <?php $result = $ai['results'][$review_index] ?? null; ?>
            <?php if ($ai['available'] && $result && $result['status'] === 'classified'): ?>
            <span class="sentiment-badge sentiment-<?= escape_html($result['label']) ?>" aria-label="تحليل التعليق: <?= escape_html($result['label_ar']) ?>"><?= escape_html($result['label_ar']) ?></span>
            <?php else: ?><span class="sentiment-badge sentiment-unknown">التحليل غير متاح</span><?php endif; ?>
            </div>
        </div>
    <?php } ?>

</div>

<!-- FOOTER -->
<footer>
    <p>© 2025 Riyadh Season Guide</p>
</footer>

</body>
</html>
