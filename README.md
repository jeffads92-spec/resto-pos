# Smart Resto POS - Sistem Manajemen Restoran

Sistem Point of Sale (POS) lengkap untuk restoran, cafe, dan warung makan dengan fitur komprehensif.

## 📋 Fitur Utama

### ✅ Dashboard Analitik
- Grafik penjualan 7 hari terakhir
- Ringkasan laba/rugi bulanan
- Total transaksi real-time
- Produk terlaris
- Notifikasi stok menipis

### 💰 Point of Sale / Kasir
- Interface cepat dan responsif
- Input pesanan dengan kategori filter
- Kalkulator kembalian otomatis
- Support member discount
- Multiple payment methods (Tunai, QRIS, Transfer)

### 📦 Manajemen Produk & Kategori
- CRUD produk lengkap
- Upload gambar produk
- Kelola kategori menu
- Set harga jual dan harga modal
- Status aktif/nonaktif produk

### 📊 Sistem Inventaris & Stok
- Update stok otomatis saat transaksi
- Notifikasi stok minimum
- Riwayat pergerakan stok
- Stock adjustment manual
- Stock opname

### 🍳 Kitchen Display System (KDS)
- Layar monitor khusus dapur
- Real-time order notification
- Status pesanan (Pending → Preparing → Ready → Served)
- Auto-refresh setiap 5 detik
- Filter berdasarkan status

### 👥 Loyalty Membership
- Database pelanggan lengkap
- Sistem akumulasi poin otomatis
- Riwayat pembelian member
- Member analytics

### 💸 Manajemen Pengeluaran
- Catat biaya operasional
- Kategori pengeluaran
- Laporan pengeluaran bulanan
- Exclude dari HPP

### 📈 Laporan Keuangan Lengkap
- Laporan penjualan harian/bulanan
- Laba kotor dan bersih
- Top selling products
- Export ke Excel
- Print report

### 🖨️ Export Data
- Print struk thermal 58mm/80mm
- Download laporan Excel
- Export transaksi
- Backup data

### 👤 Multi-User Access
- Role: Admin dan Kasir
- Hak akses berbeda per role
- Activity logging
- User management

### 📱 Web Responsive
- Mobile friendly
- Tablet optimized
- Desktop full feature
- Cross-browser compatible

## 🚀 Instalasi

### Persyaratan Sistem
- XAMPP (atau LAMP/WAMP)
- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi
- Browser modern (Chrome, Firefox, Edge)

### Langkah Instalasi

#### 1. Download & Extract
```bash
# Extract file ke folder htdocs XAMPP
C:\xampp\htdocs\smart_resto_pos\
```

#### 2. Setup Database
1. Buka **phpMyAdmin** (http://localhost/phpmyadmin)
2. Buat database baru dengan nama `smart_resto_pos`
3. Import file `smart_resto_pos.sql` atau copy-paste script SQL dari file `smart_resto_db.sql`

#### 3. Konfigurasi Database
Edit file `config.php` jika perlu mengubah setting:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', ''); // Sesuaikan password MySQL Anda
define('DB_NAME', 'smart_resto_pos');
```

#### 4. Jalankan Aplikasi
1. Start Apache dan MySQL di XAMPP Control Panel
2. Buka browser dan akses: `http://localhost/smart_resto_pos/`

#### 5. Login
**Default Login:**
- **Admin**: username: `admin` | password: `password`
- **Kasir**: username: `kasir1` | password: `password`

⚠️ **PENTING**: Ganti password default setelah login pertama kali!

## 📂 Struktur File

```
smart_resto_pos/
├── api/
│   ├── process_transaction.php    # API transaksi
│   ├── update_kitchen_status.php  # Update status dapur
│   ├── get_products.php           # Get data produk
│   └── ...
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── uploads/                       # Folder upload gambar produk
├── config.php                     # Konfigurasi database
├── index.php                      # Dashboard
├── login.php                      # Halaman login
├── logout.php                     # Logout
├── pos.php                        # Point of Sale
├── products.php                   # Manajemen produk
├── inventory.php                  # Inventaris & stok
├── kitchen.php                    # Kitchen Display
├── members.php                    # Member loyalty
├── expenses.php                   # Pengeluaran
├── reports.php                    # Laporan keuangan
├── transactions.php               # Riwayat transaksi
├── users.php                      # Manajemen user (admin only)
├── settings.php                   # Pengaturan (admin only)
├── print_receipt.php              # Print struk
├── header.php                     # Header template
├── footer.php                     # Footer template
└── README.md                      # Dokumentasi ini
```

## 🔧 Konfigurasi Tambahan

### 1. Setting Pajak (Tax)
Edit di `config.php`:
```php
define('TAX_RATE', 10); // 10%
```

### 2. Setting Poin Member
Edit di `config.php`:
```php
define('POINTS_PER_RUPIAH', 1000); // 1 poin per Rp 1.000
```

### 3. Upload Gambar Produk
Pastikan folder `uploads/products/` memiliki permission write:
```bash
chmod 777 uploads/products/
```

## 📱 Fitur Per Role

### Admin (Full Access)
✅ Semua fitur dashboard  
✅ Point of Sale  
✅ Manajemen produk & kategori  
✅ Inventaris & stok  
✅ Kitchen display  
✅ Member loyalty  
✅ Pengeluaran  
✅ Laporan keuangan  
✅ Riwayat transaksi  
✅ Manajemen user  
✅ Pengaturan sistem  

### Kasir (Limited Access)
✅ Dashboard (view only)  
✅ Point of Sale  
✅ Lihat produk (tidak bisa edit)  
✅ Lihat stok (tidak bisa edit)  
✅ Kitchen display  
✅ Member loyalty  
❌ Pengeluaran  
❌ Laporan keuangan  
✅ Riwayat transaksi (view only)  
❌ Manajemen user  
❌ Pengaturan sistem  

## 🎨 Customization

### Mengubah Warna Tema
Edit variabel CSS di `header.php`:
```css
:root {
    --primary-color: #667eea;
    --secondary-color: #764ba2;
}
```

### Mengubah Logo/Nama Aplikasi
Edit di `config.php`:
```php
define('APP_NAME', 'Smart Resto POS');
```

## 🔒 Keamanan

1. **Ganti Password Default** setelah instalasi
2. **Backup Database** secara berkala
3. **Update PHP** ke versi terbaru
4. **Setting Permission** folder upload dengan benar
5. **Aktifkan HTTPS** jika sudah production

## 📊 Cara Menggunakan

### A. Proses Transaksi (POS)
1. Login sebagai kasir/admin
2. Klik menu **Point of Sale**
3. Pilih produk dari daftar menu
4. Atur jumlah (quantity) di keranjang
5. Pilih member jika ada (optional)
6. Pilih metode pembayaran
7. Masukkan jumlah bayar (jika tunai)
8. Klik **Proses Pembayaran**
9. Print struk

### B. Manajemen Stok
1. Login sebagai admin
2. Klik menu **Inventaris & Stok**
3. Pilih produk yang ingin di-update
4. Masukkan jumlah stok masuk/keluar
5. Tambahkan catatan
6. Simpan

### C. Melihat Laporan
1. Login sebagai admin
2. Klik menu **Laporan Keuangan**
3. Pilih periode (harian/bulanan)
4. Lihat grafik dan tabel
5. Download Excel atau Print

### D. Kitchen Display
1. Buka di browser terpisah (monitor dapur)
2. Akses: `http://localhost/smart_resto_pos/kitchen.php`
3. Login menggunakan akun kasir/admin
4. Layar akan auto-refresh setiap 5 detik
5. Update status pesanan: Pending → Preparing → Ready → Served

## 🐛 Troubleshooting

### Error "Connection Failed"
- Pastikan MySQL sudah running
- Cek username dan password di `config.php`
- Pastikan database `smart_resto_pos` sudah dibuat

### Upload Gambar Gagal
- Cek permission folder `uploads/products/`
- Pastikan size file < 5MB
- Format: JPG, JPEG, PNG

### Struk Tidak Keluar
- Pastikan printer thermal sudah terinstall
- Cek koneksi printer
- Test print dari browser

### Stok Tidak Update
- Cek apakah transaksi berhasil
- Lihat tabel `stock_history`
- Pastikan product_id valid

## 📞 Support

Untuk pertanyaan atau bug report, silakan hubungi developer atau buat issue di repository.

## 📄 License

Copyright © 2025 Smart Resto POS. All rights reserved.

---

## 🎯 Roadmap Fitur Mendatang

- [ ] Multi-outlet/branch support
- [ ] WhatsApp notification
- [ ] Online ordering integration
- [ ] Table management
- [ ] Reservasi meja
- [ ] Mobile apps (Android/iOS)
- [ ] Biometric authentication
- [ ] Analisis AI prediksi stok
- [ ] Integration payment gateway

## 🙏 Credits

Developed with ❤️ using:
- Bootstrap 5
- Font Awesome 6
- Chart.js
- PHP & MySQL

---

**Selamat menggunakan Smart Resto POS!** 🎉
