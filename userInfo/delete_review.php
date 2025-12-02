<?php
include 'db.php';

$review_id = $_POST['review_id'];

if (!$review_id) {
    echo json_encode(["success" => false, "message" => "Review ID missing"]);
    exit;
}

$delete = mysqli_query($conn, "DELETE FROM reviews WHERE id='$review_id'");

if ($delete) {
    echo json_encode(["success" => true, "message" => "Review deleted"]);
} else {
    echo json_encode(["success" => false, "message" => "Delete failed"]);
}
?>
