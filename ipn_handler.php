<?php
/**
 * IPN Handler
 * ioTec Pay posts payment status updates here in the background
 * (configured per-wallet in the ioTec Pay portal under Callback URLs).
 * This endpoint must respond with HTTP 200 quickly.
 */

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/iotec_functions.php';

// ── Log helper ────────────────────────────────────────────────
function logIpn($data) {
    $logFile = __DIR__ . '/ipn_logs.txt';
    $entry   = '[' . date('Y-m-d H:i:s') . '] ' . print_r($data, true) . "\n---\n";
    file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
}

logIpn(['request' => $_REQUEST, 'input' => file_get_contents('php://input')]);

$result = processIotecIpn($conn);

logIpn(['result' => $result]);

http_response_code(200);
echo 'OK';
exit;
