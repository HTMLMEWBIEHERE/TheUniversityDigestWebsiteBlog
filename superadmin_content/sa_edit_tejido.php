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

// Check if tejido_id exists in URL
if(!isset($_GET['id'])){
   $_SESSION['message'] = 'Tejido ID is missing!';
   header('location:sa_add_tejido.php');
   exit();
}

$tejido_id = $_GET['id'];
$tejido_id = filter_var($tejido_id, FILTER_SANITIZE_NUMBER_INT);

// Fetch the tejido post to edit
$select_tejido = $conn->prepare("SELECT * FROM `tejido` WHERE tejido_id = ? AND created_by = ?");
$select_tejido->execute([$tejido_id, $admin_id]);

// If tejido doesn't exist or doesn't belong to this superadmin
if($select_tejido->rowCount() <= 0){
   $_SESSION['message'] = 'Tejido post not found or you do not have permission to edit it!';
   header('location:sa_add_tejido.php');
   exit();
}

$tejido_data = $select_tejido->fetch(PDO::FETCH_ASSOC);
$message = [];

// Handle form submission
if(isset($_POST['update_tejido'])){
   $title = $_POST['title'];
   $title = filter_var($title, FILTER_SANITIZE_STRING);
   $description = $_POST['description'];
   $description = filter_var($description, FILTER_SANITIZE_STRING);
   $category_id = $_POST['category_id'];
   $category_id = filter_var($category_id, FILTER_SANITIZE_NUMBER_INT);
   
   // Update tejido post in the database
   $update_tejido = $conn->prepare("UPDATE `tejido` SET title = ?, description = ?, category_id = ? WHERE tejido_id = ? AND created_by = ?");
   $update_tejido->execute([$title, $description, $category_id, $tejido_id, $admin_id]);
   
   $message[] = 'Tejido post updated successfully!';
   
   // Handle image update
   $old_image = $tejido_data['img'];
   $image = $_FILES['image']['name'];
   
   if(!empty($image)){
      $image = filter_var($image, FILTER_SANITIZE_STRING);
      $image_size = $_FILES['image']['size'];
      $image_tmp_name = $_FILES['image']['tmp_name'];
      $image_extension = pathinfo($image, PATHINFO_EXTENSION);
      
      // Create unique filename
      $new_image_name = uniqid() . '.' . $image_extension;
      $image_folder = '../uploaded_img/'.$new_image_name;
      
      if($image_size > 2000000){
         $message[] = 'Image size is too large!';
      } else {
         // Update the image
         $update_image = $conn->prepare("UPDATE `tejido` SET img = ? WHERE tejido_id = ?");
         $update_image->execute([$new_image_name, $tejido_id]);
         
         // Move the new image file
         move_uploaded_file($image_tmp_name, $image_folder);
         
         // Delete the old image if it exists
         if(!empty($old_image) && file_exists('../uploaded_img/'.$old_image)){
            unlink('../uploaded_img/'.$old_image);
         }
         
         $message[] = 'Image updated successfully!';
      }
   }
}

// Handle image deletion
if(isset($_POST['delete_image'])){
   $empty_image = '';
   
   // Update the database to remove image reference
   $update_image = $conn->prepare("UPDATE `tejido` SET img = ? WHERE tejido_id = ? AND created_by = ?");
   $update_image->execute([$empty_image, $tejido_id, $admin_id]);
   
   // Delete the image file
   if($tejido_data['img'] != '' && file_exists('../uploaded_img/'.$tejido_data['img'])){
      unlink('../uploaded_img/'.$tejido_data['img']);
      $message[] = 'Image deleted successfully!';
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

<?php include '../components/superadmin_header.php'; ?>

<!-- Edit Tejido Section -->
<section class="post-editor">
   <h1 class="heading">Edit Tejido Post</h1>
   
   <?php if(isset($message) && !empty($message)): ?>
      <?php foreach((array)$message as $msg): ?>
         <div class="message">
            <span><?= htmlspecialchars($msg); ?></span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
         </div>
      <?php endforeach; ?>
   <?php endif; ?>
   
   <form action="" method="post" enctype="multipart/form-data">
      <input type="hidden" name="old_image" value="<?= htmlspecialchars($tejido_data['img']); ?>">
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
            echo "<option value='".htmlspecialchars($category['category_id'])."' ".$selected.">".htmlspecialchars($category['name'])."</option>";
         }
         ?>
      </select>
      
      <p>Tejido Image</p>
      <input type="file" name="image" class="box" accept="image/jpg, image/jpeg, image/png, image/webp">
      <?php if($tejido_data['img'] != ''){ ?>
         <img src="../uploaded_img/<?= htmlspecialchars($tejido_data['img']); ?>" class="image" alt="">
         <input type="submit" value="Delete Image" class="inline-delete-btn" name="delete_image" onclick="return confirm('Delete this image?');">
      <?php } ?>
      
      <div class="flex-btn">
         <input type="submit" value="Update Tejido" name="update_tejido" class="btn">
         <a href="sa_add_tejido.php" class="option-btn">Go Back</a>
      </div>
   </form>
</section>

<!-- custom js file link  -->
<script src="../js/admin_script.js"></script>

</body>
</html>