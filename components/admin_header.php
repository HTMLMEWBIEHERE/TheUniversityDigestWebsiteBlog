<?php
// Make sure we have a connection
$db = new Database();
$conn = $db->connect();

// Check if message exists and is an array before trying to iterate
if(isset($message)){
   if(is_array($message)){
      foreach($message as $msg){
         echo '
         <div class="message">
            <span>'.$msg.'</span>
            <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
         </div>
         ';
      }
   } else {
      // Handle case where message is a string
      echo '
      <div class="message">
         <span>'.$message.'</span>
         <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
      </div>
      ';
   }
}

// Make sure admin_id and admin_role are defined
$admin_id = $_SESSION['admin_id'] ?? null;
$admin_role = $_SESSION['admin_role'] ?? null;

// Only show header content if properly logged in
$is_logged_in = isset($admin_id) && ($admin_role === 'superadmin' || $admin_role === 'subadmin');
?>

<header class="header">
   <?php if($is_logged_in): ?>
   <a href="../admin/dashboard.php" class="logo">Admin<span>Panel</span></a>

   <div class="profile">
      <?php
         // Add error checking for the profile query
         $select_profile = $conn->prepare("SELECT * FROM `accounts` WHERE account_id = ?");
         $select_profile->execute([$admin_id]);
         $fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);
         
         // Only display profile info if found
         if($fetch_profile) {
      ?>
      <!-- Add profile image with correct path -->
      <div class="profile-image">
         <?php if(!empty($fetch_profile['image'])): ?>
            <img src="../uploads/profiles/<?= htmlspecialchars($fetch_profile['image']); ?>" 
                 alt="Profile Image"
                 onerror="this.src='../imgs/default-avatar.png'; this.onerror=null;">
         <?php else: ?>
            <img src="../imgs/default-avatar.png" alt="Default Profile">
         <?php endif; ?>
      </div>
      <p><?= $fetch_profile['firstname'] . ' ' . $fetch_profile['lastname']; ?></p>
      <a href="../admin/update_profile.php" class="btn">update profile</a>
      <?php 
         } else {
            // Display a default message if profile not found
            echo "<p>Account not found</p>";
         }
      ?>
   </div>

   <nav class="navbar">
      <a href="../admin/dashboard.php"><i class="fas fa-chart-line"></i> <span>Dashboard</span></a>
      <a href="../admin/subadmin_analytics.php"><i class="fas fa-tools"></i> <span>Control Panel</span></a>
      <a href="../admin_content/add_posts.php"><i class="fas fa-pen"></i> <span>Manage Posts</span></a>
      <a href="../admin_content/view_posts.php"><i class="fas fa-eye"></i> <span>View Drafts</span></a>
      <a href="../admin_content/activity_log.php"><i class="fas fa-user"></i> <span>Activity Log</span></a>
      <?php if($admin_role === 'superadmin' || $admin_role === 'subadmin'): ?>
      <a href="../superadmin_content/sa_site_settings.php"><i class="fas fa-cogs"></i> <span>Site Settings</span></a>
      <?php endif; ?>
      <a href="../components/admin_logout.php?redirect=home" style="color:var(--red);" onclick="return confirm('logout from the website?');"><i class="fas fa-right-from-bracket"></i><span>logout</span></a>
   </nav>

   <div class="flex-btn">
      <a href="../admin_content/view_admin_accounts.php" class="option-btn">Accounts</a>
      <a href="../user_content/home.php" class="option-btn">Back to Home</a>
   </div>
   
   <?php else: ?>
   <!-- Simple header for non-authenticated users -->
   <a href="../admin/admin_login.php" class="logo">Admin<span>Panel</span></a>
   <div class="auth-message">
      <p>Please <a href="../admin/admin_login.php">login</a> to access the admin area</p>
   </div>
   <?php endif; ?>
</header>

<?php if($is_logged_in): ?>
<div id="menu-btn" class="fas fa-bars"></div>
<?php endif; ?>

<style>
.auth-message {
   padding: 20px;
   text-align: center;
}
.auth-message p {
   color: #666;
}
.auth-message a {
   color: var(--main-color);
   font-weight: bold;
}
</style>