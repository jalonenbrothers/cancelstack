<?php
declare(strict_types=1);

/**
 * CancelStack — shared bootstrap: config, DB, escaping helpers.
 * Zero dependencies. PHP 8+, MySQL via PDO.
 */

// minimal autoloader: CancelStack\X -> src/X.php
spl_autoload_register(function (string $class): void {
    if (str_starts_with($class, 'CancelStack\\')) {
        $file = __DIR__ . '/../src/' . substr($class, strlen('CancelStack\\')) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

// shared page shell used by all views
require_once __DIR__ . '/../views/layout.php';

function cs_config(): array
{
    static $cfg = null;
    if ($cfg === null) {
        $path = __DIR__ . '/config.php';
        if (!is_file($path)) {
            http_response_code(500);
            exit("Setup required: copy lib/config.example.php to lib/config.php and fill in your database credentials.");
        }
        $cfg = require $path;
    }
    return $cfg;
}

function cs_db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $c = cs_config()['db'];
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'], $c['port'], $c['name']);
        try {
            $pdo = new PDO($dsn, $c['user'], $c['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $e) {
            if (cs_config()['debug']) {
                http_response_code(500);
                exit('DB connection failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
            }
            error_log('CancelStack DB error: ' . $e->getMessage());
            http_response_code(500);
            exit('Service temporarily unavailable. Please try again shortly.');
        }
    }
    return $pdo;
}

/** Escape for HTML output. */
function e(?string $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/** JSON response helper. */
function json_out(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

/** Read JSON request body (POST/PATCH). */
function json_in(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '', true);
    return is_array($data) ? $data : [];
}

/** App base URL for links (no trailing slash). */
function app_url(): string
{
    $cfg = cs_config();
    if (!empty($cfg['app_url'])) {
        return rtrim($cfg['app_url'], '/');
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    $scheme = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host;
}
