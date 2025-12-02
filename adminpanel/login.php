<?php session_start(); 
 ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <title>Admin Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="d-flex justify-content-center align-items-center vh-100 bg-light">
  <form method="post" action="includes/auth.php" class="bg-white p-4 rounded shadow" style="width: 300px;">
    <h4 class="text-center mb-3">Admin Login</h4>
    <input type="text" name="username" class="form-control mb-2" placeholder="Username" required>
    <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>
    <button type="submit" class="btn btn-primary w-100">Login</button>
  </form>
</body>
</html>
