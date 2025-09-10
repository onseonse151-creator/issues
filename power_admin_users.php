<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Power Admin') { 
    header('Location: login.php'); 
    exit(); 
}

// Pagination logic
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$query = "SELECT * FROM users";
$totalQuery = "SELECT COUNT(*) as total FROM users";
$totalResult = $conn->query($totalQuery);
$totalRow = $totalResult->fetch_assoc();
$totalRows = (int)$totalRow['total'];
$totalPages = max(1, (int)ceil($totalRows / $limit));

$query = "SELECT * FROM users LIMIT $limit OFFSET $offset";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Power Admin · Users</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="assets/admin_theme.css">
    <style>
        /* Modern User Management Styles */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space-xl);
            padding-bottom: var(--space-lg);
            border-bottom: 1px solid var(--border-light);
        }
        
        .page-title {
            font-size: var(--font-size-3xl);
            font-weight: 700;
            color: var(--primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: var(--space-md);
        }
        
        .page-title i {
            color: var(--secondary);
            font-size: var(--font-size-2xl);
        }
        
        .page-actions {
            display: flex;
            gap: var(--space-md);
            align-items: center;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--space-lg);
            margin-bottom: var(--space-xl);
        }
        
        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-xl);
            padding: var(--space-lg);
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: all var(--transition-normal);
            box-shadow: var(--shadow-sm);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--secondary), var(--primary));
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--space-md);
            font-size: var(--font-size-xl);
            color: var(--text-white);
        }
        
        .stat-icon.users { background: linear-gradient(135deg, var(--primary), var(--primary-light)); }
        .stat-icon.active { background: linear-gradient(135deg, var(--success), #059669); }
        .stat-icon.inactive { background: linear-gradient(135deg, var(--error), #DC2626); }
        .stat-icon.admin { background: linear-gradient(135deg, var(--secondary), var(--secondary-dark)); }
        
        .stat-number {
            font-size: var(--font-size-3xl);
            font-weight: 700;
            color: var(--primary);
            margin-bottom: var(--space-xs);
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: var(--font-size-sm);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 500;
        }
        
        .controls-section {
            background: var(--bg-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-xl);
            padding: var(--space-lg);
            margin-bottom: var(--space-xl);
            box-shadow: var(--shadow-sm);
        }
        
        .search-bar {
            display: flex;
            gap: var(--space-md);
            margin-bottom: var(--space-lg);
            align-items: center;
        }
        
        .search-input {
            flex: 1;
            position: relative;
        }
        
        .search-input i {
            position: absolute;
            left: var(--space-md);
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            z-index: 1;
        }
        
        .search-input input {
            padding-left: calc(var(--space-md) + var(--space-lg));
        }
        
        .filters {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--space-md);
            align-items: end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: var(--space-xs);
        }
        
        .filter-group label {
            font-size: var(--font-size-sm);
            font-weight: 500;
            color: var(--text-primary);
        }
        
        .user-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: var(--space-lg);
            margin-top: var(--space-lg);
        }
        
        .user-card {
            background: var(--bg-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-xl);
            padding: var(--space-lg);
            transition: all var(--transition-normal);
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }
        
        .user-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--secondary), var(--primary));
            opacity: 0;
            transition: opacity var(--transition-fast);
        }
        
        .user-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--secondary);
        }
        
        .user-card:hover::before {
            opacity: 1;
        }
        
        .user-header {
            display: flex;
            align-items: center;
            gap: var(--space-md);
            margin-bottom: var(--space-lg);
            padding-bottom: var(--space-md);
            border-bottom: 1px solid var(--border-light);
        }
        
        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: var(--radius-full);
            object-fit: cover;
            border: 3px solid var(--secondary);
            box-shadow: var(--shadow-md);
            transition: all var(--transition-fast);
        }
        
        .user-card:hover .user-avatar {
            transform: scale(1.05);
            border-color: var(--primary);
        }
        
        .user-info h3 {
            margin: 0 0 var(--space-xs);
            color: var(--primary);
            font-size: var(--font-size-lg);
            font-weight: 600;
        }
        
        .user-info p {
            margin: 0;
            color: var(--text-secondary);
            font-size: var(--font-size-sm);
        }
        
        .user-details {
            margin-bottom: var(--space-lg);
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space-sm);
            padding: var(--space-sm) 0;
            border-bottom: 1px solid var(--border-light);
        }
        
        .detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        
        .detail-label {
            font-size: var(--font-size-sm);
            font-weight: 500;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: var(--space-xs);
        }
        
        .detail-value {
            font-size: var(--font-size-sm);
            color: var(--text-primary);
            font-weight: 500;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: var(--space-xs);
            padding: var(--space-xs) var(--space-sm);
            border-radius: var(--radius-full);
            font-size: var(--font-size-xs);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.025em;
        }
        
        .status-active {
            background: var(--success-light);
            color: var(--success);
        }
        
        .status-inactive {
            background: var(--error-light);
            color: var(--error);
        }
        
        .user-actions {
            display: flex;
            gap: var(--space-sm);
            margin-top: var(--space-lg);
            padding-top: var(--space-md);
            border-top: 1px solid var(--border-light);
        }
        
        .user-actions .btn {
            flex: 1;
            font-size: var(--font-size-xs);
            padding: var(--space-sm) var(--space-md);
            border-radius: var(--radius-md);
            transition: all var(--transition-fast);
        }
        
        .user-actions .btn:hover {
            transform: translateY(-1px);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: var(--space-sm);
            margin-top: var(--space-xl);
            padding-top: var(--space-lg);
            border-top: 1px solid var(--border-light);
        }
        
        .pagination .btn {
            min-width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: var(--radius-md);
        }
        
        .empty-state {
            text-align: center;
            padding: var(--space-2xl);
            color: var(--text-muted);
        }
        
        .empty-state i {
            font-size: var(--font-size-3xl);
            margin-bottom: var(--space-md);
            color: var(--text-muted);
        }
        
        .empty-state h3 {
            margin-bottom: var(--space-sm);
            color: var(--text-secondary);
        }
        
        .empty-state p {
            margin: 0;
            font-size: var(--font-size-sm);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .user-grid {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .filters {
                grid-template-columns: 1fr;
            }
            
            .search-bar {
                flex-direction: column;
                align-items: stretch;
            }
        }
    </style>
</head>
<body>
<?php include 'power_admin_header.php'; ?>
    <main class="main">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-users"></i>
                User Management
            </h1>
            <div class="page-actions">
                <button class="btn" onclick="exportUsers()">
                    <i class="fas fa-download"></i> Export Users
                </button>
                <button class="btn btn-success">
                    <i class="fas fa-user-plus"></i> Add User
                </button>
            </div>
        </div>
        
        <!-- Statistics Cards -->
        <?php
        $totalUsers = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
        $activeUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'Active'")->fetch_assoc()['count'];
        $inactiveUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'Inactive'")->fetch_assoc()['count'];
        $adminUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role LIKE '%Admin%'")->fetch_assoc()['count'];
        ?>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon users">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-number"><?= $totalUsers ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon active">
                    <i class="fas fa-user-check"></i>
                </div>
                <div class="stat-number"><?= $activeUsers ?></div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon inactive">
                    <i class="fas fa-user-times"></i>
                </div>
                <div class="stat-number"><?= $inactiveUsers ?></div>
                <div class="stat-label">Inactive Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon admin">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="stat-number"><?= $adminUsers ?></div>
                <div class="stat-label">Admin Users</div>
            </div>
        </div>

        <!-- Controls Section -->
        <div class="controls-section">
            <!-- Search Bar -->
            <div class="search-bar">
                <div class="search-input">
                    <i class="fas fa-search"></i>
                    <input class="input" type="search" id="searchUsers" placeholder="Search users by name, email, or role...">
                </div>
                <button class="btn" onclick="exportUsers()">
                    <i class="fas fa-download"></i> Export
                </button>
            </div>
            
            <!-- Filters -->
            <div class="filters">
                <div class="filter-group">
                    <label>Filter by Status</label>
                    <select class="input" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Filter by Role</label>
                    <select class="input" id="roleFilter">
                        <option value="">All Roles</option>
                        <option value="Student">Student</option>
                        <option value="Admin">Admin</option>
                        <option value="Power Admin">Power Admin</option>
                        <option value="Guidance">Guidance</option>
                        <option value="Registrar">Registrar</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Sort by</label>
                    <select class="input" id="sortBy">
                        <option value="name">Name</option>
                        <option value="email">Email</option>
                        <option value="role">Role</option>
                        <option value="date_registered">Registration Date</option>
                        <option value="last_login">Last Login</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- User Cards -->
        <div class="user-grid" id="userGrid">
            <?php while ($row = $result->fetch_assoc()): ?>
            <div class="user-card" data-name="<?= htmlspecialchars(strtolower($row['first_name'] . ' ' . $row['last_name'])) ?>" 
                 data-email="<?= htmlspecialchars(strtolower($row['email'])) ?>" 
                 data-role="<?= htmlspecialchars(strtolower($row['role'])) ?>" 
                 data-status="<?= htmlspecialchars(strtolower($row['status'])) ?>">
                
                <div class="user-header">
                    <img src="<?= !empty($row['profile_picture']) ? htmlspecialchars($row['profile_picture']) : 'assets/default_profile.png' ?>" 
                         alt="Profile" class="user-avatar">
                    <div class="user-info">
                        <h3><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></h3>
                        <p><?= htmlspecialchars($row['email']) ?></p>
                    </div>
                </div>
                
                <div class="user-details">
                    <div class="detail-row">
                        <div class="detail-label">
                            <i class="fas fa-user-tag"></i>
                            Role
                        </div>
                        <div class="detail-value"><?= htmlspecialchars($row['role']) ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">
                            <i class="fas fa-phone"></i>
                            Phone
                        </div>
                        <div class="detail-value"><?= htmlspecialchars($row['phone'] ?: 'Not provided') ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">
                            <i class="fas fa-building"></i>
                            Unit
                        </div>
                        <div class="detail-value"><?= htmlspecialchars($row['unit'] ?: 'Not assigned') ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">
                            <i class="fas fa-circle"></i>
                            Status
                        </div>
                        <div class="detail-value">
                            <span class="status-badge status-<?= strtolower($row['status']) ?>"><?= htmlspecialchars($row['status']) ?></span>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">
                            <i class="fas fa-clock"></i>
                            Last Login
                        </div>
                        <div class="detail-value"><?= $row['last_login'] ? date('M d, Y', strtotime($row['last_login'])) : 'Never' ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">
                            <i class="fas fa-calendar-plus"></i>
                            Registered
                        </div>
                        <div class="detail-value"><?= date('M d, Y', strtotime($row['date_registered'])) ?></div>
                    </div>
                </div>
                
                <div class="user-actions">
                    <form action="update_status.php" method="POST" style="display:inline;">
                        <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                        <input type="hidden" name="new_status" value="<?= ($row['status'] == 'Active') ? 'Inactive' : 'Active' ?>">
                        <button type="submit" class="btn <?= ($row['status'] == 'Active') ? 'btn-warning' : 'btn-success' ?>">
                            <i class="fas <?= ($row['status'] == 'Active') ? 'fa-user-times' : 'fa-user-check' ?>"></i>
                            <?= ($row['status'] == 'Active') ? 'Deactivate' : 'Activate' ?>
                        </button>
                    </form>
                    <form action="reset_password.php" method="POST" style="display:inline;">
                        <input type="hidden" name="user_id" value="<?= $row['user_id'] ?>">
                        <button type="submit" class="btn">
                            <i class="fas fa-key"></i>
                            Reset Password
                        </button>
                    </form>
                </div>
            </div>
            <?php endwhile; ?>
        </div>

        <!-- Pagination -->
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a class="btn btn-ghost" href="?page=<?= $page - 1 ?>">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <a class="btn <?= ($i == $page) ? '' : 'btn-ghost' ?>" href="?page=<?= $i ?>"><?= $i ?></a>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <a class="btn btn-ghost" href="?page=<?= $page + 1 ?>">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    </main>

    <script>
    (function() {
        const searchInput = document.getElementById('searchUsers');
        const statusFilter = document.getElementById('statusFilter');
        const roleFilter = document.getElementById('roleFilter');
        const sortBy = document.getElementById('sortBy');
        const userGrid = document.getElementById('userGrid');
        
        function filterUsers() {
            const searchTerm = searchInput.value.toLowerCase();
            const statusValue = statusFilter.value.toLowerCase();
            const roleValue = roleFilter.value.toLowerCase();
            const sortValue = sortBy.value;
            
            const cards = Array.from(userGrid.querySelectorAll('.user-card'));
            
            // Filter cards
            cards.forEach(card => {
                const name = card.getAttribute('data-name');
                const email = card.getAttribute('data-email');
                const role = card.getAttribute('data-role');
                const status = card.getAttribute('data-status');
                
                const matchesSearch = !searchTerm || 
                    name.includes(searchTerm) || 
                    email.includes(searchTerm) || 
                    role.includes(searchTerm);
                
                const matchesStatus = !statusValue || status === statusValue;
                const matchesRole = !roleValue || role === roleValue;
                
                if (matchesSearch && matchesStatus && matchesRole) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Sort visible cards
            const visibleCards = cards.filter(card => card.style.display !== 'none');
            visibleCards.sort((a, b) => {
                let aValue, bValue;
                
                switch(sortValue) {
                    case 'name':
                        aValue = a.getAttribute('data-name');
                        bValue = b.getAttribute('data-name');
                        break;
                    case 'email':
                        aValue = a.getAttribute('data-email');
                        bValue = b.getAttribute('data-email');
                        break;
                    case 'role':
                        aValue = a.getAttribute('data-role');
                        bValue = b.getAttribute('data-role');
                        break;
                    default:
                        return 0;
                }
                
                return aValue.localeCompare(bValue);
            });
            
            // Reorder cards in DOM
            visibleCards.forEach(card => userGrid.appendChild(card));
        }
        
        [searchInput, statusFilter, roleFilter, sortBy].forEach(element => {
            element.addEventListener('input', filterUsers);
            element.addEventListener('change', filterUsers);
        });
        
        function exportUsers() {
            // Simple CSV export functionality
            const cards = Array.from(userGrid.querySelectorAll('.user-card'));
            const csvContent = [
                ['Name', 'Email', 'Role', 'Status', 'Phone', 'Unit', 'Last Login', 'Registered'],
                ...cards.map(card => {
                    const name = card.querySelector('h3').textContent;
                    const email = card.querySelector('.user-info p').textContent;
                    const role = card.querySelector('.user-details div:nth-child(1) span').textContent;
                    const status = card.querySelector('.status-badge').textContent;
                    const phone = card.querySelector('.user-details div:nth-child(2) span').textContent;
                    const unit = card.querySelector('.user-details div:nth-child(3) span').textContent;
                    const lastLogin = card.querySelector('.user-details div:nth-child(5) span').textContent;
                    const registered = card.querySelector('.user-details div:nth-child(6) span').textContent;
                    
                    return [name, email, role, status, phone, unit, lastLogin, registered];
                })
            ].map(row => row.map(cell => `"${cell}"`).join(',')).join('\n');
            
            const blob = new Blob([csvContent], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'users_export.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }
        
        // Initial filter
        filterUsers();
    })();
    </script>
</body>
</html>