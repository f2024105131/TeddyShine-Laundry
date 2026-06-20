<?php

/**
 * Save Tracking -Saves tracking updates from staff
 */

require_once '../includes/auth_check.php';
require_once '../config/database.php';
require_once '../config/functions.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    redirect(BASE_URL . "/staff/dashboard.php");
}

$order_id = intval($_POST['order_id']);
$complete_stages = $_POST['complete_stage'] ?? [];

$updates_made = 0;

foreach ($complete_stages as $item_id => $stages) {
    foreach ($stages as $stage_id => $value) {
        if ($value == 1) {
            // Update tracking to completed
            $update_query = "UPDATE Tracking 
                            SET End_Time = NOW(), Status = 'Completed' 
                            WHERE Item_ID = $item_id AND Stage_ID = $stage_id AND Status != 'Completed'";
            if (mysqli_query($conn, $update_query)) {
                $updates_made++;
            }

            // Check if all stages for this item are completed
            $check_query = "SELECT COUNT(*) as total, 
                           SUM(CASE WHEN Status = 'Completed' THEN 1 ELSE 0 END) as completed 
                           FROM Tracking WHERE Item_ID = $item_id";
            $check_result = mysqli_query($conn, $check_query);
            $check_data = mysqli_fetch_assoc($check_result);

            if ($check_data['total'] == $check_data['completed']) {
                // All stages completed - update order status
                $order_update = "UPDATE Orders SET Status = 'Completed' WHERE Order_ID = $order_id";
                mysqli_query($conn, $order_update);
            }
        }
    }
}

setFlashMessage("Tracking updated successfully! $updates_made stage(s) completed.", "success");
redirect(BASE_URL . "/staff/update_tracking.php?order_id=$order_id");
