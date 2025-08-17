<?php
require_once __DIR__ . '/../components/connect.php';

$db = new Database();
$conn = $db->connect();

session_start();

$admin_id = $_SESSION['admin_id'] ?? null;
$admin_role = $_SESSION['admin_role'] ?? null;

// Only allow superadmin access to this page
if(!isset($admin_id) || $admin_role !== 'superadmin'){
   $_SESSION['message'] = 'Please login as a superadmin to access this content.';
   header('location:../admin/admin_login.php');
   exit();
}

// Initialize message array
$message = [];

// Display session message if it exists
if(isset($_SESSION['message'])) {
   $message[] = $_SESSION['message'];
   unset($_SESSION['message']);
}

// Publish article
if(isset($_POST['publish_article'])){
   $article_id = filter_var($_POST['article_id'], FILTER_SANITIZE_NUMBER_INT);
   
   $update_status = $conn->prepare("UPDATE articles SET status = 'published' WHERE article_id = ? AND created_by = ?");
   $update_status->execute([$article_id, $admin_id]);
   
   if($update_status->rowCount() > 0){
      $_SESSION['message'] = 'Article published successfully!';
      header('Location: ' . $_SERVER['PHP_SELF']);
      exit();
   } else {
      $message[] = 'Failed to publish article!';
   }
}

// Delete article
if(isset($_POST['delete_article'])){
   $article_id = filter_var($_POST['article_id'], FILTER_SANITIZE_NUMBER_INT);
   
   try {
      // Begin transaction
      $conn->beginTransaction();
      
      // Get article image for deletion
      $select_image = $conn->prepare("SELECT image FROM articles WHERE article_id = ? AND created_by = ?");
      $select_image->execute([$article_id, $admin_id]);
      $image_data = $select_image->fetch(PDO::FETCH_ASSOC);
      
      // Delete the article
      $delete_article = $conn->prepare("DELETE FROM articles WHERE article_id = ? AND created_by = ?");
      $delete_article->execute([$article_id, $admin_id]);
      
      if($delete_article->rowCount() > 0) {
         // Delete the image file if it exists
         if($image_data && !empty($image_data['image'])){
            $image_path = '../uploaded_img/'.$image_data['image'];
            if(file_exists($image_path)){
               unlink($image_path);
            }
         }
         
         $conn->commit();
         $_SESSION['message'] = 'Article deleted successfully!';
      } else {
         $conn->rollBack();
         $_SESSION['message'] = 'Failed to delete article or you do not have permission!';
      }
      
      header('Location: ' . $_SERVER['PHP_SELF']);
      exit();
      
   } catch (Exception $e) {
      $conn->rollBack();
      $_SESSION['message'] = 'Error: ' . $e->getMessage();
      header('Location: ' . $_SERVER['PHP_SELF']);
      exit();
   }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Draft Articles - Superadmin</title>
   
   <!-- Font Awesome CDN Link -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   
   <!-- Admin CSS File Link -->
   <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<?php include '../components/superadmin_header.php' ?>

<!-- Draft Articles Section -->
<section class="tejido-posts">
   <h1 class="heading">Your Draft Articles</h1>
   
   <?php if(!empty($message)): ?>
      <?php foreach($message as $msg): ?>
         <div class="message">
            <span><?= htmlspecialchars($msg); ?></span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
         </div>
      <?php endforeach; ?>
   <?php endif; ?>
   
   <div class="box-container">
      <?php
      // Query for DRAFT articles only
      $select_articles = $conn->prepare("SELECT a.*, c.name as category_name 
                                       FROM articles a 
                                       JOIN category c ON a.category_id = c.category_id
                                       WHERE a.created_by = ? AND a.status = 'draft'
                                       ORDER BY a.created_at DESC");
      $select_articles->execute([$admin_id]);
      
      if($select_articles->rowCount() > 0){
         while($article = $select_articles->fetch(PDO::FETCH_ASSOC)){
            $article_id = $article['article_id'];
      ?>
      <div class="box">
         <div class="status" style="background-color:coral;">Draft</div>
         <h3><?= htmlspecialchars($article['title']); ?></h3>
         
         <?php if(!empty($article['image'])): ?>
            <img src="../uploaded_img/<?= htmlspecialchars($article['image']); ?>" alt="<?= htmlspecialchars($article['title']); ?>">
         <?php endif; ?>
         
         <p>Category: <span><?= htmlspecialchars($article['category_name']); ?></span></p>
         <p>Added: <?= date('F j, Y, g:i a', strtotime($article['created_at'])); ?></p>
         
         <div class="flex-btn">
            <a href="sa_edit_article.php?id=<?= $article_id; ?>" class="btn">Edit</a>
            
            <form action="" method="post" style="display:inline;">
               <input type="hidden" name="article_id" value="<?= $article_id; ?>">
               <button type="submit" name="publish_article" class="option-btn">Publish</button>
            </form>
            
            <form action="" method="post" style="display:inline;" onsubmit="return confirm('WARNING: You are about to delete this article permanently.\n\nThis action CANNOT be undone. Are you sure you want to proceed?');">
               <input type="hidden" name="article_id" value="<?= $article_id; ?>">
               <button type="submit" name="delete_article" class="delete-btn">Delete</button>
            </form>
         </div>
      </div>
      <?php 
         }
      } else {
         echo '<p class="empty">No draft articles found! <a href="sa_add_articles.php" class="btn" style="margin-top:1.5rem;">Add New Article</a></p>';
      }
      ?>
   </div>
</section>

<!-- Admin JS File Link -->
<script src="../js/admin_script.js"></script>

</body>
</html>