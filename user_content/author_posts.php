<?php
include '../components/connect.php'; // Fixed path with ../

$db = new Database();
$conn = $db->connect();

session_start();

// Initialize $user_id for non-logged-in users
$user_id = $_SESSION['account_id'] ?? ''; // Changed from user_id to account_id for consistency

// Get author information
if (isset($_GET['author'])) {
    $author = $_GET['author'];
    
    // First, check if the parameter is numeric (ID) or a username
    if (is_numeric($author)) {
        // It's an ID
        $author_id = $author;
        
        // Get author username for display
        $get_author = $conn->prepare("SELECT user_name FROM accounts WHERE account_id = ?");
        $get_author->execute([$author_id]);
        $author_name = $get_author->fetch(PDO::FETCH_COLUMN);
    } else {
        // It's a username
        $get_author = $conn->prepare("SELECT account_id, user_name FROM accounts WHERE user_name = ?");
        $get_author->execute([$author]);
        if ($get_author->rowCount() > 0) {
            $author_data = $get_author->fetch(PDO::FETCH_ASSOC);
            $author_id = $author_data['account_id'];
            $author_name = $author_data['user_name'];
        } else {
            $author_id = '';
            $author_name = 'Unknown Author';
        }
    }
} else {
    $author_id = '';
    $author_name = '';
    header('Location: home.php'); // Redirect if no author specified
    exit();
}

// Process post likes
include '../components/like_post.php'; // Fixed path
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Posts by <?= htmlspecialchars($author_name) ?></title>

    <!-- Font Awesome CDN link -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

    <!-- Custom CSS File -->
    <link rel="stylesheet" href="../css/style.css"> <!-- Fixed path -->
    <link rel="stylesheet" href="../css/userheader.css"> <!-- Added userheader css -->
    
    <style>
        .author-profile-pic {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
        }
    </style>
</head>
<body>

<!-- Header Section -->
<?php include '../components/user_header.php'; ?> <!-- Fixed path -->

<section class="posts-container">
    <h1 class="heading">Posts by <?= htmlspecialchars($author_name) ?></h1>
    
    <div class="filter-container">
        <form action="" method="GET" class="filter-form">
            <input type="hidden" name="author" value="<?= htmlspecialchars($author) ?>">
            
            <div class="filter-group">
                <label for="category">Category:</label>
                <select name="category" id="category">
                    <option value="">All Categories</option>
                    <?php
                    // Fetch available categories
                    $cat_query = $conn->prepare("
                        SELECT DISTINCT c.category_id, c.name
                        FROM category c
                        JOIN posts p ON p.category_id = c.category_id
                        WHERE p.created_by = ? AND p.status = 'published'
                        ORDER BY c.name ASC
                    ");
                    $cat_query->execute([$author_id]);
                    while ($category = $cat_query->fetch(PDO::FETCH_ASSOC)) {
                        $selected = (isset($_GET['category']) && $_GET['category'] == $category['category_id']) ? 'selected' : '';
                        echo "<option value='" . $category['category_id'] . "' $selected>" . htmlspecialchars($category['name']) . "</option>";
                    }
                    ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="search">Search:</label>
                <input type="text" name="search" id="search" placeholder="Search in posts..." 
                       value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>">
            </div>
            
            <div class="filter-group">
                <label for="sort">Sort By:</label>
                <select name="sort" id="sort">
                    <option value="newest" <?= (isset($_GET['sort']) && $_GET['sort'] == 'newest') ? 'selected' : '' ?>>Newest First</option>
                    <option value="oldest" <?= (isset($_GET['sort']) && $_GET['sort'] == 'oldest') ? 'selected' : '' ?>>Oldest First</option>
                </select>
            </div>
            
            <button type="submit" class="filter-btn">Apply Filters</button>
            <?php if (isset($_GET['category']) || isset($_GET['search']) || isset($_GET['sort'])): ?>
                <a href="author_posts.php?author=<?= htmlspecialchars($author) ?>" class="clear-filter-btn">Clear Filters</a>
            <?php endif; ?>
        </form>
    </div>
    
    <div class="box-container">
        <?php
        try {
            // Modify your SQL query to include filters
            $query = "
                SELECT posts.*, 
                       accounts.user_name AS author_name,
                       accounts.image AS author_image,
                       accounts.role AS author_role,
                       category.name AS category_name
                FROM `posts` 
                JOIN `accounts` ON posts.created_by = accounts.account_id 
                LEFT JOIN `category` ON posts.category_id = category.category_id
                WHERE posts.created_by = ? AND posts.status = 'published'
            ";

            $params = [$author_id];

            // Add category filter if selected
            if (isset($_GET['category']) && !empty($_GET['category'])) {
                $query .= " AND posts.category_id = ?";
                $params[] = $_GET['category'];
            }

            // Add search filter if provided
            if (isset($_GET['search']) && !empty($_GET['search'])) {
                $search_term = '%' . $_GET['search'] . '%';
                $query .= " AND (posts.title LIKE ? OR posts.content LIKE ?)";
                $params[] = $search_term;
                $params[] = $search_term;
            }

            // Add sorting
            if (isset($_GET['sort']) && $_GET['sort'] == 'oldest') {
                $query .= " ORDER BY posts.created_at ASC";
            } else {
                $query .= " ORDER BY posts.created_at DESC";
            }

            $select_posts = $conn->prepare($query);
            $select_posts->execute($params);

            if ($select_posts->rowCount() > 0) {
                while ($fetch_posts = $select_posts->fetch(PDO::FETCH_ASSOC)) {
                    $post_id = $fetch_posts['post_id'];

                    // Count comments for the post - optimized query
                    $count_post_comments = $conn->prepare("SELECT COUNT(*) FROM `comments` WHERE post_id = ?");
                    $count_post_comments->execute([$post_id]);
                    $total_post_comments = $count_post_comments->fetchColumn();

                    // Count likes for the post - optimized query
                    $count_post_likes = $conn->prepare("SELECT COUNT(*) FROM `likes` WHERE post_id = ?");
                    $count_post_likes->execute([$post_id]);
                    $total_post_likes = $count_post_likes->fetchColumn();

                    // Check if the current user has liked the post
                    $liked = false;
                    if (!empty($user_id)) {
                        $confirm_likes = $conn->prepare("SELECT 1 FROM `likes` WHERE account_id = ? AND post_id = ?");
                        $confirm_likes->execute([$user_id, $post_id]);
                        $liked = $confirm_likes->rowCount() > 0;
                    }

                    // Truncate content to 2-3 sentences
                    $content = $fetch_posts['content'];
                    $content_preview = implode('. ', array_slice(explode('. ', $content), 0, 2)) . '.';
        ?>
                <form class="box" method="post">
                    <input type="hidden" name="post_id" value="<?= $post_id; ?>">
                    <input type="hidden" name="admin_id" value="<?= $fetch_posts['created_by']; ?>">
                    <div class="post-admin">
                        <?php if(!empty($fetch_posts['author_image']) && 
                                 ($fetch_posts['author_role'] == 'superadmin' || $fetch_posts['author_role'] == 'subadmin')): ?>
                            <img src="../uploads/profiles/<?= $fetch_posts['author_image']; ?>" class="author-profile-pic" alt="Admin Profile">
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                        <div>
                            <a href="author_posts.php?author=<?= $fetch_posts['created_by']; ?>">
                                <?= htmlspecialchars($fetch_posts['author_name']); ?>
                            </a>
                            <div>
                                <!-- Removed calendar icon, keeping just the date -->
                                <span><?= date('M j, Y', strtotime($fetch_posts['created_at'])); ?></span>
                                
                                <?php if(!empty($fetch_posts['category_name'])): ?>
                                <!-- Removed tags icon, keeping just the category name -->
                                <span><?= htmlspecialchars($fetch_posts['category_name']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($fetch_posts['image'])): ?>
                        <img src="../uploaded_img/<?= $fetch_posts['image']; ?>" class="post-image" alt=""> <!-- Fixed path -->
                    <?php endif; ?>

                    <div class="post-title"><?= htmlspecialchars($fetch_posts['title']); ?></div>
                    <div class="post-content content-150">
                        <?= nl2br(htmlspecialchars($content_preview)); ?>
                    </div>
                    <a href="view_post.php?post_id=<?= $post_id; ?>" class="inline-btn">read more</a>
                    <div class="icons">
                        <a href="view_post.php?post_id=<?= $post_id; ?>">
                            <i class="fas fa-comment"></i><span>(<?= $total_post_comments; ?>)</span>
                        </a>
                        <?php if($user_id): ?>
                            <div class="like-btn" data-post-id="<?= $post_id; ?>">
                                <i class="fas fa-heart" style="<?= $liked ? 'color:var(--red);' : ''; ?>"></i>
                                <span id="likes-count-<?= $post_id; ?>">(<?= $total_post_likes; ?>)</span>
                            </div>
                        <?php else: ?>
                            <a href="login_users.php" class="like-btn">
                                <i class="fas fa-heart"></i>
                                <span>(<?= $total_post_likes; ?>)</span>
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
        <?php
                }
            } else {
                echo '<p class="empty">No posts found for this author!</p>';
            }
        } catch (PDOException $e) {
            echo '<p class="empty">Error loading posts: ' . htmlspecialchars($e->getMessage()) . '</p>';
        }
        ?>
    </div>
</section>

<!-- Include footer -->
<?php include '../components/footer.php'; ?>

<!-- Add jQuery if it's needed by likes.js -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Add your scripts -->
<script src="../js/script.js"></script>
<script src="../js/likes.js"></script>
<script src="../js/header.js"></script>

</body>
</html>