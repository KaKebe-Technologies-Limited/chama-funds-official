<?php
// ============================================================
// ChamaFunds – api/hero_slides.php
// Admin management for the homepage hero photo slider
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';

if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin only.']);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ── LIST all slides (admin panel) ─────────────────────────────
if ($action === 'list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = $conn->query("SELECT * FROM hero_slides ORDER BY sort_order ASC, slide_id ASC");
    $rows = [];
    while ($r = $result->fetch_assoc()) $rows[] = $r;
    echo json_encode(['success' => true, 'slides' => $rows]);
    exit;
}

// ── UPLOAD a new slide image ──────────────────────────────────
if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_FILES['image']['tmp_name'])) {
        echo json_encode(['success' => false, 'message' => 'Please choose an image.']);
        exit;
    }

    $ext     = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowed) || $_FILES['image']['size'] > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'message' => 'Image must be JPG, PNG or WEBP and under 5MB.']);
        exit;
    }

    $uploadDir = __DIR__ . '/../uploads/hero/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $filename = 'hero_' . uniqid() . '_' . time() . '.' . $ext;
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $filename)) {
        echo json_encode(['success' => false, 'message' => 'Upload failed. Please try again.']);
        exit;
    }

    $altText   = trim($_POST['alt_text'] ?? 'ChamaFunds campaign photo');
    $altEsc    = $conn->real_escape_string($altText);
    $imageUrl  = '/uploads/hero/' . $filename;
    $imageEsc  = $conn->real_escape_string($imageUrl);

    $maxOrder = (int)($conn->query("SELECT COALESCE(MAX(sort_order),0) FROM hero_slides")->fetch_row()[0]);

    $conn->query(
        "INSERT INTO hero_slides (image_url, alt_text, sort_order, is_active)
         VALUES ('$imageEsc', '$altEsc', " . ($maxOrder + 1) . ", 1)"
    );

    echo json_encode(['success' => true, 'slide_id' => $conn->insert_id, 'image_url' => BASE . $imageUrl]);
    exit;
}

// ── DELETE a slide ─────────────────────────────────────────────
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['slide_id'] ?? 0);
    $row = $conn->query("SELECT image_url FROM hero_slides WHERE slide_id = $id LIMIT 1");
    if (!$row || $row->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Slide not found.']);
        exit;
    }
    $imgUrl = $row->fetch_assoc()['image_url'];

    $conn->query("DELETE FROM hero_slides WHERE slide_id = $id");

    // Only remove the file if it lives in our own uploads/hero/ folder
    // (seed photos in img/slider/ are shared assets — leave those alone).
    if (strpos($imgUrl, '/uploads/hero/') === 0) {
        $path = __DIR__ . '/..' . $imgUrl;
        if (file_exists($path)) @unlink($path);
    }

    echo json_encode(['success' => true]);
    exit;
}

// ── TOGGLE active/inactive ──────────────────────────────────────
if ($action === 'toggle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['slide_id'] ?? 0);
    $conn->query("UPDATE hero_slides SET is_active = 1 - is_active WHERE slide_id = $id");
    echo json_encode(['success' => true]);
    exit;
}

// ── REORDER (move up / down) ────────────────────────────────────
if ($action === 'move' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id        = (int)($_POST['slide_id'] ?? 0);
    $direction = $_POST['direction'] ?? '';

    $current = $conn->query("SELECT slide_id, sort_order FROM hero_slides WHERE slide_id = $id LIMIT 1");
    if (!$current || $current->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Slide not found.']);
        exit;
    }
    $currentRow = $current->fetch_assoc();

    $op = $direction === 'up' ? '<' : '>';
    $ord = $direction === 'up' ? 'DESC' : 'ASC';
    $neighbor = $conn->query(
        "SELECT slide_id, sort_order FROM hero_slides
         WHERE sort_order $op {$currentRow['sort_order']}
         ORDER BY sort_order $ord LIMIT 1"
    );
    if ($neighbor && $neighbor->num_rows > 0) {
        $neighborRow = $neighbor->fetch_assoc();
        $conn->query("UPDATE hero_slides SET sort_order = {$neighborRow['sort_order']} WHERE slide_id = {$currentRow['slide_id']}");
        $conn->query("UPDATE hero_slides SET sort_order = {$currentRow['sort_order']} WHERE slide_id = {$neighborRow['slide_id']}");
    }

    echo json_encode(['success' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown action.']);
