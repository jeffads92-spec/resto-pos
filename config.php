<?php
// ============================================
// DATABASE CONFIGURATION
// File: config.php
// ============================================

// Database credentials
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Sesuaikan dengan password MySQL Anda
define('DB_NAME', 'smart_resto_pos');

// Application settings
define('APP_NAME', 'Smart Resto POS');
define('APP_VERSION', '1.0.0');
define('TAX_RATE', 10); // 10%
define('POINTS_PER_RUPIAH', 1000); // 1 poin per Rp 1.000

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Error reporting (set to 0 for production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    // Set charset
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

// Helper functions
function sanitize($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

function generateCode($prefix, $table, $column) {
    global $conn;
    
    $date = date('Ymd');
    $query = "SELECT MAX(SUBSTRING($column, -4)) as max_num 
              FROM $table 
              WHERE $column LIKE '$prefix-$date%'";
    
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $max_num = $row['max_num'] ?? 0;
    $next_num = str_pad($max_num + 1, 4, '0', STR_PAD_LEFT);
    
    return "$prefix-$date-$next_num";
}

function formatRupiah($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function checkLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }
}

function checkRole($required_role) {
    checkLogin();
    if ($_SESSION['role'] !== $required_role) {
        die('Akses ditolak. Anda tidak memiliki permission untuk halaman ini.');
    }
}
?>
