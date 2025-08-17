<?php
include '../components/connect.php';

$db = new Database();
$conn = $db->connect();

session_start();

$admin_id = $_SESSION['admin_id'] ?? null;
$admin_role = $_SESSION['admin_role'] ?? null;

// Redirect if not logged in or not a subadmin or superadmin
if(!isset($admin_id) || ($admin_role != 'subadmin' && $admin_role != 'superadmin')){
   header('location:../admin/admin_login.php');
   exit();
}

// Fetch categories from the database
$select_categories = $conn->prepare("SELECT * FROM `category`");
$select_categories->execute();
$categories = $select_categories->fetchAll(PDO::FETCH_ASSOC);

if(isset($_POST['publish'])){
   $title = $_POST['title'];
   $title = filter_var($title, FILTER_SANITIZE_STRING);
   $content = $_POST['content'];
   $content = filter_var($content, FILTER_SANITIZE_STRING);
   $category_id = $_POST['category_id'];
   $category_id = filter_var($category_id, FILTER_SANITIZE_STRING);
   $status = 'published';
   
   $image = $_FILES['image']['name'];
   $image = filter_var($image, FILTER_SANITIZE_STRING);
   $image_size = $_FILES['image']['size'];
   $image_tmp_name = $_FILES['image']['tmp_name'];
   $image_folder = '../uploaded_img/'.$image;

   $select_image = $conn->prepare("SELECT * FROM `posts` WHERE image = ? AND created_by = ?");
   $select_image->execute([$image, $admin_id]);

   if($image != ''){
      if($select_image->rowCount() > 0 && $image != ''){
         $message[] = 'Image name repeated!';
      }elseif($image_size > 2000000){
         $message[] = 'Image size is too large!';
      }else{
         move_uploaded_file($image_tmp_name, $image_folder);
      }
   }else{
      $image = '';
   }

   if($select_image->rowCount() > 0 && $image != ''){
      $message[] = 'Please rename your image!';
   }else{
      $insert_post = $conn->prepare("INSERT INTO `posts` (title, content, category_id, image, status, created_by) VALUES (?, ?, ?, ?, ?, ?)");
      $insert_post->execute([$title, $content, $category_id, $image, $status, $admin_id]);
      $message[] = 'Post published!';
   }
}

if(isset($_POST['draft'])){
   $title = $_POST['title'];
   $title = filter_var($title, FILTER_SANITIZE_STRING);
   $content = $_POST['content'];
   $content = filter_var($content, FILTER_SANITIZE_STRING);
   $category_id = $_POST['category_id']; 
   $category_id = filter_var($category_id, FILTER_SANITIZE_STRING);
   $status = 'draft';
   
   $image = $_FILES['image']['name'];
   $image = filter_var($image, FILTER_SANITIZE_STRING);
   $image_size = $_FILES['image']['size'];
   $image_tmp_name = $_FILES['image']['tmp_name'];
   $image_folder = '../uploaded_img/'.$image;

   $select_image = $conn->prepare("SELECT * FROM `posts` WHERE image = ? AND created_by = ?");
   $select_image->execute([$image, $admin_id]);

   if($image != ''){
      if($select_image->rowCount() > 0 && $image != ''){
         $message[] = 'Image name repeated!';
      }elseif($image_size > 2000000){
         $message[] = 'Image size is too large!';
      }else{
         move_uploaded_file($image_tmp_name, $image_folder);
      }
   }else{
      $image = '';
   }

   if($select_image->rowCount() > 0 && $image != ''){
      $message[] = 'Please rename your image!';
   }else{
      $insert_post = $conn->prepare("INSERT INTO `posts` (title, content, category_id, image, status, created_by) VALUES (?, ?, ?, ?, ?, ?)");
      $insert_post->execute([$title, $content, $category_id, $image, $status, $admin_id]);
      $message[] = 'Draft saved!';
      // Redirect to view_posts.php after saving draft
      header('location:view_posts.php');
      exit();
   }
}

// Add this after your existing code for publishing and drafts
if(isset($_POST['delete'])){
   $post_id = $_POST['post_id'];
   $post_id = filter_var($post_id, FILTER_SANITIZE_NUMBER_INT);
   
   try {
      // Begin transaction to ensure all operations succeed or fail together
      $conn->beginTransaction();
      
      // 1. First, get the post's image filename (for deletion if needed)
      $select_image = $conn->prepare("SELECT image FROM `posts` WHERE post_id = ? AND created_by = ?");
      $select_image->execute([$post_id, $admin_id]);
      $image = $select_image->fetch(PDO::FETCH_ASSOC);
      
      // 2. Delete all likes associated with this post
      $delete_likes = $conn->prepare("DELETE FROM `likes` WHERE post_id = ?");
      $delete_likes->execute([$post_id]);
      
      // 3. Delete all comments associated with this post
      $delete_comments = $conn->prepare("DELETE FROM `comments` WHERE post_id = ?");
      $delete_comments->execute([$post_id]);
      
      // 4. Finally delete the post itself
      $delete_post = $conn->prepare("DELETE FROM `posts` WHERE post_id = ? AND created_by = ?");
      $delete_post->execute([$post_id, $admin_id]);
      
      // If everything succeeded, commit the transaction
      $conn->commit();
      
      // If post had an image, delete the file from server
      if(isset($image['image']) && $image['image'] != ''){
         unlink('../uploaded_img/'.$image['image']);
      }
      
      $message[] = 'Post deleted successfully!';
      
   } catch (Exception $e) {
      // If any query fails, roll back the transaction
      $conn->rollBack();
      $message[] = 'Error deleting post: ' . $e->getMessage();
   }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Add Post</title>

   <!-- Font Awesome CDN -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- Custom CSS File -->
   <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<?php include '../components/admin_header.php' ?>

<section class="post-editor">
   <h1 class="heading">Add New Post</h1>

   <form action="" method="post" enctype="multipart/form-data">
      <p>Post Title <span>*</span></p>
      <input type="text" name="title" maxlength="100" required placeholder="Add post title" class="box">

      <p>Post Content <span>*</span></p>
      <textarea name="content" class="box" required maxlength="10000" placeholder="Write your content..." cols="30" rows="10"></textarea>

      <p>Post Category <span>*</span></p>
      <select name="category_id" class="box" required>
         <option value="" selected disabled>-- Select Category --</option>
         <?php
         // Fetch categories from the database and populate the dropdown
         foreach($categories as $category){
            echo '<option value="'.$category['category_id'].'">'.$category['name'].'</option>';
         }
         ?>
      </select>

      <p>Post Image</p>
      <input type="file" name="image" class="box" accept="image/jpg, image/jpeg, image/png, image/webp">

      <div class="flex-btn">
         <input type="submit" value="Publish Post" name="publish" class="btn">
         <input type="submit" value="Save Draft" name="draft" class="option-btn">
      </div>
   </form>
</section>

<!-- New Section: All Published Posts by the Subadmin -->
<section class="show-posts">
   <h1 class="heading">All Published Posts</h1>

   <div class="box-container">
      <?php
         // Fetch all published posts created by the logged-in subadmin
         $select_posts = $conn->prepare("SELECT * FROM `posts` WHERE created_by = ? AND status = 'published'");
         $select_posts->execute([$admin_id]);
         if($select_posts->rowCount() > 0){
            while($fetch_posts = $select_posts->fetch(PDO::FETCH_ASSOC)){
               $post_id = $fetch_posts['post_id'];
               $status = $fetch_posts['status'];
               $image = $fetch_posts['image'];
               $title = $fetch_posts['title'];
               $content = $fetch_posts['content'];
               $created_at = $fetch_posts['created_at'];

               // Truncate content to 2-3 sentences
               $content_preview = implode('. ', array_slice(explode('. ', $content), 0, 3)) . '.';
      ?>
      <div class="box">
         <input type="hidden" name="post_id" value="<?= $post_id; ?>">
         <?php if($image != ''){ ?>
            <img src="../uploaded_img/<?= $image; ?>" class="image" alt="">
         <?php } ?>
         <div class="status" style="background-color: limegreen;"><?= $status; ?></div>
         <div class="title"><?= $title; ?></div>
         <div class="content"><?= $content_preview; ?></div>
         <div class="date"><?= $created_at; ?></div>
         <div class="flex-btn">
            <a href="edit_post.php?post_id=<?= $post_id; ?>" class="option-btn">edit</a>
            <form action="" method="post">
               <input type="hidden" name="post_id" value="<?= $post_id; ?>">
               <button type="submit" name="delete" class="delete-btn" onclick="return confirm('delete this post?');">delete</button>
            </form>
         </div>
      </div>
      <?php
            }
         }else{
            echo '<p class="empty">No published posts added yet!</p>';
         }
      ?>
   </div>
</section>

<!-- Custom JS File -->
<script src="../js/admin_script.js"></script>
</body>
</html>