<?php
session_start();
require_once __DIR__ . '/../config.php';
if (empty($_SESSION['admin_id'])) { header('Location: login.php'); exit; }
$pdo = getPDO();
// handle add
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $details = trim($_POST['details'] ?? '');
    $path_id = intval($_POST['path_id'] ?? 0) ?: null;
    $imageName = null;
    if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploaddir = __DIR__ . '/../uploads';
        if (!is_dir($uploaddir)) mkdir($uploaddir, 0755, true);
        $tmp = $_FILES['image']['tmp_name'];
        $orig = basename($_FILES['image']['name']);
        $ext = pathinfo($orig, PATHINFO_EXTENSION);
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (in_array(strtolower($ext), $allowed)) {
            $imageName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            move_uploaded_file($tmp, $uploaddir . '/' . $imageName);
        }
    }
    if ($name) {
        $stmt = $pdo->prepare('INSERT INTO workers (name, details, path_id, image, created_at) VALUES (?, ?, ?, ?, NOW())');
        $stmt->execute([$name, $details, $path_id, $imageName]);
        header('Location: workers.php'); exit;
    }
}
$workers = $pdo->query('SELECT w.*, p.name AS path_name FROM workers w LEFT JOIN paths p ON w.path_id = p.id ORDER BY w.id DESC')->fetchAll(PDO::FETCH_ASSOC);
$paths = $pdo->query('SELECT id, name FROM paths ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="fa">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>رفتگرها</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body{direction:rtl}</style>
</head>
<body class="bg-light">
<div class="container py-4">
    <a href="dashboard-new.php" class="btn btn-link">« بازگشت</a>
    <h4>رفتگرها</h4>
    <div class="card mb-3 p-3">
        <form method="post" enctype="multipart/form-data">
            <div class="row g-2">
                <div class="col-md-3"><input name="name" class="form-control" placeholder="نام" required></div>
                <div class="col-md-3"><input name="details" class="form-control" placeholder="مشخصات"></div>
                <div class="col-md-2">
                    <select name="path_id" class="form-select">
                        <option value="">مسیر</option>
                        <?php foreach($paths as $p): ?>
                            <option value="<?=$p['id']?>"><?=htmlspecialchars($p['name'])?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2"><input type="file" name="image" accept="image/*" class="form-control"></div>
                <div class="col-md-2"><button class="btn btn-success w-100">افزودن</button></div>
            </div>
        </form>
    </div>

    <div class="card p-3">
        <table class="table table-striped">
            <thead><tr><th>#</th><th>تصویر</th><th>نام</th><th>مشخصات</th><th>مسیر</th></tr></thead>
            <tbody>
            <?php foreach($workers as $w): ?>
                <tr>
                    <td><?= $w['id'] ?></td>
                    <td><?php if($w['image']): ?><img src="../uploads/<?=htmlspecialchars($w['image'])?>" style="height:48px;border-radius:4px"><?php endif; ?></td>
                    <td><?= htmlspecialchars($w['name']) ?></td>
                    <td><?= htmlspecialchars($w['details']) ?></td>
                    <td><?= htmlspecialchars($w['path_name']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>