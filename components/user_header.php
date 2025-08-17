<?php
// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Add database connection
require_once '../components/connect.php';
$db = new Database();
$conn = $db->connect();

$account_id = $_SESSION['account_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? null;
$user_role = $_SESSION['role'] ?? null;

// Fetch "About Us" content from the database
try {
    $stmt = $conn->prepare("SELECT title, description, image FROM about_us ORDER BY about_id DESC LIMIT 1");
    $stmt->execute();
    $about_us = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Error fetching About Us content: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>The University Digest</title>
    <link rel="stylesheet" href="../css/userheader.css">
    <!-- Updated Material Icons Import -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&display=swap" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        /* Replace floating animation with flip coin animation */
        .animated-logo {
            animation: flip-coin 3s ease-in-out infinite;
            transform-style: preserve-3d;
            perspective: 1000px;
        }
        
        @keyframes flip-coin {
            0% {
                transform: rotateY(0);
            }
            45% {
                transform: rotateY(180deg);
            }
            55% {
                transform: rotateY(180deg);
            }
            100% {
                transform: rotateY(360deg);
            }
        }
        
        /* Add a slight hover effect */
        .logo-container:hover .animated-logo {
            animation-play-state: paused;
        }
    </style>
</head>
<body>
<header>
    <div class="logo-container">
        <a href="../user_content/landing_page.php">
            <?php
            // Display logo from database if available, otherwise use default
            if (!empty($about_us['image'])) {
                $logo_src = "../uploaded_img/" . htmlspecialchars($about_us['image']);
                
                // Debug: Check if file exists
                $full_path = __DIR__ . "/../uploaded_img/" . $about_us['image'];
                echo "<!-- Debug: Image from DB: " . $about_us['image'] . " -->";
                echo "<!-- Debug: Full path: " . $full_path . " -->";
                echo "<!-- Debug: File exists: " . (file_exists($full_path) ? 'YES' : 'NO') . " -->";
                
                // Try different possible paths
                $possible_paths = [
                    "../uploaded_img/" . $about_us['image'],
                    "../uploads/" . $about_us['image'],
                    "../admin/uploaded_img/" . $about_us['image'],
                    "../super_admin/uploaded_img/" . $about_us['image'],
                    "../imgs/" . $about_us['image']
                ];
                
                foreach ($possible_paths as $path) {
                    $check_path = __DIR__ . "/" . $path;
                    if (file_exists($check_path)) {
                        $logo_src = $path;
                        echo "<!-- Debug: Found image at: " . $path . " -->";
                        break;
                    }
                }
            } else {
                $logo_src = "../imgs/logo.png"; // fallback to default logo
                echo "<!-- Debug: Using default logo -->";
            }
            ?>
            <img src="<?php echo $logo_src; ?>" 
                 alt="The University Digest Logo" 
                 class="animated-logo"
                 onerror="console.log('Image failed to load: <?php echo $logo_src; ?>');">
            <div class="logo"><h3 class="about-title">
            <?php echo htmlspecialchars($about_us['title'] ?? 'About The University Digest'); ?></h3></div>
        </a>
    </div>

    <div class="header-right">
        <ul class="nav-menu">
            <li><a href="../user_content/home.php">Home</a></li>
            <li><a href="../user_content/more_announcement.php">Announcements</a></li>
            <li><a href="../user_content/authors.php">Creators</a></li>
            <li><a href="../user_content/about_us.php">About</a></li>
            <li class="dropdown">
                <a href="#" class="dropbtn">Articles</a>
                <div class="dropdown-content">
                    <a href="../user_content/news.php">News</a>
                    <a href="../user_content/comics.php">Comics</a>
                    <a href="../user_content/editorial.php">Editorial</a>
                    <a href="../user_content/misc.php">Miscellaneous</a>
                </div>
            </li>
            <li><a href="../user_content/more_tejidos.php">Tejidos</a></li>
            <li><a href="../user_content/e_magazines.php">Magazines</a></li>
        </ul>
        
        <!-- User icon -->
        <div class="user-icon-container">
            <div id="user-btn" class="fas fa-user"></div>
        </div>
    </div>
</header>

<!-- Profile dropdown -->
<div class="profile">
    <?php
        if($account_id) {
            // Using account_id instead of user_id
            $select_profile = $conn->prepare("SELECT * FROM `accounts` WHERE account_id = ?");
            $select_profile->execute([$account_id]);
            if($select_profile->rowCount() > 0){
                $fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);
                $user_role = $fetch_profile['role'];
    ?>
        <p class="name"><?= htmlspecialchars($fetch_profile['firstname'] . ' ' . $fetch_profile['lastname']); ?></p>
        
        <?php if($user_role == 'superadmin' || $user_role == 'subadmin'): ?>
            <!-- Admin-only options -->
            <?php if($user_role == 'superadmin'): ?>
                <a href="../super_admin/superadmin_dashboard.php" class="option-btn">Admin Dashboard</a>
            <?php elseif($user_role == 'subadmin'): ?>
                <a href="../admin/dashboard.php" class="option-btn">Admin Dashboard</a>
            <?php endif; ?>
        <?php else: ?>
            <!-- Regular user options -->
            <a href="update_profile.php" class="btn">Update Profile</a>
            <a href="user_likes.php" class="option-btn">Likes</a>
            <a href="user_comments.php" class="option-btn">Comments</a>
        <?php endif; ?>
        
        <!-- Logout button for all users -->
        <a href="../components/user_logout.php" onclick="return confirm('logout from this website?');" class="delete-btn">Logout</a>
    <?php
            } else {
    ?>
        <p class="name">Please login first!</p>
        <div class="flex-btn">
            <a href="../user_content/login_users.php" class="option-btn">Login</a>
            <a href="../user_content/register.php" class="option-btn">Register</a>
        </div>
    <?php
            }
        } else {
    ?>
        <p class="name">Please login first!</p>
        <div class="flex-btn">
            <a href="../user_content/login_users.php" class="option-btn">Login</a>
            <a href="../user_content/register.php" class="option-btn">Register</a>
        </div>
    <?php
        }
    ?>
</div>

<!-- Include the separate JavaScript file -->
<script src="../js/header.js"></script>

<!-- Add this JavaScript at the bottom of your file before the closing </body> tag -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get all dropdown buttons
    const dropdownBtns = document.querySelectorAll('.dropbtn');
    
    // Add click event listener to each dropdown button
    dropdownBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            // Close all dropdowns first
            document.querySelectorAll('.dropdown-content').forEach(dropdown => {
                if (dropdown !== this.nextElementSibling) {
                    dropdown.classList.remove('active');
                }
            });
            
            // Toggle current dropdown
            const dropdownContent = this.nextElementSibling;
            dropdownContent.classList.toggle('active');
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            document.querySelectorAll('.dropdown-content').forEach(dropdown => {
                dropdown.classList.remove('active');
            });
        }
    });
});
</script>

</body>
</html>