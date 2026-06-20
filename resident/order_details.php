<?php
/**
 * Order Details - Teddy Shine Laundry Management System
 * 
 * Detailed view of a single order with all information
 */

require_once '../includes/auth_check.php';
require_once '../config/database.php';
require_once '../config/functions.php';

$order_id = intval($_GET['id'] ?? 0);
$resident_id = $_SESSION['resident_id'];

// Verify order belongs to resident
$order_query = "SELECT o.*, ds.Slot_Type, ds.Start_Time, ds.End_Time,
                CONCAT(r.F_Name, ' ', r.L_Name) as customer_name,
                r.Phone_No, r.Email, r.House_No, r.Street, r.Area, r.City
                FROM Orders o 
                LEFT JOIN DeliverySlots ds ON o.Slot_ID = ds.Slot_ID
                JOIN Resident r ON o.Resident_ID = r.Resident_ID
                WHERE o.Order_ID = $order_id AND o.Resident_ID = $resident_id";
$order_result = mysqli_query($conn, $order_query);

if(mysqli_num_rows($order_result) == 0) {
    setFlashMessage("Order not found.", "error");
    redirect(BASE_URL . "/resident/my_orders.php");
}

$order = mysqli_fetch_assoc($order_result);

// Get order items with details
$items_query = "SELECT oi.*, s.Service_Name, s.Service_Price as unit_price 
                FROM OrderItems oi 
                JOIN Services s ON oi.Service_ID = s.Service_ID 
                WHERE oi.Order_ID = $order_id";
$items = mysqli_query($conn, $items_query);

// Get invoice details
$invoice_query = "SELECT i.*, 
                  COALESCE(SUM(p.Payment_Amount), 0) as paid_amount
                  FROM Invoice i
                  LEFT JOIN Payments p ON i.Invoice_ID = p.Invoice_ID
                  WHERE i.Order_ID = $order_id
                  GROUP BY i.Invoice_ID";
$invoice_result = mysqli_query($conn, $invoice_query);
$invoice = mysqli_fetch_assoc($invoice_result);

if($invoice) {
    $due_amount = $invoice['Final_Amount'] - $invoice['paid_amount'];
}

$custom_title = "Order #$order_id - Teddy Shine";
include_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-receipt"></i> Order #<?php echo $order_id; ?></h2>
        <a href="my_orders.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Orders
        </a>
    </div>
    
    <!-- Order Progress Bar -->
    <div class="card mb-4">
        <div class="card-body">
            <h6 class="mb-3">Order Progress</h6>
            <?php
            $stages = ['Pending', 'Processing', 'Washing', 'Drying', 'Ironing', 'Packing', 'Delivered'];
            $current = $order['Status'];
            $current_index = array_search($current, $stages);
            $progress = (($current_index + 1) / count($stages)) * 100;
            ?>
            <div class="progress mb-2" style="height: 10px;">
                <div class="progress-bar bg-success" style="width: <?php echo $progress; ?>%"></div>
            </div>
            <div class="row text-center">
                <?php foreach($stages as $index => $stage): ?>
                <div class="col">
                    <small class="<?php echo $index <= $current_index ? 'text-success fw-bold' : 'text-muted'; ?>">
                        <?php echo $stage; ?>
                    </small>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Left Column - Order Info -->
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-info-circle"></i> Order Information</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm">
                        <tr>
                            <th width="40%">Order Date</th>
                            <td><?php echo date('d M Y h:i A', strtotime($order['Order_Date'])); ?></td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td><?php echo getStatusBadge($order['Status']); ?></td>
                        </tr>
                        <tr>
                            <th>Delivery Slot</th>
                            <td><?php echo $order['Slot_Type'] ?? 'Not assigned'; ?></td>
                        </tr>
                        <tr>
                            <th>Delivery Date</th>
                            <td><?php echo $order['Delivery_Date'] ? date('d M Y', strtotime($order['Delivery_Date'])) : 'Not scheduled'; ?></td>
                        </tr>
                        <tr>
                            <th>Total Amount</th>
                            <td><strong>Rs. <?php echo number_format($order['Amount'], 2); ?></strong></td>
                        </tr>
                    </table>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-map-marker-alt"></i> Delivery Address</h6>
                </div>
                <div class="card-body">
                    <p class="mb-0">
                        <?php echo $order['House_No'] ? $order['House_No'] . ', ' : ''; ?>
                        <?php echo $order['Street'] ? $order['Street'] . ', ' : ''; ?>
                        <?php echo $order['Area'] ? $order['Area'] . ', ' : ''; ?>
                        <?php echo $order['City']; ?>
                    </p>
                    <p class="mb-0 mt-2"><strong>Phone:</strong> <?php echo $order['Phone_No']; ?></p>
                </div>
            </div>
        </div>
        
        <!-- Right Column - Payment & Invoice -->
        <div class="col-lg-6">
            <?php if($invoice): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-credit-card"></i> Invoice Summary</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm">
                        <tr>
                            <th width="50%">Subtotal</th>
                            <td class="text-end">Rs. <?php echo number_format($invoice['Total_Amount'], 2); ?></td>
                        </tr>
                        <?php if($invoice['Discount'] > 0): ?>
                        <tr>
                            <th>Discount</th>
                            <td class="text-end text-success">- Rs. <?php echo number_format($invoice['Discount'], 2); ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th>Total Amount</th>
                            <td class="text-end"><strong>Rs. <?php echo number_format($invoice['Final_Amount'], 2); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Paid Amount</th>
                            <td class="text-end text-success">Rs. <?php echo number_format($invoice['paid_amount'], 2); ?></td>
                        </tr>
                        <tr>
                            <th>Due Amount</th>
                            <td class="text-end text-danger fw-bold">Rs. <?php echo number_format($due_amount, 2); ?></td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td class="text-end"><?php echo getPaymentStatusBadge($invoice['Invoice_Status']); ?></td>
                        </tr>
                    </table>
                    
                    <?php if($due_amount > 0): ?>
                    <div class="mt-3">
                        <a href="invoice.php?id=<?php echo $invoice['Invoice_ID']; ?>" class="btn btn-warning w-100">
                            <i class="fas fa-credit-card"></i> Pay Now (Rs. <?php echo number_format($due_amount, 2); ?>)
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-success mb-0 mt-3">
                        <i class="fas fa-check-circle"></i> Fully Paid - Thank you!
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Items Table -->
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="mb-0"><i class="fas fa-tshirt"></i> Order Items</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Service</th>
                            <th>Quantity</th>
                            <th>Unit Price</th>
                            <th>Total</th>
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
                            <td><?php echo $item['Quantity']; ?></td>
                            <td>Rs. <?php echo number_format($item['Price'] / $item['Quantity'], 2); ?></td>
                            <td class="fw-bold">Rs. <?php echo number_format($item['Price'], 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                    <tfoot class="table-active">
                        <tr>
                            <td colspan="3" class="text-end"><strong>Total</strong></td>
                            <td><strong>Rs. <?php echo number_format($subtotal, 2); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="text-center mb-4">
        <a href="track_order.php?id=<?php echo $order_id; ?>" class="btn btn-info">
            <i class="fas fa-map-marker-alt"></i> Track Order
        </a>
        <?php if($invoice && $due_amount > 0): ?>
        <a href="invoice.php?id=<?php echo $invoice['Invoice_ID']; ?>" class="btn btn-warning">
            <i class="fas fa-credit-card"></i> Make Payment
        </a>
        <?php endif; ?>
        <?php if($order['Status'] == 'Pending'): ?>
        <button class="btn btn-danger" onclick="cancelOrder(<?php echo $order_id; ?>)">
            <i class="fas fa-times"></i> Cancel Order
        </button>
        <?php endif; ?>
    </div>
</div>

<script>
function cancelOrder(orderId) {
    if(confirm('Are you sure you want to cancel this order? This action cannot be undone.')) {
        window.location.href = 'cancel_order.php?id=' + orderId;
    }
}
</script>

<?php include_once '../includes/footer.php'; ?>