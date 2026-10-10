<?php
session_start();
require_once __DIR__ . '/../config.php';
if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$pdo = getPDO();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'add';
    
    if ($action === 'delete') {
        $id = intval($_POST['id']);
        $stmt = $pdo->prepare('SELECT image FROM workers WHERE id = ?');
        $stmt->execute([$id]);
        $worker = $stmt->fetch();
        if ($worker && $worker['image']) {
            @unlink(__DIR__ . '/../uploads/' . $worker['image']);
        }
        $stmt = $pdo->prepare('DELETE FROM workers WHERE id = ?');
        $stmt->execute([$id]);
        header('Location: workers-new.php'); exit;
    }
    
    if ($action === 'edit') {
        $id = intval($_POST['id']);
        $name = trim($_POST['name'] ?? '');
        $details = trim($_POST['details'] ?? '');
        $path_id = intval($_POST['path_id'] ?? 0) ?: null;
        $face_id = intval($_POST['face_id'] ?? 0) ?: null;
        
        $imageName = $_POST['current_image'] ?? null;
        
        
        if ($face_id) {
            $stmt = $pdo->prepare('SELECT filename FROM face_encodings WHERE id = ? AND worker_id IS NULL');
            $stmt->execute([$face_id]);
            $face = $stmt->fetch();
            if ($face) {
                $sourcePath = __DIR__ . '/../uploads/faces/' . $face['filename'];
                if (file_exists($sourcePath)) {
                    $uploaddir = __DIR__ . '/../uploads';
                    if (!is_dir($uploaddir)) mkdir($uploaddir, 0755, true);
                    // Remove old image
                    if ($imageName) @unlink($uploaddir . '/' . $imageName);
                    $imageName = 'worker_' . time() . '_' . bin2hex(random_bytes(6)) . '.jpg';
                    copy($sourcePath, $uploaddir . '/' . $imageName);
                    
                    $stmt = $pdo->prepare('UPDATE face_encodings SET worker_id = NULL WHERE worker_id = ?');
                    $stmt->execute([$id]);
                    
                    $stmt = $pdo->prepare('UPDATE face_encodings SET worker_id = ? WHERE id = ?');
                    $stmt->execute([$id, $face_id]);
                }
            }
        } elseif (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $uploaddir = __DIR__ . '/../uploads';
            if (!is_dir($uploaddir)) mkdir($uploaddir, 0755, true);
            $tmp = $_FILES['image']['tmp_name'];
            $orig = basename($_FILES['image']['name']);
            $ext = pathinfo($orig, PATHINFO_EXTENSION);
            $allowed = ['jpg','jpeg','png','gif','webp'];
            if (in_array(strtolower($ext), $allowed)) {
                if ($imageName) @unlink($uploaddir . '/' . $imageName);
                $imageName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
                move_uploaded_file($tmp, $uploaddir . '/' . $imageName);
                
                $stmt = $pdo->prepare('UPDATE face_encodings SET worker_id = NULL WHERE worker_id = ?');
                $stmt->execute([$id]);
            }
        }
        
        if ($name) {
            $stmt = $pdo->prepare('UPDATE workers SET name = ?, details = ?, path_id = ?, image = ? WHERE id = ?');
            $stmt->execute([$name, $details, $path_id, $imageName, $id]);
            header('Location: workers-new.php'); exit;
        }
    }
    
    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $details = trim($_POST['details'] ?? '');
        $path_id = intval($_POST['path_id'] ?? 0) ?: null;
        $face_id = intval($_POST['face_id'] ?? 0) ?: null;
        $imageName = null;
        
        if ($face_id) {
            // Use selected face from face_encodings
            $stmt = $pdo->prepare('SELECT filename FROM face_encodings WHERE id = ? AND worker_id IS NULL');
            $stmt->execute([$face_id]);
            $face = $stmt->fetch();
            if ($face) {
                $sourcePath = __DIR__ . '/../uploads/faces/' . $face['filename'];
                if (file_exists($sourcePath)) {
                    $uploaddir = __DIR__ . '/../uploads';
                    if (!is_dir($uploaddir)) mkdir($uploaddir, 0755, true);
                    $imageName = 'worker_' . time() . '_' . bin2hex(random_bytes(6)) . '.jpg';
                    copy($sourcePath, $uploaddir . '/' . $imageName);
                }
            }
        } elseif (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
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
            
            if ($face_id && $imageName) {
                $workerId = $pdo->lastInsertId();
                $stmt = $pdo->prepare('UPDATE face_encodings SET worker_id = ? WHERE id = ?');
                $stmt->execute([$workerId, $face_id]);
            }
            
            header('Location: workers-new.php'); exit;
        }
    }
}
$workers = $pdo->query('SELECT w.*, p.name AS path_name FROM workers w LEFT JOIN paths p ON w.path_id = p.id ORDER BY w.id DESC')->fetchAll(PDO::FETCH_ASSOC);
$paths = $pdo->query('SELECT id, name FROM paths ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$availableFaces = $pdo->query('SELECT id, filename, created_at FROM face_encodings WHERE worker_id IS NULL ORDER BY created_at DESC')->fetchAll(PDO::FETCH_ASSOC);
$stmt = $pdo->prepare('SELECT username FROM admins WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html class="dark" lang="fa" dir="rtl">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>مدیریت کارگران - سیستم تشخیص</title>
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
        
        .face-option {
            transition: all 0.2s ease;
        }
        .face-radio:checked + .face-option {
            border-color: #f97316 !important;
            background-color: rgba(249, 115, 22, 0.1);
            transform: scale(1.05);
        }
        .face-radio:checked + .face-option::after {
            content: '✓';
            position: absolute;
            top: 8px;
            right: 8px;
            background: #f97316;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: bold;
        }
        .face-option {
            position: relative;
        }
        
        .edit-face-radio:checked + .edit-face-option {
            border-color: #f97316 !important;
            background-color: rgba(249, 115, 22, 0.1);
        }
        .edit-face-radio:checked + .edit-face-option::after {
            content: '✓';
            position: absolute;
            top: 4px;
            right: 4px;
            background: #f97316;
            color: white;
            border-radius: 50%;
            width: 16px;
            height: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
        }
        .edit-face-option {
            position: relative;
        }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-display text-slate-900 dark:text-white overflow-hidden h-screen flex">
<aside class="w-64 h-full flex flex-col bg-surface-darker border-l border-gray-700 shrink-0 hidden md:flex">
<div class="p-6 pb-2">
<div class="flex items-center gap-3 mb-6">
<div class="bg-center bg-no-repeat aspect-square bg-cover rounded-full size-10 border border-primary/30 shadow-[0_0_15px_rgba(249,115,22,0.2)]" style='background-image: url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 viewBox=%270 0 24 24%27 fill=%27%23f97316%27%3E%3Cpath d=%27M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z%27/%3E%3C/svg%3E");'></div>
<div class="flex flex-col">
<h1 class="text-white text-lg font-bold leading-normal tracking-tight">سیستم تشخیص</h1>
<p class="text-accent-orange text-xs font-medium leading-normal">نسخه 1.0.0</p>
</div>
</div>
</div>
<div class="flex-1 overflow-y-auto px-4 flex flex-col gap-2">
<a class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-400 hover:text-white hover:bg-gray-700/50 transition-colors duration-200" href="dashboard-new.php">
<span class="material-symbols-outlined">grid_view</span>
<p class="text-sm font-medium leading-normal">داشبورد</p>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-gray-700 group transition-all duration-200 border border-primary/20 shadow-sm" href="#">
<span class="material-symbols-outlined text-primary">badge</span>
<p class="text-white text-sm font-bold leading-normal">مدیریت کارگران</p>
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
<div class="p-4 border-t border-gray-700">
<a href="logout.php" class="flex w-full cursor-pointer items-center justify-center gap-2 overflow-hidden rounded-xl h-10 px-4 bg-primary hover:bg-orange-600 text-white transition-colors duration-200">
<span class="material-symbols-outlined text-[20px] font-bold">logout</span>
<span class="text-sm font-bold leading-normal tracking-[0.015em] truncate">خروج</span>
</a>
</div>
</aside>

<main class="flex-1 flex flex-col h-full overflow-hidden relative">
<header class="h-16 flex items-center justify-between px-6 border-b border-gray-700 bg-surface-darker shrink-0">
<div class="flex items-center gap-4">
<a href="dashboard-new.php" class="flex items-center gap-2 text-gray-400 hover:text-white transition-colors">
<span class="material-symbols-outlined">arrow_back</span>
<span class="text-sm">بازگشت به داشبورد</span>
</a>
</div>
<div class="flex items-center gap-3">
<div class="flex items-center gap-3 bg-surface-dark pr-4 pl-1 py-1 rounded-full border border-gray-700">
<div class="size-8 rounded-full bg-primary flex items-center justify-center text-white font-bold text-sm">
<?php echo strtoupper(substr($admin['username'], 0, 1)); ?>
</div>
<span class="text-sm font-bold text-white"><?php echo htmlspecialchars($admin['username']); ?></span>
</div>
</div>
</header>
<div class="flex-1 overflow-y-auto p-4 md:p-8">
<div class="max-w-[1440px] mx-auto flex flex-col gap-8">
<div class="flex flex-wrap justify-between items-end gap-4">
<div class="flex flex-col gap-2">
<h1 class="text-white text-3xl md:text-4xl font-black leading-tight tracking-[-0.033em] flex items-center gap-3">
<span class="material-symbols-outlined text-primary text-4xl">badge</span>
مدیریت کارگران
</h1>
<p class="text-accent-orange text-base font-normal leading-normal">
افزودن، ویرایش و مشاهده اطلاعات کارگران سیستم
</p>
</div>
</div>

<div class="bg-surface-dark border border-gray-700 rounded-2xl p-6">
<h2 class="text-white text-xl font-bold mb-4 flex items-center gap-2">
<span class="material-symbols-outlined text-primary">person_add</span>
افزودن کارگر جدید
</h2>
<form method="post" enctype="multipart/form-data" class="space-y-6">
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
<div class="flex flex-col">
<label class="text-gray-300 text-sm font-medium mb-2">نام کارگر</label>
<input name="name" class="bg-gray-800 border border-gray-600 rounded-lg px-4 py-2 text-white focus:border-primary focus:outline-none" placeholder="نام کامل" required>
</div>
<div class="flex flex-col">
<label class="text-gray-300 text-sm font-medium mb-2">مشخصات</label>
<input name="details" class="bg-gray-800 border border-gray-600 rounded-lg px-4 py-2 text-white focus:border-primary focus:outline-none" placeholder="سمت یا توضیحات">
</div>
<div class="flex flex-col">
<label class="text-gray-300 text-sm font-medium mb-2">مسیر مرتبط</label>
<select name="path_id" class="bg-gray-800 border border-gray-600 rounded-lg px-4 py-2 text-white focus:border-primary focus:outline-none">
<option value="">انتخاب مسیر</option>
<?php foreach($paths as $p): ?>
<option value="<?=$p['id']?>"><?=htmlspecialchars($p['name'])?></option>
<?php endforeach; ?>
</select>
</div>
</div>

<?php if (!empty($availableFaces)): ?>
<div class="border-t border-gray-700 pt-6">
<h3 class="text-white text-lg font-bold mb-4 flex items-center gap-2">
<span class="material-symbols-outlined text-primary">face</span>
انتخاب چهره از سیستم تشخیص
</h3>
<p class="text-gray-400 text-sm mb-4">چهره‌های زیر در سیستم تشخیص ثبت شده‌اند و هنوز به کارگری اختصاص نیافته‌اند:</p>
<div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-4">
<?php foreach($availableFaces as $face): ?>
<label class="cursor-pointer group">
<input type="radio" name="face_id" value="<?=$face['id']?>" class="sr-only face-radio" onchange="toggleImageUpload()">
<div class="face-option border-2 border-gray-600 rounded-lg p-2 transition-all duration-200 hover:border-primary/50 group-hover:scale-105">
<img src="../uploads/faces/<?=htmlspecialchars($face['filename'])?>" 
     class="w-full h-24 object-cover rounded-md mb-2" 
     alt="Face <?=$face['id']?>">
<div class="text-center">
<p class="text-xs text-gray-400">شناسه: <?=$face['id']?></p>
<p class="text-xs text-gray-500"><?=date('Y/m/d H:i', strtotime($face['created_at']))?></p>
</div>
</div>
</label>
<?php endforeach; ?>
</div>
</div>
<?php endif; ?>

<div class="border-t border-gray-700 pt-6" id="manualUploadSection">
<h3 class="text-white text-lg font-bold mb-4 flex items-center gap-2">
<span class="material-symbols-outlined text-primary">upload</span>
یا آپلود دستی تصویر
</h3>
<div class="flex flex-col">
<label class="text-gray-300 text-sm font-medium mb-2">تصویر کارگر</label>
<input type="file" name="image" id="imageUpload" accept="image/*" class="bg-gray-800 border border-gray-600 rounded-lg px-4 py-2 text-white focus:border-primary focus:outline-none file:bg-primary file:border-0 file:rounded file:px-3 file:py-1 file:text-white file:text-sm">
<p class="text-gray-500 text-xs mt-1">در صورت انتخاب چهره از بالا، این فیلد نادیده گرفته می‌شود</p>
</div>
</div>

<div class="flex justify-end">
<button class="bg-primary hover:bg-orange-600 text-white font-bold py-3 px-6 rounded-lg transition-colors flex items-center justify-center gap-2">
<span class="material-symbols-outlined">add</span>
افزودن کارگر
</button>
</div>
</form>
</div>

<div class="bg-surface-dark border border-gray-700 rounded-2xl p-6">
<div class="flex items-center justify-between mb-6">
<h2 class="text-white text-xl font-bold flex items-center gap-2">
<span class="material-symbols-outlined text-primary">group</span>
لیست کارگران (<?php echo count($workers); ?> نفر)
</h2>
</div>

<?php if (empty($workers)): ?>
<div class="text-center py-12">
<span class="material-symbols-outlined text-6xl text-gray-600 mb-4 block">person_off</span>
<p class="text-gray-400 text-lg">هنوز کارگری ثبت نشده است</p>
<p class="text-gray-500 text-sm mt-2">از فرم بالا برای افزودن کارگر جدید استفاده کنید</p>
</div>
<?php else: ?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
<?php foreach($workers as $w): ?>
<div class="bg-gray-800 border border-gray-600 rounded-xl p-4 hover:border-primary/50 transition-colors group">
<div class="flex items-center gap-4">
<div class="size-16 rounded-lg overflow-hidden bg-gray-700 flex items-center justify-center">
<?php if($w['image']): ?>
<img src="../uploads/<?=htmlspecialchars($w['image'])?>" class="w-full h-full object-cover">
<?php else: ?>
<span class="material-symbols-outlined text-2xl text-gray-400">person</span>
<?php endif; ?>
</div>
<div class="flex-1">
<h3 class="text-white font-bold text-lg"><?= htmlspecialchars($w['name']) ?></h3>
<p class="text-gray-400 text-sm"><?= htmlspecialchars($w['details']) ?></p>
<?php if($w['path_name']): ?>
<div class="flex items-center gap-1 mt-1">
<span class="material-symbols-outlined text-xs text-primary">location_on</span>
<span class="text-primary text-xs"><?= htmlspecialchars($w['path_name']) ?></span>
</div>
<?php endif; ?>
</div>
</div>
<div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-700">
<span class="text-xs text-gray-500">شناسه: #<?= $w['id'] ?></span>
<div class="flex gap-2">
<button onclick="editWorker(<?= $w['id'] ?>, '<?= htmlspecialchars($w['name'], ENT_QUOTES) ?>', '<?= htmlspecialchars($w['details'], ENT_QUOTES) ?>', <?= $w['path_id'] ?: 'null' ?>, '<?= $w['image'] ?>')" class="text-gray-400 hover:text-primary transition-colors">
<span class="material-symbols-outlined text-sm">edit</span>
</button>
<button onclick="deleteWorker(<?= $w['id'] ?>, '<?= htmlspecialchars($w['name'], ENT_QUOTES) ?>')" class="text-gray-400 hover:text-red-400 transition-colors">
<span class="material-symbols-outlined text-sm">delete</span>
</button>
</div>
</div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>
</div>
</div>
</div>
</main>

<div id="editModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
<div class="bg-surface-dark border border-gray-700 rounded-2xl p-6 w-full max-w-md mx-4">
<h3 class="text-white text-xl font-bold mb-4 flex items-center gap-2">
<span class="material-symbols-outlined text-primary">edit</span>
ویرایش کارگر
</h3>
<form id="editForm" method="post" enctype="multipart/form-data">
<input type="hidden" name="action" value="edit">
<input type="hidden" name="id" id="editId">
<input type="hidden" name="current_image" id="editCurrentImage">
<div class="space-y-4">
<div>
<label class="text-gray-300 text-sm font-medium block mb-2">نام کارگر</label>
<input name="name" id="editName" class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-2 text-white focus:border-primary focus:outline-none" required>
</div>
<div>
<label class="text-gray-300 text-sm font-medium block mb-2">مشخصات</label>
<input name="details" id="editDetails" class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-2 text-white focus:border-primary focus:outline-none">
</div>
<div>
<label class="text-gray-300 text-sm font-medium block mb-2">مسیر مرتبط</label>
<select name="path_id" id="editPathId" class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-2 text-white focus:border-primary focus:outline-none">
<option value="">انتخاب مسیر</option>
<?php foreach($paths as $p): ?>
<option value="<?=$p['id']?>"><?=htmlspecialchars($p['name'])?></option>
<?php endforeach; ?>
</select>
</div>
<?php if (!empty($availableFaces)): ?>
<div>
<label class="text-gray-300 text-sm font-medium block mb-2">انتخاب چهره از سیستم</label>
<div class="grid grid-cols-3 gap-2 max-h-32 overflow-y-auto">
<?php foreach($availableFaces as $face): ?>
<label class="cursor-pointer">
<input type="radio" name="face_id" value="<?=$face['id']?>" class="sr-only edit-face-radio" onchange="toggleEditImageUpload()">
<div class="edit-face-option border border-gray-600 rounded p-1 transition-all duration-200 hover:border-primary/50">
<img src="../uploads/faces/<?=htmlspecialchars($face['filename'])?>" 
     class="w-full h-12 object-cover rounded" 
     alt="Face <?=$face['id']?>">
<p class="text-xs text-gray-400 text-center mt-1">#<?=$face['id']?></p>
</div>
</label>
<?php endforeach; ?>
</div>
</div>
<?php endif; ?>
<div>
<label class="text-gray-300 text-sm font-medium block mb-2">یا تصویر جدید (اختیاری)</label>
<input type="file" name="image" id="editImageUpload" accept="image/*" class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-2 text-white focus:border-primary focus:outline-none file:bg-primary file:border-0 file:rounded file:px-3 file:py-1 file:text-white file:text-sm">
</div>
</div>
<div class="flex gap-3 mt-6">
<button type="submit" class="flex-1 bg-primary hover:bg-orange-600 text-white font-bold py-2 px-4 rounded-lg transition-colors">
ذخیره تغییرات
</button>
<button type="button" onclick="closeEditModal()" class="flex-1 bg-gray-700 hover:bg-gray-600 text-white font-bold py-2 px-4 rounded-lg transition-colors">
انصراف
</button>
</div>
</form>
</div>
</div>

<script>
function editWorker(id, name, details, pathId, image) {
    document.getElementById('editId').value = id;
    document.getElementById('editName').value = name;
    document.getElementById('editDetails').value = details;
    document.getElementById('editPathId').value = pathId || '';
    document.getElementById('editCurrentImage').value = image || '';
    document.getElementById('editModal').classList.remove('hidden');
    document.getElementById('editModal').classList.add('flex');
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.getElementById('editModal').classList.remove('flex');
}

function deleteWorker(id, name) {
    if (confirm('آیا مطمئن هستید که میخواهید کارگر "' + name + '" را حذف کنید؟')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="' + id + '">';
        document.body.appendChild(form);
        form.submit();
    }
}

function toggleImageUpload() {
    const faceRadios = document.querySelectorAll('.face-radio');
    const imageUpload = document.getElementById('imageUpload');
    const manualSection = document.getElementById('manualUploadSection');
    
    let faceSelected = false;
    faceRadios.forEach(radio => {
        if (radio.checked) {
            faceSelected = true;
        }
    });
    
    if (faceSelected) {
        imageUpload.disabled = true;
        imageUpload.style.opacity = '0.5';
        manualSection.style.opacity = '0.6';
    } else {
        imageUpload.disabled = false;
        imageUpload.style.opacity = '1';
        manualSection.style.opacity = '1';
    }
}

function toggleEditImageUpload() {
    const editFaceRadios = document.querySelectorAll('.edit-face-radio');
    const editImageUpload = document.getElementById('editImageUpload');
    
    let faceSelected = false;
    editFaceRadios.forEach(radio => {
        if (radio.checked) {
            faceSelected = true;
        }
    });
    
    if (faceSelected) {
        editImageUpload.disabled = true;
        editImageUpload.style.opacity = '0.5';
    } else {
        editImageUpload.disabled = false;
        editImageUpload.style.opacity = '1';
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    toggleImageUpload();
    toggleEditImageUpload();
});
</script>
</body>
</html>