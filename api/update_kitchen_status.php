<?php
// ============================================
// API: Update Kitchen Status (FIXED)
// File: api/update_kitchen_status.php
// Error Fixed: menggunakan transaction_items
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
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['order_id']) || !isset($data['status'])) {
        throw new Exception('Data tidak lengkap');
    }
    
    $order_id = intval($data['order_id']);
    $status = $data['status'];
    
    // Validasi status
    $valid_statuses = ['pending', 'preparing', 'ready', 'served'];
    if (!in_array($status, $valid_statuses)) {
        throw new Exception('Status tidak valid');
    }
    
    // Update status di transaction_items (FIXED)
    $stmt = $conn->prepare("
        UPDATE transaction_items 
        SET kitchen_status = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->bind_param("si", $status, $order_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Gagal update status: ' . $stmt->error);
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'message' => 'Status berhasil diupdate'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

if (isset($conn)) {
    $conn->close();
}
?>
