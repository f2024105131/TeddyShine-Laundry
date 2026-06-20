<?php
/**
 * Order Detail - Teddy Shine Laundry Management System
 * 
 * Detailed view of a single order with all information
 */

require_once '../../includes/admin_check.php';
require_once '../../config/database.php';
require_once '../../config/functions.php';

$order_id = intval($_GET['id'] ?? 0);

// Get order details
$order_query = "SELECT o.*, CONCAT(r.F_Name, ' ', r.L_Name) as customer_name,
                r.Phone_No, r.Email, r.House_No, r.Street, r.Area, r.City,
                ds.Slot_Type, ds.Start_Time, ds.End_Time,
                s.Staff_Name, s.Role as staff_role
                FROM Orders o
                JOIN Resident r ON o.Resident_ID = r.Resident_ID
                LEFT JOIN DeliverySlots ds ON o.Slot_ID = ds.Slot_ID
                LEFT JOIN Staff s ON o.Staff_ID = s.Staff_ID
                WHERE o.Order_ID = $order_id";
$order_result = mysqli_query($conn, $order_query);

if(mysqli_num_rows($order_result) == 0) {
    setFlashMessage("Order not found.", "error");
    redirect(BASE_URL . "/admin/orders/orders.php");
}
$order = mysqli_fetch_assoc($order_result);

// Get order items
$items_query = "SELECT oi.*, s.Service_Name 
                FROM OrderItems oi 
                JOIN Services s ON oi.Service_ID = s.Service_ID 
                WHERE oi.Order_ID = $order_id";
$items = mysqli_query($conn, $items_query);

// Get invoice
$invoice_query = "SELECT i.*, COALESCE(SUM(p.Payment_Amount), 0) as paid_amount
                  FROM Invoice i
                  LEFT JOIN Payments p ON i.Invoice_ID = p.Invoice_ID
                  WHERE i.Order_ID = $order_id
                  GROUP BY i.Invoice_ID";
$invoice_result = mysqli_query($conn, $invoice_query);
$invoice = mysqli_fetch_assoc($invoice_result);
$due_amount = $invoice ? ($invoice['Final_Amount'] - $invoice['paid_amount']) : 0;

$custom_title = "Order #$order_id - Admin Panel";
include_once '../../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-receipt"></i> Order Details #<?php echo $order_id; ?></h2>
        <div>
            <a href="orders.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
            <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print"></i> Print</button>
        </div>
    </div>

    <!-- Status Update -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <strong>Current Status: <?php echo getStatusBadge($order['Status']); ?></strong>
                </div>
                <div class="col-md-6">
                    <select id="statusUpdate" class="form-select">
                        <option value="Pending" <?php echo $order['Status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="Processing" <?php echo $order['Status'] == 'Processing' ? 'selected' : ''; ?>>Processing</option>
                        <option value="In Progress" <?php echo $order['Status'] == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                        <option value="Completed" <?php echo $order['Status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="Delivered" <?php echo $order['Status'] == 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                        <option value="Cancelled" <?php echo $order['Status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-primary" onclick="updateStatus()">Update Status</button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Order Information -->
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-info-circle"></i> Order Information</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm">
                        <tr><th width="40%">Order ID</th><td>#<?php echo $order_id; ?></td></tr>
                        <tr><th>Order Date</th><td><?php echo date('d M Y h:i A', strtotime($order['Order_Date'])); ?></td></tr>
                        <tr><th>Delivery Date</th><td><?php echo $order['Delivery_Date'] ? date('d M Y', strtotime($order['Delivery_Date'])) : 'Not scheduled'; ?></td></tr>
                        <tr><th>Delivery Slot</th><td><?php echo $order['Slot_Type'] ?? 'Not assigned'; ?></td></tr>
                        <tr><th>Amount</th><td><strong>Rs. <?php echo number_format($order['Amount'], 2); ?></strong></td></tr>
                    </table>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-user"></i> Customer Information</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm">
                        <tr><th width="40%">Name</th><td><?php echo htmlspecialchars($order['customer_name']); ?></td></tr>
                        <tr><th>Phone</th><td><?php echo $order['Phone_No']; ?></td></tr>
                        <tr><th>Email</th><td><?php echo $order['Email']; ?></td></tr>
                        <tr><th>Address</th><td><?php echo $order['House_No'] . ', ' . $order['Street'] . ', ' . $order['Area'] . ', ' . $order['City']; ?></td></tr>
                    </table>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-user-tie"></i> Staff Assignment</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <select id="staffAssign" class="form-select">
                                <option value="">Unassigned</option>
                                <?php
                                $staff_list = mysqli_query($conn, "SELECT Staff_ID, Staff_Name, Role FROM Staff");
                                while($staff = mysqli_fetch_assoc($staff_list)):
                                ?>
                                <option value="<?php echo $staff['Staff_ID']; ?>" <?php echo $order['Staff_ID'] == $staff['Staff_ID'] ? 'selected' : ''; ?>>
                                    <?php echo $staff['Staff_Name']; ?> (<?php echo ucfirst($staff['Role']); ?>)
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <button class="btn btn-primary btn-sm" onclick="assignStaff()">Assign</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Items & Invoice -->
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-tshirt"></i> Order Items</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr><th>Service</th><th>Qty</th><th>Price</th></tr>
                            </thead>
                            <tbody>
                                <?php while($item = mysqli_fetch_assoc($items)): ?>
                                <tr>
                                    <td><?php echo $item['Service_Name']; ?></td>
                                    <td><?php echo $item['Quantity']; ?></td>
                                    <td>Rs. <?php echo number_format($item['Price'], 2); ?></td>
                                </tr>
                                <?php endwhile; ?>
                                <tr class="table-active">
                                    <td colspan="2" class="text-end"><strong>Total</strong></td>
                                    <td><strong>Rs. <?php echo number_format($order['Amount'], 2); ?></strong></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <?php if($invoice): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-file-invoice"></i> Invoice Summary</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless table-sm">
                        <tr><th width="50%">Invoice #</th><td>INV-<?php echo str_pad($invoice['Invoice_ID'], 6, '0', STR_PAD_LEFT); ?></td></tr>
                        <tr><th>Total Amount</th><td>Rs. <?php echo number_format($invoice['Final_Amount'], 2); ?></td></tr>
                        <tr><th>Paid Amount</th><td class="text-success">Rs. <?php echo number_format($invoice['paid_amount'], 2); ?></td></tr>
                        <tr><th>Due Amount</th><td class="text-danger fw-bold">Rs. <?php echo number_format($due_amount, 2); ?></td></tr>
                        <tr><th>Status</th><td><?php echo getPaymentStatusBadge($invoice['Invoice_Status']); ?></td></tr>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function updateStatus() {
    const status = document.getElementById('statusUpdate').value;
    fetch('orders.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `update_status=1&order_id=<?php echo $order_id; ?>&status=${status}`
    }).then(() => location.reload());
}

function assignStaff() {
    const staffId = document.getElementById('staffAssign').value;
    fetch('orders.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `assign_staff=1&order_id=<?php echo $order_id; ?>&staff_id=${staffId}`
    }).then(() => location.reload());
}
</script>

<?php include_once '../../includes/footer.php'; ?>