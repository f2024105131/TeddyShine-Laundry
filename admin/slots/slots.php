<?php
/**
 * Delivery Slots Management - Teddy Shine Laundry Management System
 * 
 * List all delivery slots with options to add, edit, and delete
 */

require_once '../../includes/admin_check.php';
require_once '../../config/database.php';
require_once '../../config/functions.php';

// Get all slots
$slots_query = "SELECT * FROM DeliverySlots ORDER BY Start_Time";
$slots = mysqli_query($conn, $slots_query);

$custom_title = "Delivery Slots - Teddy Shine";
include_once '../../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-clock"></i> Delivery Slots Management</h2>
        <a href="add_slot.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Add New Slot
        </a>
    </div>

    <?php if(mysqli_num_rows($slots) > 0): ?>
        <div class="row">
            <?php while($slot = mysqli_fetch_assoc($slots)): 
                // Calculate today's bookings
                $used_query = "SELECT COUNT(*) as used FROM Orders WHERE Slot_ID = {$slot['Slot_ID']} AND Delivery_Date >= CURDATE()";
                $used_result = mysqli_query($conn, $used_query);
                $used = mysqli_fetch_assoc($used_result)['used'];
                $available = $slot['Max_Orders'] - $used;
                $percentage = min(100, ($used / $slot['Max_Orders']) * 100);
            ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="slot-card">
                    <div class="slot-header">
                        <h5 class="mb-0"><?php echo htmlspecialchars($slot['Slot_Type']); ?></h5>
                        <span class="badge bg-light text-dark">
                            <i class="fas fa-clock"></i> <?php echo date('h:i A', strtotime($slot['Start_Time'])); ?> - <?php echo date('h:i A', strtotime($slot['End_Time'])); ?>
                        </span>
                    </div>
                    <div class="slot-body">
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="slot-stat">
                                    <div class="slot-stat-value"><?php echo $slot['Max_Orders']; ?></div>
                                    <div class="slot-stat-label">Max Orders</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="slot-stat">
                                    <div class="slot-stat-value <?php echo $available > 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo $available; ?>
                                    </div>
                                    <div class="slot-stat-label">Available Today</div>
                                </div>
                            </div>
                        </div>
                        <div class="progress mt-3" style="height: 8px;">
                            <div class="progress-bar <?php echo $percentage > 80 ? 'bg-danger' : ($percentage > 50 ? 'bg-warning' : 'bg-success'); ?>" 
                                 style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                        <div class="text-center mt-1">
                            <small class="text-muted"><?php echo round($percentage); ?>% capacity used today</small>
                        </div>
                    </div>
                    <div class="slot-footer">
                        <a href="edit_slot.php?id=<?php echo $slot['Slot_ID']; ?>" class="btn btn-warning btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                        <a href="delete_slot.php?id=<?php echo $slot['Slot_ID']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this slot?')">
                            <i class="fas fa-trash"></i> Delete
                        </a>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-clock fa-4x text-muted mb-3"></i>
                <h4>No Delivery Slots Found</h4>
                <p class="text-muted">Add delivery slots to manage order scheduling.</p>
                <a href="add_slot.php" class="btn btn-primary mt-2">
                    <i class="fas fa-plus"></i> Add New Slot
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include_once '../../includes/footer.php'; ?>