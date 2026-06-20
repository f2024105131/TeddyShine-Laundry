/**
 * Teddy Shine Laundry System
 * Admin Panel JavaScript
 * For admin dashboard, CRUD operations, and reports
 */

// ============================================
// Bulk Actions Handler
// ============================================
class BulkActions {
    constructor(options = {}) {
        this.checkboxes = [];
        this.selectAllCheckbox = document.getElementById('selectAll');
        this.bulkActionsDiv = document.getElementById('bulkActions');
        this.selectedCountSpan = document.getElementById('selectedCount');
        
        this.init();
    }
    
    init() {
        this.checkboxes = document.querySelectorAll('.bulk-checkbox');
        
        if (this.selectAllCheckbox) {
            this.selectAllCheckbox.addEventListener('change', () => this.toggleAll());
        }
        
        this.checkboxes.forEach(cb => {
            cb.addEventListener('change', () => this.updateSelectedCount());
        });
        
        this.updateSelectedCount();
    }
    
    toggleAll() {
        this.checkboxes.forEach(cb => {
            cb.checked = this.selectAllCheckbox.checked;
        });
        this.updateSelectedCount();
    }
    
    updateSelectedCount() {
        const count = document.querySelectorAll('.bulk-checkbox:checked').length;
        if (this.selectedCountSpan) {
            this.selectedCountSpan.textContent = count;
        }
        
        if (this.bulkActionsDiv) {
            if (count > 0) {
                this.bulkActionsDiv.classList.add('show');
            } else {
                this.bulkActionsDiv.classList.remove('show');
            }
        }
    }
    
    getSelectedIds() {
        const selected = [];
        document.querySelectorAll('.bulk-checkbox:checked').forEach(cb => {
            selected.push(cb.value);
        });
        return selected;
    }
    
    async performAction(action) {
        const ids = this.getSelectedIds();
        if (ids.length === 0) {
            showNotification('Please select items to perform action', 'warning');
            return;
        }
        
        if (confirm(Are you sure you want to ${action} ${ids.length} item(s)?)) {
            const result = await ajaxRequest('bulk_action.php', 'POST', {
                action: action,
                ids: JSON.stringify(ids)
            });
            
            if (result && result.success) {
                showNotification(${ids.length} item(s) ${action}d successfully, 'success');
                location.reload();
            } else {
                showNotification('Action failed', 'error');
            }
        }
    }
}

// ============================================
// Status Update (AJAX)
// ============================================
async function updateStatus(type, id, status) {
    const result = await ajaxRequest('update_status.php', 'POST', {
        type: type,
        id: id,
        status: status
    });
    
    if (result && result.success) {
        showNotification(${type} status updated to ${status}, 'success');
        location.reload();
    } else {
        showNotification('Failed to update status', 'error');
    }
}

// ============================================
// Staff Assignment (AJAX)
// ============================================
async function assignStaff(orderId, staffId) {
    const result = await ajaxRequest('assign_staff.php', 'POST', {
        order_id: orderId,
        staff_id: staffId
    });
    
    if (result && result.success) {
        showNotification('Staff assigned successfully', 'success');
    } else {
        showNotification('Failed to assign staff', 'error');
    }
}

// ============================================
// Report Generator
// ============================================
class ReportGenerator {
    constructor() {
        this.filters = {
            date_from: '',
            date_to: '',
            status: 'all'
        };
        this.init();
    }
    
    init() {
        this.setupFilters();
        this.setupExport();
    }
    
    setupFilters() {
        const dateFrom = document.getElementById('date_from');
        const dateTo = document.getElementById('date_to');
        const statusFilter = document.getElementById('status_filter');
        
        if (dateFrom) dateFrom.addEventListener('change', () => this.applyFilters());
        if (dateTo) dateTo.addEventListener('change', () => this.applyFilters());
        if (statusFilter) statusFilter.addEventListener('change', () => this.applyFilters());
    }
    
    setupExport() {
        const exportBtn = document.getElementById('exportBtn');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => this.exportReport());
        }
    }
    
    applyFilters() {
        this.filters.date_from = document.getElementById('date_from')?.value || '';
        this.filters.date_to = document.getElementById('date_to')?.value || '';
        this.filters.status = document.getElementById('status_filter')?.value || 'all';
        
        this.loadReport();
    }
    
    async loadReport() {
        showLoading();
        const result = await ajaxRequest('get_report_data.php', 'POST', this.filters);
        if (result && result.success) {
            this.renderReport(result.data);
        }
        hideLoading();
    }
    
    renderReport(data) {
        console.log('Rendering report:', data);
    }
    
    exportReport() {
        console.log('Exporting report...');
    }
}

// ============================================
// Dashboard Charts Update
// ============================================
async function refreshDashboardCharts() {
    const result = await ajaxRequest('get_dashboard_data.php', 'GET');
    if (result && result.success) {
        updateCharts(result.data);
    }
}

function updateCharts(data) {
    // Update revenue chart
    if (window.revenueChart && data.revenue) {
        window.revenueChart.data.datasets[0].data = data.revenue;
        window.revenueChart.update();
    }
    
    // Update status chart
    if (window.statusChart && data.status) {
        window.statusChart.data.datasets[0].data = data.status;
        window.statusChart.update();
    }
}

// ============================================
// Settings Management
// ============================================
class SettingsManager {
    constructor() {
        this.settings = {};
        this.init();
    }
    
    init() {
        this.loadSettings();
        this.setupSaveHandlers();
    }
    
    async loadSettings() {
        const result = await ajaxRequest('get_settings.php', 'GET');
        if (result && result.success) {
            this.settings = result.data;
            this.populateForms();
        }
    }
    
    populateForms() {
        Object.keys(this.settings).forEach(key => {
            const field = document.querySelector([name="settings[${key}]"]);
            if (field) field.value = this.settings[key];
        });
    }
    
    setupSaveHandlers() {
        const saveBtn = document.getElementById('saveSettings');
        if (saveBtn) {
            saveBtn.addEventListener('click', () => this.saveSettings());
        }
    }
    
    async saveSettings() {
        const form = document.getElementById('settingsForm');
        if (!form) return;
        
        const formData = new FormData(form);
        const settings = {};
        
        for (let [key, value] of formData.entries()) {
            if (key.startsWith('settings[')) {
                const settingKey = key.match(/settings\[(.*)\]/)[1];
                settings[settingKey] = value;
            }
        }
        
        const result = await ajaxRequest('save_settings.php', 'POST', settings);
        if (result && result.success) {
            showNotification('Settings saved successfully', 'success');
        } else {
            showNotification('Failed to save settings', 'error');
        }
    }
}

// ============================================
// Helper: Debounce
// ============================================
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// ============================================
// Initialize Admin Panel
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    // Initialize bulk actions
    if (document.querySelector('.bulk-checkbox')) {
        window.bulkActions = new BulkActions();
    }
    
    // Auto-refresh dashboard every 60 seconds
    if (document.getElementById('dashboardCharts')) {
        setInterval(refreshDashboardCharts, 60000);
    }
    
    // Initialize settings manager
    if (document.getElementById('settingsForm')) {
        window.settingsManager = new SettingsManager();
    }
    
    // Add event listeners for status dropdowns
    document.querySelectorAll('.status-update').forEach(select => {
        select.addEventListener('change', function() {
            const type = this.dataset.type;
            const id = this.dataset.id;
            updateStatus(type, id, this.value);
        });
    });
    
    // Add event listeners for staff assignment
    document.querySelectorAll('.staff-assign').forEach(select => {
        select.addEventListener('change', function() {
            const orderId = this.dataset.orderId;
            assignStaff(orderId, this.value);
        });
    });
});