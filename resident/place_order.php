<?php
/**
 * resident-Place Order - Teddy Shine Laundry Management System
 * 
 * Multi-step order placement with dynamic item addition
 */

require_once '../includes/auth_check.php';
require_once '../config/database.php';
require_once '../config/functions.php';

$resident_id = $_SESSION['resident_id'];

// Get all active services
$services_query = "SELECT * FROM Services WHERE Status = 'Active' ORDER BY Service_Price";
$services = mysqli_query($conn, $services_query);
$services_array = [];
while($s = mysqli_fetch_assoc($services)) {
    $services_array[] = $s;
}
mysqli_data_seek($services, 0);

// Get delivery slots
$slots_query = "SELECT * FROM DeliverySlots ORDER BY Start_Time";
$slots = mysqli_query($conn, $slots_query);

// Calculate delivery dates
$min_date = date('Y-m-d', strtotime('+1 day'));
$max_date = date('Y-m-d', strtotime('+7 days'));

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $slot_id = intval($_POST['slot_id']);
    $delivery_date = sanitize($_POST['delivery_date']);
    $special_instructions = sanitize($_POST['special_instructions'] ?? '');
    $items = $_POST['items'] ?? [];
    
    // Validation
    if (empty($items)) {
        $error = "Please add at least one item to your order.";
    } elseif (empty($slot_id)) {
        $error = "Please select a delivery slot.";
    } else {
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Insert order
            $stmt = mysqli_prepare($conn, "INSERT INTO Orders (Resident_ID, Slot_ID, Order_Date, Delivery_Date, Status, Special_Instructions) VALUES (?, ?, CURDATE(), ?, 'Pending', ?)");
            mysqli_stmt_bind_param($stmt, "iiss", $resident_id, $slot_id, $delivery_date, $special_instructions);
            mysqli_stmt_execute($stmt);
            $order_id = mysqli_insert_id($conn);
            
            $total_amount = 0;
            $items_data = [];
            
            // Process each item
            foreach ($items as $item) {
                $cloth_type = sanitize($item['cloth_type']);
                $color = sanitize($item['color']);
                $quantity = intval($item['quantity']);
                $service_id = intval($item['service_id']);
                
                // Get service price
                $price_query = "SELECT Service_Price FROM Services WHERE Service_ID = $service_id";
                $price_result = mysqli_query($conn, $price_query);
                $service_price = mysqli_fetch_assoc($price_result)['Service_Price'];
                $item_total = $service_price * $quantity;
                $total_amount += $item_total;
                
                // Insert laundry item
                $stmt = mysqli_prepare($conn, "INSERT INTO LaundryItem (Cloth_Type, Color, Quantity) VALUES (?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "ssi", $cloth_type, $color, $quantity);
                mysqli_stmt_execute($stmt);
                $item_id = mysqli_insert_id($conn);
                
                // Insert order item
                $stmt = mysqli_prepare($conn, "INSERT INTO OrderItems (Order_ID, Service_ID, Quantity, Price) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "iiid", $order_id, $service_id, $quantity, $item_total);
                mysqli_stmt_execute($stmt);
                
                $items_data[] = ['item_id' => $item_id, 'quantity' => $quantity];
            }
            
            // Update order amount
            $stmt = mysqli_prepare($conn, "UPDATE Orders SET Amount = ? WHERE Order_ID = ?");
            mysqli_stmt_bind_param($stmt, "di", $total_amount, $order_id);
            mysqli_stmt_execute($stmt);
            
            // Create invoice
            $stmt = mysqli_prepare($conn, "INSERT INTO Invoice (Order_ID, Total_Amount, Discount, Invoice_Date, Invoice_Status) VALUES (?, ?, 0, CURDATE(), 'Unpaid')");
            mysqli_stmt_bind_param($stmt, "id", $order_id, $total_amount);
            mysqli_stmt_execute($stmt);
            
            // Create tracking entries for each stage
            $stages_result = mysqli_query($conn, "SELECT Stage_ID FROM ProcessStage ORDER BY Stage_ID");
            $stages = [];
            while($stage = mysqli_fetch_assoc($stages_result)) {
                $stages[] = $stage['Stage_ID'];
            }
            
            foreach ($items_data as $item_data) {
                foreach ($stages as $stage_id) {
                    $stmt = mysqli_prepare($conn, "INSERT INTO Tracking (Item_ID, Stage_ID, Status) VALUES (?, ?, 'Pending')");
                    mysqli_stmt_bind_param($stmt, "ii", $item_data['item_id'], $stage_id);
                    mysqli_stmt_execute($stmt);
                }
            }
            
            mysqli_commit($conn);
            
            // Log activity
            logActivity('order_placed', "Order #$order_id placed with " . count($items) . " items");
            
            setFlashMessage("Order placed successfully! Order #$order_id", "success");
            redirect(BASE_URL . "/resident/order_details.php?id=$order_id");
            
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Failed to place order: " . $e->getMessage();
            error_log("Order placement error: " . $e->getMessage());
        }
    }
}

$custom_title = "Place New Order - Teddy Shine";
include_once '../includes/header.php';
?>

<div class="container mt-4">
    <h2 class="mb-4"><i class="fas fa-plus-circle"></i> Place New Order</h2>
    
    <?php if($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <!-- Step Indicator -->
    <div class="step-indicator mb-4">
        <div class="step active" id="step1">
            <div class="step-circle">1</div>
            <div class="step-label">Add Items</div>
        </div>
        <div class="step" id="step2">
            <div class="step-circle">2</div>
            <div class="step-label">Delivery Details</div>
        </div>
        <div class="step" id="step3">
            <div class="step-circle">3</div>
            <div class="step-label">Review & Confirm</div>
        </div>
    </div>
    
    <form method="POST" action="" id="orderForm">
        <!-- Step 1: Items -->
        <div id="step1Content" class="step-content">
            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-tshirt"></i> Laundry Items</h5>
                            <button type="button" class="btn btn-sm btn-primary" onclick="addItem()">
                                <i class="fas fa-plus"></i> Add Item
                            </button>
                        </div>
                        <div class="card-body">
                            <div id="itemsContainer">
                                <div class="item-card" data-index="0">
                                    <div class="row align-items-end">
                                        <div class="col-md-3 mb-2">
                                            <label class="form-label small">Cloth Type *</label>
                                            <input type="text" name="items[0][cloth_type]" class="form-control" placeholder="e.g., Cotton Shirt" required>
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <label class="form-label small">Color</label>
                                            <input type="text" name="items[0][color]" class="form-control" placeholder="Color">
                                        </div>
                                        <div class="col-md-2 mb-2">
                                            <label class="form-label small">Quantity *</label>
                                            <input type="number" name="items[0][quantity]" class="form-control quantity" min="1" value="1" required>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label class="form-label small">Service *</label>
                                            <select name="items[0][service_id]" class="form-select service-select" required>
                                                <option value="">Select Service</option>
                                                <?php foreach($services_array as $service): ?>
                                                <option value="<?php echo $service['Service_ID']; ?>" data-price="<?php echo $service['Service_Price']; ?>">
                                                    <?php echo htmlspecialchars($service['Service_Name']); ?> - Rs. <?php echo number_format($service['Service_Price'], 2); ?>
                                                </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-1 mb-2 text-end">
                                            <i class="fas fa-trash-alt remove-item text-danger" style="cursor: pointer; font-size: 20px;"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <div class="order-summary">
                        <h5 class="mb-3">Order Summary</h5>
                        <div id="summaryItems">
                            <p class="text-muted">No items added yet</p>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal:</span>
                            <strong id="subtotal">Rs. 0.00</strong>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="fw-bold">Total:</span>
                            <span class="fw-bold text-primary fs-5" id="total">Rs. 0.00</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-end mt-3">
                <button type="button" class="btn btn-primary" onclick="nextStep(2)">
                    Next: Delivery Details <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
        
        <!-- Step 2: Delivery Details -->
        <div id="step2Content" class="step-content" style="display: none;">
            <div class="row">
                <div class="col-md-8 mx-auto">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-truck"></i> Delivery Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Delivery Slot *</label>
                                <select name="slot_id" class="form-select" required>
                                    <option value="">Select a delivery slot</option>
                                    <?php while($slot = mysqli_fetch_assoc($slots)): ?>
                                    <option value="<?php echo $slot['Slot_ID']; ?>">
                                        🕐 <?php echo htmlspecialchars($slot['Slot_Type']); ?>: <?php echo date('h:i A', strtotime($slot['Start_Time'])); ?> - <?php echo date('h:i A', strtotime($slot['End_Time'])); ?>
                                        (Max <?php echo $slot['Max_Orders']; ?> orders)
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Preferred Delivery Date</label>
                                <input type="date" name="delivery_date" class="form-control" 
                                       min="<?php echo $min_date; ?>" max="<?php echo $max_date; ?>"
                                       value="<?php echo $min_date; ?>">
                                <small class="text-muted">Select your preferred delivery date (Min 1 day, Max 7 days)</small>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Special Instructions</label>
                                <textarea name="special_instructions" class="form-control" rows="3" 
                                          placeholder="Any special requests? e.g., delicate fabrics, no starch, etc."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-end mt-3">
                <button type="button" class="btn btn-secondary me-2" onclick="prevStep(1)">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
                <button type="button" class="btn btn-primary" onclick="nextStep(3)">
                    Next: Review Order <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
        
        <!-- Step 3: Review & Confirm -->
        <div id="step3Content" class="step-content" style="display: none;">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-clipboard-list"></i> Review Your Order</h5>
                        </div>
                        <div class="card-body">
                            <div id="reviewItems"></div>
                            <hr>
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Delivery Details</h6>
                                    <p id="reviewDelivery" class="text-muted"></p>
                                </div>
                                <div class="col-md-6 text-end">
                                    <h6>Total Amount</h6>
                                    <h3 class="text-primary" id="reviewTotal">Rs. 0.00</h3>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> By placing this order, you agree to our terms and conditions. You can track your order status in real-time.
                    </div>
                </div>
            </div>
            
            <div class="text-end mt-3">
                <button type="button" class="btn btn-secondary me-2" onclick="prevStep(2)">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
                <button type="submit" class="btn btn-success btn-lg">
                    <i class="fas fa-check-circle"></i> Place Order
                </button>
            </div>
        </div>
    </form>
</div>

<script>
let itemCount = 1;
const services = <?php echo json_encode($services_array); ?>;

function addItem() {
    const container = document.getElementById('itemsContainer');
    const newItem = document.createElement('div');
    newItem.className = 'item-card';
    newItem.setAttribute('data-index', itemCount);
    newItem.innerHTML = `
        <div class="row align-items-end">
            <div class="col-md-3 mb-2">
                <label class="form-label small">Cloth Type *</label>
                <input type="text" name="items[${itemCount}][cloth_type]" class="form-control" placeholder="e.g., Cotton Shirt" required>
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label small">Color</label>
                <input type="text" name="items[${itemCount}][color]" class="form-control" placeholder="Color">
            </div>
            <div class="col-md-2 mb-2">
                <label class="form-label small">Quantity *</label>
                <input type="number" name="items[${itemCount}][quantity]" class="form-control quantity" min="1" value="1" required>
            </div>
            <div class="col-md-4 mb-2">
                <label class="form-label small">Service *</label>
                <select name="items[${itemCount}][service_id]" class="form-select service-select" required>
                    <option value="">Select Service</option>
                    ${services.map(s => `<option value="${s.Service_ID}" data-price="${s.Service_Price}">${s.Service_Name} - Rs. ${parseFloat(s.Service_Price).toFixed(2)}</option>`).join('')}
                </select>
            </div>
            <div class="col-md-1 mb-2 text-end">
                <i class="fas fa-trash-alt remove-item text-danger" style="cursor: pointer; font-size: 20px;"></i>
            </div>
        </div>
    `;
    container.appendChild(newItem);
    itemCount++;
    attachItemEvents();
    calculateTotal();
}

function removeItem(btn) {
    const container = document.getElementById('itemsContainer');
    if (container.children.length > 1) {
        btn.closest('.item-card').remove();
    } else {
        alert('You must have at least one item in your order');
    }
    calculateTotal();
}

function attachItemEvents() {
    document.querySelectorAll('.remove-item').forEach(btn => {
        btn.onclick = () => removeItem(btn);
    });
    document.querySelectorAll('.quantity, .service-select').forEach(input => {
        input.onchange = () => calculateTotal();
        input.onkeyup = () => calculateTotal();
    });
}

function calculateTotal() {
    let total = 0;
    const items = [];
    const rows = document.querySelectorAll('.item-card');
    
    rows.forEach(row => {
        const serviceSelect = row.querySelector('.service-select');
        const quantity = row.querySelector('.quantity').value;
        const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
        const price = selectedOption ? parseFloat(selectedOption.dataset.price || 0) : 0;
        const itemTotal = price * quantity;
        total += itemTotal;
        
        if (serviceSelect.value) {
            items.push({
                name: selectedOption.text.split(' - ')[0],
                qty: quantity,
                total: itemTotal
            });
        }
    });
    
    // Update summary
    const summaryDiv = document.getElementById('summaryItems');
    if (items.length > 0) {
        summaryDiv.innerHTML = items.map(item => `
            <div class="d-flex justify-content-between small mb-1">
                <span>${item.name} x${item.qty}</span>
                <span>Rs. ${item.total.toFixed(2)}</span>
            </div>
        `).join('');
    } else {
        summaryDiv.innerHTML = '<p class="text-muted text-center mb-0">No items added yet</p>';
    }
    
    document.getElementById('subtotal').innerText = 'Rs. ' + total.toFixed(2);
    document.getElementById('total').innerText = 'Rs. ' + total.toFixed(2);
    
    // Update review section
    const reviewItems = document.getElementById('reviewItems');
    if (items.length > 0) {
        reviewItems.innerHTML = `
            <table class="table table-sm">
                <thead><tr><th>Item</th><th>Qty</th><th>Total</th></tr></thead>
                <tbody>
                    ${items.map(item => `<tr><td>${item.name}</td><td>${item.qty}</td><td>Rs. ${item.total.toFixed(2)}</td></tr>`).join('')}
                    <tr class="table-active"><td colspan="2" class="text-end"><strong>Total</strong></td><td><strong>Rs. ${total.toFixed(2)}</strong></td></tr>
                </tbody>
            </table>
        `;
    } else {
        reviewItems.innerHTML = '<p class="text-danger">Please add items to your order</p>';
    }
    
    document.getElementById('reviewTotal').innerText = 'Rs. ' + total.toFixed(2);
    return total;
}

function updateReviewDelivery() {
    const slotSelect = document.querySelector('select[name="slot_id"]');
    const deliveryDate = document.querySelector('input[name="delivery_date"]');
    const instructions = document.querySelector('textarea[name="special_instructions"]');
    
    const slotText = slotSelect.options[slotSelect.selectedIndex]?.text || 'Not selected';
    const dateText = deliveryDate.value || 'Not selected';
    const instructionsText = instructions.value || 'None';
    
    document.getElementById('reviewDelivery').innerHTML = `
        <strong>Slot:</strong> ${slotText}<br>
        <strong>Date:</strong> ${dateText}<br>
        <strong>Instructions:</strong> ${instructionsText}
    `;
}

let currentStep = 1;

function nextStep(step) {
    if (step === 2 && document.querySelectorAll('.item-card').length === 0) {
        alert('Please add at least one item to your order');
        return;
    }
    
    if (step === 3) {
        updateReviewDelivery();
        if (calculateTotal() === 0) {
            alert('Please add items to your order');
            return;
        }
        const slot = document.querySelector('select[name="slot_id"]').value;
        if (!slot) {
            alert('Please select a delivery slot');
            return;
        }
    }
    
    document.querySelectorAll('.step-content').forEach(content => content.style.display = 'none');
    document.getElementById(`step${step}Content`).style.display = 'block';
    
    document.querySelectorAll('.step').forEach(stepEl => stepEl.classList.remove('active'));
    document.getElementById(`step${step}`).classList.add('active');
    
    for(let i = 1; i < step; i++) {
        document.getElementById(`step${i}`).classList.add('completed');
    }
    
    currentStep = step;
}

function prevStep(step) {
    nextStep(step);
}

// Initialize
attachItemEvents();
calculateTotal();

// Update review on delivery details change
const slotSelect = document.querySelector('select[name="slot_id"]');
const deliveryDateInput = document.querySelector('input[name="delivery_date"]');
const instructionsTextarea = document.querySelector('textarea[name="special_instructions"]');

if(slotSelect) slotSelect.addEventListener('change', updateReviewDelivery);
if(deliveryDateInput) deliveryDateInput.addEventListener('change', updateReviewDelivery);
if(instructionsTextarea) instructionsTextarea.addEventListener('input', updateReviewDelivery);
</script>

<?php include_once '../includes/footer.php'; ?>