<?php
/**
 * Delete Service - Teddy Shine Laundry Management System
 * 
 * Handles service deletion with order check
 */

require_once '../../includes/admin_check.php';
require_once '../../config/database.php';
require_once '../../config/functions.php';

$service_id = intval($_GET['id'] ?? 0);

// Check if service exists
$check_query = "SELECT * FROM Services WHERE Service_ID = $service_id";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) == 0) {
    setFlashMessage("Service not found.", "error");
    redirect(BASE_URL . "/admin/services/services.php");
}

// Check if service is used in orders
$order_check = "SELECT COUNT(*) as count FROM OrderItems WHERE Service_ID = $service_id";
$order_result = mysqli_query($conn, $order_check);
$used_count = mysqli_fetch_assoc($order_result)['count'];

if ($used_count > 0) {
    setFlashMessage("Cannot delete service that has been used in $used_count orders. Deactivate instead.", "error");
    redirect(BASE_URL . "/admin/services/services.php");
}

// Delete service
$delete_query = "DELETE FROM Services WHERE Service_ID = $service_id";
if (mysqli_query($conn, $delete_query)) {
    setFlashMessage("Service deleted successfully.", "success");
} else {
    setFlashMessage("Error deleting service.", "error");
}

redirect(BASE_URL . "/admin/services/services.php");
?>