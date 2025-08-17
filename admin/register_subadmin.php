<?php

include '../components/connect.php';
$db = new Database();
$conn = $db->connect();

session_start();

// Allow both superadmin and subadmin to access this page
$admin_id = $_SESSION['admin_id'] ?? null;
$admin_role = $_SESSION['admin_role'] ?? null;

if(!isset($admin_id) || ($admin_role !== 'superadmin' && $admin_role !== 'subadmin')){
   header('location:admin_login.php');
   exit();
}

// Fix 1: Initialize message as an array and ensure it's not overwritten
$reg_messages = [];  // Changed from array() to []

if(isset($_POST['submit'])){
   // Get form data
   $firstname = $_POST['firstname'];
   $firstname = filter_var($firstname, FILTER_SANITIZE_STRING);
   
   $lastname = $_POST['lastname'];
   $lastname = filter_var($lastname, FILTER_SANITIZE_STRING);
   
   $middlename = $_POST['middlename'];
   $middlename = filter_var($middlename, FILTER_SANITIZE_STRING);
   
   $username = $_POST['username'];
   $username = filter_var($username, FILTER_SANITIZE_STRING);
   // Strip spaces from username for security
   $username = str_replace(' ', '', $username);
   
   // Validate - username must not contain spaces
   if(strpos($username, ' ') !== false) {
      // Fix 2: Always use array syntax for adding messages
      $reg_messages[] = 'Username cannot contain spaces!';
   } else {
      $email = $_POST['email'];
      $email = filter_var($email, FILTER_SANITIZE_EMAIL);
      
      $password = $_POST['password'];
      $cpassword = $_POST['cpassword'];
      
      // Check if username or email already exists
      $check_account = $conn->prepare("SELECT * FROM `accounts` WHERE user_name = ? OR email = ?");
      $check_account->execute([$username, $email]);
      
      if($check_account->rowCount() > 0){
         $reg_messages[] = 'Username or email already exists!';
      } else {
         // Check if passwords match
         if($password !== $cpassword){
            $reg_messages[] = 'Confirm password does not match!';
         } else {
            // Hash the password securely
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Set role as subadmin
            $role = 'subadmin';
            
            // Default image can be null or a placeholder
            $image = null;
            
            // Insert the new subadmin account
            $insert_account = $conn->prepare("INSERT INTO `accounts`(firstname, lastname, middlename, user_name, email, password, role, image) VALUES(?,?,?,?,?,?,?,?)");
            $insert_account->execute([$firstname, $lastname, $middlename, $username, $email, $hashed_password, $role, $image]);
            
            if($insert_account){
               $reg_messages[] = 'New subadmin registered successfully!';
            } else {
               $reg_messages[] = 'Registration failed! Please try again.';
            }
         }
      }
   }
}

// Debug code to verify $message is an array (remove in production)
// echo "<pre>DEBUG: "; var_dump($reg_messages); echo "</pre>";
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Register Subadmin</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="../css/admin_style.css">

</head>
<body>

<?php include '../components/admin_header.php' ?>

<!-- register subadmin section starts  -->
<section class="form-container">
   <form action="" method="POST">
      <h3>Register New Subadmin</h3>
      
      <?php
      // Fix 3: Simplify the check and ensure $message is treated as an array
      if(!empty($reg_messages)){
         foreach($reg_messages as $msg){
            echo '<div class="message"><span>'.$msg.'</span><i class="fas fa-times" onclick="this.parentElement.remove();"></i></div>';
         }
      }
      ?>
      
      <input type="text" name="firstname" required placeholder="First name" class="box">
      <input type="text" name="lastname" required placeholder="Last name" class="box">
      <input type="text" name="middlename" placeholder="Middle name (optional)" class="box">
      <input type="text" name="username" required placeholder="Username" class="box" 
             oninput="this.value = this.value.replace(/\s/g, '')" 
             pattern="[^\s]+" title="Username cannot contain spaces">
      <input type="email" name="email" required placeholder="Email" class="box">
      <input type="password" name="password" required placeholder="Password" class="box" oninput="this.value = this.value.replace(/\s/g, '')">
      <input type="password" name="cpassword" required placeholder="Confirm password" class="box" oninput="this.value = this.value.replace(/\s/g, '')">
      <input type="submit" value="Register Subadmin" name="submit" class="btn">
      <a href="dashboard.php" class="option-btn">Go Back</a>
   </form>
</section>
<!-- register subadmin section ends -->

<!-- custom js file link  -->
<script src="../js/admin_script.js"></script>

</body>
</html>