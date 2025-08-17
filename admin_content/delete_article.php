<?php
// filepath: c:\xampp\htdocs\TDG-PROJECT\admin_content\delete_post.php
require_once __DIR__ . '/../components/connect.php';

$db = new Database();
$conn = $db->connect();

session_start();

$admin_id = $_SESSION['admin_id'] ?? null;
$admin_role = $_SESSION['admin_role'] ?? null;

// Redirect if not logged in or not an admin/subadmin
if(!isset($admin_id) || ($admin_role != 'superadmin' && $admin_role != 'subadmin')){
   header('location:../admin/admin_login.php');
   exit();
}

// Check if post_id is provided
if(isset($_GET['post_id'])){
   $post_id = $_GET['post_id'];
   
   // First verify this post belongs to the current admin
   $verify_post = $conn->prepare("SELECT * FROM posts WHERE post_id = ? AND created_by = ?");
   $verify_post->execute([$post_id, $admin_id]);
   
   if($verify_post->rowCount() > 0){
      $post = $verify_post->fetch(PDO::FETCH_ASSOC);
      $post_image = $post['image'];
      
      // Delete likes related to this post
      $delete_likes = $conn->prepare("DELETE FROM likes WHERE post_id = ?");
      $delete_likes->execute([$post_id]);
      
      // Delete comments related to this post
      $delete_comments = $conn->prepare("DELETE FROM comments WHERE post_id = ?");
      $delete_comments->execute([$post_id]);
      
      // Delete the post
      $delete_post = $conn->prepare("DELETE FROM posts WHERE post_id = ?");
      $delete_post->execute([$post_id]);
      
      // Delete the image file if it exists
      if(!empty($post_image)){
         $image_path = '../uploaded_img/' . $post_image;
         if(file_exists($image_path)){
            unlink($image_path);
         }
      }
      
      // Redirect with success message
      $message = 'Article deleted successfully!';
   } else {
      $message = 'Article not found or you do not have permission to delete it!';
   }
} else {
   $message = 'Missing post ID!';
}

// Set message in session and redirect
$_SESSION['message'] = $message;
header('location:add_articles.php');
exit();
?>