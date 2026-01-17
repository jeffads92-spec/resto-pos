<?php
// ============================================
// PRODUCTS PAGE (FIXED)
// File: products.php
// Error Fixed: Unknown column 'name' → menggunakan 'product_name'
// ============================================

session_start();
require_once 'config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'add') {
            // Handle image upload
            $image_name = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['image']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowed)) {
                    $image_name = time() . '_' . rand(1000, 9999) . '.' . $ext;
                    $upload_path = 'uploads/products/' . $image_name;
                    
                    // Create folder if not exists
                    if (!file_exists('uploads/products/')) {
                        mkdir('uploads/products/', 0777, true);
                    }
                    
                    move_uploaded_file($_FILES['image']['tmp_name'], $upload_path);
                } else {
                    throw new Exception('Format file tidak didukung. Gunakan JPG, JPEG, PNG, atau GIF');
                }
            }
            
            // Add product
            $stmt = $conn->prepare("
                INSERT INTO products (
                    product_name, 
                    category_id, 
                    price, 
                    cost_price, 
                    stock, 
                    min_stock, 
                    image,
                    description, 
                    is_active
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $name = $_POST['product_name'];
            $category_id = $_POST['category_id'];
            $price = $_POST['price'];
            $cost_price = $_POST['cost_price'];
            $stock = $_POST['stock'];
            $min_stock = $_POST['min_stock'] ?? 5;
            $description = $_POST['description'] ?? '';
            $is_active = 1;
            
            $stmt->bind_param(
                "siddiiiss",
                $name,
                $category_id,
                $price,
                $cost_price,
                $stock,
                $min_stock,
                $image_name,
                $description,
                $is_active
            );
            
            $stmt->execute();
            $_SESSION['success'] = 'Produk berhasil ditambahkan';
            
        } elseif ($action === 'edit') {
            // Handle image upload for edit
            $image_name = $_POST['old_image'] ?? '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['image']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowed)) {
                    // Delete old image
                    if ($image_name && file_exists('uploads/products/' . $image_name)) {
                        unlink('uploads/products/' . $image_name);
                    }
                    
                    $image_name = time() . '_' . rand(1000, 9999) . '.' . $ext;
                    $upload_path = 'uploads/products/' . $image_name;
                    move_uploaded_file($_FILES['image']['tmp_name'], $upload_path);
                }
            }
            
            // Edit product
            $stmt = $conn->prepare("
                UPDATE products 
                SET product_name = ?, 
                    category_id = ?, 
                    price = ?, 
                    cost_price = ?, 
                    stock = ?, 
                    min_stock = ?, 
                    image = ?,
                    description = ?, 
                    is_active = ?
                WHERE id = ?
            ");
            
            $name = $_POST['product_name'];
            $category_id = $_POST['category_id'];
            $price = $_POST['price'];
            $cost_price = $_POST['cost_price'];
            $stock = $_POST['stock'];
            $min_stock = $_POST['min_stock'] ?? 5;
            $description = $_POST['description'] ?? '';
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $id = $_POST['id'];
            
            $stmt->bind_param(
                "siddiiissi",
                $name,
                $category_id,
                $price,
                $cost_price,
                $stock,
                $min_stock,
                $image_name,
                $description,
                $is_active,
                $id
            );
            
            $stmt->execute();
            $_SESSION['success'] = 'Produk berhasil diupdate';
            
        } elseif ($action === 'delete') {
            // Delete product (soft delete)
            $stmt = $conn->prepare("UPDATE products SET is_active = 0 WHERE id = ?");
            $id = $_POST['id'];
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $_SESSION['success'] = 'Produk berhasil dihapus';
        }
        
        header('Location: products.php');
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error: ' . $e->getMessage();
    }
}

// Get products (FIXED: menggunakan product_name, bukan name)
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';

$query = "
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE 1=1
";

if ($search) {
    $query .= " AND p.product_name LIKE '%" . $conn->real_escape_string($search) . "%'";
}

if ($category_filter) {
    $query .= " AND p.category_id = " . intval($category_filter);
}

$query .= " ORDER BY p.product_name ASC";
$result = $conn->query($query);

// Get categories
$categories = $conn->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY name");

include 'header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2><i class="fas fa-box"></i> Manajemen Produk</h2>
        </div>
        <div class="col-md-6 text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="fas fa-plus"></i> Tambah Produk
            </button>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Filter & Search -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Cari produk..." value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-4">
                    <select name="category" class="form-select">
                        <option value="">Semua Kategori</option>
                        <?php while ($cat = $categories->fetch_assoc()): ?>
                            <option value="<?= $cat['id'] ?>" 
                                    <?= $category_filter == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Products Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Produk</th>
                            <th>Kategori</th>
                            <th>Harga Jual</th>
                            <th>Harga Modal</th>
                            <th>Stok</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?= $row['id'] ?></td>
                                <td>
                                    <?php if ($row['image'] && file_exists('uploads/products/' . $row['image'])): ?>
                                        <img src="uploads/products/<?= htmlspecialchars($row['image']) ?>" 
                                             style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px; margin-right: 10px;">
                                    <?php endif; ?>
                                    <strong><?= htmlspecialchars($row['product_name']) ?></strong>
                                    <?php if ($row['stock'] <= $row['min_stock']): ?>
                                        <span class="badge bg-danger ms-2">Stok Menipis</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['category_name'] ?? '-') ?></td>
                                <td>Rp <?= number_format($row['price'], 0, ',', '.') ?></td>
                                <td>Rp <?= number_format($row['cost_price'], 0, ',', '.') ?></td>
                                <td>
                                    <span class="badge <?= $row['stock'] <= $row['min_stock'] ? 'bg-danger' : 'bg-success' ?>">
                                        <?= $row['stock'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['is_active']): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Nonaktif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-warning" 
                                            onclick="editProduct(<?= htmlspecialchars(json_encode($row)) ?>)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" 
                                            onclick="deleteProduct(<?= $row['id'] ?>, '<?= htmlspecialchars($row['product_name']) ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Add Product -->
<div class="modal fade" id="addProductModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Foto Produk</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <small class="text-muted">Format: JPG, JPEG, PNG, GIF (Max 5MB)</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Produk *</label>
                        <input type="text" name="product_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori *</label>
                        <select name="category_id" class="form-select" required>
                            <option value="">Pilih Kategori</option>
                            <?php 
                            $categories->data_seek(0);
                            while ($cat = $categories->fetch_assoc()): 
                            ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Harga Jual *</label>
                        <input type="number" name="price" class="form-control" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Harga Modal *</label>
                        <input type="number" name="cost_price" class="form-control" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stok Awal *</label>
                        <input type="number" name="stock" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stok Minimum</label>
                        <input type="number" name="min_stock" class="form-control" value="5">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Product -->
<div class="modal fade" id="editProductModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="editForm" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="old_image" id="edit_old_image">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Produk</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Foto Produk</label>
                        <div id="current_image_preview" class="mb-2"></div>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <small class="text-muted">Kosongkan jika tidak ingin mengubah foto</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Produk *</label>
                        <input type="text" name="product_name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori *</label>
                        <select name="category_id" id="edit_category" class="form-select" required>
                            <?php 
                            $categories->data_seek(0);
                            while ($cat = $categories->fetch_assoc()): 
                            ?>
                                <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Harga Jual *</label>
                        <input type="number" name="price" id="edit_price" class="form-control" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Harga Modal *</label>
                        <input type="number" name="cost_price" id="edit_cost" class="form-control" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stok</label>
                        <input type="number" name="stock" id="edit_stock" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stok Minimum</label>
                        <input type="number" name="min_stock" id="edit_min_stock" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="description" id="edit_desc" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_active" id="edit_active" class="form-check-input">
                            <label class="form-check-label">Aktif</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editProduct(data) {
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_name').value = data.product_name;
    document.getElementById('edit_category').value = data.category_id;
    document.getElementById('edit_price').value = data.price;
    document.getElementById('edit_cost').value = data.cost_price;
    document.getElementById('edit_stock').value = data.stock;
    document.getElementById('edit_min_stock').value = data.min_stock;
    document.getElementById('edit_desc').value = data.description || '';
    document.getElementById('edit_active').checked = data.is_active == 1;
    document.getElementById('edit_old_image').value = data.image || '';
    
    // Show current image preview
    const preview = document.getElementById('current_image_preview');
    if (data.image) {
        preview.innerHTML = `<img src="uploads/products/${data.image}" style="max-width: 200px; border-radius: 5px;">`;
    } else {
        preview.innerHTML = '<small class="text-muted">Belum ada foto</small>';
    }
    
    new bootstrap.Modal(document.getElementById('editProductModal')).show();
}

function deleteProduct(id, name) {
    if (confirm('Hapus produk "' + name + '"?')) {
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
</script>

<?php include 'footer.php'; ?>