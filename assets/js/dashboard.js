/**
 * Teddy Shine Laundry System
 * Dashboard JavaScript
 * For user and admin dashboards
 */

// ============================================
// Dashboard Widgets
// ============================================
class DashboardWidget {
    constructor(elementId, options = {}) {
        this.element = document.getElementById(elementId);
        this.options = options;
        this.data = null;
        this.init();
    }
    
    init() {
        if (this.options.autoRefresh) {
            this.startAutoRefresh();
        }
        this.loadData();
    }
    
    async loadData() {
        if (this.options.apiUrl) {
            const result = await ajaxRequest(this.options.apiUrl, 'GET');
            if (result && result.success) {
                this.data = result.data;
                this.render();
            }
        }
    }
    
    render() {
        if (this.element && this.data) {
            this.element.innerHTML = this.getTemplate();
        }
    }
    
    getTemplate() {
        return '';
    }
    
    startAutoRefresh() {
        setInterval(() => {
            this.loadData();
        }, (this.options.refreshInterval || 60) * 1000);
    }
}

// ============================================
// Statistics Cards Widget
// ============================================
class StatsWidget extends DashboardWidget {
    getTemplate() {
        return `
            <div class="row">
                <div class="col-md-3 col-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-shopping-bag"></i></div>
                        <div class="stat-value">${this.data.total_orders || 0}</div>
                        <div class="stat-label">Total Orders</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-users"></i></div>
                        <div class="stat-value">${this.data.total_customers || 0}</div>
                        <div class="stat-label">Customers</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-rupee-sign"></i></div>
                        <div class="stat-value">Rs. ${(this.data.total_revenue || 0).toLocaleString()}</div>
                        <div class="stat-label">Revenue</div>
                    </div>
                </div>
                <div class="col-md-3 col-6 mb-3">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fas fa-clock"></i></div>
                        <div class="stat-value">${this.data.pending_orders || 0}</div>
                        <div class="stat-label">Pending</div>
                    </div>
                </div>
            </div>
        `;
    }
}

// ============================================
// Recent Orders Widget
// ============================================
class RecentOrdersWidget extends DashboardWidget {
    getTemplate() {
        if (!this.data.orders || this.data.orders.length === 0) {
            return '<p class="text-center text-muted">No recent orders</p>';
        }
        
        return `
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${this.data.orders.map(order => `
                            <tr>
                                <td>#${order.id}</td>
                                <td>${order.customer}</td>
                                <td>${order.date}</td>
                                <td>Rs. ${order.amount.toLocaleString()}</td>
                                <td><span class="badge bg-${this.getStatusColor(order.status)}">${order.status}</span></td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }
    
    getStatusColor(status) {
        const colors = {
            'Pending': 'warning',
            'Processing': 'info',
            'Completed': 'success',
            'Cancelled': 'danger'
        };
        return colors[status] || 'secondary';
    }
}

// ============================================
// Chart Widgets
// ============================================
class RevenueChartWidget {
    constructor(canvasId, data) {
        this.canvas = document.getElementById(canvasId);
        this.data = data;
        this.init();
    }
    
    init() {
        if (!this.canvas) return;
        
        this.chart = new Chart(this.canvas, {
            type: 'line',
            data: {
                labels: this.data.labels || [],
                datasets: [{
                    label: 'Revenue (Rs.)',
                    data: this.data.values || [],
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
                    tooltip: {
                        callbacks: {
                            label: (ctx) => Rs. ${ctx.raw.toLocaleString()}
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (v) => Rs. ${v.toLocaleString()}
                        }
                    }
                }
            }
        });
    }
    
    update(newData) {
        this.chart.data.labels = newData.labels;
        this.chart.data.datasets[0].data = newData.values;
        this.chart.update();
    }
}

// ============================================
// Initialize Dashboard Components
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    // Initialize stats widget
    if (document.getElementById('statsWidget')) {
        window.statsWidget = new StatsWidget('statsWidget', {
            apiUrl: 'get_dashboard_stats.php',
            autoRefresh: true,
            refreshInterval: 60
        });
    }
    
    // Initialize recent orders widget
    if (document.getElementById('recentOrdersWidget')) {
        window.recentOrdersWidget = new RecentOrdersWidget('recentOrdersWidget', {
            apiUrl: 'get_recent_orders.php',
            autoRefresh: true,
            refreshInterval: 120
        });
    }
    
    // Initialize revenue chart
    if (document.getElementById('revenueChart')) {
        fetchRevenueData();
    }
});

async function fetchRevenueData() {
    const result = await ajaxRequest('get_revenue_data.php', 'GET');
    if (result && result.success) {
        window.revenueChart = new RevenueChartWidget('revenueChart', result.data);
    }
}