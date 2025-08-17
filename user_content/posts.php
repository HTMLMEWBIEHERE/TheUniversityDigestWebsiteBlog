<?php
include '../components/connect.php';

$db = new Database();
$conn = $db->connect();

session_start();

if (isset($_SESSION['account_id'])) {
    $account_id = $_SESSION['account_id'];
} else {
    $account_id = '';
}

// Remove or comment out this line since the file doesn't exist
?>  

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Posts</title>

    <!-- Font Awesome CDN link -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

    <link rel="stylesheet" href="../css/style.css">
</head>
<body>

<?php include '../components/user_header.php'; // Fix: changed from use_header.php to user_header.php ?>

<section class="posts-container">
    <h1 class="heading">All Posts</h1>

    <!-- Post Filters -->
    <div class="post-filters">
        <!-- Add search bar -->
        <div class="filter-container search-container">
            <form action="" method="GET" class="search-form">
                <input type="text" name="search" placeholder="Search posts..." value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                <button type="submit"><i class="fas fa-search"></i></button>
                
                <!-- Preserve other filter parameters when searching -->
                <?php if(isset($_GET['sort']) && !empty($_GET['sort'])): ?>
                    <input type="hidden" name="sort" value="<?= htmlspecialchars($_GET['sort'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php endif; ?>
                
                <?php if(isset($_GET['category']) && !empty($_GET['category'])): ?>
                    <input type="hidden" name="category" value="<?= htmlspecialchars($_GET['category'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php endif; ?>
            </form>
        </div>

        <div class="filter-container">
            <label for="post-sort">Sort By:</label>
            <select id="post-sort" name="sort" onchange="this.form.submit()">
                <option value="newest" <?= isset($_GET['sort']) && $_GET['sort'] == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                <option value="oldest" <?= isset($_GET['sort']) && $_GET['sort'] == 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                <option value="a-z" <?= isset($_GET['sort']) && $_GET['sort'] == 'a-z' ? 'selected' : ''; ?>>Title (A-Z)</option>
                <option value="z-a" <?= isset($_GET['sort']) && $_GET['sort'] == 'z-a' ? 'selected' : ''; ?>>Title (Z-A)</option>
                <option value="most-liked" <?= isset($_GET['sort']) && $_GET['sort'] == 'most-liked' ? 'selected' : ''; ?>>Most Liked</option>
                <option value="most-commented" <?= isset($_GET['sort']) && $_GET['sort'] == 'most-commented' ? 'selected' : ''; ?>>Most Commented</option>
            </select>
        </div>
        
        <div class="filter-container">
            <label for="category-filter">Category:</label>
            <select id="category-filter" name="category" onchange="this.form.submit()">
                <option value="">All Categories</option>
                <?php
                // Fetch all categories
                $select_categories = $conn->prepare("SELECT * FROM `category` ORDER BY name ASC");
                $select_categories->execute();
                while($category = $select_categories->fetch(PDO::FETCH_ASSOC)){
                    $selected = (isset($_GET['category']) && $_GET['category'] == $category['category_id']) ? 'selected' : '';
                    echo "<option value='".$category['category_id']."' $selected>".htmlspecialchars($category['name'])."</option>";
                }
                ?>
            </select>
        </div>
    </div>

    <div class="box-container">
        <?php
        try {
            // Get the filter values and search option
            $sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
            $category_filter = isset($_GET['category']) && !empty($_GET['category']) ? $_GET['category'] : null;
            $search_term = isset($_GET['search']) && !empty($_GET['search']) ? $_GET['search'] : null;
            // Base query
            $query = "
                SELECT posts.*, 
                       accounts.user_name AS author_user_name,
                       accounts.image AS author_image,
                       accounts.role AS author_role,
                       category.name AS category_name,
                       (SELECT COUNT(*) FROM `comments` WHERE post_id = posts.post_id) AS comment_count,
                       (SELECT COUNT(*) FROM `likes` WHERE post_id = posts.post_id) AS like_count
                FROM `posts` 
                JOIN `accounts` ON posts.created_by = accounts.account_id 
                JOIN `category` ON posts.category_id = category.category_id 
                WHERE posts.status = 'published'";
            
            // Add category filter if selected
            if($category_filter) {
                $query .= " AND posts.category_id = :category_id";
            }
            //Search Filter
            if($search_term) {
                $query .= " AND (posts.title LIKE :search_term OR posts.content LIKE :search_term)";
            }
            
            // Add order by clause based on sort option
            switch($sort) {
                case 'oldest':
                    $query .= " ORDER BY posts.created_at ASC";
                    break;
                case 'a-z':
                    $query .= " ORDER BY posts.title ASC";
                    break;
                case 'z-a':
                    $query .= " ORDER BY posts.title DESC";
                    break;
                case 'most-liked':
                    $query .= " ORDER BY like_count DESC, posts.created_at DESC";
                    break;
                case 'most-commented':
                    $query .= " ORDER BY comment_count DESC, posts.created_at DESC";
                    break;
                default: // newest
                    $query .= " ORDER BY posts.created_at DESC";
            }
            
            $select_posts = $conn->prepare($query);
            
            // Bind category parameter
            if($category_filter) {
                $select_posts->bindParam(':category_id', $category_filter);
            }
            //bind search parameter
            if($search_term) {
                $search_param = "%{$search_term}%";
                $select_posts->bindParam(':search_term', $search_param);
            }
            
            $select_posts->execute();

            if ($select_posts->rowCount() > 0) {
                while ($fetch_posts = $select_posts->fetch(PDO::FETCH_ASSOC)) {
                    $post_id = $fetch_posts['post_id'];

                    // Count comments for the post
                    $count_post_comments = $conn->prepare("SELECT * FROM `comments` WHERE post_id = ?");
                    $count_post_comments->execute([$post_id]);
                    $total_post_comments = $count_post_comments->rowCount();

                    // Count likes for the post
                    $count_post_likes = $conn->prepare("SELECT * FROM `likes` WHERE post_id = ?");
                    $count_post_likes->execute([$post_id]);
                    $total_post_likes = $count_post_likes->rowCount();

                    // Check if the current user has liked the post
                    $confirm_likes = $conn->prepare("SELECT * FROM `likes` WHERE account_id = ? AND post_id = ?");
                    $confirm_likes->execute([$account_id, $post_id]);

                    // Truncate content to 2-3 sentences
                    $content = $fetch_posts['content'];
                    $content_preview = implode('. ', array_slice(explode('. ', $content), 0, 2)) . '.';
        ?>
                    <form class="box" method="post">
                        <input type="hidden" name="post_id" value="<?= $post_id; ?>">
                        <input type="hidden" name="account_id" value="<?= $fetch_posts['created_by']; ?>">
                        <div class="post-admin">
                            <?php if(!empty($fetch_posts['author_image']) && 
                                     ($fetch_posts['author_role'] == 'superadmin' || $fetch_posts['author_role'] == 'subadmin')): ?>
                                <img src="../uploads/profiles/<?= htmlspecialchars($fetch_posts['author_image'], ENT_QUOTES, 'UTF-8'); ?>" class="author-profile-pic" alt="Admin Profile">
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                            <div>
                                <a href="author_posts.php?author=<?= $fetch_posts['created_by']; ?>">
                                    <?= htmlspecialchars($fetch_posts['author_user_name'], ENT_QUOTES, 'UTF-8'); ?>
                                </a>
                                <div><?= date('M j, Y h:i A', strtotime($fetch_posts['created_at'])); ?></div>
                            </div>
                        </div>

                        <?php if ($fetch_posts['image'] != '') { ?>
                            <img src="../uploaded_img/<?= htmlspecialchars($fetch_posts['image'], ENT_QUOTES, 'UTF-8'); ?>" class="post-image" alt="">
                        <?php } ?>

                        <div class="post-title"><?= htmlspecialchars($fetch_posts['title'] ?? 'No Title', ENT_QUOTES, 'UTF-8'); ?></div>
                        <div class="post-content content-150"><?= htmlspecialchars($content_preview, ENT_QUOTES, 'UTF-8'); ?></div>
                        <a href="view_post.php?post_id=<?= htmlspecialchars($post_id, ENT_QUOTES, 'UTF-8'); ?>" class="inline-btn">Read More</a>
                        <a href="category.php?category=<?= htmlspecialchars($fetch_posts['category_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="post-cat">
                            <i class="fas fa-tag"></i> <span><?= htmlspecialchars($fetch_posts['category_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></span>
                        </a>
                        <div class="icons">
                            <a href="view_post.php?post_id=<?= htmlspecialchars($post_id, ENT_QUOTES, 'UTF-8'); ?>">
                                <i class="fas fa-comment"></i><span>(<?= $total_post_comments; ?>)</span>
                            </a>
                            <div class="like-btn" style="cursor:pointer;" data-post-id="<?= $post_id; ?>">
                                <i class="fas fa-heart" style="<?= ($confirm_likes->rowCount() > 0) ? 'color:var(--red);' : ''; ?>"></i>
                                <span>(<?= $total_post_likes; ?>)</span>
                            </div>
                        </div>
                    </form>
        <?php
                }
            } else {
                echo '<p class="empty">No posts added yet!</p>';
            }
        } catch (PDOException $e) {
            die("Error fetching posts: " . $e->getMessage()); // Display query error
        }
        ?>
    </div>
</section>
<!-- Include Footer -->
<?php include '../components/footer.php'; ?>

<!-- Scripts -->
<script src="../js/post_filter.js"></script>
<script src="../js/likes.js"></script>
<script src="../js/script.js"></script>
</body>
</html>