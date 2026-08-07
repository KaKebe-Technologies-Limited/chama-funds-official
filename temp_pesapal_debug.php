<?php
function curlJson($url, $method, $token, $body = null) {
    $ch = curl_init($url);
    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
    ];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    $resp = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);
    echo "URL: $url\n";
    echo "METHOD: $method\n";
    echo "HTTP: {$info['http_code']}\n";
    echo "RESP: $resp\n";
    echo "BODY: " . json_encode($body, JSON_PRETTY_PRINT) . "\n";
    echo "---\n";
    return [$info, $resp];
}

// Replace with valid production or sandbox auth details
$consumerKey = 'u+nAfIIT/y0vtZdwd4ypMumdpUUPmyYm';
$consumerSecret = 'r2hNkSEMzL9P4ByZiltITGsj/3g=';
$baseUrl = 'https://pay.pesapal.com/v3';
$authUrl = $baseUrl . '/api/Auth/RequestToken';
list($info, $resp) = curlJson($authUrl, 'POST', null, [
    'consumer_key' => $consumerKey,
    'consumer_secret' => $consumerSecret,
]);
$tokenData = json_decode($resp, true);
if (isset($tokenData['token'])) {
    echo "TOKEN OK: " . substr($tokenData['token'], 0, 20) . "...\n";
} else {
    echo "TOKEN FAIL\n";
    exit(1);
}

$token = $tokenData['token'];
$orderUrl = $baseUrl . '/api/Transactions/SubmitOrderRequest';
$body = [
    'id' => 'CF_TEST_0001',
    'currency' => 'UGX',
    'amount' => 1000,
    'description' => 'Donation to: Test Campaign',
    'callback_url' => 'https://localhost/chama/payment_callback.php?donation_id=38',
    'billing_address' => [
        'email_address' => '256700000000@donor.chamafunds.com',
        'phone_number' => '256700000000',
        'country_code' => 'UG',
        'first_name' => 'Test User',
        'last_name' => '',
        'line_1' => 'N/A',
        'city' => 'Uganda',
        'state' => '',
        'postal_code' => '',
    ],
];
list($info, $resp) = curlJson($orderUrl, 'POST', $token, $body);
