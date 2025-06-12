// WebSocket connection
let ws;
let reconnectAttempts = 0;
const maxReconnectAttempts = 5;

function connectWebSocket() {
    ws = new WebSocket('ws://localhost:8080');

    ws.onopen = function() {
        console.log('WebSocket connected');
        reconnectAttempts = 0;
        
        // Authenticate as admin
        ws.send(JSON.stringify({
            type: 'admin_auth',
            token: localStorage.getItem('admin_token')
        }));
    };

    ws.onmessage = function(event) {
        const data = JSON.parse(event.data);
        
        switch (data.type) {
            case 'auth_success':
                console.log('Admin authentication successful');
                break;
                
            case 'order_update':
                updateOrderInTable(data.data);
                updateDashboardStats();
                break;
        }
    };

    ws.onclose = function() {
        console.log('WebSocket disconnected');
        if (reconnectAttempts < maxReconnectAttempts) {
            setTimeout(() => {
                reconnectAttempts++;
                connectWebSocket();
            }, 5000);
        }
    };

    ws.onerror = function(error) {
        console.error('WebSocket error:', error);
    };
}

// Update order in the table
function updateOrderInTable(order) {
    const row = document.querySelector(`tr[data-order-id="${order.id}"]`);
    if (!row) {
        // If order doesn't exist in table, prepend it
        const tbody = document.querySelector('#ordersTable tbody');
        if (tbody) {
            tbody.insertAdjacentHTML('afterbegin', createOrderRow(order));
        }
    } else {
        // Update existing row
        row.innerHTML = createOrderRow(order).innerHTML;
    }
}

// Create order row HTML
function createOrderRow(order) {
    const tr = document.createElement('tr');
    tr.setAttribute('data-order-id', order.id);
    
    const statusClasses = {
        'pending': 'bg-warning',
        'completed': 'bg-success',
        'cancelled': 'bg-danger',
        'refunded': 'bg-info'
    };
    
    tr.innerHTML = `
        <td>
            <a href="orders/view.php?id=${order.id}" class="text-decoration-none">
                #${String(order.id).padStart(5, '0')}
            </a>
        </td>
        <td>
            <div class="d-flex align-items-center">
                <div class="avatar avatar-sm bg-light rounded-circle me-2">
                    <i class="bi bi-person text-muted"></i>
                </div>
                <div>
                    <div class="fw-bold">${escapeHtml(order.user_name || 'Unknown')}</div>
                    <div class="small text-muted">${escapeHtml(order.user_email || '')}</div>
                </div>
            </div>
        </td>
        <td>
            <div class="fw-bold">${parseFloat(order.total_amount).toFixed(2)} €</div>
        </td>
        <td>
            <span class="badge ${statusClasses[order.status] || 'bg-secondary'}">
                ${order.status.charAt(0).toUpperCase() + order.status.slice(1)}
            </span>
        </td>
        <td>
            <div class="small text-muted">
                ${new Date(order.created_at).toLocaleDateString()}
            </div>
        </td>
        <td>
            <div class="btn-group">
                <button type="button" class="btn btn-sm btn-outline-primary" 
                        onclick="viewOrder(${order.id})">
                    <i class="bi bi-eye"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-success" 
                        onclick="updateOrderStatus(${order.id}, 'completed')"
                        ${order.status === 'completed' ? 'disabled' : ''}>
                    <i class="bi bi-check-circle"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" 
                        onclick="cancelOrder(${order.id})"
                        ${order.status === 'cancelled' ? 'disabled' : ''}>
                    <i class="bi bi-x-circle"></i>
                </button>
            </div>
        </td>
    `;
    
    return tr;
}

// Update dashboard statistics
function updateDashboardStats() {
    fetch('/admin/api/dashboard-stats.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStatCard('totalOrders', data.stats.total_orders);
                updateStatCard('totalRevenue', data.stats.total_revenue);
                updateStatCard('avgOrderValue', data.stats.avg_order_value);
                updateStatCard('completionRate', data.stats.completion_rate);
            }
        })
        .catch(error => console.error('Error updating stats:', error));
}

// Update a stat card value
function updateStatCard(id, value) {
    const element = document.getElementById(id);
    if (element) {
        if (typeof value === 'number') {
            if (id.includes('Rate')) {
                element.textContent = `${Math.round(value)}%`;
            } else if (id.includes('Revenue') || id.includes('Value')) {
                element.textContent = `${value.toFixed(2)} €`;
            } else {
                element.textContent = value.toLocaleString();
            }
        }
    }
}

// Order actions
function viewOrder(orderId) {
    window.location.href = `orders/view.php?id=${orderId}`;
}

function updateOrderStatus(orderId, status) {
    if (!confirm(`Are you sure you want to mark this order as ${status}?`)) {
        return;
    }
    
    fetch('/admin/api/orders.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_status&order_id=${orderId}&status=${status}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // The WebSocket will handle the update
            showToast('success', data.message);
        } else {
            showToast('error', data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'An error occurred while updating the order');
    });
}

function cancelOrder(orderId) {
    if (!confirm('Are you sure you want to cancel this order?')) {
        return;
    }
    
    fetch('/admin/api/orders.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=delete&order_id=${orderId}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // The WebSocket will handle the update
            showToast('success', data.message);
        } else {
            showToast('error', data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('error', 'An error occurred while cancelling the order');
    });
}

// Helper functions
function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function showToast(type, message) {
    const toast = document.createElement('div');
    toast.className = `toast align-items-center text-white bg-${type === 'success' ? 'success' : 'danger'} border-0`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
        <div class="d-flex">
            <div class="toast-body">
                ${message}
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    `;
    
    document.getElementById('toastContainer').appendChild(toast);
    const bsToast = new bootstrap.Toast(toast);
    bsToast.show();
    
    toast.addEventListener('hidden.bs.toast', () => {
        toast.remove();
    });
}

// Initialize WebSocket connection when the page loads
document.addEventListener('DOMContentLoaded', () => {
    connectWebSocket();
}); 