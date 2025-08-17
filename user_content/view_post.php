<?php
include '../components/connect.php';

$db = new Database();
$conn = $db->connect();

session_start();

if(isset($_SESSION['account_id'])){
   $user_id = $_SESSION['account_id'];
}else{
   $user_id = '';
};

include '../components/like_post.php';

// Get the post_id from the URL
$get_id = isset($_GET['post_id']) ? $_GET['post_id'] : (isset($_GET['id']) ? $_GET['id'] : '');

if(empty($get_id)){
   header('location:home.php');
   exit();
}

// Display messages if any were set from non-AJAX operations
if(isset($_SESSION['message'])){
   $message[] = $_SESSION['message'];
   unset($_SESSION['message']);
}

// Check if user is logged in before allowing comments
if(isset($_POST['add_comment'])){
   if(empty($user_id)){
      $message[] = 'Please login to add a comment';
   } else {
      $comment = $_POST['comment'];
      $comment = filter_var($comment, FILTER_SANITIZE_STRING);
      
      // Get user's IP address
      $ip_address = $_SERVER['REMOTE_ADDR'];
      
      // First verify the user exists in the accounts table
      $verify_user = $conn->prepare("SELECT * FROM `accounts` WHERE account_id = ?");
      $verify_user->execute([$user_id]);
      
      if($verify_user->rowCount() == 0){
         $message[] = 'User account not found. Please log out and log in again.';
      } else {
         // Check if the user has reached the comment limit for this post
         $count_user_comments = $conn->prepare("SELECT COUNT(*) as comment_count FROM `comments` WHERE post_id = ? AND commented_by = ?");
         $count_user_comments->execute([$get_id, $user_id]);
         $user_comment_count = $count_user_comments->fetch(PDO::FETCH_ASSOC)['comment_count'];
         
         // Check if the IP address has reached the comment limit for this post
         $count_ip_comments = $conn->prepare("SELECT COUNT(*) as ip_comment_count FROM `comments` WHERE post_id = ? AND ip_address = ?");
         $count_ip_comments->execute([$get_id, $ip_address]);
         $ip_comment_count = $count_ip_comments->fetch(PDO::FETCH_ASSOC)['ip_comment_count'];
         
         if($user_comment_count >= 5){
            $message[] = 'You have reached the maximum limit of 5 comments per post.';
         } 
         else if($ip_comment_count >= 5){
            $message[] = 'The maximum comment limit has been reached from your network.';
         }
         else {
            // Check if the same comment was already added
            $verify_comment = $conn->prepare("SELECT * FROM `comments` WHERE post_id = ? AND commented_by = ? AND comment = ?");
            $verify_comment->execute([$get_id, $user_id, $comment]);
      
            if($verify_comment->rowCount() > 0){
               $message[] = 'Comment already added!';
            } else {
               // Now insert the comment with IP address
               $insert_comment = $conn->prepare("INSERT INTO `comments`(post_id, commented_by, ip_address, comment) VALUES(?,?,?,?)");
               $insert_comment->execute([$get_id, $user_id, $ip_address, $comment]);
               $message[] = 'New comment added!';
            }
         }
      }
   }
}


if(isset($_POST['edit_comment'])){
   $edit_comment_id = $_POST['edit_comment_id'];
   $edit_comment_id = filter_var($edit_comment_id, FILTER_SANITIZE_STRING);
   $comment_edit_box = $_POST['comment_edit_box'];
   $comment_edit_box = filter_var($comment_edit_box, FILTER_SANITIZE_STRING);

   // Verify the comment exists and belongs to this user
   $verify_comment = $conn->prepare("SELECT * FROM `comments` WHERE comment_id = ? AND commented_by = ?");
   $verify_comment->execute([$edit_comment_id, $user_id]);

   if($verify_comment->rowCount() == 0){
      $message[] = 'Comment not found or you do not have permission to edit!';
   } else {
      $update_comment = $conn->prepare("UPDATE `comments` SET comment = ? WHERE comment_id = ?");
      $update_comment->execute([$comment_edit_box, $edit_comment_id]);
      $message[] = 'Your comment edited successfully!';
   }
}

if(isset($_POST['delete_comment'])){
   $delete_comment_id = $_POST['comment_id'];
   $delete_comment_id = filter_var($delete_comment_id, FILTER_SANITIZE_STRING);
   
   // Verify the comment exists and belongs to this user
   $verify_delete = $conn->prepare("SELECT * FROM `comments` WHERE comment_id = ? AND commented_by = ?");
   $verify_delete->execute([$delete_comment_id, $user_id]);
   
   if($verify_delete->rowCount() > 0){
      $delete_comment = $conn->prepare("DELETE FROM `comments` WHERE comment_id = ?");
      $delete_comment->execute([$delete_comment_id]);
      $message[] = 'Comment deleted successfully!';
   } else {
      $message[] = 'Comment not found or you do not have permission to delete!';
   }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>View Post</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="../css/style.css">

</head>
<body>
   
<!-- header section starts  -->
<?php include '../components/user_header.php'; ?>
<!-- header section ends -->

<!-- Display messages if any -->
<?php
if(isset($message)){
   foreach($message as $msg){
      echo '<div class="message"><span>'.$msg.'</span><i class="fas fa-times" onclick="this.parentElement.remove();"></i></div>';
   }
}
?>

<?php
   if(isset($_POST['open_edit_box']) && !empty($user_id)){
   $comment_id = $_POST['comment_id'];
   $comment_id = filter_var($comment_id, FILTER_SANITIZE_STRING);
   
   // Verify the comment belongs to this user
   $verify_edit = $conn->prepare("SELECT * FROM `comments` WHERE comment_id = ? AND commented_by = ?");
   $verify_edit->execute([$comment_id, $user_id]);
   
   if($verify_edit->rowCount() > 0){
      $fetch_edit_comment = $verify_edit->fetch(PDO::FETCH_ASSOC);
?>
   <section class="comment-edit-form">
   <p>Edit Your Comment</p>
   <form action="" method="POST">
      <input type="hidden" name="edit_comment_id" value="<?= $comment_id; ?>">
      <textarea name="comment_edit_box" required cols="30" rows="10" placeholder="Please enter your comment"><?= $fetch_edit_comment['comment']; ?></textarea>
      <button type="submit" class="inline-btn" name="edit_comment">Edit Comment</button>
      <div class="inline-option-btn" onclick="window.location.href = 'view_post.php?post_id=<?= $get_id; ?>';">Cancel Edit</div>
   </form>
   </section>
<?php
   }
}
?>

<section class="posts-container" style="padding-bottom: 0;">

   <div class="box-container">

      <?php
         // Fetch the post details based on the post_id
         $select_posts = $conn->prepare("
            SELECT posts.*, accounts.firstname, accounts.lastname, accounts.user_name, accounts.image as profile_img
            FROM `posts` 
            JOIN `accounts` ON posts.created_by = accounts.account_id 
            WHERE posts.post_id = ? AND posts.status = 'published'
         ");
         $select_posts->execute([$get_id]);

         if($select_posts->rowCount() > 0){
            $fetch_posts = $select_posts->fetch(PDO::FETCH_ASSOC);
            $post_id = $fetch_posts['post_id'];

            // Count comments for the post
            $count_post_comments = $conn->prepare("SELECT * FROM `comments` WHERE post_id = ?");
            $count_post_comments->execute([$post_id]);
            $total_post_comments = $count_post_comments->rowCount(); 

            // Count likes for the post
            $count_post_likes = $conn->prepare("SELECT * FROM `likes` WHERE post_id = ?");
            $count_post_likes->execute([$post_id]);
            $total_post_likes = $count_post_likes->rowCount();

            // Check if the logged-in user has liked the post
            $confirm_likes = $conn->prepare("SELECT * FROM `likes` WHERE account_id = ? AND post_id = ?");
            $user_liked = false;
            if (!empty($user_id)) {
               $confirm_likes->execute([$user_id, $post_id]);
               $user_liked = ($confirm_likes->rowCount() > 0);
            }
      ?>
      <div class="box">
         <input type="hidden" id="post-id-<?= $get_id; ?>" value="<?= $get_id; ?>">
         <input type="hidden" name="post_id" value="<?= $get_id; ?>">
         <input type="hidden" name="admin_id" value="<?= $fetch_posts['created_by']; ?>">
         <div class="post-admin">
            <?php if(!empty($fetch_posts['profile_img'])): ?>
               <div class="profile-image">
                  <img src="../uploads/profiles/<?= $fetch_posts['profile_img']; ?>" alt="<?= $fetch_posts['user_name']; ?>">
               </div>
            <?php else: ?>
               <div class="profile-image no-image">
                  <i class="fas fa-user"></i>
               </div>
            <?php endif; ?>
            <div>
               <a href="author_posts.php?author=<?= $fetch_posts['created_by']; ?>">
                  <?= $fetch_posts['firstname'] . ' ' . $fetch_posts['lastname']; ?>
                  (<?= $fetch_posts['user_name']; ?>)
               </a>
               <div><?= date('M d, Y \a\t h:i A', strtotime($fetch_posts['created_at'])); ?></div>
            </div>
         </div>
         
         <?php if($fetch_posts['image'] != ''){ ?>
            <img src="../uploaded_img/<?= $fetch_posts['image']; ?>" class="post-image" alt="">
         <?php } ?>

         <div class="post-title"><?= $fetch_posts['title']; ?></div>
         <div class="post-content"><?= $fetch_posts['content']; ?></div>
         <div class="icons">
            <div><i class="fas fa-comment"></i><span>(<?= $total_post_comments; ?>)</span></div>
            <?php if (!empty($user_id)) { ?>
               <div class="like-btn" style="cursor:pointer;" data-post-id="<?= $get_id; ?>">
                  <i class="fas fa-heart" style="<?= $user_liked ? 'color:var(--red);' : ''; ?>"></i>
                  <span id="likes-count-<?= $get_id; ?>">(<?= $total_post_likes; ?>)</span>
               </div>
            <?php } else { ?>
               <a href="login_users.php" class="like-btn">
                  <i class="fas fa-heart"></i><span>(<?= $total_post_likes; ?>)</span>
               </a>
            <?php } ?>
         </div>
      </div>
      <?php
         }else{
            echo '<p class="empty">No post found or post has been removed!</p>';
         }
      ?>
   </div>

</section>

<section class="comments-container">

   <p class="comment-title">Add Comment</p>
   <!-- This will be updated by JavaScript -->
   <div class="comment-limit-info">
      <p id="comment-limit-message">Loading comment limits...</p>
   </div>

   <?php
      if(!empty($user_id)){   
         // Fetch the current user's profile
         $select_profile = $conn->prepare("SELECT * FROM `accounts` WHERE account_id = ?");
         $select_profile->execute([$user_id]);
         $fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);
         
         if($select_profile->rowCount() > 0){
   ?>
   <!-- Make sure this form has the correct ID -->
   <form id="comment-form" class="add-comment" action="" method="POST">
      <textarea name="comment" class="comment-box" placeholder="Write your comment..." maxlength="1000" cols="30" rows="10" required></textarea>
      <input type="hidden" name="post_id" value="<?= $get_id; ?>">
      <button type="submit" class="inline-btn">Add Comment</button>
   </form>
   <?php
         }
      }else{
   ?>
   <div class="add-comment">
      <p>Please login to add or edit your comment</p>
      <a href="login_users.php" class="inline-btn">Login Now</a>
   </div>
   <?php
      }
   ?>
   
   <p class="comment-title">Post Comments</p>
   <div class="user-comments-container">
      <?php
         $select_comments = $conn->prepare("
            SELECT c.*, a.user_name, a.firstname, a.lastname 
            FROM `comments` c
            JOIN `accounts` a ON c.commented_by = a.account_id
            WHERE c.post_id = ?
            ORDER BY c.commented_at DESC
         ");
         $select_comments->execute([$get_id]);
         
         if($select_comments->rowCount() > 0){
            while($fetch_comments = $select_comments->fetch(PDO::FETCH_ASSOC)){
               $is_own_comment = ($fetch_comments['commented_by'] == $user_id);
      ?>
      <div class="show-comments" style="<?= $is_own_comment ? 'order:-1;' : ''; ?>">
         <div class="comment-user">
            <i class="fas fa-user"></i>
            <div>
               <span><?= htmlspecialchars($fetch_comments['user_name']); ?></span>
               <div><?= date('M d, Y \a\t h:i A', strtotime($fetch_comments['commented_at'])); ?></div>
            </div>
         </div>
         <div class="comment-box" style="<?= $is_own_comment ? 'color:var(--white); background:var(--black);' : ''; ?>">
            <?= htmlspecialchars($fetch_comments['comment']); ?>
         </div>
         <?php if($is_own_comment){ ?>
         <form action="javascript:void(0);" class="comment-actions">
            <input type="hidden" name="comment_id" value="<?= $fetch_comments['comment_id']; ?>">
            <button type="button" class="inline-option-btn" name="open_edit_box">Edit Comment</button>
            <button type="button" class="inline-delete-btn" name="delete_comment">Delete Comment</button>
         </form>
         <?php } ?>
      </div>
      <?php
            }
         }else{
            echo '<p class="empty">No comments added yet!</p>';
         }
      ?>
   </div>
</section>

<!-- footer section starts  -->
<?php include '../components/footer.php'; ?>
<!-- footer section ends -->

<!-- custom js file link  -->
<script src="../js/script.js"></script>
<script src="../js/likes.js"></script>
<script src="../js/comments.js"></script>
<!-- Removed all inline script -->

</body>
</html>