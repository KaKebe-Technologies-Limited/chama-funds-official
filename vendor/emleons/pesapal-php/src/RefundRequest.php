<?php

namespace Emleons\PesapalPhp;

class RefundRequest
{
    private $baseUrl;
    private $api_path;
    protected $tokens;

    public function __construct($baseUrl, $tokens, $isSandbox = true)
    {
        $this->baseUrl = $baseUrl;
        $this->api_path = '/api/Transactions/RefundRequest';
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
     * Submits a refund request to Pesapal.
     * @param array $refundDetails The details of the refund to submit.
     * @return array The response from the API.
     * @throws \GuzzleHttp\Exception\GuzzleException If the request fails.
     */

    public function submitRefundRequest(array $refundDetails)
    {
        // Prepare and send the request
        $client = new \GuzzleHttp\Client();
        $response = $client->post($this->getApiUrl() . $this->getApiPath(), [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getTokens(),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'json' => $refundDetails,
        ]);

        return json_decode($response->getBody(), true);
    }
}