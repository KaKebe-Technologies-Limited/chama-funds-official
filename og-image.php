<?php
// ============================================================
// ChamaFunds – og-image.php
// Clean branded OG social card  1200 × 630
// ============================================================

if (!extension_loaded('gd')) {
    header('Location: https://images.unsplash.com/photo-1529156069898-49953e39b3ac?w=1200&h=630&fit=crop&q=85');
    exit;
}

header('Content-Type: image/png');
header('Cache-Control: public, max-age=86400');

$W = 1200;
$H = 630;
$img = imagecreatetruecolor($W, $H);

// ── Colours ──────────────────────────────────────────────────
$bgTop    = imagecolorallocate($img, 15,  28,  80);   // deep navy top
$bgBot    = imagecolorallocate($img, 26,  42, 108);   // #1A2A6C bottom
$orange   = imagecolorallocate($img, 255,107, 74);    // #FF6B4A
$white    = imagecolorallocate($img, 255,255,255);
$soft     = imagecolorallocate($img, 180,195,230);    // muted blue-white
$green    = imagecolorallocate($img,  16,185,129);    // #10b981
$divider  = imagecolorallocate($img,  45, 65,140);    // subtle line

// ── Background — simple two-tone fill ────────────────────────
for ($y = 0; $y < $H; $y++) {
    $ratio = $y / $H;
    $r = (int)(15  + (26  - 15)  * $ratio);
    $g = (int)(28  + (42  - 28)  * $ratio);
    $b = (int)(80  + (108 - 80)  * $ratio);
    $c = imagecolorallocate($img, $r, $g, $b);
    imageline($img, 0, $y, $W, $y, $c);
}

// ── Subtle circle decorations ─────────────────────────────────
$c1 = imagecolorallocate($img, 30, 50, 120);
imagefilledellipse($img, -80,  -80, 380, 380, $c1);
imagefilledellipse($img, $W+80, $H+80, 420, 420, $c1);

// ── Left orange accent bar ────────────────────────────────────
imagefilledrectangle($img, 0, 0, 10, $H, $orange);

// ── Orange dot accent top-right ──────────────────────────────
imagefilledellipse($img, $W - 120, 80, 160, 160,
    imagecolorallocate($img, 60, 40, 30)); // dark shadow
imagefilledellipse($img, $W - 120, 80, 130, 130, $orange);

// ── Find a usable TTF font automatically ─────────────────────
// Check project fonts folder first, then common system locations
$fontCandidates = [
    __DIR__ . '/assets/fonts/Inter-Bold.ttf',
    'C:/Windows/Fonts/arialbd.ttf',      // Windows / XAMPP bold
    'C:/Windows/Fonts/arial.ttf',         // Windows regular
    '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',  // Linux
    '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
];
$fontRegCandidates = [
    __DIR__ . '/assets/fonts/Inter-Regular.ttf',
    'C:/Windows/Fonts/arial.ttf',
    '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
    '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
];

$fontBold = null;
$fontReg  = null;
foreach ($fontCandidates    as $f) { if (file_exists($f)) { $fontBold = $f; break; } }
foreach ($fontRegCandidates as $f) { if (file_exists($f)) { $fontReg  = $f; break; } }
if (!$fontReg && $fontBold) $fontReg = $fontBold; // use bold for both if needed

$hasTTF = function_exists('imagettftext') && $fontBold !== null;

if ($hasTTF) {
    // ── Big brand name ────────────────────────────────────────
    imagettftext($img, 82, 0, 80, 290, $white, $fontBold, 'ChamaFunds');

    // ── Slogan ────────────────────────────────────────────────
    imagettftext($img, 28, 0, 80, 360, $soft, $fontReg,
        'Pool money together for what matters most.');

    // ── Divider ───────────────────────────────────────────────
    imagefilledrectangle($img, 80, 400, 560, 403, $divider);

    // ── Pill row ──────────────────────────────────────────────
    imagettftext($img, 20, 0, 80, 450, $green, $fontReg,
        '✓  Free to start    ✓  MTN & Airtel Money    ✓  Same-day payout');

    // ── Domain ───────────────────────────────────────────────
    imagettftext($img, 18, 0, 80, 510, $soft, $fontReg,
        $_SERVER['HTTP_HOST']);

} else {
    // ── GD built-in fallback (no TTF available) ───────────────

    // "ChamaFunds" — scale it up using imagestring repeated
    // We'll simulate large text by drawing chars with font 5 (biggest built-in)
    // Scale trick: draw to a small canvas then resample
    $tmp  = imagecreatetruecolor(600, 80);
    $tBg  = imagecolorallocate($tmp, 0, 0, 0);
    $tWh  = imagecolorallocate($tmp, 255, 255, 255);
    imagefilledrectangle($tmp, 0, 0, 600, 80, $tBg);
    imagestring($tmp, 5, 0, 15, 'ChamaFunds', $tWh);
    imagecopyresampled($img, $tmp, 80, 200, 0, 0, 900, 120, 600, 80);
    imagedestroy($tmp);

    // Slogan
    $tmp2 = imagecreatetruecolor(700, 30);
    $tBg2 = imagecolorallocate($tmp2, 0, 0, 0);
    $tSoft= imagecolorallocate($tmp2, 180, 195, 230);
    imagefilledrectangle($tmp2, 0, 0, 700, 30, $tBg2);
    imagestring($tmp2, 4, 0, 5, 'Pool money together for what matters most.', $tSoft);
    imagecopyresampled($img, $tmp2, 80, 350, 0, 0, 700, 30, 700, 30);
    imagedestroy($tmp2);

    // Divider
    imagefilledrectangle($img, 80, 415, 500, 418, $divider);

    // Pills
    imagestring($img, 3, 80, 445, '✓ Free to start   ✓ MTN & Airtel   ✓ Same-day payout', $green);

    // Domain
    imagestring($img, 2, 80, 490, $_SERVER['HTTP_HOST'], $soft);
}

imagepng($img);
imagedestroy($img);
