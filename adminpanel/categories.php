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
<title>Category Management - QuickWash Admin</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root { --primary-color: #ff8383; --secondary-color: #f1f1f1; --bg-color: #f8f9fa; }
body { font-family: 'Segoe UI', sans-serif; background: var(--bg-color); margin:0; }

/* Sidebar */
.sidebar {
  background: var(--primary-color);
  color: #fff;
  height: 100vh;
  width: 240px;
  position: fixed;
  top: 0; left: 0;
  padding-top: 60px;
  transition: transform 0.3s ease;
}
.sidebar a {
  color: #fff;
  display: block;
  padding: 12px 20px;
  text-decoration: none;
  font-weight: 500;
  border-radius: 6px;
  margin: 3px 10px;
}
.sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.2); }

/* Topbar */
.topbar {
  position: fixed;
  top:0; left:0; right:0;
  height:60px;
  background: var(--primary-color);
  color:#fff;
  display:flex;
  align-items:center;
  justify-content:space-between;
  padding:0 20px;
  z-index:1000;
}
.topbar .menu-btn { font-size:22px; cursor:pointer; color:#fff; display:none; }

/* Content */
.content { margin-left:240px; padding:80px 20px 20px; transition: margin-left 0.3s ease; }
.sidebar.hide ~ .content { margin-left:0; }

/* Page Header */
.page-header { margin-bottom: 30px; }
.page-header h2 { font-weight:700; margin-bottom:10px; }
.page-header .add-section { font-weight:500; color:#555; }

/* Card & Table */
.card { border:none; border-radius:12px; box-shadow:0 3px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
.table thead { background: var(--secondary-color); }
.table th { font-weight:600; vertical-align: middle; }
.table td form { margin:0; }
.table-hover tbody tr:hover { background: rgba(255,131,131,0.1); }
.thumb { height:40px; }

/* Buttons */
.btn-update { background: var(--primary-color); border:none; color:#fff; }
.btn-update:hover { background:#e76e6e; }

/* Toast */
#toast {
  position: fixed;
  top:20px; left:50%;
  transform: translateX(-50%);
  z-index:1050;
  min-width:250px;
  display:none;
}
#toast.show { display:block; animation: fadein 0.5s, fadeout 0.5s 2.5s; }
@keyframes fadein { from { opacity:0; top:0; } to { opacity:1; top:20px; } }
@keyframes fadeout { from { opacity:1; top:20px; } to { opacity:0; top:0; } }

/* Responsive */
@media(max-width:991px){
  .sidebar { transform:translateX(-100%); position:fixed; z-index:1050; }
  .sidebar.show { transform:translateX(0); }
  .content { margin-left:0; }
  .topbar .menu-btn { display:block; }
}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
  <a href="index.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
  <a href="categories.php" class="active"><i class="bi bi-tags-fill me-2"></i> Categories</a>
</div>

<!-- Topbar -->
<div class="topbar">
  <div class="d-flex align-items-center">
    <i class="bi bi-list menu-btn me-2" onclick="toggleSidebar()"></i>
    <span>QuickWash Admin</span>
  </div>
  <a href="logout.php" class="btn btn-sm btn-outline-light"><i class="bi bi-box-arrow-right"></i> Logout</a>
</div>

<!-- Toast -->
<div id="toast" class="alert text-center rounded"></div>

<!-- Content -->
<div class="content">

  <!-- Page Header -->
  <div class="page-header">
    <h2>Category Management</h2>
    <div class="add-section">Add Category / Update Existing Categories Below</div>
  </div>

  <!-- Add New Category -->
  <div class="card p-3 mb-4">
    <h5 class="mb-3">Add New Category</h5>
    <form method="POST" action="add_category.php" class="row g-3" enctype="multipart/form-data">
      <div class="col-md-4"><input type="text" name="name" class="form-control" placeholder="Category Name" required></div>
      <div class="col-md-4"><input type="file" name="image" class="form-control" accept="image/*"></div>
      <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Add</button></div>
    </form>
  </div>

  <!-- Category Table -->
  <div class="card">
    <div class="card-body p-0 table-responsive">
      <table class="table table-hover table-striped align-middle">
        <thead>
          <tr>
            <th>ID</th><th>Name</th><th>Image</th><th colspan="2">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $categoriesTable = $conn->query("SELECT * FROM categories");
          while ($cat = $categoriesTable->fetch_assoc()):
          ?>
          <tr>
            <form method="POST" action="update_categorys.php" enctype="multipart/form-data">
              <input type="hidden" name="id" value="<?= $cat['id'] ?>">
              <td><?= $cat['id'] ?></td>
              <td><input type="text" name="name" value="<?= htmlspecialchars($cat['name']) ?>" class="form-control form-control-sm"></td>
              <td>
                <?php if ($cat['image']): ?>
                  <img src="<?= htmlspecialchars($cat['image']) ?>" class="thumb" alt="Image">
                <?php else: ?> N/A <?php endif; ?>
                <input type="file" name="image" accept="image/*" class="form-control form-control-sm mt-1">
              </td>
              <td><button type="submit" class="btn btn-sm btn-update"><i class="bi bi-check-circle"></i> Update</button></td>
            </form>
            <td>
              <form method="POST" action="delete_category.php" onsubmit="return confirm('Delete this category?');">
                <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> Delete</button>
              </form>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSidebar() { document.getElementById('sidebar').classList.toggle('show'); }

function showToast(type, message) {
  const toast = document.getElementById('toast');
  toast.textContent = message;
  toast.className = 'alert text-center rounded show ' + (type === 'success' ? 'alert-success' : 'alert-danger');
  setTimeout(() => { toast.classList.remove('show'); }, 3000);
}
</script>

<?php
// Trigger toast if session message exists
if (isset($_SESSION['success'])) {
    echo "<script>showToast('success', '" . $_SESSION['success'] . "');</script>";
    unset($_SESSION['success']);
}
if (isset($_SESSION['error'])) {
    echo "<script>showToast('error', '" . $_SESSION['error'] . "');</script>";
    unset($_SESSION['error']);
}
?>

</body>
</html>
