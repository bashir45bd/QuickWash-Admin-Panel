<?php
include 'db.php';

header('Content-Type: application/json');

$baseImageUrl = "http://192.168.0.106/Laundry/adminpanel/";

$query = "SELECT r.rating, r.comment, r.created_at, u.name AS user_name, u.image AS user_image
          FROM reviews r
          JOIN users u ON r.user_id = u.id
          ORDER BY r.created_at DESC";

$result = mysqli_query($conn, $query);

$reviews = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Use default image if user image is empty
        if (empty($row['user_image'])) {
            $row['user_image'] = $baseImageUrl . "default.png"; // <-- put default image filename here
        } else {
            $row['user_image'] = $baseImageUrl . $row['user_image'];
        }

        $reviews[] = $row;
    }

    if (count($reviews) > 0) {
        echo json_encode(["success" => true, "reviews" => $reviews]);
    } else {
        echo json_encode(["success" => false, "message" => "No reviews found"]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Database error"]);
}
?>
