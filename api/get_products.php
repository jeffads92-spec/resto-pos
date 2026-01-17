<?php
session_start();
require_once '../config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get filter parameters
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$status = isset($_GET['status']) ? $_GET['status'] : 'active';

// Build query
$query = "SELECT p.*, c.category_name 
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.id
          WHERE p.status = ?";

$params = [$status];
$types = "s";

// Add category filter
if ($category_id > 0) {
    $query .= " AND p.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}

// Add search filter
if (!empty($search)) {
    $query .= " AND (p.product_name LIKE ? OR p.product_code LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= "ss";
}

$query .= " ORDER BY p.product_name ASC";

// Prepare and execute
$stmt = mysqli_prepare($conn, $query);
if ($stmt) {
    // Bind parameters dynamically
    $bind_params = [$types];
    foreach ($params as $key => $value) {
        $bind_params[] = &$params[$key];
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_params);
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $products = [];
    while ($row = mysqli_fetch_assoc($result)) {
        // Add stock status
        $row['stock_status'] = $row['stock'] <= $row['min_stock'] ? 'low' : 'normal';
        
        // Format image path
        if ($row['image'] && file_exists('../uploads/products/' . $row['image'])) {
            $row['image_url'] = 'uploads/products/' . $row['image'];
        } else {
            $row['image_url'] = 'assets/images/no-image.png';
        }
        
        $products[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'products' => $products,
        'total' => count($products)
    ]);
    
    mysqli_stmt_close($stmt);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Query preparation failed: ' . mysqli_error($conn)
    ]);
}

mysqli_close($conn);
?>
