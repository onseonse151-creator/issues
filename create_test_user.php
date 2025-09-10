<?php
// Script to create a test user for authentication testing
session_start();
require_once __DIR__ . '/csrf.php';

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "student_services_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Create Test User</h2>";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $testUserId = "test001";
    $testFirstName = "Test";
    $testEmail = "test@neust.edu.ph";
    $testPassword = "Test123456"; // This meets the password requirements
    $testRole = "Student";
    
    // Hash the password
    $hashedPassword = password_hash($testPassword, PASSWORD_DEFAULT);
    
    // Check if user already exists
    $checkStmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $checkStmt->bind_param("s", $testUserId);
    $checkStmt->execute();
    $checkStmt->store_result();
    
    if ($checkStmt->num_rows > 0) {
        echo "⚠️ Test user already exists. Updating password...<br>";
        
        // Update existing user
        $updateStmt = $conn->prepare("UPDATE users SET password_hash = ?, first_name = ?, email = ? WHERE user_id = ?");
        $updateStmt->bind_param("ssss", $hashedPassword, $testFirstName, $testEmail, $testUserId);
        
        if ($updateStmt->execute()) {
            echo "✅ Test user password updated successfully!<br>";
        } else {
            echo "❌ Error updating test user: " . $updateStmt->error . "<br>";
        }
        $updateStmt->close();
    } else {
        // Create new user
        $insertStmt = $conn->prepare("INSERT INTO users (user_id, first_name, email, role, year, section, course, password_hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $year = 1;
        $section = "A";
        $course = "BSIT";
        $insertStmt->bind_param("ssssssss", $testUserId, $testFirstName, $testEmail, $testRole, $year, $section, $course, $hashedPassword);
        
        if ($insertStmt->execute()) {
            echo "✅ Test user created successfully!<br>";
        } else {
            echo "❌ Error creating test user: " . $insertStmt->error . "<br>";
        }
        $insertStmt->close();
    }
    
    $checkStmt->close();
    
    echo "<br><h3>Test User Credentials:</h3>";
    echo "<strong>User ID:</strong> " . $testUserId . "<br>";
    echo "<strong>Password:</strong> " . $testPassword . "<br>";
    echo "<strong>Role:</strong> " . $testRole . "<br>";
    echo "<br><a href='landing_page.php'>Go to Landing Page to Test Login</a>";
    
} else {
    echo "<p>This script will create a test user for authentication testing.</p>";
    echo "<form method='POST'>";
    echo "<input type='submit' value='Create Test User' style='padding: 10px 20px; background: #021f3d; color: white; border: none; border-radius: 5px; cursor: pointer;'>";
    echo "</form>";
}

$conn->close();
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
input[type="submit"]:hover { background: #0a3a6b !important; }
</style>
