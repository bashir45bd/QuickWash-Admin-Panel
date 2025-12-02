<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}
include "db.php";

// Filters & Pagination
$filterStatus = $_GET['status'] ?? '';
$filterService = $_GET['service'] ?? '';
$searchPhone = $_GET['phone'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;

$whereClauses = [];
$params = [];
$types = '';

if (!empty($filterStatus)) {
    $whereClauses[] = "status = ?";
    $params[] = $filterStatus;
    $types .= 's';
}
if (!empty($filterService)) {
    $whereClauses[] = "service = ?";
    $params[] = $filterService;
    $types .= 's';
}
if (!empty($searchPhone)) {
    $whereClauses[] = "phone LIKE ?";
    $params[] = "%$searchPhone%";
    $types .= 's';
}

$whereSQL = count($whereClauses) ? 'WHERE ' . implode(' AND ', $whereClauses) : '';

// Fetch orders
$stmt = $conn->prepare("SELECT * FROM orders $whereSQL ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Total count
$totalQuery = $conn->prepare("SELECT COUNT(*) as total FROM orders $whereSQL");
if ($params) $totalQuery->bind_param($types, ...$params);
$totalQuery->execute();
$totalResult = $totalQuery->get_result()->fetch_assoc();
$totalRows = $totalResult['total'];
$totalPages = ceil($totalRows / $limit);

// Services
$servicesResult = $conn->query("SELECT DISTINCT service FROM orders");
$services = [];
while ($row = $servicesResult->fetch_assoc()) {
    $services[] = $row['service'];
}

// Categories + Items
$categoriesResult = $conn->query("SELECT * FROM categories");
$categories = [];
while ($cat = $categoriesResult->fetch_assoc()) {
    $catId = $cat['id'];
    $items = $conn->query("SELECT i.*, s.name as service_name FROM items i JOIN services s ON i.service_id = s.id WHERE i.category_id = $catId");
    $cat['items'] = $items;
    $categories[] = $cat;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Laundry Admin Panel</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body { background-color: #f4f6f9; font-family: 'Segoe UI', sans-serif; }
    .container { margin-bottom: 20px; }
    .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.75rem; font-weight: 500; }
    .status-Pending { background-color: #ffc107; color: #000; }
    .status-PickedUp { background-color: #0d6efd; color: #fff; }
    .status-Washed { background-color: #20c997; color: #fff; }
    .status-Delivered { background-color: #198754; color: #fff; }
    .table th, .table td { vertical-align: middle; }
    .pagination a { text-decoration: none; }
    img.thumb { width: 50px; height: auto; border-radius: 8px; }
  </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid px-4">
    <a class="navbar-brand" href="#">QuickWash Admin</a>
    <div class="ms-auto">
      <a href="logout.php" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>
  </div>
</nav>

<div class="container mt-4">
  <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
  <?php endif; ?>
  <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
  <?php endif; ?>

  <!-- Category Management Section -->
  <div class="card shadow-sm mt-5">
    <div class="card-header bg-success text-white">
      <h5 class="mb-0">Manage Categories</h5>
    </div>
    <div class="card-body">
      <table class="table table-bordered table-sm">
        <thead>
          <tr><th>#ID</th><th>Name</th><th>Image</th><th colspan="2">Actions</th></tr>
        </thead>
        <tbody>
          <?php
          $categoriesTable = $conn->query("SELECT * FROM categories");
          while ($cat = $categoriesTable->fetch_assoc()):
          ?>
          <tr>
            <form method="POST" action="update_category.php" enctype="multipart/form-data">
              <input type="hidden" name="id" value="<?= $cat['id'] ?>">
              <td><?= $cat['id'] ?></td>
              <td><input type="text" name="name" value="<?= htmlspecialchars($cat['name']) ?>" class="form-control form-control-sm"></td>
              <td>
                <?php if ($cat['image']): ?>
                  <img src="<?= htmlspecialchars($cat['image']) ?>" class="thumb" alt="Image">
                <?php else: ?> N/A <?php endif; ?>
                <input type="file" name="image" accept="image/*" class="form-control form-control-sm mt-1">
              </td>
              <td><button type="submit" class="btn btn-sm btn-primary">Update</button></td>
            </form>
            <td>
              <form method="POST" action="delete_category.php" onsubmit="return confirm('Delete this category?');">
                <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
              </form>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>

      <!-- Add New Category -->
      <hr>
      <h6 class="mt-4">Add New Category</h6>
      <form method="POST" action="add_category.php" class="row g-3 mt-2" enctype="multipart/form-data">
        <div class="col-md-4"><input type="text" name="name" class="form-control" placeholder="Category Name" required></div>
        <div class="col-md-4"><input type="file" name="image" class="form-control" accept="image/*"></div>
        <div class="col-md-2"><button type="submit" class="btn btn-success">Add</button></div>
      </form>
    </div>
  </div>
</div>

<script>
  let typingTimer;
  const delay = 500;
  function delayedSubmit(form) {
    clearTimeout(typingTimer);
    typingTimer = setTimeout(() => { form.submit(); }, delay);
  }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
