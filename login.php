<?php
// ============================================================
// ChamaFunds – login.php
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/config.php';

// Already logged in
if (!empty($_SESSION['user_id'])) {
    $dest = ($_SESSION['role'] === 'admin') ? '/admin/index.php' : '/dashboard.php';
    header("Location: " . BASE . $dest);
    exit;
}

$msg     = $_GET['msg'] ?? '';
$errMsg  = '';
$succMsg = '';
if ($msg === 'logged_out')    $succMsg = 'You have been logged out.';
if ($msg === 'session_expired') $errMsg = 'Your session expired. Please log in again.';
if ($msg === 'unauthorized')  $errMsg  = 'You need to log in to access that page.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Log In – ChamaFunds</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="<?= BASE ?>/css/style.css" />
</head>
<body>
<div class="auth-wrapper">
  <div class="auth-card">
    <div style="text-align:center;margin-bottom:28px;">
      <a href="<?= BASE ?>/index.php" style="display:inline-flex;align-items:center;gap:10px;text-decoration:none;">
        <div class="navbar-logo" style="width:48px;height:48px;font-size:1rem;">CF</div>
        <span style="font-weight:800;color:#1A2A6C;font-size:1.2rem;">ChamaFunds</span>
      </a>
      <h1 style="font-weight:800;color:#1A2A6C;font-size:1.4rem;margin-top:20px;">Welcome back</h1>
      <p style="color:#9ca3af;font-size:.88rem;">Log in to manage your campaigns</p>
    </div>

    <?php if ($errMsg): ?>
      <div style="background:#fee2e2;color:#991b1b;padding:12px 16px;border-radius:10px;font-size:.88rem;margin-bottom:16px;text-align:center;"><?= htmlspecialchars($errMsg) ?></div>
    <?php endif; ?>
    <?php if ($succMsg): ?>
      <div style="background:#d1fae5;color:#065f46;padding:12px 16px;border-radius:10px;font-size:.88rem;margin-bottom:16px;text-align:center;"><?= htmlspecialchars($succMsg) ?></div>
    <?php endif; ?>

    <div id="loginError" style="display:none;background:#fee2e2;color:#991b1b;padding:12px 16px;border-radius:10px;font-size:.88rem;margin-bottom:16px;text-align:center;"></div>

    <form id="loginForm">
      <div class="form-group">
        <label class="form-label">Email or Phone</label>
        <input type="text" id="identifier" name="identifier" class="form-input" placeholder="your@email.com or 256712..." required autofocus />
      </div>
      <div class="form-group" style="position:relative;">
        <label class="form-label">Password</label>
        <input type="password" id="loginPassword" name="password" class="form-input" placeholder="••••••••" required />
        <button type="button" onclick="togglePw(this)" style="position:absolute;right:14px;top:36px;color:#9ca3af;font-size:.85rem;background:none;border:none;cursor:pointer;">
          <i class="fas fa-eye"></i>
        </button>
      </div>
      <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <label style="display:flex;align-items:center;gap:8px;font-size:.85rem;color:#6b7280;cursor:pointer;">
          <input type="checkbox" name="remember" /> Remember me
        </label>
        <a href="#" style="font-size:.85rem;color:#FF6B4A;font-weight:600;">Forgot password?</a>
      </div>
      <button type="submit" id="loginBtn" class="btn btn-primary btn-block btn-lg">Log In</button>
    </form>

    <!-- Demo credentials hint -->
    <div style="margin-top:16px;padding:12px;background:#f9fafb;border-radius:10px;font-size:.78rem;color:#6b7280;text-align:center;">
      <strong>Demo:</strong> ot.sedrick@gmail.com / S3izE316
    </div>

    <p style="text-align:center;font-size:.88rem;color:#6b7280;margin-top:20px;">
      Don't have an account? <a href="<?= BASE ?>/signup.php" style="color:#FF6B4A;font-weight:700;">Sign Up</a>
    </p>
  </div>
</div>

<script>
// ============================================================
// Toggle password visibility
// ============================================================
function togglePw(btn) {
  var input = btn.parentElement.querySelector('input');
  var isText = input.type === 'text';
  input.type = isText ? 'password' : 'text';
  btn.innerHTML = isText ? '<i class="fas fa-eye"></i>' : '<i class="fas fa-eye-slash"></i>';
}

// ============================================================
// Login form submission with AJAX
// ============================================================
document.getElementById('loginForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    var btn = document.getElementById('loginBtn');
    var errDiv = document.getElementById('loginError');
    
    // Disable button and show loading
    btn.disabled = true;
    btn.textContent = 'Logging in…';
    errDiv.style.display = 'none';

    // Get form data
    var identifier = document.getElementById('identifier').value;
    var password = document.getElementById('loginPassword').value;

    // Send login request
    fetch('<?= BASE ?>/api/auth.php?action=login', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'identifier=' + encodeURIComponent(identifier) + '&password=' + encodeURIComponent(password)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // ✅ SUCCESS - Redirect to dashboard
            window.location.href = data.redirect;
        } else {
            // ❌ ERROR - Show message
            errDiv.textContent = data.message || 'Login failed. Please try again.';
            errDiv.style.display = 'block';
            btn.disabled = false;
            btn.textContent = 'Log In';
        }
    })
    .catch(error => {
        // ❌ NETWORK ERROR
        errDiv.textContent = 'Network error. Please check your connection.';
        errDiv.style.display = 'block';
        btn.disabled = false;
        btn.textContent = 'Log In';
        console.error('Login error:', error);
    });
});
</script>

</body>
</html>