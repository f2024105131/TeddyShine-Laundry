<?php
/**
 * resident-Track Order - Teddy Shine Laundry Management System
 * 
 * Real-time tracking of order status with visual timeline
 */

require_once '../includes/auth_check.php';
require_once '../config/database.php';
require_once '../config/functions.php';

$order_id = intval($_GET['id'] ?? 0);
$resident_id = $_SESSION['resident_id'];

// Verify order belongs to resident
$check_query = "SELECT o.*, ds.Slot_Type, ds.Start_Time, ds.End_Time 
                FROM Orders o 
                LEFT JOIN DeliverySlots ds ON o.Slot_ID = ds.Slot_ID
                WHERE o.Order_ID = $order_id AND o.Resident_ID = $resident_id";
$check_result = mysqli_query($conn, $check_query);

if(mysqli_num_rows($check_result) == 0) {
    setFlashMessage("Order not found.", "error");
    redirect(BASE_URL . "/resident/my_orders.php");
}

$order = mysqli_fetch_assoc($check_result);

// Get all items with their current stage
$items_query = "SELECT li.Item_ID, li.Cloth_Type, li.Color, li.Quantity,
                MAX(CASE WHEN t.Status = 'Completed' THEN t.Stage_ID ELSE 0 END) as current_stage
                FROM LaundryItem li
                JOIN OrderItems oi ON li.Item_ID IS NOT NULL
                JOIN Tracking t ON li.Item_ID = t.Item_ID
                WHERE oi.Order_ID = $order_id
                GROUP BY li.Item_ID";
$items = mysqli_query($conn, $items_query);

// Get all stages
$stages_query = "SELECT * FROM ProcessStage ORDER BY Stage_ID";
$stages_result = mysqli_query($conn, $stages_query);
$stages = [];
while($stage = mysqli_fetch_assoc($stages_result)) {
    $stages[] = $stage;
}
$total_stages = count($stages);

// Calculate overall progress
$overall_progress = 0;
$items_count = 0;
mysqli_data_seek($items, 0);
while($item = mysqli_fetch_assoc($items)) {
    $overall_progress += ($item['current_stage'] / $total_stages) * 100;
    $items_count++;
}
if($items_count > 0) {
    $overall_progress = round($overall_progress / $items_count);
}

$custom_title = "Track Order #$order_id - Teddy Shine";
include_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="tracking-container">
        <h2 class="text-center mb-4"><i class="fas fa-map-marker-alt"></i> Track Order #<?php echo $order_id; ?></h2>
        
        <!-- Order Info Card -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-md-4 mb-2 mb-md-0">
                        <i class="fas fa-calendar text-muted"></i>
                        <div class="small text-muted">Order Date</div>
                        <strong><?php echo date('d M Y', strtotime($order['Order_Date'])); ?></strong>
                    </div>
                    <div class="col-md-4 mb-2 mb-md-0">
                        <i class="fas fa-truck text-muted"></i>
                        <div class="small text-muted">Delivery Slot</div>
                        <strong><?php echo $order['Slot_Type'] ?? 'Not assigned'; ?></strong>
                    </div>
                    <div class="col-md-4 mb-2 mb-md-0">
                        <i class="fas fa-clock text-muted"></i>
                        <div class="small text-muted">Est. Delivery</div>
                        <strong><?php echo $order['Delivery_Date'] ? date('d M Y', strtotime($order['Delivery_Date'])) : 'TBD'; ?></strong>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Overall Progress Ring -->
        <div class="text-center mb-4">
            <div class="progress-ring">
                <svg width="150" height="150" viewBox="0 0 150 150">
                    <defs>
                        <linearGradient id="gradient" x1="0%" y1="0%" x2="100%" y2="100%">
                            <stop offset="0%" style="stop-color:#667eea"/>
                            <stop offset="100%" style="stop-color:#764ba2"/>
                        </linearGradient>
                    </defs>
                    <circle class="progress-ring-circle-bg" cx="75" cy="75" r="65" fill="none" stroke="#e5e7eb" stroke-width="8"/>
                    <circle class="progress-ring-circle-progress" cx="75" cy="75" r="65" fill="none" stroke="url(#gradient)" stroke-width="8" 
                            stroke-dasharray="408.4" stroke-dashoffset="<?php echo 408.4 - (408.4 * $overall_progress / 100); ?>"/>
                </svg>
                <div class="progress-text">
                    <div class="progress-percent"><?php echo $overall_progress; ?>%</div>
                    <div class="small">Complete</div>
                </div>
            </div>
            <div class="mt-2">
                <span class="badge bg-primary">Overall Progress</span>
            </div>
        </div>
        
        <!-- Stages Timeline -->
        <h5 class="mb-3">Order Status Timeline</h5>
        <?php foreach($stages as $index => $stage): 
            $stage_status = 'pending';
            $stage_icon = 'far fa-circle';
            
            if($index < $overall_progress / (100/$total_stages)) {
                $stage_status = 'completed';
                $stage_icon = 'fas fa-check-circle';
            } elseif($index == floor($overall_progress / (100/$total_stages))) {
                $stage_status = 'active';
                $stage_icon = 'fas fa-spinner fa-spin';
            }
        ?>
        <div class="stage-card <?php echo $stage_status; ?>">
            <div class="d-flex align-items-center">
                <div class="stage-icon">
                    <?php
                    $icons = [
                        'Washing' => 'fa-tshirt',
                        'Drying' => 'fa-wind',
                        'Ironing' => 'fa-iron',
                        'Packing' => 'fa-box',
                        'Delivery' => 'fa-truck'
                    ];
                    $icon = $icons[$stage['Stages_name']] ?? 'fa-circle';
                    ?>
                    <i class="<?php echo $stage_icon . ' ' . $icon; ?> fa-2x"></i>
                </div>
                <div class="flex-grow-1">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><?php echo $stage['Stages_name']; ?></h6>
                        <?php if($stage_status == 'completed'): ?>
                            <span class="badge bg-success">Completed</span>
                        <?php elseif($stage_status == 'active'): ?>
                            <span class="badge bg-primary">In Progress</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Pending</span>
                        <?php endif; ?>
                    </div>
                    <div class="estimated-time mt-1">
                        <?php
                        $estimate_times = [
                            'Washing' => '30-45 mins',
                            'Drying' => '30-60 mins',
                            'Ironing' => '15-30 mins',
                            'Packing' => '10-15 mins',
                            'Delivery' => '1-3 hours'
                        ];
                        echo $estimate_times[$stage['Stages_name']] ?? 'Estimate pending';
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
        <!-- Items Status -->
        <h5 class="mb-3 mt-4">Items in this Order</h5>
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th>Color</th>
                                <th>Quantity</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            mysqli_data_seek($items, 0);
                            if(mysqli_num_rows($items) > 0):
                                while($item = mysqli_fetch_assoc($items)): 
                                    $item_progress = ($item['current_stage'] / $total_stages) * 100;
                            ?>
                            <tr>
                                <td><i class="fas fa-tshirt text-muted me-2"></i><?php echo htmlspecialchars($item['Cloth_Type']); ?></td>
                                <td><?php echo htmlspecialchars($item['Color']); ?></td>
                                <td><?php echo $item['Quantity']; ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1" style="height: 6px;">
                                            <div class="progress-bar bg-success" style="width: <?php echo $item_progress; ?>%"></div>
                                        </div>
                                        <span class="ms-2 small"><?php echo round($item_progress); ?>%</span>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4">No items found for this order.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Estimated Delivery -->
        <div class="alert alert-info mt-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-info-circle fa-2x me-3"></i>
                <div>
                    <strong>Estimated Delivery Time</strong><br>
                    Based on current progress, your order is expected to be delivered 
                    <?php 
                    $remaining_stages = $total_stages - floor($overall_progress / (100/$total_stages));
                    if($remaining_stages <= 2) {
                        echo "today";
                    } elseif($remaining_stages <= 4) {
                        echo "within 24 hours";
                    } else {
                        echo "in 1-2 days";
                    }
                    ?>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="text-center mt-4">
            <a href="order_details.php?id=<?php echo $order_id; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Order
            </a>
            <button onclick="location.reload()" class="btn btn-primary">
                <i class="fas fa-sync-alt"></i> Refresh Status
            </button>
        </div>
    </div>
</div>

<!-- Refresh Button Floating -->
<button class="refresh-btn" onclick="location.reload()" title="Refresh Status">
    <i class="fas fa-sync-alt"></i>
</button>

<script>
// Auto refresh every 30 seconds
setTimeout(function() {
    location.reload();
}, 30000);
</script>

<?php include_once '../includes/footer.php'; ?>