<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include '../components/connect.php';

$db = new Database();
$conn = $db->connect();

// Check if admin is logged in and is a superadmin
$admin_id = $_SESSION['admin_id'] ?? null;
$admin_role = $_SESSION['admin_role'] ?? null; // Changed from $_SESSION['role'] to $_SESSION['admin_role']

// Only allow superadmin access to this page
if(!isset($admin_id) || $admin_role !== 'superadmin'){
   $_SESSION['message'] = 'Please login as a superadmin to access this content.';
   header('location:../admin/admin_login.php');
   exit(); // Stop further execution
}

// Initialize message array
$message = [];

// Display session message if it exists
if(isset($_SESSION['message'])) {
   $message[] = $_SESSION['message'];
   unset($_SESSION['message']);
}

// Handle account deletion with proper transaction handling
if (isset($_POST['delete'])) {
    $account_id = $_POST['account_id'];
    
    // Don't allow deleting your own account
    if ($account_id == $admin_id) {
        $message[] = "You cannot delete your own account!";
    } else {
        try {
            // Start transaction for data consistency
            $conn->beginTransaction();
            
            // Delete related data
            // 1. First get images to delete files
            $delete_post_images = $conn->prepare("SELECT image FROM `posts` WHERE created_by = ?");
            $delete_post_images->execute([$account_id]);
            while ($fetch_delete_image = $delete_post_images->fetch(PDO::FETCH_ASSOC)) {
                if (!empty($fetch_delete_image['image'])) {
                    $image_path = '../uploaded_img/' . $fetch_delete_image['image'];
                    if (file_exists($image_path)) {
                        unlink($image_path);
                    }
                }
            }
            
            // 2. Delete tejido images
            $delete_tejido_images = $conn->prepare("SELECT img FROM `tejido` WHERE created_by = ?");
            $delete_tejido_images->execute([$account_id]);
            while ($fetch_delete_image = $delete_tejido_images->fetch(PDO::FETCH_ASSOC)) {
                if (!empty($fetch_delete_image['img'])) {
                    $image_path = '../uploaded_img/' . $fetch_delete_image['img'];
                    if (file_exists($image_path)) {
                        unlink($image_path);
                    }
                }
            }
            
            // 3. Delete article images
            $delete_article_images = $conn->prepare("SELECT image FROM `articles` WHERE created_by = ?");
            $delete_article_images->execute([$account_id]);
            while ($fetch_delete_image = $delete_article_images->fetch(PDO::FETCH_ASSOC)) {
                if (!empty($fetch_delete_image['image'])) {
                    $image_path = '../uploaded_img/' . $fetch_delete_image['image'];
                    if (file_exists($image_path)) {
                        unlink($image_path);
                    }
                }
            }
            
            // 4. Delete admin's content in order (foreign key constraints)
            $conn->prepare("DELETE FROM `comments` WHERE commented_by = ?")->execute([$account_id]);
            $conn->prepare("DELETE FROM `likes` WHERE account_id = ?")->execute([$account_id]);
            $conn->prepare("DELETE FROM `posts` WHERE created_by = ?")->execute([$account_id]);
            $conn->prepare("DELETE FROM `tejido` WHERE created_by = ?")->execute([$account_id]);
            $conn->prepare("DELETE FROM `articles` WHERE created_by = ?")->execute([$account_id]);
            $conn->prepare("DELETE FROM `announcements` WHERE created_by = ?")->execute([$account_id]);
            $conn->prepare("DELETE FROM `e_magazines` WHERE created_by = ?")->execute([$account_id]);
            $conn->prepare("DELETE FROM `carousel_images` WHERE account_id = ?")->execute([$account_id]);
            
            // 5. Finally, delete the admin account
            $delete_admin = $conn->prepare("DELETE FROM `accounts` WHERE account_id = ? AND role IN ('subadmin', 'superadmin')");
            $delete_admin->execute([$account_id]);
            
            if ($delete_admin->rowCount() > 0) {
                $conn->commit();
                $_SESSION['message'] = "Admin account deleted successfully!";
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit();
            } else {
                // Rollback if account deletion failed
                $conn->rollBack();
                $message[] = "Failed to delete admin account. Admin not found or you don't have permission.";
            }
        } catch (PDOException $e) {
            $conn->rollBack();
            $message[] = "Error: " . $e->getMessage();
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
    <title>Admins Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<?php include '../components/superadmin_header.php'; // Changed from superadmin_sidebar.php to superadmin_header.php ?>

<section class="accounts">
    <h1 class="heading">Manage Admins</h1>

    <?php
    // Display messages if any
    if (!empty($message)) {
        foreach ($message as $msg) {
            echo '<div class="message"><span>' . $msg . '</span><i class="fas fa-times" onclick="this.parentElement.remove();"></i></div>';
        }
    }
    ?>

    <div class="box-container">
        <div class="box" style="order: -2;">
            <p>Register new admin</p>
            <a href="../super_admin/sa_register_subadmin.php" class="option-btn" style="margin-bottom: .5rem;">Register</a>
        </div>

        <?php
        // Select all subadmins and superadmins
        $select_account = $conn->prepare("SELECT * FROM `accounts` WHERE role IN ('subadmin', 'superadmin')");
        $select_account->execute();

        if ($select_account->rowCount() > 0) {
            while ($fetch_accounts = $select_account->fetch(PDO::FETCH_ASSOC)) {
                // Count posts
                $count_admin_posts = $conn->prepare("SELECT COUNT(*) FROM `posts` WHERE created_by = ?");
                $count_admin_posts->execute([$fetch_accounts['account_id']]);
                $total_admin_posts = $count_admin_posts->fetchColumn();
                
                // Count articles
                $count_admin_articles = $conn->prepare("SELECT COUNT(*) FROM `articles` WHERE created_by = ?");
                $count_admin_articles->execute([$fetch_accounts['account_id']]);
                $total_admin_articles = $count_admin_articles->fetchColumn();
                
                // Count tejidos
                $count_admin_tejidos = $conn->prepare("SELECT COUNT(*) FROM `tejido` WHERE created_by = ?");
                $count_admin_tejidos->execute([$fetch_accounts['account_id']]);
                $total_admin_tejidos = $count_admin_tejidos->fetchColumn();
                
                // Total content count
                $total_content = $total_admin_posts + $total_admin_articles + $total_admin_tejidos;
        ?>

        <div class="box" style="order: <?= ($fetch_accounts['account_id'] == $admin_id) ? '-1' : '0'; ?>;">
            <p>Admin ID: <span><?= htmlspecialchars($fetch_accounts['account_id']); ?></span></p>
            <p>Username: <span><?= htmlspecialchars($fetch_accounts['user_name']); ?></span></p>
            <p>Name: <span><?= htmlspecialchars($fetch_accounts['firstname'] . ' ' . $fetch_accounts['lastname']); ?></span></p>
            <p>Email: <span><?= htmlspecialchars($fetch_accounts['email']); ?></span></p>
            <p>Role: <span><?= htmlspecialchars($fetch_accounts['role']); ?></span></p>
            <p>Total Content: <span><?= $total_content; ?></span></p>
            <div class="flex-btn">
                <?php if ($fetch_accounts['account_id'] != $admin_id): ?>
                    <form action="" method="POST" onsubmit="return confirm('WARNING: Deleting this admin account will permanently remove all content created by this admin.\n\nThis includes ALL posts, articles, tejidos, and other content.\n\nThis action CANNOT be undone. Are you absolutely sure?');">
                        <input type="hidden" name="account_id" value="<?= $fetch_accounts['account_id']; ?>">
                        <button type="submit" name="delete" class="delete-btn">Delete</button>
                    </form>
                <?php else: ?>
                    <p class="note">This is your account</p>
                <?php endif; ?>
            </div>
        </div>

        <?php
            }
        } else {
            echo '<p class="empty">No admin accounts available</p>';
        }
        ?>
    </div>
</section>

<script src="../js/admin_script.js"></script>
</body>
</html>
