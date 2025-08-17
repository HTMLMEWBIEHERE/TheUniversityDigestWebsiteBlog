<?php
require_once __DIR__ . '/../components/connect.php';

$db = new Database();
$conn = $db->connect();

session_start();

$admin_id = $_SESSION['admin_id'] ?? null;
$admin_role = $_SESSION['admin_role'] ?? null;

// Redirect if not logged in as superadmin
if(!isset($admin_id) || $admin_role != 'superadmin'){
   header('location:../admin/admin_login.php');
   exit();
}

// Initialize message array
$message = [];

if(isset($_POST['delete'])){
   $p_id = $_POST['post_id'];
   $p_id = filter_var($p_id, FILTER_SANITIZE_NUMBER_INT);
   
   // Begin transaction
   $conn->beginTransaction();
   
   try {
      // Get image details first
      $delete_image = $conn->prepare("SELECT * FROM `posts` WHERE post_id = ? AND created_by = ?");
      $delete_image->execute([$p_id, $admin_id]);
      $fetch_delete_image = $delete_image->fetch(PDO::FETCH_ASSOC);
      
      if($delete_image->rowCount() > 0) {
         // Delete comments
         $delete_comments = $conn->prepare("DELETE FROM `comments` WHERE post_id = ?");
         $delete_comments->execute([$p_id]);
         
         // Delete likes
         $delete_likes = $conn->prepare("DELETE FROM `likes` WHERE post_id = ?");
         $delete_likes->execute([$p_id]);
         
         // Delete the post
         $delete_post = $conn->prepare("DELETE FROM `posts` WHERE post_id = ? AND created_by = ?");
         $delete_post->execute([$p_id, $admin_id]);
         
         // Commit the transaction
         $conn->commit();
         
         // Delete image file if exists
         if($fetch_delete_image['image'] != '' && file_exists('../uploaded_img/'.$fetch_delete_image['image'])){
            unlink('../uploaded_img/'.$fetch_delete_image['image']);
         }
         
         $message[] = 'Post deleted successfully!';
      } else {
         $conn->rollBack();
         $message[] = 'Failed to delete post or you do not have permission!';
      }
   } catch (Exception $e) {
      $conn->rollBack();
      $message[] = 'Error: ' . $e->getMessage();
   }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Draft Posts - Superadmin</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<?php include '../components/superadmin_header.php'; ?>

<section class="show-posts">
   <h1 class="heading">Your Draft Posts</h1>
   
   <?php if(isset($message)): ?>
      <?php foreach($message as $msg): ?>
         <div class="message">
            <span><?= htmlspecialchars($msg); ?></span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
         </div>
      <?php endforeach; ?>
   <?php endif; ?>

   <div class="box-container">
      <?php
         $select_posts = $conn->prepare("SELECT * FROM `posts` WHERE created_by = ? AND status = 'draft' ORDER BY post_id DESC");
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
            <img src="../uploaded_img/<?= htmlspecialchars($fetch_posts['image']); ?>" class="image" alt="">
         <?php } ?>
         <div class="status" style="background-color:<?php if($fetch_posts['status'] == 'published'){echo 'limegreen'; }else{echo 'coral';}; ?>;"><?= htmlspecialchars($fetch_posts['status']); ?></div>
         <div class="title"><?= htmlspecialchars($fetch_posts['title']); ?></div>
         <div class="posts-content"><?= substr(htmlspecialchars($fetch_posts['content']), 0, 150) . (strlen($fetch_posts['content']) > 150 ? '...' : ''); ?></div>
         <div class="icons">
            <div class="likes"><i class="fas fa-heart"></i><span><?= $total_post_likes; ?></span></div>
            <div class="comments"><i class="fas fa-comment"></i><span><?= $total_post_comments; ?></span></div>
         </div>
         <div class="flex-btn">
            <a href="sa_edit_post.php?post_id=<?= $post_id; ?>" class="option-btn">Edit</a>
            <button type="submit" name="delete" class="delete-btn" onclick="return confirm('WARNING: You are about to delete this post permanently.\n\n• All comments associated with this post will be deleted\n• All likes will be removed\n• Post images will be deleted from the server\n\nThis action CANNOT be undone. Are you sure you want to proceed?');">Delete</button>
         </div>
      </form>
      <?php
            }
         } else {
            echo '<p class="empty">No draft posts added yet! <a href="sa_add_posts.php" class="btn" style="margin-top:1.5rem;">Add Post</a></p>';
         }
      ?>
   </div>
</section>

<!-- custom js file link  -->
<script src="../js/admin_script.js"></script>

</body>
</html>