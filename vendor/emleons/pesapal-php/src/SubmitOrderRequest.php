<?php 

namespace Emleons\PesapalPhp;

class SubmitOrderRequest
{
    private $baseUrl;
    private $api_path;
    protected $tokens;

    public function __construct($baseUrl, $tokens, $isSandbox = true)
    {
        $this->baseUrl = $baseUrl;
        $this->api_path = '/api/Transactions/SubmitOrderRequest';
        $this->tokens = $tokens;
    }

    public function getApiPath()
    {
        return $this->api_path;
    }

    public function getApiUrl()
    {
        return $this->baseUrl;
    }

    public function getTokens()
    {
        return $this->tokens;
    }

    /**
     * The actual payment process it Submits an order request to Pesapal.
     * @param array $orderDetails The details of the order to submit.
     * @return array The response from the API.
     * @throws \GuzzleHttp\Exception\GuzzleException If the request fails.
     */
    public function submitOrderRequest(array $orderDetails)
    {
        // never forget to validate user inputs bro ok? 

        // Prepare and send the request
        $client = new \GuzzleHttp\Client();
        $response = $client->post($this->getApiUrl() . $this->getApiPath(), [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getTokens(),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'json' => $orderDetails,
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Get the transaction status by order tracking ID.
     * This method retrieves the transaction status using the order tracking ID.
     * @param string $orderTrackingId The order tracking ID to check the status for.
     * @return array The response from the API containing the transaction status.
     * @throws \GuzzleHttp\Exception\GuzzleException If the request fails.
     */

    public function getTransactionStatus($orderTrackingId)
    {
        $api_path = '/api/Transactions/GetTransactionStatus?orderTrackingId=';
        $client = new \GuzzleHttp\Client();
        $response = $client->get($this->getApiUrl() . $api_path . $orderTrackingId, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getTokens(),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ],
        ]);

        return json_decode($response->getBody(), true);
    }
}