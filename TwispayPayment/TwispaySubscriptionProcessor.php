<?php

class SubscriptionProcessor
{
    private $apiUrl;
    private $secretKey;
    private $siteId;

    public function __construct($secretKey, $siteId)
    {
        $this->apiUrl = "https://api.twispay.com/order";
        $this->secretKey = $secretKey;
        $this->siteId = $siteId;
    }

    public function initiateSubscription($subscriptionData)
    {
        $ch = curl_init($this->apiUrl);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Content-Type: application/json",
            "Authorization: Bearer {$this->secretKey}"
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($subscriptionData));

        $response = curl_exec($ch);

        if(curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }

        curl_close($ch);

        return json_decode($response, true);
    }
}