<?php
include "db.php";
session_start();

$id = $_POST['id'];
$conn->query("DELETE FROM promo_codes WHERE id = $id");

$_SESSION['success'] = "Promo code deleted.";
header("Location: promos.php");
exit();
