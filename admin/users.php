<?php
$pageTitle = "Users Management";
require_once('includes/header.php');

// Get filters from query string
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$limit = 10;

try {
    $db = Database::getInstance()->getConnection();
    
    // Build where clause
    $where = [];
    $params = [];
    
    if ($status) {
        $where[] = "u.status = ?";
        $params[] = $status;
    }
    
    if ($search) {
        $where[] = "(u.name LIKE ? OR u.email LIKE ?)";
        $params = array_merge($params, ["%$search%", "%$search%"]);
    }
    
    $whereClause = $where ? "WHERE " . implode(" AND ", $where) : "";
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM users u $whereClause";
    $stmt = $db->prepare($countSql);
    $stmt->execute($params);
    $total = $stmt->fetch()['total'];
    
    // Get users
    $offset = ($page - 1) * $limit;
    $sql = "SELECT 
                u.*,
                (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) as orders_count,
                (SELECT SUM(total_amount) FROM orders o WHERE o.user_id = u.id AND o.status = 'completed') as total_spent
            FROM users u
            $whereClause
            ORDER BY u.created_at DESC
            LIMIT ? OFFSET ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute(array_merge($params, [$limit, $offset]));
    $users = $stmt->fetchAll();
    
    // Get user statistics
    $statsSql = "SELECT 
                    COUNT(*) as total_users,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_users,
                    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive_users,
                    SUM(CASE WHEN status = 'banned' THEN 1 ELSE 0 END) as banned_users,
                    (SELECT COUNT(DISTINCT user_id) FROM orders WHERE status = 'completed') as customers_with_orders
                FROM users";
    $stmt = $db->prepare($statsSql);
    $stmt->execute();
    $stats = $stmt->fetch();
    
} catch (Exception $e) {
    error_log("Error fetching users: " . $e->getMessage());
    $error = "An error occurred while fetching users.";
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">Users Management</h1>
    </div>
    
    <?php if (isset($stats)): ?>
    <!-- Statistics Cards -->
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-shrink-0">
                            <div class="stat-icon bg-primary-subtle text-primary">
                                <i class="bi bi-people"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-subtitle text-muted mb-1">Total Users</h6>
                            <h2 class="card-title h1 mb-0">
                                <?php echo number_format($stats['total_users']); ?>
                            </h2>
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
                                <i class="bi bi-person-check"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-subtitle text-muted mb-1">Active Users</h6>
                            <h2 class="card-title h1 mb-0">
                                <?php echo number_format($stats['active_users']); ?>
                            </h2>
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
                                <i class="bi bi-person-dash"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-subtitle text-muted mb-1">Inactive Users</h6>
                            <h2 class="card-title h1 mb-0">
                                <?php echo number_format($stats['inactive_users']); ?>
                            </h2>
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
                                <i class="bi bi-cart-check"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1 ms-3">
                            <h6 class="card-subtitle text-muted mb-1">Customers with Orders</h6>
                            <h2 class="card-title h1 mb-0">
                                <?php echo number_format($stats['customers_with_orders']); ?>
                            </h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Search</label>
                    <input type="text" name="search" class="form-control" 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="Name or email...">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        <option value="banned" <?php echo $status === 'banned' ? 'selected' : ''; ?>>Banned</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-2"></i>Filter
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo $error; ?>
        </div>
    <?php else: ?>
    
    <!-- Users Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>User</th>
                            <th>Email</th>
                            <th>Orders</th>
                            <th>Total Spent</th>
                            <th>Status</th>
                            <th>Joined</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar avatar-sm bg-light rounded-circle me-2">
                                        <i class="bi bi-person text-muted"></i>
                                    </div>
                                    <div>
                                        <?php echo htmlspecialchars($user['name']); ?>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td>
                                <a href="orders.php?user_id=<?php echo $user['id']; ?>" 
                                   class="text-decoration-none">
                                    <?php echo number_format($user['orders_count']); ?> orders
                                </a>
                            </td>
                            <td>
                                <?php if ($user['total_spent']): ?>
                                <div class="fw-bold"><?php echo number_format($user['total_spent'], 2); ?> €</div>
                                <?php else: ?>
                                <span class="text-muted">No purchases</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo match($user['status']) {
                                        'active' => 'success',
                                        'inactive' => 'warning',
                                        'banned' => 'danger',
                                        default => 'secondary'
                                    };
                                ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </td>
                            <td>
                                <div><?php echo date('d M Y', strtotime($user['created_at'])); ?></div>
                                <div class="small text-muted">
                                    <?php echo date('H:i', strtotime($user['created_at'])); ?>
                                </div>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="users/view.php?id=<?php echo $user['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-secondary"
                                            onclick="changeStatus(<?php echo $user['id']; ?>, '<?php echo $user['status']; ?>')">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="bi bi-people display-6 d-block mb-3"></i>
                                    <h5>No Users Found</h5>
                                    <p class="mb-0">Try adjusting your search or filter criteria</p>
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
                    of <?php echo $total; ?> users
                </div>
                <nav aria-label="Page navigation">
                    <ul class="pagination mb-0">
                        <?php
                        $totalPages = ceil($total / $limit);
                        $maxPages = 5;
                        $startPage = max(1, min($page - floor($maxPages / 2), $totalPages - $maxPages + 1));
                        $endPage = min($startPage + $maxPages - 1, $totalPages);
                        
                        // Previous page
                        if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                        <?php endif;
                        
                        // Page numbers
                        for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor;
                        
                        // Next page
                        if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>">
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

<!-- Change Status Modal -->
<div class="modal fade" id="changeStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Change User Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="changeStatusForm" method="post" action="users/change-status.php">
                <div class="modal-body">
                    <input type="hidden" name="user_id" id="statusUserId">
                    <div class="mb-3">
                        <label class="form-label">New Status</label>
                        <select name="status" id="newStatus" class="form-select" required>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="banned">Banned</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason for Change</label>
                        <textarea name="reason" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function changeStatus(userId, currentStatus) {
    document.getElementById('statusUserId').value = userId;
    document.getElementById('newStatus').value = currentStatus;
    new bootstrap.Modal(document.getElementById('changeStatusModal')).show();
}
</script>

<?php require_once('includes/footer.php'); ?> 