<?php
/**
 * IPN Handler
 * Pesapal posts payment status updates here in the background.
 * This endpoint must respond with HTTP 200 quickly.
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/pesapal_functions.php';

// ── Log helper ────────────────────────────────────────────────
function logIpn($data) {
    $logFile = __DIR__ . '/ipn_logs.txt';
    $entry   = '[' . date('Y-m-d H:i:s') . '] ' . print_r($data, true) . "\n---\n";
    file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

logIpn(['request' => $_REQUEST, 'input' => file_get_contents('php://input')]);

$result = processPesapalIpn($conn);

logIpn(['result' => $result]);

http_response_code(200);
echo 'OK';
exit;
