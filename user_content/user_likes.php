<?php
include '../components/connect.php';

$db = new Database();
$conn = $db->connect();

session_start();

if(isset($_SESSION['account_id'])){
   $user_id = $_SESSION['account_id'];
}else{
   $user_id = '';
   header('location:home.php');
   exit();
};

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Liked Posts | The University Digest</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link -->
   <link rel="stylesheet" href="../css/style.css">

</head>
<body>
   
<!-- header section starts -->
<?php include '../components/user_header.php'; ?>
<!-- header section ends -->

<section class="posts-container">

   <h1 class="heading">liked posts</h1>

   <div class="box-container">

      <?php
         // Get posts liked by the user
         $select_likes = $conn->prepare("SELECT * FROM `likes` WHERE account_id = ?");
         $select_likes->execute([$user_id]);
         
         if($select_likes->rowCount() > 0){
            while($fetch_likes = $select_likes->fetch(PDO::FETCH_ASSOC)){
               // FIX: Use post_id instead of id
               $select_posts = $conn->prepare("
                  SELECT p.*, a.firstname, a.lastname, a.user_name, a.image as profile_img  
                  FROM `posts` p
                  JOIN `accounts` a ON p.created_by = a.account_id
                  WHERE p.post_id = ? AND p.status = 'published'
               ");
               $select_posts->execute([$fetch_likes['post_id']]);
               
               if($select_posts->rowCount() > 0){
                  $fetch_posts = $select_posts->fetch(PDO::FETCH_ASSOC);
                  
                  // FIX: Use post_id from posts table
                  $post_id = $fetch_posts['post_id'];

                  // Count post comments
                  $count_post_comments = $conn->prepare("SELECT * FROM `comments` WHERE post_id = ?");
                  $count_post_comments->execute([$post_id]);
                  $total_post_comments = $count_post_comments->rowCount();

                  // Count post likes
                  $count_post_likes = $conn->prepare("SELECT * FROM `likes` WHERE post_id = ?");
                  $count_post_likes->execute([$post_id]);
                  $total_post_likes = $count_post_likes->rowCount();
      ?>
      <form class="box" method="post">
         <input type="hidden" name="post_id" value="<?= $post_id; ?>">
         <!-- FIX: Use created_by instead of admin_id -->
         <input type="hidden" name="created_by" value="<?= $fetch_posts['created_by']; ?>">
         
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
               <!-- FIX: Use author account_id and proper name fields -->
               <a href="author_posts.php?author=<?= $fetch_posts['created_by']; ?>">
                  <?= $fetch_posts['firstname'] . ' ' . $fetch_posts['lastname']; ?>
               </a>
               <!-- FIX: Use created_at instead of date -->
               <div><?= date('M d, Y \a\t h:i A', strtotime($fetch_posts['created_at'])); ?></div>
            </div>
         </div>
         
         <?php if($fetch_posts['image'] != ''): ?>
            <img src="../uploaded_img/<?= $fetch_posts['image']; ?>" class="post-image" alt="">
         <?php endif; ?>
         
         <div class="post-title"><?= $fetch_posts['title']; ?></div>
         <div class="post-content content-150"><?= $fetch_posts['content']; ?></div>
         <a href="view_post.php?post_id=<?= $post_id; ?>" class="inline-btn">read more</a>
         <div class="icons">
            <a href="view_post.php?post_id=<?= $post_id; ?>">
               <i class="fas fa-comment"></i><span>(<?= $total_post_comments; ?>)</span>
            </a>
            <div class="like-btn" style="cursor:pointer;" data-post-id="<?= $post_id; ?>">
               <i class="fas fa-heart" style="color:var(--red);"></i>
               <span id="likes-count-<?= $post_id; ?>">(<?= $total_post_likes; ?>)</span>
            </div>
         </div>
      </form>
      <?php
               }
            }
         } else {
            echo '<p class="empty">no liked posts available!</p>';
         }
      ?>
   </div>

</section>
<?php include '../components/footer.php'; ?>

<!-- custom js file link  -->
<script src="../js/script.js"></script>
<script src="../js/likes.js"></script>

</body>
</html>