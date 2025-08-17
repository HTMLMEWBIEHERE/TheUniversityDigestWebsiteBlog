<?php
include '../components/connect.php';
$db = new Database();
$conn = $db->connect();

session_start();

// Only allow superadmin to access this page
$admin_id = $_SESSION['admin_id'] ?? null;
$admin_role = $_SESSION['admin_role'] ?? null;

if(!isset($admin_id) || $admin_role !== 'superadmin'){
   $_SESSION['message'] = 'Only superadmins can register new admins.';
   header('location:../admin/admin_login.php');
   exit();
}

// Initialize message as an array
$reg_messages = [];

if(isset($_POST['submit'])){
   // Get form data
   $firstname = $_POST['firstname'];
   $firstname = filter_var($firstname, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
   
   $lastname = $_POST['lastname'];
   $lastname = filter_var($lastname, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
   
   $middlename = $_POST['middlename'];
   $middlename = filter_var($middlename, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
   
   $username = $_POST['username'];
   $username = filter_var($username, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
   // Strip spaces from username for security
   $username = str_replace(' ', '', $username);
   
   // Validate - username must not contain spaces
   if(strpos($username, ' ') !== false) {
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
            // Validate password strength
            if(strlen($password) < 8) {
               $reg_messages[] = 'Password must be at least 8 characters long!';
            } else {
               // Hash the password securely
               $hashed_password = password_hash($password, PASSWORD_DEFAULT);
               
               // Get the role from the form (either subadmin or superadmin)
               $role = $_POST['role'];
               
               // Verify the role is valid
               if($role !== 'subadmin' && $role !== 'superadmin'){
                  $role = 'subadmin'; // Default to subadmin if invalid role
               }
               
               // Insert the new admin account
               try {
                  $insert_account = $conn->prepare("INSERT INTO `accounts`(firstname, lastname, middlename, user_name, email, password, role, image) VALUES(?,?,?,?,?,?,?,?)");
                  $insert_account->execute([$firstname, $lastname, $middlename, $username, $email, $hashed_password, $role, null]);
                  
                  $reg_messages[] = 'New ' . $role . ' registered successfully!';
                  
                  // Clear the form (JavaScript will handle this)
                  echo '<script>
                     document.addEventListener("DOMContentLoaded", function() {
                        setTimeout(function() {
                           document.querySelector("form").reset();
                        }, 1000);
                     });
                  </script>';
               } catch (PDOException $e) {
                  $reg_messages[] = 'Registration failed: ' . $e->getMessage();
               }
            }
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
   <title>Register Admin | Superadmin Panel</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="../css/admin_style.css">
   
   <style>
      /* Hide all span labels above input boxes */
      .form-container .input-box span,
      .form-container .flex .input-box span {
         display: none;
      }
      
      /* Hide note paragraphs */
      .form-container .note {
         display: none;
      }
      
      /* Enhance placeholder text since labels are gone */
      .form-container .box::placeholder {
         opacity: 1;
         color: var(--light-color);
      }
      
      /* Add more spacing between form elements */
      .form-container .input-box {
         margin-bottom: 1.5rem;
      }
   </style>
</head>
<body>

<?php include '../components/superadmin_header.php' ?>

<!-- register admin section starts  -->
<section class="form-container">
   <form action="" method="POST">
      <h3>Register New Admin Account</h3>
      
      <?php
      if(!empty($reg_messages)){
         foreach($reg_messages as $msg){
            echo '<div class="message"><span>'.$msg.'</span><i class="fas fa-times" onclick="this.parentElement.remove();"></i></div>';
         }
      }
      ?>
      
      <div class="flex">
         <div class="input-box">
            <input type="text" name="firstname" required placeholder="First Name *" class="box">
         </div>
         
         <div class="input-box">
            <input type="text" name="lastname" required placeholder="Last Name *" class="box">
         </div>
      </div>
      
      <div class="input-box">
         <input type="text" name="middlename" placeholder="Middle Name (optional)" class="box">
      </div>
      
      <div class="input-box">
         <input type="text" name="username" required placeholder="Username *" class="box" 
                oninput="this.value = this.value.replace(/\s/g, '')" 
                pattern="[^\s]+" title="Username cannot contain spaces">
      </div>
      
      <div class="input-box">
         <input type="email" name="email" required placeholder="Email Address *" class="box">
      </div>
      
      <div class="flex">
         <div class="input-box">
            <input type="password" name="password" required placeholder="Password (min. 8 characters) *" class="box" 
                  oninput="this.value = this.value.replace(/\s/g, '')">
         </div>
         
         <div class="input-box">
            <input type="password" name="cpassword" required placeholder="Confirm Password *" class="box" 
                  oninput="this.value = this.value.replace(/\s/g, '')">
         </div>
      </div>
      
      <div class="input-box">
         <select name="role" class="box" required>
            <option value="" disabled selected>Select Role *</option>
            <option value="subadmin">Subadmin</option>
            <option value="superadmin">Superadmin</option>
         </select>
      </div>
      
      <div class="flex-btn">
         <input type="submit" value="Register Admin" name="submit" class="btn">
         <a href="superadmin_dashboard.php" class="option-btn">Go Back</a>
      </div>
   </form>
</section>
<!-- register admin section ends -->

<!-- custom js file link  -->
<script src="../js/admin_script.js"></script>

</body>
</html>