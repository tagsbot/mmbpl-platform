<?php
declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

$configFile = APP_ROOT . '/app/config.php';
if (!is_file($configFile)) {
    $b = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
    header('Location: ' . $b . '/install/');
    exit;
}
$CONFIG = require $configFile;

date_default_timezone_set($CONFIG['timezone'] ?? 'Asia/Karachi');
error_reporting(E_ALL);
ini_set('display_errors', !empty($CONFIG['debug']) ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', APP_ROOT . '/app/storage/error.log');

require APP_ROOT . '/app/lib/core.php';
require APP_ROOT . '/app/lib/auth.php';
require APP_ROOT . '/app/lib/library.php';
require APP_ROOT . '/app/lib/icons.php';

$secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
session_name('mmbpl_sid');
session_set_cookie_params(['lifetime' => 0, 'path' => base_path() ?: '/', 'secure' => $secure, 'httponly' => true, 'samesite' => 'Lax']);
if (PHP_SAPI !== 'cli') session_start();

header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

set_exception_handler(function (Throwable $e) {
    error_log('[MMBPL] ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    if (strpos(current_path(), '/api/') === 0) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'server_error', 'message' => config('debug') ? $e->getMessage() : 'Server error']);
        return;
    }
    echo '<!doctype html><meta charset="utf-8"><title>Error</title><div style="font-family:sans-serif;max-width:560px;margin:80px auto;text-align:center">'
        . '<h1>Something went wrong</h1><p>Please try again in a moment.</p>'
        . (config('debug') ? '<pre style="text-align:left;white-space:pre-wrap">' . htmlspecialchars($e->getMessage() . "\n" . $e->getTraceAsString()) . '</pre>' : '')
        . '</div>';
});
