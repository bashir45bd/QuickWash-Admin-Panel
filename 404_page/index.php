<?php
// index.php - 404 Not Found Page
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Page Not Found - QuickWash</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<style>
:root { --primary-color: #ff8383; }

body {
    background: #f5f5f5;
    font-family: 'Segoe UI', sans-serif;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 0;
}

.container {
    text-align: center;
    padding: 30px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.1);
    max-width: 500px;
}

h1 {
    font-size: 8rem;
    font-weight: 700;
    color: var(--primary-color);
    margin: 0;
}

h2 {
    font-size: 2rem;
    margin: 10px 0;
    color: #333;
}

p {
    color: #666;
    margin-bottom: 0;
}

@media (max-width: 576px) {
    h1 { font-size: 5rem; }
    h2 { font-size: 1.5rem; }
}
</style>
</head>
<body>
<div class="container">
    <h1>404</h1>
    <h2>Oops! Page Not Found</h2>
    <p>The page you are looking for doesn't exist or has been moved.</p>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
