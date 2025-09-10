<?php
session_start();
require_once 'config.php';

// Check if user is Power Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Power Admin') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id']) && isset($_POST['new_status'])) {
    $user_id = $_POST['user_id'];
    $new_status = $_POST['new_status'];
    
    // Validate status
    if (!in_array($new_status, ['Active', 'Inactive'])) {
        $_SESSION['error'] = 'Invalid status value';
        header('Location: power_admin_users.php');
        exit();
    }
    
    // Update user status
    $stmt = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ?");
    $stmt->bind_param('ss', $new_status, $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = 'User status updated successfully';
    } else {
        $_SESSION['error'] = 'Failed to update user status';
    }
    
    $stmt->close();
}

header('Location: power_admin_users.php');
exit();
?>