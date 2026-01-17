<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$transaction_id = $_GET['id'] ?? 0;

if (!$transaction_id) {
    echo json_encode(['success' => false, 'message' => 'Transaction ID required']);
    exit;
}

// Get transaction detail
$query = "SELECT t.*, u.username as cashier_name, m.member_name
          FROM transactions t
          LEFT JOIN users u ON t.user_id = u.id
          LEFT JOIN members m ON t.member_id = m.id
          WHERE t.id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $transaction_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$transaction = mysqli_fetch_assoc($result);

if (!$transaction) {
    echo json_encode(['success' => false, 'message' => 'Transaction not found']);
    exit;
}

// Get transaction items
$items_query = "SELECT ti.*, p.product_name
                FROM transaction_items ti
                LEFT JOIN products p ON ti.product_id = p.id
                WHERE ti.transaction_id = ?";
$stmt2 = mysqli_prepare($conn, $items_query);
mysqli_stmt_bind_param($stmt2, "i", $transaction_id);
mysqli_stmt_execute($stmt2);
$items_result = mysqli_stmt_get_result($stmt2);

$items = [];
while ($item = mysqli_fetch_assoc($items_result)) {
    $items[] = $item;
}

// Format dates
$transaction['created_at'] = date('d/m/Y H:i', strtotime($transaction['created_at']));

echo json_encode([
    'success' => true,
    'transaction' => $transaction,
    'items' => $items
]);

mysqli_close($conn);
?>
