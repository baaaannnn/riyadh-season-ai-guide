<?php
include 'connect.php';
require_admin();

// جلب الفعاليات
$items = mysqli_query($conn, "SELECT * FROM items");

// جلب الكاتيجوري
$categories = mysqli_query($conn, "SELECT * FROM category");

// متغيرات
$selected_id = "";
$name = "";
$description = "";
$image = "";
$category_id = "";
$success = "";

function find_item($conn, int $id) {
    $statement = $conn->prepare('SELECT * FROM items WHERE id = ?');
    $statement->bind_param('i', $id); $statement->execute();
    return $statement->get_result()->fetch_assoc();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    try {
        $id = filter_var($_POST['item_id'] ?? $_POST['event_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || $id < 1 || !($data = find_item($conn, $id))) throw new RuntimeException('Activity not found.');
        if (isset($_POST['save_changes'])) {
            $newName = input_text('name', 255);
            $desc = input_text('description', 10000);
            $newCategory = filter_var($_POST['category_id'] ?? null, FILTER_VALIDATE_INT);
            $statement = $conn->prepare('SELECT id FROM category WHERE id = ?');
            $statement->bind_param('i', $newCategory); $statement->execute();
            if (!$statement->get_result()->fetch_assoc()) throw new RuntimeException('Choose a valid category.');
            $newImage = $data['image'];
            if (isset($_FILES['new_image']) && $_FILES['new_image']['error'] !== UPLOAD_ERR_NO_FILE) $newImage = store_image('new_image');
            $statement = $conn->prepare('UPDATE items SET name = ?, description = ?, image = ?, category_id = ? WHERE id = ?');
            $statement->bind_param('sssii', $newName, $desc, $newImage, $newCategory, $id); $statement->execute();
            $success = 'Changes saved successfully!';
            $data = find_item($conn, $id);
        }
        $selected_id = $data['id']; $name = $data['name'];
        $description = $data['description']; $image = $data['image']; $category_id = $data['category_id'];
    } catch (RuntimeException $exception) { $success = 'Could not load or edit activity. Check the form and image.'; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Edit Activity – Riyadh Season Guide</title>
<link rel="stylesheet" href="style_edititem1.css">
<link rel="stylesheet" href="header_footer.css">
</head>
<body>

<!-- HEADER -->
<header class="header">
    <div class="logo">Riyadh Season Guide</div>
    <nav>
        <a href="index.php">Home</a>
        <a href="admin_control.php">Back</a>
        <form method="post" action="logout.php" style="display:inline">
<?= csrf_input() ?><button type="submit" style="background:none;border:0;color:white;font:inherit;cursor:pointer">Logout</button></form>
    </nav>
</header>

<div class="page-container">

<!-- HERO -->
<section class="hero-section-edit">
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

<h1 class="main-title">Edit Activity</h1>

<div class="container">

<?php if ($success != "") echo "<p class='success'>$success</p>"; ?>

<!-- اختيار الفعالية -->
<form method="POST" class="box">
<?= csrf_input() ?>
    <label>Select Activity</label>
    <select name="event_id" class="input" required>
        <option disabled selected>Select activity</option>
        <?php while ($row = mysqli_fetch_assoc($items)) { ?>
            <option value="<?= $row['id']; ?>"
                <?= ($selected_id == $row['id']) ? "selected" : "" ?>>
                <?= escape_html($row['name']); ?>
            </option>
        <?php } ?>
    </select>
    <button class="btn" name="select_event">Load</button>
</form>

<!-- تعديل -->
<?php if ($selected_id != "") { ?>
<form method="POST" enctype="multipart/form-data" class="box">
<?= csrf_input() ?>

    <input type="hidden" name="item_id" value="<?= $selected_id ?>">

    <label>Name</label>
    <input type="text" name="name" value="<?= escape_html($name) ?>" class="input" required>

    <label>Description</label>
    <textarea name="description" class="input" required><?= escape_html($description) ?></textarea>

    <label>Category</label>
    <select name="category_id" class="input" required>
        <?php while ($cat = mysqli_fetch_assoc($categories)) { ?>
            <option value="<?= $cat['id']; ?>"
                <?= ($category_id == $cat['id']) ? 'selected' : '' ?>> 
                <?= escape_html($cat['name']); ?>
            </option>
        <?php } ?>
    </select>

    <label>Current Image</label><br>
    <img src="images/<?= escape_html($image) ?>" class="current-logo"><br><br>

    <label>Upload New Image</label>
    <input type="file" name="new_image" class="input">

    <button class="btn" name="save_changes">Save Changes</button>

</form>
<?php } ?>

</div>
</div>

<!-- FOOTER -->
<footer>
    <p>© 2025 Riyadh Season Guide</p>
</footer>

</body>
</html>
