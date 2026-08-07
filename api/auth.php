<?php
// ============================================================
// ChamaFunds – api/auth.php
// ============================================================

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../includes/config.php';

require_once __DIR__ . '/../includes/config.php';

$action = $_GET['action'] ?? '';

if ($action === 'login') {
    $identifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        echo json_encode(['success' => false, 'message' => 'Please enter both email/phone and password.']);
        exit;
    }

    $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
    
    if ($isEmail) {
        $sql = "SELECT * FROM users WHERE email = '" . mysqli_real_escape_string($conn, $identifier) . "' AND is_active = 1";
    } else {
        $phone = preg_replace('/[^0-9]/', '', $identifier);
        $sql = "SELECT * FROM users WHERE phone = '" . mysqli_real_escape_string($conn, $phone) . "' AND is_active = 1";
    }

    $result = mysqli_query($conn, $sql);
    $user = mysqli_fetch_assoc($result);

    // Plain text password verification
    if ($user && $password === $user['password_hash']) {
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

        mysqli_query($conn, "UPDATE users SET last_login = NOW() WHERE user_id = " . $user['user_id']);

        $dest = ($user['role'] === 'admin') ? '/admin/index.php' : '/dashboard.php';
        // Honour redirect_after_auth if set
        $redirectAfter = $_SESSION['redirect_after_auth'] ?? '';
        unset($_SESSION['redirect_after_auth']);

        echo json_encode([
            'success' => true,
            'redirect' => $redirectAfter ?: BASE . $dest
        ]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Incorrect password.']);
        exit;
    }
}

if ($action === 'logout') {
    $_SESSION = array();

    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]);
    }

    session_destroy();

    echo json_encode([
        'success' => true,
        'redirect' => BASE . '/login.php?msg=logged_out'
    ]);
    exit;
}

if ($action === 'register') {
    header('Content-Type: application/json');

    $fullName = trim($_POST['full_name'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $phone    = preg_replace('/[^0-9]/', '', trim($_POST['phone'] ?? ''));
    $password = $_POST['password'] ?? '';
    $role     = in_array($_POST['role'] ?? '', ['campaigner', 'donor']) ? $_POST['role'] : 'donor';
    $country  = trim($_POST['country'] ?? 'Uganda');

    // Basic validation
    if (!$fullName || !$email || !$phone || !$password) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields.']);
        exit;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address.']);
        exit;
    }
    if (strlen($phone) < 9) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid phone number.']);
        exit;
    }
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters.']);
        exit;
    }

    // Check email uniqueness
    $emailEsc = $conn->real_escape_string($email);
    $phoneEsc = $conn->real_escape_string($phone);
    $existing = $conn->query("SELECT user_id FROM users WHERE email='$emailEsc' OR phone='$phoneEsc' LIMIT 1");
    if ($existing && $existing->num_rows > 0) {
        echo json_encode(['success' => false, 'message' => 'An account with that email or phone already exists.']);
        exit;
    }

    $nameEsc    = $conn->real_escape_string($fullName);
    $countryEsc = $conn->real_escape_string($country);
    // Store password as plain text (matching current login logic)
    $passEsc    = $conn->real_escape_string($password);

    $conn->query(
        "INSERT INTO users (full_name, email, phone, password_hash, role, country, is_active, is_verified, created_at)
         VALUES ('$nameEsc', '$emailEsc', '$phoneEsc', '$passEsc', '$role', '$countryEsc', 1, 0, NOW())"
    );

    if ($conn->insert_id) {
        $userId = $conn->insert_id;

        // Log them in immediately
        $_SESSION['user_id'] = $userId;
        $_SESSION['role']    = $role;
        $_SESSION['user']    = [
            'user_id'    => $userId,
            'full_name'  => $fullName,
            'email'      => $email,
            'phone'      => $phone,
            'role'       => $role,
            'avatar_url' => ''
        ];

        $dest = ($role === 'admin') ? '/admin/index.php' : '/dashboard.php';
        // Honour redirect_after_auth if set (e.g. from create-campaign gate)
        $redirectAfter = $_SESSION['redirect_after_auth'] ?? '';
        unset($_SESSION['redirect_after_auth']);

        echo json_encode([
            'success'  => true,
            'message'  => 'Account created successfully! Welcome to ChamaFunds.',
            'redirect' => $redirectAfter ?: BASE . $dest
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again. ' . $conn->error]);
    }
    exit;
}

?>