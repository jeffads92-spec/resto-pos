<?php
// ============================================
// REPORTS PAGE
// File: reports.php
// ============================================

session_start();
require_once 'config.php';

// Cek login dan role admin
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    die('Akses ditolak. Hanya admin yang dapat mengakses laporan.');
}

// Get filter parameters
$report_type = $_GET['type'] ?? 'daily';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Query laporan penjualan
$sales_query = "
    SELECT 
        DATE(transaction_date) as date,
        COUNT(*) as total_transactions,
        SUM(total) as total_sales,
        SUM(subtotal) as subtotal,
        SUM(tax) as total_tax,
        SUM(discount) as total_discount
    FROM transactions
    WHERE DATE(transaction_date) BETWEEN ? AND ?
    AND status = 'completed'
    GROUP BY DATE(transaction_date)
    ORDER BY date DESC
";

$stmt = $conn->prepare($sales_query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$sales_data = $stmt->get_result();

// Query top products
$top_products_query = "
    SELECT 
        p.product_name,
        SUM(ti.quantity) as total_sold,
        SUM(ti.subtotal) as total_revenue,
        AVG(ti.price) as avg_price
    FROM transaction_items ti
    JOIN products p ON ti.product_id = p.id
    JOIN transactions t ON ti.transaction_id = t.id
    WHERE DATE(t.transaction_date) BETWEEN ? AND ?
    AND t.status = 'completed'
    GROUP BY p.id, p.product_name
    ORDER BY total_sold DESC
    LIMIT 10
";

$stmt2 = $conn->prepare($top_products_query);
$stmt2->bind_param("ss", $start_date, $end_date);
$stmt2->execute();
$top_products = $stmt2->get_result();

// Summary totals
$summary_query = "
    SELECT 
        COUNT(*) as total_transactions,
        SUM(total) as grand_total,
        AVG(total) as avg_transaction,
        SUM(discount) as total_discount
    FROM transactions
    WHERE DATE(transaction_date) BETWEEN ? AND ?
    AND status = 'completed'
";

$stmt3 = $conn->prepare($summary_query);
$stmt3->bind_param("ss", $start_date, $end_date);
$stmt3->execute();
$summary = $stmt3->get_result()->fetch_assoc();

include 'header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2><i class="fas fa-file-alt"></i> Laporan Keuangan</h2>
        </div>
        <div class="col-md-6 text-end">
            <button class="btn btn-success" onclick="window.print()">
                <i class="fas fa-print"></i> Print Laporan
            </button>
            <button class="btn btn-primary" onclick="exportToExcel()">
                <i class="fas fa-file-excel"></i> Export Excel
            </button>
        </div>
    </div>

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Tipe Laporan</label>
                    <select name="type" class="form-select">
                        <option value="daily" <?= $report_type === 'daily' ? 'selected' : '' ?>>Harian</option>
                        <option value="monthly" <?= $report_type === 'monthly' ? 'selected' : '' ?>>Bulanan</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tanggal Mulai</label>
                    <input type="date" name="start_date" class="form-control" 
                           value="<?= htmlspecialchars($start_date) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Tanggal Akhir</label>
                    <input type="date" name="end_date" class="form-control" 
                           value="<?= htmlspecialchars($end_date) ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Tampilkan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Total Transaksi</h6>
                    <h3><?= number_format($summary['total_transactions'] ?? 0) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Total Penjualan</h6>
                    <h3 class="text-success">Rp <?= number_format($summary['grand_total'] ?? 0, 0, ',', '.') ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Rata-rata Transaksi</h6>
                    <h3>Rp <?= number_format($summary['avg_transaction'] ?? 0, 0, ',', '.') ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <h6 class="text-muted">Total Diskon</h6>
                    <h3 class="text-danger">Rp <?= number_format($summary['total_discount'] ?? 0, 0, ',', '.') ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Sales Report Table -->
        <div class="col-md-7 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Laporan Penjualan Harian</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="salesTable">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Transaksi</th>
                                    <th>Subtotal</th>
                                    <th>Pajak</th>
                                    <th>Diskon</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $grand_total = 0;
                                while ($row = $sales_data->fetch_assoc()): 
                                    $grand_total += $row['total_sales'];
                                ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($row['date'])) ?></td>
                                        <td><?= $row['total_transactions'] ?></td>
                                        <td>Rp <?= number_format($row['subtotal'], 0, ',', '.') ?></td>
                                        <td>Rp <?= number_format($row['total_tax'], 0, ',', '.') ?></td>
                                        <td>Rp <?= number_format($row['total_discount'], 0, ',', '.') ?></td>
                                        <td><strong>Rp <?= number_format($row['total_sales'], 0, ',', '.') ?></strong></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-dark">
                                    <td colspan="5" class="text-end"><strong>GRAND TOTAL:</strong></td>
                                    <td><strong>Rp <?= number_format($grand_total, 0, ',', '.') ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Products -->
        <div class="col-md-5 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Top 10 Produk Terlaris</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Produk</th>
                                    <th>Terjual</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $rank = 1;
                                while ($product = $top_products->fetch_assoc()): 
                                ?>
                                    <tr>
                                        <td><?= $rank++ ?></td>
                                        <td><?= htmlspecialchars($product['product_name']) ?></td>
                                        <td><span class="badge bg-primary"><?= $product['total_sold'] ?></span></td>
                                        <td class="text-success">Rp <?= number_format($product['total_revenue'], 0, ',', '.') ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function exportToExcel() {
    window.location.href = 'api/export_excel.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>';
}
</script>

<?php include 'footer.php'; ?>