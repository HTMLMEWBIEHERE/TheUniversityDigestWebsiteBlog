<?php
session_start();
require_once '../classes/organization.class.php';

header('Content-Type: application/json');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $org_id = $_POST['org_id'] ?? null;

        if (!$org_id) {
            throw new Exception('Invalid member ID');
        }

        $organization = new Organization();
        $result = $organization->revertMember($org_id);

        echo json_encode([
            'success' => true,
            'message' => $result
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
