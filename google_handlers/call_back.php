<?php
session_start();
require_once '../components/connect.php';
require_once '../vendor/autoload.php';

$db = new Database();
$conn = $db->connect();

// Try to guess correct project folder from URL
$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$project_folder = '';

if (strpos($current_url, '/TheUnivDigest/') !== false) {
    $project_folder = '/TheUnivDigest';
} elseif (strpos($current_url, '/digest_web_blog_5/') !== false) {
    $project_folder = '/digest_web_blog_5';
} elseif (strpos($current_url, '/new-ud-main/') !== false) {
    $project_folder = '/new-ud-main';
}

// Initialize Google Client
$client = new Google_Client();
$client->setClientId('502512356932-b08caquk2r3lsqtotrl5u82surgi84sq.apps.googleusercontent.com');
$client->setClientSecret('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');

// Set redirect URIs for different project folders
if (!empty($project_folder)) {
    $redirect_uri = "http://localhost" . $project_folder . "/google_handlers/call_back.php";
    $client->setRedirectUri($redirect_uri);
} else if (isset($_SESSION['redirect_uri_used'])) {
    // Use the same URI that was used in the auth handler
    $client->setRedirectUri($_SESSION['redirect_uri_used']);
} else {
    // Fallback
    $client->setRedirectUri('http://localhost/TheUnivDigest/google_handlers/call_back.php');
}

try {
    // Exchange authorization code for access token
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token);

    // Get user information
    $google_oauth = new Google_Service_Oauth2($client);
    $google_account_info = $google_oauth->userinfo->get();

    $email = $google_account_info->email;
    $name = $google_account_info->name;
    $google_id = $google_account_info->id;
    $picture = $google_account_info->picture;

    // Split name into firstname and lastname (simple split)
    $name_parts = explode(' ', $name, 2);
    $firstname = $name_parts[0];
    $lastname = isset($name_parts[1]) ? $name_parts[1] : '';

    // Check if user exists
    $check_user = $conn->prepare("SELECT * FROM `accounts` WHERE email = ? OR google_id = ?");
    $check_user->execute([$email, $google_id]);

    if($check_user->rowCount() > 0) {
        // User exists, log them in
        $row = $check_user->fetch(PDO::FETCH_ASSOC);
        
        // Update google_id if it's not set (for users who previously registered with email but now using Google)
        if (empty($row['google_id'])) {
            $update = $conn->prepare("UPDATE `accounts` SET google_id = ? WHERE account_id = ?");
            $update->execute([$google_id, $row['account_id']]);
        }
        
        // Set session variables
        $_SESSION['account_id'] = $row['account_id'];
        $_SESSION['user_name'] = $row['user_name'];
        $_SESSION['role'] = $row['role'];
        
        // For backward compatibility with admin sections
        if($row['role'] === 'superadmin' || $row['role'] === 'subadmin'){
           $_SESSION['admin_id'] = $row['account_id'];
           $_SESSION['admin_role'] = $row['role'];
        }
        
        // Redirect to home
        header('location: ../user_content/home.php');
        exit;
    } else {
        // Create new user
        $user_name = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $firstname . $lastname)) . rand(100, 999);
        $password = bin2hex(random_bytes(8)); // Random password
        $role = 'user';
        
        // Auto-verify Google accounts
        $insert_user = $conn->prepare("INSERT INTO `accounts` (firstname, lastname, user_name, email, password, role, google_id, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
        $insert_user->execute([$firstname, $lastname, $user_name, $email, password_hash($password, PASSWORD_DEFAULT), $role, $google_id]);
        
        $user_id = $conn->lastInsertId();
        
        // Set session variables
        $_SESSION['account_id'] = $user_id;
        $_SESSION['user_name'] = $user_name;
        $_SESSION['role'] = $role;
        
        // Redirect to home page with welcome message
        $_SESSION['welcome_message'] = "Welcome to The University Digest! Your account has been created.";
        header('location: ../user_content/home.php');
        exit;
    }

} catch (Exception $e) {
    // Error handling with more details
    $_SESSION['message'] = 'Google authentication failed: ' . $e->getMessage() . '. Redirect URI: ' . $client->getRedirectUri();
    header('location: ../user_content/register.php');
    exit;
}
?>