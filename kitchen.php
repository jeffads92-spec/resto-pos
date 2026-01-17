<?php
// ============================================
// KITCHEN DISPLAY SYSTEM (FIXED)
// File: kitchen.php
// Error Fixed: Table 'transaction_details' → menggunakan 'transaction_items'
// ============================================

session_start();
require_once 'config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get filter status
$status_filter = $_GET['status'] ?? 'all';

// Query orders (FIXED: menggunakan transaction_items, bukan transaction_details)
$query = "
    SELECT 
        ti.id,
        ti.transaction_id,
        ti.product_name,
        ti.quantity,
        ti.kitchen_status,
        ti.notes,
        ti.created_at,
        t.transaction_code,
        t.transaction_date,
        u.full_name as cashier_name
    FROM transaction_items ti
    JOIN transactions t ON ti.transaction_id = t.id
    LEFT JOIN users u ON t.cashier_id = u.id
    WHERE t.status = 'completed'
";

if ($status_filter !== 'all') {
    $query .= " AND ti.kitchen_status = '" . $conn->real_escape_string($status_filter) . "'";
}

$query .= " ORDER BY ti.created_at ASC, ti.id ASC";

$result = $conn->query($query);

include 'header.php';
?>

<style>
.kitchen-display {
    background: #f8f9fa;
    min-height: 100vh;
}

.order-card {
    transition: all 0.3s;
    border-left: 5px solid;
}

.order-card.pending {
    border-left-color: #ffc107;
    background: #fff9e6;
}

.order-card.preparing {
    border-left-color: #17a2b8;
    background: #e6f7ff;
}

.order-card.ready {
    border-left-color: #28a745;
    background: #e6ffe6;
}

.order-card.served {
    border-left-color: #6c757d;
    background: #f0f0f0;
}

.badge-status {
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
}

.order-time {
    font-size: 0.85rem;
    color: #6c757d;
}

.auto-refresh-info {
    position: fixed;
    bottom: 20px;
    right: 20px;
    background: rgba(0,0,0,0.7);
    color: white;
    padding: 10px 20px;
    border-radius: 5px;
    z-index: 1000;
}
</style>

<div class="kitchen-display">
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h2><i class="fas fa-utensils"></i> Kitchen Display System</h2>
                <p class="text-muted mb-0">
                    <i class="fas fa-clock"></i> 
                    <span id="currentTime"></span>
                </p>
            </div>
            <div class="col-md-6 text-end">
                <div class="btn-group">
                    <a href="?status=all" class="btn btn-<?= $status_filter === 'all' ? 'primary' : 'outline-primary' ?>">
                        Semua
                    </a>
                    <a href="?status=pending" class="btn btn-<?= $status_filter === 'pending' ? 'warning' : 'outline-warning' ?>">
                        Pending
                    </a>
                    <a href="?status=preparing" class="btn btn-<?= $status_filter === 'preparing' ? 'info' : 'outline-info' ?>">
                        Preparing
                    </a>
                    <a href="?status=ready" class="btn btn-<?= $status_filter === 'ready' ? 'success' : 'outline-success' ?>">
                        Ready
                    </a>
                    <a href="?status=served" class="btn btn-<?= $status_filter === 'served' ? 'secondary' : 'outline-secondary' ?>">
                        Served
                    </a>
                </div>
            </div>
        </div>

        <!-- Orders Grid -->
        <div class="row" id="ordersContainer">
            <?php if ($result->num_rows == 0): ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle fa-3x mb-3"></i>
                        <h5>Tidak ada pesanan</h5>
                        <p class="mb-0">Pesanan akan muncul di sini secara otomatis</p>
                    </div>
                </div>
            <?php else: ?>
                <?php while ($order = $result->fetch_assoc()): ?>
                    <div class="col-md-4 col-lg-3 mb-4">
                        <div class="card order-card <?= $order['kitchen_status'] ?>" 
                             data-order-id="<?= $order['id'] ?>">
                            <div class="card-header">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong><?= htmlspecialchars($order['transaction_code']) ?></strong>
                                    <span class="badge bg-<?= getStatusColor($order['kitchen_status']) ?> badge-status">
                                        <?= ucfirst($order['kitchen_status']) ?>
                                    </span>
                                </div>
                                <div class="order-time mt-1">
                                    <i class="fas fa-clock"></i>
                                    <?= date('H:i', strtotime($order['created_at'])) ?>
                                    <span class="ms-2 text-muted">
                                        <?= getTimeElapsed($order['created_at']) ?>
                                    </span>
                                </div>
                            </div>
                            <div class="card-body">
                                <h5 class="mb-2">
                                    <i class="fas fa-utensils text-primary"></i>
                                    <?= htmlspecialchars($order['product_name']) ?>
                                </h5>
                                <p class="mb-2">
                                    <strong>Qty:</strong> 
                                    <span class="badge bg-dark"><?= $order['quantity'] ?></span>
                                </p>
                                <?php if ($order['notes']): ?>
                                    <div class="alert alert-warning mb-2 py-2">
                                        <small>
                                            <i class="fas fa-sticky-note"></i>
                                            <?= htmlspecialchars($order['notes']) ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                                <small class="text-muted">
                                    Kasir: <?= htmlspecialchars($order['cashier_name']) ?>
                                </small>
                            </div>
                            <div class="card-footer">
                                <div class="btn-group w-100" role="group">
                                    <?php if ($order['kitchen_status'] === 'pending'): ?>
                                        <button class="btn btn-sm btn-info" 
                                                onclick="updateStatus(<?= $order['id'] ?>, 'preparing')">
                                            <i class="fas fa-fire"></i> Masak
                                        </button>
                                    <?php elseif ($order['kitchen_status'] === 'preparing'): ?>
                                        <button class="btn btn-sm btn-success" 
                                                onclick="updateStatus(<?= $order['id'] ?>, 'ready')">
                                            <i class="fas fa-check"></i> Siap
                                        </button>
                                    <?php elseif ($order['kitchen_status'] === 'ready'): ?>
                                        <button class="btn btn-sm btn-primary" 
                                                onclick="updateStatus(<?= $order['id'] ?>, 'served')">
                                            <i class="fas fa-hand-holding"></i> Sajikan
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-secondary" disabled>
                                            <i class="fas fa-check-double"></i> Selesai
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Auto Refresh Info -->
<div class="auto-refresh-info">
    <i class="fas fa-sync-alt fa-spin"></i>
    Auto-refresh setiap 5 detik
</div>

<script>
// Update current time
function updateTime() {
    const now = new Date();
    const timeString = now.toLocaleTimeString('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });
    document.getElementById('currentTime').textContent = timeString;
}

setInterval(updateTime, 1000);
updateTime();

// Update kitchen status
function updateStatus(orderId, newStatus) {
    if (!confirm('Update status pesanan?')) return;
    
    fetch('api/update_kitchen_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            order_id: orderId,
            status: newStatus
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat update status');
    });
}

// Auto refresh setiap 5 detik
setInterval(() => {
    location.reload();
}, 5000);

// Sound notification untuk order baru (optional)
function checkNewOrders() {
    // Implementasi check new orders bisa ditambahkan di sini
}
</script>

<?php 
include 'footer.php';

// Helper functions
function getStatusColor($status) {
    $colors = [
        'pending' => 'warning',
        'preparing' => 'info',
        'ready' => 'success',
        'served' => 'secondary'
    ];
    return $colors[$status] ?? 'secondary';
}

function getTimeElapsed($time) {
    $diff = time() - strtotime($time);
    $minutes = floor($diff / 60);
    
    if ($minutes < 1) return 'baru saja';
    if ($minutes < 60) return $minutes . ' menit lalu';
    
    $hours = floor($minutes / 60);
    return $hours . ' jam lalu';
}
?>
