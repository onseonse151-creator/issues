<?php

if (defined('POWER_ADMIN_HEADER_RENDERED')) { return; }
define('POWER_ADMIN_HEADER_RENDERED', true);

$current = basename($_SERVER['SCRIPT_NAME']);
if (!function_exists('isActive')) {
    function isActive($file){
        global $current;
        return $current === $file ? 'active' : '';
    }
}

// User info (for profile image)
$userProfileImg = isset($_SESSION['profile_img']) && $_SESSION['profile_img']
    ? $_SESSION['profile_img']
    : 'assets/default_profile.png'; // Fallback image

$userName = isset($_SESSION['first_name']) && isset($_SESSION['last_name']) 
    ? $_SESSION['first_name'] . ' ' . $_SESSION['last_name']
    : 'Power Admin';

?>
<script>
// Ensure admin theme CSS is loaded even if page forgot the link
(function(){
    var hasTheme = document.querySelector('link[href*="admin_theme.css"]');
    if (!hasTheme) {
        var link = document.createElement('link');
        link.rel = 'stylesheet';
        link.href = 'assets/admin_theme.css';
        document.head.appendChild(link);
    }
})();
</script>

<!-- Enhanced JavaScript for Power Admin -->
<script src="assets/power_admin_ui.js"></script>

<!-- Modern Top Bar -->
<div class="topbar">
    <div class="brand">
        <i class="fa-solid fa-shield-halved"></i> 
        <span>NEUST Power Admin</span>
    </div>
    
    <div class="profile-area">
        <div class="user-info">
            <span class="user-name"><?= htmlspecialchars($userName) ?></span>
            <span class="user-role">Power Administrator</span>
        </div>
        
        <div class="profile-dropdown">
            <form class="profile-img-form" method="post" enctype="multipart/form-data" action="update_profile_img.php" title="Change profile image">
                <label for="profileImgInput" class="profile-img-label">
                    <img src="<?= htmlspecialchars($userProfileImg) ?>" alt="Profile" class="profile-img" id="profileImgDisplay">
                    <input type="file" name="profile_img" id="profileImgInput" accept="image/*" style="display:none" onchange="this.form.submit()">
                </label>
            </form>
            
            <div class="dropdown-menu">
                <a href="#" class="dropdown-item">
                    <i class="fas fa-user"></i> Profile Settings
                </a>
                <a href="#" class="dropdown-item">
                    <i class="fas fa-cog"></i> Preferences
                </a>
                <hr class="dropdown-divider">
                <a href="logout.php" class="dropdown-item logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>
</div>
<aside class="sidebar">
    <div class="sidebar-head">
        <div class="logo">NEUST Gabaldon</div>
        <button class="toggle" id="sidebarToggle" title="Collapse"><i class="fa-solid fa-bars"></i></button>
    </div>
    <a class="nav-link <?= isActive('admin_dashboard.php') ?>" href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> <span class="nav-text">Dashboard</span></a>
    <a class="nav-link <?= isActive('power_admin_announcement.php') ?>" href="power_admin_announcement.php"><i class="fas fa-bullhorn"></i> <span class="nav-text">Announcements</span></a>
    <a class="nav-link <?= isActive('power_admin_users.php') ?>" href="power_admin_users.php"><i class="fas fa-users"></i> <span class="nav-text">Users</span></a>
    <a class="nav-link <?= isActive('power_admin_grievance_queue.php') ?>" href="power_admin_grievance_queue.php"><i class="fas fa-exclamation-triangle"></i> <span class="nav-text">Grievance Management</span></a>
    <div class="nav-group" id="manageAdmin">
        <button class="nav-link nav-group-toggle" type="button"><span><i class="fas fa-user-shield"></i> <span class="nav-text">Manage Admin</span></span> <span class="caret"><i class="fa-solid fa-chevron-down"></i></span></button>
        <div class="subnav">
            <a class="nav-link sub <?= isActive('admin_list.php') ?>" href="admin_list.php"><i class="fas fa-list"></i> <span class="nav-text">Admin List</span></a>
            <a class="nav-link sub <?= isActive('add_admin.php') ?>" href="add_admin.php"><i class="fas fa-user-plus"></i> <span class="nav-text">Add Admin</span></a>
        </div>
    </div>
    <a class="nav-link <?= isActive('power_admin_reports.php') ?>" href="power_admin_reports.php"><i class="fas fa-chart-line"></i> <span class="nav-text">Reports</span></a>
    <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> <span class="nav-text">Logout</span></a>
</aside>
<script>
(function(){
    // Sidebar toggle functionality
    const sidebarBtn = document.getElementById('sidebarToggle');
    if (sidebarBtn) {
        sidebarBtn.addEventListener('click', function(){
            document.body.classList.toggle('sidebar-collapsed');
            localStorage.setItem('sidebar-collapsed', document.body.classList.contains('sidebar-collapsed'));
        });
    }
    // Restore sidebar state
    const sidebarCollapsed = localStorage.getItem('sidebar-collapsed');
    if (sidebarCollapsed === 'true') {
        document.body.classList.add('sidebar-collapsed');
    }

    // Navigation group functionality
    const group = document.getElementById('manageAdmin');
    if (group) {
        const toggle = group.querySelector('.nav-group-toggle');
        toggle.addEventListener('click', function(){
            group.classList.toggle('open');
            localStorage.setItem('manageAdmin-open', group.classList.contains('open'));
        });
        // Restore group state
        const groupOpen = localStorage.getItem('manageAdmin-open');
        if (groupOpen === 'true') {
            group.classList.add('open');
        }
    }

    // Profile image preview (optional, for instant feedback)
    const profileImgInput = document.getElementById('profileImgInput');
    const profileImgDisplay = document.getElementById('profileImgDisplay');
    if (profileImgInput && profileImgDisplay) {
        profileImgInput.addEventListener('change', function(event){
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    profileImgDisplay.src = e.target.result;
                };
                reader.readAsDataURL(this.files[0]);
            }
        });
    }
})();
</script>