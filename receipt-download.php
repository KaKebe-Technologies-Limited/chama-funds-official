<?php
// ============================================================
// ChamaFunds – receipt-download.php
// Streams a withdrawal's PDF receipt from persistent storage.
// This is a financial document — only the owning campaigner or
// an admin may fetch it.
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/config.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Not authenticated.');
}

$withdrawalId = (int)($_GET['id'] ?? 0);
$uid  = (int)$_SESSION['user_id'];
$role = $_SESSION['role'] ?? '';

$res = $conn->query("SELECT receipt_path, campaigner_id FROM withdrawals WHERE withdrawal_id = $withdrawalId LIMIT 1");
$w   = $res ? $res->fetch_assoc() : null;

if (!$w || empty($w['receipt_path'])) {
    http_response_code(404);
    exit('Receipt not found.');
}
if ($role !== 'admin' && (int)$w['campaigner_id'] !== $uid) {
    http_response_code(403);
    exit('Access denied.');
}

$filename = basename($w['receipt_path']);
$path = PERSISTENT_RECEIPTS_DIR . $filename;
if (!is_file($path)) {
    http_response_code(404);
    exit('Receipt file missing.');
}

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="' . $filename . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
