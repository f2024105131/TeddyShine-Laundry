<?php
/**
 * Add Staff - Teddy Shine Laundry Management System
 * 
 * Form to add new staff members
 */

require_once '../../includes/admin_check.php';
require_once '../../config/database.php';
require_once '../../config/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $contact = sanitize($_POST['contact']);
    $email = sanitize($_POST['email']);
    $role = sanitize($_POST['role']);
    $shift_start = sanitize($_POST['shift_start']);
    $shift_end = sanitize($_POST['shift_end']);
    
    // Validation
    if (empty($name)) {
        $error = "Staff name is required";
    } else {
        // Insert into database
        $query = "INSERT INTO Staff (Staff_Name, Contact_No, Email, Role, Shift_Start, Shift_End) 
                  VALUES ('$name', '$contact', '$email', '$role', '$shift_start', '$shift_end')";
        
        if (mysqli_query($conn, $query)) {
            setFlashMessage("Staff member added successfully!", "success");
            redirect(BASE_URL . "/admin/staff/staff.php");
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}

$custom_title = "Add Staff - Teddy Shine";
include_once '../../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user-plus"></i> Add New Staff Member</h5>
                </div>
                <div class="card-body">
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact" class="form-control" placeholder="03xxxxxxxxx">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select name="role" class="form-select" required>
                                <option value="collector">Collector</option>
                                <option value="washer">Washer</option>
                                <option value="deliveryboy">Delivery Boy</option>
                            </select>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Shift Start</label>
                                <input type="time" name="shift_start" class="form-control" value="09:00">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Shift End</label>
                                <input type="time" name="shift_end" class="form-control" value="17:00">
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Add Staff
                            </button>
                            <a href="staff.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?>