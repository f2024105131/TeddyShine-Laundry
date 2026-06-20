<?php
/**
 * Edit Staff - Teddy Shine Laundry Management System
 * 
 * Form to edit existing staff members
 */

require_once '../../includes/admin_check.php';
require_once '../../config/database.php';
require_once '../../config/functions.php';

$staff_id = intval($_GET['id'] ?? 0);

// Get staff data
$query = "SELECT * FROM Staff WHERE Staff_ID = $staff_id";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    setFlashMessage("Staff member not found.", "error");
    redirect(BASE_URL . "/admin/staff/staff.php");
}

$staff = mysqli_fetch_assoc($result);
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $contact = sanitize($_POST['contact']);
    $email = sanitize($_POST['email']);
    $role = sanitize($_POST['role']);
    $shift_start = sanitize($_POST['shift_start']);
    $shift_end = sanitize($_POST['shift_end']);
    
    if (empty($name)) {
        $error = "Staff name is required";
    } else {
        $update_query = "UPDATE Staff SET 
                         Staff_Name = '$name',
                         Contact_No = '$contact',
                         Email = '$email',
                         Role = '$role',
                         Shift_Start = '$shift_start',
                         Shift_End = '$shift_end'
                         WHERE Staff_ID = $staff_id";
        
        if (mysqli_query($conn, $update_query)) {
            setFlashMessage("Staff member updated successfully!", "success");
            redirect(BASE_URL . "/admin/staff/staff.php");
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}

$custom_title = "Edit Staff - Teddy Shine";
include_once '../../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user-edit"></i> Edit Staff Member</h5>
                </div>
                <div class="card-body">
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Full Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($staff['Staff_Name']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Contact Number</label>
                            <input type="text" name="contact" class="form-control" value="<?php echo htmlspecialchars($staff['Contact_No']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($staff['Email']); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Role <span class="text-danger">*</span></label>
                            <select name="role" class="form-select" required>
                                <option value="collector" <?php echo $staff['Role'] == 'collector' ? 'selected' : ''; ?>>Collector</option>
                                <option value="washer" <?php echo $staff['Role'] == 'washer' ? 'selected' : ''; ?>>Washer</option>
                                <option value="deliveryboy" <?php echo $staff['Role'] == 'deliveryboy' ? 'selected' : ''; ?>>Delivery Boy</option>
                            </select>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Shift Start</label>
                                <input type="time" name="shift_start" class="form-control" value="<?php echo $staff['Shift_Start']; ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Shift End</label>
                                <input type="time" name="shift_end" class="form-control" value="<?php echo $staff['Shift_End']; ?>">
                            </div>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Staff
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