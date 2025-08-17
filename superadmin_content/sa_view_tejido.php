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

// Initialize message array
$message = [];

// Check for messages in the session
if(isset($_SESSION['message'])) {
   $message[] = $_SESSION['message'];
   unset($_SESSION['message']); // Remove after displaying
}

// Handle tejido deletion
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

// Publish tejido
if(isset($_POST['publish_tejido'])){
   $tejido_id = filter_var($_POST['tejido_id'], FILTER_SANITIZE_NUMBER_INT);
   
   $update_status = $conn->prepare("UPDATE tejido SET status = 'published' WHERE tejido_id = ? AND created_by = ?");
   $update_status->execute([$tejido_id, $admin_id]);
   
   if($update_status->rowCount() > 0){
      $_SESSION['message'] = 'Tejido published successfully!';
      header('Location: ' . $_SERVER['PHP_SELF']);
      exit();
   } else {
      $message[] = 'Failed to publish tejido!';
   }
}

// Fetch only draft tejido posts created by the logged-in admin
$select_tejido = $conn->prepare("SELECT * FROM `tejido` WHERE created_by = ? AND status = 'draft' ORDER BY created_at DESC");
$select_tejido->execute([$admin_id]);
$tejido_posts = $select_tejido->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Draft Tejidos</title>

   <!-- Font Awesome CDN -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- Custom CSS File -->
   <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<?php include '../components/superadmin_header.php' ?>

<section class="tejido-posts">
   <h1 class="heading">Your Draft Tejido Posts</h1>

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

   <div class="box-container">
      <?php if(count($tejido_posts) > 0): ?>
         <?php foreach($tejido_posts as $post): ?>
            <div class="box">
               <div class="status" style="background-color:coral;">Draft</div>
               <p>Title: <span><?= htmlspecialchars($post['title']); ?></span></p>
               <p>Description: <span><?= htmlspecialchars(substr($post['description'], 0, 100)) . (strlen($post['description']) > 100 ? '...' : ''); ?></span></p>
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
               <p>Image: <img src="../uploaded_img/<?= htmlspecialchars($post['img']); ?>" alt="<?= htmlspecialchars($post['title']); ?>" width="100"></p>
               <p>Created At: <span><?= $post['created_at']; ?></span></p>
               
               <div class="flex-btn">
                  <a href="sa_edit_tejido.php?id=<?= $post['tejido_id']; ?>" class="btn">Edit</a>
                  
                  <form action="" method="post" style="display:inline;">
                     <input type="hidden" name="tejido_id" value="<?= $post['tejido_id']; ?>">
                     <button type="submit" name="publish_tejido" class="option-btn">Publish</button>
                  </form>
                  
                  <form action="" method="post" style="display:inline;">
                     <input type="hidden" name="tejido_id" value="<?= $post['tejido_id']; ?>">
                     <button type="submit" name="delete_tejido" class="delete-btn" onclick="return confirm('Are you sure you want to delete this post?');">
                        Delete
                     </button>
                  </form>
               </div>
            </div>
         <?php endforeach; ?>
      <?php else: ?>
         <p class="empty">No draft tejido posts found! <a href="sa_add_tejido.php" class="btn" style="margin-top:1.5rem;">Add New Tejido</a></p>
      <?php endif; ?>
   </div>
</section>

<script src="../js/admin_script.js"></script>
</body>
</html>