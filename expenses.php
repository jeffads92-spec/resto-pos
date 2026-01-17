<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit();
}

// Handle Add/Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            $category = $conn->real_escape_string($_POST['category']);
            $description = $conn->real_escape_string($_POST['description']);
            $amount = floatval($_POST['amount']);
            $expense_date = $_POST['expense_date'];
            $user_id = $_SESSION['user_id'];
            
            $sql = "INSERT INTO expenses (category, description, amount, expense_date, created_by) 
                    VALUES ('$category', '$description', $amount, '$expense_date', $user_id)";
            $conn->query($sql);
            header('Location: expenses.php?msg=success');
            exit();
        }
        
        if ($_POST['action'] == 'delete') {
            $id = intval($_POST['id']);
            $conn->query("DELETE FROM expenses WHERE id=$id");
            header('Location: expenses.php?msg=deleted');
            exit();
        }
    }
}

$expenses = $conn->query("SELECT e.*, u.username FROM expenses e 
                          LEFT JOIN users u ON e.created_by = u.id 
                          ORDER BY e.expense_date DESC");

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
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.page-title {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    font-size: 2rem;
    font-weight: 700;
    margin: 0;
}

.btn-add {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 0.75rem 2rem;
    border-radius: 50px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.table-container {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}

table {
    width: 100%;
    border-collapse: collapse;
}

thead {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

th {
    color: white;
    padding: 1rem;
    text-align: left;
    font-weight: 600;
}

tbody tr {
    border-bottom: 1px solid #e2e8f0;
}

tbody tr:hover {
    background: #f7fafc;
}

td {
    padding: 1rem;
}

.btn-delete {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.7);
}

.modal-content {
    background: white;
    margin: 5% auto;
    padding: 0;
    border-radius: 20px;
    max-width: 600px;
}

.modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem 2rem;
    border-radius: 20px 20px 0 0;
}

.modal-body {
    padding: 2rem;
}

.close {
    float: right;
    font-size: 2rem;
    font-weight: bold;
    cursor: pointer;
}

.form-group {
    margin-bottom: 1.5rem;
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
</style>

<div class="container-main">
    <div class="page-header">
        <h1 class="page-title">💸 Manajemen Pengeluaran</h1>
        <button class="btn-add" onclick="openModal()">+ Tambah Pengeluaran</button>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Kategori</th>
                    <th>Deskripsi</th>
                    <th>Jumlah</th>
                    <th>Dicatat Oleh</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php while($e = $expenses->fetch_assoc()): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($e['expense_date'])) ?></td>
                    <td><?= htmlspecialchars($e['category']) ?></td>
                    <td><?= htmlspecialchars($e['description']) ?></td>
                    <td><strong>Rp <?= number_format($e['amount'], 0, ',', '.') ?></strong></td>
                    <td><?= htmlspecialchars($e['username']) ?></td>
                    <td>
                        <button class="btn-delete" onclick="deleteExpense(<?= $e['id'] ?>)">Hapus</button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="expenseModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 style="margin: 0;">Tambah Pengeluaran</h2>
        </div>
        <div class="modal-body">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label>Kategori</label>
                    <input type="text" name="category" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Deskripsi</label>
                    <textarea name="description" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Jumlah</label>
                    <input type="number" name="amount" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Tanggal</label>
                    <input type="date" name="expense_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                
                <button type="submit" class="btn-add" style="width: 100%;">Simpan</button>
            </form>
        </div>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('expenseModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('expenseModal').style.display = 'none';
}

function deleteExpense(id) {
    if (confirm('Yakin ingin menghapus?')) {
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
