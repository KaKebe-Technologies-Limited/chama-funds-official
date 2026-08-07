<?php
// ============================================================
// ChamaFunds – admin/index.php (Admin Panel)
// ============================================================

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    require_once __DIR__ . '/../includes/config.php';
    header('Location: ' . BASE . '/login.php?msg=unauthorized');
    exit;
}

// ============================================================
// FIX: Load config first, then connection
// ============================================================
require_once __DIR__ . '/../includes/config.php';

// After require_once __DIR__ . '/../includes/config.php';
echo '<!-- BASE URL: ' . BASE . ' -->';


// Ensure database connection exists
if (!isset($conn) || !$conn) {
    $conn = require_once __DIR__ . '/../db/connection.php';
}

// If connection still fails, show error
if (!$conn) {
    die("Database connection failed. Please try again later.");
}

$admin = $_SESSION['user'];

// ── Live stats with error handling ─────────────────────────────
function getCount($conn, $query) {
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_row();
        return $row ? (int)$row[0] : 0;
    }
    return 0;
}

function getSum($conn, $query) {
    $result = $conn->query($query);
    if ($result) {
        $row = $result->fetch_row();
        return $row ? (float)$row[0] : 0;
    }
    return 0;
}

// Safe stats
$totalCampaigns    = getCount($conn, "SELECT COUNT(*) FROM campaigns");
$activeCampaigns   = getCount($conn, "SELECT COUNT(*) FROM campaigns WHERE status='active'");
$totalUsers        = getCount($conn, "SELECT COUNT(*) FROM users");
$totalDonations    = getSum($conn, "SELECT COALESCE(SUM(amount),0) FROM donations WHERE status='completed'");
$totalFees         = getSum($conn, "SELECT COALESCE(SUM(fee_amount),0) FROM donations WHERE status='completed'");
$pendingWd         = getCount($conn, "SELECT COUNT(*) FROM withdrawals WHERE status='pending'");
$newUsersWeek      = getCount($conn, "SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(NOW(),INTERVAL 7 DAY)");
$newCampsWeek      = getCount($conn, "SELECT COUNT(*) FROM campaigns WHERE created_at >= DATE_SUB(NOW(),INTERVAL 7 DAY)");

// Campaigns list
$allCampaigns = $conn->query(
    "SELECT c.*, u.full_name AS campaigner_name,
            ROUND((c.raised_amount/c.goal_amount)*100,1) AS pct
     FROM campaigns c JOIN users u ON c.campaigner_id=u.user_id
     ORDER BY c.created_at DESC"
);
if (!$allCampaigns) $allCampaigns = false;

// Users list
$allUsers = $conn->query(
    "SELECT user_id, full_name, email, phone, role, country, is_active, is_verified, created_at
     FROM users ORDER BY created_at DESC"
);
if (!$allUsers) $allUsers = false;

// Donations
$allDonations = $conn->query(
    "SELECT d.*, c.title AS campaign_title FROM donations d
     JOIN campaigns c ON d.campaign_id=c.campaign_id ORDER BY d.created_at DESC LIMIT 50"
);
if (!$allDonations) $allDonations = false;

// Pending withdrawals
$pendingWithdrawals = $conn->query(
    "SELECT w.*, c.title AS campaign_title, u.full_name AS campaigner_name, c.currency
     FROM withdrawals w
     JOIN campaigns c ON w.campaign_id=c.campaign_id
     JOIN users u ON w.campaigner_id=u.user_id
     WHERE w.status='pending' ORDER BY w.requested_at DESC"
);
if (!$pendingWithdrawals) $pendingWithdrawals = false;

// Countries
$countries = $conn->query("SELECT * FROM countries ORDER BY country_name");
if (!$countries) $countries = false;

// Settings
$settings = [];
$sRows = $conn->query("SELECT setting_key, setting_value FROM platform_settings");
if ($sRows) {
    while ($s = $sRows->fetch_assoc()) {
        $settings[$s['setting_key']] = $s['setting_value'];
    }
}

// Admin logs
$adminLogs = $conn->query(
    "SELECT l.*, u.full_name AS admin_name FROM admin_logs l
     JOIN users u ON l.admin_id=u.user_id ORDER BY l.created_at DESC LIMIT 30"
);
if (!$adminLogs) $adminLogs = false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Panel – ChamaFunds</title>
  <link rel="icon" type="image/png" href="<?= BASE ?>/img/favicon.png" />
  <link rel="shortcut icon" type="image/png" href="<?= BASE ?>/img/favicon.png" />
  <link rel="apple-touch-icon" href="<?= BASE ?>/img/favicon.png" />
  <meta name="robots" content="noindex,nofollow" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  
  <!-- ✅ CSS PATH - WORKS ON LOCAL AND LIVE -->
  <link rel="stylesheet" href="<?= BASE ?>/css/style.css" />
</head>
<body>
<!-- Mobile Top Bar -->
<nav style="background:#fff;border-bottom:1px solid #e5e7eb;padding:14px 20px;display:none;align-items:center;justify-content:space-between;position:fixed;top:0;left:0;right:0;z-index:500;" id="mobileTopBar">
  <div style="display:flex;align-items:center;gap:8px;"><div class="navbar-logo">CF</div><span style="font-weight:800;color:#1A2A6C;font-size:.9rem;">Admin</span></div>
  <button id="mobileSidebarToggle" style="font-size:1.3rem;color:#6b7280;"><i class="fas fa-bars"></i></button>
</nav>
<div class="modal-overlay" id="sidebarOverlay" style="z-index:899;"></div>

<div class="admin-layout">
  <!-- SIDEBAR -->
  <aside class="admin-sidebar">
    <div class="sidebar-brand" style="padding:16px 12px 24px;">
      <div class="navbar-logo">CF</div>
      <span style="font-weight:800;color:#1A2A6C;">Admin</span>
    </div>
    <nav class="sidebar-nav">
      <button class="tab-btn sidebar-link active" data-tab="tab-overview"><i class="fas fa-th-large" style="margin-right:10px;"></i>Overview</button>
      <button class="tab-btn sidebar-link" data-tab="tab-campaigns"><i class="fas fa-rocket" style="margin-right:10px;"></i>Campaigns</button>
      <button class="tab-btn sidebar-link" data-tab="tab-users"><i class="fas fa-users" style="margin-right:10px;"></i>Users</button>
      <button class="tab-btn sidebar-link" data-tab="tab-transactions"><i class="fas fa-credit-card" style="margin-right:10px;"></i>Transactions</button>
      <button class="tab-btn sidebar-link" data-tab="tab-withdrawals"><i class="fas fa-hand-holding-usd" style="margin-right:10px;"></i>Withdrawals <?php if ($pendingWd > 0): ?><span style="background:#FF6B4A;color:#fff;font-size:.65rem;padding:1px 6px;border-radius:99px;margin-left:4px;"><?= $pendingWd ?></span><?php endif; ?></button>
      <button class="tab-btn sidebar-link" data-tab="tab-countries"><i class="fas fa-globe-africa" style="margin-right:10px;"></i>Countries</button>
      <button class="tab-btn sidebar-link" data-tab="tab-settings"><i class="fas fa-cog" style="margin-right:10px;"></i>Settings</button>
      <button class="tab-btn sidebar-link" data-tab="tab-logs"><i class="fas fa-list" style="margin-right:10px;"></i>Audit Logs</button>
      <button class="tab-btn sidebar-link" data-tab="tab-analytics"><i class="fas fa-chart-line" style="margin-right:10px;"></i>Analytics</button>
      <hr style="border:none;border-top:1px solid #e5e7eb;margin:10px 12px;" />
      <a href="<?= BASE ?>/create-campaign.php" class="sidebar-link" style="background:linear-gradient(135deg,#FF6B4A,#e85a3a);color:#fff;border-radius:10px;margin:4px 0;font-weight:700;">
        <i class="fas fa-plus-circle" style="margin-right:10px;"></i>Create Campaign
      </a>
      <a href="<?= BASE ?>/dashboard.php" class="sidebar-link" style="color:#6b7280;">
        <i class="fas fa-th-large" style="margin-right:10px;"></i>My Dashboard
      </a>
    </nav>
  <div class="sidebar-footer">
  <!--<a href="<?= BASE ?>/dashboard.php" class="sidebar-link"><i class="fas fa-arrow-left"></i>Back to Dashboard</a>-->
  <a href="<?= BASE ?>/logout.php" class="sidebar-link" style="color:#ef4444;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </aside>

  <!-- MAIN -->
  <main class="admin-main">
    <div id="adminAlert" style="display:none;padding:12px 16px;border-radius:10px;font-size:.88rem;margin-bottom:20px;"></div>
    <div class="page-header">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
        <div>
          <h1>Good <?= date('H') < 12 ? 'morning' : (date('H') < 17 ? 'afternoon' : 'evening') ?>, <?= htmlspecialchars(explode(' ', $admin['full_name'])[0]) ?>! 👋</h1>
          <p>Platform overview — <?= date('F j, Y') ?></p>
        </div>
        <a href="<?= BASE ?>/create-campaign.php" class="btn btn-primary btn-sm">
          <i class="fas fa-plus" style="margin-right:6px;"></i>New Campaign
        </a>
      </div>
    </div>

    <!-- ══════════════ TAB: OVERVIEW ══════════════ -->
    <div class="tab-panel active" id="tab-overview">
      <div class="grid-4" style="margin-bottom:24px;">
        <div class="stat-card"><p class="stat-label">Total Campaigns</p><p class="stat-value"><?= number_format($totalCampaigns) ?></p><p class="stat-sub" style="color:#10b981;"><i class="fas fa-arrow-up"></i> +<?= $newCampsWeek ?> this week</p></div>
        <div class="stat-card"><p class="stat-label">Total Users</p><p class="stat-value"><?= number_format($totalUsers) ?></p><p class="stat-sub" style="color:#10b981;"><i class="fas fa-arrow-up"></i> +<?= $newUsersWeek ?> this week</p></div>
        <div class="stat-card"><p class="stat-label">Platform Revenue (Fees)</p><p class="stat-value" style="color:#FF6B4A;">UGX <?= number_format($totalFees) ?></p><p class="stat-sub" style="color:#9ca3af;">From completed donations</p></div>
        <div class="stat-card"><p class="stat-label">Total Contributions</p><p class="stat-value">UGX <?= number_format($totalDonations) ?></p><p class="stat-sub" style="color:#9ca3af;"><?= $activeCampaigns ?> active campaigns</p></div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;">
        <div class="card" style="padding:24px;"><p style="font-weight:700;color:#1A2A6C;margin-bottom:16px;">Contributions (Last 7 Days)</p><canvas id="contributionsChart" height="180"></canvas></div>
        <div class="card" style="padding:24px;"><p style="font-weight:700;color:#1A2A6C;margin-bottom:16px;">Campaigns by Category</p><canvas id="categoryChart" height="180"></canvas></div>
      </div>
      <div class="card" style="padding:24px;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
          <h2 style="font-weight:800;color:#1A2A6C;font-size:1rem;">Recent Transactions</h2>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Ref</th><th>Campaign</th><th>Donor</th><th>Amount</th><th>Fee</th><th>Net</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
              <?php if ($allDonations): $allDonations->data_seek(0); $i=0; while ($d = $allDonations->fetch_assoc() and $i < 8): $i++; ?>
              <tr>
                <td style="font-family:monospace;font-size:.72rem;color:#9ca3af;"><?= htmlspecialchars($d['transaction_reference'] ?? '—') ?></td>
                <td style="color:#6b7280;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($d['campaign_title']) ?></td>
                <td style="font-weight:600;color:#1A2A6C;"><?= $d['is_anonymous'] ? 'Anonymous' : htmlspecialchars($d['donor_name'] ?? '—') ?></td>
                <td style="color:#10b981;font-weight:700;">UGX <?= number_format($d['amount']) ?></td>
                <td style="color:#FF6B4A;">UGX <?= number_format($d['fee_amount']) ?></td>
                <td style="font-weight:600;">UGX <?= number_format($d['net_amount']) ?></td>
                <td><span class="status-badge status-<?= $d['status'] === 'completed' ? 'approved' : $d['status'] ?>"><?= ucfirst($d['status']) ?></span></td>
                <td style="font-size:.75rem;color:#9ca3af;"><?= date('M j', strtotime($d['created_at'])) ?></td>
              </tr>
              <?php endwhile; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ══════════════ TAB: CAMPAIGNS ══════════════ -->
    <div class="tab-panel" id="tab-campaigns">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
        <h2 style="font-weight:800;color:#1A2A6C;">All Campaigns</h2>
        <div style="display:flex;gap:12px;align-items:center;flex-wrap:wrap;">
          <div class="filter-bar" style="margin:0;">
            <div class="search-input-wrap" style="max-width:220px;"><i class="fas fa-search"></i><input type="text" id="campSearch" class="form-input" placeholder="Search…" oninput="filterTable('campsTable',this.value)" /></div>
          </div>
          <a href="<?= BASE ?>/create-campaign.php" class="btn btn-primary btn-sm">
            <i class="fas fa-plus" style="margin-right:6px;"></i>New Campaign
          </a>
        </div>
      </div>
      <div class="card" style="padding:0;overflow:hidden;">
        <div class="table-wrap">
          <table id="campsTable">
            <thead><tr><th>ID</th><th>Title</th><th>Campaigner</th><th>Goal</th><th>Raised</th><th>Progress</th><th>Status</th><th>Created</th><th>Actions</th></tr></thead>
            <tbody>
              <?php if ($allCampaigns): while ($c = $allCampaigns->fetch_assoc()): $pct = min(100,(float)$c['pct']); ?>
              <tr>
                <td style="font-size:.75rem;color:#9ca3af;">#<?= $c['campaign_id'] ?></td>
                <td style="font-weight:600;color:#1A2A6C;max-width:160px;"><a href="<?= BASE ?>/campaign-detail.php?id=<?= $c['campaign_id'] ?>" style="color:#1A2A6C;"><?= htmlspecialchars($c['title']) ?></a></td>
                <td><?= htmlspecialchars($c['campaigner_name']) ?></td>
                <td><?= $c['currency'] ?> <?= number_format($c['goal_amount']) ?></td>
                <td><?= $c['currency'] ?> <?= number_format($c['raised_amount']) ?></td>
                <td style="min-width:80px;"><div class="progress-wrap" style="margin:0;"><div class="progress-fill" data-width="<?= $pct ?>%"></div></div><span style="font-size:.72rem;color:#9ca3af;"><?= $pct ?>%</span></td>
                <td><span class="status-badge status-<?= $c['status'] ?>"><?= ucfirst($c['status']) ?></span></td>
                <td style="font-size:.75rem;color:#9ca3af;"><?= date('M j, Y', strtotime($c['created_at'])) ?></td>
                <td>
                  <div style="display:flex;gap:8px;font-size:.85rem;">
                    <a href="<?= BASE ?>/campaign-detail.php?id=<?= $c['campaign_id'] ?>" title="View" style="color:#1A2A6C;"><i class="fas fa-eye"></i></a>
                    <?php if ($c['status'] === 'active'): ?>
                    <button onclick="adminCampAction(<?= $c['campaign_id'] ?>,'paused')" title="Pause" style="color:#f59e0b;background:none;border:none;cursor:pointer;font-size:.85rem;"><i class="fas fa-pause"></i></button>
                    <?php elseif ($c['status'] === 'draft'): ?>
                    <button onclick="adminCampAction(<?= $c['campaign_id'] ?>,'active')" title="Activate" style="color:#10b981;background:none;border:none;cursor:pointer;font-size:.85rem;"><i class="fas fa-check"></i></button>
                    <?php elseif ($c['status'] === 'paused'): ?>
                    <button onclick="adminCampAction(<?= $c['campaign_id'] ?>,'active')" title="Resume" style="color:#10b981;background:none;border:none;cursor:pointer;font-size:.85rem;"><i class="fas fa-play"></i></button>
                    <?php endif; ?>
                    <button onclick="adminCampAction(<?= $c['campaign_id'] ?>,'flagged')" title="Flag" style="color:#ef4444;background:none;border:none;cursor:pointer;font-size:.85rem;"><i class="fas fa-flag"></i></button>
                    <button onclick="adminCampAction(<?= $c['campaign_id'] ?>,'suspended')" title="Suspend" style="color:#6b7280;background:none;border:none;cursor:pointer;font-size:.85rem;"><i class="fas fa-ban"></i></button>
                  </div>
                </td>
              </tr>
              <?php endwhile; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ══════════════ TAB: USERS ══════════════ -->
    <div class="tab-panel" id="tab-users">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
        <h2 style="font-weight:800;color:#1A2A6C;">All Users</h2>
        <div class="search-input-wrap" style="max-width:240px;"><i class="fas fa-search"></i><input type="text" class="form-input" placeholder="Search users…" oninput="filterTable('usersTable',this.value)" /></div>
      </div>
      <div class="card" style="padding:0;overflow:hidden;">
        <div class="table-wrap">
          <table id="usersTable">
            <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Country</th><th>Status</th><th>Joined</th><th>Actions</th></tr></thead>
            <tbody>
              <?php if ($allUsers): while ($u = $allUsers->fetch_assoc()):
                $roleColor = $u['role']==='admin' ? '#1e40af;background:#dbeafe' : ($u['role']==='campaigner' ? '#6d28d9;background:#ede9fe' : '#065f46;background:#d1fae5');
              ?>
              <tr>
                <td style="font-size:.75rem;color:#9ca3af;">#<?= $u['user_id'] ?></td>
                <td style="font-weight:600;color:#1A2A6C;"><?= htmlspecialchars($u['full_name']) ?></td>
                <td style="font-size:.82rem;"><?= htmlspecialchars($u['email']) ?></td>
                <td style="font-size:.82rem;"><?= htmlspecialchars($u['phone']) ?></td>
                <td><span style="font-size:.72rem;font-weight:700;color:<?= $roleColor ?>;padding:2px 8px;border-radius:99px;"><?= ucfirst($u['role']) ?></span></td>
                <td style="font-size:.82rem;"><?= htmlspecialchars($u['country'] ?? '—') ?></td>
                <td><span class="status-badge <?= $u['is_active'] ? 'status-active' : 'status-flagged' ?>"><?= $u['is_active'] ? 'Active' : 'Banned' ?></span></td>
                <td style="font-size:.75rem;color:#9ca3af;"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                <td>
                  <div style="display:flex;gap:8px;font-size:.85rem;">
                    <?php if ($u['user_id'] != $_SESSION['user_id']): ?>
                    <button onclick="toggleUser(<?= $u['user_id'] ?>,<?= $u['is_active'] ? 1 : 0 ?>)" title="<?= $u['is_active'] ? 'Ban' : 'Unban' ?>" style="color:<?= $u['is_active'] ? '#f59e0b' : '#10b981' ?>;background:none;border:none;cursor:pointer;font-size:.85rem;">
                      <i class="fas fa-<?= $u['is_active'] ? 'ban' : 'check' ?>"></i>
                    </button>
                    <select onchange="updateRole(<?= $u['user_id'] ?>,this.value)" style="font-size:.75rem;padding:2px 6px;border:1px solid #e5e7eb;border-radius:6px;color:#6b7280;">
                      <option value="donor"      <?= $u['role']==='donor'      ?'selected':'' ?>>Donor</option>
                      <option value="campaigner" <?= $u['role']==='campaigner' ?'selected':'' ?>>Campaigner</option>
                      <option value="admin"      <?= $u['role']==='admin'      ?'selected':'' ?>>Admin</option>
                    </select>
                    <?php else: ?>
                    <span style="font-size:.75rem;color:#9ca3af;">You</span>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
              <?php endwhile; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ══════════════ TAB: TRANSACTIONS ══════════════ -->
    <div class="tab-panel" id="tab-transactions">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
        <h2 style="font-weight:800;color:#1A2A6C;">All Transactions</h2>
        <div class="search-input-wrap" style="max-width:260px;"><i class="fas fa-search"></i><input type="text" class="form-input" placeholder="Search by ref or donor…" oninput="filterTable('txTable',this.value)" /></div>
      </div>
      <div class="card" style="padding:0;overflow:hidden;">
        <div class="table-wrap">
          <table id="txTable">
            <thead><tr><th>Ref</th><th>Campaign</th><th>Donor</th><th>Network</th><th>Amount</th><th>Fee</th><th>Net</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
              <?php if ($allDonations): $allDonations->data_seek(0); while ($d = $allDonations->fetch_assoc()): ?>
              <tr>
                <td style="font-family:monospace;font-size:.72rem;color:#9ca3af;"><?= htmlspecialchars($d['transaction_reference'] ?? '—') ?></td>
                <td style="color:#6b7280;font-size:.82rem;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars($d['campaign_title']) ?></td>
                <td style="font-weight:600;color:#1A2A6C;font-size:.82rem;"><?= $d['is_anonymous'] ? 'Anonymous' : htmlspecialchars($d['donor_name'] ?? '—') ?></td>
                <td style="font-size:.78rem;color:#6b7280;"><?= htmlspecialchars($d['mobile_money_network']) ?></td>
                <td style="color:#10b981;font-weight:700;">UGX <?= number_format($d['amount']) ?></td>
                <td style="color:#FF6B4A;font-size:.82rem;">UGX <?= number_format($d['fee_amount']) ?></td>
                <td style="font-weight:600;font-size:.82rem;">UGX <?= number_format($d['net_amount']) ?></td>
                <td><span class="status-badge status-<?= $d['status']==='completed'?'approved':$d['status'] ?>"><?= ucfirst($d['status']) ?></span></td>
                <td style="font-size:.75rem;color:#9ca3af;"><?= date('M j, Y', strtotime($d['created_at'])) ?></td>
              </tr>
              <?php endwhile; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ══════════════ TAB: WITHDRAWALS ══════════════ -->
    <div class="tab-panel" id="tab-withdrawals">
      <h2 style="font-weight:800;color:#1A2A6C;margin-bottom:20px;">Withdrawal Requests</h2>
      <?php if ($pendingWithdrawals && $pendingWithdrawals->num_rows > 0): ?>
      <div class="card" style="padding:0;overflow:hidden;margin-bottom:24px;">
        <div style="padding:14px 20px;border-bottom:1px solid #e5e7eb;font-weight:700;color:#1A2A6C;font-size:.9rem;background:#fff5f3;">
          ⏳ Pending Approvals (<?= $pendingWithdrawals->num_rows ?>)
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr><th>ID</th><th>Campaigner</th><th>Campaign</th><th>Gross</th><th>Fee</th><th>Net</th><th>Mobile #</th><th>Network</th><th>Requested</th><th>Actions</th></tr></thead>
            <tbody>
              <?php while ($w = $pendingWithdrawals->fetch_assoc()): ?>
              <tr>
                <td style="font-size:.75rem;color:#9ca3af;">#<?= $w['withdrawal_id'] ?></td>
                <td style="font-weight:600;color:#1A2A6C;"><?= htmlspecialchars($w['campaigner_name']) ?></td>
                <td style="font-size:.82rem;color:#6b7280;"><?= htmlspecialchars($w['campaign_title']) ?></td>
                <td style="font-weight:600;"><?= $w['currency'] ?> <?= number_format($w['gross_amount']) ?></td>
                <td style="color:#FF6B4A;"><?= $w['currency'] ?> <?= number_format($w['fee_amount']) ?></td>
                <td style="color:#10b981;font-weight:700;"><?= $w['currency'] ?> <?= number_format($w['net_amount']) ?></td>
                <td style="font-family:monospace;font-size:.82rem;"><?= htmlspecialchars($w['mobile_money_number']) ?></td>
                <td style="font-size:.78rem;"><?= htmlspecialchars($w['mobile_money_network']) ?></td>
                <td style="font-size:.75rem;color:#9ca3af;"><?= date('M j, Y', strtotime($w['requested_at'])) ?></td>
                <td>
                  <div style="display:flex;gap:8px;">
                    <button onclick="approveWithdrawal(<?= $w['withdrawal_id'] ?>)" class="btn btn-sm" style="background:#d1fae5;color:#065f46;font-size:.75rem;">✓ Approve</button>
                    <button onclick="rejectWithdrawal(<?= $w['withdrawal_id'] ?>)" class="btn btn-sm" style="background:#fee2e2;color:#991b1b;font-size:.75rem;">✕ Reject</button>
                  </div>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php else: ?>
      <div class="card" style="padding:40px;text-align:center;color:#9ca3af;">
        <i class="fas fa-check-circle" style="font-size:2.5rem;color:#10b981;margin-bottom:12px;display:block;"></i>
        <p>No pending withdrawal requests. All caught up!</p>
      </div>
      <?php endif; ?>
    </div>

    <!-- ══════════════ TAB: COUNTRIES ══════════════ -->
    <div class="tab-panel" id="tab-countries">
      <h2 style="font-weight:800;color:#1A2A6C;margin-bottom:20px;">Supported Countries</h2>
      <div class="card" style="padding:0;overflow:hidden;margin-bottom:24px;">
        <div class="table-wrap">
          <table>
            <thead><tr><th>Country</th><th>Code</th><th>Currency</th><th>Payment Partner</th><th>Fee %</th><th>Campaigns</th><th>Users</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody>
              <?php if ($countries): while ($ct = $countries->fetch_assoc()): ?>
              <tr>
                <td style="font-weight:600;color:#1A2A6C;"><?= htmlspecialchars($ct['country_name']) ?></td>
                <td style="font-family:monospace;"><?= htmlspecialchars($ct['country_code']) ?></td>
                <td><?= htmlspecialchars($ct['currency_code']) ?> (<?= htmlspecialchars($ct['currency_symbol']) ?>)</td>
                <td style="font-size:.82rem;"><?= htmlspecialchars($ct['payment_partner'] ?? '—') ?></td>
                <td><?= $ct['fee_percentage'] ?>%</td>
                <td><?= $ct['campaign_count'] ?></td>
                <td><?= $ct['user_count'] ?></td>
                <td><span class="status-badge <?= $ct['is_active'] ? 'status-active' : 'status-paused' ?>"><?= $ct['is_active'] ? 'Active' : 'Inactive' ?></span></td>
                <td>
                  <button onclick="toggleCountry(<?= $ct['country_id'] ?>,<?= $ct['is_active'] ?>)"
                          class="btn btn-sm"
                          style="background:<?= $ct['is_active'] ? '#fee2e2;color:#991b1b' : '#d1fae5;color:#065f46' ?>;font-size:.72rem;">
                    <?= $ct['is_active'] ? 'Disable' : 'Enable' ?>
                  </button>
                </td>
              </tr>
              <?php endwhile; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <!-- Add Country Form -->
      <div class="card" style="padding:24px;max-width:480px;">
        <h3 style="font-weight:700;color:#1A2A6C;margin-bottom:16px;font-size:.95rem;">Add New Country</h3>
        <div id="countryMsg" style="display:none;padding:10px;border-radius:8px;font-size:.84rem;margin-bottom:12px;"></div>
        <div class="form-group"><label class="form-label">Country Name</label><input type="text" id="newCountryName" class="form-input" placeholder="e.g. Ghana" /></div>
        <div class="grid-2">
          <div class="form-group"><label class="form-label">Country Code</label><input type="text" id="newCountryCode" class="form-input" placeholder="GH" maxlength="2" /></div>
          <div class="form-group"><label class="form-label">Currency Code</label><input type="text" id="newCurrencyCode" class="form-input" placeholder="GHS" maxlength="3" /></div>
        </div>
        <div class="grid-2">
          <div class="form-group"><label class="form-label">Currency Symbol</label><input type="text" id="newCurrencySymbol" class="form-input" placeholder="₵" /></div>
          <div class="form-group"><label class="form-label">Payment Partner</label>
            <select id="newPartner" class="form-input"><option>PawaPay</option><option>Flutterwave</option><option>Wave</option><option>Other</option></select>
          </div>
        </div>
        <button onclick="addCountry()" class="btn btn-primary btn-sm">Add Country</button>
      </div>
    </div>

    <!-- ══════════════ TAB: SETTINGS ══════════════ -->
    <div class="tab-panel" id="tab-settings">
      <h2 style="font-weight:800;color:#1A2A6C;margin-bottom:24px;">Platform Settings</h2>
      <div id="settingsMsg" style="display:none;padding:12px 16px;border-radius:10px;font-size:.88rem;margin-bottom:20px;"></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;max-width:860px;">
        <!-- General -->
        <div class="card" style="padding:28px;">
          <h3 style="font-weight:700;color:#1A2A6C;margin-bottom:20px;">General</h3>
          <form id="settingsGeneral">
            <div class="form-group"><label class="form-label">Platform Name</label><input type="text" name="platform_name" class="form-input" value="<?= htmlspecialchars($settings['platform_name'] ?? 'ChamaFunds') ?>" /></div>
            <div class="form-group"><label class="form-label">Support Email</label><input type="email" name="platform_email" class="form-input" value="<?= htmlspecialchars($settings['platform_email'] ?? '') ?>" /></div>
            <div class="form-group"><label class="form-label">Support Phone</label><input type="text" name="platform_phone" class="form-input" value="<?= htmlspecialchars($settings['platform_phone'] ?? '') ?>" /></div>
            <button type="submit" class="btn btn-primary btn-sm">Save General</button>
          </form>
        </div>
        <!-- Fee Config -->
        <div class="card" style="padding:28px;">
          <h3 style="font-weight:700;color:#1A2A6C;margin-bottom:20px;">Fee Configuration</h3>
          <form id="settingsFees">
            <div class="form-group"><label class="form-label">Transaction Fee (%)</label><input type="number" name="platform_fee" class="form-input" value="<?= htmlspecialchars($settings['platform_fee'] ?? '7.5') ?>" min="0" max="25" step="0.5" /></div>
            <div class="form-group"><label class="form-label">Min Donation (UGX)</label><input type="number" name="min_donation_amount" class="form-input" value="<?= htmlspecialchars($settings['min_donation_amount'] ?? '1000') ?>" /></div>
            <div class="form-group"><label class="form-label">Max Donation (UGX)</label><input type="number" name="max_donation_amount" class="form-input" value="<?= htmlspecialchars($settings['max_donation_amount'] ?? '1000000') ?>" /></div>
            <button type="submit" class="btn btn-primary btn-sm">Save Fees</button>
          </form>
        </div>
        <!-- System Toggles -->
        <div class="card" style="padding:28px;grid-column:span 2;">
          <h3 style="font-weight:700;color:#1A2A6C;margin-bottom:20px;">System Toggles</h3>
          <div style="display:flex;flex-direction:column;gap:16px;">
            <?php
            $toggles = [
              ['key'=>'maintenance_mode',            'label'=>'Maintenance Mode',        'desc'=>'Disable public access while making changes'],
              ['key'=>'email_notifications_enabled', 'label'=>'Email Notifications',     'desc'=>'Send email alerts for withdrawals & donations'],
              ['key'=>'sms_notifications_enabled',   'label'=>'SMS Notifications',       'desc'=>'Send SMS alerts to campaigners & donors'],
            ];
            foreach ($toggles as $t):
              $val = ($settings[$t['key']] ?? 'false') === 'true';
            ?>
            <div style="display:flex;justify-content:space-between;align-items:center;padding:14px;background:#f9fafb;border-radius:12px;">
              <div><p style="font-weight:600;color:#1A2A6C;font-size:.9rem;"><?= $t['label'] ?></p><p style="font-size:.78rem;color:#9ca3af;"><?= $t['desc'] ?></p></div>
              <button onclick="toggleSetting('<?= $t['key'] ?>',<?= $val ? 'true' : 'false' ?>,this)"
                      style="width:44px;height:24px;border-radius:99px;border:none;cursor:pointer;background:<?= $val ? '#FF6B4A' : '#d1d5db' ?>;transition:.3s;position:relative;">
                <span style="position:absolute;width:18px;height:18px;background:#fff;border-radius:50%;top:3px;left:<?= $val ? '23px' : '3px' ?>;transition:.3s;"></span>
              </button>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- ══════════════ TAB: AUDIT LOGS ══════════════ -->
    <div class="tab-panel" id="tab-logs">
      <h2 style="font-weight:800;color:#1A2A6C;margin-bottom:20px;">Audit Logs</h2>
      <div class="card" style="padding:0;overflow:hidden;">
        <div class="table-wrap">
          <table>
            <thead><tr><th>Admin</th><th>Action</th><th>Target Type</th><th>Target</th><th>IP Address</th><th>Time</th></tr></thead>
            <tbody>
              <?php if ($adminLogs): while ($log = $adminLogs->fetch_assoc()): ?>
              <tr>
                <td style="font-weight:600;color:#1A2A6C;font-size:.85rem;"><?= htmlspecialchars($log['admin_name']) ?></td>
                <td style="font-size:.85rem;"><?= htmlspecialchars($log['action']) ?></td>
                <td><span style="font-size:.72rem;background:#f3f4f6;color:#6b7280;padding:2px 8px;border-radius:99px;"><?= htmlspecialchars($log['target_type'] ?? '—') ?></span></td>
                <td style="font-size:.82rem;color:#6b7280;"><?= htmlspecialchars($log['target_name'] ?? '—') ?></td>
                <td style="font-family:monospace;font-size:.75rem;color:#9ca3af;"><?= htmlspecialchars($log['ip_address'] ?? '—') ?></td>
                <td style="font-size:.75rem;color:#9ca3af;"><?= date('M j, Y H:i', strtotime($log['created_at'])) ?></td>
              </tr>
              <?php endwhile; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ══════════════ TAB: ANALYTICS ══════════════ -->
    <div class="tab-panel" id="tab-analytics">
      <h2 style="font-weight:800;color:#1A2A6C;margin-bottom:24px;">Advanced Analytics</h2>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
        <div class="card" style="padding:24px;"><p style="font-weight:700;color:#1A2A6C;margin-bottom:16px;">Revenue Over Time (2026)</p><canvas id="revenueChart" height="200"></canvas></div>
        <div class="card" style="padding:24px;"><p style="font-weight:700;color:#1A2A6C;margin-bottom:16px;">User Growth (Last 6 Months)</p><canvas id="userGrowthChart" height="200"></canvas></div>
        <div class="card" style="padding:24px;"><p style="font-weight:700;color:#1A2A6C;margin-bottom:16px;">Campaign Success Rate</p><canvas id="successRateChart" height="200"></canvas></div>
        <div class="card" style="padding:24px;"><p style="font-weight:700;color:#1A2A6C;margin-bottom:16px;">Top Campaigns by Amount Raised</p><canvas id="topCampaignsChart" height="200"></canvas></div>
      </div>
    </div>

  </main>
</div><!-- end admin-layout -->

<style>
@media(max-width:1023px){
  .admin-sidebar{display:none;}
  .admin-sidebar.mobile-open{display:flex;position:fixed;left:0;top:0;bottom:0;z-index:900;}
  #mobileTopBar{display:flex!important;}
  .admin-layout{padding-top:60px;}
}
.tab-btn.sidebar-link{font-weight:600;font-size:.85rem;width:100%;text-align:left;border:none;cursor:pointer;}
.tab-btn.sidebar-link.active{background:#1A2A6C;color:#fff;}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script src="<?= BASE ?>/js/main.js"></script>
<script>
// ── Helpers ──────────────────────────────────────────────────
function adminAlert(msg, ok) {
  var el = document.getElementById('adminAlert');
  el.textContent = msg;
  el.style.cssText = 'display:block;padding:12px 16px;border-radius:10px;font-size:.88rem;margin-bottom:20px;background:'
    + (ok ? '#d1fae5;color:#065f46;' : '#fee2e2;color:#991b1b;');
  setTimeout(function(){ el.style.display='none'; }, 4000);
}

async function apiPost(url, data) {
  var fd = new FormData();
  for (var k in data) fd.append(k, data[k]);
  var res  = await fetch(url, {method:'POST', body: fd});
  return res.json();
}

// ── Campaign actions ─────────────────────────────────────────
async function adminCampAction(id, status) {
  if (!confirm('Set campaign #' + id + ' to "' + status + '"?')) return;
  var d = await apiPost('<?= BASE ?>/api/campaigns.php?action=set_status', {campaign_id: id, status: status});
  adminAlert(d.message, d.success);
  if (d.success) setTimeout(function(){ location.reload(); }, 1200);
}

// ── Withdrawal actions ───────────────────────────────────────
async function approveWithdrawal(id) {
  if (!confirm('Approve withdrawal #' + id + '?')) return;
  var d = await apiPost('<?= BASE ?>/api/withdrawals.php?action=approve', {withdrawal_id: id});
  adminAlert(d.message, d.success);
  if (d.success) setTimeout(function(){ location.reload(); }, 1200);
}
async function rejectWithdrawal(id) {
  var reason = prompt('Reason for rejection (optional):') || 'Rejected by admin.';
  var d = await apiPost('<?= BASE ?>/api/withdrawals.php?action=reject', {withdrawal_id: id, reason: reason});
  adminAlert(d.message, d.success);
  if (d.success) setTimeout(function(){ location.reload(); }, 1200);
}

// ── User actions ─────────────────────────────────────────────
async function toggleUser(id, isActive) {
  var action = isActive ? 'ban' : 'unban';
  if (!confirm((isActive ? 'Ban' : 'Unban') + ' this user?')) return;
  var d = await apiPost('<?= BASE ?>/api/users.php?action=toggle_active', {user_id: id});
  adminAlert(d.message, d.success);
  if (d.success) setTimeout(function(){ location.reload(); }, 1200);
}
async function updateRole(id, role) {
  var d = await apiPost('<?= BASE ?>/api/users.php?action=update_role', {user_id: id, role: role});
  adminAlert(d.message, d.success);
}

// ── Country actions ──────────────────────────────────────────
async function toggleCountry(id, isActive) {
  var d = await apiPost('<?= BASE ?>/api/admin.php?action=toggle_country', {country_id: id});
  adminAlert(d.message, d.success);
  if (d.success) setTimeout(function(){ location.reload(); }, 1200);
}
async function addCountry() {
  var d = await apiPost('<?= BASE ?>/api/admin.php?action=add_country', {
    country_name:    document.getElementById('newCountryName').value,
    country_code:    document.getElementById('newCountryCode').value,
    currency_code:   document.getElementById('newCurrencyCode').value,
    currency_symbol: document.getElementById('newCurrencySymbol').value,
    payment_partner: document.getElementById('newPartner').value,
  });
  var msg = document.getElementById('countryMsg');
  msg.textContent = d.message;
  msg.style.cssText = 'display:block;padding:10px;border-radius:8px;font-size:.84rem;margin-bottom:12px;background:'
    + (d.success ? '#d1fae5;color:#065f46;' : '#fee2e2;color:#991b1b;');
  if (d.success) setTimeout(function(){ location.reload(); }, 1500);
}

// ── Settings forms ───────────────────────────────────────────
async function saveSettings(formId) {
  var form = document.getElementById(formId);
  var fd   = new FormData(form);
  fd.append('action', 'save_settings');
  var res  = await fetch('<?= BASE ?>/api/admin.php?action=save_settings', {method:'POST', body: fd});
  var data = await res.json();
  var msg  = document.getElementById('settingsMsg');
  msg.textContent = data.message;
  msg.style.cssText = 'display:block;padding:12px 16px;border-radius:10px;font-size:.88rem;margin-bottom:20px;background:'
    + (data.success ? '#d1fae5;color:#065f46;' : '#fee2e2;color:#991b1b;');
  setTimeout(function(){ msg.style.display='none'; }, 3500);
}
document.getElementById('settingsGeneral').addEventListener('submit', function(e){ e.preventDefault(); saveSettings('settingsGeneral'); });
document.getElementById('settingsFees').addEventListener('submit', function(e){ e.preventDefault(); saveSettings('settingsFees'); });

async function toggleSetting(key, currentVal, btn) {
  var newVal = !currentVal;
  var d = await apiPost('<?= BASE ?>/api/admin.php?action=save_settings', {[key]: newVal ? 'true' : 'false'});
  if (d.success) {
    btn.style.background = newVal ? '#FF6B4A' : '#d1d5db';
    var knob = btn.querySelector('span');
    knob.style.left = newVal ? '23px' : '3px';
    btn.setAttribute('onclick', "toggleSetting('" + key + "'," + newVal + ",this)");
  }
  adminAlert(d.message, d.success);
}

// ── Table search filter ───────────────────────────────────────
function filterTable(tableId, query) {
  var rows = document.getElementById(tableId).querySelectorAll('tbody tr');
  var q    = query.toLowerCase();
  rows.forEach(function(row) {
    row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
  });
}

// ── Mobile layout ─────────────────────────────────────────────
var mobileBar = document.getElementById('mobileTopBar');
function checkMobile(){ mobileBar.style.display = window.innerWidth < 1024 ? 'flex' : 'none'; }
checkMobile(); window.addEventListener('resize', checkMobile);
document.querySelector('.admin-layout').style.paddingTop = window.innerWidth < 1024 ? '60px' : '0';
</script>

<script>
// ── Charts (loaded from live DB data via API) ─────────────────
(async function initCharts() {
  var res  = await fetch('<?= BASE ?>/api/admin.php?action=stats');
  var data = await res.json();
  if (!data.success) return;

  var palette = { navy:'#1A2A6C', coral:'#FF6B4A', green:'#10b981', amber:'#f59e0b', purple:'#8b5cf6', blue:'#3b82f6' };

  // Contributions line chart
  var ctx1 = document.getElementById('contributionsChart');
  if (ctx1) new Chart(ctx1, {
    type: 'line',
    data: {
      labels: data.chart_days.length ? data.chart_days : ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'],
      datasets: [{
        label: 'UGX', data: data.chart_amounts.length ? data.chart_amounts : [0,0,0,0,0,0,0],
        borderColor: palette.coral, backgroundColor: 'rgba(255,107,74,.1)',
        fill: true, tension: .3, pointBackgroundColor: palette.navy
      }]
    },
    options: { responsive: true, plugins: { legend: { display: false } },
      scales: { y: { beginAtZero: true, ticks: { callback: function(v){ return 'UGX '+(v/1000)+'K'; } } } } }
  });

  // Category doughnut
  var ctx2 = document.getElementById('categoryChart');
  if (ctx2) new Chart(ctx2, {
    type: 'doughnut',
    data: {
      labels: data.cat_labels.length ? data.cat_labels : ['No Data'],
      datasets: [{ data: data.cat_counts.length ? data.cat_counts : [1],
        backgroundColor: [palette.navy, palette.coral, palette.green, palette.amber, palette.purple, palette.blue] }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } } }
  });

  // Revenue chart (analytics tab)
  var ctx3 = document.getElementById('revenueChart');
  if (ctx3) new Chart(ctx3, {
    type: 'line',
    data: {
      labels: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'],
      datasets: [{ label: 'Revenue (UGX M)',
        data: [1.2,1.5,1.1,1.8,2.2,1.9,2.5,2.8,3.1,2.7,3.4,3.9],
        borderColor: palette.navy, backgroundColor: 'rgba(26,42,108,.08)', fill: true, tension: .3 }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
  });

  // User growth bar
  var ctx4 = document.getElementById('userGrowthChart');
  if (ctx4) new Chart(ctx4, {
    type: 'bar',
    data: {
      labels: ['Jan','Feb','Mar','Apr','May','Jun'],
      datasets: [{ label: 'New Users',
        data: [120,210,180,310,260,380],
        backgroundColor: palette.coral }]
    },
    options: { responsive: true, plugins: { legend: { display: false } } }
  });

  // Success rate pie
  var ctx5 = document.getElementById('successRateChart');
  if (ctx5) new Chart(ctx5, {
    type: 'pie',
    data: {
      labels: ['Completed','Active','Paused','Flagged'],
      datasets: [{ data: [55,30,10,5],
        backgroundColor: [palette.green, palette.navy, palette.amber, '#ef4444'] }]
    },
    options: { responsive: true, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12 } } } }
  });

  // Top campaigns horizontal bar
  var ctx6 = document.getElementById('topCampaignsChart');
  if (ctx6) new Chart(ctx6, {
    type: 'bar',
    data: {
      labels: ['Flood Relief','Family Medical','Borehole','School Fees','Clean Water'],
      datasets: [{ label: 'UGX Raised',
        data: [8300000,3750000,6200000,2850000,1200000],
        backgroundColor: palette.coral }]
    },
    options: { indexAxis: 'y', responsive: true, plugins: { legend: { display: false } },
      scales: { x: { ticks: { callback: function(v){ return 'UGX '+(v/1000000).toFixed(1)+'M'; } } } } }
  });
})();
</script>
</body>
</html>