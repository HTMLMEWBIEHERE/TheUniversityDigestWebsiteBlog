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

// Get search parameter
$search = $_GET['search'] ?? '';
$search = filter_var($search, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// Sort parameter
$sort = $_GET['sort'] ?? 'newest';
$sort = filter_var($sort, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// Handle announcement addition
if(isset($_POST['add_announcement'])) {
    $title = filter_var($_POST['title'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $content = $_POST['content']; // Allow rich text
    
    // Validate required fields
    if(empty($title)) {
        $message[] = 'Announcement title is required!';
    } elseif(empty($content)) {
        $message[] = 'Announcement content is required!';
    } else {
        $image = $_FILES['image']['name'];
        $image = filter_var($image, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
        $image_size = $_FILES['image']['size'];
        $image_tmp_name = $_FILES['image']['tmp_name'];
        $image_folder = '../uploaded_img/'.$image;
        $image_extension = strtolower(pathinfo($image, PATHINFO_EXTENSION));
        
        if(!empty($image)) {
            // Check image size (2MB max)
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
            // Insert the announcement
            try {
                $insert_announcement = $conn->prepare("INSERT INTO `announcements` (title, content, image, created_by) VALUES (?, ?, ?, ?)");
                $insert_announcement->execute([$title, $content, $image, $admin_id]);
                $message[] = 'Announcement published successfully!';
            } catch(PDOException $e) {
                $message[] = 'Error: ' . $e->getMessage();
            }
        }
    }
}

// Handle announcement deletion
if(isset($_POST['delete_announcement'])) {
    $announcement_id = filter_var($_POST['announcement_id'], FILTER_SANITIZE_NUMBER_INT);
    
    // Get the image filename before deleting
    $select_image = $conn->prepare("SELECT image FROM `announcements` WHERE announcement_id = ?");
    $select_image->execute([$announcement_id]);
    $fetch_image = $select_image->fetch(PDO::FETCH_ASSOC);
    
    // Delete the announcement
    $delete_announcement = $conn->prepare("DELETE FROM `announcements` WHERE announcement_id = ?");
    $delete_announcement->execute([$announcement_id]);
    
    if($delete_announcement->rowCount() > 0) {
        // Delete the image file if it exists
        if(!empty($fetch_image['image'])) {
            $image_path = '../uploaded_img/'.$fetch_image['image'];
            if(file_exists($image_path)) {
                unlink($image_path);
            }
        }
        $message[] = 'Announcement deleted successfully!';
    } else {
        $message[] = 'Announcement already deleted or not found!';
    }
}

// Handle announcement editing
if(isset($_POST['edit_announcement'])) {
    $announcement_id = filter_var($_POST['announcement_id'], FILTER_SANITIZE_NUMBER_INT);
    $title = filter_var($_POST['title'], FILTER_SANITIZE_FULL_SPECIAL_CHARS);
    $content = $_POST['content']; // Allow rich text
    
    // Validate required fields
    if(empty($title)) {
        $message[] = 'Announcement title is required!';
    } elseif(empty($content)) {
        $message[] = 'Announcement content is required!';
    } else {
        $image = $_FILES['image']['name'];
        $image_size = $_FILES['image']['size'];
        $image_tmp_name = $_FILES['image']['tmp_name'];
        $image_folder = '../uploaded_img/'.$image;
        $image_extension = strtolower(pathinfo($image, PATHINFO_EXTENSION));
        
        $old_image = $_POST['old_image'];
        
        if(!empty($image)) {
            // Check image size
            if($image_size > 2000000) {
                $message[] = 'Image size is too large (max 2MB)!';
            } 
            // Check file extension
            elseif(!in_array($image_extension, ['jpg', 'jpeg', 'png', 'gif'])) {
                $message[] = 'Invalid image format! Please upload JPG, JPEG, PNG, or GIF.';
            } else {
                $image = filter_var($image, FILTER_SANITIZE_FULL_SPECIAL_CHARS);
                
                // If old image exists, delete it
                if(!empty($old_image)) {
                    $old_image_path = '../uploaded_img/'.$old_image;
                    if(file_exists($old_image_path)) {
                        unlink($old_image_path);
                    }
                }
                
                move_uploaded_file($image_tmp_name, $image_folder);
            }
        } else {
            $image = $old_image; // Keep old image
        }
        
        // If no error message
        if(empty($message)) {
            // Update the announcement
            try {
                $update_announcement = $conn->prepare("UPDATE `announcements` SET title = ?, content = ?, image = ? WHERE announcement_id = ?");
                $update_announcement->execute([$title, $content, $image, $announcement_id]);
                $message[] = 'Announcement updated successfully!';
            } catch(PDOException $e) {
                $message[] = 'Error: ' . $e->getMessage();
            }
        }
    }
}

// Get announcement for editing
$edit_id = $_GET['edit'] ?? null;
$edit_announcement = null;

if($edit_id) {
    $select_edit = $conn->prepare("SELECT * FROM `announcements` WHERE announcement_id = ?");
    $select_edit->execute([$edit_id]);
    if($select_edit->rowCount() > 0) {
        $edit_announcement = $select_edit->fetch(PDO::FETCH_ASSOC);
    } else {
        $message[] = 'Announcement not found!';
        $edit_id = null;
    }
}

// Count total announcements
$count_announcements = $conn->prepare("SELECT COUNT(*) FROM `announcements`");
$count_announcements->execute();
$total_announcements = $count_announcements->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Announcement Management | Superadmin</title>

   <!-- Font Awesome CDN -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- Custom CSS File -->
   <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<?php include '../components/superadmin_header.php'; ?>

<section class="announcements">
   <h1 class="heading">Manage Announcements</h1>
   
   <?php
   if(!empty($message)){
      foreach($message as $msg){
         echo '<div class="message"><span>'.$msg.'</span><i class="fas fa-times" onclick="this.parentElement.remove();"></i></div>';
      }
   }
   ?>

   <!-- Summary Statistics -->
   <div class="box-container">
      <div class="box">
         <h3><?= $total_announcements; ?></h3>
         <p>Total Announcements</p>
      </div>
   </div>
   
   <!-- Add/Edit Announcement Form -->
   <form action="" method="post" enctype="multipart/form-data" class="add-announcement-form">
      <h3><?= $edit_id ? 'Edit Announcement' : 'Add New Announcement'; ?></h3>
      
      <?php if($edit_id): ?>
         <input type="hidden" name="announcement_id" value="<?= $edit_announcement['announcement_id']; ?>">
         <input type="hidden" name="old_image" value="<?= $edit_announcement['image']; ?>">
      <?php endif; ?>
      
      <div class="input-field">
         <input type="text" name="title" maxlength="255" required placeholder="Announcement Title *" class="box" value="<?= $edit_id ? htmlspecialchars($edit_announcement['title']) : ''; ?>">
      </div>
      
      <div class="input-field">
         <textarea name="content" class="box" required placeholder="Announcement Content *" cols="30" rows="10"><?= $edit_id ? htmlspecialchars($edit_announcement['content']) : ''; ?></textarea>
      </div>
      
      <div class="input-field">
         <input type="file" name="image" accept="image/*" class="box" style="padding-top: 1.4rem;">
         <small style="display: block; margin-top: 0.5rem; color: var(--light-color); font-size: 1.4rem;">Upload Image (optional)</small>
         
         <?php if($edit_id && !empty($edit_announcement['image'])): ?>
            <div class="current-image">
               <p>Current Image:</p>
               <img src="../uploaded_img/<?= $edit_announcement['image']; ?>" alt="Current image" style="max-width: 10rem; margin-top: 1rem;">
            </div>
         <?php endif; ?>
      </div>
      
      <div class="flex-btn">
         <?php if($edit_id): ?>
            <input type="submit" value="Update Announcement" name="edit_announcement" class="btn">
         <?php else: ?>
            <input type="submit" value="Add Announcement" name="add_announcement" class="btn">
         <?php endif; ?>
      </div>
   </form>
   
   <!-- Announcement Management Section -->
   <div class="management-section">
      <h1 class="heading">All Announcements</h1>
      
      <!-- Filter Form -->
      <div class="filter-container">
         <form action="" method="GET" class="filter-form">
            <div class="input-field">
               <input type="text" name="search" placeholder="Search announcements..." class="box" value="<?= htmlspecialchars($search); ?>">
            </div>
            
            <div class="input-field">
               <select name="sort" class="box" onchange="this.form.submit()">
                  <option value="newest" <?= ($sort === 'newest') ? 'selected' : ''; ?>>Newest First</option>
                  <option value="oldest" <?= ($sort === 'oldest') ? 'selected' : ''; ?>>Oldest First</option>
                  <option value="a-z" <?= ($sort === 'a-z') ? 'selected' : ''; ?>>A-Z</option>
                  <option value="z-a" <?= ($sort === 'z-a') ? 'selected' : ''; ?>>Z-A</option>
               </select>
            </div>
            
            <button type="submit" class="inline-btn">Apply Filters</button>
            <a href="sa_manage_announcements.php" class="inline-option-btn">Reset</a>
         </form>
      </div>
      
      <!-- Announcement List -->
      <div class="announcement-grid">
         <?php
            // Building the query with filters
            $query = "
               SELECT a.*, acc.user_name AS creator_name,
                      CONCAT(acc.firstname, ' ', acc.lastname) AS creator_fullname
               FROM `announcements` a
               LEFT JOIN `accounts` acc ON a.created_by = acc.account_id
               WHERE 1=1
            ";
            
            $params = [];
            
            // Apply search filter
            if(!empty($search)) {
               $query .= " AND (a.title LIKE ? OR a.content LIKE ?)";
               $search_param = "%$search%";
               $params[] = $search_param;
               $params[] = $search_param;
            }
            
            // Apply sort
            switch($sort) {
               case 'oldest':
                  $query .= " ORDER BY a.created_at ASC";
                  break;
               case 'a-z':
                  $query .= " ORDER BY a.title ASC";
                  break;
               case 'z-a':
                  $query .= " ORDER BY a.title DESC";
                  break;
               case 'newest':
               default:
                  $query .= " ORDER BY a.created_at DESC";
                  break;
            }
            
            $select_announcements = $conn->prepare($query);
            $select_announcements->execute($params);
            
            if($select_announcements->rowCount() > 0) {
               while($fetch_announcement = $select_announcements->fetch(PDO::FETCH_ASSOC)) {
                  // Get a trimmed content for display
                  $trimmed_content = strlen($fetch_announcement['content']) > 150 ? 
                                  substr($fetch_announcement['content'], 0, 150) . '...' : 
                                  $fetch_announcement['content'];
         ?>
         <div class="announcement-item">
            <?php if(!empty($fetch_announcement['image'])): ?>
               <img src="../uploaded_img/<?= $fetch_announcement['image']; ?>" alt="<?= htmlspecialchars($fetch_announcement['title']); ?>" class="announcement-image">
            <?php else: ?>
               <div class="announcement-image" style="display:flex;align-items:center;justify-content:center;background-color:var(--light-bg);">
                  <i class="fas fa-bullhorn fa-3x" style="opacity:0.3;"></i>
               </div>
            <?php endif; ?>
            
            <div class="announcement-details">
               <h3 class="announcement-title"><?= htmlspecialchars($fetch_announcement['title']); ?></h3>
               
               <div class="announcement-meta">
                  <span><i class="fas fa-calendar"></i> <?= date('M d, Y', strtotime($fetch_announcement['created_at'])); ?></span>
                  <span><i class="fas fa-user"></i> <?= htmlspecialchars($fetch_announcement['creator_fullname']); ?> (<?= htmlspecialchars($fetch_announcement['creator_name']); ?>)</span>
               </div>
               
               <div class="announcement-content">
                  <?= nl2br(htmlspecialchars($trimmed_content)); ?>
               </div>
               
               <div class="announcement-actions">
                  <a href="?edit=<?= $fetch_announcement['announcement_id']; ?>" class="option-btn">
                     <i class="fas fa-edit"></i> Edit
                  </a>
                  
                  <form action="" method="post" onsubmit="return confirm('Delete this announcement? This action cannot be undone!');">
                     <input type="hidden" name="announcement_id" value="<?= $fetch_announcement['announcement_id']; ?>">
                     <button type="submit" class="delete-btn" name="delete_announcement">
                        <i class="fas fa-trash"></i> Delete
                     </button>
                  </form>
               </div>
            </div>
         </div>
         <?php
               }
            } else {
               echo '<p class="empty">No announcements found!</p>';
            }
         ?>
      </div>
   </div>
</section>

<!-- Custom JS File -->
<script src="../js/admin_script.js"></script>
</body>
</html>