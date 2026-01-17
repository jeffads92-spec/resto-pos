<?php
// ============================================
// API: Process Transaction (FIXED)
// File: api/process_transaction.php
// ============================================

session_start();
require_once '../config.php';

header('Content-Type: application/json');

// Cek login
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Validasi input
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }

    // Validasi required fields
    if (empty($data['items']) || !is_array($data['items'])) {
        throw new Exception('Items tidak boleh kosong');
    }

    if (!isset($data['total']) || $data['total'] <= 0) {
        throw new Exception('Total transaksi tidak valid');
    }

    // Start transaction
    $conn->begin_transaction();

    // Generate transaction code
    $transaction_code = 'TRX-' . date('YmdHis') . '-' . rand(1000, 9999);

    // Insert transaction
    $stmt = $conn->prepare("
        INSERT INTO transactions (
            transaction_code, 
            transaction_date, 
            member_id, 
            subtotal, 
            tax, 
            discount, 
            total, 
            payment_method, 
            payment_amount, 
            change_amount, 
            cashier_id, 
            notes,
            status
        ) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed')
    ");

    $member_id = !empty($data['member_id']) ? $data['member_id'] : null;
    $subtotal = floatval($data['subtotal'] ?? $data['total']);
    $tax = floatval($data['tax'] ?? 0);
    $discount = floatval($data['discount'] ?? 0);
    $total = floatval($data['total']);
    $payment_method = $data['payment_method'] ?? 'cash';
    $payment_amount = floatval($data['payment_amount'] ?? $total);
    $change_amount = floatval($data['change_amount'] ?? 0);
    $cashier_id = $_SESSION['user_id'];
    $notes = $data['notes'] ?? '';

    $stmt->bind_param(
        "siddddsddis",
        $transaction_code,
        $member_id,
        $subtotal,
        $tax,
        $discount,
        $total,
        $payment_method,
        $payment_amount,
        $change_amount,
        $cashier_id,
        $notes
    );

    if (!$stmt->execute()) {
        throw new Exception('Gagal menyimpan transaksi: ' . $stmt->error);
    }

    $transaction_id = $conn->insert_id;
    $stmt->close();

    // Insert transaction items
    $stmt = $conn->prepare("
        INSERT INTO transaction_items (
            transaction_id, 
            product_id, 
            product_name, 
            quantity, 
            price, 
            subtotal,
            notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($data['items'] as $item) {
        $product_id = intval($item['product_id'] ?? $item['id']);
        $product_name = $item['product_name'] ?? $item['name'] ?? '';
        $quantity = intval($item['quantity'] ?? 1);
        $price = floatval($item['price'] ?? 0);
        $item_subtotal = $price * $quantity;
        $item_notes = $item['notes'] ?? '';

        $stmt->bind_param(
            "iisidds",
            $transaction_id,
            $product_id,
            $product_name,
            $quantity,
            $price,
            $item_subtotal,
            $item_notes
        );

        if (!$stmt->execute()) {
            throw new Exception('Gagal menyimpan item: ' . $stmt->error);
        }

        // Update stock
        $stmt_stock = $conn->prepare("
            UPDATE products 
            SET stock = stock - ? 
            WHERE id = ?
        ");
        $stmt_stock->bind_param("ii", $quantity, $product_id);
        if (!$stmt_stock->execute()) {
            throw new Exception('Gagal update stok: ' . $stmt_stock->error);
        }
        $stmt_stock->close();

        // Insert stock history
        $stmt_history = $conn->prepare("
            INSERT INTO stock_history (
                product_id, 
                transaction_type, 
                quantity, 
                stock_before, 
                stock_after, 
                reference_id, 
                reference_type, 
                user_id, 
                notes
            ) 
            SELECT 
                ?, 
                'sale', 
                ?, 
                stock + ?, 
                stock, 
                ?, 
                'transaction', 
                ?, 
                ?
            FROM products 
            WHERE id = ?
            LIMIT 1
        ");
        $stmt_history->bind_param(
            "iiiissi",
            $product_id,
            $quantity,
            $quantity,
            $transaction_id,
            $cashier_id,
            $item_notes,
            $product_id
        );
        if (!$stmt_history->execute()) {
            throw new Exception('Gagal insert stock history: ' . $stmt_history->error);
        }
        $stmt_history->close();
    }

    $stmt->close();

    // Update member points jika ada
    if ($member_id) {
        $points = floor($total / 1000); // 1 point per 1000
        $stmt_member = $conn->prepare("
            UPDATE members 
            SET points = points + ?, 
                total_spent = total_spent + ? 
            WHERE id = ?
        ");
        $stmt_member->bind_param("idi", $points, $total, $member_id);
        $stmt_member->execute();
        $stmt_member->close();
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Transaksi berhasil',
        'transaction_id' => $transaction_id,
        'transaction_code' => $transaction_code
    ]);

} catch (Exception $e) {
    // Rollback jika error
    if (isset($conn)) {
        $conn->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

if (isset($conn)) {
    $conn->close();
}
?>