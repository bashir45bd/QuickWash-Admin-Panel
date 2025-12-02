<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

// Fetch orders with email, service name, payment details
$orders = $conn->query("
    SELECT o.id, o.user_id, o.name, o.phone, o.email, o.address,
           s.name AS service_name, o.amount, o.noti_status, 
           o.created_at, o.promo_code, o.pickup_time, o.delivery_time,
           o.payment_method, o.payment_transaction_id, o.status
    FROM orders o
    LEFT JOIN services s ON o.service_id = s.id
    ORDER BY o.created_at DESC
");

// Fetch distinct payment statuses from orders table dynamically
$paymentStatuses = [];
$paymentResult = $conn->query("SELECT DISTINCT status FROM orders ORDER BY status ASC");
if ($paymentResult->num_rows > 0) {
    while ($row = $paymentResult->fetch_assoc()) {
        if (!empty($row['status'])) {
            $paymentStatuses[] = $row['status'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Orders - QuickWash Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<style>
/* existing styles unchanged */
:root {
  --primary-color: #ff8383;
  --secondary-color: #f1f1f1;
  --bg-color: #f8f9fa;
}
body { font-family: 'Segoe UI', sans-serif; background: var(--bg-color); margin: 0; }
.sidebar { background: var(--primary-color); color: #fff; min-height: 100vh; width: 240px; position: fixed; top: 0; left: 0; padding-top: 60px; transition: transform 0.3s ease; }
.sidebar a { color: #fff; display: block; padding: 14px 20px; text-decoration: none; font-weight: 500; border-radius: 8px; margin: 5px 10px; }
.sidebar a:hover, .sidebar a.active { background: rgba(255,255,255,0.25); }
.topbar { height: 60px; background: var(--primary-color); padding: 0 20px; display: flex; align-items: center; justify-content: space-between; color: #fff; position: fixed; top: 0; left: 0; right: 0; z-index: 1000; }
.topbar .menu-btn { display: none; font-size: 1.5rem; cursor: pointer; }
.content { margin-left: 240px; padding: 80px 20px 20px; transition: margin-left 0.3s ease; }
.card { border: none; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); margin-bottom: 20px; }
.card-header { background: var(--primary-color); color: #fff; font-weight: 600; font-size: 1.1rem; padding: 15px 20px; border-radius: 15px 15px 0 0; display: flex; flex-direction: column; }
.filter-group { margin-top: 10px; display: flex; flex-wrap: wrap; gap: 10px; }
.filter-group input, .filter-group select { flex: 1; min-width: 150px; max-width: 500px; padding: 8px 12px; border-radius: 10px; font-size: 0.95rem; }
.table thead { background: var(--secondary-color); }
.table th, .table td { vertical-align: middle; font-size: 0.9rem; }
.table tr:hover { background: rgba(255,131,131,0.08); transition: 0.2s; }
.badge-status { font-size: 0.85rem; font-weight: 500; padding: 5px 8px; border-radius: 10px; }
.action-btns .btn { padding: 4px 7px; border-radius: 6px; transition: 0.3s; }
.action-btns .btn:hover { transform: scale(1.1); }

.status-pending { background: #ffc107; color: #212529; }
.status-pickedup { background: #0dcaf0; color: #212529; }
.status-washed { background: #0d6efd; color: #fff; }
.status-delivered { background: #198754; color: #fff; }
.status-default { background: #6c757d; color: #fff; }

.payment-paid { background: #198754; color: #fff; }
.payment-pending { background: #ffc107; color: #212529; }
.payment-failed { background: #dc3545; color: #fff; }
.payment-canceled { background: #6c757d; color: #fff; }
.payment-cod { background: #0dcaf0; color: #212529; }

.item-table { margin-top: 10px; border: 1px solid #eee; border-radius: 8px; }
.item-table th, .item-table td { font-size: 0.85rem; padding: 6px 10px; }
.item-table th { background: #f8f9fa; }

@media (max-width: 768px) {
  .sidebar { transform: translateX(-100%); z-index: 1200; }
  .sidebar.active { transform: translateX(0); }
  .topbar .menu-btn { display: block; }
  .content { margin-left: 0; padding: 80px 10px 20px; }
}
</style>
</head>
<body>

<div class="sidebar" id="sidebar">
  <a href="index.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
  <a href="orders.php" class="active"><i class="bi bi-bag-check me-2"></i> Orders</a>
</div>

<div class="topbar">
  <span class="menu-btn" onclick="toggleSidebar()"><i class="bi bi-list"></i></span>
  <h6 class="m-0">QuickWash Admin - Orders</h6>
  <a href="logout.php" class="btn btn-sm btn-light"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
</div>

<div class="content">
  <div class="card">
    <div class="card-header">
      <span>All Orders</span>
      <div class="filter-group">
        <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Search by name, phone, address, or email">
        <select id="statusFilter" class="form-select form-select-sm">
          <option value="">All Order Status</option>
          <option value="pending">Pending</option>
          <option value="picked up">Picked Up</option>
          <option value="washed">Washed</option>
          <option value="delivered">Delivered</option>
        </select>
        <select id="paymentFilter" class="form-select form-select-sm">
          <option value="">All Payment Status</option>
          <?php foreach($paymentStatuses as $statusOption): ?>
            <option value="<?= strtolower($statusOption) ?>"><?= htmlspecialchars($statusOption) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="card-body p-0 table-responsive">
      <table class="table table-hover align-middle" id="ordersTable">
        <thead>
          <tr>
            <th>ID</th><th>User ID</th><th>Name</th><th>Phone</th><th>Email</th><th>Address</th>
            <th>Service</th><th>Total</th><th>Order Status</th><th>Payment Status</th>
            <th>Promo</th><th>Pickup</th><th>Delivery</th><th>Payment Method</th><th>Txn ID</th><th>Created</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($orders->num_rows > 0): ?>
            <?php while ($row = $orders->fetch_assoc()): ?>
              <?php
                $statusClass = match(strtolower($row['noti_status'])) {
                  'pending' => 'status-pending',
                  'picked up' => 'status-pickedup',
                  'washed' => 'status-washed',
                  'delivered' => 'status-delivered',
                  default => 'status-default'
                };

                $payStatusLower = strtolower($row['status']);
                $payClass = match($payStatusLower) {
                  'paid' => 'payment-paid',
                  'pending' => 'payment-pending',
                  'failed' => 'payment-failed',
                  'canceled' => 'payment-canceled',
                  default => ($row['payment_method'] === 'Cash On Delivery' ? 'payment-cod' : 'payment-canceled')
                };

                // 🔹 Fetch ordered items for this order
                $items = $conn->query("SELECT item_name, item_price, quantity FROM order_items WHERE order_id = {$row['id']}");
              ?>
              <tr>
                <td><?= $row['id'] ?></td>
                <td><?= $row['user_id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['phone']) ?></td>
                <td><?= htmlspecialchars($row['email'] ?: '-') ?></td>
                <td><?= htmlspecialchars($row['address']) ?></td>
                <td><?= htmlspecialchars($row['service_name'] ?: '-') ?></td>
                <td>৳<?= $row['amount'] ?></td>
                <td><span class="badge badge-status <?= $statusClass ?>"><?= htmlspecialchars($row['noti_status']) ?></span></td>
                <td><span class="badge badge-status <?= $payClass ?>"><?= htmlspecialchars($row['status'] ?: ($row['payment_method'] === 'Cash On Delivery' ? 'Cash On Delivery' : '-')) ?></span></td>
                <td><?= htmlspecialchars($row['promo_code']) ?></td>
                <td><?= htmlspecialchars($row['pickup_time']) ?></td>
                <td><?= htmlspecialchars($row['delivery_time']) ?></td>
                <td><?= htmlspecialchars($row['payment_method'] ?: '-') ?></td>
                <td><?= htmlspecialchars($row['payment_transaction_id'] ?: '-') ?></td>
                <td><?= $row['created_at'] ?></td>
                <td class="action-btns">
                  <button class="btn btn-info btn-sm" data-bs-toggle="collapse" data-bs-target="#items<?= $row['id'] ?>"><i class="bi bi-list-ul"></i></button>
                  <a href="edit_order.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil-square"></i></a>
                  <a href="delete_order.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure?')"><i class="bi bi-trash-fill"></i></a>
                </td>
              </tr>

              <!-- 🔽 Collapsible ordered items -->
              <tr class="collapse bg-light" id="items<?= $row['id'] ?>">
                <td colspan="17">
                  <table class="table table-sm item-table">
                    <thead>
                      <tr><th>Item Name</th><th>Price</th><th>Quantity</th><th>Subtotal</th></tr>
                    </thead>
                    <tbody>
                      <?php if ($items->num_rows > 0): ?>
                        <?php while ($it = $items->fetch_assoc()): ?>
                          <tr>
                            <td><?= htmlspecialchars($it['item_name']) ?></td>
                            <td>৳<?= $it['item_price'] ?></td>
                            <td><?= $it['quantity'] ?></td>
                            <td>৳<?= $it['item_price'] * $it['quantity'] ?></td>
                          </tr>
                        <?php endwhile; ?>
                      <?php else: ?>
                        <tr><td colspan="4" class="text-center text-muted">No items found for this order</td></tr>
                      <?php endif; ?>
                    </tbody>
                  </table>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="17" class="text-center py-3">No orders found</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
function toggleSidebar() { document.getElementById('sidebar').classList.toggle('active'); }

const searchInput = document.getElementById('searchInput');
const statusFilter = document.getElementById('statusFilter');
const paymentFilter = document.getElementById('paymentFilter');
const table = document.getElementById('ordersTable').getElementsByTagName('tbody')[0];

searchInput.addEventListener('keyup', filterTable);
statusFilter.addEventListener('change', filterTable);
paymentFilter.addEventListener('change', filterTable);

function filterTable() {
  const searchValue = searchInput.value.toLowerCase().trim();
  const statusValue = statusFilter.value.toLowerCase().trim();
  const paymentValue = paymentFilter.value.toLowerCase().trim();

  Array.from(table.rows).forEach(row => {
    if (row.classList.contains('collapse')) return; // skip collapsible rows
    const name = row.cells[2].textContent.toLowerCase().trim();
    const phone = row.cells[3].textContent.toLowerCase().trim();
    const email = row.cells[4].textContent.toLowerCase().trim();
    const address = row.cells[5].textContent.toLowerCase().trim();
    const orderStatus = row.cells[8].textContent.toLowerCase().trim();
    const payStatus = row.cells[9].textContent.toLowerCase().trim();
    const paymentMethod = row.cells[13].textContent.toLowerCase().trim();

    const matchesSearch = name.includes(searchValue) || phone.includes(searchValue) || email.includes(searchValue) || address.includes(searchValue);
    const matchesStatus = !statusValue || orderStatus === statusValue;

    let matchesPayment = true;
    if(paymentValue) {
      if(paymentValue === 'Cash On Delivery') {
        matchesPayment = paymentMethod === 'Cash On Delivery';
      } else {
        matchesPayment = payStatus === paymentValue;
      }
    }

    row.style.display = (matchesSearch && matchesStatus && matchesPayment) ? '' : 'none';
  });
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
