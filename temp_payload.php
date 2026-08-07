<?php
require __DIR__ . '/includes/pesapal_functions.php';
require __DIR__ . '/db/connection.php';

$donor = [
    'donor_name' => 'Test',
    'donor_email' => 'test@example.com',
    'donor_phone' => '256700000000',
    'is_anonymous' => 0,
    'mobile_money_network' => 'MTN Mobile Money',
];
$campaignId = 1;
$amount = 1000;
$currency = 'UGX';
$campaign = $conn->query("SELECT * FROM campaigns WHERE campaign_id=$campaignId LIMIT 1")->fetch_assoc();
$orderId = 'CF_TEST';
$ipnId = null;
$phone = preg_replace('/[^0-9]/', '', $donor['donor_phone']);
$orderData = [
    'id' => $orderId,
    'currency' => $currency,
    'amount' => floatval($amount),
    'description' => 'Donation to: ' . substr($campaign['title'], 0, 100),
    'callback_url' => 'http://example.com/test',
    'billing_address' => [
        'email_address' => !empty($donor['donor_email']) ? $donor['donor_email'] : $phone . '@donor.chamafunds.com',
        'phone_number' => $phone,
        'country_code' => 'UG',
        'first_name' => substr($donor['donor_name'], 0, 50),
        'last_name' => '',
        'line_1' => 'N/A',
        'city' => 'Kampala',
        'state' => '',
        'postal_code' => '',
    ],
];
if (!empty($ipnId)) {
    $orderData['notification_id'] = $ipnId;
}
echo json_encode($orderData, JSON_PRETTY_PRINT) . "\n";
