<?php
// Improve session security
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../classes/database.class.php';

// More robust admin role check
if (empty($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'superadmin') {
    header('Location: ../access_denied.php');
    exit();
}

// DB connection
$db = new Database();
$conn = $db->connect();

// Sanitize and validate inputs
$records_per_page = 8;
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, [
    'options' => [
        'min_range' => 1,
        'default' => 1
    ]
]);

$offset = ($page - 1) * $records_per_page;

$type_filter = filter_input(INPUT_GET, 'type', FILTER_SANITIZE_STRING) ?? 'all';
$date_filter = filter_input(INPUT_GET, 'date', FILTER_SANITIZE_STRING) ?? 'all';

// Date condition logic
$date_condition = '';
switch ($date_filter) {
    case 'today':
        $date_condition = "AND DATE(timestamp) = CURDATE()";
        break;
    case 'this_week':
        $date_condition = "AND timestamp >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)";
        break;
    case 'past_2_weeks':
        $date_condition = "AND timestamp >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)";
        break;
    case 'this_month':
        $date_condition = "AND MONTH(timestamp) = MONTH(CURDATE()) AND YEAR(timestamp) = YEAR(CURDATE())";
        break;
    case 'past_3_months':
        $date_condition = "AND timestamp >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)";
        break;
}


try {
    // Comprehensive unified logs query with type filtering
    $type_condition = ($type_filter !== 'all') ? "AND type = :type_filter" : "";

    $logs_query = $conn->prepare("
        SELECT * FROM (
            (SELECT 
                'Post' AS type,
                p.title,
                p.created_at AS timestamp,
                p.status,
                a.firstname,
                a.lastname,
                a.user_name,
                'Created' AS action
            FROM posts p
            JOIN accounts a ON p.created_by = a.account_id)

            UNION ALL

            (SELECT 
                'Post' AS type,
                p.title,
                p.updated_at AS timestamp,
                p.status,
                a.firstname,
                a.lastname,
                a.user_name,
                'Updated' AS action
            FROM posts p
            JOIN accounts a ON p.created_by = a.account_id
            WHERE p.created_at != p.updated_at)

            UNION ALL

            (SELECT 
                'Article' AS type,
                ar.title,
                ar.created_at AS timestamp,
                ar.status,
                a.firstname,
                a.lastname,
                a.user_name,
                'Created' AS action
            FROM articles ar
            JOIN accounts a ON ar.created_by = a.account_id)

            UNION ALL

            (SELECT 
                'Organizational Member' AS type,
                oc.name AS title,
                oc.created_at AS timestamp,
                oc.position AS status,
                a.firstname,
                a.lastname,
                a.user_name,
                'Added' AS action
            FROM organizational_chart oc
            JOIN accounts a ON oc.created_by = a.account_id)

            
        UNION ALL

        (SELECT 
            'Organizational Member' AS type,
            oc.name AS title,
            oc.date_ended AS timestamp,
            oc.position AS status,
            a.firstname,
            a.lastname,
            a.user_name,
            'Removed' AS action
        FROM organizational_chart oc
        JOIN accounts a ON oc.created_by = a.account_id
        WHERE oc.date_ended IS NOT NULL)


            UNION ALL

            (SELECT 
                'Comment' AS type,
                p.title,
                c.commented_at AS timestamp,
                'commented' AS status,
                a.firstname,
                a.lastname,
                a.user_name,
                'Commented' AS action
            FROM comments c
            JOIN posts p ON c.post_id = p.post_id
            JOIN accounts a ON c.commented_by = a.account_id)

            UNION ALL

            (SELECT 
                'Like' AS type,
                p.title,
                l.created_at AS timestamp,
                'liked' AS status,
                a.firstname,
                a.lastname,
                a.user_name,
                'Liked' AS action
            FROM likes l
            JOIN posts p ON l.post_id = p.post_id
            JOIN accounts a ON l.account_id = a.account_id)

            UNION ALL

            (SELECT 
                'Carousel Image' AS type,
                CONCAT('Image #', ci.id, ' - Order: ', ci.display_order) AS title,
                ci.created_at AS timestamp,
                'added' AS status,
                a.firstname,
                a.lastname,
                a.user_name,
                'Added' AS action
            FROM carousel_images ci
            JOIN accounts a ON ci.account_id = a.account_id)
        ) AS all_logs
        WHERE 1=1 $date_condition $type_condition
        ORDER BY timestamp DESC
        LIMIT :limit OFFSET :offset
    ");

    // Bind parameters with explicit type casting
    $logs_query->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
    $logs_query->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    if ($type_filter !== 'all') {
        $logs_query->bindValue(':type_filter', $type_filter, PDO::PARAM_STR);
    }

    $logs_query->execute();
    $activity_logs = $logs_query->fetchAll(PDO::FETCH_ASSOC);

    // Count total logs for pagination
    $count_query = $conn->query("
    SELECT (
        (SELECT COUNT(*) FROM posts) +
        (SELECT COUNT(*) FROM articles) +
        (SELECT COUNT(*) FROM organizational_chart WHERE date_ended IS NOT NULL) +
        (SELECT COUNT(*) FROM organizational_chart) +
        (SELECT COUNT(*) FROM comments) +
        (SELECT COUNT(*) FROM likes) +
        (SELECT COUNT(*) FROM carousel_images)
    ) AS total_logs
");

    $total_logs = $count_query->fetch(PDO::FETCH_ASSOC)['total_logs'];

    // Add a maximum page limit
    $max_pages = 100;
    $total_pages = min(ceil($total_logs / $records_per_page), $max_pages);

} catch (PDOException $e) {
    // Improved error handling
    error_log("Activity Logs Error: " . $e->getMessage());
    
    // User-friendly error message
    $_SESSION['error_message'] = "An error occurred while retrieving activity logs. Please try again later.";
    header('Location: ../error.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <!-- Add this viewport meta tag -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs | Superadmin</title>
    <link rel="stylesheet" href="../css/admin_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>
    <?php include '../components/superadmin_header.php'; ?>

    <section class="activity-logs">
        <h1 class="heading">Activity Logs</h1>

        <div class="search-container">
            <input type="text" id="searchInput" placeholder="Search logs...">
            <select id="typeFilter">
                <option value="all">All Types</option>
                <option value="Post">Posts</option>
                <option value="Article">Articles</option>
                <option value="Organizational Member">Org Members</option>
                <option value="Comment">Comments</option>
                <option value="Like">Likes</option>
                <option value="Carousel Image">Carousel Images</option>
            </select>
            <select id="dateFilter">
                <option value="all">All Dates</option>
                <option value="today">Today</option>
                <option value="this_week">This Week</option>
                <option value="past_2_weeks">Past 2 Weeks</option>
                <option value="this_month">This Month</option>
                <option value="past_3_months">Past 3 Months</option>
            </select>
        </div>

        <table class="activity-logs-table">
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Title/Name</th>
                    <th>Created By</th>
                    <th>Timestamp</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($activity_logs)): ?>
                    <?php foreach ($activity_logs as $log): ?>
                        <tr>
                            <td><?= htmlspecialchars($log['type']) ?></td>
                            <td><?= htmlspecialchars($log['title']) ?></td>
                            <td>
                                <?php if (!empty($log['firstname']) || !empty($log['lastname'])): ?>
                                    <?= htmlspecialchars($log['firstname'] . ' ' . $log['lastname']) ?>
                                    (<?= htmlspecialchars($log['user_name']) ?>)
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </td>
                            <td><?= date('F j, Y, g:i a', strtotime($log['timestamp'])) ?></td>
                            <td><?= htmlspecialchars($log['action']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5">No activity logs found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="pagination">
            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <a href="?page=<?= $i ?>&type=<?= urlencode($type_filter) ?>&date=<?= urlencode($date_filter) ?>" 
                   class="<?= ($i === $page) ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
    </section>

    <script src="../js/admin_script.js"></script>
    <script>
    document.getElementById('typeFilter').addEventListener('change', function () {
        const type = this.value;
        const date = document.getElementById('dateFilter').value;
        window.location.href = `?page=1&type=${encodeURIComponent(type)}&date=${encodeURIComponent(date)}`;
    });

    document.getElementById('dateFilter').addEventListener('change', function () {
        const date = this.value;
        const type = document.getElementById('typeFilter').value;
        window.location.href = `?page=1&type=${encodeURIComponent(type)}&date=${encodeURIComponent(date)}`;
    });
</script>
<script>
    // Preserve selected values on reload
    document.getElementById('typeFilter').value = "<?= $type_filter ?>";
    document.getElementById('dateFilter').value = "<?= $date_filter ?>";
</script>

</body>
</html>