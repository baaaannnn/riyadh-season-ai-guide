<?php
include 'connect.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    check_csrf();
    $user = is_string($_POST['username'] ?? null) ? trim($_POST['username']) : '';
    $pass = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
    $statement = $conn->prepare("SELECT id, password FROM admins WHERE username = ? LIMIT 1");
    $statement->bind_param('s', $user); $statement->execute();
    $admin = $statement->get_result()->fetch_assoc();
    if ($admin && password_verify($pass, $admin['password'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = (int)$admin['id'];
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
        header("Location: admin_control.php"); exit;
    }
    $error = "Incorrect username or password!";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Login – Riyadh Season Guide</title>
<link rel="stylesheet" href="style_login1.css">
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


    <!-- LOGIN TITLE -->
    <h1 class="login-title">Admin Login</h1>

    <!-- LOGIN BOX -->
    <form method="POST" class="login-box">
<?= csrf_input() ?>
        <?php if ($error != "") { echo "<p class='error-msg'>$error</p>"; } ?>

        <input type="text" name="username" placeholder="Username" class="login-input">
        <input type="password" name="password" placeholder="Password" class="login-input">

        <button type="submit" class="login-btn">Login</button>
    </form>

</div>

<!-- FOOTER -->
<footer>
    <p>© 2025 Riyadh Season Guide</p>
</footer>

</body>
</html>
