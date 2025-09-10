<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$host = "localhost";
$user = "root";
$password = "";
$dbname = "student_services_db"; 
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Messages and modal control
$registrationError = $_SESSION['registration_error'] ?? '';
$registrationSuccess = $_SESSION['registration_success'] ?? '';
if (isset($_SESSION['registration_error'])) unset($_SESSION['registration_error']);
if (isset($_SESSION['registration_success'])) unset($_SESSION['registration_success']);

require_once __DIR__ . '/csrf.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEUST Student Registration</title>
    
    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/landing/landing.css">
    <link rel="stylesheet" href="assets/landing/auth.css">
    
    <style>
        /* Professional Registration Modal Styles - Landscape Design */
        .registration-overlay {
            position: fixed;
            inset: 0;
            background: linear-gradient(135deg, rgba(2, 31, 61, 0.95) 0%, rgba(10, 58, 107, 0.9) 50%, rgba(212, 175, 55, 0.1) 100%);
            backdrop-filter: blur(20px);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            opacity: 1;
            animation: fadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .registration-card {
            position: relative;
            width: 95%;
            max-width: 1200px;
            height: 85vh;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(30px);
            border-radius: 32px;
            box-shadow: 
                0 32px 64px -12px rgba(0, 0, 0, 0.3),
                0 0 0 1px rgba(255, 255, 255, 0.4),
                inset 0 1px 0 rgba(255, 255, 255, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.3);
            overflow: hidden;
            transform: translateY(0) scale(1);
            animation: slideUp 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: row;
        }
        
        .registration-progress-sidebar {
            width: 350px;
            background: linear-gradient(135deg, #021f3d 0%, #0a3a6b 40%, #1e4a7c 70%, #d4af37 100%);
            color: white;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }
        
        .registration-progress-sidebar::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(212, 175, 55, 0.1) 0%, transparent 50%),
                linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.05) 50%, transparent 70%);
            opacity: 0.8;
        }
        
        .registration-form-container {
            position: relative;
            flex: 1;
            padding: 3rem;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 50%, #f1f5f9 100%);
            overflow-y: auto;
            max-height: 85vh;
        }
        
        .registration-form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 10% 10%, rgba(2, 31, 61, 0.02) 0%, transparent 50%),
                radial-gradient(circle at 90% 90%, rgba(212, 175, 55, 0.02) 0%, transparent 50%);
            opacity: 1;
        }
        
        .registration-close {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #021f3d;
            font-size: 1.5rem;
            width: 44px;
            height: 44px;
            border-radius: 50%;
            cursor: pointer;
            z-index: 10;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        .registration-close:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: scale(1.1) rotate(90deg);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .progress-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            font-size: 1.75rem;
            font-weight: 900;
            text-align: center;
            justify-content: center;
            position: relative;
            z-index: 2;
        }
        
        .progress-title {
            font-size: 2.5rem;
            font-weight: 900;
            margin-bottom: 0.5rem;
            color: white;
            text-align: center;
            position: relative;
            z-index: 2;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .progress-subtitle {
            font-size: 1.125rem;
            color: rgba(255, 255, 255, 0.85);
            margin-bottom: 2.5rem;
            text-align: center;
            font-weight: 500;
            position: relative;
            z-index: 2;
            line-height: 1.6;
        }
        
        .progress-steps {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            position: relative;
            z-index: 2;
            flex: 1;
        }
        
        .progress-step {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1.25rem;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border-radius: 16px;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            border: 2px solid transparent;
            position: relative;
            overflow: hidden;
        }
        
        .progress-step::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.1), transparent);
            transition: left 0.6s ease;
        }
        
        .progress-step:hover::before {
            left: 100%;
        }
        
        .progress-step.active {
            background: rgba(255, 255, 255, 0.15);
            border-color: rgba(255, 255, 255, 0.3);
            transform: translateX(8px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        
        .progress-step.completed {
            background: rgba(34, 197, 94, 0.15);
            border-color: rgba(34, 197, 94, 0.3);
        }
        
        .progress-step-number {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.125rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 2px solid rgba(255, 255, 255, 0.2);
        }
        
        .progress-step.active .progress-step-number {
            background: rgba(255, 255, 255, 0.25);
            transform: scale(1.15);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }
        
        .progress-step.completed .progress-step-number {
            background: #22c55e;
            border-color: #16a34a;
        }
        
        .progress-step-content h4 {
            margin: 0 0 0.25rem;
            font-size: 1rem;
            font-weight: 700;
            color: white;
        }
        
        .progress-step-content p {
            margin: 0;
            font-size: 0.875rem;
            opacity: 0.85;
            line-height: 1.4;
        }
        
        .registration-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            font-size: 1.5rem;
            font-weight: 900;
            text-align: center;
            justify-content: center;
            position: relative;
            z-index: 2;
        }
        
        .registration-logo {
            width: 64px;
            height: 64px;
            border-radius: 20px;
            box-shadow: 
                0 12px 30px -8px rgba(0, 0, 0, 0.25),
                0 0 0 1px rgba(255, 255, 255, 0.2);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .registration-logo:hover {
            transform: scale(1.05) rotate(5deg);
        }
        
        .registration-title {
            font-size: 2.75rem;
            font-weight: 900;
            margin-bottom: 0.75rem;
            color: inherit;
            text-align: center;
            background: linear-gradient(135deg, #021f3d 0%, #0a3a6b 40%, #1e4a7c 70%, #d4af37 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            position: relative;
            z-index: 2;
        }
        
        .registration-sub {
            font-size: 1.125rem;
            color: #64748b;
            margin-bottom: 2.5rem;
            text-align: center;
            font-weight: 500;
            line-height: 1.6;
            position: relative;
            z-index: 2;
        }
        
        .form-step {
            display: none;
            flex-direction: column;
            gap: 1.5rem;
            animation: fadeInStep 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            z-index: 2;
        }
        
        .form-step.active {
            display: flex;
        }
        
        .form-step-header {
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
        }
        
        .form-step-title {
            font-size: 2rem;
            font-weight: 800;
            color: #021f3d;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, #021f3d 0%, #0a3a6b 50%, #d4af37 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .form-step-description {
            font-size: 1rem;
            color: #64748b;
            margin-bottom: 0;
            line-height: 1.6;
        }
        
        .form-navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 2px solid rgba(2, 31, 61, 0.08);
            position: relative;
            z-index: 2;
        }
        
        .form-nav-btn {
            padding: 14px 28px;
            border: 2px solid transparent;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }
        
        .form-nav-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.6s ease;
        }
        
        .form-nav-btn:hover::before {
            left: 100%;
        }
        
        .form-nav-btn.prev {
            background: rgba(2, 31, 61, 0.08);
            color: #021f3d;
            border-color: rgba(2, 31, 61, 0.15);
        }
        
        .form-nav-btn.prev:hover {
            background: rgba(2, 31, 61, 0.15);
            border-color: rgba(2, 31, 61, 0.25);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(2, 31, 61, 0.15);
        }
        
        .form-nav-btn.next {
            background: linear-gradient(135deg, #021f3d 0%, #0a3a6b 40%, #1e4a7c 70%, #d4af37 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(2, 31, 61, 0.3);
        }
        
        .form-nav-btn.next:hover {
            background: linear-gradient(135deg, #011a33 0%, #083055 40%, #1a3f6b 70%, #c19d2e 100%);
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(2, 31, 61, 0.4);
        }
        
        .form-nav-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }
        
        .registration-form {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            position: relative;
            z-index: 2;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            position: relative;
        }
        
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        
        .registration-input {
            width: 100%;
            padding: 20px 24px;
            border: 2px solid rgba(2, 31, 61, 0.08);
            border-radius: 16px;
            font-size: 1rem;
            background: rgba(255, 255, 255, 0.9);
            color: #021f3d;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(15px);
            font-weight: 500;
            position: relative;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }
        
        .registration-input::placeholder {
            color: #94a3b8;
            font-weight: 400;
            transition: color 0.3s ease;
        }
        
        .registration-input:focus {
            outline: none;
            border-color: #d4af37;
            background: rgba(255, 255, 255, 1);
            box-shadow: 
                0 0 0 4px rgba(212, 175, 55, 0.12),
                0 8px 25px rgba(0, 0, 0, 0.08),
                0 0 0 1px rgba(212, 175, 55, 0.2);
            transform: translateY(-2px);
        }
        
        .registration-input:hover {
            border-color: rgba(2, 31, 61, 0.15);
            background: rgba(255, 255, 255, 0.95);
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.06);
        }
        
        .registration-input:focus::placeholder {
            color: #cbd5e1;
        }
        
        .input-wrap {
            position: relative;
        }
        
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: rgba(2, 31, 61, 0.6);
            cursor: pointer;
            font-size: 1.125rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 0.5rem;
            border-radius: 8px;
        }
        
        .password-toggle:hover {
            color: #021f3d;
            background: rgba(2, 31, 61, 0.05);
            transform: translateY(-50%) scale(1.1);
        }
        
        .password-requirements {
            font-size: 0.875rem;
            color: #64748b;
            margin-top: 0.75rem;
            padding: 1rem;
            background: rgba(2, 31, 61, 0.03);
            border-radius: 12px;
            border: 1px solid rgba(2, 31, 61, 0.08);
        }
        
        .password-requirements span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            transition: all 0.3s ease;
            font-weight: 500;
        }
        
        .password-requirements span:last-child {
            margin-bottom: 0;
        }
        
        .password-requirements span::before {
            content: '○';
            font-size: 0.75rem;
            transition: all 0.3s ease;
        }
        
        .password-requirements span.valid {
            color: #059669;
        }
        
        .password-requirements span.valid::before {
            content: '✓';
            color: #059669;
            font-weight: bold;
        }
        
        .password-requirements span.invalid {
            color: #dc2626;
        }
        
        .password-requirements span.invalid::before {
            content: '○';
            color: #dc2626;
        }
        
        .form-options {
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 1.5rem 0;
            padding: 1.5rem;
            background: rgba(2, 31, 61, 0.03);
            border-radius: 16px;
            border: 1px solid rgba(2, 31, 61, 0.08);
        }
        
        .terms-agreement {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            font-size: 0.875rem;
            color: #021f3d;
            cursor: pointer;
            line-height: 1.6;
        }
        
        .terms-agreement input[type="checkbox"] {
            width: 20px;
            height: 20px;
            accent-color: #d4af37;
            border-radius: 4px;
            margin-top: 0.125rem;
            flex-shrink: 0;
        }
        
        .terms-link {
            color: #d4af37;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .terms-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: #d4af37;
            transition: width 0.3s ease;
        }
        
        .terms-link:hover {
            color: #c19d2e;
        }
        
        .terms-link:hover::after {
            width: 100%;
        }
        
        .registration-btn {
            width: 100%;
            padding: 20px 32px;
            background: linear-gradient(135deg, #021f3d 0%, #0a3a6b 40%, #1e4a7c 70%, #d4af37 100%);
            color: white;
            border: 2px solid transparent;
            border-radius: 16px;
            font-size: 1.125rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(15px);
            box-shadow: 0 8px 25px rgba(2, 31, 61, 0.3);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .registration-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.25), transparent);
            transition: left 0.6s ease;
        }
        
        .registration-btn:hover::before {
            left: 100%;
        }
        
        .registration-btn:hover {
            background: linear-gradient(135deg, #011a33 0%, #083055 40%, #1a3f6b 70%, #c19d2e 100%);
            border-color: transparent;
            transform: translateY(-3px);
            box-shadow: 
                0 12px 35px rgba(2, 31, 61, 0.4),
                0 0 0 1px rgba(255, 255, 255, 0.1);
        }
        
        .registration-btn:active {
            transform: translateY(-1px);
        }
        
        .registration-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }
        
        .registration-socials {
            display: flex;
            justify-content: center;
            gap: 1.5rem;
            margin-top: 2rem;
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 2;
        }
        
        .registration-socials a {
            width: 60px;
            height: 60px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #021f3d;
            font-size: 1.25rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            backdrop-filter: blur(15px);
            border: 2px solid rgba(2, 31, 61, 0.08);
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        }
        
        .registration-socials a::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(2, 31, 61, 0.05) 0%, rgba(212, 175, 55, 0.05) 100%);
            opacity: 0;
            transition: opacity 0.4s ease;
        }
        
        .registration-socials a:hover::before {
            opacity: 1;
        }
        
        .registration-socials a:hover {
            background: rgba(255, 255, 255, 1);
            transform: translateY(-4px) scale(1.1);
            box-shadow: 
                0 12px 30px rgba(0, 0, 0, 0.15),
                0 0 0 2px rgba(212, 175, 55, 0.2);
            border-color: #d4af37;
        }
        
        .registration-switch-text {
            text-align: center;
            margin-top: 1.5rem;
            font-size: 0.875rem;
            color: #021f3d;
            opacity: 0.8;
            position: relative;
            z-index: 2;
        }
        
        .registration-switch-link {
            color: #d4af37;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.3s ease;
            position: relative;
        }
        
        .registration-switch-link::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: #d4af37;
            transition: width 0.3s ease;
        }
        
        .registration-switch-link:hover {
            color: #c19d2e;
        }
        
        .registration-switch-link:hover::after {
            width: 100%;
        }
        
        .error-message {
            background: linear-gradient(135deg, rgba(220, 38, 38, 0.08) 0%, rgba(239, 68, 68, 0.05) 100%);
            color: #dc2626;
            border: 2px solid rgba(220, 38, 38, 0.15);
            padding: 1rem 1.5rem;
            border-radius: 16px;
            font-size: 0.875rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 2;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.1);
        }
        
        .success-message {
            background: linear-gradient(135deg, rgba(5, 150, 105, 0.08) 0%, rgba(34, 197, 94, 0.05) 100%);
            color: #059669;
            border: 2px solid rgba(5, 150, 105, 0.15);
            padding: 1rem 1.5rem;
            border-radius: 16px;
            font-size: 0.875rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
            position: relative;
            z-index: 2;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 15px rgba(5, 150, 105, 0.1);
        }
        
        .registration-input.error {
            border-color: #dc2626;
            background: rgba(220, 38, 38, 0.05);
            box-shadow: 0 0 0 4px rgba(220, 38, 38, 0.1);
        }
        
        .registration-input.valid {
            border-color: #059669;
            background: rgba(5, 150, 105, 0.05);
            box-shadow: 0 0 0 4px rgba(5, 150, 105, 0.1);
        }
        
        .btn-loading {
            display: none;
        }
        
        .registration-btn.loading .btn-text {
            display: none;
        }
        
        .registration-btn.loading .btn-loading {
            display: flex !important;
            align-items: center;
            justify-content: center;
            gap: var(--space-sm);
        }
        
        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: var(--text-white);
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes slideUp {
            from { 
                opacity: 0;
                transform: translateY(20px) scale(0.95);
            }
            to { 
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        @keyframes fadeInStep {
            from { 
                opacity: 0;
                transform: translateX(20px);
            }
            to { 
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        /* Enhanced Responsive Design for Landscape */
        @media (max-width: 1024px) {
            .registration-card {
                width: 95%;
                height: 90vh;
                max-width: 1000px;
            }
            
            .registration-progress-sidebar {
                width: 300px;
                padding: 2rem;
            }
            
            .registration-form-container {
                padding: 2rem;
            }
        }
        
        @media (max-width: 768px) {
            .registration-card {
                width: 95%;
                height: 95vh;
                flex-direction: column;
            }
            
            .registration-progress-sidebar {
                width: 100%;
                padding: 1.5rem;
                order: 2;
                height: auto;
            }
            
            .registration-form-container {
                padding: 1.5rem;
                order: 1;
                flex: 1;
            }
            
            .progress-steps {
                flex-direction: row;
                overflow-x: auto;
                gap: 1rem;
                padding-bottom: 0.5rem;
            }
            
            .progress-step {
                min-width: 180px;
                flex-shrink: 0;
                padding: 1rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .registration-title {
                font-size: 2rem;
            }
            
            .registration-sub {
                font-size: 1rem;
            }
            
            .registration-socials {
                gap: 1rem;
            }
            
            .registration-socials a {
                width: 50px;
                height: 50px;
                font-size: 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .registration-card {
                width: 98%;
                height: 98vh;
            }
            
            .registration-form-container {
                padding: 1rem;
            }
            
            .registration-progress-sidebar {
                padding: 1rem;
            }
            
            .registration-title {
                font-size: 1.75rem;
            }
            
            .registration-brand {
                font-size: 1.25rem;
            }
            
            .registration-logo {
                width: 48px;
                height: 48px;
            }
            
            .progress-step {
                min-width: 160px;
                padding: 0.75rem;
            }
            
            .form-nav-btn {
                padding: 12px 20px;
                font-size: 0.875rem;
            }
        }
    </style>
</head>
<body>
    <!-- Registration Modal -->
    <div class="registration-overlay" id="registrationOverlay">
        <div class="registration-card">
            <button class="registration-close" id="registrationClose" aria-label="Close">×</button>
            
            <!-- Progress Sidebar -->
            <div class="registration-progress-sidebar">
                <div class="progress-brand">
                    <img src="assets/logo.svg" alt="NEUST Logo" class="registration-logo" loading="lazy">
                    <strong>NEUST</strong>
                </div>
                
                <div>
                    <h2 class="progress-title">Create Account</h2>
                    <div class="progress-subtitle">Join our community in just 4 simple steps. Let's get you started on your journey!</div>
                    
                    <div class="progress-steps">
                        <div class="progress-step active" data-step="1">
                            <div class="progress-step-number">1</div>
                            <div class="progress-step-content">
                                <h4>Personal Information</h4>
                                <p>Tell us about yourself</p>
                            </div>
                        </div>
                        
                        <div class="progress-step" data-step="2">
                            <div class="progress-step-number">2</div>
                            <div class="progress-step-content">
                                <h4>Academic Details</h4>
                                <p>Your educational background</p>
                            </div>
                        </div>
                        
                        <div class="progress-step" data-step="3">
                            <div class="progress-step-number">3</div>
                            <div class="progress-step-content">
                                <h4>Contact Information</h4>
                                <p>How we can reach you</p>
                            </div>
                        </div>
                        
                        <div class="progress-step" data-step="4">
                            <div class="progress-step-number">4</div>
                            <div class="progress-step-content">
                                <h4>Account Security</h4>
                                <p>Secure your account</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 2rem;">
                    <div style="font-size: 0.875rem; color: rgba(255, 255, 255, 0.7); margin-bottom: 0.5rem;">
                        <i class="fas fa-shield-alt" style="margin-right: 0.5rem;"></i>
                        Secure & Encrypted
                    </div>
                    <div style="font-size: 0.75rem; color: rgba(255, 255, 255, 0.6);">
                        Your data is protected with enterprise-grade security
                    </div>
                </div>
            </div>
            
            <!-- Form Container -->
            <div class="registration-form-container">
                <div class="registration-brand">
                    <img src="assets/logo.svg" alt="NEUST Logo" class="registration-logo" loading="lazy">
                    <strong>NEUST</strong>
                </div>
                
                <h3 class="registration-title">Welcome to NEUST</h3>
                <div class="registration-sub">Create your account and unlock access to all student services and resources</div>
                
                <?php if (!empty($registrationError)): ?>
                    <div class="error-message">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= htmlspecialchars($registrationError) ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($registrationSuccess)): ?>
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        <?= htmlspecialchars($registrationSuccess) ?>
                    </div>
                <?php endif; ?>
                
                <form class="registration-form" method="POST" action="process_register.php" id="registrationForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
                    <input type="hidden" name="source" value="registration_modal">
                    
                    <!-- Step 1: Personal Information -->
                    <div class="form-step active" data-step="1">
                        <div class="form-step-header">
                            <h4 class="form-step-title">Personal Information</h4>
                            <p class="form-step-description">Let's start with your basic information to create your profile</p>
                        </div>
                        
                        <div class="form-group">
                            <input class="registration-input" type="text" name="user_id" placeholder="Student ID" required aria-label="Student ID" autocomplete="username">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <input class="registration-input" type="text" name="first_name" placeholder="First Name" required aria-label="First Name" autocomplete="given-name">
                            </div>
                            <div class="form-group">
                                <input class="registration-input" type="text" name="last_name" placeholder="Last Name" required aria-label="Last Name" autocomplete="family-name">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <input class="registration-input" type="text" name="middle_name" placeholder="Middle Name (Optional)" aria-label="Middle Name" autocomplete="additional-name">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <input class="registration-input" type="date" name="birth_date" placeholder="Birth Date" required aria-label="Birth Date">
                            </div>
                            <div class="form-group">
                                <select class="registration-input" name="biological_sex" required aria-label="Biological Sex">
                                    <option value="">Select Sex</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <input class="registration-input" type="text" name="nationality" placeholder="Nationality" required aria-label="Nationality" value="Filipino">
                            </div>
                            <div class="form-group">
                                <input class="registration-input" type="text" name="religion" placeholder="Religion" required aria-label="Religion" value="Catholic">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 2: Academic Information -->
                    <div class="form-step" data-step="2">
                        <div class="form-step-header">
                            <h4 class="form-step-title">Academic Details</h4>
                            <p class="form-step-description">Help us understand your educational background and current status</p>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <select class="registration-input" name="year_level" required aria-label="Year Level">
                                    <option value="">Select Year Level</option>
                                    <option value="1st Year">1st Year</option>
                                    <option value="2nd Year">2nd Year</option>
                                    <option value="3rd Year">3rd Year</option>
                                    <option value="4th Year">4th Year</option>
                                    <option value="5th Year">5th Year</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <input class="registration-input" type="text" name="section" placeholder="Section" required aria-label="Section">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <input class="registration-input" type="text" name="course" placeholder="Course/Program" required aria-label="Course">
                        </div>
                        
                        <div class="form-group">
                            <input class="registration-input" type="text" name="department" placeholder="Department (Optional)" aria-label="Department">
                        </div>
                    </div>
                    
                    <!-- Step 3: Contact Information -->
                    <div class="form-step" data-step="3">
                        <div class="form-step-header">
                            <h4 class="form-step-title">Contact Information</h4>
                            <p class="form-step-description">We'll use this information to keep you updated and send important notifications</p>
                        </div>
                        
                        <div class="form-group">
                            <input class="registration-input" type="email" name="email" placeholder="Email Address" required aria-label="Email" autocomplete="email">
                        </div>
                        
                        <div class="form-group">
                            <input class="registration-input" type="tel" name="phone" placeholder="Phone Number" required aria-label="Phone Number" autocomplete="tel">
                        </div>
                        
                        <div class="form-group">
                            <textarea class="registration-input" name="current_address" placeholder="Current Address" required aria-label="Current Address" rows="3"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <textarea class="registration-input" name="permanent_address" placeholder="Permanent Address" required aria-label="Permanent Address" rows="3"></textarea>
                        </div>
                    </div>
                    
                    <!-- Step 4: Account Security -->
                    <div class="form-step" data-step="4">
                        <div class="form-step-header">
                            <h4 class="form-step-title">Account Security</h4>
                            <p class="form-step-description">Create a strong password to protect your account and review our terms</p>
                        </div>
                        
                        <div class="form-group">
                            <div class="input-wrap">
                                <input class="registration-input" type="password" id="regPassword" name="password" placeholder="Password" required aria-label="Password" autocomplete="new-password">
                                <button type="button" class="password-toggle" data-target="regPassword" aria-label="Show password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <div class="password-requirements">
                                <span id="req-length">Minimum 8 characters</span>
                                <span id="req-uppercase">At least one uppercase letter</span>
                                <span id="req-lowercase">At least one lowercase letter</span>
                                <span id="req-number">At least one number</span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="input-wrap">
                                <input class="registration-input" type="password" id="confirmPassword" placeholder="Confirm Password" required aria-label="Confirm Password" autocomplete="new-password">
                                <button type="button" class="password-toggle" data-target="confirmPassword" aria-label="Show password">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="form-options">
                            <label class="terms-agreement">
                                <input type="checkbox" name="terms" required>
                                <span>I agree to the <a href="#" class="terms-link">Terms of Service</a> and <a href="#" class="terms-link">Privacy Policy</a></span>
                            </label>
                        </div>
                    </div>
                    
                    <!-- Navigation -->
                    <div class="form-navigation">
                        <button type="button" class="form-nav-btn prev" id="prevBtn" style="display: none;">
                            <i class="fas fa-arrow-left"></i> Previous
                        </button>
                        <div></div>
                        <button type="button" class="form-nav-btn next" id="nextBtn">
                            Next <i class="fas fa-arrow-right"></i>
                        </button>
                        <button type="submit" class="form-nav-btn next" id="submitBtn" style="display: none;">
                            <span class="btn-text">Complete Registration</span>
                            <span class="btn-loading">
                                <span class="spinner"></span>Creating your account...
                            </span>
                        </button>
                    </div>
                    
                    <div class="registration-socials" aria-label="Social register">
                        <a href="#" aria-label="Sign up with Google" title="Continue with Google">
                            <i class="fa-brands fa-google"></i>
                        </a>
                        <a href="#" aria-label="Sign up with Facebook" title="Continue with Facebook">
                            <i class="fa-brands fa-facebook-f"></i>
                        </a>
                        <a href="#" aria-label="Sign up with Apple" title="Continue with Apple">
                            <i class="fa-brands fa-apple"></i>
                        </a>
                    </div>
                    
                    <div class="registration-switch-text">
                        Already have an account? <a href="#" id="goToLogin" class="registration-switch-link">Sign in here</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Registration Modal JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            const overlay = document.getElementById('registrationOverlay');
            const closeBtn = document.getElementById('registrationClose');
            const form = document.getElementById('registrationForm');
            const passwordInputs = document.querySelectorAll('input[type="password"]');
            const passwordToggles = document.querySelectorAll('.password-toggle');
            const confirmPasswordInput = document.getElementById('confirmPassword');
            const passwordInput = document.getElementById('regPassword');
            
            // Multi-step form variables
            let currentStep = 1;
            const totalSteps = 4;
            const formSteps = document.querySelectorAll('.form-step');
            const progressSteps = document.querySelectorAll('.progress-step');
            const nextBtn = document.getElementById('nextBtn');
            const prevBtn = document.getElementById('prevBtn');
            const submitBtn = document.getElementById('submitBtn');
            
            // Multi-step form functions
            function updateStepDisplay() {
                // Update form steps
                formSteps.forEach((step, index) => {
                    step.classList.toggle('active', index + 1 === currentStep);
                });
                
                // Update progress steps
                progressSteps.forEach((step, index) => {
                    step.classList.remove('active', 'completed');
                    if (index + 1 < currentStep) {
                        step.classList.add('completed');
                    } else if (index + 1 === currentStep) {
                        step.classList.add('active');
                    }
                });
                
                // Update navigation buttons
                prevBtn.style.display = currentStep === 1 ? 'none' : 'inline-flex';
                nextBtn.style.display = currentStep === totalSteps ? 'none' : 'inline-flex';
                submitBtn.style.display = currentStep === totalSteps ? 'inline-flex' : 'none';
            }
            
            function validateCurrentStep() {
                const currentStepElement = document.querySelector(`.form-step[data-step="${currentStep}"]`);
                const requiredInputs = currentStepElement.querySelectorAll('input[required], select[required], textarea[required]');
                let isValid = true;
                
                requiredInputs.forEach(input => {
                    if (!input.value.trim()) {
                        input.classList.add('error');
                        input.classList.remove('valid');
                        isValid = false;
                    } else {
                        input.classList.remove('error');
                        input.classList.add('valid');
                    }
                });
                
                return isValid;
            }
            
            function nextStep() {
                if (validateCurrentStep()) {
                    if (currentStep < totalSteps) {
                        currentStep++;
                        updateStepDisplay();
                    }
                } else {
                    alert('Please fill in all required fields before proceeding.');
                }
            }
            
            function prevStep() {
                if (currentStep > 1) {
                    currentStep--;
                    updateStepDisplay();
                }
            }
            
            // Close modal functions
            function closeModal() {
                overlay.style.animation = 'fadeOut 0.3s ease';
                setTimeout(() => {
                    window.close();
                }, 300);
            }
            
            // Event listeners
            closeBtn.addEventListener('click', closeModal);
            
            // Navigation button event listeners
            nextBtn.addEventListener('click', nextStep);
            prevBtn.addEventListener('click', prevStep);
            
            // Progress step click navigation
            progressSteps.forEach((step, index) => {
                step.addEventListener('click', function() {
                    const stepNumber = parseInt(this.getAttribute('data-step'));
                    if (stepNumber <= currentStep || this.classList.contains('completed')) {
                        currentStep = stepNumber;
                        updateStepDisplay();
                    }
                });
            });
            
            // Close on overlay click
            overlay.addEventListener('click', function(e) {
                if (e.target === overlay) {
                    closeModal();
                }
            });
            
            // Close on Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeModal();
                }
            });
            
            // Initialize step display
            updateStepDisplay();
            
            // Password toggle functionality
            passwordToggles.forEach(toggle => {
                toggle.addEventListener('click', function() {
                    const targetId = this.getAttribute('data-target');
                    const targetInput = document.getElementById(targetId);
                    const icon = this.querySelector('i');
                    
                    if (targetInput.type === 'password') {
                        targetInput.type = 'text';
                        icon.classList.remove('fa-eye');
                        icon.classList.add('fa-eye-slash');
                    } else {
                        targetInput.type = 'password';
                        icon.classList.remove('fa-eye-slash');
                        icon.classList.add('fa-eye');
                    }
                });
            });
            
            // Password validation
            function validatePassword(password) {
                const requirements = {
                    length: password.length >= 8,
                    uppercase: /[A-Z]/.test(password),
                    lowercase: /[a-z]/.test(password),
                    number: /\d/.test(password)
                };
                
                // Update requirement indicators
                document.getElementById('req-length').classList.toggle('valid', requirements.length);
                document.getElementById('req-uppercase').classList.toggle('valid', requirements.uppercase);
                document.getElementById('req-lowercase').classList.toggle('valid', requirements.lowercase);
                document.getElementById('req-number').classList.toggle('valid', requirements.number);
                
                return Object.values(requirements).every(req => req);
            }
            
            // Password confirmation validation
            function validatePasswordConfirmation() {
                if (confirmPasswordInput.value && passwordInput.value !== confirmPasswordInput.value) {
                    confirmPasswordInput.classList.add('error');
                    confirmPasswordInput.classList.remove('valid');
                } else {
                    confirmPasswordInput.classList.remove('error');
                    if (confirmPasswordInput.value) {
                        confirmPasswordInput.classList.add('valid');
                    }
                }
            }
            
            // Real-time validation
            passwordInput.addEventListener('input', function() {
                validatePassword(this.value);
                validatePasswordConfirmation();
            });
            
            confirmPasswordInput.addEventListener('input', validatePasswordConfirmation);
            
            // Form validation
            form.addEventListener('submit', function(e) {
                // Validate all steps
                let allStepsValid = true;
                for (let step = 1; step <= totalSteps; step++) {
                    const stepElement = document.querySelector(`.form-step[data-step="${step}"]`);
                    const requiredInputs = stepElement.querySelectorAll('input[required], select[required], textarea[required]');
                    
                    requiredInputs.forEach(input => {
                        if (!input.value.trim()) {
                            input.classList.add('error');
                            allStepsValid = false;
                        }
                    });
                }
                
                if (!allStepsValid) {
                    e.preventDefault();
                    alert('Please complete all required fields in all steps.');
                    return;
                }
                
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                
                // Validate password strength
                if (!validatePassword(password)) {
                    e.preventDefault();
                    alert('Please ensure your password meets all requirements.');
                    return;
                }
                
                // Validate password confirmation
                if (password !== confirmPassword) {
                    e.preventDefault();
                    alert('Passwords do not match.');
                    return;
                }
                
                // Show loading state
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
            });
            
            // Input validation styling
            const inputs = form.querySelectorAll('.registration-input');
            inputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.hasAttribute('required') && !this.value.trim()) {
                        this.classList.add('error');
                        this.classList.remove('valid');
                    } else if (this.value.trim()) {
                        this.classList.remove('error');
                        this.classList.add('valid');
                    }
                });
                
                input.addEventListener('input', function() {
                    if (this.classList.contains('error') && this.value.trim()) {
                        this.classList.remove('error');
                        this.classList.add('valid');
                    }
                });
            });
            
            // Go to login link
            document.getElementById('goToLogin').addEventListener('click', function(e) {
                e.preventDefault();
                // Close this modal and open login modal on parent page
                if (window.opener) {
                    window.opener.postMessage('openLogin', '*');
                    closeModal();
                }
            });
        });
        
        // Add fadeOut animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fadeOut {
                from { opacity: 1; }
                to { opacity: 0; }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>
