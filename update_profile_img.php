<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_img'])) {
    $user_id = $_SESSION['user_id'];
    $file = $_FILES['profile_img'];
    
    // Validate file
    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_size) {
            // Create uploads directory if it doesn't exist
            $upload_dir = __DIR__ . '/uploads/profile_images/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0775, true);
            }
            
            // Generate unique filename
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = $user_id . '_' . time() . '.' . $extension;
            $filepath = $upload_dir . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Update database
                $relative_path = 'uploads/profile_images/' . $filename;
                $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE user_id = ?");
                $stmt->bind_param('ss', $relative_path, $user_id);
                
                if ($stmt->execute()) {
                    $_SESSION['profile_img'] = $relative_path;
                    $_SESSION['success'] = 'Profile image updated successfully';
                } else {
                    $_SESSION['error'] = 'Failed to update profile image in database';
                    unlink($filepath); // Remove uploaded file
                }
                
                $stmt->close();
            } else {
                $_SESSION['error'] = 'Failed to upload file';
            }
        } else {
            $_SESSION['error'] = 'Invalid file type or size too large (max 5MB)';
        }
    } else {
        $_SESSION['error'] = 'File upload error';
    }
}

// Redirect back to the page that called this script
$referer = $_SERVER['HTTP_REFERER'] ?? 'admin_dashboard.php';
header('Location: ' . $referer);
exit();
?>
