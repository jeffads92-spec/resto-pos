<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit();
}

$date_from = $_GET['date_from'] ?? date('Y-m-01');
$date_to = $_GET['date_to'] ?? date('Y-m-d');

// Get sales summary - SIMPLE QUERY
$summary = $conn->query("SELECT 
    COUNT(*) as total_transactions,
    COALESCE(SUM(subtotal), 0) as total_subtotal,
    COALESCE(SUM(discount), 0) as total_discount,
    COALESCE(SUM(tax), 0) as total_tax,
    COALESCE(SUM(total), 0) as total_sales
    FROM transactions 
    WHERE DATE(created_at) BETWEEN '$date_from' AND '$date_to'
    AND status = 'completed'")->fetch_assoc();

// Get expenses
$total_expenses = $conn->query("SELECT COALESCE(SUM(amount), 0) as total 
                                 FROM expenses 
                                 WHERE expense_date BETWEEN '$date_from' AND '$date_to'")->fetch_assoc()['total'];

$net_profit = $summary['total_sales'] - $total_expenses;

include 'header.php';
?>

<style>
body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
}

.container-main {
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
    grid-template-columns: 1fr 1fr auto auto;
    gap: 1rem;
    align-items: end;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 1rem;
}

.btn {
    padding: 0.75rem 2rem;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.btn-success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: white;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 1.5rem;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    margin-bottom: 1rem;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: #2d3748;
    margin-bottom: 0.25rem;
}

.stat-label {
    color: #718096;
    font-size: 0.95rem;
    font-weight: 500;
}

@media (max-width: 768px) {
    .filter-form {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="container-main">
    <div class="page-header">
        <h1 class="page-title">📊 Laporan Keuangan</h1>
        <p style="color: #718096; margin: 0;">Ringkasan penjualan dan keuangan</p>
    </div>

    <div class="filter-card">
        <form class="filter-form" method="GET">
            <div class="form-group">
                <label>Dari Tanggal</label>
                <input type="date" name="date_from" value="<?= $date_from ?>" class="form-control">
            </div>
            <div class="form-group">
                <label>Sampai Tanggal</label>
                <input type="date" name="date_to" value="<?= $date_to ?>" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">🔍 Filter</button>
            <a href="export_excel.php?type=sales&date_from=<?= $date_from ?>&date_to=<?= $date_to ?>" class="btn btn-success">
                📥 Export Excel
            </a>
        </form>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white;">💰</div>
            <div class="stat-value">Rp <?= number_format($summary['total_sales'], 0, ',', '.') ?></div>
            <div class="stat-label">Total Penjualan</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white;">💸</div>
            <div class="stat-value">Rp <?= number_format($total_expenses, 0, ',', '.') ?></div>
            <div class="stat-label">Total Pengeluaran</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white;">📈</div>
            <div class="stat-value">Rp <?= number_format($net_profit, 0, ',', '.') ?></div>
            <div class="stat-label">Laba Bersih</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">📝</div>
            <div class="stat-value"><?= $summary['total_transactions'] ?></div>
            <div class="stat-label">Total Transaksi</div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>
