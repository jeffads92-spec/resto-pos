<?php
require_once '../config.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$id = intval($_GET['id']);

// Get transaction
$query = "SELECT t.*, u.username, m.name as member_name 
          FROM transactions t
          LEFT JOIN users u ON t.user_id = u.id
          LEFT JOIN members m ON t.member_id = m.id
          WHERE t.id = $id";
$result = $conn->query($query);

if ($result->num_rows == 0) {
    echo json_encode(['success' => false, 'message' => 'Transaction not found']);
    exit();
}

$transaction = $result->fetch_assoc();
$transaction['created_at'] = date('d F Y H:i', strtotime($transaction['created_at']));

// Get items
$items_query = "SELECT ti.*, p.name as product_name 
                FROM transaction_items ti
                JOIN products p ON ti.product_id = p.id
                WHERE ti.transaction_id = $id";
$items_result = $conn->query($items_query);

$items = [];
while ($item = $items_result->fetch_assoc()) {
    $items[] = $item;
}

echo json_encode([
    'success' => true,
    'transaction' => $transaction,
    'items' => $items
]);
?>
