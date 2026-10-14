<?php require_once 'security.php'; require_admin(); ?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Control – Riyadh Season Guide</title>
<link rel="stylesheet" href="style_admin_control.css">
<link rel="stylesheet" href="header_footer.css">

</head>

<body>

<!-- HEADER -->
<header class="header">
    <div class="logo">Riyadh Season Guide</div>
    <nav>
        <a href="index.php">Home</a>
        <a href="about.php">About</a>
        <form method="post" action="logout.php" style="display:inline"><?= csrf_input() ?><button type="submit" style="background:none;border:0;color:white;font:inherit;cursor:pointer">Logout</button></form>
    </nav>
</header>

<div class="page-container">

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

    <!-- CONTROL BOX -->
     <h2 class="control-title">Admin Control Panel</h2>

    <section class="control-box">
        <a href="add_item.php" class="control-btn">Add Activity</a>
        <a href="delete_item.php" class="control-btn">Delete Activity</a>
        <a href="edit_item.php" class="control-btn">Edit Activity</a>
    </section>

</div>

<!-- FOOTER -->
<footer>
    <p>© 2025 Riyadh Season Guide</p>
</footer>

</body>
</html>
