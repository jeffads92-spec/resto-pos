<?php
// ============================================
// DASHBOARD PAGE (FIXED)
// File: index.php
// Error Fixed: Unknown column 'status' dan berbagai error lainnya
// ============================================

session_start();
require_once 'config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get today's date
$today = date('Y-m-d');
$this_month = date('Y-m');

// ==========================================
// 1. TOTAL TRANSAKSI HARI INI
// ==========================================
$query_today = "
    SELECT 
        COUNT(*) as total_transactions,
        COALESCE(SUM(total), 0) as total_sales
    FROM transactions 
    WHERE DATE(transaction_date) = ?
    AND status = 'completed'
";
$stmt = $conn->prepare($query_today);
$stmt->bind_param("s", $today);
$stmt->execute();
$result_today = $stmt->get_result()->fetch_assoc();
$stmt->close();

$today_transactions = $result_today['total_transactions'];
$today_sales = $result_today['total_sales'];

// ==========================================
// 2. TOTAL TRANSAKSI BULAN INI
// ==========================================
$query_month = "
    SELECT 
        COUNT(*) as total_transactions,
        COALESCE(SUM(total), 0) as total_sales,
        COALESCE(SUM(total - (SELECT COALESCE(SUM(ti.quantity * p.cost_price), 0) 
                              FROM transaction_items ti 
                              JOIN products p ON ti.product_id = p.id 
                              WHERE ti.transaction_id = transactions.id)), 0) as gross_profit
    FROM transactions 
    WHERE DATE_FORMAT(transaction_date, '%Y-%m') = ?
    AND status = 'completed'
";
$stmt = $conn->prepare($query_month);
$stmt->bind_param("s", $this_month);
$stmt->execute();
$result_month = $stmt->get_result()->fetch_assoc();
$stmt->close();

$month_transactions = $result_month['total_transactions'];
$month_sales = $result_month['total_sales'];
$month_profit = $result_month['gross_profit'];

// ==========================================
// 3. TOTAL PENGELUARAN BULAN INI
// ==========================================
$query_expenses = "
    SELECT COALESCE(SUM(amount), 0) as total_expenses
    FROM expenses 
    WHERE DATE_FORMAT(expense_date, '%Y-%m') = ?
";
$stmt = $conn->prepare($query_expenses);
$stmt->bind_param("s", $this_month);
$stmt->execute();
$result_expenses = $stmt->get_result()->fetch_assoc();
$stmt->close();

$month_expenses = $result_expenses['total_expenses'];
$net_profit = $month_profit - $month_expenses;

// ==========================================
// 4. PRODUK STOK MENIPIS
// ==========================================
$query_low_stock = "
    SELECT 
        id,
        product_name,
        stock,
        min_stock
    FROM products 
    WHERE stock <= min_stock 
    AND is_active = 1
    ORDER BY stock ASC
    LIMIT 5
";
$low_stock_products = $conn->query($query_low_stock);

// ==========================================
// 5. TOP 5 PRODUK TERLARIS BULAN INI
// ==========================================
$query_top_products = "
    SELECT 
        p.product_name,
        SUM(ti.quantity) as total_sold,
        SUM(ti.subtotal) as total_revenue
    FROM transaction_items ti
    JOIN products p ON ti.product_id = p.id
    JOIN transactions t ON ti.transaction_id = t.id
    WHERE DATE_FORMAT(t.transaction_date, '%Y-%m') = ?
    AND t.status = 'completed'
    GROUP BY p.id, p.product_name
    ORDER BY total_sold DESC
    LIMIT 5
";
$stmt = $conn->prepare($query_top_products);
$stmt->bind_param("s", $this_month);
$stmt->execute();
$top_products = $stmt->get_result();
$stmt->close();

// ==========================================
// 6. GRAFIK PENJUALAN 7 HARI TERAKHIR
// ==========================================
$sales_data = [];
$dates = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dates[] = date('d M', strtotime($date));
    
    $query_daily = "
        SELECT COALESCE(SUM(total), 0) as daily_sales
        FROM transactions 
        WHERE DATE(transaction_date) = ?
        AND status = 'completed'
    ";
    $stmt = $conn->prepare($query_daily);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $sales_data[] = $result['daily_sales'];
    $stmt->close();
}

// ==========================================
// 7. PESANAN PENDING DI DAPUR
// ==========================================
$query_pending = "
    SELECT COUNT(*) as pending_orders
    FROM transaction_items 
    WHERE kitchen_status IN ('pending', 'preparing')
";
$result_pending = $conn->query($query_pending);
$pending_orders = $result_pending->fetch_assoc()['pending_orders'];

include 'header.php';
?>

<div class="container-fluid py-4">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h2><i class="fas fa-chart-line"></i> Dashboard</h2>
            <p class="text-muted mb-0">
                Selamat datang, <strong><?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?></strong>
            </p>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group">
                <a href="pos.php" class="btn btn-primary">
                    <i class="fas fa-cash-register"></i> Buka POS
                </a>
                <a href="kitchen.php" class="btn btn-info text-white">
                    <i class="fas fa-utensils"></i> Kitchen Display
                </a>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <!-- Today's Sales -->
        <div class="col-md-3">
            <div class="card stat-card border-primary" style="border-left-color: #667eea !important;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Penjualan Hari Ini</h6>
                            <h3 class="mb-0">Rp <?= number_format($today_sales ?? 0, 0, ',', '.') ?></h3>
                            <small class="text-muted"><?= $today_transactions ?? 0 ?> transaksi</small>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-dollar-sign fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Month's Sales -->
        <div class="col-md-3">
            <div class="card stat-card border-success" style="border-left-color: #28a745 !important;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Penjualan Bulan Ini</h6>
                            <h3 class="mb-0">Rp <?= number_format($month_sales ?? 0, 0, ',', '.') ?></h3>
                            <small class="text-muted"><?= $month_transactions ?? 0 ?> transaksi</small>
                        </div>
                        <div class="text-success">
                            <i class="fas fa-chart-line fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Net Profit -->
        <div class="col-md-3">
            <div class="card stat-card border-info" style="border-left-color: #17a2b8 !important;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Laba Bersih Bulan Ini</h6>
                            <h3 class="mb-0 <?= ($net_profit ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>">
                                Rp <?= number_format($net_profit ?? 0, 0, ',', '.') ?>
                            </h3>
                            <small class="text-muted d-none d-md-block">
                                Kotor: Rp <?= number_format($month_profit ?? 0, 0, ',', '.') ?>
                            </small>
                        </div>
                        <div class="text-info">
                            <i class="fas fa-piggy-bank fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Orders -->
        <div class="col-md-3">
            <div class="card stat-card border-warning" style="border-left-color: #ffc107 !important;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-muted mb-2">Pesanan Pending</h6>
                            <h3 class="mb-0"><?= $pending_orders ?? 0 ?></h3>
                            <small class="text-muted">Di kitchen</small>
                        </div>
                        <div class="text-warning">
                            <i class="fas fa-utensils fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Sales Chart -->
        <div class="col-md-8 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-area"></i>
                        Grafik Penjualan 7 Hari Terakhir
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="salesChart" height="100"></canvas>
                </div>
            </div>
        </div>

        <!-- Top Products -->
        <div class="col-md-4 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-fire"></i>
                        Produk Terlaris Bulan Ini
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($top_products->num_rows > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php $rank = 1; while ($product = $top_products->fetch_assoc()): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge bg-primary me-2">#<?= $rank++ ?></span>
                                            <strong><?= htmlspecialchars($product['product_name']) ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <?= $product['total_sold'] ?> terjual
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <strong class="text-success">
                                                Rp <?= number_format($product['total_revenue'], 0, ',', '.') ?>
                                            </strong>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>Belum ada penjualan bulan ini</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Low Stock Alert -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle"></i>
                        Stok Menipis
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($low_stock_products->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Produk</th>
                                        <th>Stok</th>
                                        <th>Min. Stok</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($item = $low_stock_products->fetch_assoc()): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($item['product_name']) ?></td>
                                            <td>
                                                <span class="badge bg-danger">
                                                    <?= $item['stock'] ?>
                                                </span>
                                            </td>
                                            <td><?= $item['min_stock'] ?></td>
                                            <td>
                                                <span class="badge bg-warning">
                                                    Perlu Restock
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center mt-3">
                            <a href="products.php" class="btn btn-sm btn-outline-primary">
                                Lihat Semua Produk
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                            <p>Semua stok aman</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle"></i>
                        Ringkasan Bulan Ini
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3 pb-3 border-bottom">
                        <div class="d-flex justify-content-between">
                            <span>Total Penjualan</span>
                            <strong class="text-primary">
                                Rp <?= number_format($month_sales ?? 0, 0, ',', '.') ?>
                            </strong>
                        </div>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <div class="d-flex justify-content-between">
                            <span>Laba Kotor</span>
                            <strong class="text-success">
                                Rp <?= number_format($month_profit ?? 0, 0, ',', '.') ?>
                            </strong>
                        </div>
                    </div>
                    <div class="mb-3 pb-3 border-bottom">
                        <div class="d-flex justify-content-between">
                            <span>Pengeluaran</span>
                            <strong class="text-danger">
                                Rp <?= number_format($month_expenses ?? 0, 0, ',', '.') ?>
                            </strong>
                        </div>
                    </div>
                    <div class="mb-0">
                        <div class="d-flex justify-content-between">
                            <span><strong>Laba Bersih</strong></span>
                            <strong class="<?= ($net_profit ?? 0) >= 0 ? 'text-success' : 'text-danger' ?>">
                                Rp <?= number_format($net_profit ?? 0, 0, ',', '.') ?>
                            </strong>
                        </div>
                    </div>
                    
                    <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                    <div class="text-center mt-4">
                        <a href="reports.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-file-alt"></i>
                            Lihat Laporan Lengkap
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Script -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Sales Chart
    const ctx = document.getElementById('salesChart').getContext('2d');
    const salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($dates) ?>,
            datasets: [{
                label: 'Penjualan (Rp)',
                data: <?= json_encode($sales_data) ?>,
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                borderColor: 'rgba(102, 126, 234, 1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true,
                pointBackgroundColor: 'rgba(102, 126, 234, 1)',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'Rp ' + context.parsed.y.toLocaleString('id-ID');
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + (value / 1000) + 'k';
                        }
                    }
                }
            }
        }
    });
});
</script>

<?php include 'footer.php'; ?>