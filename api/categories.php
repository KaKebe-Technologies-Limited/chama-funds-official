<?php
// ============================================================
// ChamaFunds – api/categories.php
// Admin-managed campaign categories
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ── LIST (public) ─────────────────────────────────────────────
if ($action === 'list') {
    $result = $conn->query(
        "SELECT * FROM campaign_categories WHERE is_active=1 ORDER BY sort_order, name"
    );
    $rows = [];
    while ($r = $result->fetch_assoc()) $rows[] = $r;
    echo json_encode(['success' => true, 'categories' => $rows]);
    exit;
}

// Admin-only below
if (($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admin only.']);
    exit;
}

// ── ADD ──────────────────────────────────────────────────────
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $icon  = trim($_POST['icon'] ?? '📌');
    $color = $conn->real_escape_string(trim($_POST['color_class'] ?? 'badge-other'));
    $desc  = $conn->real_escape_string(trim($_POST['description'] ?? ''));

    if (!$name) { echo json_encode(['success'=>false,'message'=>'Name required.']); exit; }

    $slug     = strtolower(preg_replace('/[^a-z0-9]+/','-',$name));
    $nameEsc  = $conn->real_escape_string($name);
    $slugEsc  = $conn->real_escape_string($slug);
    $iconEsc  = $conn->real_escape_string($icon);
    $order    = (int)($conn->query("SELECT MAX(sort_order) FROM campaign_categories")->fetch_row()[0] ?? 0) + 1;

    $conn->query(
        "INSERT INTO campaign_categories (name,slug,icon,color_class,description,sort_order)
         VALUES ('$nameEsc','$slugEsc','$iconEsc','$color','$desc',$order)"
    );
    echo json_encode(['success'=>true, 'message'=>"Category \"$name\" added."]);
    exit;
}

// ── TOGGLE ───────────────────────────────────────────────────
if ($action === 'toggle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['category_id'] ?? 0);
    $conn->query("UPDATE campaign_categories SET is_active=NOT is_active WHERE category_id=$id");
    echo json_encode(['success'=>true, 'message'=>'Category status toggled.']);
    exit;
}

// ── DELETE ───────────────────────────────────────────────────
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['category_id'] ?? 0);
    $conn->query("DELETE FROM campaign_categories WHERE category_id=$id");
    echo json_encode(['success'=>true, 'message'=>'Category deleted.']);
    exit;
}

// ── REORDER ──────────────────────────────────────────────────
if ($action === 'reorder' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $ids = json_decode($_POST['order'] ?? '[]', true);
    foreach ($ids as $i => $id) {
        $conn->query("UPDATE campaign_categories SET sort_order=".($i+1)." WHERE category_id=".(int)$id);
    }
    echo json_encode(['success'=>true]);
    exit;
}

http_response_code(400);
echo json_encode(['success'=>false,'message'=>'Unknown action.']);
