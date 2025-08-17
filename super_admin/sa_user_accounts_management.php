<?php
// Include the database connection file
include '../components/connect.php';

$db = new Database();
$conn = $db->connect();

// Start the session
session_start();

// Check if the admin is logged in and is a superadmin
$admin_id = $_SESSION['admin_id'] ?? null;
$admin_role = $_SESSION['admin_role'] ?? null;

// Only allow superadmin access to this page
if(!isset($admin_id) || $admin_role !== 'superadmin'){
   $_SESSION['message'] = 'Please login as a superadmin to access this content.';
   header('location:../admin/admin_login.php');
   exit(); // Stop further execution
}

// Initialize $message as an array
$message = [];

// Display session message if it exists
if(isset($_SESSION['message'])) {
   $message[] = $_SESSION['message'];
   unset($_SESSION['message']);
}

// Delete user
if (isset($_GET['delete'])) {
   $delete_id = $_GET['delete'];
   
   // Check if user exists before deleting
   $check_user = $conn->prepare("SELECT * FROM `accounts` WHERE account_id = ? AND role = 'user'");
   $check_user->execute([$delete_id]);
   
   if($check_user->rowCount() > 0) {
      try {
         // Begin transaction
         $conn->beginTransaction();
         
         // 1. Find all posts where this user commented and the comment count
         $find_posts = $conn->prepare("
            SELECT post_id, COUNT(*) as comment_count 
            FROM comments 
            WHERE commented_by = ? 
            GROUP BY post_id
         ");
         $find_posts->execute([$delete_id]);
         $affected_posts = $find_posts->fetchAll(PDO::FETCH_ASSOC);
         
         // 2. Find all posts this user liked
         $find_liked_posts = $conn->prepare("
            SELECT post_id FROM likes WHERE account_id = ?
         ");
         $find_liked_posts->execute([$delete_id]);
         $liked_posts = $find_liked_posts->fetchAll(PDO::FETCH_COLUMN);
         
         // 3. Check if posts table has comments_count column
         $check_column = $conn->prepare("SHOW COLUMNS FROM posts LIKE 'comments_count'");
         $check_column->execute();
         
         if($check_column->rowCount() > 0) {
            // If comments_count column exists, update it
            foreach ($affected_posts as $post) {
               $update_count = $conn->prepare("
                  UPDATE posts 
                  SET comments_count = comments_count - ? 
                  WHERE post_id = ?
               ");
               $update_count->execute([$post['comment_count'], $post['post_id']]);
            }
         }
         
         // 4. Check if posts table has likes_count column
         $check_likes_column = $conn->prepare("SHOW COLUMNS FROM posts LIKE 'likes_count'");
         $check_likes_column->execute();
         
         if($check_likes_column->rowCount() > 0) {
            // If likes_count column exists, decrement for each liked post
            foreach ($liked_posts as $post_id) {
               $update_likes = $conn->prepare("
                  UPDATE posts 
                  SET likes_count = likes_count - 1 
                  WHERE post_id = ?
               ");
               $update_likes->execute([$post_id]);
            }
         }
         
         // 5. Delete the user's comments
         $delete_comments = $conn->prepare("DELETE FROM comments WHERE commented_by = ?");
         $delete_comments->execute([$delete_id]);
         
         // 6. Delete the user's likes (although ON DELETE CASCADE should handle this)
         $delete_likes = $conn->prepare("DELETE FROM likes WHERE account_id = ?");
         $delete_likes->execute([$delete_id]);
         
         // 7. Delete the user
         $delete_user = $conn->prepare("DELETE FROM `accounts` WHERE account_id = ? AND role = 'user'");
         $delete_user->execute([$delete_id]);
         
         $conn->commit();
         $message[] = 'User and all their content (comments, likes) deleted successfully!';
      } catch (PDOException $e) {
         $conn->rollBack();
         $message[] = 'Error deleting user: ' . $e->getMessage();
      }
   } else {
      $message[] = 'User not found or you do not have permission to delete!';
   }
}

// Search functionality
$search = $_GET['search'] ?? '';
$search_term = '%' . $search . '%';

// Fetch users with search filter if provided
if (!empty($search)) {
    $select_users = $conn->prepare("SELECT * FROM `accounts` 
                                   WHERE role = 'user' 
                                   AND (user_name LIKE ? 
                                   OR email LIKE ? 
                                   OR firstname LIKE ? 
                                   OR lastname LIKE ?)
                                   ORDER BY account_id DESC");
    $select_users->execute([$search_term, $search_term, $search_term, $search_term]);
} else {
    $select_users = $conn->prepare("SELECT * FROM `accounts` WHERE role = 'user' ORDER BY account_id DESC");
    $select_users->execute();
}

$users = $select_users->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>User Management | Superadmin</title>

   <!-- Font Awesome CDN -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- Custom CSS File -->
   <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<?php include '../components/superadmin_header.php'; ?>

<section class="user-management">
   <h1 class="heading">User Management</h1>

   <?php
   if (isset($message) && is_array($message)) {
      foreach ($message as $msg) {
         echo '
         <div class="message">
            <span>'.$msg.'</span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
         </div>
         ';
      }
   }
   ?>

   <!-- Search form -->
   <div class="search-form-container">
      <form action="" method="GET" class="search-form">
         <input type="text" name="search" placeholder="Search by name, username or email..." value="<?= htmlspecialchars($search) ?>">
         <button type="submit" class="search-btn"><i class="fas fa-search"></i> Search</button>
         <?php if(!empty($search)): ?>
            <a href="sa_user_accounts_management.php" class="reset-btn"><i class="fas fa-undo"></i> Reset</a>
         <?php endif; ?>
      </form>
      <p class="result-count"><?= count($users) ?> user(s) found</p>
   </div>

   <div class="box-container">
      <?php if (count($users) > 0): ?>
         <?php foreach ($users as $user): ?>
            <div class="box">
               <!-- Removed profile image section -->
               
               <p>User ID: <span><?= $user['account_id']; ?></span></p>
               <p>Name: <span><?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></span></p>
               <p>Username: <span><?= htmlspecialchars($user['user_name']); ?></span></p>
               <p>Email: <span><?= htmlspecialchars($user['email']); ?></span></p>
               <p>Role: <span><?= $user['role']; ?></span></p>
               <a href="sa_user_accounts_management.php?delete=<?= $user['account_id']; ?>" class="delete-btn" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">Delete User</a>
            </div>
         <?php endforeach; ?>
      <?php else: ?>
         <p class="empty">No users found!</p>
      <?php endif; ?>
   </div>
</section>

<script src="../js/admin_script.js"></script>
</body>
</html>