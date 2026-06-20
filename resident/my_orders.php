<?php
/**
 * resident-My Orders - Teddy Shine Laundry Management System
 * 
 * List all orders with filters, search, and pagination
 */

require_once '../includes/auth_check.php';
require_once '../config/database.php';
require_once '../config/functions.php';

$resident_id = $_SESSION['resident_id'];

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filters
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : 'all';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$date_from = isset($_GET['date_from']) ? sanitize($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? sanitize($_GET['date_to']) : '';

// Build where clause
$where = "Resident_ID = $resident_id";
if ($status_filter != 'all') {
    $where .= " AND Status = '$status_filter'";
}
if (!empty($search)) {
    $where .= " AND (Order_ID LIKE '%$search%' OR Amount LIKE '%$search%')";
}
if (!empty($date_from)) {
    $where .= " AND Order_Date >= '$date_from'";
}
if (!empty($date_to)) {
    $where .= " AND Order_Date <= '$date_to'";
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM Orders WHERE $where";
$count_result = mysqli_query($conn, $count_query);
$total_rows = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_rows / $limit);

// Get orders
$orders_query = "SELECT o.*, 
                (SELECT COUNT(*) FROM OrderItems WHERE Order_ID = o.Order_ID) as item_count,
                (SELECT Slot_Type FROM DeliverySlots WHERE Slot_ID = o.Slot_ID) as slot_type,
                (SELECT Invoice_Status FROM Invoice WHERE Order_ID = o.Order_ID) as payment_status
                FROM Orders o 
                WHERE $where 
                ORDER BY o.Order_Date DESC 
                LIMIT $offset, $limit";
$orders = mysqli_query($conn, $orders_query);

$custom_title = "My Orders - Teddy Shine";
include_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-list"></i> My Orders</h2>
        <a href="place_order.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> New Order
        </a>
    </div>
    
    <!-- Filter Section -->
    <div class="filter-section mb-4">
        <div class="row">
            <div class="col-12 mb-3">
                <div class="d-flex flex-wrap gap-2">
                    <a href="?status=all" class="filter-btn <?php echo $status_filter == 'all' ? 'active' : ''; ?>">All Orders</a>
                    <a href="?status=Pending" class="filter-btn <?php echo $status_filter == 'Pending' ? 'active' : ''; ?>">Pending</a>
                    <a href="?status=Processing" class="filter-btn <?php echo $status_filter == 'Processing' ? 'active' : ''; ?>">Processing</a>
                    <a href="?status=In Progress" class="filter-btn <?php echo $status_filter == 'In Progress' ? 'active' : ''; ?>">In Progress</a>
                    <a href="?status=Completed" class="filter-btn <?php echo $status_filter == 'Completed' ? 'active' : ''; ?>">Completed</a>
                    <a href="?status=Delivered" class="filter-btn <?php echo $status_filter == 'Delivered' ? 'active' : ''; ?>">Delivered</a>
                    <a href="?status=Cancelled" class="filter-btn <?php echo $status_filter == 'Cancelled' ? 'active' : ''; ?>">Cancelled</a>
                </div>
            </div>
            <div class="col-12">
                <form method="GET" action="" class="row g-2">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="Search by Order ID..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>" placeholder="From Date">
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>" placeholder="To Date">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                    <input type="hidden" name="status" value="<?php echo $status_filter; ?>">
                </form>
            </div>
        </div>
    </div>
    
    <!-- Orders List -->
    <?php if(mysqli_num_rows($orders) > 0): ?>
        <?php while($order = mysqli_fetch_assoc($orders)): ?>
        <div class="order-card mb-3" data-order-id="<?php echo $order['Order_ID']; ?>">
            <div class="order-header" onclick="toggleOrder(this.parentElement)">
                <div class="row align-items-center">
                    <div class="col-lg-2">
                        <strong>Order #<?php echo $order['Order_ID']; ?></strong>
                        <div class="small text-muted"><?php echo date('d M Y', strtotime($order['Order_Date'])); ?></div>
                    </div>
                    <div class="col-lg-2">
                        <i class="fas fa-tshirt"></i> <?php echo $order['item_count']; ?> items
                    </div>
                    <div class="col-lg-2">
                        <strong>Rs. <?php echo number_format($order['Amount'], 2); ?></strong>
                    </div>
                    <div class="col-lg-2">
                        <?php echo getStatusBadge($order['Status']); ?>
                    </div>
                    <div class="col-lg-2">
                        <?php 
                        $payment_status = $order['payment_status'] ?? 'Unpaid';
                        $payment_badge = $payment_status == 'Paid' ? 'success' : ($payment_status == 'Partial' ? 'warning' : 'danger');
                        echo "<span class='badge bg-$payment_badge'>$payment_status</span>";
                        ?>
                    </div>
                    <div class="col-lg-2 text-end">
                        <i class="fas fa-chevron-down toggle-icon"></i>
                    </div>
                </div>
            </div>
            <div class="order-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Order Details</h6>
                        <p class="small mb-1"><strong>Delivery Slot:</strong> <?php echo $order['slot_type'] ?? 'Not assigned'; ?></p>
                        <p class="small mb-1"><strong>Delivery Date:</strong> <?php echo $order['Delivery_Date'] ? date('d M Y', strtotime($order['Delivery_Date'])) : 'Not scheduled'; ?></p>
                        <p class="small"><strong>Order Date:</strong> <?php echo date('d M Y h:i A', strtotime($order['Order_Date'])); ?></p>
                    </div>
                    <div class="col-md-6 text-end">
                        <a href="order_details.php?id=<?php echo $order['Order_ID']; ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-eye"></i> View Details
                        </a>
                        <a href="track_order.php?id=<?php echo $order['Order_ID']; ?>" class="btn btn-info btn-sm">
                            <i class="fas fa-map-marker-alt"></i> Track
                        </a>
                        <?php if($payment_status != 'Paid'): ?>
                        <a href="invoice.php?id=<?php echo $order['Order_ID']; ?>" class="btn btn-warning btn-sm">
                            <i class="fas fa-credit-card"></i> Pay Now
                        </a>
                        <?php endif; ?>
                        <?php if($order['Status'] == 'Pending'): ?>
                        <button class="btn btn-danger btn-sm" onclick="cancelOrder(<?php echo $order['Order_ID']; ?>)">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Status Timeline -->
                <div class="status-timeline mt-3">
                    <?php 
                    $stages = ['Pending', 'Processing', 'Washing', 'Drying', 'Ironing', 'Packing', 'Delivered'];
                    $current_stage = $order['Status'];
                    $current_index = array_search($current_stage, $stages);
                    if($current_index === false) $current_index = 0;
                    ?>
                    <?php foreach($stages as $index => $stage): ?>
                    <div class="timeline-step <?php echo $index < $current_index ? 'completed' : ($index == $current_index ? 'active' : ''); ?>">
                        <div class="timeline-dot">
                            <?php if($index < $current_index): ?>
                                <i class="fas fa-check"></i>
                            <?php elseif($index == $current_index): ?>
                                <i class="fas fa-spinner fa-spin"></i>
                            <?php else: ?>
                                <?php echo $index + 1; ?>
                            <?php endif; ?>
                        </div>
                        <div class="timeline-label"><?php echo $stage; ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
        
        <!-- Pagination -->
        <?php if($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php if($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page-1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">Previous</a>
                </li>
                <?php endif; ?>
                
                <?php 
                $start_page = max(1, $page - 2);
                $end_page = min($total_pages, $page + 2);
                for($i = $start_page; $i <= $end_page; $i++): 
                ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
                
                <?php if($page < $total_pages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page+1; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">Next</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
        <?php endif; ?>
        
    <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                <h4>No Orders Found</h4>
                <p class="text-muted">You haven't placed any orders yet.</p>
                <a href="place_order.php" class="btn btn-primary mt-2">Place Your First Order</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function toggleOrder(card) {
    card.classList.toggle('expanded');
    const icon = card.querySelector('.toggle-icon');
    if (card.classList.contains('expanded')) {
        icon.classList.remove('fa-chevron-down');
        icon.classList.add('fa-chevron-up');
    } else {
        icon.classList.remove('fa-chevron-up');
        icon.classList.add('fa-chevron-down');
    }
}

function cancelOrder(orderId) {
    if(confirm('Are you sure you want to cancel this order? This action cannot be undone.')) {
        window.location.href = 'cancel_order.php?id=' + orderId;
    }
}
</script>

<?php include_once '../includes/footer.php'; ?>