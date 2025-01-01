<?php
require __DIR__ . '/vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Load configuration
$config = require __DIR__ . '/config/config.php';

// Set error reporting based on environment
if ($config['app']['environment'] === 'development') {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}

// Initialize database connection
try {
    $db = new App\Database($config['database']);
} catch (Exception $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// Initialize payment processor
$paymentProcessor = new App\PaymentProcessor($config['razorpay'], $db);

// Make objects available globally (you might want to use dependency injection in a larger application)
$GLOBALS['db'] = $db;
$GLOBALS['paymentProcessor'] = $paymentProcessor;
$GLOBALS['config'] = $config;
