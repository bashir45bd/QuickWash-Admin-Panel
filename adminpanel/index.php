<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

include "includes/db.php";
include "includes/header.php";

// Fetch admin username
$adminId = $_SESSION['admin'];
$adminRow = $conn->query("SELECT username FROM admin WHERE id = $adminId")->fetch_assoc();
$adminUsername = $adminRow['username'] ?? 'Admin';

// Analytics counters
$totalUsers = $conn->query("SELECT COUNT(*) AS total FROM users")->fetch_assoc()['total'] ?? 0;
$today = date('Y-m-d');
$ordersToday = $conn->query("SELECT COUNT(*) AS total FROM orders WHERE DATE(created_at) = '$today'")->fetch_assoc()['total'] ?? 0;
$totalCategories = $conn->query("SELECT COUNT(*) AS total FROM categories")->fetch_assoc()['total'] ?? 0;
$totalPromos = $conn->query("SELECT COUNT(*) AS total FROM promo_codes")->fetch_assoc()['total'] ?? 0;

// Revenue Today / This Month
$revenueToday = $conn->query("SELECT SUM(amount) AS total FROM orders WHERE DATE(created_at) = '$today'")->fetch_assoc()['total'] ?? 0;
$monthStart = date('Y-m-01');
$revenueMonth = $conn->query("SELECT SUM(amount) AS total FROM orders WHERE DATE(created_at) >= '$monthStart'")->fetch_assoc()['total'] ?? 0;

// Pending Orders Count
$pendingOrders = $conn->query("SELECT COUNT(*) AS total FROM orders WHERE status='Pending'")->fetch_assoc()['total'] ?? 0;

// Most Popular Service
$popularService = $conn->query("
    SELECT s.name, COUNT(*) AS count
    FROM services s
    JOIN orders o ON s.id = o.service_id
    GROUP BY s.id
    ORDER BY count DESC
    LIMIT 1
")->fetch_assoc();
$popularServiceName = $popularService['name'] ?? 'N/A';

// Top Customers
$topCustomers = $conn->query("
    SELECT u.name, COUNT(*) as orders_count 
    FROM users u 
    JOIN orders o ON u.id = o.user_id 
    GROUP BY u.id 
    ORDER BY orders_count DESC LIMIT 5
");

// Fetch recent 5 orders with noti_status
$recentOrdersResult = $conn->query("
    SELECT id, name, noti_status, created_at 
    FROM orders 
    ORDER BY created_at DESC 
    LIMIT 5
");


// Latest Reviews
$reviewsQuery = "
  SELECT r.id, r.rating, r.comment, r.created_at, u.name AS user_name
  FROM reviews r
  JOIN users u ON r.user_id = u.id
  ORDER BY r.created_at DESC
  LIMIT 5
";
$reviewsResult = $conn->query($reviewsQuery);

// Order Status Data for Charts
$orderStatusData = [];
$statusResult = $conn->query("SELECT noti_status, COUNT(*) as count FROM orders GROUP BY noti_status");
while ($row = $statusResult->fetch_assoc()) {
    $orderStatusData[$row['noti_status']] = (int)$row['count'];
}

// Revenue Trend (last 7 days)
$revenueTrendData = [];
for ($i=6; $i>=0; $i--) {
    $day = date('Y-m-d', strtotime("-$i days"));
    $revenue = $conn->query("SELECT SUM(amount) AS total FROM orders WHERE DATE(created_at)='$day'")->fetch_assoc()['total'] ?? 0;
    $revenueTrendData[$day] = (float)$revenue;
}

// Category-wise Orders
$categoryData = [];
$categoryResult = $conn->query("
    SELECT c.name, COUNT(*) AS count 
    FROM categories c 
    JOIN items i ON c.id = i.category_id 
    JOIN order_items oi ON i.name = oi.item_name
    GROUP BY c.id
");
while($row = $categoryResult->fetch_assoc()) {
    $categoryData[$row['name']] = (int)$row['count'];
}

// Customer Growth (last 6 months)
$customerGrowth = [];
for ($i=5; $i>=0; $i--) {
    $month = date('Y-m', strtotime("-$i month"));
    $monthName = date('M Y', strtotime("-$i month"));
    $count = $conn->query("SELECT COUNT(*) AS total FROM users WHERE DATE(token_created_at) LIKE '$month%'")->fetch_assoc()['total'] ?? 0;
    $customerGrowth[$monthName] = (int)$count;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>QuickWash Admin Dashboard</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

<style>
:root { --primary-color: #ff8383; }

body {
    background: #f5f5f5;
    font-family: 'Segoe UI', sans-serif;
    margin: 0;
    overflow-x: hidden; /* prevent horizontal scroll */
}

/* Top Bar */
#topBar {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 60px;
    background: var(--primary-color);
    color: #fff;
    z-index: 1100;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 1rem;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}
#topBar h4 {
    margin: 0;
    font-size: 1.25rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
#topBar span { font-weight: 500; }

/* Sidebar */
.sidebar, .offcanvas-lg {
    background: var(--primary-color) !important;
    color: #fff !important;
}
.sidebar a {
    color: #fff !important;
    text-decoration: none;
    display: block;
    padding: 12px 20px;
    border-radius: 5px;
}
.sidebar a:hover, .sidebar a.active {
    background: rgba(255,255,255,0.2);
}

/* Main Content */
#mainContent {
    transition: margin-left 0.3s, width 0.3s;
    padding: 100px 20px 20px 20px;
    box-sizing: border-box;
    width: 100%;
}

/* Welcome Message */
#welcomeMsg {
    font-size: 1.1rem;
    margin-bottom: 0px;
}

/* Cards */
.card-hover {
    transition: transform 0.3s, box-shadow 0.3s;
    border-radius: 10px;
}
.card-hover:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
}

/* Charts */
.chart-card { padding: 15px; }
#barChart, #pieChart, #revenueTrendChart, #categoryChart, #customerGrowthChart {
    height: 250px;
}

/* Tables */
.table-responsive { 
    max-height: 400px; 
    overflow-y: auto; 
    overflow-x: auto; /* allow horizontal scroll if needed */
}
.table-striped thead, .table-hover thead { background: transparent !important; }

/* Drawer Toggle Button */
#drawerToggleBtn {
    position: absolute;
    top: 12px;
    left: 12px;
    z-index: 1150;
}

/* Large devices (Desktop) */
@media (min-width: 992px) {
    #sidebar {
        position: fixed;
        width: 260px;
        top: 60px;
        left: 0;
        height: calc(100vh - 60px);
        z-index: 1030;
    }
    #mainContent { margin-left: 260px; width: calc(100% - 260px); }
    #drawerToggleBtn { display: none; }
}

/* Tablets */
@media (min-width: 577px) and (max-width: 991px) {
    .card-hover { padding: 1rem !important; }
    #welcomeMsg h5 { font-size: 1.1rem; }
    .table-responsive { font-size: 0.9rem; }
    #mainContent { margin-left: 0; padding-top: 100px; }
    #topBar h4 { margin-left: 50px; }
}

/* Phones */
@media (max-width: 576px) {
    #topBar h4 { font-size: 1rem; }
    .card-hover { padding: 0.75rem !important; }
    .card-hover h4 { font-size: 1.1rem; }
    .table-responsive { font-size: 0.85rem; }
    #welcomeMsg h5 { font-size: 1rem; }
    #mainContent { padding: 30px 10px 10px 10px !important; }
}

/* Extra large desktops */
@media (min-width: 1400px) {
    .card-hover h4 { font-size: 1.5rem; }
    #mainContent { padding: 40px; }
    .table-responsive { max-height: 500px; }
}

/* Charts responsive fix */
canvas {
    width: 100% !important;
    height: auto !important;
}

</style>

</head>
<body>

<!-- Top Bar -->
<div id="topBar">
  <button id="drawerToggleBtn" class="btn btn-sm btn-light text-dark" 
      data-bs-toggle="offcanvas" data-bs-target="#sidebar" aria-controls="sidebar">
      <i class="bi bi-list fs-4"></i>
  </button>
  <h4>Admin Dashboard</h4>
  <span><?= htmlspecialchars($adminUsername) ?></span>
</div>

<!-- Main Content -->
<div id="mainContent">

<div id="welcomeMsg" class="p-3 mb-4 rounded shadow-sm" style="background: #fff; color: #333; border-left: 5px solid var(--primary-color);">
    <h5 style="margin:0; font-weight:600;">Welcome back, <span style="color: var(--primary-color);"><?= htmlspecialchars($adminUsername) ?></span>!</h5>
    <small class="text-muted">Here's an overview of your QuickWash Dashboard.</small>
</div>

<!-- Sidebar / Offcanvas -->
<div class="offcanvas-lg offcanvas-start sidebar" tabindex="-1" id="sidebar">
  <div class="offcanvas-header d-lg-none">
    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body d-flex flex-column p-0">
    <ul class="nav flex-column mb-auto">
      <li><a href="index.php" class="active"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a></li>
      <li><a href="orders.php"><i class="bi bi-basket-fill me-2"></i> Orders</a></li>
      <li><a href="manage_items.php"><i class="bi bi-tags-fill me-2"></i> Items & Prices</a></li>
      <li><a href="categories.php"><i class="bi bi-folder-fill me-2"></i> Categories</a></li>
      <li><a href="promos.php"><i class="bi bi-percent me-2"></i> Promo Codes</a></li>
      <li><a href="manageUsers.php"><i class="bi bi-people-fill me-2"></i> Users</a></li>
      <li><a href="viewReviews.php"><i class="bi bi-chat-square-text-fill me-2"></i> Reviews</a></li>
      <li><a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
    </ul>
  </div>
</div>

<!-- KPI Cards (unchanged) -->
<div class="row g-4 mb-5">
  <div class="col-6 col-md-3"><div class="card card-hover shadow-sm text-white text-center p-3" style="background: var(--primary-color);"><i class="bi bi-people-fill fs-1"></i><h4 class="mt-2"><?= $totalUsers ?></h4><small>Total Users</small></div></div>
  <div class="col-6 col-md-3"><div class="card card-hover shadow-sm text-white text-center p-3" style="background: var(--primary-color);"><i class="bi bi-basket3-fill fs-1"></i><h4 class="mt-2"><?= $ordersToday ?></h4><small>Orders Today</small></div></div>
  <div class="col-6 col-md-3"><div class="card card-hover shadow-sm text-white text-center p-3" style="background: var(--primary-color);"><i class="bi bi-folder-fill fs-1"></i><h4 class="mt-2"><?= $totalCategories ?></h4><small>Categories</small></div></div>
  <div class="col-6 col-md-3"><div class="card card-hover shadow-sm text-white text-center p-3" style="background: var(--primary-color);"><i class="bi bi-percent fs-1"></i><h4 class="mt-2"><?= $totalPromos ?></h4><small>Promo Codes</small></div></div>
</div>

<!-- Additional Analytics Cards -->
<div class="row g-4 mb-5">
  <div class="col-6 col-md-3"><div class="card card-hover shadow-sm text-white text-center p-3" style="background: var(--primary-color);"><i class="bi bi-cash-stack fs-1"></i><h4 class="mt-2"><?= number_format($revenueToday,2) ?></h4><small>Revenue Today</small></div></div>
  <div class="col-6 col-md-3"><div class="card card-hover shadow-sm text-white text-center p-3" style="background: var(--primary-color);"><i class="bi bi-cash-stack fs-1"></i><h4 class="mt-2"><?= number_format($revenueMonth,2) ?></h4><small>Revenue This Month</small></div></div>
  <div class="col-6 col-md-3"><div class="card card-hover shadow-sm text-white text-center p-3" style="background: var(--primary-color); color:#333;"><i class="bi bi-clock fs-1"></i><h4 class="mt-2"><?= $pendingOrders ?></h4><small>Pending Orders</small></div></div>
  <div class="col-6 col-md-3"><div class="card card-hover shadow-sm text-white text-center p-3" style="background: var(--primary-color);"><i class="bi bi-star-fill fs-1"></i><h4 class="mt-2"><?= htmlspecialchars($popularServiceName) ?></h4><small>Most Popular Service</small></div></div>
</div>

<!-- Recent Orders & Reviews -->
<div class="row g-4 mb-4">
  <div class="col-lg-6">
    <div class="card shadow-sm h-100">
      <div class="card-header" style="background: var(--primary-color); color:#fff;">Recent Orders</div>
      <div class="card-body p-0 table-responsive">
        <table class="table table-hover table-striped mb-0 align-middle">
          <thead class="table-light"><tr><th>#ID</th><th>Name</th><th>Status</th><th>Date</th></tr></thead>
          <tbody>
            <?php if($recentOrdersResult->num_rows>0): while($order=$recentOrdersResult->fetch_assoc()): ?>
            <tr>
              <td><?= $order['id'] ?></td>
              <td><?= htmlspecialchars($order['name']) ?></td>
              <td><span class="badge <?= $order['noti_status']=='Pending'?'bg-warning text-dark':($order['noti_status']=='Picked Up'?'bg-info text-white':($order['noti_status']=='Washed'?'bg-primary text-white':'bg-success text-white')) ?>"><?= htmlspecialchars($order['noti_status']) ?></span></td>
              <td><?= date("M d, Y", strtotime($order['created_at'])) ?></td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="4" class="text-center">No orders found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="card shadow-sm h-100">
      <div class="card-header" style="background: var(--primary-color); color:#fff;">Latest Reviews</div>
      <div class="card-body p-0 table-responsive">
        <table class="table table-striped table-hover mb-0 align-middle">
          <thead><tr><th>User</th><th>Rating</th><th>Comment</th><th>Date</th></tr></thead>
          <tbody>
            <?php if($reviewsResult->num_rows>0): while($review=$reviewsResult->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($review['user_name']) ?></td>
              <td><?php for($i=1;$i<=5;$i++){ echo $i<=$review['rating']?'<i class="bi bi-star-fill text-warning"></i>':'<i class="bi bi-star text-secondary"></i>'; } ?></td>
              <td><?= htmlspecialchars(mb_strimwidth($review['comment'],0,50,'...')) ?></td>
              <td><?= date("M d, Y", strtotime($review['created_at'])) ?></td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="4" class="text-center">No reviews found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Charts: Reduce height for smaller devices -->
<div class="row g-3">
  <div class="col-lg-6 col-md-12 mb-3"><div class="card shadow-sm chart-card"><div class="card-header" style="background: var(--primary-color); color:#fff;">Order Status (Bar)</div><div class="card-body"><canvas id="barChart" height="220"></canvas></div></div></div>
  <div class="col-lg-6 col-md-12 mb-3"><div class="card shadow-sm chart-card"><div class="card-header" style="background: var(--primary-color); color:#fff;">Order Status (Pie)</div><div class="card-body"><canvas id="pieChart" height="220"></canvas></div></div></div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-6 col-md-12 mb-3"><div class="card shadow-sm chart-card"><div class="card-header" style="background: var(--primary-color); color:#fff;">Revenue Trend (7 Days)</div><div class="card-body"><canvas id="revenueTrendChart" height="220"></canvas></div></div></div>
  <div class="col-lg-6 col-md-12 mb-3"><div class="card shadow-sm chart-card"><div class="card-header" style="background: var(--primary-color); color:#fff;">Category-wise Orders</div><div class="card-body"><canvas id="categoryChart" height="220"></canvas></div></div></div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-12 col-md-12"><div class="card shadow-sm chart-card"><div class="card-header" style="background: var(--primary-color); color:#fff;">Customer Growth (Last 6 Months)</div><div class="card-body"><canvas id="customerGrowthChart" height="250"></canvas></div></div></div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const labels = <?= json_encode(array_keys($orderStatusData)) ?>;
const counts = <?= json_encode(array_values($orderStatusData)) ?>;
new Chart(document.getElementById('barChart'),{type:'bar',data:{labels,datasets:[{label:'Orders', data:counts, backgroundColor:['#ff9999','#ff6b6b','#ffadaf','#ff5c5c']}]},options:{responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{y:{beginAtZero:true}}}});
new Chart(document.getElementById('pieChart'),{type:'pie',data:{labels,datasets:[{data:counts, backgroundColor:['#ff9999','#ff6b6b','#ffadaf','#ff5c5c']}]},options:{responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'bottom'}}}});

// Revenue Trend
const revLabels = <?= json_encode(array_keys($revenueTrendData)) ?>;
const revData = <?= json_encode(array_values($revenueTrendData)) ?>;
new Chart(document.getElementById('revenueTrendChart'),{type:'line',data:{labels:revLabels,datasets:[{label:'Revenue', data:revData, borderColor:'#ff9999', backgroundColor:'rgba(13,110,253,0.1)', fill:true}]},options:{responsive:true, maintainAspectRatio:false}});

// Category-wise Orders
const catLabels = <?= json_encode(array_keys($categoryData)) ?>;
const catData = <?= json_encode(array_values($categoryData)) ?>;
new Chart(document.getElementById('categoryChart'),{type:'pie',data:{labels:catLabels,datasets:[{data:catData, backgroundColor:['#ff5c5c','#ffadaf','#ff6b6b','#ff9999','#ff7171']}]},options:{responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'bottom'}}}});

// Customer Growth
const custLabels = <?= json_encode(array_keys($customerGrowth)) ?>;
const custData = <?= json_encode(array_values($customerGrowth)) ?>;
new Chart(document.getElementById('customerGrowthChart'),{type:'line',data:{labels:custLabels,datasets:[{label:'New Users', data:custData, borderColor:'#ff9999', backgroundColor:'rgba(25,135,84,0.1)', fill:true}]},options:{responsive:true, maintainAspectRatio:false}});
</script>

<?php include "includes/footer.php"; ?>
