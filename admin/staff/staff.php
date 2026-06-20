<?php
/**
 * Staff Management - Teddy Shine Laundry Management System
 * 
 * List all staff members with options to add, edit, and delete
 */

require_once '../../includes/admin_check.php';
require_once '../../config/database.php';
require_once '../../config/functions.php';

// Handle Delete (via GET)
if(isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $check = mysqli_query($conn, "SELECT * FROM Orders WHERE Staff_ID = $id");
    if(mysqli_num_rows($check) > 0) {
        setFlashMessage("Cannot delete staff member with assigned orders. Reassign orders first.", "error");
    } else {
        mysqli_query($conn, "DELETE FROM Staff WHERE Staff_ID = $id");
        setFlashMessage("Staff member deleted successfully.", "success");
    }
    redirect(BASE_URL . "/admin/staff/staff.php");
}

// Get all staff
$staff_query = "SELECT s.*, 
                (SELECT COUNT(*) FROM Orders WHERE Staff_ID = s.Staff_ID) as assigned_orders
                FROM Staff s
                ORDER BY s.Role, s.Staff_Name";
$staff = mysqli_query($conn, $staff_query);

$custom_title = "Staff Management - Teddy Shine";
include_once '../../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-user-tie"></i> Staff Management</h2>
        <a href="add_staff.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Staff
        </a>
    </div>
    
    <?php if(mysqli_num_rows($staff) > 0): ?>
        <div class="row">
            <?php while($staff_member = mysqli_fetch_assoc($staff)): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="staff-card">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="d-flex align-items-center">
                            <div class="staff-avatar">
                                <i class="fas fa-user fa-2x"></i>
                            </div>
                            <div class="ms-3">
                                <h6 class="mb-0"><?php echo htmlspecialchars($staff_member['Staff_Name']); ?></h6>
                                <span class="badge role-badge-<?php echo $staff_member['Role']; ?>">
                                    <?php echo ucfirst($staff_member['Role']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-link" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="edit_staff.php?id=<?php echo $staff_member['Staff_ID']; ?>">
                                    <i class="fas fa-edit"></i> Edit
                                </a></li>
                                <li><a class="dropdown-item text-danger" href="?delete=<?php echo $staff_member['Staff_ID']; ?>" onclick="return confirm('Delete this staff member?')">
                                    <i class="fas fa-trash"></i> Delete
                                </a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="staff-info">
                        <div class="row mb-2">
                            <div class="col-5 text-muted small">Contact</div>
                            <div class="col-7"><?php echo $staff_member['Contact_No'] ?? 'Not provided'; ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 text-muted small">Email</div>
                            <div class="col-7"><?php echo $staff_member['Email'] ?? 'Not provided'; ?></div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 text-muted small">Shift</div>
                            <div class="col-7">
                                <?php echo date('h:i A', strtotime($staff_member['Shift_Start'] ?? '09:00')); ?> - 
                                <?php echo date('h:i A', strtotime($staff_member['Shift_End'] ?? '17:00')); ?>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-5 text-muted small">Assigned Orders</div>
                            <div class="col-7"><strong><?php echo $staff_member['assigned_orders']; ?></strong> orders</div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-user-slash fa-4x text-muted mb-3"></i>
                <h4>No Staff Members Found</h4>
                <p class="text-muted">Add your first staff member to get started.</p>
                <a href="add_staff.php" class="btn btn-primary mt-2">
                    <i class="fas fa-plus"></i> Add New Staff
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include_once '../../includes/footer.php'; ?>