<?php
include '../components/connect.php';

require '../vendor/autoload.php';
require_once '../classes/email_helper.class.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$db = new Database();
$conn = $db->connect();

session_start();

$message = [];
$success = false;

if(isset($_POST['submit'])){
    $email = strtolower(trim($_POST['email']));
    
    // Check if email exists and is not verified
    $stmt = $conn->prepare("SELECT * FROM `accounts` WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$user){
        $message[] = 'Email address not found!';
    } elseif($user['is_verified'] == 1){
        $message[] = 'This account is already verified. Please login.';
    } else {
        // Generate new verification code
        $verification_code = bin2hex(random_bytes(16));
        
        // Update verification code in database
        $update = $conn->prepare("UPDATE `accounts` SET verification_code = ? WHERE email = ?");
        $update->execute([$verification_code, $email]);
        
        if($update){
            // Send verification email using the EmailHelper class
            try {
                EmailHelper::sendVerificationEmail($conn, $email, $user['firstname'], $user['lastname'], $verification_code);
                $success = true;
                $message[] = 'Verification email has been resent! Please check your inbox.';
            } catch (Exception $e) {
                $message[] = "Message could not be sent. Mailer Error: {$e->getMessage()}";
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
   <title>Resend Verification Email - The University Digest</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link rel="stylesheet" href="css/style.css">
   <link rel="stylesheet" href="../css/style.css">
   <style>
      .form-container {
         margin-top: 120px; /* Add top margin to prevent header overlap */
      }
   </style>
</head>
<body>
   
<?php include '../components/user_header.php'; ?>

<section class="form-container">
   <form action="" method="post">
      <h3>Resend Verification Email</h3>
      
      <?php if(!empty($message)): ?>
      <div class="message <?= $success ? 'success' : ''; ?>">
         <?php foreach($message as $msg): ?>
            <p><?= $msg; ?></p>
         <?php endforeach; ?>
      </div>
      <?php endif; ?>
      
      <div class="input-group">
         <input type="email" name="email" required placeholder="Enter your email address" class="box">
      </div>
      <input type="submit" value="Resend Verification Email" name="submit" class="btn">
      <p>Remember your password? <a href="login_users.php">Login now</a></p>
      <p>Don't have an account? <a href="register.php">Register now</a></p>
   </form>
</section>

<script src="js/script.js"></script>
<script src="../js/script.js"></script>

</body>
</html> 