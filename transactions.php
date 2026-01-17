<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Pagination
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filter
$search = isset($_GET['search']) ? $conn->real_escape_string($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

// Build query
$where = "DATE(t.created_at) BETWEEN '$date_from' AND '$date_to'";
if ($search) {
    $where .= " AND (t.invoice_number LIKE '%$search%' OR u.username LIKE '%$search%' OR m.name LIKE '%$search%')";
}

// Get transactions - FIX: Add error handling
try {
    $query = "SELECT t.*, u.username, m.name as member_name 
              FROM transactions t
              LEFT JOIN users u ON t.user_id = u.id
              LEFT JOIN members m ON t.member_id = m.id
              WHERE $where
              ORDER BY t.created_at DESC
              LIMIT $limit OFFSET $offset";
    $transactions = $conn->query($query);
    
    if (!$transactions) {
        throw new Exception($conn->error);
    }
} catch (Exception $e) {
    die("Database Error: " . $e->getMessage());
}

// Count total
$count_query = "SELECT COUNT(*) as total FROM transactions t 
                LEFT JOIN users u ON t.user_id = u.id
                LEFT JOIN members m ON t.member_id = m.id
                WHERE $where";
$total_records = $conn->query($count_query)->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

include 'header.php';
?>

<style>
body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
}

.transactions-container {
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
}

.page-title {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    font-size: 2rem;
    font-weight: 700;
    margin: 0 0 1rem 0;
}

.filter-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}

.filter-form {
    display: grid;
    grid-template-columns: 2fr 1fr 1fr auto;
    gap: 1rem;
    align-items: end;
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

.btn-filter {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 0.75rem 2rem;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-filter:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.transactions-table {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}

table {
    width: 100%;
    border-collapse: collapse;
}

thead {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

th {
    color: white;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
}

tbody tr {
    border-bottom: 1px solid #e2e8f0;
    transition: all 0.3s ease;
}

tbody tr:hover {
    background: #f7fafc;
}

td {
    padding: 1rem;
}

.badge {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 50px;
    font-size: 0.85rem;
    font-weight: 600;
}

.badge-success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: white;
}

.badge-warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
}

.badge-danger {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    color: white;
}

.btn-detail {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-detail:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(79, 172, 254, 0.4);
}

.pagination {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 2rem;
}

.pagination a {
    padding: 0.5rem 1rem;
    background: white;
    border-radius: 10px;
    text-decoration: none;
    color: #667eea;
    font-weight: 600;
    transition: all 0.3s ease;
}

.pagination a:hover,
.pagination a.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    transform: translateY(-2px);
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
    margin: 5% auto;
    padding: 0;
    border-radius: 20px;
    max-width: 600px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.5);
}

.modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem 2rem;
    border-radius: 20px 20px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-body {
    padding: 2rem;
}

.close {
    font-size: 2rem;
    font-weight: bold;
    cursor: pointer;
    color: white;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    padding: 0.75rem 0;
    border-bottom: 1px solid #e2e8f0;
}

.detail-label {
    font-weight: 600;
    color: #718096;
}

.detail-value {
    color: #2d3748;
    font-weight: 600;
}

.items-table {
    margin-top: 1.5rem;
}

.items-table table {
    background: #f7fafc;
    border-radius: 10px;
}

.items-table th {
    background: #e2e8f0;
    color: #2d3748;
    padding: 0.75rem;
}

.items-table td {
    padding: 0.75rem;
}

@media (max-width: 768px) {
    .filter-form {
        grid-template-columns: 1fr;
    }
    
    table {
        font-size: 0.85rem;
    }
    
    th, td {
        padding: 0.5rem;
    }
}
</style>

<div class="transactions-container">
    <div class="page-header">
        <h1 class="page-title">📝 Riwayat Transaksi</h1>
        <p style="color: #718096;">Semua transaksi penjualan</p>
    </div>

    <div class="filter-card">
        <form class="filter-form" method="GET">
            <div class="form-group">
                <label>Cari Invoice / Kasir / Member</label>
                <input type="text" name="search" class="form-control" placeholder="Cari..." value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="form-group">
                <label>Dari Tanggal</label>
                <input type="date" name="date_from" class="form-control" value="<?= $date_from ?>">
            </div>
            <div class="form-group">
                <label>Sampai Tanggal</label>
                <input type="date" name="date_to" class="form-control" value="<?= $date_to ?>">
            </div>
            <button type="submit" class="btn-filter">🔍 Filter</button>
        </form>
    </div>

    <div class="transactions-table">
        <table>
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Tanggal</th>
                    <th>Kasir</th>
                    <th>Member</th>
                    <th>Total</th>
                    <th>Pembayaran</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while($row = $transactions->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= $row['invoice_number'] ?></strong></td>
                    <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                    <td><?= htmlspecialchars($row['username']) ?></td>
                    <td><?= $row['member_name'] ? htmlspecialchars($row['member_name']) : '-' ?></td>
                    <td><strong>Rp <?= number_format($row['total'], 0, ',', '.') ?></strong></td>
                    <td><?= strtoupper($row['payment_method']) ?></td>
                    <td>
                        <?php if($row['status'] == 'completed'): ?>
                            <span class="badge badge-success">Selesai</span>
                        <?php elseif($row['status'] == 'pending'): ?>
                            <span class="badge badge-warning">Pending</span>
                        <?php else: ?>
                            <span class="badge badge-danger">Dibatalkan</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="btn-detail" onclick="showDetail(<?= $row['id'] ?>)">Detail</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <?php if($total_pages > 1): ?>
    <div class="pagination">
        <?php for($i = 1; $i <= $total_pages; $i++): ?>
            <a href="?page=<?= $i ?>&search=<?= $search ?>&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>" 
               class="<?= $page == $i ? 'active' : '' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Detail -->
<div id="detailModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 style="margin: 0;">Detail Transaksi</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body" id="modalBody">
            Loading...
        </div>
    </div>
</div>

<script>
function showDetail(id) {
    document.getElementById('detailModal').style.display = 'block';
    
    fetch('api/get_transaction_detail.php?id=' + id)
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                const t = data.transaction;
                const items = data.items;
                
                let html = `
                    <div class="detail-row">
                        <span class="detail-label">Invoice:</span>
                        <span class="detail-value">${t.invoice_number}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Tanggal:</span>
                        <span class="detail-value">${t.created_at}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Kasir:</span>
                        <span class="detail-value">${t.username}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Member:</span>
                        <span class="detail-value">${t.member_name || '-'}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Subtotal:</span>
                        <span class="detail-value">Rp ${parseInt(t.subtotal).toLocaleString('id-ID')}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Diskon:</span>
                        <span class="detail-value">Rp ${parseInt(t.discount).toLocaleString('id-ID')}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Pajak:</span>
                        <span class="detail-value">Rp ${parseInt(t.tax).toLocaleString('id-ID')}</span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Total:</span>
                        <span class="detail-value"><strong>Rp ${parseInt(t.total).toLocaleString('id-ID')}</strong></span>
                    </div>
                    
                    <div class="items-table">
                        <h4>Item Pesanan:</h4>
                        <table>
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
                
                items.forEach(item => {
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
                    </div>
                `;
                
                document.getElementById('modalBody').innerHTML = html;
            }
        })
        .catch(err => {
            document.getElementById('modalBody').innerHTML = 'Error: ' + err.message;
        });
}

function closeModal() {
    document.getElementById('detailModal').style.display = 'none';
}

window.onclick = function(event) {
    const modal = document.getElementById('detailModal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>

<?php include 'footer.php'; ?>
