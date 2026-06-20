<?php
/**
 * Resident Dashboard - Teddy Shine Laundry Management System
 * 
 * Main dashboard for residents showing statistics, recent orders, and quick actions
 */
require_once '../includes/auth_check.php';
require_once '../config/database.php';
require_once '../config/functions.php';
$resident_id = $_SESSION['resident_id'];
$resident_name = $_SESSION['user_name'];
// Get statistics using functions
$stats = getResidentStats($resident_id);
// Get recent orders$recent_orders_query = "SELECT o.*, 
(SELECT COUNT(*) FROM OrderItems WHERE Order_ID = o.Order_ID) as item_count
FROM Orders o 
 WHERE o.Resident_ID = $resident_id 
ORDER BY o.Order_Date DESC LIMIT 5";
$recent_orders = mysqli_query($conn, $recent_orders_query);
// Get pending payments count
$pending_payments_query = "SELECT COUNT(*) as pending_count
FROM Invoice i
JOIN Orders o ON i.Order_ID = o.Order_ID
WHERE o.Resident_ID = $resident_id AND i.Invoice_Status != 'Paid'";
$pending_result = mysqli_query($conn, $pending_payments_query);
$pending_data = mysqli_fetch_assoc($pending_result);
$pending_count = $pending_data['pending_count'] ?? 0;

$custom_title = "Dashboard - Teddy Shine";
include_once '../includes/header.php';
?>
<div class="container mt-4">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="dashboard-welcome">
                <h2>Welcome back, <?php echo htmlspecialchars($resident_name); ?>!</h2>
                <p class="text-muted">Your laundry, our priority. Track orders, place new ones, and manage your account.</p>
                <hr>
            </div>
        </div>
    </div>
<!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3 col-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shopping-bag"></i>
                </div>
                <div class="stat-value"><?php echo $stats['total_orders']; ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
        </div>
<div class="col-md-3 col-6 mb-3">
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-clock"></i>
                </div>
        <div class="stat-value"><?php echo $stats['pending_orders']; ?></div>
    <div class="stat-label">In Progress</div>
            </div>
        </div>
<div class="col-md-3 col-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon">
        <i class="fas fa-check-circle"></i>
                </div>
<div class="stat-value"><?php echo $stats['completed_orders']; ?></div>
                <div class="stat-label">Completed</div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-rupee-sign"></i>
                </div>
                <div class="stat-value">Rs. <?php echo number_format($stats['total_spent'], 0); ?></div>
                <div class="stat-label">Total Spent</div>
            </div>
        </div>
    </div>
<!-- Pending Payments Alert -->
    <?php if($pending_count > 0): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-warning d-flex align-items-center justify-content-between">
                <div>
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    You have <?php echo $pending_count; ?> pending payment(s).
                </div>
                <a href="payment_history.php" class="btn btn-sm btn-warning">Pay Now</a>
            </div>
        </div>
    </div>
<?php endif; ?>
<!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <h5 class="mb-3">Quick Actions</h5>
        </div>
<div class="col-md-3 col-6 mb-3">
    <a href="place_order.php" class="quick-action-card text-center d-block">
        <i class="fas fa-plus-circle fa-2x text-primary mb-2"></i>
            <div>New Order</div>
            </a>
        </div>
<div class="col-md-3 col-6 mb-3">
            <a href="my_orders.php" class="quick-action-card text-center d-block">
                <i class="fas fa-list fa-2x text-success mb-2"></i>
                <div>My Orders</div>
            </a>
        </div>
<div class="col-md-3 col-6 mb-3">
    <a href="services.php" class="quick-action-card text-center d-block">
        <i class="fas fa-tags fa-2x text-info mb-2"></i>
                <div>Services</div>
            </a>
        </div>
<div class="col-md-3 col-6 mb-3">
            <a href="profile.php" class="quick-action-card text-center d-block">
                <i class="fas fa-user-edit fa-2x text-warning mb-2"></i>
                <div>Edit Profile</div>
            </a>
        </div>
    </div>
<!-- Recent Orders -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-history"></i> Recent Orders</h5>
                    <a href="my_orders.php" class="btn btn-sm btn-link">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Items</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
<?php if(mysqli_num_rows($recent_orders) > 0): ?>
    <?php while($order = mysqli_fetch_assoc($recent_orders)): ?>
                                    <tr>
<td><strong>#<?php echo $order['Order_ID']; ?></strong></td>
<td><?php echo date('d M Y', strtotime($order['Order_Date'])); ?></td>
<td><?php echo $order['item_count']; ?> item(s)</td>
<td>Rs. <?php echo number_format($order['Amount'], 2); ?></td>
<td><?php echo getStatusBadge($order['Status']); ?></td>
                                        <td>
<a href="order_details.php?id=<?php echo $order['Order_ID']; ?>" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-eye"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
 <?php else: ?>
                                    <tr>
<td colspan="6" class="text-center py-4">
<i class="fas fa-inbox fa-2x text-muted mb-2 d-block"></i>
     No orders yet
    git  <a href="place_order.php" class="d-block mt-2">Place your first order</a>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>