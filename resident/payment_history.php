<?php
/**
 * resident-Payment History - Teddy Shine Laundry Management System
 * 
 * Display all payment transactions with filters
 */

require_once '../includes/auth_check.php';
require_once '../config/database.php';
require_once '../config/functions.php';

$resident_id = $_SESSION['resident_id'];

// Filters
$date_from = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';

// Build where clause
$where = "o.Resident_ID = $resident_id";
if(!empty($date_from)) {
    $where .= " AND p.Payment_Date >= '$date_from'";
}
if(!empty($date_to)) {
    $where .= " AND p.Payment_Date <= '$date_to'";
}

// Get payments
$payments_query = "SELECT p.*, i.Invoice_ID, i.Final_Amount, o.Order_ID, o.Order_Date
                   FROM Payments p
                   JOIN Invoice i ON p.Invoice_ID = i.Invoice_ID
                   JOIN Orders o ON i.Order_ID = o.Order_ID
                   WHERE $where
                   ORDER BY p.Payment_Date DESC";
$payments = mysqli_query($conn, $payments_query);

// Calculate total
$total_payments = 0;
while($payment = mysqli_fetch_assoc($payments)) {
    $total_payments += $payment['Payment_Amount'];
}
mysqli_data_seek($payments, 0);

$custom_title = "Payment History - Teddy Shine";
include_once '../includes/header.php';
?>

<div class="container mt-4">
    <h2 class="mb-4"><i class="fas fa-history"></i> Payment History</h2>
    
    <!-- Summary Card -->
    <div class="row mb-4">
        <div class="col-md-6 mx-auto">
            <div class="summary-card text-center">
                <i class="fas fa-rupee-sign fa-2x mb-2"></i>
                <h3>Rs. <?php echo number_format($total_payments, 2); ?></h3>
                <p class="mb-0">Total Paid</p>
            </div>
        </div>
    </div>
    
    <!-- Filter Section -->
    <div class="filter-section mb-4">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-5">
                <label class="form-label">From Date</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
            </div>
            <div class="col-md-5">
                <label class="form-label">To Date</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </div>
        </form>
    </div>
    
    <!-- Payments List -->
    <?php if(mysqli_num_rows($payments) > 0): ?>
        <?php while($payment = mysqli_fetch_assoc($payments)): ?>
        <div class="payment-card">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <div class="small text-muted">Date & Time</div>
                    <strong><?php echo date('d M Y', strtotime($payment['Payment_Date'])); ?></strong>
                </div>
                <div class="col-md-2">
                    <div class="small text-muted">Order #</div>
                    <strong>#<?php echo $payment['Order_ID']; ?></strong>
                </div>
                <div class="col-md-2">
                    <div class="small text-muted">Invoice #</div>
                    <strong>INV-<?php echo str_pad($payment['Invoice_ID'], 6, '0', STR_PAD_LEFT); ?></strong>
                </div>
                <div class="col-md-2">
                    <div class="small text-muted">Amount</div>
                    <strong class="text-success">Rs. <?php echo number_format($payment['Payment_Amount'], 2); ?></strong>
                </div>
                <div class="col-md-2">
                    <div class="small text-muted">Method</div>
                    <span class="badge bg-info"><?php echo ucfirst($payment['Payment_Method']); ?></span>
                </div>
                <div class="col-md-1 text-end">
                    <a href="invoice.php?id=<?php echo $payment['Invoice_ID']; ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-receipt fa-4x text-muted mb-3"></i>
                <h4>No Payment Records Found</h4>
                <p class="text-muted">You haven't made any payments yet.</p>
                <a href="place_order.php" class="btn btn-primary">Place Your First Order</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include_once '../includes/footer.php'; ?>