<?php
require_once __DIR__ . '/../classes/organization.class.php';

$organization = new Organization();

if (isset($_GET['org_id'])) {
    $org_id = $_GET['org_id'];

    try {
        $member = $organization->getOrganizationById($org_id);
        echo json_encode(['status' => 'success', 'member' => $member]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>