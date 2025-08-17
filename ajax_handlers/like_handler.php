<?php
// Include database connection
include '../components/connect.php';

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['account_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please login to like posts'
    ]);
    exit;
}

$user_id = $_SESSION['account_id'];
$db = new Database();
$conn = $db->connect();

// Get post_id from request
$post_id = $_POST['post_id'] ?? '';

if (empty($post_id)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing post ID'
    ]);
    exit;
}

// Check if user already liked the post
$check_like = $conn->prepare("SELECT * FROM `likes` WHERE post_id = ? AND account_id = ?");
$check_like->execute([$post_id, $user_id]);

if ($check_like->rowCount() > 0) {
    // User already liked, so remove the like
    $delete_like = $conn->prepare("DELETE FROM `likes` WHERE post_id = ? AND account_id = ?");
    $delete_like->execute([$post_id, $user_id]);
    
    // Get updated like count
    $count_likes = $conn->prepare("SELECT * FROM `likes` WHERE post_id = ?");
    $count_likes->execute([$post_id]);
    $total_likes = $count_likes->rowCount();
    
    echo json_encode([
        'status' => 'success',
        'action' => 'unliked',
        'likes' => $total_likes
    ]);
} else {
    // User hasn't liked, so add a like
    $insert_like = $conn->prepare("INSERT INTO `likes` (post_id, account_id) VALUES (?, ?)");
    $insert_like->execute([$post_id, $user_id]);
    
    // Get updated like count
    $count_likes = $conn->prepare("SELECT * FROM `likes` WHERE post_id = ?");
    $count_likes->execute([$post_id]);
    $total_likes = $count_likes->rowCount();
    
    echo json_encode([
        'status' => 'success',
        'action' => 'liked',
        'likes' => $total_likes
    ]);
}
?>