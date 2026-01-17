-- =====================================================
-- SMART RESTO POS - COMPLETE DATABASE SCHEMA
-- Version: 2.0
-- Date: 2025-01-17
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- Set character set
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

-- =====================================================
-- CREATE DATABASE
-- =====================================================

CREATE DATABASE IF NOT EXISTS `smart_resto_pos` 
DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `smart_resto_pos`;

-- =====================================================
-- TABLE: users
-- =====================================================

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `role` enum('admin','kasir') NOT NULL DEFAULT 'kasir',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `idx_role` (`role`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default users
INSERT INTO `users` (`id`, `username`, `password`, `full_name`, `email`, `role`, `is_active`) VALUES
(1, 'admin', MD5('password'), 'Administrator', 'admin@restopos.com', 'admin', 1),
(2, 'kasir1', MD5('password'), 'Kasir Satu', 'kasir1@restopos.com', 'kasir', 1),
(3, 'kasir2', MD5('password'), 'Kasir Dua', 'kasir2@restopos.com', 'kasir', 1);

-- =====================================================
-- TABLE: categories
-- =====================================================

DROP TABLE IF EXISTS `categories`;
CREATE TABLE `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `icon` varchar(50) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample categories
INSERT INTO `categories` (`id`, `name`, `description`, `icon`) VALUES
(1, 'Makanan', 'Menu makanan utama', 'fa-utensils'),
(2, 'Minuman', 'Minuman segar', 'fa-glass'),
(3, 'Snack', 'Cemilan dan kudapan', 'fa-cookie'),
(4, 'Dessert', 'Makanan penutup', 'fa-ice-cream'),
(5, 'Paket', 'Paket hemat', 'fa-box');

-- =====================================================
-- TABLE: products
-- =====================================================

DROP TABLE IF EXISTS `products`;
CREATE TABLE `products` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cost` decimal(10,2) DEFAULT 0.00,
  `stock` int(11) DEFAULT 0,
  `stock_min` int(11) DEFAULT 5,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_category_id` (`category_id`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_name` (`name`),
  CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample products
INSERT INTO `products` (`id`, `category_id`, `name`, `description`, `price`, `cost`, `stock`, `stock_min`) VALUES
(1, 1, 'Nasi Goreng Special', 'Nasi goreng dengan telur, ayam, dan sayuran', 25000, 12000, 50, 10),
(2, 1, 'Mie Goreng', 'Mie goreng pedas dengan topping ayam', 22000, 10000, 40, 10),
(3, 1, 'Nasi Ayam Bakar', 'Nasi putih dengan ayam bakar bumbu kecap', 30000, 15000, 35, 10),
(4, 1, 'Sate Ayam', 'Sate ayam 10 tusuk dengan bumbu kacang', 28000, 13000, 45, 10),
(5, 2, 'Es Teh Manis', 'Teh manis segar dingin', 5000, 2000, 100, 20),
(6, 2, 'Es Jeruk', 'Jus jeruk segar', 8000, 3000, 80, 15),
(7, 2, 'Kopi Susu', 'Kopi susu hangat/dingin', 12000, 5000, 60, 15),
(8, 2, 'Jus Alpukat', 'Jus alpukat creamy', 15000, 7000, 40, 10),
(9, 3, 'French Fries', 'Kentang goreng crispy', 15000, 6000, 50, 10),
(10, 3, 'Pisang Goreng', 'Pisang goreng crispy 5 pcs', 10000, 4000, 60, 15),
(11, 4, 'Es Krim Vanilla', 'Es krim vanilla premium', 12000, 5000, 30, 10),
(12, 4, 'Puding Coklat', 'Puding coklat lembut', 10000, 4000, 25, 10),
(13, 5, 'Paket Hemat A', 'Nasi goreng + Es teh', 28000, 14000, 30, 5),
(14, 5, 'Paket Hemat B', 'Mie goreng + Jus jeruk', 28000, 13000, 30, 5),
(15, 1, 'Ayam Geprek', 'Ayam crispy dengan sambal geprek level pedas', 27000, 12000, 40, 10);

-- =====================================================
-- TABLE: members
-- =====================================================

DROP TABLE IF EXISTS `members`;
CREATE TABLE `members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(20) NOT NULL,
  `name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `points` int(11) DEFAULT 0,
  `discount` decimal(5,2) DEFAULT 0.00,
  `join_date` date DEFAULT NULL,
  `last_transaction` datetime DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`),
  KEY `idx_phone` (`phone`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample members
INSERT INTO `members` (`id`, `code`, `name`, `phone`, `email`, `points`, `discount`, `join_date`) VALUES
(1, 'MBR001', 'John Doe', '081234567890', 'john@email.com', 150, 10.00, '2024-01-01'),
(2, 'MBR002', 'Jane Smith', '081234567891', 'jane@email.com', 250, 15.00, '2024-01-05'),
(3, 'MBR003', 'Bob Wilson', '081234567892', 'bob@email.com', 80, 5.00, '2024-01-10');

-- =====================================================
-- TABLE: transactions
-- =====================================================

DROP TABLE IF EXISTS `transactions`;
CREATE TABLE `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `invoice_number` varchar(50) NOT NULL,
  `user_id` int(11) NOT NULL,
  `member_id` int(11) DEFAULT NULL,
  `subtotal` decimal(12,2) DEFAULT 0.00,
  `discount` decimal(12,2) DEFAULT 0.00,
  `tax` decimal(12,2) DEFAULT 0.00,
  `total` decimal(12,2) NOT NULL,
  `payment_method` enum('cash','qris','transfer','debit','credit') DEFAULT 'cash',
  `cash_amount` decimal(12,2) DEFAULT 0.00,
  `change_amount` decimal(12,2) DEFAULT 0.00,
  `notes` text DEFAULT NULL,
  `status` enum('pending','completed','cancelled') DEFAULT 'completed',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoice_number` (`invoice_number`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_member_id` (`member_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_transactions_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  CONSTRAINT `fk_transactions_member` FOREIGN KEY (`member_id`) REFERENCES `members` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample transactions
INSERT INTO `transactions` (`id`, `invoice_number`, `user_id`, `member_id`, `subtotal`, `discount`, `tax`, `total`, `payment_method`, `cash_amount`, `change_amount`, `created_at`) VALUES
(1, 'INV-20250117-001', 2, 1, 50000, 5000, 4500, 49500, 'cash', 50000, 500, '2025-01-17 08:30:00'),
(2, 'INV-20250117-002', 2, NULL, 35000, 0, 3500, 38500, 'qris', 38500, 0, '2025-01-17 09:15:00'),
(3, 'INV-20250117-003', 2, 2, 75000, 11250, 6375, 70125, 'cash', 75000, 4875, '2025-01-17 10:45:00');

-- =====================================================
-- TABLE: transaction_items
-- =====================================================

DROP TABLE IF EXISTS `transaction_items`;
CREATE TABLE `transaction_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL DEFAULT 1,
  `price` decimal(10,2) NOT NULL,
  `subtotal` decimal(12,2) NOT NULL,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_transaction_id` (`transaction_id`),
  KEY `idx_product_id` (`product_id`),
  CONSTRAINT `fk_transaction_items_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_transaction_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample transaction items
INSERT INTO `transaction_items` (`transaction_id`, `product_id`, `quantity`, `price`, `subtotal`) VALUES
(1, 1, 2, 25000, 50000),
(2, 5, 2, 5000, 10000),
(2, 1, 1, 25000, 25000),
(3, 3, 2, 30000, 60000),
(3, 7, 1, 12000, 12000),
(3, 9, 1, 15000, 15000);

-- =====================================================
-- TABLE: stock_history
-- =====================================================

DROP TABLE IF EXISTS `stock_history`;
CREATE TABLE `stock_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `type` enum('in','out','adjustment') NOT NULL,
  `quantity` int(11) NOT NULL,
  `before_stock` int(11) DEFAULT 0,
  `after_stock` int(11) DEFAULT 0,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_product_id` (`product_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_type` (`type`),
  CONSTRAINT `fk_stock_history_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_stock_history_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: expenses
-- =====================================================

DROP TABLE IF EXISTS `expenses`;
CREATE TABLE `expenses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(12,2) NOT NULL,
  `expense_date` date NOT NULL,
  `receipt_number` varchar(50) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_expense_date` (`expense_date`),
  KEY `idx_category` (`category`),
  CONSTRAINT `fk_expenses_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert sample expenses
INSERT INTO `expenses` (`category`, `description`, `amount`, `expense_date`, `created_by`) VALUES
('Bahan Baku', 'Pembelian bahan makanan bulanan', 5000000, '2025-01-15', 1),
('Utilitas', 'Listrik dan air', 1500000, '2025-01-10', 1),
('Gaji', 'Gaji karyawan bulan Januari', 8000000, '2025-01-01', 1),
('Perawatan', 'Service AC dan peralatan dapur', 750000, '2025-01-12', 1);

-- =====================================================
-- TABLE: activity_logs
-- =====================================================

DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_action` (`action`),
  CONSTRAINT `fk_activity_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- TABLE: settings
-- =====================================================

DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default settings
INSERT INTO `settings` (`setting_key`, `setting_value`, `description`) VALUES
('app_name', 'Smart Resto POS', 'Nama aplikasi'),
('currency', 'IDR', 'Mata uang'),
('timezone', 'Asia/Jakarta', 'Zona waktu'),
('tax_rate', '10', 'Persentase pajak (%)'),
('points_per_rupiah', '1000', 'Poin per rupiah (1 poin per 1000 rupiah)'),
('default_product_image', 'assets/images/no-image.png', 'Gambar default untuk produk'),
('receipt_header', 'SMART RESTO POS', 'Header struk'),
('receipt_footer', 'Terima kasih atas kunjungan Anda!', 'Footer struk'),
('phone', '0812-3456-7890', 'Nomor telepon resto'),
('address', 'Jl. Contoh No. 123, Jakarta', 'Alamat resto'),
('low_stock_alert', '1', 'Aktifkan notifikasi stok menipis (1=Ya, 0=Tidak)'),
('auto_print_receipt', '0', 'Auto print struk setelah transaksi (1=Ya, 0=Tidak)');

-- =====================================================
-- TABLE: kitchen_orders
-- =====================================================

DROP TABLE IF EXISTS `kitchen_orders`;
CREATE TABLE `kitchen_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` int(11) NOT NULL,
  `transaction_item_id` int(11) NOT NULL,
  `product_name` varchar(200) NOT NULL,
  `quantity` int(11) NOT NULL,
  `status` enum('pending','preparing','ready','served') DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `started_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_transaction_id` (`transaction_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_kitchen_orders_transaction` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- VIEWS
-- =====================================================

-- View: Sales Report
CREATE OR REPLACE VIEW `v_sales_report` AS
SELECT 
    t.id,
    t.invoice_number,
    t.created_at,
    COALESCE(u.username, 'N/A') as cashier,
    COALESCE(m.name, '-') as member_name,
    t.subtotal,
    COALESCE(t.discount, 0) as discount,
    COALESCE(t.tax, 0) as tax,
    t.total,
    COALESCE(t.payment_method, 'cash') as payment_method,
    t.status,
    (SELECT COUNT(*) FROM transaction_items WHERE transaction_id = t.id) as items_count
FROM transactions t
LEFT JOIN users u ON t.user_id = u.id
LEFT JOIN members m ON t.member_id = m.id;

-- View: Product Stock
CREATE OR REPLACE VIEW `v_product_stock` AS
SELECT 
    p.id,
    p.name,
    COALESCE(p.stock, 0) as stock,
    COALESCE(p.stock_min, 0) as stock_min,
    COALESCE(c.name, 'Uncategorized') as category_name,
    p.price,
    COALESCE(p.cost, 0) as cost,
    (COALESCE(p.stock, 0) * COALESCE(p.cost, 0)) as stock_value,
    p.is_active,
    CASE 
        WHEN COALESCE(p.stock, 0) = 0 THEN 'Habis'
        WHEN COALESCE(p.stock, 0) <= COALESCE(p.stock_min, 0) THEN 'Menipis'
        ELSE 'Aman'
    END as stock_status
FROM products p
LEFT JOIN categories c ON p.category_id = c.id;

-- View: Daily Sales Summary
CREATE OR REPLACE VIEW `v_daily_sales` AS
SELECT 
    DATE(created_at) as sale_date,
    COUNT(*) as total_transactions,
    SUM(subtotal) as total_subtotal,
    SUM(discount) as total_discount,
    SUM(tax) as total_tax,
    SUM(total) as total_sales,
    AVG(total) as avg_transaction
FROM transactions
WHERE status = 'completed'
GROUP BY DATE(created_at)
ORDER BY sale_date DESC;

-- =====================================================
-- STORED PROCEDURES
-- =====================================================

DELIMITER //

-- Procedure: Daily Sales
DROP PROCEDURE IF EXISTS `sp_daily_sales` //
CREATE PROCEDURE `sp_daily_sales`(IN report_date DATE)
BEGIN
    SELECT 
        COUNT(*) as total_transactions,
        COALESCE(SUM(subtotal), 0) as total_subtotal,
        COALESCE(SUM(discount), 0) as total_discount,
        COALESCE(SUM(tax), 0) as total_tax,
        COALESCE(SUM(total), 0) as total_sales,
        COALESCE(AVG(total), 0) as avg_transaction
    FROM transactions
    WHERE DATE(created_at) = report_date
    AND status = 'completed';
END //

-- Procedure: Monthly Sales
DROP PROCEDURE IF EXISTS `sp_monthly_sales` //
CREATE PROCEDURE `sp_monthly_sales`(IN report_year INT, IN report_month INT)
BEGIN
    SELECT 
        DATE(created_at) as sale_date,
        COUNT(*) as total_transactions,
        SUM(total) as daily_sales
    FROM transactions
    WHERE YEAR(created_at) = report_year
    AND MONTH(created_at) = report_month
    AND status = 'completed'
    GROUP BY DATE(created_at)
    ORDER BY sale_date;
END //

-- Procedure: Top Products
DROP PROCEDURE IF EXISTS `sp_top_products` //
CREATE PROCEDURE `sp_top_products`(IN days_back INT, IN limit_rows INT)
BEGIN
    SELECT 
        p.id,
        p.name,
        p.image,
        c.name as category_name,
        SUM(ti.quantity) as qty_sold,
        SUM(ti.subtotal) as revenue,
        p.price,
        p.stock
    FROM transaction_items ti
    JOIN products p ON ti.product_id = p.id
    LEFT JOIN categories c ON p.category_id = c.id
    JOIN transactions t ON ti.transaction_id = t.id
    WHERE t.created_at >= DATE_SUB(NOW(), INTERVAL days_back DAY)
    AND t.status = 'completed'
    GROUP BY p.id
    ORDER BY qty_sold DESC
    LIMIT limit_rows;
END //

DELIMITER ;

-- =====================================================
-- INDEXES OPTIMIZATION
-- =====================================================

-- Sudah dibuat di CREATE TABLE
-- Tambahan index jika diperlukan:

-- Composite indexes untuk query yang sering digunakan
ALTER TABLE transactions ADD INDEX idx_date_status (created_at, status);
ALTER TABLE products ADD INDEX idx_active_stock (is_active, stock);

-- =====================================================
-- RESET AUTO INCREMENT
-- =====================================================

ALTER TABLE users AUTO_INCREMENT = 4;
ALTER TABLE categories AUTO_INCREMENT = 6;
ALTER TABLE products AUTO_INCREMENT = 16;
ALTER TABLE members AUTO_INCREMENT = 4;
ALTER TABLE transactions AUTO_INCREMENT = 4;
ALTER TABLE transaction_items AUTO_INCREMENT = 7;
ALTER TABLE stock_history AUTO_INCREMENT = 1;
ALTER TABLE expenses AUTO_INCREMENT = 5;
ALTER TABLE activity_logs AUTO_INCREMENT = 1;
ALTER TABLE settings AUTO_INCREMENT = 13;
ALTER TABLE kitchen_orders AUTO_INCREMENT = 1;

-- =====================================================
-- VERIFICATION
-- =====================================================

SELECT 'Database schema created successfully!' as status;

-- Show all tables
SHOW TABLES;

-- Show table counts
SELECT 
    'users' as table_name, COUNT(*) as records FROM users
UNION ALL SELECT 'categories', COUNT(*) FROM categories
UNION ALL SELECT 'products', COUNT(*) FROM products
UNION ALL SELECT 'members', COUNT(*) FROM members
UNION ALL SELECT 'transactions', COUNT(*) FROM transactions
UNION ALL SELECT 'transaction_items', COUNT(*) FROM transaction_items
UNION ALL SELECT 'expenses', COUNT(*) FROM expenses
UNION ALL SELECT 'settings', COUNT(*) FROM settings;

COMMIT;

-- =====================================================
-- END OF DATABASE SCHEMA
-- =====================================================
