<?php
session_start();
require_once __DIR__ . '/../config.php';
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$pdo = getPDO();
$stmt = $pdo->prepare('SELECT username FROM admins WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="fa">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>داشبورد</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body{direction:rtl}</style>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand bg-white shadow-sm mb-4">
    <div class="container">
        <a class="navbar-brand">پنل مدیریت</a>
        <div class="ms-auto">
            <span class="me-3"><?=htmlspecialchars($admin['username'])?></span>
            <a href="logout.php" class="btn btn-outline-secondary btn-sm">خروج</a>
        </div>
    </div>
</nav>
<div class="container">
    <div class="row g-3">
        <div class="col-md-4">
            <div class="card p-3">
                <h5>مدیریت رفتگرها</h5>
                <p><a href="workers-new.php" class="btn btn-primary btn-sm">باز کردن</a></p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3">
                <h5>مدیریت مسیرها</h5>
                <p><a href="paths-new.php" class="btn btn-primary btn-sm">باز کردن</a></p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3">
                <h5>صفحه دوربین</h5>
                <p><a href="camera-new.php" class="btn btn-primary btn-sm">باز کردن</a></p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card p-3 border-warning">
                <h5>سیستم تشخیص کارگران</h5>
                <p class="text-muted small">تشخیص هوشمند با دوربین و تحلیل حرکات</p>
                <p><a href="../index.php" class="btn btn-warning btn-sm">ورود به سیستم</a></p>
            </div>
        </div>
    </div>
</div>
</body>
</html>