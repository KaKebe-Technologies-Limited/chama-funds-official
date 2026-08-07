<?php
// ============================================================
// ChamaFunds – image-upload-handler.php
// Handles multi-image uploads for campaigns (AJAX)
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit;
}

$conn      = require_once __DIR__ . '/db/connection.php';
$action    = $_GET['action'] ?? $_POST['action'] ?? '';
$uid       = (int)$_SESSION['user_id'];
$role      = $_SESSION['role'] ?? 'donor';

$uploadDir = __DIR__ . '/uploads/campaigns/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$allowedExt  = ['jpg','jpeg','png','webp'];
$maxSize     = 5 * 1024 * 1024; // 5 MB

// ── UPLOAD one or more images ─────────────────────────────────
if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $campaignId = (int)($_POST['campaign_id'] ?? 0);
    $isCover    = !empty($_POST['is_cover']) ? 1 : 0;

    // Ownership check
    if ($campaignId > 0) {
        $check = $conn->query("SELECT campaigner_id FROM campaigns WHERE campaign_id=$campaignId LIMIT 1");
        if (!$check || $check->num_rows === 0) {
            echo json_encode(['success'=>false,'message'=>'Campaign not found.']); exit;
        }
        $owner = $check->fetch_assoc()['campaigner_id'];
        if ($role !== 'admin' && $owner != $uid) {
            echo json_encode(['success'=>false,'message'=>'Access denied.']); exit;
        }
    }

    $files   = $_FILES['images'] ?? $_FILES['image'] ?? null;
    if (!$files || empty($files['tmp_name'])) {
        echo json_encode(['success'=>false,'message'=>'No file received.']); exit;
    }

    // Normalise to array (supports single or multiple file inputs)
    $tmpNames  = is_array($files['tmp_name']) ? $files['tmp_name'] : [$files['tmp_name']];
    $origNames = is_array($files['name'])     ? $files['name']     : [$files['name']];
    $sizes     = is_array($files['size'])     ? $files['size']     : [$files['size']];

    $uploaded = [];
    $errors   = [];

    // Get current max sort_order
    $maxOrd = 0;
    if ($campaignId > 0) {
        $r = $conn->query("SELECT COALESCE(MAX(sort_order),0) FROM campaign_images WHERE campaign_id=$campaignId");
        $maxOrd = (int)$r->fetch_row()[0];
    }

    foreach ($tmpNames as $i => $tmpName) {
        if (empty($tmpName) || !is_uploaded_file($tmpName)) continue;
        $ext = strtolower(pathinfo($origNames[$i], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt)) { $errors[] = $origNames[$i].': invalid type'; continue; }
        if ($sizes[$i] > $maxSize)        { $errors[] = $origNames[$i].': exceeds 5 MB'; continue; }

        $filename = 'camp_' . $uid . '_' . time() . '_' . $i . '.' . $ext;
        if (!move_uploaded_file($tmpName, $uploadDir . $filename)) {
            $errors[] = $origNames[$i].': upload failed'; continue;
        }
        $url = '/uploads/campaigns/' . $filename;
        $urlEsc   = $conn->real_escape_string($url);
        $sortOrd  = $maxOrd + $i + 1;
        $cover    = ($isCover && $i === 0) ? 1 : 0;

        if ($campaignId > 0) {
            // If marking cover, clear previous cover first
            if ($cover) $conn->query("UPDATE campaign_images SET is_cover=0 WHERE campaign_id=$campaignId");
            $conn->query(
                "INSERT INTO campaign_images (campaign_id, image_url, is_cover, sort_order)
                 VALUES ($campaignId, '$urlEsc', $cover, $sortOrd)"
            );
            $imgId = $conn->insert_id;
            // Also update the main image_url if this is cover
            if ($cover) $conn->query("UPDATE campaigns SET image_url='$urlEsc' WHERE campaign_id=$campaignId");
        } else {
            $imgId = 0; // temp, will be associated after campaign creation
        }
        $uploaded[] = ['image_id'=>$imgId,'url'=>$url,'is_cover'=>(bool)$cover];
    }

    echo json_encode([
        'success'  => count($uploaded) > 0,
        'uploaded' => $uploaded,
        'errors'   => $errors,
        'message'  => count($uploaded).' image(s) uploaded successfully.',
    ]);
    exit;
}

// ── DELETE an image ───────────────────────────────────────────
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $imageId = (int)($_POST['image_id'] ?? 0);
    $row = $conn->query("SELECT ci.*, c.campaigner_id FROM campaign_images ci
                         JOIN campaigns c ON ci.campaign_id=c.campaign_id
                         WHERE ci.image_id=$imageId LIMIT 1");
    if (!$row || $row->num_rows === 0) {
        echo json_encode(['success'=>false,'message'=>'Image not found.']); exit;
    }
    $img = $row->fetch_assoc();
    if ($role !== 'admin' && $img['campaigner_id'] != $uid) {
        echo json_encode(['success'=>false,'message'=>'Access denied.']); exit;
    }
    // Remove physical file — strip any host/base prefix to get local path
    $imgUrl = $img['image_url'];
    // Support both /chama/uploads/... and full BASE URLs
    $relPath = parse_url($imgUrl, PHP_URL_PATH); // get just the path part
    $relPath = preg_replace('#^/chama#', '', $relPath); // strip /chama prefix if local dev
    $localPath = __DIR__ . $relPath;
    if (file_exists($localPath)) @unlink($localPath);
    $conn->query("DELETE FROM campaign_images WHERE image_id=$imageId");
    echo json_encode(['success'=>true,'message'=>'Image deleted.']);
    exit;
}

// ── SET cover image ───────────────────────────────────────────
if ($action === 'set_cover' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $imageId    = (int)($_POST['image_id']    ?? 0);
    $campaignId = (int)($_POST['campaign_id'] ?? 0);
    $row = $conn->query("SELECT ci.*, c.campaigner_id FROM campaign_images ci
                         JOIN campaigns c ON ci.campaign_id=c.campaign_id
                         WHERE ci.image_id=$imageId AND ci.campaign_id=$campaignId LIMIT 1");
    if (!$row || $row->num_rows === 0) {
        echo json_encode(['success'=>false,'message'=>'Image not found.']); exit;
    }
    $img = $row->fetch_assoc();
    if ($role !== 'admin' && $img['campaigner_id'] != $uid) {
        echo json_encode(['success'=>false,'message'=>'Access denied.']); exit;
    }
    $conn->query("UPDATE campaign_images SET is_cover=0 WHERE campaign_id=$campaignId");
    $conn->query("UPDATE campaign_images SET is_cover=1 WHERE image_id=$imageId");
    $urlEsc = $conn->real_escape_string($img['image_url']);
    $conn->query("UPDATE campaigns SET image_url='$urlEsc' WHERE campaign_id=$campaignId");
    echo json_encode(['success'=>true,'message'=>'Cover image updated.']);
    exit;
}

// ── GET images for a campaign ─────────────────────────────────
if ($action === 'list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $campaignId = (int)($_GET['campaign_id'] ?? 0);
    $result = $conn->query(
        "SELECT image_id, image_url, is_cover, sort_order, caption
         FROM campaign_images WHERE campaign_id=$campaignId
         ORDER BY is_cover DESC, sort_order ASC"
    );
    $images = [];
    while ($r = $result->fetch_assoc()) $images[] = $r;
    echo json_encode(['success'=>true,'images'=>$images]);
    exit;
}

http_response_code(400);
echo json_encode(['success'=>false,'message'=>'Unknown action.']);
