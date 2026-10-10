<?php
session_start();
require_once __DIR__ . '/../config.php';
$err = '';
$adminExists = false;
try {
    $adminExists = (bool) getPDO()->query('SELECT 1 FROM admins LIMIT 1')->fetch();
} catch (Exception $e) {
    $adminExists = false;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $pdo = getPDO();
    $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = ? LIMIT 1');
    $stmt->execute([$username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['user_id'] = $admin['id'];
        header('Location: dashboard-new.php');
        exit;
    } else {
        $err = 'نام کاربری یا رمز عبور صحیح نیست.';
    }
}
?>
<!doctype html>
<html lang="fa">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>ورود - پنل مدیریت</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body{direction:rtl}</style>
</head>
<body class="bg-light">
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">ورود ادمین</h5>
                    <?php if ($err): ?>
                        <div class="alert alert-danger"><?=htmlspecialchars($err)?></div>
                    <?php endif; ?>
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">نام کاربری</label>
                            <input name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">رمز عبور</label>
                            <input name="password" type="password" class="form-control" required>
                        </div>
                        <button class="btn btn-primary w-100">ورود</button>
                    </form>
                    <?php if (!$adminExists): ?>
                    <hr>
                    <small class="text-muted">
                        هنوز ادمینی در دیتابیس ساخته نشده است. یک رمز انتخاب کنید، هش آن را بسازید و سپس اجرا کنید:
                        <pre class="text-end" dir="ltr" style="white-space:pre-wrap;font-size:11px">php -r "echo password_hash('YOUR_PASSWORD', PASSWORD_DEFAULT);"
INSERT INTO admins (username, password_hash) VALUES ('admin', 'GENERATED_HASH');</pre>
                    </small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>