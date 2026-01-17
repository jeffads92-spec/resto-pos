<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get categories
$categories = $conn->query("SELECT * FROM categories ORDER BY name");

// Get products
$products = $conn->query("SELECT p.*, c.name as category_name FROM products p 
                          LEFT JOIN categories c ON p.category_id = c.id 
                          WHERE p.is_active = 1 AND p.stock > 0
                          ORDER BY c.name, p.name");

// Get members
$members = $conn->query("SELECT * FROM members ORDER BY name");

include 'header.php';
?>

<style>
.pos-container {
    display: grid;
    grid-template-columns: 1fr 400px;
    gap: 1.5rem;
    padding: 1.5rem;
    max-width: 1600px;
    margin: 0 auto;
    height: calc(100vh - 100px);
}

/* Left Panel - Products */
.products-panel {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 1.5rem;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    overflow-y: auto;
}

.search-bar {
    margin-bottom: 1.5rem;
    position: relative;
}

.search-input {
    width: 100%;
    padding: 1rem 1rem 1rem 3rem;
    border: 2px solid #e2e8f0;
    border-radius: 50px;
    font-size: 1rem;
    transition: all 0.3s ease;
}

.search-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.search-icon {
    position: absolute;
    left: 1.2rem;
    top: 50%;
    transform: translateY(-50%);
    color: #718096;
}

.category-filters {
    display: flex;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.category-btn {
    padding: 0.5rem 1.5rem;
    border: 2px solid #e2e8f0;
    background: white;
    border-radius: 50px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.category-btn:hover, .category-btn.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-color: #667eea;
    transform: translateY(-2px);
}

.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
    gap: 1rem;
}

.product-item {
    background: white;
    border: 2px solid #e2e8f0;
    border-radius: 15px;
    padding: 1rem;
    cursor: pointer;
    transition: all 0.3s ease;
    text-align: center;
}

.product-item:hover {
    border-color: #667eea;
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.2);
}

.product-item img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 10px;
    margin-bottom: 0.75rem;
}

.product-item-name {
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
    color: #2d3748;
}

.product-item-price {
    color: #667eea;
    font-weight: 700;
    font-size: 1rem;
}

.product-item-stock {
    font-size: 0.75rem;
    color: #718096;
    margin-top: 0.25rem;
}

/* Right Panel - Cart */
.cart-panel {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 1.5rem;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    display: flex;
    flex-direction: column;
}

.cart-header {
    font-size: 1.5rem;
    font-weight: 700;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    margin-bottom: 1rem;
}

.member-select {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    margin-bottom: 1rem;
}

.cart-items {
    flex: 1;
    overflow-y: auto;
    margin-bottom: 1rem;
}

.cart-item {
    background: #f7fafc;
    border-radius: 10px;
    padding: 1rem;
    margin-bottom: 0.75rem;
    display: flex;
    gap: 1rem;
    align-items: center;
}

.cart-item img {
    width: 50px;
    height: 50px;
    border-radius: 8px;
    object-fit: cover;
}

.cart-item-details {
    flex: 1;
}

.cart-item-name {
    font-weight: 600;
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.cart-item-price {
    color: #667eea;
    font-size: 0.85rem;
}

.cart-item-qty {
    display: flex;
    gap: 0.5rem;
    align-items: center;
}

.qty-btn {
    width: 30px;
    height: 30px;
    border: none;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 50%;
    cursor: pointer;
    font-weight: 700;
}

.qty-btn:hover {
    transform: scale(1.1);
}

.qty-input {
    width: 50px;
    text-align: center;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    padding: 0.25rem;
    font-weight: 600;
}

.remove-btn {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
}

.cart-summary {
    border-top: 2px solid #e2e8f0;
    padding-top: 1rem;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.75rem;
    font-size: 0.95rem;
}

.summary-label {
    color: #718096;
}

.summary-value {
    font-weight: 700;
    color: #2d3748;
}

.total-row {
    font-size: 1.25rem;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 2px solid #e2e8f0;
}

.total-row .summary-value {
    color: #667eea;
    font-size: 1.5rem;
}

.payment-section {
    margin-top: 1rem;
}

.payment-method {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 0.5rem;
    margin-bottom: 1rem;
}

.payment-btn {
    padding: 0.75rem;
    border: 2px solid #e2e8f0;
    background: white;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
    transition: all 0.3s ease;
}

.payment-btn.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-color: #667eea;
}

.cash-input {
    width: 100%;
    padding: 1rem;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
}

.change-display {
    background: #e7f3ff;
    padding: 1rem;
    border-radius: 10px;
    text-align: center;
    margin-bottom: 1rem;
}

.change-label {
    font-size: 0.9rem;
    color: #718096;
}

.change-amount {
    font-size: 1.5rem;
    font-weight: 700;
    color: #667eea;
}

.checkout-btn {
    width: 100%;
    padding: 1.25rem;
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: white;
    border: none;
    border-radius: 15px;
    font-size: 1.1rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
}

.checkout-btn:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(17, 153, 142, 0.4);
}

.checkout-btn:disabled {
    background: #cbd5e0;
    cursor: not-allowed;
}

@media (max-width: 1200px) {
    .pos-container {
        grid-template-columns: 1fr;
        height: auto;
    }
    
    .cart-panel {
        position: sticky;
        bottom: 0;
        max-height: 50vh;
    }
}
</style>

<div class="pos-container">
    <!-- Left Panel - Products -->
    <div class="products-panel">
        <div class="search-bar">
            <i class="fas fa-search search-icon"></i>
            <input type="text" class="search-input" id="searchProduct" placeholder="Cari menu...">
        </div>
        
        <div class="category-filters">
            <button class="category-btn active" data-category="all">
                <i class="fas fa-th"></i> Semua
            </button>
            <?php while($cat = $categories->fetch_assoc()): ?>
            <button class="category-btn" data-category="<?= $cat['id'] ?>">
                <?= htmlspecialchars($cat['name']) ?>
            </button>
            <?php endwhile; ?>
        </div>
        
        <div class="products-grid" id="productsGrid">
            <?php while($product = $products->fetch_assoc()): ?>
            <div class="product-item" 
                 data-category="<?= $product['category_id'] ?>"
                 data-name="<?= strtolower($product['name']) ?>"
                 onclick='addToCart(<?= json_encode($product) ?>)'>
                <?php 
                $img = !empty($product['image']) ? 'uploads/products/' . $product['image'] : 'assets/images/no-image.png';
                ?>
                <img src="<?= $img ?>" alt="<?= htmlspecialchars($product['name']) ?>" onerror="this.src='assets/images/no-image.png'">
                <div class="product-item-name"><?= htmlspecialchars($product['name']) ?></div>
                <div class="product-item-price">Rp <?= number_format($product['price'], 0, ',', '.') ?></div>
                <div class="product-item-stock">Stok: <?= $product['stock'] ?></div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    
    <!-- Right Panel - Cart -->
    <div class="cart-panel">
        <h2 class="cart-header">🛒 Keranjang</h2>
        
        <select class="member-select" id="memberSelect">
            <option value="">Pelanggan Umum</option>
            <?php while($member = $members->fetch_assoc()): ?>
            <option value="<?= $member['id'] ?>" data-discount="<?= $member['discount'] ?>">
                <?= htmlspecialchars($member['name']) ?> (<?= $member['discount'] ?>% disc)
            </option>
            <?php endwhile; ?>
        </select>
        
        <div class="cart-items" id="cartItems">
            <div style="text-align: center; color: #cbd5e0; padding: 2rem;">
                <i class="fas fa-shopping-cart" style="font-size: 3rem;"></i>
                <p style="margin-top: 1rem;">Keranjang kosong</p>
            </div>
        </div>
        
        <div class="cart-summary">
            <div class="summary-row">
                <span class="summary-label">Subtotal:</span>
                <span class="summary-value" id="subtotal">Rp 0</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Diskon:</span>
                <span class="summary-value" id="discount">Rp 0</span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Pajak (10%):</span>
                <span class="summary-value" id="tax">Rp 0</span>
            </div>
            <div class="summary-row total-row">
                <span class="summary-label">TOTAL:</span>
                <span class="summary-value" id="total">Rp 0</span>
            </div>
        </div>
        
        <div class="payment-section">
            <div class="payment-method">
                <button class="payment-btn active" data-method="cash">
                    <i class="fas fa-money-bill"></i><br>Tunai
                </button>
                <button class="payment-btn" data-method="qris">
                    <i class="fas fa-qrcode"></i><br>QRIS
                </button>
                <button class="payment-btn" data-method="transfer">
                    <i class="fas fa-university"></i><br>Transfer
                </button>
            </div>
            
            <div id="cashPayment">
                <input type="number" class="cash-input" id="cashAmount" placeholder="Jumlah Uang..." oninput="calculateChange()">
                <div class="change-display">
                    <div class="change-label">Kembalian</div>
                    <div class="change-amount" id="changeAmount">Rp 0</div>
                </div>
            </div>
            
            <button class="checkout-btn" id="checkoutBtn" onclick="processCheckout()">
                <i class="fas fa-check-circle"></i> Proses Pembayaran
            </button>
        </div>
    </div>
</div>

<script>
let cart = [];
let paymentMethod = 'cash';

// Category filter
document.querySelectorAll('.category-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        
        const category = this.dataset.category;
        filterProducts(category);
    });
});

function filterProducts(category) {
    document.querySelectorAll('.product-item').forEach(item => {
        if (category === 'all' || item.dataset.category === category) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

// Search
document.getElementById('searchProduct').addEventListener('input', function() {
    const search = this.value.toLowerCase();
    document.querySelectorAll('.product-item').forEach(item => {
        const name = item.dataset.name;
        item.style.display = name.includes(search) ? 'block' : 'none';
    });
});

// Payment method
document.querySelectorAll('.payment-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.payment-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        paymentMethod = this.dataset.method;
        
        document.getElementById('cashPayment').style.display = paymentMethod === 'cash' ? 'block' : 'none';
    });
});

function addToCart(product) {
    const existing = cart.find(item => item.id === product.id);
    
    if (existing) {
        existing.qty++;
    } else {
        cart.push({
            id: product.id,
            name: product.name,
            price: parseFloat(product.price),
            image: product.image,
            qty: 1,
            stock: product.stock
        });
    }
    
    renderCart();
}

function updateQty(index, change) {
    if (cart[index].qty + change > 0 && cart[index].qty + change <= cart[index].stock) {
        cart[index].qty += change;
        renderCart();
    }
}

function removeItem(index) {
    cart.splice(index, 1);
    renderCart();
}

function renderCart() {
    const container = document.getElementById('cartItems');
    
    if (cart.length === 0) {
        container.innerHTML = `
            <div style="text-align: center; color: #cbd5e0; padding: 2rem;">
                <i class="fas fa-shopping-cart" style="font-size: 3rem;"></i>
                <p style="margin-top: 1rem;">Keranjang kosong</p>
            </div>
        `;
    } else {
        container.innerHTML = cart.map((item, index) => `
            <div class="cart-item">
                <img src="${item.image ? 'uploads/products/' + item.image : 'assets/images/no-image.png'}" 
                     onerror="this.src='assets/images/no-image.png'">
                <div class="cart-item-details">
                    <div class="cart-item-name">${item.name}</div>
                    <div class="cart-item-price">Rp ${item.price.toLocaleString('id-ID')}</div>
                </div>
                <div class="cart-item-qty">
                    <button class="qty-btn" onclick="updateQty(${index}, -1)">-</button>
                    <input type="number" class="qty-input" value="${item.qty}" readonly>
                    <button class="qty-btn" onclick="updateQty(${index}, 1)">+</button>
                </div>
                <button class="remove-btn" onclick="removeItem(${index})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `).join('');
    }
    
    calculateTotal();
}

function calculateTotal() {
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
    
    const memberSelect = document.getElementById('memberSelect');
    const discountPercent = memberSelect.selectedOptions[0]?.dataset.discount || 0;
    const discount = subtotal * (discountPercent / 100);
    
    const tax = (subtotal - discount) * 0.1;
    const total = subtotal - discount + tax;
    
    document.getElementById('subtotal').textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
    document.getElementById('discount').textContent = 'Rp ' + discount.toLocaleString('id-ID');
    document.getElementById('tax').textContent = 'Rp ' + tax.toLocaleString('id-ID');
    document.getElementById('total').textContent = 'Rp ' + total.toLocaleString('id-ID');
    
    calculateChange();
}

function calculateChange() {
    const total = parseFloat(document.getElementById('total').textContent.replace(/[^0-9]/g, ''));
    const cash = parseFloat(document.getElementById('cashAmount').value) || 0;
    const change = cash - total;
    
    document.getElementById('changeAmount').textContent = 'Rp ' + (change > 0 ? change : 0).toLocaleString('id-ID');
    
    const checkoutBtn = document.getElementById('checkoutBtn');
    checkoutBtn.disabled = cart.length === 0 || (paymentMethod === 'cash' && change < 0);
}

document.getElementById('memberSelect').addEventListener('change', calculateTotal);

function processCheckout() {
    if (cart.length === 0) return;
    
    showLoading();
    
    const total = parseFloat(document.getElementById('total').textContent.replace(/[^0-9]/g, ''));
    
    const data = {
        items: cart,
        member_id: document.getElementById('memberSelect').value || null,
        payment_method: paymentMethod,
        cash_amount: paymentMethod === 'cash' ? document.getElementById('cashAmount').value : total,
        total: total
    };
    
    fetch('api/process_transaction.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(result => {
        hideLoading();
        if (result.success) {
            alert('✅ Transaksi berhasil!\nInvoice: ' + result.invoice);
            cart = [];
            renderCart();
            document.getElementById('cashAmount').value = '';
            
            if (confirm('Print struk?')) {
                window.open('print_receipt.php?id=' + result.transaction_id, '_blank');
            }
        } else {
            alert('❌ Error: ' + result.message);
        }
    })
    .catch(err => {
        hideLoading();
        alert('❌ Terjadi kesalahan: ' + err.message);
    });
}
</script>

<?php include 'footer.php'; ?>
