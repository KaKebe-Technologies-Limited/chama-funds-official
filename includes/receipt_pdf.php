<?php
// ============================================================
// ChamaFunds – includes/receipt_pdf.php
// Generates the "money receipt" PDF for a completed withdrawal —
// a formal receipt (logo, company details, received-from, signature
// lines) branded in ChamaFunds' own navy/coral and "CF" mark, with
// real data filled in, plus a fee-transparency breakdown so the
// campaigner can see exactly what was raised, sent, and kept as the
// platform's fee and ioTec's own disbursement fee.
// ============================================================
require_once __DIR__ . '/simple_pdf.php';

function numberToWords(int $num): string {
    if ($num === 0) return 'Zero';
    $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
              'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen',
              'Seventeen', 'Eighteen', 'Nineteen'];
    $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

    $threeDigits = function (int $n) use ($ones, $tens): string {
        $out = '';
        if ($n >= 100) {
            $out .= $ones[intdiv($n, 100)] . ' Hundred';
            $n %= 100;
            if ($n > 0) $out .= ' ';
        }
        if ($n >= 20) {
            $out .= $tens[intdiv($n, 10)];
            $n %= 10;
            if ($n > 0) $out .= '-' . $ones[$n];
        } elseif ($n > 0) {
            $out .= $ones[$n];
        }
        return $out;
    };

    $parts = [];
    $n = $num;
    $billions = intdiv($n, 1000000000); $n %= 1000000000;
    $millions = intdiv($n, 1000000);    $n %= 1000000;
    $thousands = intdiv($n, 1000);      $n %= 1000;
    $rest = $n;

    if ($billions)  $parts[] = $threeDigits($billions) . ' Billion';
    if ($millions)  $parts[] = $threeDigits($millions) . ' Million';
    if ($thousands) $parts[] = $threeDigits($thousands) . ' Thousand';
    if ($rest)      $parts[] = $threeDigits($rest);

    return implode(' ', $parts);
}

// Wraps text to fit a max pixel width using the PDF's average-width
// estimate, splitting on word boundaries. Returns an array of lines.
function wrapPdfText(SimplePdf $pdf, string $text, float $maxWidth, float $size, bool $bold = false): array {
    $words = explode(' ', $text);
    $lines = [];
    $current = '';
    foreach ($words as $word) {
        $trial = $current === '' ? $word : $current . ' ' . $word;
        if ($pdf->estimateTextWidth($trial, $size, $bold) > $maxWidth && $current !== '') {
            $lines[] = $current;
            $current = $word;
        } else {
            $current = $trial;
        }
    }
    if ($current !== '') $lines[] = $current;
    return $lines;
}

// Builds the receipt PDF for withdrawal $withdrawalId, saves it to
// persistent storage, and returns the bare filename (to store in
// withdrawals.receipt_path) — or null if the withdrawal isn't found.
function generateWithdrawalReceipt(mysqli $conn, int $withdrawalId): ?string {
    $res = $conn->query(
        "SELECT w.*, c.title AS campaign_title, c.raised_amount AS campaign_total_raised, c.currency,
                u.full_name AS campaigner_name,
                a.full_name AS approved_by_name
         FROM withdrawals w
         JOIN campaigns c ON w.campaign_id = c.campaign_id
         JOIN users u ON w.campaigner_id = u.user_id
         LEFT JOIN users a ON w.approved_by = a.user_id
         WHERE w.withdrawal_id = $withdrawalId LIMIT 1"
    );
    $w = $res ? $res->fetch_assoc() : null;
    if (!$w) return null;

    $platformName = 'ChamaFunds';
    $platformPhone = '';
    $sRes = $conn->query("SELECT setting_key, setting_value FROM platform_settings WHERE setting_key IN ('platform_name','platform_phone')");
    if ($sRes) {
        while ($s = $sRes->fetch_assoc()) {
            if ($s['setting_key'] === 'platform_name' && $s['setting_value'] !== '') $platformName = $s['setting_value'];
            if ($s['setting_key'] === 'platform_phone') $platformPhone = $s['setting_value'];
        }
    }

    $navyDark = [17, 29, 78];
    $navy     = [26, 42, 108];
    $coral    = [255, 107, 74];
    $dark     = [33, 33, 38];
    $gray     = [110, 116, 128];
    $red      = [185, 60, 45];
    $green    = [16, 122, 78];

    // Generously tall — footer position is computed from actual content
    // below, but the canvas itself still needs to be tall enough to hold it.
    $pageHeight = 610;
    $pdf = new SimplePdf(595, $pageHeight);

    // ── Background + a thin frame for a clean, contained card look ──
    $pdf->setFillColor(255, 255, 255);
    $pdf->rect(0, 0, 595, $pageHeight, 'f');
    $pdf->setStrokeColor(...$navy);
    $pdf->rect(3, 3, 589, $pageHeight - 6, 's');

    // ── Header band ──
    $pdf->setFillColor(...$navyDark);
    $pdf->rect(3, 3, 589, 75, 'f');
    $pdf->setFillColor(...$coral);
    $pdf->rect(3, 78, 589, 5, 'f');

    // Logo mark — the same "CF" mark used across the site: navy square, white "CF"
    $pdf->setFillColor(255, 255, 255);
    $pdf->rect(24, 20, 38, 38, 'f');
    $pdf->setFillColor(...$navyDark);
    $pdf->textCentered(43, 45, 'CF', 15, true);

    $pdf->setFillColor(255, 255, 255);
    $pdf->text(72, 34, $platformName, 16, true);
    $pdf->setFillColor(210, 218, 240);
    $pdf->text(72, 50, 'chamafunds.com', 8);

    $pdf->setFillColor(255, 255, 255);
    $pdf->textCentered(297, 30, 'MONEY RECEIPT', 17, true);
    $pdf->setFillColor(210, 218, 240);
    $pdf->textCentered(297, 50, 'Withdrawal Payout Confirmation', 8);

    $pdf->setFillColor(255, 255, 255);
    $pdf->textRight(566, 26, $platformName, 11, true);
    $pdf->setFillColor(210, 218, 240);
    if ($platformPhone !== '') $pdf->textRight(566, 40, $platformPhone, 8);
    $pdf->textRight(566, 52, 'Kampala, Uganda', 8);

    // ── Receipt No. / Date ──
    $receiptNo = 'WD-' . str_pad((string)$w['withdrawal_id'], 6, '0', STR_PAD_LEFT);
    $dateStr   = date('M j, Y g:i A', strtotime($w['completed_at'] ?? $w['approved_at'] ?? 'now'));
    $pdf->setFillColor(...$dark);
    $pdf->text(24, 104, 'Receipt No: ' . $receiptNo, 10, true);
    $pdf->textRight(570, 104, 'Date: ' . $dateStr, 10, true);
    $pdf->setStrokeColor(220, 222, 228);
    $pdf->line(24, 114, 570, 114, 0.6);

    // ── Body fields ──
    $labelColor = $gray;
    $valueColor = $dark;
    $y = 140;
    $lineGap = 26;

    $field = function (float $y, string $label, string $value, bool $valueBold = true) use ($pdf, $labelColor, $valueColor) {
        $pdf->setFillColor(...$labelColor);
        $pdf->text(24, $y, $label, 9.5);
        $pdf->setFillColor(...$valueColor);
        $pdf->text(190, $y, $value, 10.5, $valueBold);
    };

    $field($y, 'Received with thanks from', htmlspecialchars_decode($w['campaigner_name']));
    $y += $lineGap;

    $field($y, 'Amount Paid (Net)', $w['currency'] . ' ' . number_format((float)$w['net_amount']));
    $y += $lineGap;

    $currencyWords = $w['currency'] === 'UGX' ? 'Uganda Shillings' : $w['currency'];
    $words = numberToWords((int)round((float)$w['net_amount'])) . ' ' . $currencyWords . ' Only';
    $pdf->setFillColor(...$labelColor);
    $pdf->text(24, $y, 'In Words', 9.5);
    $pdf->setFillColor(...$valueColor);
    $wrapped = wrapPdfText($pdf, $words, 350, 10);
    foreach ($wrapped as $i => $line) {
        $pdf->text(190, $y + ($i * 13), $line, 10);
    }
    $y += $lineGap + (max(0, count($wrapped) - 1) * 13);

    $field($y, 'For (Campaign)', mb_strimwidth(htmlspecialchars_decode($w['campaign_title']), 0, 42, '…'));
    $y += $lineGap;

    $field($y, 'Paid To', ($w['mobile_money_network'] ?? '') . ' - ' . ($w['mobile_money_number'] ?? ''));
    $y += $lineGap;

    $field($y, 'Status', 'PAID IN FULL', true);
    $y += 16;

    $pdf->setStrokeColor(220, 222, 228);
    $pdf->line(24, $y, 570, $y, 0.6);
    $y += 22;

    // ── Transparency breakdown ──
    $pdf->setFillColor(247, 248, 250);
    $pdf->rect(24, $y - 16, 546, 118, 'f');
    $pdf->setFillColor(...$dark);
    $pdf->text(38, $y, 'Transaction Breakdown - for full transparency', 10, true);
    $y += 22;

    $breakdownRow = function (float $y, string $label, string $value, array $color, bool $bold = false) use ($pdf) {
        $pdf->setFillColor(90, 96, 108);
        $pdf->text(38, $y, $label, 9.5);
        $pdf->setFillColor(...$color);
        $pdf->textRight(556, $y, $value, 10.5, $bold);
    };
    $curr = $w['currency'];
    $breakdownRow($y, 'Total Raised by Campaign (to date)', $curr . ' ' . number_format((float)$w['campaign_total_raised']), $dark);
    $y += 18;
    $breakdownRow($y, 'This Withdrawal - Gross Amount', $curr . ' ' . number_format((float)$w['gross_amount']), $dark);
    $y += 18;
    $breakdownRow($y, 'Platform Fee (' . number_format((float)$w['fee_percentage'], 2) . '%) - retained by ' . $platformName, '- ' . $curr . ' ' . number_format((float)$w['fee_amount']), $red);
    $y += 18;
    $breakdownRow($y, 'ioTec Transfer Fee (wallet-to-mobile-money)', '- ' . $curr . ' ' . number_format((float)($w['iotec_fee'] ?? 0)), $red);
    $y += 18;
    $breakdownRow($y, 'Net Amount Sent', $curr . ' ' . number_format((float)$w['net_amount']), $green, true);
    $y += 56;

    // ── Amount box ──
    $pdf->setFillColor(...$dark);
    $pdf->text(24, $y, 'Amount=', 11, true);
    $pdf->setStrokeColor(...$navyDark);
    $pdf->rect(90, $y - 14, 180, 20, 's');
    $pdf->setFillColor(...$navy);
    $pdf->text(96, $y, $curr . ' ' . number_format((float)$w['net_amount']), 11, true);

    // ── Signature lines ──
    $sigY = $y + 46;
    $pdf->setStrokeColor(150, 154, 164);
    $pdf->line(300, $sigY, 420, $sigY, 0.7);
    $pdf->line(450, $sigY, 570, $sigY, 0.7);
    $pdf->setFillColor(...$dark);
    $pdf->textCentered(360, $sigY - 4, htmlspecialchars_decode($w['campaigner_name']), 9, true);
    $pdf->textCentered(510, $sigY - 4, $w['approved_by_name'] ? htmlspecialchars_decode($w['approved_by_name']) : $platformName . ' Admin', 9, true);
    $pdf->setFillColor(...$gray);
    $pdf->textCentered(360, $sigY + 12, 'Received by', 8);
    $pdf->textCentered(510, $sigY + 12, 'Authorized Signature', 8);

    // ── Footer — positioned from where the content above actually
    //    ends, not a fixed offset, so it can never overlap/clip content ──
    $footerY = $sigY + 38;
    $pdf->setFillColor(...$coral);
    $pdf->rect(3, $footerY, 589, 30, 'f');
    $pdf->setFillColor(255, 255, 255);
    $pdf->textCentered(297, $footerY + 19, 'This is a system-generated receipt from ' . $platformName . ' - no physical signature required.', 8, true);

    ensurePersistentReceiptsDir();
    $filename = 'withdrawal_' . $w['withdrawal_id'] . '_receipt.pdf';
    $pdf->output(PERSISTENT_RECEIPTS_DIR . $filename);

    return $filename;
}
