<?php
require_once __DIR__ . '/../components/connect.php';

$db = new Database();
$conn = $db->connect();

session_start();

$admin_id = $_SESSION['admin_id'] ?? null;
$admin_role = $_SESSION['admin_role'] ?? null;

// Allow access to both superadmin and subadmin
if(!isset($admin_id) || ($admin_role !== 'superadmin' && $admin_role !== 'subadmin')){
   $_SESSION['message'] = 'Please login to access this content.';
   header('location:../admin/admin_login.php');
   exit();
}

// Check if article_id exists in URL
if(!isset($_GET['article_id'])){
   $_SESSION['message'] = 'Article ID is missing!';
   header('location:view_articles.php');
   exit();
}

$article_id = filter_var($_GET['article_id'], FILTER_SANITIZE_NUMBER_INT);

// Fetch article data
$select_article = $conn->prepare("SELECT a.*, c.name as category_name 
                                FROM articles a 
                                LEFT JOIN category c ON a.category_id = c.category_id
                                WHERE a.article_id = ?");
$select_article->execute([$article_id]);
$article = $select_article->fetch(PDO::FETCH_ASSOC);

// Check if article exists
if(!$article){
   $_SESSION['message'] = 'Article not found!';
   header('location:view_articles.php');
   exit();
}

// For security, check if the user is either a superadmin or the creator of the article
if($admin_role !== 'superadmin' && $article['created_by'] != $admin_id){
   $_SESSION['message'] = 'You do not have permission to edit this article!';
   header('location:view_articles.php');
   exit();
}

// Fetch all categories for the dropdown
$select_categories = $conn->prepare("SELECT * FROM category ORDER BY name ASC");
$select_categories->execute();
$categories = $select_categories->fetchAll(PDO::FETCH_ASSOC);

// Initialize message array
$message = [];

// Display session message if it exists
if(isset($_SESSION['message'])) {
   $message[] = $_SESSION['message'];
   unset($_SESSION['message']);
}

// Handle article update
if(isset($_POST['update_article'])){
   $title = trim($_POST['title']);
   $content = trim($_POST['content']);
   $category_id = filter_var($_POST['category_id'], FILTER_SANITIZE_NUMBER_INT);
   $status = $_POST['status'];
   
   // Validate inputs
   if(empty($title)){
      $message[] = 'Article title is required!';
   }
   
   if(empty($content)){
      $message[] = 'Article content is required!';
   }
   
   if(empty($category_id)){
      $message[] = 'Please select a category!';
   }
   
   // Process image if uploaded
   $new_image = $article['image']; // Default to keeping the old image
   
   if(isset($_FILES['image']) && $_FILES['image']['size'] > 0){
      $image_name = $_FILES['image']['name'];
      $image_size = $_FILES['image']['size'];
      $image_tmp_name = $_FILES['image']['tmp_name'];
      $image_extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
      
      // Generate unique filename to prevent overwriting
      $new_image = uniqid('article_') . '.' . $image_extension;
      $image_folder = '../uploaded_img/'.$new_image;
      
      $allowed_exts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
      
      if(!in_array($image_extension, $allowed_exts)){
         $message[] = 'Invalid image format! Allowed formats: JPG, JPEG, PNG, GIF, WEBP';
      }
      
      if($image_size > 2000000){
         $message[] = 'Image size is too large! Max: 2MB';
      }
      
      if(empty($message)){
         move_uploaded_file($image_tmp_name, $image_folder);
         
         // Delete old image if exists
         if(!empty($article['image'])){
            $old_image_path = '../uploaded_img/' . $article['image'];
            if(file_exists($old_image_path)){
               unlink($old_image_path);
            }
         }
      }
   }
   
   if(empty($message)){
      try {
         // Begin transaction
         $conn->beginTransaction();
         
         // Update article - note we removed the created_by check since we already validated above
         $update_article = $conn->prepare("UPDATE articles SET title = ?, content = ?, image = ?, category_id = ?, status = ? WHERE article_id = ?");
         $update_article->execute([$title, $content, $new_image, $category_id, $status, $article_id]);
         
         // Debug the update operation
         if($update_article->rowCount() == 0){
            // Even if no rows were changed, we want to consider it a success
            // This happens when we submit the exact same data
            $conn->commit();
            $_SESSION['message'] = 'No changes were needed, article is up to date!';
         } else {
            $conn->commit();
            $_SESSION['message'] = 'Article updated successfully!';
         }
         
         // Redirect based on status
         if($status == 'draft'){
            header('Location: view_articles.php');
         } else {
            header('Location: add_articles.php');
         }
         exit();
      } catch (Exception $e) {
         $conn->rollBack();
         $message[] = 'Error updating article: ' . $e->getMessage();
      }
   }
}

// Handle article deletion
if(isset($_POST['delete_article'])){
   try {
      // Begin transaction
      $conn->beginTransaction();
      
      // Delete the article - removed the created_by check since we validated permission above
      $delete_article = $conn->prepare("DELETE FROM articles WHERE article_id = ?");
      $delete_article->execute([$article_id]);
      
      if($delete_article->rowCount() > 0) {
         // Delete the image file if it exists
         if(!empty($article['image'])){
            $image_path = '../uploaded_img/'.$article['image'];
            if(file_exists($image_path)){
               unlink($image_path);
            }
         }
         
         $conn->commit();
         $_SESSION['message'] = 'Article deleted successfully!';
         
         if($article['status'] == 'draft'){
            header('Location: view_articles.php');
         } else {
            header('Location: add_articles.php');
         }
         exit();
      } else {
         $conn->rollBack();
         $message[] = 'Failed to delete article!';
      }
      
   } catch (Exception $e) {
      $conn->rollBack();
      $message[] = 'Error: ' . $e->getMessage();
   }
}

// Handle image deletion
if(isset($_POST['delete_image'])){
   if(!empty($article['image'])){
      $image_path = '../uploaded_img/'.$article['image'];
      if(file_exists($image_path)){
         unlink($image_path);
      }
      
      // Removed the created_by check since we validated permission above
      $update_image = $conn->prepare("UPDATE articles SET image = '' WHERE article_id = ?");
      $update_image->execute([$article_id]);
      
      if($update_image->rowCount() > 0){
         $_SESSION['message'] = 'Image removed successfully!';
         header('Location: ' . $_SERVER['PHP_SELF'] . '?article_id=' . $article_id);
         exit();
      } else {
         $message[] = 'Failed to remove image!';
      }
   } else {
      $message[] = 'No image to delete!';
   }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Edit Article</title>
   
   <!-- Font Awesome CDN Link -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   
   <!-- Admin CSS File Link -->
   <link rel="stylesheet" href="../css/admin_style.css">
   
</head>
<body>

<?php 
// Include the appropriate header based on admin role
if($admin_role === 'superadmin') {
   include '../components/superadmin_header.php';
} else {
   include '../components/admin_header.php';
}
?>

<!-- Edit Article Section -->
<section class="add-tejido">
   <h1 class="heading">Edit Article</h1>
   
   <?php if(!empty($message)): ?>
      <?php foreach($message as $msg): ?>
         <div class="message">
            <span><?= htmlspecialchars($msg); ?></span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
         </div>
      <?php endforeach; ?>
   <?php endif; ?>
   
   <!-- Main form -->
   <form action="<?= $_SERVER['PHP_SELF'] . '?article_id=' . $article_id; ?>" method="post" enctype="multipart/form-data">
      <div class="input-box">
         <label for="title">Article Title:</label>
         <input type="text" name="title" id="title" class="box" placeholder="Enter article title" required value="<?= htmlspecialchars($article['title']); ?>">
      </div>
      
      <div class="input-box">
         <label for="content">Article Content:</label>
         <textarea name="content" id="content" class="box" placeholder="Enter article content" required cols="30" rows="10"><?= htmlspecialchars($article['content']); ?></textarea>
      </div>
      
      <div class="input-box">
         <label for="category_id">Article Category:</label>
         <select name="category_id" id="category_id" class="box" required>
            <?php foreach($categories as $category): ?>
               <option value="<?= $category['category_id']; ?>" <?= ($category['category_id'] == $article['category_id']) ? 'selected' : ''; ?>>
                  <?= htmlspecialchars($category['name']); ?>
               </option>
            <?php endforeach; ?>
         </select>
      </div>
      
      <div class="input-box">
         <label for="status">Article Status:</label>
         <select name="status" id="status" class="box" required>
            <option value="draft" <?= ($article['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
            <option value="published" <?= ($article['status'] == 'published') ? 'selected' : ''; ?>>Published</option>
         </select>
      </div>
      
      <div class="input-box">
         <label for="image">Article Image:</label>
         <input type="file" name="image" id="image" class="box" accept="image/jpg, image/jpeg, image/png, image/gif, image/webp">
      </div>
      
      <?php if(!empty($article['image'])): ?>
         <div class="current-image">
            <p>Current Image:</p>
            <img src="../uploaded_img/<?= htmlspecialchars($article['image']); ?>" alt="Current Article Image" style="max-width: 300px; margin: 10px 0;">
         </div>
         <!-- Image delete is now a separate form -->
         <form action="<?= $_SERVER['PHP_SELF'] . '?article_id=' . $article_id; ?>" method="post" style="margin-bottom: 20px;">
            <button type="submit" name="delete_image" class="delete-btn" onclick="return confirm('Remove this image?');">Remove Image</button>
         </form>
      <?php endif; ?>
      
      <div class="flex-btn">
         <button type="submit" name="update_article" class="btn">Update Article</button>
         <a href="<?= ($article['status'] == 'draft') ? 'view_articles.php' : 'add_articles.php'; ?>" class="option-btn">Go Back</a>
      </div>
   </form>
   
   <!-- Separate form for delete action to avoid conflicts -->
   <form action="<?= $_SERVER['PHP_SELF'] . '?article_id=' . $article_id; ?>" method="post" style="margin-top: 20px;">
      <button type="submit" name="delete_article" class="delete-btn" onclick="return confirm('WARNING: You are about to delete this article permanently.\n\nThis action CANNOT be undone. Are you sure you want to proceed?');">Delete Article</button>
   </form>
</section>

<!-- Admin JS File Link -->
<script src="../js/admin_script.js"></script>

</body>
</html>