<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Query untuk data dashboard
$query_sales_today = "SELECT COALESCE(SUM(total), 0) as total FROM transactions WHERE DATE(created_at) = CURDATE()";
$result_sales = $conn->query($query_sales_today);
$sales_today = $result_sales->fetch_assoc()['total'];

$query_transactions_today = "SELECT COUNT(*) as count FROM transactions WHERE DATE(created_at) = CURDATE()";
$result_trans = $conn->query($query_transactions_today);
$transactions_today = $result_trans->fetch_assoc()['count'];

$query_products_low = "SELECT COUNT(*) as count FROM products WHERE stock <= stock_min AND stock_min > 0";
$result_low = $conn->query($query_products_low);
$products_low_stock = $result_low->fetch_assoc()['count'];

$query_members = "SELECT COUNT(*) as count FROM members";
$result_members = $conn->query($query_members);
$total_members = $result_members->fetch_assoc()['count'];

// Data untuk grafik 7 hari terakhir
$sales_chart_data = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $query = "SELECT COALESCE(SUM(total), 0) as total FROM transactions WHERE DATE(created_at) = '$date'";
    $result = $conn->query($query);
    $sales_chart_data[] = [
        'date' => date('d M', strtotime($date)),
        'total' => $result->fetch_assoc()['total']
    ];
}

// Produk Terlaris
$query_top_products = "SELECT p.name, p.image, SUM(ti.quantity) as qty_sold, SUM(ti.subtotal) as revenue
    FROM transaction_items ti
    JOIN products p ON ti.product_id = p.id
    JOIN transactions t ON ti.transaction_id = t.id
    WHERE DATE(t.created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY p.id
    ORDER BY qty_sold DESC
    LIMIT 5";
$top_products = $conn->query($query_top_products);

include 'header.php';
?>

<style>
:root {
    --primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --success: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    --warning: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --info: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --danger: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
}

body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.dashboard-container {
    padding: 2rem;
    max-width: 1400px;
    margin: 0 auto;
}

.welcome-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    border: 1px solid rgba(255,255,255,0.3);
}

.welcome-card h1 {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.stat-cards {
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
    border: 1px solid rgba(255,255,255,0.3);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 5px;
    background: var(--primary);
}

.stat-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.stat-card.success::before {
    background: var(--success);
}

.stat-card.warning::before {
    background: var(--warning);
}

.stat-card.info::before {
    background: var(--info);
}

.stat-card.danger::before {
    background: var(--danger);
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
    background: var(--primary);
    color: white;
}

.stat-card.success .stat-icon {
    background: var(--success);
}

.stat-card.warning .stat-icon {
    background: var(--warning);
}

.stat-card.info .stat-icon {
    background: var(--info);
}

.stat-card.danger .stat-icon {
    background: var(--danger);
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

.chart-container {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    border: 1px solid rgba(255,255,255,0.3);
    margin-bottom: 2rem;
}

.chart-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #2d3748;
    margin-bottom: 1.5rem;
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.product-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 1.5rem;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    border: 1px solid rgba(255,255,255,0.3);
    transition: all 0.3s ease;
    text-align: center;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 50px rgba(0,0,0,0.3);
}

.product-img {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    object-fit: cover;
    margin: 0 auto 1rem;
    border: 4px solid #f7fafc;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.product-name {
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 0.5rem;
    font-size: 1rem;
}

.product-stats {
    display: flex;
    justify-content: space-around;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 2px solid #e2e8f0;
}

.product-stat {
    text-align: center;
}

.product-stat-value {
    font-weight: 700;
    color: #667eea;
    font-size: 1.1rem;
}

.product-stat-label {
    font-size: 0.75rem;
    color: #718096;
}

canvas {
    max-height: 300px;
}

@media (max-width: 768px) {
    .dashboard-container {
        padding: 1rem;
    }
    
    .stat-cards {
        grid-template-columns: 1fr;
    }
    
    .products-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="dashboard-container">
    <div class="welcome-card">
        <h1>👋 Selamat Datang, <?= htmlspecialchars($_SESSION['username']) ?>!</h1>
        <p style="color: #718096; font-size: 1.1rem;">Dashboard - <?= date('l, d F Y') ?></p>
    </div>

    <div class="stat-cards">
        <div class="stat-card success">
            <div class="stat-icon">💰</div>
            <div class="stat-value">Rp <?= number_format($sales_today, 0, ',', '.') ?></div>
            <div class="stat-label">Penjualan Hari Ini</div>
        </div>

        <div class="stat-card info">
            <div class="stat-icon">📊</div>
            <div class="stat-value"><?= $transactions_today ?></div>
            <div class="stat-label">Transaksi Hari Ini</div>
        </div>

        <div class="stat-card warning">
            <div class="stat-icon">⚠️</div>
            <div class="stat-value"><?= $products_low_stock ?></div>
            <div class="stat-label">Produk Stok Menipis</div>
        </div>

        <div class="stat-card danger">
            <div class="stat-icon">👥</div>
            <div class="stat-value"><?= $total_members ?></div>
            <div class="stat-label">Total Member</div>
        </div>
    </div>

    <div class="chart-container">
        <h2 class="chart-title">📈 Penjualan 7 Hari Terakhir</h2>
        <canvas id="salesChart"></canvas>
    </div>

    <div class="chart-container">
        <h2 class="chart-title">🏆 Top 5 Produk Terlaris</h2>
        <div class="products-grid">
            <?php while($product = $top_products->fetch_assoc()): ?>
            <div class="product-card">
                <?php 
                $img_path = !empty($product['image']) ? 'uploads/products/' . $product['image'] : 'assets/images/no-image.png';
                ?>
                <img src="<?= $img_path ?>" alt="<?= htmlspecialchars($product['name']) ?>" class="product-img" onerror="this.src='assets/images/no-image.png'">
                <div class="product-name"><?= htmlspecialchars($product['name']) ?></div>
                <div class="product-stats">
                    <div class="product-stat">
                        <div class="product-stat-value"><?= $product['qty_sold'] ?></div>
                        <div class="product-stat-label">Terjual</div>
                    </div>
                    <div class="product-stat">
                        <div class="product-stat-value">Rp <?= number_format($product['revenue']/1000, 0) ?>k</div>
                        <div class="product-stat-label">Revenue</div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
const ctx = document.getElementById('salesChart').getContext('2d');
const gradient = ctx.createLinearGradient(0, 0, 0, 300);
gradient.addColorStop(0, 'rgba(102, 126, 234, 0.5)');
gradient.addColorStop(1, 'rgba(118, 75, 162, 0.1)');

new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($sales_chart_data, 'date')) ?>,
        datasets: [{
            label: 'Penjualan (Rp)',
            data: <?= json_encode(array_column($sales_chart_data, 'total')) ?>,
            backgroundColor: gradient,
            borderColor: '#667eea',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#fff',
            pointBorderColor: '#667eea',
            pointBorderWidth: 3,
            pointRadius: 5,
            pointHoverRadius: 7
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
                backgroundColor: 'rgba(0,0,0,0.8)',
                padding: 12,
                cornerRadius: 8,
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
                        return 'Rp ' + (value/1000) + 'k';
                    }
                },
                grid: {
                    color: 'rgba(0,0,0,0.05)'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});
</script>

<?php include 'footer.php'; ?>
