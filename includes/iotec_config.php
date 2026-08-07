<?php
/**
 * ioTec Pay Configuration
 * Keep this file secure - DO NOT commit to public repositories
 */

// ── Environment ───────────────────────────────────────────────
// ioTec Pay uses a single API host for both modes; "sandbox" here
// just means we charge against the TEST wallet (paired with the
// magic test phone numbers from ioTec's docs) instead of the LIVE
// wallet. true = test wallet, false = live wallet (real money).
define('IOTEC_SANDBOX', false);   // ← PRODUCTION MODE (LIVE)

// ── API Credentials ──────────────────────────────────────────
// From the ioTec Pay merchant portal: https://pay.iotec.io
define('IOTEC_CLIENT_ID',     'pay-019f314e-f9f1-70a0-b089-06f53b92df21');
define('IOTEC_CLIENT_SECRET', 'IO-87xRMPjXj99LezKzP884sJED8cnDQUynS');
define('IOTEC_GRANT_TYPE',    'client_credentials');

// ── Wallets ───────────────────────────────────────────────────
define('IOTEC_TEST_WALLET_ID', '019f314e-fa12-764a-b7a5-be6c5938974d');
define('IOTEC_LIVE_WALLET_ID', '019f37d2-82a0-721e-8d72-7fd11d81368a');
define('IOTEC_WALLET_ID', IOTEC_SANDBOX ? IOTEC_TEST_WALLET_ID : IOTEC_LIVE_WALLET_ID);

// ── IPN / Callback verification ─────────────────────────────────
// ioTec Pay callback URLs are configured in the merchant portal
// (Wallet → Settings → Callback URLs), not via the API. Configure
// a "Security Header" there (e.g. name: X-Ipn-Secret) with this
// value so ipn_handler.php can verify incoming callbacks are genuine.
define('IOTEC_IPN_SECRET', '2ta6cfziH7W54kgDFGhUmZRq8esTXMw9SEBvLQyb');

// ── API Endpoints ─────────────────────────────────────────────
define('IOTEC_AUTH_URL', 'https://id.iotec.io/connect/token');
define('IOTEC_BASE_URL', 'https://pay.iotec.io');

// ── Currency ──────────────────────────────────────────────────
// ioTec Pay only settles in ITX, UGX or USD.
define('IOTEC_DEFAULT_CURRENCY', 'UGX');

// ── Public Base URL (for reference / portal callback setup) ──
if (!defined('IOTEC_PUBLIC_BASE_URL')) {
    $scheme = 'https';
    $host   = $_SERVER['HTTP_HOST'] ?? 'chamafunds.com';
    define('IOTEC_PUBLIC_BASE_URL', $scheme . '://' . $host);
}
$_iotecScriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$_iotecBasePath  = rtrim($_iotecScriptDir === '/' ? '' : $_iotecScriptDir, '/');
$_iotecRootPath  = rtrim(dirname($_iotecBasePath), '/');
$_iotecRootPath  = (strlen($_iotecRootPath) > 1) ? $_iotecRootPath : '';

// Reference only — paste this into the ioTec portal's Callback URL field.
define('IOTEC_IPN_URL', IOTEC_PUBLIC_BASE_URL . $_iotecRootPath . '/ipn_handler.php');
