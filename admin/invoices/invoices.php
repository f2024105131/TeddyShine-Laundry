<?php
/**
 * Invoice Management - Teddy Shine Laundry Management System
 * 
 * View all invoices with payment status and filters
 */

require_once '../../includes/admin_check.php';
require_once '../../config/database.php';
require_once '../../config/functions.php';

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filters
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';

// Build query
$where = "1=1";
if($status_filter != 'all') {
    $where .= " AND i.Invoice_Status = '$status_filter'";
}
if(!empty($search)) {
    $where .= " AND (i.Invoice_ID LIKE '%$search%' OR o.Order_ID LIKE '%$search%' OR CONCAT(r.F_Name, ' ', r.L_Name) LIKE '%$search%')";
}
if(!empty($date_from)) {
    $where .= " AND i.Invoice_Date >= '$date_from'";
}
if(!empty($date_to)) {
    $where .= " AND i.Invoice_Date <= '$date_to'";
}

// Get total count
$count_query = "SELECT COUNT(*) as total FROM Invoice i 
                JOIN Orders o ON i.Order_ID = o.Order_ID 
                JOIN Resident r ON o.Resident_ID = r.Resident_ID 
                WHERE $where";
$count_result = mysqli_query($conn, $count_query);
$total_rows = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_rows / $limit);

// Get invoices
$invoices_query = "SELECT i.*, o.Order_ID, o.Order_Date,
                   CONCAT(r.F_Name, ' ', r.L_Name) as customer_name,
                   (SELECT SUM(Payment_Amount) FROM Payments WHERE Invoice_ID = i.Invoice_ID) as paid_amount
                   FROM Invoice i
                   JOIN Orders o ON i.Order_ID = o.Order_ID
                   JOIN Resident r ON o.Resident_ID = r.Resident_ID
                   WHERE $where
                   ORDER BY i.Invoice_Date DESC
                   LIMIT $offset, $limit";
$invoices = mysqli_query($conn, $invoices_query);

// Calculate totals for summary
$summary_query = "SELECT 
                   SUM(CASE WHEN Invoice_Status = 'Paid' THEN Final_Amount ELSE 0 END) as total_paid,
                   SUM(CASE WHEN Invoice_Status = 'Unpaid' THEN Final_Amount ELSE 0 END) as total_unpaid,
                   SUM(CASE WHEN Invoice_Status = 'Partial' THEN Final_Amount ELSE 0 END) as total_partial
                   FROM Invoice i
                   JOIN Orders o ON i.Order_ID = o.Order_ID";
$summary_result = mysqli_query($conn, $summary_query);
$summary = mysqli_fetch_assoc($summary_result);

$custom_title = "Invoice Management - Teddy Shine";
include_once '../../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-file-invoice-dollar"></i> Invoice Management</h2>
        <div>
            <span class="badge bg-primary">Total: <?php echo $total_rows; ?> invoices</span>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="summary-card text-center bg-success text-white">
                <i class="fas fa-check-circle fa-2x mb-2"></i>
                <h3>Rs. <?php echo number_format($summary['total_paid'], 2); ?></h3>
                <p class="mb-0">Paid Invoices</p>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="summary-card text-center bg-warning text-dark">
                <i class="fas fa-hourglass-half fa-2x mb-2"></i>
                <h3>Rs. <?php echo number_format($summary['total_partial'], 2); ?></h3>
                <p class="mb-0">Partial Payments</p>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="summary-card text-center bg-danger text-white">
                <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                <h3>Rs. <?php echo number_format($summary['total_unpaid'], 2); ?></h3>
                <p class="mb-0">Unpaid Invoices</p>
            </div>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section mb-4">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Search by invoice #, order #, customer..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Invoices</option>
                    <option value="Paid" <?php echo $status_filter == 'Paid' ? 'selected' : ''; ?>>Paid</option>
                    <option value="Partial" <?php echo $status_filter == 'Partial' ? 'selected' : ''; ?>>Partial</option>
                    <option value="Unpaid" <?php echo $status_filter == 'Unpaid' ? 'selected' : ''; ?>>Unpaid</option>
                </select>
            </div>
            <div class="col-md-2">
                <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>" placeholder="From Date">
            </div>
            <div class="col-md-2">
                <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>" placeholder="To Date">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Apply Filters</button>
                <a href="invoices.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>

    <!-- Invoices Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Invoice #</th>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Paid</th>
                            <th>Due</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($invoice = mysqli_fetch_assoc($invoices)): 
                            $paid = $invoice['paid_amount'] ?? 0;
                            $due = $invoice['Final_Amount'] - $paid;
                        ?>
                        <tr>
                            <td><strong>INV-<?php echo str_pad($invoice['Invoice_ID'], 6, '0', STR_PAD_LEFT); ?></strong></td>
                            <td>#<?php echo $invoice['Order_ID']; ?></td>
                            <td><?php echo htmlspecialchars($invoice['customer_name']); ?></td>
                            <td><?php echo date('d M Y', strtotime($invoice['Invoice_Date'])); ?></td>
                            <td>Rs. <?php echo number_format($invoice['Final_Amount'], 2); ?></td>
                            <td class="text-success">Rs. <?php echo number_format($paid, 2); ?></td>
                            <td class="text-danger">Rs. <?php echo number_format($due, 2); ?></td>
                            <td><?php echo getPaymentStatusBadge($invoice['Invoice_Status']); ?></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="invoice_detail.php?id=<?php echo $invoice['Invoice_ID']; ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="../orders/order_detail.php?id=<?php echo $invoice['Order_ID']; ?>" class="btn btn-outline-info">
                                        <i class="fas fa-box"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                        <?php if(mysqli_num_rows($invoices) == 0): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4">
                                <i class="fas fa-inbox fa-2x text-muted mb-2 d-block"></i>
                                No invoices found
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pagination -->
    <?php if($total_pages > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php if($page > 1): ?>
            <li class="page-item"><a class="page-link" href="?page=<?php echo $page-1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">Previous</a></li>
            <?php endif; ?>
            <?php for($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>"><a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>"><?php echo $i; ?></a></li>
            <?php endfor; ?>
            <?php if($page < $total_pages): ?>
            <li class="page-item"><a class="page-link" href="?page=<?php echo $page+1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">Next</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php include_once '../../includes/footer.php'; ?>