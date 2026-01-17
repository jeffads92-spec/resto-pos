<?php
session_start();
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Get date range
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Set headers for Excel download
header('Content-Type: application/vnd.ms-excel');
header('Content-Disposition: attachment;filename="Laporan_Keuangan_' . $start_date . '_to_' . $end_date . '.xls"');
header('Cache-Control: max-age=0');

// Get transactions data
$query = "SELECT t.invoice_number, t.created_at, u.username as cashier, m.member_name,
          t.subtotal, t.discount_amount, t.tax_amount, t.total_amount, t.payment_method
          FROM transactions t
          LEFT JOIN users u ON t.user_id = u.id
          LEFT JOIN members m ON t.member_id = m.id
          WHERE DATE(t.created_at) BETWEEN '$start_date' AND '$end_date'
          AND t.status = 'completed'
          ORDER BY t.created_at ASC";
$result = mysqli_query($conn, $query);

// Summary calculations
$summary_query = "SELECT 
                  COUNT(*) as total_transactions,
                  SUM(total_amount) as total_sales,
                  SUM(tax_amount) as total_tax,
                  SUM(discount_amount) as total_discount
                  FROM transactions 
                  WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date'
                  AND status = 'completed'";
$summary_result = mysqli_query($conn, $summary_query);
$summary = mysqli_fetch_assoc($summary_result);

// Get HPP (Cost)
$cost_query = "SELECT SUM(ti.quantity * p.cost_price) as total_cost
               FROM transaction_items ti
               JOIN products p ON ti.product_id = p.id
               JOIN transactions t ON ti.transaction_id = t.id
               WHERE DATE(t.created_at) BETWEEN '$start_date' AND '$end_date'
               AND t.status = 'completed'";
$cost_result = mysqli_query($conn, $cost_query);
$cost_data = mysqli_fetch_assoc($cost_result);

// Get Expenses
$expense_query = "SELECT SUM(amount) as total_expenses
                  FROM expenses
                  WHERE DATE(expense_date) BETWEEN '$start_date' AND '$end_date'";
$expense_result = mysqli_query($conn, $expense_query);
$expense_data = mysqli_fetch_assoc($expense_result);

$total_sales = $summary['total_sales'] ?? 0;
$total_cost = $cost_data['total_cost'] ?? 0;
$total_expenses = $expense_data['total_expenses'] ?? 0;
$gross_profit = $total_sales - $total_cost;
$net_profit = $gross_profit - $total_expenses;

// Output Excel content
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid black; padding: 8px; text-align: left; }
        th { background-color: #4CAF50; color: white; }
        .summary { background-color: #f2f2f2; font-weight: bold; }
    </style>
</head>
<body>
    <h2>LAPORAN KEUANGAN SMART RESTO POS</h2>
    <p>Periode: <?php echo date('d/m/Y', strtotime($start_date)); ?> s/d <?php echo date('d/m/Y', strtotime($end_date)); ?></p>
    
    <h3>RINGKASAN KEUANGAN</h3>
    <table>
        <tr>
            <th>Keterangan</th>
            <th>Jumlah</th>
        </tr>
        <tr>
            <td>Total Transaksi</td>
            <td><?php echo $summary['total_transactions']; ?> transaksi</td>
        </tr>
        <tr>
            <td>Total Penjualan</td>
            <td>Rp <?php echo number_format($total_sales, 0, ',', '.'); ?></td>
        </tr>
        <tr>
            <td>Total Diskon</td>
            <td>Rp <?php echo number_format($summary['total_discount'], 0, ',', '.'); ?></td>
        </tr>
        <tr>
            <td>Total Pajak</td>
            <td>Rp <?php echo number_format($summary['total_tax'], 0, ',', '.'); ?></td>
        </tr>
        <tr>
            <td>HPP (Cost of Goods Sold)</td>
            <td>Rp <?php echo number_format($total_cost, 0, ',', '.'); ?></td>
        </tr>
        <tr>
            <td>Laba Kotor</td>
            <td>Rp <?php echo number_format($gross_profit, 0, ',', '.'); ?></td>
        </tr>
        <tr>
            <td>Pengeluaran Operasional</td>
            <td>Rp <?php echo number_format($total_expenses, 0, ',', '.'); ?></td>
        </tr>
        <tr class="summary">
            <td>LABA BERSIH</td>
            <td>Rp <?php echo number_format($net_profit, 0, ',', '.'); ?></td>
        </tr>
    </table>
    
    <br><br>
    
    <h3>DETAIL TRANSAKSI</h3>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Invoice</th>
                <th>Tanggal</th>
                <th>Kasir</th>
                <th>Member</th>
                <th>Subtotal</th>
                <th>Diskon</th>
                <th>Pajak</th>
                <th>Total</th>
                <th>Pembayaran</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $no = 1;
            $total_row_subtotal = 0;
            $total_row_discount = 0;
            $total_row_tax = 0;
            $total_row_amount = 0;
            
            while ($row = mysqli_fetch_assoc($result)): 
                $total_row_subtotal += $row['subtotal'];
                $total_row_discount += $row['discount_amount'];
                $total_row_tax += $row['tax_amount'];
                $total_row_amount += $row['total_amount'];
            ?>
            <tr>
                <td><?php echo $no++; ?></td>
                <td><?php echo $row['invoice_number']; ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                <td><?php echo $row['cashier']; ?></td>
                <td><?php echo $row['member_name'] ?? '-'; ?></td>
                <td>Rp <?php echo number_format($row['subtotal'], 0, ',', '.'); ?></td>
                <td>Rp <?php echo number_format($row['discount_amount'], 0, ',', '.'); ?></td>
                <td>Rp <?php echo number_format($row['tax_amount'], 0, ',', '.'); ?></td>
                <td>Rp <?php echo number_format($row['total_amount'], 0, ',', '.'); ?></td>
                <td><?php echo strtoupper($row['payment_method']); ?></td>
            </tr>
            <?php endwhile; ?>
            <tr class="summary">
                <td colspan="5">TOTAL</td>
                <td>Rp <?php echo number_format($total_row_subtotal, 0, ',', '.'); ?></td>
                <td>Rp <?php echo number_format($total_row_discount, 0, ',', '.'); ?></td>
                <td>Rp <?php echo number_format($total_row_tax, 0, ',', '.'); ?></td>
                <td>Rp <?php echo number_format($total_row_amount, 0, ',', '.'); ?></td>
                <td></td>
            </tr>
        </tbody>
    </table>
    
    <br><br>
    <p><em>Dicetak pada: <?php echo date('d/m/Y H:i:s'); ?></em></p>
</body>
</html>
<?php
mysqli_close($conn);
?>
