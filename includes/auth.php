<?php
// ============================================================
// ChamaFunds – includes/auth.php
// Session management & role-based access helpers
// ============================================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$conn = require_once __DIR__ . '/../db/connection.php';

// ── Helpers ────────────────────────────────────────────────

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function currentUser(): ?array {
    return $_SESSION['user'] ?? null;
}

function currentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

function currentRole(): string {
    return $_SESSION['role'] ?? 'guest';
}

function isAdmin(): bool {
    return currentRole() === 'admin';
}

function isCampaigner(): bool {
    return in_array(currentRole(), ['admin', 'campaigner']);
}

function requireLogin(string $redirect = ''): void {
    if (!isLoggedIn()) {
        $url = $redirect ?: (defined('BASE') ? BASE . '/login.php' : '/login.php');
        header("Location: $url");
        exit;
    }
}

function requireAdmin(string $redirect = ''): void {
    if (!isLoggedIn() || !isAdmin()) {
        $url = $redirect ?: (defined('BASE') ? BASE . '/login.php' : '/login.php');
        header("Location: $url");
        exit;
    }
}

function requireCampaigner(string $redirect = ''): void {
    if (!isLoggedIn() || !isCampaigner()) {
        $url = $redirect ?: (defined('BASE') ? BASE . '/login.php' : '/login.php');
        header("Location: $url");
        exit;
    }
}

// ── CSRF ───────────────────────────────────────────────────

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(): void {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die(json_encode(['success' => false, 'message' => 'Invalid CSRF token.']));
    }
}

// ── Sanitise input ─────────────────────────────────────────

function clean(string $val): string {
    return htmlspecialchars(trim($val), ENT_QUOTES, 'UTF-8');
}

// ── Admin log helper ───────────────────────────────────────

function logAdminAction(
    mysqli $conn,
    string $action,
    string $targetType = '',
    int    $targetId   = 0,
    string $targetName = '',
    array  $changes    = []
): void {
    if (!isAdmin()) return;
    $adminId    = currentUserId();
    $ip         = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua         = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $changesJson = $conn->real_escape_string(json_encode($changes));
    $action      = $conn->real_escape_string($action);
    $targetType  = $conn->real_escape_string($targetType);
    $targetName  = $conn->real_escape_string($targetName);
    $ip          = $conn->real_escape_string($ip);
    $ua          = $conn->real_escape_string($ua);
    $conn->query(
        "INSERT INTO admin_logs (admin_id, action, target_type, target_id, target_name, changes, ip_address, user_agent)
         VALUES ($adminId, '$action', '$targetType', $targetId, '$targetName', '$changesJson', '$ip', '$ua')"
    );
}

// ── Logout Function ────────────────────────────────────────

function logoutUser(string $redirect = ''): void {
    // Clear all session variables
    $_SESSION = array();
    
    // Delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    // Destroy the session
    session_destroy();
    
    // Redirect to login page using dynamic BASE if available
    $url = $redirect ?: (defined('BASE') ? BASE . '/login.php?msg=logged_out' : '/login.php?msg=logged_out');
    header("Location: $url");
    exit;
}

// ── Auth Check for API Endpoints ──────────────────────────

/**
 * Check if user is authenticated via API (for AJAX requests)
 * Returns JSON response if not authenticated
 */
function requireLoginApi(): void {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
        exit;
    }
}

function requireAdminApi(): void {
    if (!isLoggedIn() || !isAdmin()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden. Admin access required.']);
        exit;
    }
}

// ── Session regeneration for security ─────────────────────

function regenerateSession(): void {
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
}

// ── Get user by ID ─────────────────────────────────────────

function getUserById(mysqli $conn, int $userId): ?array {
    $stmt = $conn->prepare("SELECT user_id, full_name, email, phone, role, country, avatar_url, is_active, is_verified FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// ── Update user last login ─────────────────────────────────

function updateLastLogin(mysqli $conn, int $userId): void {
    $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
}

// ── Password verification (plain text for testing) ────────

function verifyUserPassword(mysqli $conn, string $identifier, string $password): ?array {
    // Check if identifier is email or phone
    $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
    
    if ($isEmail) {
        $sql = "SELECT * FROM users WHERE email = '" . mysqli_real_escape_string($conn, $identifier) . "' AND is_active = 1";
    } else {
        $phone = preg_replace('/[^0-9]/', '', $identifier);
        $sql = "SELECT * FROM users WHERE phone = '" . mysqli_real_escape_string($conn, $phone) . "' AND is_active = 1";
    }
    
    $result = mysqli_query($conn, $sql);
    $user = mysqli_fetch_assoc($result);
    
    // Plain text password comparison (for testing)
    if ($user && $password === $user['password_hash']) {
        return $user;
    }
    
    return null;
}

// ── Login user function ────────────────────────────────────

function loginUser(mysqli $conn, array $user): void {
    // Set session variables
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['role'] = $user['role'];
    $_SESSION['user'] = [
        'user_id' => $user['user_id'],
        'full_name' => $user['full_name'],
        'email' => $user['email'],
        'phone' => $user['phone'],
        'role' => $user['role'],
        'avatar_url' => $user['avatar_url'] ?? ''
    ];
    
    // Update last login
    updateLastLogin($conn, $user['user_id']);
    
    // Regenerate session for security
    regenerateSession();
}

// ── Get redirect URL after login ──────────────────────────

function getLoginRedirectUrl(array $user): string {
    $base = defined('BASE') ? BASE : '';
    if ($user['role'] === 'admin') {
        return $base . '/admin/index.php';
    }
    return $base . '/dashboard.php';
}
?>