<?php

namespace Emleons\PesapalPhp;

/**
 * Class Pesa
 * @package Emleons\PesapalPhp
 * This class manages configuration settings for the Pesapal PHP SDK.
 * It includes constants for default API settings and allows dynamic configuration
 * via an array passed to the constructor. The class provides methods to access
 * configuration values and methods for consistent usage across the SDK.
 */
final class Pesa
{
    // Default constants for API settings
    public const API_VERSION = 'v3';
    public const API_BASE_URL_SANDBOX = 'https://cybqa.pesapal.com/pesapal' . self::API_VERSION;
    public const API_BASE_URL_LIVE = 'https://pay.pesapal.com/' . self::API_VERSION;

    // Dynamic configuration properties
    private string $consumerKey;
    private string $consumerSecret;
    private string $apiBaseUrl;
    private bool $isSandbox;

    /**
     * Config constructor.
     * @param array $config An associative array containing configuration values.
     * Expected keys: consumer_key, consumer_secret, is_sandbox (optional, defaults to true).
     * @throws \InvalidArgumentException If required configuration values are missing or invalid.
     */
    public function __construct(array $config = [])
    {
        // Validate required configuration values
        if (empty($config['consumer_key'])) {
            throw new \InvalidArgumentException('Consumer key is required.');
        }
        if (empty($config['consumer_secret'])) {
            throw new \InvalidArgumentException('Consumer secret is required.');
        }

        // Set dynamic properties
        $this->consumerKey = $config['consumer_key'];
        $this->consumerSecret = $config['consumer_secret'];
        $this->isSandbox = $config['is_sandbox'] ?? true; // Default to sandbox
        $this->apiBaseUrl = $this->isSandbox ? self::API_BASE_URL_SANDBOX : self::API_BASE_URL_LIVE;
    }

    /**
     * Get the consumer key.
     * @return string
     */
    public function getConsumerKey(): string
    {
        return $this->consumerKey;
    }

    /**
     * Get the consumer secret.
     * @return string
     */
    public function getConsumerSecret(): string
    {
        return $this->consumerSecret;
    }

    /**
     * Get the API base URL based on the environment.
     * @return string
     */
    public function getApiBaseUrl(): string
    {
        return $this->apiBaseUrl;
    }

    /**
     * Check if the sandbox environment is enabled.
     * @return bool
     */
    public function isSandbox(): bool
    {
        return $this->isSandbox;
    }

    /**
     * Get the token for authentication.
     * This method uses the Auth class to generate a token based on the consumer key and secret.
     * @throws \GuzzleHttp\Exception\GuzzleException If the request fails.
     * @throws \InvalidArgumentException If the consumer key or secret is not set.
     * @throws \Exception If the token generation fails.
     * @see Auth::getToken()
     * @throws \Exception If the token generation fails.
     * @return string
     */

    public function getToken()
    {
        $auth = new Auth($this->getApiBaseUrl(), $this->consumerKey, $this->consumerSecret, $this->isSandbox);
        $token = $auth->getToken();
        if (empty($token)) {
            throw new \Exception('Failed to generate token.');
        }
        return $token['token'];
    }

    /**
     * Register an IPN URL with Pesapal.
     * This method uses the IpnRegistration class to register the provided IPN URL.
     * @param string $ipnUrl The URL to register for IPN notifications.
     * @param string $ipn_notification_type The type of notification, either 'GET' or 'POST'.
     * @return array The response from the API.
     * @throws \GuzzleHttp\Exception\GuzzleException If the request fails.
     * @throws \InvalidArgumentException If the IPN URL or notification type is invalid.
     */


    public function registerIpnUrl($ipnUrl, $ipn_notification_type)
    {
        $ipnRegistration = new IpnRegistration($this->getApiBaseUrl(), $this->getToken(), $this->isSandbox);
        return $ipnRegistration->registerIpnUrl($ipnUrl, $ipn_notification_type);
    }

    /**
     * Get the registered IPN URL.
     * This method retrieves the registered IPN URL using the IpnRegistration class.
     * @return string The registered IPN URL.
     * @throws \GuzzleHttp\Exception\GuzzleException If the request fails.
     */

    public function getReigsteredIpnUrl()
    {
        $ipnRegistration = new IpnRegistration($this->getApiBaseUrl(), $this->getToken(), $this->isSandbox);
        return $ipnRegistration->getRegisteredIpnUrl();
    }


    /**
     * Make the payment by submitting an order request.
     * This method uses the SubmitOrderRequest class to submit the order details.
     * Make sure to validate user inputs on your end before calling this method by checking required fields from the Docs.
     * @param array $orderDetails The details of the order to submit as payment payloads.
     * @return array The response from the API.
     * @throws \GuzzleHttp\Exception\GuzzleException If the request fails.
     */

    public function makeThePayment(array $orderDetails)
    {
        $submitOrderRequest = new SubmitOrderRequest($this->getApiBaseUrl(), $this->getToken(), $this->isSandbox);
        return $submitOrderRequest->submitOrderRequest($orderDetails);
    }

    /**
     * Get the transaction status by order tracking ID.
     * This method retrieves the transaction status using the SubmitOrderRequest class.
     * @param string $orderTrackingId The order tracking ID to check the status for.
     * @return array The response from the API containing the transaction status.
     * @throws \GuzzleHttp\Exception\GuzzleException If the request fails.
     */

    public function getTransactionStatus($orderTrackingId)
    {
        $submitOrderRequest = new SubmitOrderRequest($this->getApiBaseUrl(), $this->getToken(), $this->isSandbox);
        return $submitOrderRequest->getTransactionStatus($orderTrackingId);
    }

    /**
     * Send a refund request.
     * This method uses the RefundRequest class to submit a refund request.
     * @param array $refundDetails The details of the refund request.
     * @return array The response from the API.
     * @throws \GuzzleHttp\Exception\GuzzleException If the request fails.
     */

    public function sendRefundRequest(array $refundDetails)
    {
        $refundRequest = new RefundRequest($this->getApiBaseUrl(), $this->getToken(), $this->isSandbox);
        return $refundRequest->submitRefundRequest($refundDetails);
    }

    /**
     * Cancel an order request by order tracking ID.
     * This method uses the SubmitOrderRequest class to cancel the order.
     * @param string $orderTrackingId The tracking ID of the order to cancel.
     * @return array The response from the API.
     * @throws \GuzzleHttp\Exception\GuzzleException If the request fails.
     * @throws \InvalidArgumentException If the order tracking ID is not provided.
     */

    public function cancelOrderRequest($orderTrackingId)
    {
        $cancelOrderRequest = new OrderCancellationAPI($this->getApiBaseUrl(), $this->getToken(), $this->isSandbox);
        return $cancelOrderRequest->cancelOrderRequest($orderTrackingId);
    }
}
