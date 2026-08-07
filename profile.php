<?php
// ============================================================
// ChamaFunds – profile.php
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/config.php';
if (!isset($_SESSION['user_id'])) { header('Location: ' . BASE . '/login.php?msg=unauthorized'); exit; }

// $conn is set by config.php
$uid  = (int)$_SESSION['user_id'];

// Fetch fresh user data
$userRow = $conn->query(
    "SELECT * FROM users WHERE user_id = $uid LIMIT 1"
)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" /><meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Profile Settings – ChamaFunds</title>
  <meta name="robots" content="noindex,nofollow" />
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="<?= BASE ?>/css/style.css" />
</head>
<body>
<nav style="background:#fff;border-bottom:1px solid #e5e7eb;padding:14px 20px;display:none;align-items:center;justify-content:space-between;position:fixed;top:0;left:0;right:0;z-index:500;" id="mobileTopBar">
  <a href="<?= BASE ?>/index.php" style="display:flex;align-items:center;gap:8px;"><div class="navbar-logo">CF</div></a>
  <button id="mobileSidebarToggle" style="font-size:1.3rem;color:#6b7280;"><i class="fas fa-bars"></i></button>
</nav>
<div class="modal-overlay" id="sidebarOverlay" style="z-index:899;"></div>

<div class="dashboard-layout">
  <aside class="sidebar">
    <div class="sidebar-brand"><div class="navbar-logo">CF</div><span style="font-weight:800;color:#1A2A6C;">ChamaFunds</span></div>
    <nav class="sidebar-nav">
      <a href="<?= BASE ?>/dashboard.php" class="sidebar-link"><i class="fas fa-th-large"></i>Dashboard</a>
      <a href="<?= BASE ?>/create-campaign.php" class="sidebar-link"><i class="fas fa-plus-circle"></i>Create Campaign</a>
      <a href="<?= BASE ?>/withdraw.php" class="sidebar-link"><i class="fas fa-credit-card"></i>Withdrawals</a>
      <a href="<?= BASE ?>/profile.php" class="sidebar-link active"><i class="fas fa-cog"></i>Settings</a>
    </nav>
    <div class="sidebar-footer"><a href="<?= BASE ?>/logout.php">Logout</a></div>
  </aside>

  <main class="main-content">
    <div class="page-header"><h1>Profile Settings</h1><p>Manage your account details and preferences.</p></div>

    <div id="profileMsg" style="display:none;padding:12px 16px;border-radius:10px;font-size:.88rem;margin-bottom:20px;"></div>

    <div style="max-width:680px;">
      <!-- Avatar -->
      <div class="card" style="padding:28px;margin-bottom:24px;">
        <h2 style="font-weight:800;color:#1A2A6C;margin-bottom:20px;font-size:1rem;">Profile Photo</h2>
        <div style="display:flex;align-items:center;gap:20px;flex-wrap:wrap;">
          <div style="width:80px;height:80px;border-radius:50%;background:#e5e7eb;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:1.5rem;color:#1A2A6C;flex-shrink:0;overflow:hidden;">
            <?php if ($userRow['avatar_url']): ?>
              <img src="<?= htmlspecialchars($userRow['avatar_url']) ?>" style="width:100%;height:100%;object-fit:cover;" alt="Avatar" />
            <?php else: ?>
              <?= strtoupper(substr($userRow['full_name'], 0, 2)) ?>
            <?php endif; ?>
          </div>
          <div>
            <p style="font-size:.82rem;color:#6b7280;margin-bottom:8px;">Role: <strong style="color:<?= $userRow['role'] === 'admin' ? '#1e40af' : ($userRow['role'] === 'campaigner' ? '#6d28d9' : '#065f46') ?>;"><?= ucfirst($userRow['role']) ?></strong></p>
            <?php if ($userRow['is_verified']): ?>
            <span style="font-size:.72rem;background:#d1fae5;color:#065f46;padding:2px 8px;border-radius:99px;"><i class="fas fa-check-circle"></i> Verified</span>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Personal Info -->
      <div class="card" style="padding:28px;margin-bottom:24px;">
        <h2 style="font-weight:800;color:#1A2A6C;margin-bottom:20px;font-size:1rem;">Personal Information</h2>
        <form id="profileForm" enctype="multipart/form-data">
          <div class="grid-2">
            <div class="form-group">
              <label class="form-label">Full Name <span class="required">*</span></label>
              <input type="text" name="full_name" class="form-input" value="<?= htmlspecialchars($userRow['full_name']) ?>" required />
            </div>
            <div class="form-group">
              <label class="form-label">Email <span class="required">*</span></label>
              <input type="email" name="email" class="form-input" value="<?= htmlspecialchars($userRow['email']) ?>" required />
            </div>
            <div class="form-group">
              <label class="form-label">Phone Number <span class="required">*</span></label>
              <input type="tel" name="phone" class="form-input" value="<?= htmlspecialchars($userRow['phone']) ?>" required />
            </div>
            <div class="form-group">
              <label class="form-label">Country</label>
              <select name="country" class="form-input">
                <?php foreach (['Uganda','Kenya','Rwanda','Nigeria','Zambia','Senegal'] as $ctry): ?>
                <option <?= $userRow['country'] === $ctry ? 'selected' : '' ?>><?= $ctry ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="form-label">Profile Photo</label>
            <input type="file" name="avatar" class="form-input" accept="image/*" style="padding:8px;" />
            <p style="font-size:.75rem;color:#9ca3af;margin-top:4px;">JPG or PNG. Max 2MB.</p>
          </div>
          <button type="submit" id="profileBtn" class="btn btn-primary">Save Changes</button>
        </form>
      </div>

      <!-- Password -->
      <div class="card" style="padding:28px;margin-bottom:24px;">
        <h2 style="font-weight:800;color:#1A2A6C;margin-bottom:20px;font-size:1rem;">Change Password</h2>
        <form id="passwordForm">
          <div class="form-group">
            <label class="form-label">Current Password</label>
            <input type="password" name="current_password" class="form-input" placeholder="••••••••" required />
          </div>
          <div class="grid-2">
            <div class="form-group">
              <label class="form-label">New Password</label>
              <input type="password" name="new_password" id="newPw" class="form-input" placeholder="••••••••" required />
            </div>
            <div class="form-group">
              <label class="form-label">Confirm New Password</label>
              <input type="password" name="confirm_password" id="confirmPw" class="form-input" placeholder="••••••••" required />
            </div>
          </div>
          <button type="submit" id="pwBtn" class="btn btn-primary">Update Password</button>
        </form>
      </div>
    </div>
  </main>
</div>

<style>
@media(max-width:1023px){ .sidebar{display:none;} .sidebar.mobile-open{display:flex;position:fixed;left:0;top:0;bottom:0;z-index:900;} }
</style>
<script src="<?= BASE ?>/js/main.js"></script>
<script>
var mobileBar = document.getElementById('mobileTopBar');
function checkMobile(){ mobileBar.style.display = window.innerWidth < 1024 ? 'flex' : 'none'; }
checkMobile(); window.addEventListener('resize', checkMobile);
document.querySelector('.dashboard-layout').style.paddingTop = window.innerWidth < 1024 ? '60px' : '0';

function showMsg(msg, ok) {
  var el = document.getElementById('profileMsg');
  el.textContent = msg;
  el.style.cssText = 'display:block;padding:12px 16px;border-radius:10px;font-size:.88rem;margin-bottom:20px;background:' +
    (ok ? '#d1fae5;color:#065f46;' : '#fee2e2;color:#991b1b;');
  setTimeout(function(){ el.style.display='none'; }, 4000);
}

document.getElementById('profileForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  var btn = document.getElementById('profileBtn');
  btn.disabled = true; btn.textContent = 'Saving…';
  var fd = new FormData(this);
  fd.append('action', 'update_profile');
  var res  = await fetch('<?= BASE ?>/api/users.php?action=update_profile', {method:'POST', body: fd});
  var data = await res.json();
  showMsg(data.message, data.success);
  btn.disabled = false; btn.textContent = 'Save Changes';
});

document.getElementById('passwordForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  if (document.getElementById('newPw').value !== document.getElementById('confirmPw').value) {
    return showMsg('New passwords do not match.', false);
  }
  var btn = document.getElementById('pwBtn');
  btn.disabled = true; btn.textContent = 'Updating…';
  var fd = new FormData(this);
  fd.append('action', 'change_password');
  var res  = await fetch('<?= BASE ?>/api/users.php?action=change_password', {method:'POST', body: fd});
  var data = await res.json();
  showMsg(data.message, data.success);
  if (data.success) this.reset();
  btn.disabled = false; btn.textContent = 'Update Password';
});
</script>
</body>
</html>
