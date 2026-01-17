<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$user_id = intval($data['id'] ?? 0);
$status = $data['status'] ?? '';

if (!$user_id || !in_array($status, ['active', 'inactive'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

// Prevent disabling self
if ($user_id == $_SESSION['user_id']) {
    echo json_encode(['success' => false, 'message' => 'Tidak dapat mengubah status akun sendiri']);
    exit;
}

$update_query = "UPDATE users SET status = ? WHERE id = ?";
$stmt = mysqli_prepare($conn, $update_query);
mysqli_stmt_bind_param($stmt, "si", $status, $user_id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'Status user berhasil diubah']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal mengubah status: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>
