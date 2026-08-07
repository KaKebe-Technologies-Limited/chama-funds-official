<?php
// ============================================================
// ChamaFunds – api/withdrawals.php
// Request, list, approve/reject withdrawals
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ── REQUEST withdrawal (logged-in campaigner) ─────────────────
if ($action === 'request' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
        exit;
    }

    $uid        = (int)$_SESSION['user_id'];
    $campaignId = (int)($_POST['campaign_id'] ?? 0);
    $gross      = (float)($_POST['gross_amount'] ?? 0);
    $momoNum    = $conn->real_escape_string(trim($_POST['mobile_money_number'] ?? ''));
    $momoNet    = $conn->real_escape_string(trim($_POST['mobile_money_network'] ?? ''));

    if ($gross < 5000) {
        echo json_encode(['success' => false, 'message' => 'Minimum withdrawal is UGX 5,000.']);
        exit;
    }
    if (empty($momoNum)) {
        echo json_encode(['success' => false, 'message' => 'Mobile money number is required.']);
        exit;
    }

    // Verify campaign ownership
    $camp = $conn->query(
        "SELECT campaign_id, title, raised_amount FROM campaigns
         WHERE campaign_id = $campaignId AND campaigner_id = $uid LIMIT 1"
    );
    if (!$camp || $camp->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Campaign not found or access denied.']);
        exit;
    }

    // Check already-withdrawn totals
    $withdrawn = $conn->query(
        "SELECT COALESCE(SUM(gross_amount),0) AS total
         FROM withdrawals
         WHERE campaign_id = $campaignId AND status IN ('pending','approved','completed')"
    )->fetch_row()[0];

    $campRow   = $camp->fetch_assoc();
    $available = $campRow['raised_amount'] - $withdrawn;

    if ($gross > $available) {
        echo json_encode(['success' => false, 'message' => "You can only withdraw up to " . number_format($available) . " (available balance)."]);
        exit;
    }

    $conn->query(
        "INSERT INTO withdrawals (campaign_id, campaigner_id, gross_amount, fee_percentage,
                                  mobile_money_number, mobile_money_network, status)
         VALUES ($campaignId, $uid, $gross, 7.50, '$momoNum', '$momoNet', 'pending')"
    );
    $wid = $conn->insert_id;

    $fee = round($gross * 0.075, 2);
    $net = $gross - $fee;

    echo json_encode([
        'success'       => true,
        'message'       => 'Withdrawal request submitted. You\'ll receive an SMS once approved.',
        'withdrawal_id' => $wid,
        'gross'         => $gross,
        'fee'           => $fee,
        'net'           => $net,
    ]);
    exit;
}

// ── LIST withdrawals for logged-in user ───────────────────────
if ($action === 'list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
        exit;
    }
    $uid  = (int)$_SESSION['user_id'];
    $role = $_SESSION['role'] ?? '';

    if ($role === 'admin') {
        $result = $conn->query(
            "SELECT w.*, c.title AS campaign_title, u.full_name AS campaigner_name
             FROM withdrawals w
             JOIN campaigns c ON w.campaign_id = c.campaign_id
             JOIN users u ON w.campaigner_id = u.user_id
             ORDER BY w.requested_at DESC LIMIT 50"
        );
    } else {
        $result = $conn->query(
            "SELECT w.*, c.title AS campaign_title
             FROM withdrawals w
             JOIN campaigns c ON w.campaign_id = c.campaign_id
             WHERE w.campaigner_id = $uid
             ORDER BY w.requested_at DESC LIMIT 50"
        );
    }
    $rows = [];
    while ($r = $result->fetch_assoc()) $rows[] = $r;
    echo json_encode(['success' => true, 'withdrawals' => $rows]);
    exit;
}

// ── ADMIN: approve / reject ────────────────────────────────────
if ($action === 'approve' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_SESSION['role'] ?? '') !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Admin only.']);
        exit;
    }
    $wid     = (int)($_POST['withdrawal_id'] ?? 0);
    $adminId = (int)$_SESSION['user_id'];
    $txRef   = 'MMT-WD-' . strtoupper(uniqid());

    $conn->query(
        "UPDATE withdrawals
         SET status = 'completed', approved_by = $adminId,
             approved_at = NOW(), completed_at = NOW(),
             transaction_reference = '$txRef'
         WHERE withdrawal_id = $wid AND status IN ('pending','approved')"
    );

    // Notify campaigner
    $w = $conn->query("SELECT campaigner_id, gross_amount FROM withdrawals WHERE withdrawal_id = $wid")->fetch_assoc();
    if ($w) {
        $amt         = number_format($w['gross_amount']);
        $msg         = $conn->real_escape_string("Your withdrawal of UGX $amt has been approved and is being processed.");
        $withdrawLink = $conn->real_escape_string(BASE . '/withdraw.php');
        $conn->query(
            "INSERT INTO notifications (user_id, type, title, message, link)
             VALUES ({$w['campaigner_id']}, 'withdrawal', 'Withdrawal Approved', '$msg', '$withdrawLink')"
        );
    }
    echo json_encode(['success' => true, 'message' => 'Withdrawal approved and processed.']);
    exit;
}

if ($action === 'reject' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_SESSION['role'] ?? '') !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Admin only.']);
        exit;
    }
    $wid    = (int)($_POST['withdrawal_id'] ?? 0);
    $reason = $conn->real_escape_string(trim($_POST['reason'] ?? 'Rejected by admin.'));

    $conn->query(
        "UPDATE withdrawals
         SET status = 'rejected', rejection_reason = '$reason'
         WHERE withdrawal_id = $wid"
    );
    echo json_encode(['success' => true, 'message' => 'Withdrawal rejected.']);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown action.']);
