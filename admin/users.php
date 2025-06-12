<?php
require_once '../config/database.php';
require_once '../classes/User.php';
require_once 'includes/auth_check.php';

$pageTitle = "Users Management";
require_once 'includes/header.php';

$user = new User($conn);
$totalUsers = $user->getTotalUsers();
$activeUsers = $user->getActiveUsers();
$inactiveUsers = $user->getInactiveUsers();
$usersWithOrders = $user->getUsersWithOrders();

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$totalPages = ceil($totalUsers / $limit);

// Get users for current page
$users = $user->getAllUsers($limit, $offset);
?>

<div class="container-fluid">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/football_tickets/admin/">Dashboard</a></li>
            <li class="breadcrumb-item active">Users</li>
        </ol>
    </nav>

    <h1 class="h3 mb-4">Users Management</h1>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $totalUsers; ?></h3>
                        <p>Total Users</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                        <i class="bi bi-person-check-fill"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $activeUsers; ?></h3>
                        <p>Active Users</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                        <i class="bi bi-person-dash-fill"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $inactiveUsers; ?></h3>
                        <p>Inactive Users</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card">
                <div class="d-flex align-items-center">
                    <div class="stat-icon bg-info bg-opacity-10 text-info">
                        <i class="bi bi-cart-check-fill"></i>
                    </div>
                    <div class="stat-info">
                        <h3><?php echo $usersWithOrders; ?></h3>
                        <p>Customers with Orders</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filter -->
    <div class="search-box">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="bi bi-search"></i>
                    </span>
                    <input type="text" class="form-control border-start-0" id="searchInput" placeholder="Search by name or email...">
                </div>
            </div>
            <div class="col-md-3">
                <select class="form-select" id="statusFilter">
                    <option value="">All Status</option>
                    <option value="active">Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" id="applyFilters">
                    <i class="bi bi-funnel-fill me-2"></i>Apply Filters
                </button>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="table-container">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>User</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $userData): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar bg-primary bg-opacity-10 text-primary me-3">
                                        <?php echo strtoupper(substr($userData['name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($userData['name']); ?></h6>
                                        <small class="text-muted">Created <?php echo date('M j, Y', strtotime($userData['created_at'])); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($userData['email']); ?></td>
                            <td>
                                <span class="badge bg-secondary"><?php echo ucfirst($userData['role']); ?></span>
                            </td>
                            <td>
                                <span class="status-badge <?php echo $userData['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                    <?php echo $userData['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <?php echo $userData['last_login'] ? date('M j, Y H:i', strtotime($userData['last_login'])) : 'Never'; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="action-button" title="Edit User">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="action-button toggle-status" 
                                            data-user-id="<?php echo $userData['id']; ?>" 
                                            data-current-status="<?php echo $userData['is_active']; ?>"
                                            title="<?php echo $userData['is_active'] ? 'Deactivate' : 'Activate'; ?> User">
                                        <i class="bi bi-power"></i>
                                    </button>
                                    <?php if ($userData['role'] !== 'admin'): ?>
                                        <button class="action-button text-danger" title="Delete User">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav class="d-flex justify-content-center mt-4">
                <ul class="pagination">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle user status
    document.querySelectorAll('.toggle-status').forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.dataset.userId;
            const currentStatus = this.dataset.currentStatus === '1';
            const newStatus = !currentStatus;
            
            if (confirm(`Are you sure you want to ${currentStatus ? 'deactivate' : 'activate'} this user?`)) {
                fetch('/football_tickets/ajax/toggle_user_status.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        user_id: userId,
                        status: newStatus
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Failed to update user status');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating user status');
                });
            }
        });
    });

    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const applyFiltersBtn = document.getElementById('applyFilters');

    applyFiltersBtn.addEventListener('click', function() {
        const searchQuery = searchInput.value.trim();
        const statusValue = statusFilter.value;
        
        let url = window.location.pathname;
        let params = new URLSearchParams();
        
        if (searchQuery) {
            params.append('search', searchQuery);
        }
        if (statusValue) {
            params.append('status', statusValue);
        }
        if (params.toString()) {
            url += '?' + params.toString();
        }
        
        window.location.href = url;
    });
});
</script>

<?php require_once 'includes/footer.php'; ?> 