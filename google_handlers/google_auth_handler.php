<?php
session_start();
require_once '../components/connect.php'; 
require_once '../vendor/autoload.php';

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
} else {
    // Fallback to what seems to be registered in your Google Console
    // Try all possible URIs
    $possible_uris = [
        'http://localhost/TheUnivDigest/google_handlers/call_back.php',
        'http://localhost/digest_web_blog_5/google_handlers/call_back.php',
        'http://localhost/new-ud-main/google_handlers/call_back.php'
    ];
    
    $_SESSION['redirect_debug'] = [
        'detected_project' => $project_folder,
        'current_url' => $current_url,
        'tried_uris' => $possible_uris
    ];
    
    // Default to the first one
    $client->setRedirectUri($possible_uris[0]);
}

$client->addScope('email');
$client->addScope('profile');

// For debugging
$_SESSION['redirect_uri_used'] = $client->getRedirectUri();

try {
    // Generate the Google login URL and redirect
    $authUrl = $client->createAuthUrl();
    header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
    exit;
} catch (Exception $e) {
    $_SESSION['google_error'] = $e->getMessage();
    header('Location: ../user_content/register.php');
    exit;
}
?>