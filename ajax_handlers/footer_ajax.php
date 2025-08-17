<?php
/**
 * Footer AJAX Handler
 * Processes AJAX requests for footer management
 */

// Include necessary files
include_once '../components/connect.php';
include_once '../superadmin_content/footer_controller.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
$admin_id = $_SESSION['admin_id'] ?? null;
$admin_role = $_SESSION['admin_role'] ?? null;

// Initialize response array
$response = [
    'success' => false,
    'message' => 'Invalid request'
];

// Verify user is logged in and is a superadmin
if (!isset($admin_id) || $admin_role != 'superadmin') {
    $response['message'] = 'Unauthorized access';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// Initialize database connection
$db = new Database();
$conn = $db->connect();

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'get_socials') {
        // Get footer data
        $footer = getFooter();
        $socials = getFooterSocials($footer['footer_id']);
        
        $response = [
            'success' => true,
            'socials' => $socials,
            'footer' => $footer
        ];
    }
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Update footer information
    if ($action === 'update_footer') {
        $footer_id = $_POST['footer_id'] ?? 0;
        $address = $_POST['address'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $copyright_text = $_POST['copyright_text'] ?? '';
        
        // Validate inputs
        if (empty($address) || empty($email) || empty($phone)) {
            $response['message'] = 'Please fill in all required fields';
        } else {
            $data = [
                'footer_id' => $footer_id,
                'address' => $address,
                'email' => $email,
                'phone' => $phone,
                'copyright_text' => $copyright_text
            ];
            
            $result = updateFooter($data);
            
            if ($result) {
                $response = [
                    'success' => true,
                    'message' => 'Footer information updated successfully!'
                ];
            } else {
                $response['message'] = 'Failed to update footer information';
            }
        }
    }
    
    // Add social media link
    else if ($action === 'add_social') {
        $platform = $_POST['platform'] ?? '';
        $url = $_POST['url'] ?? '';
        $icon_class = $_POST['icon_class'] ?? '';
        $footer_id = $_POST['footer_id'] ?? 0;
        
        // Validate inputs
        if (empty($platform) || empty($url) || empty($icon_class) || empty($footer_id)) {
            $response['message'] = 'Please fill in all required fields';
        } else {
            $data = [
                'platform' => $platform,
                'url' => $url,
                'icon_class' => $icon_class,
                'footer_id' => $footer_id
            ];
            
            $result = addFooterSocial($data);
            
            if ($result) {
                $response = [
                    'success' => true,
                    'message' => 'Social media link added successfully!'
                ];
            } else {
                $response['message'] = 'Failed to add social media link';
            }
        }
    }
    
    // Update social media link
    else if ($action === 'update_social') {
        $social_id = $_POST['social_id'] ?? 0;
        $platform = $_POST['platform'] ?? '';
        $url = $_POST['url'] ?? '';
        $icon_class = $_POST['icon_class'] ?? '';
        
        // Validate inputs
        if (empty($social_id) || empty($platform) || empty($url) || empty($icon_class)) {
            $response['message'] = 'Please fill in all required fields';
        } else {
            $data = [
                'social_id' => $social_id,
                'platform' => $platform,
                'url' => $url,
                'icon_class' => $icon_class
            ];
            
            $result = updateFooterSocial($data);
            
            if ($result) {
                $response = [
                    'success' => true,
                    'message' => 'Social media link updated successfully!'
                ];
            } else {
                $response['message'] = 'Failed to update social media link';
            }
        }
    }
    
    // Delete social media link
    else if ($action === 'delete_social') {
        $social_id = $_POST['social_id'] ?? 0;
        
        // Validate input
        if (empty($social_id)) {
            $response['message'] = 'Invalid social media ID';
        } else {
            $result = deleteFooterSocial($social_id);
            
            if ($result) {
                $response = [
                    'success' => true,
                    'message' => 'Social media link deleted successfully!'
                ];
            } else {
                $response['message'] = 'Failed to delete social media link';
            }
        }
    }
}

// Always return JSON response
header('Content-Type: application/json');
echo json_encode($response);
exit;