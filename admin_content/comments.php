<?php
include '../components/connect.php';

$db = new Database();
$conn = $db->connect();

session_start();

$admin_id = $_SESSION['admin_id'];

if (!isset($admin_id)) {
   header('location:admin_login.php');
   exit();
}

if (isset($_POST['delete_comment'])) {
   $comment_id = $_POST['comment_id'];
   $comment_id = filter_var($comment_id, FILTER_SANITIZE_STRING);
   $delete_comment = $conn->prepare("DELETE FROM `comments` WHERE comment_id = ?");
   $delete_comment->execute([$comment_id]);
   $message[] = 'Comment deleted!';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Users Accounts</title>

   <!-- Font Awesome CDN -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- Custom CSS File -->
   <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<section class="comments">
   <h1 class="heading">Posts Comments</h1>
   
   <p class="comment-title">Post Comments</p>
   <div class="box-container">
      <?php
         // Fetch comments on posts created by the admin
         $select_comments = $conn->prepare("
            SELECT comments.*, posts.title 
            FROM `comments` 
            JOIN `posts` ON comments.post_id = posts.post_id 
            WHERE posts.created_by = ?
         ");
         $select_comments->execute([$admin_id]);

         if ($select_comments->rowCount() > 0) {
            while ($fetch_comments = $select_comments->fetch(PDO::FETCH_ASSOC)) {
      ?>
               <div class="post-title">From: <span><?= $fetch_comments['title']; ?></span> 
                  <a href="read_post.php?post_id=<?= $fetch_comments['post_id']; ?>">View Post</a>
               </div>
               <div class="box">
                  <div class="user">
                     <i class="fas fa-user"></i>
                     <div class="user-info">
                        <span><?= $fetch_comments['commented_by']; ?></span>
                        <div><?= $fetch_comments['commented_at']; ?></div>
                     </div>
                  </div>
                  <div class="text"><?= $fetch_comments['comment']; ?></div>
                  <form action="" method="POST">
                     <input type="hidden" name="comment_id" value="<?= $fetch_comments['comment_id']; ?>">
                     <button type="submit" class="inline-delete-btn" name="delete_comment" onclick="return confirm('Delete this comment?');">Delete Comment</button>
                  </form>
               </div>
      <?php
            }
         } else {
            echo '<p class="empty">No comments added yet!</p>';
         }
      ?>
   </div>
</section>

<!-- Custom JS File -->
<script src="../js/admin_script.js"></script>
</body>
</html>