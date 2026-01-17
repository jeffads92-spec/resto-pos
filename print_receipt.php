<?php
// ============================================
// PRINT RECEIPT
// File: print_receipt.php
// ============================================

require_once 'config.php';

// Get transaction ID
$transaction_id = $_GET['id'] ?? 0;

if (!$transaction_id) {
    die('Transaction ID tidak valid');
}

// Get transaction data (FIXED: menggunakan transaction_items bukan transaction_details)
$query = "
    SELECT 
        t.*,
        m.name as member_name,
        m.member_code,
        u.full_name as cashier_name
    FROM transactions t
    LEFT JOIN members m ON t.member_id = m.id
    LEFT JOIN users u ON t.cashier_id = u.id
    WHERE t.id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $transaction_id);
$stmt->execute();
$transaction = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$transaction) {
    die('Transaksi tidak ditemukan');
}

// Get transaction items (FIXED: menggunakan transaction_items)
$items_query = "
    SELECT * FROM transaction_items 
    WHERE transaction_id = ? 
    ORDER BY id
";
$stmt = $conn->prepare($items_query);
$stmt->bind_param("i", $transaction_id);
$stmt->execute();
$items = $stmt->get_result();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Struk - <?= $transaction['transaction_code'] ?></title>
    <style>
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
        
        body {
            font-family: 'Courier New', monospace;
            width: 80mm;
            margin: 0 auto;
            padding: 10px;
            background: white;
        }
        
        .receipt {
            width: 100%;
        }
        
        .header {
            text-align: center;
            margin-bottom: 10px;
            border-bottom: 2px dashed #000;
            padding-bottom: 10px;
        }
        
        .header h2 {
            margin: 5px 0;
            font-size: 18px;
        }
        
        .header p {
            margin: 2px 0;
            font-size: 12px;
        }
        
        .info {
            margin: 10px 0;
            font-size: 12px;
        }
        
        .info div {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
            font-size: 12px;
        }
        
        table th {
            text-align: left;
            border-bottom: 1px solid #000;
            padding: 5px 0;
        }
        
        table td {
            padding: 5px 0;
        }
        
        .text-right {
            text-align: right;
        }
        
        .summary {
            margin-top: 10px;
            border-top: 1px dashed #000;
            padding-top: 10px;
            font-size: 12px;
        }
        
        .summary div {
            display: flex;
            justify-content: space-between;
            margin: 3px 0;
        }
        
        .total {
            font-size: 14px;
            font-weight: bold;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 5px 0;
            margin: 5px 0;
        }
        
        .footer {
            text-align: center;
            margin-top: 15px;
            border-top: 2px dashed #000;
            padding-top: 10px;
            font-size: 11px;
        }
        
        .no-print {
            text-align: center;
            margin: 20px 0;
        }
        
        @page {
            size: 80mm auto;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="receipt">
        <!-- Header -->
        <div class="header">
            <h2><?= APP_NAME ?></h2>
            <p>Jl. Contoh No. 123, Jakarta</p>
            <p>Telp: 021-12345678</p>
            <p>www.restopos.com</p>
        </div>

        <!-- Transaction Info -->
        <div class="info">
            <div>
                <span>No. Transaksi:</span>
                <strong><?= $transaction['transaction_code'] ?></strong>
            </div>
            <div>
                <span>Tanggal:</span>
                <span><?= date('d/m/Y H:i', strtotime($transaction['transaction_date'])) ?></span>
            </div>
            <div>
                <span>Kasir:</span>
                <span><?= htmlspecialchars($transaction['cashier_name']) ?></span>
            </div>
            <?php if ($transaction['member_name']): ?>
                <div>
                    <span>Member:</span>
                    <span><?= htmlspecialchars($transaction['member_name']) ?> (<?= $transaction['member_code'] ?>)</span>
                </div>
            <?php endif; ?>
        </div>

        <!-- Items Table -->
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Harga</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($item = $items->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                        <td class="text-right"><?= $item['quantity'] ?></td>
                        <td class="text-right"><?= number_format($item['price'], 0, ',', '.') ?></td>
                        <td class="text-right"><?= number_format($item['subtotal'], 0, ',', '.') ?></td>
                    </tr>
                    <?php if ($item['notes']): ?>
                        <tr>
                            <td colspan="4" style="font-style: italic; font-size: 10px; padding-left: 10px;">
                                Note: <?= htmlspecialchars($item['notes']) ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- Summary -->
        <div class="summary">
            <div>
                <span>Subtotal:</span>
                <span>Rp <?= number_format($transaction['subtotal'], 0, ',', '.') ?></span>
            </div>
            <div>
                <span>Pajak (<?= TAX_RATE ?>%):</span>
                <span>Rp <?= number_format($transaction['tax'], 0, ',', '.') ?></span>
            </div>
            <?php if ($transaction['discount'] > 0): ?>
                <div>
                    <span>Diskon:</span>
                    <span>- Rp <?= number_format($transaction['discount'], 0, ',', '.') ?></span>
                </div>
            <?php endif; ?>
            <div class="total">
                <span>TOTAL:</span>
                <span>Rp <?= number_format($transaction['total'], 0, ',', '.') ?></span>
            </div>
            <div>
                <span>Bayar (<?= ucfirst($transaction['payment_method']) ?>):</span>
                <span>Rp <?= number_format($transaction['payment_amount'], 0, ',', '.') ?></span>
            </div>
            <?php if ($transaction['payment_method'] === 'cash'): ?>
                <div>
                    <span>Kembalian:</span>
                    <span>Rp <?= number_format($transaction['change_amount'], 0, ',', '.') ?></span>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($transaction['notes']): ?>
            <div style="margin-top: 10px; font-size: 11px; font-style: italic;">
                Catatan: <?= htmlspecialchars($transaction['notes']) ?>
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="footer">
            <p>Terima kasih atas kunjungan Anda!</p>
            <p>Barang yang sudah dibeli tidak dapat dikembalikan</p>
            <p>==================================</p>
            <p><?= APP_NAME ?> v<?= APP_VERSION ?></p>
            <p><?= date('d/m/Y H:i:s') ?></p>
        </div>
    </div>

    <!-- Print Button -->
    <div class="no-print">
        <button onclick="window.print()" style="padding: 10px 30px; font-size: 16px; cursor: pointer; background: #667eea; color: white; border: none; border-radius: 5px;">
            <i class="fas fa-print"></i> Print Struk
        </button>
        <button onclick="window.close()" style="padding: 10px 30px; font-size: 16px; cursor: pointer; background: #dc3545; color: white; border: none; border-radius: 5px; margin-left: 10px;">
            <i class="fas fa-times"></i> Tutup
        </button>
    </div>

    <script>
        // Auto print on load (optional)
        // window.onload = function() {
        //     window.print();
        // }
    </script>
</body>
</html>
