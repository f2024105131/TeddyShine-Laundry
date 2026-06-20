<?php
/**
 * Admin Dashboard - Teddy Shine Laundry Management System
 * 
 * Main dashboard for administrators with statistics, charts, and quick actions
 */

require_once '../includes/admin_check.php';
require_once '../config/database.php';
require_once '../config/functions.php';

// Get statistics
$stats = getAdminStats();

// Get recent orders
$recent_orders = mysqli_query($conn, "SELECT o.*, CONCAT(r.F_Name, ' ', r.L_Name) as customer_name 
                                      FROM Orders o 
                                      JOIN Resident r ON o.Resident_ID = r.Resident_ID 
                                      ORDER BY o.Order_Date DESC LIMIT 10");

// Get recent payments
$recent_payments = mysqli_query($conn, "SELECT p.*, i.Invoice_ID, o.Order_ID,
                                        CONCAT(r.F_Name, ' ', r.L_Name) as customer_name
                                        FROM Payments p
                                        JOIN Invoice i ON p.Invoice_ID = i.Invoice_ID
                                        JOIN Orders o ON i.Order_ID = o.Order_ID
                                        JOIN Resident r ON o.Resident_ID = r.Resident_ID
                                        ORDER BY p.Payment_Date DESC LIMIT 5");

// Get order status distribution
$status_counts = [];
$statuses = ['Pending', 'Processing', 'In Progress', 'Completed', 'Delivered', 'Cancelled'];
foreach($statuses as $status) {
    $query = "SELECT COUNT(*) as count FROM Orders WHERE Status = '$status'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $status_counts[$status] = $row['count'];
}

// Get top services
$top_services = mysqli_query($conn, "SELECT s.Service_Name, COUNT(oi.Service_ID) as order_count, SUM(oi.Price) as total_revenue
                                     FROM OrderItems oi
                                     JOIN Services s ON oi.Service_ID = s.Service_ID
                                     GROUP BY oi.Service_ID
                                     ORDER BY order_count DESC LIMIT 5");

// Get monthly revenue for chart (last 6 months)
$monthly_revenue = [];
for($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $month_name = date('M', strtotime("-$i months"));
    $query = "SELECT COALESCE(SUM(Payment_Amount), 0) as total FROM Payments 
              WHERE DATE_FORMAT(Payment_Date, '%Y-%m') = '$month' AND Payment_Status = 'Completed'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $monthly_revenue[] = ['month' => $month_name, 'revenue' => $row['total']];
}

$custom_title = "Admin Dashboard - Teddy Shine";
include_once '../includes/header.php';
?>

<div class="container mt-4">
    <!-- Welcome Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-chalkboard-user"></i> Admin Dashboard</h2>
            <p class="text-muted">Welcome back, <?php echo $_SESSION['user_name']; ?>!</p>
        </div>
        <div class="text-end">
            <span class="badge bg-primary"><?php echo date('l, d M Y'); ?></span>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon bg-primary bg-opacity-10">
                    <i class="fas fa-shopping-bag fa-2x text-primary"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['total_orders']); ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon bg-success bg-opacity-10">
                    <i class="fas fa-users fa-2x text-success"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['total_residents']); ?></div>
                <div class="stat-label">Total Residents</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon bg-warning bg-opacity-10">
                    <i class="fas fa-rupee-sign fa-2x text-warning"></i>
                </div>
                <div class="stat-value">Rs. <?php echo number_format($stats['total_revenue'], 0); ?></div>
                <div class="stat-label">Total Revenue</div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-3">
            <div class="stat-card">
                <div class="stat-icon bg-danger bg-opacity-10">
                    <i class="fas fa-clock fa-2x text-danger"></i>
                </div>
                <div class="stat-value"><?php echo number_format($stats['pending_orders']); ?></div>
                <div class="stat-label">Pending Orders</div>
            </div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-lg-8 mb-3">
            <div class="chart-card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-chart-line"></i> Revenue Trend (Last 6 Months)</h6>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="250"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4 mb-3">
            <div class="chart-card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-chart-pie"></i> Order Status Distribution</h6>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <h5 class="mb-3"><i class="fas fa-bolt"></i> Quick Actions</h5>
        </div>
        <div class="col-md-2 col-6 mb-2">
            <a href="orders/orders.php" class="quick-action-card text-center d-block">
                <i class="fas fa-box fa-2x text-primary mb-2"></i>
                <div>Manage Orders</div>
            </a>
        </div>
        <div class="col-md-2 col-6 mb-2">
            <a href="staff/staff.php" class="quick-action-card text-center d-block">
                <i class="fas fa-users fa-2x text-success mb-2"></i>
                <div>Manage Staff</div>
            </a>
        </div>
        <div class="col-md-2 col-6 mb-2">
            <a href="services/services.php" class="quick-action-card text-center d-block">
                <i class="fas fa-tags fa-2x text-info mb-2"></i>
                <div>Manage Services</div>
            </a>
        </div>
        <div class="col-md-2 col-6 mb-2">
            <a href="slots/slots.php" class="quick-action-card text-center d-block">
                <i class="fas fa-clock fa-2x text-warning mb-2"></i>
                <div>Delivery Slots</div>
            </a>
        </div>
        <div class="col-md-2 col-6 mb-2">
            <a href="invoices/invoices.php" class="quick-action-card text-center d-block">
                <i class="fas fa-file-invoice fa-2x text-danger mb-2"></i>
                <div>Invoices</div>
            </a>
        </div>
        <div class="col-md-2 col-6 mb-2">
            <a href="reports/reports.php" class="quick-action-card text-center d-block">
                <i class="fas fa-chart-bar fa-2x text-secondary mb-2"></i>
                <div>Reports</div>
            </a>
        </div>
    </div>
    
    <div class="row">
        <!-- Recent Orders -->
        <div class="col-lg-7 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-history"></i> Recent Orders</h6>
                    <a href="orders/orders.php" class="btn btn-sm btn-link">View All</a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Order #</th>
                                    <th>Customer</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($order = mysqli_fetch_assoc($recent_orders)): ?>
                                <tr>
                                    <td><strong>#<?php echo $order['Order_ID']; ?></strong></td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td><?php echo date('d M Y', strtotime($order['Order_Date'])); ?></td>
                                    <td>Rs. <?php echo number_format($order['Amount'], 2); ?></td>
                                    <td><?php echo getStatusBadge($order['Status']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Payments & Top Services -->
        <div class="col-lg-5 mb-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-credit-card"></i> Recent Payments</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr><th>Invoice</th><th>Customer</th><th>Amount</th></tr>
                            </thead>
                            <tbody>
                                <?php while($payment = mysqli_fetch_assoc($recent_payments)): ?>
                                <tr>
                                    <td>INV-<?php echo str_pad($payment['Invoice_ID'], 6, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo htmlspecialchars($payment['customer_name']); ?></td>
                                    <td>Rs. <?php echo number_format($payment['Payment_Amount'], 2); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-crown"></i> Top Performing Services</h6>
                </div>
                <div class="card-body">
                    <?php while($service = mysqli_fetch_assoc($top_services)): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span><?php echo htmlspecialchars($service['Service_Name']); ?></span>
                        <span class="badge bg-primary"><?php echo $service['order_count']; ?> orders</span>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Revenue Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
const revenueData = <?php 
    $months = [];
    $revenues = [];
    foreach($monthly_revenue as $row) {
        $months[] = $row['month'];
        $revenues[] = $row['revenue'];
    }
    echo json_encode(['months' => $months, 'revenues' => $revenues]);
?>;
new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: revenueData.months,
        datasets: [{
            label: 'Revenue (Rs.)',
            data: revenueData.revenues,
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { position: 'top' },
            tooltip: { callbacks: { label: function(ctx) { return 'Rs. ' + ctx.raw.toLocaleString(); } } }
        },
        scales: { y: { beginAtZero: true, ticks: { callback: function(v) { return 'Rs. ' + v.toLocaleString(); } } } }
    }
});

// Status Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
const statusData = <?php 
    $labels = [];
    $counts = [];
    foreach($status_counts as $status => $count) {
        if($count > 0) {
            $labels[] = $status;
            $counts[] = $count;
        }
    }
    echo json_encode(['labels' => $labels, 'counts' => $counts]);
?>;
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: statusData.labels,
        datasets: [{
            data: statusData.counts,
            backgroundColor: ['#f59e0b', '#3b82f6', '#8b5cf6', '#10b981', '#06b6d4', '#ef4444'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { position: 'bottom' } }
    }
});
</script>

<?php include_once '../includes/footer.php'; ?>