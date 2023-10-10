<?php

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

if ($mode == 'place_order') {
    // Obtain order information and perform validations
    
    $order_id = isset($_REQUEST['order_id']) ? (int) $_REQUEST['order_id'] : 0;
    $order_info = fn_get_order_info($order_id);

    if (!$order_info) {
        // Handle error: order not found
        return [CONTROLLER_STATUS_NO_PAGE];
    }

    // Payment processor parameters
    $processor_data = $order_info['payment_method'];
    $processor_params = $processor_data['processor_params'];

    // Collect required Twispay data
    
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
    
    // Initiate payment via CURL or an HTML form submission, depending on the API requirements
    // E.g., if a form submission is used:
    Tygh::$app['view']->assign('twispay_url', 'https://api.twispay.com/transaction/create');
    Tygh::$app['view']->assign('postData', $postData);
    Tygh::$app['view']->display('addons/twispay/twispay_payment.tpl');
    exit;
    
} elseif ($mode == 'process') {

    // Redirect user based on payment status, e.g., to order details page
    fn_redirect("order.detail?order_id={$order_id}");

} elseif ($mode == 'cancel') {
    // Handle payment cancellation if applicable
    fn_order_placement_routines('route', $_REQUEST['order_id']);
}
