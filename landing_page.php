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
$openPane = $_GET['open'] ?? '';
$landingError = $_SESSION['landing_error'] ?? '';
$landingSuccess = $_SESSION['landing_success'] ?? '';
if (isset($_SESSION['landing_error'])) unset($_SESSION['landing_error']);
if (isset($_SESSION['landing_success'])) unset($_SESSION['landing_success']);

// Structured form errors and persisted values for inline, step-scoped display
$formErrors = $_SESSION['form_errors'] ?? null;
if (isset($_SESSION['form_errors'])) unset($_SESSION['form_errors']);

$sql = "SELECT * FROM announcements ORDER BY date_posted DESC";
$result = $conn->query($sql);
require_once __DIR__ . '/csrf.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NEUST Gabaldon Student Services - Modern Portal</title>
  
  <!-- Fonts & Icons -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  
  <!-- Slick Carousel CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css"/>
  
  <!-- Custom CSS -->
  <link rel="stylesheet" href="assets/landing/landing.css">
  <link rel="stylesheet" href="assets/landing/auth.css">
  
  <!-- Meta Tags -->
  <meta name="description" content="NEUST Gabaldon Student Services - Modern, secure, and efficient platform for scholarships, dormitory, guidance, and more.">
  <meta name="keywords" content="NEUST, student services, scholarships, dormitory, guidance, Gabaldon">
  <meta name="author" content="NEUST Gabaldon">
  
  <!-- Open Graph -->
  <meta property="og:title" content="NEUST Gabaldon Student Services">
  <meta property="og:description" content="Modern, secure, and efficient platform for student services">
  <meta property="og:type" content="website">
  <meta property="og:url" content="<?= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>">
  
  <style>
    /* Enhanced landing page specific styles */
    .landing-page {
      --gradient-primary: linear-gradient(135deg, #021f3d 0%, #0a3a6b 50%, #d4af37 100%);
      --gradient-secondary: linear-gradient(135deg, #d4af37 0%, #f4e4a6 100%);
      --shadow-glow: 0 0 30px rgba(2, 31, 61, 0.3);
      --shadow-soft: 0 10px 40px rgba(0, 0, 0, 0.1);
    }
    
    /* Enhanced slideshow container */
    .landing-page .slideshow-container { 
      max-width: 90%; 
      margin: var(--space-2xl) auto; 
      position: relative; 
      border-radius: var(--radius-2xl);
      overflow: hidden;
      box-shadow: var(--shadow-glow);
      background: var(--bg-primary);
      border: 1px solid var(--border-light);
    }
    
    .landing-page .slide { 
      position: relative; 
      overflow: hidden; 
      border-radius: var(--radius-2xl); 
      transition: all var(--transition-normal); 
      cursor: pointer;
    }
    
    .landing-page .slide:hover { 
      transform: scale(1.03); 
      box-shadow: var(--shadow-soft);
    }
    
    .landing-page .slide img { 
      width: 100%; 
      height: 450px; 
      object-fit: cover; 
      border-radius: var(--radius-2xl); 
      transition: transform var(--transition-slow);
    }
    
    .landing-page .slide:hover img {
      transform: scale(1.05);
    }
    
    .landing-page .caption { 
      position: absolute; 
      bottom: 0; 
      left: 0; 
      right: 0; 
      background: linear-gradient(transparent, rgba(0, 0, 0, 0.9)); 
      color: var(--text-white); 
      padding: var(--space-3xl) var(--space-xl) var(--space-xl); 
      font-size: var(--font-size-xl); 
      font-weight: 700; 
      text-align: center; 
      backdrop-filter: blur(10px);
    }
    
    .landing-page .caption h3 {
      font-size: var(--font-size-2xl);
      margin-bottom: var(--space-sm);
      text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
    }
    
    .landing-page .caption p {
      font-size: var(--font-size-base);
      opacity: 0.9;
      line-height: 1.4;
    }
    
    /* Enhanced navigation arrows */
    .landing-page .slick-prev, 
    .landing-page .slick-next { 
      position: absolute; 
      top: 50%; 
      transform: translateY(-50%); 
      background: rgba(255, 255, 255, 0.95); 
      color: var(--primary); 
      border: none; 
      font-size: var(--font-size-xl); 
      padding: var(--space-lg); 
      cursor: pointer; 
      z-index: 10; 
      border-radius: var(--radius-full); 
      width: 60px; 
      height: 60px; 
      display: flex; 
      align-items: center; 
      justify-content: center; 
      transition: all var(--transition-normal); 
      box-shadow: var(--shadow-lg);
      backdrop-filter: blur(10px);
    }
    
    .landing-page .slick-prev { left: -30px; }
    .landing-page .slick-next { right: -30px; }
    
    .landing-page .slick-prev:hover, 
    .landing-page .slick-next:hover { 
      background: var(--text-white); 
      color: var(--primary); 
      transform: translateY(-50%) scale(1.15); 
      box-shadow: var(--shadow-xl);
    }
    
    /* Enhanced dots */
    .landing-page .slick-dots { 
      bottom: var(--space-xl);
      display: flex !important;
      justify-content: center;
      gap: var(--space-sm);
    }
    
    .landing-page .slick-dots li {
      width: auto;
      height: auto;
      margin: 0;
    }
    
    .landing-page .slick-dots li button {
      width: 12px;
      height: 12px;
      border-radius: var(--radius-full);
      background: rgba(255, 255, 255, 0.5);
      border: none;
      transition: all var(--transition-fast);
    }
    
    .landing-page .slick-dots li button:before {
      display: none;
    }
    
    .landing-page .slick-dots li.slick-active button {
      background: var(--secondary);
      transform: scale(1.2);
      box-shadow: 0 0 10px rgba(212, 175, 55, 0.5);
    }
    
    /* Enhanced no announcements card */
    .landing-page .no-announcements {
      background: var(--gradient-primary);
      color: var(--text-white);
      text-align: center;
      padding: var(--space-3xl);
      border-radius: var(--radius-2xl);
      box-shadow: var(--shadow-glow);
    }
    
    .landing-page .no-announcements-logo {
      width: 100px;
      height: 100px;
      margin: 0 auto var(--space-lg);
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow-lg);
    }
    
    .landing-page .no-announcements h3 {
      font-size: var(--font-size-2xl);
      margin-bottom: var(--space-md);
      color: var(--text-white);
    }
    
    .landing-page .no-announcements p {
      font-size: var(--font-size-lg);
      opacity: 0.9;
      color: var(--text-white);
    }
    
    /* Enhanced announcements CTA */
    .announcements-cta {
      text-align: center;
      margin-top: var(--space-2xl);
    }
    
    .announcements-cta .btn {
      background: var(--gradient-secondary);
      color: var(--text-primary);
      font-weight: 700;
      padding: var(--space-lg) var(--space-2xl);
      border-radius: var(--radius-xl);
      box-shadow: var(--shadow-lg);
      transition: all var(--transition-normal);
    }
    
    .announcements-cta .btn:hover {
      transform: translateY(-3px);
      box-shadow: var(--shadow-xl);
    }
    
    /* Enhanced mobile responsiveness */
    @media (max-width: 768px) {
      .landing-page .slideshow-container {
        max-width: 95%;
        margin: var(--space-xl) auto;
      }
      
      .landing-page .slide img {
        height: 300px;
      }
      
      .landing-page .caption {
        padding: var(--space-2xl) var(--space-lg) var(--space-lg);
      }
      
      .landing-page .caption h3 {
        font-size: var(--font-size-xl);
      }
      
      .landing-page .slick-prev,
      .landing-page .slick-next {
        width: 50px;
        height: 50px;
        font-size: var(--font-size-lg);
      }
      
      .landing-page .slick-prev {
        left: var(--space-sm);
      }
      
      .landing-page .slick-next {
        right: var(--space-sm);
      }
    }
    
    @media (max-width: 480px) {
      .landing-page .slide img {
        height: 250px;
      }
      
      .landing-page .caption {
        padding: var(--space-xl) var(--space-md) var(--space-md);
      }
      
      .landing-page .caption h3 {
        font-size: var(--font-size-lg);
      }
      
      .landing-page .caption p {
        font-size: var(--font-size-sm);
      }
    }
    
    /* Loading animation for slideshow */
    .landing-page .slideshow-container.loading {
      opacity: 0.7;
    }
    
    .landing-page .slideshow-container.loading::after {
      content: '';
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 40px;
      height: 40px;
      border: 3px solid rgba(255, 255, 255, 0.3);
      border-top-color: var(--primary);
      border-radius: 50%;
      animation: spin 1s linear infinite;
      z-index: 5;
    }
  </style>
</head>
<body class="landing-page">
    <!-- Navigation -->
    <nav class="nav">
        <div class="container nav-inner">
            <a class="brand" href="#" aria-label="NEUST Home">
                <img src="assets/logo.svg" alt="NEUST Gabaldon Logo" loading="lazy">
                <span>NEUST Student Services</span>
            </a>
            <div class="nav-links" role="navigation" aria-label="Primary">
                <a href="#features">Features</a>
                <a href="#announcements">Announcements</a>
                <a href="#about">About</a>
                <button id="modeToggle" class="mode-toggle" aria-label="Toggle dark mode">
                    <i class="fa-regular fa-moon"></i>
                </button>
                <a href="#" id="openLogin" class="btn btn-ghost" aria-haspopup="dialog">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
                <a href="#" id="openRegister" class="btn btn-primary" aria-haspopup="dialog">
                    <i class="fas fa-user-plus"></i> Register
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container hero-grid">
            <div class="hero-content">
                <div class="hero-badge reveal">
                    <i class="fas fa-star"></i>
                    <span>Trusted by 500+ Students</span>
                </div>
                <h1 class="headline reveal">
                    Student Services, 
                    <span class="text-gradient">Simplified</span>
                </h1>
                <p class="subhead reveal" style="transition-delay:.08s">
                    Access scholarships, dormitory rooms, guidance, and more — 
                    <strong>fast, modern, and secure.</strong>
                </p>
                <div class="cta-row reveal" style="transition-delay:.16s">
                    <a href="#" id="ctaRegister" class="btn btn-primary btn-large">
                        <i class="fas fa-user-plus"></i> 
                        Create Account
                        <span class="btn-shine"></span>
                    </a>
                    <button id="ctaLogin" class="btn btn-ghost btn-large">
                        <i class="fas fa-sign-in-alt"></i> 
                        Login
                    </button>
                </div>
                <div class="hero-features reveal" style="transition-delay:.24s">
                    <div class="feature-item">
                        <i class="fas fa-shield-check"></i>
                        <span>Bank-Level Security</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-mobile-alt"></i>
                        <span>Mobile Optimized</span>
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-clock"></i>
                        <span>24/7 Access</span>
                    </div>
                </div>
                <div class="hero-stats reveal" style="transition-delay:.32s">
                    <div class="stat">
                        <div class="stat-number" data-count="500">0</div>
                        <div class="stat-label">Active Students</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number" data-count="50">0</div>
                        <div class="stat-label">Scholarships</div>
                    </div>
                    <div class="stat">
                        <div class="stat-number" data-count="100">0</div>
                        <div class="stat-label">Dormitory Rooms</div>
                    </div>
                </div>
            </div>
            <div class="hero-visual reveal" style="transition-delay:.24s" aria-hidden="true">
                <div class="hero-card">
                    <div class="card-header">
                        <div class="card-dots">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                        <div class="card-title">NEUST Student Portal</div>
                    </div>
                    <div class="card-content">
                        <div class="service-item">
                            <i class="fas fa-graduation-cap"></i>
                            <span>Scholarships</span>
                        </div>
                        <div class="service-item">
                            <i class="fas fa-bed"></i>
                            <span>Dormitory</span>
                        </div>
                        <div class="service-item">
                            <i class="fas fa-comments"></i>
                            <span>Guidance</span>
                        </div>
                        <div class="service-item">
                            <i class="fas fa-bullhorn"></i>
                            <span>Announcements</span>
                        </div>
                    </div>
                </div>
                <div class="blob b1"></div>
                <div class="blob b2"></div>
                <div class="blob b3"></div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="section" id="features">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Why Choose NEUST Student Services?</h2>
                <p class="section-sub">Modern, reliable, and designed specifically for NEUST Gabaldon students.</p>
            </div>
            <div class="grid grid-4">
                <div class="card reveal">
                    <div class="card-icon ic-blue">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <h4>Lightning Fast</h4>
                    <p>Optimized workflows to apply, track, and manage your requests in minutes, not hours.</p>
                </div>
                <div class="card reveal">
                    <div class="card-icon ic-cyan">
                        <i class="fas fa-shield-halved"></i>
                    </div>
                    <h4>Bank-Level Security</h4>
                    <p>Role-based access, CSRF protection, and comprehensive audit logs keep your data safe.</p>
                </div>
                <div class="card reveal">
                    <div class="card-icon ic-gold">
                        <i class="fas fa-palette"></i>
                    </div>
                    <h4>Modern Design</h4>
                    <p>Beautiful, intuitive interface with smooth animations and accessible interactions.</p>
                </div>
                <div class="card reveal">
                    <div class="card-icon ic-green">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h4>Mobile First</h4>
                    <p>Works perfectly on any device - from smartphone to desktop without compromise.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Announcements Section -->
    <section class="section" id="announcements">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Latest Announcements</h2>
                <p class="section-sub">Stay updated with the latest news and important updates from NEUST Gabaldon.</p>
            </div>
            <div class="slideshow-container reveal" aria-label="Announcements slideshow">
                <?php mysqli_data_seek($result, 0); if ($result && $result->num_rows > 0): while ($row = $result->fetch_assoc()): ?>
                    <div class="slide">
                        <a href="announcement_details.php?id=<?= htmlspecialchars($row['id']) ?>" class="slide-link">
                            <img src="uploads/announcements/<?= htmlspecialchars($row['image']) ?>" 
                                 alt="<?= htmlspecialchars($row['title']) ?>" 
                                 loading="lazy" 
                                 onerror="this.onerror=null;this.src='assets/logo.png';">
                            <div class="caption">
                                <h3><?= htmlspecialchars($row['title']) ?></h3>
                                <p><?= htmlspecialchars(substr($row['content'], 0, 100)) ?>...</p>
                            </div>
                        </a>
                    </div>
                <?php endwhile; else: ?>
                    <div class="slide">
                        <div class="card no-announcements">
                            <img src="assets/logo.svg" alt="NEUST" loading="lazy" class="no-announcements-logo">
                            <h3>No announcements yet</h3>
                            <p>Please check back later for the latest updates.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            <div class="announcements-cta">
                <a href="student_announcement.php" class="btn btn-primary">
                    <i class="fas fa-bullhorn"></i> 
                    View All Announcements
                </a>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="section" id="how">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">How It Works</h2>
                <p class="section-sub">Get started in just three simple steps and access all student services.</p>
            </div>
            <div class="grid grid-3">
                <div class="card reveal">
                    <div class="card-icon ic-gold">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h4>Create Your Account</h4>
                    <p>Register in seconds with your basic student information and get instant access to all services.</p>
                </div>
                <div class="card reveal">
                    <div class="card-icon ic-blue">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h4>Apply or Request</h4>
                    <p>Submit applications for guidance, dormitory rooms, scholarships, and other student services.</p>
                </div>
                <div class="card reveal">
                    <div class="card-icon ic-cyan">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h4>Track & Manage</h4>
                    <p>Monitor your application status, receive real-time updates, and manage all your requests.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="section" id="testimonials">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">What Students Say</h2>
                <p class="section-sub">Hear from our students about their experience with NEUST Student Services.</p>
            </div>
            <div class="grid grid-3">
                <div class="card reveal">
                    <p class="quote">"Super dali gamitin, ang bilis mag-apply at mag-track ng applications. Very user-friendly!"</p>
                    <div class="testimonial-author">
                        <div class="avatar">JS</div>
                        <div class="author-info">
                            <strong>J. Santos</strong>
                            <div class="muted">BSIT Student</div>
                        </div>
                    </div>
                </div>
                <div class="card reveal">
                    <p class="quote">"Modern UI, smooth and walang hassle sa submissions. Everything is organized and easy to find."</p>
                    <div class="testimonial-author">
                        <div class="avatar">AC</div>
                        <div class="author-info">
                            <strong>A. Cruz</strong>
                            <div class="muted">Education Student</div>
                        </div>
                    </div>
                </div>
                <div class="card reveal">
                    <p class="quote">"Finally, one place for dorm, guidance, and scholarships. Saves me so much time!"</p>
                    <div class="testimonial-author">
                        <div class="avatar">KD</div>
                        <div class="author-info">
                            <strong>K. Dela Cruz</strong>
                            <div class="muted">Engineering Student</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="section">
        <div class="container">
            <div class="cta-strip reveal">
                <div class="cta-content">
                    <h3>Ready to Get Started?</h3>
                    <p>Join thousands of NEUST students who are already using our platform to manage their academic journey.</p>
                </div>
                <div class="cta-actions">
                    <a href="#" id="openRegisterBottom" class="btn btn-primary">
                        <i class="fas fa-user-plus"></i> 
                        Register Now
                    </a>
                    <a href="#" id="openLoginBottom" class="btn btn-ghost">
                        <i class="fas fa-sign-in-alt"></i> 
                        Login
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section class="section" id="about">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">About NEUST Student Services</h2>
                <p class="section-sub">Empowering students with modern, efficient, and secure digital solutions.</p>
            </div>
            <div class="about-content">
                <div class="about-text">
                    <p>
                        NEUST Gabaldon Student Services Management System is designed to optimize and enhance the management of various student services. 
                        Our goal is to provide an efficient, user-friendly platform for students and faculty to access essential services such as announcements, 
                        scholarships, grievances, and dormitory management.
                    </p>
                    <p>
                        Built with modern web technologies and security best practices, our platform ensures that every interaction is fast, secure, and reliable. 
                        We're committed to making student life easier and more organized.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="section" id="services">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">Our Services</h2>
                <p class="section-sub">Comprehensive student services all in one convenient platform.</p>
            </div>
            <div class="grid grid-4">
                <div class="service-card reveal">
                    <div class="service-icon">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <h3>Announcements</h3>
                    <p>Stay updated with the latest news and announcements from NEUST Gabaldon. Never miss important updates.</p>
                </div>
                <div class="service-card reveal">
                    <div class="service-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <h3>Scholarships</h3>
                    <p>Apply for various scholarships with our streamlined application process. Secure your financial support easily.</p>
                </div>
                <div class="service-card reveal">
                    <div class="service-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h3>Guidance Services</h3>
                    <p>Access counseling and guidance services. Schedule appointments and get the support you need.</p>
                </div>
                <div class="service-card reveal">
                    <div class="service-icon">
                        <i class="fas fa-bed"></i>
                    </div>
                    <h3>Dormitory Management</h3>
                    <p>Apply for dormitory rooms, manage your housing applications, and stay updated with dormitory services.</p>
                </div>
            </div>
        </div>
    </section>
    <!-- Footer -->
    <footer class="footer">
        <div class="container footer-grid">
            <div class="footer-brand">
                <div class="footer-logo">
                    <img src="assets/logo.svg" alt="NEUST Gabaldon" loading="lazy">
                    <strong>NEUST Student Services</strong>
                </div>
                <p class="footer-description">All-in-one portal for NEUST Gabaldon students. Modern, secure, and efficient.</p>
                <div class="socials" aria-label="Social links">
                    <a href="#" aria-label="Facebook" class="social-link">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" aria-label="Twitter" class="social-link">
                        <i class="fab fa-x-twitter"></i>
                    </a>
                    <a href="#" aria-label="Instagram" class="social-link">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <a href="#" aria-label="LinkedIn" class="social-link">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                </div>
            </div>
            <div class="footer-links">
                <h4>Quick Links</h4>
                <a href="student_announcement.php">Announcements</a>
                <a href="#features">Features</a>
                <a href="#about">About Us</a>
                <a href="#services">Services</a>
            </div>
            <div class="footer-links">
                <h4>Student Services</h4>
                <a href="scholarships.php">Scholarships</a>
                <a href="dormitory_manage_applications.php">Dormitory</a>
                <a href="guidance_request.php">Guidance</a>
                <a href="grievance_list.php">Grievances</a>
            </div>
            <div class="footer-links">
                <h4>Support</h4>
                <a href="#" id="openLoginFooter">Login</a>
                <a href="#" id="openRegisterFooter">Register</a>
                <a href="mailto:support@neust.edu.ph">Contact Us</a>
                <a href="#help">Help Center</a>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="container">
                <div class="footer-bottom-content">
                    <p>&copy; 2025 NEUST Gabaldon. All Rights Reserved.</p>
                    <div class="footer-bottom-links">
                        <a href="#privacy">Privacy Policy</a>
                        <a href="#terms">Terms of Service</a>
                        <a href="#cookies">Cookie Policy</a>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Overlay Modals -->
    <style>
        .overlay-backdrop { position: fixed; inset: 0; background: rgba(0,0,0,0.55); display: none; align-items: center; justify-content: center; z-index: 1050; opacity: 0; transition: opacity .25s ease; }
        .overlay-backdrop.open { opacity: 1; }
        .overlay-modal { width: 90%; max-width: 980px; height: 85vh; background: #fff; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,.35); overflow: hidden; position: relative; transform: translateY(16px) scale(.98); opacity: 0; transition: transform .3s ease, opacity .3s ease; }
        .overlay-backdrop.open .overlay-modal { transform: translateY(0) scale(1); opacity: 1; }
        .overlay-header { position: absolute; top: 0; left: 0; right: 0; height: 50px; background: rgb(2, 31, 61); color: #fff; display: flex; align-items: center; justify-content: space-between; padding: 0 14px; }
        .overlay-title { font-weight: 700; }
        .overlay-close { background: transparent; border: none; color: #fff; font-size: 22px; cursor: pointer; }
        .overlay-body { position: absolute; top: 50px; left: 0; right: 0; bottom: 0; }
        .overlay-body iframe { width: 100%; height: 100%; border: 0; }
        @media (max-width: 768px){ .overlay-modal{ width: 96%; height: 90vh; } }

        /* Background blur when modal open */
        body.modal-blur .navbar,
        body.modal-blur .hero,
        body.modal-blur .slideshow-container,
        body.modal-blur .about,
        body.modal-blur .services,
        body.modal-blur .footer { filter: blur(6px) brightness(.9); transition: filter .25s ease; pointer-events: none; }
        
        /* Auth pane visibility */
        .auth-pane {
            display: none;
        }
        .auth-pane.visible {
            display: block;
        }
        
        /* Multi-step Registration Styles */
        .registration-progress {
            margin-bottom: 2rem;
        }
        
        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            position: relative;
        }
        
        .progress-steps::before {
            content: '';
            position: absolute;
            top: 20px;
            left: 20px;
            right: 20px;
            height: 2px;
            background: #e5e7eb;
            z-index: 1;
        }
        
        .progress-steps .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            z-index: 2;
            flex: 1;
        }
        
        .step-number {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e5e7eb;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            border: 3px solid #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .step.active .step-number {
            background: linear-gradient(135deg, #021f3d 0%, #0a3a6b 50%, #d4af37 100%);
            color: white;
            transform: scale(1.1);
        }
        
        .step.completed .step-number {
            background: #10b981;
            color: white;
        }
        
        .step.completed .step-number::after {
            content: '✓';
            font-size: 16px;
        }
        
        .step-label {
            margin-top: 8px;
            font-size: 12px;
            font-weight: 500;
            color: #6b7280;
            text-align: center;
        }
        
        .step.active .step-label {
            color: #021f3d;
            font-weight: 600;
        }
        
        .progress-bar {
            height: 4px;
            background: #e5e7eb;
            border-radius: 2px;
            overflow: hidden;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(135deg, #021f3d 0%, #0a3a6b 50%, #d4af37 100%);
            border-radius: 2px;
            transition: width 0.5s ease;
        }
        
        .form-step {
            display: none;
            animation: fadeIn 0.3s ease-in-out;
        }
        
        .form-step.active {
            display: block;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .step-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        
        .step-header h4 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #021f3d;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .step-header h4 i {
            color: #d4af37;
        }
        
        .step-header p {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .form-label .required {
            color: #dc2626;
        }
        
        .auth-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            background: #fff;
            color: #374151;
            transition: all 0.2s ease;
        }
        
        .auth-input:focus {
            outline: none;
            border-color: #d4af37;
            box-shadow: 0 0 0 3px rgba(212, 175, 55, 0.1);
        }
        
        .auth-input.error {
            border-color: #dc2626;
            box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
        }
        
        .auth-input.valid {
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        
        textarea.auth-input {
            resize: vertical;
            min-height: 80px;
        }
        
        select.auth-input {
            cursor: pointer;
        }
        
        .password-requirements {
            margin-top: 0.5rem;
            font-size: 0.75rem;
        }
        
        .password-requirements span {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.25rem;
            color: #6b7280;
            transition: color 0.2s ease;
        }
        
        .password-requirements span.valid {
            color: #10b981;
        }
        
        .password-requirements span.invalid {
            color: #dc2626;
        }
        
        .password-requirements span i {
            font-size: 0.5rem;
        }
        
        .form-navigation {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 1px solid #e5e7eb;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        /* Map nav-btn to unified button styles */
        .nav-btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; border-radius: 0.5rem; font-size: 0.875rem; font-weight: 600; cursor: pointer; border: none; }
        .prev-btn { background: #f3f4f6; color: #374151; border: 1px solid #d1d5db; }
        .prev-btn:hover { background: #e5e7eb; }
        .next-btn, .submit-btn { background: linear-gradient(135deg, #021f3d 0%, #0a3a6b 50%, #d4af37 100%); color: #fff; box-shadow: 0 2px 4px rgba(2,31,61,.2); }
        .next-btn:hover, .submit-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 8px rgba(2,31,61,.3); }
        
        .btn-primary {
            background: linear-gradient(135deg, #021f3d 0%, #0a3a6b 50%, #d4af37 100%);
            color: white;
            box-shadow: 0 2px 4px rgba(2, 31, 61, 0.2);
        }
        
        .btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(2, 31, 61, 0.3);
        }
        
        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none !important;
        }
        /* Login button unify */
        .login-btn { background: linear-gradient(135deg, #021f3d 0%, #0a3a6b 50%, #d4af37 100%); color: #fff; border: none; border-radius: 8px; padding: 12px 16px; font-weight: 600; width: 100%; box-shadow: 0 2px 4px rgba(2,31,61,.2); cursor: pointer; }
        .login-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 8px rgba(2,31,61,.3); }
        .login-btn:disabled { opacity: .6; cursor: not-allowed; }

        /* Sidebar progress tracker consistency */
        .registration-sidebar .progress-tracker h4 { color: #fff; font-weight: 800; margin-bottom: 12px; }
        .registration-sidebar .progress-steps { display: flex; flex-direction: column; gap: 10px; position: relative; }
        .registration-sidebar .progress-step { display: flex; align-items: center; gap: 10px; padding: 10px; border-radius: 10px; transition: background .2s ease, transform .2s ease; }
        .registration-sidebar .progress-step .step-indicator { display: flex; align-items: center; gap: 10px; }
        .registration-sidebar .progress-step .step-number { width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,.25); color: #fff; font-weight: 800; display: inline-flex; align-items: center; justify-content: center; }
        .registration-sidebar .progress-step.active .step-number { background: #d4af37; color: #021f3d; }
        .registration-sidebar .progress-step.completed .step-number { background: #10b981; color: #fff; }
        .registration-sidebar .progress-step .step-content h5 { margin: 0; color: #fff; font-weight: 700; font-size: 0.95rem; }
        .registration-sidebar .progress-step .step-content p { margin: 0; color: rgba(255,255,255,.8); font-size: 0.8rem; }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
            
            .progress-steps {
                gap: 0.5rem;
            }
            
            .step-number {
                width: 32px;
                height: 32px;
                font-size: 12px;
            }
            
            .step-label {
                font-size: 10px;
            }
            
            .form-navigation {
                flex-direction: column;
                gap: 1rem;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
    <style>
        :root { --neust-primary:#021f3d; --neust-primary-600:#0a3a6b; --neust-gold:#d4af37; --neust-gray-200:#e5e7eb; --neust-gray-500:#6b7280; --neust-gray-700:#374151; --radius-md:8px; }
        .progress-steps { gap: 12px; }
        .progress-steps::before { left: 40px; right: 40px; background: var(--neust-gray-200); }
        .step-number { background: var(--neust-gray-200); color: var(--neust-gray-500); font-weight:700; box-shadow: 0 1px 2px rgba(0,0,0,.06); }
        .step.active .step-number { background: linear-gradient(135deg, var(--neust-primary) 0%, var(--neust-primary-600) 50%, var(--neust-gold) 100%); }
        .step-label { font-weight:600; color: var(--neust-gray-500); }
        .step.active .step-label { color: var(--neust-primary); font-weight:700; }
        .form-label { font-weight:600; color: var(--neust-gray-700); }
        .input-wrapper .input-icon { display:none; }
        .input-wrapper .password-toggle { position:absolute; right:10px; top:50%; transform:translateY(-50%); background:none; border:0; color: var(--neust-gray-500); cursor:pointer; }
        .field-error { color:#dc2626; font-size:12px; margin-top:4px; }
        .form-input, .registration-input, .auth-input, .form-select, textarea.form-input { width:100%; padding:12px 14px; border:2px solid var(--neust-gray-200); border-radius: var(--radius-md); font-size:.95rem; color: var(--neust-gray-700); }
        .form-input:focus, .registration-input:focus, .auth-input:focus, .form-select:focus, textarea.form-input:focus { outline:none; border-color: var(--neust-gold); box-shadow:0 0 0 3px rgba(212,175,55,.15); }
        .form-input.error, .registration-input.error, .auth-input.error { border-color:#dc2626; box-shadow:0 0 0 3px rgba(220,38,38,.1); }
        .form-input.valid, .registration-input.valid, .auth-input.valid { border-color:#10b981; box-shadow:0 0 0 3px rgba(16,185,129,.1); }
        .checkbox-wrapper { display:inline-flex; align-items:center; gap:8px; cursor:pointer; }
        .checkbox-wrapper input[type="checkbox"] { width:18px; height:18px; accent-color: var(--neust-gold); }
        .forgot-link { color: var(--neust-primary-600); font-weight:600; }
        .forgot-link:hover { color: var(--neust-primary); text-decoration: underline; }
        .terms-link { color: var(--neust-gold); font-weight:600; text-decoration:none; }
        .terms-link:hover { color:#c19d2e; text-decoration: underline; }
        .form-navigation { gap:.75rem; margin-top:1.5rem; }
    </style>
    <!-- Auth Split Overlay -->
    <div class="auth-overlay" id="authOverlay" aria-hidden="true">
        <div class="auth-bg" aria-hidden="true">
            <div class="auth-blob b1"></div>
            <div class="auth-blob b2"></div>
        </div>
        <div class="auth-card" id="authCard" role="dialog" aria-modal="true" aria-labelledby="authTitle">
            <button class="auth-close auth-switch" id="authClose" aria-label="Close">×</button>
            <!-- Single Form Container -->
            <div class="auth-form-container">
                <!-- Login Form -->
                <div id="authPaneLogin" class="auth-pane <?= ($openPane === 'login') ? 'visible' : '' ?>">
                    <div class="login-container">
                        <div class="login-header">
                            <div class="login-brand">
                                <img src="assets/logo.svg" alt="NEUST" class="login-logo" loading="lazy">
                                <div class="brand-text">
                                    <h2>NEUST</h2>
                                    <p>Student Services</p>
                                </div>
                            </div>
                            <h3 class="login-title">Welcome Back!</h3>
                            <p class="login-subtitle">Sign in to access your student portal</p>
                        </div>

                        <?php if (!empty($landingError) && $openPane === 'login'): ?>
                          <div class="alert alert-error">
                            <i class="fas fa-exclamation-circle"></i>
                            <span><?= htmlspecialchars($landingError) ?></span>
                          </div>
                        <?php endif; ?>
                        <?php if (!empty($landingSuccess) && $openPane === 'login'): ?>
                          <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            <span><?= htmlspecialchars($landingSuccess) ?></span>
                          </div>
                        <?php endif; ?>

                        <form class="login-form" method="POST" action="process_login.php">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
                            <input type="hidden" name="source" value="landing">
                            
                            <div class="form-group">
                                <label for="loginUserId" class="form-label">User ID</label>
                                <div class="input-wrapper">
                                    <input 
                                        class="form-input" 
                                        type="text" 
                                        id="loginUserId"
                                        name="user_id" 
                                        placeholder="Enter your User ID" 
                                        required 
                                        aria-label="User ID" 
                                        autocomplete="username"
                                    >
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="loginPwd" class="form-label">Password</label>
                                <div class="input-wrapper">
                                    <input 
                                        class="form-input" 
                                        type="password" 
                                        id="loginPwd" 
                                        name="password" 
                                        placeholder="Enter your password" 
                                        required 
                                        aria-label="Password" 
                                        autocomplete="current-password"
                                    >
                                    <button type="button" class="password-toggle" data-target="loginPwd" aria-label="Show password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="form-options">
                                <label class="checkbox-wrapper" for="rememberMe">
                                    <input type="checkbox" name="remember_me" id="rememberMe">
                                    <span class="checkbox-label">Remember me</span>
                                </label>
                                <a href="reset_password.php" class="forgot-link">Forgot password?</a>
                            </div>

                            <button class="login-btn" type="submit">
                                <span class="btn-text">Sign In</span>
                                <span class="btn-loading" style="display: none;">
                                    <span class="spinner"></span>
                                    Signing in...
                                </span>
                            </button>
                        </form>

                        <div class="login-footer">
                            <p>Don't have an account? <a href="#" class="switch-link" id="switchToRegister">Create one</a></p>
                        </div>
                    </div>
                </div>
                
                <!-- Registration Form -->
                <div id="authPaneRegister" class="auth-pane <?= ($openPane === 'register') ? 'visible' : '' ?>">
                    <div class="registration-container">
                        <!-- Progress Sidebar -->
                        <div class="registration-sidebar">
                            <div class="sidebar-header">
                                <div class="sidebar-brand">
                                    <img src="assets/logo.svg" alt="NEUST" class="sidebar-logo" loading="lazy">
                                    <div class="brand-info">
                                        <h3>NEUST</h3>
                                        <p>Student Services</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="progress-tracker">
                                <h4>Registration Progress</h4>
                                <div class="progress-steps">
                                    <div class="progress-step active" data-step="1">
                                        <div class="step-indicator">
                                            <div class="step-number">1</div>
                                            <div class="step-line"></div>
                                        </div>
                                        <div class="step-content">
                                            <h5>Personal Info</h5>
                                            <p>Basic details and demographics</p>
                                        </div>
                                    </div>
                                    
                                    <div class="progress-step" data-step="2">
                                        <div class="step-indicator">
                                            <div class="step-number">2</div>
                                            <div class="step-line"></div>
                                        </div>
                                        <div class="step-content">
                                            <h5>Academic Info</h5>
                                            <p>Course and year information</p>
                                        </div>
                                    </div>
                                    
                                    <div class="progress-step" data-step="3">
                                        <div class="step-indicator">
                                            <div class="step-number">3</div>
                                            <div class="step-line"></div>
                                        </div>
                                        <div class="step-content">
                                            <h5>Contact Info</h5>
                                            <p>Address and phone details</p>
                                        </div>
                                    </div>
                                    
                                    <div class="progress-step" data-step="4">
                                        <div class="step-indicator">
                                            <div class="step-number">4</div>
                                        </div>
                                        <div class="step-content">
                                            <h5>Account Setup</h5>
                                            <p>Password and terms agreement</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Content -->
                        <div class="registration-content">
                            <div class="content-header">
                                <h3 class="registration-title">Create Your Account</h3>
                                <p class="registration-subtitle">Join thousands of NEUST students</p>
                            </div>

                            <?php if (!empty($landingError) && $openPane === 'register' && empty($formErrors)): ?>
                              <div class="alert alert-error">
                                <i class="fas fa-exclamation-circle"></i>
                                <span><?= htmlspecialchars($landingError) ?></span>
                              </div>
                            <?php endif; ?>
                            <?php if (!empty($landingSuccess) && $openPane === 'register'): ?>
                              <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                <span><?= htmlspecialchars($landingSuccess) ?></span>
                              </div>
                            <?php endif; ?>

                            <form class="registration-form" method="POST" action="process_register.php" id="registerMultiStep" <?php if (!empty($formErrors) && ($formErrors['source'] ?? '') === 'landing'): ?>data-initial-step="<?= (int)($formErrors['step'] ?? 1) ?>"<?php endif; ?>>
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES) ?>">
                                <input type="hidden" name="source" value="landing">
                                
                                <!-- Step 1: Personal Information -->
                                <div class="form-step active" data-step="1">
                                    <div class="step-header">
                                        <h4><i class="fas fa-user"></i> Personal Information</h4>
                                        <p>Tell us about yourself</p>
                                    </div>
                                    
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label class="form-label">Student ID <span class="required">*</span></label>
                                            <div class="input-wrapper">
                                                <i class="fas fa-id-card input-icon"></i>
                                                <input class="form-input" type="text" name="user_id" placeholder="Enter your Student ID" required aria-label="Student ID" autocomplete="username">
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="form-label">Biological Sex <span class="required">*</span></label>
                                            <div class="input-wrapper">
                                                <i class="fas fa-venus-mars input-icon"></i>
                                                <select class="form-input" name="biological_sex" required>
                                                    <option value="">Select Sex</option>
                                                    <option value="Male">Male</option>
                                                    <option value="Female">Female</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="form-label">First Name <span class="required">*</span></label>
                                            <div class="input-wrapper">
                                                <i class="fas fa-user input-icon"></i>
                                                <input class="form-input" type="text" name="first_name" placeholder="Enter your first name" required aria-label="First Name" autocomplete="given-name">
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="form-label">Last Name <span class="required">*</span></label>
                                            <div class="input-wrapper">
                                                <i class="fas fa-user input-icon"></i>
                                                <input class="form-input" type="text" name="last_name" placeholder="Enter your last name" required aria-label="Last Name" autocomplete="family-name">
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="form-label">Middle Name</label>
                                            <div class="input-wrapper">
                                                <i class="fas fa-user input-icon"></i>
                                                <input class="form-input" type="text" name="middle_name" placeholder="Enter your middle name" aria-label="Middle Name" autocomplete="additional-name">
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="form-label">Birth Date <span class="required">*</span></label>
                                            <div class="input-wrapper">
                                                <i class="fas fa-calendar input-icon"></i>
                                                <input class="form-input" type="date" name="birth_date" required aria-label="Birth Date">
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="form-label">Nationality <span class="required">*</span></label>
                                            <div class="input-wrapper">
                                                <i class="fas fa-flag input-icon"></i>
                                                <input class="form-input" type="text" name="nationality" placeholder="Filipino" value="Filipino" required aria-label="Nationality">
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="form-label">Religion <span class="required">*</span></label>
                                            <div class="input-wrapper">
                                                <i class="fas fa-pray input-icon"></i>
                                                <select class="form-input" name="religion" required>
                                                    <option value="">Select Religion</option>
                                                    <option value="Catholic">Catholic</option>
                                                    <option value="Protestant">Protestant</option>
                                                    <option value="Islam">Islam</option>
                                                    <option value="Buddhism">Buddhism</option>
                                                    <option value="Hinduism">Hinduism</option>
                                                    <option value="Atheist">Atheist</option>
                                                    <option value="Other">Other</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Step 2: Academic Information -->
                                <div class="form-step" data-step="2">
                                    <div class="step-header">
                                        <h4><i class="fas fa-graduation-cap"></i> Academic Information</h4>
                                        <p>Your academic details</p>
                                    </div>
                                    
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label class="form-label">Year Level <span class="required">*</span></label>
                                            <div class="input-wrapper">
                                                <i class="fas fa-calendar-alt input-icon"></i>
                                                <select class="form-input" name="year" required>
                                                    <option value="">Select Year</option>
                                                    <option value="1">1st Year</option>
                                                    <option value="2">2nd Year</option>
                                                    <option value="3">3rd Year</option>
                                                    <option value="4">4th Year</option>
                                                    <option value="5">5th Year</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="form-label">Section <span class="required">*</span></label>
                                            <div class="input-wrapper">
                                                <i class="fas fa-users input-icon"></i>
                                                <input class="form-input" type="text" name="section" placeholder="e.g., A, B, C" required aria-label="Section">
                                            </div>
                                        </div>
                                        
                                        <div class="form-group full-width">
                                            <label class="form-label">Course <span class="required">*</span></label>
                                            <div class="input-wrapper">
                                                <i class="fas fa-book input-icon"></i>
                                                <select class="form-input" name="course" required>
                                                    <option value="">Select Course</option>
                                                    <option value="Bachelor of Science in Information Technology">Bachelor of Science in Information Technology</option>
                                                    <option value="Bachelor of Science in Computer Science">Bachelor of Science in Computer Science</option>
                                                    <option value="Bachelor of Science in Information Systems">Bachelor of Science in Information Systems</option>
                                                    <option value="Bachelor of Science in Education">Bachelor of Science in Education</option>
                                                    <option value="Bachelor of Science in Business Administration">Bachelor of Science in Business Administration</option>
                                                    <option value="Bachelor of Science in Engineering">Bachelor of Science in Engineering</option>
                                                    <option value="Bachelor of Science in Nursing">Bachelor of Science in Nursing</option>
                                                    <option value="Other">Other</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group full-width">
                                            <label class="form-label">Department</label>
                                            <div class="input-wrapper">
                                                <i class="fas fa-building input-icon"></i>
                                                <input class="form-input" type="text" name="department" placeholder="College of Information and Communications Technology" value="College of Information and Communications Technology" aria-label="Department">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Step 3: Contact Information -->
                                <div class="form-step" data-step="3">
                                    <div class="step-header">
                                        <h4><i class="fas fa-phone"></i> Contact Information</h4>
                                        <p>How can we reach you?</p>
                                    </div>
                                    
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label class="form-label">Email Address <span class="required">*</span></label>
                                            <div class="input-wrapper">
                                                <i class="fas fa-envelope input-icon"></i>
                                                <input class="form-input" type="email" name="email" placeholder="Enter your email address" required aria-label="Email" autocomplete="email">
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="form-label">Phone Number <span class="required">*</span></label>
                                            <div class="input-wrapper">
                                                <i class="fas fa-phone input-icon"></i>
                                                <input class="form-input" type="tel" name="phone" placeholder="+63 912 345 6789" required aria-label="Phone Number" autocomplete="tel">
                                            </div>
                                        </div>
                                        
                                        <div class="form-group full-width">
                                            <label class="form-label">Current Address <span class="required">*</span></label>
                                            <div class="input-wrapper">
                                                <i class="fas fa-map-marker-alt input-icon"></i>
                                                <textarea class="form-input" name="current_address" placeholder="Enter your current address" required aria-label="Current Address" rows="3"></textarea>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group full-width">
                                            <label class="form-label">Permanent Address <span class="required">*</span></label>
                                            <div class="input-wrapper">
                                                <i class="fas fa-home input-icon"></i>
                                                <textarea class="form-input" name="permanent_address" placeholder="Enter your permanent address" required aria-label="Permanent Address" rows="3"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Step 4: Security & Account Setup -->
                                <div class="form-step" data-step="4">
                                    <div class="step-header">
                                        <h4><i class="fas fa-lock"></i> Account Security</h4>
                                        <p>Create a secure password</p>
                                    </div>
                                    
                                    <div class="form-grid">
                                        <div class="form-group">
                                            <label class="form-label">Password <span class="required">*</span></label>
                                            <div class="input-wrapper">
                                                <i class="fas fa-lock input-icon"></i>
                                                <input class="form-input" type="password" id="regPassword" name="password" placeholder="Create a strong password" required aria-label="Password" autocomplete="new-password">
                                                <button type="button" class="password-toggle" data-target="regPassword" aria-label="Show password">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                            <div class="password-requirements">
                                                <div class="requirement" id="req-length">
                                                    <i class="fas fa-circle"></i>
                                                    <span>Minimum 8 characters</span>
                                                </div>
                                                <div class="requirement" id="req-uppercase">
                                                    <i class="fas fa-circle"></i>
                                                    <span>At least one uppercase letter</span>
                                                </div>
                                                <div class="requirement" id="req-lowercase">
                                                    <i class="fas fa-circle"></i>
                                                    <span>At least one lowercase letter</span>
                                                </div>
                                                <div class="requirement" id="req-number">
                                                    <i class="fas fa-circle"></i>
                                                    <span>At least one number</span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label class="form-label">Confirm Password <span class="required">*</span></label>
                                            <div class="input-wrapper">
                                                <i class="fas fa-lock input-icon"></i>
                                                <input class="form-input" type="password" id="confirmPassword" placeholder="Confirm your password" required aria-label="Confirm Password" autocomplete="new-password">
                                                <button type="button" class="password-toggle" data-target="confirmPassword" aria-label="Show password">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                        
                                        <div class="form-group full-width">
                                            <label class="checkbox-wrapper">
                                                <input type="checkbox" name="terms" required id="termsCheckbox">
                                                <span class="checkmark"></span>
                                                <span class="checkbox-label">I agree to the <a href="#" class="terms-link">Terms of Service</a> and <a href="#" class="terms-link">Privacy Policy</a></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Navigation Buttons -->
                                <div class="form-navigation">
                                    <button type="button" class="nav-btn prev-btn" id="prevStep" style="display: none;">
                                        <i class="fas fa-arrow-left"></i>
                                        Previous
                                    </button>
                                    <div class="nav-spacer"></div>
                                    <button type="button" class="nav-btn next-btn" id="nextStep">
                                        Next
                                        <i class="fas fa-arrow-right"></i>
                                    </button>
                                    <button type="submit" class="nav-btn submit-btn" id="submitForm" style="display: none;">
                                        <i class="fas fa-user-plus"></i>
                                        Create Account
                                    </button>
                                </div>
                            </form>

                            <div class="registration-footer">
                                <p>Already have an account? <a href="#" class="switch-link" id="switchToLogin">Sign in</a></p>
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js"></script>
    <script src="assets/landing/landing.js"></script>
    <script src="assets/landing/auth.js"></script>
    
    <script>
        // Initialize announcements slideshow
        $(document).ready(function(){
            $('.slideshow-container').slick({
                dots: true,
                infinite: true,
                speed: 600,
                fade: false,
                autoplay: true,
                autoplaySpeed: 4000,
                arrows: true,
                prevArrow: '<button class="slick-prev" aria-label="Previous"><i class="fas fa-chevron-left"></i></button>',
                nextArrow: '<button class="slick-next" aria-label="Next"><i class="fas fa-chevron-right"></i></button>',
                responsive: [
                    {
                        breakpoint: 768,
                        settings: {
                            arrows: false,
                            dots: true
                        }
                    }
                ]
            });
        });
        
        // Additional landing page interactions
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-open auth modal if needed
            var openPane = '<?= htmlspecialchars($openPane) ?>';
            if (openPane === 'login' || openPane === 'register') {
                try {
                    document.getElementById('authOverlay').classList.add('open');
                    // Force the correct pane to be visible
                    if (openPane === 'register') {
                        document.getElementById('authPaneLogin').style.display = 'none';
                        document.getElementById('authPaneRegister').style.display = 'block';
                        document.getElementById('authPaneLogin').classList.remove('visible');
                        document.getElementById('authPaneRegister').classList.add('visible');
                    } else if (openPane === 'login') {
                        document.getElementById('authPaneRegister').style.display = 'none';
                        document.getElementById('authPaneLogin').style.display = 'block';
                        document.getElementById('authPaneRegister').classList.remove('visible');
                        document.getElementById('authPaneLogin').classList.add('visible');
                    }
                    window.NEUSTAuth && window.NEUSTAuth.switchAuthPane(openPane);
                } catch(e) {
                    console.error('Error opening auth modal:', e);
                }
            }
            
            // Handle pane switching
            document.getElementById('switchToRegister')?.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Switch to register clicked');
                window.NEUSTAuth && window.NEUSTAuth.switchAuthPane('register');
            });
            
            document.getElementById('switchToLogin')?.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Switch to login clicked');
                window.NEUSTAuth && window.NEUSTAuth.switchAuthPane('login');
            });
            
            // Debug: Log all register button clicks
            document.querySelectorAll('#openRegister, #openRegisterBottom, #openRegisterFooter, #ctaRegister').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    console.log('Register button clicked:', this.id);
                });
            });
            
            // Initialize multi-step registration form
            initMultiStepRegistration();
            
            // Password toggle functionality
            document.querySelectorAll('.password-toggle').forEach(toggle => {
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
            
            // Smooth scrolling for anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function (e) {
                    e.preventDefault();
                    const href = this.getAttribute('href');
                    // Only process if href is not just "#"
                    if (href && href !== '#' && href.length > 1) {
                        const target = document.querySelector(href);
                        if (target) {
                            target.scrollIntoView({
                                behavior: 'smooth',
                                block: 'start'
                            });
                        }
                    }
                });
            });
            
            // Add loading states to buttons
            document.querySelectorAll('.btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    if (this.type === 'submit' || this.classList.contains('btn-primary')) {
                        const originalText = this.innerHTML;
                        this.innerHTML = '<span class="spinner"></span>Loading...';
                        this.disabled = true;
                        
                        // Re-enable after 2 seconds (for demo purposes)
                        setTimeout(() => {
                            this.innerHTML = originalText;
                            this.disabled = false;
                        }, 2000);
                    }
                });
            });
        });
        
        // Multi-step Registration Form Handler
        function initMultiStepRegistration() {
            const form = document.getElementById('registerMultiStep');
            if (!form) return;
            
            const steps = form.querySelectorAll('.form-step');
            const progressSteps = document.querySelectorAll('.progress-step');
            const prevBtn = document.getElementById('prevStep');
            const nextBtn = document.getElementById('nextStep');
            const submitBtn = document.getElementById('submitForm');
            
            let currentStep = 1;
            const totalSteps = steps.length;
            
            // Initialize with server-provided error state if present
            const initialStepAttr = form.getAttribute('data-initial-step');
            if (initialStepAttr) {
                const stepNum = parseInt(initialStepAttr, 10);
                if (!isNaN(stepNum) && stepNum >= 1 && stepNum <= totalSteps) {
                    currentStep = stepNum;
                }
            }

            // Apply server-side field errors and persisted values
            <?php if (!empty($formErrors) && ($formErrors['source'] ?? '') === 'landing'): ?>
            const serverErrors = <?= json_encode($formErrors['fields'] ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
            const serverValues = <?= json_encode($formErrors['values'] ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
            Object.keys(serverValues || {}).forEach(function(name){
                const field = form.querySelector('[name="' + name + '"]');
                if (field && field.type !== 'password' && field.tagName !== 'TEXTAREA') {
                    field.value = serverValues[name];
                } else if (field && field.tagName === 'TEXTAREA') {
                    field.textContent = serverValues[name];
                }
            });
            Object.keys(serverErrors || {}).forEach(function(name){
                const field = form.querySelector('[name="' + name + '"]');
                if (!field) return;
                field.classList.add('error');
                let holder = field.parentNode;
                if (holder) {
                    let e = holder.querySelector('.field-error');
                    if (!e) {
                        e = document.createElement('div');
                        e.className = 'field-error';
                        holder.appendChild(e);
                    }
                    e.textContent = serverErrors[name];
                }
            });
            <?php endif; ?>

            updateStepDisplay();
            updateProgress();
            
            // Jump to initial step if provided
            if (currentStep > 1) {
                updateStepDisplay();
                updateProgress();
            }

            // Next button handler
            if (nextBtn) {
                nextBtn.addEventListener('click', function() {
                    if (validateCurrentStep()) {
                        if (currentStep < totalSteps) {
                            currentStep++;
                            updateStepDisplay();
                            updateProgress();
                        }
                    }
                });
            }
            
            // Previous button handler
            if (prevBtn) {
                prevBtn.addEventListener('click', function() {
                    if (currentStep > 1) {
                        currentStep--;
                        updateStepDisplay();
                        updateProgress();
                    }
                });
            }
            
            // Form submission handler
            if (submitBtn) {
                submitBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (validateCurrentStep() && validateAllSteps()) {
                        form.submit();
                    }
                });
            }
            
            // Progress step click navigation
            progressSteps.forEach((step, index) => {
                step.addEventListener('click', function() {
                    const stepNumber = parseInt(this.getAttribute('data-step'));
                    // Allow navigating back and to already completed steps; block jumping forward past validation
                    if (stepNumber <= currentStep) {
                        currentStep = stepNumber;
                        updateStepDisplay();
                        updateProgress();
                    } else {
                        // If trying to jump ahead, validate current step first
                        if (validateCurrentStep()) {
                            currentStep = stepNumber;
                            updateStepDisplay();
                            updateProgress();
                        }
                    }
                });
            });
            
            // Real-time validation
            form.addEventListener('input', function(e) {
                if (e.target.matches('.form-input')) {
                    validateField(e.target);
                }
            });
            
            // Password validation
            const passwordInput = document.getElementById('regPassword');
            const confirmPasswordInput = document.getElementById('confirmPassword');
            
            if (passwordInput) {
                passwordInput.addEventListener('input', function() {
                    validatePassword(this.value);
                    if (confirmPasswordInput.value) {
                        validatePasswordMatch();
                    }
                });
            }
            
            if (confirmPasswordInput) {
                confirmPasswordInput.addEventListener('input', validatePasswordMatch);
            }
            
            function updateStepDisplay() {
                steps.forEach((step, index) => {
                    step.classList.toggle('active', index + 1 === currentStep);
                });
                
                // Update navigation buttons
                if (prevBtn) {
                    prevBtn.style.display = currentStep > 1 ? 'flex' : 'none';
                }
                
                if (nextBtn) {
                    nextBtn.style.display = currentStep < totalSteps ? 'flex' : 'none';
                }
                
                if (submitBtn) {
                    submitBtn.style.display = currentStep === totalSteps ? 'flex' : 'none';
                }
            }
            
            function updateProgress() {
                progressSteps.forEach((step, index) => {
                    const stepNumber = index + 1;
                    step.classList.remove('active', 'completed');
                    
                    if (stepNumber < currentStep) {
                        step.classList.add('completed');
                    } else if (stepNumber === currentStep) {
                        step.classList.add('active');
                    }
                });
            }
            
            function validateCurrentStep() {
                const currentStepElement = steps[currentStep - 1];
                const requiredFields = currentStepElement.querySelectorAll('.form-input[required]');
                let isValid = true;
                
                requiredFields.forEach(field => {
                    if (!validateField(field)) {
                        isValid = false;
                    }
                });
                
                return isValid;
            }
            
            function validateAllSteps() {
                const allRequiredFields = form.querySelectorAll('.form-input[required]');
                let isValid = true;
                
                allRequiredFields.forEach(field => {
                    if (!validateField(field)) {
                        isValid = false;
                    }
                });
                
                return isValid;
            }
            
            function validateField(field) {
                const value = field.value.trim();
                const fieldName = field.name;
                let isValid = true;
                let errorMessage = '';
                
                // Required field validation
                if (field.hasAttribute('required') && !value) {
                    isValid = false;
                    errorMessage = 'This field is required';
                }
                
                // Email validation
                if (fieldName === 'email' && value) {
                    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                    if (!emailRegex.test(value)) {
                        isValid = false;
                        errorMessage = 'Please enter a valid email address';
                    }
                }
                
                // Phone validation
                if (fieldName === 'phone' && value) {
                    const phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,}$/;
                    if (!phoneRegex.test(value)) {
                        isValid = false;
                        errorMessage = 'Please enter a valid phone number';
                    }
                }
                
                // Student ID validation
                if (fieldName === 'user_id' && value) {
                    if (value.length < 3) {
                        isValid = false;
                        errorMessage = 'Student ID must be at least 3 characters';
                    }
                }
                
                // Update field appearance
                field.classList.remove('error', 'valid');
                if (value) {
                    field.classList.add(isValid ? 'valid' : 'error');
                }
                
                // Show/hide error message scoped to input-wrapper parent
                let holder = field.parentNode;
                if (holder && !holder.classList.contains('input-wrapper')) {
                    holder = holder.querySelector('.input-wrapper') || holder;
                }
                let errorElement = holder.querySelector ? holder.querySelector('.field-error') : null;
                if (!isValid && errorMessage) {
                    if (!errorElement) {
                        errorElement = document.createElement('div');
                        errorElement.className = 'field-error';
                        errorElement.style.cssText = 'color: #dc2626; font-size: 0.75rem; margin-top: 0.25rem;';
                        holder.appendChild(errorElement);
                    }
                    errorElement.textContent = errorMessage;
                } else if (errorElement) {
                    errorElement.remove();
                }
                
                return isValid;
            }
            
            function validatePassword(password) {
                const requirements = {
                    length: password.length >= 8,
                    uppercase: /[A-Z]/.test(password),
                    lowercase: /[a-z]/.test(password),
                    number: /\d/.test(password)
                };
                
                Object.keys(requirements).forEach(req => {
                    const element = document.getElementById('req-' + req);
                    if (element) {
                        element.classList.toggle('valid', requirements[req]);
                        element.classList.toggle('invalid', !requirements[req]);
                    }
                });
                
                return Object.values(requirements).every(req => req);
            }
            
            function validatePasswordMatch() {
                const password = passwordInput.value;
                const confirmPassword = confirmPasswordInput.value;
                const isValid = password === confirmPassword && password.length > 0;
                
                confirmPasswordInput.classList.remove('error', 'valid');
                if (confirmPassword) {
                    confirmPasswordInput.classList.add(isValid ? 'valid' : 'error');
                }
                
                return isValid;
            }
        }
    </script>
</body>
</html>


