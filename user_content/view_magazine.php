<?php
session_start();
require_once '../components/connect.php';

$db = new Database();
$conn = $db->connect();

$magazine_id = filter_var($_GET['id'], FILTER_SANITIZE_NUMBER_INT);
$account_id = $_SESSION['account_id'] ?? null;

if(!$magazine_id) {
    header('location:e_magazines.php');
    exit();
}

// Get magazine info
$select_magazine = $conn->prepare("SELECT * FROM e_magazines WHERE magazine_id = ?");
$select_magazine->execute([$magazine_id]);

if($select_magazine->rowCount() > 0) {
    $magazine = $select_magazine->fetch(PDO::FETCH_ASSOC);
    
    // Record the view if user is logged in
    if($account_id) {
        // Insert view record
        $insert_view = $conn->prepare("INSERT INTO magazine_views (magazine_id, account_id) VALUES (?, ?)");
        $insert_view->execute([$magazine_id, $account_id]);
    }
    
    // Redirect to actual magazine link
    header('location: ' . $magazine['link']);
    exit();
} else {
    header('location:e_magazines.php');
    exit();
}