<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/../config.php';
$pdo = getPDO();
$activities = $pdo->query("
    SELECT al.*, w.name as worker_name, p.name as path_name 
    FROM activity_logs al 
    LEFT JOIN workers w ON al.worker_id = w.id 
    LEFT JOIN paths p ON al.path_id = p.id 
    ORDER BY al.created_at DESC LIMIT 100
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>گزارش فعالیت‌ها</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900 text-white">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-3xl font-bold">گزارش فعالیت‌های تشخیص داده شده</h1>
            <a href="dashboard.php" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded">بازگشت به داشبورد</a>
        </div>

        <div class="bg-gray-800 rounded-lg overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-700">
                    <tr>
                        <th class="px-4 py-3 text-right">زمان</th>
                        <th class="px-4 py-3 text-right">کارگر</th>
                        <th class="px-4 py-3 text-right">فعالیت</th>
                        <th class="px-4 py-3 text-right">اطمینان</th>
                        <th class="px-4 py-3 text-right">مسیر</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($activities as $activity): ?>
                    <tr class="border-b border-gray-700 hover:bg-gray-750">
                        <td class="px-4 py-3"><?= date('H:i:s Y/m/d', strtotime($activity['created_at'])) ?></td>
                        <td class="px-4 py-3"><?= htmlspecialchars($activity['worker_name'] ?? 'نامشخص') ?></td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-1 rounded text-sm <?= getActivityColor($activity['activity_type']) ?>">
                                <?= htmlspecialchars($activity['activity_type']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3"><?= number_format($activity['confidence'], 1) ?>%</td>
                        <td class="px-4 py-3"><?= htmlspecialchars($activity['path_name'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Auto refresh every 30 seconds
        setTimeout(() => location.reload(), 30000);
    </script>
</body>
</html>

<?php
function getActivityColor($activity) {
    $colors = [
        'جارو زدن' => 'bg-orange-600',
        'استراحت' => 'bg-green-600',
        'راه رفتن' => 'bg-blue-600',
        'ایستادن' => 'bg-gray-600',
        'نشستن' => 'bg-purple-600',
        'کار با دست' => 'bg-yellow-600'
    ];
    return $colors[$activity] ?? 'bg-gray-500';
}
?>