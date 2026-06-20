<?php
/**
 * Delete Staff - Teddy Shine Laundry Management System
 * 
 * Handles staff deletion with confirmation and order check
 */

require_once '../../includes/admin_check.php';
require_once '../../config/database.php';
require_once '../../config/functions.php';

$staff_id = intval($_GET['id'] ?? 0);

// Check if staff exists
$check_query = "SELECT * FROM Staff WHERE Staff_ID = $staff_id";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) == 0) {
    setFlashMessage("Staff member not found.", "error");
    redirect(BASE_URL . "/admin/staff/staff.php");
}

// Check if staff has assigned orders
$orders_query = "SELECT COUNT(*) as count FROM Orders WHERE Staff_ID = $staff_id";
$orders_result = mysqli_query($conn, $orders_query);
$orders_count = mysqli_fetch_assoc($orders_result)['count'];

if ($orders_count > 0) {
    setFlashMessage("Cannot delete staff member with $orders_count assigned orders. Reassign orders first.", "error");
    redirect(BASE_URL . "/admin/staff/staff.php");
}

// Delete staff
$delete_query = "DELETE FROM Staff WHERE Staff_ID = $staff_id";
if (mysqli_query($conn, $delete_query)) {
    setFlashMessage("Staff member deleted successfully.", "success");
} else {
    setFlashMessage("Error deleting staff member.", "error");
}

redirect(BASE_URL . "/admin/staff/staff.php");
?>