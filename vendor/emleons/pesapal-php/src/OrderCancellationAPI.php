<?php 

namespace Emleons\PesapalPhp;

class OrderCancellationAPI
{
    private $baseUrl;
    private $api_path;
    protected $tokens;

    public function __construct($baseUrl, $tokens, $isSandbox = true)
    {
        $this->baseUrl = $baseUrl;
        $this->api_path = '/api/Transactions/CancelOrder';
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
     * Cancels an order request to Pesapal.
     * @param string $orderTrackingId The tracking ID of the order to cancel.
     * @return array The response from the API.
     * @throws \GuzzleHttp\Exception\GuzzleException If the request fails.
     */
    public function cancelOrderRequest($orderTrackingId)
    {
        if (empty($orderTrackingId)) {
            throw new \InvalidArgumentException('Order tracking ID must be provided.');     
        }
        // Prepare and send the request
        $client = new \GuzzleHttp\Client();
        $response = $client->post($this->getApiUrl() . $this->getApiPath(), [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getTokens(),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'json' => [
                'order_tracking_id' => $orderTrackingId,
            ],
        ]);
        $responseBody = json_decode($response->getBody(), true);
        return $responseBody;   
    }
}