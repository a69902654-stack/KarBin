<?php
// Public camera page - shows video and overlays
session_start();
require_once __DIR__ . '/../config.php';
if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
?>
<!doctype html>
<html lang="fa">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>دوربین - تشخیص رنگ نارنجی</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body{direction:rtl}
        #videoWrapper{position:relative; display:inline-block}
        #overlay {position:absolute; left:0; top:0}
        .infoBox{position:absolute; background:rgba(0,0,0,0.6); color:#fff; padding:6px; border-radius:4px; font-size:13px}
        .pathRect{position:absolute; border:2px dashed rgba(0,128,255,0.7); pointer-events:none}
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
    <a href="dashboard-new.php" class="btn btn-link">« بازگشت</a>
    <h4>پخش دوربین</h4>
    <div class="card p-3">
        <div id="videoWrapper">
            <video id="video" width="640" height="480" autoplay playsinline></video>
            <canvas id="overlay" width="640" height="480"></canvas>
        </div>
        <div class="mt-3">
            <button id="startBtn" class="btn btn-primary">شروع دوربین</button>
            <button id="stopBtn" class="btn btn-secondary">توقف</button>
        </div>
        <div class="mt-3">توضیح: سیستم رنگ نارنجی را شناسایی می‌کند؛ اگر centroid داخل ناحیهٔ تعریف‌شده باشد، اطلاعات مرتبط در کنار کادر نمایش داده می‌شود.</div>
    </div>
</div>
<script src="../assets/js/camera-map.js"></script>
</body>
</html>