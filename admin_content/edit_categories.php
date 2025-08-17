<?php
require_once __DIR__ . '/../components/connect.php';

$db = new Database();
$conn = $db->connect();

session_start();

$admin_id = $_SESSION['admin_id'] ?? null;
$admin_role = $_SESSION['admin_role'] ?? null;

// Redirect if not logged in or not an admin
if(!isset($admin_id) || $admin_role != 'subadmin'){
   header('location:../admin_content/admin_login.php');
   exit();
}

// Initialize messages array
$message = [];

// Handle Add Category
if(isset($_POST['add_category'])){
    $name = trim($_POST['name']);
    
    // Validate category name
    if(empty($name)){
        $message[] = 'Category name is required!';
    } else {
        // Check if category already exists
        $check_category = $conn->prepare("SELECT * FROM `category` WHERE name = ?");
        $check_category->execute([$name]);
        
        if($check_category->rowCount() > 0){
            $message[] = 'Category already exists!';
        } else {
            // Insert new category
            $insert_category = $conn->prepare("INSERT INTO `category` (name) VALUES (?)");
            $insert_category->execute([$name]);
            
            if($insert_category){
                $message[] = 'Category added successfully!';
            } else {
                $message[] = 'Failed to add category!';
            }
        }
    }
}

// Handle Edit Category
if(isset($_POST['edit_category'])){
    $category_id = $_POST['category_id'];
    $name = trim($_POST['name']);
    
    // Validate category name
    if(empty($name)){
        $message[] = 'Category name is required!';
    } else {
        // Check if category already exists (except the current one)
        $check_category = $conn->prepare("SELECT * FROM `category` WHERE name = ? AND category_id != ?");
        $check_category->execute([$name, $category_id]);
        
        if($check_category->rowCount() > 0){
            $message[] = 'Category name already exists!';
        } else {
            // Update category
            $update_category = $conn->prepare("UPDATE `category` SET name = ? WHERE category_id = ?");
            $update_category->execute([$name, $category_id]);
            
            if($update_category){
                $message[] = 'Category updated successfully!';
            } else {
                $message[] = 'Failed to update category!';
            }
        }
    }
}

// Handle Delete Category
if(isset($_GET['delete'])){
    $category_id = $_GET['delete'];
    
    // Check if category is used in posts
    $check_posts = $conn->prepare("SELECT * FROM `posts` WHERE category_id = ?");
    $check_posts->execute([$category_id]);
    
    // Check if category is used in tejido
    $check_tejido = $conn->prepare("SELECT * FROM `tejido` WHERE category_id = ?");
    $check_tejido->execute([$category_id]);
    
    // Check if category is used in e_magazines
    $check_magazines = $conn->prepare("SELECT * FROM `e_magazines` WHERE category_id = ?");
    $check_magazines->execute([$category_id]);
    
    if($check_posts->rowCount() > 0 || $check_tejido->rowCount() > 0 || $check_magazines->rowCount() > 0){
        $message[] = 'Cannot delete category - it is in use!';
    } else {
        // Delete category
        $delete_category = $conn->prepare("DELETE FROM `category` WHERE category_id = ?");
        $delete_category->execute([$category_id]);
        
        if($delete_category){
            $message[] = 'Category deleted successfully!';
        } else {
            $message[] = 'Failed to delete category!';
        }
    }
}

// Get category for editing (if edit mode is active)
$edit_mode = false;
$edit_id = null;
$edit_name = '';

if(isset($_GET['edit'])){
    $edit_id = $_GET['edit'];
    $edit_mode = true;
    
    $get_category = $conn->prepare("SELECT * FROM `category` WHERE category_id = ?");
    $get_category->execute([$edit_id]);
    
    if($get_category->rowCount() > 0){
        $category = $get_category->fetch(PDO::FETCH_ASSOC);
        $edit_name = $category['name'];
    } else {
        $edit_mode = false;
    }
}

// Get all categories
$select_categories = $conn->prepare("SELECT * FROM `category` ORDER BY name ASC");
$select_categories->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories</title>
    
    <!-- Font Awesome CDN link -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    
    <!-- Admin CSS File Link -->
    <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<?php include '../components/admin_header.php' ?>

<!-- Category Management Section -->
<section class="add-tejido">
    <h1 class="heading"><?= $edit_mode ? 'Edit Category' : 'Add New Category' ?></h1>
    
    <?php if(!empty($message)): ?>
        <div class="message">
            <?php 
            // Ensure $message is always an array
            $messageArray = is_array($message) ? $message : array($message);
            foreach($messageArray as $msg): 
            ?>
                <span><?= $msg; ?></span>
                <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <!-- Add/Edit Category Form -->
    <form action="" method="post">
        <?php if($edit_mode): ?>
            <input type="hidden" name="category_id" value="<?= $edit_id; ?>">
        <?php endif; ?>
        
        <div class="input-box">
            <label for="name">Category Name:</label>
            <input type="text" name="name" id="name" class="box" placeholder="Enter category name" value="<?= $edit_name; ?>" required>
        </div>
        
        <button type="submit" name="<?= $edit_mode ? 'edit_category' : 'add_category' ?>" class="btn">
            <?= $edit_mode ? 'Update Category' : 'Add Category' ?>
        </button>
        
        <?php if($edit_mode): ?>
            <a href="edit_categories.php" class="option-btn" style="margin-top: 1rem;">Cancel Edit</a>
        <?php endif; ?>
    </form>
</section>

<!-- Categories List Section -->
<section class="tejido-posts">
    <h1 class="heading">All Categories</h1>
    
    <?php if(!empty($message)): ?>
        <div class="message">
            <?php 
            // Ensure $message is always an array
            $messageArray = is_array($message) ? $message : array($message);
            foreach($messageArray as $msg): 
            ?>
                <span><?= $msg; ?></span>
                <i class="fas fa-times" onclick="this.parentElement.remove();"></i>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    
    <div class="box-container">
        <?php
        if($select_categories->rowCount() > 0){
            while($category = $select_categories->fetch(PDO::FETCH_ASSOC)){
                // Count items in this category
                $count_posts = $conn->prepare("SELECT COUNT(*) as post_count FROM `posts` WHERE category_id = ?");
                $count_posts->execute([$category['category_id']]);
                $post_count = $count_posts->fetch(PDO::FETCH_ASSOC)['post_count'];
                
                $count_tejido = $conn->prepare("SELECT COUNT(*) as tejido_count FROM `tejido` WHERE category_id = ?");
                $count_tejido->execute([$category['category_id']]);
                $tejido_count = $count_tejido->fetch(PDO::FETCH_ASSOC)['tejido_count'];
                
                $count_magazines = $conn->prepare("SELECT COUNT(*) as magazine_count FROM `e_magazines` WHERE category_id = ?");
                $count_magazines->execute([$category['category_id']]);
                $magazine_count = $count_magazines->fetch(PDO::FETCH_ASSOC)['magazine_count'];
                
                $total_count = $post_count + $tejido_count + $magazine_count;
        ?>
        <div class="box">
            <h3><?= htmlspecialchars($category['name']); ?></h3>
            <p>Total Items: <span><?= $total_count; ?></span></p>
            <p>Posts: <span><?= $post_count; ?></span></p>
            <p>Tejidos: <span><?= $tejido_count; ?></span></p>
            <p>E-Magazines: <span><?= $magazine_count; ?></span></p>
            
            <div class="flex-btn">
                <a href="edit_categories.php?edit=<?= $category['category_id']; ?>" class="btn">Edit</a>
                <a href="edit_categories.php?delete=<?= $category['category_id']; ?>" 
                   class="delete-btn" 
                   onclick="return confirm('Delete this category? This action cannot be undone if the category contains items.');">
                    Delete
                </a>
            </div>
        </div>
        <?php 
            }
        } else {
            echo '<p class="empty">No categories found!</p>';
        }
        ?>
    </div>
</section>

<!-- Admin JS File Link -->
<script src="../js/admin_script.js"></script>

</body>
</html>