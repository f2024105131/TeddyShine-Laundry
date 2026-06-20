<?php
/* Display and manage all orders assigned to staff member */

require_once '../includes/auth_check.php';
require_once '../config/database.php';
require_once '../config/functions.php';

$staff_id = $_SESSION['staff_id'] ?? 1;
$staff_role = $_SESSION['role'] ?? 'staff';
$order_id = $_GET['id'] ?? 0;
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// If specific order ID is provided, show detailed view
if ($order_id > 0) {
    $order_query = "SELECT o.*, 
                    CONCAT(r.F_Name, ' ', r.L_Name) as customer_name,
                    r.Phone_No, r.Email, r.House_No, r.Street, r.Area, r.City,
                    ds.Slot_Type, ds.Start_Time, ds.End_Time,
                    (SELECT COUNT(*) FROM OrderItems WHERE Order_ID = o.Order_ID) as item_count
                    FROM Orders o
                    JOIN Resident r ON o.Resident_ID = r.Resident_ID
                    LEFT JOIN DeliverySlots ds ON o.Slot_ID = ds.Slot_ID
                    WHERE o.Order_ID = $order_id AND o.Staff_ID = $staff_id";
    $order_result = mysqli_query($conn, $order_query);

    if (mysqli_num_rows($order_result) == 0) {
        setFlashMessage("Order not found or not assigned to you.", "error");
        redirect(BASE_URL . "/staff/assigned_orders.php");
    }
    $order = mysqli_fetch_assoc($order_result);

    // Get order items
    $items_query = "SELECT oi.*, s.Service_Name 
                    FROM OrderItems oi 
                    JOIN Services s ON oi.Service_ID = s.Service_ID 
                    WHERE oi.Order_ID = $order_id";
    $items = mysqli_query($conn, $items_query);

    $custom_title = "Order #$order_id - Staff Panel";
    include_once '../includes/header.php';
?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-receipt"></i> Order Details #<?php echo $order_id; ?></h2>
            <a href="assigned_orders.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Orders
            </a>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- Order Info -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-info-circle"></i> Order Information</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="small text-muted">Customer</div>
                                    <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong>
                                </div>
                                <div class="mb-3">
                                    <div class="small text-muted">Contact</div>
                                    <div><?php echo $order['Phone_No']; ?></div>
                                    <div class="small"><?php echo $order['Email']; ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="small text-muted">Delivery Address</div>
                                    <div>
                                        <?php echo $order['House_No'] ? $order['House_No'] . ', ' : ''; ?>
                                        <?php echo $order['Street'] ? $order['Street'] . ', ' : ''; ?>
                                        <?php echo $order['Area'] ? $order['Area'] . ', ' : ''; ?>
                                        <?php echo $order['City']; ?>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <div class="small text-muted">Delivery Slot</div>
                                    <div><?php echo $order['Slot_Type'] ?? 'Not assigned'; ?></div>
                                    <div class="small">Date: <?php echo $order['Delivery_Date'] ?? 'TBD'; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-tshirt"></i> Order Items</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Service</th>
                                        <th>Quantity</th>
                                        <th>Price</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($item = mysqli_fetch_assoc($items)): ?>
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
            </div>

            <div class="col-lg-4">
                <!-- Actions Panel -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-cog"></i> Actions</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-primary" onclick="updateStatus('Processing')">
                                <i class="fas fa-play"></i> Mark as Processing
                            </button>
                            <button class="btn btn-outline-success" onclick="updateStatus('Completed')">
                                <i class="fas fa-check"></i> Mark as Completed
                            </button>
                            <hr>
                            <a href="update_tracking.php?order_id=<?php echo $order_id; ?>" class="btn btn-info">
                                <i class="fas fa-chart-line"></i> Update Tracking
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateStatus(status) {
            if (confirm(`Mark this order as ${status}?`)) {
                fetch('update_order_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `order_id=<?php echo $order_id; ?>&status=${status}`
                }).then(() => location.reload());
            }
        }
    </script>

    <?php include_once '../includes/footer.php'; ?>
    <?php exit(); ?>
<?php } ?>

<?php
// List view for all assigned orders
$where = "o.Staff_ID = $staff_id";
if ($status_filter != 'all') {
    $where .= " AND o.Status = '$status_filter'";
}
if (!empty($search)) {
    $where .= " AND (o.Order_ID LIKE '%$search%' OR CONCAT(r.F_Name, ' ', r.L_Name) LIKE '%$search%')";
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

$count_query = "SELECT COUNT(*) as total FROM Orders o WHERE $where";
$count_result = mysqli_query($conn, $count_query);
$total_rows = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_rows / $limit);

$orders_query = "SELECT o.*, 
                 CONCAT(r.F_Name, ' ', r.L_Name) as customer_name,
                 r.Phone_No,
                 (SELECT COUNT(*) FROM OrderItems WHERE Order_ID = o.Order_ID) as item_count
                 FROM Orders o
                 JOIN Resident r ON o.Resident_ID = r.Resident_ID
                 WHERE $where
                 ORDER BY FIELD(o.Status, 'Pending', 'Processing', 'In Progress', 'Completed'), o.Order_Date DESC
                 LIMIT $offset, $limit";
$orders = mysqli_query($conn, $orders_query);

$custom_title = "Assigned Orders - Staff Panel";
include_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-clipboard-list"></i> Assigned Orders</h2>
        <div>
            <span class="badge bg-primary">Total: <?php echo $total_rows; ?></span>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="filter-section mb-4">
        <div class="row">
            <div class="col-md-8 mb-3 mb-md-0">
                <div class="d-flex flex-wrap gap-2">
                    <a href="?status=all" class="filter-btn <?php echo $status_filter == 'all' ? 'active' : ''; ?>">All</a>
                    <a href="?status=Pending" class="filter-btn <?php echo $status_filter == 'Pending' ? 'active' : ''; ?>">Pending</a>
                    <a href="?status=Processing" class="filter-btn <?php echo $status_filter == 'Processing' ? 'active' : ''; ?>">Processing</a>
                    <a href="?status=In Progress" class="filter-btn <?php echo $status_filter == 'In Progress' ? 'active' : ''; ?>">In Progress</a>
                    <a href="?status=Completed" class="filter-btn <?php echo $status_filter == 'Completed' ? 'active' : ''; ?>">Completed</a>
                </div>
            </div>
            <div class="col-md-4">
                <form method="GET" action="">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search order # or customer..." value="<?php echo htmlspecialchars($search); ?>">
                        <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Orders List -->
    <?php if (mysqli_num_rows($orders) > 0): ?>
        <?php while ($order = mysqli_fetch_assoc($orders)): ?>
            <div class="order-card mb-3" onclick="window.location.href='assigned_orders.php?id=<?php echo $order['Order_ID']; ?>'">
                <div class="row align-items-center">
                    <div class="col-md-2">
                        <strong>#<?php echo $order['Order_ID']; ?></strong>
                        <div class="small text-muted"><?php echo date('d M Y', strtotime($order['Order_Date'])); ?></div>
                    </div>
                    <div class="col-md-3">
                        <div><?php echo htmlspecialchars($order['customer_name']); ?></div>
                        <div class="small text-muted"><i class="fas fa-phone"></i> <?php echo $order['Phone_No']; ?></div>
                    </div>
                    <div class="col-md-2">
                        <span class="badge bg-secondary"><?php echo $order['item_count']; ?> items</span>
                    </div>
                    <div class="col-md-2">
                        <?php echo getStatusBadge($order['Status']); ?>
                    </div>
                    <div class="col-md-2">
                        <strong>Rs. <?php echo number_format($order['Amount'], 2); ?></strong>
                    </div>
                    <div class="col-md-1 text-end">
                        <i class="fas fa-chevron-right text-muted"></i>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php if ($page > 1): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>">Previous</a></li>
                    <?php endif; ?>
                    <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>"><a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a></li>
                    <?php endfor; ?>
                    <?php if ($page < $total_pages): ?>
                        <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>">Next</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>

    <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                <h4>No Orders Found</h4>
                <p class="text-muted">You don't have any <?php echo $status_filter != 'all' ? $status_filter . ' ' : ''; ?>orders assigned.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    document.querySelectorAll('.order-card').forEach(card => {
        card.style.cursor = 'pointer';
    });
</script>

<?php include_once '../includes/footer.php'; ?>