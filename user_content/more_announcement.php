<?php
// Include your database connection
include '../components/connect.php';

// Start the session if needed
session_start();

// Initialize variables to avoid "undefined variable" warnings
$search = isset($_GET['search']) ? $_GET['search'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Establish a database connection using your Database class
$db = new Database();
$conn = $db->connect();

// Prepare the query with LEFT JOIN to get author's full name
$query = "SELECT a.*, 
          CONCAT(ac.firstname, ' ', COALESCE(ac.middlename, ''), ' ', ac.lastname) AS author_name 
          FROM `announcements` a 
          LEFT JOIN `accounts` ac ON a.created_by = ac.account_id";

// Apply search filter if search keyword is provided
if (!empty($search)) {
    $search_param = "%" . $search . "%";
    $query .= " WHERE a.`title` LIKE :search OR a.`content` LIKE :search";
}

// Apply sorting based on the selected option
switch ($sort) {
    case 'oldest':
        $query .= " ORDER BY a.`created_at` ASC";
        break;
    case 'a-z':
        $query .= " ORDER BY a.`title` ASC";
        break;
    case 'z-a':
        $query .= " ORDER BY a.`title` DESC";
        break;
    case 'newest':
    default:
        $query .= " ORDER BY a.`created_at` DESC";
        break;
}

// Prepare and execute the query
$select_announcements = $conn->prepare($query);
if (!empty($search)) {
    $select_announcements->bindParam(':search', $search_param, PDO::PARAM_STR);
}
$select_announcements->execute();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Announcements</title>
    <link rel="stylesheet" href="../css/announcement.css">
    <link rel="stylesheet" href="../css/userheader.css">
    <link rel="stylesheet" href="../css/footer.css">
</head>
<body>

<!-- Fixed path: added ../ prefix -->
<?php include '../components/user_header.php'; ?>

<div class="management-section">
    <h1 class="heading">ALL ANNOUNCEMENTS</h1>

    <!-- Filter Form -->
    <div class="filter-container">
        <form action="" method="GET" class="filter-form">
            <div class="input-field">
                <input type="text" name="search" placeholder="Search announcements..." class="box" value="<?= htmlspecialchars($search); ?>">
            </div>
            <br>
            <div class="input-field">
                <select name="sort" class="box" onchange="this.form.submit()">
                    <option value="newest" <?= ($sort === 'newest') ? 'selected' : ''; ?>>Newest First</option>
                    <option value="oldest" <?= ($sort === 'oldest') ? 'selected' : ''; ?>>Oldest First</option>
                    <option value="a-z" <?= ($sort === 'a-z') ? 'selected' : ''; ?>>A-Z</option>
                    <option value="z-a" <?= ($sort === 'z-a') ? 'selected' : ''; ?>>Z-A</option>
                </select>
            </div>

            <button type="submit" class="inline-btn">Apply Filters</button>
            <a href="more_announcement.php?search=&sort=newest" class="inline-option-btn">Reset</a>
        </form>
    </div>

    <!-- Announcements Display Section -->
    <section class="announcements">
        <div class="card-announcements-container">
            <?php
            if ($select_announcements->rowCount() > 0) {
                $announcements = $select_announcements->fetchAll(PDO::FETCH_ASSOC);

                foreach ($announcements as $announcement) {
                    ?>
                    <div class="card-announcements">
                        <div class="card-announcements-body">
                            <h2 class="card-announcements-title"><?= htmlspecialchars($announcement['title']); ?></h2>
                            <p class="card-announcements-content"><?= nl2br(htmlspecialchars($announcement['content'])); ?></p>

                            <?php if (!empty($announcement['image'])): ?>
                                <img class="card-announcements-image" src="../uploaded_img/<?= htmlspecialchars($announcement['image']); ?>" alt="<?= htmlspecialchars($announcement['title']); ?>">
                            <?php endif; ?>

                            <p class="card-announcements-footer">
                                Posted by: <span style="color: #EEA61A;">
                                    <?= htmlspecialchars(trim($announcement['author_name']) ?: 'Unknown'); ?>
                                </span> |
                                <?= date('M j, Y h:i A', strtotime($announcement['created_at'])); ?>
                            </p>

                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<p class="card-announcements-content">No announcements available at the moment.</p>';
            }
            ?>
        </div>
    </section>
</div>

<!-- Fixed path: added ../ prefix -->
<?php include '../components/footer.php'; ?>

</body>
</html>