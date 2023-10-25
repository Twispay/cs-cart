<?php
namespace Tygh\Addons\TwispayPayment;

class TwispayProcessor
{
    protected $secret_key;
    protected $checkoutUrl = 'https://secure.twispay.com'; // URL of Twispay's payment page


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

        // Prepare the data that will be sent
        $postData = [
            'id' => 0, // this should be replaced with the actual order ID from your system
            'siteId' => $order_info['siteId'], // assuming 'siteId' is included in $order_info
            'customerId' => $order_info['customerId'], // assuming 'customerId' is included in $order_info
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
            'nextDueDate' => date("Y-m-d\TH:i:s.v\Z", strtotime('+1 month')), // assuming a monthly subscription
            'transactionMethod' => "card",
            'tags' => [
                [
                    'tag' => "e-commerce",
                    'creationDate' => date("Y-m-d\TH:i:s.v\Z"),
                    'creationTimestamp' => time()
                ]
            ]
        ];

        // Create an auto-submit form with the data as hidden fields
        echo '<html><body>';
        echo '<form id="twispay_payment_form" action="' . $this->checkoutUrl . '" method="post">';

        foreach ($postData as $key => $value) {
            // If the value is an array, we need to handle it a bit differently
            if (is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    // This assumes that sub-arrays are one-level deep
                    if (is_array($subValue)) {
                        foreach ($subValue as $lastKey => $lastValue) {
                            echo '<input type="hidden" name="'. htmlspecialchars($key).'['. htmlspecialchars($subKey).']['. htmlspecialchars($lastKey) .']" value="'. htmlspecialchars($lastValue) .'">';
                        }
                    } else {
                        echo '<input type="hidden" name="'. htmlspecialchars($key).'['. htmlspecialchars($subKey) .']" value="'. htmlspecialchars($subValue) .'">';
                    }
                }
            } else {
                echo '<input type="hidden" name="'. htmlspecialchars($key) .'" value="'. htmlspecialchars($value) .'">';
            }
        }

        echo '</form>';
        echo '<script type="text/javascript">document.getElementById("twispay_payment_form").submit();</script>';
        echo '</body></html>';

        // The script will auto-submit the form, redirecting the customer to the payment page.
        exit; // Important to prevent further page processing
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
