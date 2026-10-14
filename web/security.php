<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
    session_start();
}
function escape_html($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function require_admin(): void {
    if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
}
function csrf_input(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return '<input type="hidden" name="csrf" value="' . escape_html($_SESSION['csrf']) . '">';
}
function check_csrf(): void {
    if (!is_string($_POST['csrf'] ?? null) || empty($_SESSION['csrf']) ||
        !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
        http_response_code(403); exit('Request expired. Reload the page and try again.');
    }
}
function input_text(string $key, int $max): string {
    $text = $_POST[$key] ?? '';
    if (!is_string($text)) throw new RuntimeException('Invalid text.');
    $text = trim($text);
    if ($text === '' || preg_match_all('/./us', $text) > $max) throw new RuntimeException('Invalid text length.');
    return $text;
}
function store_image(string $field): string {
    $file = $_FILES[$field] ?? null;
    if (!is_array($file) || $file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('Upload a JPG or PNG image up to 5 MB.');
    }
    $info = @getimagesize($file['tmp_name']);
    $extensions = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png'];
    if (!$info || !isset($extensions[$info[2]])) throw new RuntimeException('Only JPG and PNG images are accepted.');
    $name = bin2hex(random_bytes(16)) . '.' . $extensions[$info[2]];
    if (!move_uploaded_file($file['tmp_name'], __DIR__ . '/images/' . $name)) throw new RuntimeException('Image upload failed.');
    return $name;
}
