<?php
/**
 * This file contains the HTML head, navigation bar, and global styles
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Define base URL if not defined
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/TeddyShine_Laundry');
}

// Get current page for active menu highlighting
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Page title dynamic generation
$page_title = "Teddy Shine - Premium Laundry Service";
if (isset($custom_title) && !empty($custom_title)) {
    $page_title = $custom_title;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Teddy Shine Laundry Management System - Professional laundry and dry cleaning services">
    <meta name="keywords" content="laundry, dry cleaning, laundry service, teddy shine">
    <meta name="author" content="Teddy Shine Team">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?php echo BASE_URL; ?>/assets/img/logo/favicon.ico">
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="<?php echo BASE_URL; ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>

<!-- Navigation Bar -->
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="<?php echo BASE_URL; ?>/public/index.php">
            <i class="fas fa-tshirt"></i> 
            <span>Teddy Shine</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <?php if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true): ?>
                    <!-- Logged in menu items -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>/<?php echo strtolower($_SESSION['role']); ?>/dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    
                    <?php if ($_SESSION['role'] == 'resident'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'place_order.php') ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>/resident/place_order.php">
                                <i class="fas fa-plus-circle"></i> New Order
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'my_orders.php') ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>/resident/my_orders.php">
                                <i class="fas fa-list"></i> My Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'services.php') ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>/resident/services.php">
                                <i class="fas fa-tags"></i> Services
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php if ($_SESSION['role'] == 'admin'): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="ordersDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-box"></i> Orders
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/orders/orders.php">All Orders</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/orders/orders.php?status=Pending">Pending Orders</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/invoices/invoices.php">Invoices</a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="managementDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-cog"></i> Management
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/staff/staff.php"><i class="fas fa-users"></i> Staff</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/services/services.php"><i class="fas fa-tags"></i> Services</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/slots/slots.php"><i class="fas fa-clock"></i> Delivery Slots</a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/residents.php"><i class="fas fa-user-friends"></i> Residents</a></li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>" 
                               href="<?php echo BASE_URL; ?>/admin/reports/reports.php">
                                <i class="fas fa-chart-bar"></i> Reports
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php if ($_SESSION['role'] == 'staff'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/staff/assigned_orders.php">
                                <i class="fas fa-clipboard-list"></i> Assigned Orders
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>/staff/delivery_list.php">
                                <i class="fas fa-truck"></i> Deliveries
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <!-- User Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <div class="user-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <span class="ms-2"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><h6 class="dropdown-header">Signed in as</h6></li>
                            <li><a class="dropdown-item text-muted small"><?php echo htmlspecialchars($_SESSION['email'] ?? ''); ?></a></li>
                            <li><hr class="dropdown-divider"></li>
                            <?php if ($_SESSION['role'] == 'resident'): ?>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/resident/profile.php">
                                    <i class="fas fa-user-edit fa-fw me-2"></i> Edit Profile
                                </a></li>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/resident/payment_history.php">
                                    <i class="fas fa-history fa-fw me-2"></i> Payment History
                                </a></li>
                            <?php endif; ?>
                            <?php if ($_SESSION['role'] == 'admin'): ?>
                                <li><a class="dropdown-item" href="<?php echo BASE_URL; ?>/admin/residents.php">
                                    <i class="fas fa-users fa-fw me-2"></i> Manage Residents
                                </a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo BASE_URL; ?>/public/logout.php">
                                <i class="fas fa-sign-out-alt fa-fw me-2"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <!-- Guest menu items -->
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>/public/index.php">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo BASE_URL; ?>/public/index.php#services">
                            <i class="fas fa-tags"></i> Services
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($current_page == 'login.php') ? 'active' : ''; ?>" 
                           href="<?php echo BASE_URL; ?>/public/login.php">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-light text-primary ms-2" href="<?php echo BASE_URL; ?>/public/register.php">
                            <i class="fas fa-user-plus"></i> Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Main Content Container -->
<main class="flex-grow-1">
    <div class="container mt-4">
        <!-- Display Flash Messages -->
        <?php 
        if (function_exists('displayMessage')) {
            displayMessage();
        }
        ?>