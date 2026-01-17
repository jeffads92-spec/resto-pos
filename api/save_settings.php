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

$type = $_POST['type'] ?? '';

// Check if settings exist
$check_query = "SELECT id FROM settings LIMIT 1";
$check_result = mysqli_query($conn, $check_query);
$settings_exist = mysqli_num_rows($check_result) > 0;

if ($type == 'store') {
    $store_name = trim($_POST['store_name'] ?? '');
    $store_address = trim($_POST['store_address'] ?? '');
    $store_phone = trim($_POST['store_phone'] ?? '');
    $store_email = trim($_POST['store_email'] ?? '');
    
    if (!$store_name) {
        echo json_encode(['success' => false, 'message' => 'Nama toko wajib diisi']);
        exit;
    }
    
    if ($settings_exist) {
        $query = "UPDATE settings SET store_name = ?, store_address = ?, store_phone = ?, store_email = ? WHERE id = 1";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssss", $store_name, $store_address, $store_phone, $store_email);
    } else {
        $query = "INSERT INTO settings (store_name, store_address, store_phone, store_email) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ssss", $store_name, $store_address, $store_phone, $store_email);
    }
    
} elseif ($type == 'tax') {
    $tax_rate = floatval($_POST['tax_rate'] ?? 10);
    $member_discount = floatval($_POST['member_discount'] ?? 5);
    $points_per_rupiah = intval($_POST['points_per_rupiah'] ?? 1000);
    
    if ($settings_exist) {
        $query = "UPDATE settings SET tax_rate = ?, member_discount = ?, points_per_rupiah = ? WHERE id = 1";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ddi", $tax_rate, $member_discount, $points_per_rupiah);
    } else {
        $query = "INSERT INTO settings (tax_rate, member_discount, points_per_rupiah) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ddi", $tax_rate, $member_discount, $points_per_rupiah);
    }
    
} elseif ($type == 'receipt') {
    $receipt_width = intval($_POST['receipt_width'] ?? 58);
    $receipt_footer = trim($_POST['receipt_footer'] ?? 'Terima kasih atas kunjungan Anda!');
    $show_logo = isset($_POST['show_logo']) ? 1 : 0;
    
    if ($settings_exist) {
        $query = "UPDATE settings SET receipt_width = ?, receipt_footer = ?, show_logo = ? WHERE id = 1";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "isi", $receipt_width, $receipt_footer, $show_logo);
    } else {
        $query = "INSERT INTO settings (receipt_width, receipt_footer, show_logo) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "isi", $receipt_width, $receipt_footer, $show_logo);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Tipe pengaturan tidak valid']);
    exit;
}

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true, 'message' => 'Pengaturan berhasil disimpan']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan pengaturan: ' . mysqli_error($conn)]);
}

mysqli_close($conn);
?>
