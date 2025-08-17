<?php
// Include the database connection file
include '../components/connect.php';

$db = new Database();
$conn = $db->connect();

// Start the session
session_start();

// Check if the admin is logged in
$admin_id = $_SESSION['admin_id'] ?? null;

if (!isset($admin_id)) {
   header('location:admin_login.php');
   exit(); // Stop further execution
}

// Initialize $message as an array
$message = [];

// Delete user
if (isset($_GET['delete'])) {
   $delete_id = $_GET['delete'];
   $delete_user = $conn->prepare("DELETE FROM `accounts` WHERE account_id = ?");
   $delete_user->execute([$delete_id]);
   $message[] = 'User deleted successfully!'; // Add message to the array
}

// Fetch all users
$select_users = $conn->prepare("SELECT * FROM `accounts` WHERE role = 'user'");
$select_users->execute();
$users = $select_users->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>User Management</title>

   <!-- Font Awesome CDN -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- Custom CSS File -->
   <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<?php include '../components/admin_header.php'; ?>

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

   <div class="box-container">
      <?php if (count($users) > 0): ?>
         <?php foreach ($users as $user): ?>
            <div class="box">
               <p>User ID: <span><?= $user['account_id']; ?></span></p>
               <p>Name: <span><?= $user['firstname'] . ' ' . $user['lastname']; ?></span></p>
               <p>Username: <span><?= $user['user_name']; ?></span></p>
               <p>Email: <span><?= $user['email']; ?></span></p>
               <p>Role: <span><?= $user['role']; ?></span></p>
               <a href="user_accounts_management.php?delete=<?= $user['account_id']; ?>" class="btn" onclick="return confirm('Are you sure you want to delete this user?');">Delete User</a>
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