<?php
/**
 * Edit Service - Teddy Shine Laundry Management System
 * 
 * Form to edit existing laundry services
 */

require_once '../../includes/admin_check.php';
require_once '../../config/database.php';
require_once '../../config/functions.php';

$service_id = intval($_GET['id'] ?? 0);

// Get service data
$query = "SELECT * FROM Services WHERE Service_ID = $service_id";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) == 0) {
    setFlashMessage("Service not found.", "error");
    redirect(BASE_URL . "/admin/services/services.php");
}

$service = mysqli_fetch_assoc($result);
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
        $update_query = "UPDATE Services SET 
                         Service_Name = '$name',
                         Service_Price = $price,
                         Estimate_Time = $time,
                         Status = '$status'
                         WHERE Service_ID = $service_id";
        
        if (mysqli_query($conn, $update_query)) {
            setFlashMessage("Service updated successfully!", "success");
            redirect(BASE_URL . "/admin/services/services.php");
        } else {
            $error = "Error: " . mysqli_error($conn);
        }
    }
}

$custom_title = "Edit Service - Teddy Shine";
include_once '../../includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-edit"></i> Edit Service</h5>
                </div>
                <div class="card-body">
                    <?php if($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Service Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($service['Service_Name']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Price (Rs.) <span class="text-danger">*</span></label>
                            <input type="number" name="price" class="form-control" step="0.01" min="1" value="<?php echo $service['Service_Price']; ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Estimated Time (minutes)</label>
                            <input type="number" name="time" class="form-control" value="<?php echo $service['Estimate_Time']; ?>" min="1">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="Active" <?php echo $service['Status'] == 'Active' ? 'selected' : ''; ?>>Active</option>
                                <option value="Inactive" <?php echo $service['Status'] == 'Inactive' ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Service
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