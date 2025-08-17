<?php
require_once __DIR__ . '/../classes/organization.class.php';

$organization = new Organization();

try {
    // Fetch all categories
    $categories = $organization->getCategories();
    echo json_encode($categories);
} catch (Exception $e) {
    // Handle error if categories cannot be fetched
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>