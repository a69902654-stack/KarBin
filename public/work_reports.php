<?php
session_start();
require_once __DIR__ . '/../config.php';
if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$pdo = getPDO();

$logs = [];
$stats = ['total_sessions' => 0, 'total_minutes' => 0, 'avg_minutes' => 0];
$dbError = null;

try {
    // Get work logs with worker names
    $logs = $pdo->query('
        SELECT wl.*, w.name as worker_name 
        FROM work_logs wl 
        JOIN workers w ON wl.worker_id = w.id 
        ORDER BY wl.start_time DESC 
        LIMIT 50
    ')->fetchAll(PDO::FETCH_ASSOC);

    // Get summary stats
    $stats = $pdo->query('
        SELECT 
            COUNT(*) as total_sessions,
            SUM(duration_minutes) as total_minutes,
            AVG(duration_minutes) as avg_minutes
        FROM work_logs 
        WHERE status = "completed"
    ')->fetch(PDO::FETCH_ASSOC);
    if (!$stats) {
        $stats = ['total_sessions' => 0, 'total_minutes' => 0, 'avg_minutes' => 0];
    }
} catch (PDOException $e) {
    $dbError = $e->getMessage();
}
?>
<!doctype html>
<html lang="fa">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>گزارش زمان کار</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body{direction:rtl}</style>
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>گزارش زمان کار کارگران</h2>
        <a href="dashboard-new.php" class="btn btn-secondary">بازگشت</a>
    </div>

    <?php if ($dbError): ?>
    <div class="alert alert-danger">
        خطا در دسترسی به جدول <code>work_logs</code>: <?=htmlspecialchars($dbError)?>
        <br><small>فایل <code>add_work_logs.sql</code> را روی دیتابیس <code>kar_db</code> در phpMyAdmin اجرا کنید.</small>
    </div>
    <?php endif; ?>

    <!-- Summary Stats -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title"><?=$stats['total_sessions']?></h5>
                    <p class="card-text">کل جلسات کاری</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title"><?=round($stats['total_minutes']/60, 1)?> ساعت</h5>
                    <p class="card-text">کل زمان کار</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h5 class="card-title"><?=round($stats['avg_minutes'], 1)?> دقیقه</h5>
                    <p class="card-text">میانگین زمان کار</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Work Logs Table -->
    <div class="card">
        <div class="card-header">
            <h5>جزئیات زمان کار</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>کارگر</th>
                            <th>شروع</th>
                            <th>پایان</th>
                            <th>مدت زمان</th>
                            <th>وضعیت</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($logs as $log): ?>
                        <tr>
                            <td><?=htmlspecialchars($log['worker_name'])?></td>
                            <td><?=date('Y/m/d H:i', strtotime($log['start_time']))?></td>
                            <td>
                                <?php if($log['end_time']): ?>
                                    <?=date('Y/m/d H:i', strtotime($log['end_time']))?>
                                <?php else: ?>
                                    <span class="badge bg-warning">در حال کار</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($log['duration_minutes']): ?>
                                    <?=floor($log['duration_minutes']/60)?>:<?=sprintf('%02d', $log['duration_minutes']%60)?> ساعت
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($log['status'] === 'active'): ?>
                                    <span class="badge bg-success">فعال</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">تمام شده</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html>