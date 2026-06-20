<?php
/**
 * Delete Delivery Slot - Teddy Shine Laundry Management System
 * 
 * Handles slot deletion with order check
 */

require_once '../../includes/admin_check.php';
require_once '../../config/database.php';
require_once '../../config/functions.php';

$slot_id = intval($_GET['id'] ?? 0);

// Check if slot exists
$check_query = "SELECT * FROM DeliverySlots WHERE Slot_ID = $slot_id";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) == 0) {
    setFlashMessage("Slot not found.", "error");
    redirect(BASE_URL . "/admin/slots/slots.php");
}

// Check if slot is used in orders
$order_check = "SELECT COUNT(*) as count FROM Orders WHERE Slot_ID = $slot_id";
$order_result = mysqli_query($conn, $order_check);
$used_count = mysqli_fetch_assoc($order_result)['count'];

if ($used_count > 0) {
    setFlashMessage("Cannot delete slot that has been used in $used_count orders.", "error");
    redirect(BASE_URL . "/admin/slots/slots.php");
}

// Delete slot
$delete_query = "DELETE FROM DeliverySlots WHERE Slot_ID = $slot_id";
if (mysqli_query($conn, $delete_query)) {
    setFlashMessage("Slot deleted successfully.", "success");
} else {
    setFlashMessage("Error deleting slot.", "error");
}

redirect(BASE_URL . "/admin/slots/slots.php");
?>