<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$username = trim($_POST['username'] ?? '');
$full_name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? '';
$status = $_POST['status'] ?? 'active';

// Validation
if (!$username || !$full_name || !$password || !$role) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
}

if (strlen($password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password minimal 6 karakter']);
    exit;
}

if (!in_array($role, ['admin', 'cashier'])) {
    echo json_encode(['success' => false, 'message' => 'Role tidak valid']);
    exit;
}

// Check if username already exists
$check_query = "SELECT id FROM users WHERE username = ?";
$stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);
$check_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($check_result) > 0) {
    echo json_encode(['success' => false, 'message' => 'Username sudah digunakan']);
    exit;
}

// Hash password
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert user
$insert_query = "INSERT INTO users (username, full_name, email, password, role, status) 
                 VALUES (?, ?, ?, ?, ?, ?)";
$stmt2 = mysqli_prepare($conn, $insert_query);
mysqli_stmt_bind_param($stmt2, "ssssss", $username, $full_name, $email, $hashed_password, $role, $status);

if (mysqli_stmt_execute($stmt2)) {
    echo json_encode([
        'success' => true,
        'message' => 'User berhasil ditambahkan',
        'user_id' => mysqli_insert_id($conn)
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menambahkan user: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>
