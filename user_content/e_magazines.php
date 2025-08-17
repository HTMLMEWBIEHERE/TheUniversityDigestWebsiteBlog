<?php
// Include database connection
include '../components/connect.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize connection using Database class (like in comics.php)
$db = new Database();
$conn = $db->connect();

// Define number of magazines per page
$magazinesPerPage = 9;

// Get current page number
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); // Ensure page is at least 1

// Calculate the starting offset for the query
$offset = ($page - 1) * $magazinesPerPage;

// Query to get all e-magazines with pagination
$select_magazines = $conn->prepare("
    SELECT m.*, a.firstname, a.lastname, a.user_name, c.name as category_name 
    FROM e_magazines m
    LEFT JOIN accounts a ON m.created_by = a.account_id
    LEFT JOIN category c ON m.category_id = c.category_id
    ORDER BY m.created_at DESC
    LIMIT :limit OFFSET :offset
");
$select_magazines->bindValue(':limit', $magazinesPerPage, PDO::PARAM_INT);
$select_magazines->bindValue(':offset', $offset, PDO::PARAM_INT);
$select_magazines->execute();
$magazines = $select_magazines->fetchAll(PDO::FETCH_ASSOC);

// Get total number of magazines for pagination
$count_magazines = $conn->prepare("SELECT COUNT(*) FROM e_magazines");
$count_magazines->execute();
$totalMagazines = $count_magazines->fetchColumn();

// Calculate total number of pages
$totalPages = ceil($totalMagazines / $magazinesPerPage);

// Check if user is logged in
$isLoggedIn = isset($_SESSION['account_id']) && !empty($_SESSION['account_id']);

// Add this code where you display magazine links
// When a user clicks to view a magazine, capture the view event
function recordMagazineView($magazine_id, $conn) {
    $account_id = $_SESSION['account_id'] ?? null;
    
    if ($account_id) {
        // Insert view record
        $insert_view = $conn->prepare("INSERT INTO magazine_views (magazine_id, account_id) VALUES (?, ?)");
        $insert_view->execute([$magazine_id, $account_id]);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Magazines | University Digest</title>
    <link rel="stylesheet" href="../css/userheader.css">
    <link rel="stylesheet" href="../css/e_magazines.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include_once '../components/user_header.php'; ?>

    <div class="magazines-container">
        <h1 class="page-title">E-Magazines</h1>
        
        <?php if($select_magazines->rowCount() > 0): ?>
            <div class="magazines-grid">
                <?php foreach($magazines as $magazine): ?>
                    <div class="magazine-card">
                        <img src="../uploaded_img/<?= $magazine['image'] ?? 'default_magazine.jpg' ?>" class="magazine-img" alt="<?= htmlspecialchars($magazine['title']) ?>">
                        <div class="magazine-content">
                            <h3 class="magazine-title"><?= htmlspecialchars($magazine['title']) ?></h3>
                            
                            <?php if(!empty($magazine['category_name'])): ?>
                                <div class="magazine-category"><?= htmlspecialchars($magazine['category_name']) ?></div>
                            <?php endif; ?>
                            
                            <p class="magazine-author">By: <?= htmlspecialchars($magazine['author']) ?></p>
                            
                            <p class="magazine-excerpt"><?= substr(htmlspecialchars($magazine['context']), 0, 100) ?>...</p>
                            
                            <?php if($isLoggedIn): ?>
                                <!-- Show active link for logged in users -->
                                <a href="../user_content/view_magazine.php?id=<?= $magazine['magazine_id'] ?>" target="_blank" class="read-more">View Magazine</a>
                            <?php else: ?>
                                <!-- Show login prompt for guests -->
                                <a href="javascript:void(0);" class="login-required">View Magazine</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if($totalPages > 1): ?>
                <div class="pagination">
                    <?php if($page > 1): ?>
                        <a href="?page=1">&laquo; First</a>
                        <a href="?page=<?= $page - 1 ?>">Previous</a>
                    <?php endif; ?>
                    
                    <?php
                    // Show page numbers with current page highlighted
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    for($i = $startPage; $i <= $endPage; $i++):
                    ?>
                        <?php if($i == $page): ?>
                            <span class="active"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?page=<?= $i ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>">Next</a>
                        <a href="?page=<?= $totalPages ?>">Last &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="no-magazines">
                <p>No magazines available at the moment.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Login message for non-logged-in users -->
    <?php if(!$isLoggedIn): ?>
    <div class="overlay" id="overlay"></div>
    <div class="login-message" id="login-message">
        <span class="close-login" id="close-login">&times;</span>
        <h3>Login Required</h3>
        <p>You need to login to view our magazines.</p>
        <div class="login-btns">
            <a href="login_users.php" class="login-btn">Login</a>
            <a href="register.php" class="register-btn">Register</a>
        </div>
    </div>
    <?php endif; ?>
    <!-- Footer and Scripts -->
    <?php include '../components/footer.php'; ?>
    <script src="../js/e_magazines.js"></script>
</body>
</html>