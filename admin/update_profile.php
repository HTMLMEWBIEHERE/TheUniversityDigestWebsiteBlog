<?php
include '../components/connect.php';

$db = new Database();
$conn = $db->connect();

session_start();

// Initialize message as array from the beginning
$message = [];

if(isset($_SESSION['admin_id'])){
   $admin_id = $_SESSION['admin_id'];
} else {
   header('location:admin_login.php');
   exit();
}

// Fetch current profile data
$select_profile = $conn->prepare("SELECT * FROM `accounts` WHERE account_id = ?");
$select_profile->execute([$admin_id]);
$fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);

// Get current image if exists
$current_image = $fetch_profile['image'] ?? '';

if(isset($_POST['submit'])){
   // Get and sanitize input data
   $firstname = trim($_POST['firstname']);
   $lastname = trim($_POST['lastname']);
   $middlename = trim($_POST['middlename']);
   $user_name = trim($_POST['user_name']);
   $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);

   // Check if username or email already exists
   if(!empty($user_name)){
      $select_name = $conn->prepare("SELECT * FROM `accounts` WHERE user_name = ? AND account_id != ?");
      $select_name->execute([$user_name, $admin_id]);
      if($select_name->rowCount() > 0){
         $message[] = 'Username already taken!';
      }
   }

   if(!empty($email)){
      $select_email = $conn->prepare("SELECT * FROM `accounts` WHERE email = ? AND account_id != ?");
      $select_email->execute([$email, $admin_id]);
      if($select_email->rowCount() > 0){
         $message[] = 'Email already taken!';
      }
   }

   // Handle profile image upload
   $image = $current_image; // Default to current image
   
   if(isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
      $image_name = $_FILES['image']['name'];
      $image_tmp_name = $_FILES['image']['tmp_name'];
      $image_size = $_FILES['image']['size'];
      $image_error = $_FILES['image']['error'];
      
      if($image_error === 0) {
         // Check file size (5MB max)
         if($image_size > 5000000) {
            $message[] = 'Image is too large! Max size is 5MB.';
         } else {
            $image_ext = pathinfo($image_name, PATHINFO_EXTENSION);
            $image_ext_lc = strtolower($image_ext);
            
            // Allowed extensions
            $allowed_exts = array('jpg', 'jpeg', 'png', 'gif');
            
            if(in_array($image_ext_lc, $allowed_exts)) {
               // Generate unique filename
               $new_image_name = uniqid('profile_') . '.' . $image_ext_lc;
               $image_upload_path = '../uploads/profiles/' . $new_image_name;
               
               // Make sure uploads directory exists
               if(!is_dir('../uploads/profiles/')) {
                  mkdir('../uploads/profiles/', 0777, true);
               }
               
               // Move the uploaded file
               if(move_uploaded_file($image_tmp_name, $image_upload_path)) {
                  // Delete old image if it exists and is not the default
                  if(!empty($current_image) && $current_image != 'default.jpg' && file_exists('../uploads/profiles/'.$current_image)) {
                     unlink('../uploads/profiles/'.$current_image);
                  }
                  
                  $image = $new_image_name;
               } else {
                  $message[] = 'Failed to upload image!';
               }
            } else {
               $message[] = 'Invalid file type! Only JPG, JPEG, PNG and GIF are allowed.';
            }
         }
      } else {
         $message[] = 'Error uploading image!';
      }
   }

   // Update profile if no errors
   if(empty($message)){
      $update_profile = $conn->prepare("UPDATE `accounts` SET firstname = ?, lastname = ?, middlename = ?, user_name = ?, email = ?, image = ? WHERE account_id = ?");
      $update_profile->execute([$firstname, $lastname, $middlename, $user_name, $email, $image, $admin_id]);
      $message[] = 'Profile updated successfully!';
      
      // Refresh profile data after update
      $select_profile->execute([$admin_id]);
      $fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);
      $current_image = $fetch_profile['image'] ?? '';
   }

   // Handle password update
   if(!empty($_POST['old_pass']) || !empty($_POST['new_pass']) || !empty($_POST['confirm_pass'])){
      $old_pass = $_POST['old_pass'];
      $new_pass = $_POST['new_pass'];
      $confirm_pass = $_POST['confirm_pass'];

      if(empty($old_pass) || empty($new_pass) || empty($confirm_pass)){
         $message[] = 'Please fill out all password fields!';
      } else {
         // First check if password is correct
         $password_verified = false;
         
         // Try different password formats
         if(password_verify($old_pass, $fetch_profile['password'])){
            $password_verified = true;
         } 
         else if(sha1($old_pass) === $fetch_profile['password']){
            $password_verified = true;
         }
         else if(md5($old_pass) === $fetch_profile['password']){
            $password_verified = true;
         }
         
         if($password_verified){
            if($new_pass !== $confirm_pass){
               $message[] = 'New password and confirm password do not match!';
            } else {
               // Use modern password hashing
               $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
               $update_pass = $conn->prepare("UPDATE `accounts` SET password = ? WHERE account_id = ?");
               $update_pass->execute([$hashed_pass, $admin_id]);
               $message[] = 'Password updated successfully!';
            }
         } else {
            $message[] = 'Old password is incorrect!';
         }
      }
   }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Update Profile</title>

   <!-- Font Awesome CDN -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- Custom CSS File -->
   <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<?php include '../components/admin_header.php' ?>

<!-- Profile Update Section Starts -->
<section class="form-container">
   <?php
   // CRITICAL FIX: This is where the error was happening
   if(isset($message) && is_array($message) && !empty($message)){
      foreach($message as $msg){
         echo '<div class="message"><span>'.$msg.'</span><i class="fas fa-times" onclick="this.parentElement.remove();"></i></div>';
      }
   }
   ?>

   <form action="" method="POST" enctype="multipart/form-data">
      <h3>Update Profile</h3>

      <div class="profile-image-container">
         <img src="<?= !empty($current_image) ? '../uploads/profiles/'.htmlspecialchars($current_image) : '../uploads/profiles/default.jpg'; ?>" alt="Profile Picture" class="profile-image" id="profile-preview">
         <label for="image" class="image-upload-label">
            <i class="fas fa-camera"></i> Change Profile Picture
         </label>
         <input type="file" name="image" id="image" class="image-upload" accept="image/*" onchange="previewImage(this)">
      </div>

      <input type="text" name="firstname" maxlength="50" class="box" placeholder="First Name" value="<?= htmlspecialchars($fetch_profile['firstname'] ?? ''); ?>">
      <input type="text" name="lastname" maxlength="50" class="box" placeholder="Last Name" value="<?= htmlspecialchars($fetch_profile['lastname'] ?? ''); ?>">
      <input type="text" name="middlename" maxlength="50" class="box" placeholder="Middle Name" value="<?= htmlspecialchars($fetch_profile['middlename'] ?? ''); ?>">
      <input type="text" name="user_name" maxlength="20" class="box" placeholder="Username" value="<?= htmlspecialchars($fetch_profile['user_name'] ?? ''); ?>">
      <input type="email" name="email" maxlength="255" class="box" placeholder="Email" value="<?= htmlspecialchars($fetch_profile['email'] ?? ''); ?>">

      <input type="password" name="old_pass" maxlength="20" placeholder="Enter your old password" class="box">
      <input type="password" name="new_pass" maxlength="20" placeholder="Enter your new password" class="box">
      <input type="password" name="confirm_pass" maxlength="20" placeholder="Confirm your new password" class="box">

      <input type="submit" value="Update Now" name="submit" class="btn">
   </form>
</section>
<!-- Profile Update Section Ends -->

<!-- Custom JS File -->
<script src="../js/admin_script.js"></script>
<script>
function previewImage(input) {
    const preview = document.getElementById('profile-preview');
    const file = input.files[0];
    
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
        }
        reader.readAsDataURL(file);
    }
}
</script>
</body>
</html>