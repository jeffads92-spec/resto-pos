<?php
/**
 * Configuration File - Smart Resto POS
 * Version: Simple & Safe
 */

// Enable error reporting (DEVELOPMENT ONLY - Disable di production!)
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');  // Ganti jika MySQL Anda pakai password
define('DB_NAME', 'smart_resto_pos');

// Application Configuration
define('APP_NAME', 'Smart Resto POS');
define('APP_VERSION', '2.0');

// Base URL - SESUAIKAN dengan folder Anda
// Contoh: http://localhost/resto-pos/
// Atau: http://localhost/smart-pos/
define('BASE_URL', 'http://localhost/resto-pos/');

// Upload Configuration
define('UPLOAD_PATH', 'uploads/products/');
define('MAX_FILE_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Default Images
define('NO_IMAGE', 'assets/images/no-image.png');

// System Settings
define('TAX_RATE', 10); // 10%
define('POINTS_PER_RUPIAH', 1000); // 1 point per 1000 rupiah
define('TIMEZONE', 'Asia/Jakarta');

// Set timezone
date_default_timezone_set(TIMEZONE);

// Session Configuration - JANGAN START SESSION DI CONFIG!
// Session akan di-start di setiap file yang membutuhkan

// Database Connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if ($conn->connect_error) {
        die("
        <!DOCTYPE html>
        <html>
        <head>
            <title>Database Error</title>
            <style>
                body { font-family: Arial; padding: 50px; background: #f5f5f5; }
                .error-box { 
                    background: white; 
                    padding: 30px; 
                    border-radius: 10px; 
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    max-width: 600px;
                    margin: 0 auto;
                }
                h1 { color: #e74c3c; }
                .detail { 
                    background: #f8f9fa; 
                    padding: 15px; 
                    border-radius: 5px; 
                    margin-top: 20px;
                    font-family: monospace;
                }
                .solution {
                    background: #fff3cd;
                    padding: 15px;
                    border-radius: 5px;
                    margin-top: 20px;
                    border-left: 4px solid #ffc107;
                }
            </style>
        </head>
        <body>
            <div class='error-box'>
                <h1>❌ Database Connection Error</h1>
                <p>Tidak dapat terhubung ke database.</p>
                
                <div class='detail'>
                    <strong>Error:</strong> " . $conn->connect_error . "
                </div>
                
                <div class='solution'>
                    <strong>💡 Solusi:</strong><br>
                    1. Pastikan MySQL sudah running di XAMPP<br>
                    2. Cek username & password di config.php<br>
                    3. Pastikan database 'smart_resto_pos' sudah dibuat<br>
                    4. Cek di phpMyAdmin: <a href='http://localhost/phpmyadmin'>localhost/phpmyadmin</a>
                </div>
            </div>
        </body>
        </html>
        ");
    }
    
    // Set charset
    $conn->set_charset("utf8mb4");
    
    // Success - no output
    
} catch (Exception $e) {
    die("
    <!DOCTYPE html>
    <html>
    <head>
        <title>Database Error</title>
        <style>
            body { font-family: Arial; padding: 50px; background: #f5f5f5; }
            .error-box { 
                background: white; 
                padding: 30px; 
                border-radius: 10px; 
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                max-width: 600px;
                margin: 0 auto;
            }
            h1 { color: #e74c3c; }
            .detail { 
                background: #f8f9fa; 
                padding: 15px; 
                border-radius: 5px; 
                margin-top: 20px;
                font-family: monospace;
            }
        </style>
    </head>
    <body>
        <div class='error-box'>
            <h1>❌ Database Exception</h1>
            <div class='detail'>" . $e->getMessage() . "</div>
        </div>
    </body>
    </html>
    ");
}

// Helper Functions
function get_product_image($image_name) {
    if (empty($image_name)) {
        return NO_IMAGE;
    }
    
    $image_path = UPLOAD_PATH . $image_name;
    
    if (file_exists($image_path)) {
        return $image_path;
    }
    
    return NO_IMAGE;
}

function format_rupiah($amount) {
    return 'Rp ' . number_format($amount, 0, ',', '.');
}

function generate_invoice() {
    return 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

// Auto-load settings dari database (optional)
try {
    $settings_query = "SELECT setting_key, setting_value FROM settings";
    $settings_result = $conn->query($settings_query);
    
    if ($settings_result) {
        while ($row = $settings_result->fetch_assoc()) {
            $key = strtoupper($row['setting_key']);
            if (!defined($key)) {
                define($key, $row['setting_value']);
            }
        }
    }
} catch (Exception $e) {
    // Ignore if settings table doesn't exist yet
}

?>
