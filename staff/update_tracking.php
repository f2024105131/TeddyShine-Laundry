<?php
/*
 * Staff can update tracking status for laundry items through each stage
 */

require_once '../includes/auth_check.php';
require_once '../config/database.php';
require_once '../config/functions.php';

$staff_id = $_SESSION['staff_id'] ?? 1;
$order_id = $_GET['order_id'] ?? 0;

// Get all orders assigned to this staff for dropdown
$orders_query = "SELECT o.Order_ID, CONCAT(r.F_Name, ' ', r.L_Name) as customer_name 
                 FROM Orders o
                 JOIN Resident r ON o.Resident_ID = r.Resident_ID
                 WHERE o.Staff_ID = $staff_id
                 ORDER BY o.Order_Date DESC";
$orders = mysqli_query($conn, $orders_query);

// If order selected, get its items
$items = [];
$stages = [];

if ($order_id > 0) {
    // Get order details
    $order_detail_query = "SELECT o.*, CONCAT(r.F_Name, ' ', r.L_Name) as customer_name 
                           FROM Orders o
                           JOIN Resident r ON o.Resident_ID = r.Resident_ID
                           WHERE o.Order_ID = $order_id";
    $order_detail = mysqli_fetch_assoc(mysqli_query($conn, $order_detail_query));

    // Get all process stages
    $stages_query = "SELECT * FROM ProcessStage ORDER BY Stage_ID";
    $stages_result = mysqli_query($conn, $stages_query);
    while ($stage = mysqli_fetch_assoc($stages_result)) {
        $stages[] = $stage;
    }

    // Get items with their tracking status
    $items_query = "SELECT li.Item_ID, li.Cloth_Type, li.Color, li.Quantity
                    FROM LaundryItem li
                    LIMIT 20";
    $items_result = mysqli_query($conn, $items_query);
    while ($item = mysqli_fetch_assoc($items_result)) {
        // Get tracking for this item
        $track = [];
        foreach ($stages as $stage) {
            $track_query = "SELECT * FROM Tracking 
                           WHERE Item_ID = {$item['Item_ID']} AND Stage_ID = {$stage['Stage_ID']}";
            $track_result = mysqli_query($conn, $track_query);
            $track[$stage['Stage_ID']] = mysqli_fetch_assoc($track_result);
        }
        $item['tracking'] = $track;
        $items[] = $item;
    }
}

$custom_title = "Update Tracking - Staff Panel";
include_once '../includes/header.php';
?>

<div class="container mt-4">
    <h2 class="mb-4"><i class="fas fa-chart-line"></i> Update Tracking Status</h2>

    <!-- Order Selection -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <label class="form-label">Select Order</label>
                    <select id="orderSelect" class="form-select" onchange="window.location.href='update_tracking.php?order_id='+this.value">
                        <option value="">-- Select an order --</option>
                        <?php while ($order = mysqli_fetch_assoc($orders)): ?>
                            <option value="<?php echo $order['Order_ID']; ?>" <?php echo $order_id == $order['Order_ID'] ? 'selected' : ''; ?>>
                                #<?php echo $order['Order_ID']; ?> - <?php echo $order['customer_name']; ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <?php if ($order_id > 0 && isset($order_detail)): ?>
                    <div class="col-md-6">
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle"></i>
                            Order #<?php echo $order_id; ?> - Customer: <?php echo $order_detail['customer_name']; ?>
                            <br><small>Delivery: <?php echo $order_detail['Delivery_Date'] ?? 'Not scheduled'; ?></small>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($order_id > 0 && !empty($items)): ?>
        <form method="POST" action="save_tracking.php">
            <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">

            <?php foreach ($items as $index => $item): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-tshirt"></i>
                            <?php echo htmlspecialchars($item['Cloth_Type']); ?>
                            (<?php echo $item['Color']; ?>) - Qty: <?php echo $item['Quantity']; ?>
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($stages as $stage):
                                $track = $item['tracking'][$stage['Stage_ID']] ?? null;
                                $is_completed = $track && $track['Status'] == 'Completed';
                                $is_active = $track && $track['Status'] == 'In Progress';
                            ?>
                                <div class="col-md-2 text-center mb-3">
                                    <div class="stage-status">
                                        <div class="stage-circle <?php echo $is_completed ? 'completed' : ($is_active ? 'active' : 'pending'); ?>">
                                            <?php if ($is_completed): ?>
                                                <i class="fas fa-check"></i>
                                            <?php elseif ($is_active): ?>
                                                <i class="fas fa-spinner fa-spin"></i>
                                            <?php else: ?>
                                                <?php echo $stage['Stage_ID']; ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="stage-name"><?php echo $stage['Stages_name']; ?></div>
                                        <?php if (!$is_completed): ?>
                                            <div class="mt-2">
                                                <input type="checkbox" name="complete_stage[<?php echo $item['Item_ID']; ?>][<?php echo $stage['Stage_ID']; ?>]" value="1" class="form-check-input">
                                                <label class="form-check-label small">Mark Complete</label>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge bg-success mt-2">Completed</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="text-center mt-4 mb-5">
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="fas fa-save"></i> Save All Updates
                </button>
                <a href="dashboard.php" class="btn btn-secondary btn-lg">Cancel</a>
            </div>
        </form>

    <?php elseif ($order_id > 0): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                <h4>No Items Found</h4>
                <p class="text-muted">No laundry items found for this order.</p>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
    .stage-circle {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 8px;
        font-weight: bold;
    }

    .stage-circle.completed {
        background: #10b981;
        color: white;
    }

    .stage-circle.active {
        background: #667eea;
        color: white;
        animation: pulse 2s infinite;
    }

    .stage-circle.pending {
        background: #e5e7eb;
        color: #6b7280;
    }

    .stage-name {
        font-size: 12px;
    }

    @keyframes pulse {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.05);
        }
    }
</style>

<?php include_once '../includes/footer.php'; ?>