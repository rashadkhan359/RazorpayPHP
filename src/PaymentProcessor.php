<?php

namespace App;

use Exception;
use Razorpay\Api\Api;

class PaymentProcessor
{
    private $api;
    private $db;

    public function __construct(array $config, $db)
    {
        $this->api = new Api($config['key_id'], $config['key_secret']);
        $this->db = $db;
    }

    public function createOrder($amount, $currency)
    {
        try {
            // Validate amount and currency before creating order
            $this->validateAmount($amount);
            $this->validateCurrency($currency);

            $order = $this->api->order->create([
                'amount' => $amount * 100, // Convert to smallest currency unit
                'currency' => $currency,
                'payment_capture' => 1,
                'notes' => [
                    'currency_original' => $currency,
                    'amount_original' => $amount
                ]
            ]);

            // Log order creation
            $this->logOrder($order->id, $amount, $currency);

            return $order;
        } catch (Exception $e) {
            $this->logError('Order Creation Error', $e->getMessage());
            throw new Exception("Error creating order: " . $e->getMessage());
        }
    }

    public function verifyPayment($paymentId, $orderId, $signature)
    {
        try {
            $attributes = [
                'razorpay_payment_id' => $paymentId,
                'razorpay_order_id' => $orderId,
                'razorpay_signature' => $signature
            ];

            $this->api->utility->verifyPaymentSignature($attributes);

            // Fetch payment details from Razorpay
            $paymentDetails = $this->api->payment->fetch($paymentId);
            return [
                'verified' => true,
                'payment_details' => [
                    'method' => $paymentDetails->method,
                    'card' => isset($paymentDetails->card) ? [
                        'network' => $paymentDetails->card->network,
                        'last4' => $paymentDetails->card->last4,
                        'issuer' => $paymentDetails->card->issuer,
                        'type' => $paymentDetails->card->type,
                        'international' => $paymentDetails->card->international
                    ] : null,
                    'bank' => $paymentDetails->bank,
                    'wallet' => $paymentDetails->wallet,
                    'vpa' => $paymentDetails->vpa
                ]
            ];
        } catch (Exception $e) {
            $this->logError('Payment Verification Error', $e->getMessage());
            return [
                'verified' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function saveTransaction($data)
    {
        try {
            // Sanitize and validate data
            $sanitizedData = $this->sanitizeTransactionData($data);

            // Insert main transaction record
            $sql = "INSERT INTO payment_transactions (
                payment_id, order_id, name, email, tel,
                address, address2, city, state, zip_code, country,
                amount, currency_type, status, payment_method,
                card_network, card_last4, card_issuer, card_type,
                bank_name, wallet_type, vpa, error_message
            ) VALUES (
                :payment_id, :order_id, :name, :email, :tel,
                :address, :address2, :city, :state, :zip_code, :country,
                :amount, :currency_type, :status, :payment_method,
                :card_network, :card_last4, :card_issuer, :card_type,
                :bank_name, :wallet_type, :vpa, :error_message
            )";

            $this->db->query($sql, $sanitizedData);

            return $this->db->getLastInsertId();
        } catch (Exception $e) {
            $this->logError('Transaction Save Error', $e->getMessage());
            throw new Exception("Error saving transaction: " . $e->getMessage());
        }
    }

    private function sanitizeTransactionData($data)
    {
        return [
            'payment_id' => strip_tags($data['payment_id']),
            'order_id' => strip_tags($data['order_id']),
            'name' => htmlspecialchars($data['name'], ENT_QUOTES, 'UTF-8'),
            'email' => filter_var($data['email'], FILTER_SANITIZE_EMAIL),
            'tel' => strip_tags($data['tel']),
            'address' => strip_tags($data['address']),
            'address2' => strip_tags($data['address2']),
            'city' => strip_tags($data['city']),
            'state' => strip_tags($data['state']),
            'zip_code' => strip_tags($data['zip_code']),
            'country' => strip_tags($data['country']),
            'amount' => filter_var($data['amount'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
            'currency_type' => strip_tags($data['currency_type']),
            'status' => strip_tags($data['status']),
            'payment_method' => strip_tags($data['payment_method']),
            'card_network' => strip_tags($data['card_network'] ?? null),
            'card_last4' => strip_tags($data['card_last4'] ?? null),
            'card_issuer' => strip_tags($data['card_issuer'] ?? null),
            'card_type' => strip_tags($data['card_type'] ?? null),
            'bank_name' => strip_tags($data['bank_name'] ?? null),
            'wallet_type' => strip_tags($data['wallet_type'] ?? null),
            'vpa' => strip_tags($data['vpa'] ?? null),
            'error_message' => strip_tags($data['error_message'] ?? null)
        ];
    }

    public function logPaymentDetails($eventType, $data)
    {
        // Log raw response for debugging
        $sql = "INSERT INTO payment_logs (
            log_type, data
        ) VALUES (
            :log_type, :data
        )";

        $this->db->query($sql, [
            'log_type' => $eventType,
            'data' => json_encode($data)
        ]);
    }


    public function logOrder($orderId, $amount, $currency)
    {
        try {
            $sql = "INSERT INTO order_logs (order_id, amount, currency)
                VALUES (:order_id, :amount, :currency)";

            $this->db->query($sql, [
                'order_id' => $orderId,
                'amount' => $amount,
                'currency' => $currency,
            ]);

        } catch (Exception $e) {
            // Just log the error but don't throw - this is non-critical
            $this->logError('Order Logging Error', $e->getMessage());
        }
    }

    public function logError($type, $message)
    {
        try {
            $sql = "INSERT INTO error_logs (error_type, error_message)
                VALUES (:error_type, :error_message)";

            $this->db->query($sql, [
                'error_type' => $type,
                'error_message' => $message,
            ]);
        } catch (Exception $e) {
            // If we can't even log the error, write to error_log
            logPaymentError("Payment System Error ($type): $message");
        }
    }

    private function validateAmount($amount)
    {
        if (!is_numeric($amount) || $amount <= 0) {
            throw new Exception("Invalid amount: Amount must be greater than 0");
        }
    }

    private function validateCurrency($currency)
    {
        $allowedCurrencies = ['INR', 'USD', 'EUR', 'GBP', 'SGD', 'AUD', 'CAD'];
        if (!in_array($currency, $allowedCurrencies)) {
            throw new Exception("Invalid currency: {$currency}");
        }
    }

    public function getTransactionDetails($paymentId)
    {
        try {
            $sql = "SELECT * FROM payment_transactions WHERE payment_id = :payment_id";
            $result = $this->db->query($sql, ['payment_id' => $paymentId]);

            if (!$result) {
                throw new Exception("Transaction not found");
            }

            // Get additional logs
            $sql = "SELECT * FROM payment_logs WHERE transaction_id = :transaction_id";
            $logs = $this->db->query($sql, ['transaction_id' => $result['id']]);

            return [
                'transaction' => $result,
                'logs' => $logs
            ];
        } catch (Exception $e) {
            $this->logError('Transaction Details Error', $e->getMessage());
            throw new Exception("Error fetching transaction details: " . $e->getMessage());
        }
    }

    public function refundPayment($paymentId, $amount = null)
    {
        try {
            $refund = $this->api->refund->create([
                'payment_id' => $paymentId,
                'amount' => $amount ? $amount * 100 : null // Optional partial refund
            ]);

            // Save refund details
            $this->saveRefund($paymentId, $refund);

            return $refund;
        } catch (Exception $e) {
            $this->logError('Refund Error', $e->getMessage());
            throw new Exception("Error processing refund: " . $e->getMessage());
        }
    }

    private function saveRefund($paymentId, $refundData)
    {
        try {
            $sql = "INSERT INTO payment_refunds (
                payment_id, refund_id, amount, status, created_at
            ) VALUES (
                :payment_id, :refund_id, :amount, :status, NOW()
            )";

            $this->db->query($sql, [
                'payment_id' => $paymentId,
                'refund_id' => $refundData->id,
                'amount' => $refundData->amount / 100,
                'status' => $refundData->status
            ]);

            // Update original transaction status
            $this->updateTransactionStatus($paymentId, 'refunded');
        } catch (Exception $e) {
            $this->logError('Refund Save Error', $e->getMessage());
            throw new Exception("Error saving refund details: " . $e->getMessage());
        }
    }

    private function updateTransactionStatus($paymentId, $status)
    {
        $sql = "UPDATE payment_transactions
                SET status = :status, updated_at = NOW()
                WHERE payment_id = :payment_id";

        $this->db->query($sql, [
            'status' => $status,
            'payment_id' => $paymentId
        ]);
    }

    public function getPaymentStatistics($startDate = null, $endDate = null)
    {
        try {
            $params = [];
            $dateCondition = "";

            if ($startDate && $endDate) {
                $dateCondition = "WHERE created_at BETWEEN :start_date AND :end_date";
                $params['start_date'] = $startDate;
                $params['end_date'] = $endDate;
            }

            $sql = "SELECT
                    COUNT(*) as total_transactions,
                    SUM(amount) as total_amount,
                    currency_type,
                    status,
                    COUNT(CASE WHEN status = 'success' THEN 1 END) as successful_transactions,
                    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_transactions,
                    AVG(CASE WHEN status = 'success' THEN amount END) as avg_transaction_amount
                FROM payment_transactions
                $dateCondition
                GROUP BY currency_type, status";

            return $this->db->query($sql, $params);
        } catch (Exception $e) {
            $this->logError('Statistics Error', $e->getMessage());
            throw new Exception("Error generating payment statistics: " . $e->getMessage());
        }
    }
}
