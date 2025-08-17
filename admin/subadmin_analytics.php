<?php
// Add these lines at the very top, before any HTML output
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

include '../components/connect.php';

$db = new Database();
$conn = $db->connect();

session_start();

// Check if the admin is logged in
if(!isset($_SESSION['admin_id'])){
   header('location:../user_content/login_users.php');
   exit();
}

// Check if the user is a subadmin (add role check)
if(!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'subadmin'){
   // Redirect to appropriate page for unauthorized access
   header('location:../access_denied.php');
   exit();
}

$admin_id = $_SESSION['admin_id'];

// Fetch profile data if needed
$select_profile = $conn->prepare("SELECT firstname, lastname FROM `accounts` WHERE account_id = ?");
$select_profile->execute([$admin_id]);
$fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);

if (!$fetch_profile) {
    echo '<p class="error">Profile not found. Please contact support.</p>';
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>dashboard</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="../css/admin_style.css">

</head>
<body>

<?php include '../components/admin_header.php' ?>

<!-- admin dashboard section starts  -->

<section class="dashboard">

   <h1 class="heading">dashboard</h1>

   <div class="box-container">

   <div class="box">
      <h3>welcome!</h3>
      <p><?= $fetch_profile['firstname'] . ' ' . $fetch_profile['lastname']; ?></p>
      <a href="update_profile.php" class="btn">update profile</a>
   </div>

   <div class="box">
      <?php
         $select_posts = $conn->prepare("SELECT * FROM `posts` WHERE created_by = ? AND status = 'published'");
         $select_posts->execute([$admin_id]);
         $numbers_of_posts = $select_posts->rowCount();
      ?>
      <h3><?= $numbers_of_posts; ?></h3>
      <p>published posts</p>
      <a href="../admin_content/add_posts.php" class="btn">add new post</a>
   </div>

   <div class="box">
      <?php
      $select_tejido = $conn->prepare("SELECT * FROM `tejido` WHERE created_by = ? AND status = 'published'");
      $select_tejido->execute([$admin_id]);
      $numbers_of_tejido = $select_tejido->rowCount();
      ?>
      <h3><?= $numbers_of_tejido; ?></h3>
      <p>Published Tejido</p>
      <a href="../admin_content/add_tejido.php" class="btn">Add tejido</a>
   </div>

   <div class="box">
      <?php
         $select_deactive_posts = $conn->prepare("SELECT * FROM `posts` WHERE created_by = ? AND status = 'draft'");
         $select_deactive_posts->execute([$admin_id]);
         $numbers_of_deactive_posts = $select_deactive_posts->rowCount();
      ?>
      <h3><?= $numbers_of_deactive_posts; ?></h3>
      <p>All Draft Posts</p>
      <a href="../admin_content/view_posts.php" class="btn">See Posts Drafts</a>
   </div>

   <div class="box">
      <?php
         // Count draft tejidos
         $select_draft_tejido = $conn->prepare("SELECT * FROM `tejido` WHERE created_by = ? AND status = 'draft'");
         $select_draft_tejido->execute([$admin_id]);
         $numbers_of_draft_tejido = $select_draft_tejido->rowCount();
      ?>
      <h3><?= $numbers_of_draft_tejido; ?></h3>
      <p>draft tejidos</p>
      <a href="../admin_content/view_tejido.php" class="btn">View draft tejidos</a>
   </div>

   <div class="box">
      <?php
         $select_users = $conn->prepare("SELECT * FROM `accounts` WHERE role = 'user'");
         $select_users->execute();
         $numbers_of_users = $select_users->rowCount();
      ?>
      <h3><?= $numbers_of_users; ?></h3>
      <p>Users Account</p>
      <a href="./user_accounts_management.php" class="btn">See Users</a>
   </div>

   <div class="box">
      <?php
         // Get the article category ID
         $article_category_query = $conn->prepare("SELECT category_id FROM category WHERE name = 'Articles'");
         $article_category_query->execute();
         $article_category = $article_category_query->fetch(PDO::FETCH_ASSOC);
         $article_category_id = $article_category['category_id'] ?? 0;
         
         // Count published articles
         $select_articles = $conn->prepare("SELECT * FROM `articles` WHERE created_by = ? AND status = 'published'");
         $select_articles->execute([$admin_id]);
         $numbers_of_articles = $select_articles->rowCount();
      ?>
      <h3><?= $numbers_of_articles; ?></h3>
      <p>Published Articles</p>
      <a href="../admin_content/add_articles.php" class="btn">Add Articles</a>
   </div>

   <div class="box">
      <?php
         // Count draft articles
         $select_draft_articles = $conn->prepare("SELECT * FROM `articles` WHERE created_by = ? AND status = 'draft'");
         $select_draft_articles->execute([$admin_id]);
         $numbers_of_draft_articles = $select_draft_articles->rowCount();
      ?>
      <h3><?= $numbers_of_draft_articles; ?></h3>
      <p>Draft Articles</p>
      <a href="../admin_content/view_articles.php" class="btn">View Draft Articles</a>
   </div>
   
   <div class="box">
      <?php
         // Count comments on posts created by this admin
         $select_comments = $conn->prepare("
             SELECT COUNT(*) as total_comments 
             FROM `comments` c
             JOIN `posts` p ON c.post_id = p.post_id
             WHERE p.created_by = ?
         ");
         $select_comments->execute([$admin_id]);
         $result = $select_comments->fetch(PDO::FETCH_ASSOC);
         $numbers_of_comments = $result['total_comments'];
      ?>
      <h3><?= $numbers_of_comments; ?></h3>
      <p>Comments on your Posts</p>
      <a href="../admin_content/comments.php" class="btn">See Comments</a>
   </div>

   <div class="box">
      <?php
         // Count likes on posts created by this admin
         $select_likes = $conn->prepare("
             SELECT COUNT(*) as total_likes 
             FROM `likes` l
             JOIN `posts` p ON l.post_id = p.post_id
             WHERE p.created_by = ?
         ");
         $select_likes->execute([$admin_id]);
         $result = $select_likes->fetch(PDO::FETCH_ASSOC);
         $numbers_of_likes = $result['total_likes'];
      ?>
      <h3><?= $numbers_of_likes; ?></h3>
      <p>Likes on your Posts</p>
      <a href="../admin_content/total_likes.php" class="btn">See Total Likes</a>
   </div>

   <div class="box">
      <?php
         $select_categories = $conn->prepare("SELECT * FROM `category`");
         $select_categories->execute();
         $numbers_of_categories = $select_categories->rowCount();
      ?> 
      <h3><?= $numbers_of_categories; ?></h3>
      <p>Categories</p>
      <a href="../admin_content/edit_categories.php" class="btn">Edit Categories</a>
   </div>
   </div>

</section>
<script src="../js/admin_script.js"></script>

</body>
</html>