<?php
include '../components/connect.php';

$db = new Database();
$conn = $db->connect();

session_start();

// Check if admin is logged in and is a superadmin
$admin_id = $_SESSION['admin_id'] ?? null;
$admin_role = $_SESSION['admin_role'] ?? null;

// Only allow superadmin access
if(!isset($admin_id) || $admin_role !== 'superadmin'){
   $_SESSION['message'] = 'Please login as a superadmin to access this content.';
   header('location:../admin/admin_login.php');
   exit();
}

// Filtering parameters
$magazine_filter = $_GET['magazine'] ?? 'all';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Base query
$query = "
    SELECT mv.view_id, mv.viewed_at, 
           e.magazine_id, e.title as magazine_title, e.author as magazine_author,
           a.account_id, a.firstname, a.lastname, a.user_name, a.email
    FROM magazine_views mv
    JOIN e_magazines e ON mv.magazine_id = e.magazine_id
    JOIN accounts a ON mv.account_id = a.account_id
    WHERE 1=1
";

// Apply filters
$params = [];

if($magazine_filter != 'all') {
    $query .= " AND e.magazine_id = ?";
    $params[] = $magazine_filter;
}

if(!empty($date_from)) {
    $query .= " AND DATE(mv.viewed_at) >= ?";
    $params[] = $date_from;
}

if(!empty($date_to)) {
    $query .= " AND DATE(mv.viewed_at) <= ?";
    $params[] = $date_to;
}

// Order by most recent first
$query .= " ORDER BY mv.viewed_at DESC";

// Execute the query
$select_views = $conn->prepare($query);
$select_views->execute($params);

// Get all magazines for filter
$select_magazines = $conn->prepare("SELECT magazine_id, title FROM e_magazines ORDER BY title");
$select_magazines->execute();
$magazines = $select_magazines->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Magazine View Statistics</title>
   
   <!-- Font Awesome CDN link -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

   <!-- Admin CSS file link -->
   <link rel="stylesheet" href="../css/admin_style.css">

   <style>
      .filter-form {
         display: flex;
         flex-wrap: wrap;
         gap: 10px;
         margin-bottom: 20px;
      }
      
      .filter-form .input-field {
         flex: 1;
         min-width: 200px;
      }
      
      .stats-container {
         margin-top: 20px;
         display: flex;
         flex-wrap: wrap;
         gap: 15px;
      }
      
      .stat-box {
         background-color: #fff;
         padding: 20px;
         border-radius: 5px;
         box-shadow: 0 0 10px rgba(0,0,0,0.1);
         flex: 1;
         min-width: 250px;
      }
      
      .stat-box h3 {
         margin-bottom: 10px;
         color: #4F0003;
      }
      
      .stat-box .count {
         font-size: 2rem;
         font-weight: bold;
      }
      
      .view-table {
         width: 100%;
         border-collapse: collapse;
         margin-top: 20px;
      }
      
      .view-table th, .view-table td {
         padding: 12px;
         text-align: left;
         border-bottom: 1px solid #ddd;
      }
      
      .view-table th {
         background-color: #4F0003;
         color: white;
      }
      
      .view-table tr:hover {
         background-color: #f5f5f5;
      }
   </style>
</head>
<body>

<?php include '../components/superadmin_header.php'; ?>

<section class="dashboard">

   <h1 class="heading">Magazine View Statistics</h1>
   
   <!-- Filter form -->
   <div class="filter-container">
      <form action="" method="GET" class="filter-form">
         <div class="input-field">
            <select name="magazine" class="box">
               <option value="all" <?= ($magazine_filter === 'all') ? 'selected' : ''; ?>>All Magazines</option>
               <?php foreach($magazines as $magazine): ?>
                  <option value="<?= $magazine['magazine_id']; ?>" <?= ($magazine_filter == $magazine['magazine_id']) ? 'selected' : ''; ?>>
                     <?= htmlspecialchars($magazine['title']); ?>
                  </option>
               <?php endforeach; ?>
            </select>
         </div>
         
         <div class="input-field">
            <input type="date" name="date_from" placeholder="From Date" class="box" value="<?= htmlspecialchars($date_from); ?>">
         </div>
         
         <div class="input-field">
            <input type="date" name="date_to" placeholder="To Date" class="box" value="<?= htmlspecialchars($date_to); ?>">
         </div>
         
         <button type="submit" class="inline-btn">Apply Filters</button>
         <a href="sa_magazine_downloads.php" class="inline-option-btn">Reset</a>
      </form>
   </div>
   
   <!-- Statistics summary -->
   <div class="stats-container">
      <?php
         // Total views
         $total_views = $conn->prepare("SELECT COUNT(*) as total FROM magazine_views");
         $total_views->execute();
         $total = $total_views->fetch(PDO::FETCH_ASSOC)['total'];
         
         // Total unique users
         $unique_users = $conn->prepare("SELECT COUNT(DISTINCT account_id) as total FROM magazine_views");
         $unique_users->execute();
         $users = $unique_users->fetch(PDO::FETCH_ASSOC)['total'];
         
         // Total magazines viewed
         $viewed_magazines = $conn->prepare("SELECT COUNT(DISTINCT magazine_id) as total FROM magazine_views");
         $viewed_magazines->execute();
         $magazines_count = $viewed_magazines->fetch(PDO::FETCH_ASSOC)['total'];
      ?>
      
      <div class="stat-box">
         <h3>Total Views</h3>
         <div class="count"><?= $total; ?></div>
      </div>
      
      <div class="stat-box">
         <h3>Unique Users</h3>
         <div class="count"><?= $users; ?></div>
      </div>
      
      <div class="stat-box">
         <h3>Magazines Viewed</h3>
         <div class="count"><?= $magazines_count; ?></div>
      </div>
   </div>
   
   <!-- Views table -->
   <div class="table-container">
      <table class="view-table">
         <thead>
            <tr>
               <th>#</th>
               <th>Magazine</th>
               <th>User</th>
               <th>Email</th>
               <th>Viewed On</th>
            </tr>
         </thead>
         <tbody>
            <?php if($select_views->rowCount() > 0): ?>
               <?php $i = 1; while($row = $select_views->fetch(PDO::FETCH_ASSOC)): ?>
                  <tr>
                     <td><?= $i++; ?></td>
                     <td><?= htmlspecialchars($row['magazine_title']) . ' by ' . htmlspecialchars($row['magazine_author']); ?></td>
                     <td><?= htmlspecialchars($row['firstname']) . ' ' . htmlspecialchars($row['lastname']) . ' (' . htmlspecialchars($row['user_name']) . ')'; ?></td>
                     <td><?= htmlspecialchars($row['email']); ?></td>
                     <td><?= date('M d, Y - h:i A', strtotime($row['viewed_at'])); ?></td>
                  </tr>
               <?php endwhile; ?>
            <?php else: ?>
               <tr>
                  <td colspan="5" style="text-align:center;">No magazine views found</td>
               </tr>
            <?php endif; ?>
         </tbody>
      </table>
   </div>

</section>

<script src="../js/admin_script.js"></script>

</body>
</html>