<?php
// ============================================
// MEMBERS PAGE
// File: members.php
// ============================================

session_start();
require_once 'config.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'add') {
            // Generate member code
            $member_code = 'MBR-' . date('Ymd') . '-' . rand(1000, 9999);
            
            // Add member
            $stmt = $conn->prepare("
                INSERT INTO members (
                    member_code, 
                    name, 
                    phone, 
                    email, 
                    address
                ) VALUES (?, ?, ?, ?, ?)
            ");
            
            $name = $_POST['name'];
            $phone = $_POST['phone'];
            $email = $_POST['email'] ?? '';
            $address = $_POST['address'] ?? '';
            
            $stmt->bind_param(
                "sssss",
                $member_code,
                $name,
                $phone,
                $email,
                $address
            );
            
            $stmt->execute();
            $_SESSION['success'] = 'Member berhasil ditambahkan';
            
        } elseif ($action === 'edit') {
            // Edit member
            $stmt = $conn->prepare("
                UPDATE members 
                SET name = ?, 
                    phone = ?, 
                    email = ?, 
                    address = ?
                WHERE id = ?
            ");
            
            $name = $_POST['name'];
            $phone = $_POST['phone'];
            $email = $_POST['email'] ?? '';
            $address = $_POST['address'] ?? '';
            $id = $_POST['id'];
            
            $stmt->bind_param(
                "ssssi",
                $name,
                $phone,
                $email,
                $address,
                $id
            );
            
            $stmt->execute();
            $_SESSION['success'] = 'Member berhasil diupdate';
            
        } elseif ($action === 'delete') {
            // Soft delete member
            $stmt = $conn->prepare("UPDATE members SET is_active = 0 WHERE id = ?");
            $id = $_POST['id'];
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $_SESSION['success'] = 'Member berhasil dihapus';
        }
        
        $stmt->close();
        header('Location: members.php');
        exit;
        
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error: ' . $e->getMessage();
    }
}

// Get members
$search = $_GET['search'] ?? '';

$query = "
    SELECT 
        m.*,
        COUNT(DISTINCT t.id) as total_transactions,
        COALESCE(m.total_spent, 0) as total_spent,
        COALESCE(m.points, 0) as points
    FROM members m
    LEFT JOIN transactions t ON m.id = t.member_id AND t.status = 'completed'
    WHERE m.is_active = 1
";

if ($search) {
    $query .= " AND (m.name LIKE '%" . $conn->real_escape_string($search) . "%' 
                 OR m.phone LIKE '%" . $conn->real_escape_string($search) . "%'
                 OR m.member_code LIKE '%" . $conn->real_escape_string($search) . "%')";
}

$query .= " GROUP BY m.id ORDER BY m.created_at DESC";
$result = $conn->query($query);

include 'header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2><i class="fas fa-users"></i> Manajemen Member</h2>
        </div>
        <div class="col-md-6 text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                <i class="fas fa-plus"></i> Tambah Member
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

    <!-- Search -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-10">
                    <input type="text" name="search" class="form-control" 
                           placeholder="Cari member (nama, telepon, kode member)..." 
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i> Cari
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Members Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Kode Member</th>
                            <th>Nama</th>
                            <th>Telepon</th>
                            <th>Email</th>
                            <th>Points</th>
                            <th>Total Belanja</th>
                            <th>Total Transaksi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows == 0): ?>
                            <tr>
                                <td colspan="8" class="text-center">Tidak ada data member</td>
                            </tr>
                        <?php else: ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($row['member_code']) ?></strong>
                                    </td>
                                    <td><?= htmlspecialchars($row['name']) ?></td>
                                    <td>
                                        <i class="fas fa-phone text-muted"></i>
                                        <?= htmlspecialchars($row['phone']) ?>
                                    </td>
                                    <td>
                                        <?php if ($row['email']): ?>
                                            <i class="fas fa-envelope text-muted"></i>
                                            <?= htmlspecialchars($row['email']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning text-dark">
                                            <?= number_format($row['points'], 0, ',', '.') ?> pts
                                        </span>
                                    </td>
                                    <td>
                                        <strong class="text-success">
                                            Rp <?= number_format($row['total_spent'], 0, ',', '.') ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">
                                            <?= $row['total_transactions'] ?> transaksi
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-warning" 
                                                onclick="editMember(<?= htmlspecialchars(json_encode($row)) ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" 
                                                onclick="deleteMember(<?= $row['id'] ?>, '<?= htmlspecialchars($row['name']) ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Add Member -->
<div class="modal fade" id="addMemberModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title">Tambah Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">No. Telepon *</label>
                        <input type="tel" name="phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat</label>
                        <textarea name="address" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="alert alert-info">
                        <small>
                            <i class="fas fa-info-circle"></i>
                            Kode member akan di-generate otomatis
                        </small>
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

<!-- Modal Edit Member -->
<div class="modal fade" id="editMemberModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Kode Member</label>
                        <input type="text" id="edit_code" class="form-control" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nama Lengkap *</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">No. Telepon *</label>
                        <input type="tel" name="phone" id="edit_phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Alamat</label>
                        <textarea name="address" id="edit_address" class="form-control" rows="3"></textarea>
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
function editMember(data) {
    document.getElementById('edit_id').value = data.id;
    document.getElementById('edit_code').value = data.member_code;
    document.getElementById('edit_name').value = data.name;
    document.getElementById('edit_phone').value = data.phone;
    document.getElementById('edit_email').value = data.email || '';
    document.getElementById('edit_address').value = data.address || '';
    
    new bootstrap.Modal(document.getElementById('editMemberModal')).show();
}

function deleteMember(id, name) {
    if (confirm('Hapus member "' + name + '"?\n\nData transaksi member akan tetap tersimpan.')) {
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