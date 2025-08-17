<?php
include 'connect.php';

// Start the session
session_start();

// Save role before clearing session
$was_admin = isset($_SESSION['admin_role']) && ($_SESSION['admin_role'] == 'superadmin' || $_SESSION['admin_role'] == 'subadmin');
$came_from_user_section = isset($_GET['from']) && $_GET['from'] == 'user';
$redirect_to_home = isset($_GET['redirect']) && $_GET['redirect'] == 'home';

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
}

// Destroy the session
session_destroy();

// Determine where to redirect based on context
if ($redirect_to_home) {
    // Redirect to home page 
    header('location: ../user_content/home.php?logout=' . time());
} else if ($came_from_user_section) {
    // If logged out from user section, go to user login
    header('location: ../user_content/login_users.php?logout=' . time());
} else {
    // Otherwise go to admin login (default)
    header('location: ../user_content/landing_page.php?logout=' . time());
}
exit();
?>