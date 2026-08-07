<?php
// ============================================================
// ChamaFunds – withdraw.php
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/config.php';
if (!isset($_SESSION['user_id'])) { header('Location: ' . BASE . '/login.php?msg=unauthorized'); exit; }

// $conn is set by config.php
$uid  = (int)$_SESSION['user_id'];
$user = $_SESSION['user'];

// Pre-select campaign if passed via query string
$preselectedCampaignId = (int)($_GET['campaign_id'] ?? 0);

// Campaigner's active campaigns with available balance
$campaigns = $conn->query(
    "SELECT c.campaign_id, c.title, c.currency, c.raised_amount,
            COALESCE((SELECT SUM(w2.gross_amount) FROM withdrawals w2
                      WHERE w2.campaign_id = c.campaign_id
                        AND w2.status IN ('pending','approved','completed')), 0) AS withdrawn,
            c.raised_amount - COALESCE((SELECT SUM(w2.gross_amount) FROM withdrawals w2
                      WHERE w2.campaign_id = c.campaign_id
                        AND w2.status IN ('pending','approved','completed')), 0) AS available
     FROM campaigns c
     WHERE c.campaigner_id = $uid AND c.raised_amount > 0
     ORDER BY c.created_at DESC"
);

// Withdrawal history
$history = $conn->query(
    "SELECT w.*, c.title AS campaign_title, c.currency
     FROM withdrawals w
     JOIN campaigns c ON w.campaign_id = c.campaign_id
     WHERE w.campaigner_id = $uid
     ORDER BY w.requested_at DESC LIMIT 20"
);

// Total available
$totalAvailable = $conn->query(
    "SELECT COALESCE(SUM(c.raised_amount),0) - COALESCE(
        (SELECT SUM(w2.gross_amount) FROM withdrawals w2
         JOIN campaigns c2 ON w2.campaign_id = c2.campaign_id
         WHERE c2.campaigner_id = $uid AND w2.status IN ('pending','approved','completed')), 0)
     FROM campaigns c WHERE c.campaigner_id = $uid"
)->fetch_row()[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Withdraw Funds – ChamaFunds</title>
  <link rel="icon" type="image/png" href="<?= BASE ?>/img/favicon.png" />
  <link rel="shortcut icon" type="image/png" href="<?= BASE ?>/img/favicon.png" />
  <link rel="apple-touch-icon" href="<?= BASE ?>/img/favicon.png" />
  <meta name="robots" content="noindex,nofollow" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="<?= BASE ?>/css/style.css" />
</head>
<body>
<nav style="background:#fff;border-bottom:1px solid #e5e7eb;padding:14px 20px;display:none;align-items:center;justify-content:space-between;position:fixed;top:0;left:0;right:0;z-index:500;" id="mobileTopBar">
  <a href="<?= BASE ?>/index.php" style="display:flex;align-items:center;gap:8px;"><div class="navbar-logo">CF</div><span style="font-weight:800;color:#1A2A6C;font-size:.9rem;">ChamaFunds</span></a>
  <button id="mobileSidebarToggle" style="font-size:1.3rem;color:#6b7280;"><i class="fas fa-bars"></i></button>
</nav>
<div class="modal-overlay" id="sidebarOverlay" style="z-index:899;"></div>

<div class="dashboard-layout">
  <aside class="sidebar">
    <div class="sidebar-brand"><div class="navbar-logo">CF</div><span style="font-weight:800;color:#1A2A6C;font-size:1rem;">ChamaFunds</span></div>
    <nav class="sidebar-nav">
      <a href="<?= BASE ?>/dashboard.php" class="sidebar-link"><i class="fas fa-th-large"></i>Dashboard</a>
      <a href="<?= BASE ?>/create-campaign.php" class="sidebar-link"><i class="fas fa-plus-circle"></i>Create Campaign</a>
      <a href="<?= BASE ?>/withdraw.php" class="sidebar-link active"><i class="fas fa-credit-card"></i>Withdrawals</a>
      <a href="<?= BASE ?>/profile.php" class="sidebar-link"><i class="fas fa-cog"></i>Settings</a>
    </nav>
    <div class="sidebar-footer"><a href="<?= BASE ?>/api/auth.php?action=logout" class="sidebar-link logout-link"><i class="fas fa-sign-out-alt"></i>Logout</a></div>
  </aside>

  <main class="main-content">
    <div class="page-header"><h1>Withdraw Funds</h1><p>Withdraw your campaign earnings to mobile money.</p></div>

    <div id="wdAlert" style="display:none;padding:12px 16px;border-radius:10px;font-size:.88rem;margin-bottom:20px;"></div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start;max-width:860px;">
      <!-- Withdraw Form -->
      <div class="card" style="padding:28px;">
        <!-- Balance -->
        <div style="background:linear-gradient(135deg,#1A2A6C,#2a3f8a);border-radius:16px;padding:24px;margin-bottom:24px;color:#fff;">
          <p style="font-size:.78rem;opacity:.7;text-transform:uppercase;letter-spacing:.08em;">Available Balance</p>
          <p style="font-size:2.2rem;font-weight:800;margin:6px 0;">UGX <?= number_format(max(0, $totalAvailable)) ?></p>
          <p style="font-size:.78rem;opacity:.6;">Across all your campaigns</p>
        </div>

        <form id="withdrawForm">
          <div class="form-group">
            <label class="form-label">Campaign <span class="required">*</span></label>
            <select id="wdCampaign" class="form-input" required>
              <option value="">— Select Campaign —</option>
              <?php if ($campaigns): while ($camp = $campaigns->fetch_assoc()): ?>
              <option value="<?= $camp['campaign_id'] ?>"
                      data-available="<?= max(0, $camp['available']) ?>"
                      data-currency="<?= $camp['currency'] ?>"
                      <?= $preselectedCampaignId === $camp['campaign_id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($camp['title']) ?> (Available: <?= $camp['currency'] ?> <?= number_format(max(0, $camp['available'])) ?>)
              </option>
              <?php endwhile; endif; ?>
            </select>
          </div>

          <div class="form-group">
            <label class="form-label">Withdraw Amount (UGX) <span class="required">*</span></label>
            <input type="number" id="withdrawAmount" class="form-input" placeholder="e.g. 200000" min="5000" required />
          </div>

          <!-- Fee Breakdown -->
          <div style="background:#f9fafb;border-radius:12px;padding:16px;margin-bottom:20px;font-size:.85rem;border:1px solid #e5e7eb;">
            <p style="font-weight:700;color:#1A2A6C;margin-bottom:10px;">Fee Breakdown</p>
            <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #e5e7eb;">
              <span style="color:#6b7280;">Gross Amount</span><span style="font-weight:600;color:#1A2A6C;" id="grossDisplay">UGX 0</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #e5e7eb;">
              <span style="color:#6b7280;">Platform Fee (7.5%)</span><span style="font-weight:600;color:#FF6B4A;" id="wdFeeDisplay">UGX 0</span>
            </div>
            <div style="display:flex;justify-content:space-between;padding:8px 0 0;">
              <span style="font-weight:700;color:#1A2A6C;">You Receive</span>
              <span style="font-weight:800;color:#10b981;font-size:1rem;" id="wdNetDisplay">UGX 0</span>
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Mobile Money Number <span class="required">*</span></label>
            <input type="tel" id="wdMomoNum" class="form-input" value="<?= htmlspecialchars($user['phone']) ?>" placeholder="256712345678" required />
          </div>
          <div class="form-group">
            <label class="form-label">Network</label>
            <select id="wdNetwork" class="form-input">
              <option>MTN Mobile Money</option><option>Airtel Money</option><option>Orange Money</option><option>Safaricom M-PESA</option>
            </select>
          </div>
          <button type="submit" id="wdBtn" class="btn btn-primary btn-block btn-lg" style="margin-top:8px;">
            <i class="fas fa-credit-card" style="margin-right:8px;"></i>Request Withdrawal
          </button>
        </form>
      </div>

      <!-- Withdrawal History -->
      <div class="card" style="padding:24px;">
        <h2 style="font-weight:800;color:#1A2A6C;margin-bottom:16px;font-size:1rem;">Withdrawal History</h2>
        <?php if ($history && $history->num_rows > 0): ?>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Date</th><th>Amount</th><th>Fee</th><th>Net</th><th>Status</th></tr></thead>
            <tbody>
              <?php while ($w = $history->fetch_assoc()): ?>
              <tr>
                <td style="font-size:.78rem;"><?= date('M j, Y', strtotime($w['requested_at'])) ?></td>
                <td style="font-weight:600;"><?= $w['currency'] ?> <?= number_format($w['gross_amount']) ?></td>
                <td style="color:#FF6B4A;"><?= $w['currency'] ?> <?= number_format($w['fee_amount']) ?></td>
                <td style="color:#10b981;font-weight:700;"><?= $w['currency'] ?> <?= number_format($w['net_amount']) ?></td>
                <td><span class="status-badge status-<?= $w['status'] ?>"><?= ucfirst($w['status']) ?></span></td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
        <?php else: ?>
        <p style="color:#9ca3af;font-size:.88rem;text-align:center;padding:20px 0;">No withdrawal history yet.</p>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>

<!-- Confirmation Modal -->
<div class="modal-overlay" id="withdrawModal">
  <div class="modal" style="max-width:380px;">
    <div class="modal-body" style="text-align:center;padding:36px 28px;">
      <div style="font-size:2.5rem;margin-bottom:14px;">💸</div>
      <h3 style="font-weight:800;color:#1A2A6C;margin-bottom:8px;">Withdrawal Submitted!</h3>
      <p style="color:#9ca3af;font-size:.88rem;margin-bottom:24px;">Your request is under review. Funds will be sent to your mobile money number once approved.</p>
      <button data-close-modal="withdrawModal" class="btn btn-secondary btn-block" onclick="location.reload()">Done</button>
    </div>
  </div>
</div>

<style>
@media(max-width:767px){ div[style*="grid-template-columns:1fr 1fr"]{display:block!important;} }
@media(max-width:1023px){ .sidebar{display:none;} .sidebar.mobile-open{display:flex;position:fixed;left:0;top:0;bottom:0;z-index:900;} }
</style>
<script src="<?= BASE ?>/js/main.js"></script>
<script>
var mobileBar = document.getElementById('mobileTopBar');
function checkMobile(){ mobileBar.style.display = window.innerWidth < 1024 ? 'flex' : 'none'; }
checkMobile(); window.addEventListener('resize', checkMobile);
document.querySelector('.dashboard-layout').style.paddingTop = window.innerWidth < 1024 ? '60px' : '0';

document.getElementById('withdrawAmount').addEventListener('input', function() {
  var amt = parseFloat(this.value) || 0;
  var fee = Math.round(amt * 0.075);
  var net = amt - fee;
  document.getElementById('grossDisplay').textContent  = 'UGX ' + amt.toLocaleString();
  document.getElementById('wdFeeDisplay').textContent  = 'UGX ' + fee.toLocaleString();
  document.getElementById('wdNetDisplay').textContent  = 'UGX ' + net.toLocaleString();
});

document.getElementById('withdrawForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  var btn    = document.getElementById('wdBtn');
  var alert  = document.getElementById('wdAlert');
  alert.style.display = 'none';
  btn.disabled = true;
  btn.textContent = 'Submitting…';

  var fd = new FormData();
  fd.append('action',                'request');
  fd.append('campaign_id',           document.getElementById('wdCampaign').value);
  fd.append('gross_amount',          document.getElementById('withdrawAmount').value);
  fd.append('mobile_money_number',   document.getElementById('wdMomoNum').value);
  fd.append('mobile_money_network',  document.getElementById('wdNetwork').value);

  try {
    var res  = await fetch('<?= BASE ?>/api/withdrawals.php?action=request', {method:'POST', body: fd});
    var data = await res.json();
    if (data.success) {
      document.getElementById('withdrawModal').classList.add('open');
    } else {
      alert.style.cssText = 'display:block;background:#fee2e2;color:#991b1b;padding:12px 16px;border-radius:10px;font-size:.88rem;margin-bottom:20px;';
      alert.textContent = data.message;
    }
  } catch(err) {
    alert.style.cssText = 'display:block;background:#fee2e2;color:#991b1b;padding:12px 16px;border-radius:10px;font-size:.88rem;margin-bottom:20px;';
    alert.textContent = 'Error. Please try again.';
  }
  btn.disabled = false;
  btn.innerHTML = '<i class="fas fa-credit-card" style="margin-right:8px;"></i>Request Withdrawal';
});
</script>
</body>
</html>
