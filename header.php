<?php
// ============================================
// HEADER & NAVIGATION
// File: header.php
// ============================================

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);
$is_admin = ($_SESSION['role'] === 'admin');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
        }
        
        body {
            background: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        
        .sidebar {
            background: white;
            min-height: calc(100vh - 56px);
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            padding: 20px 0;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                left: -250px;
                top: 56px;
                width: 250px;
                height: calc(100vh - 56px);
                z-index: 1000;
                transition: left 0.3s;
            }
            
            .sidebar.show {
                left: 0;
            }
            
            .main-content {
                margin-left: 0 !important;
            }
            
            .overlay {
                display: none;
                position: fixed;
                top: 56px;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: 999;
            }
            
            .overlay.show {
                display: block;
            }
        }
        
        .nav-link {
            color: #6c757d;
            padding: 12px 20px;
            margin: 5px 15px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .nav-link:hover {
            background: #f8f9fa;
            color: var(--primary-color);
        }
        
        .nav-link.active {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
        }
        
        .nav-link i {
            width: 20px;
            margin-right: 10px;
        }
        
        .main-content {
            padding: 20px;
        }
        
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        
        .card-header {
            background: white;
            border-bottom: 2px solid #f8f9fa;
            padding: 15px 20px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .stat-card {
            border-left: 4px solid;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .table {
            background: white;
        }
        
        .badge {
            padding: 5px 10px;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <button class="navbar-toggler me-2 d-lg-none" type="button" onclick="toggleSidebar()">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-utensils"></i>
                <?= APP_NAME ?>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-white" href="#" id="userDropdown" role="button" 
                           data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i>
                            <?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?>
                            <span class="badge bg-light text-dark ms-2">
                                <?= ucfirst($_SESSION['role'] ?? 'guest') ?>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <span class="dropdown-item-text">
                                    <strong><?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?></strong><br>
                                    <small class="text-muted"><?= htmlspecialchars($_SESSION['username'] ?? '') ?></small>
                                </span>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <?php if ($is_admin): ?>
                            <li>
                                <a class="dropdown-item" href="users.php">
                                    <i class="fas fa-users-cog me-2"></i>
                                    Manajemen User
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="settings.php">
                                    <i class="fas fa-cog me-2"></i>
                                    Pengaturan
                                </a>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <?php endif; ?>
                            <li>
                                <a class="dropdown-item text-danger" href="logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>
                                    Logout
                                </a>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Overlay untuk mobile -->
            <div class="overlay" id="overlay" onclick="toggleSidebar()"></div>
            
            <!-- Sidebar -->
            <nav class="col-md-2 d-md-block sidebar" id="sidebar">
                <div class="position-sticky">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page == 'index.php' ? 'active' : '' ?>" 
                               href="index.php">
                                <i class="fas fa-chart-line"></i>
                                Dashboard
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page == 'pos.php' ? 'active' : '' ?>" 
                               href="pos.php">
                                <i class="fas fa-cash-register"></i>
                                Point of Sale
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page == 'products.php' ? 'active' : '' ?>" 
                               href="products.php">
                                <i class="fas fa-box"></i>
                                Produk
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page == 'kitchen.php' ? 'active' : '' ?>" 
                               href="kitchen.php">
                                <i class="fas fa-utensils"></i>
                                Kitchen Display
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page == 'members.php' ? 'active' : '' ?>" 
                               href="members.php">
                                <i class="fas fa-users"></i>
                                Member
                            </a>
                        </li>
                        
                        <?php if ($is_admin): ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page == 'expenses.php' ? 'active' : '' ?>" 
                               href="expenses.php">
                                <i class="fas fa-money-bill-wave"></i>
                                Pengeluaran
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page == 'reports.php' ? 'active' : '' ?>" 
                               href="reports.php">
                                <i class="fas fa-file-alt"></i>
                                Laporan
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link <?= $current_page == 'users.php' ? 'active' : '' ?>" 
                               href="users.php">
                                <i class="fas fa-users-cog"></i>
                                Users
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <li class="nav-item mt-3">
                            <hr>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link text-danger" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i>
                                Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-10 ms-sm-auto main-content"><?php // Content starts here ?>