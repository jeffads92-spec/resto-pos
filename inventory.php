<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Handle stock adjustment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'adjust') {
        $product_id = intval($_POST['product_id']);
        $quantity = intval($_POST['quantity']);
        $type = $_POST['type']; // 'in' atau 'out'
        $notes = $conn->real_escape_string($_POST['notes']);
        
        // Get current stock
        $current = $conn->query("SELECT stock FROM products WHERE id=$product_id")->fetch_assoc();
        $before_stock = $current['stock'];
        
        if ($type == 'in') {
            $new_stock = $before_stock + $quantity;
        } else {
            $new_stock = $before_stock - $quantity;
        }
        
        // Update stock
        $conn->query("UPDATE products SET stock=$new_stock WHERE id=$product_id");
        
        // Log history
        $user_id = $_SESSION['user_id'];
        $conn->query("INSERT INTO stock_history (product_id, type, quantity, before_stock, after_stock, notes, created_by) 
                      VALUES ($product_id, '$type', $quantity, $before_stock, $new_stock, '$notes', $user_id)");
        
        header('Location: inventory.php?msg=success');
        exit();
    }
}

// Get products with stock info
$products = $conn->query("SELECT p.*, c.name as category_name 
                          FROM products p 
                          LEFT JOIN categories c ON p.category_id = c.id 
                          ORDER BY p.name");

include 'header.php';
?>

<!-- COPY CSS DARI products.php -->

<div class="container-main">
    <div class="page-header">
        <h1>📦 Manajemen Inventaris</h1>
    </div>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Kategori</th>
                    <th>Stok</th>
                    <th>Stok Min</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while($p = $products->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($p['name']) ?></td>
                    <td><?= htmlspecialchars($p['category_name']) ?></td>
                    <td><strong><?= $p['stock'] ?></strong></td>
                    <td><?= $p['stock_min'] ?></td>
                    <td>
                        <?php if($p['stock'] <= $p['stock_min']): ?>
                            <span class="badge badge-danger">⚠️ Menipis</span>
                        <?php else: ?>
                            <span class="badge badge-success">✅ Aman</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="adjustStock(<?= $p['id'] ?>, '<?= htmlspecialchars($p['name']) ?>')">
                            Adjust Stok
                        </button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Adjust Stock -->
<div id="adjustModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Adjust Stok</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="adjust">
                <input type="hidden" name="product_id" id="productId">
                
                <div class="form-group">
                    <label>Produk</label>
                    <input type="text" id="productName" class="form-control" readonly>
                </div>
                
                <div class="form-group">
                    <label>Tipe</label>
                    <select name="type" class="form-control" required>
                        <option value="in">Stok Masuk (+)</option>
                        <option value="out">Stok Keluar (-)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Jumlah</label>
                    <input type="number" name="quantity" class="form-control" min="1" required>
                </div>
                
                <div class="form-group">
                    <label>Catatan</label>
                    <textarea name="notes" class="form-control" rows="3"></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Simpan</button>
            </form>
        </div>
    </div>
</div>

<script>
function adjustStock(id, name) {
    document.getElementById('adjustModal').style.display = 'block';
    document.getElementById('productId').value = id;
    document.getElementById('productName').value = name;
}

function closeModal() {
    document.getElementById('adjustModal').style.display = 'none';
}
</script>

<?php include 'footer.php'; ?>
