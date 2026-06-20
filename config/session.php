<?php
/**
 * Session Management - Teddy Shine Laundry Management System
 * 
 * Handles user sessions, authentication, and role management
 */

// Set session configuration for security
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS
ini_set('session.cookie_samesite', 'Strict');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerate session ID periodically to prevent fixation
if (!isset($_SESSION['last_regeneration'])) {
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
} elseif (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutes
    session_regenerate_id(true);
    $_SESSION['last_regeneration'] = time();
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && 
           isset($_SESSION['logged_in']) && 
           $_SESSION['logged_in'] === true;
}

/**
 * Get user role
 * @return string|null
 */
function getUserRole() {
    return $_SESSION['role'] ?? null;
}

/**
 * Get user ID
 * @return int|null
 */
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get resident ID
 * @return int|null
 */
function getResidentId() {
    return $_SESSION['resident_id'] ?? null;
}

/**
 * Get staff ID
 * @return int|null
 */
function getStaffId() {
    return $_SESSION['staff_id'] ?? null;
}

/**
 * Get user name
 * @return string
 */
function getUserName() {
    return $_SESSION['user_name'] ?? 'Guest';
}

/**
 * Get user email
 * @return string|null
 */
function getUserEmail() {
    return $_SESSION['email'] ?? null;
}

/**
 * Check if user has specific role
 * @param string|array $roles
 * @return bool
 */
function hasRole($roles) {
    if (!isLoggedIn()) return false;
    if (is_array($roles)) {
        return in_array($_SESSION['role'], $roles);
    }
    return $_SESSION['role'] === $roles;
}

/**
 * Require authentication (redirect if not logged in)
 * @param string|array $roles Optional role requirement
 */
function requireAuth($roles = null) {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        setFlashMessage('Please login to access this page.', 'warning');
        header("Location: " . BASE_URL . "/public/login.php");
        exit();
    }
    
    if ($roles !== null && !hasRole($roles)) {
        setFlashMessage('Access denied. Insufficient permissions.', 'error');
        header("Location: " . BASE_URL . "/" . strtolower($_SESSION['role']) . "/dashboard.php");
        exit();
    }
}

/**
 * Destroy session (logout)
 */
function logout() {
    // Clear all session variables
    $_SESSION = array();
    
    // Delete session cookie
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Destroy session
    session_destroy();
}

/**
 * Set flash message
 * @param string $message
 * @param string $type (success, error, warning, info)
 */
function setFlashMessage($message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
}

/**
 * Get flash message
 * @return array|null
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'text' => $_SESSION['flash_message'],
            'type' => $_SESSION['flash_type'] ?? 'success'
        ];
        unset($_SESSION['flash_message']);
        unset($_SESSION['flash_type']);
        return $message;
    }
    return null;
}

/**
 * Display flash message (called in header)
 */
function displayMessage() {
    $flash = getFlashMessage();
    if ($flash) {
        $icons = [
            'success' => 'fa-check-circle',
            'error' => 'fa-exclamation-circle',
            'warning' => 'fa-exclamation-triangle',
            'info' => 'fa-info-circle'
        ];
        $icon = $icons[$flash['type']] ?? 'fa-info-circle';
        $alertClass = $flash['type'] === 'success' ? 'alert-success' : 
                     ($flash['type'] === 'error' ? 'alert-danger' : 
                     ($flash['type'] === 'warning' ? 'alert-warning' : 'alert-info'));
        
        echo '<div class="alert ' . $alertClass . ' alert-dismissible fade show shadow-sm" role="alert" style="border-radius: 10px;">
                <i class="fas ' . $icon . ' me-2"></i> ' . htmlspecialchars($flash['text']) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>';
    }
}

/**
 * Get CSRF token for forms
 * @return string
 */
function getCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 * @param string $token
 * @return bool
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Check session timeout
 * @param int $timeout Timeout in seconds (default: 3600 = 1 hour)
 * @return bool
 */
function isSessionExpired($timeout = 3600) {
    if (isset($_SESSION['last_activity'])) {
        return (time() - $_SESSION['last_activity']) > $timeout;
    }
    return true;
}

/**
 * Update session activity time
 */
function updateSessionActivity() {
    $_SESSION['last_activity'] = time();
}
?>