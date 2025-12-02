<?php
include 'db.php';

$order_id = $_GET['order_id'];
$user_id = $_GET['user_id'];

$result = mysqli_query($conn, "SELECT * FROM reviews WHERE order_id='$order_id' AND user_id='$user_id' LIMIT 1");

if ($row = mysqli_fetch_assoc($result)) {
    echo json_encode([
        "success" => true,
        "review_id" => $row['id'],
        "rating" => $row['rating'],
        "comment" => $row['comment']
    ]);
} else {
    echo json_encode(["success" => false, "message" => "No review found"]);
}
?>
