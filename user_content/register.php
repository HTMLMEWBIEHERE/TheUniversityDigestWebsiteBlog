<?php
include '../components/connect.php';

require '../vendor/autoload.php';
require_once '../classes/email_helper.class.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$db = new Database();
$conn = $db->connect();

session_start();

if(isset($_SESSION['account_id'])){
   header('location:home.php');
   exit();
}

if(isset($_POST['submit'])){
   $firstname = trim($_POST['firstname']);
   $lastname = trim($_POST['lastname']);
   $middlename = trim($_POST['middlename'] ?? '');
   $username = trim($_POST['username']);
   $email = strtolower(trim($_POST['email']));
   $password = $_POST['password'];
   $cpassword = $_POST['cpassword'];
   
   // Validation
   $message = [];
   
   // Check if username exists
   $check_username = $conn->prepare("SELECT * FROM `accounts` WHERE user_name = ?");
   $check_username->execute([$username]);
   
   if($check_username->rowCount() > 0){
      $message[] = 'Username already exists!';
   }
   
   // Check if email exists
   $check_email = $conn->prepare("SELECT * FROM `accounts` WHERE email = ?");
   $check_email->execute([$email]);
   
   if($check_email->rowCount() > 0){
      $message[] = 'Email already exists!';
   }
   
   // Password validation
   if(strlen($password) < 8){
      $message[] = 'Password must be at least 8 characters long!';
   }
   
   if($password !== $cpassword){
      $message[] = 'Passwords do not match!';
   }
   
   // If no errors, register the user
   if(empty($message)){
      $hashed_password = password_hash($password, PASSWORD_DEFAULT);
      $role = 'user';
      $verification_code = bin2hex(random_bytes(16));

      // Insert new user with verification code and is_verified = 0
      $insert_user = $conn->prepare("INSERT INTO `accounts` (firstname, lastname, middlename, user_name, email, password, role, verification_code, is_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)");
      $insert_user->execute([$firstname, $lastname, $middlename, $username, $email, $hashed_password, $role, $verification_code]);

      if($insert_user){
         // Send verification email using the EmailHelper class
         try {
            EmailHelper::sendVerificationEmail($conn, $email, $firstname, $lastname, $verification_code);
            $message[] = 'Registration successful! Please check your email to verify your account. <br>If you did not receive the email, you can <a href="resend_verification.php">request a new verification email</a>.';
         } catch (Exception $e) {
            $message[] = "Message could not be sent. Mailer Error: {$e->getMessage()}";
         }
      }
   }
}

// Check for Google auth error messages
if(isset($_SESSION['message'])) {
   $message[] = $_SESSION['message'];
   unset($_SESSION['message']);
}

if(isset($_SESSION['google_error'])) {
   $message[] = 'Google login error: ' . $_SESSION['google_error'];
   unset($_SESSION['google_error']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Register - TDG Project</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link rel="stylesheet" href="css/style.css">
   <link rel="stylesheet" href="../css/style.css">
   <style>
      .form-container {
         margin-top: 120px;
      }
   </style>
</head>
<body>
   
<?php include '../components/user_header.php'; ?>

<section class="form-container">
   <form action="" method="post">
      <h3>Create an Account</h3>
      
      <?php if(!empty($message)): ?>
      <div class="message">
         <?php foreach($message as $msg): ?>
            <p><?= $msg; ?></p>
         <?php endforeach; ?>
      </div>
      <?php endif; ?>
      
      <div class="input-group">
         <input type="text" name="firstname" required placeholder="First Name" class="box" value="<?= $firstname ?? ''; ?>">
      </div>
      <div class="input-group">
         <input type="text" name="lastname" required placeholder="Last Name" class="box" value="<?= $lastname ?? ''; ?>">
      </div>
      <div class="input-group">
         <input type="text" name="middlename" placeholder="Middle Name (optional)" class="box" value="<?= $middlename ?? ''; ?>">
      </div>
      <div class="input-group">
         <input type="text" name="username" required placeholder="Username" class="box" value="<?= $username ?? ''; ?>">
      </div>
      <div class="input-group">
         <input type="email" name="email" required placeholder="Email Address" class="box" value="<?= $email ?? ''; ?>">
      </div>
      <div class="input-group">
         <input type="password" name="password" required placeholder="Password (min. 8 characters)" class="box">
      </div>
      <div class="input-group">
         <input type="password" name="cpassword" required placeholder="Confirm Password" class="box">
      </div>
      <input type="submit" value="Register" name="submit" class="btn">
      
      <p>Already have an account? <a href="login_users.php">Login now</a></p>
   </form>

</section>

<script src="js/script.js"></script>
<script src="../js/script.js"></script>

</body>
</html>