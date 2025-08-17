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

// Initialize message array
$message = [];

// Display session message if it exists
if(isset($_SESSION['message'])) {
   $message[] = $_SESSION['message'];
   unset($_SESSION['message']);
}

// Fetch all categories for the dropdown
$select_categories = $conn->prepare("SELECT * FROM category ORDER BY name ASC");
$select_categories->execute();
$categories = $select_categories->fetchAll(PDO::FETCH_ASSOC);

// Add article or save as draft
if(isset($_POST['add_article']) || isset($_POST['save_draft'])){
   $title = trim($_POST['title']);
   $content = trim($_POST['content']);
   $category_id = filter_var($_POST['category_id'], FILTER_SANITIZE_NUMBER_INT);
   $status = isset($_POST['save_draft']) ? 'draft' : 'published';
   
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
   $image = '';
   if(isset($_FILES['image']) && $_FILES['image']['size'] > 0){
      $image_name = $_FILES['image']['name'];
      $image_size = $_FILES['image']['size'];
      $image_tmp_name = $_FILES['image']['tmp_name'];
      $image_extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
      
      // Generate unique filename to prevent overwriting
      $new_image_name = uniqid('article_') . '.' . $image_extension;
      $image_folder = '../uploaded_img/'.$new_image_name;
      
      $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
      
      if(!in_array($image_extension, $allowed_exts)){
         $message[] = 'Invalid image format! Allowed formats: JPG, JPEG, PNG, GIF';
      }
      
      if($image_size > 2000000){
         $message[] = 'Image size is too large! Max: 2MB';
      }
      
      if(empty($message)){
         move_uploaded_file($image_tmp_name, $image_folder);
         $image = $new_image_name;
      }
   }
   
   if(empty($message)){
      // Insert article with status
      $insert_article = $conn->prepare("INSERT INTO articles (title, content, image, category_id, created_by, status) 
                                     VALUES (?, ?, ?, ?, ?, ?)");
      $insert_article->execute([$title, $content, $image, $category_id, $admin_id, $status]);
      
      if($insert_article->rowCount() > 0){
         if($status == 'draft'){
            $_SESSION['message'] = 'Article saved as draft!';
            header('Location: view_articles.php');
            exit();
         } else {
            $_SESSION['message'] = 'Article published successfully!';
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit();
         }
      } else {
         $message[] = 'Failed to add article. Please try again.';
      }
   }
}

// Handle article deletion
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
   <title>Articles Management</title>
   
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

<!-- Add Article Section -->
<section class="add-tejido">
   <h1 class="heading">Article Management</h1>
   
   <?php if(!empty($message)): ?>
      <?php foreach($message as $msg): ?>
         <div class="message">
            <span><?= htmlspecialchars($msg); ?></span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
         </div>
      <?php endforeach; ?>
   <?php endif; ?>
   
   <form action="" method="post" enctype="multipart/form-data">
      <div class="input-box">
         <label for="title">Article Title:</label>
         <input type="text" name="title" id="title" class="box" placeholder="Enter article title" required>
      </div>
      
      <div class="input-box">
         <label for="content">Article Content:</label>
         <textarea name="content" id="content" class="box" placeholder="Enter article content" required cols="30" rows="10"></textarea>
      </div>
      
      <div class="input-box">
         <label for="category_id">Article Category:</label>
         <select name="category_id" id="category_id" class="box" required>
            <option value="" disabled selected>Select a category</option>
            <?php foreach($categories as $category): ?>
               <option value="<?= $category['category_id']; ?>"><?= htmlspecialchars($category['name']); ?></option>
            <?php endforeach; ?>
         </select>
      </div>
      
      <div class="input-box">
         <label for="image">Article Image:</label>
         <input type="file" name="image" id="image" class="box" accept="image/jpg, image/jpeg, image/png, image/gif">
      </div>
      
      <div class="flex-btn">
         <button type="submit" name="add_article" class="btn">Publish Article</button>
         <button type="submit" name="save_draft" class="option-btn">Save as Draft</button>
      </div>
   </form>
</section>

<!-- View Articles Section -->
<section class="tejido-posts">
   <h1 class="heading">Your Published Articles</h1>
   
   <div class="box-container">
      <?php
      // Simple query for PUBLISHED articles only
      $select_articles = $conn->prepare("SELECT a.*, c.name as category_name 
                                       FROM articles a 
                                       JOIN category c ON a.category_id = c.category_id
                                       WHERE a.created_by = ? AND a.status = 'published'
                                       ORDER BY a.created_at DESC");
      $select_articles->execute([$admin_id]);
      
      if($select_articles->rowCount() > 0){
         while($article = $select_articles->fetch(PDO::FETCH_ASSOC)){
            $article_id = $article['article_id'];
      ?>
      <div class="box">
         <h3><?= htmlspecialchars($article['title']); ?></h3>
         
         <?php if(!empty($article['image'])): ?>
            <img src="../uploaded_img/<?= htmlspecialchars($article['image']); ?>" alt="<?= htmlspecialchars($article['title']); ?>">
         <?php endif; ?>
         
         <p>Category: <span><?= htmlspecialchars($article['category_name']); ?></span></p>
         <p>Added: <?= date('F j, Y, g:i a', strtotime($article['created_at'])); ?></p>
         
         <div class="flex-btn">
            <a href="edit_article.php?article_id=<?= $article_id; ?>" class="btn">Edit</a>
            
            <form action="" method="post" style="display:inline;" onsubmit="return confirm('WARNING: You are about to delete this article permanently.\n\nThis action CANNOT be undone. Are you sure you want to proceed?');">
               <input type="hidden" name="article_id" value="<?= $article_id; ?>">
               <button type="submit" name="delete_article" class="delete-btn">Delete</button>
            </form>
         </div>
      </div>
      <?php 
         }
      } else {
         echo '<p class="empty">No articles found! Click the "Add Article" button above to create your first article.</p>';
      }
      ?>
   </div>
</section>

<!-- Admin JS File Link -->
<script src="../js/admin_script.js"></script>

</body>
</html>