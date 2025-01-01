<?php
require __DIR__ . '/../bootstrap.php';
header('Content-Type: application/json');


// Function to prepare transaction data
function prepareTransactionData($payment, $postData)
{
    return [
        'payment_id' => $postData['payment_id'],
        'order_id' => $postData['order_id'],
        'name' => $postData['name'],
        'email' => $postData['email'],
        'address' => $postData['address1'],
        'address2' => $postData['address2'],
        'city' => $postData['city'],
        'zip_code' => $postData['zipcode'],
        'country' => $postData['country'],
        'amount' => $postData['amount'],
        'currency_type' => $postData['currency'],
        'status' => 'success',
        'payment_method' => $payment['payment_details']['method'],
        'card_network' => $payment['payment_details']['card']['network'] ?? null,
        'card_last4' => $payment['payment_details']['card']['last4'] ?? null,
        'card_issuer' => $payment['payment_details']['card']['issuer'] ?? null,
        'card_type' => $payment['payment_details']['card']['type'] ?? null,
        'bank_name' => $payment['payment_details']['bank'] ?? null,
        'wallet_type' => $payment['payment_details']['wallet'] ?? null,
        'vpa' => $payment['payment_details']['vpa'] ?? null,
    ];
}

try {
    // Check if required POST parameters are present
    $requiredParams = ['payment_id', 'order_id', 'signature'];
    foreach ($requiredParams as $param) {
        if (!isset($_POST[$param])) {
            echo json_encode(['success' => false, 'error' => "Missing required parameter: $param"]);
            exit;
        }
    }

    // Sanitize the incoming POST data
    $paymentId = $_POST['payment_id'];
    $orderId = $_POST['order_id'];
    $signature = $_POST['signature'];

    // Verify payment using the payment processor
    $payment = $GLOBALS['paymentProcessor']->verifyPayment($paymentId, $orderId, $signature);

    if ($payment['verified']) {
        // Prepare the transaction data
        $transactionData = prepareTransactionData($payment, $_POST);

        // Save the transaction and respond
        $savedPaymentId = $GLOBALS['paymentProcessor']->saveTransaction($transactionData);

        echo json_encode(
            [
                'success' => true,
                'transaction_id' => $savedPaymentId,
                'razorpay_payment_id' => $paymentId
            ]
        );
    } else {
        echo json_encode(['success' => false, 'error' => 'Payment verification failed']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'An error occurred: ' . $e->getMessage()]);
}
