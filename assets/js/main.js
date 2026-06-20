/**
 * Teddy Shine Laundry Management System
 * Main JavaScript File
 * Version: 1.0
 * Author: Teddy Shine Team
 */

// ============================================
// DOM Ready Event
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    // Initialize all components
    initTooltips();
    initDropdowns();
    initFormValidation();
    initDataTables();
    initCharts();
    initNotifications();
    initBackToTop();
    initPasswordToggle();
    initPhoneFormatting();
    initConfirmDelete();
    initAutoRefresh();
});

// ============================================
// Global Variables
// ============================================
const TEDDY_SHIINE = {
    apiBaseUrl: '/TeddyShine_Laundry/api/',
    currentUser: null,
    notifications: [],
    cart: []
};

// ============================================
// Tooltips Initialization
// ============================================
function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// ============================================
// Dropdowns Initialization
// ============================================
function initDropdowns() {
    const dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
    dropdownElementList.map(function(dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl);
    });
}

// ============================================
// Form Validation (Bootstrap)
// ============================================
function initFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', event => {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
}

// ============================================
// Data Tables Enhancement
// ============================================
function initDataTables() {
    const tables = document.querySelectorAll('.data-table');
    
    tables.forEach(table => {
        // Add search input
        const searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.placeholder = 'Search...';
        searchInput.className = 'form-control form-control-sm mb-3';
        searchInput.style.width = '250px';
        
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = table.querySelectorAll('tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
        
        table.parentNode.insertBefore(searchInput, table);
    });
}

// ============================================
// Charts Initialization (if Chart.js is loaded)
// ============================================
function initCharts() {
    if (typeof Chart === 'undefined') return;
    
    // Revenue Chart
    const revenueCanvas = document.getElementById('revenueChart');
    if (revenueCanvas) {
        new Chart(revenueCanvas, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                datasets: [{
                    label: 'Revenue (Rs.)',
                    data: [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: { callbacks: { label: (ctx) => 'Rs. ' + ctx.raw.toLocaleString() } }
                },
                scales: { y: { beginAtZero: true, ticks: { callback: (v) => 'Rs. ' + v.toLocaleString() } } }
            }
        });
    }
}

// ============================================
// Notifications System
// ============================================
function initNotifications() {
    // Auto-hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
}

function showNotification(message, type = 'success') {
    const alertDiv = document.createElement('div');
    alertDiv.className = alert alert-${type} alert-dismissible fade show position-fixed;
    alertDiv.style.top = '20px';
    alertDiv.style.right = '20px';
    alertDiv.style.zIndex = '9999';
    alertDiv.style.minWidth = '300px';
    alertDiv.style.maxWidth = '500px';
    alertDiv.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : (type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle')} me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);
    
    setTimeout(() => {
        if (alertDiv) alertDiv.remove();
    }, 5000);
}

// ============================================
// Back to Top Button
// ============================================
function initBackToTop() {
    const backToTopBtn = document.getElementById('backToTop');
    if (!backToTopBtn) return;
    
    window.addEventListener('scroll', () => {
        if (window.pageYOffset > 300) {
            backToTopBtn.style.display = 'block';
        } else {
            backToTopBtn.style.display = 'none';
        }
    });
    
    backToTopBtn.addEventListener('click', () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
}

function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ============================================
// Password Toggle Visibility
// ============================================
function initPasswordToggle() {
    const toggleButtons = document.querySelectorAll('.toggle-password');
    
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const passwordInput = this.parentElement.querySelector('input[type="password"], input[type="text"]');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classlist.toggle('fa-eye-slash');
        });
    });
}

// ============================================
// Phone Number Formatting
// ============================================
function initPhoneFormatting() {
    const phoneInputs = document.querySelectorAll('input[type="tel"]');
    
    phoneInputs.forEach(input => {
        input.addEventListener('input', function(e) {
            let value = this.value.replace(/\D/g, '');
            if (value.length > 11) value = value.slice(0, 11);
            if (value.length > 4) {
                value = value.slice(0, 4) + '-' + value.slice(4);
            }
            if (value.length > 8) {
                value = value.slice(0, 8) + '-' + value.slice(8);
            }
            this.value = value;
        });
    });
}

// ============================================
// Confirm Delete Handler
// ============================================
function initConfirmDelete() {
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function(e) {
            const message = this.dataset.confirm || 'Are you sure?';
            if (!confirm(message)) {
                e.preventDefault();
            }
        });
    });
}

function confirmDelete(url, itemName = 'this item') {
    if (confirm(Are you sure you want to delete ${itemName}? This action cannot be undone.)) {
        window.location.href = url;
    }
    return false;
}

// ============================================
// Auto Refresh for Tracking Pages
// ============================================
function initAutoRefresh() {
    if (document.querySelector('.auto-refresh')) {
        const interval = parseInt(document.querySelector('.auto-refresh').dataset.interval) || 30;
        setTimeout(() => {
            location.reload();
        }, interval * 1000);
    }
}

// ============================================
// Loading Spinner
// ============================================
function showLoading() {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) spinner.classList.add('active');
}

function hideLoading() {
    const spinner = document.getElementById('loadingSpinner');
    if (spinner) spinner.classList.remove('active');
}

// ============================================
// AJAX Request Helper
// ============================================
async function ajaxRequest(url, method = 'GET', data = null) {
    showLoading();
    
    const options = {
        method: method,
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    if (data && (method === 'POST' || method === 'PUT')) {
        options.body = new URLSearchParams(data).toString();
    }
    
    try {
        const response = await fetch(url, options);
        const result = await response.json();
        hideLoading();
        return result;
    } catch (error) {
        hideLoading();
        console.error('AJAX Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
        return null;
    }
}

// ============================================
// Print Function
// ============================================
function printPage() {
    window.print();
}

// ============================================
// Export to CSV
// ============================================
function exportToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const rows = table.querySelectorAll('tr');
    const csv = [];
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td, th');
        const rowData = Array.from(cols).map(col => {
            let text = col.innerText.replace(/"/g, '""');
            return "${text}";
        });
        csv.push(rowData.join(','));
    });
    
    const blob = new Blob([csv.join('\n')], { type: 'text/csv' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = filename;
    link.click();
    URL.revokeObjectURL(link.href);
}

// ============================================
// Format Currency
// ============================================
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-PK', {
        style: 'currency',
        currency: 'PKR',
        minimumFractionDigits: 2
    }).format(amount);
}

// ============================================
// Format Date
// ============================================
function formatDate(dateString, format = 'DD MMM YYYY') {
    const date = new Date(dateString);
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return date.toLocaleDateString('en-PK', options);
}

// ============================================
// Time Ago Function
// ============================================
function timeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    if (seconds < 60) return 'just now';
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return ${minutes} minute${minutes > 1 ? 's' : ''} ago;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return ${hours} hour${hours > 1 ? 's' : ''} ago;
    const days = Math.floor(hours / 24);
    if (days < 7) return ${days} day${days > 1 ? 's' : ''} ago;
    return formatDate(dateString);
}

// ============================================
// Copy to Clipboard
// ============================================
function copyToClipboard(text, message = 'Copied to clipboard!') {
    navigator.clipboard.writeText(text).then(() => {
        showNotification(message, 'success');
    }).catch(() => {
        showNotification('Failed to copy', 'error');
    });
}

// ============================================
// Form Submit with Loading
// ============================================
function setupFormSubmit() {
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', () => {
            showLoading();
        });
    });
}

// ============================================
// Toggle Visibility
// ============================================
function toggleVisibility(elementId) {
    const el = document.getElementById(elementId);
    if (el) {
        el.style.display = el.style.display === 'none' ? 'block' : 'none';
    }
}