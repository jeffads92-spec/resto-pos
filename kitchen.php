<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get kitchen orders
$query = "SELECT 
    ko.*,
    p.name as product_name,
    p.image,
    t.invoice_number,
    ti.notes
    FROM kitchen_orders ko
    JOIN transactions t ON ko.transaction_id = t.id
    JOIN transaction_items ti ON ko.transaction_item_id = ti.id
    JOIN products p ON ti.product_id = p.id
    WHERE ko.status != 'served'
    ORDER BY 
        CASE ko.status
            WHEN 'pending' THEN 1
            WHEN 'preparing' THEN 2
            WHEN 'ready' THEN 3
        END,
        ko.created_at ASC";
$orders = $conn->query($query);

include 'header.php';
?>

<style>
body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
}

.kitchen-container {
    padding: 1.5rem;
    max-width: 1600px;
    margin: 0 auto;
}

.kitchen-header {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 1.5rem 2rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.kitchen-title {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
}

.refresh-info {
    color: #718096;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.refresh-dot {
    width: 10px;
    height: 10px;
    background: #38ef7d;
    border-radius: 50%;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.status-tabs {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.tab {
    padding: 0.75rem 1.5rem;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 50px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.tab.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.tab:hover {
    transform: translateY(-2px);
}

.orders-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

.order-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 1.5rem;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
    border: 3px solid transparent;
}

.order-card.pending {
    border-color: #f5576c;
}

.order-card.preparing {
    border-color: #ffc107;
}

.order-card.ready {
    border-color: #38ef7d;
}

.order-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 50px rgba(0,0,0,0.3);
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e2e8f0;
}

.order-invoice {
    font-size: 1.25rem;
    font-weight: 700;
    color: #2d3748;
}

.order-time {
    font-size: 0.85rem;
    color: #718096;
}

.order-product {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
}

.product-image {
    width: 60px;
    height: 60px;
    border-radius: 10px;
    object-fit: cover;
    background: #f7fafc;
}

.product-info {
    flex: 1;
}

.product-name {
    font-size: 1.1rem;
    font-weight: 700;
    color: #2d3748;
    margin-bottom: 0.25rem;
}

.product-qty {
    display: inline-block;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: 50px;
    font-size: 0.9rem;
    font-weight: 600;
}

.order-notes {
    background: #fff3cd;
    padding: 0.75rem;
    border-radius: 10px;
    margin-bottom: 1rem;
    font-size: 0.9rem;
    color: #856404;
}

.order-status {
    display: flex;
    gap: 0.5rem;
}

.status-btn {
    flex: 1;
    padding: 0.75rem;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.status-btn.pending {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
}

.status-btn.preparing {
    background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
    color: white;
}

.status-btn.ready {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: white;
}

.status-btn.served {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
}

.status-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
}

.empty-state {
    text-align: center;
    padding: 3rem;
    color: white;
    font-size: 1.25rem;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

@media (max-width: 768px) {
    .orders-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="kitchen-container">
    <div class="kitchen-header">
        <div>
            <h1 class="kitchen-title">🍳 Kitchen Display System</h1>
            <p style="color: #718096; margin: 0;">Monitor pesanan dapur real-time</p>
        </div>
        <div class="refresh-info">
            <span class="refresh-dot"></span>
            Auto-refresh setiap 5 detik
        </div>
    </div>

    <div class="status-tabs">
        <div class="tab active" onclick="filterStatus('all')">
            📋 Semua Pesanan
        </div>
        <div class="tab" onclick="filterStatus('pending')">
            🔴 Pending
        </div>
        <div class="tab" onclick="filterStatus('preparing')">
            🟡 Sedang Diproses
        </div>
        <div class="tab" onclick="filterStatus('ready')">
            🟢 Siap Diantar
        </div>
    </div>

    <div class="orders-grid" id="ordersGrid">
        <?php if($orders->num_rows == 0): ?>
            <div class="empty-state">
                <i class="fas fa-check-circle"></i>
                <p>Tidak ada pesanan saat ini</p>
            </div>
        <?php else: ?>
            <?php while($order = $orders->fetch_assoc()): ?>
            <div class="order-card <?= $order['status'] ?>" data-status="<?= $order['status'] ?>">
                <div class="order-header">
                    <div class="order-invoice">#<?= htmlspecialchars($order['invoice_number']) ?></div>
                    <div class="order-time">
                        <?php
                        $time_ago = time() - strtotime($order['created_at']);
                        $minutes = floor($time_ago / 60);
                        echo $minutes . ' menit lalu';
                        ?>
                    </div>
                </div>
                
                <div class="order-product">
                    <?php 
                    $img = !empty($order['image']) ? 'uploads/products/' . $order['image'] : 'assets/images/no-image.png';
                    ?>
                    <img src="<?= $img ?>" alt="" class="product-image" onerror="this.src='assets/images/no-image.png'">
                    <div class="product-info">
                        <div class="product-name"><?= htmlspecialchars($order['product_name']) ?></div>
                        <span class="product-qty">Qty: <?= $order['quantity'] ?></span>
                    </div>
                </div>
                
                <?php if($order['notes']): ?>
                <div class="order-notes">
                    <strong>Catatan:</strong> <?= htmlspecialchars($order['notes']) ?>
                </div>
                <?php endif; ?>
                
                <div class="order-status">
                    <?php if($order['status'] == 'pending'): ?>
                        <button class="status-btn preparing" onclick="updateStatus(<?= $order['id'] ?>, 'preparing')">
                            👨‍🍳 Mulai Masak
                        </button>
                    <?php elseif($order['status'] == 'preparing'): ?>
                        <button class="status-btn ready" onclick="updateStatus(<?= $order['id'] ?>, 'ready')">
                            ✅ Siap Diantar
                        </button>
                    <?php elseif($order['status'] == 'ready'): ?>
                        <button class="status-btn served" onclick="updateStatus(<?= $order['id'] ?>, 'served')">
                            🚀 Sudah Diantar
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Auto refresh every 5 seconds
setInterval(function() {
    location.reload();
}, 5000);

function filterStatus(status) {
    // Update active tab
    document.querySelectorAll('.tab').forEach(tab => tab.classList.remove('active'));
    event.target.classList.add('active');
    
    // Filter cards
    const cards = document.querySelectorAll('.order-card');
    cards.forEach(card => {
        if (status === 'all' || card.dataset.status === status) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function updateStatus(id, newStatus) {
    if (confirm('Update status pesanan?')) {
        showLoading();
        
        fetch('api/update_kitchen_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id, status: newStatus })
        })
        .then(res => res.json())
        .then(data => {
            hideLoading();
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(err => {
            hideLoading();
            alert('Error: ' + err.message);
        });
    }
}
</script>

<?php include 'footer.php'; ?>
