<?php
require __DIR__ . '/../bootstrap.php';
header('Content-Type: application/json');

try {
    // Validate the request
    if (!isset($_POST['event_type'])) {
        throw new Exception('Event type is required');
    }

    $allowedEvents = [
        'payment_success',
        'payment_error',
        'payment_failed',
        'modal_closed'
    ];

    if (!in_array($_POST['event_type'], $allowedEvents)) {
        throw new Exception('Invalid event type');
    }

    // Helper function to safely sanitize potentially null values
    function sanitizeInput($value) {
        return $value !== null ? strip_tags($value) : null;
    }

    // Sanitize input data
    $eventData = [
        'event_type' => sanitizeInput($_POST['event_type']),
        'payment_id' => isset($_POST['payment_id']) ? sanitizeInput($_POST['payment_id']) : null,
        'order_id' => isset($_POST['order_id']) ? sanitizeInput($_POST['order_id']) : null,
        'amount' => isset($_POST['amount']) ?
            filter_var($_POST['amount'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : null,
        'currency' => isset($_POST['currency']) ? sanitizeInput($_POST['currency']) : null,
        'error_code' => isset($_POST['error_code']) ? sanitizeInput($_POST['error_code']) : null,
        'error_description' => isset($_POST['error_message']) ? sanitizeInput($_POST['error_message']) : null,
    ];

    // Remove null values before logging
    $filteredData = array_filter($eventData, function($value) {
        return $value !== null;
    });

    $GLOBALS['paymentProcessor']->logPaymentDetails(
        $eventData['event_type'],
        $filteredData
    );

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
