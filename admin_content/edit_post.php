<?php
include '../components/connect.php';

$db = new Database();
$conn = $db->connect();

session_start();

$admin_id = $_SESSION['admin_id'] ?? null;

if(!isset($admin_id)){
   header('location:../admin/admin_login.php');
   exit();
}

// Check if post_id exists in URL
if(!isset($_GET['post_id'])){
   $message[] = 'Post ID is missing!';
   header('location:view_posts.php');
   exit();
}

$post_id = $_GET['post_id'];
$post_id = filter_var($post_id, FILTER_SANITIZE_NUMBER_INT);

// Get post data
$select_post = $conn->prepare("SELECT * FROM `posts` WHERE post_id = ? AND created_by = ?");
$select_post->execute([$post_id, $admin_id]);
$fetch_post = $select_post->fetch(PDO::FETCH_ASSOC);

// Check if post exists and belongs to the logged-in admin
if($select_post->rowCount() <= 0){
   $message[] = 'Post not found or you do not have permission to edit it!';
   header('location:view_posts.php');
   exit();
}

// Fetch categories
$select_categories = $conn->prepare("SELECT * FROM `category`");
$select_categories->execute();
$categories = $select_categories->fetchAll(PDO::FETCH_ASSOC);

// Handle post update
if(isset($_POST['update'])){
   $title = $_POST['title'];
   $title = filter_var($title, FILTER_SANITIZE_STRING);
   $content = $_POST['content'];
   $content = filter_var($content, FILTER_SANITIZE_STRING);
   $category_id = $_POST['category_id'];
   $category_id = filter_var($category_id, FILTER_SANITIZE_STRING);
   $status = $_POST['status'];
   $status = filter_var($status, FILTER_SANITIZE_STRING);

   // Update post
   $update_post = $conn->prepare("UPDATE `posts` SET title = ?, content = ?, category_id = ?, status = ? WHERE post_id = ?");
   $update_post->execute([$title, $content, $category_id, $status, $post_id]);
   
   $message[] = 'Post updated!';

   // Handle image upload if provided
   $image = $_FILES['image']['name'];
   
   if(!empty($image)){
      $image = filter_var($image, FILTER_SANITIZE_STRING);
      $image_size = $_FILES['image']['size'];
      $image_tmp_name = $_FILES['image']['tmp_name'];
      $image_folder = '../uploaded_img/'.$image;
      $old_image = $fetch_post['image'];
      
      if($image_size > 2000000){
         $message[] = 'Image size is too large!';
      }else{
         // Update image in database
         $update_image = $conn->prepare("UPDATE `posts` SET image = ? WHERE post_id = ?");
         $update_image->execute([$image, $post_id]);
         
         // Upload new image
         move_uploaded_file($image_tmp_name, $image_folder);
         
         // Delete old image if exists
         if($old_image != ''){
            $image_path = '../uploaded_img/'.$old_image;
            if(file_exists($image_path)){
                unlink($image_path);
            }
         }
         
         $message[] = 'Image updated!';
      }
   }

   // Redirect back to view posts if publishing from draft
   if($fetch_post['status'] == 'draft' && $status == 'published'){
      $_SESSION['message'] = 'Post published successfully!';
      header('location:view_posts.php');
      exit();
   }
}

// Handle delete post
if(isset($_POST['delete_post'])){
   try {
      // Begin transaction
      $conn->beginTransaction();
      
      // Delete likes
      $delete_likes = $conn->prepare("DELETE FROM `likes` WHERE post_id = ?");
      $delete_likes->execute([$post_id]);
      
      // Delete comments
      $delete_comments = $conn->prepare("DELETE FROM `comments` WHERE post_id = ?");
      $delete_comments->execute([$post_id]);
      
      // Delete post
      $delete_post = $conn->prepare("DELETE FROM `posts` WHERE post_id = ?");
      $delete_post->execute([$post_id]);
      
      // Commit if all succeeded
      $conn->commit();
      
      // Delete image file
      if($fetch_post['image'] != ''){
         $image_path = '../uploaded_img/'.$fetch_post['image'];
         if(file_exists($image_path)){
             unlink($image_path);
         }
      }
      
      $_SESSION['message'] = 'Post deleted successfully!';
      header('location:view_posts.php');
      exit();
      
   } catch (Exception $e) {
      $conn->rollBack();
      $message[] = 'Error: ' . $e->getMessage();
   }
}

// Handle delete image
if(isset($_POST['delete_image'])){
   $empty_image = '';
   
   // Get current image
   $select_image = $conn->prepare("SELECT image FROM `posts` WHERE post_id = ?");
   $select_image->execute([$post_id]);
   $fetch_image = $select_image->fetch(PDO::FETCH_ASSOC);
   
   // Delete image file if exists
   if($fetch_image['image'] != ''){
      $image_path = '../uploaded_img/'.$fetch_image['image'];
      if(file_exists($image_path)){
          unlink($image_path);
      }
   }
   
   // Update post to have no image
   $update_image = $conn->prepare("UPDATE `posts` SET image = ? WHERE post_id = ?");
   $update_image->execute([$empty_image, $post_id]);
   
   $message[] = 'Image removed!';
}
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

<?php include '../components/admin_header.php' ?>

<section class="post-editor">
   <h1 class="heading">edit post</h1>
   
   <?php
   if(isset($message)){
      foreach($message as $message){
         echo '
         <div class="message">
            <span>'.$message.'</span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
         </div>
         ';
      }
   }
   ?>
   
   <form action="" method="post" enctype="multipart/form-data">
      <p>Post Title <span>*</span></p>
      <input type="text" name="title" maxlength="100" required placeholder="Add post title" class="box" value="<?= $fetch_post['title']; ?>">
      
      <p>Post Content <span>*</span></p>
      <textarea name="content" class="box" required maxlength="10000" placeholder="Write your content..." cols="30" rows="10"><?= $fetch_post['content']; ?></textarea>
      
      <p>Post Category <span>*</span></p>
      <select name="category_id" class="box" required>
         <?php
         foreach($categories as $category){
            $selected = ($category['category_id'] == $fetch_post['category_id']) ? 'selected' : '';
            echo '<option value="'.$category['category_id'].'" '.$selected.'>'.$category['name'].'</option>';
         }
         ?>
      </select>
      
      <p>Post Status <span>*</span></p>
      <select name="status" class="box" required>
         <option value="draft" <?= ($fetch_post['status'] == 'draft') ? 'selected' : ''; ?>>Draft</option>
         <option value="published" <?= ($fetch_post['status'] == 'published') ? 'selected' : ''; ?>>Published</option>
      </select>
      
      <p>Post Image</p>
      <input type="file" name="image" class="box" accept="image/jpg, image/jpeg, image/png, image/webp">
      
      <?php if($fetch_post['image'] != ''){ ?>
      <div class="current-image">
         <img src="../uploaded_img/<?= $fetch_post['image']; ?>" alt="Current Post Image">
         <form action="" method="post">
            <button type="submit" name="delete_image" class="delete-btn" onclick="return confirm('Delete this image?');">Delete Image</button>
         </form>
      </div>
      <?php } ?>
      
      <div class="flex-btn">
         <input type="submit" value="Update Post" name="update" class="btn">
         <a href="view_posts.php" class="option-btn">Go Back</a>
         <form action="" method="post" style="margin-left: 5px;">
            <button type="submit" name="delete_post" class="delete-btn" onclick="return confirm('Delete this post?');">Delete Post</button>
         </form>
      </div>
   </form>
</section>

<!-- custom js file link  -->
<script src="../js/admin_script.js"></script>

</body>
</html>