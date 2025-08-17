<?php
// Include necessary files
include '../components/connect.php';
include 'footer_controller.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
$admin_id = $_SESSION['admin_id'] ?? null;
$admin_role = $_SESSION['admin_role'] ?? null;

// Redirect if not logged in or not a superadmin
if(!isset($admin_id) || $admin_role != 'superadmin'){
   header('location:../admin/admin_login.php');
   exit();
}

// Initialize database connection
$db = new Database();
$conn = $db->connect();

// Initialize message array
$message = [];

// Check for session messages and transfer them to the local message array
if(isset($_SESSION['message'])) {
    $message[] = $_SESSION['message'];
    unset($_SESSION['message']); // Clear the session message after use
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Footer Info Update
    if (isset($_POST['update_footer_info'])) {
        $footer_id = $_POST['footer_id'];
        $address = $_POST['address'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $copyright_text = $_POST['copyright_text'];

        try {
            $update_footer = $conn->prepare("UPDATE footer_info SET 
                address = ?, 
                email = ?, 
                phone = ?, 
                copyright_text = ? 
                WHERE footer_id = ?");
            $update_footer->execute([$address, $email, $phone, $copyright_text, $footer_id]);
            
            // Store message in session before redirect
            $_SESSION['message'] = "Footer information updated successfully!";
            
            // Redirect to prevent form resubmission
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } catch (PDOException $e) {
            $message[] = "Error updating footer info: " . $e->getMessage();
        }
    }

    // Add Social Link
    if (isset($_POST['add_social'])) {
        $platform = $_POST['platform'];
        $url = $_POST['url'];
        $icon_class = $_POST['icon_class'];
        $footer_id = $_POST['footer_id'];

        try {
            $add_social = $conn->prepare("INSERT INTO footer_socials (platform, url, icon_class, footer_id) VALUES (?, ?, ?, ?)");
            $add_social->execute([$platform, $url, $icon_class, $footer_id]);
            
            // Store message in session before redirect
            $_SESSION['message'] = "Social link added successfully!";
            
            // Redirect to prevent form resubmission
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } catch (PDOException $e) {
            $message[] = "Error adding social link: " . $e->getMessage();
        }
    }

    // Edit Social Link
    if (isset($_POST['edit_social'])) {
        $social_id = $_POST['social_id'];
        $platform = $_POST['platform'];
        $url = $_POST['url'];
        $icon_class = $_POST['icon_class'];

        try {
            $edit_social = $conn->prepare("UPDATE footer_socials SET platform = ?, url = ?, icon_class = ? WHERE social_id = ?");
            $edit_social->execute([$platform, $url, $icon_class, $social_id]);
            
            // Store message in session before redirect
            $_SESSION['message'] = "Social link updated successfully!";
            
            // Redirect to prevent form resubmission
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } catch (PDOException $e) {
            $message[] = "Error updating social link: " . $e->getMessage();
        }
    }

    // Delete Social Link
    if (isset($_POST['delete_social'])) {
        $social_id = $_POST['social_id'];

        try {
            $delete_social = $conn->prepare("DELETE FROM footer_socials WHERE social_id = ?");
            $delete_social->execute([$social_id]);
            
            // Store message in session before redirect
            $_SESSION['message'] = "Social link deleted successfully!";
            
            // Redirect to prevent form resubmission
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        } catch (PDOException $e) {
            $message[] = "Error deleting social link: " . $e->getMessage();
        }
    }
}

// Fetch footer data
$footer = getFooter();
$socials = getFooterSocials($footer['footer_id']);
?>



<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Footer Management</title>

   <!-- font awesome cdn link  -->
    
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- custom css file link  -->
   <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<?php include '../components/superadmin_header.php'; ?>

<section class="footer-management">
    <h1 class="heading">Footer Management</h1>

  
    <!-- Tab Buttons -->
    <div class="tab-container">
        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('footerInfoTab', this)">Footer Information</button>
            <button class="tab-btn" onclick="showTab('socialMediaTab', this)">Social Media Links</button>
        </div>
    </div>

    <!-- Footer Info Section -->
    <div id="footerInfoTab" class="tab-content active">
        <div class="box-container">
            <div class="box">
                <h3>Footer Information</h3>
                <form action="" method="POST" class="form">
                    <input type="hidden" name="footer_id" value="<?= $footer['footer_id'] ?>">
                    
                    <div class="flex">
                        <div class="input-box">
                            <span>Address:</span>
                            <input type="text" name="address" value="<?= htmlspecialchars($footer['address']) ?>" required class="box">
                        </div>
                        
                        <div class="input-box">
                            <span>Email:</span>
                            <input type="email" name="email" value="<?= htmlspecialchars($footer['email']) ?>" required class="box">
                        </div>
                        
                        <div class="input-box">
                            <span>Phone:</span>
                            <input type="text" name="phone" value="<?= htmlspecialchars($footer['phone']) ?>" required class="box">
                        </div>
                        
                        <div class="input-box">
                            <span>Copyright Text:</span>
                            <input type="text" name="copyright_text" value="<?= htmlspecialchars($footer['copyright_text'] ?? '') ?>" class="box">
                        </div>
                    </div>
                    
                    <div class="flex-btn">
                        <button type="submit" name="update_footer_info" class="btn">Update Footer Info</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Social Links Section -->
    <div id="socialMediaTab" class="tab-content">
        <div class="box-container">
            <div class="box">
                <h3>Social Media Links</h3>
                
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Platform</th>
                                <th>URL</th>
                                <th>Icon Class</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($socials) > 0): ?>
                                <?php foreach ($socials as $social): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($social['platform']) ?></td>
                                        <td><?= htmlspecialchars($social['url']) ?></td>
                                        <td><i class="<?= htmlspecialchars($social['icon_class']) ?>"></i> <?= htmlspecialchars($social['platform']) ?></td>
                                        <td class="flex-btn">
                                            <button class="option-btn" onclick="openEditSocialModal(<?= $social['social_id'] ?>, '<?= htmlspecialchars($social['platform']) ?>', '<?= htmlspecialchars($social['url']) ?>', '<?= htmlspecialchars($social['icon_class'] ?? '') ?>')">Edit</button>
                                            
                                            <form action="" method="POST" onsubmit="return confirm('Are you sure you want to delete this social media link?');">
                                                <input type="hidden" name="social_id" value="<?= $social['social_id'] ?>">
                                                <button type="submit" name="delete_social" class="delete-btn">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="empty">No social media links found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <form action="" method="POST" class="form">
                    <input type="hidden" name="footer_id" value="<?= $footer['footer_id'] ?>">
                    
                    <div class="flex">
                        <div class="input-box">
                            <span>Platform:</span>
                            <input type="text" name="platform" required placeholder="e.g. Facebook" class="box">
                        </div>
                        
                        <div class="input-box">
                            <span>URL:</span>
                            <input type="url" name="url" required placeholder="e.g. https://facebook.com/yourpage" class="box">
                        </div>
                        
                        <div class="input-box">
                            <label for="edit_social_icon">Select Icon</label>
                            <div class="custom-select" id="icon-dropdown">
                                <div class="selected">
                                    <i class="fab fa-facebook-f"></i> Facebook
                                </div>
                                <div class="options d-none">
                                    <div data-value="fab fa-facebook-f"><i class="fab fa-facebook-f"></i> Facebook</div>
                                    <div data-value="fab fa-twitter"><i class="fab fa-twitter"></i> Twitter</div>
                                    <div data-value="fab fa-instagram"><i class="fab fa-instagram"></i> Instagram</div>
                                    <div data-value="fab fa-linkedin-in"><i class="fab fa-linkedin-in"></i> LinkedIn</div>
                                    <div data-value="fab fa-youtube"><i class="fab fa-youtube"></i> YouTube</div>
                                    <div data-value="fab fa-pinterest"><i class="fab fa-pinterest"></i> Pinterest</div>
                                    <div data-value="fab fa-tiktok"><i class="fab fa-tiktok"></i> TikTok</div>
                                    <div data-value="fab fa-snapchat"><i class="fab fa-snapchat"></i> Snapchat</div>
                                    <div data-value="fab fa-whatsapp"><i class="fab fa-whatsapp"></i> WhatsApp</div>
                                </div>
                            </div>
                            <input type="hidden" name="icon_class" id="icon_class_input" value="fab fa-facebook-f">
                        </div>
                    </div>
                    
                    <div class="flex-btn">
                        <button type="submit" name="add_social" class="btn">Add Social Media</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Modal for editing social media links -->
<div id="edit-social-modal" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal('edit-social-modal')">×</span>
        <h3>Edit Social Media Link</h3>
        <form action="" method="POST" class="form">
            <input type="hidden" name="social_id" id="edit_social_id">
            
            <div class="input-box">
                <span>Platform:</span>
                <input type="text" name="platform" id="edit_social_platform" required class="box">
            </div>
            
            <div class="input-box">
                <span>URL:</span>
                <input type="url" name="url" id="edit_social_url" required class="box">
            </div>

            <div class="input-box">
                <label for="edit_social_icon">Select Icon</label>
                <div class="custom-select" id="edit_icon_dropdown">
                    <div class="selected" id="edit_icon_selected">
                        <i class="fab fa-facebook-f"></i> Facebook
                    </div>
                    <div class="options d-none">
                        <div data-value="fab fa-facebook-f"><i class="fab fa-facebook-f"></i> Facebook</div>
                        <div data-value="fab fa-twitter"><i class="fab fa-twitter"></i> Twitter</div>
                        <div data-value="fab fa-instagram"><i class="fab fa-instagram"></i> Instagram</div>
                        <div data-value="fab fa-linkedin-in"><i class="fab fa-linkedin-in"></i> LinkedIn</div>
                        <div data-value="fab fa-youtube"><i class="fab fa-youtube"></i> YouTube</div>
                        <div data-value="fab fa-pinterest"><i class="fab fa-pinterest"></i> Pinterest</div>
                        <div data-value="fab fa-tiktok"><i class="fab fa-tiktok"></i> TikTok</div>
                        <div data-value="fab fa-snapchat"><i class="fab fa-snapchat"></i> Snapchat</div>
                        <div data-value="fab fa-whatsapp"><i class="fab fa-whatsapp"></i> WhatsApp</div>
                    </div>
                </div>
                <input type="hidden" name="icon_class" id="edit_icon_class_input">
            </div>
            
            <div class="flex-btn">
                <button type="submit" name="edit_social" class="btn">Update Social Link</button>
            </div>
        </form>
    </div>
</div>


<!-- Custom JS File -->
<script src="../js/admin_script.js"></script>
<script src="../js/footer_ajax.js"></script>


<!-- At the end of the file, before closing body tag -->

<!-- Add data-tab attributes to the tab buttons -->
<script>
    // Add data-tab attributes to tab buttons if they don't have them
    document.addEventListener('DOMContentLoaded', function() {
        const footerInfoBtn = document.querySelector('.tab-btn:nth-child(1)');
        const

</script>

</body>
</html>