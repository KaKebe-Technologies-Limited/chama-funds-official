<?php
// ============================================================
// ChamaFunds – signup.php  (Redesigned)
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/config.php';
if (!empty($_SESSION['user_id'])) { header('Location: ' . BASE . '/dashboard.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Sign Up – ChamaFunds</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="<?= BASE ?>/css/style.css" />
  <style>
    /* ── Page Background ───────────────────────────────────── */
    .signup-page {
      min-height: 100vh;
      display: flex;
      align-items: stretch;
      font-family: 'Inter', sans-serif;
    }

    /* ── Left Panel – Background Image ────────────────────── */
    .signup-left {
      flex: 1;
      position: relative;
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      padding: 48px;
      overflow: hidden;
      min-height: 100vh;
    }
    .signup-left-bg {
      position: absolute;
      inset: 0;
      background-image: url('https://images.unsplash.com/photo-1529156069898-49953e39b3ac?w=1200&q=80');
      background-size: cover;
      background-position: center;
      background-attachment: fixed;
    }
    .signup-left-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(
        160deg,
        rgba(26, 42, 108, 0.82) 0%,
        rgba(26, 42, 108, 0.55) 50%,
        rgba(0, 0, 0, 0.60) 100%
      );
    }
    .signup-left-content {
      position: relative;
      z-index: 2;
    }
    .signup-left-logo {
      display: inline-flex;
      align-items: center;
      gap: 10px;
      margin-bottom: auto;
      position: absolute;
      top: 48px;
      left: 48px;
      text-decoration: none;
    }
    .signup-left-logo-icon {
      width: 44px; height: 44px;
      background: #FF6B4A;
      border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      color: #fff; font-weight: 800; font-size: .85rem;
      flex-shrink: 0;
    }
    .signup-left-logo-name {
      font-weight: 800;
      font-size: 1.2rem;
      color: #fff;
    }
    .signup-left-tagline {
      font-size: clamp(1.6rem, 3.5vw, 2.4rem);
      font-weight: 800;
      color: #fff;
      line-height: 1.2;
      margin-bottom: 16px;
    }
    .signup-left-tagline span { color: #F59E0B; }
    .signup-left-sub {
      font-size: .95rem;
      color: rgba(255,255,255,.78);
      line-height: 1.7;
      max-width: 380px;
      margin-bottom: 32px;
    }
    .signup-stats {
      display: flex;
      gap: 24px;
      flex-wrap: wrap;
    }
    .signup-stat {
      background: rgba(255,255,255,.12);
      backdrop-filter: blur(8px);
      border: 1px solid rgba(255,255,255,.2);
      border-radius: 14px;
      padding: 14px 20px;
      text-align: center;
      min-width: 100px;
    }
    .signup-stat-value {
      font-size: 1.35rem;
      font-weight: 800;
      color: #F59E0B;
      display: block;
    }
    .signup-stat-label {
      font-size: .72rem;
      color: rgba(255,255,255,.7);
      margin-top: 2px;
      font-weight: 500;
    }
    .signup-trust-pills {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 24px;
    }
    .signup-trust-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      background: rgba(255,255,255,.13);
      backdrop-filter: blur(6px);
      border: 1px solid rgba(255,255,255,.18);
      border-radius: 99px;
      padding: 6px 14px;
      font-size: .76rem;
      color: rgba(255,255,255,.88);
      font-weight: 600;
    }
    .signup-trust-pill i { font-size: .75rem; color: #10B981; }

    /* ── Right Panel – Form ────────────────────────────────── */
    .signup-right {
      width: 520px;
      flex-shrink: 0;
      background: #f9fafb;
      display: flex;
      align-items: flex-start;
      justify-content: center;
      padding: 40px 40px;
      overflow-y: auto;
      min-height: 100vh;
    }
    .signup-form-wrap {
      width: 100%;
      max-width: 440px;
      padding: 8px 0 48px;
    }

    /* ── Card Glass Effect ─────────────────────────────────── */
    .signup-card {
      background: rgba(255,255,255,0.97);
      backdrop-filter: blur(20px);
      border-radius: 24px;
      box-shadow: 0 25px 60px rgba(0,0,0,.12);
      padding: 36px;
      border: 1px solid rgba(255,255,255,.6);
    }
    /* ── Form Heading ──────────────────────────────────────── */
    .signup-heading {
      font-size: 1.5rem;
      font-weight: 800;
      color: #1A2A6C;
      margin-bottom: 4px;
    }
    .signup-subheading {
      font-size: .88rem;
      color: #6b7280;
      margin-bottom: 28px;
    }

    /* ── Input with Icon ───────────────────────────────────── */
    .input-icon-wrap {
      position: relative;
    }
    .input-icon-wrap .input-icon-left {
      position: absolute;
      left: 14px;
      top: 50%;
      transform: translateY(-50%);
      color: #9ca3af;
      font-size: .88rem;
      pointer-events: none;
    }
    .input-icon-wrap .form-input {
      padding-left: 40px;
    }
    .input-icon-wrap .pw-toggle {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      color: #9ca3af;
      font-size: .82rem;
      background: none;
      border: none;
      cursor: pointer;
      padding: 4px;
      transition: color .2s;
    }
    .input-icon-wrap .pw-toggle:hover { color: #1A2A6C; }

    /* ── Password strength bar ─────────────────────────────── */
    .pw-strength-bar {
      display: flex;
      gap: 4px;
      margin-top: 6px;
    }
    .pw-strength-seg {
      flex: 1; height: 4px; border-radius: 99px;
      background: #e5e7eb;
      transition: background .3s;
    }
    .pw-strength-label {
      font-size: .72rem;
      margin-top: 4px;
      font-weight: 600;
      color: #9ca3af;
    }

    /* ── Role Selector ─────────────────────────────────────── */
    .role-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
      margin-bottom: 20px;
    }
    .role-card {
      border: 2px solid #e5e7eb;
      border-radius: 14px;
      padding: 16px 12px;
      cursor: pointer;
      text-align: center;
      transition: all .22s ease;
      background: #fff;
      position: relative;
      overflow: hidden;
    }
    .role-card:hover {
      border-color: #FF6B4A;
      background: #fff8f6;
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(255,107,74,.15);
    }
    .role-card.selected {
      border-color: #FF6B4A;
      background: linear-gradient(135deg, #fff8f6 0%, #fff3ef 100%);
      box-shadow: 0 6px 24px rgba(255,107,74,.18);
    }
    .role-card.selected::after {
      content: '\f00c';
      font-family: 'Font Awesome 6 Free';
      font-weight: 900;
      position: absolute;
      top: 8px; right: 10px;
      font-size: .7rem;
      color: #FF6B4A;
    }
    .role-card input[type="radio"] { display: none; }
    .role-emoji { font-size: 1.8rem; margin-bottom: 8px; display: block; }
    .role-title { font-weight: 700; color: #1A2A6C; font-size: .85rem; margin-bottom: 2px; }
    .role-desc  { font-size: .72rem; color: #9ca3af; }

    /* ── Error box ─────────────────────────────────────────── */
    .error-box {
      display: none;
      background: #fee2e2;
      color: #991b1b;
      padding: 12px 16px;
      border-radius: 12px;
      font-size: .86rem;
      margin-bottom: 18px;
      border-left: 4px solid #ef4444;
      animation: shake .4s ease;
    }
    @keyframes shake {
      0%,100%{transform:translateX(0)}
      20%{transform:translateX(-6px)}
      40%{transform:translateX(6px)}
      60%{transform:translateX(-4px)}
      80%{transform:translateX(4px)}
    }

    /* ── Submit button states ──────────────────────────────── */
    .btn-signup {
      width: 100%;
      justify-content: center;
      background: linear-gradient(135deg, #FF6B4A 0%, #e85a3a 100%);
      color: #fff;
      border: none;
      padding: 15px;
      border-radius: 14px;
      font-size: 1rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 10px;
      transition: all .25s ease;
      cursor: pointer;
      box-shadow: 0 6px 20px rgba(255,107,74,.35);
      margin-top: 4px;
    }
    .btn-signup:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0 10px 28px rgba(255,107,74,.45);
    }
    .btn-signup:disabled {
      opacity: .7;
      cursor: not-allowed;
      transform: none;
    }
    .btn-signup .spinner {
      width: 18px; height: 18px;
      border: 2.5px solid rgba(255,255,255,.4);
      border-top-color: #fff;
      border-radius: 50%;
      animation: spin .7s linear infinite;
      display: none;
    }
    @keyframes spin { to { transform: rotate(360deg); } }

    /* ── Divider ───────────────────────────────────────────── */
    .form-divider {
      display: flex; align-items: center; gap: 12px;
      margin: 16px 0; color: #9ca3af; font-size: .8rem;
    }
    .form-divider::before, .form-divider::after {
      content: ''; flex: 1; height: 1px; background: #e5e7eb;
    }

    /* ── Fade-in animation ─────────────────────────────────── */
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(20px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .signup-card { animation: fadeInUp .5s ease both; }

    /* ── Mobile stacked layout ─────────────────────────────── */
    @media (max-width: 900px) {
      .signup-left { display: none; }
      .signup-right {
        width: 100%;
        background: linear-gradient(135deg, #1A2A6C 0%, #2a3f8a 60%, #FF6B4A 100%);
        align-items: center;
        padding: 24px 16px;
      }
      .signup-card { box-shadow: 0 25px 60px rgba(0,0,0,.3); }
    }
    @media (max-width: 480px) {
      .signup-right { padding: 16px 12px; }
      .signup-card  { padding: 24px 18px; border-radius: 20px; }
      .role-grid    { grid-template-columns: 1fr 1fr; }
    }
  </style>
</head>
<body>
<div class="signup-page">

  <!-- ═══════════════════ LEFT PANEL ═══════════════════ -->
  <div class="signup-left">
    <div class="signup-left-bg"></div>
    <div class="signup-left-overlay"></div>

    <!-- Logo top-left -->
    <a href="<?= BASE ?>/index.php" class="signup-left-logo">
      <div class="signup-left-logo-icon">CF</div>
      <span class="signup-left-logo-name">ChamaFunds</span>
    </a>

    <!-- Bottom content -->
    <div class="signup-left-content">
      <h2 class="signup-left-tagline">
        Pool Money Together<br>for <span>What Matters</span>
      </h2>
      <p class="signup-left-sub">
        Uganda's leading mobile money crowdfunding platform. Launch a campaign
        or support causes you care about — transparent, fast, and built for Africa.
      </p>

      <div class="signup-stats">
        <div class="signup-stat">
          <span class="signup-stat-value">12+</span>
          <span class="signup-stat-label">Countries Live</span>
        </div>
        <div class="signup-stat">
          <span class="signup-stat-value">UGX 2B+</span>
          <span class="signup-stat-label">Total Raised</span>
        </div>
        <div class="signup-stat">
          <span class="signup-stat-value">50K+</span>
          <span class="signup-stat-label">Contributors</span>
        </div>
      </div>

      <div class="signup-trust-pills">
        <span class="signup-trust-pill"><i class="fas fa-check-circle"></i> Free to start</span>
        <span class="signup-trust-pill"><i class="fas fa-check-circle"></i> Same-day payout</span>
        <span class="signup-trust-pill"><i class="fas fa-check-circle"></i> MTN &amp; Airtel Money</span>
        <span class="signup-trust-pill"><i class="fas fa-check-circle"></i> Live tracking</span>
      </div>
    </div>
  </div>

  <!-- ═══════════════════ RIGHT PANEL ═══════════════════ -->
  <div class="signup-right">
    <div class="signup-form-wrap">
      <!-- Mobile-only logo -->
      <div style="text-align:center;margin-bottom:24px;display:none;" id="mobileLogo">
        <a href="<?= BASE ?>/index.php" style="display:inline-flex;align-items:center;gap:10px;text-decoration:none;">
          <div style="width:42px;height:42px;background:#FF6B4A;border-radius:12px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:.85rem;">CF</div>
          <span style="font-weight:800;color:#fff;font-size:1.15rem;">ChamaFunds</span>
        </a>
      </div>

      <div class="signup-card">
        <h1 class="signup-heading">Create your account</h1>
        <p class="signup-subheading">Join thousands pooling money across Africa 🌍</p>

        <!-- Error box -->
        <div id="signupError" class="error-box">
          <i class="fas fa-exclamation-circle" style="margin-right:6px;"></i>
          <span id="signupErrorMsg"></span>
        </div>

        <form id="signupForm" novalidate>

          <!-- Full Name -->
          <div class="form-group" style="margin-bottom:16px;">
            <label class="form-label">Full Name <span class="required">*</span></label>
            <div class="input-icon-wrap">
              <i class="fas fa-user input-icon-left"></i>
              <input type="text" name="full_name" id="full_name"
                     class="form-input" placeholder="Sarah Nakato" required autocomplete="name" />
            </div>
          </div>

          <!-- Email + Phone -->
          <div class="grid-2" style="gap:12px;margin-bottom:0;">
            <div class="form-group" style="margin-bottom:16px;">
              <label class="form-label">Email <span class="required">*</span></label>
              <div class="input-icon-wrap">
                <i class="fas fa-envelope input-icon-left"></i>
                <input type="email" name="email" id="email"
                       class="form-input" placeholder="you@email.com" required autocomplete="email" />
              </div>
            </div>
            <div class="form-group" style="margin-bottom:16px;">
              <label class="form-label">Phone <span class="required">*</span></label>
              <div class="input-icon-wrap">
                <i class="fas fa-phone input-icon-left"></i>
                <input type="tel" name="phone" id="phone"
                       class="form-input" placeholder="256712345678" required autocomplete="tel" />
              </div>
            </div>
          </div>

          <!-- Password + Confirm -->
          <div class="grid-2" style="gap:12px;margin-bottom:0;">
            <div class="form-group" style="margin-bottom:4px;">
              <label class="form-label">Password <span class="required">*</span></label>
              <div class="input-icon-wrap">
                <i class="fas fa-lock input-icon-left"></i>
                <input type="password" id="password" name="password"
                       class="form-input" placeholder="••••••••" required autocomplete="new-password" />
                <button type="button" class="pw-toggle" onclick="togglePw('password',this)" aria-label="Toggle password">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
              <!-- Strength bar -->
              <div class="pw-strength-bar" id="pwStrengthBar">
                <div class="pw-strength-seg" id="seg1"></div>
                <div class="pw-strength-seg" id="seg2"></div>
                <div class="pw-strength-seg" id="seg3"></div>
                <div class="pw-strength-seg" id="seg4"></div>
              </div>
              <div class="pw-strength-label" id="pwStrengthLabel"></div>
            </div>
            <div class="form-group" style="margin-bottom:4px;">
              <label class="form-label">Confirm Password <span class="required">*</span></label>
              <div class="input-icon-wrap">
                <i class="fas fa-lock input-icon-left"></i>
                <input type="password" id="confirmPassword" name="confirm_password"
                       class="form-input" placeholder="••••••••" required autocomplete="new-password" />
                <button type="button" class="pw-toggle" onclick="togglePw('confirmPassword',this)" aria-label="Toggle confirm password">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Country -->
          <div class="form-group" style="margin-bottom:16px;margin-top:12px;">
            <label class="form-label">Country</label>
            <div class="input-icon-wrap">
              <i class="fas fa-globe-africa input-icon-left"></i>
              <select name="country" class="form-input">
                <option>Uganda</option>
                <option>Kenya</option>
                <option>Rwanda</option>
                <option>Tanzania</option>
                <option>Nigeria</option>
                <option>Ghana</option>
                <option>Zambia</option>
                <option>Senegal</option>
              </select>
            </div>
          </div>

          <!-- Role Selector -->
          <div class="form-group" style="margin-bottom:18px;">
            <label class="form-label">I want to… <span class="required">*</span></label>
            <div class="role-grid">
              <label class="role-card" id="roleCreator" onclick="selectRole('creator')" tabindex="0">
                <input type="radio" name="role" value="campaigner" />
                <span class="role-emoji">🚀</span>
                <p class="role-title">Start Campaigns</p>
                <p class="role-desc">Raise money for causes</p>
              </label>
              <label class="role-card" id="roleDonor" onclick="selectRole('donor')" tabindex="0">
                <input type="radio" name="role" value="donor" />
                <span class="role-emoji">❤️</span>
                <p class="role-title">Donate to Causes</p>
                <p class="role-desc">Support campaigns</p>
              </label>
            </div>
          </div>

          <!-- Terms -->
          <div style="display:flex;align-items:flex-start;gap:10px;margin-bottom:20px;">
            <input type="checkbox" id="agreeTerms" required
                   style="margin-top:2px;width:16px;height:16px;cursor:pointer;accent-color:#FF6B4A;flex-shrink:0;" />
            <label for="agreeTerms" style="font-size:.82rem;color:#6b7280;line-height:1.5;">
              I agree to the <a href="#" style="color:#FF6B4A;font-weight:600;">Terms of Service</a>
              and <a href="#" style="color:#FF6B4A;font-weight:600;">Privacy Policy</a>
            </label>
          </div>

          <!-- Submit -->
          <button type="submit" id="signupBtn" class="btn-signup">
            <div class="spinner" id="btnSpinner"></div>
            <i class="fas fa-user-plus" id="btnIcon"></i>
            <span id="btnText">Create Account</span>
          </button>
        </form>

        <div class="form-divider">or</div>

        <p style="text-align:center;font-size:.88rem;color:#6b7280;">
          Already have an account?
          <a href="<?= BASE ?>/login.php" style="color:#FF6B4A;font-weight:700;"> Log In</a>
        </p>
      </div><!-- /.signup-card -->
    </div><!-- /.signup-form-wrap -->
  </div><!-- /.signup-right -->
</div><!-- /.signup-page -->

<script src="<?= BASE ?>/js/main.js"></script>
<script>
// ── Toggle password visibility ──────────────────────────────
function togglePw(id, btn) {
  var input = document.getElementById(id);
  var isText = input.type === 'text';
  input.type = isText ? 'password' : 'text';
  btn.innerHTML = isText
    ? '<i class="fas fa-eye"></i>'
    : '<i class="fas fa-eye-slash"></i>';
}

// ── Role card selection ─────────────────────────────────────
function selectRole(role) {
  var creator = document.getElementById('roleCreator');
  var donor   = document.getElementById('roleDonor');
  creator.classList.toggle('selected', role === 'creator');
  donor.classList.toggle('selected', role === 'donor');
  creator.querySelector('input').checked = (role === 'creator');
  donor.querySelector('input').checked   = (role === 'donor');
}
// Keyboard support for role cards
document.querySelectorAll('.role-card').forEach(function(card) {
  card.addEventListener('keydown', function(e) {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      card.click();
    }
  });
});

// ── Password strength ───────────────────────────────────────
var strengthColors = ['', '#ef4444', '#f59e0b', '#3b82f6', '#10b981'];
var strengthLabels = ['', 'Weak', 'Fair', 'Good', 'Strong'];
document.getElementById('password').addEventListener('input', function() {
  var val = this.value;
  var score = 0;
  if (val.length >= 8)          score++;
  if (/[A-Z]/.test(val))        score++;
  if (/[0-9]/.test(val))        score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;
  var segs = document.querySelectorAll('.pw-strength-seg');
  segs.forEach(function(seg, i) {
    seg.style.background = i < score ? strengthColors[score] : '#e5e7eb';
  });
  var lbl = document.getElementById('pwStrengthLabel');
  lbl.textContent = val.length ? strengthLabels[score] : '';
  lbl.style.color = strengthColors[score] || '#9ca3af';
});

// ── Show error box with shake ────────────────────────────────
function showError(msg) {
  var box = document.getElementById('signupError');
  var msgEl = document.getElementById('signupErrorMsg');
  msgEl.textContent = msg;
  box.style.display = 'block';
  box.style.animation = 'none';
  box.offsetHeight; // reflow
  box.style.animation = 'shake .4s ease';
  box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
function hideError() {
  document.getElementById('signupError').style.display = 'none';
}

// ── Mobile logo visibility ───────────────────────────────────
if (window.innerWidth <= 900) {
  document.getElementById('mobileLogo').style.display = 'block';
}

// ── Form submission ─────────────────────────────────────────
document.getElementById('signupForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  hideError();

  // Client-side validation
  var pw  = document.getElementById('password').value;
  var cpw = document.getElementById('confirmPassword').value;
  var roleChecked = document.querySelector('input[name="role"]:checked');

  if (pw.length < 6) {
    showError('Password must be at least 6 characters.');
    return;
  }
  if (pw !== cpw) {
    showError('Passwords do not match.');
    return;
  }
  if (!roleChecked) {
    showError('Please select how you want to use ChamaFunds.');
    return;
  }
  if (!document.getElementById('agreeTerms').checked) {
    showError('You must agree to the Terms of Service to continue.');
    return;
  }

  // Loading state
  var btn      = document.getElementById('signupBtn');
  var spinner  = document.getElementById('btnSpinner');
  var btnIcon  = document.getElementById('btnIcon');
  var btnText  = document.getElementById('btnText');
  btn.disabled = true;
  spinner.style.display = 'block';
  btnIcon.style.display  = 'none';
  btnText.textContent    = 'Creating account…';

  var fd = new FormData(this);
  fd.append('action', 'register');

  try {
    var res  = await fetch('<?= BASE ?>/api/auth.php?action=register', { method: 'POST', body: fd });
    var data = await res.json();

    if (data.success) {
      btnText.textContent = 'Success! Redirecting…';
      spinner.style.borderTopColor = '#10b981';
      window.location.href = data.redirect;
    } else {
      showError(data.message || 'Registration failed. Please try again.');
      btn.disabled = false;
      spinner.style.display = 'none';
      btnIcon.style.display  = 'inline';
      btnText.textContent    = 'Create Account';
    }
  } catch (err) {
    showError('Network error. Please check your connection and try again.');
    btn.disabled = false;
    spinner.style.display = 'none';
    btnIcon.style.display  = 'inline';
    btnText.textContent    = 'Create Account';
  }
});
</script>
</body>
</html>
