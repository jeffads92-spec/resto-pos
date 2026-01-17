<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Handle Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add' || $_POST['action'] == 'edit') {
            $name = $conn->real_escape_string($_POST['name']);
            $category_id = intval($_POST['category_id']);
            $price = floatval($_POST['price']);
            $cost = floatval($_POST['cost']);
            $stock = intval($_POST['stock']);
            $stock_min = intval($_POST['stock_min']);
            $description = $conn->real_escape_string($_POST['description']);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            // Handle image upload
            $image_name = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['image']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowed)) {
                    $image_name = uniqid() . '.' . $ext;
                    $upload_path = 'uploads/products/' . $image_name;
                    
                    // Create directory if not exists
                    if (!file_exists('uploads/products')) {
                        mkdir('uploads/products', 0777, true);
                    }
                    
                    move_uploaded_file($_FILES['image']['tmp_name'], $upload_path);
                }
            }
            
            if ($_POST['action'] == 'add') {
                $sql = "INSERT INTO products (name, category_id, price, cost, stock, stock_min, description, image, is_active) 
                        VALUES ('$name', $category_id, $price, $cost, $stock, $stock_min, '$description', '$image_name', $is_active)";
            } else {
                $id = intval($_POST['id']);
                if ($image_name) {
                    $sql = "UPDATE products SET name='$name', category_id=$category_id, price=$price, cost=$cost, 
                            stock=$stock, stock_min=$stock_min, description='$description', image='$image_name', is_active=$is_active 
                            WHERE id=$id";
                } else {
                    $sql = "UPDATE products SET name='$name', category_id=$category_id, price=$price, cost=$cost, 
                            stock=$stock, stock_min=$stock_min, description='$description', is_active=$is_active 
                            WHERE id=$id";
                }
            }
            
            $conn->query($sql);
            header('Location: products.php?msg=success');
            exit();
        }
        
        if ($_POST['action'] == 'delete') {
            $id = intval($_POST['id']);
            $conn->query("DELETE FROM products WHERE id=$id");
            header('Location: products.php?msg=deleted');
            exit();
        }
    }
}

// Get categories
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

// Get products with category
$products = $conn->query("SELECT p.*, c.name as category_name FROM products p 
                          LEFT JOIN categories c ON p.category_id = c.id 
                          ORDER BY p.id DESC");

include 'header.php';
?>

<style>
body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
}

.products-container {
    padding: 2rem;
    max-width: 1400px;
    margin: 0 auto;
}

.page-header {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.page-title {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
}

.btn-add {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 0.75rem 2rem;
    border-radius: 50px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.btn-add:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.6);
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
}

.product-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
    border: 1px solid rgba(255,255,255,0.3);
}

.product-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.product-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
}

.product-body {
    padding: 1.5rem;
}

.product-category {
    display: inline-block;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 0.25rem 1rem;
    border-radius: 50px;
    font-size: 0.75rem;
    font-weight: 600;
    margin-bottom: 0.75rem;
}

.product-name {
    font-size: 1.25rem;
    font-weight: 700;
    color: #2d3748;
    margin-bottom: 0.5rem;
}

.product-description {
    color: #718096;
    font-size: 0.9rem;
    margin-bottom: 1rem;
    line-height: 1.5;
}

.product-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.75rem;
    margin-bottom: 1rem;
    padding-top: 1rem;
    border-top: 2px solid #e2e8f0;
}

.info-item {
    text-align: center;
}

.info-label {
    font-size: 0.75rem;
    color: #718096;
    margin-bottom: 0.25rem;
}

.info-value {
    font-size: 1.1rem;
    font-weight: 700;
    color: #667eea;
}

.product-stock {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: white;
    padding: 0.5rem;
    border-radius: 10px;
    text-align: center;
    font-weight: 600;
    margin-bottom: 1rem;
}

.product-stock.low {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
}

.product-actions {
    display: flex;
    gap: 0.5rem;
}

.btn {
    flex: 1;
    padding: 0.65rem;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-edit {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
}

.btn-delete {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    color: white;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
    backdrop-filter: blur(5px);
}

.modal-content {
    background: white;
    margin: 2% auto;
    padding: 0;
    border-radius: 20px;
    max-width: 600px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.5);
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        transform: translateY(-50px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

.modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem 2rem;
    border-radius: 20px 20px 0 0;
}

.modal-header h2 {
    margin: 0;
    font-size: 1.5rem;
}

.close {
    float: right;
    font-size: 2rem;
    font-weight: bold;
    cursor: pointer;
    line-height: 1;
}

.modal-body {
    padding: 2rem;
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #2d3748;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.image-preview {
    width: 150px;
    height: 150px;
    border: 3px dashed #e2e8f0;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-top: 1rem;
    overflow: hidden;
}

.image-preview img {
    max-width: 100%;
    max-height: 100%;
    object-fit: cover;
}

@media (max-width: 768px) {
    .products-grid {
        grid-template-columns: 1fr;
    }
    
    .page-header {
        flex-direction: column;
        gap: 1rem;
    }
}
</style>

<div class="products-container">
    <div class="page-header">
        <h1 class="page-title">🍽️ Manajemen Produk</h1>
        <button class="btn-add" onclick="openModal()">
            ➕ Tambah Produk Baru
        </button>
    </div>

    <div class="products-grid">
        <?php while($product = $products->fetch_assoc()): ?>
        <div class="product-card">
            <?php 
            // Fix: pastikan path gambar benar
            $image_path = !empty($product['image']) ? 'uploads/products/' . $product['image'] : 'assets/images/no-image.png';
            
            // Debug: cek apakah file ada
            if (!file_exists($image_path) && !empty($product['image'])) {
                $image_path = 'assets/images/no-image.png';
            }
            ?>
            <img src="<?= $image_path ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-image" 
                 onerror="this.src='assets/images/no-image.png'">
            
            <div class="product-body">
                <span class="product-category"><?= htmlspecialchars($product['category_name']) ?></span>
                <h3 class="product-name"><?= htmlspecialchars($product['name']) ?></h3>
                <p class="product-description"><?= htmlspecialchars(substr($product['description'], 0, 80)) ?>...</p>
                
                <div class="product-info">
                    <div class="info-item">
                        <div class="info-label">Harga Jual</div>
                        <div class="info-value">Rp <?= number_format($product['price'], 0, ',', '.') ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">Modal</div>
                        <div class="info-value">Rp <?= number_format($product['cost'], 0, ',', '.') ?></div>
                    </div>
                </div>
                
                <div class="product-stock <?= $product['stock'] <= $product['stock_min'] ? 'low' : '' ?>">
                    📦 Stok: <?= $product['stock'] ?> <?= $product['stock'] <= $product['stock_min'] ? '⚠️ Menipis!' : '' ?>
                </div>
                
                <div class="product-actions">
                    <button class="btn btn-edit" onclick='editProduct(<?= json_encode($product) ?>)'>
                        ✏️ Edit
                    </button>
                    <button class="btn btn-delete" onclick="deleteProduct(<?= $product['id'] ?>)">
                        🗑️ Hapus
                    </button>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- Modal -->
<div id="productModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modalTitle">Tambah Produk Baru</h2>
        </div>
        <div class="modal-body">
            <form method="POST" enctype="multipart/form-data" id="productForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="productId">
                
                <div class="form-group">
                    <label>Nama Produk *</label>
                    <input type="text" name="name" id="productName" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Kategori *</label>
                    <select name="category_id" id="categoryId" class="form-control" required>
                        <option value="">Pilih Kategori</option>
                        <?php 
                        $categories->data_seek(0);
                        while($cat = $categories->fetch_assoc()): 
                        ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="form-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label>Harga Jual *</label>
                        <input type="number" name="price" id="productPrice" class="form-control" required>
                    </div>
                    <div>
                        <label>Harga Modal *</label>
                        <input type="number" name="cost" id="productCost" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label>Stok</label>
                        <input type="number" name="stock" id="productStock" class="form-control" value="0">
                    </div>
                    <div>
                        <label>Stok Minimum</label>
                        <input type="number" name="stock_min" id="productStockMin" class="form-control" value="5">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="description" id="productDescription" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Gambar Produk</label>
                    <input type="file" name="image" id="productImage" class="form-control" accept="image/*" onchange="previewImage(event)">
                    <div class="image-preview" id="imagePreview">
                        <span style="color: #cbd5e0;">Preview</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" name="is_active" id="isActive" checked>
                        <span>Produk Aktif</span>
                    </label>
                </div>
                
                <button type="submit" class="btn-add" style="width: 100%;">
                    💾 Simpan Produk
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('productModal').style.display = 'block';
    document.getElementById('productForm').reset();
    document.getElementById('formAction').value = 'add';
    document.getElementById('modalTitle').textContent = 'Tambah Produk Baru';
    document.getElementById('imagePreview').innerHTML = '<span style="color: #cbd5e0;">Preview</span>';
}

function closeModal() {
    document.getElementById('productModal').style.display = 'none';
}

function editProduct(product) {
    document.getElementById('productModal').style.display = 'block';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('modalTitle').textContent = 'Edit Produk';
    document.getElementById('productId').value = product.id;
    document.getElementById('productName').value = product.name;
    document.getElementById('categoryId').value = product.category_id;
    document.getElementById('productPrice').value = product.price;
    document.getElementById('productCost').value = product.cost;
    document.getElementById('productStock').value = product.stock;
    document.getElementById('productStockMin').value = product.stock_min;
    document.getElementById('productDescription').value = product.description;
    document.getElementById('isActive').checked = product.is_active == 1;
    
    if (product.image) {
        document.getElementById('imagePreview').innerHTML = 
            `<img src="uploads/products/${product.image}" alt="Preview">`;
    }
}

function deleteProduct(id) {
    if (confirm('Yakin ingin menghapus produk ini?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function previewImage(event) {
    const file = event.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('imagePreview').innerHTML = 
                `<img src="${e.target.result}" alt="Preview">`;
        }
        reader.readAsDataURL(file);
    }
}

window.onclick = function(event) {
    const modal = document.getElementById('productModal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>

<?php include 'footer.php'; ?>
