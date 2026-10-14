<?php
/**
 * Bootstrap Application
 * TAVIS – Traffic Awareness Vaccine Intelligent System
 */

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('CONFIG_PATH', BASE_PATH . '/config');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('PUBLIC_PATH', BASE_PATH . '/public');
define('VIEW_PATH', APP_PATH . '/Views');

$autoload = BASE_PATH . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
} else {
    spl_autoload_register(function (string $class): void {
        $prefix = 'App\\';
        if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
            return;
        }
        $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
        $file = APP_PATH . '/' . $relative . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    });
    require_once APP_PATH . '/Helpers/helpers.php';
    if (is_file(APP_PATH . '/Helpers/datatable.php')) {
        require_once APP_PATH . '/Helpers/datatable.php';
    }
}

$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value, " \t\"'");
        $_ENV[$name] = $value;
        putenv("{$name}={$value}");
    }
}

$config = require CONFIG_PATH . '/app.php';
date_default_timezone_set($config['timezone']);

// Only the actual request. APP_URL=https must not force HSTS on plain HTTP,
// or browsers lock onto HTTPS while the origin certificate is still invalid.
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ((int) ($_SERVER['SERVER_PORT'] ?? 0) === 443)
    || strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https';

$requestPath = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '');
$isGdriveMedia = str_contains($requestPath, '/media/gdrive/');

$logDir = STORAGE_PATH . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
ini_set('log_errors', '1');
ini_set('error_log', $logDir . '/php-error.log');
if (!empty($config['debug'])) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
}
ini_set('expose_php', '0');

if (!$isGdriveMedia && !headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    if ($isHttps) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    }
}

if (session_status() === PHP_SESSION_NONE && !$isGdriveMedia) {
    $lifetime = (int) ($config['session_lifetime'] ?? 120) * 60;
    session_name('TAVIS_SESSION');
    session_set_cookie_params([
        'lifetime' => $lifetime > 0 ? $lifetime : 0,
        'path' => '/',
        'secure' => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    if ($isHttps) {
        ini_set('session.cookie_secure', '1');
    }
    session_start();
}

if (!$isGdriveMedia && !empty($config['maintenance_mode']) && empty($_SESSION['user_id'])) {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (!str_contains($uri, '/login') && !str_contains($uri, '/assets')) {
        http_response_code(503);
        require VIEW_PATH . '/errors/maintenance.php';
        exit;
    }
}

return $config;
