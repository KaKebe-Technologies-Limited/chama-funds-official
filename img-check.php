<?php
require_once __DIR__ . '/includes/config.php';
header('Content-Type: text/plain');

echo "=== ENVIRONMENT ===\n";
echo "BASE = " . BASE . "\n";
echo "HTTP_HOST = " . $_SERVER['HTTP_HOST'] . "\n";
echo "HTTPS = " . ($_SERVER['HTTPS'] ?? 'NOT SET') . "\n\n";

echo "=== FILES ON DISK (uploads/campaigns/) ===\n";
$dir = $_SERVER['DOCUMENT_ROOT'] . '/uploads/campaigns/';
if (is_dir($dir)) {
    $files = array_diff(scandir($dir), ['.','..']);
    foreach ($files as $f) echo "  $f\n";
    echo "Total: " . count($files) . " files\n";
} else {
    echo "DIRECTORY NOT FOUND: $dir\n";
}

echo "\n=== DB IMAGE URLs ===\n";
$result = $conn->query("SELECT campaign_id, title, status, image_url FROM campaigns WHERE image_url IS NOT NULL AND image_url != '' ORDER BY campaign_id");
while ($r = $result->fetch_assoc()) {
    $url = $r['image_url'];
    $resolved = imgUrl($url);
    // Check if file exists on disk
    $path = parse_url($resolved, PHP_URL_PATH);
    $disk = $_SERVER['DOCUMENT_ROOT'] . $path;
    $exists = file_exists($disk) ? '✅' : '❌ MISSING';
    echo "camp#{$r['campaign_id']} [{$r['status']}]: stored=[$url]\n";
    echo "         resolved=[$resolved]\n";
    echo "         disk=$exists\n\n";
}
?>
