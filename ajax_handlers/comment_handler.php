<?php
// Include database connection
include '../components/connect.php';

// Start session
session_start();

// Check if user is logged in
if (!isset($_SESSION['account_id'])) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Please login to perform this action'
    ]);
    exit;
}

$user_id = $_SESSION['account_id'];
$db = new Database();
$conn = $db->connect();

// Handle different actions
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'add_comment':
        addComment($conn, $user_id);
        break;
        
    case 'edit_comment':
        editComment($conn, $user_id);
        break;
        
    case 'delete_comment':
        deleteComment($conn, $user_id);
        break;
        
    case 'get_comment_count':
        getCommentCount($conn, $user_id);
        break;
        
    default:
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid action'
        ]);
}

// Add comment function
function addComment($conn, $user_id) {
    // Get post data
    $post_id = $_POST['post_id'] ?? 0;
    $comment = $_POST['comment'] ?? '';
    
    // Validate data
    if(empty($post_id) || empty($comment)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing required fields'
        ]);
        exit;
    }
    
    // Get user's IP address
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    // Check comment limits by user account
    $count_user_comments = $conn->prepare("SELECT COUNT(*) as comment_count FROM `comments` WHERE post_id = ? AND commented_by = ?");
    $count_user_comments->execute([$post_id, $user_id]);
    $user_comment_count = $count_user_comments->fetch(PDO::FETCH_ASSOC)['comment_count'];
    
    // Check comment limits by IP address
    $count_ip_comments = $conn->prepare("SELECT COUNT(*) as ip_comment_count FROM `comments` WHERE post_id = ? AND ip_address = ?");
    $count_ip_comments->execute([$post_id, $ip_address]);
    $ip_comment_count = $count_ip_comments->fetch(PDO::FETCH_ASSOC)['ip_comment_count'];
    
    // Determine remaining comments (use the higher count between user and IP)
    $max_count = max($user_comment_count, $ip_comment_count);
    
    if($max_count >= 5) {
        echo json_encode([
            'status' => 'error',
            'message' => 'You have reached the maximum limit of 5 comments per post.'
        ]);
        exit;
    }
    
    // Sanitize comment
    $comment = htmlspecialchars($comment, ENT_QUOTES, 'UTF-8');
    
    // Check for duplicate comment
    $verify_comment = $conn->prepare("SELECT * FROM `comments` WHERE post_id = ? AND commented_by = ? AND comment = ?");
    $verify_comment->execute([$post_id, $user_id, $comment]);
    
    if($verify_comment->rowCount() > 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Comment already added!'
        ]);
        exit;
    }
    
    // Insert the comment with IP address
    $insert_comment = $conn->prepare("INSERT INTO `comments`(post_id, commented_by, ip_address, comment) VALUES(?,?,?,?)");
    $success = $insert_comment->execute([$post_id, $user_id, $ip_address, $comment]);
    
    if($success) {
        // Get comment ID and user info for response
        $comment_id = $conn->lastInsertId();
        
        // Get user name for the response
        $get_user = $conn->prepare("SELECT user_name FROM accounts WHERE account_id = ?");
        $get_user->execute([$user_id]);
        $user_name = $get_user->fetch(PDO::FETCH_ASSOC)['user_name'] ?? 'User';
        
        // Calculate remaining comments
        $comments_remaining = 5 - ($max_count + 1);
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Comment added successfully!',
            'comment_id' => $comment_id,
            'user_name' => $user_name,
            'comments_remaining' => $comments_remaining
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to add comment'
        ]);
    }
}

// Function to get remaining comment count
function getCommentCount($conn, $user_id) {
    $post_id = $_POST['post_id'] ?? 0;
    
    if(empty($post_id)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing post ID'
        ]);
        exit;
    }
    
    // Get user's IP address
    $ip_address = $_SERVER['REMOTE_ADDR'];
    
    // Check comment limits by user account
    $count_user_comments = $conn->prepare("SELECT COUNT(*) as comment_count FROM `comments` WHERE post_id = ? AND commented_by = ?");
    $count_user_comments->execute([$post_id, $user_id]);
    $user_comment_count = $count_user_comments->fetch(PDO::FETCH_ASSOC)['comment_count'];
    
    // Check comment limits by IP address
    $count_ip_comments = $conn->prepare("SELECT COUNT(*) as ip_comment_count FROM `comments` WHERE post_id = ? AND ip_address = ?");
    $count_ip_comments->execute([$post_id, $ip_address]);
    $ip_comment_count = $count_ip_comments->fetch(PDO::FETCH_ASSOC)['ip_comment_count'];
    
    // Determine remaining comments
    $max_count = max($user_comment_count, $ip_comment_count);
    $comments_remaining = 5 - $max_count;
    
    echo json_encode([
        'status' => 'success',
        'comments_remaining' => $comments_remaining,
        'user_count' => $user_comment_count,
        'ip_count' => $ip_comment_count
    ]);
}

// Edit comment function
function editComment($conn, $user_id) {
    $comment_id = $_POST['comment_id'] ?? '';
    $comment = $_POST['comment'] ?? '';
    
    // Validate data
    if (empty($comment_id) || empty($comment)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing required data'
        ]);
        exit;
    }
    
    // Sanitize data
    $comment = filter_var($comment, FILTER_SANITIZE_STRING);
    
    // Verify the comment exists and belongs to this user
    $verify_comment = $conn->prepare("SELECT * FROM `comments` WHERE comment_id = ? AND commented_by = ?");
    $verify_comment->execute([$comment_id, $user_id]);
    
    if ($verify_comment->rowCount() == 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Comment not found or you do not have permission to edit!'
        ]);
        exit;
    }
    
    // Update comment
    $update_comment = $conn->prepare("UPDATE `comments` SET comment = ? WHERE comment_id = ?");
    $update_comment->execute([$comment, $comment_id]);
    
    if ($update_comment->rowCount() > 0) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Comment updated successfully!'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'No changes made to the comment.'
        ]);
    }
}

// Delete comment function
function deleteComment($conn, $user_id) {
    $comment_id = $_POST['comment_id'] ?? '';
    
    // Validate data
    if (empty($comment_id)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Missing required data'
        ]);
        exit;
    }
    
    // Get post_id before deleting (needed to calculate updated limits)
    $get_comment = $conn->prepare("SELECT post_id FROM `comments` WHERE comment_id = ? AND commented_by = ?");
    $get_comment->execute([$comment_id, $user_id]);
    
    if ($get_comment->rowCount() == 0) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Comment not found or you do not have permission to delete!'
        ]);
        exit;
    }
    
    // Store post_id before deletion
    $post_id = $get_comment->fetch(PDO::FETCH_ASSOC)['post_id'];
    
    // Delete comment
    $delete_comment = $conn->prepare("DELETE FROM `comments` WHERE comment_id = ?");
    $delete_comment->execute([$comment_id]);
    
    if ($delete_comment->rowCount() > 0) {
        // Get user's IP address
        $ip_address = $_SERVER['REMOTE_ADDR'];
        
        // Re-check comment limits by user account AFTER deletion
        $count_user_comments = $conn->prepare("SELECT COUNT(*) as comment_count FROM `comments` WHERE post_id = ? AND commented_by = ?");
        $count_user_comments->execute([$post_id, $user_id]);
        $user_comment_count = $count_user_comments->fetch(PDO::FETCH_ASSOC)['comment_count'];
        
        // Re-check comment limits by IP address AFTER deletion
        $count_ip_comments = $conn->prepare("SELECT COUNT(*) as ip_comment_count FROM `comments` WHERE post_id = ? AND ip_address = ?");
        $count_ip_comments->execute([$post_id, $ip_address]);
        $ip_comment_count = $count_ip_comments->fetch(PDO::FETCH_ASSOC)['ip_comment_count'];
        
        // Calculate remaining comments based on the higher count
        $max_count = max($user_comment_count, $ip_comment_count);
        $comments_remaining = 5 - $max_count;
        
        echo json_encode([
            'status' => 'success',
            'message' => 'Comment deleted successfully!',
            'comments_remaining' => $comments_remaining,
            'user_count' => $user_comment_count,
            'ip_count' => $ip_comment_count
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to delete comment. Please try again.'
        ]);
    }
}
?>