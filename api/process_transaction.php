<?php
/**
 * API untuk memproses transaksi POS
 * File: api/process_transaction.php
 */

require_once '../config.php';
session_start();

// Set header JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Get POST data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Validate required data
if (!isset($data['items']) || empty($data['items'])) {
    echo json_encode(['success' => false, 'message' => 'Keranjang kosong']);
    exit();
}

if (!isset($data['total']) || $data['total'] <= 0) {
    echo json_encode(['success' => false, 'message' => 'Total invalid']);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Generate invoice number
    $invoice_number = 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
    
    // Get member info
    $member_id = !empty($data['member_id']) ? intval($data['member_id']) : null;
    $discount_percent = 0;
    
    if ($member_id) {
        $member_query = "SELECT discount FROM members WHERE id = $member_id";
        $member_result = $conn->query($member_query);
        if ($member_result && $member_row = $member_result->fetch_assoc()) {
            $discount_percent = floatval($member_row['discount']);
        }
    }
    
    // Calculate totals
    $subtotal = 0;
    foreach ($data['items'] as $item) {
        $subtotal += floatval($item['price']) * intval($item['qty']);
    }
    
    $discount = $subtotal * ($discount_percent / 100);
    $tax = ($subtotal - $discount) * 0.1; // 10% tax
    $total = $subtotal - $discount + $tax;
    
    // Validate total
    if (abs($total - floatval($data['total'])) > 1) {
        throw new Exception('Total tidak sesuai dengan perhitungan server');
    }
    
    // Payment info
    $payment_method = $conn->real_escape_string($data['payment_method']);
    $cash_amount = isset($data['cash_amount']) ? floatval($data['cash_amount']) : $total;
    
    // Validate cash payment
    if ($payment_method === 'cash' && $cash_amount < $total) {
        throw new Exception('Uang tidak cukup');
    }
    
    $change_amount = $payment_method === 'cash' ? ($cash_amount - $total) : 0;
    
    // Insert transaction
    $user_id = intval($_SESSION['user_id']);
    $member_id_sql = $member_id ? $member_id : 'NULL';
    
    $insert_transaction = "INSERT INTO transactions 
        (invoice_number, user_id, member_id, subtotal, discount, tax, total, 
         payment_method, cash_amount, change_amount, created_at) 
        VALUES 
        ('$invoice_number', $user_id, $member_id_sql, $subtotal, $discount, $tax, $total,
         '$payment_method', $cash_amount, $change_amount, NOW())";
    
    if (!$conn->query($insert_transaction)) {
        throw new Exception('Gagal menyimpan transaksi: ' . $conn->error);
    }
    
    $transaction_id = $conn->insert_id;
    
    // Insert transaction items and update stock
    foreach ($data['items'] as $item) {
        $product_id = intval($item['id']);
        $qty = intval($item['qty']);
        $price = floatval($item['price']);
        $item_subtotal = $price * $qty;
        
        // Check stock availability
        $stock_query = "SELECT stock FROM products WHERE id = $product_id FOR UPDATE";
        $stock_result = $conn->query($stock_query);
        
        if (!$stock_result) {
            throw new Exception('Gagal mengecek stok produk');
        }
        
        $stock_row = $stock_result->fetch_assoc();
        if ($stock_row['stock'] < $qty) {
            throw new Exception('Stok tidak mencukupi untuk produk ID: ' . $product_id);
        }
        
        // Insert transaction item
        $insert_item = "INSERT INTO transaction_items 
            (transaction_id, product_id, quantity, price, subtotal) 
            VALUES 
            ($transaction_id, $product_id, $qty, $price, $item_subtotal)";
        
        if (!$conn->query($insert_item)) {
            throw new Exception('Gagal menyimpan item: ' . $conn->error);
        }
        
        // Update product stock
        $update_stock = "UPDATE products SET stock = stock - $qty WHERE id = $product_id";
        
        if (!$conn->query($update_stock)) {
            throw new Exception('Gagal update stok: ' . $conn->error);
        }
        
        // Log stock movement
        $log_stock = "INSERT INTO stock_history 
            (product_id, type, quantity, notes, created_at, created_by) 
            VALUES 
            ($product_id, 'out', $qty, 'Penjualan: $invoice_number', NOW(), $user_id)";
        
        $conn->query($log_stock); // Optional, jika gagal tidak perlu rollback
    }
    
    // Update member points if applicable
    if ($member_id) {
        $points_earned = floor($total / 1000); // 1 point per Rp 1.000
        
        $update_points = "UPDATE members 
            SET points = points + $points_earned,
                last_transaction = NOW()
            WHERE id = $member_id";
        
        $conn->query($update_points);
    }
    
    // Commit transaction
    $conn->commit();
    
    // Prepare success response
    $response = [
        'success' => true,
        'message' => 'Transaksi berhasil',
        'transaction_id' => $transaction_id,
        'invoice' => $invoice_number,
        'total' => $total,
        'cash' => $cash_amount,
        'change' => $change_amount,
        'points_earned' => $points_earned ?? 0
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
