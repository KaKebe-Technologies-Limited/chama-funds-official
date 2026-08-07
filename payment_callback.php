<?php
/**
 * Payment Callback
 * ioTec Pay mobile money collections don't use a hosted-checkout redirect —
 * the payer approves on their phone and we learn the result via the IPN
 * webhook or the AJAX status poll. This endpoint is kept for any future
 * redirect-based flow (e.g. card payments using CollectionRequest.redirectUrl)
 * and simply re-verifies + forwards to the success/processing page.
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/iotec_functions.php';

$donation_id = (int)($_GET['donation_id'] ?? 0);

// ── Look up the donation ──────────────────────────────────────
$donation = null;
if ($donation_id > 0) {
    $result   = $conn->query("SELECT * FROM donations WHERE donation_id = $donation_id LIMIT 1");
    $donation = $result ? $result->fetch_assoc() : null;
}

if (!$donation) {
    // Unknown donation — send user home with a generic message
    $_SESSION['flash_error'] = 'We could not locate your donation record. Please contact support.';
    header('Location: index.php');
    exit;
}

$campaign_id = (int)$donation['campaign_id'];

// ── Only verify if still pending ─────────────────────────────
if ($donation['status'] === 'pending') {
    $verify = verifyIotecTransaction($conn, $donation_id);
    $status = $verify['status'] ?? 'pending';

    if ($status === 'completed') {
        header('Location: donation_success.php?donation_id=' . $donation_id);
        exit;
    }

    if ($status === 'failed') {
        $_SESSION['donation_error'] = 'Your payment was not completed. Please try again.';
        header('Location: campaign-detail.php?id=' . $campaign_id);
        exit;
    }

    // Still pending — payer may still be entering their PIN.
    header('Location: donation_success.php?donation_id=' . $donation_id . '&status=pending');
    exit;
}

// Donation was already processed (completed / failed)
if ($donation['status'] === 'completed') {
    header('Location: donation_success.php?donation_id=' . $donation['donation_id']);
} else {
    $_SESSION['donation_error'] = 'Your previous payment attempt was unsuccessful. Please try again.';
    header('Location: campaign-detail.php?id=' . $campaign_id);
}
exit;
