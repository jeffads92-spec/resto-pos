<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Resto POS</title>
    
    <!-- jQuery (PENTING: Load duluan!) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        
        .navbar-custom {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
            padding: 1rem 2rem;
        }
        
        .navbar-brand {
            font-weight: 800;
            font-size: 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .nav-link {
            color: #2d3748 !important;
            font-weight: 600;
            padding: 0.5rem 1rem !important;
            border-radius: 50px;
            transition: all 0.3s ease;
        }
        
        .nav-link:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
        }
        
        .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
        }
        
        .dropdown-menu {
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            border: none;
            margin-top: 0.5rem;
        }
        
        .dropdown-item {
            padding: 0.75rem 1.5rem;
            transition: all 0.3s ease;
        }
        
        .dropdown-item:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
        }
        
        .dropdown-item i {
            width: 20px;
            margin-right: 0.5rem;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light navbar-custom">
    <div class="container-fluid">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-utensils"></i> Smart Resto POS
        </a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="index.php"><i class="fas fa-chart-line"></i> Dashboard</a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="pos.php"><i class="fas fa-cash-register"></i> POS</a>
                </li>
                
                <!-- DROPDOWN PRODUK - MANUAL TRIGGER -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="dropdownProduk" role="button">
                        <i class="fas fa-box"></i> Produk
                    </a>
                    <ul class="dropdown-menu" id="menuProduk">
                        <li><a class="dropdown-item" href="products.php"><i class="fas fa-list"></i> Daftar Produk</a></li>
                        <li><a class="dropdown-item" href="inventory.php"><i class="fas fa-boxes"></i> Inventaris</a></li>
                    </ul>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="transactions.php"><i class="fas fa-receipt"></i> Transaksi</a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link" href="members.php"><i class="fas fa-users"></i> Member</a>
                </li>
                
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'admin'): ?>
                <!-- DROPDOWN ADMIN - MANUAL TRIGGER -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="dropdownAdmin" role="button">
                        <i class="fas fa-cog"></i> Admin
                    </a>
                    <ul class="dropdown-menu" id="menuAdmin">
                        <li><a class="dropdown-item" href="reports.php"><i class="fas fa-chart-bar"></i> Laporan</a></li>
                        <li><a class="dropdown-item" href="expenses.php"><i class="fas fa-money-bill"></i> Pengeluaran</a></li>
                        <li><a class="dropdown-item" href="users.php"><i class="fas fa-user-shield"></i> Kelola User</a></li>
                        <li><a class="dropdown-item" href="settings.php"><i class="fas fa-sliders-h"></i> Pengaturan</a></li>
                    </ul>
                </li>
                <?php endif; ?>
                
                <li class="nav-item">
                    <a class="nav-link" href="kitchen.php"><i class="fas fa-fire"></i> Dapur</a>
                </li>
            </ul>
            
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="dropdownUser" role="button">
                        <i class="fas fa-user-circle"></i> <?= $_SESSION['username'] ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" id="menuUser">
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<script>
// MANUAL DROPDOWN TOGGLE (PASTI JALAN!)
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initializing manual dropdowns...');
    
    // Dropdown Produk
    const dropdownProduk = document.getElementById('dropdownProduk');
    const menuProduk = document.getElementById('menuProduk');
    
    if (dropdownProduk && menuProduk) {
        dropdownProduk.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Toggle menu
            if (menuProduk.style.display === 'block') {
                menuProduk.style.display = 'none';
            } else {
                // Hide all other menus
                document.querySelectorAll('.dropdown-menu').forEach(m => m.style.display = 'none');
                menuProduk.style.display = 'block';
            }
        });
    }
    
    // Dropdown Admin
    const dropdownAdmin = document.getElementById('dropdownAdmin');
    const menuAdmin = document.getElementById('menuAdmin');
    
    if (dropdownAdmin && menuAdmin) {
        dropdownAdmin.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Toggle menu
            if (menuAdmin.style.display === 'block') {
                menuAdmin.style.display = 'none';
            } else {
                // Hide all other menus
                document.querySelectorAll('.dropdown-menu').forEach(m => m.style.display = 'none');
                menuAdmin.style.display = 'block';
            }
        });
    }
    
    // Dropdown User
    const dropdownUser = document.getElementById('dropdownUser');
    const menuUser = document.getElementById('menuUser');
    
    if (dropdownUser && menuUser) {
        dropdownUser.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Toggle menu
            if (menuUser.style.display === 'block') {
                menuUser.style.display = 'none';
            } else {
                // Hide all other menus
                document.querySelectorAll('.dropdown-menu').forEach(m => m.style.display = 'none');
                menuUser.style.display = 'block';
            }
        });
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                menu.style.display = 'none';
            });
        }
    });
    
    console.log('Manual dropdowns initialized!');
});
</script>
