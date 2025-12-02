// update_review.php
<?php
include 'db.php';

$review_id = $_POST['review_id'];
$rating = $_POST['rating'];
$comment = $_POST['comment'];

$update = mysqli_query($conn, "UPDATE reviews SET rating='$rating', comment='$comment', updated_at=NOW() WHERE id='$review_id'");

if ($update) {
    echo json_encode(["success" => true, "message" => "Review updated"]);
} else {
    echo json_encode(["success" => false, "message" => "Update failed"]);
}
?>