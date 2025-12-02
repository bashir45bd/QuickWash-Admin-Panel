// submit_review.php
<?php
include 'db.php';

$order_id = $_POST['order_id'];
$user_id = $_POST['user_id'];
$rating = $_POST['rating'];
$comment = $_POST['comment'];

// Check if review exists
$check = mysqli_query($conn, "SELECT * FROM reviews WHERE order_id='$order_id' AND user_id='$user_id'");

if (mysqli_num_rows($check) > 0) {
    // Update review
    $update = mysqli_query($conn, "UPDATE reviews SET rating='$rating', comment='$comment', updated_at=NOW() WHERE order_id='$order_id' AND user_id='$user_id'");
    if ($update) {
        echo json_encode(["success" => true, "message" => "Review updated"]);
    } else {
        echo json_encode(["success" => false, "message" => "Update failed"]);
    }
} else {
    // Insert new review
    $insert = mysqli_query($conn, "INSERT INTO reviews (order_id, user_id, rating, comment, created_at) VALUES ('$order_id', '$user_id', '$rating', '$comment', NOW())");
    if ($insert) {
        echo json_encode(["success" => true, "message" => "Review submitted"]);
    } else {
        echo json_encode(["success" => false, "message" => "Submit failed"]);
    }
}
?>