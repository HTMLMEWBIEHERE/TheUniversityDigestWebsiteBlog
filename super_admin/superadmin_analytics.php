<?php
// Add these lines at the very top, before any HTML output
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once '../components/connect.php';

$db = new Database();
$conn = $db->connect();

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    // Updated redirect to use the user_content login page instead of admin login
    header('location: ../user_content/login_users.php');
    exit();
}

// Check if the user is a super admin
if (!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'superadmin') {
    // Redirect to appropriate page for unauthorized access
    header('location: ../access_denied.php');
    exit();
}

$admin_id = $_SESSION['admin_id'];

// Fetch profile data
$select_profile = $conn->prepare("SELECT firstname, lastname FROM `accounts` WHERE account_id = ?");
$select_profile->execute([$admin_id]);
$fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);

if (!$fetch_profile) {
    echo '<p class="error">Profile not found. Please contact support.</p>';
    exit();
}

// Fetch post counts - PLATFORM-WIDE
$select_posts = $conn->prepare("SELECT COUNT(*) FROM `posts` WHERE status = 'published'");
$select_posts->execute();
$numbers_of_posts = $select_posts->fetchColumn();

$select_tejido = $conn->prepare("SELECT COUNT(*) FROM `tejido`");
$select_tejido->execute();
$numbers_of_tejido = $select_tejido->fetchColumn();

$select_draft_tejido = $conn->prepare("SELECT COUNT(*) FROM `tejido` WHERE status = 'draft'");
$select_draft_tejido->execute();
$numbers_of_draft_tejido = $select_draft_tejido->fetchColumn();

$select_articles = $conn->prepare("SELECT COUNT(*) FROM `articles`");
$select_articles->execute();
$numbers_of_articles = $select_articles->fetchColumn();

$select_draft_articles = $conn->prepare("SELECT COUNT(*) FROM `articles` WHERE status = 'draft'");
$select_draft_articles->execute();
$numbers_of_draft_articles = $select_draft_articles->fetchColumn();

try {
    $select_categories = $conn->prepare("SELECT COUNT(*) FROM category");
    $select_categories->execute();
    $numbers_of_categories = $select_categories->fetchColumn();
} catch (PDOException $e) {
    $numbers_of_categories = 0;
}

$select_deactive_posts = $conn->prepare("SELECT COUNT(*) FROM `posts` WHERE status = 'draft'");
$select_deactive_posts->execute();
$numbers_of_deactive_posts = $select_deactive_posts->fetchColumn();

$select_users = $conn->prepare("SELECT COUNT(*) FROM `accounts` WHERE role = 'user'");
$select_users->execute();
$numbers_of_users = $select_users->fetchColumn();

$select_admins = $conn->prepare("SELECT COUNT(*) FROM `accounts` WHERE role IN ('superadmin', 'subadmin')");
$select_admins->execute();
$numbers_of_admins = $select_admins->fetchColumn();

$select_comments = $conn->prepare("SELECT COUNT(*) FROM `comments`");
$select_comments->execute();
$numbers_of_comments = $select_comments->fetchColumn();

// Count all likes in the system
$select_likes = $conn->prepare("SELECT COUNT(*) FROM `likes`");
$select_likes->execute();
$numbers_of_likes = $select_likes->fetchColumn();

try {
    // First check if the table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'e_magazines'");
    if ($table_check->rowCount() > 0) {
        $select_magazines = $conn->prepare("SELECT COUNT(*) FROM `e_magazines`");
        $select_magazines->execute();
        $numbers_of_magazines = $select_magazines->fetchColumn();
    } else {
        $numbers_of_magazines = 0;
    }
} catch (PDOException $e) {
    $numbers_of_magazines = 0;
}

try {
    // First check if the table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'announcements'");
    if ($table_check->rowCount() > 0) {
        $select_announcements = $conn->prepare("SELECT COUNT(*) FROM `announcements`");
        $select_announcements->execute();
        $numbers_of_announcements = $select_announcements->fetchColumn();
    } else {
        $numbers_of_announcements = 0;
    }
} catch (PDOException $e) {
    $numbers_of_announcements = 0;
}

try {
    $stmt = $conn->prepare("SELECT COUNT(org_id) FROM organizational_chart WHERE is_deleted = 0 AND (date_ended IS NULL OR date_ended = '')");
    $stmt->execute();
    $number_of_org_members = $stmt->fetchColumn();
} catch (PDOException $e) {
    $number_of_org_members = 0;
}

// Fetch top posts by engagement (likes + comments) - PLATFORM-WIDE
$top_posts = [];
try {
    $stmt = $conn->prepare("
        SELECT 
            p.post_id, 
            p.title, 
            p.created_at,
            COUNT(DISTINCT l.like_id) as likes_count, 
            COUNT(DISTINCT c.comment_id) as comments_count,
            (COUNT(DISTINCT l.like_id) + COUNT(DISTINCT c.comment_id)) as total_engagement
        FROM 
            posts p
        LEFT JOIN 
            likes l ON p.post_id = l.post_id
        LEFT JOIN 
            comments c ON p.post_id = c.post_id
        WHERE 
            p.status = 'published'
        GROUP BY 
            p.post_id
        ORDER BY 
            total_engagement DESC
        LIMIT 10
    ");
    $stmt->execute();
    $top_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle error silently
}

// Get top users by engagement (comments + likes) - Already PLATFORM-WIDE
$top_engaging_users = [];
try {
    $stmt = $conn->prepare("
        SELECT 
            a.account_id,
            a.firstname,
            a.lastname,
            COUNT(DISTINCT c.comment_id) as comments_count,
            COUNT(DISTINCT l.like_id) as likes_count,
            (COUNT(DISTINCT c.comment_id) + COUNT(DISTINCT l.like_id)) as total_engagement
        FROM 
            accounts a
        LEFT JOIN 
            comments c ON a.account_id = c.commented_by
        LEFT JOIN 
            likes l ON a.account_id = l.liked_by
        WHERE 
            a.role = 'user'
        GROUP BY 
            a.account_id
        ORDER BY 
            total_engagement DESC
        LIMIT 10
    ");
    $stmt->execute();
    $top_engaging_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle error silently
}

// Get top contributors (users who post the most) - Already PLATFORM-WIDE
$top_contributors = [];
try {
    $stmt = $conn->prepare("
        SELECT 
            a.account_id,
            a.firstname,
            a.lastname,
            COUNT(DISTINCT p.post_id) as posts_count,
            COUNT(DISTINCT t.tejido_id) as tejido_count,
            COUNT(DISTINCT ar.article_id) as articles_count,
            (COUNT(DISTINCT p.post_id) + COUNT(DISTINCT t.tejido_id) + COUNT(DISTINCT ar.article_id)) as total_contributions
        FROM 
            accounts a
        LEFT JOIN 
            posts p ON a.account_id = p.created_by
        LEFT JOIN 
            tejido t ON a.account_id = t.created_by
        LEFT JOIN 
            articles ar ON a.account_id = ar.created_by
        GROUP BY 
            a.account_id
        ORDER BY 
            total_contributions DESC
        LIMIT 10
    ");
    $stmt->execute();
    $top_contributors = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle error silently
}

// Get monthly content creation data for the current year - PLATFORM-WIDE
$current_year = date('Y');
$monthly_posts = array_fill(0, 12, 0);
$monthly_tejido = array_fill(0, 12, 0);
$monthly_articles = array_fill(0, 12, 0);

try {
    // Monthly posts
    $stmt = $conn->prepare("SELECT MONTH(created_at) as month, COUNT(*) as count FROM posts 
                           WHERE YEAR(created_at) = ? 
                           GROUP BY MONTH(created_at)");
    $stmt->execute([$current_year]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $monthly_posts[$row['month']-1] = $row['count'];
    }
    
    // Monthly tejido
    $stmt = $conn->prepare("SELECT MONTH(created_at) as month, COUNT(*) as count FROM tejido 
                           WHERE YEAR(created_at) = ? 
                           GROUP BY MONTH(created_at)");
    $stmt->execute([$current_year]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $monthly_tejido[$row['month']-1] = $row['count'];
    }
    
    // Monthly articles
    $stmt = $conn->prepare("SELECT MONTH(created_at) as month, COUNT(*) as count FROM articles 
                           WHERE YEAR(created_at) = ? 
                           GROUP BY MONTH(created_at)");
    $stmt->execute([$current_year]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $monthly_articles[$row['month']-1] = $row['count'];
    }
} catch (PDOException $e) {
    // Handle error silently
}

// Get category distribution - Already PLATFORM-WIDE
$categories = [];
$category_counts = [];

try {
    $stmt = $conn->prepare("SELECT c.name, COUNT(p.post_id) as count 
                           FROM category c
                           LEFT JOIN posts p ON p.category_id = c.category_id
                           GROUP BY c.category_id
                           ORDER BY count DESC
                           LIMIT 6");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $categories[] = $row['name'];
        $category_counts[] = $row['count'];
    }
} catch (PDOException $e) {
    // Handle error silently
}

// Get monthly engagement data - PLATFORM-WIDE
$monthly_likes = array_fill(0, 12, 0);
$monthly_comments = array_fill(0, 12, 0);

try {
    // Monthly likes
    $stmt = $conn->prepare("
        SELECT MONTH(created_at) as month, COUNT(*) as count 
        FROM likes
        WHERE YEAR(created_at) = ?
        GROUP BY MONTH(created_at)
    ");
    $stmt->execute([date('Y')]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $monthly_likes[$row['month']-1] = $row['count'];
    }
    
    // Monthly comments
    $stmt = $conn->prepare("
        SELECT MONTH(created_at) as month, COUNT(*) as count 
        FROM comments
        WHERE YEAR(created_at) = ?
        GROUP BY MONTH(created_at)
    ");
    $stmt->execute([date('Y')]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $monthly_comments[$row['month']-1] = $row['count'];
    }
} catch (PDOException $e) {
    // Handle error silently
}

// Prepare data for top posts chart
$post_titles = [];
$post_likes = [];
$post_comments = [];

foreach ($top_posts as $post) {
    // Truncate long titles for better display in chart
    $post_titles[] = strlen($post['title']) > 20 ? substr($post['title'], 0, 20) . '...' : $post['title'];
    $post_likes[] = $post['likes_count'];
    $post_comments[] = $post['comments_count'];
}

// Prepare data for top engaging users chart
$engaging_user_names = [];
$engaging_user_comments = [];
$engaging_user_likes = [];

foreach ($top_engaging_users as $user) {
    $engaging_user_names[] = $user['firstname'] . ' ' . substr($user['lastname'], 0, 1) . '.';
    $engaging_user_comments[] = $user['comments_count'];
    $engaging_user_likes[] = $user['likes_count'];
}

// Prepare data for top contributors chart
$contributor_names = [];
$contributor_posts = [];
$contributor_tejido = [];
$contributor_articles = [];

foreach ($top_contributors as $user) {
    $contributor_names[] = $user['firstname'] . ' ' . substr($user['lastname'], 0, 1) . '.';
    $contributor_posts[] = $user['posts_count'];
    $contributor_tejido[] = $user['tejido_count'];
    $contributor_articles[] = $user['articles_count'];
}

// E-Magazine Analytics - Already PLATFORM-WIDE
$magazine_data = [];
$magazine_views = [];
$magazine_categories = [];
$magazine_authors = [];
$monthly_magazine_uploads = array_fill(0, 12, 0);

try {
    // Check if e_magazines table exists
    $table_check = $conn->query("SHOW TABLES LIKE 'e_magazines'");
    if ($table_check->rowCount() > 0) {
        // Get total magazines
        $stmt = $conn->prepare("SELECT COUNT(*) FROM e_magazines");
        $stmt->execute();
        $total_magazines = $stmt->fetchColumn();
        
        // Get magazines by category
        $stmt = $conn->prepare("
            SELECT c.name, COUNT(m.magazine_id) as count 
            FROM e_magazines m
            JOIN category c ON m.category_id = c.category_id
            GROUP BY m.category_id
            ORDER BY count DESC
            LIMIT 5
        ");
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $magazine_categories[$row['name']] = $row['count'];
        }
        
        // Check if magazine_views table exists
        $views_table_check = $conn->query("SHOW TABLES LIKE 'magazine_views'");
        if ($views_table_check->rowCount() > 0) {
            // Get top 5 most viewed magazines
            $stmt = $conn->prepare("
                SELECT m.title, COUNT(mv.view_id) as view_count
                FROM e_magazines m
                JOIN magazine_views mv ON m.magazine_id = mv.magazine_id
                GROUP BY m.magazine_id
                ORDER BY view_count DESC
                LIMIT 5
            ");
            $stmt->execute();
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $magazine_data[] = $row['title'];
                $magazine_views[] = $row['view_count'];
            }
        }
        
        // Get magazines by author
        $stmt = $conn->prepare("
            SELECT author, COUNT(*) as count
            FROM e_magazines
            GROUP BY author
            ORDER BY count DESC
            LIMIT 5
        ");
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $magazine_authors[$row['author']] = $row['count'];
        }
        
        // Get monthly magazine uploads for current year
        $stmt = $conn->prepare("
            SELECT MONTH(created_at) as month, COUNT(*) as count 
            FROM e_magazines
            WHERE YEAR(created_at) = ?
            GROUP BY MONTH(created_at)
        ");
        $stmt->execute([date('Y')]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $monthly_magazine_uploads[$row['month']-1] = $row['count'];
        }
    }
} catch (PDOException $e) {
    // Handle error silently
}

try {
    // Total published posts
    $stmt = $conn->prepare("SELECT COUNT(*) FROM posts WHERE status = 'published'");
    $stmt->execute();
    $total_posts = $stmt->fetchColumn();
    
    // Total tejido
    $stmt = $conn->prepare("SELECT COUNT(*) FROM tejido");
    $stmt->execute();
    $total_tejido = $stmt->fetchColumn();
    
    // Total draft tejido
    $stmt = $conn->prepare("SELECT COUNT(*) FROM tejido WHERE status = 'draft'");
    $stmt->execute();
    $total_draft_tejido = $stmt->fetchColumn();
    
    // Total articles
    $stmt = $conn->prepare("SELECT COUNT(*) FROM articles");
    $stmt->execute();
    $total_articles = $stmt->fetchColumn();
    
    // Total draft articles
    $stmt = $conn->prepare("SELECT COUNT(*) FROM articles WHERE status = 'draft'");
    $stmt->execute();
    $total_draft_articles = $stmt->fetchColumn();
    
    // Total draft posts
    $stmt = $conn->prepare("SELECT COUNT(*) FROM posts WHERE status = 'draft'");
    $stmt->execute();
    $total_deactive_posts = $stmt->fetchColumn();
    
    // Total likes
    $stmt = $conn->prepare("SELECT COUNT(*) FROM likes");
    $stmt->execute();
    $total_likes = $stmt->fetchColumn();
    
    // Total comments
    $stmt = $conn->prepare("SELECT COUNT(*) FROM comments");
    $stmt->execute();
    $total_comments = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    // Handle error silently
    $total_posts = 0;
    $total_tejido = 0;
    $total_draft_tejido = 0;
    $total_articles = 0;
    $total_draft_articles = 0;
    $total_deactive_posts = 0;
    $total_likes = 0;
    $total_comments = 0;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Analytics Dashboard</title>

    <!-- Font Awesome CDN Link -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" />

    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom CSS File -->
    <link rel="stylesheet" href="../css/admin_style.css" />   
    
</head>

<body>
<script src="../js/session_check.js"></script>

<?php include '../components/superadmin_header.php'; ?>

<section class="dashboard">
    <div class="analytics-header">
        <h1 class="heading">Platform Analytics Dashboard</h1>
        <div class="user-info">
            <p>Welcome, <?= htmlspecialchars($fetch_profile['firstname'] . ' ' . $fetch_profile['lastname']); ?></p>
        </div>
    </div>
 

<!-- Add this after the existing analytics-overview section -->
    <div class="section-divider"></div>
    
    <h2 class="heading">Platform-Wide Analytics</h2>
    <p class="sub-heading">Total content across all users</p>
    
    <div class="analytics-overview platform-wide">
        <div class="analytics-card">
            <h3><?= $total_posts; ?></h3>
            <p>Total Published Posts</p>
        </div>
        
        <div class="analytics-card">
            <h3><?= $total_tejido; ?></h3>
            <p>Total Tejido</p>
        </div>
        
        <div class="analytics-card">
            <h3><?= $total_articles; ?></h3>
            <p>Total Articles</p>
        </div>
        
        <div class="analytics-card">
            <h3><?= $total_likes; ?></h3>
            <p>Total Likes</p>
        </div>
        
        <div class="analytics-card">
            <h3><?= $total_comments; ?></h3>
            <p>Total Comments</p>
        </div>
        
        <div class="analytics-card">
            <h3><?= $numbers_of_magazines; ?></h3>
            <p>Total E-Magazines</p>
        </div>
        
        <div class="analytics-card">
            <h3><?= $numbers_of_users + $numbers_of_admins; ?></h3>
            <p>Total Users</p>
        </div>    
        <div class="analytics-card">
            <h3><?= $number_of_org_members; ?></h3>
            <p>Organizational Members</p>
        </div>
        <div class="analytics-card">
            <h3><?= $total_deactive_posts; ?></h3>
            <p>Draft Posts</p>
        </div>
        
        <div class="analytics-card">
            <h3><?= $total_draft_tejido; ?></h3>
            <p>Draft Tejido</p>
        </div>
        
        <div class="analytics-card">
            <h3><?= $total_draft_articles; ?></h3>
            <p>Draft Articles</p>
        </div>
        
        <div class="analytics-card">
            <h3><?= $numbers_of_categories; ?></h3>
            <p>Categories</p>
        </div>
        
        <div class="analytics-card">
            <h3><?= $numbers_of_announcements; ?></h3>
            <p>Announcements</p>
        </div>
    </div>




    <!-- Charts Section -->
    <h2 class="heading">Content Analytics</h2>
    <div class="charts-container">
        <div class="chart-box">
            <h3>Content Overview</h3>
            <canvas id="contentChart"></canvas>
        </div>
        
        <div class="chart-box">
            <h3>Content Status</h3>
            <canvas id="contentStatusChart"></canvas>
        </div>
        
        <div class="chart-box">
            <h3>Monthly Content Creation</h3>
            <canvas id="monthlyContentChart"></canvas>
        </div>
        
        <div class="chart-box">
            <h3>Category Distribution</h3>
            <canvas id="categoryChart"></canvas>
        </div>
    </div>
    
    <h2 class="heading">Engagement Analytics</h2>
    <div class="charts-container">
        <div class="chart-box">
            <h3>User Engagement</h3>
            <canvas id="engagementChart"></canvas>
        </div>
        
        <div class="chart-box">
            <h3>Monthly Engagement Trends</h3>
            <canvas id="monthlyEngagementChart"></canvas>
        </div>
        
        <div class="chart-box">
            <h3>Top Posts by Engagement</h3>
            <canvas id="topPostsChart"></canvas>
        </div>
        
        <div class="chart-box top-posts-table">
            <h3>Top Posts Details</h3>
            <table>
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Date</th>
                        <th>Likes</th>
                        <th>Comments</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($top_posts)): ?>
                        <?php foreach ($top_posts as $post): ?>
                            <tr>
                                <td><?= htmlspecialchars($post['title']) ?></td>
                                <td><?= date('M d, Y', strtotime($post['created_at'])) ?></td>
                                <td><?= $post['likes_count'] ?></td>
                                <td><?= $post['comments_count'] ?></td>
                                <td><?= $post['total_engagement'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">No posts found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <h2 class="heading">User Analytics</h2>
    <div class="charts-container">
        <div class="chart-box">
            <h3>Account Distribution</h3>
            <canvas id="accountsChart"></canvas>
        </div>
        
        <div class="chart-box">
            <h3>Top Users by Engagement</h3>
            <canvas id="topEngagingUsersChart"></canvas>
        </div>
        
        <div class="chart-box">
            <h3>Top Content Contributors</h3>
            <canvas id="topContributorsChart"></canvas>
        </div>
        
        <div class="chart-box top-posts-table">
            <h3>Top Engaging Users Details</h3>
            <table>
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Comments</th>
                        <th>Likes</th>
                        <th>Total Engagement</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($top_engaging_users)): ?>
                        <?php foreach ($top_engaging_users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?></td>
                                <td class="text-center"><?= $user['comments_count'] ?></td>
                                <td class="text-center"><?= $user['likes_count'] ?></td>
                                <td class="text-center"><?= $user['total_engagement'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4">No user engagement data found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add this after the User Analytics section -->
    <h2 class="heading">E-Magazine Analytics</h2>
    <div class="charts-container">
        <div class="chart-box">
            <h3>Most Viewed E-Magazines</h3>
            
            <canvas id="magazineViewsChart"></canvas>
        </div>
        
        <div class="chart-box">
            <h3>E-Magazines by Category</h3>
            <canvas id="magazineCategoriesChart"></canvas>
        </div>
        
        <div class="chart-box">
            <h3>E-Magazines by Author</h3>
            <canvas id="magazineAuthorsChart"></canvas>
        </div>
        
        <div class="chart-box">
            <h3>Monthly E-Magazine Uploads</h3>
            <canvas id="monthlyMagazineChart"></canvas>
        </div>
    </div>
</section>

<script>
// Content Overview Chart
const contentCtx = document.getElementById('contentChart').getContext('2d');
const contentChart = new Chart(contentCtx, {
    type: 'bar',
    data: {
        labels: ['Posts', 'Tejido', 'Articles', 'E-Magazines'],
        datasets: [{
            label: 'Published',
            data: [<?= $numbers_of_posts ?>, <?= $numbers_of_tejido ?>, <?= $numbers_of_articles ?>, <?= $numbers_of_magazines ?>],
            backgroundColor: [
                'rgba(54, 162, 235, 0.7)',
                'rgba(75, 192, 192, 0.7)',
                'rgba(153, 102, 255, 0.7)',
                'rgba(255, 159, 64, 0.7)'
            ],
            borderColor: [
                'rgba(54, 162, 235, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(153, 102, 255, 1)',
                'rgba(255, 159, 64, 1)'
            ],
            borderWidth: 1
        }, {
            label: 'Drafts',
            data: [<?= $numbers_of_deactive_posts ?>, <?= $numbers_of_draft_tejido ?>, <?= $numbers_of_draft_articles ?>, 0],
            backgroundColor: [
                'rgba(54, 162, 235, 0.3)',
                'rgba(75, 192, 192, 0.3)',
                'rgba(153, 102, 255, 0.3)',
                'rgba(255, 159, 64, 0.3)'
            ],
            borderColor: [
                'rgba(54, 162, 235, 0.8)',
                'rgba(75, 192, 192, 0.8)',
                'rgba(153, 102, 255, 0.8)',
                'rgba(255, 159, 64, 0.8)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true
            }
        },
        plugins: {
            title: {
                display: true,
                text: 'Platform-Wide Content Distribution'
            }
        }
    }
});

// User Engagement Chart
const engagementCtx = document.getElementById('engagementChart').getContext('2d');
const engagementChart = new Chart(engagementCtx, {
    type: 'pie',
    data: {
        labels: ['Comments', 'Likes', 'Announcements'],
        datasets: [{
            data: [<?= $numbers_of_comments ?>, <?= $numbers_of_likes ?>, <?= $numbers_of_announcements ?>],
            backgroundColor: [
                'rgba(255, 99, 132, 0.7)',
                'rgba(54, 162, 235, 0.7)',
                'rgba(255, 206, 86, 0.7)'
            ],
            borderColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            },
            title: {
                display: true,
                text: 'Platform-Wide User Engagement Metrics'
            }
        }
    }
});

// Account Distribution Chart
const accountsCtx = document.getElementById('accountsChart').getContext('2d');
const accountsChart = new Chart(accountsCtx, {
    type: 'doughnut',
    data: {
        labels: ['Users', 'Admins', 'Org Members'],
        datasets: [{
            data: [<?= $numbers_of_users ?>, <?= $numbers_of_admins ?>, <?= $number_of_org_members ?>],
            backgroundColor: [
                'rgba(75, 192, 192, 0.7)',
                'rgba(153, 102, 255, 0.7)',
                'rgba(255, 159, 64, 0.7)'
            ],
            borderColor: [
                'rgba(75, 192, 192, 1)',
                'rgba(153, 102, 255, 1)',
                'rgba(255, 159, 64, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
            },
            title: {
                display: true,
                text: 'Account Distribution'
            }
        }
    }
});

// Content Status Chart
const contentStatusCtx = document.getElementById('contentStatusChart').getContext('2d');
const contentStatusChart = new Chart(contentStatusCtx, {
    type: 'polarArea',
    data: {
        labels: ['Published Posts', 'Draft Posts', 'Published Tejido', 'Draft Tejido', 'Published Articles', 'Draft Articles'],
        datasets: [{
            data: [
                <?= $numbers_of_posts ?>, 
                <?= $numbers_of_deactive_posts ?>, 
                <?= $numbers_of_tejido - $numbers_of_draft_tejido ?>, 
                <?= $numbers_of_draft_tejido ?>, 
                <?= $numbers_of_articles - $numbers_of_draft_articles ?>, 
                <?= $numbers_of_draft_articles ?>
            ],
            backgroundColor: [
                'rgba(54, 162, 235, 0.7)',
                'rgba(54, 162, 235, 0.3)',
                'rgba(75, 192, 192, 0.7)',
                'rgba(75, 192, 192, 0.3)',
                'rgba(153, 102, 255, 0.7)',
                'rgba(153, 102, 255, 0.3)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'right',
            },
            title: {
                display: true,
                text: 'Platform-Wide Content Status Distribution'
            }
        }
    }
});

// Monthly Content Creation Chart
const monthlyContentCtx = document.getElementById('monthlyContentChart').getContext('2d');
const monthlyContentChart = new Chart(monthlyContentCtx, {
    type: 'line',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        datasets: [
            {
                label: 'Posts',
                data: [<?= implode(',', $monthly_posts) ?>],
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                tension: 0.3,
                fill: true
            },
            {
                label: 'Tejido',
                data: [<?= implode(',', $monthly_tejido) ?>],
                borderColor: 'rgba(75, 192, 192, 1)',
                backgroundColor: 'rgba(75, 192, 192, 0.1)',
                tension: 0.3,
                fill: true
            },
            {
                label: 'Articles',
                data: [<?= implode(',', $monthly_articles) ?>],
                borderColor: 'rgba(153, 102, 255, 1)',
                backgroundColor: 'rgba(153, 102, 255, 0.1)',
                tension: 0.3,
                fill: true
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Number of Items'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Month (<?= $current_year ?>)'
                }
            }
        },
        plugins: {
            title: {
                display: true,
                text: 'Platform-Wide Monthly Content Creation'
            }
        }
    }
});

// Category Distribution Chart
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
const categoryChart = new Chart(categoryCtx, {
    type: 'radar',
    data: {
        labels: [<?= !empty($categories) ? "'" . implode("','", $categories) . "'" : '' ?>],
        datasets: [{
            label: 'Posts per Category',
            data: [<?= !empty($category_counts) ? implode(',', $category_counts) : '' ?>],
            backgroundColor: 'rgba(255, 99, 132, 0.2)',
            borderColor: 'rgba(255, 99, 132, 1)',
            borderWidth: 1,
            pointBackgroundColor: 'rgba(255, 99, 132, 1)'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            title: {
                display: true,
                text: 'Platform-Wide Category Distribution'
            }
        },
        scales: {
            r: {
                beginAtZero: true
            }
        }
    }
});

// Monthly Engagement Trends Chart
const monthlyEngagementCtx = document.getElementById('monthlyEngagementChart').getContext('2d');
const monthlyEngagementChart = new Chart(monthlyEngagementCtx, {
    type: 'line',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        datasets: [
            {
                label: 'Likes',
                data: [<?= implode(',', $monthly_likes) ?>],
                borderColor: 'rgba(54, 162, 235, 1)',
                backgroundColor: 'rgba(54, 162, 235, 0.1)',
                tension: 0.3,
                fill: true
            },
            {
                label: 'Comments',
                data: [<?= implode(',', $monthly_comments) ?>],
                borderColor: 'rgba(255, 99, 132, 1)',
                backgroundColor: 'rgba(255, 99, 132, 0.1)',
                tension: 0.3,
                fill: true
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Number of Interactions'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Month (<?= $current_year ?>)'
                }
            }
        },
        plugins: {
            title: {
                display: true,
                text: 'Platform-Wide Monthly Engagement Trends'
            }
        }
    }
});

// Top Posts by Engagement Chart
const topPostsCtx = document.getElementById('topPostsChart').getContext('2d');
const topPostsChart = new Chart(topPostsCtx, {
    type: 'bar',
    data: {
        labels: [<?= !empty($post_titles) ? "'" . implode("','", $post_titles) . "'" : '' ?>],
        datasets: [
            {
                label: 'Likes',
                data: [<?= !empty($post_likes) ? implode(',', $post_likes) : '' ?>],
                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            },
            {
                label: 'Comments',
                data: [<?= !empty($post_comments) ? implode(',', $post_comments) : '' ?>],
                backgroundColor: 'rgba(255, 99, 132, 0.7)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1
            }
        ]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        scales: {
            x: {
                beginAtZero: true,
                stacked: true,
                title: {
                    display: true,
                    text: 'Number of Interactions'
                }
            },
            y: {
                stacked: true,
                title: {
                    display: true,
                    text: 'Post Title'
                }
            }
        },
        plugins: {
            title: {
                display: true,
                text: 'Platform-Wide Top Posts by Engagement'
            },
            tooltip: {
                callbacks: {
                    title: function(tooltipItems) {
                        // Get the full title from the original data
                        const index = tooltipItems[0].dataIndex;
                        return <?= !empty($top_posts) ? json_encode(array_column($top_posts, 'title')) : '[]' ?>[index] || tooltipItems[0].label;
                    }
                }
            }
        }
    }
});

// Top Engaging Users Chart
const topEngagingUsersCtx = document.getElementById('topEngagingUsersChart').getContext('2d');
const topEngagingUsersChart = new Chart(topEngagingUsersCtx, {
    type: 'bar',
    data: {
        labels: [<?= !empty($engaging_user_names) ? "'" . implode("','", $engaging_user_names) . "'" : '' ?>],
        datasets: [
            {
                label: 'Comments',
                data: [<?= !empty($engaging_user_comments) ? implode(',', $engaging_user_comments) : '' ?>],
                backgroundColor: 'rgba(255, 99, 132, 0.7)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1
            },
            {
                label: 'Likes',
                data: [<?= !empty($engaging_user_likes) ? implode(',', $engaging_user_likes) : '' ?>],
                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            }
        ]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        scales: {
            x: {
                beginAtZero: true,
                stacked: true,
                title: {
                    display: true,
                    text: 'Number of Interactions'
                }
            },
            y: {
                stacked: true,
                title: {
                    display: true,
                    text: 'User'
                }
            }
        },
        plugins: {
            title: {
                display: true,
                text: 'Top Users by Engagement'
            }
        }
    }
});

// Top Contributors Chart
const topContributorsCtx = document.getElementById('topContributorsChart').getContext('2d');
const topContributorsChart = new Chart(topContributorsCtx, {
    type: 'bar',
    data: {
        labels: [<?= !empty($contributor_names) ? "'" . implode("','", $contributor_names) . "'" : '' ?>],
        datasets: [
            {
                label: 'Posts',
                data: [<?= !empty($contributor_posts) ? implode(',', $contributor_posts) : '' ?>],
                backgroundColor: 'rgba(54, 162, 235, 0.7)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1
            },
            {
                label: 'Tejido',
                data: [<?= !empty($contributor_tejido) ? implode(',', $contributor_tejido) : '' ?>],
                backgroundColor: 'rgba(75, 192, 192, 0.7)',
                borderColor: 'rgba(75, 192, 192, 1)',
                borderWidth: 1
            },
            {
                label: 'Articles',
                data: [<?= !empty($contributor_articles) ? implode(',', $contributor_articles) : '' ?>],
                backgroundColor: 'rgba(153, 102, 255, 0.7)',
                borderColor: 'rgba(153, 102, 255, 1)',
                borderWidth: 1
            }
        ]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        scales: {
            x: {
                beginAtZero: true,
                stacked: true,
                title: {
                    display: true,
                    text: 'Number of Contributions'
                }
            },
            y: {
                stacked: true,
                title: {
                    display: true,
                    text: 'User'
                }
            }
        },
        plugins: {
            title: {
                display: true,
                text: 'Top Content Contributors'
            }
        }
    }
});

// Most Viewed E-Magazines Chart
const viewsCtx = document.getElementById('magazineViewsChart').getContext('2d');
const magazineViewsChart = new Chart(viewsCtx, {
    type: 'bar',
    data: {
        labels: <?= !empty($magazine_data) ? json_encode(array_map(function($title) { 
            return strlen($title) > 20 ? substr($title, 0, 20) . '...' : $title; 
        }, $magazine_data)) : '[]' ?>,
        datasets: [{
            label: 'Number of Views',
            data: <?= !empty($magazine_views) ? json_encode($magazine_views) : '[]' ?>,
            backgroundColor: 'rgba(54, 162, 235, 0.7)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Views'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'E-Magazine Title'
                }
            }
        },
        plugins: {
            title: {
                display: true,
                text: 'Top E-Magazines by Views'
            },
            tooltip: {
                callbacks: {
                    title: function(tooltipItems) {
                        const index = tooltipItems[0].dataIndex;
                        return <?= !empty($magazine_data) ? json_encode($magazine_data) : '[]' ?>[index] || tooltipItems[0].label;
                    }
                }
            }
        }
    }
});

// E-Magazines by Category Chart
const categoriesCtx = document.getElementById('magazineCategoriesChart').getContext('2d');
const magazineCategoriesChart = new Chart(categoriesCtx, {
    type: 'pie',
    data: {
        labels: <?= !empty($magazine_categories) ? json_encode(array_keys($magazine_categories)) : '[]' ?>,
        datasets: [{
            data: <?= !empty($magazine_categories) ? json_encode(array_values($magazine_categories)) : '[]' ?>,
            backgroundColor: [
                'rgba(255, 99, 132, 0.7)',
                'rgba(54, 162, 235, 0.7)',
                'rgba(255, 206, 86, 0.7)',
                'rgba(75, 192, 192, 0.7)',
                'rgba(153, 102, 255, 0.7)'
            ],
            borderColor: [
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)',
                'rgba(255, 206, 86, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(153, 102, 255, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'right',
            },
            title: {
                display: true,
                text: 'Distribution by Category'
            }
        }
    }
});

// E-Magazines by Author Chart
const authorsCtx = document.getElementById('magazineAuthorsChart').getContext('2d');
const magazineAuthorsChart = new Chart(authorsCtx, {
    type: 'doughnut',
    data: {
        labels: <?= !empty($magazine_authors) ? json_encode(array_keys($magazine_authors)) : '[]' ?>,
        datasets: [{
            data: <?= !empty($magazine_authors) ? json_encode(array_values($magazine_authors)) : '[]' ?>,
            backgroundColor: [
                'rgba(75, 192, 192, 0.7)',
                'rgba(153, 102, 255, 0.7)',
                'rgba(255, 159, 64, 0.7)',
                'rgba(255, 99, 132, 0.7)',
                'rgba(54, 162, 235, 0.7)'
            ],
            borderColor: [
                'rgba(75, 192, 192, 1)',
                'rgba(153, 102, 255, 1)',
                'rgba(255, 159, 64, 1)',
                'rgba(255, 99, 132, 1)',
                'rgba(54, 162, 235, 1)'
            ],
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'right',
            },
            title: {
                display: true,
                text: 'Distribution by Author'
            }
        }
    }
});

// Monthly E-Magazine Uploads Chart
const monthlyMagazineCtx = document.getElementById('monthlyMagazineChart').getContext('2d');
const monthlyMagazineChart = new Chart(monthlyMagazineCtx, {
    type: 'line',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        datasets: [{
            label: 'E-Magazines Uploaded',
            data: <?= json_encode($monthly_magazine_uploads) ?>,
            borderColor: 'rgba(255, 159, 64, 1)',
            backgroundColor: 'rgba(255, 159, 64, 0.1)',
            tension: 0.3,
            fill: true
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Number of Uploads'
                }
            },
            x: {
                title: {
                    display: true,
                    text: 'Month (<?= date('Y') ?>)'
                }
            }
        },
        plugins: {
            title: {
                display: true,
                text: 'Monthly E-Magazine Publication Trend'
            }
        }
    }
});
</script>

<script src="../js/admin_script.js"></script>

</body>

</html>