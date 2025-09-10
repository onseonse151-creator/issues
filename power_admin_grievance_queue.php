<?php
session_start();
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';

// Security checks
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Power Admin') {
    logSecurityEvent('unauthorized_access_attempt', ['page' => 'grievance_queue']);
    header('Location: login.php');
    exit();
}

if (!hasPermission('manage_grievances')) {
    logSecurityEvent('permission_denied', ['page' => 'grievance_queue']);
    header('Location: error_page.php?error=access_denied');
    exit();
}

// Rate limiting
if (!checkRateLimit('grievance_view', 30, 300)) {
    logSecurityEvent('rate_limit_exceeded', ['action' => 'grievance_view']);
    header('Location: error_page.php?error=rate_limit');
    exit();
}

// Sanitize and validate inputs
$q = sanitizeInput($_GET['q'] ?? '', 'string');
$status = sanitizeInput($_GET['status'] ?? '', 'string');
$severity = sanitizeInput($_GET['severity'] ?? '', 'string');
$category_id = isset($_GET['category_id']) && ctype_digit((string)$_GET['category_id']) ? (int)sanitizeInput($_GET['category_id'], 'int') : null;

$hasCategoryIdCol = db_has_column($conn, 'grievances', 'category_id');
$hasSeverityCol = db_has_column($conn, 'grievances', 'severity');
$hasAssignedCol = db_has_column($conn, 'grievances', 'assigned_to_user_id');

// Categories for filter
$categories = [];
if ($hasCategoryIdCol && db_table_exists($conn, 'grievance_categories')) {
    try {
        $res = $conn->query("SELECT id, name FROM grievance_categories ORDER BY name");
        if ($res) { 
            $categories = $res->fetch_all(MYSQLI_ASSOC); 
        }
    } catch (Exception $e) {
        logSecurityEvent('database_error', ['error' => $e->getMessage()]);
        error_log("Database error in grievance queue: " . $e->getMessage());
    }
}

// Build query with proper parameterization
$sql = "SELECT g.id, g.title, g.status, g.submission_date, g.resolution_date, g.user_id" .
       ($hasSeverityCol ? ", g.severity" : "") .
       ($hasAssignedCol ? ", g.assigned_to_user_id" : "") .
       ($hasCategoryIdCol ? ", gc.name AS category_name" : ", g.category AS category_name") .
       " FROM grievances g " .
       ($hasCategoryIdCol ? "LEFT JOIN grievance_categories gc ON g.category_id = gc.id " : "");

$where = [];
$params = [];
$types = '';

if ($q !== '') {
    $where[] = "(g.title LIKE ? OR g.description LIKE ? OR g.user_id LIKE ?)";
    $like = "%$q%";
    $params[] = $like; 
    $params[] = $like; 
    $params[] = $like; 
    $types .= 'sss';
}

if ($status !== '') {
    $where[] = "g.status = ?"; 
    $params[] = $status; 
    $types .= 's';
}

if ($hasSeverityCol && $severity !== '') {
    $where[] = "g.severity = ?"; 
    $params[] = $severity; 
    $types .= 's';
}

if ($hasCategoryIdCol && $category_id) {
    $where[] = "g.category_id = ?"; 
    $params[] = $category_id; 
    $types .= 'i';
}

if (!empty($where)) { 
    $sql .= ' WHERE ' . implode(' AND ', $where); 
}

$sql .= ' ORDER BY g.submission_date DESC LIMIT 200';

try {
    $stmt = $conn->prepare($sql);
    if (!empty($params)) { 
        $stmt->bind_param($types, ...$params); 
    }
    $stmt->execute();
    $res = $stmt->get_result();
    $rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
} catch (Exception $e) {
    logSecurityEvent('database_error', ['error' => $e->getMessage()]);
    error_log("Database error in grievance queue: " . $e->getMessage());
    $rows = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grievance Queue</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="assets/admin_theme.css">
    <style>
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
        
        .chart-title {
            font-size: var(--font-size-lg);
            font-weight: 600;
            color: var(--primary);
            margin-bottom: var(--space-lg);
            display: flex;
            align-items: center;
            gap: var(--space-sm);
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
        
        .filter-actions {
            display: flex;
            gap: var(--space-sm);
        }
        
        .right { text-align: right; }
        
        .hint { 
            color: var(--text-muted);
            font-size: var(--font-size-sm);
            margin-top: var(--space-md);
            padding: var(--space-sm) var(--space-md);
            background: var(--info-light);
            border-radius: var(--radius-md);
            border-left: 4px solid var(--info);
        }
        
        .hint i {
            color: var(--info);
            margin-right: var(--space-xs);
        }
        
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: var(--space-md);
            }
            
            .filters {
                grid-template-columns: 1fr;
            }
            
            .filter-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
<div class="layout">
<?php include 'power_admin_header.php'; ?>
    <main class="main">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fas fa-exclamation-triangle"></i>
                Grievance Management Queue
            </h1>
            <div class="page-actions">
                <button class="btn btn-success" onclick="refreshData()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
                <a class="btn" href="power_admin_reports.php">
                    <i class="fas fa-chart-line"></i> Reports
                </a>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="card">
            <h3 class="chart-title">
                <i class="fas fa-filter"></i>
                Filter & Search Grievances
            </h3>
            <form class="filters" method="get">
                <div class="filter-group">
                    <label>Search</label>
                    <input class="input" type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search title, description, or user...">
                </div>
                <div class="filter-group">
                    <label>Status</label>
                    <select class="input" name="status">
                        <option value="">All Status</option>
                        <?php $statuses = ['pending','acknowledged','info_requested','assigned','in_progress','resolved','rejected','escalated','closed','reopened','withdrawn'];
                        foreach ($statuses as $s): ?>
                            <option value="<?= $s ?>"<?= $status===$s?' selected':'' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Severity</label>
                    <select class="input" name="severity" <?= $hasSeverityCol ? '' : 'disabled' ?>>
                        <option value="">All Severity</option>
                        <?php foreach (['low','medium','high','critical'] as $sev): ?>
                            <option value="<?= $sev ?>"<?= $severity===$sev?' selected':'' ?>><?= ucfirst($sev) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Category</label>
                    <select class="input" name="category_id" <?= $hasCategoryIdCol ? '' : 'disabled' ?>>
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int)$c['id'] ?>"<?= ($category_id===(int)$c['id'])?' selected':'' ?>><?= htmlspecialchars($c['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>&nbsp;</label>
                    <div class="filter-actions">
                        <button class="btn" type="submit">
                            <i class="fas fa-search"></i> Filter
                        </button>
                        <a class="btn btn-ghost" href="power_admin_grievance_queue.php">
                            <i class="fas fa-times"></i> Reset
                        </a>
                    </div>
                </div>
            </form>
            <?php if (!$hasSeverityCol || !$hasCategoryIdCol): ?>
            <div class="hint">
                <i class="fas fa-info-circle"></i>
                Tip: For enhanced fields (severity, categories, assignment), run the migration at <code>migrate_grievances_web.php</code>.
            </div>
            <?php endif; ?>
        </div>

        <!-- Grievances Table -->
        <div class="card">
            <h3 class="chart-title">
                <i class="fas fa-list"></i>
                Grievance List
            </h3>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Status</th>
                            <th>Severity</th>
                            <th>Category</th>
                            <th>Submitted</th>
                            <th>Assigned</th>
                            <th class="right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $r): $st = strtolower($r['status']); ?>
                        <tr>
                            <td>#<?= (int)$r['id'] ?></td>
                            <td><?= htmlspecialchars($r['title']) ?></td>
                            <td><span class="badge <?= get_status_badge_class($r['status']) ?>"><?= htmlspecialchars(ucfirst($st)) ?></span></td>
                            <td><?= htmlspecialchars($r['severity'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($r['category_name'] ?? '—') ?></td>
                            <td><?= format_date($r['submission_date']) ?></td>
                            <td><?= htmlspecialchars($r['assigned_to_user_id'] ?? '—') ?></td>
                            <td class="right">
                                <a class="btn" href="power_admin_grievance_workbench.php?id=<?= (int)$r['id'] ?>">
                                    <i class="fas fa-tools"></i> Open
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="8" class="text-center">
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <h3>No grievances found</h3>
                                    <p>Try adjusting your filters or check back later.</p>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
    function refreshData() {
        location.reload();
    }
    </script>
</body>
</html>