<?php
session_start(); // Add this line to fix the redirection issue

include '../components/connect.php'; // Fixed path

$db = new Database();
$conn = $db->connect();

// Use account_id instead of user_id to match your other files
if(isset($_SESSION['account_id'])){
   $account_id = $_SESSION['account_id'];
}else{
   $account_id = '';
   header('location:home.php');
   exit;
}

// Fetch current user data
$select_profile = $conn->prepare("SELECT * FROM `accounts` WHERE account_id = ?");
$select_profile->execute([$account_id]);
$fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);

$message = [];

if(isset($_POST['submit'])){
   // For username update
   $username = $_POST['username'] ?? '';
   $username = filter_var($username, FILTER_SANITIZE_STRING);
   
   // For firstname and lastname update
   $firstname = $_POST['firstname'] ?? '';
   $firstname = filter_var($firstname, FILTER_SANITIZE_STRING);
   
   $lastname = $_POST['lastname'] ?? '';
   $lastname = filter_var($lastname, FILTER_SANITIZE_STRING);

   $email = $_POST['email'] ?? '';
   $email = filter_var($email, FILTER_SANITIZE_STRING);
   
   // Update username if provided
   if(!empty($username) && $username != $fetch_profile['user_name']){
      // Check if username already exists
      $select_username = $conn->prepare("SELECT * FROM `accounts` WHERE user_name = ? AND account_id != ?");
      $select_username->execute([$username, $account_id]);
      if($select_username->rowCount() > 0){
         $message[] = 'Username already taken!';
      }else{
         $update_username = $conn->prepare("UPDATE `accounts` SET user_name = ? WHERE account_id = ?");
         $update_username->execute([$username, $account_id]);
         $_SESSION['user_name'] = $username; // Update session
         $message[] = 'Username updated successfully!';
      }
   }
   
   // Update firstname if provided
   if(!empty($firstname) && $firstname != $fetch_profile['firstname']){
      $update_firstname = $conn->prepare("UPDATE `accounts` SET firstname = ? WHERE account_id = ?");
      $update_firstname->execute([$firstname, $account_id]);
      $message[] = 'First name updated successfully!';
   }
   
   // Update lastname if provided
   if(!empty($lastname) && $lastname != $fetch_profile['lastname']){
      $update_lastname = $conn->prepare("UPDATE `accounts` SET lastname = ? WHERE account_id = ?");
      $update_lastname->execute([$lastname, $account_id]);
      $message[] = 'Last name updated successfully!';
   }

   // Handle password update
   $empty_pass = 'da39a3ee5e6b4b0d3255bfef95601890afd80709'; // SHA1 of empty string
   $old_pass = sha1($_POST['old_pass'] ?? '');
   $old_pass = filter_var($old_pass, FILTER_SANITIZE_STRING);
   $new_pass = sha1($_POST['new_pass'] ?? '');
   $new_pass = filter_var($new_pass, FILTER_SANITIZE_STRING);
   $confirm_pass = sha1($_POST['confirm_pass'] ?? '');
   $confirm_pass = filter_var($confirm_pass, FILTER_SANITIZE_STRING);

   if($old_pass != $empty_pass){
      $select_prev_pass = $conn->prepare("SELECT password FROM `accounts` WHERE account_id = ?");
      $select_prev_pass->execute([$account_id]);
      $fetch_prev_pass = $select_prev_pass->fetch(PDO::FETCH_ASSOC);
      $prev_pass = $fetch_prev_pass['password'];
      
      if($old_pass != $prev_pass){
         $message[] = 'Old password not matched!';
      }elseif($new_pass != $confirm_pass){
         $message[] = 'Confirm password not matched!';
      }else{
         if($new_pass != $empty_pass){
            $update_pass = $conn->prepare("UPDATE `accounts` SET password = ? WHERE account_id = ?");
            $update_pass->execute([$confirm_pass, $account_id]);
            $message[] = 'Password updated successfully!';
         }else{
            $message[] = 'Please enter a new password!';
         }
      }
   }
   
   // Reload profile data after updates
   $select_profile = $conn->prepare("SELECT * FROM `accounts` WHERE account_id = ?");
   $select_profile->execute([$account_id]);
   $fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Update Profile</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link - fixed paths -->
   <link rel="stylesheet" href="../css/style.css">
   <link rel="stylesheet" href="../css/userheader.css">
   <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
   <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
   
<!-- header section starts  -->
<?php include '../components/user_header.php'; ?> <!-- Fixed path -->
<!-- header section ends -->

<section class="profile-update-container">
   <form action="" method="post" class="profile-form">
      <h3 class="profile-heading">Update Profile</h3>
      
      <!-- Current profile info -->
      <div class="current-profile-details">
         <p><strong>Current Username:</strong> <?= htmlspecialchars($fetch_profile['user_name'] ?? ''); ?></p>
         <p><strong>Current Name:</strong> <?= htmlspecialchars($fetch_profile['firstname'] ?? '') . ' ' . htmlspecialchars($fetch_profile['lastname'] ?? ''); ?></p>
         <p><strong>Current Email:</strong> <?= htmlspecialchars($fetch_profile['email'] ?? ''); ?></p>
      </div>
      
      <?php
         // Display any messages
         if(isset($message) && !empty($message)){
            foreach($message as $msg){
               echo '<div class="profile-update-message">'.$msg.'</div>';
            }
         }
      ?>
      
      <!-- Username update -->
      <label class="profile-input-label">Username:</label>
      <input type="text" name="username" placeholder="Enter new username" class="profile-input-field" maxlength="50">
      
      <!-- First name update -->
      <label class="profile-input-label">First Name:</label>
      <input type="text" name="firstname" placeholder="Enter new first name" class="profile-input-field" maxlength="50">
      
      <!-- Last name update -->
      <label class="profile-input-label">Last Name:</label>
      <input type="text" name="lastname" placeholder="Enter new last name" class="profile-input-field" maxlength="50">
      
      <h3 class="profile-heading password-heading">Change Password</h3>
      
      <label class="profile-input-label">Old Password:</label>
      <input type="password" name="old_pass" placeholder="Enter your old password" class="profile-input-field" maxlength="50" oninput="this.value = this.value.replace(/\s/g, '')">
      
      <label class="profile-input-label">New Password:</label>
      <input type="password" name="new_pass" placeholder="Enter your new password" class="profile-input-field" maxlength="50" oninput="this.value = this.value.replace(/\s/g, '')">
      
      <label class="profile-input-label">Confirm Password:</label>
      <input type="password" name="confirm_pass" placeholder="Confirm your new password" class="profile-input-field" maxlength="50" oninput="this.value = this.value.replace(/\s/g, '')">
      
      <div class="profile-button-group">
         <input type="submit" value="Update Profile" name="submit" class="profile-update-btn">
         <a href="home.php" class="profile-cancel-btn">Go Back</a>
      </div>
   </form>
</section>

<?php include '../components/footer.php'; ?> <!-- Fixed path -->

<!-- Custom JS file link -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="../js/script.js"></script> <!-- Fixed path -->

</body>
</html>