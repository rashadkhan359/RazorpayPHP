<?php

return [
    'app' => [
        'environment' => $_ENV['APP_ENV'] ?? 'production',
        'name' => $_ENV['COMPANY_NAME'] ?? 'Payment System',
        'url' => $_ENV['APP_URL'] ?? 'http://localhost',
        'logo' => $ENV['COMPANY_LOGO'] ?? null,
        // 'logo' => $ENV['COMPANY_LOGO'] ?? (isset($_ENV['COMPANY_NAME'])
        //     ? "https://ui-avatars.com/api/?name=" . urlencode($_ENV['COMPANY_NAME']) . "&background=random"
        //     : "https://ui-avatars.com/api/?name=Payment%20System&background=random"),
        'address' => $_ENV['COMPANY_ADDRESS'] ?? null,
        'email' => $_ENV['COMPANY_EMAIL'] ?? null,
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
    ],
    'email' => [
        'smtp' => [
            'host' => $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com',
            'port' => $_ENV['MAIL_PORT'] ?? 587,
            'username' => $_ENV['MAIL_USERNAME'] ?? 'your-email@example.com',
            'password' => $_ENV['MAIL_PASSWORD'] ?? 'your-app-specific-password'
        ],
        'from' => [
            'email' => $_ENV['MAIL_FROM'] ?? 'noreply@yourcompany.com',
            'name' => $_ENV['COMPANY_NAME'] ?? 'Your Company Name'
        ],
        'subject_prefix' => '[' . $_ENV['COMPANY_NAME'] ?? 'Payment System' . ']'
    ]
];
