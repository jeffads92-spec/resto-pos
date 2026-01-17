<?php
// ============================================
// SETTINGS PAGE
// File: settings.php
// ============================================

session_start();
require_once 'config.php';

// Cek login dan role admin
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    die('Akses ditolak. Hanya admin yang dapat mengakses pengaturan.');
}

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_profile') {
        // Update admin profile
        $full_name = $_POST['full_name'];
        $username = $_POST['username'];
        
        if (!empty($_POST['new_password'])) {
            $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET username = ?, full_name = ?, password = ? WHERE id = ?");
            $stmt->bind_param("sssi", $username, $full_name, $new_password, $_SESSION['user_id']);
        } else {
            $stmt = $conn->prepare("UPDATE users SET username = ?, full_name = ? WHERE id = ?");
            $stmt->bind_param("ssi", $username, $full_name, $_SESSION['user_id']);
        }
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Profile berhasil diupdate';
            $_SESSION['full_name'] = $full_name;
            $_SESSION['username'] = $username;
        } else {
            $_SESSION['error'] = 'Gagal update profile';
        }
        $stmt->close();
    }
    
    header('Location: settings.php');
    exit;
}

// Get current user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$current_user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get database info
$db_size_query = "
    SELECT 
        table_schema AS 'Database',
        ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS 'Size (MB)'
    FROM information_schema.tables 
    WHERE table_schema = '" . DB_NAME . "'
    GROUP BY table_schema
";
$db_size = $conn->query($db_size_query)->fetch_assoc();

// Get record counts
$products_count = $conn->query("SELECT COUNT(*) as total FROM products")->fetch_assoc()['total'];
$transactions_count = $conn->query("SELECT COUNT(*) as total FROM transactions")->fetch_assoc()['total'];
$members_count = $conn->query("SELECT COUNT(*) as total FROM members")->fetch_assoc()['total'];
$users_count = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];

include 'header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2><i class="fas fa-cog"></i> Pengaturan Sistem</h2>
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

    <div class="row">
        <!-- System Info -->
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Informasi Sistem</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Nama Aplikasi:</strong></td>
                            <td><?= APP_NAME ?></td>
                        </tr>
                        <tr>
                            <td><strong>Versi:</strong></td>
                            <td><?= APP_VERSION ?></td>
                        </tr>
                        <tr>
                            <td><strong>Database:</strong></td>
                            <td><?= DB_NAME ?></td>
                        </tr>
                        <tr>
                            <td><strong>Ukuran Database:</strong></td>
                            <td><?= $db_size['Size (MB)'] ?? '0' ?> MB</td>
                        </tr>
                        <tr>
                            <td><strong>PHP Version:</strong></td>
                            <td><?= phpversion() ?></td>
                        </tr>
                        <tr>
                            <td><strong>Server:</strong></td>
                            <td><?= $_SERVER['SERVER_SOFTWARE'] ?></td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Database Statistics -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-database"></i> Statistik Database</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Total Produk:</span>
                            <strong><?= number_format($products_count) ?></strong>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Total Transaksi:</span>
                            <strong><?= number_format($transactions_count) ?></strong>
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span>Total Member:</span>
                            <strong><?= number_format($members_count) ?></strong>
                        </div>
                    </div>
                    <div class="mb-0">
                        <div class="d-flex justify-content-between">
                            <span>Total User:</span>
                            <strong><?= number_format($users_count) ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Settings Form -->
        <div class="col-md-6 mb-4">
            <!-- Profile Settings -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user-cog"></i> Pengaturan Profile</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="mb-3">
                            <label class="form-label">Username</label>
                            <input type="text" name="username" class="form-control" 
                                   value="<?= htmlspecialchars($current_user['username']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Nama Lengkap</label>
                            <input type="text" name="full_name" class="form-control" 
                                   value="<?= htmlspecialchars($current_user['full_name']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Password Baru</label>
                            <input type="password" name="new_password" class="form-control" minlength="6">
                            <small class="text-muted">Kosongkan jika tidak ingin mengubah password</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Role</label>
                            <input type="text" class="form-control" value="<?= ucfirst($current_user['role']) ?>" disabled>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan Perubahan
                        </button>
                    </form>
                </div>
            </div>

            <!-- System Settings -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-sliders-h"></i> Pengaturan Sistem</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Pajak (%)</label>
                        <input type="number" class="form-control" value="<?= TAX_RATE ?>" readonly>
                        <small class="text-muted">Edit di config.php untuk mengubah</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Poin per Rupiah</label>
                        <input type="number" class="form-control" value="<?= POINTS_PER_RUPIAH ?>" readonly>
                        <small class="text-muted">1 poin setiap Rp <?= number_format(POINTS_PER_RUPIAH, 0, ',', '.') ?></small>
                    </div>
                    
                    <hr>
                    
                    <h6>Database Actions</h6>
                    <div class="d-grid gap-2">
                        <button class="btn btn-warning" onclick="backupDatabase()">
                            <i class="fas fa-download"></i> Backup Database
                        </button>
                        <button class="btn btn-danger" onclick="confirmClearData()">
                            <i class="fas fa-trash-alt"></i> Clear All Data
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function backupDatabase() {
    if (confirm('Download backup database?')) {
        window.location.href = 'api/backup_database.php';
    }
}

function confirmClearData() {
    if (confirm('PERINGATAN!\n\nIni akan menghapus SEMUA data transaksi, member, dan stok history.\n\nData produk, kategori, dan user akan tetap ada.\n\nLanjutkan?')) {
        if (confirm('Apakah Anda YAKIN? Tindakan ini TIDAK DAPAT dibatalkan!')) {
            clearAllData();
        }
    }
}

function clearAllData() {
    fetch('api/clear_data.php', {
        method: 'POST'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Data berhasil dihapus!');
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        alert('Terjadi kesalahan: ' + error);
    });
}
</script>

<?php include 'footer.php'; ?>