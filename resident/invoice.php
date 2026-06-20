<?php
/**
 * resident-Invoice - Teddy Shine Laundry Management System
 * 
 * Display detailed invoice with payment options and print functionality
 */

require_once '../includes/auth_check.php';
require_once '../config/database.php';
require_once '../config/functions.php';

$invoice_id = intval($_GET['id'] ?? 0);
$resident_id = $_SESSION['resident_id'];

// Get invoice details with validation
$invoice_query = "SELECT i.*, o.Order_ID, o.Order_Date, o.Status as order_status,
                  CONCAT(r.F_Name, ' ', r.L_Name) as customer_name,
                  r.Phone_No, r.Email, r.House_No, r.Street, r.Area, r.City,
                  ds.Slot_Type, ds.Start_Time, ds.End_Time
                  FROM Invoice i
                  JOIN Orders o ON i.Order_ID = o.Order_ID
                  JOIN Resident r ON o.Resident_ID = r.Resident_ID
                  LEFT JOIN DeliverySlots ds ON o.Slot_ID = ds.Slot_ID
                  WHERE i.Invoice_ID = $invoice_id AND o.Resident_ID = $resident_id";
$invoice_result = mysqli_query($conn, $invoice_query);

if(mysqli_num_rows($invoice_result) == 0) {
    setFlashMessage("Invoice not found.", "error");
    redirect(BASE_URL . "/resident/my_orders.php");
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

$custom_title = "Invoice #INV-" . str_pad($invoice_id, 6, '0', STR_PAD_LEFT);
include_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="invoice-container">
        <!-- Print Button -->
        <div class="text-end mb-3 no-print">
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="fas fa-print"></i> Print Invoice
            </button>
            <a href="my_orders.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
        
        <!-- Invoice -->
        <div class="invoice-header">
            <i class="fas fa-tshirt fa-3x mb-3"></i>
            <h2>Teddy Shine Laundry</h2>
            <p>Premium Laundry Service</p>
        </div>
        
        <div class="invoice-body">
            <!-- Company Info -->
            <div class="company-info">
                <div class="invoice-title">TAX INVOICE</div>
                <div class="invoice-number">#INV-<?php echo str_pad($invoice_id, 6, '0', STR_PAD_LEFT); ?></div>
                <div class="small text-muted mt-2">Issue Date: <?php echo date('d M Y', strtotime($invoice['Invoice_Date'])); ?></div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="info-row">
                        <div class="info-label">Bill To</div>
                        <div class="info-value"><?php echo htmlspecialchars($invoice['customer_name']); ?></div>
                        <div class="small text-muted">
                            <?php echo $invoice['House_No'] ? $invoice['House_No'] . ', ' : ''; ?>
                            <?php echo $invoice['Street'] ? $invoice['Street'] . ', ' : ''; ?>
                            <?php echo $invoice['Area'] ? $invoice['Area'] . ', ' : ''; ?>
                            <?php echo $invoice['City']; ?>
                        </div>
                        <div class="small text-muted">Phone: <?php echo $invoice['Phone_No']; ?></div>
                        <div class="small text-muted">Email: <?php echo $invoice['Email']; ?></div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="info-row text-md-end">
                        <div class="info-label">Order Information</div>
                        <div class="info-value">Order #<?php echo $invoice['Order_ID']; ?></div>
                        <div class="small text-muted">Order Date: <?php echo date('d M Y', strtotime($invoice['Order_Date'])); ?></div>
                        <div class="small text-muted">Delivery Slot: <?php echo $invoice['Slot_Type'] ?? 'Not assigned'; ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Items Table -->
            <table class="table table-bordered mt-4">
                <thead class="table-light">
                    <tr>
                        <th>Description</th>
                        <th class="text-center">Quantity</th>
                        <th class="text-end">Unit Price</th>
                        <th class="text-end">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $subtotal = 0;
                    while($item = mysqli_fetch_assoc($items)): 
                        $subtotal += $item['Price'];
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['Service_Name']); ?></td>
                        <td class="text-center"><?php echo $item['Quantity']; ?></td>
                        <td class="text-end">Rs. <?php echo number_format($item['Price'] / $item['Quantity'], 2); ?></td>
                        <td class="text-end">Rs. <?php echo number_format($item['Price'], 2); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" class="text-end"><strong>Subtotal</strong></td>
                        <td class="text-end"><strong>Rs. <?php echo number_format($subtotal, 2); ?></strong></td>
                    </tr>
                    <?php if($invoice['Discount'] > 0): ?>
                    <tr>
                        <td colspan="3" class="text-end text-success">Discount</td>
                        <td class="text-end text-success">- Rs. <?php echo number_format($invoice['Discount'], 2); ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr class="table-active">
                        <td colspan="3" class="text-end"><strong>Total Amount</strong></td>
                        <td class="text-end"><strong>Rs. <?php echo number_format($invoice['Final_Amount'], 2); ?></strong></td>
                    </tr>
                </tfoot>
            </table>
            
            <!-- Payment Summary -->
            <div class="payment-summary">
                <div class="row">
                    <div class="col-md-6">
                        <strong>Payment Summary</strong>
                        <div class="mt-2">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Total Amount:</span>
                                <span>Rs. <?php echo number_format($invoice['Final_Amount'], 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Amount Paid:</span>
                                <span class="text-success">Rs. <?php echo number_format($total_paid, 2); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Due Amount:</span>
                                <span class="text-danger fw-bold">Rs. <?php echo number_format($due_amount, 2); ?></span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <strong>Payment Status</strong>
                        <div class="mt-2">
                            <?php if($due_amount <= 0): ?>
                                <span class="status-paid"><i class="fas fa-check-circle"></i> PAID</span>
                            <?php elseif($total_paid > 0): ?>
                                <span class="status-partial"><i class="fas fa-hourglass-half"></i> PARTIAL</span>
                            <?php else: ?>
                                <span class="status-unpaid"><i class="fas fa-exclamation-circle"></i> UNPAID</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Payment Button -->
            <?php if($due_amount > 0): ?>
            <div class="mt-4 text-center no-print">
                <button class="btn btn-warning btn-lg" data-bs-toggle="modal" data-bs-target="#paymentModal">
                    <i class="fas fa-credit-card"></i> Pay Now (Rs. <?php echo number_format($due_amount, 2); ?>)
                </button>
            </div>
            <?php endif; ?>
            
            <!-- Footer Note -->
            <div class="text-center mt-4 pt-3 border-top">
                <small class="text-muted">
                    Thank you for choosing Teddy Shine!<br>
                    For any queries, contact us at support@teddyshine.com
                </small>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-credit-card"></i> Make Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="process_payment.php">
                <input type="hidden" name="invoice_id" value="<?php echo $invoice_id; ?>">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Invoice Amount</label>
                        <input type="text" class="form-control" value="Rs. <?php echo number_format($invoice['Final_Amount'], 2); ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Already Paid</label>
                        <input type="text" class="form-control" value="Rs. <?php echo number_format($total_paid, 2); ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Due Amount</label>
                        <input type="text" class="form-control text-danger fw-bold" value="Rs. <?php echo number_format($due_amount, 2); ?>" disabled>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Amount *</label>
                        <input type="number" name="amount" class="form-control" max="<?php echo $due_amount; ?>" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="cash">Cash on Delivery</option>
                            <option value="card">Credit/Debit Card</option>
                            <option value="online">Online Banking</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Confirm Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include_once '../includes/footer.php'; ?>