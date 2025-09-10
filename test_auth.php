<?php
// Simple test file to check authentication setup
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

echo "<h2>Authentication Test</h2>";

// Test 1: Check if users table exists
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result->num_rows > 0) {
    echo "✅ Users table exists<br>";
    
    // Test 2: Check table structure
    $result = $conn->query("DESCRIBE users");
    echo "<h3>Users table structure:</h3>";
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test 3: Check if there are any users
    $result = $conn->query("SELECT COUNT(*) as count FROM users");
    $row = $result->fetch_assoc();
    echo "<br>📊 Total users in database: " . $row['count'] . "<br>";
    
    // Test 4: Show sample users (without passwords)
    if ($row['count'] > 0) {
        $result = $conn->query("SELECT user_id, first_name, role FROM users LIMIT 5");
        echo "<h3>Sample users:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>User ID</th><th>First Name</th><th>Role</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['first_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['role']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} else {
    echo "❌ Users table does not exist<br>";
}

// Test 5: Check CSRF token generation
echo "<br><h3>CSRF Test:</h3>";
$token = csrf_token();
echo "✅ CSRF token generated: " . substr($token, 0, 20) . "...<br>";

$conn->close();
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
table { border-collapse: collapse; margin: 10px 0; }
th, td { padding: 8px; text-align: left; }
th { background-color: #f2f2f2; }
</style>
