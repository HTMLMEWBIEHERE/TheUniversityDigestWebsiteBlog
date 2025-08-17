<?php

include '../components/connect.php';

$db = new Database();
$conn = $db->connect();

session_start();

$admin_id = $_SESSION['admin_id'] ?? null;
$admin_role = $_SESSION['admin_role'] ?? null;

// Redirect if not logged in
if(!isset($admin_id)){
   header('location:../admin/admin_login.php');
   exit();
}

// Initialize message array
$message = [];

// Check for messages in session
if(isset($_SESSION['message'])) {
   $message[] = $_SESSION['message'];
   unset($_SESSION['message']); // Clear after displaying
}

// Handle post deletion
if(isset($_POST['delete'])){
   $p_id = $_POST['post_id'];
   $p_id = filter_var($p_id, FILTER_SANITIZE_STRING);
   
   try {
      // Begin transaction
      $conn->beginTransaction();
      
      // Get image info for deletion
      $delete_image = $conn->prepare("SELECT * FROM `posts` WHERE post_id = ? AND created_by = ?");
      $delete_image->execute([$p_id, $admin_id]);
      $fetch_delete_image = $delete_image->fetch(PDO::FETCH_ASSOC);
      
      if($delete_image->rowCount() > 0) {
         // Delete post likes
         $delete_likes = $conn->prepare("DELETE FROM `likes` WHERE post_id = ?");
         $delete_likes->execute([$p_id]);
         
         // Delete post comments
         $delete_comments = $conn->prepare("DELETE FROM `comments` WHERE post_id = ?");
         $delete_comments->execute([$p_id]);
         
         // Delete post
         $delete_post = $conn->prepare("DELETE FROM `posts` WHERE post_id = ? AND created_by = ?");
         $delete_post->execute([$p_id, $admin_id]);
         
         // Commit transaction
         $conn->commit();
         
         // Delete image file if exists
         if($fetch_delete_image['image'] != ''){
            unlink('../uploaded_img/'.$fetch_delete_image['image']);
         }
         
         $message[] = 'Post deleted successfully!';
      } else {
         $message[] = 'You do not have permission to delete this post';
         $conn->rollBack();
      }
   } catch (Exception $e) {
      $conn->rollBack();
      $message[] = 'Error: ' . $e->getMessage();
   }
}

// Add publish functionality
if(isset($_POST['publish'])){
   $p_id = $_POST['post_id'];
   $p_id = filter_var($p_id, FILTER_SANITIZE_STRING);
   
   $update_status = $conn->prepare("UPDATE `posts` SET status = 'published' WHERE post_id = ? AND created_by = ?");
   $update_status->execute([$p_id, $admin_id]);
   
   if($update_status->rowCount() > 0){
      $message[] = 'Post published successfully!';
   } else {
      $message[] = 'Failed to publish post!';
   }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Draft Posts</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="../css/admin_style.css">

</head>
<body>

<?php include '../components/admin_header.php' ?>

<section class="show-posts">

   <h1 class="heading">your draft posts</h1>
   
   <?php
   if(!empty($message)){
      foreach($message as $msg){
         echo '
         <div class="message">
            <span>'.$msg.'</span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
         </div>
         ';
      }
   }
   ?>

   <div class="box-container">

      <?php
         $select_posts = $conn->prepare("SELECT * FROM `posts` WHERE created_by = ? AND status = 'draft'");
         $select_posts->execute([$admin_id]);
         if($select_posts->rowCount() > 0){
            while($fetch_posts = $select_posts->fetch(PDO::FETCH_ASSOC)){
               $post_id = $fetch_posts['post_id'];

               $count_post_comments = $conn->prepare("SELECT * FROM `comments` WHERE post_id = ?");
               $count_post_comments->execute([$post_id]);
               $total_post_comments = $count_post_comments->rowCount();

               $count_post_likes = $conn->prepare("SELECT * FROM `likes` WHERE post_id = ?");
               $count_post_likes->execute([$post_id]);
               $total_post_likes = $count_post_likes->rowCount();

      ?>
      <form method="post" class="box">
         <input type="hidden" name="post_id" value="<?= $post_id; ?>">
         <?php if($fetch_posts['image'] != ''){ ?>
            <img src="../uploaded_img/<?= $fetch_posts['image']; ?>" class="image" alt="">
         <?php } ?>
         <div class="status" style="background-color:coral;"><?= $fetch_posts['status']; ?></div>
            <div class="title"><?= $fetch_posts['title']; ?></div>
         <div class="posts-content"><?= $fetch_posts['content']; ?></div>
         <div class="icons">
            <div class="likes"><i class="fas fa-heart"></i><span><?= $total_post_likes; ?></span></div>
            <div class="comments"><i class="fas fa-comment"></i><span><?= $total_post_comments; ?></span></div>
         </div>
         <div class="flex-btn">
            <a href="edit_post.php?post_id=<?= $post_id; ?>" class="option-btn">edit</a>
            <button type="submit" name="publish" class="btn">publish</button>
         </div>   
      </form>
      <?php
            }
         }else{
            echo '<p class="empty">no draft posts added yet! <a href="add_posts.php" class="btn" style="margin-top:1.5rem;">add post</a></p>';
         }
      ?>

   </div>

</section>

<!-- custom js file link  -->
<script src="../js/admin_script.js"></script>

</body>
</html>