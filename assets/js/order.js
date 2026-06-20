/**
 * Teddy Shine Laundry System
 * Order Management JavaScript
 * For dynamic order placement and management
 */

// ============================================
// Order Cart Management
// ============================================
class OrderCart {
    constructor() {
        this.items = [];
        this.services = [];
        this.loadServices();
        this.initEventListeners();
    }
    
    loadServices() {
        // Services data (from PHP - will be populated)
        const servicesElement = document.getElementById('servicesData');
        if (servicesElement) {
            try {
                this.services = JSON.parse(servicesElement.value);
            } catch (e) {
                this.services = [];
            }
        }
    }
    
    initEventListeners() {
        // Add item button
        const addBtn = document.getElementById('addItemBtn');
        if (addBtn) {
            addBtn.addEventListener('click', () => this.addItem());
        }
        
        // Remove item delegation
        document.addEventListener('click', (e) => {
            if (e.target.closest('.remove-item')) {
                const index = e.target.closest('.item-card')?.dataset.index;
                if (index !== undefined) this.removeItem(parseInt(index));
            }
        });
        
        // Calculate total on change
        document.addEventListener('change', (e) => {
            if (e.target.closest('.quantity') || e.target.closest('.service-select')) {
                this.calculateTotal();
            }
        });
    }
    
    addItem() {
        const container = document.getElementById('itemsContainer');
        const index = this.items.length;
        
        const itemHtml = `
            <div class="item-card" data-index="${index}">
                <div class="row align-items-end">
                    <div class="col-md-3 mb-2">
                        <label class="form-label small">Cloth Type</label>
                        <input type="text" name="items[${index}][cloth_type]" class="form-control" placeholder="e.g., Cotton Shirt" required>
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label small">Color</label>
                        <input type="text" name="items[${index}][color]" class="form-control" placeholder="Color">
                    </div>
                    <div class="col-md-2 mb-2">
                        <label class="form-label small">Quantity</label>
                        <input type="number" name="items[${index}][quantity]" class="form-control quantity" min="1" value="1" required>
                    </div>
                    <div class="col-md-4 mb-2">
                        <label class="form-label small">Service</label>
                        <select name="items[${index}][service_id]" class="form-select service-select" required>
                            <option value="">Select Service</option>
                            ${this.services.map(s => <option value="${s.Service_ID}" data-price="${s.Service_Price}">${s.Service_Name} - Rs. ${parseFloat(s.Service_Price).toFixed(2)}</option>).join('')}
                        </select>
                    </div>
                    <div class="col-md-1 mb-2 text-end">
                        <i class="fas fa-trash-alt remove-item text-danger" style="cursor: pointer; font-size: 20px;"></i>
                    </div>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', itemHtml);
        this.items.push({});
        this.calculateTotal();
    }
    
    removeItem(index) {
        const container = document.getElementById('itemsContainer');
        const items = container.querySelectorAll('.item-card');
        if (items.length > 1) {
            items[index].remove();
            this.items.splice(index, 1);
            this.reindexItems();
            this.calculateTotal();
        } else {
            showNotification('You must have at least one item in your order', 'warning');
        }
    }
    
    reindexItems() {
        const items = document.querySelectorAll('.item-card');
        items.forEach((item, newIndex) => {
            item.dataset.index = newIndex;
            const inputs = item.querySelectorAll('input, select');
            inputs.forEach(input => {
                const name = input.getAttribute('name');
                if (name) {
                    input.setAttribute('name', name.replace(/items\[\d+\]/, items[${newIndex}]));
                }
            });
        });
    }
    
    calculateTotal() {
        let total = 0;
        const items = document.querySelectorAll('.item-card');
        const summaryItems = [];
        
        items.forEach((item, index) => {
            const serviceSelect = item.querySelector('.service-select');
            const quantity = item.querySelector('.quantity').value;
            const selectedOption = serviceSelect.options[serviceSelect.selectedIndex];
            const price = selectedOption ? parseFloat(selectedOption.dataset.price || 0) : 0;
            const itemTotal = price * quantity;
            total += itemTotal;
            
            if (serviceSelect.value) {
                summaryItems.push({
                    name: selectedOption.text.split(' - ')[0],
                    qty: quantity,
                    total: itemTotal
                });
            }
        });
        
        // Update summary display
        const summaryDiv = document.getElementById('orderSummary');
        if (summaryDiv) {
            if (summaryItems.length > 0) {
                summaryDiv.innerHTML = `
                    <div class="summary-items">
                        ${summaryItems.map(item => `
                            <div class="d-flex justify-content-between small mb-1">
                                <span>${item.name} x${item.qty}</span>
                                <span>Rs. ${item.total.toFixed(2)}</span>
                            </div>
                        `).join('')}
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between fw-bold">
                        <span>Total:</span>
                        <span>Rs. ${total.toFixed(2)}</span>
                    </div>
                `;
            } else {
                summaryDiv.innerHTML = '<p class="text-muted text-center mb-0">No items added yet</p>';
            }
        }
        
        // Update hidden total field
        const totalField = document.getElementById('orderTotal');
        if (totalField) totalField.value = total;
        
        return total;
    }
}

// ============================================
// Order Validation
// ============================================
function validateOrder() {
    const items = document.querySelectorAll('.item-card');
    if (items.length === 0) {
        showNotification('Please add at least one item to your order', 'error');
        return false;
    }
    
    let isValid = true;
    items.forEach((item, index) => {
        const clothType = item.querySelector('input[name*="cloth_type"]')?.value;
        const service = item.querySelector('.service-select')?.value;
        
        if (!clothType) {
            showNotification(Item ${index + 1}: Please enter cloth type, 'error');
            isValid = false;
        }
        if (!service) {
            showNotification(Item ${index + 1}: Please select a service, 'error');
            isValid = false;
        }
    });
    
    return isValid;
}

// ============================================
// Delivery Slot Availability Check
// ============================================
async function checkSlotAvailability(slotId, date) {
    const result = await ajaxRequest('check_slot.php', 'POST', { slot_id: slotId, date: date });
    if (result && !result.available) {
        showNotification(Slot is full for ${date}. Please select another slot., 'warning');
        return false;
    }
    return true;
}

// ============================================
// Order Status Update
// ============================================
async function updateOrderStatus(orderId, status) {
    const result = await ajaxRequest('update_status.php', 'POST', { order_id: orderId, status: status });
    if (result && result.success) {
        showNotification(Order status updated to ${status}, 'success');
        location.reload();
    } else {
        showNotification('Failed to update order status', 'error');
    }
}

// ============================================
// Cancel Order
// ============================================
async function cancelOrder(orderId) {
    if (confirm('Are you sure you want to cancel this order? This action cannot be undone.')) {
        const result = await ajaxRequest('cancel_order.php', 'POST', { order_id: orderId });
        if (result && result.success) {
            showNotification('Order cancelled successfully', 'success');
            location.reload();
        } else {
            showNotification('Failed to cancel order', 'error');
        }
    }
}

// ============================================
// Reorder Function
// ============================================
function reorder(orderId) {
    window.location.href = place_order.php?reorder=${orderId};
}

// ============================================
// Initialize Order Page
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    // Initialize cart if on order page
    if (document.getElementById('itemsContainer')) {
        window.orderCart = new OrderCart();
    }
    
    // Order form validation
    const orderForm = document.getElementById('orderForm');
    if (orderForm) {
        orderForm.addEventListener('submit', function(e) {
            if (!validateOrder()) {
                e.preventDefault();
            }
        });
    }
});