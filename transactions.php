<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_role = $_SESSION['user_role'];
$is_admin = ($user_role === 'admin');

// Get transactions with filters
$where = "WHERE 1=1";
if (isset($_GET['date'])) {
    $date = $_GET['date'];
    $where .= " AND DATE(t.created_at) = '$date'";
}
if (isset($_GET['payment_method']) && $_GET['payment_method'] != '') {
    $method = $_GET['payment_method'];
    $where .= " AND t.payment_method = '$method'";
}
if (isset($_GET['cashier']) && $_GET['cashier'] != '') {
    $cashier_id = $_GET['cashier'];
    $where .= " AND t.user_id = '$cashier_id'";
}

$query = "SELECT t.*, u.username as cashier_name, m.member_name, m.phone as member_phone
          FROM transactions t
          LEFT JOIN users u ON t.user_id = u.id
          LEFT JOIN members m ON t.member_id = m.id
          $where
          ORDER BY t.created_at DESC
          LIMIT 100";
$result = mysqli_query($conn, $query);

// Get cashiers for filter
$cashiers_query = "SELECT id, username FROM users WHERE status = 'active'";
$cashiers_result = mysqli_query($conn, $cashiers_query);

include 'header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-receipt"></i> Riwayat Transaksi</h2>
        </div>
    </div>

    <!-- Filter -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Tanggal</label>
                    <input type="date" class="form-control" name="date" value="<?php echo isset($_GET['date']) ? $_GET['date'] : ''; ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Metode Pembayaran</label>
                    <select class="form-select" name="payment_method">
                        <option value="">-- Semua --</option>
                        <option value="cash" <?php echo (isset($_GET['payment_method']) && $_GET['payment_method'] == 'cash') ? 'selected' : ''; ?>>Tunai</option>
                        <option value="qris" <?php echo (isset($_GET['payment_method']) && $_GET['payment_method'] == 'qris') ? 'selected' : ''; ?>>QRIS</option>
                        <option value="transfer" <?php echo (isset($_GET['payment_method']) && $_GET['payment_method'] == 'transfer') ? 'selected' : ''; ?>>Transfer</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Kasir</label>
                    <select class="form-select" name="cashier">
                        <option value="">-- Semua --</option>
                        <?php while ($cashier = mysqli_fetch_assoc($cashiers_result)): ?>
                        <option value="<?php echo $cashier['id']; ?>" 
                                <?php echo (isset($_GET['cashier']) && $_GET['cashier'] == $cashier['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cashier['username']); ?>
                        </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-filter"></i> Filter
                        </button>
                        <a href="transactions.php" class="btn btn-secondary">
                            <i class="fas fa-redo"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="transactionsTable">
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Tanggal</th>
                            <th>Kasir</th>
                            <th>Member</th>
                            <th>Items</th>
                            <th>Total</th>
                            <th>Pembayaran</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($trx = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td>
                                <strong><?php echo $trx['invoice_number']; ?></strong>
                            </td>
                            <td><?php echo date('d/m/Y H:i', strtotime($trx['created_at'])); ?></td>
                            <td><?php echo htmlspecialchars($trx['cashier_name']); ?></td>
                            <td>
                                <?php if ($trx['member_id']): ?>
                                <span class="badge bg-info">
                                    <?php echo htmlspecialchars($trx['member_name']); ?>
                                </span>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-link" onclick="viewItems(<?php echo $trx['id']; ?>)">
                                    <i class="fas fa-list"></i> Lihat Items
                                </button>
                            </td>
                            <td>
                                <strong>Rp <?php echo number_format($trx['total_amount'], 0, ',', '.'); ?></strong>
                            </td>
                            <td>
                                <?php
                                $payment_badges = [
                                    'cash' => '<span class="badge bg-success">Tunai</span>',
                                    'qris' => '<span class="badge bg-primary">QRIS</span>',
                                    'transfer' => '<span class="badge bg-info">Transfer</span>'
                                ];
                                echo $payment_badges[$trx['payment_method']];
                                ?>
                            </td>
                            <td>
                                <?php if ($trx['status'] == 'completed'): ?>
                                <span class="badge bg-success">Selesai</span>
                                <?php else: ?>
                                <span class="badge bg-warning">Pending</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="viewDetail(<?php echo $trx['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <a href="print_receipt.php?id=<?php echo $trx['id']; ?>" target="_blank" class="btn btn-sm btn-secondary">
                                    <i class="fas fa-print"></i>
                                </a>
                                <?php if ($is_admin): ?>
                                <button class="btn btn-sm btn-danger" onclick="deleteTransaction(<?php echo $trx['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Transaksi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailContent">
                <div class="text-center py-5">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Items Modal -->
<div class="modal fade" id="itemsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Items Transaksi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="itemsContent">
                <div class="text-center py-5">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function viewDetail(id) {
    fetch('api/get_transaction_detail.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const trx = data.transaction;
                let html = `
                    <div class="row mb-3">
                        <div class="col-6">
                            <strong>Invoice:</strong> ${trx.invoice_number}
                        </div>
                        <div class="col-6 text-end">
                            <strong>Tanggal:</strong> ${trx.created_at}
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6">
                            <strong>Kasir:</strong> ${trx.cashier_name}
                        </div>
                        <div class="col-6 text-end">
                            <strong>Member:</strong> ${trx.member_name || '-'}
                        </div>
                    </div>
                    <hr>
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Produk</th>
                                <th>Qty</th>
                                <th>Harga</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                data.items.forEach(item => {
                    html += `
                        <tr>
                            <td>${item.product_name}</td>
                            <td>${item.quantity}</td>
                            <td>Rp ${parseInt(item.price).toLocaleString('id-ID')}</td>
                            <td>Rp ${parseInt(item.subtotal).toLocaleString('id-ID')}</td>
                        </tr>
                    `;
                });
                
                html += `
                        </tbody>
                    </table>
                    <hr>
                    <div class="row">
                        <div class="col-6">Subtotal</div>
                        <div class="col-6 text-end">Rp ${parseInt(trx.subtotal).toLocaleString('id-ID')}</div>
                    </div>
                    <div class="row">
                        <div class="col-6">Diskon Member</div>
                        <div class="col-6 text-end">- Rp ${parseInt(trx.discount_amount || 0).toLocaleString('id-ID')}</div>
                    </div>
                    <div class="row">
                        <div class="col-6">Pajak (${trx.tax_rate}%)</div>
                        <div class="col-6 text-end">Rp ${parseInt(trx.tax_amount).toLocaleString('id-ID')}</div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-6"><strong>TOTAL</strong></div>
                        <div class="col-6 text-end"><strong>Rp ${parseInt(trx.total_amount).toLocaleString('id-ID')}</strong></div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-6">Pembayaran</div>
                        <div class="col-6 text-end">${trx.payment_method.toUpperCase()}</div>
                    </div>
                    <div class="row">
                        <div class="col-6">Bayar</div>
                        <div class="col-6 text-end">Rp ${parseInt(trx.amount_paid).toLocaleString('id-ID')}</div>
                    </div>
                    <div class="row">
                        <div class="col-6">Kembalian</div>
                        <div class="col-6 text-end">Rp ${parseInt(trx.change_amount).toLocaleString('id-ID')}</div>
                    </div>
                `;
                
                document.getElementById('detailContent').innerHTML = html;
                new bootstrap.Modal(document.getElementById('detailModal')).show();
            }
        });
}

function viewItems(id) {
    fetch('api/get_transaction_items.php?id=' + id)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                let html = '<table class="table table-sm"><thead><tr><th>Produk</th><th>Qty</th><th>Harga</th></tr></thead><tbody>';
                data.items.forEach(item => {
                    html += `<tr>
                        <td>${item.product_name}</td>
                        <td>${item.quantity}</td>
                        <td>Rp ${parseInt(item.price).toLocaleString('id-ID')}</td>
                    </tr>`;
                });
                html += '</tbody></table>';
                
                document.getElementById('itemsContent').innerHTML = html;
                new bootstrap.Modal(document.getElementById('itemsModal')).show();
            }
        });
}

function deleteTransaction(id) {
    if (!confirm('Yakin ingin menghapus transaksi ini? Stok akan dikembalikan.')) return;
    
    fetch('api/delete_transaction.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id: id})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Transaksi berhasil dihapus');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    });
}

$(document).ready(function() {
    $('#transactionsTable').DataTable({
        order: [[1, 'desc']],
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/id.json'
        }
    });
});
</script>

<?php include 'footer.php'; ?>
