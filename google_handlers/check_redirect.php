<?php
session_start();
require_once '../vendor/autoload.php';

// Initialize Google Client with logging
$client = new Google_Client();
$client->setClientId('502512356932-b08caquk2r3lsqtotrl5u82surgi84sq.apps.googleusercontent.com');
$client->setClientSecret('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');

// Try different redirect URIs
$redirectUris = [
    'http://localhost/TheUnivDigest/google_handlers/call_back.php',
    'http://localhost/digest_web_blog_5/google_handlers/call_back.php',
    'http://localhost/new-ud-main/google_handlers/call_back.php'
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Google OAuth Debug</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        h1 { color: #333; }
        .uri-card { background: #f5f5f5; padding: 15px; margin-bottom: 15px; border-radius: 5px; }
        .uri-card h3 { margin-top: 0; }
        a { display: inline-block; margin: 10px 0; padding: 10px 15px; background: #4285F4; color: white; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>Google OAuth Redirect URI Debug</h1>
    
    <p>The error "redirect_uri_mismatch" occurs when the redirect URI in your code doesn't match what's registered in the Google Cloud Console.</p>
    
    <p>Try each of these links below. The one that works is the correctly configured redirect URI in your Google Cloud Console:</p>
    
    <?php foreach($redirectUris as $index => $uri): 
        $client->setRedirectUri($uri);
        $authUrl = $client->createAuthUrl();
    ?>
        <div class="uri-card">
            <h3>Option <?= $index + 1 ?>:</h3>
            <code><?= htmlspecialchars($uri) ?></code>
            <div>
                <a href="<?= $authUrl ?>">Test this URI</a>
            </div>
        </div>
    <?php endforeach; ?>
    
    <h2>Instructions:</h2>
    <ol>
        <li>Click each link above to test different redirect URIs</li>
        <li>When you find one that works (doesn't give the redirect_uri_mismatch error), update your code to use that URI</li>
        <li>If none work, you need to add your current URI to the Google Cloud Console</li>
    </ol>
    
    <h2>How to fix in Google Cloud Console:</h2>
    <ol>
        <li>Go to the <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console Credentials page</a></li>
        <li>Find and edit your OAuth 2.0 Client ID</li>
        <li>Add <code><?= htmlspecialchars($redirectUris[0]) ?></code> to the Authorized redirect URIs</li>
        <li>Save your changes</li>
    </ol>
</body>
</html> 