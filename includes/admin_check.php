<?php
/**
 * Admin Access Checker
 * Verifies admin privileges before accessing admin pages
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load required files
require_once __DIR__ . '/../config/functions.php';

// First check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    setFlashMessage('Please login to access this page.', 'warning');
    header("Location: " . BASE_URL . "/public/login.php");
    exit();
}

// Check if user has admin role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    // Get user role for redirect
    $user_role = $_SESSION['role'] ?? 'guest';
    $user_name = $_SESSION['user_name'] ?? 'User';
    
    // Log unauthorized access attempt
    error_log("ADMIN ACCESS DENIED: User '$user_name' (Role: $user_role) attempted to access: " . $_SERVER['REQUEST_URI']);
    
    // Set appropriate message based on role
    if ($user_role === 'resident') {
        setFlashMessage('Access denied. Admin privileges required.', 'error');
        $redirect_url = BASE_URL . "/resident/dashboard.php";
    } elseif ($user_role === 'staff') {
        setFlashMessage('Access denied. Admin privileges required.', 'error');
        $redirect_url = BASE_URL . "/staff/dashboard.php";
    } else {
        setFlashMessage('Access denied. Please login with admin account.', 'error');
        $redirect_url = BASE_URL . "/public/login.php";
    }
    
    header("Location: $redirect_url");
    exit();
}
?>