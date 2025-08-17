<?php
require_once __DIR__ . '/../classes/organization.class.php';

header('Content-Type: application/json');

session_start();

$admin_id = $_SESSION['admin_id'] ?? null;
$admin_role = $_SESSION['admin_role'] ?? null;

if (!isset($admin_id) || $admin_role !== 'superadmin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $organization = new Organization();
    $org_members = $organization->getOrganizations();
    echo json_encode(['success' => true, 'members' => $org_members]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}