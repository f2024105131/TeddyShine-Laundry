<?php

/**
 * Delivery List - Delivery boy's daily schedule with status updates
 */

require_once '../includes/auth_check.php';
require_once '../config/database.php';
require_once '../config/functions.php';

$staff_id = $_SESSION['staff_id'] ?? 1;
$date = $_GET['date'] ?? date('Y-m-d');
$view = $_GET['view'] ?? 'today';

// Build query for deliveries
if ($view == 'today') {
    $date_condition = "Delivery_Date = CURDATE()";
} elseif ($view == 'tomorrow') {
    $date_condition = "Delivery_Date = CURDATE() + INTERVAL 1 DAY";
} elseif ($view == 'week') {
    $date_condition = "Delivery_Date BETWEEN CURDATE() AND CURDATE() + INTERVAL 7 DAY";
} else {
    $date_condition = "Delivery_Date = '$date'";
}

$deliveries_query = "SELECT o.Order_ID, o.Order_Date, o.Delivery_Date, o.Status,
                     CONCAT(r.F_Name, ' ', r.L_Name) as customer_name,
                     r.Phone_No, r.Email, r.House_No, r.Street, r.Area, r.City,
                     ds.Slot_Type, ds.Start_Time, ds.End_Time,
                     (SELECT COUNT(*) FROM OrderItems WHERE Order_ID = o.Order_ID) as item_count
                     FROM Orders o
                     JOIN Resident r ON o.Resident_ID = r.Resident_ID
                     LEFT JOIN DeliverySlots ds ON o.Slot_ID = ds.Slot_ID
                     WHERE o.Staff_ID = $staff_id AND $date_condition
                     ORDER BY ds.Start_Time, o.Delivery_Date";
$deliveries = mysqli_query($conn, $deliveries_query);

// Get statistics
$total_deliveries = mysqli_num_rows($deliveries);
$completed_deliveries = 0;
$deliveries_array = [];

while ($del = mysqli_fetch_assoc($deliveries)) {
    if ($del['Status'] == 'Delivered') {
        $completed_deliveries++;
    }
    $deliveries_array[] = $del;
}
mysqli_data_seek($deliveries, 0);

$custom_title = "Delivery Schedule - Teddy Shine";
include_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-truck"></i> Delivery Schedule</h2>
        <button onclick="window.print()" class="btn btn-secondary no-print">
            <i class="fas fa-print"></i> Print Route
        </button>
    </div>

    <!-- Statistics Banner -->
    <div class="delivery-stats mb-4">
        <div class="row text-center">
            <div class="col-md-4 mb-3 mb-md-0">
                <i class="fas fa-calendar-day fa-2x mb-2"></i>
                <h3 class="mb-0"><?php echo $total_deliveries; ?></h3>
                <p class="mb-0">Total Deliveries</p>
            </div>
            <div class="col-md-4 mb-3 mb-md-0">
                <i class="fas fa-check-circle fa-2x mb-2"></i>
                <h3 class="mb-0"><?php echo $completed_deliveries; ?></h3>
                <p class="mb-0">Completed</p>
            </div>
            <div class="col-md-4 mb-3 mb-md-0">
                <i class="fas fa-hourglass-half fa-2x mb-2"></i>
                <h3 class="mb-0"><?php echo $total_deliveries - $completed_deliveries; ?></h3>
                <p class="mb-0">Pending</p>
            </div>
        </div>
    </div>

    <!-- Date Navigation -->
    <div class="card mb-4 no-print">
        <div class="card-body">
            <div class="btn-group w-100 mb-3">
                <a href="?view=today" class="btn btn-outline-primary <?php echo $view == 'today' ? 'active' : ''; ?>">
                    <i class="fas fa-sun"></i> Today
                </a>
                <a href="?view=tomorrow" class="btn btn-outline-primary <?php echo $view == 'tomorrow' ? 'active' : ''; ?>">
                    <i class="fas fa-cloud-sun"></i> Tomorrow
                </a>
                <a href="?view=week" class="btn btn-outline-primary <?php echo $view == 'week' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-week"></i> This Week
                </a>
            </div>
            <div class="text-center">
                <input type="date" class="form-control d-inline-block w-auto" value="<?php echo $date; ?>"
                    onchange="window.location.href='delivery_list.php?date='+this.value">
            </div>
        </div>
    </div>

    <?php if ($total_deliveries > 0): ?>
        <div class="delivery-timeline">
            <?php
            $stop_number = 1;
            foreach ($deliveries_array as $delivery):
                $is_delivered = $delivery['Status'] == 'Delivered';
            ?>
                <div class="delivery-card <?php echo $is_delivered ? 'delivered' : ''; ?>">
                    <div class="row align-items-center">
                        <div class="col-md-1">
                            <div class="stop-number"><?php echo $stop_number; ?></div>
                        </div>
                        <div class="col-md-2">
                            <strong>Order #<?php echo $delivery['Order_ID']; ?></strong>
                            <div class="small text-muted">
                                <i class="fas fa-clock"></i> <?php echo $delivery['Slot_Type'] ?? 'Standard'; ?>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div><i class="fas fa-user"></i> <strong><?php echo htmlspecialchars($delivery['customer_name']); ?></strong></div>
                            <div class="small text-muted"><i class="fas fa-phone"></i> <?php echo $delivery['Phone_No']; ?></div>
                        </div>
                        <div class="col-md-4">
                            <div class="small text-muted">
                                <i class="fas fa-map-marker-alt"></i>
                                <?php
                                echo $delivery['House_No'] . ', ';
                                echo $delivery['Street'] ? $delivery['Street'] . ', ' : '';
                                echo $delivery['Area'] ? $delivery['Area'] . ', ' : '';
                                echo $delivery['City'];
                                ?>
                            </div>
                            <div class="small text-muted">
                                <i class="fas fa-box"></i> <?php echo $delivery['item_count']; ?> items
                            </div>
                        </div>
                        <div class="col-md-2 text-end">
                            <?php if ($is_delivered): ?>
                                <span class="badge bg-success">
                                    <i class="fas fa-check-circle"></i> Delivered
                                </span>
                            <?php else: ?>
                                <button class="btn btn-sm btn-success" onclick="markDelivered(<?php echo $delivery['Order_ID']; ?>)">
                                    <i class="fas fa-check"></i> Mark Delivered
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php
                $stop_number++;
            endforeach;
            ?>
        </div>

        <!-- Route Summary -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-chart-bar"></i> Route Summary</h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <strong>Total Stops:</strong> <?php echo $total_deliveries; ?><br>
                        <strong>Estimated Route Time:</strong> <?php echo ($total_deliveries * 15); ?> - <?php echo ($total_deliveries * 25); ?> minutes
                    </div>
                    <div class="col-md-6">
                        <strong>Tips:</strong>
                        <ul class="mb-0">
                            <li>Call customers 15 minutes before arrival</li>
                            <li>Collect cash payments if applicable</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    <?php else: ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-truck fa-4x text-muted mb-3"></i>
                <h4>No Deliveries Scheduled</h4>
                <p class="text-muted">You have no deliveries for <?php echo $view == 'today' ? 'today' : ($view == 'tomorrow' ? 'tomorrow' : 'this week'); ?>.</p>
                <a href="dashboard.php" class="btn btn-primary">Back to Dashboard</a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    function markDelivered(orderId) {
        if (confirm('Mark this order as delivered?')) {
            fetch('save_delivery.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `order_id=${orderId}&action=delivered`
            }).then(() => location.reload());
        }
    }
</script>

<?php include_once '../includes/footer.php'; ?>