<?php
header('Content-Type: application/json');

require_once '../classes/organization.class.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryName = trim($_POST['category_name'] ?? '');

    if ($categoryName === '') {
        echo json_encode([
            'success' => false,
            'message' => 'Category name cannot be empty.'
        ]);
        exit;
    }

    try {
        $organization = new Organization();
        $categoryId = $organization->addCategory($categoryName);

        // Fetch all categories after adding the new one
        $categories = $organization->getCategories(); // Assuming this method fetches all categories

        // Prepare category options for the dropdown
        $categoryOptions = '';
        foreach ($categories as $category) {
            $categoryOptions .= '<option value="' . $category['category_id'] . '">' . $category['category_name'] . '</option>';
        }

        echo json_encode([
            'success' => true,
            'message' => 'Category added successfully.',
            'category_id' => $categoryId,
            'category_name' => $categoryName,
            'category_options' => $categoryOptions // Return all categories as HTML
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
}
