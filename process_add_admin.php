<?php
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';
require_once 'includes/audit_logger.php';

// Security checks
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Power Admin') {
    logSecurityEvent('unauthorized_access_attempt', ['page' => 'process_add_admin']);
    header('Location: login.php');
    exit();
}

if (!hasPermission('manage_users')) {
    logSecurityEvent('permission_denied', ['page' => 'process_add_admin']);
    header('Location: error_page.php?error=access_denied');
    exit();
}

// Rate limiting
if (!checkRateLimit('add_admin_action', 5, 300)) {
    logSecurityEvent('rate_limit_exceeded', ['action' => 'add_admin_action']);
    header('Location: error_page.php?error=rate_limit');
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF protection
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        logSecurityEvent('csrf_token_mismatch', ['page' => 'process_add_admin']);
        header('Location: error_page.php?error=csrf');
        exit();
    }
    // ✅ Get form data safely with sanitization
    $user_id = sanitizeInput($_POST['user_id'] ?? "", 'string');
    $first_name = sanitizeInput($_POST['first_name'] ?? "", 'string');
    $middle_name = sanitizeInput($_POST['middle_name'] ?? "", 'string');
    $last_name = sanitizeInput($_POST['last_name'] ?? "", 'string');
    $birth_date = sanitizeInput($_POST['birth_date'] ?? "", 'string');
    $email = sanitizeInput($_POST['email'] ?? "", 'email');
    $phone = sanitizeInput($_POST['phone'] ?? "", 'string');
    $current_address = sanitizeInput($_POST['current_address'] ?? "", 'string');
    $permanent_address = sanitizeInput($_POST['permanent_address'] ?? "", 'string');
    $role = sanitizeInput($_POST['role'] ?? "", 'string'); 
    $password = $_POST['password'] ?? "";
    $mother_name = sanitizeInput($_POST['mother_name'] ?? "", 'string');
    $mother_work = sanitizeInput($_POST['mother_work'] ?? "", 'string');
    $mother_contact = sanitizeInput($_POST['mother_contact'] ?? "", 'string');
    $father_name = sanitizeInput($_POST['father_name'] ?? "", 'string');
    $father_work = sanitizeInput($_POST['father_work'] ?? "", 'string');
    $father_contact = sanitizeInput($_POST['father_contact'] ?? "", 'string');
    $siblings_count = sanitizeInput($_POST['siblings_count'] ?? "0", 'int');
    $unit = sanitizeInput($_POST['unit'] ?? "", 'string');

    // ✅ If role is empty, use unit as the role
    if (empty($role) && !empty($unit)) {
        $role = $unit; // Assign unit as role
    }

    // ✅ Validation checks
    if (empty($user_id)) {
        logSecurityEvent('admin_creation_validation_failed', ['error' => 'user_id_required']);
        $_SESSION['error_message'] = 'User ID is required.';
        header('Location: add_admin.php');
        exit();
    }
    if (empty($role)) {
        logSecurityEvent('admin_creation_validation_failed', ['error' => 'role_required']);
        $_SESSION['error_message'] = 'Admin role is required.';
        header('Location: add_admin.php');
        exit();
    }
    // Validate that the role is one of the allowed admin roles
    $allowedRoles = ['Dormitory Admin', 'Guidance Admin', 'Scholarship Admin'];
    if (!in_array($role, $allowedRoles)) {
        logSecurityEvent('admin_creation_validation_failed', ['error' => 'invalid_role', 'role' => $role]);
        $_SESSION['error_message'] = 'Invalid admin role selected.';
        header('Location: add_admin.php');
        exit();
    }
    if (strlen($password) < 8) {
        logSecurityEvent('admin_creation_validation_failed', ['error' => 'password_too_short']);
        $_SESSION['error_message'] = 'Password must be at least 8 characters long.';
        header('Location: add_admin.php');
        exit();
    }
    
    // ✅ Hash the password before storing it
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // ✅ Check if `user_id` or `email` already exists
    $checkQuery = "SELECT user_id, email FROM users WHERE email = ? OR user_id = ?";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bind_param("ss", $email, $user_id);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        logSecurityEvent('admin_creation_duplicate', [
            'user_id' => $user_id,
            'email' => $email
        ]);
        $_SESSION['error_message'] = 'Email or User ID already exists.';
        header('Location: add_admin.php');
        $checkStmt->close();
        exit();
    }
    $checkStmt->close();

    // ✅ Prepare SQL Query
    $sql = "INSERT INTO users 
            (user_id, first_name, middle_name, last_name, birth_date, email, phone, current_address, permanent_address, role, password_hash, mother_name, mother_work, mother_contact, father_name, father_work, father_contact, siblings_count, unit) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = $conn->prepare($sql)) {
        // ✅ Bind Parameters
        $stmt->bind_param("sssssssssssssssssss", $user_id, $first_name, $middle_name, $last_name, $birth_date, $email, $phone, $current_address, $permanent_address, $role, $hashed_password, $mother_name, $mother_work, $mother_contact, $father_name, $father_work, $father_contact, $siblings_count, $unit);

        // ✅ Execute
        if ($stmt->execute()) {
            // Log successful admin creation
            audit_data_modification('admin', $user_id, 'create', null, [
                'role' => $role,
                'unit' => $unit,
                'email' => $email
            ]);
            
            logSecurityEvent('admin_created', [
                'admin_id' => $user_id,
                'role' => $role,
                'unit' => $unit
            ]);
            
            // Set success message in session and redirect
            $_SESSION['success_message'] = "Admin created successfully! User ID: $user_id";
            header('Location: admin_list.php');
            exit();
        } else {
            logSecurityEvent('admin_creation_failed', [
                'error' => $stmt->error,
                'admin_id' => $user_id
            ]);
            $_SESSION['error_message'] = 'Failed to create admin: ' . $stmt->error;
            header('Location: add_admin.php');
        }
        $stmt->close();
    } else {
        logSecurityEvent('admin_creation_database_error', [
            'error' => $conn->error,
            'admin_id' => $user_id
        ]);
        $_SESSION['error_message'] = 'Database error: ' . $conn->error;
        header('Location: add_admin.php');
    }
    
    // ✅ Close Connection
    $conn->close();
} else {
    logSecurityEvent('invalid_request_method', [
        'method' => $_SERVER['REQUEST_METHOD'],
        'page' => 'process_add_admin'
    ]);
    $_SESSION['error_message'] = 'Invalid request method.';
    header('Location: add_admin.php');
}
?>