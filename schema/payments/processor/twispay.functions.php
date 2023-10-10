<?php

use Tygh\Registry;

if (!defined('BOOTSTRAP')) { die('Access denied'); }

/**
 * Handle Twispay IPN callback and update order status accordingly.
 *
 * @param array $order_info Order information.
 * @param array $processor_data Payment processor configuration.
 * @param array $callback_data Data received from Twispay.
 *
 * @return void
 */
function fn_twispay_process_ipn_callback($order_info, $processor_data, $callback_data)
{
    // Ensure that the callback data is not empty
    if (empty($callback_data)) {
        fn_log_event('general', 'runtime', [
            'message' => 'Twispay IPN callback received, but callback data is empty.',
        ]);
        return;
    }

    // Extract the processor parameters for easier usage
    $processor_params = $processor_data['processor_params'];

    // Perform validations such as checking the status and verifying the signature.
    // Note: Implement signature verification based on Twispay’s documentation.
    if (!fn_twispay_is_valid_signature($callback_data, $processor_params['secret_key'])) {
        fn_log_event('general', 'runtime', [
            'message' => 'Twispay IPN callback signature validation failed.',
            'content' => $callback_data,
        ]);
        return;
    }

    // Check the payment status from the callback data
    $payment_status = isset($callback_data['status']) ? $callback_data['status'] : '';

    // Update order status based on payment status
    switch ($payment_status) {
        case 'complete':
            fn_change_order_status($order_info['order_id'], 'P', '', true);
            break;
        case 'cancelled':
            fn_change_order_status($order_info['order_id'], 'F', '', true);
            break;
        // Implement additional cases based on Twispay's payment statuses.
        default:
            fn_log_event('general', 'runtime', [
                'message' => 'Twispay IPN callback received with unexpected payment status.',
                'content' => $callback_data,
            ]);
            break;
    }
}

/**
 * Validate the signature of callback data.
 *
 * @param array $callback_data Data received from Twispay.
 * @param string $secret_key The secret key to verify the signature.
 *
 * @return bool Whether the signature is valid or not.
 */
function fn_twispay_is_valid_signature($callback_data, $secret_key)
    { if (!isset($callback_data['signature'])) {
        return false;
    }

    // Ensure the secret key is not empty.
    if (empty($secret_key)) {
        return false;
    }

    // Typically, the payload data (minus the signature) is used to validate the signature.
    // Extract the received signature from the callback data.
    $received_signature = $callback_data['signature'];

    // Remove the signature element from the data that will be used to generate our own signature.
    unset($callback_data['signature']);

    // Generate an expected signature using the same method that was used by Twispay to generate the received signature.
    // The actual method should be based on Twispay’s API documentation.
    // Example: using HMAC SHA256 algorithm.
    $expected_signature = hash_hmac('sha256', json_encode($callback_data), $secret_key);

    // Use a time-constant comparison to prevent timing attacks.
    return hash_equals($expected_signature, $received_signature);

}
