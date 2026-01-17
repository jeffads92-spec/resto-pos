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

$query = "SELECT ti.*, p.product_name
          FROM transaction_items ti
          LEFT JOIN products p ON ti.product_id = p.id
          WHERE ti.transaction_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $transaction_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$items = [];
while ($item = mysqli_fetch_assoc($result)) {
    $items[] = $item;
}

echo json_encode([
    'success' => true,
    'items' => $items
]);

mysqli_close($conn);
?>
