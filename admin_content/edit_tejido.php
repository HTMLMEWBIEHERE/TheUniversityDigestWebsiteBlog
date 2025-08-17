<?php
require_once __DIR__ . '/../components/connect.php';

$db = new Database();
$conn = $db->connect();

session_start();

$admin_id = $_SESSION['admin_id'] ?? null;
$admin_role = $_SESSION['admin_role'] ?? null;

// Redirect if not logged in OR not the right role (allow both subadmin and superadmin)
if(!isset($admin_id) || ($admin_role != 'subadmin' && $admin_role != 'superadmin')){
   header('location:../admin/admin_login.php');
   exit();
}

// Check if tejido_id exists in URL
if(!isset($_GET['id'])){
   $_SESSION['message'] = 'Tejido ID is missing!';
   header('location:add_tejido.php');
   exit();
}

$tejido_id = $_GET['id'];
$tejido_id = filter_var($tejido_id, FILTER_SANITIZE_NUMBER_INT);

// Fetch the tejido post to edit
$select_tejido = $conn->prepare("SELECT * FROM `tejido` WHERE tejido_id = ? AND created_by = ?");
$select_tejido->execute([$tejido_id, $admin_id]);

// If tejido doesn't exist or doesn't belong to this admin
if($select_tejido->rowCount() <= 0){
   $_SESSION['message'] = 'Tejido post not found or you do not have permission to edit it!';
   header('location:add_tejido.php');
   exit();
}

$tejido_data = $select_tejido->fetch(PDO::FETCH_ASSOC);

// Initialize messages array
$message = [];

// Display session message if it exists
if(isset($_SESSION['message'])) {
   $message[] = $_SESSION['message'];
   unset($_SESSION['message']);
}

// Handle form submission
if(isset($_POST['update_tejido'])){
   $title = $_POST['title'];
   $title = filter_var($title, FILTER_SANITIZE_STRING);
   $description = $_POST['description'];
   $description = filter_var($description, FILTER_SANITIZE_STRING);
   $category_id = $_POST['category_id'];
   $category_id = filter_var($category_id, FILTER_SANITIZE_NUMBER_INT);
   $status = $_POST['status']; // Added status field
   $status = filter_var($status, FILTER_SANITIZE_STRING);
   
   try {
      // Begin transaction for data integrity
      $conn->beginTransaction();
      
      // Update tejido post in the database
      $update_tejido = $conn->prepare("UPDATE `tejido` SET title = ?, description = ?, category_id = ?, status = ? WHERE tejido_id = ? AND created_by = ?");
      $update_tejido->execute([$title, $description, $category_id, $status, $tejido_id, $admin_id]);
      
      // Handle image update
      $old_image = $tejido_data['img'];
      $image = $_FILES['image']['name'];
      
      if(!empty($image)){
         $image = filter_var($image, FILTER_SANITIZE_STRING);
         $image_size = $_FILES['image']['size'];
         $image_tmp_name = $_FILES['image']['tmp_name'];
         $image_extension = pathinfo($image, PATHINFO_EXTENSION);
         $new_image_name = uniqid('tejido_') . '.' . $image_extension;
         $image_folder = '../uploaded_img/'.$new_image_name;
         
         if($image_size > 2000000){
            $message[] = 'Image size is too large!';
         } else {
            // Move the new image file
            move_uploaded_file($image_tmp_name, $image_folder);
            
            // Update the image in database
            $update_image = $conn->prepare("UPDATE `tejido` SET img = ? WHERE tejido_id = ? AND created_by = ?");
            $update_image->execute([$new_image_name, $tejido_id, $admin_id]);
            
            // Delete the old image if it exists
            if(!empty($old_image)){
               $old_image_path = '../uploaded_img/'.$old_image;
               if(file_exists($old_image_path)){
                  unlink($old_image_path);
               }
            }
         }
      }
      
      // Commit the transaction
      $conn->commit();
      
      // Set success message and redirect
      $_SESSION['message'] = 'Tejido post updated successfully!';
      
      // Redirect based on status
      if($status == 'draft'){
         header('location:view_tejido.php');
      } else {
         header('location:add_tejido.php');
      }
      exit();
      
   } catch (Exception $e) {
      // Rollback transaction on error
      $conn->rollBack();
      $message[] = 'Error: ' . $e->getMessage();
   }
}

// Handle image deletion
if(isset($_POST['delete_image'])){
   if($tejido_data['img'] != ''){
      $image_path = '../uploaded_img/'.$tejido_data['img'];
      
      // Try to delete the file
      if(file_exists($image_path) && unlink($image_path)){
         // Update the database to remove image reference
         $update_image = $conn->prepare("UPDATE `tejido` SET img = '' WHERE tejido_id = ? AND created_by = ?");
         $update_image->execute([$tejido_id, $admin_id]);
         
         if($update_image->rowCount() > 0){
            $_SESSION['message'] = 'Image deleted successfully!';
            header('location:' . $_SERVER['PHP_SELF'] . '?id=' . $tejido_id);
            exit();
         } else {
            $message[] = 'Failed to update database after image deletion!';
         }
      } else {
         $message[] = 'Failed to delete image file!';
      }
   } else {
      $message[] = 'No image to delete!';
   }
}

// Handle post deletion
if(isset($_POST['delete_tejido'])){
   try {
      // Begin transaction
      $conn->beginTransaction();
      
      // Delete the tejido post
      $delete_tejido = $conn->prepare("DELETE FROM `tejido` WHERE tejido_id = ? AND created_by = ?");
      $delete_tejido->execute([$tejido_id, $admin_id]);
      
      if($delete_tejido->rowCount() > 0){
         // Delete the image if it exists
         if($tejido_data['img'] != ''){
            $image_path = '../uploaded_img/'.$tejido_data['img'];
            if(file_exists($image_path)){
               unlink($image_path);
            }
         }
         
         // Commit the transaction
         $conn->commit();
         
         $_SESSION['message'] = 'Tejido post deleted successfully!';
         
         // Redirect based on original status
         if($tejido_data['status'] == 'draft'){
            header('location:view_tejido.php');
         } else {
            header('location:add_tejido.php');
         }
         exit();
      } else {
         $conn->rollBack();
         $message[] = 'Failed to delete tejido post!';
      }
   } catch (Exception $e) {
      $conn->rollBack();
      $message[] = 'Error: ' . $e->getMessage();
   }
}

// Refetch the tejido data in case it was updated
$select_tejido = $conn->prepare("SELECT * FROM `tejido` WHERE tejido_id = ?");
$select_tejido->execute([$tejido_id]);
$tejido_data = $select_tejido->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Edit Tejido Post</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<?php include '../components/admin_header.php'; ?>

<!-- Edit Tejido Section -->
<section class="post-editor">
   <h1 class="heading">Edit Tejido Post</h1>
   
   <?php if(!empty($message)): ?>
      <?php foreach($message as $msg): ?>
         <div class="message">
            <span><?= $msg; ?></span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
         </div>
      <?php endforeach; ?>
   <?php endif; ?>
   
   <form action="" method="post" enctype="multipart/form-data">
      <input type="hidden" name="old_image" value="<?= $tejido_data['img']; ?>">
      <input type="hidden" name="tejido_id" value="<?= $tejido_data['tejido_id']; ?>">
      
      <p>Tejido Title <span>*</span></p>
      <input type="text" name="title" maxlength="100" required placeholder="Enter tejido title" class="box" value="<?= htmlspecialchars($tejido_data['title']); ?>">
      
      <p>Tejido Description <span>*</span></p>
      <textarea name="description" class="box" required maxlength="10000" placeholder="Enter tejido description..." cols="30" rows="10"><?= htmlspecialchars($tejido_data['description']); ?></textarea>
      
      <p>Tejido Category <span>*</span></p>
      <select name="category_id" class="box" required>
         <?php
         // Fetch categories from database
         $select_categories = $conn->prepare("SELECT * FROM `category`");
         $select_categories->execute();
         $categories = $select_categories->fetchAll(PDO::FETCH_ASSOC);
         
         foreach($categories as $category){
            $selected = ($tejido_data['category_id'] == $category['category_id']) ? 'selected' : '';
            echo "<option value='".$category['category_id']."' ".$selected.">".htmlspecialchars($category['name'])."</option>";
         }
         ?>
      </select>
      
      <p>Status <span>*</span></p>
      <select name="status" class="box" required>
         <option value="draft" <?= ($tejido_data['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
         <option value="published" <?= ($tejido_data['status'] == 'published') ? 'selected' : ''; ?>>Published</option>
      </select>
      
      <p>Tejido Image</p>
      <input type="file" name="image" class="box" accept="image/jpg, image/jpeg, image/png, image/webp">
      <small class="note">Leave empty to keep current image</small>
      
      <?php if($tejido_data['img'] != ''){ ?>
         <div class="image-preview">
            <img src="../uploaded_img/<?= htmlspecialchars($tejido_data['img']); ?>" class="image" alt="">
            <input type="submit" value="Delete Image" class="inline-delete-btn" name="delete_image" onclick="return confirm('Delete this image?');">
         </div>
      <?php } ?>
      
      <div class="flex-btn">
         <input type="submit" value="Update Tejido" name="update_tejido" class="btn">
         <a href="<?= ($tejido_data['status'] == 'draft') ? 'view_tejido.php' : 'add_tejido.php'; ?>" class="option-btn">Go Back</a>
      </div>
   </form>
   
   <!-- Separate form for delete action -->
   <form action="" method="post" style="margin-top: 20px;">
      <input type="hidden" name="tejido_id" value="<?= $tejido_data['tejido_id']; ?>">
      <input type="submit" value="Delete Tejido Post" class="delete-btn" name="delete_tejido" onclick="return confirm('WARNING: You are about to delete this tejido post permanently.\n\nThis action CANNOT be undone. Are you sure you want to proceed?');">
   </form>
</section>

<!-- custom js file link  -->
<script src="../js/admin_script.js"></script>

</body>
</html>