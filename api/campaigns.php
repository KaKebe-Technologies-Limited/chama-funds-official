<?php
// ============================================================
// ChamaFunds – api/campaigns.php
// CRUD for campaigns
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/notifications.php';
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ── Helper function to get full image URL ────────────────────
function getFullImageUrl($path) {
    if (empty($path)) return '';
    if (strpos($path, 'http') === 0) return $path; // already absolute
    // Use BASE so it works on both localhost and live
    return BASE . '/' . ltrim($path, '/');
}

// ── LIST / SEARCH campaigns ───────────────────────────────────
if ($action === 'list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $search   = $conn->real_escape_string($_GET['search']   ?? '');
    $category = $conn->real_escape_string($_GET['category'] ?? '');
    $country  = $conn->real_escape_string($_GET['country']  ?? '');
    $sort     = $_GET['sort'] ?? 'most-recent';
    $page     = max(1, (int)($_GET['page'] ?? 1));
    $limit    = 9;
    $offset   = ($page - 1) * $limit;

    $where = ["c.status = 'active'"];
    if ($search)   $where[] = "(c.title LIKE '%$search%' OR c.description LIKE '%$search%')";
    if ($category) $where[] = "c.category = '$category'";
    if ($country)  $where[] = "c.country = '$country'";

    $whereClause = 'WHERE ' . implode(' AND ', $where);

    $orderMap = [
        'most-funded'  => 'c.raised_amount DESC',
        'ending-soon'  => 'c.end_date ASC',
        'most-recent'  => 'c.created_at DESC',
    ];
    $orderBy = $orderMap[$sort] ?? 'c.created_at DESC';

    $total = $conn->query("SELECT COUNT(*) FROM campaigns c $whereClause")->fetch_row()[0];

    $sql = "SELECT c.*, u.full_name AS campaigner_name, u.avatar_url AS campaigner_avatar,
                   ROUND((c.raised_amount / c.goal_amount) * 100, 1) AS pct,
                   DATEDIFF(c.end_date, NOW()) AS days_left
            FROM campaigns c
            JOIN users u ON c.campaigner_id = u.user_id
            $whereClause
            ORDER BY $orderBy
            LIMIT $limit OFFSET $offset";

    $result = $conn->query($sql);
    $rows   = [];
    while ($r = $result->fetch_assoc()) {
        // Convert image URLs to full URLs for response
        $r['image_url'] = getFullImageUrl($r['image_url'] ?? '');
        $rows[] = $r;
    }

    echo json_encode(['success' => true, 'campaigns' => $rows, 'total' => (int)$total, 'page' => $page, 'limit' => $limit]);
    exit;
}

// ── GET single campaign ───────────────────────────────────────
if ($action === 'get' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $id   = (int)($_GET['id'] ?? 0);
    $slug = $conn->real_escape_string($_GET['slug'] ?? '');

    $condition = $id ? "c.campaign_id = $id" : "c.slug = '$slug'";
    $sql = "SELECT c.*, u.full_name AS campaigner_name, u.email AS campaigner_email,
                   u.avatar_url AS campaigner_avatar, u.phone AS campaigner_phone,
                   ROUND((c.raised_amount / c.goal_amount) * 100, 1) AS pct,
                   DATEDIFF(c.end_date, NOW()) AS days_left
            FROM campaigns c
            JOIN users u ON c.campaigner_id = u.user_id
            WHERE $condition LIMIT 1";

    $result = $conn->query($sql);
    if (!$result || $result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Campaign not found.']);
        exit;
    }
    $campaign = $result->fetch_assoc();
    
    // Convert image URLs to full URLs
    $campaign['image_url'] = getFullImageUrl($campaign['image_url'] ?? '');

    // Increment view count
    $cid = $campaign['campaign_id'];
    $conn->query("UPDATE campaigns SET view_count = view_count + 1 WHERE campaign_id = $cid");

    // Recent donations
    $dons = $conn->query(
        "SELECT donor_name, is_anonymous, amount, mobile_money_network, payment_date
         FROM donations
         WHERE campaign_id = $cid AND status = 'completed'
         ORDER BY payment_date DESC LIMIT 10"
    );
    $donations = [];
    while ($d = $dons->fetch_assoc()) $donations[] = $d;

    echo json_encode(['success' => true, 'campaign' => $campaign, 'recent_donations' => $donations]);
    exit;
}

// ── CREATE campaign ────────────────────────────────────────────
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'You must be logged in to create a campaign.']);
        exit;
    }
    // Allow any logged-in user — role is set to campaigner on signup or elevated by admin
    if (!in_array($_SESSION['role'], ['admin', 'campaigner', 'donor'])) {
        echo json_encode(['success' => false, 'message' => 'Please log in to create a campaign.']);
        exit;
    }

    $title      = trim($_POST['title'] ?? '');
    $description= trim($_POST['description'] ?? '');
    $category   = trim($_POST['category'] ?? '');
    $goalAmount = (float)($_POST['goal_amount'] ?? 0);
    $currency   = $conn->real_escape_string($_POST['currency'] ?? 'UGX');
    $momoNumber = trim($_POST['mobile_money_number'] ?? '');
    $momoNet    = trim($_POST['mobile_money_network'] ?? '');
    $country    = trim($_POST['country'] ?? 'Uganda');
    $endDate    = trim($_POST['end_date'] ?? '');

    if (!$title || !$description || !$category || $goalAmount < 1000 || !$momoNumber || !$momoNet) {
        echo json_encode(['success' => false, 'message' => 'All required fields must be filled.']);
        exit;
    }

    // Generate unique slug
    $baseSlug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
    $slug     = $baseSlug . '-' . substr(uniqid(), -5);
    $slugEsc  = $conn->real_escape_string($slug);

    $titleEsc  = $conn->real_escape_string($title);
    $descEsc   = $conn->real_escape_string($description);
    $catEsc    = $conn->real_escape_string($category);
    $momoNumEsc= $conn->real_escape_string($momoNumber);
    $momoNetEsc= $conn->real_escape_string($momoNet);
    $ctryEsc   = $conn->real_escape_string($country);
    $uid       = (int)$_SESSION['user_id'];

    // ── Handle multi-image upload (images[] or legacy image) ──
    $uploadDir = __DIR__ . '/../uploads/campaigns/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $allowed  = ['jpg','jpeg','png','webp'];
    $maxBytes = 5 * 1024 * 1024;

    // Normalise: support name="images[]" (new multi) or name="image" (legacy)
    $rawFiles = [];
    if (!empty($_FILES['images']['tmp_name'])) {
        $tmpNames  = is_array($_FILES['images']['tmp_name']) ? $_FILES['images']['tmp_name'] : [$_FILES['images']['tmp_name']];
        $origNames = is_array($_FILES['images']['name'])     ? $_FILES['images']['name']     : [$_FILES['images']['name']];
        $sizes     = is_array($_FILES['images']['size'])     ? $_FILES['images']['size']     : [$_FILES['images']['size']];
        foreach ($tmpNames as $i => $tmp) {
            if (!empty($tmp) && is_uploaded_file($tmp))
                $rawFiles[] = ['tmp'=>$tmp,'name'=>$origNames[$i],'size'=>$sizes[$i]];
        }
    } elseif (!empty($_FILES['image']['tmp_name'])) {
        $rawFiles[] = ['tmp'=>$_FILES['image']['tmp_name'],'name'=>$_FILES['image']['name'],'size'=>$_FILES['image']['size']];
    }

    $imageUrls = [];
    foreach ($rawFiles as $f) {
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed) || $f['size'] > $maxBytes) continue;
        $filename = 'camp_' . $uid . '_' . time() . '_' . count($imageUrls) . '.' . $ext;
        if (move_uploaded_file($f['tmp'], $uploadDir . $filename)) {
            $imageUrls[] = '/uploads/campaigns/' . $filename; // relative — works on any domain
        }
    }

    $imageUrl    = $imageUrls[0] ?? '';      // cover = first image
    $imageUrlEsc = $conn->real_escape_string($imageUrl);
    $endDateSql  = $endDate ? "'$endDate'" : 'NULL';

    // Campaigns are created as 'draft', awaiting admin review
    $conn->query(
        "INSERT INTO campaigns (campaigner_id, title, slug, description, category, goal_amount, currency,
                                mobile_money_number, mobile_money_network, status, country, image_url, end_date)
         VALUES ($uid, '$titleEsc', '$slugEsc', '$descEsc', '$catEsc', $goalAmount, '$currency',
                 '$momoNumEsc', '$momoNetEsc', 'draft', '$ctryEsc', '$imageUrlEsc', $endDateSql)"
    );
    $newId = $conn->insert_id;

    // ── Save all images to campaign_images ─────────────────────
    try {
        foreach ($imageUrls as $sort => $url) {
            $urlEsc  = $conn->real_escape_string($url);
            $isCover = ($sort === 0) ? 1 : 0;
            $conn->query(
                "INSERT INTO campaign_images (campaign_id, image_url, is_cover, sort_order)
                 VALUES ($newId, '$urlEsc', $isCover, $sort)"
            );
        }
    } catch (Exception $e) { /* table may not exist yet — non-fatal */ }

    // ── Send email notification to admin ──────────────────────
    $campaign_data = [
        'campaign_id'      => $newId,
        'title'            => $title,
        'campaigner_name'  => $_SESSION['user']['full_name'] ?? 'Unknown',
        'campaigner_email' => $_SESSION['user']['email']     ?? '',
        'campaigner_phone' => $_SESSION['user']['phone']     ?? 'N/A',
        'category'         => $category,
        'goal_amount'      => $goalAmount,
        'currency'         => $currency,
        'country'          => $country,
    ];
    // Fire-and-forget — email failure must never block campaign creation
    try {
        notifyNewCampaign($conn, $newId, $campaign_data);
    } catch (Exception $e) {
        error_log('Campaign notification error: ' . $e->getMessage());
    }

    echo json_encode([
        'success'     => true,
        'message'     => 'Campaign submitted for review! It will go live within 48 hours.',
        'campaign_id' => $newId,
        'slug'        => $slug,
    ]);
    exit;
}

// ── UPDATE campaign (campaigner owns it, or admin) ────────────
if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
        exit;
    }
    $id   = (int)($_POST['campaign_id'] ?? 0);
    $uid  = (int)$_SESSION['user_id'];
    $role = $_SESSION['role'];

    // Ownership check
    $check = $conn->query("SELECT campaigner_id, title FROM campaigns WHERE campaign_id = $id LIMIT 1");
    if (!$check || $check->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Campaign not found.']);
        exit;
    }
    $c = $check->fetch_assoc();
    if ($role !== 'admin' && $c['campaigner_id'] != $uid) {
        echo json_encode(['success' => false, 'message' => 'Access denied.']);
        exit;
    }

    $sets   = [];
    $allowed = ['title','description','category','goal_amount','mobile_money_number','mobile_money_network','country','end_date'];
    foreach ($allowed as $field) {
        if (isset($_POST[$field])) {
            $val    = $conn->real_escape_string(trim($_POST[$field]));
            $sets[] = "`$field` = '$val'";
        }
    }
    // Admin-only status change
    if ($role === 'admin' && isset($_POST['status'])) {
        $s      = $conn->real_escape_string($_POST['status']);
        $sets[] = "status = '$s'";
    }
    if (empty($sets)) {
        echo json_encode(['success' => false, 'message' => 'Nothing to update.']);
        exit;
    }
    $conn->query("UPDATE campaigns SET " . implode(', ', $sets) . " WHERE campaign_id = $id");
    echo json_encode(['success' => true, 'message' => 'Campaign updated.']);
    exit;
}

// ── DELETE campaign (admin only) ──────────────────────────────
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_SESSION['role'] ?? '') !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Admin only.']);
        exit;
    }
    $id = (int)($_POST['campaign_id'] ?? 0);
    $conn->query("UPDATE campaigns SET status = 'suspended' WHERE campaign_id = $id");
    echo json_encode(['success' => true, 'message' => 'Campaign suspended.']);
    exit;
}

// ── Track share count ─────────────────────────────────────────
if ($action === 'track_share' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['campaign_id'] ?? 0);
    if ($id > 0) $conn->query("UPDATE campaigns SET share_count = share_count + 1 WHERE campaign_id = $id");
    echo json_encode(['success' => true]);
    exit;
}

// ── Admin: change status ──────────────────────────────────────
if ($action === 'set_status' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_SESSION['role'] ?? '') !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Admin only.']);
        exit;
    }
    $id     = (int)($_POST['campaign_id'] ?? 0);
    $status = $conn->real_escape_string($_POST['status'] ?? '');
    $allowed = ['active','paused','suspended','completed','flagged','draft'];
    if (!in_array($status, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status.']);
        exit;
    }
    $conn->query("UPDATE campaigns SET status = '$status' WHERE campaign_id = $id");
    echo json_encode(['success' => true, 'message' => "Campaign set to $status."]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown action.']);