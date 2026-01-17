<?php
require_once '../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Handle image upload
$image_name = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $target_dir = "../uploads/products/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $image_name = time() . '_' . basename($_FILES['image']['name']);
    $target_file = $target_dir . $image_name;
    move_uploaded_file($_FILES['image']['tmp_name'], $target_file);
}

// Insert product
$stmt = $conn->prepare("INSERT INTO products (category_id, product_code, product_name, description, price, cost_price, stock, min_stock, image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

$stmt->bind_param("isssddiiis", 
    $_POST['category_id'],
    $_POST['product_code'],
    $_POST['product_name'],
    $_POST['description'],
    $_POST['price'],
    $_POST['cost_price'],
    $_POST['stock'],
    $_POST['min_stock'],
    $image_name,
    $_POST['status']
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Produk berhasil ditambahkan']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menambahkan produk']);
}
?>
