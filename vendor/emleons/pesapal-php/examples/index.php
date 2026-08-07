<?php

//for testing purposes, this file is not used in the SDK
require 'vendor/autoload.php';
header('Content-Type: application/json');

$pesa = new \Emleons\PesapalPhp\Pesa([
    'consumer_key' => 'ngW+UEcnDhltUc5fxPfrCD987xMh3Lx8',
    'consumer_secret' => 'q27RChYs5UkypdcNYKzuUw460Dg=',
    'is_sandbox' => true, // Set to false for live environment
]);

// geneqrate random string 50
$randomString = bin2hex(random_bytes(25));

$data = [
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
];

$data2 = [
    "confirmation_code" => "AA11BB22",
    "amount" => "100.00",
    "username" => "John Doe",
    "remarks" => "Service not offered"
];



die(json_encode($pesa->makeThePayment($data)));