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

// Check if article ID exists in URL
if(!isset($_GET['id'])){
   $_SESSION['message'] = 'Article ID is missing!';
   header('location:sa_add_articles.php');
   exit();
}

$article_id = $_GET['id'];
$article_id = filter_var($article_id, FILTER_SANITIZE_NUMBER_INT);

// Fetch the article to edit
$select_article = $conn->prepare("SELECT a.*, c.name as category_name 
                                FROM articles a 
                                LEFT JOIN category c ON a.category_id = c.category_id
                                WHERE a.article_id = ? AND a.created_by = ?");
$select_article->execute([$article_id, $admin_id]);

// If article doesn't exist or doesn't belong to this admin
if($select_article->rowCount() <= 0){
   $_SESSION['message'] = 'Article not found or you do not have permission to edit it!';
   header('location:sa_add_articles.php');
   exit();
}

$article = $select_article->fetch(PDO::FETCH_ASSOC);

// Handle form submission
if(isset($_POST['update_article'])){
   $title = trim($_POST['title']);
   $content = trim($_POST['content']);
   $category_id = filter_var($_POST['category_id'], FILTER_SANITIZE_NUMBER_INT);
   
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
   
   if(empty($message)){
      // Update article in the database
      $update_article = $conn->prepare("UPDATE articles SET title = ?, content = ?, category_id = ? WHERE article_id = ? AND created_by = ?");
      $update_article->execute([$title, $content, $category_id, $article_id, $admin_id]);
      
      $message[] = 'Article updated successfully!';
      
      // Handle image update
      $old_image = $article['image'] ?? '';
      
      if(isset($_FILES['image']) && $_FILES['image']['size'] > 0){
         $image_name = $_FILES['image']['name'];
         $image_size = $_FILES['image']['size'];
         $image_tmp_name = $_FILES['image']['tmp_name'];
         $image_extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
         
         // Generate unique filename
         $new_image_name = uniqid('article_') . '.' . $image_extension;
         $image_folder = '../uploaded_img/'.$new_image_name;
         
         $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];
         
         if(!in_array($image_extension, $allowed_exts)){
            $message[] = 'Invalid image format! Allowed formats: JPG, JPEG, PNG, GIF';
         }
         
         if($image_size > 2000000){
            $message[] = 'Image size is too large! Max: 2MB';
         }
         
         if(empty($message) || (count($message) == 1 && $message[0] == 'Article updated successfully!')){
            // Update the image
            move_uploaded_file($image_tmp_name, $image_folder);
            
            $update_image = $conn->prepare("UPDATE articles SET image = ? WHERE article_id = ? AND created_by = ?");
            $update_image->execute([$new_image_name, $article_id, $admin_id]);
            
            // Delete old image if it exists
            if(!empty($old_image) && file_exists('../uploaded_img/'.$old_image)){
               unlink('../uploaded_img/'.$old_image);
            }
            
            $message[] = 'Image updated successfully!';
         }
      }
      
      // Refresh article data
      $select_article->execute([$article_id, $admin_id]);
      $article = $select_article->fetch(PDO::FETCH_ASSOC);
   }
}

// Handle image deletion
if(isset($_POST['delete_image'])){
   $empty_image = '';
   
   // Delete the image file
   if(!empty($article['image']) && file_exists('../uploaded_img/'.$article['image'])){
      unlink('../uploaded_img/'.$article['image']);
   }
   
   // Update the database to remove image reference
   $update_image = $conn->prepare("UPDATE articles SET image = ? WHERE article_id = ? AND created_by = ?");
   $update_image->execute([$empty_image, $article_id, $admin_id]);
   
   $message[] = 'Image deleted successfully!';
   
   // Refresh article data
   $select_article->execute([$article_id, $admin_id]);
   $article = $select_article->fetch(PDO::FETCH_ASSOC);
}

// Fetch all categories for the dropdown
$select_categories = $conn->prepare("SELECT * FROM category ORDER BY name ASC");
$select_categories->execute();
$categories = $select_categories->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Edit Article - Superadmin</title>

   <!-- Font Awesome CDN Link -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- Admin CSS File Link -->
   <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<?php include '../components/superadmin_header.php' ?>

<!-- Edit Article Section -->
<section class="post-editor">
   <h1 class="heading">Edit Article</h1>
   
   <?php if(!empty($message)): ?>
      <?php foreach($message as $msg): ?>
         <div class="message">
            <span><?= htmlspecialchars($msg); ?></span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
         </div>
      <?php endforeach; ?>
   <?php endif; ?>
   
   <form action="" method="post" enctype="multipart/form-data">
      <p>Article Title <span>*</span></p>
      <input type="text" name="title" maxlength="100" required placeholder="Enter article title" class="box" value="<?= htmlspecialchars($article['title']); ?>">
      
      <p>Article Content <span>*</span></p>
      <textarea name="content" class="box" required maxlength="10000" placeholder="Enter article content..." cols="30" rows="10"><?= htmlspecialchars($article['content']); ?></textarea>
      
      <p>Article Category <span>*</span></p>
      <select name="category_id" class="box" required>
         <option value="" disabled>Select category</option>
         <?php foreach($categories as $category): ?>
            <?php $selected = ($article['category_id'] == $category['category_id']) ? 'selected' : ''; ?>
            <option value="<?= $category['category_id']; ?>" <?= $selected; ?>><?= htmlspecialchars($category['name']); ?></option>
         <?php endforeach; ?>
      </select>
      
      <p>Article Image</p>
      <input type="file" name="image" class="box" accept="image/jpg, image/jpeg, image/png, image/webp">
      
      <?php if(!empty($article['image'])): ?>
         <div class="image-preview">
            <img src="../uploaded_img/<?= htmlspecialchars($article['image']); ?>" class="image" alt="Article Image">
            <input type="submit" value="Delete Image" class="inline-delete-btn" name="delete_image" onclick="return confirm('Are you sure you want to delete this image?');">
         </div>
      <?php endif; ?>
      
      <div class="flex-btn">
         <input type="submit" value="Update Article" name="update_article" class="btn">
         <a href="sa_add_articles.php" class="option-btn">Go Back</a>
      </div>
   </form>
</section>

<!-- Admin JS File Link -->
<script src="../js/admin_script.js"></script>
</body>
</html>