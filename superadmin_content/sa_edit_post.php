<?php
include '../components/connect.php';

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

// Check if post_id exists in URL
if(!isset($_GET['post_id'])){
   $_SESSION['message'] = 'Post ID is missing!';
   header('location:sa_view_posts.php');
   exit();
}

$post_id = $_GET['post_id'];
$post_id = filter_var($post_id, FILTER_SANITIZE_NUMBER_INT);

if(isset($_POST['save'])){
   $title = trim($_POST['title']);
   $content = trim($_POST['content']);
   $category = $_POST['category'];
   $category = filter_var($category, FILTER_SANITIZE_NUMBER_INT);
   
   // Always set status to 'published' when updating
   $status = 'published';
   
   // Update post
   $update_post = $conn->prepare("UPDATE `posts` SET title = ?, content = ?, category_id = ?, status = ? WHERE post_id = ? AND created_by = ?");
   $update_post->execute([$title, $content, $category, $status, $post_id, $admin_id]);

   $message[] = 'Post published successfully!';
   
   $old_image = $_POST['old_image'];
   $image = $_FILES['image']['name'];
   
   if(!empty($image)){
      $image_size = $_FILES['image']['size'];
      $image_tmp_name = $_FILES['image']['tmp_name'];
      $image_extension = pathinfo($image, PATHINFO_EXTENSION);
      $new_image_name = uniqid() . '.' . $image_extension;
      $image_folder = '../uploaded_img/'.$new_image_name;

      if($image_size > 2000000){
         $message[] = 'Image size is too large! Max size is 2MB.';
      } else {
         // Update with post_id
         $update_image = $conn->prepare("UPDATE `posts` SET image = ? WHERE post_id = ? AND created_by = ?");
         move_uploaded_file($image_tmp_name, $image_folder);
         $update_image->execute([$new_image_name, $post_id, $admin_id]);
         
         // Delete old image if it exists
         if(!empty($old_image) && file_exists('../uploaded_img/'.$old_image)){
            unlink('../uploaded_img/'.$old_image);
         } 
         $message[] = 'Image updated!';
      }
   }
}

if(isset($_POST['delete_post'])){
   $post_id = $_POST['post_id'];
   $post_id = filter_var($post_id, FILTER_SANITIZE_NUMBER_INT);
   
   try {
      // Begin transaction
      $conn->beginTransaction();
      
      // Get post image for deletion
      $delete_image = $conn->prepare("SELECT * FROM `posts` WHERE post_id = ? AND created_by = ?");
      $delete_image->execute([$post_id, $admin_id]);
      $fetch_delete_image = $delete_image->fetch(PDO::FETCH_ASSOC);
      
      if($delete_image->rowCount() > 0) {
         // Delete likes
         $delete_likes = $conn->prepare("DELETE FROM `likes` WHERE post_id = ?");
         $delete_likes->execute([$post_id]);
         
         // Delete comments
         $delete_comments = $conn->prepare("DELETE FROM `comments` WHERE post_id = ?");
         $delete_comments->execute([$post_id]);
         
         // Delete post
         $delete_post = $conn->prepare("DELETE FROM `posts` WHERE post_id = ? AND created_by = ?");
         $delete_post->execute([$post_id, $admin_id]);
         
         // Commit if all succeeded
         $conn->commit();
         
         // Delete image file
         if(!empty($fetch_delete_image['image']) && file_exists('../uploaded_img/'.$fetch_delete_image['image'])){
            unlink('../uploaded_img/'.$fetch_delete_image['image']);
         }
         
         $_SESSION['message'] = 'Post deleted successfully!';
         header('location:sa_add_posts.php');
         exit();
      } else {
         $message[] = 'You do not have permission to delete this post!';
         $conn->rollBack();
      }
      
   } catch (Exception $e) {
      $conn->rollBack();
      $message[] = 'Error: ' . $e->getMessage();
   }
}

if(isset($_POST['delete_image'])){
   $empty_image = '';
   $post_id = $_POST['post_id'];
   $post_id = filter_var($post_id, FILTER_SANITIZE_NUMBER_INT);
   
   // Update with post_id and created_by for security
   $delete_image = $conn->prepare("SELECT * FROM `posts` WHERE post_id = ? AND created_by = ?");
   $delete_image->execute([$post_id, $admin_id]);
   $fetch_delete_image = $delete_image->fetch(PDO::FETCH_ASSOC);
   
   if($fetch_delete_image['image'] != '' && file_exists('../uploaded_img/'.$fetch_delete_image['image'])){
      unlink('../uploaded_img/'.$fetch_delete_image['image']);
   }
   
   // Update with post_id and created_by for security
   $unset_image = $conn->prepare("UPDATE `posts` SET image = ? WHERE post_id = ? AND created_by = ?");
   $unset_image->execute([$empty_image, $post_id, $admin_id]);
   $message[] = 'Image deleted successfully!';
}

// Use post_id
$select_posts = $conn->prepare("SELECT * FROM `posts` WHERE post_id = ?");
$select_posts->execute([$post_id]);
$fetch_posts = $select_posts->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Edit Post</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="../css/admin_style.css">

</head>
<body>

<?php include '../components/superadmin_header.php' ?>

<section class="post-editor">
   <h1 class="heading">Edit Post</h1>

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

   <?php
   if($select_posts->rowCount() > 0){
   ?>
   
   <form action="sa_edit_post.php?post_id=<?= $post_id; ?>" method="post" enctype="multipart/form-data">
      <input type="hidden" name="old_image" value="<?= htmlspecialchars($fetch_posts['image'] ?? ''); ?>">
      <input type="hidden" name="post_id" value="<?= $fetch_posts['post_id']; ?>">
      
      <!-- Status will always be published, so add as a hidden field -->
      <input type="hidden" name="status" value="published">
      
      <p>Post Title <span>*</span></p>
      <input type="text" name="title" maxlength="100" required placeholder="Add post title" class="box" value="<?= htmlspecialchars($fetch_posts['title']); ?>">
      
      <p>Post Content <span>*</span></p>
      <textarea name="content" class="box" required maxlength="10000" placeholder="Write your content..." cols="30" rows="10"><?= htmlspecialchars($fetch_posts['content']); ?></textarea>
      
      <p>Post Category <span>*</span></p>
      <select name="category" class="box" required>
         <?php
         // Fetch categories from database
         $select_categories = $conn->prepare("SELECT * FROM `category`");
         $select_categories->execute();
         $categories = $select_categories->fetchAll(PDO::FETCH_ASSOC);
         
         foreach($categories as $category){
            $selected = ($fetch_posts['category_id'] == $category['category_id']) ? 'selected' : '';
            echo "<option value='".htmlspecialchars($category['category_id'])."' ".$selected.">".htmlspecialchars($category['name'])."</option>";
         }
         ?>
      </select>
      
      <p>Post Image</p>
      <input type="file" name="image" class="box" accept="image/jpg, image/jpeg, image/png, image/webp">
      <?php if($fetch_posts['image'] != ''){ ?>
         <img src="../uploaded_img/<?= htmlspecialchars($fetch_posts['image']); ?>" class="image" alt="">
         <input type="submit" value="Delete Image" class="inline-delete-btn" name="delete_image" onclick="return confirm('Delete this image?');">
      <?php } ?>
      
      <div class="flex-btn">
         <input type="submit" value="Upload Post" name="save" class="btn">
         <a href="sa_add_posts.php" class="option-btn">Go Back</a>
         <input type="submit" value="Delete Post" class="delete-btn" name="delete_post" onclick="return confirm('WARNING: You are about to delete this post permanently.\n\n• All comments associated with this post will be deleted\n• All likes will be removed\n• Post images will be deleted from the server\n\nThis action CANNOT be undone. Are you sure you want to proceed?');">
      </div>
   </form>

   <?php
      } else {
         echo '<p class="empty">No post found!</p>';
   ?>
   <div class="flex-btn">
      <a href="sa_add_posts.php" class="option-btn">Go Back</a>
   </div>
   <?php
      }
   ?>
</section>

<!-- custom js file link  -->
<script src="../js/admin_script.js"></script>
</body>
</html>