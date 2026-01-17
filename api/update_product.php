<?php
require_once '../config.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$product_id = $_POST['product_id'];

// Handle image upload
$image_update = "";
if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
    $target_dir = "../uploads/products/";
    $image_name = time() . '_' . basename($_FILES['image']['name']);
    $target_file = $target_dir . $image_name;
    move_uploaded_file($_FILES['image']['tmp_name'], $target_file);
    $image_update = ", image = '$image_name'";
}

$sql = "UPDATE products SET 
        category_id = ?,
        product_code = ?,
        product_name = ?,
        description = ?,
        price = ?,
        cost_price = ?,
        stock = ?,
        min_stock = ?,
        status = ?
        $image_update
        WHERE id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("isssddiiisi", 
    $_POST['category_id'],
    $_POST['product_code'],
    $_POST['product_name'],
    $_POST['description'],
    $_POST['price'],
    $_POST['cost_price'],
    $_POST['stock'],
    $_POST['min_stock'],
    $_POST['status'],
    $product_id
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Produk berhasil diupdate']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal update produk']);
}
?>
