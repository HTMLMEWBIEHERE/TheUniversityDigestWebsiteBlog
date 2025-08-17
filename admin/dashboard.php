<?php
// Add these lines at the very top, before any HTML output
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

require_once '../components/connect.php';

$db = new Database();
$conn = $db->connect();

session_start();

// Check if the admin is logged in
if(!isset($_SESSION['admin_id'])){
   header('location:../user_content/login_users.php');
   exit();
}

// Check if the user is a subadmin (add role check)
if(!isset($_SESSION['admin_role']) || $_SESSION['admin_role'] !== 'subadmin'){
   // Redirect to appropriate page for unauthorized access
   header('location:../access_denied.php');
   exit();
}

$admin_id = $_SESSION['admin_id'];

// Fetch profile data if needed
$select_profile = $conn->prepare("SELECT firstname, lastname FROM `accounts` WHERE account_id = ?");
$select_profile->execute([$admin_id]);
$fetch_profile = $select_profile->fetch(PDO::FETCH_ASSOC);

if (!$fetch_profile) {
    echo '<p class="error">Profile not found. Please contact support.</p>';
    exit();
}

// Fetch post counts
$select_posts = $conn->prepare("SELECT COUNT(*) FROM `posts` WHERE created_by = ? AND status = 'published'");
$select_posts->execute([$admin_id]);
$numbers_of_posts = $select_posts->fetchColumn();

$select_tejido = $conn->prepare("SELECT COUNT(*) FROM `tejido` WHERE created_by = ? AND status = 'published'");
$select_tejido->execute([$admin_id]);
$numbers_of_tejido = $select_tejido->fetchColumn();

$select_draft_posts = $conn->prepare("SELECT COUNT(*) FROM `posts` WHERE created_by = ? AND status = 'draft'");
$select_draft_posts->execute([$admin_id]);
$numbers_of_deactive_posts = $select_draft_posts->fetchColumn();

$select_draft_tejido = $conn->prepare("SELECT COUNT(*) FROM `tejido` WHERE created_by = ? AND status = 'draft'");
$select_draft_tejido->execute([$admin_id]);
$numbers_of_draft_tejido = $select_draft_tejido->fetchColumn();

$select_articles = $conn->prepare("SELECT COUNT(*) FROM `articles` WHERE created_by = ? AND status = 'published'");
$select_articles->execute([$admin_id]);
$numbers_of_articles = $select_articles->fetchColumn();

$select_draft_articles = $conn->prepare("SELECT COUNT(*) FROM `articles` WHERE created_by = ? AND status = 'draft'");
$select_draft_articles->execute([$admin_id]);
$numbers_of_draft_articles = $select_draft_articles->fetchColumn();

$select_users = $conn->prepare("SELECT COUNT(*) FROM `accounts` WHERE role = 'user'");
$select_users->execute();
$numbers_of_users = $select_users->fetchColumn();

$select_categories = $conn->prepare("SELECT COUNT(*) FROM `category`");
$select_categories->execute();
$numbers_of_categories = $select_categories->fetchColumn();

// Count comments on posts created by this admin
$select_comments = $conn->prepare("
    SELECT COUNT(*) as total_comments 
    FROM `comments` c
    JOIN `posts` p ON c.post_id = p.post_id
    WHERE p.created_by = ?
");
$select_comments->execute([$admin_id]);
$result = $select_comments->fetch(PDO::FETCH_ASSOC);
$numbers_of_comments = $result['total_comments'];

// Count likes on posts created by this admin
$select_likes = $conn->prepare("
    SELECT COUNT(*) as total_likes 
    FROM `likes` l
    JOIN `posts` p ON l.post_id = p.post_id
    WHERE p.created_by = ?
");
$select_likes->execute([$admin_id]);
$result = $select_likes->fetch(PDO::FETCH_ASSOC);
$numbers_of_likes = $result['total_likes'];

// Get monthly content creation data for the current year
$current_year = date('Y');
$monthly_posts = array_fill(0, 12, 0);
$monthly_tejido = array_fill(0, 12, 0);
$monthly_articles = array_fill(0, 12, 0);

try {
    // Monthly posts
    $stmt = $conn->prepare("SELECT MONTH(created_at) as month, COUNT(*) as count FROM posts 
                           WHERE YEAR(created_at) = ? AND created_by = ? 
                           GROUP BY MONTH(created_at)");
    $stmt->execute([$current_year, $admin_id]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $monthly_posts[$row['month']-1] = $row['count'];
    }
    
    // Monthly tejido
    $stmt = $conn->prepare("SELECT MONTH(created_at) as month, COUNT(*) as count FROM tejido 
                           WHERE YEAR(created_at) = ? AND created_by = ? 
                           GROUP BY MONTH(created_at)");
    $stmt->execute([$current_year, $admin_id]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $monthly_tejido[$row['month']-1] = $row['count'];
    }
    
    // Monthly articles
    $stmt = $conn->prepare("SELECT MONTH(created_at) as month, COUNT(*) as count FROM articles 
                           WHERE YEAR(created_at) = ? AND created_by = ? 
                           GROUP BY MONTH(created_at)");
    $stmt->execute([$current_year, $admin_id]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $monthly_articles[$row['month']-1] = $row['count'];
    }
} catch (PDOException $e) {
    // Handle error silently
}

// Get category distribution for this admin's posts
$categories = [];
$category_counts = [];

try {
    $stmt = $conn->prepare("SELECT c.name, COUNT(p.post_id) as count 
                           FROM category c
                           LEFT JOIN posts p ON p.category_id = c.category_id AND p.created_by = ?
                           GROUP BY c.category_id
                           HAVING count > 0
                           ORDER BY count DESC
                           LIMIT 6");
    $stmt->execute([$admin_id]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $categories[] = $row['name'];
        $category_counts[] = $row['count'];
    }
} catch (PDOException $e) {
    // Handle error silently
}

// Get monthly engagement data
$monthly_likes = array_fill(0, 12, 0);
$monthly_comments = array_fill(0, 12, 0);

try {
    // Monthly likes
    $stmt = $conn->prepare("
        SELECT MONTH(l.created_at) as month, COUNT(*) as count 
        FROM likes l
        JOIN posts p ON l.post_id = p.post_id
        WHERE YEAR(l.created_at) = ? AND p.created_by = ?
        GROUP BY MONTH(l.created_at)
    ");
    $stmt->execute([date('Y'), $admin_id]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $monthly_likes[$row['month']-1] = $row['count'];
    }
    
    // Monthly comments
    $stmt = $conn->prepare("
        SELECT MONTH(c.created_at) as month, COUNT(*) as count 
        FROM comments c
        JOIN posts p ON c.post_id = p.post_id
        WHERE YEAR(c.created_at) = ? AND p.created_by = ?
        GROUP BY MONTH(c.created_at)
    ");
    $stmt->execute([date('Y'), $admin_id]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $monthly_comments[$row['month']-1] = $row['count'];
    }
} catch (PDOException $e) {
    // Handle error silently
}

// Fetch top posts by engagement (likes + comments)
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
            p.created_by = ? AND p.status = 'published'
        GROUP BY 
            p.post_id
        ORDER BY 
            total_engagement DESC
        LIMIT 5
    ");
    $stmt->execute([$admin_id]);
    $top_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Subadmin Analytics</title>

   <!-- font awesome cdn link  -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- Chart.js CDN -->
   <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

   <!-- custom css file link  -->
   <link rel="stylesheet" href="../css/admin_style.css">

   
</head>
<body>

<?php include '../components/admin_header.php' ?>

<section class="dashboard">
    <div class="analytics-header">
        <h1 class="heading">Analytics Dashboard</h1>
        <div class="user-info">
            <p>Welcome, <?= htmlspecialchars($fetch_profile['firstname'] . ' ' . $fetch_profile['lastname']); ?></p>
        </div>
    </div>
    
    <!-- Analytics Overview Cards -->
    <div class="analytics-overview">
        <div class="analytics-card">
            <h3><?= $numbers_of_posts; ?></h3>
            <p>Published Posts</p>
        </div>
        
        <div class="analytics-card">
            <h3><?= $numbers_of_tejido; ?></h3>
            <p>Published Tejido</p>
        </div>
        
        <div class="analytics-card">
            <h3><?= $numbers_of_articles; ?></h3>
            <p>Published Articles</p>
        </div>
        
        <div class="analytics-card">
            <h3><?= $numbers_of_likes; ?></h3>
            <p>Likes Received</p>
        </div>
        
        <div class="analytics-card">
            <h3><?= $numbers_of_comments; ?></h3>
            <p>Comments Received</p>
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
            <h3>Your Top Posts by Engagement</h3>
            <canvas id="topPostsChart"></canvas>
        </div>
        
        <div class="chart-box top-posts-table">
            <h3>Your Top Posts Details</h3>
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
                                <td class="text-center"><?= $post['likes_count'] ?></td>
                                <td class="text-center"><?= $post['comments_count'] ?></td>
                                <td class="text-center"><?= $post['total_engagement'] ?></td>
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
</section>

<script>
// Content Overview Chart
const contentCtx = document.getElementById('contentChart').getContext('2d');
const contentChart = new Chart(contentCtx, {
    type: 'bar',
    data: {
        labels: ['Posts', 'Tejido', 'Articles'],
        datasets: [{
            label: 'Published',
            data: [<?= $numbers_of_posts ?>, <?= $numbers_of_tejido ?>, <?= $numbers_of_articles ?>],
            backgroundColor: [
                'rgba(54, 162, 235, 0.7)',
                'rgba(75, 192, 192, 0.7)',
                'rgba(153, 102, 255, 0.7)'
            ],
            borderColor: [
                'rgba(54, 162, 235, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(153, 102, 255, 1)'
            ],
            borderWidth: 1
        }, {
            label: 'Drafts',
            data: [<?= $numbers_of_deactive_posts ?>, <?= $numbers_of_draft_tejido ?>, <?= $numbers_of_draft_articles ?>],
            backgroundColor: [
                'rgba(54, 162, 235, 0.3)',
                'rgba(75, 192, 192, 0.3)',
                'rgba(153, 102, 255, 0.3)'
            ],
            borderColor: [
                'rgba(54, 162, 235, 0.8)',
                'rgba(75, 192, 192, 0.8)',
                'rgba(153, 102, 255, 0.8)'
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
                text: 'Content Distribution'
            }
        }
    }
});

// User Engagement Chart
const engagementCtx = document.getElementById('engagementChart').getContext('2d');
const engagementChart = new Chart(engagementCtx, {
    type: 'pie',
    data: {
        labels: ['Comments', 'Likes'],
        datasets: [{
            data: [<?= $numbers_of_comments ?>, <?= $numbers_of_likes ?>],
            backgroundColor: [
                'rgba(255, 99, 132, 0.7)',
                'rgba(54, 162, 235, 0.7)'
            ],
            borderColor: [
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
                position: 'top',
            },
            title: {
                display: true,
                text: 'User Engagement Metrics'
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
                <?= $numbers_of_tejido ?>, 
                <?= $numbers_of_draft_tejido ?>, 
                <?= $numbers_of_articles ?>, 
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
                text: 'Content Status Distribution'
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
                text: 'Monthly Content Creation'
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
                text: 'Category Distribution'
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
                text: 'Monthly Engagement Trends'
            }
        }
    }
});

// Your Top Posts by Engagement Chart
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
                text: 'Your Top Posts by Engagement'
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
</script>

<script src="../js/admin_script.js"></script>

</body>
</html>