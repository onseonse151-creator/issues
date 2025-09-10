<?php
session_start();
require_once 'config.php';
require_once 'includes/functions.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Power Admin') {
    header("Location: login.php");
    exit();
}

// Get current user info
$currentUser = (isset($_SESSION['first_name']) && isset($_SESSION['last_name'])) 
    ? $_SESSION['first_name'] . ' ' . $_SESSION['last_name'] 
    : 'Power Administrator';
$currentRole = $_SESSION['role'] ?? 'Power Admin';

// Fetch comprehensive dashboard statistics
try {
    // User Statistics
    $totalUsers = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'];
    $activeUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'Active'")->fetch_assoc()['count'];
    $inactiveUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE status = 'Inactive'")->fetch_assoc()['count'];
    $adminUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role LIKE '%Admin%'")->fetch_assoc()['count'];
    $studentUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'Student'")->fetch_assoc()['count'];
    
    // Announcement Statistics
    $totalAnnouncements = $conn->query("SELECT COUNT(*) as count FROM announcements")->fetch_assoc()['count'];
    $recentAnnouncements = $conn->query("SELECT COUNT(*) as count FROM announcements WHERE date_posted >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['count'];
    
    // Grievance Statistics
    $totalGrievances = $conn->query("SELECT COUNT(*) as count FROM grievances")->fetch_assoc()['count'];
    $pendingGrievances = $conn->query("SELECT COUNT(*) as count FROM grievances WHERE status = 'pending'")->fetch_assoc()['count'];
    $resolvedGrievances = $conn->query("SELECT COUNT(*) as count FROM grievances WHERE status = 'resolved'")->fetch_assoc()['count'];
    $recentGrievances = $conn->query("SELECT COUNT(*) as count FROM grievances WHERE submission_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch_assoc()['count'];
    
    // Application Statistics (if tables exist)
    $scholarshipApps = 0;
    $dormitoryApps = 0;
    $guidanceApps = 0;
    
    if (db_table_exists($conn, 'scholarship_applications')) {
        $scholarshipApps = $conn->query("SELECT COUNT(*) as count FROM scholarship_applications")->fetch_assoc()['count'];
    }
    
    if (db_table_exists($conn, 'dormitory_applications')) {
        $dormitoryApps = $conn->query("SELECT COUNT(*) as count FROM dormitory_applications")->fetch_assoc()['count'];
    }
    
    if (db_table_exists($conn, 'guidance_appointments')) {
        $guidanceApps = $conn->query("SELECT COUNT(*) as count FROM guidance_appointments")->fetch_assoc()['count'];
    }
    
    // Recent Activities
    $recentUsers = $conn->query("SELECT first_name, last_name, role, date_registered FROM users ORDER BY date_registered DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
    $recentGrievancesList = $conn->query("SELECT g.id, g.title, g.status, g.submission_date, u.first_name, u.last_name FROM grievances g JOIN users u ON g.user_id = u.user_id ORDER BY g.submission_date DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
    $recentAnnouncementsList = $conn->query("SELECT title, date_posted FROM announcements ORDER BY date_posted DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);
    
    // Monthly Statistics for Charts
    $monthlyUsers = $conn->query("SELECT DATE_FORMAT(date_registered, '%Y-%m') as month, COUNT(*) as count FROM users WHERE date_registered >= DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY month ORDER BY month")->fetch_all(MYSQLI_ASSOC);
    $monthlyGrievances = $conn->query("SELECT DATE_FORMAT(submission_date, '%Y-%m') as month, COUNT(*) as count FROM grievances WHERE submission_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY month ORDER BY month")->fetch_all(MYSQLI_ASSOC);
    
    // Status Distribution
    $grievanceStatuses = $conn->query("SELECT status, COUNT(*) as count FROM grievances GROUP BY status")->fetch_all(MYSQLI_ASSOC);
    $userRoles = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role")->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    // Handle database errors gracefully
    $totalUsers = $activeUsers = $inactiveUsers = $adminUsers = $studentUsers = 0;
    $totalAnnouncements = $recentAnnouncements = 0;
    $totalGrievances = $pendingGrievances = $resolvedGrievances = $recentGrievances = 0;
    $recentUsers = $recentGrievancesList = $recentAnnouncementsList = [];
    $monthlyUsers = $monthlyGrievances = $grievanceStatuses = $userRoles = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Power Admin Dashboard - NEUST Gabaldon</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="assets/admin_theme.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Dashboard Specific Styles */
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: var(--text-white);
            padding: var(--space-xl);
            border-radius: var(--radius-xl);
            margin-bottom: var(--space-xl);
            position: relative;
            overflow: hidden;
        }
        
        .dashboard-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 215, 0, 0.1) 0%, transparent 70%);
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        .welcome-section {
            position: relative;
            z-index: 1;
        }
        
        .welcome-title {
            font-size: var(--font-size-3xl);
            font-weight: 700;
            margin-bottom: var(--space-sm);
            display: flex;
            align-items: center;
            gap: var(--space-md);
        }
        
        .welcome-subtitle {
            font-size: var(--font-size-lg);
            opacity: 0.9;
            margin-bottom: var(--space-lg);
        }
        
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: var(--space-lg);
            margin-top: var(--space-lg);
        }
        
        .quick-stat {
            text-align: center;
            padding: var(--space-md);
            background: rgba(255, 255, 255, 0.1);
            border-radius: var(--radius-lg);
            backdrop-filter: blur(10px);
        }
        
        .quick-stat-number {
            font-size: var(--font-size-2xl);
            font-weight: 700;
            margin-bottom: var(--space-xs);
        }
        
        .quick-stat-label {
            font-size: var(--font-size-sm);
            opacity: 0.8;
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
        
        .stat-icon.users { background: linear-gradient(135deg, var(--primary), var(--primary-light)); }
        .stat-icon.announcements { background: linear-gradient(135deg, var(--secondary), var(--secondary-dark)); }
        .stat-icon.grievances { background: linear-gradient(135deg, var(--warning), #D97706); }
        .stat-icon.applications { background: linear-gradient(135deg, var(--success), #059669); }
        
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
        
        .activity-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: var(--space-lg);
            margin-bottom: var(--space-xl);
        }
        
        .activity-card {
            background: var(--bg-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-xl);
            padding: var(--space-lg);
            box-shadow: var(--shadow-sm);
        }
        
        .activity-title {
            font-size: var(--font-size-lg);
            font-weight: 600;
            color: var(--primary);
            margin-bottom: var(--space-lg);
            display: flex;
            align-items: center;
            gap: var(--space-sm);
        }
        
        .activity-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .activity-item {
            display: flex;
            align-items: center;
            gap: var(--space-md);
            padding: var(--space-md) 0;
            border-bottom: 1px solid var(--border-light);
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: var(--font-size-sm);
            color: var(--text-white);
        }
        
        .activity-icon.user { background: var(--primary); }
        .activity-icon.grievance { background: var(--warning); }
        .activity-icon.announcement { background: var(--secondary); }
        
        .activity-content {
            flex: 1;
        }
        
        .activity-text {
            font-size: var(--font-size-sm);
            color: var(--text-primary);
            margin-bottom: var(--space-xs);
        }
        
        .activity-time {
            font-size: var(--font-size-xs);
            color: var(--text-muted);
        }
        
        .system-health {
            background: var(--bg-card);
            border: 1px solid var(--border-light);
            border-radius: var(--radius-xl);
            padding: var(--space-lg);
            box-shadow: var(--shadow-sm);
        }
        
        .health-indicators {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--space-lg);
            margin-top: var(--space-lg);
        }
        
        .health-indicator {
            text-align: center;
            padding: var(--space-md);
            border-radius: var(--radius-lg);
            background: var(--bg-secondary);
        }
        
        .health-status {
            width: 60px;
            height: 60px;
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto var(--space-md);
            font-size: var(--font-size-xl);
            color: var(--text-white);
        }
        
        .health-status.good { background: var(--success); }
        .health-status.warning { background: var(--warning); }
        .health-status.error { background: var(--error); }
        
        .health-label {
            font-size: var(--font-size-sm);
            font-weight: 500;
            color: var(--text-primary);
        }
        
        .health-value {
            font-size: var(--font-size-lg);
            font-weight: 700;
            color: var(--primary);
            margin-top: var(--space-xs);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .charts-section {
                grid-template-columns: 1fr;
            }
            
            .activity-section {
                grid-template-columns: 1fr;
            }
            
            .quick-stats {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
<?php include 'power_admin_header.php'; ?>
    <main class="main">
        <!-- Dashboard Header -->
        <div class="dashboard-header">
            <div class="welcome-section">
                <h1 class="welcome-title">
                    <i class="fas fa-tachometer-alt"></i>
                    Welcome back, <?= htmlspecialchars($currentUser) ?>
                </h1>
                <p class="welcome-subtitle">Power Administrator Dashboard - NEUST Gabaldon Student Services</p>
                
                <div class="quick-stats">
                    <div class="quick-stat">
                        <div class="quick-stat-number"><?= $totalUsers ?></div>
                        <div class="quick-stat-label">Total Users</div>
                    </div>
                    <div class="quick-stat">
                        <div class="quick-stat-number"><?= $totalGrievances ?></div>
                        <div class="quick-stat-label">Grievances</div>
                    </div>
                    <div class="quick-stat">
                        <div class="quick-stat-number"><?= $totalAnnouncements ?></div>
                        <div class="quick-stat-label">Announcements</div>
                    </div>
                    <div class="quick-stat">
                        <div class="quick-stat-number"><?= $pendingGrievances ?></div>
                        <div class="quick-stat-label">Pending Issues</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon users">
                        <i class="fas fa-users"></i>
                    </div>
                </div>
                <div class="stat-number"><?= $totalUsers ?></div>
                <div class="stat-label">Total Users</div>
                <div class="stat-change positive">
                    <i class="fas fa-arrow-up"></i> <?= $activeUsers ?> Active
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon announcements">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                </div>
                <div class="stat-number"><?= $totalAnnouncements ?></div>
                <div class="stat-label">Announcements</div>
                <div class="stat-change positive">
                    <i class="fas fa-plus"></i> <?= $recentAnnouncements ?> This Week
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon grievances">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
                <div class="stat-number"><?= $totalGrievances ?></div>
                <div class="stat-label">Grievances</div>
                <div class="stat-change <?= $pendingGrievances > 0 ? 'negative' : 'positive' ?>">
                    <i class="fas fa-<?= $pendingGrievances > 0 ? 'exclamation' : 'check' ?>"></i> 
                    <?= $pendingGrievances ?> Pending
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-header">
                    <div class="stat-icon applications">
                        <i class="fas fa-file-alt"></i>
                    </div>
                </div>
                <div class="stat-number"><?= $scholarshipApps + $dormitoryApps + $guidanceApps ?></div>
                <div class="stat-label">Applications</div>
                <div class="stat-change positive">
                    <i class="fas fa-chart-line"></i> All Services
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="charts-section">
            <div class="chart-card">
                <h3 class="chart-title">
                    <i class="fas fa-chart-line"></i>
                    User Registration Trends
                </h3>
                <div class="chart-container">
                    <canvas id="userChart"></canvas>
                </div>
            </div>
            
            <div class="chart-card">
                <h3 class="chart-title">
                    <i class="fas fa-chart-pie"></i>
                    Grievance Status Distribution
                </h3>
                <div class="chart-container">
                    <canvas id="grievanceChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Activity Section -->
        <div class="activity-section">
            <div class="activity-card">
                <h3 class="activity-title">
                    <i class="fas fa-user-plus"></i>
                    Recent User Registrations
                </h3>
                <ul class="activity-list">
                    <?php foreach ($recentUsers as $user): ?>
                    <li class="activity-item">
                        <div class="activity-icon user">
                            <i class="fas fa-user"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-text">
                                <strong><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></strong>
                                registered as <strong><?= htmlspecialchars($user['role']) ?></strong>
                            </div>
                            <div class="activity-time">
                                <?= format_date($user['date_registered']) ?>
                            </div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="activity-card">
                <h3 class="activity-title">
                    <i class="fas fa-exclamation-triangle"></i>
                    Recent Grievances
                </h3>
                <ul class="activity-list">
                    <?php foreach ($recentGrievancesList as $grievance): ?>
                    <li class="activity-item">
                        <div class="activity-icon grievance">
                            <i class="fas fa-exclamation"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-text">
                                <strong><?= htmlspecialchars($grievance['title']) ?></strong>
                                by <?= htmlspecialchars($grievance['first_name'] . ' ' . $grievance['last_name']) ?>
                            </div>
                            <div class="activity-time">
                                Status: <span class="badge <?= get_status_badge_class($grievance['status']) ?>"><?= ucfirst($grievance['status']) ?></span>
                                • <?= format_date($grievance['submission_date']) ?>
                            </div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <div class="activity-card">
                <h3 class="activity-title">
                    <i class="fas fa-bullhorn"></i>
                    Recent Announcements
                </h3>
                <ul class="activity-list">
                    <?php foreach ($recentAnnouncementsList as $announcement): ?>
                    <li class="activity-item">
                        <div class="activity-icon announcement">
                            <i class="fas fa-bullhorn"></i>
                        </div>
                        <div class="activity-content">
                            <div class="activity-text">
                                <strong><?= htmlspecialchars($announcement['title']) ?></strong>
                            </div>
                            <div class="activity-time">
                                <?= format_date($announcement['date_posted']) ?>
                            </div>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>

        <!-- System Health -->
        <div class="system-health">
            <h3 class="chart-title">
                <i class="fas fa-heartbeat"></i>
                System Health Overview
            </h3>
            <div class="health-indicators">
                <div class="health-indicator">
                    <div class="health-status <?= $activeUsers > 0 ? 'good' : 'warning' ?>">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="health-label">User Activity</div>
                    <div class="health-value"><?= $activeUsers ?> Active</div>
                </div>
                
                <div class="health-indicator">
                    <div class="health-status <?= $pendingGrievances < 10 ? 'good' : ($pendingGrievances < 20 ? 'warning' : 'error') ?>">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div class="health-label">Grievance Queue</div>
                    <div class="health-value"><?= $pendingGrievances ?> Pending</div>
                </div>
                
                <div class="health-indicator">
                    <div class="health-status good">
                        <i class="fas fa-database"></i>
                    </div>
                    <div class="health-label">Database</div>
                    <div class="health-value">Online</div>
                </div>
                
                <div class="health-indicator">
                    <div class="health-status <?= $recentAnnouncements > 0 ? 'good' : 'warning' ?>">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <div class="health-label">Communication</div>
                    <div class="health-value"><?= $recentAnnouncements ?> This Week</div>
                </div>
            </div>
        </div>
    </main>

    <script>
    // User Registration Chart
    const userCtx = document.getElementById('userChart').getContext('2d');
    const userData = <?= json_encode($monthlyUsers) ?>;
    const userLabels = userData.map(item => item.month);
    const userCounts = userData.map(item => item.count);
    
    new Chart(userCtx, {
        type: 'line',
        data: {
            labels: userLabels,
            datasets: [{
                label: 'New Users',
                data: userCounts,
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
    
    // Grievance Status Chart
    const grievanceCtx = document.getElementById('grievanceChart').getContext('2d');
    const grievanceData = <?= json_encode($grievanceStatuses) ?>;
    const grievanceLabels = grievanceData.map(item => item.status);
    const grievanceCounts = grievanceData.map(item => item.count);
    
    new Chart(grievanceCtx, {
        type: 'doughnut',
        data: {
            labels: grievanceLabels,
            datasets: [{
                data: grievanceCounts,
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
    
    // Auto-refresh dashboard every 5 minutes
    setTimeout(function() {
        location.reload();
    }, 300000);
    </script>
</body>
</html>