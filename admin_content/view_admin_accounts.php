<?php

include '../components/connect.php';

$db = new Database();
$conn = $db->connect();

session_start();

// Get admin information from session
$admin_id = $_SESSION['admin_id'] ?? null;
$admin_role = $_SESSION['admin_role'] ?? null;

// Redirect if not logged in or not an admin
if(!isset($admin_id) || ($admin_role !== 'superadmin' && $admin_role !== 'subadmin')){
   header('location:../admin/admin_login.php');
   exit();
}

// Initialize message array to avoid potential issues with admin_header.php
$message = [];

// Fetch all admin accounts
$select_admins = $conn->prepare("SELECT * FROM `accounts` WHERE role IN ('superadmin', 'subadmin') ORDER BY role DESC, firstname ASC");
$select_admins->execute();
$admins = $select_admins->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Admin Accounts</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<?php include '../components/admin_header.php' ?>

<!-- admin accounts section starts  -->
<section class="accounts">
   <h1 class="heading">Admin Accounts</h1>
   
   <?php if(isset($message)): ?>
      <?php if(is_array($message)): ?>
         <?php foreach($message as $msg): ?>
            <div class="message">
               <span><?= $msg; ?></span>
               <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
            </div>
         <?php endforeach; ?>
      <?php else: ?>
         <div class="message">
            <span><?= $message; ?></span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
         </div>
      <?php endif; ?>
   <?php endif; ?>

   <div class="box-container">
      <?php if(count($admins) > 0): ?>
         <?php foreach($admins as $admin): ?>
            <div class="box">
               <p>Account ID: <span><?= $admin['account_id']; ?></span></p>
               <p>Username: <span><?= $admin['user_name']; ?></span></p>
               <p>Name: <span><?= $admin['firstname'] . ' ' . ($admin['middlename'] ? $admin['middlename'] . ' ' : '') . $admin['lastname']; ?></span></p>
               <p>Email: <span><?= $admin['email']; ?></span></p>
               <p>Role: <span class="status-<?= $admin['role'] === 'superadmin' ? 'published' : 'draft'; ?>"><?= strtoupper($admin['role']); ?></span></p>
               
               <?php if(isset($admin['created_at'])): ?>
                  <p>Created At: <span><?= $admin['created_at']; ?></span></p>
               <?php endif; ?>
               
               <?php if($admin['account_id'] == $admin_id): ?>
                  <div class="flex-btn">
                     <span class="option-btn">Current User</span>
                  </div>
               <?php endif; ?>
            </div>
         <?php endforeach; ?>
      <?php else: ?>
         <p class="empty">No admin accounts found!</p>
      <?php endif; ?>
   </div>
</section>
<!-- admin accounts section ends -->

<!-- custom js file link  -->
<script src="../js/admin_script.js"></script>

</body>
</html>

