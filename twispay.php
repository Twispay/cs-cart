<?php

use Tygh\Registry;
use Tygh\Addons\TwispayPayment\TwispayProcessor;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'place_order') {
    $processor = new TwispayProcessor();
    $processor->log("Place order action initiated.");
    
    $order_id = $_REQUEST['order_id'];
    $order_info = fn_get_order_info($order_id);
    
    // You may perform validations like checking if order_info is not empty, etc.
    
    if ($processor->initiatePayment($order_info)) {
        // If payment initiation is successful, you might want to redirect to a success page or Twispay payment page.
        $processor->log("Payment initiation successful for Order ID: {$order_id}");
    } else {
        // Handle payment initiation failure.
        $processor->log("Payment initiation failed for Order ID: {$order_id}", 'error');
    }

} elseif ($mode == 'callback') {
    $processor = new TwispayProcessor();
    $processor->log("Callback action initiated.");
    
    // Generally, payment gateways post transaction data to callback URL.
    $response = $_POST;

    // Validate the callback response.
    if ($processor->validateCallback($response)) {
        // Handle successful payment completion like updating order status, sending confirmation email etc.
        $processor->log("Payment completed successfully through Twispay.");
    } else {
        // Handle failure like logging the error, sending alerts etc.
        $processor->log("Payment failed through Twispay callback.", 'error');
    }
}
