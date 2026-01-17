<?php
// ============================================
// API: Export Excel
// File: api/export_excel.php
// ============================================

session_start();
require_once '../config.php';

// Cek login dan role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    die('Unauthorized');
}

$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Query data
$query = "
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

$stmt = $conn->prepare($query);
$stmt->bind_param("ss", $start_date, $end_date);
$stmt->execute();
$result = $stmt->get_result();

// Set header untuk download
$filename = "Laporan_Penjualan_" . date('Y-m-d_His') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Output CSV
$output = fopen('php://output', 'w');

// Header CSV
fputcsv($output, ['LAPORAN PENJUALAN']);
fputcsv($output, ['Periode: ' . date('d/m/Y', strtotime($start_date)) . ' - ' . date('d/m/Y', strtotime($end_date))]);
fputcsv($output, []);
fputcsv($output, ['Tanggal', 'Jumlah Transaksi', 'Subtotal', 'Pajak', 'Diskon', 'Total Penjualan']);

// Data
$grand_total = 0;
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        date('d/m/Y', strtotime($row['date'])),
        $row['total_transactions'],
        number_format($row['subtotal'], 0, ',', '.'),
        number_format($row['total_tax'], 0, ',', '.'),
        number_format($row['total_discount'], 0, ',', '.'),
        number_format($row['total_sales'], 0, ',', '.')
    ]);
    $grand_total += $row['total_sales'];
}

// Total
fputcsv($output, []);
fputcsv($output, ['GRAND TOTAL', '', '', '', '', number_format($grand_total, 0, ',', '.')]);

fclose($output);
$stmt->close();
$conn->close();
?>