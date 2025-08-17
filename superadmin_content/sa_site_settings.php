<?php
include '../components/connect.php';
require_once '../vendor/autoload.php';

$db = new Database();
$conn = $db->connect();

session_start();

// Redirect if not logged in as admin
if(!isset($_SESSION['admin_id']) || ($_SESSION['admin_role'] !== 'superadmin' && $_SESSION['admin_role'] !== 'subadmin')){
   header('location: ../user_content/login_users.php');
   exit();
}

// Log activity
try {
    $log_stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action_type, action_details) VALUES (?, 'settings_access', 'Accessed site settings page')");
    $log_stmt->execute([$_SESSION['admin_id']]);
} catch (Exception $e) {
    // Silently handle if activity_logs table doesn't exist
}

// Create settings table if it doesn't exist
$create_table = $conn->prepare("
CREATE TABLE IF NOT EXISTS `site_settings` (
  `setting_id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_name` varchar(50) NOT NULL,
  `setting_value` text NOT NULL,
  `setting_description` text DEFAULT NULL,
  `setting_group` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `setting_name` (`setting_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
$create_table->execute();

// Initialize default settings if they don't exist
$default_settings = [
    [
        'name' => 'email_smtp_host',
        'value' => 'smtp.gmail.com',
        'description' => 'SMTP Server Host',
        'group' => 'email'
    ],
    [
        'name' => 'email_smtp_port',
        'value' => '587',
        'description' => 'SMTP Server Port',
        'group' => 'email'
    ],
    [
        'name' => 'email_username',
        'value' => 'theuniversitydigestt@gmail.com',
        'description' => 'Email Username/Address',
        'group' => 'email'
    ],
    [
        'name' => 'email_password',
        'value' => 'hzit uknk vjxf eesj',
        'description' => 'Email Password (App Password for Gmail)',
        'group' => 'email'
    ],
    [
        'name' => 'email_sender_name',
        'value' => 'The University Digest',
        'description' => 'Email Sender Name',
        'group' => 'email'
    ],
    [
        'name' => 'email_encryption',
        'value' => 'tls',
        'description' => 'Email Encryption (tls/ssl)',
        'group' => 'email'
    ],
    [
        'name' => 'google_client_id',
        'value' => '718589190814-4em7rbk66m65a7ssef8i8cn6lr5pn7le.apps.googleusercontent.com',
        'description' => 'Google OAuth Client ID',
        'group' => 'google'
    ],
    [
        'name' => 'google_client_secret',
        'value' => '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',
        'description' => 'Google OAuth Client Secret',
        'group' => 'google'
    ],
    [
        'name' => 'site_name',
        'value' => 'The University Digest',
        'description' => 'Website Name',
        'group' => 'general'
    ],
    [
        'name' => 'verification_expiry_hours',
        'value' => '24',
        'description' => 'Email Verification Link Expiry (Hours)',
        'group' => 'email'
    ],
    [
        'name' => 'site_url',
        'value' => 'http://the-university-digest.site/TheUnivDigest',
        'description' => 'Full Site URL (without trailing slash)',
        'group' => 'site'
    ],
    [
        'name' => 'site_domain',
        'value' => 'the-university-digest.site',
        'description' => 'Site Domain Name',
        'group' => 'site'
    ],
    [
        'name' => 'oauth_redirect_uri',
        'value' => 'http://the-university-digest.site/TheUnivDigest/google_handlers/call_back.php',
        'description' => 'Google OAuth Redirect URI',
        'group' => 'site'
    ]
];

$check_settings = $conn->prepare("SELECT COUNT(*) FROM `site_settings`");
$check_settings->execute();
$settings_count = $check_settings->fetchColumn();

if($settings_count == 0) {
    $insert_setting = $conn->prepare("INSERT INTO `site_settings` (setting_name, setting_value, setting_description, setting_group) VALUES (?, ?, ?, ?)");
    
    foreach($default_settings as $setting) {
        $insert_setting->execute([
            $setting['name'],
            $setting['value'],
            $setting['description'],
            $setting['group']
        ]);
    }
}

// Handle form submission
$message = [];
$success = false;

if(isset($_POST['update_settings'])) {
    try {
        // Start transaction to ensure all updates are successful
        $conn->beginTransaction();
        
        // Process each setting group
        if(isset($_POST['email'])) {
            foreach($_POST['email'] as $setting_name => $setting_value) {
                $update = $conn->prepare("UPDATE `site_settings` SET setting_value = ? WHERE setting_name = ?");
                $update->execute([$setting_value, $setting_name]);
            }
        }
        
        if(isset($_POST['google'])) {
            foreach($_POST['google'] as $setting_name => $setting_value) {
                $update = $conn->prepare("UPDATE `site_settings` SET setting_value = ? WHERE setting_name = ?");
                $update->execute([$setting_value, $setting_name]);
            }
        }
        
        if(isset($_POST['general'])) {
            foreach($_POST['general'] as $setting_name => $setting_value) {
                $update = $conn->prepare("UPDATE `site_settings` SET setting_value = ? WHERE setting_name = ?");
                $update->execute([$setting_value, $setting_name]);
            }
        }
        
        // Commit the transaction
        $conn->commit();
        
        $success = true;
        $message[] = 'Settings updated successfully!';
    } catch(Exception $e) {
        // Rollback on error
        $conn->rollBack();
        $message[] = 'Error updating settings: ' . $e->getMessage();
    }
}

// Fetch all settings grouped by type
$get_settings = $conn->prepare("SELECT * FROM `site_settings` ORDER BY setting_group, setting_name");
$get_settings->execute();
$all_settings = $get_settings->fetchAll(PDO::FETCH_ASSOC);

// Group settings by type
$settings = [];
foreach($all_settings as $setting) {
    $settings[$setting['setting_group']][$setting['setting_name']] = [
        'value' => $setting['setting_value'],
        'description' => $setting['setting_description']
    ];
}

// Test email functionality
$test_sent = false;
$test_error = '';

if(isset($_POST['test_email'])) {
    try {
        $test_email = $_POST['test_email_address'];
        
        if(empty($test_email)) {
            $message[] = 'Please enter a test email address.';
        } else {
            // Get email settings
            $email_settings = [];
            foreach($all_settings as $setting) {
                if($setting['setting_group'] == 'email') {
                    $email_settings[$setting['setting_name']] = $setting['setting_value'];
                }
            }
            
            // Send test email using PHPMailer
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            $mail->isSMTP();
            $mail->Host       = $email_settings['email_smtp_host'];
            $mail->SMTPAuth   = true;
            $mail->Username   = $email_settings['email_username'];
            $mail->Password   = $email_settings['email_password'];
            $mail->SMTPSecure = $email_settings['email_encryption'];
            $mail->Port       = $email_settings['email_smtp_port'];
            
            $mail->setFrom($email_settings['email_username'], $email_settings['email_sender_name']);
            $mail->addAddress($test_email);
            
            $mail->isHTML(true);
            $mail->Subject = 'Email Configuration Test';
            $mail->Body    = "
            <html>
            <body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                <h2 style='color: #444;'>Email Configuration Test</h2>
                <p>This is a test email from " . $email_settings['email_sender_name'] . ".</p>
                <p>If you received this email, it means your email configuration is working correctly.</p>
                <p>Configuration details:</p>
                <ul>
                    <li>SMTP Host: " . $email_settings['email_smtp_host'] . "</li>
                    <li>SMTP Port: " . $email_settings['email_smtp_port'] . "</li>
                    <li>Encryption: " . $email_settings['email_encryption'] . "</li>
                    <li>Sender: " . $email_settings['email_username'] . "</li>
                </ul>
                <p>Best regards,<br>" . $email_settings['email_sender_name'] . " Team</p>
            </body>
            </html>";
            
            $mail->send();
            $test_sent = true;
            $message[] = 'Test email sent successfully to ' . $test_email;
        }
    } catch(Exception $e) {
        $test_error = $e->getMessage();
        $message[] = 'Error sending test email: ' . $test_error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Site Settings - The University Digest</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
   <link rel="stylesheet" href="../css/style.css">
   <link rel="stylesheet" href="../css/admin_style.css">
   <style>
      .settings-container {
         max-width: 1200px;
         margin: 100px auto 20px;
         padding: 20px;
      }
      
      /* Fix for bold text in header */
      .header .navbar a,
      .header .navbar a span,
      .header .logo,
      .header .profile p,
      .header .flex-btn a {
         font-weight: normal;
      }
      
      /* Additional header styling fixes */
      .header .navbar a {
         font-size: 1.6rem;
         color: #333;
      }
      
      .header .navbar a i {
         margin-right: 0.5rem;
      }
      
      .header .logo {
         font-size: 2.5rem;
         color: #4F0003; /* Match brand color */
      }
      
      .header .logo span {
         color: #800000; /* Different shade for contrast */
      }
      
      .header .profile p {
         font-size: 1.6rem;
      }
      
      /* Ensure header appears above content */
      .header {
         z-index: 1000 !important;
         background-color: white;
         box-shadow: 0 2px 5px rgba(0,0,0,0.1);
      }
      
      .settings-title {
         border-bottom: 2px solid #800000;
         padding-bottom: 10px;
         margin-bottom: 20px;
         color: #800000;
      }
      
      .settings-section {
         background: #fff;
         border-radius: 8px;
         box-shadow: 0 0 10px rgba(0,0,0,0.1);
         padding: 20px;
         margin-bottom: 20px;
      }
      
      .settings-section h3 {
         border-bottom: 1px solid #ddd;
         padding-bottom: 10px;
         margin-bottom: 20px;
      }
      
      .form-group {
         margin-bottom: 15px;
      }
      
      .form-group label {
         display: block;
         margin-bottom: 5px;
         font-weight: 500;
      }
      
      .form-control {
         width: 100%;
         padding: 8px 12px;
         border: 1px solid #ddd;
         border-radius: 4px;
         font-size: 14px;
      }
      
      .btn-submit {
         background-color: #800000;
         color: #fff;
         border: none;
         padding: 10px 20px;
         border-radius: 4px;
         cursor: pointer;
         font-size: 16px;
         margin-top: 10px;
      }
      
      .btn-test {
         background-color: #4285F4;
         color: #fff;
         border: none;
         padding: 8px 15px;
         border-radius: 4px;
         cursor: pointer;
         font-size: 14px;
      }
      
      .test-email-container {
         margin-top: 20px;
         padding-top: 20px;
         border-top: 1px solid #ddd;
      }
      
      .alert {
         padding: 15px;
         margin-bottom: 20px;
         border-radius: 4px;
      }
      
      .alert-success {
         background-color: #d4edda;
         color: #155724;
      }
      
      .alert-danger {
         background-color: #f8d7da;
         color: #721c24;
      }
      
      .setting-description {
         font-size: 12px;
         color: #666;
         margin-top: 3px;
      }
      
      /* Password visibility toggle */
      .password-field {
         position: relative;
      }
      
      .password-toggle {
         position: absolute;
         right: 10px;
         top: 50%;
         transform: translateY(-50%);
         background: none;
         border: none;
         cursor: pointer;
         color: #666;
      }
   </style>
</head>
<body>
   <?php include '../components/superadmin_header.php'; ?>
   
   <div class="settings-container">
      <h1 class="settings-title">Site Settings</h1>
      
      <?php if(!empty($message)): ?>
         <div class="alert <?= $success ? 'alert-success' : 'alert-danger' ?>">
            <?php foreach($message as $msg): ?>
               <p><?= $msg ?></p>
            <?php endforeach; ?>
         </div>
      <?php endif; ?>
      
      <form action="" method="post">
         <!-- Email Settings -->
         <div class="settings-section">
            <h3><i class="fas fa-envelope"></i> Email Settings</h3>
            
            <?php if(isset($settings['email'])): ?>
               <?php foreach($settings['email'] as $name => $setting): ?>
                  <div class="form-group">
                     <label for="<?= $name ?>"><?= $setting['description'] ?></label>
                     
                     <?php if($name == 'email_password'): ?>
                        <div class="password-field">
                           <input type="password" name="email[<?= $name ?>]" id="<?= $name ?>" class="form-control" value="<?= htmlspecialchars($setting['value']) ?>" required>
                           <button type="button" class="password-toggle" onclick="togglePassword('<?= $name ?>')">
                              <i class="fas fa-eye"></i>
                           </button>
                        </div>
                     <?php elseif($name == 'email_encryption'): ?>
                        <select name="email[<?= $name ?>]" id="<?= $name ?>" class="form-control">
                           <option value="tls" <?= $setting['value'] == 'tls' ? 'selected' : '' ?>>TLS</option>
                           <option value="ssl" <?= $setting['value'] == 'ssl' ? 'selected' : '' ?>>SSL</option>
                           <option value="" <?= $setting['value'] == '' ? 'selected' : '' ?>>None</option>
                        </select>
                     <?php else: ?>
                        <input type="text" name="email[<?= $name ?>]" id="<?= $name ?>" class="form-control" value="<?= htmlspecialchars($setting['value']) ?>" required>
                     <?php endif; ?>
                     
                     <div class="setting-description">
                        <?php if($name == 'email_password'): ?>
                           For Gmail, use an <a href="https://support.google.com/accounts/answer/185833" target="_blank">App Password</a> instead of your regular password.
                        <?php endif; ?>
                     </div>
                  </div>
               <?php endforeach; ?>
            <?php endif; ?>
            
            <div class="test-email-container">
               <h4>Test Email Configuration</h4>
               <div class="form-group">
                  <label for="test_email_address">Send test email to:</label>
                  <div style="display: flex;">
                     <input type="email" name="test_email_address" id="test_email_address" class="form-control" style="margin-right: 10px;">
                     <button type="submit" name="test_email" class="btn-test">Send Test</button>
                  </div>
               </div>
               
               <?php if($test_sent): ?>
                  <div class="alert alert-success">
                     Test email sent successfully!
                  </div>
               <?php elseif(!empty($test_error)): ?>
                  <div class="alert alert-danger">
                     <?= $test_error ?>
                  </div>
               <?php endif; ?>
            </div>
         </div>
         
         <!-- Google Authentication Settings -->
         <div class="settings-section">
            <h3><i class="fab fa-google"></i> Google Authentication Settings</h3>
            
            <?php if(isset($settings['google'])): ?>
               <?php foreach($settings['google'] as $name => $setting): ?>
                  <div class="form-group">
                     <label for="<?= $name ?>"><?= $setting['description'] ?></label>
                     
                     <?php if($name == 'google_client_secret'): ?>
                        <div class="password-field">
                           <input type="password" name="google[<?= $name ?>]" id="<?= $name ?>" class="form-control" value="<?= htmlspecialchars($setting['value']) ?>" required>
                           <button type="button" class="password-toggle" onclick="togglePassword('<?= $name ?>')">
                              <i class="fas fa-eye"></i>
                           </button>
                        </div>
                     <?php else: ?>
                        <input type="text" name="google[<?= $name ?>]" id="<?= $name ?>" class="form-control" value="<?= htmlspecialchars($setting['value']) ?>" required>
                     <?php endif; ?>
                     
                     <div class="setting-description">
                        <?php if($name == 'google_client_id'): ?>
                           <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console</a> > APIs & Services > Credentials
                        <?php endif; ?>
                     </div>
                  </div>
               <?php endforeach; ?>
            <?php endif; ?>
            
            <div class="setting-description" style="margin-top: 15px;">
               <strong>Important:</strong> After changing the Client ID or Secret, remember to update the Authorized redirect URIs in the 
               <a href="https://console.cloud.google.com/apis/credentials" target="_blank">Google Cloud Console</a>.
               <br>
               Add: <code><?= 'http://' . $_SERVER['HTTP_HOST'] . '/google_handlers/call_back.php' ?></code>
            </div>
         </div>
         
         <!-- General Settings -->
         <div class="settings-section">
            <h3><i class="fas fa-cog"></i> General Settings</h3>
            
            <?php if(isset($settings['general'])): ?>
               <?php foreach($settings['general'] as $name => $setting): ?>
                  <div class="form-group">
                     <label for="<?= $name ?>"><?= $setting['description'] ?></label>
                     <input type="text" name="general[<?= $name ?>]" id="<?= $name ?>" class="form-control" value="<?= htmlspecialchars($setting['value']) ?>" required>
                  </div>
               <?php endforeach; ?>
            <?php endif; ?>
         </div>
         
         <button type="submit" name="update_settings" class="btn-submit">Save All Settings</button>
      </form>
   </div>
   
   <script>
   function togglePassword(fieldId) {
      const field = document.getElementById(fieldId);
      const icon = event.currentTarget.querySelector('i');
      
      if (field.type === 'password') {
         field.type = 'text';
         icon.classList.remove('fa-eye');
         icon.classList.add('fa-eye-slash');
      } else {
         field.type = 'password';
         icon.classList.remove('fa-eye-slash');
         icon.classList.add('fa-eye');
      }
   }
   </script>
</body>
</html>