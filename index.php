<?php
declare(strict_types=1);

/**
 * CancelStack front controller. All requests route through here (.htaccess).
 * Routes:
 *   GET  /                      Home
 *   GET  /case/new              New case form
 *   GET  /about                 About & legal
 *   GET  /c/{token}             Case timeline
 *   GET  /c/{token}/export      Export page
 *   GET  /c/{token}/bundle      Print bundle (?type=bank|regulator&to=...)
 *   POST /api/case              Create case
 *   GET  /api/case/{token}      Fetch case + attempts (JSON)
 *   POST /api/case/{token}/attempt   Log an attempt
 *   PATCH /api/attempt/{id}     Edit an attempt
 *   POST /api/attempt/{id}      (alias for PATCH, some clients can't PATCH)
 */

require __DIR__ . '/lib/bootstrap.php';

$base = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
// Normalize: front controller lives at repo root, so base is '' or '/subdir'
$base = rtrim(str_replace('\\', '/', $base), '/');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
if ($base !== '' && str_starts_with($path, $base)) {
    $path = substr($path, strlen($base));
}
$path = '/' . trim($path, '/');
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// Redirect old-style index.php/ paths
$seg = explode('/', trim($path, '/'));
$seg = array_values(array_filter($seg, fn($s) => $s !== ''));

try {
    // --- API ---
    if (($seg[0] ?? '') === 'api') {
        header('Cache-Control: no-store');
        switch (true) {
            case $method === 'POST' && count($seg) === 2 && $seg[1] === 'case':
                require __DIR__ . '/src/api/create_case.php';
                return;
            case $method === 'GET' && count($seg) === 3 && $seg[1] === 'case':
                require __DIR__ . '/src/api/get_case.php';
                return;
            case $method === 'POST' && count($seg) === 4 && $seg[1] === 'case' && $seg[3] === 'attempt':
                require __DIR__ . '/src/api/add_attempt.php';
                return;
            case ($method === 'PATCH' || $method === 'POST') && count($seg) === 3 && $seg[1] === 'attempt':
                require __DIR__ . '/src/api/edit_attempt.php';
                return;
        }
        json_out(['error' => 'Not found'], 404);
    }

    // --- Pages ---
    switch (true) {
        case $path === '/' || $path === '':
            require __DIR__ . '/views/home.php';
            return;
        case $path === '/case/new':
            require __DIR__ . '/views/new_case.php';
            return;
        case $path === '/about':
            require __DIR__ . '/views/about.php';
            return;
        case count($seg) === 2 && $seg[0] === 'c':
            require __DIR__ . '/views/timeline.php';
            return;
        case count($seg) === 3 && $seg[0] === 'c' && $seg[2] === 'export':
            require __DIR__ . '/views/export.php';
            return;
        case count($seg) === 3 && $seg[0] === 'c' && $seg[2] === 'bundle':
            require __DIR__ . '/views/bundle.php';
            return;
        case $path === '/health':
            header('Content-Type: text/plain; charset=utf-8');
            echo 'ok';
            return;
    }

    http_response_code(404);
    $notFoundTokenCase = (count($seg) === 2 && $seg[0] === 'c');
    require __DIR__ . '/views/404.php';
} catch (InvalidArgumentException $e) {
    json_out(['error' => $e->getMessage()], 422);
} catch (Throwable $e) {
    if (cs_config()['debug']) {
        http_response_code(500);
        exit('Exception: ' . htmlspecialchars($e->getMessage() . "\n" . $e->getTraceAsString(), ENT_QUOTES, 'UTF-8'));
    }
    error_log('CancelStack error: ' . $e->getMessage());
    http_response_code(500);
    if (str_starts_with($path, '/api')) {
        json_out(['error' => 'Internal error. Please try again.'], 500);
    }
    exit('Something went wrong. Please try again shortly.');
}
