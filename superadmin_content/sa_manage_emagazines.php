<?php
include '../components/connect.php';

$db = new Database();
$conn = $db->connect();

session_start();

// Check if admin is logged in and is a superadmin
$admin_id = $_SESSION['admin_id'] ?? null;
$admin_role = $_SESSION['admin_role'] ?? null;

// Only allow superadmin access to this page
if(!isset($admin_id) || $admin_role !== 'superadmin'){
   $_SESSION['message'] = 'Please login as a superadmin to access this content.';
   header('location:../admin/admin_login.php');
   exit(); // Stop further execution
}

// Initialize message array
$message = [];

// Get filter parameter for status
$filter_category = $_GET['category'] ?? 'all';
$filter_category = filter_var($filter_category, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// Get search parameter
$search = $_GET['search'] ?? '';
$search = filter_var($search, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// Handle magazine addition
if(isset($_POST['add_magazine'])) {
    $title = filter_var($_POST['title'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $author = filter_var($_POST['author'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $context = $_POST['context']; // Allow rich text
    $link = filter_var($_POST['link'], FILTER_SANITIZE_URL);
    $category = filter_var($_POST['category'], FILTER_SANITIZE_NUMBER_INT);
    
    // Validate required fields
    if(empty($title)) {
        $message[] = 'Magazine title is required!';
    } elseif(empty($author)) {
        $message[] = 'Author name is required!';
    } elseif(empty($context)) {
        $message[] = 'Context/description is required!';
    } elseif(empty($link)) {
        $message[] = 'Magazine link is required!';
    } elseif(empty($category)) {
        $message[] = 'Please select a category!';
    } else {
        $image = $_FILES['image']['name'];
        $image = filter_var($image, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $image_size = $_FILES['image']['size'];
        $image_tmp_name = $_FILES['image']['tmp_name'];
        $image_folder = '../uploaded_img/'.$image;
        $image_extension = strtolower(pathinfo($image, PATHINFO_EXTENSION));
        
        if(!empty($image)) {
            // Check image size
            if($image_size > 2000000) {
                $message[] = 'Image size is too large (max 2MB)!';
            } 
            // Check file extension
            elseif(!in_array($image_extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                $message[] = 'Invalid image format! Please upload JPG, JPEG, PNG, or GIF.';
            } else {
                move_uploaded_file($image_tmp_name, $image_folder);
            }
        } else {
            $image = ''; // Set to empty if no image uploaded
        }
        
        // If no error message
        if(empty($message)) {
            // Insert the magazine
            try {
                $insert_magazine = $conn->prepare("INSERT INTO `e_magazines` (title, author, context, link, image, created_by, category_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $insert_magazine->execute([$title, $author, $context, $link, $image, $admin_id, $category]);
                $message[] = 'E-Magazine published successfully!';
            } catch(PDOException $e) {
                $message[] = 'Error: ' . $e->getMessage();
            }
        }
    }
}

// Handle magazine deletion
if(isset($_POST['delete_magazine'])) {
    $magazine_id = filter_var($_POST['magazine_id'], FILTER_SANITIZE_NUMBER_INT);
    
    // Get the image filename before deleting
    $select_image = $conn->prepare("SELECT image FROM `e_magazines` WHERE magazine_id = ?");
    $select_image->execute([$magazine_id]);
    $fetch_image = $select_image->fetch(PDO::FETCH_ASSOC);
    
    // Delete the magazine
    $delete_magazine = $conn->prepare("DELETE FROM `e_magazines` WHERE magazine_id = ?");
    $delete_magazine->execute([$magazine_id]);
    
    if($delete_magazine->rowCount() > 0) {
        // Delete the image file if it exists
        if(!empty($fetch_image['image'])) {
            $image_path = '../uploaded_img/'.$fetch_image['image'];
            if(file_exists($image_path)) {
                unlink($image_path);
            }
        }
        $message[] = 'E-Magazine deleted successfully!';
    } else {
        $message[] = 'E-Magazine already deleted or not found!';
    }
}

// Fetch all categories for dropdown
$select_categories = $conn->prepare("SELECT * FROM `category` ORDER BY name ASC");
$select_categories->execute();
$categories = $select_categories->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>E-Magazine Management | Superadmin</title>

   <!-- Font Awesome CDN -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- Custom CSS File -->
   <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<?php include '../components/superadmin_header.php'; ?>

<section class="magazines">
   <h1 class="heading">Manage E-Magazines</h1>
   <h2>Note: Make sure the magazine covers are same sizes for better view</h2>
   
   <?php
   if(!empty($message)){
      foreach($message as $msg){
         echo '<div class="message"><span>'.$msg.'</span><i class="fas fa-times" onclick="this.parentElement.remove();"></i></div>';
      }
   }
   ?>
   
   <!-- Add Magazine Form -->
   <div class="form-container">
      <form action="" method="post" enctype="multipart/form-data">
         <h3>Add New E-Magazine</h3>
         
         <div class="flex">
            <div class="input-field">
               <input type="text" name="title" maxlength="255" required placeholder="Magazine Title *" class="box">
            </div>
            
            <div class="input-field">
               <input type="text" name="author" maxlength="255" required placeholder="Author Name *" class="box">
            </div>
            
            <div class="input-field">
               <select name="category" class="box" required>
                  <option value="" selected disabled>Select Category *</option>
                  <?php foreach($categories as $category): ?>
                     <option value="<?= $category['category_id']; ?>"><?= htmlspecialchars($category['name']); ?></option>
                  <?php endforeach; ?>
               </select>
            </div>
            
            <div class="input-field">
               <input type="url" name="link" required placeholder="Magazine URL (PDF link) *" class="box">
            </div>
            
            <div class="input-field">
               <input type="file" name="image" accept="image/*" class="box" style="padding-top: 10px;" placeholder="Cover Image">
               <small style="display: block; margin-top: 5px; color: var(--light-color);">Upload Cover Image (optional)</small>
            </div>
         </div>
         
         <div class="input-field">
            <textarea name="context" class="box" required placeholder="Magazine Description or Summary *" cols="30" rows="10"></textarea>
         </div>
         
         <div class="flex-btn">
            <input type="submit" value="Add Magazine" name="add_magazine" class="btn">
            <a href="sa_manage_emagazines.php" class="option-btn">Cancel</a>
         </div>
      </form>
   </div>
   
   <!-- Magazine Management Section -->
   <div class="management-section">
      <h2 class="sub-heading">E-Magazine Library</h2>
      
      <!-- Filter Form -->
      <div class="filter-container">
         <form action="" method="GET" class="filter-form">
            <div class="input-field">
               <select name="category" class="box" onchange="this.form.submit()">
                  <option value="all" <?= ($filter_category === 'all') ? 'selected' : ''; ?>>All Categories</option>
                  <?php foreach($categories as $category): ?>
                     <option value="<?= $category['category_id']; ?>" <?= ($filter_category == $category['category_id']) ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($category['name']); ?>
                     </option>
                  <?php endforeach; ?>
               </select>
            </div>
            
            <div class="input-field">
               <input type="text" name="search" placeholder="Search by title or author..." class="box" value="<?= htmlspecialchars($search); ?>">
            </div>
            
            <button type="submit" class="inline-btn">Apply Filters</button>
            <a href="sa_manage_emagazines.php" class="inline-option-btn">Reset</a>
         </form>
      </div>
      
      <!-- Magazine List -->
      <div class="magazine-grid">
         <?php
            // Building the query with filters
            $query = "
               SELECT e.*, c.name AS category_name, a.user_name AS creator
               FROM `e_magazines` e
               LEFT JOIN `category` c ON e.category_id = c.category_id
               LEFT JOIN `accounts` a ON e.created_by = a.account_id
               WHERE 1=1
            ";
            
            $params = [];
            
            // Apply category filter
            if($filter_category !== 'all' && !empty($filter_category)) {
               $query .= " AND e.category_id = ?";
               $params[] = $filter_category;
            }
            
            // Apply search filter
            if(!empty($search)) {
               $query .= " AND (e.title LIKE ? OR e.author LIKE ? OR e.context LIKE ?)";
               $search_param = "%$search%";
               $params[] = $search_param;
               $params[] = $search_param;
               $params[] = $search_param;
            }
            
            // Order by newest first
            $query .= " ORDER BY e.created_at DESC";
            
            $select_magazines = $conn->prepare($query);
            $select_magazines->execute($params);
            
            if($select_magazines->rowCount() > 0) {
               while($fetch_magazine = $select_magazines->fetch(PDO::FETCH_ASSOC)) {
                  // Get a trimmed context for display
                  $trimmed_context = strlen($fetch_magazine['context']) > 200 ? 
                                    substr($fetch_magazine['context'], 0, 200) . '...' : 
                                    $fetch_magazine['context'];
         ?>
         <div class="magazine-item">
            <?php if(!empty($fetch_magazine['image'])): ?>
               <img src="../uploaded_img/<?= $fetch_magazine['image']; ?>" alt="<?= htmlspecialchars($fetch_magazine['title']); ?>" class="magazine-image">
            <?php else: ?>
               <div class="magazine-image" style="display:flex;align-items:center;justify-content:center;background-color:var(--light-bg);">
                  <i class="fas fa-book fa-3x" style="opacity:0.3;"></i>
               </div>
            <?php endif; ?>
            
            <div class="magazine-details">
               <span class="magazine-category"><?= htmlspecialchars($fetch_magazine['category_name']); ?></span>
               <h3 class="magazine-title"><?= htmlspecialchars($fetch_magazine['title']); ?></h3>
               
               <p class="magazine-info">
                  <i class="fas fa-user"></i>
                  <span>Author: <?= htmlspecialchars($fetch_magazine['author']); ?></span>
               </p>
               
               <p class="magazine-info">
                  <i class="fas fa-calendar"></i>
                  <span>Added: <?= date('M d, Y', strtotime($fetch_magazine['created_at'])); ?></span>
               </p>
               
               <p class="magazine-info">
                  <i class="fas fa-user-edit"></i>
                  <span>Added by: <?= htmlspecialchars($fetch_magazine['creator']); ?></span>
               </p>
               
               <div class="magazine-context">
                  <?= nl2br(htmlspecialchars($trimmed_context)); ?>
               </div>
               
               <div class="magazine-actions">
                  <a href="<?= htmlspecialchars($fetch_magazine['link']); ?>" class="btn magazine-link" target="_blank">
                     <i class="fas fa-external-link-alt"></i> View Magazine
                  </a>
                  
                  <form action="" method="post" onsubmit="return confirm('Delete this magazine? This action cannot be undone!');">
                     <input type="hidden" name="magazine_id" value="<?= $fetch_magazine['magazine_id']; ?>">
                     <button type="submit" class="delete-btn magazine-link" name="delete_magazine">
                        <i class="fas fa-trash"></i> Delete Magazine
                     </button>
                  </form>
               </div>
            </div>
         </div>
         <?php
               }
            } else {
               echo '<p class="empty">No e-magazines found!</p>';
            }
         ?>
      </div>
   </div>
</section>

<!-- Custom JS File -->
<script src="../js/admin_script.js"></script>
</body>
</html>