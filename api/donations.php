<?php
// ============================================================
// ChamaFunds – api/donations.php
// Initiates Pesapal payment + lists donations
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ── INITIATE DONATION via Pesapal ─────────────────────────────
if ($action === 'submit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../includes/pesapal_functions.php';

    $campaignId = (int)($_POST['campaign_id'] ?? 0);
    $amount     = (float)($_POST['amount'] ?? 0);
    $donorPhone = trim($_POST['donor_phone'] ?? '');
    $donorName  = trim($_POST['donor_name']  ?? '');
    $donorEmail = trim($_POST['donor_email'] ?? '');
    $isAnon     = !empty($_POST['is_anonymous']) ? 1 : 0;
    $network    = trim($_POST['mobile_money_network'] ?? 'MTN Mobile Money');

    // ── Validation ────────────────────────────────────────────
    if ($campaignId <= 0 || $amount < 1000 || empty($donorPhone)) {
        echo json_encode([
            'success' => false,
            'message' => 'Campaign, phone and amount (min UGX 1,000) are required.'
        ]);
        exit;
    }
    if (!$isAnon && empty($donorName)) {
        echo json_encode([
            'success' => false,
            'message' => 'Enter your name or choose "Remain anonymous".'
        ]);
        exit;
    }

    // ── Verify campaign is still active ───────────────────────
    $camp = $conn->query(
        "SELECT campaign_id, status, currency FROM campaigns
         WHERE campaign_id = $campaignId LIMIT 1"
    );
    if (!$camp || $camp->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Campaign not found.']);
        exit;
    }
    $campRow = $camp->fetch_assoc();
    if ($campRow['status'] !== 'active') {
        echo json_encode([
            'success' => false,
            'message' => 'This campaign is not currently accepting donations.'
        ]);
        exit;
    }

    $currency   = $campRow['currency'] ?: 'UGX';
    $donorIdSql = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

    $donorData = [
        'donor_name'           => $isAnon ? 'Anonymous' : $donorName,
        'donor_email'          => $donorEmail,
        'donor_phone'          => $donorPhone,
        'is_anonymous'         => $isAnon,
        'mobile_money_network' => $network,
        'donor_id'             => $donorIdSql,
    ];

    // ── Call Pesapal ──────────────────────────────────────────
    $result = initiateDonationPayment($conn, $campaignId, $donorData, $amount, $currency);

    if (isset($result['error'])) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $result['error']]);
        exit;
    }

    // Return the Pesapal hosted-checkout URL to the front-end
    echo json_encode([
        'success'      => true,
        'redirect_url' => $result['redirect_url'],
        'donation_id'  => $result['donation_id'],
    ]);
    exit;
}

// ── LIST donations for a campaign ────────────────────────────
if ($action === 'list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $campaignId = (int)($_GET['campaign_id'] ?? 0);
    if ($campaignId <= 0) {
        echo json_encode(['success' => false, 'message' => 'campaign_id is required.']);
        exit;
    }
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $limit  = 20;
    $offset = ($page - 1) * $limit;

    $result = $conn->query(
        "SELECT donor_name, is_anonymous, amount, mobile_money_network, payment_date
         FROM donations
         WHERE campaign_id = $campaignId AND status = 'completed'
         ORDER BY payment_date DESC
         LIMIT $limit OFFSET $offset"
    );
    $rows = [];
    while ($r = $result->fetch_assoc()) {
        if ($r['is_anonymous']) $r['donor_name'] = 'Anonymous';
        $rows[] = $r;
    }
    echo json_encode(['success' => true, 'donations' => $rows]);
    exit;
}

// ── Admin list all donations ──────────────────────────────────
if ($action === 'admin_list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (($_SESSION['role'] ?? '') !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Admin only.']);
        exit;
    }
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $limit  = 25;
    $offset = ($page - 1) * $limit;

    $result = $conn->query(
        "SELECT d.*, c.title AS campaign_title
         FROM donations d
         JOIN campaigns c ON d.campaign_id = c.campaign_id
         ORDER BY d.created_at DESC
         LIMIT $limit OFFSET $offset"
    );
    $rows = [];
    while ($r = $result->fetch_assoc()) $rows[] = $r;
    $total = $conn->query("SELECT COUNT(*) FROM donations")->fetch_row()[0];
    echo json_encode(['success' => true, 'donations' => $rows, 'total' => (int)$total]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown action.']);
