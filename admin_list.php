<?php
session_start();
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';
require_once 'includes/audit_logger.php';

// Security checks
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Power Admin') {
    logSecurityEvent('unauthorized_access_attempt', ['page' => 'admin_list']);
    header('Location: login.php');
    exit();
}

if (!hasPermission('manage_users')) {
    logSecurityEvent('permission_denied', ['page' => 'admin_list']);
    header('Location: error_page.php?error=access_denied');
    exit();
}

// Rate limiting
if (!checkRateLimit('admin_list_view', 20, 300)) {
    logSecurityEvent('rate_limit_exceeded', ['action' => 'admin_list_view']);
    header('Location: error_page.php?error=rate_limit');
    exit();
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action'])) {
    // CSRF protection
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        logSecurityEvent('csrf_token_mismatch', ['page' => 'admin_list']);
        header('Location: error_page.php?error=csrf');
        exit();
    }
    
    $bulkAction = sanitizeInput($_POST['bulk_action'], 'string');
    $selectedIds = $_POST['selected_admins'] ?? [];
    
    if (!empty($selectedIds) && in_array($bulkAction, ['activate', 'deactivate', 'delete'])) {
        $processed = 0;
        
        foreach ($selectedIds as $adminId) {
            $adminId = (int)sanitizeInput($adminId, 'int');
            if ($adminId <= 0) continue;
            
            try {
                if ($bulkAction === 'delete') {
                    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND role != 'Power Admin'");
                    $stmt->bind_param('s', $adminId);
                    $stmt->execute();
                    $stmt->close();
                    
                    audit_data_modification('admin', $adminId, 'bulk_delete', null, ['action' => 'deleted']);
                } elseif ($bulkAction === 'activate') {
                    $stmt = $conn->prepare("UPDATE users SET status = 'Active' WHERE user_id = ?");
                    $stmt->bind_param('s', $adminId);
                    $stmt->execute();
                    $stmt->close();
                    
                    audit_data_modification('admin', $adminId, 'bulk_activate', null, ['status' => 'Active']);
                } elseif ($bulkAction === 'deactivate') {
                    $stmt = $conn->prepare("UPDATE users SET status = 'Inactive' WHERE user_id = ?");
                    $stmt->bind_param('s', $adminId);
                    $stmt->execute();
                    $stmt->close();
                    
                    audit_data_modification('admin', $adminId, 'bulk_deactivate', null, ['status' => 'Inactive']);
                }
                
                $processed++;
            } catch (Exception $e) {
                logSecurityEvent('database_error', ['error' => $e->getMessage()]);
                error_log("Database error in admin list bulk action: " . $e->getMessage());
            }
        }
        
        logSecurityEvent('bulk_action_performed', [
            'action' => $bulkAction,
            'processed_count' => $processed
        ]);
        
        $message = "Processed {$processed} admin(s) successfully.";
    }
}

// Fetch admins with proper filtering and pagination
$search = sanitizeInput($_GET['search'] ?? '', 'string');
$role = sanitizeInput($_GET['role'] ?? '', 'string');
$status = sanitizeInput($_GET['status'] ?? '', 'string');
$page = max(1, (int)sanitizeInput($_GET['page'] ?? 1, 'int'));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Build query
$sql = "SELECT user_id, first_name, last_name, email, phone, role, status, date_registered, last_login 
        FROM users 
        WHERE role IN ('Power Admin', 'Dormitory Admin', 'Guidance Admin', 'Scholarship Admin')";

$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR user_id LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= 'ssss';
}

if ($role !== '') {
    $where[] = "role = ?";
    $params[] = $role;
    $types .= 's';
}

if ($status !== '') {
    $where[] = "status = ?";
    $statusValue = $status === 'active' ? 'Active' : ($status === 'inactive' ? 'Inactive' : $status);
    $params[] = $statusValue;
    $types .= 's';
}

if (!empty($where)) {
    $sql .= ' AND ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY date_registered DESC LIMIT ? OFFSET ?';
$params[] = $perPage;
$params[] = $offset;
$types .= 'ii';

try {
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $admins = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Get total count for pagination
    $countSql = "SELECT COUNT(*) as total FROM users WHERE role IN ('Power Admin', 'Dormitory Admin', 'Guidance Admin', 'Scholarship Admin')";
    if (!empty($where)) {
        $countSql .= ' AND ' . implode(' AND ', $where);
    }
    
    $countStmt = $conn->prepare($countSql);
    if (!empty($params) && count($params) > 2) {
        $countParams = array_slice($params, 0, -2);
        $countTypes = substr($types, 0, -2);
        $countStmt->bind_param($countTypes, ...$countParams);
    }
    $countStmt->execute();
    $totalCount = $countStmt->get_result()->fetch_assoc()['total'];
    $countStmt->close();
    
    $totalPages = ceil($totalCount / $perPage);
    
} catch (Exception $e) {
    logSecurityEvent('database_error', ['error' => $e->getMessage()]);
    error_log("Database error in admin list: " . $e->getMessage());
    $admins = [];
    $totalPages = 0;
}

// Get role statistics
try {
    $roleStats = $conn->query("SELECT role, COUNT(*) as count FROM users WHERE role IN ('Power Admin', 'Dormitory Admin', 'Guidance Admin', 'Scholarship Admin') GROUP BY role");
    $roleCounts = [];
    if ($roleStats) {
        while ($row = $roleStats->fetch_assoc()) {
            $roleCounts[$row['role']] = $row['count'];
        }
    }
} catch (Exception $e) {
    $roleCounts = [];
}

audit_data_access('admin_list', null, 'view');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management - NEUST Power Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="assets/admin_theme.css">
    <style>
        .admin-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--space-lg);
            margin-bottom: var(--space-xl);
        }
        
        .stat-card {
            background: var(--bg-card);
            border-radius: var(--radius-xl);
            padding: var(--space-lg);
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-light);
            text-align: center;
            transition: all var(--transition-normal);
        }
        
        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
        }
        
        .stat-number {
            font-size: var(--font-size-3xl);
            font-weight: 700;
            color: var(--primary);
            margin-bottom: var(--space-xs);
        }
        
        .stat-label {
            font-size: var(--font-size-sm);
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        
        .filters-section {
            background: var(--bg-card);
            border-radius: var(--radius-xl);
            padding: var(--space-lg);
            margin-bottom: var(--space-lg);
            box-shadow: var(--shadow-sm);
        }
        
        .filters-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--space-md);
            align-items: end;
        }
        
        .bulk-actions {
            display: flex;
            gap: var(--space-sm);
            align-items: center;
            margin-bottom: var(--space-lg);
            padding: var(--space-md);
            background: var(--bg-secondary);
            border-radius: var(--radius-lg);
        }
        
        .admin-table {
            background: var(--bg-card);
            border-radius: var(--radius-xl);
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }
        
        .table-header {
            padding: var(--space-lg);
            border-bottom: 1px solid var(--border-light);
            background: linear-gradient(135deg, var(--bg-secondary), var(--bg-card));
        }
        
        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-full);
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-white);
            font-weight: 600;
            font-size: var(--font-size-sm);
        }
        
        .admin-info {
            display: flex;
            align-items: center;
            gap: var(--space-md);
        }
        
        .admin-details h4 {
            margin: 0;
            font-size: var(--font-size-sm);
            font-weight: 600;
            color: var(--text-primary);
        }
        
        .admin-details p {
            margin: 0;
            font-size: var(--font-size-xs);
            color: var(--text-muted);
        }
        
        .role-badge {
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
        
        .role-power-admin {
            background: var(--error-light);
            color: var(--error);
        }
        
        .role-admin {
            background: var(--warning-light);
            color: var(--warning);
        }
        
        .role-dormitory {
            background: var(--info-light);
            color: var(--info);
        }
        
        .role-guidance {
            background: var(--success-light);
            color: var(--success);
        }
        
        .role-registrar {
            background: var(--secondary-light);
            color: var(--primary);
        }
        
        .role-scholarship {
            background: var(--primary-light);
            color: var(--primary);
        }
        
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: var(--space-xs);
            padding: var(--space-xs) var(--space-sm);
            border-radius: var(--radius-full);
            font-size: var(--font-size-xs);
            font-weight: 600;
        }
        
        .status-active {
            background: var(--success-light);
            color: var(--success);
        }
        
        .status-inactive {
            background: var(--error-light);
            color: var(--error);
        }
        
        .action-buttons {
            display: flex;
            gap: var(--space-xs);
        }
        
        .action-btn {
            padding: var(--space-xs) var(--space-sm);
            border: none;
            border-radius: var(--radius-md);
            font-size: var(--font-size-xs);
            cursor: pointer;
            transition: all var(--transition-fast);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: var(--space-xs);
        }
        
        .action-btn.edit {
            background: var(--info-light);
            color: var(--info);
        }
        
        .action-btn.edit:hover {
            background: var(--info);
            color: var(--text-white);
        }
        
        .action-btn.delete {
            background: var(--error-light);
            color: var(--error);
        }
        
        .action-btn.delete:hover {
            background: var(--error);
            color: var(--text-white);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: var(--space-sm);
            margin-top: var(--space-lg);
            padding: var(--space-md);
        }
        
        .pagination button {
            padding: var(--space-sm) var(--space-md);
            border: 1px solid var(--border-light);
            background: var(--bg-card);
            color: var(--text-primary);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all var(--transition-fast);
        }
        
        .pagination button:hover:not(:disabled) {
            background: var(--primary);
            color: var(--text-white);
            border-color: var(--primary);
        }
        
        .pagination button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .pagination .current-page {
            background: var(--primary);
            color: var(--text-white);
            border-color: var(--primary);
        }
        
        @media (max-width: 768px) {
            .filters-grid {
                grid-template-columns: 1fr;
            }
            
            .bulk-actions {
                flex-direction: column;
                align-items: stretch;
            }
            
            .admin-stats {
                grid-template-columns: repeat(2, 1fr);
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
                <i class="fas fa-user-shield"></i>
                Admin Management
            </h1>
            <div class="page-actions">
                <a class="btn" href="add_admin.php">
                    <i class="fas fa-user-plus"></i> Add New Admin
                </a>
                <button class="btn btn-success" onclick="refreshData()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>

        <!-- Display Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
          <div class="alert alert-success" style="margin-bottom: var(--space-lg); padding: var(--space-md); background: var(--success-light); color: var(--success); border: 1px solid var(--success); border-radius: var(--radius-md);">
            <i class="fas fa-check-circle"></i>
            <?= htmlspecialchars($_SESSION['success_message']) ?>
          </div>
          <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
          <div class="alert alert-error" style="margin-bottom: var(--space-lg); padding: var(--space-md); background: var(--error-light); color: var(--error); border: 1px solid var(--error); border-radius: var(--radius-md);">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($_SESSION['error_message']) ?>
          </div>
          <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="admin-stats">
            <div class="stat-card">
                <div class="stat-number"><?= count($admins) ?></div>
                <div class="stat-label">Total Admins</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $roleCounts['Power Admin'] ?? 0 ?></div>
                <div class="stat-label">Power Admins</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= ($roleCounts['Dormitory Admin'] ?? 0) + ($roleCounts['Guidance Admin'] ?? 0) + ($roleCounts['Scholarship Admin'] ?? 0) ?></div>
                <div class="stat-label">Regular Admins</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $totalPages ?></div>
                <div class="stat-label">Total Pages</div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="filters-section">
            <h3 class="chart-title">
                <i class="fas fa-filter"></i>
                Filter & Search Admins
            </h3>
            <form class="filters-grid" method="get">
                <div class="filter-group">
                    <label>Search</label>
                    <input class="input" type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name, email, or ID...">
                </div>
                <div class="filter-group">
                    <label>Role</label>
                    <select class="input" name="role">
                        <option value="">All Roles</option>
                        <option value="Power Admin" <?= $role === 'Power Admin' ? 'selected' : '' ?>>Power Admin</option>
                        <option value="Dormitory Admin" <?= $role === 'Dormitory Admin' ? 'selected' : '' ?>>Dormitory Admin</option>
                        <option value="Guidance Admin" <?= $role === 'Guidance Admin' ? 'selected' : '' ?>>Guidance Admin</option>
                        <option value="Scholarship Admin" <?= $role === 'Scholarship Admin' ? 'selected' : '' ?>>Scholarship Admin</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Status</label>
                    <select class="input" name="status">
                        <option value="">All Status</option>
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label>&nbsp;</label>
                    <div class="filter-actions">
                        <button class="btn" type="submit">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a class="btn btn-ghost" href="admin_list.php">
                            <i class="fas fa-times"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Bulk Actions -->
        <form method="post" class="bulk-actions">
            <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
            <div style="display: flex; align-items: center; gap: var(--space-sm);">
                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                <label for="selectAll">Select All</label>
            </div>
            <select class="input" name="bulk_action" style="width: auto;">
                <option value="">Bulk Actions</option>
                <option value="activate">Activate Selected</option>
                <option value="deactivate">Deactivate Selected</option>
                <option value="delete">Delete Selected</option>
            </select>
            <button class="btn btn-warning" type="submit" onclick="return confirm('Are you sure you want to perform this bulk action?')">
                <i class="fas fa-tasks"></i> Apply
            </button>
        </form>

        <!-- Admins Table -->
        <div class="admin-table">
            <div class="table-header">
                <h3 class="chart-title">
                    <i class="fas fa-users"></i>
                    Admin List
                </h3>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 50px;">
                                <input type="checkbox" id="selectAllTable" onchange="toggleSelectAll()">
                            </th>
                            <th>Admin</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $admin): ?>
                        <tr>
                            <td>
                                <input type="checkbox" name="selected_admins[]" value="<?= htmlspecialchars($admin['user_id']) ?>" class="admin-checkbox">
                            </td>
                            <td>
                                <div class="admin-info">
                                    <div class="admin-avatar">
                                        <?= strtoupper(substr($admin['first_name'], 0, 1) . substr($admin['last_name'], 0, 1)) ?>
                                    </div>
                                    <div class="admin-details">
                                        <h4><?= htmlspecialchars($admin['first_name'] . ' ' . $admin['last_name']) ?></h4>
                                        <p><?= htmlspecialchars($admin['user_id']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="role-badge role-<?= strtolower(str_replace(' ', '-', $admin['role'])) ?>">
                                    <i class="fas fa-user-shield"></i>
                                    <?= htmlspecialchars($admin['role']) ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-indicator status-<?= strtolower($admin['status'] ?? 'Active') ?>">
                                    <i class="fas fa-circle"></i>
                                    <?= $admin['status'] ?? 'Active' ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($admin['email']) ?></td>
                            <td><?= htmlspecialchars($admin['phone']) ?></td>
                            <td><?= $admin['last_login'] ? format_date($admin['last_login']) : 'Never' ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="edit_admin.php?id=<?= urlencode($admin['user_id']) ?>" class="action-btn edit">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <?php if ($admin['role'] !== 'Power Admin'): ?>
                                    <a href="delete_admin.php?id=<?= urlencode($admin['user_id']) ?>" 
                                       class="action-btn delete" 
                                       onclick="return confirm('Are you sure you want to delete this admin?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($admins)): ?>
                        <tr>
                            <td colspan="8" class="text-center">
                                <div class="empty-state">
                                    <i class="fas fa-users"></i>
                                    <h3>No admins found</h3>
                                    <p>Try adjusting your filters or add a new admin.</p>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <button <?= $page <= 1 ? 'disabled' : '' ?> onclick="goToPage(<?= $page - 1 ?>)">
                <i class="fas fa-chevron-left"></i> Previous
            </button>
            
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
            <button class="<?= $i === $page ? 'current-page' : '' ?>" onclick="goToPage(<?= $i ?>)">
                <?= $i ?>
            </button>
            <?php endfor; ?>
            
            <button <?= $page >= $totalPages ? 'disabled' : '' ?> onclick="goToPage(<?= $page + 1 ?>)">
                Next <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        <?php endif; ?>
    </main>

    <script>
    function toggleSelectAll() {
        const selectAll = document.getElementById('selectAll');
        const selectAllTable = document.getElementById('selectAllTable');
        const checkboxes = document.querySelectorAll('.admin-checkbox');
        
        const isChecked = selectAll.checked || selectAllTable.checked;
        
        checkboxes.forEach(checkbox => {
            checkbox.checked = isChecked;
        });
        
        selectAll.checked = isChecked;
        selectAllTable.checked = isChecked;
    }
    
    function goToPage(page) {
        const url = new URL(window.location);
        url.searchParams.set('page', page);
        window.location.href = url.toString();
    }
    
    function refreshData() {
        window.location.reload();
    }
    
    // Auto-refresh every 5 minutes
    setTimeout(function() {
        window.location.reload();
    }, 300000);
    </script>
</body>
</html>