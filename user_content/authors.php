<?php
include '../components/connect.php';

$db = new Database();
$conn = $db->connect();

session_start();

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    $user_id = '';
}

include '../components/like_post.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Creators</title>

    <!-- Font Awesome CDN link -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

    <!-- Custom CSS File -->
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/userheader.css">
</head>
<body>

<!-- Header Section Starts -->
<?php include '../components/user_header.php'; ?>
<!-- Header Section Ends -->

<section class="authors">
    <h1 class="heading">Creators</h1>

    <div class="box-container">
        <?php
        // Fetch all authors (superadmin and subadmin)
        $select_author = $conn->prepare("SELECT * FROM `accounts` WHERE role IN ('superadmin', 'subadmin')");
        $select_author->execute();

        if ($select_author->rowCount() > 0) {
            while ($fetch_authors = $select_author->fetch(PDO::FETCH_ASSOC)) {
                $author_id = $fetch_authors['account_id'];

                // Count posts by the author
                $count_admin_posts = $conn->prepare("SELECT * FROM `posts` WHERE created_by = ? AND status = 'published'");
                $count_admin_posts->execute([$author_id]);
                $total_admin_posts = $count_admin_posts->rowCount();

                // Count likes on the author's posts
                $count_admin_likes = $conn->prepare("
                    SELECT * FROM `likes` 
                    WHERE post_id IN (SELECT post_id FROM `posts` WHERE created_by = ?)
                ");
                $count_admin_likes->execute([$author_id]);
                $total_admin_likes = $count_admin_likes->rowCount();

                // Count comments on the author's posts
                $count_admin_comments = $conn->prepare("
                    SELECT * FROM `comments` 
                    WHERE post_id IN (SELECT post_id FROM `posts` WHERE created_by = ?)
                ");
                $count_admin_comments->execute([$author_id]);
                $total_admin_comments = $count_admin_comments->rowCount();
        ?>
                <div class="box">
                    <p>Author: <span><?= htmlspecialchars($fetch_authors['user_name'], ENT_QUOTES, 'UTF-8'); ?></span></p>
                    <p>Total Posts: <span><?= $total_admin_posts; ?></span></p>
                    <p>Posts Likes: <span><?= $total_admin_likes; ?></span></p>
                    <p>Posts Comments: <span><?= $total_admin_comments; ?></span></p>
                    <a href="author_posts.php?author=<?= $author_id; ?>" class="btn">View Posts</a>
                </div>
        <?php
            }
        } else {
            echo '<p class="empty">No authors found!</p>';
        }
        ?>
    </div>
</section>

<!-- Custom JS File -->
<script src="../js/script.js"></script>
<?php include '../components/footer.php'; ?>
</body>
</html>