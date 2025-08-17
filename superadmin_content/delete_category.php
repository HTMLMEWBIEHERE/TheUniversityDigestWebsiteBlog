<?php
require_once '../classes/organization.class.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    if (!isset($_POST['category_id'])) {
        throw new Exception("No category ID provided.");
    }

    $org = new Organization();
    $category_id = intval($_POST['category_id']);
    $org->deleteCategory($category_id);

    $response['success'] = true;
    $response['message'] = "Category deleted successfully!";
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
