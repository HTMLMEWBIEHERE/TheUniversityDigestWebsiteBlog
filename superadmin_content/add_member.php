<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../classes/organization.class.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not authenticated. Please log in.'
    ]);
    exit();
}

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $org = new Organization();

    try {
        // Ensure that all required fields are captured
        $name = $_POST['name'] ?? '';
        $position = $_POST['position'] ?? '';
        $category = $_POST['category'] ?? '';
        $category_id = $_POST['category_id'] ?? '';
        $new_category = $_POST['new_category'] ?? '';
        $date_appointed = $_POST['date_appointed'] ?? '';
        $date_ended = $_POST['date_ended'] ?? null;
        $image = $_FILES['image'] ?? null; // Handle image if needed

        // Validate required fields
        if (empty($name) || empty($position) || empty($category)) {
            throw new Exception('Missing required member fields.');
        }

        // If category is new, add it to the database
        if ($category === 'new' && !empty($new_category)) {
            // Assuming addCategory method will handle inserting the new category into the database
            $category_id = $org->addCategory($new_category);  // Add new category and get its ID
        }

        // Prepare data for the member
        $data = [
            'name' => $name,
            'position' => $position,
            'category_id' => $category_id,
            'new_category' => $new_category,
            'date_appointed' => $date_appointed,
            'date_ended' => $date_ended,
            'image' => $image,
            'created_by' => $_SESSION['admin_id'] // Ensure this is set before use
        ];

        // Add the organization member
        $message = $org->addOrganization($data);

        echo json_encode([
            'success' => true,
            'message' => $message
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
}