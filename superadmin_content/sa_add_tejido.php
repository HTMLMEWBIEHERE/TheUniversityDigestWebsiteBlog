<?php
require_once __DIR__ . '/../components/connect.php';

$db = new Database();
$conn = $db->connect();

session_start();

$admin_id = $_SESSION['admin_id'] ?? null;
$admin_role = $_SESSION['admin_role'] ?? null;

// Redirect if not logged in or not a superadmin
if(!isset($admin_id) || $admin_role != 'superadmin'){
   header('location:../admin/admin_login.php');
   exit();
}

// Initialize messages array
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

// Add tejido post (published or draft)
if(isset($_POST['add_tejido']) || isset($_POST['save_draft'])){
   $title = trim($_POST['title']);
   $description = trim($_POST['description']);
   $category_id = $_POST['category_id'];
   $status = isset($_POST['save_draft']) ? 'draft' : 'published';
   
   // Image handling with validation
   $image = $_FILES['image']['name'];
   $image_size = $_FILES['image']['size'];
   $image_tmp_name = $_FILES['image']['tmp_name'];
   $image_extension = pathinfo($image, PATHINFO_EXTENSION);
   
   // Create a unique image name to prevent overwriting
   $new_image_name = uniqid() . '.' . $image_extension;
   $image_folder = '../uploaded_img/' . $new_image_name;

   // Validate inputs
   if(empty($title) || empty($description) || empty($category_id) || empty($image)){
      $message[] = 'Please fill out all fields!';
   } elseif($image_size > 2000000) { // Check if image size is less than 2MB
      $message[] = 'Image size is too large! Max size is 2MB.';
   } else {
      try {
         // Insert tejido post into the database with status
         $insert_tejido = $conn->prepare("INSERT INTO `tejido` (title, description, category_id, img, created_by, status) VALUES (?, ?, ?, ?, ?, ?)");
         $insert_tejido->execute([$title, $description, $category_id, $new_image_name, $admin_id, $status]);

         if($insert_tejido->rowCount() > 0){ 
            move_uploaded_file($image_tmp_name, $image_folder);
            
            if($status == 'draft'){
               $_SESSION['message'] = 'Tejido post saved as draft!';
               header('Location: sa_view_tejido.php'); // Redirect to drafts page
            } else {
               $_SESSION['message'] = 'Tejido post published successfully!';
               header('Location: ' . $_SERVER['PHP_SELF']); // Stay on same page
            }
            exit();
         } else {
            $message[] = 'Failed to add tejido post!';
         }
      } catch (PDOException $e) {
         $message[] = 'Database error: ' . $e->getMessage();
      }
   }
}

// Delete tejido post
if(isset($_POST['delete_tejido'])){
   $tejido_id = $_POST['tejido_id'];
   $tejido_id = filter_var($tejido_id, FILTER_SANITIZE_NUMBER_INT);
   
   try {
      // Begin transaction
      $conn->beginTransaction();
      
      // Get tejido image for deletion
      $select_image = $conn->prepare("SELECT img FROM `tejido` WHERE tejido_id = ? AND created_by = ?");
      $select_image->execute([$tejido_id, $admin_id]);
      $image_data = $select_image->fetch(PDO::FETCH_ASSOC);
      
      if($select_image->rowCount() > 0) {
         // Delete the tejido record
         $delete_tejido = $conn->prepare("DELETE FROM `tejido` WHERE tejido_id = ? AND created_by = ?");
         $delete_tejido->execute([$tejido_id, $admin_id]);
         
         // Commit if all succeeded
         $conn->commit();
         
         // Delete image file if it exists
         if($image_data && !empty($image_data['img'])){
            $image_path = '../uploaded_img/'.$image_data['img'];
            if(file_exists($image_path)){
               unlink($image_path);
            }
         }
         
         // Store success message in session
         $_SESSION['message'] = 'Tejido post deleted successfully!';
         
         // Redirect to the same page to prevent form resubmission
         header('Location: ' . $_SERVER['PHP_SELF']);
         exit();
      } else {
         $message[] = 'You do not have permission to delete this tejido post!';
         $conn->rollBack();
      }
   } catch (Exception $e) {
      // If any query fails, roll back the transaction
      $conn->rollBack();
      $message[] = 'Error deleting tejido post: ' . $e->getMessage();
   }
}

// Fetch only published tejido posts created by the logged-in admin
$select_tejido = $conn->prepare("SELECT * FROM `tejido` WHERE created_by = ? AND status = 'published' ORDER BY created_at DESC");
$select_tejido->execute([$admin_id]);
$tejido_posts = $select_tejido->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Add Tejido - Superadmin</title>

   <!-- Font Awesome CDN -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- Custom CSS File -->
   <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<?php include '../components/superadmin_header.php' ?>

<!-- Add Tejido Section -->
<section class="add-tejido">
   <h1 class="heading">Add New Tejido</h1>

   <?php
   if(isset($message) && is_array($message)){
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

   <form action="" method="post" enctype="multipart/form-data">
      <div class="input-box">
         <label for="title">Tejido Title:</label>
         <input type="text" name="title" id="title" class="box" placeholder="Enter tejido title" required>
      </div>
      
      <div class="input-box">
         <label for="description">Tejido Description:</label>
         <textarea name="description" id="description" class="box" placeholder="Enter tejido description" required cols="30" rows="10"></textarea>
      </div>
      
      <div class="input-box">
         <label for="category_id">Tejido Category:</label>
         <select name="category_id" id="category_id" class="box" required>
            <option value="" disabled selected>Select a category</option>
            <?php foreach($categories as $category): ?>
               <option value="<?= $category['category_id']; ?>"><?= htmlspecialchars($category['name']); ?></option>
            <?php endforeach; ?>
         </select>
      </div>
      
      <div class="input-box">
         <label for="image">Tejido Image:</label>
         <input type="file" name="image" id="image" class="box" accept="image/jpg, image/jpeg, image/png, image/gif" required>
      </div>
      
      <div class="flex-btn">
         <button type="submit" name="add_tejido" class="btn">Publish Tejido</button>
         <button type="submit" name="save_draft" class="option-btn">Save as Draft</button>
      </div>
   </form>
</section>

<!-- View Tejido Posts Section -->
<section class="tejido-posts">
   <h1 class="heading">Your Tejido Posts</h1>
   
   <div class="box-container">
      <?php if(count($tejido_posts) > 0): ?>
         <?php foreach($tejido_posts as $post): ?>
            <div class="box">
               <h3><?= htmlspecialchars($post['title']); ?></h3>
               <p class="description"><?= htmlspecialchars(substr($post['description'], 0, 150)) . (strlen($post['description']) > 150 ? '...' : ''); ?></p>
               
               <?php if(!empty($post['img'])): ?>
                  <img src="../uploaded_img/<?= htmlspecialchars($post['img']); ?>" alt="<?= htmlspecialchars($post['title']); ?>">
               <?php endif; ?>
               
               <p>Category: 
                  <span>
                     <?php
                     // Fetch category name
                     $select_category = $conn->prepare("SELECT name FROM `category` WHERE category_id = ?");
                     $select_category->execute([$post['category_id']]);
                     $category = $select_category->fetch(PDO::FETCH_ASSOC);
                     echo htmlspecialchars($category['name'] ?? 'Uncategorized');
                     ?>
                  </span>
               </p>
               <p>Added: <?= date('F j, Y, g:i a', strtotime($post['created_at'])); ?></p>
               
               <div class="flex-btn">
                  <a href="sa_edit_tejido.php?id=<?= $post['tejido_id']; ?>" class="btn">Edit</a>
                  
                  <form action="" method="post" style="display:inline;" onsubmit="return confirm('WARNING: You are about to delete this tejido post permanently.\n\nThis action CANNOT be undone. Are you sure you want to proceed?');">
                     <input type="hidden" name="tejido_id" value="<?= $post['tejido_id']; ?>">
                     <button type="submit" name="delete_tejido" class="delete-btn">Delete</button>
                  </form>
               </div>
            </div>
         <?php endforeach; ?>
      <?php else: ?>
         <?php
         // Check if any tejido exists at all, regardless of status
         $check_any_tejido = $conn->prepare("SELECT COUNT(*) as count FROM `tejido` WHERE created_by = ?");
         $check_any_tejido->execute([$admin_id]);
         $any_tejido = $check_any_tejido->fetch(PDO::FETCH_ASSOC)['count'];
         
         if ($any_tejido > 0) {
            echo '<p class="empty">You have ' . $any_tejido . ' tejido posts, but they might be saved as drafts. <a href="sa_view_tejido.php" class="btn" style="margin-top:1.5rem;">View Drafts</a></p>';
         } else {
            echo '<p class="empty">No tejido posts found!</p>';
         }
         ?>
      <?php endif; ?>
   </div>
</section>

<script src="../js/admin_script.js"></script>
</body>
</html>