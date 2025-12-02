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
<title>Manage Users - QuickWash Admin</title>
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

/* User Cards */
.user-card {
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    padding: 20px;
    transition: all 0.3s ease;
}
.user-card:hover { transform: scale(1.02); }
.user-image {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    object-fit: cover;
    border: 2px solid #dee2e6;
}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <a href="index.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
    <a href="manageUsers.php" class="active"><i class="bi bi-people-fill me-2"></i> Users</a>
</div>

<!-- Topbar -->
<div class="topbar">
    <span class="menu-btn" onclick="toggleSidebar()"><i class="bi bi-list"></i></span>
    <h6 class="m-0">QuickWash Admin - Manage Users</h6>
    <a href="logout.php" class="btn btn-sm btn-light"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
</div>

<!-- Content -->
<div class="content">

    <!-- Header -->
    <div class="mb-4">
        <h3 class="fw-bold">Manage Users</h3>
        <small class="text-muted">View and manage registered users</small>
    </div>

    <!-- Search -->
    <div class="mb-4">
        <input type="text" id="searchInput" class="form-control" placeholder="Search name, email, phone">
    </div>

    <!-- Users Cards -->
    <div class="row g-4" id="userCards">
        <?php
        $userResult = $conn->query("SELECT * FROM users ORDER BY id DESC");
        if($userResult->num_rows > 0):
            while($user = $userResult->fetch_assoc()):
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="user-card d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <img src="<?= !empty($user['image']) && file_exists($user['image']) ? $user['image'] : 'https://via.placeholder.com/70' ?>" class="user-image me-3">
                    <div>
                        <h6 class="mb-0"><?= htmlspecialchars($user['name']) ?></h6>
                        <small class="text-muted d-block"><?= htmlspecialchars($user['email']) ?></small>
                        <small class="text-muted d-block"><?= htmlspecialchars($user['phone']) ?></small>
                    </div>
                </div>
                <div class="d-flex flex-column align-items-end">
                    <button class="btn btn-sm btn-outline-primary mb-1" data-bs-toggle="modal" data-bs-target="#editUserModal"
                        data-id="<?= $user['id'] ?>"
                        data-name="<?= htmlspecialchars($user['name']) ?>"
                        data-email="<?= htmlspecialchars($user['email']) ?>"
                        data-phone="<?= htmlspecialchars($user['phone']) ?>"
                        data-image="<?= !empty($user['image']) && file_exists($user['image']) ? $user['image'] : '' ?>"
                    ><i class="bi bi-pencil-square"></i></button>
                    <form method="POST" action="delete_user.php" onsubmit="return confirm('Delete this user?');">
                        <input type="hidden" name="id" value="<?= $user['id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                    </form>
                </div>
            </div>
        </div>
        <?php
            endwhile;
        else:
            echo '<div class="text-center text-muted">No users found.</div>';
        endif;
        ?>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="edit_user.php" enctype="multipart/form-data">
        <div class="modal-header" style="background:var(--primary-color); color:#fff;">
          <h5 class="modal-title">Edit User</h5>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="id" id="modalUserId">
          <input type="hidden" name="old_image" id="modalUserOldImage">
          
          <div class="mb-3 text-center">
            <img src="" id="modalUserImagePreview" class="user-image mb-2" alt="Profile">
          </div>

          <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" id="modalUserName" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Email</label>
            <input type="email" name="email" id="modalUserEmail" class="form-control" required>
          </div>
          <div class="mb-3">
            <label>Phone</label>
            <input type="text" name="phone" id="modalUserPhone" class="form-control">
          </div>
          <div class="mb-3">
            <label>Profile Image</label>
            <input type="file" name="image" id="modalUserImage" class="form-control">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Update</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSidebar() { document.getElementById('sidebar').classList.toggle('active'); }

// Search filter
const searchInput = document.getElementById('searchInput');
searchInput.addEventListener('keyup', () => {
    const val = searchInput.value.toLowerCase();
    document.querySelectorAll('#userCards .col-md-6').forEach(card => {
        card.style.display = card.textContent.toLowerCase().includes(val) ? '' : 'none';
    });
});

// Populate Edit Modal with current user data
const editModal = document.getElementById('editUserModal');
editModal.addEventListener('show.bs.modal', event => {
  const btn = event.relatedTarget;
  document.getElementById('modalUserId').value = btn.dataset.id;
  document.getElementById('modalUserName').value = btn.dataset.name;
  document.getElementById('modalUserEmail').value = btn.dataset.email;
  document.getElementById('modalUserPhone').value = btn.dataset.phone;

  // Show existing image preview
  const imgPreview = document.getElementById('modalUserImagePreview');
  if(btn.dataset.image && btn.dataset.image.trim() !== '') {
      imgPreview.src = btn.dataset.image;
  } else {
      imgPreview.src = 'https://via.placeholder.com/80';
  }

  // Save old image path
  document.getElementById('modalUserOldImage').value = btn.dataset.image;
});
</script>
</body>
</html>
