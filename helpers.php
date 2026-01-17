<?php
/**
 * Helper Functions untuk Smart Resto POS
 * File: helpers.php
 */

/**
 * Get product image with fallback
 */
function get_product_image($image_name, $size = 'medium') {
    if (empty($image_name)) {
        return defined('NO_IMAGE') ? NO_IMAGE : 'assets/images/no-image.png';
    }
    
    $upload_path = defined('UPLOAD_PATH') ? UPLOAD_PATH : 'uploads/products/';
    $image_path = $upload_path . $image_name;
    
    // Cek apakah file ada
    if (file_exists($image_path)) {
        return $image_path;
    }
    
    return defined('NO_IMAGE') ? NO_IMAGE : 'assets/images/no-image.png';
}

/**
 * Format currency to IDR
 */
function format_rupiah($amount, $prefix = 'Rp ') {
    return $prefix . number_format($amount, 0, ',', '.');
}

/**
 * Format date Indonesia
 */
function format_date($date, $format = 'd F Y') {
    $months = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    
    $days = [
        'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];
    
    $timestamp = strtotime($date);
    $day = date('d', $timestamp);
    $month = $months[(int)date('m', $timestamp)];
    $year = date('Y', $timestamp);
    $day_name = $days[date('l', $timestamp)];
    
    if ($format == 'd F Y') {
        return $day . ' ' . $month . ' ' . $year;
    } elseif ($format == 'l, d F Y') {
        return $day_name . ', ' . $day . ' ' . $month . ' ' . $year;
    } else {
        return date($format, $timestamp);
    }
}

/**
 * Format datetime
 */
function format_datetime($datetime) {
    return format_date($datetime, 'd F Y') . ' ' . date('H:i', strtotime($datetime));
}

/**
 * Generate invoice number
 */
function generate_invoice($prefix = 'INV') {
    return $prefix . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

/**
 * Sanitize input
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Upload image dengan validasi
 */
function upload_image($file, $destination = 'uploads/products/') {
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Error upload file'];
    }
    
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File terlalu besar (max 5MB)'];
    }
    
    $filename = $file['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed_types)) {
        return ['success' => false, 'message' => 'Format file tidak didukung'];
    }
    
    // Create unique filename
    $new_filename = uniqid() . '_' . time() . '.' . $ext;
    $upload_path = $destination . $new_filename;
    
    // Create directory if not exists
    if (!file_exists($destination)) {
        mkdir($destination, 0777, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        return ['success' => true, 'filename' => $new_filename, 'path' => $upload_path];
    }
    
    return ['success' => false, 'message' => 'Gagal upload file'];
}

/**
 * Delete image
 */
function delete_image($filename, $directory = 'uploads/products/') {
    $file_path = $directory . $filename;
    
    if (file_exists($file_path)) {
        return unlink($file_path);
    }
    
    return false;
}

/**
 * Calculate discount
 */
function calculate_discount($amount, $discount_percent) {
    return $amount * ($discount_percent / 100);
}

/**
 * Calculate tax
 */
function calculate_tax($amount, $tax_rate = 10) {
    return $amount * ($tax_rate / 100);
}

/**
 * Get stock status
 */
function get_stock_status($current_stock, $min_stock) {
    if ($current_stock == 0) {
        return ['status' => 'out', 'label' => 'Habis', 'class' => 'danger'];
    } elseif ($current_stock <= $min_stock) {
        return ['status' => 'low', 'label' => 'Menipis', 'class' => 'warning'];
    } else {
        return ['status' => 'ok', 'label' => 'Aman', 'class' => 'success'];
    }
}

/**
 * Calculate points
 */
function calculate_points($total_amount, $points_per_rupiah = 1000) {
    return floor($total_amount / $points_per_rupiah);
}

/**
 * Log activity
 */
function log_activity($conn, $user_id, $action, $description) {
    $action = $conn->real_escape_string($action);
    $description = $conn->real_escape_string($description);
    
    $sql = "INSERT INTO activity_logs (user_id, action, description, created_at) 
            VALUES ($user_id, '$action', '$description', NOW())";
    
    return $conn->query($sql);
}

/**
 * Send alert (email/whatsapp) - stub untuk implementasi nanti
 */
function send_alert($type, $recipient, $message) {
    // TODO: Implement email/whatsapp notification
    // For now, just log it
    error_log("Alert ($type) to $recipient: $message");
    return true;
}

/**
 * Check low stock products
 */
function check_low_stock($conn) {
    $query = "SELECT id, name, stock, stock_min FROM products 
              WHERE stock <= stock_min AND stock_min > 0 AND is_active = 1";
    
    $result = $conn->query($query);
    $low_stock_products = [];
    
    while ($row = $result->fetch_assoc()) {
        $low_stock_products[] = $row;
    }
    
    return $low_stock_products;
}

/**
 * Get sales summary
 */
function get_sales_summary($conn, $start_date, $end_date) {
    $query = "SELECT 
                COUNT(*) as total_transactions,
                COALESCE(SUM(subtotal), 0) as total_subtotal,
                COALESCE(SUM(discount), 0) as total_discount,
                COALESCE(SUM(tax), 0) as total_tax,
                COALESCE(SUM(total), 0) as total_sales
              FROM transactions 
              WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date'";
    
    $result = $conn->query($query);
    return $result->fetch_assoc();
}

/**
 * Export to CSV
 */
function export_to_csv($data, $filename, $headers = []) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Add headers if provided
    if (!empty($headers)) {
        fputcsv($output, $headers);
    }
    
    // Add data
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit();
}

/**
 * Validate user permission
 */
function has_permission($required_role) {
    if (!isset($_SESSION['role'])) {
        return false;
    }
    
    if ($required_role == 'admin') {
        return $_SESSION['role'] == 'admin';
    }
    
    return true; // All logged in users have access
}

/**
 * Redirect with message
 */
function redirect($url, $message = null) {
    if ($message) {
        $_SESSION['flash_message'] = $message;
    }
    header('Location: ' . $url);
    exit();
}

/**
 * Flash message display
 */
function show_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Pagination helper
 */
function paginate($conn, $table, $per_page = 20, $page = 1, $where = '1=1') {
    // Get total records
    $count_query = "SELECT COUNT(*) as total FROM $table WHERE $where";
    $count_result = $conn->query($count_query);
    $total_records = $count_result->fetch_assoc()['total'];
    
    // Calculate pagination
    $total_pages = ceil($total_records / $per_page);
    $offset = ($page - 1) * $per_page;
    
    return [
        'total_records' => $total_records,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'per_page' => $per_page,
        'offset' => $offset,
        'has_prev' => $page > 1,
        'has_next' => $page < $total_pages
    ];
}

?>
