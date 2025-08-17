<?php
require_once __DIR__ . '/../components/connect.php';

$db = new Database();
$conn = $db->connect();

session_start();

$admin_id = $_SESSION['admin_id'] ?? null;
$admin_role = $_SESSION['admin_role'] ?? null;

if (!isset($admin_id) || $admin_role !== 'superadmin') {
    header('Location: ../access_denied.php');
    exit();
}

// Initialize message array
$message = [];

// Handle update form submission
if (isset($_POST['update_about'])) {
    $about_id = $_POST['about_id'];
    $title = filter_var($_POST['title'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $description = $_POST['description']; // Allow HTML content
    
    // Update the about us content
    $update_about = $conn->prepare("UPDATE `about_us` SET title = ?, description = ? WHERE about_id = ?");
    $update_about->execute([$title, $description, $about_id]);
    
    // Handle image upload if provided
    if (!empty($_FILES['image']['name'])) {
        $image = $_FILES['image']['name'];
        $image_tmp_name = $_FILES['image']['tmp_name'];
        $image_folder = '../uploads/';
        $image_size = $_FILES['image']['size'];
        $image_ext = pathinfo($image, PATHINFO_EXTENSION);
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        // Generate unique image name
        $new_image_name = uniqid() . '.' . $image_ext;
        
        // Validate image
        if (!in_array(strtolower($image_ext), $allowed_extensions)) {
            $message[] = 'Invalid image extension! Please use jpg, jpeg, png, gif, or webp.';
        } elseif ($image_size > 2000000) {
            $message[] = 'Image size is too large! Maximum size is 2MB.';
        } else {
            // Get current image to delete
            $select_old_image = $conn->prepare("SELECT image FROM `about_us` WHERE about_id = ?");
            $select_old_image->execute([$about_id]);
            $old_image = $select_old_image->fetch(PDO::FETCH_ASSOC);
            
            // Delete old image if exists
            if (!empty($old_image['image']) && file_exists($image_folder . $old_image['image'])) {
                unlink($image_folder . $old_image['image']);
            }
            
            // Upload new image
            move_uploaded_file($image_tmp_name, $image_folder . $new_image_name);
            
            // Update image in database
            $update_image = $conn->prepare("UPDATE `about_us` SET image = ? WHERE about_id = ?");
            $update_image->execute([$new_image_name, $about_id]);
        }
    }
    
    $message[] = 'About Us content updated successfully!';
}

// Handle delete image request
if (isset($_GET['delete_image'])) {
    $about_id = $_GET['delete_image'];
    
    // Get current image
    $select_image = $conn->prepare("SELECT image FROM `about_us` WHERE about_id = ?");
    $select_image->execute([$about_id]);
    $fetch_image = $select_image->fetch(PDO::FETCH_ASSOC);
    
    // Delete image file if exists
    if (!empty($fetch_image['image']) && file_exists('../uploads/' . $fetch_image['image'])) {
        unlink('../uploads/' . $fetch_image['image']);
    }
    
    // Update database to remove image reference
    $update_image = $conn->prepare("UPDATE `about_us` SET image = '' WHERE about_id = ?");
    $update_image->execute([$about_id]);
    
    $message[] = 'Image removed successfully!';
    header('Location: sa_aboutus.php');
    exit();
}

// Get all about us content
$select_about = $conn->prepare("SELECT * FROM about_us ORDER BY about_id DESC");
$select_about->execute();
$about_content = $select_about->fetchAll(PDO::FETCH_ASSOC);

// Get specific about us content for editing
$edit_id = $_GET['edit'] ?? null;
$edit_about = null;

if ($edit_id) {
    $select_edit = $conn->prepare("SELECT * FROM `about_us` WHERE about_id = ?");
    $select_edit->execute([$edit_id]);
    if ($select_edit->rowCount() > 0) {
        $edit_about = $select_edit->fetch(PDO::FETCH_ASSOC);
    } else {
        $message[] = 'Content not found!';
        $edit_id = null;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage About Us - Superadmin</title>
    <link rel="stylesheet" href="../css/spurpose.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/admin_style.css">
    <!-- Font Awesome CDN Link -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

    <!-- Admin CSS File Link -->
    <link rel="stylesheet" href="../css/admin_style.css">
    <link rel="stylesheet" href="../css/sa_carousel.css">

    <!-- Inside <head> -->
<style>


</style>

</head>
<body>

<?php include __DIR__ . '/../components/superadmin_header.php'; ?>

<div class="container mt-4">
    <h1 class="mb-4">Manage About Us Content</h1>
    
    <?php if (!empty($message)): ?>
        <div class="alert alert-info">
            <?php foreach ($message as $msg): ?>
                <p><?= $msg ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">About Us Content</h5>
                </div>
                <div class="card-body">
                    <?php if (count($about_content) > 0): ?>
                        <?php foreach ($about_content as $about): ?>
                            <div class="about-content">
                                <h4><?= htmlspecialchars($about['title'], ENT_QUOTES, 'UTF-8') ?></h4>
                                <p><?= htmlspecialchars($about['description'], ENT_QUOTES, 'UTF-8') ?></p>
                                
                                <?php if (!empty($about['image'])): ?>
                                    <div>
                                        <img src="../uploads/<?= htmlspecialchars($about['image'], ENT_QUOTES, 'UTF-8') ?>" alt="About Us Image" class="about-image">
                                    </div>
                                <?php endif; ?>
                                
                                <div class="mt-3">
                                    <a href="?edit=<?= htmlspecialchars($about['about_id'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-warning btn-sm">Edit</a>
                                    <?php if (!empty($about['image'])): ?>
                                        <a href="?delete_image=<?= htmlspecialchars($about['about_id'], ENT_QUOTES, 'UTF-8') ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this image?')">Delete Image</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No content available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Replace the entire modal section and JavaScript with this code -->

<!-- Custom Edit Form (instead of modal) -->
<?php if ($edit_about): ?>
<div class="container mt-4">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-warning">
                    <h5 class="mb-0">Edit About Us Content</h5>
                </div>
                <div class="card-body">
                    <form action="sa_aboutus.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="about_id" value="<?= htmlspecialchars($edit_about['about_id'], ENT_QUOTES, 'UTF-8') ?>">
                        
                        <div class="form-group">
                            <label for="edit_title">Title:</label>
                            <input type="text" id="edit_title" name="title" class="form-control" required value="<?= htmlspecialchars_decode($edit_about['title']) ?>">
                            </div>

                        <div class="form-group">
                            <label for="edit_description">Description</label>
                            <textarea id="edit_description" name="description" class="form-control" required rows="5"><?= htmlspecialchars_decode($edit_about['description']) ?></textarea>
                            </div>

                        <div class="form-group">
                            <label for="edit_image">Image</label>
                            <input type="file" id="edit_image" name="image" class="form-control">
                            <?php if (!empty($edit_about['image'])): ?>
                                <div class="mt-2">
                                    <img src="../uploads/<?= htmlspecialchars($edit_about['image']) ?>" alt="Current Image" class="img-thumbnail" style="max-width: 150px;">
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group mt-4">
                            <button type="submit" name="update_about" class="btn btn-primary">Save Changes</button>
                            <a href="sa_aboutus.php" class="btn btn-secondary ml-2">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="../js/admin_script.js"></script>   <!-- FOR THE SIDEBAR TOGGLE -->

</body>
</html>