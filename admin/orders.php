<?php
$pageTitle = "Orders Management";
require_once('includes/header.php');

// Get filters from query string
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;

try {
    $db = Database::getInstance()->getConnection();
    
    // Build where clause
    $where = [];
    $params = [];
    
    if ($status) {
        $where[] = "o.status = ?";
        $params[] = $status;
    }
    
    if ($search) {
        $where[] = "(o.id LIKE ? OR u.email LIKE ? OR u.name LIKE ?)";
        $params = array_merge($params, ["%$search%", "%$search%", "%$search%"]);
    }
    
    if ($dateFrom) {
        $where[] = "DATE(o.created_at) >= ?";
        $params[] = $dateFrom;
    }
    
    if ($dateTo) {
        $where[] = "DATE(o.created_at) <= ?";
        $params[] = $dateTo;
    }
    
    $whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total 
                 FROM orders o 
                 LEFT JOIN users u ON o.user_id = u.id 
                 $whereClause";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];
    
    // Get orders
    $offset = ($page - 1) * $limit;
    $sql = "SELECT 
                o.*,
                u.name as user_name,
                u.email as user_email,
                (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) as items_count
            FROM orders o
            LEFT JOIN users u ON o.user_id = u.id
            $whereClause
            ORDER BY o.created_at DESC
            LIMIT ? OFFSET ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute(array_merge($params, [$limit, $offset]));
    $orders = $stmt->fetchAll();
    
} catch (Exception $e) {
    error_log("Error fetching orders: " . $e->getMessage());
    $error = "An error occurred while fetching orders.";
}

// Calculate summary statistics
try {
    $statsSql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END) as total_revenue,
                    AVG(CASE WHEN status = 'completed' THEN total_amount ELSE NULL END) as avg_order_value,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_orders,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders
                FROM orders o
                $whereClause";
    $stmt = $db->prepare($statsSql);
    $stmt->execute($params);
    $stats = $stmt->fetch();
} catch (Exception $e) {
    error_log("Error fetching order stats: " . $e->getMessage());
    $stats = null;
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Orders Management</h1>
    </div>
    
    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="stat-icon bg-primary-subtle text-primary">
                                <i class="bi bi-cart"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-subtitle text-muted mb-1">Total Orders</h6>
                            <h2 class="card-title h1 mb-0" id="totalOrders">-</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="stat-icon bg-success-subtle text-success">
                                <i class="bi bi-currency-euro"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-subtitle text-muted mb-1">Total Revenue</h6>
                            <h2 class="card-title h1 mb-0" id="totalRevenue">-</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="stat-icon bg-info-subtle text-info">
                                <i class="bi bi-cash-stack"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-subtitle text-muted mb-1">Average Order Value</h6>
                            <h2 class="card-title h1 mb-0" id="avgOrderValue">-</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="stat-icon bg-warning-subtle text-warning">
                                <i class="bi bi-graph-up"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-subtitle text-muted mb-1">Completion Rate</h6>
                            <h2 class="card-title h1 mb-0" id="completionRate">-</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Order ID, customer email...">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        <option value="refunded" <?php echo $status === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" class="form-control" 
                           value="<?php echo htmlspecialchars($dateFrom); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" class="form-control" 
                           value="<?php echo htmlspecialchars($dateTo); ?>">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="bi bi-search"></i> Search
                    </button>
                    <a href="orders.php" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo $error; ?>
        </div>
    <?php else: ?>
    
    <!-- Orders Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" id="ordersTable">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($orders)): ?>
                            <?php foreach ($orders as $order): ?>
                                <tr data-order-id="<?php echo $order['id']; ?>">
                                    <td>
                                        <a href="orders/view.php?id=<?php echo $order['id']; ?>" class="text-decoration-none">
                                            #<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm bg-light rounded-circle me-2">
                                                <i class="bi bi-person text-muted"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($order['user_name'] ?? 'Unknown'); ?></div>
                                                <div class="small text-muted"><?php echo htmlspecialchars($order['user_email'] ?? ''); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?php echo number_format($order['total_amount'], 2); ?> €</div>
                                    </td>
                                    <td>
                                        <?php
                                        $statusClasses = [
                                            'pending' => 'bg-warning',
                                            'completed' => 'bg-success',
                                            'cancelled' => 'bg-danger',
                                            'refunded' => 'bg-info'
                                        ];
                                        $statusClass = $statusClasses[$order['status']] ?? 'bg-secondary';
                                        ?>
                                        <span class="badge <?php echo $statusClass; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="small text-muted">
                                            <?php echo date('Y-m-d', strtotime($order['created_at'])); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="viewOrder(<?php echo $order['id']; ?>)">
                                                <i class="bi bi-eye"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-success" 
                                                    onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'completed')"
                                                    <?php echo $order['status'] === 'completed' ? 'disabled' : ''; ?>>
                                                <i class="bi bi-check-circle"></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                    onclick="cancelOrder(<?php echo $order['id']; ?>)"
                                                    <?php echo $order['status'] === 'cancelled' ? 'disabled' : ''; ?>>
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="bi bi-inbox h1 d-block mb-3"></i>
                                        No orders found
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total > $limit): ?>
                <div class="d-flex justify-content-between align-items-center mt-4">
                    <div class="text-muted">
                        Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $total); ?> 
                        of <?php echo $total; ?> orders
                    </div>
                    <nav>
                        <ul class="pagination mb-0">
                            <?php
                            $totalPages = ceil($total / $limit);
                            $maxPages = 5;
                            $startPage = max(1, min($page - floor($maxPages / 2), $totalPages - $maxPages + 1));
                            $endPage = min($startPage + $maxPages - 1, $totalPages);
                            
                            // Previous page
                            if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($status); ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>">
                                        <i class="bi bi-chevron-left"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status); ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($status); ?>&search=<?php echo urlencode($search); ?>&date_from=<?php echo urlencode($dateFrom); ?>&date_to=<?php echo urlencode($dateTo); ?>">
                                        <i class="bi bi-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Toast Container -->
<div class="toast-container position-fixed bottom-0 end-0 p-3" id="toastContainer"></div>

<!-- Include orders.js -->
<script src="assets/js/orders.js"></script>

<?php require_once('includes/footer.php'); ?> 