<?php
// ============================================================
// ChamaFunds – db/connection.php
// ============================================================

// Reuse existing connection if config.php already created it
if (isset($GLOBALS['conn']) && $GLOBALS['conn'] instanceof mysqli) {
    $conn = $GLOBALS['conn'];
    return $conn;
}

// Detect environment
$isLocal = strpos($_SERVER['HTTP_HOST'], 'localhost') !== false ||
           strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false;

if ($isLocal) {
    $host     = 'localhost';
    $username = 'root';
    $password = '';
    $database = 'chamafunds';
} else {
    $host     = 'localhost';
    $username = 'u850523537_VPS_ChamaUser';
    $password = '@Kt2026#Kakebe';
    $database = 'u850523537_ChamaFunds';
}

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

$GLOBALS['conn'] = $conn;

return $conn;
