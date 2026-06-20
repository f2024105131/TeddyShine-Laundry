<?php
/**
 * Order Management - Teddy Shine Laundry Management System
 * 
 * List all orders with filters, search, pagination, and bulk actions
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

// Build where clause
$where = "1=1";
if($status_filter != 'all') {
    $where .= " AND o.Status = '$status_filter'";
}
if(!empty($search)) {
    $where .= " AND (o.Order_ID LIKE '%$search%' OR CONCAT(r.F_Name, ' ', r.L_Name) LIKE '%$search%' OR r.Phone_No LIKE '%$search%')";
}
if(!empty($date_from)) {
    $where .= " AND DATE(o.Order_Date) >= '$date_from'";
}
if(!empty($date_to)) {
    $where .= " AND DATE(o.Order_Date) <= '$date_to'";
}

// Get total count
$count_query = "SELECT COUNT(*) as total FROM Orders o JOIN Resident r ON o.Resident_ID = r.Resident_ID WHERE $where";
$count_result = mysqli_query($conn, $count_query);
$total_rows = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_rows / $limit);

// Get orders
$orders_query = "SELECT o.*, CONCAT(r.F_Name, ' ', r.L_Name) as customer_name, r.Phone_No,
                 (SELECT COUNT(*) FROM OrderItems WHERE Order_ID = o.Order_ID) as item_count,
                 (SELECT Slot_Type FROM DeliverySlots WHERE Slot_ID = o.Slot_ID) as slot_type
                 FROM Orders o
                 JOIN Resident r ON o.Resident_ID = r.Resident_ID
                 WHERE $where
                 ORDER BY o.Order_Date DESC
                 LIMIT $offset, $limit";
$orders = mysqli_query($conn, $orders_query);

// Get staff for assignment dropdown
$staff_list = mysqli_query($conn, "SELECT Staff_ID, Staff_Name, Role FROM Staff ORDER BY Role, Staff_Name");

// Handle status update via AJAX
if(isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $status = sanitize($_POST['status']);
    mysqli_query($conn, "UPDATE Orders SET Status = '$status' WHERE Order_ID = $order_id");
    echo json_encode(['success' => true]);
    exit();
}

// Handle staff assignment
if(isset($_POST['assign_staff'])) {
    $order_id = intval($_POST['order_id']);
    $staff_id = intval($_POST['staff_id']);
    if($staff_id > 0) {
        mysqli_query($conn, "UPDATE Orders SET Staff_ID = $staff_id WHERE Order_ID = $order_id");
    } else {
        mysqli_query($conn, "UPDATE Orders SET Staff_ID = NULL WHERE Order_ID = $order_id");
    }
    echo json_encode(['success' => true]);
    exit();
}

$custom_title = "Order Management - Teddy Shine";
include_once '../../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-box"></i> Order Management</h2>
        <div>
            <span class="badge bg-primary">Total: <?php echo $total_rows; ?> orders</span>
        </div>
    </div>
    
    <!-- Filter Section -->
    <div class="filter-section mb-4">
        <form method="GET" action="" class="row g-3">
            <div class="col-md-3">
                <input type="text" name="search" class="form-control" placeholder="Order #, Customer, Phone..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select" onchange="this.form.submit()">
                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="Pending" <?php echo $status_filter == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="Processing" <?php echo $status_filter == 'Processing' ? 'selected' : ''; ?>>Processing</option>
                    <option value="In Progress" <?php echo $status_filter == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                    <option value="Completed" <?php echo $status_filter == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="Delivered" <?php echo $status_filter == 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                    <option value="Cancelled" <?php echo $status_filter == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
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
                <a href="orders.php" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
    
    <!-- Orders Table -->
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Items</th>
                            <th>Amount</th>
                            <th>Slot</th>
                            <th>Status</th>
                            <th>Staff</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(mysqli_num_rows($orders) > 0): ?>
                            <?php while($order = mysqli_fetch_assoc($orders)): ?>
                            <tr>
                                <td><strong>#<?php echo $order['Order_ID']; ?></strong></td>
                                <td>
                                    <?php echo htmlspecialchars($order['customer_name']); ?>
                                    <div class="small text-muted"><?php echo $order['Phone_No']; ?></div>
                                </td>
                                <td><?php echo date('d M Y', strtotime($order['Order_Date'])); ?></td>
                                <td><?php echo $order['item_count']; ?></td>
                                <td><strong>Rs. <?php echo number_format($order['Amount'], 2); ?></strong></td>
                                <td><?php echo $order['slot_type'] ?? '-'; ?></td>
                                <td>
                                    <select class="form-select form-select-sm status-update" data-order-id="<?php echo $order['Order_ID']; ?>" style="width: 130px;">
                                        <option value="Pending" <?php echo $order['Status'] == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="Processing" <?php echo $order['Status'] == 'Processing' ? 'selected' : ''; ?>>Processing</option>
                                        <option value="In Progress" <?php echo $order['Status'] == 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option value="Completed" <?php echo $order['Status'] == 'Completed' ? 'selected' : ''; ?>>Completed</option>
                                        <option value="Delivered" <?php echo $order['Status'] == 'Delivered' ? 'selected' : ''; ?>>Delivered</option>
                                        <option value="Cancelled" <?php echo $order['Status'] == 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm staff-assign" data-order-id="<?php echo $order['Order_ID']; ?>" style="width: 120px;">
                                        <option value="">Unassigned</option>
                                        <?php mysqli_data_seek($staff_list, 0); ?>
                                        <?php while($staff = mysqli_fetch_assoc($staff_list)): ?>
                                        <option value="<?php echo $staff['Staff_ID']; ?>" <?php echo $order['Staff_ID'] == $staff['Staff_ID'] ? 'selected' : ''; ?>>
                                            <?php echo $staff['Staff_Name']; ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </td>
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="order_detail.php?id=<?php echo $order['Order_ID']; ?>" class="btn btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-4">
                                    <i class="fas fa-inbox fa-2x text-muted mb-2 d-block"></i>
                                    No orders found
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

<script>
// Status update via AJAX
document.querySelectorAll('.status-update').forEach(select => {
    select.addEventListener('change', function() {
        const orderId = this.dataset.orderId;
        const status = this.value;
        fetch('orders.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `update_status=1&order_id=${orderId}&status=${status}`
        }).then(() => {
            showNotification('Status updated', 'success');
        });
    });
});

// Staff assignment via AJAX
document.querySelectorAll('.staff-assign').forEach(select => {
    select.addEventListener('change', function() {
        const orderId = this.dataset.orderId;
        const staffId = this.value;
        fetch('orders.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: `assign_staff=1&order_id=${orderId}&staff_id=${staffId}`
        }).then(() => {
            showNotification('Staff assigned', 'success');
        });
    });
});

function showNotification(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
    alertDiv.style.top = '20px';
    alertDiv.style.right = '20px';
    alertDiv.style.zIndex = '9999';
    alertDiv.innerHTML = `${message}<button type="button" class="btn-close" data-bs-dismiss="alert"></button>`;
    document.body.appendChild(alertDiv);
    setTimeout(() => alertDiv.remove(), 3000);
}
</script>

<?php include_once '../../includes/footer.php'; ?>