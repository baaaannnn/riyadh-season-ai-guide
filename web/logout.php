<?php
require_once 'security.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Use the logout button.'); }
check_csrf();
$_SESSION = [];
session_destroy();
header('Location: index.php');
exit;
