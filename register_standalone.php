<?php 
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/csrf.php';

$landingError = $_SESSION['landing_error'] ?? '';
if (isset($_SESSION['landing_error'])) unset($_SESSION['landing_error']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Create Account - NEUST Student Services</title>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
	<link rel="stylesheet" href="assets/admin_theme.css">
	<link rel="stylesheet" href="assets/landing/auth.css">
	<style>
		body { background: var(--bg-secondary); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: var(--space-lg); }
		.page-wrap { width: 100%; max-width: 1100px; }
		/* Reuse auth-card layout as full page */
		.auth-card { max-width: 1100px; width: 100%; height: 90vh; max-height: 90vh; }
		/* Show both panes to mirror split layout */
		.auth-left { display: flex; }
		.auth-right { flex: 1; }
		.header-bar { display:flex; align-items:center; justify-content:space-between; padding: 8px 14px; border-bottom: 1px solid var(--border-light); background: linear-gradient(135deg, var(--bg-card), #fafbfc); }
		.header-bar .brand { display:flex; align-items:center; gap:10px; color: var(--primary); font-weight: 800; }
		.header-bar .brand img { width: 32px; height: 32px; }
		.header-actions { display:flex; gap:10px; }
		.header-actions a { text-decoration:none; }
	</style>
</head>
<body>
	<div class="page-wrap">
		<div class="auth-card" role="dialog" aria-modal="true" aria-labelledby="regTitle">
			<div class="header-bar">
				<div class="brand">
					<img src="assets/logo.png" alt="NEUST">
					<span>NEUST Student Services</span>
				</div>
				<div class="header-actions">
					<a class="btn btn-ghost" href="landing_page.php"><i class="fas fa-home"></i> Home</a>
					<a class="btn" href="landing_page.php?open=login"><i class="fas fa-sign-in-alt"></i> Login</a>
				</div>
			</div>
			<!-- Left promo sign-in pane -->
			<div class="auth-side auth-left">
				<div class="auth-pane visible" style="text-align:center;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:14px">
					<div class="auth-brand">
						<img src="assets/logo.png" alt="NEUST" class="auth-logo small" loading="lazy">
						<strong>NEUST</strong>
					</div>
					<h3 class="auth-title" style="margin-bottom:4px">Welcome Back!</h3>
					<div class="auth-sub" style="margin-bottom:16px">To keep connected with us please login with your personal info</div>
					<a href="landing_page.php?open=login" class="btn btn-ghost" style="min-width:180px">Sign In</a>
				</div>
			</div>
			<div class="auth-side auth-right">
				<div id="authPaneRegister" class="auth-pane visible">
					<h3 class="auth-title" id="regTitle">Create Account</h3>
					<div class="auth-sub">Join NEUST Student Services and access all features</div>
					<?php if (!empty($landingError)): ?>
						<div class="error-message" style="display:block;background:#ffe8e8;color:#b91c1c;border:1px solid #fca5a5;padding:10px;border-radius:8px;margin-bottom:10px;display:flex;gap:8px;align-items:center">
							<i class="fas fa-exclamation-circle"></i>
							<?= htmlspecialchars($landingError) ?>
						</div>
					<?php endif; ?>
					<div class="progress-container">
						<div class="progress-bar">
							<div class="step active" data-step="1"></div>
							<div class="step" data-step="2"></div>
							<div class="step" data-step="3"></div>
							<div class="step" data-step="4"></div>
						</div>
						<div class="step-indicators" style="display:flex; justify-content:space-between; gap:12px;">
							<div class="step-indicator active" data-step="1"><i class="fas fa-user"></i><span>Personal</span></div>
							<div class="step-indicator" data-step="2"><i class="fas fa-graduation-cap"></i><span>Academic</span></div>
							<div class="step-indicator" data-step="3"><i class="fas fa-phone"></i><span>Contact</span></div>
							<div class="step-indicator" data-step="4"><i class="fas fa-lock"></i><span>Security</span></div>
						</div>
					</div>

					<form id="registrationForm" method="POST" action="auth_register.php">
						<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
						<input type="hidden" id="role" name="role" value="Student">

						<div class="form-step active" data-step="1">
							<h2 class="step-title"><i class="fas fa-user"></i> Personal Information</h2>
							<div class="form-grid">
								<div class="form-group">
									<label for="studentId" class="form-label required">Student ID</label>
									<input type="text" id="studentId" name="user_id" class="form-input" placeholder="Enter your Student ID" required>
								</div>
								<div class="form-group">
									<label for="biologicalSex" class="form-label required">Biological Sex</label>
									<select id="biologicalSex" name="biological_sex" class="form-select" required>
										<option value="">Select Biological Sex</option>
										<option value="Male">Male</option>
										<option value="Female">Female</option>
									</select>
								</div>
								<div class="form-group">
									<label for="firstName" class="form-label required">First Name</label>
									<input type="text" id="firstName" name="first_name" class="form-input" placeholder="Enter your first name" required>
								</div>
								<div class="form-group">
									<label for="lastName" class="form-label required">Last Name</label>
									<input type="text" id="lastName" name="last_name" class="form-input" placeholder="Enter your last name" required>
								</div>
								<div class="form-group">
									<label for="middleName" class="form-label">Middle Name</label>
									<input type="text" id="middleName" name="middle_name" class="form-input" placeholder="Enter your middle name">
								</div>
								<div class="form-group">
									<label for="birthDate" class="form-label required">Birth Date</label>
									<input type="date" id="birthDate" name="birth_date" class="form-input" required>
								</div>
								<div class="form-group">
									<label for="nationality" class="form-label required">Nationality</label>
									<input type="text" id="nationality" name="nationality" class="form-input" placeholder="Enter your nationality" required>
								</div>
								<div class="form-group">
									<label for="religion" class="form-label required">Religion</label>
									<input type="text" id="religion" name="religion" class="form-input" placeholder="Enter your religion" required>
								</div>
								<div class="form-group">
									<label for="year" class="form-label required">Year Level</label>
									<input type="number" id="year" name="year" class="form-input" placeholder="Enter your year level" min="1" max="5" required>
								</div>
								<div class="form-group">
									<label for="section" class="form-label required">Section</label>
									<input type="text" id="section" name="section" class="form-input" placeholder="Enter your section" required>
								</div>
								<div class="form-group full-width">
									<label for="course" class="form-label required">Course</label>
									<input type="text" id="course" name="course" class="form-input" placeholder="Enter your course" required>
								</div>
							</div>
							<div class="form-actions">
								<button type="button" class="btn btn-ghost next" id="nextBtn1">Next <i class="fas fa-arrow-right"></i></button>
							</div>
						</div>

						<div class="form-step" data-step="2">
							<h2 class="step-title"><i class="fas fa-phone"></i> Contact Information</h2>
							<div class="form-grid">
								<div class="form-group full-width">
									<label for="email" class="form-label required">Email Address</label>
									<input type="email" id="email" name="email" class="form-input" placeholder="Enter your email address" required>
								</div>
								<div class="form-group">
									<label for="phone" class="form-label required">Phone Number</label>
									<input type="tel" id="phone" name="phone" class="form-input" placeholder="Enter your phone number" required>
								</div>
								<div class="form-group">
									<label for="currentAddress" class="form-label required">Current Address</label>
									<input type="text" id="currentAddress" name="current_address" class="form-input" placeholder="Enter your current address" required>
								</div>
								<div class="form-group">
									<label for="permanentAddress" class="form-label required">Permanent Address</label>
									<input type="text" id="permanentAddress" name="permanent_address" class="form-input" placeholder="Enter your permanent address" required>
								</div>
							</div>
							<div class="form-actions">
								<button type="button" class="btn btn-ghost prev" id="prevBtn2"><i class="fas fa-arrow-left"></i> Previous</button>
								<button type="button" class="btn btn-primary next" id="nextBtn2">Next <i class="fas fa-arrow-right"></i></button>
							</div>
						</div>

						<div class="form-step" data-step="3">
							<h2 class="step-title"><i class="fas fa-users"></i> Family Information</h2>
							<div class="form-grid">
								<div class="form-group">
									<label for="motherName" class="form-label required">Mother's Name</label>
									<input type="text" id="motherName" name="motherName" class="form-input" placeholder="Enter mother's full name" required>
								</div>
								<div class="form-group">
									<label for="fatherName" class="form-label required">Father's Name</label>
									<input type="text" id="fatherName" name="fatherName" class="form-input" placeholder="Enter father's full name" required>
								</div>
								<div class="form-group">
									<label for="motherWork" class="form-label required">Mother's Occupation</label>
									<input type="text" id="motherWork" name="motherWork" class="form-input" placeholder="Enter mother's occupation" required>
								</div>
								<div class="form-group">
									<label for="fatherWork" class="form-label required">Father's Occupation</label>
									<input type="text" id="fatherWork" name="fatherWork" class="form-input" placeholder="Enter father's occupation" required>
								</div>
								<div class="form-group">
									<label for="motherContact" class="form-label required">Mother's Contact</label>
									<input type="tel" id="motherContact" name="motherContact" class="form-input" placeholder="Enter mother's contact number" required>
								</div>
								<div class="form-group">
									<label for="fatherContact" class="form-label required">Father's Contact</label>
									<input type="tel" id="fatherContact" name="fatherContact" class="form-input" placeholder="Enter father's contact number" required>
								</div>
								<div class="form-group full-width">
									<label for="siblingsCount" class="form-label required">Number of Siblings</label>
									<input type="number" id="siblingsCount" name="siblingsCount" class="form-input" placeholder="Enter number of siblings" min="0" required>
								</div>
							</div>
							<div class="form-actions">
								<button type="button" class="btn btn-ghost prev" id="prevBtn3"><i class="fas fa-arrow-left"></i> Previous</button>
								<button type="button" class="btn btn-primary next" id="nextBtn3">Next <i class="fas fa-arrow-right"></i></button>
							</div>
						</div>

						<div class="form-step" data-step="4">
							<h2 class="step-title"><i class="fas fa-lock"></i> Account Security</h2>
							<div class="form-grid">
								<div class="form-group full-width">
									<label for="password" class="form-label required">Password</label>
									<div class="input-wrap">
										<input type="password" id="password" name="password" class="form-input" placeholder="Create a strong password" required>
										<button type="button" class="eye" data-eye="password" aria-label="Show password"><i class="fas fa-eye"></i></button>
									</div>
									<div class="password-requirements">
										<span id="req-length">Minimum 8 characters</span>
										<span id="req-uppercase">At least one uppercase letter</span>
										<span id="req-lowercase">At least one lowercase letter</span>
										<span id="req-number">At least one number</span>
									</div>
								</div>
								<div class="form-group full-width">
									<label for="confirmPassword" class="form-label required">Confirm Password</label>
									<div class="input-wrap">
										<input type="password" id="confirmPassword" name="confirmPassword" class="form-input" placeholder="Confirm your password" required>
										<button type="button" class="eye" data-eye="confirmPassword" aria-label="Show password"><i class="fas fa-eye"></i></button>
									</div>
									<div class="error-message" id="passwordMatchError" style="display: none;">
										<i class="fas fa-exclamation-circle"></i>
										Passwords do not match
									</div>
								</div>
							</div>
							<div class="form-actions">
								<button type="button" class="btn btn-ghost prev" id="prevBtn4"><i class="fas fa-arrow-left"></i> Previous</button>
								<button type="submit" class="btn btn-primary" id="submitBtn"><i class="fas fa-user-plus"></i> Create Account</button>
							</div>
							<div style="margin-top:16px;text-align:center">Have an account? <a href="landing_page.php?open=login" style="text-decoration:underline;">Sign in</a></div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>

	<script src="assets/landing/auth.js"></script>
</body>
</html>


