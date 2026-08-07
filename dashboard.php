<?php
// ============================================================
// ChamaFunds – dashboard.php (User Dashboard)
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE . '/login.php?msg=unauthorized');
    exit;
}

// ============================================================
// $conn is already set by config.php
// ============================================================
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection failed. Please try again later.");
}

$uid  = (int)$_SESSION['user_id'];
$user = $_SESSION['user'];

// My campaigns
$myCampaigns = $conn->query(
    "SELECT c.*, ROUND((c.raised_amount / c.goal_amount) * 100, 1) AS pct,
            DATEDIFF(c.end_date, NOW()) AS days_left
     FROM campaigns c
     WHERE c.campaigner_id = $uid
     ORDER BY c.created_at DESC"
);

// Stats
$totalRaised = $conn->query(
    "SELECT COALESCE(SUM(raised_amount),0) FROM campaigns WHERE campaigner_id = $uid"
)->fetch_row()[0];

$activeCnt = $conn->query(
    "SELECT COUNT(*) FROM campaigns WHERE campaigner_id = $uid AND status='active'"
)->fetch_row()[0];

$totalContribs = $conn->query(
    "SELECT COALESCE(SUM(contributor_count),0) FROM campaigns WHERE campaigner_id = $uid"
)->fetch_row()[0];

$pendingWd = $conn->query(
    "SELECT COALESCE(SUM(gross_amount),0) FROM withdrawals w
     JOIN campaigns c ON w.campaign_id = c.campaign_id
     WHERE c.campaigner_id = $uid AND w.status IN ('pending','approved')"
)->fetch_row()[0];

// Recent donations to my campaigns
$recentDonations = $conn->query(
    "SELECT d.donor_name, d.is_anonymous, d.amount, d.payment_date, c.title AS campaign_title, c.currency
     FROM donations d
     JOIN campaigns c ON d.campaign_id = c.campaign_id
     WHERE c.campaigner_id = $uid AND d.status='completed'
     ORDER BY d.payment_date DESC LIMIT 5"
);

// Unread notifications
$unreadCount = $conn->query(
    "SELECT COUNT(*) FROM notifications WHERE user_id = $uid AND is_read = 0"
)->fetch_row()[0];

// Available balance (raised - already withdrawn or requested)
$withdrawnTotal = $conn->query(
    "SELECT COALESCE(SUM(w.gross_amount),0)
     FROM withdrawals w
     JOIN campaigns c ON w.campaign_id = c.campaign_id
     WHERE c.campaigner_id = $uid AND w.status IN ('pending','approved','completed')"
)->fetch_row()[0];
$availableBalance = $totalRaised - $withdrawnTotal;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Dashboard – ChamaFunds</title>
  <link rel="icon" type="image/png" href="<?= BASE ?>/img/favicon.png" />
  <link rel="shortcut icon" type="image/png" href="<?= BASE ?>/img/favicon.png" />
  <link rel="apple-touch-icon" href="<?= BASE ?>/img/favicon.png" />
  <meta name="robots" content="noindex,nofollow" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="<?= BASE ?>/css/style.css" />
</head>
<body>

<!-- Mobile Top Bar -->
<nav style="background:#fff;border-bottom:1px solid #e5e7eb;padding:14px 20px;display:none;align-items:center;justify-content:space-between;position:fixed;top:0;left:0;right:0;z-index:500;" id="mobileTopBar">
  <a href="<?= BASE ?>/index.php" style="display:flex;align-items:center;gap:8px;"><div class="navbar-logo">CF</div><span style="font-weight:800;color:#1A2A6C;font-size:.9rem;">ChamaFunds</span></a>
  <button id="mobileSidebarToggle" style="font-size:1.3rem;color:#6b7280;"><i class="fas fa-bars"></i></button>
</nav>
<div class="modal-overlay" id="sidebarOverlay" style="z-index:899;"></div>

<div class="dashboard-layout">
  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sidebar-brand">
      <div class="navbar-logo">CF</div>
      <span style="font-weight:800;color:#1A2A6C;font-size:1rem;">ChamaFunds</span>
    </div>
    <nav class="sidebar-nav">
      <a href="<?= BASE ?>/dashboard.php" class="sidebar-link active"><i class="fas fa-th-large"></i>Dashboard</a>
      <a href="#myCampaignsSection" class="sidebar-link"><i class="fas fa-rocket"></i>My Campaigns</a>
      <a href="<?= BASE ?>/create-campaign.php" class="sidebar-link"><i class="fas fa-plus-circle"></i>Create Campaign</a>
      <a href="#recentDonationsSection" class="sidebar-link"><i class="fas fa-users"></i>Contributions</a>
      <a href="<?= BASE ?>/withdraw.php" class="sidebar-link"><i class="fas fa-credit-card"></i>Withdrawals</a>
      <a href="<?= BASE ?>/profile.php" class="sidebar-link"><i class="fas fa-cog"></i>Settings</a>
      <?php if ($_SESSION['role'] === 'admin'): ?>
      <a href="<?= BASE ?>/admin/index.php" class="sidebar-link" style="color:#FF6B4A;"><i class="fas fa-shield-alt"></i>Admin Panel</a>
      <?php endif; ?>
    </nav>
    <div class="sidebar-footer">
      <a href="<?= BASE ?>/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </aside>

  <!-- MAIN -->
  <main class="main-content">
    <div class="page-header">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;">
        <div>
          <h1>Welcome back, <?= htmlspecialchars(explode(' ', $user['full_name'])[0]) ?>! 👋</h1>
          <p>Here's what's happening with your campaigns.</p>
        </div>

    <!-- Campaigner Guide banner -->
    <div style="background:linear-gradient(135deg,rgba(255,107,74,.1),rgba(26,42,108,.07));
                border:1px solid rgba(255,107,74,.25);border-radius:14px;padding:14px 18px;
                display:flex;align-items:center;gap:14px;margin-bottom:24px;flex-wrap:wrap;">
      <div style="width:40px;height:40px;background:#FF6B4A;border-radius:10px;display:flex;
                  align-items:center;justify-content:center;flex-shrink:0;">
        <i class="fas fa-file-pdf" style="color:#fff;font-size:1rem;"></i>
      </div>
      <div style="flex:1;min-width:180px;">
        <p style="font-weight:700;color:#1A2A6C;font-size:.9rem;margin-bottom:2px;">Campaigner Guide Available</p>
        <p style="font-size:.78rem;color:#6b7280;">Read our guide to understand how campaigns, payouts, and verification work.</p>
      </div>
      <a href="<?= BASE ?>/admin/CampaignerDetails.pdf" target="_blank"
         style="background:#FF6B4A;color:#fff;padding:9px 18px;border-radius:99px;
                font-size:.82rem;font-weight:700;text-decoration:none;white-space:nowrap;flex-shrink:0;">
        <i class="fas fa-download" style="margin-right:6px;"></i>Read Guide
      </a>
    </div>
        <div style="display:flex;gap:10px;align-items:center;">
          <?php if ($unreadCount > 0): ?>
          <span style="background:#FF6B4A;color:#fff;font-size:.72rem;font-weight:700;padding:4px 8px;border-radius:99px;">
            <?= $unreadCount ?> new notification<?= $unreadCount > 1 ? 's' : '' ?>
          </span>
          <?php endif; ?>
          <a href="<?= BASE ?>/create-campaign.php" class="btn btn-primary btn-sm"><i class="fas fa-plus" style="margin-right:6px;"></i>New Campaign</a>
        </div>
      </div>
    </div>

    <!-- STATS -->
    <div class="grid-4" style="margin-bottom:28px;">
      <div class="stat-card">
        <p class="stat-label">Total Raised</p>
        <p class="stat-value">UGX <?= number_format($totalRaised) ?></p>
        <p class="stat-sub" style="color:#10b981;">Across all campaigns</p>
      </div>
      <div class="stat-card">
        <p class="stat-label">Active Campaigns</p>
        <p class="stat-value"><?= $activeCnt ?></p>
        <p class="stat-sub" style="color:#9ca3af;"><?= $myCampaigns ? $myCampaigns->num_rows : 0 ?> total campaigns</p>
      </div>
      <div class="stat-card">
        <p class="stat-label">Total Contributors</p>
        <p class="stat-value"><?= number_format($totalContribs) ?></p>
        <p class="stat-sub" style="color:#9ca3af;">Across all your campaigns</p>
      </div>
      <div class="stat-card">
        <p class="stat-label">Available Balance</p>
        <p class="stat-value" style="color:<?= $availableBalance > 0 ? '#10b981' : '#9ca3af' ?>;">UGX <?= number_format($availableBalance) ?></p>
        <p class="stat-sub" style="color:#f59e0b;">
          <?php if ($pendingWd > 0): ?>
          <i class="fas fa-clock" style="margin-right:4px;"></i>UGX <?= number_format($pendingWd) ?> pending
          <?php else: echo 'No pending withdrawals'; endif; ?>
        </p>
      </div>
    </div>

    <!-- MY CAMPAIGNS TABLE -->
    <div class="card" style="padding:24px;margin-bottom:24px;" id="myCampaignsSection">
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
        <h2 style="font-weight:800;color:#1A2A6C;">Your Campaigns</h2>
        <a href="<?= BASE ?>/create-campaign.php" class="btn btn-primary btn-sm"><i class="fas fa-plus" style="margin-right:6px;"></i>Create New</a>
      </div>
      <?php if ($myCampaigns && $myCampaigns->num_rows > 0): ?>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Campaign</th><th>Goal</th><th>Raised</th><th>Contributors</th><th>Status</th><th>Actions</th></tr></thead>
          <tbody>
            <?php
            $myCampaigns->data_seek(0);
            while ($c = $myCampaigns->fetch_assoc()):
              $pct = min(100, (float)$c['pct']);
            ?>
            <tr>
              <td>
                <p style="font-weight:700;color:#1A2A6C;"><?= htmlspecialchars($c['title']) ?></p>
                <p style="font-size:.75rem;color:#9ca3af;">
                  Created <?= date('M j Y', strtotime($c['created_at'])) ?>
                  <?= $c['days_left'] > 0 ? ' · ' . $c['days_left'] . ' days left' : '' ?>
                </p>
              </td>
              <td style="font-weight:600;"><?= $c['currency'] ?> <?= number_format($c['goal_amount']) ?></td>
              <td>
                <p style="font-size:.82rem;color:#6b7280;margin-bottom:4px;"><?= $c['currency'] ?> <?= number_format($c['raised_amount']) ?> <span style="color:#1A2A6C;font-weight:700;">(<?= $pct ?>%)</span></p>
                <div class="progress-wrap" style="min-width:100px;"><div class="progress-fill" data-width="<?= $pct ?>%"></div></div>
              </td>
              <td><?= $c['contributor_count'] ?></td>
              <td><span class="status-badge status-<?= $c['status'] ?>"><?= ucfirst($c['status']) ?></span></td>
              <td>
                <div style="display:flex;gap:10px;color:#9ca3af;font-size:.9rem;">
                  <a href="<?= BASE ?>/campaign-detail.php?id=<?= $c['campaign_id'] ?>" title="View" style="color:#1A2A6C;"><i class="fas fa-eye"></i></a>
                  <?php if (in_array($c['status'], ['draft','active','paused'])): ?>
                  <a href="<?= BASE ?>/edit-campaign.php?id=<?= $c['campaign_id'] ?>" title="Edit campaign" style="color:#f59e0b;"><i class="fas fa-edit"></i></a>
                  <?php endif; ?>
                  <?php if ($c['status'] === 'active' && $c['raised_amount'] > 0): ?>
                  <a href="<?= BASE ?>/withdraw.php?campaign_id=<?= $c['campaign_id'] ?>" title="Withdraw" style="color:#FF6B4A;"><i class="fas fa-credit-card"></i></a>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
      <?php else: ?>
      <div style="text-align:center;padding:40px 0;color:#9ca3af;">
        <i class="fas fa-rocket" style="font-size:3rem;margin-bottom:16px;display:block;opacity:.4;"></i>
        <p>You haven't created any campaigns yet.</p>
        <a href="<?= BASE ?>/create-campaign.php" class="btn btn-primary" style="margin-top:16px;">🚀 Start your first campaign</a>
      </div>
      <?php endif; ?>
    </div>

    <!-- BOTTOM GRID -->
    <div class="grid-2">
      <!-- Quick Actions -->
      <div class="card" style="padding:24px;">
        <h2 style="font-weight:800;color:#1A2A6C;margin-bottom:16px;">Quick Actions</h2>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
          <a href="<?= BASE ?>/create-campaign.php" class="card" style="padding:16px;text-align:center;text-decoration:none;">
            <div style="font-size:1.6rem;color:#1A2A6C;"><i class="fas fa-plus-circle"></i></div>
            <p style="font-size:.78rem;font-weight:600;color:#6b7280;margin-top:6px;">New Campaign</p>
          </a>
          <a href="<?= BASE ?>/campaign-drives.php" class="card" style="padding:16px;text-align:center;text-decoration:none;">
            <div style="font-size:1.6rem;color:#1A2A6C;"><i class="fas fa-share-alt"></i></div>
            <p style="font-size:.78rem;font-weight:600;color:#6b7280;margin-top:6px;">Browse Campaigns</p>
          </a>
          <a href="<?= BASE ?>/donate.php" class="card" style="padding:16px;text-align:center;text-decoration:none;">
            <div style="font-size:1.6rem;color:#10b981;"><i class="fas fa-heart"></i></div>
            <p style="font-size:.78rem;font-weight:600;color:#6b7280;margin-top:6px;">Donate</p>
          </a>
          <a href="<?= BASE ?>/withdraw.php" class="card" style="padding:16px;text-align:center;text-decoration:none;">
            <div style="font-size:1.6rem;color:#FF6B4A;"><i class="fas fa-credit-card"></i></div>
            <p style="font-size:.78rem;font-weight:600;color:#6b7280;margin-top:6px;">Withdraw Funds</p>
          </a>
        </div>
      </div>

      <!-- Recent Contributions -->
      <div class="card" style="padding:24px;" id="recentDonationsSection">
        <h2 style="font-weight:800;color:#1A2A6C;margin-bottom:16px;">Recent Contributions</h2>
        <?php if ($recentDonations && $recentDonations->num_rows > 0): ?>
          <?php while ($d = $recentDonations->fetch_assoc()): ?>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid #f3f4f6;">
            <div>
              <p style="font-weight:600;color:#1A2A6C;font-size:.88rem;"><?= $d['is_anonymous'] ? 'Anonymous' : htmlspecialchars($d['donor_name']) ?></p>
              <p style="font-size:.75rem;color:#9ca3af;"><?= htmlspecialchars($d['campaign_title']) ?></p>
            </div>
            <div style="text-align:right;">
              <p style="font-weight:700;color:#10b981;font-size:.9rem;">+ <?= $d['currency'] ?> <?= number_format($d['amount']) ?></p>
              <p style="font-size:.72rem;color:#9ca3af;"><?= date('M j', strtotime($d['payment_date'])) ?></p>
            </div>
          </div>
          <?php endwhile; ?>
        <?php else: ?>
          <p style="color:#9ca3af;font-size:.88rem;text-align:center;padding:20px 0;">No contributions yet.</p>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>

<script src="<?= BASE ?>/js/main.js"></script>
<script>
  var mobileBar = document.getElementById('mobileTopBar');
  function checkMobile() { mobileBar.style.display = window.innerWidth < 1024 ? 'flex' : 'none'; }
  checkMobile(); window.addEventListener('resize', checkMobile);
  document.querySelector('.dashboard-layout').style.paddingTop = window.innerWidth < 1024 ? '60px' : '0';
</script>
</body>
</html>