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

// Fetch all carousel images for display and editing
$select_carousel_images = $conn->prepare("SELECT * FROM carousel_images ORDER BY display_order ASC");
$select_carousel_images->execute();
$carousel_images = $select_carousel_images->fetchAll(PDO::FETCH_ASSOC);

// Handle Carousel Image Deletion
if(isset($_POST['delete_carousel_image'])){
   $carousel_id = filter_var($_POST['carousel_id'], FILTER_SANITIZE_NUMBER_INT);

   // Fetch image path for deletion
   $select_image = $conn->prepare("SELECT image_url FROM carousel_images WHERE id = ?");
   $select_image->execute([$carousel_id]);
   $image_data = $select_image->fetch(PDO::FETCH_ASSOC);

   // Delete the carousel image
   $delete_carousel_image = $conn->prepare("DELETE FROM carousel_images WHERE id = ?");
   $delete_carousel_image->execute([$carousel_id]);

   if($delete_carousel_image->rowCount() > 0) {
      // Delete image file from server
      if($image_data && !empty($image_data['image_url'])){
         $image_path = '../uploaded_img/'.$image_data['image_url'];
         if(file_exists($image_path)){
            unlink($image_path);
         }
      }

      $_SESSION['message'] = 'Carousel image deleted successfully!';
   } else {
      $_SESSION['message'] = 'Failed to delete carousel image.';
   }

   header('Location: ' . $_SERVER['PHP_SELF']);
   exit();
}

// Handle Carousel Image Display Order Update
if(isset($_POST['update_display_order'])){
   $carousel_id = filter_var($_POST['carousel_id'], FILTER_SANITIZE_NUMBER_INT);
   $new_display_order = filter_var($_POST['display_order'], FILTER_SANITIZE_NUMBER_INT);

   // Validate the new display order (1, 2, or 3)
   if($new_display_order >= 1 && $new_display_order <= 3) {
       $update_display_order = $conn->prepare("UPDATE carousel_images SET display_order = ? WHERE id = ?");
       $update_display_order->execute([$new_display_order, $carousel_id]);

       if($update_display_order->rowCount() > 0){
          $_SESSION['message'] = 'Carousel display order updated successfully!';
       } else {
          $_SESSION['message'] = 'Failed to update display order.';
       }
   } else {
       $_SESSION['message'] = 'Invalid display order selected.';
   }

   // Redirect to sa_add_carousel.php after updating display order
   header('Location: sa_add_carousel.php');
   exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Superadmin - Edit Carousel Images</title>
   
   <!-- Font Awesome CDN Link -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   
   <!-- Admin CSS File Link -->
   <link rel="stylesheet" href="../css/admin_style.css">
   <link rel="stylesheet" href="../css/sa_carousel.css">
   
   <!-- Carousel CSS -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<?php include '../components/superadmin_header.php' ?>

<!-- Edit Carousel Section -->
<section class="edit-carousel">
   <h1 class="heading">Manage Carousel Images</h1>

   <?php if(!empty($message)): ?>
      <?php foreach($message as $msg): ?>
         <div class="message">
            <span><?= htmlspecialchars($msg); ?></span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
         </div>
      <?php endforeach; ?>
   <?php endif; ?>

   <div class="carousel-images">
      <?php if(count($carousel_images) > 0): ?>
         <?php foreach($carousel_images as $carousel): ?>
            <div class="carousel-item-box">
               <img src="../uploaded_img/<?= htmlspecialchars($carousel['image_url']); ?>" class="d-block w-100" alt="Carousel Image">
               <p>Display Order: <?= htmlspecialchars($carousel['display_order']); ?></p>

               <!-- Edit Display Order Form (Dropdown for 1-3) -->
               <form action="" method="post" class="update-order-form">
                  <input type="hidden" name="carousel_id" value="<?= $carousel['id']; ?>">

                  <!-- Dropdown for selecting display order (1 to 3) -->
                  <select name="display_order" class="box" required>
                     <option value="1" <?= ($carousel['display_order'] == 1) ? 'selected' : ''; ?>>1</option>
                     <option value="2" <?= ($carousel['display_order'] == 2) ? 'selected' : ''; ?>>2</option>
                     <option value="3" <?= ($carousel['display_order'] == 3) ? 'selected' : ''; ?>>3</option>
                  </select>

                  <button type="submit" name="update_display_order" class="btn">Update Order</button>
               </form>

               <!-- Delete Carousel Image Form -->
               <form action="" method="post" class="delete-form" onsubmit="return confirm('Are you sure you want to delete this image?');">
                  <input type="hidden" name="carousel_id" value="<?= $carousel['id']; ?>">
                  <button type="submit" name="delete_carousel_image" class="delete-btn">Delete</button>
               </form>
            </div>
         <?php endforeach; ?>
      <?php else: ?>
         <p class="empty">No carousel images available to manage.</p>
      <?php endif; ?>
   </div>
</section>

<!-- Admin JS File Link -->
<script src="../js/admin_script.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

</body>
</html>
