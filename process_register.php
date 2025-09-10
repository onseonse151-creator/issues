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

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$successMessage = "";
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!csrf_validate($_POST['csrf_token'] ?? null)) {
        $errors[] = "Invalid request. Please refresh and try again.";
        if (($_POST['source'] ?? '') === 'landing') {
            $_SESSION['landing_error'] = end($errors);
            header('Location: landing_page.php?open=register');
            exit;
        }
    } else {
        // Collect all required fields from POST
        $studentId = trim($_POST['user_id'] ?? '');
        $firstName = trim($_POST['first_name'] ?? '');
        $middleName = trim($_POST['middle_name'] ?? '');
        $lastName = trim($_POST['last_name'] ?? '');
        $birthDate = $_POST['birth_date'] ?? '';
        $nationality = trim($_POST['nationality'] ?? '');
        $religion = trim($_POST['religion'] ?? '');
        $biologicalSex = trim($_POST['biological_sex'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $currentAddress = trim($_POST['current_address'] ?? '');
        $permanentAddress = trim($_POST['permanent_address'] ?? '');
        $year = $_POST['year'] ?? '';
        $section = trim($_POST['section'] ?? '');
        $course = trim($_POST['course'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $rawPassword = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirmPassword'] ?? '';
        $terms = isset($_POST['terms']);

        // Placeholders for parent/family info
        $motherName = 'N/A';
        $motherWork = 'N/A';
        $motherContact = 'N/A';
        $fatherName = 'N/A';
        $fatherWork = 'N/A';
        $fatherContact = 'N/A';
        $siblingsCount = 0;

        // Role and other defaults
        $role = "Student";
        $status = "Inactive";
        $gpa = 0.0;
        $family_income = 0.0;
        $unit = null;
        $profile_picture = null;

        // Comprehensive validation for multi-step form
        if (empty($studentId)) {
            $errors[] = "Please provide your Student ID to continue with registration.";
        } elseif (strlen($studentId) < 3) {
            $errors[] = "Student ID must contain at least 3 characters for verification purposes.";
        }
        
        if (empty($firstName)) {
            $errors[] = "First name is required for your student profile.";
        }
        
        if (empty($lastName)) {
            $errors[] = "Last name is required for your student profile.";
        }
        
        if (empty($birthDate)) {
            $errors[] = "Please provide your birth date for age verification.";
        }
        
        if (empty($nationality)) {
            $errors[] = "Please specify your nationality for enrollment records.";
        }
        
        if (empty($religion)) {
            $errors[] = "Please indicate your religious affiliation for student records.";
        }
        
        if (empty($biologicalSex)) {
            $errors[] = "Please specify your biological sex for demographic records.";
        }
        
        if (empty($email)) {
            $errors[] = "A valid email address is required for account verification and communication.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address format (e.g., student@example.com).";
        }
        
        if (empty($phone)) {
            $errors[] = "Phone number is required for emergency contact purposes.";
        } elseif (!preg_match('/^[\+]?[0-9\s\-\(\)]{10,}$/', $phone)) {
            $errors[] = "Please enter a valid phone number format (e.g., +63 912 345 6789).";
        }
        
        if (empty($currentAddress)) {
            $errors[] = "Current address is required for student records and correspondence.";
        }
        
        if (empty($permanentAddress)) {
            $errors[] = "Permanent address is required for official student documentation.";
        }
        
        if (empty($year)) {
            $errors[] = "Please select your current year level for academic planning.";
        }
        
        if (empty($section)) {
            $errors[] = "Please specify your section for class scheduling and organization.";
        }
        
        if (empty($course)) {
            $errors[] = "Please select your enrolled course for academic tracking.";
        }
        
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $rawPassword)) { 
            $errors[] = "Password must be at least 8 characters long and include uppercase letters, lowercase letters, and numbers for security."; 
        }
        if ($rawPassword !== $confirmPassword) {
            $errors[] = "Password confirmation does not match. Please ensure both password fields are identical.";
        }
        if (!$terms) {
            $errors[] = "You must accept the Terms of Service and Privacy Policy to create your account.";
        }

        // Check if user already exists
        if (!$errors) {
            $checkStmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? OR email = ?");
            if ($checkStmt) {
                $checkStmt->bind_param("ss", $studentId, $email);
                $checkStmt->execute();
                $checkStmt->store_result();
                if ($checkStmt->num_rows > 0) {
                    $errors[] = "This Student ID or Email address is already registered. Please use different credentials or contact support if you believe this is an error.";
                }
                $checkStmt->close();
            } else {
                $errors[] = "We're experiencing technical difficulties. Please try again in a few moments or contact our support team for assistance.";
            }
        }

        // If no errors, create user
        if (!$errors) {
            $password = password_hash($rawPassword, PASSWORD_DEFAULT);
            $stmt = $conn->prepare(
                "INSERT INTO users
                (user_id, first_name, middle_name, last_name, birth_date, nationality, religion, biological_sex, email, phone, current_address, permanent_address, role, password_hash, mother_name, mother_work, mother_contact, father_name, father_work, father_contact, siblings_count, unit, profile_picture, status, year, section, course, department, gpa, family_income)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            if ($stmt) {
                $stmt->bind_param(
                    "sssssssssssssssssssssssssssdds",
                    $studentId,
                    $firstName,
                    $middleName,
                    $lastName,
                    $birthDate,
                    $nationality,
                    $religion,
                    $biologicalSex,
                    $email,
                    $phone,
                    $currentAddress,
                    $permanentAddress,
                    $role,
                    $password,
                    $motherName,
                    $motherWork,
                    $motherContact,
                    $fatherName,
                    $fatherWork,
                    $fatherContact,
                    $siblingsCount,
                    $unit,
                    $profile_picture,
                    $status,
                    $year,
                    $section,
                    $course,
                    $department,
                    $gpa,
                    $family_income
                );
                if ($stmt->execute()) {
                    if (($_POST['source'] ?? '') === 'landing') {
                        $_SESSION['landing_success'] = 'Congratulations! Your account has been successfully created. You can now access all NEUST student services.';
                        header('Location: landing_page.php?open=login');
                    } elseif (($_POST['source'] ?? '') === 'registration_modal') {
                        $_SESSION['registration_success'] = 'Congratulations! Your account has been successfully created. You can now access all NEUST student services.';
                        header('Location: registration_modal.php?success=1');
                    } else {
                        header('Location: landing_page.php?registered=1');
                    }
                    exit;
                } else {
                    $errors[] = "Error saving record: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $errors[] = "System error. Please try again later.";
            }
        }

        if (($_POST['source'] ?? '') === 'landing') {
            $_SESSION['landing_error'] = end($errors);
            header('Location: landing_page.php?open=register');
            exit;
        } elseif (($_POST['source'] ?? '') === 'registration_modal') {
            $_SESSION['registration_error'] = end($errors);
            header('Location: registration_modal.php?error=1');
            exit;
        }
    }
}

// If we reach here, there was an error or it's not a POST request
if (($_POST['source'] ?? '') === 'landing') {
    $_SESSION['landing_error'] = end($errors) ?: "Registration failed. Please try again.";
    header('Location: landing_page.php?open=register');
    exit();
} elseif (($_POST['source'] ?? '') === 'registration_modal') {
    $_SESSION['registration_error'] = end($errors) ?: "Registration failed. Please try again.";
    header('Location: registration_modal.php?error=1');
    exit();
} else {
    // Redirect to landing page if not from landing
    header('Location: landing_page.php');
    exit();
}
?>