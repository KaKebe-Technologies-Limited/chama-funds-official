<?php
function curlJson($url, $method, $token = null, $body = null) {
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
    return [$info, $resp];
}

$consumerKey = 'u+nAfIIT/y0vtZdwd4ypMumdpUUPmyYm';
$consumerSecret = 'r2hNkSEMzL9P4ByZiltITGsj/3g=';
$baseUrl = 'https://pay.pesapal.com/v3';
list($info, $resp) = curlJson($baseUrl . '/api/Auth/RequestToken', 'POST', null, [
    'consumer_key' => $consumerKey,
    'consumer_secret' => $consumerSecret,
]);
print_r($info);
echo "\n";
echo $resp . "\n";
$tokenData = json_decode($resp, true);
if (empty($tokenData['token'])) {
    exit(1);
}
$token = $tokenData['token'];
$body = [
    'id' => 'CF_TEST_0002',
    'currency' => 'UGX',
    'amount' => 1000,
    'description' => 'Donation to: Test Campaign',
    'callback_url' => 'https://localhost/chama/payment_callback.php?donation_id=999',
    'notification_id' => '124d3334-69f7-47df-bbfd-da32eecb8735',
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
list($info, $resp) = curlJson($baseUrl . '/api/Transactions/SubmitOrderRequest', 'POST', $token, $body);
print_r($info);
echo "\n";
echo $resp . "\n";
