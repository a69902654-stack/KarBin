<?php
require_once __DIR__ . '/../config.php';
$pdo = getPDO();
$workers = $pdo->query('SELECT w.*, p.name AS path_name FROM workers w LEFT JOIN paths p ON w.path_id = p.id ORDER BY w.id DESC')->fetchAll(PDO::FETCH_ASSOC);
$paths_count = $pdo->query('SELECT COUNT(*) FROM paths')->fetchColumn();
$workers_count = count($workers);
?>
<!doctype html>
<html lang="fa">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>سیستم تشخیص کارگران</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body{direction:rtl; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;}
        .worker-card{cursor:pointer; transition: all 0.3s;}
        .worker-card:hover{transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.1);}
        .system-info{background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;}
        .worker-image{width: 60px; height: 60px; object-fit: cover; border-radius: 50%;}
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="text-center mb-4">
        <h2>سیستم تشخیص کارگران</h2>
        <p class="text-muted">مدیریت هوشمند نیروی کار با تکنولوژی تشخیص حرکت</p>
    </div>

    <!-- System Information -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card system-info p-4 text-center">
                <h4>اطلاعات سیستم</h4>
                <div class="row mt-3">
                    <div class="col-6">
                        <h3><?=$workers_count?></h3>
                        <small>تعداد کارگران</small>
                    </div>
                    <div class="col-6">
                        <h3><?=$paths_count?></h3>
                        <small>تعداد مسیرها</small>
                    </div>
                </div>
                <div class="mt-3">
                    <a href="login.php" class="btn btn-light">ورود به پنل مدیریت</a>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card p-4">
                <h5>دسترسی سریع</h5>
                <div class="d-grid gap-2">
                    <a href="camera-new.php" class="btn btn-primary">مشاهده دوربین</a>
                    <a href="face_recognition.php" class="btn btn-success">تشخیص چهره</a>
                    <a href="dashboard-new.php" class="btn btn-outline-primary">داشبورد مدیریت</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Workers Section -->
    <div class="card p-4">
        <h5 class="mb-3">کارگران</h5>
        <div class="row g-3">
            <?php foreach($workers as $worker): ?>
                <div class="col-md-4">
                    <div class="card worker-card p-3" onclick="alert('نام: <?=htmlspecialchars($worker['name'])?> - مسیر: <?=htmlspecialchars($worker['path_name'] ?? 'بدون مسیر')?> - جزئیات: <?=htmlspecialchars($worker['details'] ?? 'اطلاعات موجود نیست')?>')">
                        <div class="d-flex align-items-center">
                            <?php if($worker['image']): ?>
                                <img src="../uploads/<?=basename($worker['image'])?>" class="worker-image me-3" alt="تصویر کارگر">
                            <?php else: ?>
                                <div class="worker-image me-3 bg-secondary d-flex align-items-center justify-content-center text-white">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php endif; ?>
                            <div>
                                <h6 class="mb-1"><?=htmlspecialchars($worker['name'])?></h6>
                                <small class="text-muted"><?=htmlspecialchars($worker['path_name'] ?? 'بدون مسیر')?></small>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>