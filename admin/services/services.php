<?php
/**
 * Service Management - Teddy Shine Laundry Management System
 * 
 * List all laundry services with options to add, edit, delete, and toggle status
 */

require_once '../../includes/admin_check.php';
require_once '../../config/database.php';
require_once '../../config/functions.php';

// Handle Status Toggle
if(isset($_GET['toggle'])) {
    $id = intval($_GET['toggle']);
    $current = mysqli_fetch_assoc(mysqli_query($conn, "SELECT Status FROM Services WHERE Service_ID = $id"));
    $new_status = $current['Status'] == 'Active' ? 'Inactive' : 'Active';
    mysqli_query($conn, "UPDATE Services SET Status = '$new_status' WHERE Service_ID = $id");
    setFlashMessage("Service status updated to $new_status.", "success");
    redirect(BASE_URL . "/admin/services/services.php");
}

// Get all services
$services_query = "SELECT * FROM Services ORDER BY Service_Price";
$services = mysqli_query($conn, $services_query);

$custom_title = "Service Management - Teddy Shine";
include_once '../../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-tags"></i> Service Management</h2>
        <a href="add_service.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Service
        </a>
    </div>

    <?php if(mysqli_num_rows($services) > 0): ?>
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Service Name</th>
                                <th>Price</th>
                                <th>Est. Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($service = mysqli_fetch_assoc($services)): ?>
                            <tr>
                                <td><?php echo $service['Service_ID']; ?></td>
                                <td><strong><?php echo htmlspecialchars($service['Service_Name']); ?></strong></td>
                                <td><strong>Rs. <?php echo number_format($service['Service_Price'], 2); ?></strong></td>
                                <td><?php echo $service['Estimate_Time']; ?> mins</td>
                                <td>
                                    <?php if($service['Status'] == 'Active'): ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit_service.php?id=<?php echo $service['Service_ID']; ?>" class="btn btn-outline-primary">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="?toggle=<?php echo $service['Service_ID']; ?>" class="btn btn-outline-warning">
                                            <i class="fas fa-power-off"></i>
                                        </a>
                                        <a href="delete_service.php?id=<?php echo $service['Service_ID']; ?>" class="btn btn-outline-danger" onclick="return confirm('Delete this service?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-tags fa-4x text-muted mb-3"></i>
                <h4>No Services Found</h4>
                <p class="text-muted">Add your first laundry service to get started.</p>
                <a href="add_service.php" class="btn btn-primary mt-2">
                    <i class="fas fa-plus"></i> Add New Service
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include_once '../../includes/footer.php'; ?>