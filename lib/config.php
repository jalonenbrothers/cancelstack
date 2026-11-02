<?php
declare(strict_types=1);

return [
    // --- database (required) ---
    'db' => [
        'host'    => 'csmysql',
        'port'    => 3306,
        'name'    => 'cancelstack',
        'user'    => 'qa1',
        'pass'    => 'qa1pw',
    ],

    // --- app (optional, one line) ---
    'app_url' => 'http://localhost:8719', // used for "Copy link" on the token reveal card

    // --- debug (always present) ---
    'debug' => false, // true = display errors, no .htaccess-internal blocking in logs
];