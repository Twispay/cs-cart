<?php
namespace Tygh\Addons\TwispayPayment;

class TwispayProcessor
{
    protected $secret_key;

    public function __construct($secret_key)
    {
        $this->secret_key = $secret_key;
    }

    public function log($message, $type = 'notice')
    {
        fn_log_event('general', $type, ['message' => $message]);
    }

    public function initiatePayment($order_info)
    {
        $this->log('Initiating payment for Order ID: ' . $order_info['order_id']);

        $postData = [
            'id' => 0,
            'siteId' => [YOUR_TWISPAY_SITE_ID],
            'customerId' => [YOUR_TWISPAY_CUSTOMER_ID],
            'externalOrderId' => $order_info['order_id'],
            'orderType' => "one-time",
            'orderStatus' => "pending",
            'amount' => $order_info['total'],
            'currency' => $order_info['secondary_currency'],
            'description' => "Payment for Order ID: " . $order_info['order_id'],
            'invoiceEmail' => $order_info['email'],
            'createdAt' => date("Y-m-d\TH:i:s.v\Z"),
            'intervalType' => "day",
            'intervalValue' => 0,
            'retryPayment' => "true",
            'nextDueDate' => "2023-11-10T05:17:55.957Z",
            'transactionMethod' => "card",
            'tags' => [
                [
                    'tag' => "e-commerce",
                    'creationDate' => date("Y-m-d\TH:i:s.v\Z"),
                    'creationTimestamp' => time()
                ]
            ]
        ];

        $ch = curl_init($this->apiUrl);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer {$this->secretKey}"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));

        $response = curl_exec($ch);

        if(curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }

        curl_close($ch);
    }

    public function validateCallback($response)
    {
        $this->log('Validating Twispay callback.');

        if (!isset($response['signature']) || !isset($response['order_id']) || !isset($response['status'])) {
            $this->log('Invalid Twispay callback: required data missing.', 'error');
            return false;
        }

        $expected_signature = hash_hmac('sha256', $response['order_id'] . $response['status'], $this->secret_key);
        
        if ($expected_signature !== $response['signature']) {
            $this->log('Invalid Twispay callback: signature mismatch.', 'error');
            return false;
        }
        
        return true;
    }
}
