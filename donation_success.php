<?php
/**
 * Donation Processing / Failed Page
 * On confirmed success we redirect straight to the campaign page — the
 * donor gets their "thank you" via email (see markDonationCompleted() in
 * includes/iotec_functions.php) instead of an on-site confirmation screen.
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/config.php';

$donation_id = (int)($_GET['donation_id'] ?? 0);

if ($donation_id <= 0) {
    header('Location: index.php');
    exit;
}

$result = $conn->query(
    "SELECT d.*, c.title AS campaign_title, c.currency
     FROM donations d
     JOIN campaigns c ON d.campaign_id = c.campaign_id
     WHERE d.donation_id = $donation_id
     LIMIT 1"
);
$donation = $result ? $result->fetch_assoc() : null;

if (!$donation) {
    header('Location: index.php');
    exit;
}

$campaignId = (int)$donation['campaign_id'];

if ($donation['status'] === 'completed') {
    header('Location: ' . BASE . '/campaign-detail.php?id=' . $campaignId . '&donated=1');
    exit;
}

$status   = $donation['status'];
$currency = htmlspecialchars($donation['currency'] ?? 'UGX');
$amount   = number_format($donation['amount']);
$campTitle = htmlspecialchars($donation['campaign_title']);

$pageTitle = $status === 'pending' ? 'Processing Payment' : 'Payment Failed';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= $pageTitle ?> – ChamaFunds</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
  <link rel="stylesheet" href="<?= BASE ?>/css/style.css?v=<?= @filemtime(__DIR__ . '/css/style.css') ?: time() ?>" />
</head>
<body>
<div class="auth-wrapper">
  <div class="auth-card ds-card">

    <a href="<?= BASE ?>/index.php" class="ds-brand">
      <div class="navbar-logo">CF</div>
      <span>ChamaFunds</span>
    </a>

    <?php if ($status === 'pending'): ?>
    <!-- ── Processing ─────────────────────────────────────────── -->
    <div class="ds-icon ds-icon-pending"><i class="fas fa-clock"></i></div>
    <h1 class="ds-title">Payment Processing…</h1>
    <p class="ds-sub">We're waiting for confirmation from your mobile money network. This usually takes less than a minute.</p>

    <div class="ds-stay-warning">
      <i class="fas fa-mobile-alt"></i>
      <div>
        <strong>Enter your PIN, then stay on this page.</strong>
        <span>Closing the app or browser now may prevent your donation from being confirmed.</span>
      </div>
    </div>

    <p class="ds-note">You'll receive an email confirmation once the payment is verified. You can also check back on the campaign page.</p>

    <div class="ds-actions">
      <a href="<?= BASE ?>/campaign-detail.php?id=<?= $campaignId ?>" class="btn btn-primary btn-block">View Campaign</a>
      <a href="<?= BASE ?>/index.php" class="btn btn-outline btn-block">Return to Home</a>
    </div>

    <script>
    // Poll for the mobile money confirmation (webhook may take a few seconds).
    // On success, go straight to the campaign page — no on-site "thank you"
    // screen; the donor's confirmation email is what tells them it's done.
    (function poll() {
      fetch('<?= BASE ?>/api/donations.php?action=check_status&donation_id=<?= $donation_id ?>')
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (data.success && data.status === 'completed') {
            window.location.href = '<?= BASE ?>/campaign-detail.php?id=<?= $campaignId ?>';
          } else if (data.success && data.status === 'failed') {
            window.location.reload();
          } else {
            setTimeout(poll, 4000);
          }
        })
        .catch(function() { setTimeout(poll, 6000); });
    })();
    </script>

    <?php else: ?>
    <!-- ── Failed ─────────────────────────────────────────────── -->
    <div class="ds-icon ds-icon-failed"><i class="fas fa-times"></i></div>
    <h1 class="ds-title">Payment Not Completed</h1>
    <p class="ds-sub">Your donation of <?= $currency ?> <?= $amount ?> to <strong><?= $campTitle ?></strong> could not be processed.</p>
    <p class="ds-note">No funds were taken. You can try again anytime.</p>

    <div class="ds-actions">
      <a href="<?= BASE ?>/campaign-detail.php?id=<?= $campaignId ?>" class="btn btn-primary btn-block">Try Again</a>
      <a href="<?= BASE ?>/index.php" class="btn btn-outline btn-block">Return to Home</a>
    </div>
    <?php endif; ?>

  </div>
</div>
</body>
</html>
