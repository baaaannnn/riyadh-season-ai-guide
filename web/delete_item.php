<?php
include 'connect.php';
require_admin();

$message = "";

// عند تنفيذ الحذف
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    check_csrf();
    $event_id = filter_var($_POST['event_id'] ?? null, FILTER_VALIDATE_INT);
    if ($event_id && $event_id > 0) {
        $conn->begin_transaction();
        try {
            $statement = $conn->prepare('DELETE FROM reviews WHERE item_id = ?');
            $statement->bind_param('i', $event_id); $statement->execute();
            $statement = $conn->prepare('DELETE FROM items WHERE id = ?');
            $statement->bind_param('i', $event_id); $statement->execute();
            $message = $statement->affected_rows ? 'Activity deleted successfully' : 'Activity not found.';
            $conn->commit();
        } catch (mysqli_sql_exception $exception) { $conn->rollback(); $message = 'Could not delete activity.'; }
    } else { $message = 'Select a valid activity.'; }
}

// جلب أسماء الفعاليات
$events = mysqli_query($conn, "SELECT id, name FROM items");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Delete Activity – Riyadh Season Guide</title>
<link rel="stylesheet" href="style_delete_item1.css">
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

    <!-- HERO SECTION -->
    <section class="hero-section-delete">

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

    <!-- TITLE -->
    <h1 class="main-title">Delete Activity</h1>

    <!-- DELETE BOX -->
    <div class="delete-box">

        <?php if ($message != "") { ?>
            <p class="success-msg"><?= escape_html($message) ?></p>
        <?php } ?>

        <form method="POST">
<?= csrf_input() ?>

            <label>Select Activity</label>
            <select name="event_id" class="delete-select" required>
                <option value="">Select activity</option>

                <?php while ($row = mysqli_fetch_assoc($events)) { ?>
                    <option value="<?= $row['id'] ?>"><?= escape_html($row['name']) ?></option>
                <?php } ?>

            </select>

            <button type="submit" class="delete-btn">Delete</button>

        </form>

    </div>

</div>

<!-- FOOTER -->
<footer>
    <p>© 2025 Riyadh Season Guide</p>
</footer>

</body>
</html>
