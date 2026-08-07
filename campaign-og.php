<?php
// ============================================================
// campaign-og.php
// Generates a 1200×630 branded OG card for a campaign.
// Used as fallback when the campaign image is missing.
// Usage: /campaign-og.php?title=My+Campaign&sub=UGX+500K+raised
// ============================================================

if (!extension_loaded('gd')) {
    // GD not available — redirect to logo
    require_once __DIR__ . '/includes/config.php';
    header('Location: ' . BASE . '/img/logo.png');
    exit;
}

header('Content-Type: image/png');
header('Cache-Control: public, max-age=3600');

$title = trim($_GET['title'] ?? 'ChamaFunds Campaign');
$sub   = trim($_GET['sub']   ?? 'Support this campaign on ChamaFunds');
$title = mb_substr(strip_tags($title), 0, 55);
$sub   = mb_substr(strip_tags($sub),   0, 80);

$W = 1200; $H = 630;
$img = imagecreatetruecolor($W, $H);

// ── Colours ──────────────────────────────────────────────────
$white   = imagecolorallocate($img, 255, 255, 255);
$navy    = imagecolorallocate($img, 26,  42,  108);  // #1A2A6C
$orange  = imagecolorallocate($img, 255, 107,  74);  // #FF6B4A
$green   = imagecolorallocate($img,  16, 185, 129);  // #10b981
$softblu = imagecolorallocate($img, 235, 240, 255);  // very light blue
$muted   = imagecolorallocate($img, 100, 120, 180);
$divclr  = imagecolorallocate($img, 200, 210, 240);

// ── White background ─────────────────────────────────────────
imagefilledrectangle($img, 0, 0, $W, $H, $white);

// ── Top navy band ─────────────────────────────────────────────
imagefilledrectangle($img, 0, 0, $W, 140, $navy);

// ── Bottom navy band ──────────────────────────────────────────
imagefilledrectangle($img, 0, $H - 90, $W, $H, $navy);

// ── Left orange accent bar ────────────────────────────────────
imagefilledrectangle($img, 0, 0, 10, $H, $orange);

// ── Logo box (top-left in navy band) ─────────────────────────
$lx = 50; $ly = 30; $ls = 80;
imagefilledrectangle($img, $lx, $ly, $lx+$ls, $ly+$ls, $orange);
// Rounded feel — small circles at corners
$r = 12;
imagefilledellipse($img, $lx+$r,    $ly+$r,    $r*2, $r*2, $orange);
imagefilledellipse($img, $lx+$ls-$r,$ly+$r,    $r*2, $r*2, $orange);
imagefilledellipse($img, $lx+$r,    $ly+$ls-$r,$r*2, $r*2, $orange);
imagefilledellipse($img, $lx+$ls-$r,$ly+$ls-$r,$r*2, $r*2, $orange);

// "CF" text in logo box
imagestring($img, 5, $lx+28, $ly+30, 'CF', $white);

// ── "ChamaFunds" brand name (top band) ───────────────────────
imagestring($img, 5, $lx+$ls+18, $ly+22, 'ChamaFunds', $white);
imagestring($img, 3, $lx+$ls+18, $ly+50, 'chamafunds.com', $muted);

// ── Light blue middle panel ───────────────────────────────────
imagefilledrectangle($img, 50, 165, $W-50, $H-110, $softblu);

// ── Try TTF for big beautiful title text ─────────────────────
$fontCandidates = [
    __DIR__ . '/assets/fonts/Inter-Bold.ttf',
    'C:/Windows/Fonts/arialbd.ttf',
    'C:/Windows/Fonts/arial.ttf',
    '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
    '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
    '/usr/share/fonts/truetype/freefont/FreeSansBold.ttf',
];
$fontRegCandidates = [
    __DIR__ . '/assets/fonts/Inter-Regular.ttf',
    'C:/Windows/Fonts/arial.ttf',
    '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
    '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
    '/usr/share/fonts/truetype/freefont/FreeSans.ttf',
];

$fontBold = null; $fontReg = null;
foreach ($fontCandidates    as $f) { if (file_exists($f)) { $fontBold = $f; break; } }
foreach ($fontRegCandidates as $f) { if (file_exists($f)) { $fontReg  = $f; break; } }
if (!$fontReg) $fontReg = $fontBold;

$hasTTF = function_exists('imagettftext') && $fontBold;

if ($hasTTF) {
    // ── Campaign label ────────────────────────────────────────
    imagettftext($img, 16, 0, 80, 210, $muted, $fontReg, 'CAMPAIGN');

    // ── Campaign title — large navy text, word-wrapped ────────
    $titleSize = 54;
    // Reduce size if title is long
    if (mb_strlen($title) > 30) $titleSize = 44;
    if (mb_strlen($title) > 42) $titleSize = 36;

    // Word-wrap to ~30 chars per line
    $wrap = ($titleSize >= 50) ? 28 : ($titleSize >= 40 ? 32 : 38);
    $lines = explode("\n", wordwrap($title, $wrap, "\n", false));

    $ty = 250;
    foreach ($lines as $line) {
        imagettftext($img, $titleSize, 0, 80, $ty, $navy, $fontBold, $line);
        $ty += $titleSize + 14;
    }

    // ── Divider ───────────────────────────────────────────────
    imagefilledrectangle($img, 80, $ty + 10, 500, $ty + 13, $divclr);

    // ── Subtitle ──────────────────────────────────────────────
    imagettftext($img, 22, 0, 80, $ty + 50, $muted, $fontReg, $sub);

    // ── Bottom band text ──────────────────────────────────────
    imagettftext($img, 18, 0, 50, $H - 35, $white,  $fontReg,
        '✓ Free to donate   ✓ MTN & Airtel Money   ✓ Verified campaign');
    imagettftext($img, 18, 0, $W - 310, $H - 35, $softblu, $fontReg, 'chamafunds.com');

} else {
    // ── GD fallback ───────────────────────────────────────────
    imagestring($img, 3, 80, 195, 'CAMPAIGN', $muted);

    // Scale up title text using resampling trick
    $tmp = imagecreatetruecolor(1100, 60);
    $bg  = imagecolorallocate($tmp, 235, 240, 255);
    $fg  = imagecolorallocate($tmp, 26, 42, 108);
    imagefilledrectangle($tmp, 0, 0, 1100, 60, $bg);
    imagestring($tmp, 5, 0, 10, $title, $fg);
    imagecopyresampled($img, $tmp, 80, 220, 0, 0, 900, 50, 1100, 60);
    imagedestroy($tmp);

    imagestring($img, 3, 80, 320, $sub, $muted);
    imagefilledrectangle($img, 80, 360, 450, 362, $divclr);
    imagestring($img, 2, 50, $H - 35, '✓ Free to donate  ✓ MTN & Airtel  ✓ Verified', $white);
}

imagepng($img);
imagedestroy($img);
