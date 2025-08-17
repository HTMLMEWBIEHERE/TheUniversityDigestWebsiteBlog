<?php
require_once __DIR__ . '/../classes/organization.class.php';

header('Content-Type: application/json');

try {
    // Only org_id is strictly required
    if (empty($_POST['org_id'])) {
        throw new Exception('Member ID (org_id) is required');
    }

    $organization = new Organization();
    $org_id = $_POST['org_id'];

    // Get existing member data
    $existingData = $organization->getOrganizationById($org_id);
    if (!$existingData) {
        throw new Exception('Member not found');
    }

    // Prepare update data - only update fields that were provided
    $updateData = [
        'name' => $_POST['name'] ?? $existingData['name'],
        'position' => $_POST['position'] ?? $existingData['position'],
        'category_id' => $_POST['category_id'] ?? $existingData['category_id'],
        'date_appointed' => $_POST['date_appointed'] ?? $existingData['date_appointed'],
        'date_ended' => $_POST['date_ended'] ?? $existingData['date_ended'],
        'existing_image' => $existingData['image'] // Always keep existing as fallback
    ];

    // Handle image upload if provided
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $updateData['image'] = $_FILES['image'];
    } else {
        $updateData['image'] = $updateData['existing_image'];
    }

    // Perform the update
    $result = $organization->editOrganization($org_id, $updateData);

    echo json_encode([
        'status' => 'success',
        'message' => 'Member updated successfully',
        'data' => $updateData
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
