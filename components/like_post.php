<?php
require_once 'connect.php';   
$db = new Database();
$conn = $db->connect();

// Add these lines for session handling
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize user_id from session
$user_id = '';
if (isset($_SESSION['account_id'])) {
    $user_id = $_SESSION['account_id'];
}

// Check if this is an AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

// Debug info
if ($isAjax) {
    error_log("AJAX request received");
    error_log("POST data: " . print_r($_POST, true));
    error_log("User ID: " . $user_id);
}

if(isset($_POST['like_post']) || ($isAjax && isset($_POST['post_id']))){

   if($user_id != ''){
      
      // Get post_id from either regular form or AJAX request
      $post_id = isset($_POST['post_id']) ? $_POST['post_id'] : '';
      $post_id = filter_var($post_id, FILTER_SANITIZE_STRING);
      
      if (empty($post_id)) {
          if ($isAjax) {
              header('Content-Type: application/json');
              echo json_encode(['status' => 'error', 'message' => 'No post ID provided']);
              exit;
          }
          return; // Exit if no post_id
      }
      
      // Select query with account_id
      $select_post_like = $conn->prepare("SELECT * FROM `likes` WHERE post_id = ? AND account_id = ?");
      $select_post_like->execute([$post_id, $user_id]);

      if($select_post_like->rowCount() > 0){
         // Unlike post
         $remove_like = $conn->prepare("DELETE FROM `likes` WHERE post_id = ? AND account_id = ?");
         $remove_like->execute([$post_id, $user_id]);
         $action = 'unliked';
      }else{
         // Like post
         $add_like = $conn->prepare("INSERT INTO `likes`(account_id, post_id) VALUES(?,?)");
         $add_like->execute([$user_id, $post_id]);
         $action = 'liked';
      }
      
      // Count total likes
      $count_post_likes = $conn->prepare("SELECT * FROM `likes` WHERE post_id = ?");
      $count_post_likes->execute([$post_id]);
      $total_likes = $count_post_likes->rowCount();
      
      // If AJAX request, return JSON
      if($isAjax){
         header('Content-Type: application/json');
         echo json_encode([
            'status' => 'success',
            'action' => $action,
            'likes' => $total_likes
         ]);
         exit; // Stop further execution for AJAX requests
      } else {
         $message[] = "Post $action!";
      }
      
   } else {
      if($isAjax){
         header('Content-Type: application/json');
         echo json_encode(['status' => 'error', 'message' => 'Please login first!']);
         exit;
      } else {
         $message[] = 'please login first!';
      }
   }
}
?>