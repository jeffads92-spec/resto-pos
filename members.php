<?php
require_once 'config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Handle Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add' || $_POST['action'] == 'edit') {
            $code = $conn->real_escape_string($_POST['code']);
            $name = $conn->real_escape_string($_POST['name']);
            $phone = $conn->real_escape_string($_POST['phone']);
            $email = $conn->real_escape_string($_POST['email']);
            $address = $conn->real_escape_string($_POST['address']);
            $discount = floatval($_POST['discount']);
            
            if ($_POST['action'] == 'add') {
                $sql = "INSERT INTO members (code, name, phone, email, address, discount, join_date) 
                        VALUES ('$code', '$name', '$phone', '$email', '$address', $discount, CURDATE())";
            } else {
                $id = intval($_POST['id']);
                $sql = "UPDATE members SET code='$code', name='$name', phone='$phone', 
                        email='$email', address='$address', discount=$discount WHERE id=$id";
            }
            
            $conn->query($sql);
            header('Location: members.php?msg=success');
            exit();
        }
        
        if ($_POST['action'] == 'delete') {
            $id = intval($_POST['id']);
            $conn->query("DELETE FROM members WHERE id=$id");
            header('Location: members.php?msg=deleted');
            exit();
        }
    }
}

// Get members with statistics
$members = $conn->query("SELECT 
    m.*,
    COUNT(t.id) as total_transactions,
    COALESCE(SUM(t.total), 0) as total_spent,
    MAX(t.created_at) as last_transaction_date
    FROM members m
    LEFT JOIN transactions t ON m.id = t.member_id
    GROUP BY m.id
    ORDER BY m.created_at DESC");

include 'header.php';
?>

<style>
body {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
}

.members-container {
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
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
}

.btn-add:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.6);
}

.members-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

.member-card {
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 1.5rem;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
    border: 1px solid rgba(255,255,255,0.3);
}

.member-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.member-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 2px solid #e2e8f0;
}

.member-avatar {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    font-weight: 700;
}

.member-info {
    flex: 1;
}

.member-name {
    font-size: 1.25rem;
    font-weight: 700;
    color: #2d3748;
    margin-bottom: 0.25rem;
}

.member-code {
    color: #718096;
    font-size: 0.9rem;
    font-weight: 600;
}

.member-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 1rem;
}

.stat-box {
    text-align: center;
    padding: 1rem;
    background: #f7fafc;
    border-radius: 10px;
}

.stat-value {
    font-size: 1.25rem;
    font-weight: 700;
    color: #667eea;
    margin-bottom: 0.25rem;
}

.stat-label {
    font-size: 0.75rem;
    color: #718096;
    font-weight: 600;
}

.member-contact {
    margin-bottom: 1rem;
}

.contact-row {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
    color: #718096;
    font-size: 0.9rem;
}

.contact-row i {
    width: 20px;
    text-align: center;
}

.member-actions {
    display: flex;
    gap: 0.5rem;
}

.btn {
    flex: 1;
    padding: 0.65rem;
    border: none;
    border-radius: 10px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-edit {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
}

.btn-delete {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    color: white;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.3);
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
    backdrop-filter: blur(5px);
}

.modal-content {
    background: white;
    margin: 2% auto;
    padding: 0;
    border-radius: 20px;
    max-width: 600px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.5);
}

.modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1.5rem 2rem;
    border-radius: 20px 20px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-body {
    padding: 2rem;
}

.close {
    font-size: 2rem;
    font-weight: bold;
    cursor: pointer;
    color: white;
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
    transition: all 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

@media (max-width: 768px) {
    .members-grid {
        grid-template-columns: 1fr;
    }
    
    .page-header {
        flex-direction: column;
        gap: 1rem;
    }
}
</style>

<div class="members-container">
    <div class="page-header">
        <div>
            <h1 class="page-title">👥 Manajemen Member</h1>
            <p style="color: #718096; margin: 0;">Program loyalty pelanggan</p>
        </div>
        <button class="btn-add" onclick="openModal()">
            ➕ Tambah Member Baru
        </button>
    </div>

    <div class="members-grid">
        <?php while($member = $members->fetch_assoc()): ?>
        <div class="member-card">
            <div class="member-header">
                <div class="member-avatar">
                    <?= strtoupper(substr($member['name'], 0, 1)) ?>
                </div>
                <div class="member-info">
                    <div class="member-name"><?= htmlspecialchars($member['name']) ?></div>
                    <div class="member-code"><?= htmlspecialchars($member['code']) ?></div>
                </div>
            </div>
            
            <div class="member-stats">
                <div class="stat-box">
                    <div class="stat-value"><?= $member['points'] ?></div>
                    <div class="stat-label">Poin</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?= $member['discount'] ?>%</div>
                    <div class="stat-label">Diskon</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value"><?= $member['total_transactions'] ?></div>
                    <div class="stat-label">Transaksi</div>
                </div>
            </div>
            
            <div class="member-contact">
                <?php if($member['phone']): ?>
                <div class="contact-row">
                    <i class="fas fa-phone"></i>
                    <span><?= htmlspecialchars($member['phone']) ?></span>
                </div>
                <?php endif; ?>
                <?php if($member['email']): ?>
                <div class="contact-row">
                    <i class="fas fa-envelope"></i>
                    <span><?= htmlspecialchars($member['email']) ?></span>
                </div>
                <?php endif; ?>
                <div class="contact-row">
                    <i class="fas fa-wallet"></i>
                    <span><strong>Total Belanja: Rp <?= number_format($member['total_spent'], 0, ',', '.') ?></strong></span>
                </div>
            </div>
            
            <div class="member-actions">
                <button class="btn btn-edit" onclick='editMember(<?= json_encode($member) ?>)'>
                    ✏️ Edit
                </button>
                <button class="btn btn-delete" onclick="deleteMember(<?= $member['id'] ?>)">
                    🗑️ Hapus
                </button>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- Modal -->
<div id="memberModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 style="margin: 0;" id="modalTitle">Tambah Member Baru</h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form method="POST" id="memberForm">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="memberId">
                
                <div class="form-group">
                    <label>Kode Member *</label>
                    <input type="text" name="code" id="memberCode" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label>Nama Lengkap *</label>
                    <input type="text" name="name" id="memberName" class="form-control" required>
                </div>
                
                <div class="form-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                    <div>
                        <label>Nomor HP *</label>
                        <input type="text" name="phone" id="memberPhone" class="form-control" required>
                    </div>
                    <div>
                        <label>Email</label>
                        <input type="email" name="email" id="memberEmail" class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Alamat</label>
                    <textarea name="address" id="memberAddress" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Diskon (%)</label>
                    <input type="number" name="discount" id="memberDiscount" class="form-control" value="5" min="0" max="100" step="0.5">
                </div>
                
                <button type="submit" class="btn-add" style="width: 100%;">
                    💾 Simpan Member
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function openModal() {
    document.getElementById('memberModal').style.display = 'block';
    document.getElementById('memberForm').reset();
    document.getElementById('formAction').value = 'add';
    document.getElementById('modalTitle').textContent = 'Tambah Member Baru';
    document.getElementById('memberCode').value = 'MBR' + Date.now().toString().substr(-6);
}

function closeModal() {
    document.getElementById('memberModal').style.display = 'none';
}

function editMember(member) {
    document.getElementById('memberModal').style.display = 'block';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('modalTitle').textContent = 'Edit Member';
    document.getElementById('memberId').value = member.id;
    document.getElementById('memberCode').value = member.code;
    document.getElementById('memberName').value = member.name;
    document.getElementById('memberPhone').value = member.phone;
    document.getElementById('memberEmail').value = member.email;
    document.getElementById('memberAddress').value = member.address;
    document.getElementById('memberDiscount').value = member.discount;
}

function deleteMember(id) {
    if (confirm('Yakin ingin menghapus member ini?')) {
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

window.onclick = function(event) {
    const modal = document.getElementById('memberModal');
    if (event.target == modal) {
        closeModal();
    }
}
</script>

<?php include 'footer.php'; ?>
