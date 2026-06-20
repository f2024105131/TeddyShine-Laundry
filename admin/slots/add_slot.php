<?php
/**
 * Add Delivery Slot - Teddy Shine Laundry Management System
 * 
 * Form to add new delivery time slots
 */

require_once '../../includes/admin_check.php';
require_once '../../config/database.php';
require_once '../../config/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type = sanitize($_POST['type']);
    $start_time = sanitize($_POST['start_time']);
    $end_time = sanitize($_POST['end_time']);
    $max_orders = intval($_POST['max_orders']);
    
    // Validation
    if (empty($type)) {
        $error = "Slot type is required";
    } elseif (empty($start_time) || empty($end_time)) {
        $error = "Start time and end time are required";
    } elseif ($start_time >= $end_time) {
        $error = "End time must be after start time";
    } elseif ($max_orders < 1) {
        $error = "Maximum orders must be at least 1";
    } else {
        $query = "INSERT INTO DeliverySlots (Slot_Type, Start_Time, End_Time, Max_Orders) 
                  VALUES ('$type', '$start_time', '$end_time', $max_orders)";
        
        if (mysqli_query($conn, $query)) {
            setFlashMessage("Delivery slot added successfully!", "success");
            redirect(BASE_URL . "/admin/slots/slots.php");
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}

$custom_title = "Add Delivery Slot - Teddy Shine";
include_once '../../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-plus-circle"></i> Add Delivery Slot</h5>
                </div>
                <div class="card-body">
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Slot Type <span class="text-danger">*</span></label>
                            <input type="text" name="type" class="form-control" placeholder="e.g., Morning, Afternoon, Evening" required>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Start Time <span class="text-danger">*</span></label>
                                <input type="time" name="start_time" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">End Time <span class="text-danger">*</span></label>
                                <input type="time" name="end_time" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Maximum Orders <span class="text-danger">*</span></label>
                            <input type="number" name="max_orders" class="form-control" value="10" min="1" required>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Add Slot
                            </button>
                            <a href="slots.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?>