# Pesapal PHP SDK

[![Latest Version](https://img.shields.io/packagist/v/emleons/pesapal-php.svg?style=flat-square)](https://packagist.org/packages/emleons/pesapal-php)
[![License](https://img.shields.io/badge/license-MIT-blue.svg?style=flat-square)](LICENSE.md)
[![PHP Version](https://img.shields.io/packagist/php-v/emleons/pesapal-php.svg?style=flat-square)](https://php.net)

> **Please Check the Official Pesapal API Documentation**: [https://developer.pesapal.com/](https://developer.pesapal.com/)

A modern PHP wrapper for the Pesapal v3 API, providing easy integration with Pesapal's payment services. This SDK implements all core API endpoints with PHP best practices.

## Key Features

- **Complete API Coverage**: All essential [Pesapal API v3 endpoints](https://developer.pesapal.com/api3-docs) implemented
- **Automatic Token Handling**: OAuth tokens managed automatically
- **Sandbox Support**: Built-in sandbox mode for testing
- **Strict Typing**: PHP 7.4+ type declarations for reliability
- **PSR Standards**: Composer-ready and framework-agnostic

## Installation

```bash
composer require emleons/pesapal-php
```

## Basic Usage

```php
use Emleons\PesapalPhp\Pesa;

$pesa = new Pesa([
    'consumer_key'    => 'your_key_here',
    'consumer_secret' => 'your_secret_here',
    'is_sandbox'      => true // Set false for production
]);

// Register IPN URL (GET or POST)
$ipnResponse = $pesa->registerIpnUrl(
    'https://yourdomain.com/ipn',
    'POST'
);



// Submit payment
$payment = $pesa->makeThePayment(
    [
    "id" => $randomString,
    "currency" => "TZS",
    "amount" => 10000.00,
    "description" => "Payment description goes here",
    "callback_url" => "http://localhost:8080/pesapal/pin.php",
    "redirect_mode" => "",
    "notification_id" => "030bf9b0-a5d7-4cbd-b5cb-dbb88f03b75e",
    "branch" => "Store Name - HQ",
    "billing_address" => [
        "email_address" => "john.doe@example.com",
        "phone_number" => "0723xxxxxx",
        "country_code" => "KE",
        "first_name" => "John",
        "middle_name" => "",
        "last_name" => "Doe",
        "line_1" => "Pesapal Limited",
        "line_2" => "",
        "city" => "",
        "state" => "",
        "postal_code" => "",
        "zip_code" => ""
    ]
]
);
```

## Official API Reference

For complete API specifications and required parameters, always refer to the:
👉 [Official Pesapal API Documentation](https://developer.pesapal.com/api3-docs)

Particularly useful sections:
- [Authentication](https://developer.pesapal.com/how-to-integrate/e-commerce/api-30-json/authentication)
- [IPN Registration](https://developer.pesapal.com/how-to-integrate/e-commerce/api-30-json/registeripnurl)
- [Order Submission](https://developer.pesapal.com/how-to-integrate/e-commerce/api-30-json/submitorderrequest)
- [Status Checking](https://developer.pesapal.com/how-to-integrate/e-commerce/api-30-json/gettransactionstatus)

## Testing with Sandbox

1. Get sandbox credentials from [Pesapal Developer Portal](https://developer.pesapal.com/)
2. Set `is_sandbox => true`

## Security Notice

Always:
- Validate all user inputs before API calls
- Use HTTPS for all callbacks
- Regularly check [Pesapal's security advisories](https://developer.pesapal.com/)

---

This unofficial SDK is maintained by [emleonstz](https://github.com/emleonstz) as an open source project.  
Pesapal is not responsible for this implementation.


