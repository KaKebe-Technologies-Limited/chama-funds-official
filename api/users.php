<?php
// ============================================================
// ChamaFunds – api/users.php
// Profile update, user management (admin), notifications
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/config.php';
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ── UPDATE PROFILE ────────────────────────────────────────────
if ($action === 'update_profile' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
        exit;
    }
    $uid      = (int)$_SESSION['user_id'];
    $fullName = $conn->real_escape_string(trim($_POST['full_name'] ?? ''));
    $email    = $conn->real_escape_string(trim($_POST['email'] ?? ''));
    $phone    = $conn->real_escape_string(trim($_POST['phone'] ?? ''));
    $country  = $conn->real_escape_string(trim($_POST['country'] ?? ''));

    if (empty($fullName) || empty($email) || empty($phone)) {
        echo json_encode(['success' => false, 'message' => 'Name, email and phone are required.']);
        exit;
    }

    // Check email/phone not taken by another user
    $dup = $conn->query(
        "SELECT user_id FROM users WHERE (email='$email' OR phone='$phone') AND user_id != $uid LIMIT 1"
    );
    if ($dup->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'Email or phone already in use by another account.']);
        exit;
    }

    // Handle avatar upload
    $avatarSql = '';
    if (!empty($_FILES['avatar']['tmp_name'])) {
        $uploadDir = __DIR__ . '/../uploads/avatars/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $ext     = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp'];
        if (in_array($ext, $allowed) && $_FILES['avatar']['size'] < 2 * 1024 * 1024) {
            $filename = 'avatar_' . $uid . '_' . time() . '.' . $ext;
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $uploadDir . $filename)) {
                $avatarUrl  = '/uploads/avatars/' . $filename;
                $avatarEsc  = $conn->real_escape_string($avatarUrl);
                $avatarSql  = ", avatar_url = '$avatarEsc'";
            }
        }
    }

    $conn->query(
        "UPDATE users SET full_name='$fullName', email='$email', phone='$phone', country='$country' $avatarSql
         WHERE user_id = $uid"
    );

    // Refresh session
    $_SESSION['user']['full_name'] = $fullName;
    $_SESSION['user']['email']     = $email;
    $_SESSION['user']['phone']     = $phone;
    $_SESSION['user']['country']   = $country;
    if ($avatarSql) $_SESSION['user']['avatar_url'] = $avatarUrl;

    echo json_encode(['success' => true, 'message' => 'Profile updated successfully.']);
    exit;
}

// ── CHANGE PASSWORD ───────────────────────────────────────────
if ($action === 'change_password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
        exit;
    }
    $uid        = (int)$_SESSION['user_id'];
    $currentPw  = $_POST['current_password'] ?? '';
    $newPw      = $_POST['new_password'] ?? '';
    $confirmPw  = $_POST['confirm_password'] ?? '';

    if (strlen($newPw) < 6) {
        echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters.']);
        exit;
    }
    if ($newPw !== $confirmPw) {
        echo json_encode(['success' => false, 'message' => 'New passwords do not match.']);
        exit;
    }

    $row = $conn->query("SELECT password_hash FROM users WHERE user_id = $uid LIMIT 1")->fetch_assoc();
    if (!password_verify($currentPw, $row['password_hash'])) {
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
        exit;
    }

    $hash = $conn->real_escape_string(password_hash($newPw, PASSWORD_BCRYPT));
    $conn->query("UPDATE users SET password_hash = '$hash' WHERE user_id = $uid");
    echo json_encode(['success' => true, 'message' => 'Password updated successfully.']);
    exit;
}

// ── GET NOTIFICATIONS ─────────────────────────────────────────
if ($action === 'notifications' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'notifications' => []]);
        exit;
    }
    $uid    = (int)$_SESSION['user_id'];
    $result = $conn->query(
        "SELECT * FROM notifications WHERE user_id = $uid ORDER BY created_at DESC LIMIT 20"
    );
    $rows = [];
    while ($r = $result->fetch_assoc()) $rows[] = $r;
    $unread = $conn->query("SELECT COUNT(*) FROM notifications WHERE user_id = $uid AND is_read = 0")->fetch_row()[0];
    echo json_encode(['success' => true, 'notifications' => $rows, 'unread' => (int)$unread]);
    exit;
}

// ── MARK NOTIFICATION READ ────────────────────────────────────
if ($action === 'mark_read' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false]);
        exit;
    }
    $uid = (int)$_SESSION['user_id'];
    $nid = (int)($_POST['notification_id'] ?? 0);
    if ($nid) {
        $conn->query("UPDATE notifications SET is_read = 1 WHERE notification_id = $nid AND user_id = $uid");
    } else {
        $conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $uid");
    }
    echo json_encode(['success' => true]);
    exit;
}

// ── Admin: list all users ─────────────────────────────────────
if ($action === 'admin_list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (($_SESSION['role'] ?? '') !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Admin only.']);
        exit;
    }
    $search = $conn->real_escape_string($_GET['search'] ?? '');
    $where  = $search ? "WHERE full_name LIKE '%$search%' OR email LIKE '%$search%' OR phone LIKE '%$search%'" : '';
    $page   = max(1, (int)($_GET['page'] ?? 1));
    $limit  = 25;
    $offset = ($page - 1) * $limit;

    $result = $conn->query(
        "SELECT user_id, full_name, email, phone, role, country, is_active, is_verified, created_at
         FROM users $where ORDER BY created_at DESC LIMIT $limit OFFSET $offset"
    );
    $rows  = [];
    while ($r = $result->fetch_assoc()) $rows[] = $r;
    $total = $conn->query("SELECT COUNT(*) FROM users $where")->fetch_row()[0];
    echo json_encode(['success' => true, 'users' => $rows, 'total' => (int)$total]);
    exit;
}

// ── Admin: toggle user active/banned ─────────────────────────
if ($action === 'toggle_active' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_SESSION['role'] ?? '') !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Admin only.']);
        exit;
    }
    $uid = (int)($_POST['user_id'] ?? 0);
    // Prevent admin from banning themselves
    if ($uid === (int)$_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Cannot change your own account status.']);
        exit;
    }
    $conn->query("UPDATE users SET is_active = NOT is_active WHERE user_id = $uid");
    echo json_encode(['success' => true, 'message' => 'User status toggled.']);
    exit;
}

// ── Admin: update user role ───────────────────────────────────
if ($action === 'update_role' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_SESSION['role'] ?? '') !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Admin only.']);
        exit;
    }
    $uid  = (int)($_POST['user_id'] ?? 0);
    $role = $conn->real_escape_string($_POST['role'] ?? '');
    if (!in_array($role, ['admin','campaigner','donor'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid role.']);
        exit;
    }
    $conn->query("UPDATE users SET role = '$role' WHERE user_id = $uid");
    echo json_encode(['success' => true, 'message' => 'User role updated.']);
    exit;
}

// ── Admin: delete user ────────────────────────────────────────
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (($_SESSION['role'] ?? '') !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Admin only.']);
        exit;
    }
    $uid = (int)($_POST['user_id'] ?? 0);
    if ($uid === (int)$_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Cannot delete your own account.']);
        exit;
    }
    // Soft-delete: deactivate
    $conn->query("UPDATE users SET is_active = 0, email = CONCAT('deleted_', user_id, '_', email) WHERE user_id = $uid");
    echo json_encode(['success' => true, 'message' => 'User account deactivated.']);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown action.']);
