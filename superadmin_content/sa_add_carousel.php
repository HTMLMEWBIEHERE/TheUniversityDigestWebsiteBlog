<?php
require_once __DIR__ . '/../components/connect.php';

$db = new Database();
$conn = $db->connect();

session_start();

$admin_id = $_SESSION['admin_id'] ?? null;
$admin_role = $_SESSION['admin_role'] ?? null;

// Only allow superadmin access to this page
if (!isset($admin_id) || $admin_role !== 'superadmin') {
    $_SESSION['message'] = 'Please login as a superadmin to access this content.';
    header('location:../admin/admin_login.php');
    exit();
}

// Initialize message array
$message = [];

// Display session message if it exists
if (isset($_SESSION['message'])) {
    $message[] = $_SESSION['message'];
    unset($_SESSION['message']);
}

// Carousel Image Upload
if (isset($_POST['upload_carousel'])) {
    $image = '';
    if (isset($_FILES['carousel_image']) && $_FILES['carousel_image']['size'] > 0) {
        $image_name = $_FILES['carousel_image']['name'];
        $image_size = $_FILES['carousel_image']['size'];
        $image_tmp_name = $_FILES['carousel_image']['tmp_name'];
        $image_extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));

        // Generate unique filename to prevent overwriting
        $new_image_name = uniqid('carousel_') . '.' . $image_extension;
        $image_folder = '../uploaded_img/' . $new_image_name;

        $allowed_exts = ['jpg', 'jpeg', 'png', 'gif'];

        if (!in_array($image_extension, $allowed_exts)) {
            $message[] = 'Invalid image format! Allowed formats: JPG, JPEG, PNG, GIF';
        }

        if ($image_size > 2000000) {
            $message[] = 'Image size is too large! Max: 2MB';
        }

        // Get the display order from the form (use the max order + 1 or default to 1)
        $display_order = isset($_POST['display_order']) ? (int)$_POST['display_order'] : 1;

        // Check if there is a message before proceeding
        if (empty($message)) {
            if (move_uploaded_file($image_tmp_name, $image_folder)) {
                $image = $new_image_name;

                // Insert carousel image into database
                $insert_carousel = $conn->prepare("INSERT INTO carousel_images (image_url, display_order, account_id) VALUES (?, ?, ?)");
                $insert_carousel->execute([$image, $display_order, $admin_id]);

                if ($insert_carousel->rowCount() > 0) {
                    $_SESSION['message'] = 'Carousel image uploaded successfully!';
                    header('Location: ' . $_SERVER['PHP_SELF']);
                    exit();
                } else {
                    $message[] = 'Failed to upload carousel image. Please try again.';
                }
            } else {
                $message[] = 'Failed to move uploaded file. Please try again.';
            }
        }
    } else {
        $message[] = 'Please select an image to upload.';
    }
}

// Delete Carousel Image
if (isset($_GET['delete_image_id'])) {
    $image_id = $_GET['delete_image_id'];

    // Get the image filename from the database before deleting
    $select_image = $conn->prepare("SELECT image_url FROM carousel_images WHERE id = ?");
    $select_image->execute([$image_id]);
    $image = $select_image->fetch(PDO::FETCH_ASSOC);

    if ($image) {
        // Delete the image file from the server
        $image_path = '../uploaded_img/' . $image['image_url'];
        if (file_exists($image_path)) {
            unlink($image_path);
        }

        // Delete the image record from the database
        $delete_image = $conn->prepare("DELETE FROM carousel_images WHERE id = ?");
        $delete_image->execute([$image_id]);

        $_SESSION['message'] = 'Carousel image deleted successfully!';
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

// Fetch all carousel images for display
$select_carousel_images = $conn->prepare("SELECT * FROM carousel_images ORDER BY display_order ASC");
$select_carousel_images->execute();
$carousel_images = $select_carousel_images->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Superadmin - Carousel Management</title>

    <!-- Font Awesome CDN Link -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

    <!-- Admin CSS File Link -->
    <link rel="stylesheet" href="../css/admin_style.css">
    <link rel="stylesheet" href="../css/sa_carousel.css">

    <!-- Carousel CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>

<?php include '../components/superadmin_header.php' ?>

<!-- Add Carousel Image Section -->
<section class="add-carousel">
    <h1 class="heading">Add Carousel Image</h1>
    <h2>Note: The image size should be 4000 x 1500</h2>

    <?php if (!empty($message)): ?>
        <?php foreach ($message as $msg): ?>
            <div class="message">
                <span><?= htmlspecialchars($msg); ?></span>
                <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <form action="" method="post" enctype="multipart/form-data">
        <div class="input-box">
            <label for="carousel_image">Carousel Image:</label>
            <input type="file" name="carousel_image" id="carousel_image" class="box" accept="image/jpg, image/jpeg, image/png, image/gif" required>
        </div>

        <div class="input-box">
            <label for="display_order">Display Order:</label>
            <select name="display_order" id="display_order" class="box" required>
                <option value="1" <?= count($carousel_images) < 1 ? 'selected' : ''; ?>>1</option>
                <option value="2" <?= count($carousel_images) < 2 ? 'selected' : ''; ?>>2</option>
                <option value="3" <?= count($carousel_images) < 3 ? 'selected' : ''; ?>>3</option>
            </select>
        </div>

        <div class="flex-btn">
            <button type="submit" name="upload_carousel" class="btn">Upload Carousel Image</button>
        </div>
    </form>
</section>

<!-- Display Carousel Section -->
<section class="carousel-display">
    <h1 class="heading">Carousel Images</h1>

    <div class="carousel-container">
        <?php foreach ($carousel_images as $carousel): ?>
            <div class="carousel-item-container">
                <div class="carousel-item">
                    <img src="../uploaded_img/<?= htmlspecialchars($carousel['image_url']); ?>" class="d-block w-100" alt="Carousel Image">
                </div>

                <!-- Edit and Delete Buttons -->
                <div class="carousel-controls">
                    <a href="sa_edit_carousel.php?edit_image_id=<?= $carousel['id']; ?>" class="btn btn-primary">Edit</a>
                    <a href="?delete_image_id=<?= $carousel['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this image?');">Delete</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Admin JS File Link -->
<script src="../js/admin_script.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>

</body>
</html>
