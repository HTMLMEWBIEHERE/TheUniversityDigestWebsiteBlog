<?php
include '../components/connect.php';

$db = new Database();
$conn = $db->connect();

session_start();

$message = '';
$success = false;

if(isset($_GET['code'])) {
    $code = $_GET['code'];
    
    // Check if verification code exists
    $stmt = $conn->prepare("SELECT * FROM `accounts` WHERE verification_code = ?");
    $stmt->execute([$code]);
    
    if($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($user['is_verified'] == 1) {
            $message = 'Your account is already verified. Please login.';
        } else {
            // Generate a unique ID for non-Google users similar to Google ID format
            $pseudo_google_id = 'man_' . bin2hex(random_bytes(8));
            
            // Update user to verified and set the pseudo Google ID
            $update = $conn->prepare("UPDATE `accounts` SET is_verified = 1, google_id = ? WHERE account_id = ? AND google_id IS NULL");
            $update->execute([$pseudo_google_id, $user['account_id']]);
            
            if($update) {
                $success = true;
                $message = 'Your email has been verified successfully! You can now login to your account.';
            } else {
                $message = 'Verification failed. Please try again or contact support.';
            }
        }
    } else {
        $message = 'Invalid verification code.';
    }
} else {
    $message = 'No verification code provided.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Email Verification - The University Digest</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link rel="stylesheet" href="css/style.css">
   <link rel="stylesheet" href="../css/style.css">
   <style>
       .verification-container {
           max-width: 600px;
           margin: 120px auto 50px;
           padding: 30px;
           background-color: #fff;
           border-radius: 8px;
           box-shadow: 0 5px 15px rgba(0,0,0,0.1);
           text-align: center;
       }
       
       .icon {
           font-size: 80px;
           margin-bottom: 20px;
       }
       
       .success-icon {
           color: #4CAF50;
       }
       
       .error-icon {
           color: #F44336;
       }
       
       .verification-message {
           margin-bottom: 25px;
           font-size: 18px;
           color: #333;
       }
       
       .btn-container {
           margin-top: 30px;
       }
       
       .btn {
           display: inline-block;
           padding: 10px 25px;
           background-color: #4285f4;
           color: #fff;
           text-decoration: none;
           border-radius: 4px;
           font-weight: 500;
           transition: background-color 0.3s;
       }
       
       .btn:hover {
           background-color: #3367d6;
       }
   </style>
</head>
<body>
   
<?php include '../components/user_header.php'; ?>

<div class="verification-container">
   <?php if($success): ?>
       <div class="icon success-icon"><i class="fas fa-check-circle"></i></div>
       <h2>Email Verified!</h2>
   <?php else: ?>
       <div class="icon error-icon"><i class="fas fa-exclamation-circle"></i></div>
       <h2>Verification Status</h2>
   <?php endif; ?>
   
   <div class="verification-message">
       <?= $message; ?>
   </div>
   
   <div class="btn-container">
       <?php if($success): ?>
           <a href="login_users.php" class="btn">Login Now</a>
       <?php else: ?>
           <a href="resend_verification.php" class="btn">Resend Verification</a>
           <a href="register.php" class="btn">Back to Register</a>
       <?php endif; ?>
   </div>
</div>

<script src="js/script.js"></script>
<script src="../js/script.js"></script>

</body>
</html>
