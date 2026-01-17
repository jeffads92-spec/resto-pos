<?php
// ============================================
// EXPENSES PAGE (FIXED)
// File: expenses.php
// Error Fixed: Unknown column 'e.date' → menggunakan 'e.expense_date'
// ============================================

session_start();
require_once 'config.php';

// Cek login dan role
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    die('Akses ditolak. Hanya admin yang dapat mengakses halaman ini.');
}

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'add') {
            // Add expense
            $stmt = $conn->prepare("
                INSERT INTO expenses (
                    expense_date, 
                    category, 
                    description, 
                    amount, 
                    payment_method, 
                    user_id, 
                    receipt_number, 
                    notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $expense_date = $_POST['expense_date'];
            $category = $_POST['category'];
            $description = $_POST['description'];
            $amount = floatval($_POST['amount']);
            $payment_method = $_POST['payment_method'];
            $user_id = $_SESSION['user_id'];
            $receipt_number = $_POST['receipt_number'] ?? '';
            $notes = $_POST['notes'] ?? '';
            
            $stmt->bind_param(
                "sssdssss",
                $expense_date,
                $category,
                $description,
                $amount,
                $payment_method,
                $user_id,
                $receipt_number,
                $notes
            );
            
            $stmt->execute();
            $_SESSION['success'] = 'Pengeluaran berhasil ditambahkan';
            
        } elseif ($action === 'edit') {
            // Edit expense
            $stmt = $conn->prepare("
                UPDATE expenses 
                SET expense_date = ?, 
                    category = ?, 
                    description = ?, 
                    amount = ?, 
                    payment_method = ?, 
                    receipt_number = ?, 
                    notes = ?
                WHERE id = ?
            ");
            
            $expense_date = $_POST['expense_date'];
            $category = $_POST['category'];
            $description = $_POST['description'];
            $amount = floatval($_POST['amount']);
            $payment_method = $_POST['payment_method'];
            $receipt_number = $_POST['receipt_number'] ?? '';
            $notes = $_POST['notes'] ?? '';
            $id = intval($_POST['id']);
            
            $stmt->bind_param(
                "sssdsssi",
                $expense_date,
                $category,
                $description,
                $amount,
                $payment_method,
                $receipt_number,
                $notes,
                $id
            );
            
            $stmt->execute();
            $_SESSION['success'] = 'Pengeluaran berhasil diupdate';
            
        } elseif ($action === 'delete') {
            // Delete expense
            $stmt = $conn->prepare("DELETE FROM expenses WHERE id = ?");
            $id = intval($_POST['id']);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $_SESSION['success'] = 'Pengeluaran berhasil dihapus';
        }
        
        $stmt->close();
        header('Location: expenses.php');
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error: ' . $e->getMessage();
    }
}

// Get filter parameters
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');
$category_filter = $_GET['category'] ?? '';

// Query expenses (FIXED: menggunakan expense_date, bukan date)
$query = "
    SELECT 
        e.*, 
        u.full_name as user_name 
    FROM expenses e 
    LEFT JOIN users u ON e.user_id = u.id 
    WHERE e.expense_date BETWEEN ? AND ?
";

$params = [$start_date, $end_date];
$types = "ss";

if ($category_filter) {
    $query .= " AND e.category = ?";
    $params[] = $category_filter;
    $types .= "s";
}

$query .= " ORDER BY e.expense_date DESC, e.id DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Get total expenses
$total_query = "
    SELECT SUM(amount) as total 
    FROM expenses 
    WHERE expense_date BETWEEN ? AND ?
";

if ($category_filter) {
    $total_query .= " AND category = ?";
}

$stmt_total = $conn->prepare($total_query);
$stmt_total->bind_param($types, ...$params);
$stmt_total->execute();
$total_result = $stmt_total->get_result();
$total_expenses = $total_result->fetch_assoc()['total'] ?? 0;

// Get categories untuk filter
$categories_query = "SELECT DISTINCT category FROM expenses ORDER BY category";
$categories_result = $conn->query($categories_query);

include 'header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2><i class="fas fa-money-bill-wave"></i> Manajemen Pengeluaran</h2>
        </div>
        <div class="col-md-6 text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
                <i class="fas fa-plus"></i> Tambah Pengeluaran
            </button>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Summary Card -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <h5 class="card-title mb-0">Total Pengeluaran</h5>
                    <h2 class="mb-0">Rp <?= number_format($total_expenses, 0, ',', '.') ?></h2>
                    <small>Periode: <?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?></small>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
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
                <div class="col-md-4">
                    <label class="form-label">Kategori</label>
                    <select name="category" class="form-select">
                        <option value="">Semua Kategori</option>
                        <?php while ($cat = $categories_result->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($cat['category']) ?>" 
                                    <?= $category_filter === $cat['category'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['category']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Expenses Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>Kategori</th>
                            <th>Deskripsi</th>
                            <th>Jumlah</th>
                            <th>Metode Bayar</th>
                            <th>No. Bukti</th>
                            <th>Input By</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows == 0): ?>
                            <tr>
                                <td colspan="8" class="text-center">Tidak ada data pengeluaran</td>
                            </tr>
                        <?php else: ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($row['expense_date'])) ?></td>
                                    <td>
                                        <span class="badge bg-info"><?= htmlspecialchars($row['category']) ?></span>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($row['description']) ?>
                                        <?php if ($row['notes']): ?>
                                            <br><small class="text-muted">
                                                <i class="fas fa-sticky-note"></i> 
                                                <?= htmlspecialchars($row['notes']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong class="text-danger">
                                            Rp <?= number_format($row['amount'], 0, ',', '.') ?>
                                        </strong>
                                    </td>
                                    <td><?= ucfirst($row['payment_method']) ?></td>
                                    <td><?= htmlspecialchars($row['receipt_number'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($row['user_name'] ?? '-') ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" 
                                                onclick="editExpense(<?= htmlspecialchars(json_encode($row)) ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" 
                                                onclick="deleteExpense(<?= $row['id'] ?>, '<?= htmlspecialchars($row['description']) ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-dark">
                            <td colspan="3" class="text-end"><strong>TOTAL:</strong></td>
                            <td colspan="5">
                                <strong class="text-danger">
                                    Rp <?= number_format($total_expenses, 0, ',', '.') ?>
                                </strong>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Add Expense -->
<div class="modal fade" id="addExpenseModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Pengeluaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tanggal *</label>
                        <input type="date" name="expense_date" class="form-control" 
                               value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori *</label>
                        <select name="category" class="form-select" required>
                            <option value="">Pilih Kategori</option>
                            <option value="Bahan Baku">Bahan Baku</option>
                            <option value="Gaji Karyawan">Gaji Karyawan</option>
                            <option value="Listrik">Listrik</option>
                            <option value="Air">Air</option>
                            <option value="Gas">Gas</option>
                            <option value="Transportasi">Transportasi</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Promosi">Promosi</option>
                            <option value="Lain-lain">Lain-lain</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi *</label>
                        <textarea name="description" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jumlah *</label>
                        <input type="number" name="amount" class="form-control" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Metode Pembayaran *</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="cash">Tunai</option>
                            <option value="transfer">Transfer</option>
                            <option value="debit">Debit</option>
                            <option value="credit">Kredit</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">No. Bukti</label>
                        <input type="text" name="receipt_number" class="form-control" 
                               placeholder="Nomor invoice/kwitansi">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Edit Expense -->
<div class="modal fade" id="editExpenseModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Pengeluaran</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tanggal *</label>
                        <input type="date" name="expense_date" id="edit_date" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori *</label>
                        <select name="category" id="edit_category" class="form-select" required>
                            <option value="Bahan Baku">Bahan Baku</option>
                            <option value="Gaji Karyawan">Gaji Karyawan</option>
                            <option value="Listrik">Listrik</option>
                            <option value="Air">Air</option>
                            <option value="Gas">Gas</option>
                            <option value="Transportasi">Transportasi</option>
                            <option value="Maintenance">Maintenance</option>
                            <option value="Promosi">Promosi</option>
                            <option value="Lain-lain">Lain-lain</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi *</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Jumlah *</label>
                        <input type="number" name="amount" id="edit_amount" class="form-control" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Metode Pembayaran *</label>
                        <select name="payment_method" id="edit_payment" class="form-select" required>
                            <option value="cash">Tunai</option>
                            <option value="transfer">Transfer</option>
                            <option value="debit">Debit</option>
                            <option value="credit">Kredit</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">No. Bukti</label>
                        <input type="text" name="receipt_number" id="edit_receipt" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan</label>
                        <textarea name="notes" id="edit_notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editExpense(data) {
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_date').value = data.expense_date;
    document.getElementById('edit_category').value = data.category;
    document.getElementById('edit_description').value = data.description;
    document.getElementById('edit_amount').value = data.amount;
    document.getElementById('edit_payment').value = data.payment_method;
    document.getElementById('edit_receipt').value = data.receipt_number || '';
    document.getElementById('edit_notes').value = data.notes || '';
    
    new bootstrap.Modal(document.getElementById('editExpenseModal')).show();
}

function deleteExpense(id, description) {
    if (confirm('Hapus pengeluaran "' + description + '"?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php include 'footer.php'; ?>