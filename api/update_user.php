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

$user_id = intval($_POST['user_id'] ?? 0);
$username = trim($_POST['username'] ?? '');
$full_name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? '';
$status = $_POST['status'] ?? 'active';

// Validation
if (!$user_id || !$username || !$full_name || !$role) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
}

if (!in_array($role, ['admin', 'cashier'])) {
    echo json_encode(['success' => false, 'message' => 'Role tidak valid']);
    exit;
}

// Check if username already exists (except current user)
$check_query = "SELECT id FROM users WHERE username = ? AND id != ?";
$stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($stmt, "si", $username, $user_id);
mysqli_stmt_execute($stmt);
$check_result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($check_result) > 0) {
    echo json_encode(['success' => false, 'message' => 'Username sudah digunakan']);
    exit;
}

// Update user - with or without password
if (!empty($password)) {
    if (strlen($password) < 6) {
        echo json_encode(['success' => false, 'message' => 'Password minimal 6 karakter']);
        exit;
    }
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $update_query = "UPDATE users SET username = ?, full_name = ?, email = ?, password = ?, role = ?, status = ? 
                     WHERE id = ?";
    $stmt2 = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt2, "ssssssi", $username, $full_name, $email, $hashed_password, $role, $status, $user_id);
} else {
    $update_query = "UPDATE users SET username = ?, full_name = ?, email = ?, role = ?, status = ? 
                     WHERE id = ?";
    $stmt2 = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($stmt2, "sssssi", $username, $full_name, $email, $role, $status, $user_id);
}

if (mysqli_stmt_execute($stmt2)) {
    echo json_encode([
        'success' => true,
        'message' => 'User berhasil diupdate'
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal mengupdate user: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>
