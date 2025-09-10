<?php
session_start();
require_once 'config.php';
require_once 'includes/security.php';

// Get error type
$error = $_GET['error'] ?? 'unknown';

// Define error messages
$errorMessages = [
    'access_denied' => [
        'title' => 'Access Denied',
        'message' => 'You do not have permission to access this resource.',
        'icon' => 'fas fa-ban',
        'color' => 'error'
    ],
    'csrf' => [
        'title' => 'Security Error',
        'message' => 'Invalid security token. Please try again.',
        'icon' => 'fas fa-shield-alt',
        'color' => 'warning'
    ],
    'rate_limit' => [
        'title' => 'Rate Limit Exceeded',
        'message' => 'Too many requests. Please wait a few minutes before trying again.',
        'icon' => 'fas fa-clock',
        'color' => 'warning'
    ],
    'invalid_action' => [
        'title' => 'Invalid Action',
        'message' => 'The action you attempted is not allowed.',
        'icon' => 'fas fa-exclamation-triangle',
        'color' => 'error'
    ],
    'database_error' => [
        'title' => 'System Error',
        'message' => 'A database error occurred. Please try again later.',
        'icon' => 'fas fa-database',
        'color' => 'error'
    ],
    'unknown' => [
        'title' => 'Unknown Error',
        'message' => 'An unexpected error occurred.',
        'icon' => 'fas fa-question-circle',
        'color' => 'info'
    ]
];

$errorInfo = $errorMessages[$error] ?? $errorMessages['unknown'];

// Log the error
logSecurityEvent('error_page_accessed', [
    'error_type' => $error,
    'user_id' => $_SESSION['user_id'] ?? 'anonymous',
    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Error - NEUST Power Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="assets/admin_theme.css">
    <style>
        .error-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--bg-secondary) 0%, #f8fafc 100%);
            padding: var(--space-xl);
        }
        
        .error-card {
            background: var(--bg-card);
            border-radius: var(--radius-xl);
            padding: var(--space-2xl);
            box-shadow: var(--shadow-xl);
            border: 1px solid var(--border-light);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        
        .error-icon {
            width: 80px;
            height: 80px;
            border-radius: var(--radius-full);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: var(--font-size-3xl);
            margin: 0 auto var(--space-lg);
            color: var(--text-white);
        }
        
        .error-icon.error {
            background: linear-gradient(135deg, var(--error), #DC2626);
        }
        
        .error-icon.warning {
            background: linear-gradient(135deg, var(--warning), #D97706);
        }
        
        .error-icon.info {
            background: linear-gradient(135deg, var(--info), #2563EB);
        }
        
        .error-title {
            font-size: var(--font-size-2xl);
            font-weight: 700;
            color: var(--primary);
            margin-bottom: var(--space-md);
        }
        
        .error-message {
            font-size: var(--font-size-lg);
            color: var(--text-secondary);
            margin-bottom: var(--space-xl);
            line-height: 1.6;
        }
        
        .error-actions {
            display: flex;
            gap: var(--space-md);
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-home {
            background: var(--primary);
            color: var(--text-white);
            padding: var(--space-md) var(--space-lg);
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: 600;
            transition: all var(--transition-fast);
            display: inline-flex;
            align-items: center;
            gap: var(--space-sm);
        }
        
        .btn-home:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .btn-back {
            background: transparent;
            color: var(--primary);
            border: 2px solid var(--primary);
            padding: var(--space-md) var(--space-lg);
            border-radius: var(--radius-md);
            text-decoration: none;
            font-weight: 600;
            transition: all var(--transition-fast);
            display: inline-flex;
            align-items: center;
            gap: var(--space-sm);
        }
        
        .btn-back:hover {
            background: var(--primary);
            color: var(--text-white);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }
        
        .error-details {
            margin-top: var(--space-lg);
            padding: var(--space-md);
            background: var(--bg-secondary);
            border-radius: var(--radius-md);
            border-left: 4px solid var(--warning);
            font-size: var(--font-size-sm);
            color: var(--text-muted);
        }
        
        @media (max-width: 768px) {
            .error-container {
                padding: var(--space-md);
            }
            
            .error-card {
                padding: var(--space-xl);
            }
            
            .error-actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-card">
            <div class="error-icon <?= $errorInfo['color'] ?>">
                <i class="<?= $errorInfo['icon'] ?>"></i>
            </div>
            
            <h1 class="error-title"><?= htmlspecialchars($errorInfo['title']) ?></h1>
            
            <p class="error-message"><?= htmlspecialchars($errorInfo['message']) ?></p>
            
            <div class="error-actions">
                <a href="javascript:history.back()" class="btn-back">
                    <i class="fas fa-arrow-left"></i>
                    Go Back
                </a>
                
                <a href="admin_dashboard.php" class="btn-home">
                    <i class="fas fa-home"></i>
                    Dashboard
                </a>
            </div>
            
            <?php if ($error === 'rate_limit'): ?>
            <div class="error-details">
                <i class="fas fa-info-circle"></i>
                <strong>Rate Limiting:</strong> This helps protect the system from abuse. 
                You can try again in a few minutes.
            </div>
            <?php elseif ($error === 'csrf'): ?>
            <div class="error-details">
                <i class="fas fa-shield-alt"></i>
                <strong>Security Token:</strong> This error occurs when the security token 
                has expired or is invalid. Please refresh the page and try again.
            </div>
            <?php elseif ($error === 'access_denied'): ?>
            <div class="error-details">
                <i class="fas fa-user-shield"></i>
                <strong>Permission Required:</strong> You need the appropriate permissions 
                to access this resource. Contact your administrator if you believe this is an error.
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Auto-redirect after 30 seconds for certain errors
        <?php if (in_array($error, ['rate_limit', 'csrf'])): ?>
        setTimeout(function() {
            if (confirm('Would you like to return to the dashboard?')) {
                window.location.href = 'admin_dashboard.php';
            }
        }, 30000);
        <?php endif; ?>
        
        // Log client-side error details
        console.log('Error page accessed:', {
            error: '<?= $error ?>',
            timestamp: new Date().toISOString(),
            userAgent: navigator.userAgent
        });
    </script>
</body>
</html>