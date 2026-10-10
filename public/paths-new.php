<?php
session_start();
require_once __DIR__ . '/../config.php';
if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$pdo = getPDO();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $start_lat = floatval($_POST['start_lat'] ?? 0);
    $start_lng = floatval($_POST['start_lng'] ?? 0);
    $end_lat = floatval($_POST['end_lat'] ?? 0);
    $end_lng = floatval($_POST['end_lng'] ?? 0);
    $edit_id = intval($_POST['edit_id'] ?? 0);
    
    if ($name) {
        if ($edit_id) {
            // Update existing path
            $stmt = $pdo->prepare('UPDATE paths SET name = ?, start_lat = ?, start_lng = ?, end_lat = ?, end_lng = ? WHERE id = ?');
            $stmt->execute([$name, $start_lat, $start_lng, $end_lat, $end_lng, $edit_id]);
        } else {
            // Insert new path
            $stmt = $pdo->prepare('INSERT INTO paths (name, start_lat, start_lng, end_lat, end_lng, description) VALUES (?, ?, ?, ?, ?, ?)');
            $stmt->execute([$name, $start_lat, $start_lng, $end_lat, $end_lng, '']);
        }
        header('Location: paths-new.php'); exit;
    }
}
$paths = $pdo->query('
    SELECT p.*, w.name as worker_name, w.image as worker_image 
    FROM paths p 
    LEFT JOIN path_assignments pa ON p.id = pa.path_id 
    LEFT JOIN workers w ON pa.worker_id = w.id 
    ORDER BY p.id DESC
')->fetchAll(PDO::FETCH_ASSOC);
$stmt = $pdo->prepare('SELECT username FROM admins WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html class="dark" lang="fa" dir="rtl">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>مدیریت مسیرها - سیستم تشخیص</title>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
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
<a class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-400 hover:text-white hover:bg-gray-700/50 transition-colors duration-200" href="dashboard-new.php">
<span class="material-symbols-outlined">grid_view</span>
<p class="text-sm font-medium leading-normal">داشبورد</p>
</a>
<a class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-gray-400 hover:text-white hover:bg-gray-700/50 transition-colors duration-200" href="workers-new.php">
<span class="material-symbols-outlined">badge</span>
<p class="text-sm font-medium leading-normal">مدیریت کارگران</p>
</a>
<!-- Active Item -->
<a class="flex items-center gap-3 px-3 py-2.5 rounded-xl bg-gray-700 group transition-all duration-200 border border-primary/20 shadow-sm" href="#">
<span class="material-symbols-outlined text-primary">map</span>
<p class="text-white text-sm font-bold leading-normal">مدیریت مسیرها</p>
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
<!-- Page Heading -->
<div class="flex flex-wrap justify-between items-end gap-4">
<div class="flex flex-col gap-2">
<h1 class="text-white text-3xl md:text-4xl font-black leading-tight tracking-[-0.033em] flex items-center gap-3">
<span class="material-symbols-outlined text-primary text-4xl">map</span>
مدیریت مسیرها
</h1>
<p class="text-accent-orange text-base font-normal leading-normal">
تعریف مناطق نظارت و مسیرهای تشخیص کارگران
</p>
</div>
</div>

<!-- Add Path Form -->
<div class="bg-surface-dark border border-gray-700 rounded-2xl p-6">
<h2 class="text-white text-xl font-bold mb-4 flex items-center gap-2">
<span class="material-symbols-outlined text-primary">add_location</span>
افزودن مسیر جدید
</h2>
<div id="map" style="height: 400px; margin-bottom: 20px; border-radius: 12px;"></div>
<div id="status" class="text-center mb-4 p-2 bg-gray-800 rounded-lg">
<span class="text-gray-400">برای شروع انتخاب منطقه، دکمه "انتخاب منطقه" را کلیک کنید</span>
</div>
<form method="post" id="pathForm" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-4">
<div class="flex flex-col">
<label class="text-gray-300 text-sm font-medium mb-2">نام مسیر</label>
<input name="name" class="bg-gray-800 border border-gray-600 rounded-lg px-4 py-2 text-white focus:border-primary focus:outline-none" placeholder="نام منطقه" required>
</div>
<div class="flex flex-col">
<label class="text-gray-300 text-sm font-medium mb-2">عرض شروع</label>
<input name="start_lat" class="bg-gray-800 border border-gray-600 rounded-lg px-4 py-2 text-white focus:border-primary focus:outline-none" placeholder="عرض جغرافیایی" readonly>
</div>
<div class="flex flex-col">
<label class="text-gray-300 text-sm font-medium mb-2">طول شروع</label>
<input name="start_lng" class="bg-gray-800 border border-gray-600 rounded-lg px-4 py-2 text-white focus:border-primary focus:outline-none" placeholder="طول جغرافیایی" readonly>
</div>
<div class="flex flex-col">
<label class="text-gray-300 text-sm font-medium mb-2">عرض پایان</label>
<input name="end_lat" class="bg-gray-800 border border-gray-600 rounded-lg px-4 py-2 text-white focus:border-primary focus:outline-none" placeholder="عرض جغرافیایی" readonly>
</div>
<div class="flex flex-col">
<label class="text-gray-300 text-sm font-medium mb-2">طول پایان</label>
<input name="end_lng" class="bg-gray-800 border border-gray-600 rounded-lg px-4 py-2 text-white focus:border-primary focus:outline-none" placeholder="طول جغرافیایی" readonly>
</div>
<div class="flex flex-col gap-2">
<button type="button" onclick="selectArea()" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition-colors flex items-center justify-center gap-2">
<span class="material-symbols-outlined">location_searching</span>
انتخاب منطقه
</button>
<button type="submit" class="w-full bg-primary hover:bg-orange-600 text-white font-bold py-2 px-4 rounded-lg transition-colors flex items-center justify-center gap-2">
<span class="material-symbols-outlined">add</span>
افزودن مسیر
</button>
<button type="button" onclick="showWorkerAssignment()" class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition-colors flex items-center justify-center gap-2" id="assignBtn" disabled>
<span class="material-symbols-outlined">person_add</span>
تخصیص سریع
</button>
</div>
</form>
</div>

<!-- Paths List -->
<div class="bg-surface-dark border border-gray-700 rounded-2xl p-6">
<div class="flex items-center justify-between mb-6">
<h2 class="text-white text-xl font-bold flex items-center gap-2">
<span class="material-symbols-outlined text-primary">route</span>
لیست مسیرها (<?php echo count($paths); ?> مسیر)
</h2>
</div>

<?php if (empty($paths)): ?>
<div class="text-center py-12">
<span class="material-symbols-outlined text-6xl text-gray-600 mb-4 block">location_off</span>
<p class="text-gray-400 text-lg">هنوز مسیری تعریف نشده است</p>
<p class="text-gray-500 text-sm mt-2">از نقشه بالا برای افزودن مسیر جدید استفاده کنید</p>
</div>
<?php else: ?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
<?php foreach($paths as $p): ?>
<div class="bg-gray-800 border border-gray-600 rounded-xl p-4 hover:border-primary/50 transition-colors group">
<div class="flex items-start justify-between mb-4">
<div class="flex items-center gap-3">
<div class="size-12 rounded-lg bg-primary/20 flex items-center justify-center relative">
<?php if($p['worker_image']): ?>
<img src="../uploads/<?= htmlspecialchars($p['worker_image']) ?>" class="w-full h-full rounded-lg object-cover">
<div class="absolute -top-1 -right-1 w-4 h-4 bg-green-500 rounded-full border-2 border-gray-800"></div>
<?php else: ?>
<span class="material-symbols-outlined text-primary">location_on</span>
<?php endif; ?>
</div>
<div>
<h3 class="text-white font-bold text-lg"><?= htmlspecialchars($p['name']) ?></h3>
<p class="text-gray-400 text-sm">شناسه: #<?= $p['id'] ?></p>
<?php if($p['worker_name']): ?>
<p class="text-green-400 text-xs">تخصیص: <?= htmlspecialchars($p['worker_name']) ?></p>
<?php endif; ?>
</div>
</div>
<div class="flex gap-2">
<button onclick="editPath(<?= $p['id'] ?>)" class="text-blue-400 hover:text-blue-300 transition-colors">
<span class="material-symbols-outlined text-sm">edit</span>
</button>
<button onclick="deletePath(<?= $p['id'] ?>)" class="text-red-400 hover:text-red-300 transition-colors">
<span class="material-symbols-outlined text-sm">delete</span>
</button>
</div>
</div>
<div class="grid grid-cols-2 gap-3">
<div class="bg-gray-700 rounded-lg p-3">
<p class="text-gray-400 text-xs mb-1">شروع (عرض)</p>
<p class="text-white font-bold"><?= isset($p['start_lat']) ? $p['start_lat'] : 'N/A' ?></p>
</div>
<div class="bg-gray-700 rounded-lg p-3">
<p class="text-gray-400 text-xs mb-1">شروع (طول)</p>
<p class="text-white font-bold"><?= isset($p['start_lng']) ? $p['start_lng'] : 'N/A' ?></p>
</div>
<div class="bg-gray-700 rounded-lg p-3">
<p class="text-gray-400 text-xs mb-1">پایان (عرض)</p>
<p class="text-white font-bold"><?= isset($p['end_lat']) ? $p['end_lat'] : 'N/A' ?></p>
</div>
<div class="bg-gray-700 rounded-lg p-3">
<p class="text-gray-400 text-xs mb-1">پایان (طول)</p>
<p class="text-white font-bold"><?= isset($p['end_lng']) ? $p['end_lng'] : 'N/A' ?></p>
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
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
let map, startMarker, endMarker, selecting = false, step = 0;
map = L.map('map').setView([33.4877, 48.3553], 13);
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);
function selectArea() {
    selecting = true; step = 0;
    if(startMarker) map.removeLayer(startMarker);
    if(endMarker) map.removeLayer(endMarker);
    document.getElementById('status').innerHTML = '<span class="text-blue-400">نقطه شروع را انتخاب کنید</span>';
}
map.on('click', function(e) {
    if(!selecting) return;
    if(step === 0) {
        startMarker = L.marker([e.latlng.lat, e.latlng.lng]).addTo(map).bindPopup('نقطه شروع').openPopup();
        document.querySelector('input[name="start_lat"]').value = e.latlng.lat.toFixed(6);
        document.querySelector('input[name="start_lng"]').value = e.latlng.lng.toFixed(6);
        step = 1;
        document.getElementById('status').innerHTML = '<span class="text-green-400">نقطه پایان را انتخاب کنید</span>';
    } else if(step === 1) {
        endMarker = L.marker([e.latlng.lat, e.latlng.lng]).addTo(map).bindPopup('نقطه پایان').openPopup();
        document.querySelector('input[name="end_lat"]').value = e.latlng.lat.toFixed(6);
        document.querySelector('input[name="end_lng"]').value = e.latlng.lng.toFixed(6);
        selecting = false;
        step = 0;
        L.polyline([startMarker.getLatLng(), endMarker.getLatLng()], {color: 'red'}).addTo(map);
        document.getElementById('status').innerHTML = '<span class="text-primary">منطقه انتخاب شد - آماده ثبت</span>';
        document.getElementById('assignBtn').disabled = false;
        
        selectedPathCoords = {
            start_lat: document.querySelector('input[name="start_lat"]').value,
            start_lng: document.querySelector('input[name="start_lng"]').value,
            end_lat: document.querySelector('input[name="end_lat"]').value,
            end_lng: document.querySelector('input[name="end_lng"]').value
        };
    }
});

// Worker assignment functionality
let selectedPathCoords = null;

function showWorkerAssignment() {
    if (!selectedPathCoords) {
        alert('ابتدا منطقه را انتخاب کنید');
        return;
    }
    
    // Get workers list
    fetch('get_workers.php')
        .then(response => response.json())
        .then(workers => {
            showWorkerModal(workers);
        })
        .catch(error => {
            console.error('Error:', error);
            alert('خطا در دریافت لیست کارگران');
        });
}

function showWorkerModal(workers) {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-[9999]';
    modal.innerHTML = `
        <div class="bg-surface-dark p-6 rounded-2xl max-w-md w-full mx-4 max-h-96 overflow-y-auto">
            <h3 class="text-white text-xl font-bold mb-4">انتخاب کارگر</h3>
            <div class="space-y-2">
                ${workers.map(worker => `
                    <div class="bg-gray-700 p-3 rounded-lg cursor-pointer hover:bg-gray-600 transition-colors" onclick="assignWorkerToPath(${worker.id}, '${worker.name}')">
                        <div class="flex items-center gap-3">
                            ${worker.image ? `<img src="../uploads/${worker.image}" class="w-10 h-10 rounded-full object-cover">` : '<div class="w-10 h-10 bg-gray-500 rounded-full flex items-center justify-center"><i class="fas fa-user text-white"></i></div>'}
                            <div>
                                <div class="text-white font-medium">${worker.name}</div>
                                <div class="text-gray-400 text-sm">${worker.details || 'جزئیات موجود نیست'}</div>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
            <button onclick="closeWorkerModal()" class="mt-4 w-full bg-gray-600 hover:bg-gray-500 text-white py-2 px-4 rounded-lg">بستن</button>
        </div>
    `;
    document.body.appendChild(modal);
    window.currentModal = modal;
}

function closeWorkerModal() {
    if (window.currentModal) {
        document.body.removeChild(window.currentModal);
        window.currentModal = null;
    }
}

function assignWorkerToPath(workerId, workerName) {
    if (!selectedPathCoords) return;
    
    const pathName = document.querySelector('input[name="name"]').value || 'مسیر جدید';
    
    // First create the path
    const formData = new FormData();
    formData.append('name', pathName);
    formData.append('start_lat', selectedPathCoords.start_lat);
    formData.append('start_lng', selectedPathCoords.start_lng);
    formData.append('end_lat', selectedPathCoords.end_lat);
    formData.append('end_lng', selectedPathCoords.end_lng);
    
    fetch('paths-new.php', {
        method: 'POST',
        body: formData
    })
    .then(() => {
        // Then assign worker to the new path
        return fetch('assign_worker_to_path.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                worker_id: workerId,
                path_name: pathName
            })
        });
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(`مسیر با موفقیت به ${workerName} تخصیص داده شد`);
            location.reload();
        } else {
            alert('خطا: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('خطا در تخصیص مسیر');
    })
    .finally(() => {
        closeWorkerModal();
    });
}



// Edit and delete functions
function editPath(pathId) {
    console.log('Editing path ID:', pathId);
    // Get path data
    fetch(`get_path.php?id=${pathId}`)
        .then(response => response.json())
        .then(path => {
            console.log('Received path data:', path);
            if (path.error) {
                alert('خطا: ' + path.error);
                return;
            }
            
            // Fill form with existing data
            document.querySelector('input[name="name"]').value = path.name;
            document.querySelector('input[name="start_lat"]').value = path.start_lat;
            document.querySelector('input[name="start_lng"]').value = path.start_lng;
            document.querySelector('input[name="end_lat"]').value = path.end_lat;
            document.querySelector('input[name="end_lng"]').value = path.end_lng;
            
            // Add markers to map
            if (startMarker) map.removeLayer(startMarker);
            if (endMarker) map.removeLayer(endMarker);
            
            startMarker = L.marker([path.start_lat, path.start_lng]).addTo(map).bindPopup('نقطه شروع');
            endMarker = L.marker([path.end_lat, path.end_lng]).addTo(map).bindPopup('نقطه پایان');
            L.polyline([startMarker.getLatLng(), endMarker.getLatLng()], {color: 'red'}).addTo(map);
            
            // Change form to edit mode
            const form = document.getElementById('pathForm');
            // Remove existing hidden input if any
            const existingInput = form.querySelector('input[name="edit_id"]');
            if (existingInput) existingInput.remove();
            
            // Add new hidden input
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'edit_id';
            hiddenInput.value = pathId;
            form.appendChild(hiddenInput);
            
            document.querySelector('button[type="submit"]').innerHTML = '<span class="material-symbols-outlined">edit</span>بهروزرسانی مسیر';
            document.getElementById('assignBtn').disabled = false;
            
            // Update selectedPathCoords for assignment
            selectedPathCoords = {
                start_lat: path.start_lat,
                start_lng: path.start_lng,
                end_lat: path.end_lat,
                end_lng: path.end_lng
            };
            
            document.getElementById('status').innerHTML = '<span class="text-yellow-400">حالت ویرایش - آماده بهروزرسانی</span>';
        })
        .catch(error => {
            console.error('Error:', error);
            alert('خطا در دریافت اطلاعات مسیر');
        });
}

function deletePath(pathId) {
    if (!confirm('آیا مطمئن هستید که میخواهید این مسیر را حذف کنید؟')) {
        return;
    }
    
    fetch('delete_path.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id: pathId})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('مسیر با موفقیت حذف شد');
            location.reload();
        } else {
            alert('خطا: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('خطا در حذف مسیر');
    });
}
</script>
</body>
</html>