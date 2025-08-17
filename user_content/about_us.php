<?php
include '../components/connect.php';  // Fixed path with ../

$db = new Database();
$conn = $db->connect();

// Start session if needed
session_start();

// Fetch all organization categories
$select_categories = $conn->prepare("SELECT * FROM org_categories ORDER BY category_name ASC");
$select_categories->execute();
$categories = $select_categories->fetchAll(PDO::FETCH_ASSOC);

// Fetch the latest About Us entry
$about_query = $conn->prepare("SELECT * FROM about_us ORDER BY about_id DESC LIMIT 1");
$about_query->execute();
$about_us = $about_query->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - The University Digest</title>
    <link rel="stylesheet" href="../css/about_us.css">  <!-- Fixed path with ../ -->
    <link rel="stylesheet" href="../css/userheader.css"> 

</head>
<body>
    <?php include '../components/user_header.php'; ?>  <!-- Fixed path with ../ -->
    
    <div class="main">
        <div class="card-2">
            <div class="card-content">
                <h2 style="color:#4F0003;">
                    <?= htmlspecialchars($about_us['title'] ?? 'About The University Digest') ?>
                </h2>
                <p>
                    <?= nl2br(htmlspecialchars($about_us['description'] ?? 'No description available.')) ?>
                </p>
                <?php if (!empty($about_us['purpose'])): ?>
                    <p><strong>Purpose:</strong> <?= htmlspecialchars($about_us['purpose']) ?></p>
                <?php endif; ?>
            </div>
            <div class="logo-image">
                <img src="<?= !empty($about_us['image']) ? '../uploads/' . htmlspecialchars($about_us['image']) : '../imgs/logo_trans.png'; ?>" alt="About Us Image">
            </div>
        </div>

        

        <?php 
        // Loop through each category and display its members
        foreach ($categories as $category) {
            // Fetch members for this category who are not deleted
            $select_members = $conn->prepare("
                SELECT * FROM organizational_chart 
                WHERE category_id = ? AND is_deleted = 0 
                ORDER BY position, date_appointed DESC
            ");
            $select_members->execute([$category['category_id']]);
            $members = $select_members->fetchAll(PDO::FETCH_ASSOC);

            // Group members by position
            $grouped_members = [];
            foreach ($members as $member) {
                $grouped_members[$member['position']][] = $member;
            }

            // Only display section if members exist
            if (!empty($grouped_members)) {
        ?>
        <div class="writers-container">
            <h1><?= htmlspecialchars($category['category_name']) ?></h1>
            
            <?php 
            // Iterate through each position group
            foreach ($grouped_members as $position => $position_members) { 
            ?>
            <div class="position-group">
                <h2><?= htmlspecialchars($position) ?></h2>
                <div class="editorial-cards">
                    <?php foreach ($position_members as $member) { 
                        // Get image directly from database field
                        if (!empty($member['image'])) {
                            // Use the image path stored in the database, but ensure proper path prefix
                            $member_image = '../' . htmlspecialchars($member['image']);
                        } else {
                            // Default image if no image in database
                            $member_image = '../imgs/member.jpg';
                        }
                    ?>
                    <div class="card">
                        <div class="card-image">
                            <img src="<?= $member_image; ?>" alt="<?= htmlspecialchars($member['name']); ?>" onerror="this.onerror=null;this.src='../imgs/member.jpg';">
                        </div>
                        <div class="card-content">
                            <h2><?= htmlspecialchars($member['name']); ?></h2>
                            <p><?= htmlspecialchars($position); ?></p>
                        </div>
                    </div>
                    <?php } ?>
                </div>
            </div>
            <?php } ?>
        </div>
        <?php 
            } 
        } 
        ?>
    </div>  

    <div style="margin-bottom: 50px;"></div>
    
    <?php include '../components/footer.php'; ?>  <!-- Fixed path with ../ -->
    
    <script src="../js/script.js"></script>  <!-- Fixed path with ../ -->
</body>
</html>
