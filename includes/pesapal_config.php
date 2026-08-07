<?php
/**
 * Pesapal Configuration
 * Keep this file secure - DO NOT commit to public repositories
 */

// ── Environment ───────────────────────────────────────────────
// true  = sandbox (test mode, fake money)
// false = production (LIVE, real money)
define('PESAPAL_SANDBOX', false);   // ← PRODUCTION MODE (LIVE)

// ── API Credentials ───────────────────────────────────────────
// Get your LIVE keys from: https://pay.pesapal.com/merchant/settings/api
if (PESAPAL_SANDBOX) {
    // Sandbox credentials
    define('PESAPAL_CONSUMER_KEY',    'u+nAfIIT/y0vtZdwd4ypMumdpUUPmyYm');
    define('PESAPAL_CONSUMER_SECRET', 'r2hNkSEMzL9P4ByZiltITGsj/3g=');
} else {
    // Production credentials — replace with your real keys
    define('PESAPAL_CONSUMER_KEY',    'u+nAfIIT/y0vtZdwd4ypMumdpUUPmyYm');
    define('PESAPAL_CONSUMER_SECRET', 'r2hNkSEMzL9P4ByZiltITGsj/3g=');
}

// ── Public Base URL ───────────────────────────────────────────
// When running locally, set this to your ngrok HTTPS URL so
// Pesapal can reach your callback and IPN endpoints.
// Example: 'https://a1b2-105-163-0-42.ngrok-free.app'
// Leave as empty string ('') to auto-detect (works on live servers).
define('PESAPAL_NGROK_URL', '');    // ← paste your ngrok URL here when testing locally

// ── Auto-detect or use ngrok ──────────────────────────────────
if (!defined('PESAPAL_BASE_URL')) {
    if (PESAPAL_NGROK_URL !== '') {
        define('PESAPAL_BASE_URL', rtrim(PESAPAL_NGROK_URL, '/'));
    } else {
        // ALWAYS HTTPS for production. Do NOT trust $_SERVER['HTTPS'].
        $scheme = 'https'; 
        $host   = $_SERVER['HTTP_HOST'] ?? 'chamafunds.com';
        define('PESAPAL_BASE_URL', $scheme . '://' . $host);
    }
}

// ── Callback & IPN URLs ───────────────────────────────────────
// Auto-detect the subfolder path (e.g. /chama on local, / on live)
$_pesapalScriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
$_pesapalBasePath  = rtrim($_pesapalScriptDir === '/' ? '' : $_pesapalScriptDir, '/');
// Walk up to project root (this file is in includes/, so go 1 level up)
$_pesapalRootPath  = rtrim(dirname($_pesapalBasePath), '/');
// If we're already at root or the path looks off, just use the auto-detected base
$_pesapalRootPath  = (strlen($_pesapalRootPath) > 1) ? $_pesapalRootPath : '';

define('PESAPAL_CALLBACK_URL', PESAPAL_BASE_URL . $_pesapalRootPath . '/payment_callback.php');
define('PESAPAL_IPN_URL',      PESAPAL_BASE_URL . $_pesapalRootPath . '/ipn_handler.php');

// ── Currency ──────────────────────────────────────────────────
define('PESAPAL_DEFAULT_CURRENCY', 'UGX');
