<?php
// Include database connection
include '../components/connect.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize variables
$db = new Database();
$conn = $db->connect();
$category_name = "Miscellaneous"; // Page title

// Get the miscellaneous category ID from database
$get_misc_category = $conn->prepare("SELECT category_id FROM category WHERE name LIKE '%miscellaneous%' LIMIT 1");
$get_misc_category->execute();
if($get_misc_category->rowCount() > 0) {
    $current_category = $get_misc_category->fetchColumn();
} else {
    $current_category = 5; // Fallback ID - adjust this to your actual miscellaneous category ID
}

// Search and sort functionality
$search_term = isset($_GET['search']) ? filter_var($_GET['search'], FILTER_SANITIZE_FULL_SPECIAL_CHARS) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build query for miscellaneous articles
$query = "SELECT a.*, 
          CONCAT(ac.firstname, ' ', COALESCE(ac.middlename, ''), ' ', ac.lastname) AS author_name 
          FROM `articles` a 
          LEFT JOIN `accounts` ac ON a.created_by = ac.account_id
          LEFT JOIN `category` c ON a.category_id = c.category_id
          WHERE a.category_id = :category_id AND a.status = 'published'";

// Apply search filter if provided
if (!empty($search_term)) {
    $query .= " AND (a.title LIKE :search OR a.content LIKE :search)";
}

// Apply sorting
switch ($sort) {
    case 'oldest':
        $query .= " ORDER BY a.created_at ASC";
        break;
    case 'a-z':
        $query .= " ORDER BY a.title ASC";
        break;
    case 'z-a':
        $query .= " ORDER BY a.title DESC";
        break;
    case 'most-viewed':
        $query .= " ORDER BY a.views DESC";
        break;
    case 'newest':
    default:
        $query .= " ORDER BY a.created_at DESC";
        break;
}

// Prepare and execute the query
$select_misc = $conn->prepare($query);
$select_misc->bindParam(':category_id', $current_category, PDO::PARAM_INT);

if (!empty($search_term)) {
    $search_param = "%$search_term%";
    $select_misc->bindParam(':search', $search_param, PDO::PARAM_STR);
}

$select_misc->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $category_name ?> | The University Digest</title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="../css/articles.css">
    <link rel="stylesheet" href="../css/userheader.css">
    <link rel="stylesheet" href="../css/footer.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    
    <!-- Material Icons -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>

<?php include_once '../components/user_header.php'; ?>

<div class="articles-section">
    <div class="article-card-wrapper">
        <div class="card-articles-header"><?= strtoupper($category_name) ?></div>
        
        <!-- Filter Form -->
        <div class="filter-container">
            <form action="" method="GET" class="filter-form">
                <div class="input-field">
                    <input type="text" name="search" placeholder="Search miscellaneous articles..." class="box" value="<?= htmlspecialchars($search_term); ?>">
                </div>
                <div class="input-field">
                    <select name="sort" class="box" onchange="this.form.submit()">
                        <option value="newest" <?= ($sort === 'newest') ? 'selected' : ''; ?>>Newest First</option>
                        <option value="oldest" <?= ($sort === 'oldest') ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="a-z" <?= ($sort === 'a-z') ? 'selected' : ''; ?>>A-Z</option>
                        <option value="z-a" <?= ($sort === 'z-a') ? 'selected' : ''; ?>>Z-A</option>
                        <option value="most-viewed" <?= ($sort === 'most-viewed') ? 'selected' : ''; ?>>Most Viewed</option>
                    </select>
                </div>

                <div class="button-container">
                    <button type="submit" class="inline-btn">Apply Filters</button>
                    <a href="misc.php" class="inline-option-btn">Reset</a>
                </div>
            </form>
        </div>

        <!-- Miscellaneous Articles Display Section -->
        <section class="articles">
            <div class="card-articles-container">
                <?php
                if ($select_misc->rowCount() > 0) {
                    $misc_articles = $select_misc->fetchAll(PDO::FETCH_ASSOC);

                    foreach ($misc_articles as $article) {
                        ?>
                        <div class="card-article">
                            <div class="card-article-body">
                                <h2 class="card-article-title"><?= htmlspecialchars($article['title']); ?></h2>
                                
                                <?php if (!empty($article['image'])): ?>
                                    <img class="card-article-image" src="../uploaded_img/<?= htmlspecialchars($article['image']); ?>" alt="<?= htmlspecialchars($article['title']); ?>">
                                <?php endif; ?>
                                
                                <!-- Short excerpt that's visible by default -->
                                <div class="card-article-excerpt short-content">
                                    <?= htmlspecialchars(substr($article['content'], 0, 300)) . '...'; ?>
                                </div>
                                
                                <!-- Full content that's initially hidden -->
                                <div class="card-article-full-content" style="display: none;">
                                    <?= nl2br(htmlspecialchars($article['content'])); ?>
                                </div>
                                
                                <div class="card-article-footer">
                                    <span class="article-author">By: <?= htmlspecialchars($article['author_name'] ?? 'Unknown'); ?></span>
                                    <span class="article-date"><?= date('M j, Y', strtotime($article['created_at'])); ?></span>
                                </div>
                                
                                <div class="button-container">
                                    <button class="read-more-btn toggle-content" data-article-id="<?= $article['article_id']; ?>">Read More</button>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<p class="no-articles">No miscellaneous articles available at the moment.</p>';
                }
                ?>
            </div>
        </section>
    </div>
</div>

<?php include_once '../components/footer.php'; ?>

<script src="../js/news.js"></script>

</body>
</html>