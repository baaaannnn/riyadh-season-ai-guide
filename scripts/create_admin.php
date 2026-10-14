<?php
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
require __DIR__ . '/../web/connect.php';
echo "New admin username: ";
$username = trim(fgets(STDIN));
echo "New admin password (input is visible in this terminal): ";
$password = rtrim(fgets(STDIN), "\r\n");
if ($username === '' || strlen($username) > 100 || strlen($password) < 8) {
    exit("Use a username and a password of at least 8 characters.\n");
}
$hash = password_hash($password, PASSWORD_DEFAULT);
$statement = $conn->prepare('INSERT INTO admins (username, password) VALUES (?, ?)');
$statement->bind_param('ss', $username, $hash);
try { $statement->execute(); echo "Admin created. No plaintext password stored.\n"; }
catch (mysqli_sql_exception $exception) { exit("Could not create admin; check the database or use another username.\n"); }
