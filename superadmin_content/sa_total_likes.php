<?php
// Include the database connection file
include '../components/connect.php';

$db = new Database();
$conn = $db->connect();

// Start the session
session_start();

// Check if the admin is logged in and is a superadmin
$admin_id = $_SESSION['admin_id'] ?? null;
$admin_role = $_SESSION['admin_role'] ?? null;

// Only allow superadmin access to this page
if(!isset($admin_id) || $admin_role !== 'superadmin'){
   $_SESSION['message'] = 'Please login as a superadmin to access this content.';
   header('location:../admin/admin_login.php');
   exit(); // Stop further execution
}

// Fetch all published posts (since superadmin should see all posts)
$select_posts = $conn->prepare("
    SELECT posts.post_id, posts.title, posts.created_by, 
           COUNT(likes.like_id) AS total_likes,
           accounts.firstname, accounts.lastname, accounts.user_name
    FROM `posts` 
    LEFT JOIN `likes` ON posts.post_id = likes.post_id 
    LEFT JOIN `accounts` ON posts.created_by = accounts.account_id
    WHERE posts.status = 'published' 
    GROUP BY posts.post_id
    ORDER BY total_likes DESC
");
$select_posts->execute();
$posts = $select_posts->fetchAll(PDO::FETCH_ASSOC);

// Get filter parameters
$filter_admin = $_GET['admin_id'] ?? 'all';
$filter_admin = ($filter_admin === 'all') ? 'all' : filter_var($filter_admin, FILTER_SANITIZE_NUMBER_INT);

// Get all admin accounts for the filter dropdown
$select_admins = $conn->prepare("SELECT account_id, user_name, firstname, lastname, role FROM `accounts` WHERE role IN ('superadmin', 'subadmin') ORDER BY role DESC, firstname ASC");
$select_admins->execute();
$admins = $select_admins->fetchAll(PDO::FETCH_ASSOC);

// Total likes across all posts
$total_likes_query = $conn->prepare("SELECT COUNT(*) as total FROM `likes`");
$total_likes_query->execute();
$total_likes = $total_likes_query->fetch(PDO::FETCH_ASSOC)['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Likes Analysis | Superadmin</title>

    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

    <!-- Custom CSS File -->
    <link rel="stylesheet" href="../css/admin_style.css">
</head>
<body>

<?php include '../components/superadmin_header.php'; ?>

<section class="likes-analysis-section">
    <h1 class="heading">Post Likes Analysis</h1>
    
    <div class="likes-analytics-summary">
        <div class="likes-summary-card">
            <h3><?= count($posts); ?></h3>
            <p>Published Posts</p>
        </div>
        <div class="likes-summary-card likes-total-card">
            <h3><?= $total_likes; ?></h3>
            <p>Total Likes</p>
        </div>
        <div class="likes-summary-card">
            <h3><?= count($posts) > 0 ? round($total_likes / count($posts), 1) : 0; ?></h3>
            <p>Average Likes Per Post</p>
        </div>
    </div>
    
    <div class="likes-filter-panel">
        <form action="" method="GET" class="likes-filter-form">
            <div class="likes-filter-input">
                <label for="admin_id">Filter by Admin:</label>
                <select name="admin_id" id="admin_id" class="box" onchange="this.form.submit()">
                    <option value="all" <?= ($filter_admin === 'all') ? 'selected' : ''; ?>>All Admins</option>
                    <option value="<?= $admin_id; ?>" <?= ($filter_admin == $admin_id) ? 'selected' : ''; ?>>My Posts Only</option>
                    
                    <?php if(count($admins) > 0): ?>
                        <optgroup label="Filter by specific admin">
                            <?php foreach($admins as $admin): ?>
                                <?php if($admin['account_id'] != $admin_id): ?>
                                    <option value="<?= $admin['account_id']; ?>" <?= ($filter_admin == $admin['account_id']) ? 'selected' : ''; ?>>
                                        <?= htmlspecialchars($admin['firstname'] . ' ' . $admin['lastname']); ?> 
                                        (<?= htmlspecialchars($admin['user_name']); ?>) 
                                        - <?= ucfirst($admin['role']); ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                </select>
            </div>
            
            <a href="sa_total_likes.php" class="option-btn">Reset Filters</a>
        </form>
    </div>

    <div class="post-likes-container">
        <?php 
        $filtered_posts = $posts;
        
        // Apply admin filter if needed
        if ($filter_admin !== 'all') {
            $filtered_posts = array_filter($posts, function($post) use ($filter_admin) {
                return $post['created_by'] == $filter_admin;
            });
        }
        
        if (count($filtered_posts) > 0): 
        ?>
            <?php foreach ($filtered_posts as $post): ?>
                <div class="post-likes-card">
                    <div class="post-author-info">
                        <p>Posted by: 
                            <?= htmlspecialchars($post['firstname'] . ' ' . $post['lastname']); ?>
                            <span class="post-author-username"><?= htmlspecialchars($post['user_name']); ?></span>
                        </p>
                    </div>
                    
                    <h3 class="post-likes-title"><?= htmlspecialchars($post['title']); ?></h3>
                    
                    <div class="post-likes-count">
                        <i class="fas fa-heart"></i>
                        <div class="likes-counter"><?= $post['total_likes']; ?></div>
                        <p>likes</p>
                    </div>
                    
                    <a href="../user_content/view_post.php?post_id=<?= $post['post_id']; ?>" class="btn" target="_blank">View Post</a>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="empty">
                <?php if ($filter_admin !== 'all'): ?>
                    No published posts found for the selected admin!
                <?php else: ?>
                    No published posts found!
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>
</section>

<!-- Custom JS File -->
<script src="../js/admin_script.js"></script>
</body>
</html>