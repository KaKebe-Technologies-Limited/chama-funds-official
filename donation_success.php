<?php
/**
 * Donation Success / Processing Page
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/includes/config.php';

$donation_id    = (int)($_GET['donation_id'] ?? 0);
$pendingStatus  = (isset($_GET['status']) && $_GET['status'] === 'pending');

if ($donation_id <= 0) {
    header('Location: index.php');
    exit;
}

$result   = $conn->query(
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

$isPending  = $pendingStatus || $donation['status'] === 'pending';
$currency   = htmlspecialchars($donation['currency'] ?? 'UGX');
$amount     = number_format($donation['amount']);
$donorName  = $donation['is_anonymous'] ? 'Anonymous' : htmlspecialchars($donation['donor_name']);
$campTitle  = htmlspecialchars($donation['campaign_title']);
$payDate    = $donation['payment_date']
                ? date('F j, Y', strtotime($donation['payment_date']))
                : 'Pending';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isPending ? 'Processing Payment' : 'Thank You' ?> – ChamaFunds</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .navy { color: #1A2A6C; }
        .bg-navy { background-color: #1A2A6C; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-xl max-w-lg w-full p-8 text-center">

        <?php if ($isPending): ?>
        <!-- Processing state -->
        <div class="w-24 h-24 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-clock text-5xl text-yellow-500 animate-pulse"></i>
        </div>
        <h1 class="text-2xl font-bold navy mb-2">Payment Processing…</h1>
        <p class="text-gray-500 mb-4">
            We're waiting for confirmation from your mobile money network.
            This usually takes less than a minute.
        </p>
        <p class="text-sm text-gray-400 mb-6">
            You'll receive an SMS confirmation once the payment is verified.
            You can also check back on the campaign page.
        </p>
        <a href="campaign-detail.php?id=<?= $donation['campaign_id'] ?>"
           class="block bg-navy text-white px-6 py-3 rounded-xl font-semibold hover:opacity-90 transition mb-3">
            View Campaign
        </a>
        <a href="index.php"
           class="block bg-gray-100 text-gray-700 px-6 py-3 rounded-xl font-medium hover:bg-gray-200 transition">
            Return to Home
        </a>

        <?php else: ?>
        <!-- Success state -->
        <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
            <i class="fas fa-check-circle text-5xl text-green-600"></i>
        </div>
        <h1 class="text-2xl font-bold navy mb-2">Thank You! 🎉</h1>
        <p class="text-gray-600 mb-6">Your donation has been received and confirmed.</p>

        <div class="bg-gray-50 rounded-xl p-4 mb-6 text-left">
            <p class="text-sm text-gray-400 mb-2 font-semibold uppercase tracking-wide">Donation Details</p>
            <div class="grid grid-cols-2 gap-y-2 text-sm">
                <span class="text-gray-500">Campaign</span>
                <span class="font-medium navy"><?= $campTitle ?></span>

                <span class="text-gray-500">Amount</span>
                <span class="font-bold text-green-600"><?= $currency ?> <?= $amount ?></span>

                <span class="text-gray-500">Donor</span>
                <span class="font-medium"><?= $donorName ?></span>

                <span class="text-gray-500">Reference</span>
                <code class="text-xs bg-gray-100 px-2 py-0.5 rounded"><?= htmlspecialchars($donation['transaction_reference']) ?></code>

                <span class="text-gray-500">Date</span>
                <span class="font-medium"><?= $payDate ?></span>
            </div>
        </div>

        <div class="space-y-3">
            <a href="campaign-detail.php?id=<?= $donation['campaign_id'] ?>"
               class="block bg-navy text-white px-6 py-3 rounded-xl font-semibold hover:opacity-90 transition">
                View Campaign
            </a>
            <a href="index.php"
               class="block bg-gray-100 text-gray-700 px-6 py-3 rounded-xl font-medium hover:bg-gray-200 transition">
                Return to Home
            </a>
        </div>

        <p class="text-xs text-gray-400 mt-6">
            A confirmation has been sent to your phone.
        </p>
        <?php endif; ?>

    </div>
</body>
</html>
