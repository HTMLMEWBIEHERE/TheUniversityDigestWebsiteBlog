<?php
// Make sure we have a connection
$db = new Database();
$conn = $db->connect();

// Check if admin is logged in as superadmin
$admin_id = $_SESSION['admin_id'] ?? null;
$admin_role = $_SESSION['admin_role'] ?? null;
$is_superadmin = isset($admin_id) && ($admin_role === 'superadmin' || $admin_role === 'subadmin');

// If not a superadmin, redirect immediately
if (!$is_superadmin) {
    header('Location: ../access_denied.php');
    exit(); // Stop script execution after redirect
}

// Display messages if they exist
if (isset($message)) {
    if (is_array($message)) {
        foreach ($message as $msg) {
            echo '<div class="message"><span>' . htmlspecialchars($msg) . '</span><i class="fas fa-times" onclick="this.parentElement.remove();"></i></div>';
        }
    } else {
        echo '<div class="message"><span>' . htmlspecialchars($message) . '</span><i class="fas fa-times" onclick="this.parentElement.remove();"></i></div>';
    }
}

// Get admin profile information
$select_profile = $conn->prepare("SELECT account_id, firstname, lastname, image FROM `accounts` WHERE account_id = ? LIMIT 1");
$select_profile->execute([$admin_id]);
$fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);

// Set default profile image if none exists
$profile_image = '../uploads/profiles/default.jpg';
if ($fetch_profile && !empty($fetch_profile['image'])) {
    $profile_image = '../uploads/profiles/' . htmlspecialchars($fetch_profile['image']);
}

// Get admin's full name
$admin_name = $fetch_profile ? htmlspecialchars($fetch_profile['firstname'] . ' ' . $fetch_profile['lastname']) : 'Admin';
?>

<header class="header">
    <a href="../super_admin/superadmin_dashboard.php" class="logo">Super<span>Admin</span></a>

    <div class="profile">
        <div class="profile-image-container">
            <img src="<?= $profile_image; ?>" alt="Profile" class="profile-pic">
        </div>
        <p><?= $admin_name; ?></p>
        <a href="../super_admin/superadmin_update_profile.php" class="btn">Update Profile</a>
    </div>

    <nav class="navbar">
    <a href="../super_admin/superadmin_analytics.php">
        <i class="fas fa-chart-line"></i> 
        <span>Dashboard</span>
    </a>
    <a href="../super_admin/superadmin_dashboard.php">
        <i class="fas fa-tools"></i> 
        <span>Control Panel</span>
    </a>
    <a href="../superadmin_content/sa_add_posts.php">
        <i class="fas fa-pen"></i> 
        <span>Manage Posts</span>
    </a>
    <a href="../superadmin_content/sa_add_carousel.php">
        <i class="fas fa-images"></i> 
        <span>Manage Carousel</span>
    </a>


    <!-- // Dropdown Menu -->
    <div class="dropdown">
        <a href="#" class="dropdown-toggle">
            <i class="fas fa-cog"></i> 
            <span>Manage Content</span> 
            <i class="fas fa-chevron-down"></i>
        </a>
        <div class="dropdown-menu">
            <a href="../superadmin_content/sa_purpose.php">
            <i class="fas fa-exclamation-circle"></i>
            <span>Purpose Card</span>
            </a>
            <a href="../superadmin_content/sa_aboutus.php">
                <i class="fas fa-question"></i> 
                <span>About Us</span>
            </a>
            <a href="../superadmin_content/sa_footer.php">
            <i class="fas fa-file-signature"></i> 
            <span>Footer</span>
            </a>
        </div>
    </div>


    
    <a href="../superadmin_content/sa_view_posts.php">
        <i class="fas fa-eye"></i> 
        <span>View Drafts</span>
    </a>
    <a href="../superadmin_content/sa_admin_accounts.php">
        <i class="fas fa-user-shield"></i> 
        <span>Admin Accounts</span>
    </a>
    <a href="../superadmin_content/sa_manage_emagazines.php">
        <i class="fas fa-book"></i> 
        <span>E-Magazines</span>
    </a>
    <a href="../superadmin_content/sa_magazine_downloads.php">
        <i class="fas fa-file-download"></i> 
        <span>View Magazine Downloads</span>
    </a>
    <a href="../superadmin_content/sa_manage_announcements.php">
        <i class="fas fa-bullhorn"></i> 
        <span>Announcements</span>
    </a>
    <a href="../superadmin_content/sa_manage_org_chart.php">
        <i class="fas fa-sitemap"></i> 
        <span>Organizational Chart</span>
    </a>
    <a href="../superadmin_content/sa_site_settings.php">
        <i class="fas fa-cogs"></i> 
        <span>Site Settings</span>
    </a>
    <a href="../superadmin_content/sa_activity_logs.php">
        <i class="fas fa-clipboard-list"></i> 
        <span>Activity Logs</span>
    </a>
    <a href="../components/admin_logout.php" class="logout-link" onclick="return confirm('Logout from the website?');">
        <i class="fas fa-right-from-bracket"></i>
        <span>Logout</span>
    </a>
</nav>

    <div class="flex-btn">
        <a href="../superadmin_content/sa_admin_accounts.php" class="option-btn">Accounts</a>
        <a href="../user_content/home.php" class="option-btn">Back to Home</a>
    </div>
</header>

<div id="menu-btn" class="fas fa-bars"></div>
<script src="../js/dropdown.js"></script>