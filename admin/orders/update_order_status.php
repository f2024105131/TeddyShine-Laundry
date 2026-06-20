<?php
/**
 * Update Order Status - Teddy Shine Laundry Management System
 * 
 * AJAX handler for updating order status
 */

require_once '../../includes/admin_check.php';
require_once '../../config/database.php';
require_once '../../config/functions.php';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $order_id = intval($_POST['order_id']);
    $status = sanitize($_POST['status']);
    
    // Validate status
    $valid_statuses = ['Pending', 'Processing', 'In Progress', 'Completed', 'Delivered', 'Cancelled'];
    if(!in_array($status, $valid_statuses)) {
        echo json_encode(['success' => false, 'message' => 'Invalid status']);
        exit();
    }
    
    $query = "UPDATE Orders SET Status = '$status' WHERE Order_ID = $order_id";
    if(mysqli_query($conn, $query)) {
        // Log activity
        logActivity('order_status_update', "Order #$order_id status updated to $status");
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>