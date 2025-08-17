<?php
include '../components/connect.php';

$db = new Database();
$conn = $db->connect();

session_start();
$user_id = $_SESSION['user_id'] ?? '';

// Pagination
$results_per_page = 9;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $results_per_page;

// Filters
$category_filter = $_GET['category'] ?? null;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : null;

try {
    // Main Query
    $query = "SELECT t.*, a.user_name AS author_user_name, c.name AS category_name
              FROM tejido t
              LEFT JOIN accounts a ON t.created_by = a.account_id
              LEFT JOIN category c ON t.category_id = c.category_id
              WHERE t.status = 'published'";
    
    if ($category_filter) {
        $query .= " AND LOWER(c.name) = LOWER(:category)";
    }
    if ($search_query) {
        $query .= " AND (t.title LIKE :search OR t.description LIKE :search)";
    }

    $query .= " ORDER BY t.created_at DESC LIMIT :limit OFFSET :offset";
    $stmt = $conn->prepare($query);

    if ($category_filter) {
        $stmt->bindValue(':category', $category_filter, PDO::PARAM_STR);
    }
    if ($search_query) {
        $stmt->bindValue(':search', "%$search_query%", PDO::PARAM_STR);
    }

    $stmt->bindValue(':limit', $results_per_page, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    $tejidos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count total for pagination
    $count_query = "SELECT COUNT(*) FROM tejido t
                    LEFT JOIN category c ON t.category_id = c.category_id
                    WHERE t.status = 'published'";
    if ($category_filter) {
        $count_query .= " AND LOWER(c.name) = LOWER(:category)";
    }
    if ($search_query) {
        $count_query .= " AND (t.title LIKE :search OR t.description LIKE :search)";
    }

    $count_stmt = $conn->prepare($count_query);
    if ($category_filter) {
        $count_stmt->bindValue(':category', $category_filter, PDO::PARAM_STR);
    }
    if ($search_query) {
        $count_stmt->bindValue(':search', "%$search_query%", PDO::PARAM_STR);
    }
    $count_stmt->execute();
    $total_tejidos = $count_stmt->fetchColumn();
    $total_pages = ceil($total_tejidos / $results_per_page);

    // Categories
    $categories_stmt = $conn->query("SELECT DISTINCT name FROM category ORDER BY name ASC");
    $categories = $categories_stmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    $tejidos = [];
    $total_pages = 0;
    $categories = [];
    error_log("Tejido fetch error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Tejidos - The University Digest</title>
  <link rel="stylesheet" href="../css/more_tejidos.css">
  <link rel="stylesheet" href="../css/footer.css">
  <link rel="stylesheet" href="../css/userheader.css">
  
</head>
<body>

<?php include '../components/user_header.php'; ?>

<div class="main">
  <div class="card-2">
    <div class="card-content">
      <h2 style="color:#4F0003;">Tejidos Collection</h2>
      <p>Explore our curated collection of tejidos, showcasing creativity, culture, and storytelling through unique textile artworks.</p>
    </div>
    <div class="logo-image">
      <img src="../imgs/logo_trans.png" alt="Tejidos Logo">
    </div>
  </div>

  <!-- Filter -->
  <div class="tejidos-filter">
    <form action="" method="get" class="filter-form">
      <select name="category" class="box">
        <option value="">All Categories</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= htmlspecialchars($cat) ?>" <?= (strcasecmp($category_filter, $cat) === 0) ? 'selected' : '' ?>>
            <?= htmlspecialchars($cat) ?>
          </option>
        <?php endforeach; ?>
      </select>
      <input type="text" name="search" class="box" placeholder="Search tejidos..." value="<?= htmlspecialchars($search_query ?? '') ?>">
      <!-- <button type="submit" class="btn">Filter</button> -->
    </form>
  </div>

  <!-- Tejido Grid -->
  <div class="tejidos-container">
    <?php if (count($tejidos) > 0): ?>
      <?php foreach ($tejidos as $tejido): ?>
        <div class="tejido-card" onclick="showTejidoDetails(this)">
          <!-- Author section first -->
          <div class="post-admin">
            <i class="fas fa-user"></i>
            <div>
              <span class="author-name"><?= htmlspecialchars($tejido['author_user_name']) ?></span>
            </div>
          </div>
          
          <!-- Title next -->
          <h3><?= htmlspecialchars($tejido['title']) ?></h3>
          
          <!-- Image third -->
          <div class="tejido-image">
            <img src="../uploaded_img/<?= htmlspecialchars($tejido['img']) ?>" 
                 alt="<?= htmlspecialchars($tejido['title']) ?>"
                 onerror="this.src='../imgs/placeholder.jpg';">
          </div>
          
          <!-- Description and other content last -->
          <div class="tejido-content">
            <div class="description-wrapper">
              <p class="tejido-description collapsed" data-full-description="<?= htmlspecialchars($tejido['description']) ?>"><?= substr(htmlspecialchars($tejido['description']), 0, 150) ?>...</p>
            </div>
            
            <div class="tejido-meta">
              <span>Category: <?= htmlspecialchars($tejido['category_name']) ?></span>
              <span>Date: <?= date('F j, Y', strtotime($tejido['created_at'])) ?></span>
            </div>
            
            <div class="tejido-actions">
              <?php 
                $tejido_id = $tejido['tejido_id'];
                include '../components/like_post.php'; 
              ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="empty-state">
        <p>No tejidos found!</p>
        <?php if ($category_filter || $search_query): ?>
          <a href="more_tejidos.php" class="btn">Reset Filters</a>
        <?php endif; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Modal Overlay -->
  <div class="modal-overlay" onclick="closeModal()"></div>

  <!-- Pagination -->
  <?php if ($total_pages > 1): ?>
    <div class="pagination">
      <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <?php
          $queryParams = $_GET;
          $queryParams['page'] = $i;
          $queryString = http_build_query($queryParams);
        ?>
        <a href="?<?= $queryString ?>" class="<?= ($page == $i) ? 'active' : '' ?>">
          <?= $i ?>
        </a>
      <?php endfor; ?>
    </div>
  <?php endif; ?>
</div>

<?php include '../components/footer.php'; ?>
<script src="../js/script.js"></script>
<script src="../js/tejidos.js"></script>

</body>
</html>