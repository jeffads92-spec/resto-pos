<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Check if user is admin
if ($_SESSION['role'] != 'admin') {
    die('Access denied');
}

// Get parameters
$report_type = $_GET['type'] ?? 'sales';
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Set filename
$filename = "Laporan_" . ucfirst($report_type) . "_" . date('Y-m-d_His') . ".xls";

// Set headers for Excel download
header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Add UTF-8 BOM for proper Excel encoding
echo "\xEF\xBB\xBF";

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            font-family: Arial, sans-serif;
        }
        
        .header {
            background-color: #667eea;
            color: white;
            font-weight: bold;
            text-align: center;
            padding: 15px;
            font-size: 18px;
        }
        
        .info-section {
            margin: 20px 0;
            padding: 10px;
            background-color: #f8f9fa;
        }
        
        .info-row {
            padding: 5px 0;
        }
        
        .info-label {
            font-weight: bold;
            width: 150px;
            display: inline-block;
        }
        
        th {
            background-color: #667eea;
            color: white;
            font-weight: bold;
            padding: 12px 8px;
            text-align: left;
            border: 1px solid #5568d3;
        }
        
        td {
            padding: 10px 8px;
            border: 1px solid #dee2e6;
            text-align: left;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .total-row {
            background-color: #e7f3ff;
            font-weight: bold;
        }
        
        .summary-section {
            margin-top: 30px;
            padding: 15px;
            background-color: #e7f3ff;
        }
        
        .summary-item {
            padding: 8px 0;
            font-size: 14px;
        }
        
        .summary-label {
            font-weight: bold;
            width: 200px;
            display: inline-block;
        }
        
        .summary-value {
            font-weight: bold;
            color: #667eea;
        }
        
        .footer {
            margin-top: 30px;
            padding: 15px;
            text-align: center;
            font-size: 12px;
            color: #6c757d;
        }
    </style>
</head>
<body>

<?php if ($report_type == 'sales'): ?>
    
    <!-- SALES REPORT -->
    <div class="header">
        📊 LAPORAN PENJUALAN<br>
        <?= date('d F Y', strtotime($start_date)) ?> - <?= date('d F Y', strtotime($end_date)) ?>
    </div>
    
    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Nama Toko:</span>
            <?= defined('APP_NAME') ? APP_NAME : 'Smart Resto POS' ?>
        </div>
        <div class="info-row">
            <span class="info-label">Periode:</span>
            <?= date('d/m/Y', strtotime($start_date)) ?> s/d <?= date('d/m/Y', strtotime($end_date)) ?>
        </div>
        <div class="info-row">
            <span class="info-label">Tanggal Cetak:</span>
            <?= date('d F Y H:i:s') ?>
        </div>
        <div class="info-row">
            <span class="info-label">Dicetak Oleh:</span>
            <?= htmlspecialchars($_SESSION['username']) ?>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th style="width: 50px;">No</th>
                <th style="width: 150px;">Tanggal</th>
                <th style="width: 120px;">Invoice</th>
                <th style="width: 150px;">Kasir</th>
                <th style="width: 150px;">Member</th>
                <th style="width: 100px; text-align: right;">Subtotal</th>
                <th style="width: 80px; text-align: right;">Diskon</th>
                <th style="width: 80px; text-align: right;">Pajak</th>
                <th style="width: 100px; text-align: right;">Total</th>
                <th style="width: 100px;">Pembayaran</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $query = "SELECT t.*, u.username, m.name as member_name 
                     FROM transactions t
                     LEFT JOIN users u ON t.user_id = u.id
                     LEFT JOIN members m ON t.member_id = m.id
                     WHERE DATE(t.created_at) BETWEEN '$start_date' AND '$end_date'
                     ORDER BY t.created_at DESC";
            
            $result = $conn->query($query);
            $no = 1;
            $grand_total = 0;
            $grand_subtotal = 0;
            $grand_discount = 0;
            $grand_tax = 0;
            
            while($row = $result->fetch_assoc()):
                $grand_subtotal += $row['subtotal'];
                $grand_discount += $row['discount'];
                $grand_tax += $row['tax'];
                $grand_total += $row['total'];
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                <td><?= htmlspecialchars($row['invoice_number']) ?></td>
                <td><?= htmlspecialchars($row['username']) ?></td>
                <td><?= $row['member_name'] ? htmlspecialchars($row['member_name']) : '-' ?></td>
                <td class="text-right">Rp <?= number_format($row['subtotal'], 0, ',', '.') ?></td>
                <td class="text-right">Rp <?= number_format($row['discount'], 0, ',', '.') ?></td>
                <td class="text-right">Rp <?= number_format($row['tax'], 0, ',', '.') ?></td>
                <td class="text-right">Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
                <td><?= htmlspecialchars($row['payment_method']) ?></td>
            </tr>
            <?php endwhile; ?>
            
            <tr class="total-row">
                <td colspan="5" class="text-right"><strong>TOTAL:</strong></td>
                <td class="text-right"><strong>Rp <?= number_format($grand_subtotal, 0, ',', '.') ?></strong></td>
                <td class="text-right"><strong>Rp <?= number_format($grand_discount, 0, ',', '.') ?></strong></td>
                <td class="text-right"><strong>Rp <?= number_format($grand_tax, 0, ',', '.') ?></strong></td>
                <td class="text-right"><strong>Rp <?= number_format($grand_total, 0, ',', '.') ?></strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>
    
    <div class="summary-section">
        <h3 style="margin-top: 0;">📈 RINGKASAN PENJUALAN</h3>
        <div class="summary-item">
            <span class="summary-label">Total Transaksi:</span>
            <span class="summary-value"><?= ($no - 1) ?> transaksi</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Total Penjualan Kotor:</span>
            <span class="summary-value">Rp <?= number_format($grand_subtotal, 0, ',', '.') ?></span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Total Diskon:</span>
            <span class="summary-value">Rp <?= number_format($grand_discount, 0, ',', '.') ?></span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Total Pajak:</span>
            <span class="summary-value">Rp <?= number_format($grand_tax, 0, ',', '.') ?></span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Total Penjualan Bersih:</span>
            <span class="summary-value">Rp <?= number_format($grand_total, 0, ',', '.') ?></span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Rata-rata per Transaksi:</span>
            <span class="summary-value">Rp <?= number_format(($no > 1) ? ($grand_total / ($no - 1)) : 0, 0, ',', '.') ?></span>
        </div>
    </div>

<?php elseif ($report_type == 'products'): ?>
    
    <!-- PRODUCTS REPORT -->
    <div class="header">
        📦 LAPORAN PRODUK TERLARIS<br>
        <?= date('d F Y', strtotime($start_date)) ?> - <?= date('d F Y', strtotime($end_date)) ?>
    </div>
    
    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Periode:</span>
            <?= date('d/m/Y', strtotime($start_date)) ?> s/d <?= date('d/m/Y', strtotime($end_date)) ?>
        </div>
        <div class="info-row">
            <span class="info-label">Tanggal Cetak:</span>
            <?= date('d F Y H:i:s') ?>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th style="width: 50px;">No</th>
                <th style="width: 200px;">Nama Produk</th>
                <th style="width: 150px;">Kategori</th>
                <th style="width: 100px; text-align: right;">Harga Satuan</th>
                <th style="width: 80px; text-align: right;">Qty Terjual</th>
                <th style="width: 120px; text-align: right;">Total Revenue</th>
                <th style="width: 80px; text-align: right;">Stok Saat Ini</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $query = "SELECT p.name, p.price, p.stock, c.name as category_name,
                     SUM(ti.quantity) as qty_sold,
                     SUM(ti.subtotal) as revenue
                     FROM transaction_items ti
                     JOIN products p ON ti.product_id = p.id
                     LEFT JOIN categories c ON p.category_id = c.id
                     JOIN transactions t ON ti.transaction_id = t.id
                     WHERE DATE(t.created_at) BETWEEN '$start_date' AND '$end_date'
                     GROUP BY p.id
                     ORDER BY qty_sold DESC";
            
            $result = $conn->query($query);
            $no = 1;
            $total_qty = 0;
            $total_revenue = 0;
            
            while($row = $result->fetch_assoc()):
                $total_qty += $row['qty_sold'];
                $total_revenue += $row['revenue'];
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['category_name']) ?></td>
                <td class="text-right">Rp <?= number_format($row['price'], 0, ',', '.') ?></td>
                <td class="text-right"><?= number_format($row['qty_sold'], 0, ',', '.') ?></td>
                <td class="text-right">Rp <?= number_format($row['revenue'], 0, ',', '.') ?></td>
                <td class="text-right"><?= number_format($row['stock'], 0, ',', '.') ?></td>
            </tr>
            <?php endwhile; ?>
            
            <tr class="total-row">
                <td colspan="4" class="text-right"><strong>TOTAL:</strong></td>
                <td class="text-right"><strong><?= number_format($total_qty, 0, ',', '.') ?></strong></td>
                <td class="text-right"><strong>Rp <?= number_format($total_revenue, 0, ',', '.') ?></strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>
    
    <div class="summary-section">
        <h3 style="margin-top: 0;">📊 RINGKASAN PRODUK</h3>
        <div class="summary-item">
            <span class="summary-label">Total Item Terjual:</span>
            <span class="summary-value"><?= number_format($total_qty, 0, ',', '.') ?> unit</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Total Revenue Produk:</span>
            <span class="summary-value">Rp <?= number_format($total_revenue, 0, ',', '.') ?></span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Jumlah Produk Berbeda:</span>
            <span class="summary-value"><?= ($no - 1) ?> produk</span>
        </div>
    </div>

<?php elseif ($report_type == 'stock'): ?>
    
    <!-- STOCK REPORT -->
    <div class="header">
        📊 LAPORAN STOK PRODUK<br>
        Per Tanggal: <?= date('d F Y') ?>
    </div>
    
    <div class="info-section">
        <div class="info-row">
            <span class="info-label">Tanggal Cetak:</span>
            <?= date('d F Y H:i:s') ?>
        </div>
    </div>
    
    <table>
        <thead>
            <tr>
                <th style="width: 50px;">No</th>
                <th style="width: 200px;">Nama Produk</th>
                <th style="width: 150px;">Kategori</th>
                <th style="width: 100px; text-align: right;">Harga Modal</th>
                <th style="width: 100px; text-align: right;">Harga Jual</th>
                <th style="width: 80px; text-align: right;">Stok</th>
                <th style="width: 80px; text-align: right;">Stok Min</th>
                <th style="width: 120px; text-align: right;">Nilai Stok</th>
                <th style="width: 100px; text-align: center;">Status</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $query = "SELECT p.*, c.name as category_name
                     FROM products p
                     LEFT JOIN categories c ON p.category_id = c.id
                     ORDER BY c.name, p.name";
            
            $result = $conn->query($query);
            $no = 1;
            $total_stock_value = 0;
            
            while($row = $result->fetch_assoc()):
                $stock_value = $row['stock'] * $row['cost'];
                $total_stock_value += $stock_value;
                $status = $row['stock'] <= $row['stock_min'] ? 'MENIPIS' : ($row['stock'] == 0 ? 'HABIS' : 'AMAN');
            ?>
            <tr>
                <td class="text-center"><?= $no++ ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['category_name']) ?></td>
                <td class="text-right">Rp <?= number_format($row['cost'], 0, ',', '.') ?></td>
                <td class="text-right">Rp <?= number_format($row['price'], 0, ',', '.') ?></td>
                <td class="text-right"><?= number_format($row['stock'], 0, ',', '.') ?></td>
                <td class="text-right"><?= number_format($row['stock_min'], 0, ',', '.') ?></td>
                <td class="text-right">Rp <?= number_format($stock_value, 0, ',', '.') ?></td>
                <td class="text-center"><?= $status ?></td>
            </tr>
            <?php endwhile; ?>
            
            <tr class="total-row">
                <td colspan="7" class="text-right"><strong>TOTAL NILAI STOK:</strong></td>
                <td class="text-right"><strong>Rp <?= number_format($total_stock_value, 0, ',', '.') ?></strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>
    
    <div class="summary-section">
        <h3 style="margin-top: 0;">📦 RINGKASAN STOK</h3>
        <div class="summary-item">
            <span class="summary-label">Total Produk:</span>
            <span class="summary-value"><?= ($no - 1) ?> produk</span>
        </div>
        <div class="summary-item">
            <span class="summary-label">Total Nilai Stok (Modal):</span>
            <span class="summary-value">Rp <?= number_format($total_stock_value, 0, ',', '.') ?></span>
        </div>
    </div>

<?php endif; ?>

    <div class="footer">
        Generated by <?= defined('APP_NAME') ? APP_NAME : 'Smart Resto POS' ?> | 
        <?= date('d F Y H:i:s') ?>
    </div>

</body>
</html>
