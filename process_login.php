<?php 
session_start();
require_once __DIR__ . '/csrf.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

$errorMessage = ""; 
$successMessage = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Debug: Log the POST data
    error_log("Login attempt - POST data: " . print_r($_POST, true));
    
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $errorMessage = "Invalid request. Please refresh and try again.";
        if (($_POST['source'] ?? '') === 'landing') {
            $_SESSION['landing_error'] = $errorMessage;
            header('Location: landing_page.php?open=login');
            exit();
        }
    } else {
        $userId = trim($_POST['user_id']);
        $password = $_POST['password'];
        $rememberMe = isset($_POST['remember_me']);

        // SQL query to fetch user information
        $stmt = $conn->prepare("SELECT password_hash, role FROM users WHERE user_id = ?");
        if ($stmt === false) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("s", $userId);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($passwordHash, $role);
            $stmt->fetch();

            if (password_verify($password, $passwordHash)) {
                $_SESSION['user_id'] = $userId;
                $_SESSION['role'] = $role;
                
                // Debug: Log successful login
                error_log("Successful login for user: $userId with role: $role");

                if ($rememberMe) {
                    setcookie("user_id", $userId, time() + (86400 * 30), "/"); // Save user for 30 days
                }

                // Redirect based on user role
                switch ($role) {
                    case 'Power Admin':
                        error_log("Redirecting to admin_dashboard.php");
                        header("Location: admin_dashboard.php");
                        break;
                    case 'Student':
                        error_log("Redirecting to student_dashboard.php");
                        header("Location: student_dashboard.php");
                        break;
                    case 'Faculty':
                        error_log("Redirecting to faculty_dashboard.php");
                        header("Location: faculty_dashboard.php");
                        break;
                    case 'Scholarship Admin':
                        error_log("Redirecting to scholarship_admin_dashboard.php");
                        header("Location: scholarship_admin_dashboard.php");
                        break;
                    case 'Guidance Admin':
                        error_log("Redirecting to guidance_admin_dashboard.php");
                        header("Location: guidance_admin_dashboard.php");
                        break;
                    case 'Dormitory Admin':
                        error_log("Redirecting to admin_dormitory_dashboard.php");
                        header("Location: admin_dormitory_dashboard.php");
                        break;
                    case 'Registrar Admin':
                        error_log("Redirecting to registrar_dashboard.php");
                        header("Location: registrar_dashboard.php");
                        break;
                    default:
                        $errorMessage = "Unauthorized role.";
                        session_destroy();
                        exit();
                }
                exit();
            } else {
                $errorMessage = "Invalid password.";
                if (($_POST['source'] ?? '') === 'landing') {
                    $_SESSION['landing_error'] = $errorMessage;
                    header('Location: landing_page.php?open=login');
                    exit();
                }
            }
        } else {
            $errorMessage = "User ID does not exist.";
            if (($_POST['source'] ?? '') === 'landing') {
                $_SESSION['landing_error'] = $errorMessage;
                header('Location: landing_page.php?open=login');
                exit();
            }
        }
        $stmt->close();
    }
}

// If we reach here, there was an error or it's not a POST request
if (($_POST['source'] ?? '') === 'landing') {
    $_SESSION['landing_error'] = $errorMessage ?: "Login failed. Please try again.";
    header('Location: landing_page.php?open=login');
    exit();
} else {
    // Redirect to landing page if not from landing
    header('Location: landing_page.php');
    exit();
}
?>
