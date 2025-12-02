<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}
include "db.php";

// Delete review
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM reviews WHERE id=$id");
    $_SESSION['success'] = "Review deleted successfully.";
    header("Location: viewReviews.php");
    exit();
}

// Update review
if (isset($_POST['update_review'])) {
    $id = (int)$_POST['review_id'];
    $rating = (int)$_POST['rating'];
    $comment = $conn->real_escape_string($_POST['comment']);
    $conn->query("UPDATE reviews SET rating=$rating, comment='$comment', updated_at=NOW() WHERE id=$id");
    $_SESSION['success'] = "Review updated successfully.";
    header("Location: viewReviews.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>View Reviews - QuickWash Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root {
    --primary-color: #ff8383;
    --secondary-color: #f1f1f1;
    --bg-color: #f8f9fa;
}
body { font-family: 'Segoe UI', sans-serif; background: var(--bg-color); margin:0; }

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
.topbar .menu-btn { display: none; font-size: 1.5rem; cursor: pointer; }

/* Content */
.content { margin-left: 240px; padding: 80px 20px 20px; transition: margin-left 0.3s ease; }

/* Review Cards */
.review-card {
    border-radius: 12px;
    background: #fff;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    padding: 20px;
    transition: all 0.3s ease;
    position: relative;
}
.review-card:hover { transform: scale(1.02); }
.user-image { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid #dee2e6; }

/* Action buttons */
.review-actions { position: absolute; top: 10px; right: 10px; }
.review-actions button { margin-left: 5px; }

/* Modal */
.modal-header { background: var(--primary-color); color:#fff; }
.modal-footer button { min-width: 80px; }

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
    <a href="viewReviews.php" class="active"><i class="bi bi-chat-dots-fill me-2"></i> Reviews</a>
</div>

<!-- Topbar -->
<div class="topbar">
    <span class="menu-btn" onclick="toggleSidebar()"><i class="bi bi-list"></i></span>
    <h6 class="m-0">QuickWash Admin - View Reviews</h6>
    <a href="logout.php" class="btn btn-sm btn-light"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
</div>

<div class="content">

    <div class="mb-4">
        <h3 class="fw-bold">User Reviews</h3>
        <small class="text-muted">All reviews submitted by users</small>
    </div>

    <!-- Messages -->
    <?php if(isset($_SESSION['success'])): ?>
        <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
    <?php endif; ?>

    <!-- Search -->
    <div class="mb-4">
        <input type="text" id="searchInput" class="form-control" placeholder="Search by user, order, or comment">
    </div>

    <!-- Review Cards -->
    <div class="row g-4" id="reviewCards">
        <?php
        $reviewsResult = $conn->query("
            SELECT r.*, u.name AS user_name, u.email AS user_email, u.image AS user_image
            FROM reviews r
            LEFT JOIN users u ON r.user_id = u.id
            ORDER BY r.created_at DESC
        ");
        if($reviewsResult->num_rows > 0):
            while($review = $reviewsResult->fetch_assoc()):
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="review-card">
                <div class="review-actions">
                    <button class="btn btn-sm btn-primary" onclick="openEditModal(<?= $review['id'] ?>,'<?= htmlspecialchars(addslashes($review['comment'])) ?>',<?= $review['rating'] ?>)">
                        <i class="bi bi-pencil-square"></i>
                    </button>
                    <a href="?delete=<?= $review['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this review?');">
                        <i class="bi bi-trash"></i>
                    </a>
                </div>
                <div class="d-flex align-items-center mb-2">
                    <img src="<?= !empty($review['user_image']) && file_exists($review['user_image']) ? $review['user_image'] : 'https://via.placeholder.com/50' ?>" class="user-image me-2">
                    <div>
                        <h6 class="mb-0"><?= htmlspecialchars($review['user_name'] ?: 'Unknown User') ?></h6>
                        <small class="text-muted"><?= htmlspecialchars($review['user_email'] ?: '-') ?></small>
                    </div>
                </div>
                <div class="mb-2">
                    <strong>Rating:</strong>
                    <?php for($i=1;$i<=5;$i++): ?>
                        <?php if($i <= $review['rating']): ?>
                            <i class="bi bi-star-fill text-warning"></i>
                        <?php else: ?>
                            <i class="bi bi-star text-warning"></i>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
                <div class="mb-2"><strong>Comment:</strong> <?= htmlspecialchars($review['comment']) ?></div>
                <div class="text-muted small">Order ID: <?= $review['order_id'] ?> | <?= $review['created_at'] ?></div>
            </div>
        </div>
        <?php
            endwhile;
        else:
            echo '<div class="text-center text-muted">No reviews found.</div>';
        endif;
        ?>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editReviewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" id="editReviewForm">
        <div class="modal-header">
          <h5 class="modal-title">Edit Review</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
            <input type="hidden" name="review_id" id="review_id">
            <div class="mb-3">
                <label>Rating (1-5)</label>
                <input type="number" name="rating" id="review_rating" class="form-control" min="1" max="5" required>
            </div>
            <div class="mb-3">
                <label>Comment</label>
                <textarea name="comment" id="review_comment" class="form-control" rows="3" required></textarea>
            </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="update_review" class="btn btn-primary">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSidebar() { document.getElementById('sidebar').classList.toggle('active'); }

function openEditModal(id, comment, rating){
    document.getElementById('review_id').value = id;
    document.getElementById('review_comment').value = comment;
    document.getElementById('review_rating').value = rating;
    new bootstrap.Modal(document.getElementById('editReviewModal')).show();
}

// Search filter
const searchInput = document.getElementById('searchInput');
searchInput.addEventListener('keyup', () => {
    const val = searchInput.value.toLowerCase();
    document.querySelectorAll('#reviewCards .col-md-6').forEach(card => {
        card.style.display = card.textContent.toLowerCase().includes(val) ? '' : 'none';
    });
});
</script>
</body>
</html>
