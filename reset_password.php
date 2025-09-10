<?php
session_start();
require_once __DIR__ . '/config.php';

// Check if user is Power Admin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Power Admin') {
    header('Location: landing_page.php?open=login');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];
    
    // Generate a random password
    $new_password = bin2hex(random_bytes(8)); // 16 character random password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update user password
    $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
    $stmt->bind_param('ss', $hashed_password, $user_id);
    
    if ($stmt->execute()) {
        // Get user email for notification
        $email_stmt = $conn->prepare("SELECT email, first_name, last_name FROM users WHERE user_id = ?");
        $email_stmt->bind_param('s', $user_id);
        $email_stmt->execute();
        $result = $email_stmt->get_result();
        $user = $result->fetch_assoc();
        $email_stmt->close();
        
        if ($user) {
            // Send email notification (you may need to configure SMTP)
            $subject = "Password Reset - NEUST Gabaldon Student Services";
            $message = "Hello " . $user['first_name'] . " " . $user['last_name'] . ",\n\n";
            $message .= "Your password has been reset by an administrator.\n";
            $message .= "Your new temporary password is: " . $new_password . "\n\n";
            $message .= "Please log in and change your password immediately for security reasons.\n\n";
            $message .= "Best regards,\nNEUST Gabaldon Student Services Team";
            
            $headers = "From: noreply@neust-gabaldon.edu.ph\r\n";
            $headers .= "Reply-To: noreply@neust-gabaldon.edu.ph\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            
            // Uncomment the line below when SMTP is configured
            // mail($user['email'], $subject, $message, $headers);
            
            $_SESSION['success'] = 'Password reset successfully. New password: ' . $new_password;
        } else {
            $_SESSION['error'] = 'User not found';
        }
    } else {
        $_SESSION['error'] = 'Failed to reset password';
    }
    
    $stmt->close();
}

header('Location: power_admin_users.php');
exit();
?>
