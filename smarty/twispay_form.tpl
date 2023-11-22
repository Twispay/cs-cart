<form method="post" action="{$payment_action}" name="payment_form">
    <input type="hidden" name="order_id" value="{$order_id}" />
    <input type="hidden" name="amount" value="{$amount}" />
    {/* Additional fields as required by Twispay API */}
        <noscript>
            <input type="submit" name="submit" value="{__("twispay_payment_submit")}" />
        </noscript>
</form>
