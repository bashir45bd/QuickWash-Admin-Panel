<?php
$conn = new mysqli("localhost", "root", "", "Laundry");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
