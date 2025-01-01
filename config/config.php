<?php

return [
    'app' => [
        'environment' => $_ENV['APP_ENV'] ?? 'production',
        'name' => $_ENV['COMPANY_NAME'] ?? 'Payment System',
        'url' => $_ENV['APP_URL'] ?? 'http://localhost',
    ],
    'database' => [
        'host' => $_ENV['DB_HOST'],
        'port' => $_ENV['DB_PORT'],
        'dbname' => $_ENV['DB_NAME'],
        'username' => $_ENV['DB_USER'],
        'password' => $_ENV['DB_PASS'],
        'charset' => 'utf8mb4',
    ],
    'razorpay' => [
        'key_id' => $_ENV['RAZORPAY_KEY_ID'],
        'key_secret' => $_ENV['RAZORPAY_KEY_SECRET'],
    ]
];
