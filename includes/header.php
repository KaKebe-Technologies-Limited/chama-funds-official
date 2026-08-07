<?php
// ============================================================
// ChamaFunds – includes/header.php
// Shared public navbar (included in public-facing .php pages)
// ============================================================

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config.php';

// Determine active page for nav highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
$isLoggedIn  = isset($_SESSION['user_id']);
$userRole    = $_SESSION['role'] ?? 'guest';
$userName    = $_SESSION['user']['full_name'] ?? '';
$userAvatar  = $_SESSION['user']['avatar_url'] ?? '';

// ============================================================
// SESSION HELPERS (for use in this file)
// ============================================================
function getBasePath() {
    return defined('BASE') ? BASE : '';
}

// Get the correct logout URL
$logoutUrl = getBasePath() . '/logout.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= $pageTitle ?? 'ChamaFunds – Mobile Money Crowdfunding' ?></title>
  <link rel="icon" type="image/png" href="<?= BASE ?>/img/favicon.png" />
  <link rel="shortcut icon" type="image/png" href="<?= BASE ?>/img/favicon.png" />
  <link rel="apple-touch-icon" href="<?= BASE ?>/img/favicon.png" />
  <?php if (!empty($pageDescription)): ?>
  <meta name="description" content="<?= htmlspecialchars($pageDescription) ?>" />
  <?php endif; ?>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="<?= BASE ?>/css/style.css" />
  <?php if (!empty($extraCss)) echo $extraCss; ?>
</head>
<body>

<nav class="navbar">
  <div class="container">
    <a href="<?= BASE ?>/index.php" class="navbar-brand">
      <div class="navbar-logo">CF</div>
      <span class="navbar-name">ChamaFunds</span>
    </a>
    <div class="navbar-links">
      <a href="<?= BASE ?>/campaign-drives.php" <?= $currentPage === 'campaign-drives.php' ? 'style="color:#FF6B4A;"' : '' ?>>Campaign Drives</a>
      <a href="<?= BASE ?>/donate.php" <?= $currentPage === 'donate.php' ? 'style="color:#FF6B4A;"' : '' ?>>Donate</a>
      <a href="<?= BASE ?>/index.php#how-it-works">How It Works</a>
      <?php if ($isLoggedIn): ?>
        <div class="user-menu">
          <div class="user-avatar" id="userMenuTrigger" title="<?= htmlspecialchars($userName) ?>">
            <?php if ($userAvatar): ?>
              <img src="<?= htmlspecialchars($userAvatar) ?>" style="width:36px;height:36px;border-radius:50%;object-fit:cover;" alt="Avatar" />
            <?php else: ?>
              <?= strtoupper(substr($userName, 0, 2)) ?>
            <?php endif; ?>
          </div>
          <div class="user-dropdown" id="userDropdown">
            <a href="<?= BASE ?>/dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
            <?php if ($userRole === 'admin'): ?>
            <a href="<?= BASE ?>/admin/index.php"><i class="fas fa-shield-alt"></i> Admin Panel</a>
            <?php endif; ?>
            <a href="<?= BASE ?>/profile.php"><i class="fas fa-user-cog"></i> Profile</a>
           <!-- Change this line -->
            <a href="<?= BASE ?>/logout.php" class="logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
          </div>
        </div>
      <?php else: ?>
        <a href="<?= BASE ?>/login.php">Log In</a>
        <a href="<?= BASE ?>/create-campaign.php" class="btn btn-primary nav-cta">Start a Campaign</a>
      <?php endif; ?>
    </div>
    <button class="hamburger" id="hamburger" aria-label="Open menu">
      <span></span><span></span><span></span>
    </button>
  </div>
</nav>

<!-- Mobile overlay + drawer -->
<div class="mobile-menu-overlay" id="menuOverlay"></div>
<div class="mobile-menu" id="mobileMenu">
  <button class="mobile-menu-close" id="menuClose"><i class="fas fa-times"></i></button>
  <a href="<?= BASE ?>/index.php" class="mobile-menu-brand">
    <div class="navbar-logo" style="width:34px;height:34px;font-size:.75rem;">CF</div>
    <span style="font-weight:800;color:var(--navy);font-size:1rem;">ChamaFunds</span>
  </a>
  <a href="<?= BASE ?>/campaign-drives.php">Campaign Drives</a>
  <a href="<?= BASE ?>/donate.php">Donate</a>
  <a href="<?= BASE ?>/index.php#how-it-works">How It Works</a>
  <?php if ($isLoggedIn): ?>
    <a href="<?= BASE ?>/dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
    <?php if ($userRole === 'admin'): ?>
    <a href="<?= BASE ?>/admin/index.php"><i class="fas fa-shield-alt"></i> Admin Panel</a>
    <?php endif; ?>
    <a href="<?= $logoutUrl ?>" style="color:#ef4444;"><i class="fas fa-sign-out-alt"></i> Logout</a>
  <?php else: ?>
    <a href="<?= BASE ?>/login.php">Log In</a>
    <a href="<?= BASE ?>/create-campaign.php" class="btn btn-primary mobile-menu-cta">Start a Campaign</a>
  <?php endif; ?>
</div>