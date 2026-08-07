<?php
/**
 * Pesapal Helper Functions for ChamaFunds
 */

require_once __DIR__ . '/pesapal_config.php';

class PesapalApiClient
{
    private $consumerKey;
    private $consumerSecret;
    private $isSandbox;
    private $baseUrl;

    public function __construct($consumerKey, $consumerSecret, $isSandbox = true)
    {
        $this->consumerKey = $consumerKey;
        $this->consumerSecret = $consumerSecret;
        $this->isSandbox = (bool) $isSandbox;
        $this->baseUrl = $this->isSandbox
            ? 'https://cybqa.pesapal.com/pesapalv3'
            : 'https://pay.pesapal.com/v3';
    }

    private function request($method, $path, $body = null, $token = null)
    {
        if (!function_exists('curl_init')) {
            throw new Exception('cURL is required for Pesapal payments.');
        }

        $ch = curl_init();
        $url = rtrim($this->baseUrl, '/') . $path;
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
        ];
        if ($token !== null) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $curlOptions = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
        ];

        if ($body !== null) {
            $payload = json_encode($body);
            // Debug logfile for outgoing Pesapal requests
            file_put_contents(__DIR__ . '/../pesapal_request_debug.log',
                "=== " . date('Y-m-d H:i:s') . " ===\n" .
                "URL: $url\n" .
                "METHOD: $method\n" .
                "TOKEN: " . ($token ? substr($token, 0, 10) . '...' : '[none]') . "\n" .
                "BODY: $payload\n\n",
                FILE_APPEND | LOCK_EX
            );
            $curlOptions[CURLOPT_POSTFIELDS] = $payload;
        }

        curl_setopt_array($ch, $curlOptions);
        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Debug response for Pesapal requests
        file_put_contents(__DIR__ . '/../pesapal_request_debug.log',
            "RESPONSE_CODE: $httpCode\n" .
            "RESPONSE_BODY: " . ($response === false ? $curlError : $response) . "\n\n",
            FILE_APPEND | LOCK_EX
        );

        if ($response === false) {
            throw new Exception('Pesapal cURL error: ' . $curlError);
        }

        if ($httpCode >= 400) {
            file_put_contents(__DIR__ . '/../pesapal_request_debug.log',
                "ERROR_RESPONSE: HTTP $httpCode\n" . $response . "\n\n",
                FILE_APPEND | LOCK_EX
            );
            throw new Exception('Pesapal HTTP ' . $httpCode . ': ' . $response);
        }

        return json_decode($response) ?: (object) ['raw' => $response];
    }

    public function getToken()
    {
        $payload = [
            'consumer_key'    => $this->consumerKey,
            'consumer_secret' => $this->consumerSecret,
        ];
        $response = $this->request('POST', '/api/Auth/RequestToken', $payload);
        if (empty($response->token)) {
            throw new Exception('Pesapal token request failed.');
        }
        return $response->token;
    }

    public function registerIpnUrl($ipnUrl, $ipnNotificationType = 'POST')
    {
        $token = $this->getToken();
        return $this->request('POST', '/api/URLSetup/RegisterIPN', [
            'url' => $ipnUrl,
            'ipn_notification_type' => $ipnNotificationType,
        ], $token);
    }

    public function makeThePayment($orderData)
    {
        $token = $this->getToken();
        return $this->request('POST', '/api/Transactions/SubmitOrderRequest', $orderData, $token);
    }

    public function getTransactionStatus($orderTrackingId)
    {
        $token = $this->getToken();
        return $this->request('GET', '/api/Transactions/GetTransactionStatus?orderTrackingId=' . urlencode($orderTrackingId), null, $token);
    }

    public function getIpnStatus()
    {
        return (object) ($_POST ?? []);
    }
}

/**
 * Initialize Pesapal Client
 */
function initializePesapal()
{
    return new PesapalApiClient(
        PESAPAL_CONSUMER_KEY,
        PESAPAL_CONSUMER_SECRET,
        PESAPAL_SANDBOX
    );
}

/**
 * Generate a unique order ID
 */
function generateOrderId() {
    return 'CF_' . uniqid() . '_' . time();
}

/**
 * Get or create IPN registration ID.
 * Stored in platform_settings to avoid re-registering on every request.
 * NOTE: IPN registration disabled for now as Pesapal keeps rejecting it.
 * Payments work fine with just callback_url.
 */
function getPesapalIpnId($conn) {
    $settingKey = 'pesapal_ipn_id';

    $result = $conn->query(
        "SELECT setting_value FROM platform_settings WHERE setting_key = '$settingKey' LIMIT 1"
    );
    if ($result && ($row = $result->fetch_assoc())) {
        $ipnId = trim($row['setting_value']);
        if ($ipnId !== '') {
            return $ipnId;
        }
    }

    $pesa     = initializePesapal();
    $response = $pesa->registerIpnUrl(PESAPAL_IPN_URL, 'POST');
    $ipnId    = null;
    if (is_array($response)) {
        $ipnId = $response['ipn_id'] ?? null;
    } elseif (is_object($response)) {
        $ipnId = $response->ipn_id ?? null;
    }
    if (empty($ipnId)) {
        $debugResponse = is_scalar($response) ? $response : json_encode($response);
        throw new Exception('Pesapal IPN registration did not return an ipn_id. Response: ' . $debugResponse);
    }

    $ipnIdEsc = $conn->real_escape_string($ipnId);
    if ($result && $result->num_rows > 0) {
        $conn->query(
            "UPDATE platform_settings SET setting_value = '$ipnIdEsc', updated_at = NOW() WHERE setting_key = '$settingKey'"
        );
    } else {
        $conn->query(
            "INSERT INTO platform_settings (setting_key, setting_value, setting_group, is_encrypted, updated_at)
             VALUES ('$settingKey', '$ipnIdEsc', 'payment', 0, NOW())"
        );
    }

    return $ipnId;
}


/**
 * Initiate a donation payment via Pesapal.
 * Returns ['success'=>true, 'redirect_url'=>'...', 'donation_id'=>N]
 * or      ['error' => 'message'].
 */
function initiateDonationPayment($conn, $campaign_id, $donor_data, $amount, $currency = 'UGX') {
    // Get campaign details
    $cid    = (int)$campaign_id;
    $result = $conn->query("SELECT * FROM campaigns WHERE campaign_id = $cid LIMIT 1");
    if (!$result || $result->num_rows === 0) {
        return ['error' => 'Campaign not found'];
    }
    $campaign = $result->fetch_assoc();

    // Generate unique order ID
    $orderId    = generateOrderId();
    $nameEsc    = $conn->real_escape_string($donor_data['donor_name'] ?? '');
    $emailEsc   = $conn->real_escape_string($donor_data['donor_email'] ?? '');
    $phoneEsc   = $conn->real_escape_string($donor_data['donor_phone']);
    $networkEsc = $conn->real_escape_string($donor_data['mobile_money_network'] ?? 'MTN Mobile Money');
    $isAnon     = !empty($donor_data['is_anonymous']) ? 1 : 0;
    $donorIdSql = isset($donor_data['donor_id']) && is_numeric($donor_data['donor_id'])
                    ? (int)$donor_data['donor_id'] : 'NULL';
    $orderIdEsc = $conn->real_escape_string($orderId);

    // Insert a pending donation record first
    $conn->query(
        "INSERT INTO donations
             (campaign_id, donor_id, donor_name, donor_email, donor_phone,
              is_anonymous, amount, fee_percentage, status,
              transaction_reference, mobile_money_network)
         VALUES
             ($cid, $donorIdSql, '$nameEsc', '$emailEsc', '$phoneEsc',
              $isAnon, " . floatval($amount) . ", 7.50, 'pending',
              '$orderIdEsc', '$networkEsc')"
    );
    if ($conn->error) {
        return ['error' => 'Failed to save donation: ' . $conn->error];
    }
    $donation_id = $conn->insert_id;

    // Attempt to get IPN ID, but safely ignore any failures
    $ipnId = null;
    try {
        $ipnId = getPesapalIpnId($conn);
    } catch (Exception $e) {
        // IPN registration failed; proceed without it
        error_log('IPN registration warning: ' . $e->getMessage());
    }

    // Clean phone number — digits only
    $phone = preg_replace('/[^0-9]/', '', $donor_data['donor_phone']);

    // Build Pesapal order payload
    $orderData = [
        'id'              => $orderId,
        'currency'        => $currency,
        'amount'          => floatval($amount),
        'description'     => 'Donation to: ' . substr($campaign['title'], 0, 100),
        'callback_url'    => PESAPAL_CALLBACK_URL . '?donation_id=' . $donation_id,
        'billing_address' => [
            'email_address' => !empty($donor_data['donor_email'])
                                    ? $donor_data['donor_email']
                                    : $phone . '@donor.chamafunds.com',
            'phone_number'  => $phone,
            'country_code'  => getCountryCode($campaign['country'] ?? ''),
            'first_name'    => substr($donor_data['donor_name'] ?? 'Donor', 0, 50),
            'last_name'     => '',
            'line_1'        => 'N/A',
            'city'          => $campaign['country'] ?? 'Kampala',
            'state'         => '',
            'postal_code'   => '',
        ],
    ];
    
    // Only add IPN if we have a valid ID (Pesapal rejects empty/invalid IPN IDs)
    if (!empty($ipnId)) {
        $orderData['notification_id'] = $ipnId;
    }

    try {
        $pesa            = initializePesapal();
        $paymentResponse = $pesa->makeThePayment($orderData);

        $redirectUrl = $paymentResponse->redirect_url ?? $paymentResponse->redirectUrl ?? null;
        $trackingId  = $paymentResponse->order_tracking_id ?? $paymentResponse->orderTrackingId ?? '';
        if (!empty($redirectUrl)) {
            // Save the Pesapal tracking ID against this donation
            $trackingIdEsc = $conn->real_escape_string($trackingId);
            $conn->query(
                "UPDATE donations
                 SET pesapal_tracking_id = '$trackingIdEsc'
                 WHERE donation_id = $donation_id"
            );

            return [
                'success'      => true,
                'redirect_url' => $redirectUrl,
                'donation_id'  => $donation_id,
            ];
        }

        // Pesapal returned an error object
        $errMsg = $paymentResponse->error->message
               ?? $paymentResponse->error_message
               ?? $paymentResponse->message
               ?? json_encode($paymentResponse);
        return ['error' => 'Pesapal error: ' . $errMsg];

    } catch (Exception $e) {
        return ['error' => 'Payment exception: ' . $e->getMessage()];
    }
}

/**
 * Get country code for Pesapal billing address.
 */
function getCountryCode($country_name) {
    $map = [
        'Uganda'       => 'UG',
        'Kenya'        => 'KE',
        'Tanzania'     => 'TZ',
        'Rwanda'       => 'RW',
        'Nigeria'      => 'NG',
        'South Africa' => 'ZA',
    ];
    return $map[$country_name] ?? 'UG';
}

/**
 * Verify a completed payment by querying Pesapal's transaction status.
 * Called from payment_callback.php after the user returns.
 */
function verifyPesapalTransaction($order_tracking_id) {
    try {
        $pesa     = initializePesapal();
        $response = $pesa->getTransactionStatus($order_tracking_id);
        return $response;
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Process IPN notification from Pesapal.
 * Called by ipn_handler.php when Pesapal posts a status update.
 */
function processPesapalIpn($conn) {
    $pesa        = initializePesapal();
    $ipnResponse = $pesa->getIpnStatus();

    if (!$ipnResponse || !isset($ipnResponse->order_tracking_id)) {
        return ['error' => 'Invalid IPN response'];
    }

    $tracking_id = $conn->real_escape_string($ipnResponse->order_tracking_id);
    $result      = $conn->query(
        "SELECT * FROM donations
         WHERE pesapal_tracking_id = '$tracking_id'
           AND status = 'pending'
         LIMIT 1"
    );
    $donation = $result ? $result->fetch_assoc() : null;

    if (!$donation) {
        return ['error' => 'Donation not found for tracking ID: ' . $tracking_id];
    }

    $status = strtoupper($ipnResponse->payment_status_description ?? $ipnResponse->status ?? '');

    if ($status === 'COMPLETED') {
        $conn->query(
            "UPDATE donations
             SET status = 'completed', payment_date = NOW()
             WHERE donation_id = " . $donation['donation_id']
        );
        // Bump campaign totals
        $conn->query(
            "UPDATE campaigns
             SET raised_amount     = raised_amount + " . floatval($donation['amount']) . ",
                 contributor_count = contributor_count + 1,
                 updated_at        = NOW()
             WHERE campaign_id = " . (int)$donation['campaign_id']
        );
    } elseif (in_array($status, ['FAILED', 'INVALID', 'REVERSED'])) {
        $conn->query(
            "UPDATE donations SET status = 'failed'
             WHERE donation_id = " . $donation['donation_id']
        );
    }
    // PENDING / unknown — leave as-is

    return ['success' => true, 'status' => $status];
}
