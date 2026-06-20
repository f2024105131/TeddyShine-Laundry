<?php
/**
 * Staff Dashboard
 * Overview dashboard for staff members showing assigned tasks and statistics
 */

require_once '../includes/auth_check.php';
require_once '../config/database.php';
require_once '../config/functions.php';

// Get staff ID from session
$staff_id = $_SESSION['staff_id'] ?? 1;
$staff_role = $_SESSION['role'] ?? 'staff';
$staff_name = $_SESSION['user_name'] ?? 'Staff Member';

// Get staff details
$staff_query = "SELECT * FROM Staff WHERE Staff_ID = $staff_id";
$staff_result = mysqli_query($conn, $staff_query);
$staff = mysqli_fetch_assoc($staff_result);

// Get statistics based on role
$stats = [];

// Total assigned orders
$orders_query = "SELECT COUNT(*) as total FROM Orders WHERE Staff_ID = $staff_id";
$orders_result = mysqli_query($conn, $orders_query);
$stats['total_orders'] = mysqli_fetch_assoc($orders_result)['total'];

// Pending orders
$pending_query = "SELECT COUNT(*) as total FROM Orders WHERE Staff_ID = $staff_id AND Status IN ('Pending', 'Processing')";
$pending_result = mysqli_query($conn, $pending_query);
$stats['pending_orders'] = mysqli_fetch_assoc($pending_result)['total'];

// Completed orders
$completed_query = "SELECT COUNT(*) as total FROM Orders WHERE Staff_ID = $staff_id AND Status = 'Completed'";
$completed_result = mysqli_query($conn, $completed_query);
$stats['completed_orders'] = mysqli_fetch_assoc($completed_result)['total'];

// Today's deliveries
$today_query = "SELECT COUNT(*) as total FROM Orders WHERE Staff_ID = $staff_id AND Delivery_Date = CURDATE()";
$today_result = mysqli_query($conn, $today_query);
$stats['today_deliveries'] = mysqli_fetch_assoc($today_result)['total'];

// Get assigned orders
$assigned_orders_query = "SELECT o.*, 
                          CONCAT(r.F_Name, ' ', r.L_Name) as customer_name,
                          r.Phone_No,
                          (SELECT COUNT(*) FROM OrderItems WHERE Order_ID = o.Order_ID) as item_count
                          FROM Orders o
                          JOIN Resident r ON o.Resident_ID = r.Resident_ID
                          WHERE o.Staff_ID = $staff_id 
                          ORDER BY o.Order_Date DESC LIMIT 10";
$assigned_orders = mysqli_query($conn, $assigned_orders_query);

$custom_title = "Staff Dashboard - Teddy Shine";
include_once '../includes/header.php';
?>

<div class="container mt-4">
    <!-- Welcome Section -->
    <div class="welcome-card mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h3 class="mb-2">
                    <i class="fas fa-user-circle"></i> 
                    Welcome back, <?php echo htmlspecialchars($staff_name); ?>!
                </h3>
                <p class="mb-0 opacity-75">
                    <i class="fas fa-briefcase"></i> Role: 
                    <span class="badge bg-light text-dark">
                        <?php echo ucfirst($staff['Role'] ?? $staff_role); ?>
                    </span>
                    <span class="ms-3"><i class="fas fa-clock"></i> Shift: 
                        <?php echo $staff['Shift_Start'] ?? '09:00'; ?> - <?php echo $staff['Shift_End'] ?? '17:00'; ?>
                    </span>
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <span class="badge bg-light text-dark">
                    <i class="fas fa-calendar"></i> <?php echo date('l, d M Y'); ?>
                </span>
            </div>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 col-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-clipboard-list"></i></div>
                <div class="stat-value"><?php echo $stats['total_orders']; ?></div>
                <div class="stat-label">Total Assigned</div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-hourglass-half"></i></div>
                <div class="stat-value"><?php echo $stats['pending_orders']; ?></div>
                <div class="stat-label">Pending Orders</div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-value"><?php echo $stats['completed_orders']; ?></div>
                <div class="stat-label">Completed</div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-truck"></i></div>
                <div class="stat-value"><?php echo $stats['today_deliveries']; ?></div>
                <div class="stat-label">Today's Deliveries</div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <h5 class="mb-3"><i class="fas fa-bolt"></i> Quick Actions</h5>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <a href="assigned_orders.php" class="quick-action-card text-center d-block">
                <i class="fas fa-eye fa-2x text-primary mb-2"></i>
                <div>View Orders</div>
                <small class="text-muted"><?php echo $stats['pending_orders']; ?> pending</small>
            </a>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <a href="update_tracking.php" class="quick-action-card text-center d-block">
                <i class="fas fa-chart-line fa-2x text-success mb-2"></i>
                <div>Update Tracking</div>
            </a>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <a href="delivery_list.php" class="quick-action-card text-center d-block">
                <i class="fas fa-truck fa-2x text-warning mb-2"></i>
                <div>Delivery Schedule</div>
                <small class="text-muted"><?php echo $stats['today_deliveries']; ?> today</small>
            </a>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <a href="assigned_orders.php?status=completed" class="quick-action-card text-center d-block">
                <i class="fas fa-history fa-2x text-info mb-2"></i>
                <div>History</div>
            </a>
        </div>
    </div>
    
    <!-- Assigned Orders List -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-tasks"></i> Your Assigned Orders</h5>
            <a href="assigned_orders.php" class="btn btn-sm btn-link">View All</a>
        </div>
        <div class="card-body p-0">
            <?php if(mysqli_num_rows($assigned_orders) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($order = mysqli_fetch_assoc($assigned_orders)): ?>
                            <tr>
                                <td><strong>#<?php echo $order['Order_ID']; ?></strong></td>
                                <td>
                                    <?php echo htmlspecialchars($order['customer_name']); ?>
                                    <div class="small text-muted"><?php echo $order['Phone_No']; ?></div>
                                </td>
                                <td><?php echo date('d M Y', strtotime($order['Order_Date'])); ?></td>
                                <td><?php echo $order['item_count']; ?> item(s)</td>
                                <td><?php echo getStatusBadge($order['Status']); ?></td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="assigned_orders.php?id=<?php echo $order['Order_ID']; ?>" class="btn btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="update_tracking.php?order_id=<?php echo $order['Order_ID']; ?>" class="btn btn-outline-info">
                                            <i class="fas fa-chart-line"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                    <p class="mb-0">No orders assigned to you yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Auto refresh every 60 seconds
setTimeout(function() {
    location.reload();
}, 60000);
</script>

<?php include_once '../includes/footer.php'; ?>