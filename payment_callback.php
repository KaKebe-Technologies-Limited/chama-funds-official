<?php
/**
 * Payment Callback
 * Pesapal redirects the user back here after they complete (or cancel) payment.
 * We verify the actual transaction status via the Pesapal API before updating the DB.
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/pesapal_functions.php';

$donation_id       = (int)($_GET['donation_id'] ?? 0);
$order_tracking_id = trim($_GET['OrderTrackingId'] ?? $_GET['order_tracking_id'] ?? '');
$merchant_reference= trim($_GET['OrderMerchantReference'] ?? '');

// ── Look up the donation ──────────────────────────────────────
$donation = null;
if ($donation_id > 0) {
    $result   = $conn->query("SELECT * FROM donations WHERE donation_id = $donation_id LIMIT 1");
    $donation = $result ? $result->fetch_assoc() : null;
}

// Fallback: look up by tracking id if we have it in the URL
if (!$donation && !empty($order_tracking_id)) {
    $ote    = $conn->real_escape_string($order_tracking_id);
    $result = $conn->query(
        "SELECT * FROM donations WHERE pesapal_tracking_id = '$ote' LIMIT 1"
    );
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

    // Use whichever tracking id we have
    $trackingId = $order_tracking_id ?: $donation['pesapal_tracking_id'] ?? '';

    if (!empty($trackingId)) {
        $statusResponse = verifyPesapalTransaction($trackingId);
        $paymentStatus  = strtoupper(
            $statusResponse->payment_status_description
            ?? $statusResponse->status
            ?? ''
        );
    } else {
        $paymentStatus = '';
    }

    if ($paymentStatus === 'COMPLETED') {
        $did = $donation['donation_id'];
        $conn->query(
            "UPDATE donations
             SET status = 'completed', payment_date = NOW()
             WHERE donation_id = $did AND status = 'pending'"
        );
        // Bump campaign totals (only if we actually changed a row)
        if ($conn->affected_rows > 0) {
            $amt = floatval($donation['amount']);
            $conn->query(
                "UPDATE campaigns
                 SET raised_amount     = raised_amount + $amt,
                     contributor_count = contributor_count + 1,
                     updated_at        = NOW()
                 WHERE campaign_id = $campaign_id"
            );

            // Notify the campaign owner
            $campRow = $conn->query(
                "SELECT campaigner_id, title FROM campaigns WHERE campaign_id = $campaign_id LIMIT 1"
            )->fetch_assoc();
            if ($campRow) {
                $ownerId     = (int)$campRow['campaigner_id'];
                $campTitle   = $conn->real_escape_string($campRow['title']);
                $donorLabel  = $donation['is_anonymous'] ? 'Anonymous Donor'
                                : $conn->real_escape_string($donation['donor_name']);
                $notifMsg    = "$donorLabel just donated " . number_format($amt)
                             . " to your campaign \"$campTitle\"";
                $notifMsgEsc = $conn->real_escape_string($notifMsg);
                $notifLink   = $conn->real_escape_string(BASE . '/campaign-detail.php?id=' . $campaign_id);
                $conn->query(
                    "INSERT INTO notifications (user_id, type, title, message, link)
                     VALUES ($ownerId, 'donation', 'New Donation Received!',
                             '$notifMsgEsc',
                             '$notifLink')"
                );
            }
        }

        header('Location: donation_success.php?donation_id=' . $donation['donation_id']);
        exit;

    } elseif (in_array($paymentStatus, ['FAILED', 'INVALID', 'REVERSED'])) {
        $did = $donation['donation_id'];
        $conn->query("UPDATE donations SET status = 'failed' WHERE donation_id = $did");
        $_SESSION['donation_error'] = 'Your payment was not completed. Please try again.';
        header('Location: campaign-detail.php?id=' . $campaign_id);
        exit;
    }

    // PENDING or unknown status — Pesapal may still be processing;
    // show a "processing" page rather than marking it failed.
    header('Location: donation_success.php?donation_id=' . $donation['donation_id'] . '&status=pending');
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
