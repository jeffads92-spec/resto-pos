<?php
require_once '../config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

$id = intval($data['id']);
$status = $conn->real_escape_string($data['status']);

// Validate status
$valid_statuses = ['pending', 'preparing', 'ready', 'served'];
if (!in_array($status, $valid_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

// Update status
$sql = "UPDATE kitchen_orders SET status = '$status'";

if ($status == 'preparing') {
    $sql .= ", started_at = NOW()";
} elseif ($status == 'ready' || $status == 'served') {
    $sql .= ", completed_at = NOW()";
}

$sql .= " WHERE id = $id";

if ($conn->query($sql)) {
    echo json_encode(['success' => true, 'message' => 'Status updated']);
} else {
    echo json_encode(['success' => false, 'message' => $conn->error]);
}
?>
