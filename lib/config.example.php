<?php
declare(strict_types=1);

return [
    // --- database (required) ---
    'db' => [
        'host'    => 'localhost',   // Hostinger preset; rarely changes
        'port'    => 3306,
        'name'    => 'u123456789_cancelstack',
        'user'    => 'u123456789_csuser',
        'pass'    => 'CHANGE_ME',
    ],

    // --- app (optional, one line) ---
    'app_url' => 'https://myapp5mvp.earth-turtle.com', // used for "Copy link" on the token reveal card

    // --- debug (always present) ---
    'debug' => false, // true = display errors, no .htaccess-internal blocking in logs
];
