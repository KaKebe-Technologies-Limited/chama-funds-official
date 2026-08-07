<?php
/**
 * ioTec Pay Helper Functions for ChamaFunds
 */

require_once __DIR__ . '/iotec_config.php';

class IotecApiClient
{
    private $clientId;
    private $clientSecret;
    private $token;

    public function __construct($clientId, $clientSecret)
    {
        $this->clientId     = $clientId;
        $this->clientSecret = $clientSecret;
    }

    private function request($method, $url, $body = null, $token = null, $isForm = false)
    {
        if (!function_exists('curl_init')) {
            throw new Exception('cURL is required for ioTec Pay payments.');
        }

        $headers = ['Accept: application/json'];
        $headers[] = $isForm ? 'Content-Type: application/x-www-form-urlencoded' : 'Content-Type: application/json';
        if ($token !== null) {
            $headers[] = 'Authorization: Bearer ' . $token;
        }

        $ch = curl_init();
        $curlOptions = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
        ];

        if ($body !== null) {
            $curlOptions[CURLOPT_POSTFIELDS] = $isForm ? http_build_query($body) : json_encode($body);
        }

        curl_setopt_array($ch, $curlOptions);
        $response  = curl_exec($ch);
        $httpCode  = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new Exception('ioTec Pay cURL error: ' . $curlError);
        }

        $decoded = json_decode($response);

        if ($httpCode >= 400) {
            $msg = $decoded->message ?? $decoded->error_description ?? $decoded->error ?? $response;
            throw new Exception('ioTec Pay HTTP ' . $httpCode . ': ' . $msg);
        }

        return $decoded ?: (object) ['raw' => $response];
    }

    public function getToken()
    {
        if ($this->token !== null) {
            return $this->token;
        }
        $response = $this->request('POST', IOTEC_AUTH_URL, [
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type'    => IOTEC_GRANT_TYPE,
        ], null, true);

        if (empty($response->access_token)) {
            throw new Exception('ioTec Pay token request failed.');
        }
        $this->token = $response->access_token;
        return $this->token;
    }

    public function collect($payload)
    {
        $token = $this->getToken();
        return $this->request('POST', IOTEC_BASE_URL . '/api/collections/collect', $payload, $token);
    }

    public function getCollectionStatus($requestId)
    {
        $token = $this->getToken();
        return $this->request('GET', IOTEC_BASE_URL . '/api/collections/status/' . urlencode($requestId), null, $token);
    }
}

/**
 * Initialize ioTec Pay Client
 */
function initializeIotec()
{
    return new IotecApiClient(IOTEC_CLIENT_ID, IOTEC_CLIENT_SECRET);
}

/**
 * Generate a unique order/external reference ID
 */
function generateOrderId() {
    return 'CF_' . uniqid() . '_' . time();
}

/**
 * Normalize a Ugandan phone number to local MSISDN format (0XXXXXXXXX)
 * as expected by the ioTec Pay `payer` field.
 */
function normalizeUgPhone($phone) {
    $digits = preg_replace('/[^0-9]/', '', $phone ?? '');
    if (substr($digits, 0, 3) === '256' && strlen($digits) === 12) {
        return '0' . substr($digits, 3);
    }
    if (substr($digits, 0, 1) === '0' && strlen($digits) === 10) {
        return $digits;
    }
    if (strlen($digits) === 9) {
        return '0' . $digits;
    }
    return $digits;
}

/**
 * Map an ioTec Pay campaign currency to one it actually supports (ITX, UGX, USD).
 */
function iotecCurrency($currency) {
    $currency = strtoupper($currency ?? '');
    return in_array($currency, ['UGX', 'USD', 'ITX'], true) ? $currency : IOTEC_DEFAULT_CURRENCY;
}

/**
 * Initiate a donation payment via ioTec Pay mobile money collection.
 * Returns ['success'=>true, 'donation_id'=>N, 'status'=>'pending'|'completed']
 * or      ['error' => 'message'].
 */
function initiateDonationPayment($conn, $campaign_id, $donor_data, $amount, $currency = 'UGX') {
    $cid    = (int)$campaign_id;
    $result = $conn->query("SELECT * FROM campaigns WHERE campaign_id = $cid LIMIT 1");
    if (!$result || $result->num_rows === 0) {
        return ['error' => 'Campaign not found'];
    }
    $campaign = $result->fetch_assoc();

    $orderId    = generateOrderId();
    $nameEsc    = $conn->real_escape_string($donor_data['donor_name'] ?? '');
    $emailEsc   = $conn->real_escape_string($donor_data['donor_email'] ?? '');
    $phoneEsc   = $conn->real_escape_string($donor_data['donor_phone']);
    $networkEsc = $conn->real_escape_string($donor_data['mobile_money_network'] ?? 'MTN Mobile Money');
    $isAnon     = !empty($donor_data['is_anonymous']) ? 1 : 0;
    $donorIdSql = isset($donor_data['donor_id']) && is_numeric($donor_data['donor_id'])
                    ? (int)$donor_data['donor_id'] : 'NULL';
    $orderIdEsc = $conn->real_escape_string($orderId);
    $currency   = iotecCurrency($currency);

    $conn->query(
        "INSERT INTO donations
             (campaign_id, donor_id, donor_name, donor_email, donor_phone,
              is_anonymous, amount, fee_percentage, status,
              transaction_reference, mobile_money_network, currency)
         VALUES
             ($cid, $donorIdSql, '$nameEsc', '$emailEsc', '$phoneEsc',
              $isAnon, " . floatval($amount) . ", 7.50, 'pending',
              '$orderIdEsc', '$networkEsc', '" . $conn->real_escape_string($currency) . "')"
    );
    if ($conn->error) {
        return ['error' => 'Failed to save donation: ' . $conn->error];
    }
    $donation_id = $conn->insert_id;

    $payer = normalizeUgPhone($donor_data['donor_phone']);

    $payload = [
        'category'    => 'MobileMoney',
        'currency'    => $currency,
        'walletId'    => IOTEC_WALLET_ID,
        'externalId'  => (string)$donation_id,
        'payer'       => $payer,
        'payerName'   => substr($donor_data['donor_name'] ?? 'Donor', 0, 150),
        'payerNote'   => substr('Donation to ' . $campaign['title'], 0, 100),
        'amount'      => floatval($amount),
        'payeeNote'   => 'ChamaFunds donation #' . $donation_id,
    ];

    try {
        $iotec    = initializeIotec();
        $response = $iotec->collect($payload);

        $transactionId = $response->id ?? null;
        $status         = $response->status ?? 'Pending';

        if (empty($transactionId)) {
            $errMsg = $response->message ?? json_encode($response);
            $conn->query("UPDATE donations SET status = 'failed' WHERE donation_id = $donation_id");
            return ['error' => 'ioTec Pay error: ' . $errMsg];
        }

        $txIdEsc = $conn->real_escape_string($transactionId);
        $conn->query(
            "UPDATE donations SET iotec_transaction_id = '$txIdEsc' WHERE donation_id = $donation_id"
        );

        if ($status === 'Success') {
            markDonationCompleted($conn, $donation_id);
            return ['success' => true, 'donation_id' => $donation_id, 'status' => 'completed'];
        }

        if (in_array($status, ['Failed', 'RolledBack', 'Cancelled', 'Rejected'], true)) {
            $conn->query("UPDATE donations SET status = 'failed' WHERE donation_id = $donation_id");
            return ['error' => 'Payment was not accepted by your mobile money provider.'];
        }

        return ['success' => true, 'donation_id' => $donation_id, 'status' => 'pending'];

    } catch (Exception $e) {
        $conn->query("UPDATE donations SET status = 'failed' WHERE donation_id = $donation_id");
        return ['error' => 'Payment exception: ' . $e->getMessage()];
    }
}

/**
 * Mark a donation completed, bump campaign totals, and notify the campaign owner.
 * Shared by the AJAX status poll, the IPN handler, and immediate-success responses.
 */
function markDonationCompleted($conn, $donation_id) {
    $did = (int)$donation_id;
    $conn->query(
        "UPDATE donations SET status = 'completed', payment_date = NOW()
         WHERE donation_id = $did AND status = 'pending'"
    );
    if ($conn->affected_rows <= 0) {
        return false;
    }

    $donRow = $conn->query("SELECT * FROM donations WHERE donation_id = $did LIMIT 1")->fetch_assoc();
    if (!$donRow) return true;

    $campaign_id = (int)$donRow['campaign_id'];
    $amt         = floatval($donRow['amount']);

    $conn->query(
        "UPDATE campaigns
         SET raised_amount     = raised_amount + $amt,
             contributor_count = contributor_count + 1,
             updated_at        = NOW()
         WHERE campaign_id = $campaign_id"
    );

    $campRow = $conn->query(
        "SELECT campaigner_id, title FROM campaigns WHERE campaign_id = $campaign_id LIMIT 1"
    )->fetch_assoc();
    if ($campRow) {
        $ownerId     = (int)$campRow['campaigner_id'];
        $campTitle   = $conn->real_escape_string($campRow['title']);
        $donorLabel  = $donRow['is_anonymous'] ? 'Anonymous Donor'
                        : $conn->real_escape_string($donRow['donor_name']);
        $notifMsg    = "$donorLabel just donated " . number_format($amt)
                     . " to your campaign \"$campTitle\"";
        $notifMsgEsc = $conn->real_escape_string($notifMsg);
        $notifLink   = $conn->real_escape_string(BASE . '/campaign-detail.php?id=' . $campaign_id);
        $conn->query(
            "INSERT INTO notifications (user_id, type, title, message, link)
             VALUES ($ownerId, 'donation', 'New Donation Received!',
                     '$notifMsgEsc', '$notifLink')"
        );
    }
    return true;
}

/**
 * Mark a donation failed (only if still pending).
 */
function markDonationFailed($conn, $donation_id) {
    $did = (int)$donation_id;
    $conn->query("UPDATE donations SET status = 'failed' WHERE donation_id = $did AND status = 'pending'");
}

/**
 * Verify a donation's current status by asking ioTec Pay directly.
 * Called from the AJAX status-poll endpoint when a donation is still pending locally.
 */
function verifyIotecTransaction($conn, $donation_id) {
    $did    = (int)$donation_id;
    $result = $conn->query("SELECT * FROM donations WHERE donation_id = $did LIMIT 1");
    $donation = $result ? $result->fetch_assoc() : null;
    if (!$donation) {
        return ['error' => 'Donation not found'];
    }
    if ($donation['status'] !== 'pending') {
        return ['success' => true, 'status' => $donation['status']];
    }
    if (empty($donation['iotec_transaction_id'])) {
        return ['success' => true, 'status' => 'pending'];
    }

    try {
        $iotec    = initializeIotec();
        $response = $iotec->getCollectionStatus($donation['iotec_transaction_id']);
        $status   = $response->status ?? 'Pending';

        if ($status === 'Success') {
            markDonationCompleted($conn, $donation_id);
            return ['success' => true, 'status' => 'completed'];
        }
        if (in_array($status, ['Failed', 'RolledBack', 'Cancelled', 'Rejected'], true)) {
            markDonationFailed($conn, $donation_id);
            return ['success' => true, 'status' => 'failed'];
        }
        return ['success' => true, 'status' => 'pending'];
    } catch (Exception $e) {
        // Network/API hiccup — leave as pending, the webhook or next poll will resolve it.
        return ['success' => true, 'status' => 'pending'];
    }
}

/**
 * Verify that an incoming callback request really came from ioTec Pay.
 * ioTec lets you configure a custom "Security Header" per callback URL
 * in the merchant portal, but the exact header name/format the portal
 * accepts can vary — so instead of requiring one specific header name,
 * we accept the request if ANY incoming header value contains our
 * secret (either raw, or as an `Authorization: Bearer <secret>` value).
 */
function verifyIotecIpnSecret() {
    $headers = function_exists('getallheaders') ? getallheaders() : [];

    foreach ($headers as $value) {
        if ($value === IOTEC_IPN_SECRET || $value === 'Bearer ' . IOTEC_IPN_SECRET) {
            return true;
        }
    }
    return false;
}

/**
 * Process an ioTec Pay callback (IPN) notification.
 * Called by ipn_handler.php when ioTec posts a transaction status update.
 */
function processIotecIpn($conn) {
    if (!verifyIotecIpnSecret()) {
        return ['error' => 'Invalid or missing IPN secret'];
    }

    $raw     = file_get_contents('php://input');
    $payload = json_decode($raw);
    if (!$payload) {
        return ['error' => 'Invalid IPN payload'];
    }

    $transactionId = $payload->id ?? null;
    $externalId    = $payload->externalId ?? null;
    $status        = $payload->status ?? '';

    $donation = null;
    if (!empty($transactionId)) {
        $txEsc  = $conn->real_escape_string($transactionId);
        $result = $conn->query("SELECT * FROM donations WHERE iotec_transaction_id = '$txEsc' LIMIT 1");
        $donation = $result ? $result->fetch_assoc() : null;
    }
    if (!$donation && !empty($externalId) && ctype_digit((string)$externalId)) {
        $result   = $conn->query("SELECT * FROM donations WHERE donation_id = " . (int)$externalId . " LIMIT 1");
        $donation = $result ? $result->fetch_assoc() : null;
    }

    if (!$donation) {
        return ['error' => 'Donation not found for transaction: ' . $transactionId];
    }

    if ($status === 'Success') {
        markDonationCompleted($conn, $donation['donation_id']);
    } elseif (in_array($status, ['Failed', 'RolledBack', 'Cancelled', 'Rejected'], true)) {
        markDonationFailed($conn, $donation['donation_id']);
    }
    // Pending / SentToVendor / AwaitingApproval / Scheduled — leave as-is

    return ['success' => true, 'status' => $status];
}
