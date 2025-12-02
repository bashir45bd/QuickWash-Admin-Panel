<?php
session_start();
include "db.php";

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();
    if (!$order) {
        header("Location: orders.php?error=Order not found");
        exit();
    }
} else {
    header("Location: orders.php?error=Invalid request");
    exit();
}

// Fetch admin username
$adminId = $_SESSION['admin'];
$adminRow = $conn->query("SELECT username FROM admin WHERE id = $adminId")->fetch_assoc();
$adminUsername = $adminRow['username'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Edit Order - QuickWash Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root { --primary-color: #ff8383; }
body { font-family: 'Segoe UI', sans-serif; background: #f5f6fa; margin:0; }

/* Sidebar */
.sidebar {
    background: var(--primary-color);
    color: #fff;
    height: 100vh;
    position: fixed;
    top: 0; left: 0;
    width: 250px;
    padding-top: 60px;
    transition: transform 0.3s ease;
}
.sidebar a {
    color: #fff; display: block; padding: 12px 20px; text-decoration: none; font-weight: 500;
}
.sidebar a:hover { background: rgba(255,255,255,0.2); }
.sidebar.hide { transform: translateX(-100%); }

/* Topbar */
.topbar {
    position: fixed; top:0; left:0; right:0;
    height:60px; background: var(--primary-color); color:#fff;
    display:flex; align-items:center; justify-content:space-between; padding:0 15px; z-index:1000;
}
.topbar .menu-btn { font-size:22px; cursor:pointer; color:#fff; }

/* Content */
.content { margin-left:250px; padding:80px 20px 20px; transition: margin-left 0.3s ease; }
.sidebar.hide ~ .content { margin-left:0; }

/* Card & Form */
.card { border:none; border-radius:12px; box-shadow:0 2px 6px rgba(0,0,0,0.1); }
.card-header { font-weight:600; font-size:18px; background:#fff; border-bottom:none; }
form .form-label { font-weight:500; }
#updateMessage { display:none; transition: opacity 0.5s ease-in-out; }

/* Responsive */
@media (max-width:991px){
  .sidebar { transform: translateX(-100%); }
  .sidebar.show { transform: translateX(0); }
  .content { margin-left:0; }
}
</style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <a href="index.php"><i class="bi bi-speedometer2"></i> Dashboard</a>
    <a href="orders.php"><i class="bi bi-bag-check"></i> Orders</a>
</div>

<!-- Topbar -->
<div class="topbar">
    <i class="bi bi-list menu-btn" onclick="toggleSidebar()"></i>
    <span>QuickWash Admin</span>
    <a href="logout.php" class="btn btn-sm btn-outline-light">Logout</a>
</div>

<!-- Content -->
<div class="content">
  <div class="card shadow-sm">
    <div class="card-header">Edit Order #<?= $order['id'] ?></div>
    <div class="card-body">
      <form id="editForm">
        <input type="hidden" name="id" value="<?= $order['id'] ?>">

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Customer Name</label>
            <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($order['name']) ?>" required>
          </div>
          <div class="col-md-6">
            <label class="form-label">Phone</label>
            <input type="text" class="form-control" name="phone" value="<?= htmlspecialchars($order['phone']) ?>" required>
          </div>
          <div class="col-12">
            <label class="form-label">Address</label>
            <textarea class="form-control" name="address" rows="2" required><?= htmlspecialchars($order['address']) ?></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label">Status</label>
            <select class="form-select" name="noti_status" required>
              <option value="Pending" <?= $order['noti_status']=='Pending'?'selected':'' ?>>Pending</option>
              <option value="Picked Up" <?= $order['noti_status']=='Picked Up'?'selected':'' ?>>Picked Up</option>
              <option value="Washed" <?= $order['noti_status']=='Washed'?'selected':'' ?>>Washed</option>
              <option value="Delivered" <?= $order['noti_status']=='Delivered'?'selected':'' ?>>Delivered</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Promo Code</label>
            <input type="text" class="form-control" name="promo_code" value="<?= htmlspecialchars($order['promo_code']) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Pickup Time</label>
            <input type="datetime-local" class="form-control" name="pickup_time" value="<?= date('Y-m-d\TH:i', strtotime($order['pickup_time'])) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Delivery Time</label>
            <input type="datetime-local" class="form-control" name="delivery_time" value="<?= date('Y-m-d\TH:i', strtotime($order['delivery_time'])) ?>">
          </div>
        </div>

        <div class="d-flex justify-content-between mt-3">
          <a href="orders.php" class="btn btn-secondary">Cancel</a>
          <button type="submit" class="btn btn-primary">Update Order</button>
        </div>
      </form>
      <div id="updateMessage" class="alert mt-3"></div>
    </div>
  </div>
</div>

<script>
function toggleSidebar() {
    document.getElementById("sidebar").classList.toggle("show");
}

document.getElementById('editForm').addEventListener('submit', function(e){
    e.preventDefault();
    const form = e.target;
    const formData = {
        id: form.id.value,
        name: form.name.value,
        phone: form.phone.value,
        address: form.address.value,
        noti_status: form.noti_status.value,
        promo_code: form.promo_code.value,
        pickup_time: form.pickup_time.value,
        delivery_time: form.delivery_time.value
    };
    fetch('update_order.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify(formData)
    })
    .then(res=>res.json())
    .then(data=>{
        const msg = document.getElementById('updateMessage');
        msg.style.display='block';
        msg.classList.remove('alert-success','alert-danger');
        if(data.status==='success'){
            msg.classList.add('alert-success');
            msg.innerHTML = '✅ ' + data.message;
        }else{
            msg.classList.add('alert-danger');
            msg.innerHTML = '❌ ' + data.message;
        }
        setTimeout(()=>{ msg.style.display='none'; }, 3000);
    }).catch(()=>{
        const msg = document.getElementById('updateMessage');
        msg.style.display='block';
        msg.classList.remove('alert-success');
        msg.classList.add('alert-danger');
        msg.innerHTML='⚠️ Failed to send request';
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
