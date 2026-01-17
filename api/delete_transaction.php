<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$transaction_id = $data['id'] ?? 0;

if (!$transaction_id) {
    echo json_encode(['success' => false, 'message' => 'Transaction ID required']);
    exit;
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Get transaction items to restore stock
    $items_query = "SELECT product_id, quantity FROM transaction_items WHERE transaction_id = ?";
    $stmt = mysqli_prepare($conn, $items_query);
    mysqli_stmt_bind_param($stmt, "i", $transaction_id);
    mysqli_stmt_execute($stmt);
    $items_result = mysqli_stmt_get_result($stmt);
    
    // Restore stock for each item
    while ($item = mysqli_fetch_assoc($items_result)) {
        $update_stock = "UPDATE products SET stock = stock + ? WHERE id = ?";
        $stmt2 = mysqli_prepare($conn, $update_stock);
        mysqli_stmt_bind_param($stmt2, "ii", $item['quantity'], $item['product_id']);
        mysqli_stmt_execute($stmt2);
        
        // Add stock history
        $history_query = "INSERT INTO stock_history (product_id, type, quantity, notes, user_id) 
                         VALUES (?, 'in', ?, 'Return from deleted transaction', ?)";
        $stmt3 = mysqli_prepare($conn, $history_query);
        $user_id = $_SESSION['user_id'];
        mysqli_stmt_bind_param($stmt3, "iii", $item['product_id'], $item['quantity'], $user_id);
        mysqli_stmt_execute($stmt3);
    }
    
    // Delete transaction items
    $delete_items = "DELETE FROM transaction_items WHERE transaction_id = ?";
    $stmt4 = mysqli_prepare($conn, $delete_items);
    mysqli_stmt_bind_param($stmt4, "i", $transaction_id);
    mysqli_stmt_execute($stmt4);
    
    // Delete transaction
    $delete_trx = "DELETE FROM transactions WHERE id = ?";
    $stmt5 = mysqli_prepare($conn, $delete_trx);
    mysqli_stmt_bind_param($stmt5, "i", $transaction_id);
    mysqli_stmt_execute($stmt5);
    
    mysqli_commit($conn);
    
    echo json_encode(['success' => true, 'message' => 'Transaction deleted and stock restored']);
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => 'Failed to delete transaction: ' . $e->getMessage()]);
}

mysqli_close($conn);
?>
