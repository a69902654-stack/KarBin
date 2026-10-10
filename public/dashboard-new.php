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

// Get statistics
$workerCount = $pdo->query('SELECT COUNT(*) FROM workers')->fetchColumn();
$pathCount = $pdo->query('SELECT COUNT(*) FROM paths')->fetchColumn();
?>
<!DOCTYPE html>
<html class="dark" lang="fa" dir="rtl">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>پنل مدیریت سیستم تشخیص کارگران</title>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script id="tailwind-config">
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: {
                        "primary": "#f97316",
                        "background-light": "#f6f8f6",
                        "background-dark": "#111827",
                        "surface-dark": "#1f2937",
                        "surface-darker": "#0f172a",
                        "accent-orange": "#fb923c",
                        "alert-red": "#ef4444",
                    },
                    fontFamily: {
                        "display": ["Vazirmatn", "sans-serif"]
                    },
                    borderRadius: { "DEFAULT": "0.5rem", "lg": "1rem", "xl": "1.5rem", "full": "9999px" },
                },
            },
        }
    </script>
<style>
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: #111827; 
        }
        ::-webkit-scrollbar-thumb {
            background: #374151; 
            border-radius: 4px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #f97316; 
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-slate-900 dark:text-white overflow-hidden h-screen flex">
<!-- Sidebar -->
<aside class="w-64 h-full flex flex-col bg-surface-darker border-l border-gray-700 shrink-0 hidden md:flex">
<!-- Sidebar Header -->
<div class="p-6 pb-2">
<div class="flex items-center gap-3 mb-6">
<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10 border border-primary/30 shadow-[0_0_15px_rgba(249,115,22,0.2)]" style='background-image: url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 24 24%27 fill=%27%23f97316%27%3E%3Cpath d=%27M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z%27/%3E%3C/svg%3E");'></div>
<div class="flex flex-col">
<h1 class="text-white text-lg font-bold leading-normal tracking-tight">سیستم تشخیص</h1>
<p class="text-accent-orange text-xs font-medium leading-normal">نسخه 1.0.0</p>
</div>
</div>
</div>
<!-- Navigation -->
<div class="flex-1 overflow-y-auto px-4 flex flex-col gap-2">
<!-- Active Item -->
<a class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-gray-700 group transition-all duration-200 border border-primary/20 shadow-sm" href="#">
<span class="material-symbols-outlined text-primary">grid_view</span>
<p class="text-white text-sm font-bold leading-normal">داشبورد</p>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-400 hover:text-white hover:bg-gray-700/50 transition-colors duration-200" href="workers-new.php">
<span class="material-symbols-outlined">badge</span>
<p class="text-sm font-medium leading-normal">مدیریت کارگران</p>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-400 hover:text-white hover:bg-gray-700/50 transition-colors duration-200" href="paths-new.php">
<span class="material-symbols-outlined">map</span>
<p class="text-sm font-medium leading-normal">مدیریت مسیرها</p>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-400 hover:text-white hover:bg-gray-700/50 transition-colors duration-200" href="camera-new.php">
<span class="material-symbols-outlined">videocam</span>
<p class="text-sm font-medium leading-normal">دوربین ساده</p>
</a>
<div class="h-px bg-gray-700 my-2 mx-2"></div>
<a class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-400 hover:text-white hover:bg-gray-700/50 transition-colors duration-200" href="../index.php">
<div class="relative">
<span class="material-symbols-outlined">analytics</span>
<span class="absolute top-0 right-0 size-2 bg-primary rounded-full animate-pulse"></span>
</div>
<p class="text-sm font-medium leading-normal">سیستم هوشمند</p>
</a>
</div>
<!-- Logout Section -->
<div class="p-4 border-t border-gray-700">
<a href="logout.php" class="flex w-full cursor-pointer items-center justify-center gap-2 overflow-hidden rounded-xl h-10 px-4 bg-primary hover:bg-orange-600 text-white transition-colors duration-200">
<span class="material-symbols-outlined text-[20px] font-bold">logout</span>
<span class="text-sm font-bold leading-normal tracking-[0.015em] truncate">خروج</span>
</a>
</div>
</aside>
<!-- Main Content -->
<main class="flex-1 flex flex-col h-full overflow-hidden relative">
<!-- Top Bar -->
<header class="h-16 flex items-center justify-between px-6 border-b border-gray-700 md:hidden bg-surface-darker shrink-0">
<div class="flex items-center gap-2">
<div class="bg-center bg-no-repeat bg-cover rounded-full size-8" style='background-image: url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 24 24%27 fill=%27%23f97316%27%3E%3Cpath d=%27M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z%27/%3E%3C/svg%3E");'></div>
<span class="text-white font-bold text-lg">سیستم تشخیص</span>
</div>
<button class="text-white">
<span class="material-symbols-outlined">menu</span>
</button>
</header>
<div class="flex-1 overflow-y-auto p-4 md:p-8">
<div class="max-w-[1440px] mx-auto flex flex-col gap-8">
<!-- Page Heading -->
<div class="flex flex-wrap justify-between items-end gap-4">
<div class="flex flex-col gap-2">
<h1 class="text-white text-3xl md:text-4xl font-black leading-tight tracking-[-0.033em]">سلام، مدیر محترم</h1>
<p class="text-accent-orange text-base font-normal leading-normal flex items-center gap-2">
<span class="material-symbols-outlined text-sm">calendar_today</span>
نمای کلی عملیات برای <?php echo date('Y/m/d'); ?>
</p>
</div>
<!-- Quick Actions -->
<div class="flex items-center gap-3">
<button class="flex items-center justify-center size-10 rounded-full bg-gray-700 text-white hover:bg-gray-600 transition-colors relative">
<span class="material-symbols-outlined">notifications</span>
<span class="absolute top-2 right-2 size-2 bg-primary rounded-full"></span>
</button>
<div class="flex items-center gap-3 bg-surface-dark pr-4 pl-1 py-1 rounded-full border border-gray-700">
<div class="size-8 rounded-full bg-primary flex items-center justify-center text-white font-bold text-sm">
<?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
</div>
<span class="text-sm font-bold text-white"><?php echo htmlspecialchars($admin['username']); ?></span>
</div>
</div>
</div>
<!-- Stats Overview -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
<!-- Stat Card 1 -->
<div class="flex flex-col gap-1 rounded-2xl p-6 bg-surface-dark border border-gray-700 relative overflow-hidden group hover:border-primary/50 transition-colors">
<div class="absolute top-0 left-0 p-6 opacity-10 group-hover:opacity-20 transition-opacity">
<span class="material-symbols-outlined text-6xl text-white">badge</span>
</div>
<p class="text-accent-orange text-sm font-medium uppercase tracking-wider">کارگران ثبت شده</p>
<div class="flex items-baseline gap-2 mt-2">
<p class="text-white text-4xl font-bold leading-tight"><?php echo $workerCount; ?></p>
<span class="inline-flex items-center gap-1 rounded bg-primary/10 px-2 py-0.5 text-xs font-bold text-primary">
<span class="material-symbols-outlined text-[14px]">trending_up</span> فعال
</span>
</div>
<p class="text-gray-400 text-sm mt-1">آماده تشخیص</p>
</div>
<!-- Stat Card 2 -->
<div class="flex flex-col gap-1 rounded-2xl p-6 bg-surface-dark border border-gray-700 relative overflow-hidden group hover:border-primary/50 transition-colors">
<div class="absolute top-0 left-0 p-6 opacity-10 group-hover:opacity-20 transition-opacity">
<span class="material-symbols-outlined text-6xl text-white">map</span>
</div>
<p class="text-accent-orange text-sm font-medium uppercase tracking-wider">مسیرهای تعریف شده</p>
<div class="flex items-baseline gap-2 mt-2">
<p class="text-white text-4xl font-bold leading-tight"><?php echo $pathCount; ?></p>
<span class="inline-flex items-center gap-1 rounded bg-primary/10 px-2 py-0.5 text-xs font-bold text-primary">
<span class="material-symbols-outlined text-[14px]">check_circle</span> تنظیم شده
</span>
</div>
<p class="text-gray-400 text-sm mt-1">مناطق نظارت</p>
</div>
<!-- Stat Card 3 -->
<div class="flex flex-col gap-1 rounded-2xl p-6 bg-surface-dark border border-gray-700 relative overflow-hidden group hover:border-primary/50 transition-colors">
<div class="absolute top-0 left-0 p-6 opacity-10 group-hover:opacity-20 transition-opacity">
<span class="material-symbols-outlined text-6xl text-white">analytics</span>
</div>
<p class="text-accent-orange text-sm font-medium uppercase tracking-wider">سیستم تشخیص</p>
<div class="flex items-baseline gap-2 mt-2">
<p class="text-white text-4xl font-bold leading-tight">AI</p>
<span class="inline-flex items-center gap-1 rounded bg-green-500/10 px-2 py-0.5 text-xs font-bold text-green-400">
<span class="material-symbols-outlined text-[14px]">psychology</span> هوشمند
</span>
</div>
<p class="text-gray-400 text-sm mt-1">تحلیل پیشرفته</p>
</div>
</div>
<!-- Administration Grid -->
<div class="flex flex-col gap-4">
<div class="flex items-center justify-between">
<h2 class="text-white text-xl font-bold leading-tight tracking-tight flex items-center gap-2">
<span class="material-symbols-outlined text-primary">admin_panel_settings</span>
مدیریت سیستم
</h2>
<button class="text-sm text-accent-orange hover:text-white transition-colors">مشاهده همه</button>
</div>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
<!-- Card 1 -->
<a href="workers-new.php" class="bg-cover bg-center flex flex-col justify-between rounded-xl p-5 aspect-video relative group cursor-pointer overflow-hidden shadow-lg" style='background-image: linear-gradient(0deg, rgba(17, 24, 39, 0.9) 0%, rgba(17, 24, 39, 0.4) 100%), url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27%3E%3Cdefs%3E%3Cpattern id=%27grid%27 width=%2710%27 height=%2710%27 patternUnits=%27userSpaceOnUse%27%3E%3Cpath d=%27M 10 0 L 0 0 0 10%27 fill=%27none%27 stroke=%27%23374151%27 stroke-width=%270.5%27/%3E%3C/pattern%3E%3C/defs%3E%3Crect width=%27100%27 height=%27100%27 fill=%27url(%23grid)%27/%3E%3C/svg%3E");'>
<div class="absolute inset-0 bg-primary opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
<div class="flex justify-between items-start">
<div class="bg-white/10 backdrop-blur-sm p-2 rounded-lg text-white">
<span class="material-symbols-outlined">badge</span>
</div>
<span class="material-symbols-outlined text-white/50 group-hover:text-primary transition-colors">arrow_outward</span>
</div>
<div>
<h3 class="text-white text-lg font-bold leading-tight mb-1">مدیریت کارگران</h3>
<p class="text-gray-300 text-xs line-clamp-1">افزودن و ویرایش کارگران</p>
</div>
</a>
<!-- Card 2 -->
<a href="paths-new.php" class="bg-cover bg-center flex flex-col justify-between rounded-xl p-5 aspect-video relative group cursor-pointer overflow-hidden shadow-lg" style='background-image: linear-gradient(0deg, rgba(17, 24, 39, 0.9) 0%, rgba(17, 24, 39, 0.4) 100%), url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27%3E%3Cdefs%3E%3Cpattern id=%27dots%27 width=%275%27 height=%275%27 patternUnits=%27userSpaceOnUse%27%3E%3Ccircle cx=%272.5%27 cy=%272.5%27 r=%271%27 fill=%27%23374151%27/%3E%3C/pattern%3E%3C/defs%3E%3Crect width=%27100%27 height=%27100%27 fill=%27url(%23dots)%27/%3E%3C/svg%3E");'>
<div class="absolute inset-0 bg-primary opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
<div class="flex justify-between items-start">
<div class="bg-white/10 backdrop-blur-sm p-2 rounded-lg text-white">
<span class="material-symbols-outlined">map</span>
</div>
<span class="material-symbols-outlined text-white/50 group-hover:text-primary transition-colors">arrow_outward</span>
</div>
<div>
<h3 class="text-white text-lg font-bold leading-tight mb-1">مدیریت مسیرها</h3>
<p class="text-gray-300 text-xs line-clamp-1">تعریف مناطق نظارت</p>
</div>
</a>
<!-- Card 3 -->
<a href="camera-new.php" class="bg-cover bg-center flex flex-col justify-between rounded-xl p-5 aspect-video relative group cursor-pointer overflow-hidden shadow-lg" style='background-image: linear-gradient(0deg, rgba(17, 24, 39, 0.9) 0%, rgba(17, 24, 39, 0.4) 100%), url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27%3E%3Cpath d=%27M0,0 L100,100 M100,0 L0,100%27 stroke=%27%23374151%27 stroke-width=%270.5%27/%3E%3C/svg%3E");'>
<div class="absolute inset-0 bg-primary opacity-0 group-hover:opacity-10 transition-opacity duration-300"></div>
<div class="flex justify-between items-start">
<div class="bg-white/10 backdrop-blur-sm p-2 rounded-lg text-white">
<span class="material-symbols-outlined">videocam</span>
</div>
<span class="material-symbols-outlined text-white/50 group-hover:text-primary transition-colors">arrow_outward</span>
</div>
<div>
<h3 class="text-white text-lg font-bold leading-tight mb-1">دوربین ساده</h3>
<p class="text-gray-300 text-xs line-clamp-1">نمایش تشخیص رنگ</p>
</div>
</a>
<!-- Card 4 -->
<a href="../index.php" class="bg-cover bg-center flex flex-col justify-between rounded-xl p-5 aspect-video relative group cursor-pointer overflow-hidden shadow-lg border-2 border-primary/50" style='background-image: linear-gradient(0deg, rgba(249, 115, 22, 0.2) 0%, rgba(17, 24, 39, 0.8) 100%), url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 100 100%27%3E%3Cpath d=%27M50,10 L90,50 L50,90 L10,50 Z%27 fill=%27none%27 stroke=%27%23f97316%27 stroke-width=%271%27/%3E%3C/svg%3E");'>
<div class="absolute inset-0 bg-primary opacity-10 group-hover:opacity-20 transition-opacity duration-300"></div>
<div class="flex justify-between items-start">
<div class="bg-primary/20 backdrop-blur-sm p-2 rounded-lg text-primary border border-primary/30">
<span class="material-symbols-outlined">psychology</span>
</div>
<span class="material-symbols-outlined text-primary group-hover:text-white transition-colors">arrow_outward</span>
</div>
<div>
<h3 class="text-white text-lg font-bold leading-tight mb-1">سیستم هوشمند</h3>
<p class="text-primary text-xs line-clamp-1 font-medium">تشخیص پیشرفته + AI</p>
</div>
</a>
</div>
</div>
<!-- Quick Actions -->
<div class="flex flex-col gap-4 pb-8">
<h2 class="text-white text-xl font-bold leading-tight tracking-tight flex items-center gap-2">
<span class="material-symbols-outlined text-primary">bolt</span>
اقدامات سریع
</h2>
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
<!-- Secondary Card 1 -->
<div class="flex items-center gap-4 p-4 rounded-xl border border-gray-700 bg-surface-dark hover:bg-gray-700 cursor-pointer transition-colors group">
<div class="size-12 rounded-lg bg-gray-700 group-hover:bg-primary flex items-center justify-center transition-colors text-white group-hover:text-white">
<span class="material-symbols-outlined">download</span>
</div>
<div class="flex flex-col">
<span class="text-white font-bold">دریافت گزارش</span>
<span class="text-gray-400 text-xs">فرمت PDF و Excel</span>
</div>
</div>
<!-- Secondary Card 2 -->
<div class="flex items-center gap-4 p-4 rounded-xl border border-gray-700 bg-surface-dark hover:bg-gray-700 cursor-pointer transition-colors group">
<div class="size-12 rounded-lg bg-gray-700 group-hover:bg-primary flex items-center justify-center transition-colors text-white group-hover:text-white">
<span class="material-symbols-outlined">settings</span>
</div>
<div class="flex flex-col">
<span class="text-white font-bold">تنظیمات سیستم</span>
<span class="text-gray-400 text-xs">پیکربندی عمومی</span>
</div>
</div>
<!-- Secondary Card 3 -->
<div class="flex items-center gap-4 p-4 rounded-xl border border-gray-700 bg-surface-dark hover:bg-gray-700 cursor-pointer transition-colors group">
<div class="size-12 rounded-lg bg-gray-700 group-hover:bg-primary flex items-center justify-center transition-colors text-white group-hover:text-white">
<span class="material-symbols-outlined">help</span>
</div>
<div class="flex flex-col">
<span class="text-white font-bold">راهنما و پشتیبانی</span>
<span class="text-gray-400 text-xs">دریافت کمک فنی</span>
</div>
</div>
</div>
</div>
</div>
</div>
</main>
</body>
</html>