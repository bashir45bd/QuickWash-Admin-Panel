<?php

// Database configuration
$host = "localhost";
$username = "root";
$password = "";
$database = "Laundry";

// Get POST data
$name = $_POST['name'];
$mail = $_POST['mail'];
$image = $_POST['image'];
$id = $_POST['id'];
$key = $_POST['key'];
$phone = $_POST['phone'];
$address = $_POST['address'];

$f_key = decryptData($key);
$f_mail = decryptData($mail);

if ($f_key == '2021') {

    $conn = new mysqli($host, $username, $password, $database);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    if (!filter_var($f_mail, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format");
    }

    // Check if the user exists
    $checker = "SELECT * FROM users WHERE id = '$id'";
    $result6 = mysqli_query($conn, $checker);
    $rowcount = mysqli_num_rows($result6);

    if ($rowcount > 0) {
        $row = mysqli_fetch_assoc($result6);
        $oldImage = $row['image']; // Get the previous image path

        if (!empty($image)) { // If a new image is provided
            $decodedImage = base64_decode($image);
            $fileName = time() . '_' . rand(1000, 100000) . '.jpg';
            $filePath = 'uploads/' . $fileName;

            if (file_put_contents($filePath, $decodedImage)) {
                // Delete previous image if exists (not the default image)
                if (!empty($oldImage) && file_exists($oldImage)) {
                    unlink($oldImage);
                }
            } else {
                die("Image Upload Failed");
            }
        } else {
            $filePath = $oldImage; // Keep the old image if no new image is provided
        }

        // Update user data (added phone & address)
        $sql = "UPDATE `users` SET 
                    `name` = '$name',
                    `email` = '$f_mail',
                    `phone` = '$phone',
                    `address` = '$address',
                    `image` = '$filePath'
                WHERE `id` = '$id'";

        $result = mysqli_query($conn, $sql);

        if ($result) {
            echo "Update Successful";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    } else {
        echo "User Not Found";
    }

    $conn->close();
}

// ======================= Helper Functions =====================================

function encryptData($text) {
    $encrypted = openssl_encrypt($text, 'AES-128-ECB', 'aB3$dEfGh1JkLmNo', OPENSSL_RAW_DATA);
    return base64_encode($encrypted);
}

function decryptData($text) {
    $decoded = base64_decode($text);
    return openssl_decrypt($decoded, 'AES-128-ECB', 'aB3$dEfGh1JkLmNo', OPENSSL_RAW_DATA);
}

?>
