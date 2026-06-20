<?php
/**
 * Monthly Report - Teddy Shine Laundry Management System
 * 
 * Generate detailed monthly financial and operational report
 */

require_once '../../includes/admin_check.php';
require_once '../../config/database.php';
require_once '../../config/functions.php';

// Get selected month (default: current month)
$report_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');
$month_year = date('F Y', strtotime($report_month . '-01'));
$year = date('Y', strtotime($report_month . '-01'));
$month = date('m', strtotime($report_month . '-01'));

// Get monthly summary
$summary_query = "SELECT 
                   COUNT(DISTINCT o.Order_ID) as total_orders,
                   SUM(o.Amount) as total_revenue,
                   COUNT(DISTINCT o.Resident_ID) as unique_customers,
                   (SELECT COUNT(*) FROM Orders WHERE MONTH(Order_Date) = $month AND YEAR(Order_Date) = $year AND Status = 'Completed') as completed_orders,
                   (SELECT COUNT(*) FROM Orders WHERE MONTH(Order_Date) = $month AND YEAR(Order_Date) = $year AND Status = 'Pending') as pending_orders,
                   (SELECT COUNT(*) FROM Orders WHERE MONTH(Order_Date) = $month AND YEAR(Order_Date) = $year AND Status = 'Cancelled') as cancelled_orders,
                   (SELECT COUNT(*) FROM Invoice WHERE MONTH(Invoice_Date) = $month AND YEAR(Invoice_Date) = $year AND Invoice_Status = 'Paid') as paid_invoices,
                   (SELECT COUNT(*) FROM Invoice WHERE MONTH(Invoice_Date) = $month AND YEAR(Invoice_Date) = $year AND Invoice_Status = 'Unpaid') as unpaid_invoices
                   FROM Orders o
                   WHERE MONTH(o.Order_Date) = $month AND YEAR(o.Order_Date) = $year";
$summary_result = mysqli_query($conn, $summary_query);
$summary = mysqli_fetch_assoc($summary_result);

// Get daily orders for the month
$daily_query = "SELECT DATE(Order_Date) as date, COUNT(*) as count, SUM(Amount) as revenue
                FROM Orders 
                WHERE MONTH(Order_Date) = $month AND YEAR(Order_Date) = $year
                GROUP BY DATE(Order_Date)
                ORDER BY date";
$daily_data = mysqli_query($conn, $daily_query);

// Get top services for the month
$top_services_query = "SELECT s.Service_Name, COUNT(oi.Service_ID) as order_count, SUM(oi.Price) as revenue
                       FROM OrderItems oi
                       JOIN Services s ON oi.Service_ID = s.Service_ID
                       JOIN Orders o ON oi.Order_ID = o.Order_ID
                       WHERE MONTH(o.Order_Date) = $month AND YEAR(o.Order_Date) = $year
                       GROUP BY oi.Service_ID
                       ORDER BY order_count DESC
                       LIMIT 5";
$top_services = mysqli_query($conn, $top_services_query);

// Get recent orders for the month
$recent_orders_query = "SELECT o.Order_ID, o.Order_Date, o.Amount, o.Status,
                        CONCAT(r.F_Name, ' ', r.L_Name) as customer_name
                        FROM Orders o
                        JOIN Resident r ON o.Resident_ID = r.Resident_ID
                        WHERE MONTH(o.Order_Date) = $month AND YEAR(o.Order_Date) = $year
                        ORDER BY o.Order_Date DESC
                        LIMIT 20";
$recent_orders = mysqli_query($conn, $recent_orders_query);

// Get payment summary
$payment_query = "SELECT 
                  SUM(Payment_Amount) as total_collected,
                  COUNT(*) as total_transactions,
                  AVG(Payment_Amount) as avg_payment
                  FROM Payments 
                  WHERE MONTH(Payment_Date) = $month AND YEAR(Payment_Date) = $year AND Payment_Status = 'Completed'";
$payment_result = mysqli_query($conn, $payment_query);
$payment_data = mysqli_fetch_assoc($payment_result);

$custom_title = "Monthly Report - " . $month_year;
include_once '../../includes/header.php';
?>

<style>
.report-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 30px;
    text-align: center;
}
.report-section {
    background: white;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.summary-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.summary-value {
    font-size: 28px;
    font-weight: 800;
    color: #667eea;
}
.summary-label {
    color: #6b7280;
    font-size: 14px;
}
@media print {
    .no-print { display: none !important; }
    .report-section { break-inside: avoid; box-shadow: none; border: 1px solid #ddd; }
}
</style>

<div class="container mt-4">
    <!-- Report Header -->
    <div class="report-header">
        <h2><i class="fas fa-file-alt"></i> Monthly Report</h2>
        <h4><?php echo $month_year; ?></h4>
        <p class="mb-0">Generated on: <?php echo date('d M Y h:i A'); ?></p>
    </div>

    <!-- Month Selector -->
    <div class="row mb-4 no-print">
        <div class="col-md-6 mx-auto">
            <form method="GET" action="" class="row g-2">
                <div class="col-md-8">
                    <input type="month" name="month" class="form-control" value="<?php echo $report_month; ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">Generate Report</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Print/Export Buttons -->
    <div class="text-end mb-4 no-print">
        <button onclick="window.print()" class="btn btn-secondary">
            <i class="fas fa-print"></i> Print Report
        </button>
        <button onclick="exportReport()" class="btn btn-success">
            <i class="fas fa-file-excel"></i> Export Excel
        </button>
        <a href="reports.php" class="btn btn-primary">
            <i class="fas fa-arrow-left"></i> Back to Reports
        </a>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3 col-6 mb-3">
            <div class="summary-card">
                <div class="summary-value"><?php echo $summary['total_orders'] ?? 0; ?></div>
                <div class="summary-label">Total Orders</div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="summary-card">
                <div class="summary-value">Rs. <?php echo number_format($summary['total_revenue'] ?? 0, 2); ?></div>
                <div class="summary-label">Total Revenue</div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="summary-card">
                <div class="summary-value"><?php echo $summary['unique_customers'] ?? 0; ?></div>
                <div class="summary-label">Unique Customers</div>
            </div>
        </div>
        <div class="col-md-3 col-6 mb-3">
            <div class="summary-card">
                <div class="summary-value"><?php echo $summary['completed_orders'] ?? 0; ?></div>
                <div class="summary-label">Completed Orders</div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Order Status -->
        <div class="col-md-6">
            <div class="report-section">
                <h6><i class="fas fa-chart-pie"></i> Order Status Breakdown</h6>
                <hr>
                <div class="row">
                    <div class="col-6">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Completed:</span>
                            <strong><?php echo $summary['completed_orders'] ?? 0; ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Pending:</span>
                            <strong><?php echo $summary['pending_orders'] ?? 0; ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Cancelled:</span>
                            <strong><?php echo $summary['cancelled_orders'] ?? 0; ?></strong>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Paid Invoices:</span>
                            <strong><?php echo $summary['paid_invoices'] ?? 0; ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Unpaid Invoices:</span>
                            <strong><?php echo $summary['unpaid_invoices'] ?? 0; ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Summary -->
        <div class="col-md-6">
            <div class="report-section">
                <h6><i class="fas fa-credit-card"></i> Payment Summary</h6>
                <hr>
                <div class="row">
                    <div class="col-6">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Total Collected:</span>
                            <strong>Rs. <?php echo number_format($payment_data['total_collected'] ?? 0, 2); ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Transactions:</span>
                            <strong><?php echo $payment_data['total_transactions'] ?? 0; ?></strong>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Average Payment:</span>
                            <strong>Rs. <?php echo number_format($payment_data['avg_payment'] ?? 0, 2); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Services -->
    <div class="row">
        <div class="col-md-6">
            <div class="report-section">
                <h6><i class="fas fa-crown"></i> Top 5 Services</h6>
                <hr>
                <?php while($service = mysqli_fetch_assoc($top_services)): ?>
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span><?php echo htmlspecialchars($service['Service_Name']); ?></span>
                    <div>
                        <span class="badge bg-primary me-2"><?php echo $service['order_count']; ?> orders</span>
                        <span class="small">Rs. <?php echo number_format($service['revenue'], 2); ?></span>
                    </div>
                </div>
                <?php endwhile; ?>
                <?php if(mysqli_num_rows($top_services) == 0): ?>
                <p class="text-muted text-center">No services data available for this month.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Daily Orders Chart -->
        <div class="col-md-6">
            <div class="report-section">
                <h6><i class="fas fa-chart-line"></i> Daily Orders</h6>
                <hr>
                <canvas id="dailyChart" height="200"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="report-section">
        <h6><i class="fas fa-history"></i> Recent Orders (This Month)</h6>
        <hr>
        <div class="table-responsive">
            <table class="table table-hover">
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
                        <td>#<?php echo $order['Order_ID']; ?></td>
                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                        <td><?php echo date('d M Y', strtotime($order['Order_Date'])); ?></td>
                        <td>Rs. <?php echo number_format($order['Amount'], 2); ?></td>
                        <td><?php echo getStatusBadge($order['Status']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if(mysqli_num_rows($recent_orders) == 0): ?>
                    <tr>
                        <td colspan="5" class="text-center">No orders found for this month.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Footer -->
    <div class="text-center mt-3 no-print">
        <small class="text-muted">Report generated by Teddy Shine Laundry Management System</small>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Daily Orders Chart
const dailyData = <?php 
$dates = [];
$counts = [];
while($row = mysqli_fetch_assoc($daily_data)) {
    $dates[] = date('d M', strtotime($row['date']));
    $counts[] = $row['count'];
}
echo json_encode(['dates' => $dates, 'counts' => $counts]);
?>;

const dailyCtx = document.getElementById('dailyChart').getContext('2d');
new Chart(dailyCtx, {
    type: 'bar',
    data: {
        labels: dailyData.dates,
        datasets: [{
            label: 'Orders',
            data: dailyData.counts,
            backgroundColor: 'rgba(102, 126, 234, 0.7)',
            borderColor: '#667eea',
            borderWidth: 1,
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
    }
});

// Export Report
function exportReport() {
    const table = document.querySelector('.table');
    if (!table) return;
    
    let html = `
        <html>
        <head><title>Monthly Report - <?php echo $month_year; ?></title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; }
            table { border-collapse: collapse; width: 100%; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background: #667eea; color: white; }
            .header { text-align: center; margin-bottom: 30px; }
        </style>
        </head>
        <body>
            <div class="header">
                <h2>Teddy Shine Laundry - Monthly Report</h2>
                <h3><?php echo $month_year; ?></h3>
                <p>Generated: <?php echo date('d M Y h:i A'); ?></p>
            </div>
            <table>
                ${table.outerHTML}
            </table>
        </body>
        </html>
    `;
    
    const blob = new Blob([html], { type: 'application/vnd.ms-excel' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'monthly_report_<?php echo $report_month; ?>.xls';
    link.click();
}
</script>

<?php include_once '../../includes/footer.php'; ?>