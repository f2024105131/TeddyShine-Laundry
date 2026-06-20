<?php
/**
 * Add Service - Teddy Shine Laundry Management System
 * 
 * Form to add new laundry services
 */

require_once '../../includes/admin_check.php';
require_once '../../config/database.php';
require_once '../../config/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize($_POST['name']);
    $price = floatval($_POST['price']);
    $time = intval($_POST['time']);
    $status = sanitize($_POST['status']);
    
    if (empty($name)) {
        $error = "Service name is required";
    } elseif ($price <= 0) {
        $error = "Price must be greater than 0";
    } else {
        $query = "INSERT INTO Services (Service_Name, Service_Price, Estimate_Time, Status) 
                  VALUES ('$name', $price, $time, '$status')";
        
        if (mysqli_query($conn, $query)) {
            setFlashMessage("Service added successfully!", "success");
            redirect(BASE_URL . "/admin/services/services.php");
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}

$custom_title = "Add Service - Teddy Shine";
include_once '../../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-plus-circle"></i> Add New Service</h5>
                </div>
                <div class="card-body">
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Service Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g., Wash & Fold" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Price (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" name="price" class="form-control" step="0.01" min="1" placeholder="150.00" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Estimated Time (minutes)</label>
                            <input type="number" name="time" class="form-control" value="60" min="1">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Add Service
                            </button>
                            <a href="services.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?>