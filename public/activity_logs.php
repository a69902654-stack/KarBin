<?php
session_start();
require_once __DIR__ . '/../config.php';
if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }

$pdo = getPDO();
$logs = $pdo->query('
    SELECT al.*, w.name as worker_name 
    FROM activity_logs al 
    LEFT JOIN workers w ON al.worker_id = w.id 
    ORDER BY al.created_at DESC 
    LIMIT 200
')->fetchAll(PDO::FETCH_ASSOC);

// ستون metadata به صورت JSON ذخیره شده؛ مختصات را از آن استخراج می‌کنیم
function logCoordinates($log) {
    $meta = $log['metadata'] ?? null;
    if ($meta === null || $meta === '') return '-';
    $decoded = json_decode($meta, true);
    if (is_array($decoded)) {
        if (isset($decoded['coordinates'])) {
            return is_string($decoded['coordinates'])
                ? $decoded['coordinates']
                : json_encode($decoded['coordinates'], JSON_UNESCAPED_UNICODE);
        }
        return json_encode($decoded, JSON_UNESCAPED_UNICODE);
    }
    return (string) $meta;
}
?>
<!doctype html>
<html lang="fa">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>لاگ فعالیتها</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>body{direction:rtl}</style>
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>لاگ فعالیتهای دوربین</h2>
        <div>
            <button onclick="exportLogs()" class="btn btn-success me-2">خروجی Excel</button>
            <a href="dashboard-new.php" class="btn btn-secondary">بازگشت</a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="logsTable">
                    <thead>
                        <tr>
                            <th>زمان</th>
                            <th>نوع رویداد</th>
                            <th>توضیحات</th>
                            <th>کارگر</th>
                            <th>مختصات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($logs as $log): ?>
                        <tr>
                            <td><?=date('Y/m/d H:i:s', strtotime($log['created_at']))?></td>
                            <td><span class="badge bg-primary"><?=htmlspecialchars($log['activity_type'])?></span></td>
                            <td><?=htmlspecialchars($log['description'])?></td>
                            <td><?=htmlspecialchars($log['worker_name'] ?? '-')?></td>
                            <td><?=htmlspecialchars(logCoordinates($log))?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function exportLogs() {
    let csv = 'زمان,نوع رویداد,توضیحات,کارگر,مختصات\n';
    const table = document.getElementById('logsTable');
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const cols = row.querySelectorAll('td');
        const rowData = Array.from(cols).map(col => {
            return '"' + col.textContent.trim().replace(/"/g, '""') + '"';
        });
        csv += rowData.join(',') + '\n';
    });
    
    const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'activity_logs_' + new Date().toISOString().slice(0,10) + '.csv';
    link.click();
}
</script>
</body>
</html>