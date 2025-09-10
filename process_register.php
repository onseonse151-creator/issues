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
$fieldErrors = [];

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

        // Comprehensive validation for multi-step form (field-scoped)
        if (empty($studentId)) {
            $fieldErrors['user_id'] = "Student ID is required.";
        } elseif (strlen($studentId) < 3) {
            $fieldErrors['user_id'] = "Student ID must be at least 3 characters.";
        }
        
        if (empty($firstName)) {
            $fieldErrors['first_name'] = "First name is required.";
        }
        
        if (empty($lastName)) {
            $fieldErrors['last_name'] = "Last name is required.";
        }
        
        if (empty($birthDate)) {
            $fieldErrors['birth_date'] = "Birth date is required.";
        }
        
        if (empty($nationality)) {
            $fieldErrors['nationality'] = "Nationality is required.";
        }
        
        if (empty($religion)) {
            $fieldErrors['religion'] = "Religion is required.";
        }
        
        if (empty($biologicalSex)) {
            $fieldErrors['biological_sex'] = "Please select sex.";
        }
        
        if (empty($email)) {
            $fieldErrors['email'] = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $fieldErrors['email'] = "Please enter a valid email address.";
        }
        
        if (empty($phone)) {
            $fieldErrors['phone'] = "Phone number is required.";
        } elseif (!preg_match('/^[\+]?[0-9\s\-\(\)]{10,}$/', $phone)) {
            $fieldErrors['phone'] = "Please enter a valid phone number.";
        }
        
        if (empty($currentAddress)) {
            $fieldErrors['current_address'] = "Current address is required.";
        }
        
        if (empty($permanentAddress)) {
            $fieldErrors['permanent_address'] = "Permanent address is required.";
        }
        
        if (empty($year)) {
            $fieldErrors['year'] = "Please select your year level.";
        }
        
        if (empty($section)) {
            $fieldErrors['section'] = "Section is required.";
        }
        
        if (empty($course)) {
            $fieldErrors['course'] = "Course is required.";
        }
        
        if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $rawPassword)) { 
            $fieldErrors['password'] = "Password must be 8+ characters with uppercase, lowercase, and number."; 
        }
        if ($rawPassword !== $confirmPassword) {
            $fieldErrors['confirmPassword'] = "Passwords do not match.";
        }
        if (!$terms) {
            $fieldErrors['terms'] = "You must accept the Terms and Privacy Policy.";
        }

        // Check if user already exists
        if (!$fieldErrors) {
            $checkStmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? OR email = ?");
            if ($checkStmt) {
                $checkStmt->bind_param("ss", $studentId, $email);
                $checkStmt->execute();
                $checkStmt->store_result();
                if ($checkStmt->num_rows > 0) {
                    // Determine which field conflicts if possible
                    $dupStmt = $conn->prepare("SELECT user_id, email FROM users WHERE user_id = ? OR email = ? LIMIT 1");
                    if ($dupStmt) {
                        $dupStmt->bind_param("ss", $studentId, $email);
                        $dupStmt->execute();
                        $dupStmt->bind_result($existingUserId, $existingEmail);
                        if ($dupStmt->fetch()) {
                            if ($existingUserId === $studentId) {
                                $fieldErrors['user_id'] = "This Student ID is already registered.";
                            }
                            if ($existingEmail === $email) {
                                $fieldErrors['email'] = "This email is already registered.";
                            }
                        }
                        $dupStmt->close();
                    } else {
                        $fieldErrors['user_id'] = "This Student ID or Email is already registered.";
                    }
                }
                $checkStmt->close();
            } else {
                $fieldErrors['user_id'] = "System error. Please try again shortly.";
            }
        }

        // If no errors, create user
        if (!$fieldErrors) {
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
                    unset($_SESSION['form_errors']);
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
                    $fieldErrors['user_id'] = "Error saving record: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $fieldErrors['user_id'] = "System error. Please try again later.";
            }
        }

        // If there are field errors, persist them to the session (scoped to step and fields)
        if ($fieldErrors) {
            // Map fields to their corresponding steps
            $fieldToStep = [
                'user_id' => 1,
                'first_name' => 1,
                'middle_name' => 1,
                'last_name' => 1,
                'birth_date' => 1,
                'nationality' => 1,
                'religion' => 1,
                'biological_sex' => 1,
                'year' => 2,
                'section' => 2,
                'course' => 2,
                'department' => 2,
                'email' => 3,
                'phone' => 3,
                'current_address' => 3,
                'permanent_address' => 3,
                'password' => 4,
                'confirmPassword' => 4,
                'terms' => 4,
            ];
            $errorStep = 1;
            foreach ($fieldErrors as $f => $_msg) {
                if (isset($fieldToStep[$f])) {
                    $errorStep = max($errorStep, $fieldToStep[$f]);
                }
            }

            // Persist values (except sensitive)
            $persistValues = [
                'user_id' => $studentId,
                'first_name' => $firstName,
                'middle_name' => $middleName,
                'last_name' => $lastName,
                'birth_date' => $birthDate,
                'nationality' => $nationality,
                'religion' => $religion,
                'biological_sex' => $biologicalSex,
                'email' => $email,
                'phone' => $phone,
                'current_address' => $currentAddress,
                'permanent_address' => $permanentAddress,
                'year' => $year,
                'section' => $section,
                'course' => $course,
                'department' => $department,
            ];

            $_SESSION['form_errors'] = [
                'fields' => $fieldErrors,
                'values' => $persistValues,
                'step' => $errorStep,
                'source' => ($_POST['source'] ?? ''),
            ];

            // Also set a generic error message for legacy display if needed
            $genericError = reset($fieldErrors) ?: 'Please correct the highlighted fields.';

            if (($_POST['source'] ?? '') === 'landing') {
                $_SESSION['landing_error'] = is_string($genericError) ? $genericError : 'Please correct the highlighted fields.';
                header('Location: landing_page.php?open=register');
                exit;
            } elseif (($_POST['source'] ?? '') === 'registration_modal') {
                $_SESSION['registration_error'] = is_string($genericError) ? $genericError : 'Please correct the highlighted fields.';
                header('Location: registration_modal.php?error=1');
                exit;
            }
        }
    }
}

// If we reach here, there was an error or it's not a POST request
if (($_POST['source'] ?? '') === 'landing') {
    $_SESSION['landing_error'] = end($errors) ?: (isset($_SESSION['form_errors']) ? 'Please correct the highlighted fields.' : "Registration failed. Please try again.");
    header('Location: landing_page.php?open=register');
    exit();
} elseif (($_POST['source'] ?? '') === 'registration_modal') {
    $_SESSION['registration_error'] = end($errors) ?: (isset($_SESSION['form_errors']) ? 'Please correct the highlighted fields.' : "Registration failed. Please try again.");
    header('Location: registration_modal.php?error=1');
    exit();
} else {
    // Redirect to landing page if not from landing
    header('Location: landing_page.php');
    exit();
}
?>