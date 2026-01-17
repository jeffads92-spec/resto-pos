<!-- FILE 1: users.php -->
<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit();
}

$users = $conn->query("SELECT * FROM users ORDER BY id");

include 'header.php';
?>

<style>
body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
}

.container-main {
    padding: 2rem;
    max-width: 1200px;
    margin: 0 auto;
}

.page-header {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.page-title {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    font-size: 2rem;
    font-weight: 700;
    margin: 0 0 1rem 0;
}

.users-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 1.5rem;
}

.user-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    text-align: center;
}

.user-avatar {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 2rem;
    font-weight: 700;
    margin: 0 auto 1rem;
}

.user-name {
    font-size: 1.25rem;
    font-weight: 700;
    color: #2d3748;
    margin-bottom: 0.5rem;
}

.user-role {
    display: inline-block;
    padding: 0.25rem 1rem;
    border-radius: 50px;
    font-size: 0.85rem;
    font-weight: 600;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}
</style>

<div class="container-main">
    <div class="page-header">
        <h1 class="page-title">👤 Manajemen User</h1>
        <p style="color: #718096; margin: 0;">Daftar pengguna sistem</p>
    </div>

    <div class="users-grid">
        <?php while($user = $users->fetch_assoc()): ?>
        <div class="user-card">
            <div class="user-avatar"><?= strtoupper(substr($user['username'], 0, 1)) ?></div>
            <div class="user-name"><?= htmlspecialchars($user['full_name'] ?? $user['username']) ?></div>
            <p style="color: #718096;">@<?= htmlspecialchars($user['username']) ?></p>
            <span class="user-role"><?= ucfirst($user['role']) ?></span>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<?php include 'footer.php'; ?>

<!-- ===================================== -->
<!-- FILE 2: settings.php -->
<!-- Save this as a separate file -->
<!-- ===================================== -->

<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit();
}

// Handle Update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach ($_POST as $key => $value) {
        if ($key != 'action') {
            $key_escaped = $conn->real_escape_string($key);
            $value_escaped = $conn->real_escape_string($value);
            $conn->query("UPDATE settings SET setting_value='$value_escaped' WHERE setting_key='$key_escaped'");
        }
    }
    header('Location: settings.php?msg=success');
    exit();
}

$settings = $conn->query("SELECT * FROM settings ORDER BY id");

include 'header.php';
?>

<style>
body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
}

.container-main {
    padding: 2rem;
    max-width: 900px;
    margin: 0 auto;
}

.page-header {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.page-title {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    font-size: 2rem;
    font-weight: 700;
    margin: 0 0 1rem 0;
}

.settings-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #2d3748;
}

.form-control {
    width: 100%;
    padding: 0.75rem;
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    font-size: 1rem;
}

.form-text {
    display: block;
    margin-top: 0.25rem;
    font-size: 0.85rem;
    color: #718096;
}

.btn-save {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 1rem 2rem;
    border-radius: 10px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    width: 100%;
}
</style>

<div class="container-main">
    <div class="page-header">
        <h1 class="page-title">⚙️ Pengaturan Sistem</h1>
        <p style="color: #718096; margin: 0;">Konfigurasi aplikasi</p>
    </div>

    <div class="settings-card">
        <form method="POST">
            <input type="hidden" name="action" value="update">
            
            <?php while($setting = $settings->fetch_assoc()): ?>
            <div class="form-group">
                <label><?= ucwords(str_replace('_', ' ', $setting['setting_key'])) ?></label>
                <input type="text" name="<?= $setting['setting_key'] ?>" 
                       value="<?= htmlspecialchars($setting['setting_value']) ?>" 
                       class="form-control">
                <?php if($setting['description']): ?>
                <small class="form-text"><?= htmlspecialchars($setting['description']) ?></small>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
            
            <button type="submit" class="btn-save">💾 Simpan Pengaturan</button>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>
