<?php

namespace App\Controllers;

use App\Core\Validator;
use App\Services\PaymentProcessor;
use Exception;

class PaymentController
{
    public function verifyPayment()
    {
        try {
            $rules = [
                'payment_id' => ['required', 'numeric'],
                'order_id' => ['required', 'string'],
                'signature' => ['required', 'string'],
            ];

            $validator = new Validator($_POST, $rules);

            if (!$validator->validate()) {
                $errors = $validator->errors();
                $firstError = reset($errors)[0];
                throw new Exception($firstError);
            }

            $input = $validator->sanitized();

            // Verify payment using the payment processor
            $payment = (new PaymentProcessor)->verifyPayment(
                $input['payment_id'],
                $input['order_id'],
                $input['signature']
            );

            if ($payment['verified']) {
                // Prepare the transaction data
                $transactionData = prepareTransactionData($payment, $_POST);

                // Save the transaction and respond
                $savedPaymentId = (new PaymentProcessor)->saveTransaction($transactionData);

                echo json_encode(
                    [
                        'success' => true,
                        'transaction_id' => $savedPaymentId,
                        'razorpay_payment_id' => $input['payment_id']
                    ]
                );
            } else {
                echo json_encode(['success' => false, 'error' => 'Payment verification failed']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'error' => 'An error occurred: ' . $e->getMessage()]);
        }
    }

    public function success()
    {
        $rules = [
            'payment_id' => ['required', 'numeric']
        ];
        $validator = new Validator($_GET, $rules);

        if (!$validator->validate()) {
            header("Location: " . $GLOBALS['config']->get('app')['url']);
            exit;
        }

        $input = $validator->sanitized();

        $transactionData = (new PaymentProcessor)->getTransactionDetails($input['payment_id']);

        if (!$transactionData) {
            header("Location: " . $GLOBALS['config']->get('app')['url']);
            exit;
        }
    }
}
