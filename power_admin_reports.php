<?php
session_start();
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';

// Security checks
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Power Admin') {
    logSecurityEvent('unauthorized_access_attempt', ['page' => 'reports']);
    header('Location: login.php');
    exit();
}

if (!hasPermission('view_reports')) {
    logSecurityEvent('permission_denied', ['page' => 'reports']);
    header('Location: error_page.php?error=access_denied');
    exit();
}

// Rate limiting
if (!checkRateLimit('reports_view', 15, 300)) {
    logSecurityEvent('rate_limit_exceeded', ['action' => 'reports_view']);
    header('Location: error_page.php?error=rate_limit');
    exit();
}

// Basic metrics with error handling
try {
    $total = (int)($conn->query("SELECT COUNT(*) c FROM grievances")->fetch_assoc()['c'] ?? 0);
    $resolved = (int)($conn->query("SELECT COUNT(*) c FROM grievances WHERE status='resolved'")->fetch_assoc()['c'] ?? 0);
    $pending = (int)($conn->query("SELECT COUNT(*) c FROM grievances WHERE status='pending'")->fetch_assoc()['c'] ?? 0);
    $rejected = (int)($conn->query("SELECT COUNT(*) c FROM grievances WHERE status='rejected'")->fetch_assoc()['c'] ?? 0);
    
    // Counts by status
    $byStatus = [];
    $res = $conn->query("SELECT status, COUNT(*) c FROM grievances GROUP BY status");
    if ($res) { 
        while ($r = $res->fetch_assoc()) { 
            $byStatus[$r['status']] = (int)$r['c']; 
        } 
    }
    
    // Counts by month (last 6)
    $byMonth = [];
    $res = $conn->query("SELECT DATE_FORMAT(submission_date,'%Y-%m') ym, COUNT(*) c FROM grievances GROUP BY ym ORDER BY ym DESC LIMIT 6");
    if ($res) { 
        while ($r = $res->fetch_assoc()) { 
            $byMonth[$r['ym']] = (int)$r['c']; 
        } 
    }
    $byMonth = array_reverse($byMonth, true);
    
} catch (Exception $e) {
    logSecurityEvent('database_error', ['error' => $e->getMessage()]);
    error_log("Database error in reports: " . $e->getMessage());
    
    // Set default values on error
    $total = $resolved = $pending = $rejected = 0;
    $byStatus = [];
    $byMonth = [];
}

// CSV Export with security
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Additional CSRF check for export
    if (!verifyCSRFToken($_GET['csrf_token'] ?? '')) {
        logSecurityEvent('csrf_token_mismatch', ['action' => 'csv_export']);
        header('Location: error_page.php?error=csrf');
        exit();
    }
    
    logSecurityEvent('csv_export_requested', ['admin_id' => $_SESSION['user_id'] ?? 'unknown']);
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="grievance_report_' . date('Y-m-d_H-i-s') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID','User','Title','Status','Submitted','Resolved']);
    
    try {
        $rs = $conn->query("SELECT id, user_id, title, status, submission_date, resolution_date FROM grievances ORDER BY submission_date DESC");
        while ($row = $rs->fetch_assoc()) {
            fputcsv($out, [
                $row['id'],
                $row['user_id'],
                $row['title'],
                $row['status'],
                $row['submission_date'],
                $row['resolution_date']
            ]);
        }
    } catch (Exception $e) {
        logSecurityEvent('database_error', ['error' => $e->getMessage()]);
        error_log("Database error in CSV export: " . $e->getMessage());
    }
    
    fclose($out);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grievance Reports</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="assets/admin_theme.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: var(--space-lg);
            margin-bottom: var(--space-xl);
        }
        
        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-xl);
            padding: var(--space-lg);
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
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--space-md);
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: var(--radius-lg);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: var(--font-size-xl);
            color: var(--text-white);
        }
        
        .stat-icon.grievances { background: linear-gradient(135deg, var(--warning), #D97706); }
        .stat-icon.pending { background: linear-gradient(135deg, var(--warning), #F59E0B); }
        .stat-icon.resolved { background: linear-gradient(135deg, var(--success), #059669); }
        .stat-icon.rejected { background: linear-gradient(135deg, var(--error), #DC2626); }
        
        .stat-number {
            font-size: var(--font-size-3xl);
            font-weight: 700;
            color: var(--primary);
            margin-bottom: var(--space-xs);
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: var(--font-size-sm);
            font-weight: 500;
        }
        
        .stat-change {
            font-size: var(--font-size-xs);
            margin-top: var(--space-xs);
            padding: var(--space-xs) var(--space-sm);
            border-radius: var(--radius-full);
            font-weight: 600;
        }
        
        .stat-change.positive {
            background: var(--success-light);
            color: var(--success);
        }
        
        .stat-change.negative {
            background: var(--error-light);
            color: var(--error);
        }
        
        .charts-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: var(--space-lg);
            margin-bottom: var(--space-xl);
        }
        
        .chart-card {
            background: var(--bg-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-xl);
            padding: var(--space-lg);
            box-shadow: var(--shadow-sm);
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
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        .export-options {
            display: flex;
            gap: var(--space-md);
            flex-wrap: wrap;
        }
        
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .charts-section {
                grid-template-columns: 1fr;
            }
            
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: var(--space-md);
            }
            
            .export-options {
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
                <i class="fas fa-chart-line"></i>
                Grievance Reports & Analytics
            </h1>
            <div class="page-actions">
                <a class="btn" href="power_admin_reports.php?export=csv&csrf_token=<?= getCSRFToken() ?>">
                    <i class="fas fa-download"></i> Export CSV
                </a>
                <button class="btn btn-success" onclick="refreshData()">
                    <i class="fas fa-sync-alt"></i> Refresh Data
                </button>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon grievances">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
                <div class="stat-number"><?= $total ?></div>
                <div class="stat-label">Total Grievances</div>
                <div class="stat-change positive">
                    <i class="fas fa-chart-line"></i> All Time
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon pending">
                        <i class="fas fa-clock"></i>
                    </div>
                </div>
                <div class="stat-number"><?= $pending ?></div>
                <div class="stat-label">Pending</div>
                <div class="stat-change <?= $pending > 0 ? 'negative' : 'positive' ?>">
                    <i class="fas fa-<?= $pending > 0 ? 'exclamation' : 'check' ?>"></i> 
                    <?= $pending > 0 ? 'Needs Attention' : 'All Clear' ?>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon resolved">
                        <i class="fas fa-check-circle"></i>
                    </div>
                </div>
                <div class="stat-number"><?= $resolved ?></div>
                <div class="stat-label">Resolved</div>
                <div class="stat-change positive">
                    <i class="fas fa-percentage"></i> 
                    <?= $total > 0 ? round(($resolved / $total) * 100, 1) : 0 ?>% Rate
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon rejected">
                        <i class="fas fa-times-circle"></i>
                    </div>
                </div>
                <div class="stat-number"><?= $rejected ?></div>
                <div class="stat-label">Rejected</div>
                <div class="stat-change <?= $rejected > 0 ? 'negative' : 'positive' ?>">
                    <i class="fas fa-ban"></i> 
                    <?= $rejected > 0 ? 'Some Rejected' : 'None Rejected' ?>
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="charts-section">
            <div class="chart-card">
                <h3 class="chart-title">
                    <i class="fas fa-chart-pie"></i>
                    Grievance Status Distribution
                </h3>
                <div class="chart-container">
                    <canvas id="byStatus"></canvas>
                </div>
            </div>
            
            <div class="chart-card">
                <h3 class="chart-title">
                    <i class="fas fa-chart-line"></i>
                    Monthly Submission Trends
                </h3>
                <div class="chart-container">
                    <canvas id="byMonth"></canvas>
                </div>
            </div>
        </div>

        <!-- Export Section -->
        <div class="card">
            <h3 class="chart-title">
                <i class="fas fa-download"></i>
                Data Export & Reports
            </h3>
            <div class="export-options">
                <a class="btn" href="power_admin_reports.php?export=csv&csrf_token=<?= getCSRFToken() ?>">
                    <i class="fas fa-file-csv"></i> Export CSV Report
                </a>
                <button class="btn btn-ghost" onclick="printReport()">
                    <i class="fas fa-print"></i> Print Report
                </button>
                <button class="btn btn-ghost" onclick="generatePDF()">
                    <i class="fas fa-file-pdf"></i> Generate PDF
                </button>
            </div>
        </div>
    </main>
<script>
// Chart configurations
const statusData = <?= json_encode($byStatus) ?>;
const byStatusCtx = document.getElementById('byStatus');
new Chart(byStatusCtx, {
    type: 'doughnut',
    data: { 
        labels: Object.keys(statusData), 
        datasets: [{ 
            data: Object.values(statusData), 
            backgroundColor: [
                '#FFD700',
                '#002147', 
                '#10B981',
                '#F59E0B',
                '#EF4444',
                '#3B82F6'
            ],
            borderWidth: 0
        }] 
    },
    options: { 
        responsive: true,
        maintainAspectRatio: false,
        plugins: { 
            legend: { 
                position: 'bottom',
                labels: {
                    padding: 20,
                    usePointStyle: true
                }
            }
        }
    }
});

const monthData = <?= json_encode($byMonth) ?>;
const byMonthCtx = document.getElementById('byMonth');
new Chart(byMonthCtx, {
    type: 'line',
    data: { 
        labels: Object.keys(monthData), 
        datasets: [{ 
            data: Object.values(monthData), 
            label: 'Grievance Submissions',
            borderColor: '#002147',
            backgroundColor: 'rgba(0, 33, 71, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4
        }] 
    },
    options: { 
        responsive: true,
        maintainAspectRatio: false,
        plugins: { 
            legend: { 
                display: false
            }
        },
        scales: { 
            y: { 
                beginAtZero: true,
                grid: {
                    color: 'rgba(0, 0, 0, 0.1)'
                }
            },
            x: {
                grid: {
                    display: false
                }
            }
        }
    }
});

// Additional functions
function refreshData() {
    location.reload();
}

function printReport() {
    window.print();
}

function generatePDF() {
    alert('PDF generation feature will be implemented soon!');
}

// Auto-refresh every 5 minutes
setTimeout(function() {
    location.reload();
}, 300000);
</script>
</body>
</html>