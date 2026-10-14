<?php
require_once __DIR__ . '/security.php';
$local = __DIR__ . '/../config/local.php';
$settings = is_file($local) ? require $local : [];
$settings = is_array($settings) ? $settings : [];
function db_setting(string $key, $fallback) {
    global $settings;
    $environment = getenv($key);
    return $environment !== false ? $environment : ($settings[$key] ?? $fallback);
}
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $conn = mysqli_connect(db_setting('DB_HOST', 'localhost'), db_setting('DB_USER', 'root'),
        db_setting('DB_PASSWORD', ''), db_setting('DB_NAME', 'riyadh_season_db'),
        (int)db_setting('DB_PORT', 3306), db_setting('DB_SOCKET', null));
    mysqli_set_charset($conn, 'utf8mb4');
} catch (mysqli_sql_exception $exception) {
    http_response_code(503);
    exit('Database unavailable. Check config/local.php and the database setup in README.md.');
}
