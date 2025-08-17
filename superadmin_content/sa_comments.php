<?php
include '../components/connect.php';

$db = new Database();
$conn = $db->connect();

session_start();

$admin_id = $_SESSION['admin_id'] ?? null;
$admin_role = $_SESSION['admin_role'] ?? null;

// Only allow superadmin access to this page
if(!isset($admin_id) || $admin_role !== 'superadmin'){
   $_SESSION['message'] = 'Please login as a superadmin to access this content.';
   header('location:../admin/admin_login.php');
   exit(); // Stop further execution
}

// Initialize message array
$message = [];

// Get filter parameters
$filter_admin = $_GET['admin_id'] ?? 'all';
$filter_admin = ($filter_admin === 'all') ? 'all' : filter_var($filter_admin, FILTER_SANITIZE_NUMBER_INT);

// Get search parameter
$search = $_GET['search'] ?? '';
$search = filter_var($search, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// Handle comment deletion
if (isset($_POST['delete_comment'])) {
   $comment_id = $_POST['comment_id'];
   $comment_id = filter_var($comment_id, FILTER_SANITIZE_NUMBER_INT);
   
   try {
      $delete_comment = $conn->prepare("DELETE FROM `comments` WHERE comment_id = ?");
      $delete_comment->execute([$comment_id]);
      $message[] = 'Comment deleted successfully!';
   } catch (PDOException $e) {
      $message[] = 'Error: ' . $e->getMessage();
   }
}

// Get all admin accounts for the filter dropdown
$select_admins = $conn->prepare("SELECT account_id, user_name, firstname, lastname, role FROM `accounts` WHERE role IN ('superadmin', 'subadmin') ORDER BY role DESC, firstname ASC");
$select_admins->execute();
$admins = $select_admins->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>All Comments | Superadmin</title>

   <!-- Font Awesome CDN -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- Custom CSS File -->
   <link rel="stylesheet" href="../css/admin_style.css">
   
   <style>
      .filter-container {
         background-color: var(--light-bg);
         padding: 2rem;
         border-radius: 0.5rem;
         margin-bottom: 2rem;
      }
      
      .filter-form {
         display: flex;
         flex-wrap: wrap;
         gap: 1.5rem;
         align-items: flex-end;
      }
      
      .filter-form .input-box {
         flex: 1;
         min-width: 250px;
      }
      
      .filter-form label {
         display: block;
         margin-bottom: 0.5rem;
         font-size: 1.6rem;
         color: var(--black);
      }
      
      .filter-form .box,
      .filter-form select {
         width: 100%;
         padding: 1.2rem;
         font-size: 1.6rem;
         border-radius: 0.5rem;
      }
      
      .filter-form .btn {
         padding: 1.2rem 2rem;
      }
      
      .admin-tag {
         display: inline-block;
         margin-right: 1rem;
         padding: 0.3rem 0.8rem;
         border-radius: 0.4rem;
         font-size: 1.2rem;
         color: #fff;
      }
      
      .superadmin-tag {
         background-color: #e63946;
      }
      
      .subadmin-tag {
         background-color: #457b9d;
      }
      
      .post-title {
         margin-bottom: 1.5rem;
         display: flex;
         flex-wrap: wrap;
         justify-content: space-between;
         align-items: center;
         gap: 1rem;
      }
      
      .post-details {
         display: flex;
         flex-wrap: wrap;
         align-items: center;
         gap: 1rem;
         margin-bottom: 1rem;
         font-size: 1.4rem;
      }
      
      .post-creator {
         display: inline-flex;
         align-items: center;
         gap: 0.5rem;
      }
      
      .post-creator i {
         color: var(--main-color);
      }
      
      .text {
         background-color: rgba(0,0,0,0.03);
         padding: 1.5rem;
         border-radius: 0.5rem;
         margin: 1rem 0;
      }
   </style>
</head>
<body>

<?php include '../components/superadmin_header.php'; ?>

<section class="comments">
   <h1 class="heading">Manage All Comments</h1>

   <?php
   if(!empty($message)){
      foreach($message as $msg){
         echo '<div class="message"><span>'.$msg.'</span><i class="fas fa-times" onclick="this.parentElement.remove();"></i></div>';
      }
   }
   ?>
   
   <div class="filter-container">
      <form action="" method="GET" class="filter-form">
         <div class="input-box">
            <label for="admin_id">Filter by Admin:</label>
            <select name="admin_id" id="admin_id" class="box" onchange="this.form.submit()">
               <option value="all" <?= ($filter_admin === 'all') ? 'selected' : ''; ?>>All Admins</option>
               <option value="<?= $admin_id; ?>" <?= ($filter_admin == $admin_id) ? 'selected' : ''; ?>>My Posts Only</option>
               
               <?php if(count($admins) > 0): ?>
                  <optgroup label="Filter by specific admin">
                     <?php foreach($admins as $admin): ?>
                        <?php if($admin['account_id'] != $admin_id): ?>
                           <option value="<?= $admin['account_id']; ?>" <?= ($filter_admin == $admin['account_id']) ? 'selected' : ''; ?>>
                              <?= htmlspecialchars($admin['firstname'] . ' ' . $admin['lastname']); ?> 
                              (<?= htmlspecialchars($admin['user_name']); ?>) 
                              - <?= ucfirst($admin['role']); ?>
                           </option>
                        <?php endif; ?>
                     <?php endforeach; ?>
                  </optgroup>
               <?php endif; ?>
            </select>
         </div>
         
         <div class="input-box">
            <label for="search">Search Comments:</label>
            <input type="text" name="search" id="search" placeholder="Search comments..." class="box" value="<?= htmlspecialchars($search); ?>">
         </div>
         
         <button type="submit" class="btn">Apply Filters</button>
         <a href="sa_comments.php" class="option-btn">Reset Filters</a>
      </form>
   </div>
   
   <div class="box-container">
      <?php
         // Build the query based on filters
         $query = "
            SELECT c.*, p.title, p.post_id, 
                  a1.user_name AS commenter_name,
                  a2.user_name AS post_creator_name,
                  a2.firstname AS creator_firstname,
                  a2.lastname AS creator_lastname,
                  a2.role AS creator_role,
                  a2.account_id AS creator_id
            FROM `comments` c
            JOIN `posts` p ON c.post_id = p.post_id 
            JOIN `accounts` a1 ON c.commented_by = a1.account_id
            JOIN `accounts` a2 ON p.created_by = a2.account_id
            WHERE 1=1
         ";
         
         $params = [];
         
         // Add admin filter if not 'all'
         if ($filter_admin !== 'all') {
            $query .= " AND p.created_by = ?";
            $params[] = $filter_admin;
         }
         
         // Add search filter if provided
         if (!empty($search)) {
            $query .= " AND (c.comment LIKE ? OR p.title LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
         }
         
         // Add order by
         $query .= " ORDER BY c.commented_at DESC";
         
         $select_comments = $conn->prepare($query);
         $select_comments->execute($params);

         if ($select_comments->rowCount() > 0) {
            while ($fetch_comments = $select_comments->fetch(PDO::FETCH_ASSOC)) {
               $is_own_post = ($fetch_comments['creator_id'] == $admin_id);
      ?>
               <div class="box">
                  <div class="post-title">
                     <span>Post: <a href="../post.php?id=<?= $fetch_comments['post_id']; ?>"><?= htmlspecialchars($fetch_comments['title']); ?></a></span> 
                  </div>
                  
                  <div class="post-details">
                     <div class="post-creator">
                        <i class="fas fa-user-shield"></i>
                        <span>
                           Post by: 
                           <?= htmlspecialchars($fetch_comments['creator_firstname'] . ' ' . $fetch_comments['creator_lastname']); ?> 
                           (<?= htmlspecialchars($fetch_comments['post_creator_name']); ?>)
                        </span>
                        <span class="admin-tag <?= $fetch_comments['creator_role']; ?>-tag">
                           <?= ucfirst($fetch_comments['creator_role']); ?>
                        </span>
                     </div>
                  </div>
                  
                  <div class="user">
                     <i class="fas fa-user"></i>
                     <div class="user-info">
                        <span>Comment by: <?= htmlspecialchars($fetch_comments['commenter_name']); ?></span>
                        <div><?= date('M d, Y \a\t h:i A', strtotime($fetch_comments['commented_at'])); ?></div>
                     </div>
                  </div>
                  
                  <div class="text"><?= htmlspecialchars($fetch_comments['comment']); ?></div>
                  
                  <form action="" method="POST">
                     <input type="hidden" name="comment_id" value="<?= $fetch_comments['comment_id']; ?>">
                     <button type="submit" class="inline-delete-btn" name="delete_comment" onclick="return confirm('Delete this comment? This action cannot be undone.');">Delete Comment</button>
                  </form>
               </div>
      <?php
            }
         } else {
            if ($filter_admin !== 'all') {
               $admin_name = 'Selected admin';
               
               if ($filter_admin == $admin_id) {
                  $admin_name = 'your posts';
               } else {
                  foreach ($admins as $admin) {
                     if ($admin['account_id'] == $filter_admin) {
                        $admin_name = htmlspecialchars($admin['firstname'] . ' ' . $admin['lastname']);
                        break;
                     }
                  }
               }
               
               echo '<p class="empty">No comments found on ' . $admin_name . '\'s posts' . (!empty($search) ? ' matching your search' : '') . '.</p>';
            } else {
               echo '<p class="empty">No comments found' . (!empty($search) ? ' matching your search' : '') . '.</p>';
            }
         }
      ?>
   </div>
</section>

<!-- Custom JS File -->
<script src="../js/admin_script.js"></script>
</body>
</html>