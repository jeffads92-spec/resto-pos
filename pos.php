<?php
// ============================================
// POINT OF SALE (POS) PAGE
// File: pos.php
// ============================================

session_start();
require_once 'config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Get products with categories (FIXED: product_name bukan name)
$products_query = "
    SELECT 
        p.id,
        p.product_name,
        p.category_id,
        p.price,
        p.stock,
        p.image,
        c.name as category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    WHERE p.is_active = 1 AND p.stock > 0
    ORDER BY c.name, p.product_name
";
$products = $conn->query($products_query);

// Get categories
$categories_query = "SELECT * FROM categories WHERE is_active = 1 ORDER BY name";
$categories = $conn->query($categories_query);

// Get members untuk dropdown
$members_query = "SELECT id, member_code, name, points FROM members WHERE is_active = 1 ORDER BY name";
$members = $conn->query($members_query);

include 'header.php';
?>

<style>
.pos-container {
    background: #f8f9fa;
    min-height: calc(100vh - 56px);
}

.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 15px;
    max-height: 60vh;
    overflow-y: auto;
}

.product-card {
    background: white;
    border-radius: 10px;
    padding: 15px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    border: 2px solid transparent;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    border-color: #667eea;
}

.product-card.out-of-stock {
    opacity: 0.5;
    cursor: not-allowed;
}

.product-image {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 8px;
    margin-bottom: 10px;
}

.cart-container {
    background: white;
    border-radius: 10px;
    padding: 20px;
    height: calc(100vh - 100px);
    display: flex;
    flex-direction: column;
}

.cart-items {
    flex: 1;
    overflow-y: auto;
    margin-bottom: 20px;
}

.cart-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    border-bottom: 1px solid #eee;
}

.cart-summary {
    border-top: 2px solid #eee;
    padding-top: 15px;
}

.category-filter {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.category-btn {
    padding: 8px 15px;
    border-radius: 20px;
    border: 2px solid #ddd;
    background: white;
    cursor: pointer;
    transition: all 0.3s;
}

.category-btn.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-color: #667eea;
}

.qty-control {
    display: flex;
    align-items: center;
    gap: 10px;
}

.qty-btn {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    border: none;
    background: #667eea;
    color: white;
    cursor: pointer;
    font-weight: bold;
}

.qty-btn:hover {
    background: #764ba2;
}

.total-display {
    font-size: 2rem;
    font-weight: bold;
    color: #667eea;
}

@media (max-width: 768px) {
    .product-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    
    .product-card {
        padding: 10px;
    }
    
    .product-image {
        width: 60px;
        height: 60px;
    }
    
    .cart-container {
        height: auto;
        min-height: 400px;
    }
    
    .cart-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .qty-control {
        width: 100%;
        justify-content: space-between;
    }
    
    .total-display {
        font-size: 1.5rem;
    }
}

@media (max-width: 576px) {
    .category-filter {
        overflow-x: auto;
        flex-wrap: nowrap;
        padding-bottom: 10px;
    }
    
    .category-btn {
        white-space: nowrap;
    }
}
</style>

<div class="pos-container">
    <div class="container-fluid py-3">
        <div class="row mb-3">
            <div class="col-md-12">
                <h3><i class="fas fa-cash-register"></i> Point of Sale</h3>
            </div>
        </div>

        <div class="row">
            <!-- Left Side - Products -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Pilih Produk</h5>
                    </div>
                    <div class="card-body">
                        <!-- Category Filter -->
                        <div class="category-filter">
                            <button class="category-btn active" onclick="filterCategory('all')">
                                Semua
                            </button>
                            <?php 
                            $categories->data_seek(0);
                            while ($cat = $categories->fetch_assoc()): 
                            ?>
                                <button class="category-btn" onclick="filterCategory(<?= $cat['id'] ?>)">
                                    <?= htmlspecialchars($cat['name']) ?>
                                </button>
                            <?php endwhile; ?>
                        </div>

                        <!-- Search -->
                        <div class="mb-3">
                            <input type="text" id="searchProduct" class="form-control" 
                                   placeholder="Cari produk..." onkeyup="searchProducts()">
                        </div>

                        <!-- Product Grid -->
                        <div class="product-grid" id="productGrid">
                            <?php while ($product = $products->fetch_assoc()): ?>
                                <div class="product-card" 
                                     data-category="<?= $product['category_id'] ?>"
                                     data-name="<?= strtolower($product['product_name']) ?>"
                                     onclick='addToCart(<?= json_encode($product) ?>)'>
                                    <?php if ($product['image'] && file_exists('uploads/products/' . $product['image'])): ?>
                                        <img src="uploads/products/<?= htmlspecialchars($product['image']) ?>" 
                                             class="product-image" 
                                             alt="<?= htmlspecialchars($product['product_name']) ?>"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                        <div class="product-image bg-light d-none align-items-center justify-content-center" style="display: none !important;">
                                            <i class="fas fa-image fa-2x text-muted"></i>
                                        </div>
                                    <?php else: ?>
                                        <div class="product-image bg-light d-flex align-items-center justify-content-center">
                                            <i class="fas fa-utensils fa-2x text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="fw-bold"><?= htmlspecialchars($product['product_name']) ?></div>
                                    <div class="text-success fw-bold">
                                        Rp <?= number_format($product['price'], 0, ',', '.') ?>
                                    </div>
                                    <small class="text-muted">Stok: <?= $product['stock'] ?></small>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Side - Cart -->
            <div class="col-md-4">
                <div class="cart-container">
                    <h5 class="mb-3"><i class="fas fa-shopping-cart"></i> Keranjang</h5>
                    
                    <!-- Member Selection -->
                    <div class="mb-3">
                        <label class="form-label">Member (Optional)</label>
                        <select class="form-select" id="memberId" onchange="updateMemberInfo()">
                            <option value="">Non-Member</option>
                            <?php while ($member = $members->fetch_assoc()): ?>
                                <option value="<?= $member['id'] ?>" data-points="<?= $member['points'] ?>">
                                    <?= htmlspecialchars($member['name']) ?> 
                                    (<?= $member['member_code'] ?>) - <?= $member['points'] ?> pts
                                </option>
                            <?php endwhile; ?>
                        </select>
                        <div id="memberInfo" class="mt-2"></div>
                    </div>

                    <!-- Cart Items -->
                    <div class="cart-items" id="cartItems">
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                            <p>Keranjang kosong</p>
                        </div>
                    </div>

                    <!-- Cart Summary -->
                    <div class="cart-summary">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <strong id="subtotal">Rp 0</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Pajak (<?= TAX_RATE ?>%):</span>
                            <strong id="tax">Rp 0</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Diskon:</span>
                            <input type="number" id="discount" class="form-control form-control-sm w-50 text-end" 
                                   value="0" min="0" onchange="calculateTotal()">
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="h5">TOTAL:</span>
                            <span class="total-display" id="total">Rp 0</span>
                        </div>

                        <!-- Payment Method -->
                        <div class="mb-3">
                            <label class="form-label">Metode Pembayaran</label>
                            <select class="form-select" id="paymentMethod" onchange="togglePaymentInput()">
                                <option value="cash">Tunai</option>
                                <option value="qris">QRIS</option>
                                <option value="transfer">Transfer</option>
                                <option value="debit">Kartu Debit</option>
                                <option value="credit">Kartu Kredit</option>
                            </select>
                        </div>

                        <!-- Payment Amount (for cash) -->
                        <div id="cashPayment" class="mb-3">
                            <label class="form-label">Jumlah Bayar</label>
                            <input type="number" id="paymentAmount" class="form-control" 
                                   min="0" onkeyup="calculateChange()">
                            <div id="change" class="mt-2"></div>
                        </div>

                        <!-- Notes -->
                        <div class="mb-3">
                            <label class="form-label">Catatan</label>
                            <textarea id="notes" class="form-control" rows="2"></textarea>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-grid gap-2">
                            <button class="btn btn-primary btn-lg" onclick="processPayment()" id="btnPay" disabled>
                                <i class="fas fa-check-circle"></i> Proses Pembayaran
                            </button>
                            <button class="btn btn-danger" onclick="clearCart()">
                                <i class="fas fa-trash"></i> Hapus Semua
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let cart = [];

// Add to cart
function addToCart(product) {
    const existingItem = cart.find(item => item.id === product.id);
    
    if (existingItem) {
        if (existingItem.quantity < product.stock) {
            existingItem.quantity++;
        } else {
            alert('Stok tidak mencukupi!');
            return;
        }
    } else {
        cart.push({
            id: product.id,
            product_name: product.product_name,
            price: parseFloat(product.price),
            quantity: 1,
            stock: product.stock
        });
    }
    
    updateCart();
}

// Update cart display
function updateCart() {
    const cartItemsDiv = document.getElementById('cartItems');
    
    if (cart.length === 0) {
        cartItemsDiv.innerHTML = `
            <div class="text-center text-muted py-5">
                <i class="fas fa-shopping-cart fa-3x mb-3"></i>
                <p>Keranjang kosong</p>
            </div>
        `;
        document.getElementById('btnPay').disabled = true;
    } else {
        let html = '';
        cart.forEach((item, index) => {
            html += `
                <div class="cart-item">
                    <div class="cart-item-info">
                        <strong>${item.product_name}</strong><br>
                        <small class="text-muted">Rp ${item.price.toLocaleString('id-ID')} × ${item.quantity}</small>
                        <br>
                        <small class="text-success fw-bold">
                            = Rp ${(item.price * item.quantity).toLocaleString('id-ID')}
                        </small>
                    </div>
                    <div class="qty-control">
                        <button class="qty-btn" onclick="updateQuantity(${index}, -1)" title="Kurangi">
                            −
                        </button>
                        <input type="number" 
                               class="qty-input"
                               value="${item.quantity}" 
                               min="1" 
                               max="${item.stock}"
                               onchange="setQuantity(${index}, this.value)"
                               onclick="this.select()">
                        <button class="qty-btn" onclick="updateQuantity(${index}, 1)" title="Tambah">
                            +
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="removeItem(${index})" title="Hapus">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
        });
        cartItemsDiv.innerHTML = html;
        document.getElementById('btnPay').disabled = false;
    }
    
    calculateTotal();
}

// Set quantity directly from input
function setQuantity(index, value) {
    const item = cart[index];
    const qty = parseInt(value) || 1;
    
    if (qty <= 0) {
        removeItem(index);
    } else if (qty <= item.stock) {
        item.quantity = qty;
        updateCart();
    } else {
        alert('Stok tidak mencukupi! Maksimal: ' + item.stock);
        updateCart(); // Reset to previous value
    }
}

// Update quantity
function updateQuantity(index, change) {
    const item = cart[index];
    const newQty = item.quantity + change;
    
    if (newQty <= 0) {
        removeItem(index);
    } else if (newQty <= item.stock) {
        item.quantity = newQty;
        updateCart();
    } else {
        alert('Stok tidak mencukupi!');
    }
}

// Remove item
function removeItem(index) {
    cart.splice(index, 1);
    updateCart();
}

// Clear cart
function clearCart() {
    if (confirm('Hapus semua item di keranjang?')) {
        cart = [];
        document.getElementById('discount').value = 0;
        document.getElementById('paymentAmount').value = '';
        updateCart();
    }
}

// Calculate total
function calculateTotal() {
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const tax = subtotal * (<?= TAX_RATE ?> / 100);
    const discount = parseFloat(document.getElementById('discount').value) || 0;
    const total = subtotal + tax - discount;
    
    document.getElementById('subtotal').textContent = 'Rp ' + subtotal.toLocaleString('id-ID');
    document.getElementById('tax').textContent = 'Rp ' + tax.toLocaleString('id-ID');
    document.getElementById('total').textContent = 'Rp ' + total.toLocaleString('id-ID');
    
    calculateChange();
}

// Calculate change
function calculateChange() {
    const total = parseFloat(document.getElementById('total').textContent.replace(/[^0-9]/g, ''));
    const payment = parseFloat(document.getElementById('paymentAmount').value) || 0;
    const change = payment - total;
    
    const changeDiv = document.getElementById('change');
    if (payment > 0) {
        if (change >= 0) {
            changeDiv.innerHTML = `<div class="alert alert-success">Kembalian: <strong>Rp ${change.toLocaleString('id-ID')}</strong></div>`;
        } else {
            changeDiv.innerHTML = `<div class="alert alert-danger">Uang kurang: Rp ${Math.abs(change).toLocaleString('id-ID')}</div>`;
        }
    } else {
        changeDiv.innerHTML = '';
    }
}

// Toggle payment input
function togglePaymentInput() {
    const method = document.getElementById('paymentMethod').value;
    const cashDiv = document.getElementById('cashPayment');
    
    if (method === 'cash') {
        cashDiv.style.display = 'block';
    } else {
        cashDiv.style.display = 'none';
    }
}

// Filter category
function filterCategory(categoryId) {
    const buttons = document.querySelectorAll('.category-btn');
    buttons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    const products = document.querySelectorAll('.product-card');
    products.forEach(product => {
        if (categoryId === 'all' || product.dataset.category == categoryId) {
            product.style.display = 'block';
        } else {
            product.style.display = 'none';
        }
    });
}

// Search products
function searchProducts() {
    const search = document.getElementById('searchProduct').value.toLowerCase();
    const products = document.querySelectorAll('.product-card');
    
    products.forEach(product => {
        const name = product.dataset.name;
        if (name.includes(search)) {
            product.style.display = 'block';
        } else {
            product.style.display = 'none';
        }
    });
}

// Update member info
function updateMemberInfo() {
    const select = document.getElementById('memberId');
    const option = select.options[select.selectedIndex];
    const infoDiv = document.getElementById('memberInfo');
    
    if (option.value) {
        const points = option.dataset.points;
        infoDiv.innerHTML = `<small class="text-info"><i class="fas fa-star"></i> Points: ${points}</small>`;
    } else {
        infoDiv.innerHTML = '';
    }
}

// Process payment
async function processPayment() {
    if (cart.length === 0) {
        alert('Keranjang kosong!');
        return;
    }
    
    const paymentMethod = document.getElementById('paymentMethod').value;
    const paymentAmount = parseFloat(document.getElementById('paymentAmount').value) || 0;
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const tax = subtotal * (<?= TAX_RATE ?> / 100);
    const discount = parseFloat(document.getElementById('discount').value) || 0;
    const total = subtotal + tax - discount;
    
    // Validasi payment untuk cash
    if (paymentMethod === 'cash' && paymentAmount < total) {
        alert('Jumlah pembayaran kurang!');
        return;
    }
    
    const data = {
        items: cart,
        subtotal: subtotal,
        tax: tax,
        discount: discount,
        total: total,
        payment_method: paymentMethod,
        payment_amount: paymentMethod === 'cash' ? paymentAmount : total,
        change_amount: paymentMethod === 'cash' ? (paymentAmount - total) : 0,
        member_id: document.getElementById('memberId').value || null,
        notes: document.getElementById('notes').value
    };
    
    try {
        const response = await fetch('api/process_transaction.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('Transaksi berhasil!\nKode: ' + result.transaction_code);
            
            // Print receipt (optional)
            if (confirm('Print struk?')) {
                window.open('print_receipt.php?id=' + result.transaction_id, '_blank');
            }
            
            // Clear cart
            cart = [];
            document.getElementById('discount').value = 0;
            document.getElementById('paymentAmount').value = '';
            document.getElementById('notes').value = '';
            document.getElementById('memberId').value = '';
            updateCart();
        } else {
            alert('Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat memproses transaksi');
    }
}
</script>

<?php include 'footer.php'; ?>