<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

include "db.php";

// Fetch categories with items
$categoriesResult = $conn->query("SELECT * FROM categories");
$categories = [];
while ($cat = $categoriesResult->fetch_assoc()) {
    $catId = $cat['id'];
    $items = $conn->query("SELECT i.*, s.name as service_name FROM items i JOIN services s ON i.service_id = s.id WHERE i.category_id = $catId");
    $cat['items'] = $items;
    $categories[] = $cat;
}

// Fetch all services for add item form
$servicesAll = $conn->query("SELECT * FROM services");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Manage Items & Prices - Laundry Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root { --primary-color: #ff8383; --secondary-color: #f1f1f1; }
body { font-family: 'Segoe UI', sans-serif; background: #f5f6fa; margin:0; }

/* Sidebar */
.sidebar { background: var(--primary-color); color: #fff; height: 100vh; position: fixed; top: 0; left: 0; width: 240px; padding-top: 60px; transition: transform 0.3s ease; }
.sidebar a { color: #fff; display: block; padding: 12px 20px; text-decoration: none; font-weight: 500; border-radius: 6px; margin: 3px 10px; }
.sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.2); }

/* Topbar */
.topbar { position: fixed; top:0; left:0; right:0; height:60px; background: var(--primary-color); color:#fff; display:flex; align-items:center; justify-content:space-between; padding:0 20px; z-index:1000; }
.topbar .menu-btn { font-size:22px; cursor:pointer; color:#fff; }
.topbar .title { font-weight:600; font-size:18px; }

/* Content */
.content { margin-left:240px; padding:80px 20px 20px; transition: margin-left 0.3s ease; }
.sidebar.hide ~ .content { margin-left:0; }

/* Cards and table */
.card { border:none; border-radius:12px; box-shadow:0 3px 10px rgba(0,0,0,0.1); }
.card-header { font-weight:600; background:#fff; border-bottom:none; }
.table thead { background: var(--secondary-color); }
.table th { font-weight:600; }
.table td form { margin:0; }
.table-hover tbody tr:hover { background: rgba(255,131,131,0.1); }

/* Buttons */
.btn-sm { font-size:0.8rem; }
.btn-update { background: var(--primary-color); border:none; color:#fff; }
.btn-update:hover { background: #e76e6e; }

/* Toast popup */
#toast { position: fixed; top: 20px; left: 50%; transform: translateX(-50%); z-index: 1050; min-width: 250px; display: none; }
#toast.show { display: block; animation: fadein 0.5s, fadeout 0.5s 2.5s; }
@keyframes fadein { from { opacity:0; top:0; } to { opacity:1; top:20px; } }
@keyframes fadeout { from { opacity:1; top:20px; } to { opacity:0; top:0; } }

/* Add Item Popup */
#addItemPopup { position: fixed; top: -100%; left: 50%; transform: translateX(-50%); width: 90%; max-width: 900px; background: #fff; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); z-index: 1100; transition: top 0.4s ease; padding: 20px; }
#addItemPopup.show { top: 80px; }
#addItemPopup .popup-header { display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #ddd; margin-bottom:15px; }
#addItemPopup .popup-header h5 { margin:0; color: var(--primary-color); }
#addItemPopup .popup-header .close-btn { cursor:pointer; font-size:1.2rem; color:#888; }

/* Responsive */
@media(max-width:991px){ .sidebar { transform:translateX(-100%); position:fixed; z-index:1050; } .sidebar.show { transform:translateX(0); } .content { margin-left:0; } }
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
  <a href="index.php" class="active"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
  <a href="manage_items.php"><i class="bi bi-tags-fill me-2"></i> Items & Prices</a>
</div>

<!-- Topbar -->
<div class="topbar">
  <div class="d-flex align-items-center">
    <i class="bi bi-list menu-btn me-2" onclick="toggleSidebar()"></i>
    <span class="title">QuickWash Admin</span>
  </div>
  <div>
    <a href="logout.php" class="btn btn-sm btn-outline-light"><i class="bi bi-box-arrow-right"></i> Logout</a>
  </div>
</div>

<!-- Toast -->
<div id="toast" class="alert alert-success text-center rounded">Updated successfully!</div>

<!-- Add Item Popup -->
<div id="addItemPopup">
  <div class="popup-header">
    <h5>Add New Item</h5>
    <span class="close-btn" onclick="closeAddPopup()">&times;</span>
  </div>
  <form method="POST" action="add_item.php" class="row g-3" enctype="multipart/form-data">
    <div class="col-md-4"><input type="text" name="name" class="form-control" placeholder="Item Name" required></div>
    <div class="col-md-4"><input type="text" name="subtitle" class="form-control" placeholder="Subtitle (optional)"></div>
    <div class="col-md-4"><input type="file" name="image" class="form-control" accept="image/*"></div>
    <div class="col-md-4">
      <select name="category_id" class="form-select" required>
        <option value="">Select Category</option>
        <?php foreach($categories as $category): ?>
          <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-4">
      <select name="service_id" class="form-select" required>
        <option value="">Select Service</option>
        <?php $servicesAll->data_seek(0); while($s = $servicesAll->fetch_assoc()): ?>
          <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="col-md-4"><input type="number" name="price" class="form-control" step="0.01" placeholder="Price" required></div>
    <div class="col-12"><button type="submit" class="btn btn-primary w-100">Add Item</button></div>
  </form>
</div>

<!-- Content -->
<div class="content">
  <div class="d-flex flex-wrap align-items-center mb-3 gap-2">
    <input type="text" id="searchInput" class="form-control me-2" placeholder="Search by item name" style="max-width:250px;">
    <select id="categoryFilter" class="form-select" style="max-width:200px;">
      <option value="">All Categories</option>
      <?php foreach($categories as $category): ?>
        <option value="<?= htmlspecialchars($category['name']) ?>"><?= htmlspecialchars($category['name']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4>Manage Items & Prices</h4>
    <button class="btn btn-primary" onclick="openAddPopup()"><i class="bi bi-plus-circle"></i> Add Item</button>
  </div>

  <?php foreach($categories as $category): ?>
    <div class="card mb-4 category-card" data-category="<?= htmlspecialchars($category['name']) ?>">
      <div class="card-header"><?= htmlspecialchars($category['name']) ?></div>
      <div class="card-body p-0 table-responsive">
        <table class="table table-bordered table-hover mb-0 align-middle">
          <thead>
            <tr>
              <th>Image</th>
              <th>Item</th>
              <th>Subtitle</th>
              <th>Service</th>
              <th>Price / Subtitle / Image</th>
              <th>Update</th>
              <th>Delete</th>
            </tr>
          </thead>
          <tbody>
            <?php while($item = $category['items']->fetch_assoc()): ?>
            <tr class="item-row">
              <td>
                <?php if($item['image']): ?>
                  <img src="uploads/items/<?= htmlspecialchars($item['image']) ?>" width="60" height="60" style="border-radius:8px; object-fit:cover;">
                <?php else: ?>
                  <span class="text-muted">No Image</span>
                <?php endif; ?>
              </td>
              <td class="item-name"><?= htmlspecialchars($item['name']) ?></td>
              <td><input type="text" class="form-control form-control-sm subtitle-input" value="<?= htmlspecialchars($item['subtitle'] ?? '') ?>" data-item-id="<?= $item['id'] ?>"></td>
              <td><?= htmlspecialchars($item['service_name']) ?></td>
              <td>
                <input type="number" step="0.01" class="form-control form-control-sm price-input" value="<?= $item['price'] ?>" data-item-id="<?= $item['id'] ?>" placeholder="Price">
                <input type="file" class="form-control form-control-sm mt-1 image-input" data-item-id="<?= $item['id'] ?>" accept="image/*">
              </td>
              <td><button class="btn btn-sm btn-update update-btn" data-item-id="<?= $item['id'] ?>"><i class="bi bi-check-circle"></i> Update</button></td>
              <td>
                <form method="POST" action="delete_item.php" onsubmit="return confirm('Delete this item?');">
                  <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-danger"><i class="bi bi-trash"></i> Delete</button>
                </form>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Sidebar toggle
function toggleSidebar() { document.getElementById('sidebar').classList.toggle('show'); }
// Toast
function showToast() { const toast = document.getElementById('toast'); toast.classList.add('show'); setTimeout(() => toast.classList.remove('show'), 3000); }
// Add popup
function openAddPopup() { document.getElementById('addItemPopup').classList.add('show'); }
function closeAddPopup() { document.getElementById('addItemPopup').classList.remove('show'); }

// Update item via AJAX
document.querySelectorAll('.update-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    const itemId = btn.dataset.itemId;
    const price = document.querySelector('.price-input[data-item-id="'+itemId+'"]').value;
    const subtitle = document.querySelector('.subtitle-input[data-item-id="'+itemId+'"]').value;
    const imageInput = document.querySelector('.image-input[data-item-id="'+itemId+'"]');
    
    const formData = new FormData();
    formData.append('item_id', itemId);
    formData.append('price', price);
    formData.append('subtitle', subtitle);
    if(imageInput.files[0]) formData.append('image', imageInput.files[0]);
    
    fetch('update_item.php', {
      method: 'POST',
      body: formData
    })
    .then(res => res.text())
    .then(data => {
      if(data.trim() === 'success'){
        showToast();
        setTimeout(()=> location.reload(), 800); // reload to show updated image
      } else {
        alert(data);
      }
    })
    .catch(err => alert('Failed to update'));
  });
});

// Search & Filter
const searchInput = document.getElementById('searchInput');
const categoryFilter = document.getElementById('categoryFilter');
searchInput.addEventListener('keyup', filterItems);
categoryFilter.addEventListener('change', filterItems);
function filterItems() {
  const searchText = searchInput.value.toLowerCase();
  const selectedCategory = categoryFilter.value;
  document.querySelectorAll('.category-card').forEach(card => {
    const categoryName = card.dataset.category;
    let showCard = false;
    card.querySelectorAll('.item-row').forEach(row => {
      const itemName = row.querySelector('.item-name').textContent.toLowerCase();
      const matchesSearch = itemName.includes(searchText);
      const matchesCategory = !selectedCategory || selectedCategory === categoryName;
      row.style.display = (matchesSearch && matchesCategory) ? '' : 'none';
      if(row.style.display !== 'none') showCard = true;
    });
    card.style.display = showCard ? '' : 'none';
  });
}
</script>
</body>
</html>
