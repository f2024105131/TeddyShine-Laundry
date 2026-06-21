<?php
/**
 * Logout Handler - Teddy Shine Laundry Management System
 * 
 * Destroys user session and redirects to login page
 */

require_once '../config/session.php';
require_once '../config/functions.php';

// Clear remember me cookie if exists
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Clear all session data
logout();

// Set flash message
setFlashMessage('You have been successfully logged out.', 'info');

// Redirect to home page
redirect(BASE_URL . "/public/index.php");
exit();
?>