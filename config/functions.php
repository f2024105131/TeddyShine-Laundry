<?php
/**
 * Helper Functions 
 * Contains common functions used throughout the application
 */

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/session.php';

// Sanitization & Validation Functions
/**
 * Sanitize input data (XSS prevention)
 * @param string|array $data
 * @return string|array
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    $conn = Database::getConnection();
    if ($conn) {
        $data = mysqli_real_escape_string($conn, trim(htmlspecialchars($data, ENT_QUOTES, 'UTF-8')));
    } else {
        $data = htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    return $data;
}

/**
 * Validate email format
 * @param string $email
 * @return bool
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone number (Pakistan format: 03xxxxxxxxx)
 * @param string $phone
 * @return bool
 */
function validatePhone($phone) {
    return preg_match('/^03[0-9]{9}$/', $phone);
}

/**
 * Validate password strength (min 6 chars, at least 1 letter and 1 number)
 * @param string $password
 * @return bool
 */
function validatePassword($password) {
    if (strlen($password) < 6) return false;
    if (!preg_match('/[A-Za-z]/', $password)) return false;
    if (!preg_match('/[0-9]/', $password)) return false;
    return true;
}
// Database Helper Functions

/**
 * Get resident name by ID
 * @param int $resident_id
 * @return string
 */
function getResidentName($resident_id) {
    $conn = Database::getConnection();
    $stmt = mysqli_prepare($conn, "SELECT CONCAT(F_Name, ' ', L_Name) as name FROM Resident WHERE Resident_ID = ?");
    mysqli_stmt_bind_param($stmt, "i", $resident_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row['name'] ?? 'Unknown';
}

/**
 * Get service name by ID
 * @param int $service_id
 * @return string
 */
function getServiceName($service_id) {
    $conn = Database::getConnection();
    $stmt = mysqli_prepare($conn, "SELECT Service_Name FROM Services WHERE Service_ID = ?");
    mysqli_stmt_bind_param($stmt, "i", $service_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row['Service_Name'] ?? 'Unknown';
}

/**
 * Get service price by ID
 * @param int $service_id
 * @return float
 */
function getServicePrice($service_id) {
    $conn = Database::getConnection();
    $stmt = mysqli_prepare($conn, "SELECT Service_Price FROM Services WHERE Service_ID = ?");
    mysqli_stmt_bind_param($stmt, "i", $service_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row['Service_Price'] ?? 0;
}

/**
 * Get order total amount
 * @param int $order_id
 * @return float
 */
function getOrderTotal($order_id) {
    $conn = Database::getConnection();
    $stmt = mysqli_prepare($conn, "SELECT SUM(Price * Quantity) as total FROM OrderItems WHERE Order_ID = ?");
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row['total'] ?? 0;
}
// Display Functions (Badges, Statuses,)

/**
 * Get order status badge HTML
 * @param string $status
 * @return string
 */
function getStatusBadge($status) {
    $badges = [
        'Pending' => '<span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>Pending</span>',
        'Processing' => '<span class="badge bg-info"><i class="fas fa-spinner fa-spin me-1"></i>Processing</span>',
        'In Progress' => '<span class="badge bg-primary"><i class="fas fa-cogs me-1"></i>In Progress</span>',
        'Completed' => '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Completed</span>',
        'Delivered' => '<span class="badge bg-success"><i class="fas fa-truck me-1"></i>Delivered</span>',
        'Cancelled' => '<span class="badge bg-danger"><i class="fas fa-times-circle me-1"></i>Cancelled</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">' . $status . '</span>';
}

/**
 * Get payment status badge HTML
 * @param string $status
 * @return string
 */
function getPaymentStatusBadge($status) {
    $badges = [
        'Unpaid' => '<span class="badge bg-danger"><i class="fas fa-credit-card me-1"></i>Unpaid</span>',
        'Partial' => '<span class="badge bg-warning text-dark"><i class="fas fa-hourglass-half me-1"></i>Partial</span>',
        'Paid' => '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Paid</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">' . $status . '</span>';
}
// Formatting Functions


/**
 * Format currency
 * @param float $amount
 * @return string
 */
function formatCurrency($amount) {
    return 'Rs. ' . number_format($amount, 2);
}

/**
 * Format date
 * @param string $date
 * @param string $format
 * @return string
 */
function formatDate($date, $format = 'd M Y') {
    if (!$date || $date == '0000-00-00' || $date == '0000-00-00 00:00:00') return '-';
    return date($format, strtotime($date));
}

/**
 * Generate invoice number
 * @param int $invoice_id
 * @return string
 */
function generateInvoiceNumber($invoice_id) {
    return 'INV-' . date('Y') . '-' . str_pad($invoice_id, 6, '0', STR_PAD_LEFT);
}

/**
 * Generate order number
 * @param int $order_id
 * @return string
 */
function generateOrderNumber($order_id) {
    return 'ORD-' . str_pad($order_id, 6, '0', STR_PAD_LEFT);
}
// Redirect & Navigation Functions


/**
 * Redirect with optional flash message
 * @param string $url
 * @param string $message
 * @param string $type
 */
function redirect($url, $message = '', $type = 'success') {
    if ($message) {
        setFlashMessage($message, $type);
    }
    header("Location: $url");
    exit();
}

// Dashboard Statistics Functions


/**
 * Get admin dashboard statistics
 * @return array
 */
function getAdminStats() {
    $conn = Database::getConnection();
    $stats = [];
    
    // Total orders
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM Orders");
    $stats['total_orders'] = mysqli_fetch_assoc($result)['count'] ?? 0;
    
    // Total residents
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM Resident");
    $stats['total_residents'] = mysqli_fetch_assoc($result)['count'] ?? 0;
    
    // Total revenue
    $result = mysqli_query($conn, "SELECT SUM(Payment_Amount) as total FROM Payments WHERE Payment_Status = 'Completed'");
    $stats['total_revenue'] = mysqli_fetch_assoc($result)['total'] ?? 0;
    
    // Pending orders
    $result = mysqli_query($conn, "SELECT COUNT(*) as count FROM Orders WHERE Status IN ('Pending', 'Processing')");
    $stats['pending_orders'] = mysqli_fetch_assoc($result)['count'] ?? 0;
    
    // Monthly revenue
    $result = mysqli_query($conn, "SELECT SUM(Payment_Amount) as total FROM Payments 
                                   WHERE MONTH(Payment_Date) = MONTH(CURDATE()) 
                                   AND YEAR(Payment_Date) = YEAR(CURDATE())");
    $stats['monthly_revenue'] = mysqli_fetch_assoc($result)['total'] ?? 0;
    
    return $stats;
}

/**
 * Get resident dashboard statistics
 * @param int $resident_id
 * @return array
 */
function getResidentStats($resident_id) {
    $conn = Database::getConnection();
    $stats = [];
    
    // Total orders
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM Orders WHERE Resident_ID = ?");
    mysqli_stmt_bind_param($stmt, "i", $resident_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $stats['total_orders'] = mysqli_fetch_assoc($result)['count'] ?? 0;
    
    // Pending orders
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM Orders WHERE Resident_ID = ? AND Status IN ('Pending', 'Processing')");
    mysqli_stmt_bind_param($stmt, "i", $resident_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $stats['pending_orders'] = mysqli_fetch_assoc($result)['count'] ?? 0;
    
    // Completed orders
    $stmt = mysqli_prepare($conn, "SELECT COUNT(*) as count FROM Orders WHERE Resident_ID = ? AND Status = 'Completed'");
    mysqli_stmt_bind_param($stmt, "i", $resident_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $stats['completed_orders'] = mysqli_fetch_assoc($result)['count'] ?? 0;
    
    // Total spent
    $stmt = mysqli_prepare($conn, "SELECT SUM(Amount) as total FROM Orders WHERE Resident_ID = ?");
    mysqli_stmt_bind_param($stmt, "i", $resident_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $stats['total_spent'] = mysqli_fetch_assoc($result)['total'] ?? 0;
    
    mysqli_stmt_close($stmt);
    return $stats;
}
// Activity Logging

/**
 * Log user activity for audit trail
 * @param string $action
 * @param string $details
 */
function logActivity($action, $details = '') {
    $conn = Database::getConnection();
    if (!$conn) return;
    
    $user_id = getUserId() ?? 0;
    $user_role = getUserRole() ?? 'guest';
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $stmt = mysqli_prepare($conn, "INSERT INTO ActivityLog (User_ID, User_Role, Action, Details, IP_Address, User_Agent, Created_At) 
                                   VALUES (?, ?, ?, ?, ?, ?, NOW())");
    mysqli_stmt_bind_param($stmt, "isssss", $user_id, $user_role, $action, $details, $ip, $user_agent);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}
/**
 * Send email (development version - logs instead of sending)
 * @param string $to
 * @param string $subject
 * @param string $message
 * @return bool
 */
function sendEmail($to, $subject, $message) {
    // For development, log the email
    error_log("EMAIL TO: $to | SUBJECT: $subject | MESSAGE: $message");
    return true;
}
/**
 * Get tracking progress percentage for an order
 * @param int $order_id
 * @return int
 */
function getTrackingProgress($order_id) {
    $conn = Database::getConnection();
    $stmt = mysqli_prepare($conn, "SELECT COUNT(DISTINCT t.Stage_ID) as completed 
                                   FROM Tracking t 
                                   JOIN OrderItems oi ON t.Item_ID = oi.Item_ID
                                   WHERE oi.Order_ID = ? AND t.Status = 'Completed'");
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    $total_stages = 5; // Washing, Drying, Ironing, Packing, Delivery
    return round(($row['completed'] / $total_stages) * 100);
}
/**
 * Get order status options for dropdown
 * @return array
 */
function getOrderStatusOptions() {
    return [
        'Pending' => 'Pending',
        'Processing' => 'Processing',
        'In Progress' => 'In Progress',
        'Completed' => 'Completed',
        'Delivered' => 'Delivered',
        'Cancelled' => 'Cancelled'
    ];
}

/**
 * Get delivery slot options for dropdown
 * @return array
 */
function getDeliverySlots() {
    $conn = Database::getConnection();
    $slots = [];
    $result = mysqli_query($conn, "SELECT * FROM DeliverySlots ORDER BY Start_Time");
    while ($row = mysqli_fetch_assoc($result)) {
        $slots[$row['Slot_ID']] = $row['Slot_Type'] . ' (' . date('h:i A', strtotime($row['Start_Time'])) . ' - ' . date('h:i A', strtotime($row['End_Time'])) . ')';
    }
    return $slots;
}

/**
 * Check if a service is available
 * @param int $service_id
 * @return bool
 */
function isServiceAvailable($service_id) {
    $conn = Database::getConnection();
    $stmt = mysqli_prepare($conn, "SELECT Status FROM Services WHERE Service_ID = ?");
    mysqli_stmt_bind_param($stmt, "i", $service_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row && $row['Status'] === 'Active';
}
?>