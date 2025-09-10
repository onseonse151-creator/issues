<?php
session_start();
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';

// Security checks
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Power Admin') {
    logSecurityEvent('unauthorized_access_attempt', ['page' => 'manage_grievances']);
    header('Location: login.php');
    exit();
}

if (!hasPermission('manage_grievances')) {
    logSecurityEvent('permission_denied', ['page' => 'manage_grievances']);
    header('Location: error_page.php?error=access_denied');
    exit();
}

// Rate limiting
if (!checkRateLimit('grievance_management', 20, 300)) {
    logSecurityEvent('rate_limit_exceeded', ['action' => 'grievance_management']);
    header('Location: error_page.php?error=rate_limit');
    exit();
}

// Handle actions (resolve, reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['id'])) {
    // CSRF protection
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        logSecurityEvent('csrf_token_mismatch', ['page' => 'manage_grievances']);
        header('Location: error_page.php?error=csrf');
        exit();
    }
    
    $action = sanitizeInput($_POST['action'], 'string');
    $id = (int)sanitizeInput($_POST['id'], 'int');
    
    // Validate action
    $allowedActions = ['resolve', 'reject'];
    if (!in_array($action, $allowedActions)) {
        logSecurityEvent('invalid_action_attempt', ['action' => $action]);
        header('Location: error_page.php?error=invalid_action');
        exit();
    }

    try {
        if ($action === 'resolve') {
            $stmt = $conn->prepare("UPDATE grievances SET status = 'resolved', resolution_date = NOW() WHERE id = ?");
        } elseif ($action === 'reject') {
            $stmt = $conn->prepare("UPDATE grievances SET status = 'rejected', resolution_date = NOW() WHERE id = ?");
        }

        if (isset($stmt)) {
            $stmt->bind_param("i", $id);
            $stmt->execute();

            // Fetch user email to notify
            $userStmt = $conn->prepare("SELECT u.email FROM grievances g JOIN users u ON g.user_id = u.user_id WHERE g.id = ?");
            $userStmt->bind_param("i", $id);
            $userStmt->execute();
            $userStmt->bind_result($email);
            $userStmt->fetch();
            $userStmt->close();

            // Send notification email
            $subject = "Grievance Status Updated";
            $message = "Your grievance (ID: $id) has been " . ($action === 'resolve' ? 'resolved' : 'rejected') . ".";
            $headers = "From: no-reply@yourdomain.com";
            mail($email, $subject, $message, $headers);

            $stmt->close();
            
            logSecurityEvent('grievance_action_performed', [
                'action' => $action,
                'grievance_id' => $id,
                'admin_id' => $_SESSION['user_id'] ?? 'unknown'
            ]);
        }
    } catch (Exception $e) {
        logSecurityEvent('database_error', [
            'action' => $action,
            'error' => $e->getMessage()
        ]);
        error_log("Database error in manage grievances: " . $e->getMessage());
    }
}

// Fetch all grievances with proper error handling
try {
    $result = $conn->query("SELECT g.id, g.title, g.description, g.status, g.submission_date, g.resolution_date, u.first_name, u.last_name FROM grievances g JOIN users u ON g.user_id = u.user_id ORDER BY g.submission_date DESC");
} catch (Exception $e) {
    logSecurityEvent('database_error', ['error' => $e->getMessage()]);
    error_log("Database error in manage grievances fetch: " . $e->getMessage());
    $result = false;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Power Admin · Manage Grievances</title>
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
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .action-buttons {
            display: flex;
            gap: var(--space-sm);
            flex-wrap: wrap;
        }
        
        .action-buttons .btn {
            font-size: var(--font-size-xs);
            padding: var(--space-xs) var(--space-sm);
        }
        
        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: var(--space-md);
            }
            
            .action-buttons {
                flex-direction: column;
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
                <i class="fas fa-tools"></i>
                Manage Grievances
            </h1>
            <div class="page-actions">
                <a class="btn" href="power_admin_grievance_queue.php">
                    <i class="fas fa-list"></i> View Queue
                </a>
                <button class="btn btn-success" onclick="refreshData()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
        </div>

        <!-- Grievances Table -->
        <div class="card">
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Status</th>
                            <th>Submitted By</th>
                            <th>Submission Date</th>
                            <th>Resolution Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>#<?= (int)$row['id'] ?></td>
                                <td><?= htmlspecialchars($row['title']) ?></td>
                                <td><?= htmlspecialchars(substr($row['description'], 0, 100)) ?><?= strlen($row['description']) > 100 ? '...' : '' ?></td>
                                <td>
                                    <span class="badge <?= get_status_badge_class($row['status']) ?>">
                                        <?= ucfirst($row['status']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($row['first_name'] . ' ' . $row['last_name']) ?></td>
                                <td><?= format_date($row['submission_date']) ?></td>
                                <td><?= $row['resolution_date'] ? format_date($row['resolution_date']) : 'N/A' ?></td>
                                <td>
                                    <?php if ($row['status'] == 'pending'): ?>
                                        <div class="action-buttons">
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <input type="hidden" name="action" value="resolve">
                                                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Resolve this grievance?')">
                                                    <i class="fas fa-check"></i> Resolve
                                                </button>
                                            </form>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
                                                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn btn-error btn-sm" onclick="return confirm('Reject this grievance?')">
                                                    <i class="fas fa-times"></i> Reject
                                                </button>
                                            </form>
                                        </div>
                                    <?php else: ?>
                                        <span class="muted">No actions available</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
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