<?php
/**
 * Invoice Detail - Teddy Shine Laundry Management System
 * 
 * View invoice details and record payments
 */

require_once '../../includes/admin_check.php';
require_once '../../config/database.php';
require_once '../../config/functions.php';

$invoice_id = intval($_GET['id'] ?? 0);

// Get invoice details
$invoice_query = "SELECT i.*, o.Order_ID, o.Order_Date, o.Delivery_Date,
                  CONCAT(r.F_Name, ' ', r.L_Name) as customer_name,
                  r.Phone_No, r.Email, r.House_No, r.Street, r.Area, r.City,
                  ds.Slot_Type
                  FROM Invoice i
                  JOIN Orders o ON i.Order_ID = o.Order_ID
                  JOIN Resident r ON o.Resident_ID = r.Resident_ID
                  LEFT JOIN DeliverySlots ds ON o.Slot_ID = ds.Slot_ID
                  WHERE i.Invoice_ID = $invoice_id";
$invoice_result = mysqli_query($conn, $invoice_query);

if(mysqli_num_rows($invoice_result) == 0) {
    setFlashMessage("Invoice not found.", "error");
    redirect(BASE_URL . "/admin/invoices/invoices.php");
}
$invoice = mysqli_fetch_assoc($invoice_result);

// Get order items
$items_query = "SELECT oi.*, s.Service_Name 
                FROM OrderItems oi 
                JOIN Services s ON oi.Service_ID = s.Service_ID 
                WHERE oi.Order_ID = {$invoice['Order_ID']}";
$items = mysqli_query($conn, $items_query);

// Get payments
$payments_query = "SELECT * FROM Payments WHERE Invoice_ID = $invoice_id ORDER BY Payment_Date DESC";
$payments = mysqli_query($conn, $payments_query);
$total_paid = 0;
while($payment = mysqli_fetch_assoc($payments)) {
    $total_paid += $payment['Payment_Amount'];
}
mysqli_data_seek($payments, 0);
$due_amount = $invoice['Final_Amount'] - $total_paid;

// Handle payment recording
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['record_payment'])) {
    $amount = floatval($_POST['amount']);
    $method = sanitize($_POST['payment_method']);
    
    if($amount <= 0) {
        $payment_error = "Please enter a valid amount.";
    } elseif($amount > $due_amount) {
        $payment_error = "Amount cannot exceed due amount of Rs. " . number_format($due_amount, 2);
    } else {
        $query = "INSERT INTO Payments (Invoice_ID, Payment_Date, Payment_Amount, Payment_Method, Payment_Status) 
                  VALUES ($invoice_id, CURDATE(), $amount, '$method', 'Completed')";
        if(mysqli_query($conn, $query)) {
            setFlashMessage("Payment of Rs. " . number_format($amount, 2) . " recorded successfully.", "success");
            redirect(BASE_URL . "/admin/invoices/invoice_detail.php?id=$invoice_id");
        } else {
            $payment_error = "Error recording payment.";
        }
    }
}

$custom_title = "Invoice #INV-" . str_pad($invoice_id, 6, '0', STR_PAD_LEFT);
include_once '../../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-file-invoice"></i> Invoice Details</h2>
        <div class="no-print">
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="fas fa-print"></i> Print
            </button>
            <a href="invoices.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Invoice Info -->
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-info-circle"></i> Invoice Information</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm">
                        <tr><th width="40%">Invoice #</th><td>INV-<?php echo str_pad($invoice_id, 6, '0', STR_PAD_LEFT); ?></td></tr>
                        <tr><th>Order #</th><td>#<?php echo $invoice['Order_ID']; ?></td></tr>
                        <tr><th>Invoice Date</th><td><?php echo date('d M Y', strtotime($invoice['Invoice_Date'])); ?></td></tr>
                        <tr><th>Order Date</th><td><?php echo date('d M Y', strtotime($invoice['Order_Date'])); ?></td></tr>
                        <tr><th>Delivery Date</th><td><?php echo $invoice['Delivery_Date'] ? date('d M Y', strtotime($invoice['Delivery_Date'])) : 'Not scheduled'; ?></td></tr>
                        <tr><th>Delivery Slot</th><td><?php echo $invoice['Slot_Type'] ?? 'Not assigned'; ?></td></tr>
                    </table>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-user"></i> Customer Information</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm">
                        <tr><th width="40%">Name</th><td><?php echo htmlspecialchars($invoice['customer_name']); ?></td></tr>
                        <tr><th>Phone</th><td><?php echo $invoice['Phone_No']; ?></td></tr>
                        <tr><th>Email</th><td><?php echo $invoice['Email']; ?></td></tr>
                        <tr><th>Address</th><td><?php echo $invoice['House_No'] . ', ' . $invoice['Street'] . ', ' . $invoice['Area'] . ', ' . $invoice['City']; ?></td></tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Payment Summary -->
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-credit-card"></i> Payment Summary</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm">
                        <tr><th width="50%">Total Amount</th><td>Rs. <?php echo number_format($invoice['Final_Amount'], 2); ?></td></tr>
                        <tr><th>Amount Paid</th><td class="text-success">Rs. <?php echo number_format($total_paid, 2); ?></td></tr>
                        <tr><th>Due Amount</th><td class="text-danger fw-bold">Rs. <?php echo number_format($due_amount, 2); ?></td></tr>
                        <tr><th>Status</th><td><?php echo getPaymentStatusBadge($invoice['Invoice_Status']); ?></td></tr>
                    </table>

                    <?php if($due_amount > 0): ?>
                    <div class="mt-3 p-3 bg-light rounded no-print">
                        <strong><i class="fas fa-plus-circle"></i> Record Payment</strong>
                        <?php if(isset($payment_error)): ?>
                            <div class="alert alert-danger alert-sm mt-2"><?php echo $payment_error; ?></div>
                        <?php endif; ?>
                        <form method="POST" class="mt-2">
                            <div class="row g-2">
                                <div class="col-md-5">
                                    <input type="number" name="amount" class="form-control" placeholder="Amount" step="0.01" max="<?php echo $due_amount; ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <select name="payment_method" class="form-select" required>
                                        <option value="cash">Cash</option>
                                        <option value="card">Card</option>
                                        <option value="online">Online Banking</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <button type="submit" name="record_payment" class="btn btn-primary w-100">Record</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Items -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0"><i class="fas fa-tshirt"></i> Order Items</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0">
                    <thead class="table-light">
                        <tr><th>Service</th><th>Quantity</th><th>Unit Price</th><th>Total</th></tr>
                    </thead>
                    <tbody>
                        <?php 
                        $subtotal = 0;
                        while($item = mysqli_fetch_assoc($items)): 
                            $subtotal += $item['Price'];
                        ?>
                        <tr>
                            <td><?php echo $item['Service_Name']; ?></td>
                            <td><?php echo $item['Quantity']; ?></td>
                            <td>Rs. <?php echo number_format($item['Price'] / $item['Quantity'], 2); ?></td>
                            <td>Rs. <?php echo number_format($item['Price'], 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-active">
                            <td colspan="3" class="text-end"><strong>Subtotal</strong></td>
                            <td><strong>Rs. <?php echo number_format($subtotal, 2); ?></strong></td>
                        </tr>
                        <?php if($invoice['Discount'] > 0): ?>
                        <tr>
                            <td colspan="3" class="text-end text-success">Discount</td>
                            <td class="text-success">- Rs. <?php echo number_format($invoice['Discount'], 2); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr class="table-active">
                            <td colspan="3" class="text-end"><strong>Total</strong></td>
                            <td><strong>Rs. <?php echo number_format($invoice['Final_Amount'], 2); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Payment History -->
    <?php if(mysqli_num_rows($payments) > 0): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0"><i class="fas fa-history"></i> Payment History</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr><th>Date</th><th>Amount</th><th>Method</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php while($payment = mysqli_fetch_assoc($payments)): ?>
                        <tr>
                            <td><?php echo date('d M Y', strtotime($payment['Payment_Date'])); ?></td>
                            <td>Rs. <?php echo number_format($payment['Payment_Amount'], 2); ?></td>
                            <td><?php echo ucfirst($payment['Payment_Method']); ?></td>
                            <td><span class="badge bg-success">Completed</span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Invoice Footer -->
    <div class="text-center mt-3 no-print">
        <small class="text-muted">Thank you for choosing Teddy Shine Laundry Service!</small>
    </div>
</div>

<?php include_once '../../includes/footer.php'; ?>