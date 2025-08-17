<?php
// Only include database connection if not already included
if (!class_exists('Database')) {
    include_once __DIR__ . '/connect.php';
    $db = new Database();
    $conn = $db->connect();
}

// Fetch footer information
$footer_query = $conn->prepare("SELECT * FROM footer_info LIMIT 1");
$footer_query->execute();
$footer = $footer_query->fetch(PDO::FETCH_ASSOC);

// Fetch social media links
$socials_query = $conn->prepare("SELECT * FROM footer_socials WHERE footer_id = ?");
$socials_query->execute([$footer['footer_id'] ?? 1]);
$socials = $socials_query->fetchAll(PDO::FETCH_ASSOC);

// Fetch about us content
$about_query = $conn->prepare("SELECT * FROM about_us ORDER BY about_id DESC LIMIT 1");
$about_query->execute();
$about_us = $about_query->fetch(PDO::FETCH_ASSOC);
?>


<link rel="stylesheet" href="../css/footer.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">


<footer class="footer">
    <div class="footer-container">
        <div class="footer-section logo">
            <img src="<?php echo htmlspecialchars($about_us['image'] ? '../uploads/' . $about_us['image'] : 'imgs/logo_trans.png'); ?>" alt="About Us Image" class="footer-logo">
        </div>
        <div class="footer-section contact">
            <h4>Contact Us</h4>
            <p>Address: <?= htmlspecialchars($footer['address'] ?? '123 University Ave, Zamboanga, Philippines') ?></p>
            <p>Email: <?= htmlspecialchars($footer['email'] ?? 'info@university.edu') ?></p>
            <p>Phone: <?= htmlspecialchars($footer['phone'] ?? '+63 123 456 7890') ?></p>
        </div> 
        <div class="footer-section about">
            <h4>About Us</h4>
            <p><?= htmlspecialchars(substr($about_us['description'] ?? 'Learn more about our university, our values, and our mission to provide quality education to students worldwide.', 0, 150)) ?>...</p>
            <a href="../user_content/about_us.php" class="footer-link">Read More</a>
        </div>
        <div class="footer-section links">
            <h4>Quick Links</h4>
            <ul>
                <li><a href="../user_content/more_announcement.php">Announcements</a></li>
                <li><a href="../user_content/news.php">News</a></li>
                <li><a href="../user_content/contact.php">Contact</a></li>
            </ul>
        </div>

        <div class="footer-section social">
            <h4>Follow Us</h4>
            <ul>
                <?php if (count($socials) > 0): ?>
                    <?php foreach ($socials as $social): ?>
                        <li>
                            <a href="<?= htmlspecialchars($social['url']) ?>">
                                <i class="<?= htmlspecialchars($social['icon_class']) ?>"></i> 
                                <?= htmlspecialchars($social['platform']) ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <!-- Fallback to static links if no social links in database -->
                    <li><a href="https://www.facebook.com/theuniversitydigest"><i class="fab fa-facebook-f"></i> Facebook</a></li>
                    <li><a href="https://www.facebook.com/theuniversitydigest"><i class="fab fa-instagram"></i> Instagram</a></li>
                    <li><a href="https://plus.google.com/university"><i class="fab fa-google"></i> Google</a></li>
                    <li><a href="https://www.youtube.com"><i class="fab fa-youtube"></i> YouTube</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; <?= htmlspecialchars($footer['copyright_text'] ?? '2025 The University Digest. All rights reserved.') ?></p>
    </div>
</footer>