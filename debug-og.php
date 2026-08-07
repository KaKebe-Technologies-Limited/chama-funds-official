<?php
require_once __DIR__ . '/includes/config.php';
$cid = (int)($_GET['id'] ?? 10);
header('Content-Type: text/plain');

echo "BASE = " . BASE . "\n";
echo "HTTP_HOST = " . $_SERVER['HTTP_HOST'] . "\n";
echo "HTTPS = " . ($_SERVER['HTTPS'] ?? 'NOT SET') . "\n";
echo "HTTP_X_FORWARDED_PROTO = " . ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? 'NOT SET') . "\n\n";

$c = $conn->query("SELECT campaign_id, title, image_url FROM campaigns WHERE campaign_id=$cid LIMIT 1")->fetch_assoc();
echo "campaign_id = " . $c['campaign_id'] . "\n";
echo "title       = " . $c['title'] . "\n";
echo "image_url   = [" . $c['image_url'] . "]\n\n";

$imgs = $conn->query("SELECT image_id, image_url, is_cover FROM campaign_images WHERE campaign_id=$cid ORDER BY is_cover DESC LIMIT 5");
echo "campaign_images rows: " . $imgs->num_rows . "\n";
while ($r = $imgs->fetch_assoc()) {
    echo "  id={$r['image_id']} cover={$r['is_cover']} url=[{$r['image_url']}]\n";
}
echo "\n";

echo "imgUrl(image_url) = [" . imgUrl($c['image_url']) . "]\n\n";

// Check file on disk
$rel = '/' . ltrim($c['image_url'], '/');
$disk = $_SERVER['DOCUMENT_ROOT'] . $rel;
echo "Disk path = " . $disk . "\n";
echo "Exists?   = " . (file_exists($disk) ? "YES ✅" : "NO ❌") . "\n\n";

// Check uploads folder
$dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/campaigns/';
echo "Files in /uploads/campaigns/:\n";
if (is_dir($dir)) {
    $files = array_diff(scandir($dir), ['.','..']);
    foreach (array_slice($files, 0, 10) as $f) echo "  $f\n";
} else {
    echo "  Directory NOT found at: $dir\n";
}
?>
