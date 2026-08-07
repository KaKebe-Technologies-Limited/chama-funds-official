<?php

namespace Emleons\PesapalPhp;



class IpnRegistration
{
    private $baseUrl;
    private $api_path;
    protected $tokens;

    public function __construct($baseUrl, $tokens, $isSandbox = true)
    {
        $this->baseUrl = $baseUrl;
        $this->api_path = '/api/URLSetup/RegisterIPN';
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
     * Registers the IPN URL with Pesapal.
     * @param string $ipnUrl The URL to register for IPN notifications.
     * @return array The response from the API.
     * @param string $ipn_notification_type The type of notification, either 'GET' or 'POST'.
     * @throws \GuzzleHttp\Exception\GuzzleException If the request fails.
     */

    public function registerIpnUrl($ipnUrl, $ipn_notification_type)
    {
        if (empty($ipn_notification_type) && ! $ipn_notification_type == 'GET' || ! $ipn_notification_type == 'POST') {
            throw new \InvalidArgumentException('IPN notification type must be either GET or POST.');
        }

        if (empty($this->tokens)) {
            throw new \InvalidArgumentException('Tokens must be provided.');
        }

        if (empty($ipnUrl)) {
            throw new \InvalidArgumentException('IPN URL must be provided.');
        }

        $client = new \GuzzleHttp\Client();
        $response = $client->post($this->getApiUrl() . $this->getApiPath(), [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getTokens(),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
            'json' => [
                'url' => $ipnUrl,
                'ipn_notification_type' => $ipn_notification_type,
            ],
        ]);
        $responseBody = json_decode($response->getBody(), true);
        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Failed to register IPN URL: ' . json_encode($responseBody));
        }
        return $responseBody;
    }


    public function getRegisteredIpnUrl()
    {
        $api_path = '/api/URLSetup/GetIpnList';
        $client = new \GuzzleHttp\Client();
        $response = $client->get($this->getApiUrl() . $api_path, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->getTokens(),
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        ]);
        $responseBody = json_decode($response->getBody(), true);
        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Failed to get registered IPN URL: ' . json_encode($responseBody));
        }
        return $responseBody;

    }
}
