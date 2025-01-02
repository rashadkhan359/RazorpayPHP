<?php
require __DIR__ . '/../bootstrap.php';
header('Content-Type: application/json');

try {
    // Define validation rules
    $rules = [
        'amount' => ['required', 'numeric', 'min:0.01'],
        'currency' => ['required', 'string', 'in:INR,AUD,USD,GBP,CAD,EUR,SGD'],
        'name' => ['required', 'string', 'min:2'],
        'email' => ['required', 'email'],
        'address1' => ['required', 'string'],
        'country' => ['required', 'string'],
        'zipcode' => ['required', 'string'],
        'state' => ['required', 'string'],
        'city' => ['required', 'string']
    ];

    $validator = validate($_POST, $rules);

    if (!$validator->validate()) {
        $errors = $validator->errors();
        // Get the first error message
        $firstError = reset($errors)[0];
        throw new Exception($firstError);
    }

    // Get sanitized data
    $input = $validator->sanitized();

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
