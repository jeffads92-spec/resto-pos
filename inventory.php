<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_role = $_SESSION['user_role'];
$is_admin = ($user_role === 'admin');

// Get products with stock info
$query = "SELECT p.*, c.category_name, 
          CASE WHEN p.stock <= p.min_stock THEN 'low' ELSE 'normal' END as stock_status
          FROM products p
          LEFT JOIN categories c ON p.category_id = c.id
          ORDER BY p.stock ASC, p.product_name ASC";
$result = mysqli_query($conn, $query);

// Get stock history
$history_query = "SELECT sh.*, p.product_name, u.username
                  FROM stock_history sh
                  LEFT JOIN products p ON sh.product_id = p.id
                  LEFT JOIN users u ON sh.user_id = u.id
                  ORDER BY sh.created_at DESC
                  LIMIT 50";
$history_result = mysqli_query($conn, $history_query);

include 'header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-boxes"></i> Inventaris & Stok</h2>
                <?php if ($is_admin): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#stockModal">
                    <i class="fas fa-plus"></i> Adjustment Stok
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Stock Alert -->
    <?php
    $low_stock_query = "SELECT COUNT(*) as count FROM products WHERE stock <= min_stock AND status = 'active'";
    $low_stock_result = mysqli_query($conn, $low_stock_query);
    $low_stock_count = mysqli_fetch_assoc($low_stock_result)['count'];
    if ($low_stock_count > 0):
    ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle"></i> 
        <strong>Peringatan!</strong> Ada <?php echo $low_stock_count; ?> produk dengan stok menipis.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Products Table -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-warehouse"></i> Daftar Produk & Stok</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="productsTable">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Produk</th>
                            <th>Kategori</th>
                            <th>Stok Saat Ini</th>
                            <th>Stok Minimum</th>
                            <th>Status</th>
                            <th>Harga Modal</th>
                            <th>Harga Jual</th>
                            <?php if ($is_admin): ?>
                            <th>Aksi</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        while ($row = mysqli_fetch_assoc($result)): 
                        ?>
                        <tr class="<?php echo $row['stock_status'] == 'low' ? 'table-warning' : ''; ?>">
                            <td><?php echo $no++; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($row['product_name']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $row['stock_status'] == 'low' ? 'warning' : 'success'; ?>">
                                    <?php echo $row['stock']; ?> unit
                                </span>
                            </td>
                            <td><?php echo $row['min_stock']; ?> unit</td>
                            <td>
                                <?php if ($row['stock_status'] == 'low'): ?>
                                <span class="badge bg-danger"><i class="fas fa-exclamation-circle"></i> Stok Menipis</span>
                                <?php else: ?>
                                <span class="badge bg-success"><i class="fas fa-check-circle"></i> Normal</span>
                                <?php endif; ?>
                            </td>
                            <td>Rp <?php echo number_format($row['cost_price'], 0, ',', '.'); ?></td>
                            <td>Rp <?php echo number_format($row['price'], 0, ',', '.'); ?></td>
                            <?php if ($is_admin): ?>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="adjustStock(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars($row['product_name']); ?>', <?php echo $row['stock']; ?>)">
                                    <i class="fas fa-edit"></i> Adjust
                                </button>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Stock History -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-history"></i> Riwayat Pergerakan Stok</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm" id="historyTable">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Produk</th>
                            <th>Tipe</th>
                            <th>Jumlah</th>
                            <th>Stok Sebelum</th>
                            <th>Stok Sesudah</th>
                            <th>Catatan</th>
                            <th>User</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($history = mysqli_fetch_assoc($history_result)): ?>
                        <tr>
                            <td><?php echo date('d/m/Y H:i', strtotime($history['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($history['product_name']); ?></td>
                            <td>
                                <?php if ($history['type'] == 'in'): ?>
                                <span class="badge bg-success"><i class="fas fa-arrow-up"></i> Masuk</span>
                                <?php else: ?>
                                <span class="badge bg-danger"><i class="fas fa-arrow-down"></i> Keluar</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $history['quantity']; ?></td>
                            <td><?php echo $history['stock_before']; ?></td>
                            <td><?php echo $history['stock_after']; ?></td>
                            <td><?php echo htmlspecialchars($history['notes']); ?></td>
                            <td><?php echo htmlspecialchars($history['username']); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Stock Adjustment Modal -->
<div class="modal fade" id="stockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adjustment Stok</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="stockForm">
                <div class="modal-body">
                    <input type="hidden" id="product_id" name="product_id">
                    <div class="mb-3">
                        <label class="form-label">Produk</label>
                        <input type="text" id="product_name" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stok Saat Ini</label>
                        <input type="text" id="current_stock" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tipe Adjustment</label>
                        <select class="form-select" id="adjustment_type" name="type" required>
                            <option value="">-- Pilih --</option>
                            <option value="in">Stok Masuk (+)</option>
                            <option value="out">Stok Keluar (-)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jumlah</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3" required></textarea>
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

<script>
function adjustStock(id, name, stock) {
    document.getElementById('product_id').value = id;
    document.getElementById('product_name').value = name;
    document.getElementById('current_stock').value = stock + ' unit';
    document.getElementById('quantity').value = '';
    document.getElementById('notes').value = '';
    document.getElementById('adjustment_type').value = '';
    
    var modal = new bootstrap.Modal(document.getElementById('stockModal'));
    modal.show();
}

document.getElementById('stockForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('api/adjust_stock.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Stok berhasil di-update!');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat menyimpan data');
    });
});

// DataTables initialization
$(document).ready(function() {
    $('#productsTable').DataTable({
        order: [[3, 'asc']],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json'
        }
    });
    
    $('#historyTable').DataTable({
        order: [[0, 'desc']],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json'
        }
    });
});
</script>

<?php include 'footer.php'; ?>
