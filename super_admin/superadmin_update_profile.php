<?php
include '../components/connect.php';

$db = new Database();
$conn = $db->connect();

session_start();

$admin_id = $_SESSION['admin_id'];

if (!isset($admin_id)) {
    header('location:admin_login.php');
    exit();
}

// Fetch current profile data
$select_profile = $conn->prepare("SELECT * FROM `accounts` WHERE account_id = ?");
$select_profile->execute([$admin_id]);
$fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);

// Check if profile data exists
if (!$fetch_profile) {
    echo '<p class="error">Error: No profile data found. Please check your account.</p>';
    exit();
}

// Use null coalescing operator to prevent undefined array key warnings
$firstname = $fetch_profile['firstname'] ?? '';
$lastname = $fetch_profile['lastname'] ?? '';
$middlename = $fetch_profile['middlename'] ?? '';
$user_name = $fetch_profile['user_name'] ?? '';
$email = $fetch_profile['email'] ?? '';
$current_image = $fetch_profile['image'] ?? '';

if (isset($_POST['submit'])) {
    // Sanitize input
    $firstname = filter_var($_POST['firstname'], FILTER_SANITIZE_STRING);
    $lastname = filter_var($_POST['lastname'], FILTER_SANITIZE_STRING);
    $middlename = filter_var($_POST['middlename'], FILTER_SANITIZE_STRING);
    $user_name = filter_var($_POST['user_name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    // Validate and check for duplicates
    $message = [];

    if (!empty($user_name)) {
        $select_name = $conn->prepare("SELECT * FROM `accounts` WHERE user_name = ? AND account_id != ?");
        $select_name->execute([$user_name, $admin_id]);
        if ($select_name->rowCount() > 0) {
            $message[] = 'Username already taken!';
        }
    }

    if (!empty($email)) {
        $select_email = $conn->prepare("SELECT * FROM `accounts` WHERE email = ? AND account_id != ?");
        $select_email->execute([$email, $admin_id]);
        if ($select_email->rowCount() > 0) {
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
    if (empty($message)) {
        $update_profile = $conn->prepare("UPDATE `accounts` SET firstname = ?, lastname = ?, middlename = ?, user_name = ?, email = ?, image = ? WHERE account_id = ?");
        $update_profile->execute([$firstname, $lastname, $middlename, $user_name, $email, $image, $admin_id]);
        $message[] = 'Profile updated successfully!';
        
        // Refresh profile data
        $select_profile->execute([$admin_id]);
        $fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);
        $current_image = $fetch_profile['image'] ?? '';
    }

    // Handle password update
    $old_pass = $_POST['old_pass'] ?? '';
    $new_pass = $_POST['new_pass'] ?? '';
    $confirm_pass = $_POST['confirm_pass'] ?? '';

    if (!empty($old_pass) || !empty($new_pass) || !empty($confirm_pass)) {
        if (empty($old_pass) || empty($new_pass) || empty($confirm_pass)) {
            $message[] = 'Please fill out all password fields!';
        } else {
            // Check password using multiple formats
            $password_correct = false;
            
            // First check if using password_hash format
            if (password_verify($old_pass, $fetch_profile['password'])) {
                $password_correct = true;
            } 
            // Then check if using sha1 format
            else if (sha1($old_pass) === $fetch_profile['password']) {
                $password_correct = true;
            }

            if ($password_correct) {
                if ($new_pass === $confirm_pass) {
                    // Always use password_hash for new passwords (more secure)
                    $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
                    $update_pass = $conn->prepare("UPDATE `accounts` SET password = ? WHERE account_id = ?");
                    $update_pass->execute([$hashed_pass, $admin_id]);
                    $message[] = 'Password updated successfully!';
                } else {
                    $message[] = 'New password and confirm password do not match!';
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

<?php include '../components/superadmin_header.php'; ?>

<!-- Display Messages -->
<?php
if (!empty($message)) {
    foreach ($message as $msg) {
        echo "<div class='message'><span>$msg</span><i class='fas fa-times' onclick='this.parentElement.remove();'></i></div>";
    }
}
?>

<!-- Profile Update Section -->
<section class="form-container">
   <form action="" method="POST" enctype="multipart/form-data">
      <h3>Update Profile</h3>

      <div class="profile-image-container">
         <img src="<?= !empty($current_image) ? '../uploads/profiles/'.htmlspecialchars($current_image) : '../uploads/profiles/default.jpg'; ?>" alt="Profile Picture" class="profile-image" id="profile-preview">
         <label for="image" class="image-upload-label">
            <i class="fas fa-camera"></i> Change Profile Picture
         </label>
         <input type="file" name="image" id="image" class="image-upload" accept="image/*" onchange="previewImage(this)">
      </div>

      <input type="text" name="firstname" maxlength="50" class="box" placeholder="First Name" value="<?= htmlspecialchars($firstname); ?>">
      <input type="text" name="lastname" maxlength="50" class="box" placeholder="Last Name" value="<?= htmlspecialchars($lastname); ?>">
      <input type="text" name="middlename" maxlength="50" class="box" placeholder="Middle Name" value="<?= htmlspecialchars($middlename); ?>">
      <input type="text" name="user_name" maxlength="20" class="box" placeholder="Username" value="<?= htmlspecialchars($user_name); ?>">
      <input type="email" name="email" maxlength="255" class="box" placeholder="Email" value="<?= htmlspecialchars($email); ?>">

      <input type="password" name="old_pass" maxlength="20" placeholder="Enter your old password" class="box">
      <input type="password" name="new_pass" maxlength="20" placeholder="Enter your new password" class="box">
      <input type="password" name="confirm_pass" maxlength="20" placeholder="Confirm your new password" class="box">

      <input type="submit" value="Update Now" name="submit" class="btn">
   </form>
</section>

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
