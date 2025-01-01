<?php
require __DIR__ . '/../bootstrap.php';
header('Content-Type: application/json');

try {
    // Validate input
    $requiredFields = ['amount', 'currency', 'name', 'email', 'address1', 'country', 'zipcode', 'state', 'city'];
    $input = [];

    foreach ($requiredFields as $field) {
        if (!isset($_POST[$field]) || empty(trim($_POST[$field]))) {
            throw new Exception("Missing required field: $field");
        }
        $input[$field] = trim($_POST[$field]);
    }

    // Validate amount
    if (!is_numeric($input['amount']) || $input['amount'] <= 0) {
        throw new Exception("Invalid amount");
    }

    // Validate email
    if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
        throw new Exception("Invalid email format");
    }

    // Validate currency
    $allowedCurrencies = ['INR', 'AUD', 'USD', 'GBP', 'CAD', 'EUR', 'SGD'];
    if (!in_array($input['currency'], $allowedCurrencies)) {
        throw new Exception("Invalid currency");
    }

    // Create order using global PaymentProcessor instance
    $order = $GLOBALS['paymentProcessor']->createOrder(
        $input['amount'],
        $input['currency']
    );

    // Return successful response
    echo json_encode([
        'success' => true,
        'order_id' => $order->id,
        'amount' => $input['amount'] * 100, // Convert to smallest currency unit
        'currency' => $input['currency'],
        'key' => $GLOBALS['config']['razorpay']['key_id']
    ]);

} catch (Exception $e) {
    // Log error
    $GLOBALS['paymentProcessor']->logError('Order Creation', $e->getMessage());

    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
