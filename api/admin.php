<?php
// ============================================================
// ChamaFunds – api/admin.php
// Admin stats, settings, countries management
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin only.']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ── OVERVIEW STATS ────────────────────────────────────────────
if ($action === 'stats') {
    $totalCampaigns    = $conn->query("SELECT COUNT(*) FROM campaigns")->fetch_row()[0];
    $activeCampaigns   = $conn->query("SELECT COUNT(*) FROM campaigns WHERE status='active'")->fetch_row()[0];
    $totalUsers        = $conn->query("SELECT COUNT(*) FROM users")->fetch_row()[0];
    $totalDonations    = $conn->query("SELECT COALESCE(SUM(amount),0) FROM donations WHERE status='completed'")->fetch_row()[0];
    $totalFees         = $conn->query("SELECT COALESCE(SUM(fee_amount),0) FROM donations WHERE status='completed'")->fetch_row()[0];
    $pendingWithdrawals= $conn->query("SELECT COUNT(*) FROM withdrawals WHERE status='pending'")->fetch_row()[0];
    $pendingWdAmt      = $conn->query("SELECT COALESCE(SUM(gross_amount),0) FROM withdrawals WHERE status='pending'")->fetch_row()[0];
    $newUsersWeek      = $conn->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetch_row()[0];
    $newCampaignsWeek  = $conn->query("SELECT COUNT(*) FROM campaigns WHERE created_at >= DATE_SUB(NOW(),INTERVAL 7 DAY)")->fetch_row()[0];

    // Last 7 days donations chart data
    $chartResult = $conn->query(
        "SELECT DATE(payment_date) AS day, SUM(amount) AS total
         FROM donations
         WHERE status = 'completed' AND payment_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
         GROUP BY DATE(payment_date)
         ORDER BY day ASC"
    );
    $chartDays = []; $chartAmounts = [];
    while ($r = $chartResult->fetch_assoc()) {
        $chartDays[]    = date('D', strtotime($r['day']));
        $chartAmounts[] = (float)$r['total'];
    }

    // Category breakdown
    $catResult = $conn->query(
        "SELECT category, COUNT(*) AS cnt FROM campaigns WHERE status IN ('active','completed') GROUP BY category"
    );
    $catLabels = []; $catCounts = [];
    while ($r = $catResult->fetch_assoc()) {
        $catLabels[] = $r['category'];
        $catCounts[] = (int)$r['cnt'];
    }

    echo json_encode([
        'success'            => true,
        'total_campaigns'    => (int)$totalCampaigns,
        'active_campaigns'   => (int)$activeCampaigns,
        'total_users'        => (int)$totalUsers,
        'total_donations'    => (float)$totalDonations,
        'total_fees'         => (float)$totalFees,
        'pending_withdrawals'=> (int)$pendingWithdrawals,
        'pending_wd_amount'  => (float)$pendingWdAmt,
        'new_users_week'     => (int)$newUsersWeek,
        'new_campaigns_week' => (int)$newCampaignsWeek,
        'chart_days'         => $chartDays,
        'chart_amounts'      => $chartAmounts,
        'cat_labels'         => $catLabels,
        'cat_counts'         => $catCounts,
    ]);
    exit;
}

// ── GET SETTINGS ─────────────────────────────────────────────
if ($action === 'get_settings') {
    $result = $conn->query("SELECT setting_key, setting_value, setting_group FROM platform_settings ORDER BY setting_group, setting_key");
    $settings = [];
    while ($r = $result->fetch_assoc()) {
        $settings[$r['setting_key']] = ['value' => $r['setting_value'], 'group' => $r['setting_group']];
    }
    echo json_encode(['success' => true, 'settings' => $settings]);
    exit;
}

// ── SAVE SETTINGS ─────────────────────────────────────────────
if ($action === 'save_settings' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $allowed = [
        'platform_fee', 'min_donation_amount', 'max_donation_amount',
        'maintenance_mode', 'email_notifications_enabled', 'sms_notifications_enabled',
        'session_timeout', 'platform_name', 'platform_email', 'platform_phone',
    ];
    foreach ($allowed as $key) {
        if (isset($_POST[$key])) {
            $k = $conn->real_escape_string($key);
            $v = $conn->real_escape_string($_POST[$key]);
            $conn->query(
                "INSERT INTO platform_settings (setting_key, setting_value)
                 VALUES ('$k', '$v')
                 ON DUPLICATE KEY UPDATE setting_value = '$v', updated_at = NOW()"
            );
        }
    }
    echo json_encode(['success' => true, 'message' => 'Settings saved.']);
    exit;
}

// ── COUNTRIES: list ────────────────────────────────────────────
if ($action === 'list_countries') {
    $result = $conn->query("SELECT * FROM countries ORDER BY country_name");
    $rows   = [];
    while ($r = $result->fetch_assoc()) $rows[] = $r;
    echo json_encode(['success' => true, 'countries' => $rows]);
    exit;
}

// ── COUNTRIES: add ─────────────────────────────────────────────
if ($action === 'add_country' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = $conn->real_escape_string(trim($_POST['country_name'] ?? ''));
    $code     = strtoupper($conn->real_escape_string(trim($_POST['country_code'] ?? '')));
    $currency = strtoupper($conn->real_escape_string(trim($_POST['currency_code'] ?? '')));
    $symbol   = $conn->real_escape_string(trim($_POST['currency_symbol'] ?? ''));
    $partner  = $conn->real_escape_string(trim($_POST['payment_partner'] ?? 'PawaPay'));
    $fee      = (float)($_POST['fee_percentage'] ?? 7.5);

    if (!$name || !$code || !$currency) {
        echo json_encode(['success' => false, 'message' => 'Country name, code and currency are required.']);
        exit;
    }
    $conn->query(
        "INSERT INTO countries (country_name, country_code, currency_code, currency_symbol, payment_partner, is_active, fee_percentage)
         VALUES ('$name', '$code', '$currency', '$symbol', '$partner', 1, $fee)"
    );
    echo json_encode(['success' => true, 'message' => "Country $name added."]);
    exit;
}

// ── COUNTRIES: toggle active ───────────────────────────────────
if ($action === 'toggle_country' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['country_id'] ?? 0);
    $conn->query("UPDATE countries SET is_active = NOT is_active WHERE country_id = $id");
    echo json_encode(['success' => true, 'message' => 'Country status toggled.']);
    exit;
}

// ── ADMIN LOGS ────────────────────────────────────────────────
if ($action === 'logs') {
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $limit  = 25;
    $offset = ($page - 1) * $limit;
    $result = $conn->query(
        "SELECT l.*, u.full_name AS admin_name
         FROM admin_logs l JOIN users u ON l.admin_id = u.user_id
         ORDER BY l.created_at DESC LIMIT $limit OFFSET $offset"
    );
    $rows = [];
    while ($r = $result->fetch_assoc()) $rows[] = $r;
    echo json_encode(['success' => true, 'logs' => $rows]);
    exit;
}

// ── FEATURE / UNFEATURE campaign ──────────────────────────────
if ($action === 'toggle_featured' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['campaign_id'] ?? 0);
    $conn->query("UPDATE campaigns SET is_featured = NOT is_featured WHERE campaign_id = $id");
    echo json_encode(['success' => true, 'message' => 'Featured status toggled.']);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown action.']);
