<?php

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'ipn') {
    // Read the IPN data sent from Twispay
    $ipnData = $_POST;

    if (!empty($ipnData)) {
        $processor_data = fn_get_processor_data_by_name('twispay.php');
        $twispayProcessor = new TwispayProcessor($processor_data['processor_params']['secret_key']);

        if ($twispayProcessor->validateCallback($ipnData)) {
            $order_id = $ipnData['externalOrderId']; // Assuming Twispay sends back the external order ID
            $order_info = fn_get_order_info($order_id);

            if ($order_info) {
                switch ($ipnData['status']) {
                    case 'PENDING':
                        // Handle pending payment status
                        fn_change_order_status($order_id, 'O'); // 'O' for Open
                        break;

                    case 'PROCESSING':
                        // Handle processing payment status
                        fn_change_order_status($order_id, 'O'); // 'O' for Open, or another appropriate status
                        break;

                    case 'REJECTED':
                        // Handle rejected payment status
                        fn_change_order_status($order_id, 'D'); // 'D' for Declined
                        break;

                    case 'FAILED':
                        // Handle failed payment status
                        fn_change_order_status($order_id, 'F'); // 'F' for Failed
                        break;

                    // Add more cases as necessary
                    
                    default:
                        // Handle other unexpected statuses
                        $twispayProcessor->log("Received unknown status in IPN: " . $ipnData['status'], 'error');
                        break;
                }
            } else {
                $twispayProcessor->log("Order not found for IPN with Order ID: $order_id", 'error');
            }
        } else {
            $twispayProcessor->log("Invalid IPN received", 'error');
        }
    } else {
        $twispayProcessor->log("Empty IPN received", 'error');
    }
    
    exit; // Important to prevent CS-Cart from further processing
}