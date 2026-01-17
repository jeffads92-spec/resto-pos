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

$product_id = $_POST['product_id'] ?? 0;
$type = $_POST['type'] ?? '';
$quantity = intval($_POST['quantity'] ?? 0);
$notes = $_POST['notes'] ?? '';
$user_id = $_SESSION['user_id'];

if (!$product_id || !$type || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
}

// Get current stock
$query = "SELECT stock, product_name FROM products WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Produk tidak ditemukan']);
    exit;
}

$stock_before = $product['stock'];

// Calculate new stock
if ($type == 'in') {
    $new_stock = $stock_before + $quantity;
} else {
    $new_stock = $stock_before - $quantity;
    if ($new_stock < 0) {
        echo json_encode(['success' => false, 'message' => 'Stok tidak mencukupi']);
        exit;
    }
}

// Update product stock
$update_query = "UPDATE products SET stock = ? WHERE id = ?";
$stmt = mysqli_prepare($conn, $update_query);
mysqli_stmt_bind_param($stmt, "ii", $new_stock, $product_id);

if (mysqli_stmt_execute($stmt)) {
    // Insert stock history
    $history_query = "INSERT INTO stock_history (product_id, type, quantity, stock_before, stock_after, notes, user_id) 
                      VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt2 = mysqli_prepare($conn, $history_query);
    mysqli_stmt_bind_param($stmt2, "isiissi", $product_id, $type, $quantity, $stock_before, $new_stock, $notes, $user_id);
    mysqli_stmt_execute($stmt2);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Stok berhasil di-update',
        'new_stock' => $new_stock
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal update stok: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>
