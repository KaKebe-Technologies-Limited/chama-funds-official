<?php
// ============================================================
// ChamaFunds – api/donations.php
// Initiates ioTec Pay mobile money payment + lists donations
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ── INITIATE DONATION via ioTec Pay ───────────────────────────
if ($action === 'submit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../includes/iotec_functions.php';

    $campaignId = (int)($_POST['campaign_id'] ?? 0);
    $amount     = (float)($_POST['amount'] ?? 0);
    $donorPhone = trim($_POST['donor_phone'] ?? '');
    $donorName  = trim($_POST['donor_name']  ?? '');
    $donorEmail = trim($_POST['donor_email'] ?? '');
    $isAnon     = !empty($_POST['is_anonymous']) ? 1 : 0;
    $network    = trim($_POST['mobile_money_network'] ?? 'Mobile Money');

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
    if ($network === 'Card Payment' && empty($donorEmail)) {
        echo json_encode([
            'success' => false,
            'message' => 'Email is required for card payments.'
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

    // ── Call ioTec Pay ──────────────────────────────────────────
    $result = initiateDonationPayment($conn, $campaignId, $donorData, $amount, $currency);

    if (isset($result['error'])) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $result['error']]);
        exit;
    }

    // Mobile money: payer gets a USSD/app prompt on their phone, no redirect.
    // Card: redirect_url sends the browser to PegPay's hosted payment page.
    echo json_encode([
        'success'      => true,
        'donation_id'  => $result['donation_id'],
        'status'       => $result['status'],
        'redirect_url' => $result['redirect_url'] ?? null,
    ]);
    exit;
}

// ── POLL DONATION STATUS (used by the "waiting for confirmation" UI) ─
if ($action === 'check_status' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    require_once __DIR__ . '/../includes/iotec_functions.php';

    $donationId = (int)($_GET['donation_id'] ?? 0);
    if ($donationId <= 0) {
        echo json_encode(['success' => false, 'message' => 'donation_id is required.']);
        exit;
    }

    $result = verifyIotecTransaction($conn, $donationId);
    echo json_encode($result);
    exit;
}

// ── RECONCILE stuck pending donations against ioTec (admin only) ─────
// Catches payments that actually succeeded on ioTec's side but never got
// finalized locally — e.g. the donor closed their browser before the
// on-page poll caught up, and the ioTec callback URL isn't set up yet.
if ($action === 'reconcile' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../includes/iotec_functions.php';

    if (($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin only.']);
        exit;
    }

    $summary = reconcilePendingDonations($conn, 50);
    echo json_encode(['success' => true] + $summary);
    exit;
}

// ── MANUALLY confirm a pending donation (admin only) ──────────────────
// For cases the admin has verified some other way (e.g. checked the
// mobile money statement directly) and doesn't want to wait on ioTec.
if ($action === 'admin_mark_completed' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../includes/iotec_functions.php';
    require_once __DIR__ . '/../includes/auth.php';

    if (($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin only.']);
        exit;
    }

    $donationId = (int)($_POST['donation_id'] ?? 0);
    $result = $conn->query(
        "SELECT donation_id, campaign_id, amount, currency, donor_name, status
         FROM donations WHERE donation_id = $donationId LIMIT 1"
    );
    $donation = $result ? $result->fetch_assoc() : null;
    if (!$donation) {
        echo json_encode(['success' => false, 'message' => 'Donation not found.']);
        exit;
    }
    if ($donation['status'] !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'This donation is already ' . $donation['status'] . '.']);
        exit;
    }

    $didUpdate = markDonationCompleted($conn, $donationId);
    if (!$didUpdate) {
        echo json_encode(['success' => false, 'message' => 'Could not confirm this donation. Please refresh and try again.']);
        exit;
    }

    logAdminAction($conn, 'Manually Confirmed Donation', 'donation', $donationId,
        $donation['donor_name'] ?: 'Anonymous',
        ['amount' => $donation['amount'], 'currency' => $donation['currency'], 'campaign_id' => $donation['campaign_id']]
    );

    echo json_encode(['success' => true, 'message' => 'Donation confirmed.']);
    exit;
}

// ── ADD a manual/offline contribution (admin only) ──────────────────
// For funds an admin knows were given outside the platform (cash, bank
// transfer, etc.) that should still count toward the campaign. Flagged
// via added_by_admin_id so it's always distinguishable from a real
// mobile-money/card donation for accountability.
if ($action === 'admin_add_manual' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../includes/iotec_functions.php';
    require_once __DIR__ . '/../includes/auth.php';

    if (($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Admin only.']);
        exit;
    }

    $campaignId = (int)($_POST['campaign_id'] ?? 0);
    $amount     = (float)($_POST['amount'] ?? 0);
    $donorName  = trim($_POST['donor_name'] ?? '');
    $isAnon     = !empty($_POST['is_anonymous']) ? 1 : 0;
    $note       = trim($_POST['note'] ?? '');
    $adminId    = (int)$_SESSION['user_id'];

    if ($campaignId <= 0 || $amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Campaign and a positive amount are required.']);
        exit;
    }
    if (!$isAnon && $donorName === '') {
        echo json_encode(['success' => false, 'message' => 'Enter a donor name, or mark it anonymous.']);
        exit;
    }

    $camp = $conn->query("SELECT campaign_id, title, currency FROM campaigns WHERE campaign_id = $campaignId LIMIT 1");
    if (!$camp || $camp->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Campaign not found.']);
        exit;
    }
    $campRow = $camp->fetch_assoc();

    $nameEsc = $conn->real_escape_string($isAnon ? 'Anonymous' : $donorName);
    $refEsc  = $conn->real_escape_string('ADMIN_' . uniqid());
    $currEsc = $conn->real_escape_string($campRow['currency'] ?: 'UGX');

    $conn->query(
        "INSERT INTO donations
             (campaign_id, donor_name, is_anonymous, amount, fee_percentage, status,
              transaction_reference, mobile_money_network, currency, added_by_admin_id)
         VALUES
             ($campaignId, '$nameEsc', $isAnon, $amount, 0, 'pending',
              '$refEsc', 'Manual Entry', '$currEsc', $adminId)"
    );
    if ($conn->error) {
        echo json_encode(['success' => false, 'message' => 'Failed to save: ' . $conn->error]);
        exit;
    }
    $newId = $conn->insert_id;

    // Reuses the same completion path as a real payment — bumps campaign
    // totals and notifies the campaign owner exactly as usual.
    markDonationCompleted($conn, $newId);

    logAdminAction($conn, 'Manually Added Funds', 'donation', $newId,
        $campRow['title'] . ' — ' . ($isAnon ? 'Anonymous' : $donorName),
        ['amount' => $amount, 'currency' => $campRow['currency'], 'campaign_id' => $campaignId, 'note' => $note]
    );

    echo json_encode(['success' => true, 'message' => 'Funds added.', 'donation_id' => $newId]);
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
