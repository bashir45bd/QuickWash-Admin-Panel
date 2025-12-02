<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}
include "db.php";
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Promo Code Management - QuickWash Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root {
    --primary-color: #ff8383;
    --secondary-color: #f1f1f1;
    --bg-color: #f8f9fa;
}
body {
    font-family: 'Segoe UI', sans-serif;
    background: var(--bg-color);
    margin:0;
}

/* Sidebar */
.sidebar {
    background: var(--primary-color);
    color: #fff;
    min-height: 100vh;
    width: 240px;
    position: fixed;
    top: 0;
    left: 0;
    padding-top: 60px;
    transition: transform 0.3s ease;
}
.sidebar a {
    color: #fff;
    display: block;
    padding: 14px 20px;
    text-decoration: none;
    font-weight: 500;
    border-radius: 8px;
    margin: 5px 10px;
}
.sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.25); }

/* Topbar */
.topbar {
    height: 60px;
    background: var(--primary-color);
    padding: 0 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    color: #fff;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    z-index: 1000;
}
.topbar .menu-btn {
    display: none;
    font-size: 1.5rem;
    cursor: pointer;
}

/* Content */
.content {
    margin-left: 240px;
    padding: 80px 20px 20px;
    transition: margin-left 0.3s ease;
}

/* Cards */
.card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    margin-bottom: 20px;
}
.card-header {
    background: var(--primary-color);
    color: #fff;
    font-weight: 600;
    font-size: 1.1rem;
    border-radius: 12px 12px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

/* Search & filter */
.filter-group input, .filter-group select {
    max-width: 220px;
    margin-left: 10px;
    border-radius: 10px;
}

/* Table */
.table thead { background: var(--secondary-color); }
.table th, .table td { vertical-align: middle; }
.table tr:hover { background: rgba(255,131,131,0.1); }

/* Buttons */
.btn-sm { font-size: 0.85rem; }
.btn-update { background: var(--primary-color); border:none; color:#fff; }
.btn-update:hover { background: #e76b6b; }

/* Responsive */
@media (max-width: 768px) {
    .sidebar { transform: translateX(-100%); z-index: 1200; }
    .sidebar.active { transform: translateX(0); }
    .topbar .menu-btn { display: block; }
    .content { margin-left: 0; padding: 80px 10px 20px; }
}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <a href="index.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
    <a href="promos.php" class="active"><i class="bi bi-ticket-perforated me-2"></i> Promo Codes</a>
</div>

<!-- Topbar -->
<div class="topbar">
    <span class="menu-btn" onclick="toggleSidebar()"><i class="bi bi-list"></i></span>
    <h6 class="m-0">QuickWash Admin - Promo Codes</h6>
    <a href="logout.php" class="btn btn-sm btn-light"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
</div>

<!-- Content -->
<div class="content">

    <!-- Header -->
    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div>
            <h3 class="fw-bold">Promo Code Management</h3>
            <small class="text-muted">Manage all active and inactive promo codes below</small>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPromoModal"><i class="bi bi-plus-circle me-1"></i> Add Promo</button>
    </div>

    <!-- Filter -->
    <div class="mb-3 d-flex align-items-center">
        <input type="text" id="searchInput" class="form-control me-2" placeholder="Search by code or discount">
        <select id="statusFilter" class="form-select" style="max-width:200px;">
            <option value="">All Status</option>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
        </select>
    </div>

    <!-- Promo Code Table -->
    <div class="card shadow-sm">
        <div class="card-body p-0 table-responsive">
            <table class="table table-bordered table-hover align-middle" id="promoTable">
                <thead>
                    <tr>
                        <th>#ID</th>
                        <th>Code</th>
                        <th>Discount (%)</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th colspan="2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $promoResult = $conn->query("SELECT * FROM promo_codes ORDER BY created_at DESC");
                while ($promo = $promoResult->fetch_assoc()):
                ?>
                    <tr>
                        <form method="POST" action="update_promo.php">
                            <input type="hidden" name="id" value="<?= $promo['id'] ?>">
                            <td><?= $promo['id'] ?></td>
                            <td><input type="text" name="code" value="<?= htmlspecialchars($promo['code']) ?>" class="form-control form-control-sm" required></td>
                            <td><input type="number" name="discount_percent" value="<?= $promo['discount_percent'] ?>" class="form-control form-control-sm" required></td>
                            <td>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="Active" <?= $promo['status']=='Active'?'selected':'' ?>>Active</option>
                                    <option value="Inactive" <?= $promo['status']=='Inactive'?'selected':'' ?>>Inactive</option>
                                </select>
                            </td>
                            <td><?= $promo['created_at'] ?></td>
                            <td><button type="submit" class="btn btn-sm btn-update">Update</button></td>
                        </form>
                        <td>
                            <form method="POST" action="delete_promo.php" onsubmit="return confirm('Delete this promo code?');">
                                <input type="hidden" name="id" value="<?= $promo['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Add Promo Modal -->
<div class="modal fade" id="addPromoModal" tabindex="-1" aria-labelledby="addPromoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="add_promo.php">
        <div class="modal-header" style="background:var(--primary-color); color:#fff;">
          <h5 class="modal-title" id="addPromoModalLabel">Add New Promo Code</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            <div class="mb-3">
                <label>Code</label>
                <input type="text" name="code" class="form-control" placeholder="Enter code" required>
            </div>
            <div class="mb-3">
                <label>Discount (%)</label>
                <input type="number" step="0.01" name="discount_percent" class="form-control" placeholder="Discount %" required>
            </div>
            <div class="mb-3">
                <label>Status</label>
                <select name="status" class="form-select" required>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Add Promo</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSidebar() {
    document.getElementById('sidebar').classList.toggle('active');
}

// Search & filter
const searchInput = document.getElementById('searchInput');
const statusFilter = document.getElementById('statusFilter');
const table = document.getElementById('promoTable').getElementsByTagName('tbody')[0];

searchInput.addEventListener('keyup', filterTable);
statusFilter.addEventListener('change', filterTable);

function filterTable() {
    const searchValue = searchInput.value.toLowerCase();
    const statusValue = statusFilter.value.toLowerCase();

    Array.from(table.rows).forEach(row => {
        const code = row.cells[1].textContent.toLowerCase();
        const discount = row.cells[2].textContent.toLowerCase();
        const status = row.cells[3].textContent.toLowerCase();

        const matchesSearch = code.includes(searchValue) || discount.includes(searchValue);
        const matchesStatus = !statusValue || status === statusValue;

        row.style.display = (matchesSearch && matchesStatus) ? '' : 'none';
    });
}
</script>
</body>
</html>
