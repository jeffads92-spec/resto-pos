# 📦 PANDUAN INSTALASI SMART RESTO POS

## Langkah-Langkah Instalasi Detail

### 1️⃣ Persiapan Awal

#### A. Download dan Install XAMPP
1. Download XAMPP dari: https://www.apachefriends.org/
2. Install XAMPP di `C:\xampp\` (Windows) atau `/opt/lampp` (Linux)
3. Jalankan XAMPP Control Panel
4. Start **Apache** dan **MySQL**

#### B. Cek Instalasi
- Buka browser, akses: `http://localhost/`
- Jika muncul dashboard XAMPP, instalasi berhasil

---

### 2️⃣ Setup Database

#### A. Buat Database
1. Buka browser, akses: `http://localhost/phpmyadmin`
2. Klik tab **"Databases"**
3. Di kolom **"Create database"**, ketik: `smart_resto_pos`
4. Collation: `utf8mb4_general_ci`
5. Klik **"Create"**

#### B. Import SQL
Anda punya 2 opsi:

**Opsi 1: Import File SQL**
1. Klik database `smart_resto_pos`
2. Klik tab **"Import"**
3. Click **"Choose File"** → pilih file `smart_resto_pos.sql`
4. Klik **"Go"** di bagian bawah

**Opsi 2: Copy-Paste Script**
1. Klik database `smart_resto_pos`
2. Klik tab **"SQL"**
3. Copy semua isi dari file artifact **"Smart Resto POS - Database Schema"**
4. Paste ke textarea SQL
5. Klik **"Go"**

#### C. Verifikasi Database
Pastikan tabel-tabel ini sudah terbuat:
- ✅ users
- ✅ categories
- ✅ products
- ✅ members
- ✅ transactions
- ✅ transaction_details
- ✅ expenses
- ✅ stock_history
- ✅ activity_logs

---

### 3️⃣ Setup Aplikasi

#### A. Extract File
1. Extract semua file ke: `C:\xampp\htdocs\smart_resto_pos\`
2. Struktur folder harus seperti ini:
```
C:\xampp\htdocs\smart_resto_pos\
├── api/
├── config.php
├── index.php
├── login.php
├── pos.php
├── products.php
├── kitchen.php
├── members.php
├── expenses.php
├── reports.php
├── transactions.php
├── inventory.php
├── users.php
├── settings.php
├── print_receipt.php
├── header.php
├── footer.php
├── logout.php
└── uploads/
    └── products/
```

#### B. Buat File-file yang Diperlukan

**PENTING**: Anda perlu membuat file-file berikut secara manual:

##### 1. Buat folder `api/`
Lokasi: `C:\xampp\htdocs\smart_resto_pos\api\`

##### 2. Buat folder `uploads/products/`
Lokasi: `C:\xampp\htdocs\smart_resto_pos\uploads\products\`

##### 3. Set Permission Folder (untuk Linux/Mac)
```bash
chmod 777 uploads/products/
```

#### C. Konfigurasi Database
1. Buka file `config.php`
2. Sesuaikan jika perlu:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Kosongkan jika tidak ada password
define('DB_NAME', 'smart_resto_pos');
```

---

### 4️⃣ Jalankan Aplikasi

#### A. Akses Aplikasi
1. Buka browser
2. Ketik: `http://localhost/smart_resto_pos/`
3. Akan muncul halaman login

#### B. Login Pertama Kali
Gunakan akun default:

**👤 ADMIN**
- Username: `admin`
- Password: `password`

**👤 KASIR**
- Username: `kasir1`
- Password: `password`

⚠️ **WAJIB GANTI PASSWORD** setelah login pertama!

---

### 5️⃣ Konfigurasi Awal

#### A. Ganti Password Default
1. Login sebagai `admin`
2. Menu **Manajemen User**
3. Edit user admin dan kasir1
4. Ganti password

#### B. Tambah Kategori Produk
1. Menu **Produk & Kategori**
2. Klik **"Tambah Kategori"**
3. Contoh kategori:
   - Makanan Utama
   - Minuman Dingin
   - Minuman Panas
   - Snack
   - Dessert

#### C. Tambah Produk
1. Menu **Produk & Kategori**
2. Klik **"Tambah Produk"**
3. Isi form:
   - Pilih kategori
   - Nama produk
   - Harga jual
   - Harga modal (HPP)
   - Stok awal
   - Minimum stok

#### D. Setting Member (Optional)
1. Menu **Member Loyalty**
2. Klik **"Tambah Member"**
3. Isi data member

---

### 6️⃣ Testing Sistem

#### A. Test Transaksi POS
1. Menu **Point of Sale**
2. Pilih beberapa produk
3. Pilih metode pembayaran: **Tunai**
4. Input jumlah bayar
5. Klik **"Proses Pembayaran"**
6. Print struk

#### B. Test Kitchen Display
1. Buka tab/window baru
2. Akses: `http://localhost/smart_resto_pos/kitchen.php`
3. Login dengan akun kasir/admin
4. Lihat pesanan masuk
5. Update status: Pending → Preparing → Ready → Served

#### C. Test Dashboard
1. Menu **Dashboard**
2. Cek statistik:
   - Penjualan hari ini
   - Penjualan bulan ini
   - Laba bersih
   - Stok menipis

#### D. Test Laporan
1. Menu **Laporan Keuangan**
2. Pilih periode
3. Lihat grafik dan tabel
4. Download Excel

---

### 7️⃣ Daftar File yang Harus Dibuat

Berikut ini file-file yang perlu Anda buat **SECARA MANUAL** karena saya sudah berikan kodenya:

#### ✅ File Utama
1. **config.php** - Konfigurasi database
2. **login.php** - Halaman login
3. **logout.php** - Logout handler
4. **index.php** - Dashboard
5. **header.php** - Template header
6. **footer.php** - Template footer

#### ✅ File Fitur Utama
7. **pos.php** - Point of Sale
8. **products.php** - Manajemen produk
9. **kitchen.php** - Kitchen Display System
10. **print_receipt.php** - Print struk

#### ✅ File API (dalam folder api/)
11. **api/process_transaction.php** - API transaksi
12. **api/update_kitchen_status.php** - API kitchen status

#### ✅ File yang Masih Perlu Dibuat
Untuk melengkapi sistem, Anda masih perlu membuat:

13. **members.php** - Manajemen member
14. **expenses.php** - Manajemen pengeluaran
15. **reports.php** - Laporan keuangan
16. **transactions.php** - Riwayat transaksi
17. **inventory.php** - Manajemen inventory
18. **users.php** - Manajemen user (admin only)
19. **settings.php** - Pengaturan sistem
20. **api/get_order_count.php** - API count order

---

### 8️⃣ Template Kode untuk File yang Belum Dibuat

Saya akan berikan template dasar yang bisa Anda kembangkan:

#### A. members.php (Template Dasar)
```php
<?php
require_once 'config.php';
requireLogin();

// Get all members
$members = $conn->query("SELECT * FROM members ORDER BY name");

include 'header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2><i class="fas fa-users"></i> Member Loyalty</h2>
        </div>
        <div class="col-md-6 text-end">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                <i class="fas fa-plus"></i> Tambah Member
            </button>
        </div>
    </div>
    
    <!-- Member table dan modal di sini -->
</div>

<?php include 'footer.php'; ?>
```

#### B. expenses.php (Template Dasar)
```php
<?php
require_once 'config.php';
requireLogin();
requireAdmin(); // Only admin can access

$expenses = $conn->query("SELECT * FROM expenses ORDER BY date DESC");

include 'header.php';
?>

<div class="container-fluid py-4">
    <h2><i class="fas fa-money-bill-wave"></i> Manajemen Pengeluaran</h2>
    <!-- Expense form dan table -->
</div>

<?php include 'footer.php'; ?>
```

#### C. reports.php (Template Dasar)
```php
<?php
require_once 'config.php';
requireLogin();
requireAdmin();

// Get report data based on date range

include 'header.php';
?>

<div class="container-fluid py-4">
    <h2><i class="fas fa-file-alt"></i> Laporan Keuangan</h2>
    <!-- Charts and tables -->
</div>

<?php include 'footer.php'; ?>
```

---

### 9️⃣ Troubleshooting Umum

#### ❌ Error "Connection Failed"
**Solusi:**
- Pastikan MySQL di XAMPP sudah running
- Cek `config.php`, pastikan DB_USER dan DB_PASS benar
- Test koneksi di phpMyAdmin

#### ❌ Error "Table doesn't exist"
**Solusi:**
- Import ulang file SQL database
- Pastikan database `smart_resto_pos` sudah dipilih saat import

#### ❌ Halaman Blank/Putih
**Solusi:**
1. Enable error reporting di `config.php`:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```
2. Cek error log di: `C:\xampp\apache\logs\error.log`

#### ❌ Warning: session_start()
**Solusi:**
- Pastikan folder `C:\xampp\tmp` ada dan writable

#### ❌ Upload Gambar Gagal
**Solusi:**
- Buat folder: `uploads/products/`
- Set permission (Linux/Mac): `chmod 777 uploads/products/`

#### ❌ Print Struk Tidak Muncul
**Solusi:**
- Pastikan printer thermal sudah terinstall
- Test print dari browser: File → Print
- Sesuaikan ukuran kertas ke 58mm atau 80mm

---

### 🔟 Tips Optimasi

#### A. Performance
1. **Enable OPCache** di `php.ini`:
```ini
opcache.enable=1
opcache.memory_consumption=128
```

2. **Index Database** (sudah otomatis di SQL schema)

#### B. Keamanan
1. **Ganti Password Default** segera
2. **Backup Database** rutin (weekly/monthly)
3. **Update PHP** ke versi terbaru
4. **Disable Directory Listing** di `.htaccess`:
```apache
Options -Indexes
```

#### C. Production Ready
1. **Matikan Error Display**:
```php
ini_set('display_errors', 0);
error_reporting(0);
```

2. **Enable HTTPS** (SSL Certificate)

3. **Ubah BASE_URL** di `config.php`:
```php
define('BASE_URL', 'https://yourdomain.com/');
```

---

### 1️⃣1️⃣ Checklist Instalasi

Gunakan checklist ini untuk memastikan instalasi berhasil:

- [ ] XAMPP terinstall dan running
- [ ] Database `smart_resto_pos` sudah dibuat
- [ ] Semua tabel database sudah ada (10 tabel)
- [ ] Folder `api/` sudah dibuat
- [ ] Folder `uploads/products/` sudah dibuat
- [ ] File `config.php` sudah dikonfigurasi
- [ ] Bisa akses `http://localhost/smart_resto_pos/`
- [ ] Bisa login dengan admin/password
- [ ] Bisa tambah kategori produk
- [ ] Bisa tambah produk
- [ ] Bisa proses transaksi di POS
- [ ] Bisa print struk
- [ ] Kitchen display berfungsi
- [ ] Dashboard menampilkan statistik

---

### 1️⃣2️⃣ FAQ (Frequently Asked Questions)

**Q: Apakah bisa diakses dari komputer lain di jaringan lokal?**
A: Ya. Akses dengan: `http://[IP_SERVER]:80/smart_resto_pos/`

**Q: Apakah bisa deploy ke hosting online?**
A: Ya. Upload semua file ke public_html, import database, ubah config.php

**Q: Bagaimana cara backup data?**
A: Export database dari phpMyAdmin: Export → SQL → Go

**Q: Apakah support multi-cabang?**
A: Belum. Ini untuk single outlet. Multi-branch ada di roadmap.

**Q: Printer apa yang compatible?**
A: Thermal printer 58mm atau 80mm dengan driver ESC/POS

**Q: Bagaimana cara custom struk?**
A: Edit file `print_receipt.php`

---

### 1️⃣3️⃣ Kontak Support

Jika mengalami kesulitan:
1. Periksa file log error
2. Cek dokumentasi PHP dan MySQL
3. Konsultasi dengan developer

---

## 🎉 Selamat! Instalasi Selesai

Jika semua checklist terpenuhi, aplikasi **Smart Resto POS** siap digunakan!

**Next Steps:**
1. Ganti password default
2. Input data produk Anda
3. Training staff untuk menggunakan sistem
4. Mulai transaksi pertama

**Happy Selling!** 🍽️💰
