<?php
session_start();
require_once __DIR__ . '/../config.php';
if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
$pdo = getPDO();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $start_lat = floatval($_POST['start_lat'] ?? 0);
    $start_lng = floatval($_POST['start_lng'] ?? 0);
    $end_lat = floatval($_POST['end_lat'] ?? 0);
    $end_lng = floatval($_POST['end_lng'] ?? 0);
    if ($name) {
        $stmt = $pdo->prepare('INSERT INTO paths (name, start_lat, start_lng, end_lat, end_lng, description) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$name, $start_lat, $start_lng, $end_lat, $end_lng, '']);
        header('Location: paths.php'); exit;
    }
}
$paths = $pdo->query('SELECT * FROM paths ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="fa">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>مسیرها</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <style>body{direction:rtl}</style>
</head>
<body class="bg-light">
<div class="container py-4">
    <a href="dashboard-new.php" class="btn btn-link">« بازگشت</a>
    <h4>مسیرها - انتخاب از نقشه</h4>
    <div class="card mb-3 p-3">
        <div id="map" style="height: 400px; margin-bottom: 15px;"></div>
        <form method="post" id="pathForm">
            <div class="row g-2">
                <div class="col-md-4"><input name="name" class="form-control" placeholder="نام مسیر" required></div>
                <div class="col-md-2"><input name="start_lat" class="form-control" placeholder="عرض جغرافیایی شروع" readonly></div>
                <div class="col-md-2"><input name="start_lng" class="form-control" placeholder="طول جغرافیایی شروع" readonly></div>
                <div class="col-md-2"><input name="end_lat" class="form-control" placeholder="عرض جغرافیایی پایان" readonly></div>
                <div class="col-md-2"><input name="end_lng" class="form-control" placeholder="طول جغرافیایی پایان" readonly></div>
            </div>
            <div class="mt-2">
                <button type="button" class="btn btn-info" onclick="selectArea()">انتخاب منطقه</button>
                <button type="submit" class="btn btn-success">افزودن مسیر</button>
            </div>
        </form>
    </div>

    <div class="card p-3">
        <table class="table table-striped">
            <thead><tr><th>#</th><th>نام</th><th>شروع (عرض)</th><th>شروع (طول)</th><th>پایان (عرض)</th><th>پایان (طول)</th></tr></thead>
            <tbody>
            <?php foreach($paths as $p): ?>
                <tr>
                    <td><?=$p['id']?></td>
                    <td><?=htmlspecialchars($p['name'])?></td>
                    <td><?=$p['start_lat']?></td>
                    <td><?=$p['start_lng']?></td>
                    <td><?=$p['end_lat']?></td>
                    <td><?=$p['end_lng']?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="alert alert-info">نکته: برای انتخاب مسیر، روی دکمه "انتخاب منطقه" کلیک کنید و دو نقطه شروع و پایان را روی نقشه انتخاب کنید.</div>
</div>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
let map, startMarker, endMarker, selecting = false, step = 0;
map = L.map('map').setView([35.6892, 51.3890], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
function selectArea() {
    selecting = true; step = 0;
    if(startMarker) map.removeLayer(startMarker);
    if(endMarker) map.removeLayer(endMarker);
    alert('ابتدا نقطه شروع را انتخاب کنید');
}
map.on('click', function(e) {
    if(!selecting) return;
    if(step === 0) {
        startMarker = L.marker([e.latlng.lat, e.latlng.lng]).addTo(map);
        document.querySelector('input[name="start_lat"]').value = e.latlng.lat.toFixed(6);
        document.querySelector('input[name="start_lng"]').value = e.latlng.lng.toFixed(6);
        step = 1;
        alert('حالا نقطه پایان را انتخاب کنید');
    } else {
        endMarker = L.marker([e.latlng.lat, e.latlng.lng]).addTo(map);
        document.querySelector('input[name="end_lat"]').value = e.latlng.lat.toFixed(6);
        document.querySelector('input[name="end_lng"]').value = e.latlng.lng.toFixed(6);
        selecting = false;
        L.polyline([startMarker.getLatLng(), endMarker.getLatLng()], {color: 'red'}).addTo(map);
    }
});
</script>
</body>
</html>