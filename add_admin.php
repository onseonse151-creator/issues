<?php
require_once 'config.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';
require_once 'includes/audit_logger.php';

// Security checks
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'Power Admin') {
    logSecurityEvent('unauthorized_access_attempt', ['page' => 'add_admin']);
    header('Location: login.php');
    exit();
}

if (!hasPermission('manage_users')) {
    logSecurityEvent('permission_denied', ['page' => 'add_admin']);
    header('Location: error_page.php?error=access_denied');
    exit();
}

// Rate limiting
if (!checkRateLimit('add_admin_view', 10, 300)) {
    logSecurityEvent('rate_limit_exceeded', ['action' => 'add_admin_view']);
    header('Location: error_page.php?error=rate_limit');
    exit();
}

audit_data_access('add_admin', null, 'view');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Add New Admin - NEUST Power Admin</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
  <link rel="stylesheet" href="assets/admin_theme.css">
  <style>
    /* Add Admin Specific Styles - Using existing theme variables */
    .add-admin-container {
      max-width: 1000px;
      margin: 0 auto;
      padding: var(--space-xl);
    }

    .form-container {
      background: var(--bg-card);
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow-lg);
      border: 1px solid var(--border-light);
      overflow: hidden;
    }

    .form-header {
      padding: var(--space-xl);
      border-bottom: 1px solid var(--border-light);
      background: linear-gradient(135deg, var(--bg-card), var(--bg-secondary));
    }

    .form-title {
      font-size: var(--font-size-xl);
      font-weight: 600;
      color: var(--text-primary);
      margin: 0 0 var(--space-sm) 0;
    }

    .form-subtitle {
      color: var(--text-secondary);
      font-size: var(--font-size-sm);
      margin: 0;
    }

    .progress-container {
      padding: var(--space-xl);
      border-bottom: 1px solid var(--border-light);
    }

  .progress-tracker {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
      margin-bottom: var(--space-lg);
    }

    .progress-step {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: var(--space-sm);
      z-index: 2;
      position: relative;
    }

    .step-circle {
      width: 48px;
      height: 48px;
      border-radius: var(--radius-full);
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 600;
      font-size: var(--font-size-sm);
      transition: all var(--transition-normal);
      border: 2px solid var(--border-medium);
      background: var(--bg-card);
      color: var(--text-secondary);
    }

    .step-circle.active {
      background: var(--primary);
      border-color: var(--primary);
      color: var(--text-white);
      box-shadow: var(--shadow-md);
    }

    .step-circle.completed {
      background: var(--success);
      border-color: var(--success);
      color: var(--text-white);
    }

    .step-label {
      font-size: var(--font-size-xs);
    font-weight: 500;
      color: var(--text-secondary);
      text-align: center;
      max-width: 80px;
    }

    .step-circle.active + .step-label,
    .step-circle.completed + .step-label {
      color: var(--text-primary);
      font-weight: 600;
    }

    .progress-line {
    position: absolute;
      top: 24px;
    left: 0;
    right: 0;
      height: 2px;
      background: var(--border-light);
      z-index: 1;
    }

    .progress-fill {
    height: 100%;
      background: linear-gradient(90deg, var(--primary), var(--success));
      border-radius: var(--radius-full);
      transition: width var(--transition-slow);
    }

    .form-content {
      padding: var(--space-2xl);
    }

  .form-step {
    display: none;
      animation: fadeIn 0.3s ease-in-out;
  }

    .form-step.active {
    display: block;
  }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .step-title {
      font-size: var(--font-size-lg);
      font-weight: 600;
      color: var(--text-primary);
      margin: 0 0 var(--space-lg) 0;
      display: flex;
      align-items: center;
      gap: var(--space-sm);
    }

    .step-title i {
      color: var(--primary);
    }

    .form-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: var(--space-lg);
    }

    .form-group {
      display: flex;
      flex-direction: column;
      gap: var(--space-sm);
    }

    .form-label {
      font-size: var(--font-size-sm);
      font-weight: 500;
      color: var(--text-primary);
      display: flex;
      align-items: center;
      gap: var(--space-xs);
    }

    .form-label.required::after {
      content: '*';
      color: var(--error);
      margin-left: var(--space-xs);
    }

    .form-input,
    .form-select {
      padding: var(--space-md);
      border: 2px solid var(--border-light);
      border-radius: var(--radius-lg);
      font-size: var(--font-size-sm);
      transition: all var(--transition-fast);
      background: var(--bg-card);
      color: var(--text-primary);
    }

    .form-input:focus,
    .form-select:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px var(--primary-light);
    }

    .form-input.error,
    .form-select.error {
      border-color: var(--error);
      box-shadow: 0 0 0 3px var(--error-light);
    }

  .error-message {
      color: var(--error);
      font-size: var(--font-size-xs);
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: var(--space-xs);
    }

    .form-actions {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: var(--space-xl);
      border-top: 1px solid var(--border-light);
      background: var(--bg-secondary);
    }

    .unit-cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: var(--space-md);
      margin-top: var(--space-md);
    }

    .unit-card {
      padding: var(--space-lg);
      border: 2px solid var(--border-light);
      border-radius: var(--radius-xl);
      cursor: pointer;
      transition: all var(--transition-normal);
      background: var(--bg-card);
      text-align: center;
    }

    .unit-card:hover {
      border-color: var(--primary);
      box-shadow: var(--shadow-md);
      transform: translateY(-2px);
    }

    .unit-card.selected {
      border-color: var(--primary);
      background: var(--primary-light);
      box-shadow: var(--shadow-md);
    }

    .unit-icon {
      width: 48px;
      height: 48px;
      border-radius: var(--radius-lg);
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto var(--space-sm);
      font-size: var(--font-size-lg);
      color: var(--text-white);
    }

    .unit-card:nth-child(1) .unit-icon { background: var(--info); }
    .unit-card:nth-child(2) .unit-icon { background: var(--success); }
    .unit-card:nth-child(3) .unit-icon { background: var(--warning); }

    .unit-name {
      font-weight: 600;
      color: var(--text-primary);
      margin: 0;
    }

    .unit-description {
      font-size: var(--font-size-xs);
      color: var(--text-secondary);
      margin: var(--space-xs) 0 0 0;
    }

    .password-strength {
      margin-top: var(--space-sm);
    }

    .strength-bar {
      height: 4px;
      background: var(--border-light);
      border-radius: var(--radius-full);
      overflow: hidden;
      margin-bottom: var(--space-xs);
    }

    .strength-fill {
      height: 100%;
      transition: all var(--transition-normal);
      border-radius: var(--radius-full);
    }

    .strength-weak { background: var(--error); width: 25%; }
    .strength-fair { background: var(--warning); width: 50%; }
    .strength-good { background: var(--info); width: 75%; }
    .strength-strong { background: var(--success); width: 100%; }

    .strength-text {
      font-size: var(--font-size-xs);
      font-weight: 500;
    }

    .strength-weak + .strength-text { color: var(--error); }
    .strength-fair + .strength-text { color: var(--warning); }
    .strength-good + .strength-text { color: var(--info); }
    .strength-strong + .strength-text { color: var(--success); }

    @media (max-width: 768px) {
      .add-admin-container {
        padding: var(--space-md);
      }
      
      .form-grid {
        grid-template-columns: 1fr;
      }
      
      .progress-tracker {
        flex-direction: column;
        gap: var(--space-md);
      }
      
      .progress-line {
        display: none;
      }
      
      .unit-cards {
        grid-template-columns: 1fr;
      }
      
      .form-actions {
        flex-direction: column;
        gap: var(--space-md);
    }
  }
</style>
</head>
<body>
  <?php include 'power_admin_header.php'; ?>
  
  <main class="main">
    <div class="add-admin-container">
      <!-- Page Header -->
      <div class="page-header">
        <h1 class="page-title">
          <i class="fas fa-user-plus"></i>
          Add New Admin
        </h1>
        <div class="page-actions">
          <a class="btn btn-secondary" href="admin_list.php">
            <i class="fas fa-arrow-left"></i> Back to Admin List
          </a>
        </div>
      </div>

    <!-- Form Container -->
    <div class="form-container">
      <!-- Form Header -->
      <div class="form-header">
        <h2 class="form-title">Create New Admin Account</h2>
        <p class="form-subtitle">Fill in the information below to create a new admin account for the system</p>
        
        <!-- Display Messages -->
        <?php if (isset($_SESSION['error_message'])): ?>
          <div class="alert alert-error" style="margin-top: var(--space-md); padding: var(--space-md); background: var(--error-light); color: var(--error); border: 1px solid var(--error); border-radius: var(--radius-md);">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($_SESSION['error_message']) ?>
          </div>
          <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success_message'])): ?>
          <div class="alert alert-success" style="margin-top: var(--space-md); padding: var(--space-md); background: var(--success-light); color: var(--success); border: 1px solid var(--success); border-radius: var(--radius-md);">
            <i class="fas fa-check-circle"></i>
            <?= htmlspecialchars($_SESSION['success_message']) ?>
          </div>
          <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>
      </div>

      <!-- Progress Tracker -->
      <div class="progress-container">
        <div class="progress-tracker">
          <div class="progress-step">
            <div class="step-circle active" data-step="1">1</div>
            <div class="step-label">Personal Info</div>
          </div>
          <div class="progress-step">
            <div class="step-circle" data-step="2">2</div>
            <div class="step-label">Contact Details</div>
          </div>
          <div class="progress-step">
            <div class="step-circle" data-step="3">3</div>
            <div class="step-label">Family Info</div>
          </div>
          <div class="progress-step">
            <div class="step-circle" data-step="4">4</div>
            <div class="step-label">Admin Setup</div>
          </div>
          <div class="progress-line">
            <div class="progress-fill" style="width: 25%"></div>
      </div>
    </div>
      </div>

      <!-- Form Content -->
      <div class="form-content">
        <form id="adminForm" method="POST" action="process_add_admin.php">
          <input type="hidden" name="csrf_token" value="<?= getCSRFToken() ?>">
          
          <!-- Step 1: Personal Information -->
          <div id="step1" class="form-step active" data-step="1">
            <h3 class="step-title">
              <i class="fas fa-user"></i>
              Personal Information
            </h3>
            <div class="form-grid">
              <div class="form-group">
                <label for="first_name" class="form-label required">
                  <i class="fas fa-user"></i>
                  First Name
                </label>
                <input type="text" id="first_name" name="first_name" class="form-input" required>
                <div class="error-message" id="firstNameError"></div>
              </div>
              <div class="form-group">
                <label for="middle_name" class="form-label">
                  <i class="fas fa-user"></i>
                  Middle Name
                </label>
                <input type="text" id="middle_name" name="middle_name" class="form-input">
                <div class="error-message" id="middleNameError"></div>
              </div>
              <div class="form-group">
                <label for="last_name" class="form-label required">
                  <i class="fas fa-user"></i>
                  Last Name
                </label>
                <input type="text" id="last_name" name="last_name" class="form-input" required>
                <div class="error-message" id="lastNameError"></div>
              </div>
              <div class="form-group">
                <label for="birth_date" class="form-label required">
                  <i class="fas fa-calendar"></i>
                  Birth Date
                </label>
                <input type="date" id="birth_date" name="birth_date" class="form-input" required>
                <div class="error-message" id="birthDateError"></div>
              </div>
            </div>
          </div>

      <!-- Step 2: Contact Information -->
      <div id="step2" class="form-step" data-step="2">
            <h3 class="step-title">
              <i class="fas fa-address-book"></i>
              Contact Information
            </h3>
            <div class="form-grid">
              <div class="form-group">
                <label for="email" class="form-label required">
                  <i class="fas fa-envelope"></i>
                  Email Address
                </label>
                <input type="email" id="email" name="email" class="form-input" required>
                <div class="error-message" id="emailError"></div>
              </div>
              <div class="form-group">
                <label for="phone" class="form-label required">
                  <i class="fas fa-phone"></i>
                  Phone Number
                </label>
                <input type="text" id="phone" name="phone" class="form-input" required>
                <div class="error-message" id="phoneError"></div>
              </div>
              <div class="form-group">
                <label for="current_address" class="form-label required">
                  <i class="fas fa-map-marker-alt"></i>
                  Current Address
                </label>
                <input type="text" id="current_address" name="current_address" class="form-input" required>
                <div class="error-message" id="currentAddressError"></div>
              </div>
              <div class="form-group">
                <label for="permanent_address" class="form-label required">
                  <i class="fas fa-home"></i>
                  Permanent Address
                </label>
                <input type="text" id="permanent_address" name="permanent_address" class="form-input" required>
                <div class="error-message" id="permanentAddressError"></div>
              </div>
            </div>
      </div>

      <!-- Step 3: Family Information -->
      <div id="step3" class="form-step" data-step="3">
            <h3 class="step-title">
              <i class="fas fa-users"></i>
              Family Information
            </h3>
            <div class="form-grid">
              <div class="form-group">
                <label for="mother_name" class="form-label required">
                  <i class="fas fa-female"></i>
                  Mother's Name
                </label>
                <input type="text" id="mother_name" name="mother_name" class="form-input" required>
                <div class="error-message" id="motherNameError"></div>
              </div>
              <div class="form-group">
                <label for="father_name" class="form-label required">
                  <i class="fas fa-male"></i>
                  Father's Name
                </label>
                <input type="text" id="father_name" name="father_name" class="form-input" required>
                <div class="error-message" id="fatherNameError"></div>
              </div>
            </div>
      </div>

      <!-- Step 4: Admin Information -->
      <div id="step4" class="form-step" data-step="4">
            <h3 class="step-title">
              <i class="fas fa-user-shield"></i>
              Admin Setup
            </h3>
            
            <!-- Unit Selection -->
            <div class="form-group">
              <label class="form-label required">
                <i class="fas fa-building"></i>
                Select Admin Unit
              </label>
              <div class="unit-cards">
                <div class="unit-card" data-unit="Dormitory Admin">
                  <div class="unit-icon">
                    <i class="fas fa-bed"></i>
                  </div>
                  <h4 class="unit-name">Dormitory</h4>
                  <p class="unit-description">Manage dormitory applications and residents</p>
                </div>
                <div class="unit-card" data-unit="Guidance Admin">
                  <div class="unit-icon">
                    <i class="fas fa-heart"></i>
                  </div>
                  <h4 class="unit-name">Guidance</h4>
                  <p class="unit-description">Handle guidance appointments and counseling</p>
                </div>
                <div class="unit-card" data-unit="Scholarship Admin">
                  <div class="unit-icon">
                    <i class="fas fa-graduation-cap"></i>
                  </div>
                  <h4 class="unit-name">Scholarship</h4>
                  <p class="unit-description">Manage scholarship applications and awards</p>
                </div>
              </div>
              <input type="hidden" id="unit" name="unit" required>
              <div class="error-message" id="unitError"></div>
            </div>

            <div class="form-grid">
              <div class="form-group">
                <label for="user_id" class="form-label required">
                  <i class="fas fa-id-card"></i>
                  User ID
                </label>
                <input type="text" id="user_id" name="user_id" class="form-input" required>
                <div class="error-message" id="userIdError"></div>
              </div>
              <div class="form-group">
                <label for="password" class="form-label required">
                  <i class="fas fa-lock"></i>
                  Password
                </label>
                <input type="password" id="password" name="password" class="form-input" required>
                <div class="password-strength">
                  <div class="strength-bar">
                    <div class="strength-fill" id="strengthFill"></div>
                  </div>
                  <div class="strength-text" id="strengthText">Enter a password</div>
                </div>
                <div class="error-message" id="passwordError"></div>
              </div>
              <div class="form-group">
                <label for="confirm_password" class="form-label required">
                  <i class="fas fa-lock"></i>
                  Confirm Password
                </label>
                <input type="password" id="confirm_password" class="form-input" required>
                <div class="error-message" id="confirmPasswordError"></div>
              </div>
            </div>
      </div>
    </form>
  </div>

      <!-- Form Actions -->
      <div class="form-actions">
        <button type="button" class="btn btn-secondary" id="prevBtn" style="display: none;">
          <i class="fas fa-arrow-left"></i> Previous
        </button>
        <div></div>
        <button type="button" class="btn btn-primary" id="nextBtn">
          Next <i class="fas fa-arrow-right"></i>
        </button>
        <button type="submit" class="btn btn-primary" id="submitBtn" form="adminForm" style="display: none;">
          <i class="fas fa-user-plus"></i> Create Admin
        </button>
      </div>
    </div>
    </div>
  </main>
    <script>
    document.addEventListener("DOMContentLoaded", function () {
    const steps = document.querySelectorAll(".form-step");
      const stepCircles = document.querySelectorAll(".step-circle");
      const progressFill = document.querySelector(".progress-fill");
      const nextBtn = document.getElementById("nextBtn");
      const prevBtn = document.getElementById("prevBtn");
      const submitBtn = document.getElementById("submitBtn");
    let currentStep = 0;                                                    
        
      // Unit card selection
      const unitCards = document.querySelectorAll(".unit-card");
      const unitInput = document.getElementById("unit");

      unitCards.forEach(card => {
        card.addEventListener("click", function() {
          unitCards.forEach(c => c.classList.remove("selected"));
          this.classList.add("selected");
          unitInput.value = this.dataset.unit;
          clearError("unitError");
        });
      });

      // Password strength indicator
      const passwordInput = document.getElementById("password");
      const strengthFill = document.getElementById("strengthFill");
      const strengthText = document.getElementById("strengthText");

      passwordInput.addEventListener("input", function() {
        const password = this.value;
        const strength = calculatePasswordStrength(password);
        
        strengthFill.className = "strength-fill";
        strengthFill.classList.add(strength.class);
        strengthText.textContent = strength.text;
        strengthText.className = "strength-text";
        strengthText.classList.add(strength.class);
      });

      function calculatePasswordStrength(password) {
        if (password.length === 0) {
          return { class: "", text: "Enter a password" };
        }
        if (password.length < 6) {
          return { class: "strength-weak", text: "Weak" };
        }
        if (password.length < 8) {
          return { class: "strength-fair", text: "Fair" };
        }
        if (password.length < 12) {
          return { class: "strength-good", text: "Good" };
        }
        return { class: "strength-strong", text: "Strong" };
      }

      // Form validation
      function validateStep(stepIndex) {
        const step = steps[stepIndex];
        const inputs = step.querySelectorAll("input[required], select[required]");
        let isValid = true;

        inputs.forEach(input => {
          if (!input.value.trim()) {
            showError(input, "This field is required");
            isValid = false;
          } else {
            clearError(input);
          }
        });

        // Special validations
        if (stepIndex === 1) { // Contact step
          const email = document.getElementById("email");
          const phone = document.getElementById("phone");
          
          if (email.value && !isValidEmail(email.value)) {
            showError(email, "Invalid email format");
            isValid = false;
          }
          
          if (phone.value && !isValidPhone(phone.value)) {
            showError(phone, "Phone number must be at least 10 digits");
            isValid = false;
          }
        }

        if (stepIndex === 3) { // Admin step
          const password = document.getElementById("password");
          const confirmPassword = document.getElementById("confirm_password");
          
          if (password.value && password.value.length < 8) {
            showError(password, "Password must be at least 8 characters");
            isValid = false;
          }
          
          if (confirmPassword.value && confirmPassword.value !== password.value) {
            showError(confirmPassword, "Passwords do not match");
            isValid = false;
          }
          
          if (!unitInput.value) {
            showError(unitInput, "Please select an admin unit");
            isValid = false;
          }
        }

        return isValid;
      }

      function showError(input, message) {
        const errorId = input.id + "Error";
        const errorElement = document.getElementById(errorId);
        if (errorElement) {
          errorElement.textContent = message;
          input.classList.add("error");
        }
      }

      function clearError(input) {
        const errorId = input.id + "Error";
        const errorElement = document.getElementById(errorId);
        if (errorElement) {
          errorElement.textContent = "";
          input.classList.remove("error");
        }
      }

      function clearErrorById(errorId) {
        const errorElement = document.getElementById(errorId);
        if (errorElement) {
          errorElement.textContent = "";
        }
      }

      function isValidEmail(email) {
        return /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/.test(email);
      }

      function isValidPhone(phone) {
        return /^[0-9]{10,}$/.test(phone);
      }

      function showStep(index) {
        steps.forEach((step, i) => {
          step.classList.toggle("active", i === index);
        });
        
        stepCircles.forEach((circle, i) => {
          circle.classList.remove("active", "completed");
          if (i < index) {
            circle.classList.add("completed");
          } else if (i === index) {
            circle.classList.add("active");
          }
        });

        const progress = ((index + 1) / steps.length) * 100;
        progressFill.style.width = progress + "%";

        // Update buttons
        prevBtn.style.display = index === 0 ? "none" : "inline-flex";
        nextBtn.style.display = index === steps.length - 1 ? "none" : "inline-flex";
        submitBtn.style.display = index === steps.length - 1 ? "inline-flex" : "none";
      }

      nextBtn.addEventListener("click", function() {
        if (validateStep(currentStep)) {
          currentStep++;
          showStep(currentStep);
        }
      });

      prevBtn.addEventListener("click", function() {
        currentStep--;
        showStep(currentStep);
      });

      // Real-time validation
      document.querySelectorAll("input, select").forEach(input => {
        input.addEventListener("blur", function() {
          if (this.hasAttribute("required") && !this.value.trim()) {
            showError(this, "This field is required");
          } else {
            clearError(this);
          }
        });

        input.addEventListener("input", function() {
          if (this.value.trim()) {
            clearError(this);
      }
    });
    });

      // Initialize
      showStep(currentStep);
    });
</script>


  </main>
</body>
</html>