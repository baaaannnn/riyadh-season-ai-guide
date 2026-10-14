<?php
include "connect.php";
require_admin();

$categories_q = mysqli_query($conn, "SELECT * FROM category");

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    check_csrf();
    try {
        $name = input_text('name', 255);
        $desc = input_text('description', 10000);
        $category_id = filter_var($_POST['category_id'] ?? null, FILTER_VALIDATE_INT);
        $valid = $conn->prepare('SELECT id FROM category WHERE id = ?');
        $valid->bind_param('i', $category_id); $valid->execute();
        if (!$valid->get_result()->fetch_assoc()) throw new RuntimeException('Choose a valid category.');
        $imgName = store_image('logo');
        $statement = $conn->prepare('INSERT INTO items (name, description, image, rating, category_id) VALUES (?, ?, ?, 0, ?)');
        $statement->bind_param('sssi', $name, $desc, $imgName, $category_id);
        $statement->execute();
        $success = 'Activity added successfully!';
    } catch (RuntimeException $exception) { $error = 'Could not add activity. Check the form and upload a JPG or PNG up to 5 MB.'; }

}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Add Activity – Riyadh Season Guide</title>
<link rel="stylesheet" href="style_add_item1.css">
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


<!-- HERO SECTION -->
<section class="hero-section-add">

    <!-- LEFT TEXT -->
    <div class="hero-left">
        <h1>
            <span class="green">Riyadh</span><br>
            <span class="blue">season</span><br>
            <span class="white">guide</span>
        </h1>
    </div>

    <!-- RIGHT LOGO -->
    <div class="hero-right">
        <img src="images/logo.png" class="hero-logo">
    </div>

</section>


<!-- PAGE TITLE -->
<h1 class="add-title">Add New Activity</h1>

<!-- FORM BOX -->
<div class="form-box">

<?php 
if ($success != "") echo "<p class='success'>$success</p>";
if ($error != "") echo "<p class='error'>$error</p>";
?>

<form method="POST" enctype="multipart/form-data">
<?= csrf_input() ?>

    <label>Name</label>
    <input type="text" name="name" required>

    <label>Description</label>
    <textarea name="description" required></textarea>
    

    <label>Category</label>
    <select name="category_id" required>
        <option value="">Select category</option>
        <?php while ($cat = mysqli_fetch_assoc($categories_q)) { ?>
            <option value="<?= $cat['id'] ?>">
                <?= escape_html($cat['name']) ?>
            </option>
        <?php } ?>
    </select>


    <label>Upload image</label>
    <input type="file" name="logo" required>

    <button type="submit" class="btn">Add Activity</button>

</form>

</div>

<!-- FOOTER -->
<footer>
    <p>© 2025 Riyadh Season Guide</p>
</footer>

</body>
</html>
