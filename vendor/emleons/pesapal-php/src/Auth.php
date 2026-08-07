<?php

namespace Emleons\PesapalPhp;

/**
 * Class Auth
 * @package Emleons\PesapalPhp
 * This class handles authentication for the Pesapal PHP SDK.
 * It stores the consumer key, consumer secret, and sandbox mode status,
 * providing methods to access these properties.
 */
class Auth
{
    private $consumerKey;
    private $baseUrl;
    private $consumerSecret;
    private $isSandbox;
    private $api_path;

    public function __construct($baseUrl, $consumerKey, $consumerSecret, $isSandbox = true)
    {
        $this->baseUrl = $baseUrl;
        $this->consumerKey = $consumerKey;
        $this->consumerSecret = $consumerSecret;
        $this->isSandbox = $isSandbox;
        $this->api_path = '/api/Auth/RequestToken';
    }

    public function getConsumerKey()
    {
        return $this->consumerKey;
    }

    public function getConsumerSecret()
    {
        return $this->consumerSecret;
    }

    public function isSandbox()
    {
        return $this->isSandbox;
    }

    public function getApiPath()
    {
        return $this->api_path;
    }

    public function getApiAuthUrl()
    {
        return $this->baseUrl . $this->getApiPath();
    }

    /**
     * Generates a token using the consumer key and secret.
     * @return array The response containing the token.
     * @throws \GuzzleHttp\Exception\GuzzleException If the request fails.
     */
    public function getToken()
    {
        // Check if consumer key and secret are set
        if (empty($this->consumerKey) || empty($this->consumerSecret)) {
            throw new \InvalidArgumentException('Consumer key and secret must be set.');
        }
        // Generate the token using the consumer key and secret
        $token = $this->generateToken();
        return $token;
        
    }

    public function generateToken()
    {
        //generating a token using the consumer key and secret
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
        ];
        $payloads = [
            'consumer_key' => $this->consumerKey,
            'consumer_secret' => $this->consumerSecret,
        ];


        $client = new \GuzzleHttp\Client();
        $response = $client->post($this->getApiAuthUrl(), [
            'headers' => $headers,
            'json' => $payloads,
        ]);

        $responseBody = json_decode($response->getBody(), true);

        return $responseBody;
    }

    
}
