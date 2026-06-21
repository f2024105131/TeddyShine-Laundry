<?php

/**
 * Authentication Checker  

 * Verifies user authentication before accessing protected pages
 */

// Load required files
require_once __DIR__ . '/../config/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    // Store the current URL to redirect back after login
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];

    // Set flash message
    setFlashMessage('Please login to access this page.', 'warning');

    // Redirect to login page
    header("Location: " . BASE_URL . "/public/login.php");
    exit();
}

// Optional: Check if session is expired (last activity check)
$session_timeout = 3600; // 1 hour
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $session_timeout) {
    // Session expired
    session_unset();
    session_destroy();

    setFlashMessage('Your session has expired. Please login again.', 'warning');
    header("Location: " . BASE_URL . "/public/login.php");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();
