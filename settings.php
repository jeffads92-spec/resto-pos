<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header('Location: index.php');
    exit();
}

// Handle Update Settings
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

<div class="container-main">
    <div class="page-header">
        <h1>⚙️ Pengaturan Sistem</h1>
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
            
            <button type="submit" class="btn btn-primary">💾 Simpan Pengaturan</button>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>
