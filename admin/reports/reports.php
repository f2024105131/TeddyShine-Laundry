<?php
/**
 * Reports Dashboard - Teddy Shine Laundry Management System
 * 
 * Generate and view various business reports
 */

require_once '../../includes/admin_check.php';
require_once '../../config/database.php';
require_once '../../config/functions.php';

// Get date range for reports
$year = date('Y');
$month = date('m');

// Monthly Revenue Report
$monthly_query = "SELECT DATE_FORMAT(Payment_Date, '%Y-%m') as month,
                  SUM(Payment_Amount) as revenue,
                  COUNT(DISTINCT Invoice_ID) as transactions
                  FROM Payments 
                  WHERE Payment_Status = 'Completed'
                  GROUP BY DATE_FORMAT(Payment_Date, '%Y-%m')
                  ORDER BY month DESC
                  LIMIT 12";
$monthly_revenue = mysqli_query($conn, $monthly_query);

// Order Status Summary
$status_summary = mysqli_query($conn, "SELECT Status, COUNT(*) as count FROM Orders GROUP BY Status");

// Top Customers
$top_customers = mysqli_query($conn, "SELECT CONCAT(r.F_Name, ' ', r.L_Name) as name,
                                      COUNT(o.Order_ID) as orders,
                                      SUM(o.Amount) as total_spent
                                      FROM Orders o
                                      JOIN Resident r ON o.Resident_ID = r.Resident_ID
                                      GROUP BY o.Resident_ID
                                      ORDER BY total_spent DESC
                                      LIMIT 10");

// Staff Performance
$staff_performance = mysqli_query($conn, "SELECT s.Staff_Name, s.Role,
                                          COUNT(o.Order_ID) as orders_handled
                                          FROM Staff s
                                          LEFT JOIN Orders o ON s.Staff_ID = o.Staff_ID
                                          GROUP BY s.Staff_ID
                                          ORDER BY orders_handled DESC");

// Daily Orders (last 30 days)
$daily_orders = mysqli_query($conn, "SELECT DATE(Order_Date) as date, 
                                     COUNT(*) as count, 
                                     SUM(Amount) as revenue
                                     FROM Orders 
                                     WHERE Order_Date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                                     GROUP BY DATE(Order_Date)
                                     ORDER BY date DESC");

// Popular services
$popular_services = mysqli_query($conn, "SELECT s.Service_Name, 
                                         COUNT(oi.Service_ID) as order_count,
                                         SUM(oi.Price) as revenue
                                         FROM OrderItems oi
                                         JOIN Services s ON oi.Service_ID = s.Service_ID
                                         GROUP BY oi.Service_ID
                                         ORDER BY order_count DESC
                                         LIMIT 5");

// Store monthly data for charts
$months = [];
$revenues = [];
mysqli_data_seek($monthly_revenue, 0);
while($row = mysqli_fetch_assoc($monthly_revenue)) {
    $months[] = date('M Y', strtotime($row['month'] . '-01'));
    $revenues[] = $row['revenue'];
}
$months = array_reverse($months);
$revenues = array_reverse($revenues);

// Store daily data for charts
$daily_dates = [];
$daily_counts = [];
while($row = mysqli_fetch_assoc($daily_orders)) {
    $daily_dates[] = date('d M', strtotime($row['date']));
    $daily_counts[] = $row['count'];
}
$daily_dates = array_reverse($daily_dates);
$daily_counts = array_reverse($daily_counts);
mysqli_data_seek($daily_orders, 0);

// Store status data for chart
$status_labels = [];
$status_counts = [];
mysqli_data_seek($status_summary, 0);
while($row = mysqli_fetch_assoc($status_summary)) {
    $status_labels[] = $row['Status'];
    $status_counts[] = $row['count'];
}

$custom_title = "Reports Dashboard - Teddy Shine";
include_once '../../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-chart-bar"></i> Reports Dashboard</h2>
        <div class="no-print">
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="fas fa-print"></i> Print Report
            </button>
            <button onclick="exportToExcel()" class="btn btn-success">
                <i class="fas fa-file-excel"></i> Export to Excel
            </button>
        </div>
    </div>

    <div class="row">
        <!-- Left Column - Charts -->
        <div class="col-lg-8">
            <!-- Revenue Chart -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-chart-line"></i> Monthly Revenue (Last 12 Months)</h6>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="250"></canvas>
                </div>
            </div>

            <!-- Daily Orders Chart -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-calendar-day"></i> Daily Orders (Last 30 Days)</h6>
                </div>
                <div class="card-body">
                    <canvas id="dailyChart" height="200"></canvas>
                </div>
            </div>

            <!-- Top Customers Table -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-crown"></i> Top Customers by Spending</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Customer Name</th>
                                    <th>Orders</th>
                                    <th>Total Spent</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rank = 1; while($customer = mysqli_fetch_assoc($top_customers)): ?>
                                <tr>
                                    <td><?php echo $rank++; ?></td>
                                    <td><?php echo htmlspecialchars($customer['name']); ?></td>
                                    <td><?php echo $customer['orders']; ?> orders</td>
                                    <td class="fw-bold">Rs. <?php echo number_format($customer['total_spent'], 2); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column - Stats -->
        <div class="col-lg-4">
            <!-- Order Status Summary -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-chart-pie"></i> Order Status</h6>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" height="200"></canvas>
                    <div class="mt-3">
                        <?php while($status = mysqli_fetch_assoc($status_summary)): ?>
                        <div class="d-flex justify-content-between mb-1">
                            <span><?php echo $status['Status']; ?></span>
                            <span class="fw-bold"><?php echo $status['count']; ?></span>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>

            <!-- Popular Services -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-tags"></i> Popular Services</h6>
                </div>
                <div class="card-body">
                    <?php 
                    $max_count = 1;
                    $services_data = [];
                    while($service = mysqli_fetch_assoc($popular_services)) {
                        $services_data[] = $service;
                        if($service['order_count'] > $max_count) $max_count = $service['order_count'];
                    }
                    foreach($services_data as $service): 
                    ?>
                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span><?php echo htmlspecialchars($service['Service_Name']); ?></span>
                            <span class="fw-bold"><?php echo $service['order_count']; ?> orders</span>
                        </div>
                        <div class="progress" style="height: 6px;">
                            <div class="progress-bar bg-primary" style="width: <?php echo min(100, ($service['order_count'] / $max_count) * 100); ?>%"></div>
                        </div>
                        <small class="text-muted">Revenue: Rs. <?php echo number_format($service['revenue'], 2); ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Staff Performance -->
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-user-tie"></i> Staff Performance</h6>
                </div>
                <div class="card-body">
                    <?php while($staff = mysqli_fetch_assoc($staff_performance)): ?>
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <strong><?php echo htmlspecialchars($staff['Staff_Name']); ?></strong>
                            <div class="small text-muted"><?php echo ucfirst($staff['Role']); ?></div>
                        </div>
                        <span class="badge bg-primary"><?php echo $staff['orders_handled']; ?> orders</span>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Revenue Table -->
    <div class="card">
        <div class="card-header">
            <h6 class="mb-0"><i class="fas fa-table"></i> Monthly Revenue Breakdown</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0" id="revenueTable">
                    <thead class="table-light">
                        <tr>
                            <th>Month</th>
                            <th>Revenue</th>
                            <th>Transactions</th>
                            <th>Avg. Transaction</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php mysqli_data_seek($monthly_revenue, 0); ?>
                        <?php while($row = mysqli_fetch_assoc($monthly_revenue)): ?>
                        <tr>
                            <td><?php echo date('F Y', strtotime($row['month'] . '-01')); ?></td>
                            <td class="fw-bold">Rs. <?php echo number_format($row['revenue'], 2); ?></td>
                            <td><?php echo $row['transactions']; ?></td>
                            <td>Rs. <?php echo number_format($row['revenue'] / max(1, $row['transactions']), 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Revenue Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($months); ?>,
        datasets: [{
            label: 'Revenue (Rs.)',
            data: <?php echo json_encode($revenues); ?>,
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#764ba2',
            pointBorderColor: '#fff',
            pointRadius: 4,
            pointHoverRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: {
            legend: { position: 'top' },
            tooltip: { callbacks: { label: (ctx) => 'Rs. ' + ctx.raw.toLocaleString() } }
        },
        scales: { y: { beginAtZero: true, ticks: { callback: (v) => 'Rs. ' + v.toLocaleString() } } }
    }
});

// Daily Orders Chart
const dailyCtx = document.getElementById('dailyChart').getContext('2d');
new Chart(dailyCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($daily_dates); ?>,
        datasets: [{
            label: 'Number of Orders',
            data: <?php echo json_encode($daily_counts); ?>,
            backgroundColor: 'rgba(102, 126, 234, 0.7)',
            borderColor: '#667eea',
            borderWidth: 1,
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});

// Status Chart
const statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode($status_labels); ?>,
        datasets: [{
            data: <?php echo json_encode($status_counts); ?>,
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

// Export to Excel
function exportToExcel() {
    const table = document.getElementById('revenueTable');
    let html = table.outerHTML;
    const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'revenue_report.xls';
    link.click();
}
</script>

<?php include_once '../../includes/footer.php'; ?>