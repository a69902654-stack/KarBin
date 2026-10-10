<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: public/login.php');
    exit;
}
require_once __DIR__ . '/config.php';
 $pdo = getPDO();
 $paths = $pdo->query('SELECT id,name,x,y,width,height FROM paths ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
 $workersRaw = $pdo->query('SELECT id,name,details,path_id,image FROM workers ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
 $base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
 $workers = [];
foreach($workersRaw as $w){
    $w['image_url'] = $w['image'] ? (($base? $base : '') . '/uploads/' . basename($w['image'])) : null;
    $workers[] = $w;
}
 $init = ['paths' => $paths, 'workers' => $workers];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تشخیص رفتگر</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script defer src="https://unpkg.com/face-api.js@0.22.2/dist/face-api.min.js"></script>
    <style>
        .tracking-cube {
            position: absolute;
            border: 3px solid rgba(255, 165, 0, 0.9);
            background-color: rgba(255, 165, 0, 0.15);
            z-index: 10;
            transition: all 0.2s ease-out;
            border-radius: 8px;
            box-shadow: 0 0 25px rgba(255, 165, 0, 0.6);
            animation: pulse-orange 1.5s infinite;
        }
        .tracking-cube.known-worker {
            border: 3px solid rgba(34, 197, 94, 0.9);
            background-color: rgba(34, 197, 94, 0.15);
            box-shadow: 0 0 25px rgba(34, 197, 94, 0.6);
            animation: pulse-green 1.5s infinite;
        }
        .tracking-cube.unknown-face {
            border: 3px solid rgba(239, 68, 68, 0.9);
            background-color: rgba(239, 68, 68, 0.15);
            box-shadow: 0 0 25px rgba(239, 68, 68, 0.6);
            animation: pulse-red 1.5s infinite;
        }
        @keyframes pulse-orange { 
            0% { box-shadow: 0 0 15px rgba(255,165,0,0.5);} 
            50% { box-shadow: 0 0 35px rgba(255,165,0,0.8);} 
            100% { box-shadow: 0 0 15px rgba(255,165,0,0.5);} 
        }
        @keyframes pulse-green { 
            0% { box-shadow: 0 0 15px rgba(34,197,94,0.5);} 
            50% { box-shadow: 0 0 35px rgba(34,197,94,0.8);} 
            100% { box-shadow: 0 0 15px rgba(34,197,94,0.5);} 
        }
        @keyframes pulse-red { 
            0% { box-shadow: 0 0 15px rgba(239,68,68,0.5);} 
            50% { box-shadow: 0 0 35px rgba(239,68,68,0.8);} 
            100% { box-shadow: 0 0 15px rgba(239,68,68,0.5);} 
        }
        .video-container { 
            position: relative; 
            width: 100%; 
            height: 0; 
            padding-bottom: 56.25%; 
            overflow: hidden; 
            background-color: #111; 
            border-radius: 0.5rem; 
        }
        .video-element { 
            position: absolute; 
            top:0; 
            left:0; 
            width:100%; 
            height:100%; 
            object-fit:cover; 
        }
        .canvas-element { 
            position:absolute; 
            top:0; 
            left:0; 
            width:100%; 
            height:100%; 
            display:none; 
        }
        .controls { 
            transition: all 0.3s ease; 
        }
        .controls:hover { 
            transform: translateY(-5px); 
        }
        .sensitivity-slider::-webkit-slider-thumb { 
            -webkit-appearance:none; 
            appearance:none; 
            width:20px; 
            height:20px; 
            border-radius:50%; 
            background:#f97316; 
            cursor:pointer; 
        }
        .source-toggle { 
            display:flex; 
            background-color:#374151; 
            border-radius:0.5rem; 
            padding:4px; 
        }
        .source-option { 
            padding:0.5rem 1rem; 
            border-radius:0.375rem; 
            cursor:pointer; 
            transition:all 0.3s ease; 
            color:#d1d5db; 
        }
        .source-option.active { 
            background-color:#f97316; 
            color:white; 
        }
        .worker-info-card {
            position: absolute;
            background-color: rgba(0, 0, 0, 0.8);
            border-radius: 8px;
            padding: 12px;
            width: 280px;
            z-index: 20;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 165, 0, 0.3);
            display: flex;
            align-items: center;
        }
        .worker-info-card.known-worker {
            border: 1px solid rgba(34, 197, 94, 0.5);
            background-color: rgba(0, 50, 0, 0.8);
        }
        .worker-info-card.unknown-face {
            border: 1px solid rgba(239, 68, 68, 0.5);
            background-color: rgba(50, 0, 0, 0.8);
        }
        .worker-info-card img {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            object-fit: cover;
            margin-right: 12px;
        }
        .worker-info-card .details {
            flex: 1;
        }
        .worker-info-card .name {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 4px;
            color: #fff;
        }
        .worker-info-card .info {
            font-size: 14px;
            color: #ddd;
            margin-bottom: 2px;
        }
        .worker-info-card .confidence {
            font-size: 12px;
            color: #f97316;
        }
    </style>
    <script>window.INIT_DATA = <?php echo json_encode($init, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG); ?>;</script>
    <script>

        function updateStatus(camera, detection, worker) {
            document.getElementById('cameraStatus').textContent = camera ? 'فعال' : 'خاموش';
            document.getElementById('cameraStatus').className = camera ? 'text-green-400' : 'text-red-400';
            
            document.getElementById('detectionStatus').textContent = detection ? 'فعال' : 'غیرفعال';
            document.getElementById('detectionStatus').className = detection ? 'text-green-400' : 'text-red-400';
            
            document.getElementById('detectedWorker').textContent = worker || 'هیچکدام';
            document.getElementById('detectedWorker').className = worker ? 'text-orange-400' : 'text-gray-400';
            

            const confirmationEl = document.getElementById('confirmationStatus');
            const confidenceEl = document.getElementById('confidenceLevel');
            const consecutiveEl = document.getElementById('consecutiveCount');
            
            if (confirmedWorker) {
                confirmationEl.textContent = `تایید شده: ${confirmedWorker.name}`;
                confirmationEl.className = 'text-green-400';
                
                const confidencePercent = Math.round(confidenceScore * 100);
                confidenceEl.textContent = `${confidencePercent}%`;
                confidenceEl.className = confidencePercent > 80 ? 'text-green-400' : 
                                        confidencePercent > 60 ? 'text-yellow-400' : 'text-orange-400';
                
                consecutiveEl.textContent = consecutiveDetections;
                consecutiveEl.className = consecutiveDetections > 5 ? 'text-green-400' : 'text-yellow-400';
            } else {
                confirmationEl.textContent = 'غیرفعال';
                confirmationEl.className = 'text-gray-400';
                confidenceEl.textContent = '0%';
                confidenceEl.className = 'text-gray-400';
                consecutiveEl.textContent = '0';
                consecutiveEl.className = 'text-gray-400';
            }
        }
        

        updateStatus(false, false, null);
    </script>
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <header class="mb-8">
            <div class="flex justify-between items-center mb-4">
                <a href="public/dashboard-new.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                    <i class="fas fa-arrow-left mr-2"></i>بازگشت به داشبورد
                </a>
                <a href="public/logout.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg flex items-center">
                    <i class="fas fa-sign-out-alt mr-2"></i>خروج
                </a>
            </div>
            <div class="text-center">
                <h1 class="text-4xl font-bold mb-2 bg-gradient-to-r from-orange-500 to-red-600 bg-clip-text text-transparent">
                    <i class="fas fa-hard-hat fa-fw mr-2"></i>سیستم تشخیص هوشمند
                </h1>
                <p class="text-gray-400"></p>
            </div>
        </header>

        <main class="max-w-4xl mx-auto">
            
            <div class="bg-gray-800 rounded-lg p-4 mb-6">
                <h3 class="text-lg font-semibold mb-3 text-gray-300">مرور فایل ها</h3>
                <div class="source-toggle">
                    <div id="cameraOption" class="source-option active">
                        <i class="fas fa-video mr-2"></i>دوربین مدار بسته
                    </div>
                    <div id="videoOption" class="source-option">
                        <i class="fas fa-file-video mr-2"></i>ویدیو
                    </div>
                </div>
                <div id="videoFileInput" class="mt-4 hidden">
                    <label for="videoUpload" class="block text-sm font-medium text-gray-400 mb-2">انتخاب فایل</label>
                    <input type="file" id="videoUpload" accept="video/*" class="block w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-orange-600 file:text-white hover:file:bg-orange-700 cursor-pointer">
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                <div class="lg:col-span-2">
                    <div class="video-container shadow-xl">
                        <video id="video" class="video-element" muted playsinline></video>
                        <canvas id="canvas" class="canvas-element"></canvas>
                        <div id="trackingContainer">
                            <div id="trackingCube" class="tracking-cube" style="display: none;"></div>
                            <div id="workerInfo" class="worker-info-card" style="display: none;">
                                <img id="workerImage" src="" alt="Worker">
                                <div class="details">
                                    <div id="workerName" class="name"></div>
                                    <div id="workerDetails" class="info"></div>
                                    <div id="workerConfidence" class="confidence"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-black rounded-lg p-4 h-fit">
                    <h3 class="text-orange-400 font-bold mb-4 text-center border-b border-orange-400 pb-2">
                        <i class="fas fa-database mr-2"></i>اطلاعات سیستم
                    </h3>
                    
                    <div class="mb-4">
                        <h4 class="text-white font-semibold mb-2 flex items-center">
                            <i class="fas fa-route mr-2 text-blue-400"></i>مسیرها (<?php echo count($paths); ?>)
                        </h4>
                        <div class="space-y-2 max-h-32 overflow-y-auto">
                            <?php foreach($paths as $path): ?>
                            <div class="bg-gray-800 p-2 rounded text-sm">
                                <div class="text-orange-300 font-medium"><?php echo htmlspecialchars($path['name']); ?></div>
                                <div class="text-gray-400 text-xs">
                                    موقعیت: x=<?php echo $path['x']; ?>, y=<?php echo $path['y']; ?><br>
                                    اندازه: <?php echo $path['width']; ?>×<?php echo $path['height']; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <h4 class="text-white font-semibold mb-2 flex items-center">
                            <i class="fas fa-users mr-2 text-green-400"></i>کارگران (<?php echo count($workers); ?>)
                        </h4>
                        <div class="space-y-2 max-h-40 overflow-y-auto">
                            <?php foreach($workers as $worker): ?>
                            <div class="bg-gray-800 p-2 rounded text-sm flex items-center">
                                <?php if($worker['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($worker['image_url']); ?>" class="w-8 h-8 rounded mr-2 object-cover">
                                <?php else: ?>
                                <div class="w-8 h-8 bg-gray-600 rounded mr-2 flex items-center justify-center">
                                    <i class="fas fa-user text-xs"></i>
                                </div>
                                <?php endif; ?>
                                <div class="flex-1">
                                    <div class="text-orange-300 font-medium"><?php echo htmlspecialchars($worker['name']); ?></div>
                                    <div class="text-gray-400 text-xs"><?php echo htmlspecialchars($worker['details']); ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="bg-gray-800 p-3 rounded">
                        <h4 class="text-white font-semibold mb-2 flex items-center">
                            <i class="fas fa-chart-line mr-2 text-yellow-400"></i>آمار لحظه‌ای
                        </h4>
                        <div class="text-sm space-y-1">
                            <div class="flex justify-between">
                                <span class="text-gray-400">وضعیت دوربین:</span>
                                <span id="cameraStatus" class="text-red-400">خاموش</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">تشخیص فعال:</span>
                                <span id="detectionStatus" class="text-red-400">غیرفعال</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">کارگر شناسایی شده:</span>
                                <span id="detectedWorker" class="text-gray-400">هیچ‌کدام</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">وضعیت تایید:</span>
                                <span id="confirmationStatus" class="text-gray-400">غیرفعال</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">میزان اعتماد:</span>
                                <span id="confidenceLevel" class="text-gray-400">0%</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">تشخیص‌های پیاپی:</span>
                                <span id="consecutiveCount" class="text-gray-400">0</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gray-800 rounded-lg p-6 mb-6">
                <h2 class="text-lg font-semibold mb-4 text-orange-400">تنظیمات بیشتر</h2>
                <p class="text-xs text-gray-400 mb-4">توصیه میشود تنظیمات را دست نزنید</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="flex flex-col">
                        <label for="minR" class="text-sm font-medium mb-2 flex items-center">
                            <i class="fas fa-tint mr-2 text-red-500"></i> محدوده قرمز رنگ
                        </label>
                        <div class="flex items-center space-x-2">
                            <input type="range" id="minR" min="0" max="255" value="150" class="sensitivity-slider w-full h-2 bg-gray-700 rounded-lg appearance-none cursor-pointer">
                            <input type="range" id="maxR" min="0" max="255" value="255" class="sensitivity-slider w-full h-2 bg-gray-700 rounded-lg appearance-none cursor-pointer">
                        </div>
                        <div class="flex justify-between text-xs text-gray-400 mt-1">
                            <span id="minGVal">50</span>
                            <span id="maxGVal">100</span>
                        </div>
                    </div>
                    <div class="flex flex-col">
                        <label for="minG" class="text-sm font-medium mb-2 flex items-center">
                            <i class="fas fa-tint mr-2 text-green-500"></i> محدوده سبز رنگ
                        </label>
                        <div class="flex items-center space-x-2">
                            <input type="range" id="minG" min="0" max="255" value="50" class="sensitivity-slider w-full h-2 bg-gray-700 rounded-lg appearance-none cursor-pointer">
                            <input type="range" id="maxG" min="0" max="255" value="180" class="sensitivity-slider w-full h-2 bg-gray-700 rounded-lg appearance-none cursor-pointer">
                        </div>
                        <div class="flex justify-between text-xs text-gray-400 mt-1">
                            <span id="minGVal">50</span>
                            <span id="maxGVal">180</span>
                        </div>
                    </div>
                    <div class="flex flex-col">
                        <label for="minB" class="text-sm font-medium mb-2 flex items-center">
                            <i class="fas fa-tint mr-2 text-blue-500"></i> محدوده آبی رنگ
                        </label>
                        <div class="flex items-center space-x-2">
                            <input type="range" id="minB" min="0" max="255" value="0" class="sensitivity-slider w-full h-2 bg-gray-700 rounded-lg appearance-none cursor-pointer">
                            <input type="range" id="maxB" min="0" max="255" value="100" class="sensitivity-slider w-full h-2 bg-gray-700 rounded-lg appearance-none cursor-pointer">
                        </div>
                        <div class="flex justify-between text-xs text-gray-400 mt-1">
                            <span id="minBVal">0</span>
                            <span id="maxBVal">100</span>
                        </div>
                    </div>
                     <div class="flex flex-col">
                        <label for="minPixelCount" class="text-sm font-medium mb-2 flex items-center">
                            <i class="fas fa-filter mr-2 text-yellow-500"></i> آستانه تشخیص
                        </label>
                        <input type="range" id="minPixelCount" min="10" max="500" value="100" class="sensitivity-slider w-full h-2 bg-gray-700 rounded-lg appearance-none cursor-pointer">
                        <div class="flex justify-between text-xs text-gray-400 mt-1">
                            <span>حساسیت کمتر</span>
                            <span>حساسیت بیشتر</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <button id="startBtn" class="bg-orange-600 hover:bg-orange-700 text-white font-bold py-3 px-4 rounded-lg flex items-center justify-center controls">
                    <i class="fas fa-play mr-2"></i> شروع تشخیص
                </button>
                <button id="scanBtn" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg flex items-center justify-center controls" disabled>
                    <i class="fas fa-magic mr-2"></i> تشخیص خودکار چهره
                </button>
                <button id="clearCacheBtn" class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 px-4 rounded-lg flex items-center justify-center controls" disabled>
                    <i class="fas fa-refresh mr-2"></i> پاک کردن تایید
                </button>
                <button id="stopBtn" class="bg-gray-700 hover:bg-gray-600 text-white font-bold py-3 px-4 rounded-lg flex items-center justify-center controls" disabled>
                    <i class="fas fa-stop mr-2"></i> توقف تشخیص
                </button>
            </div>

            <div class="bg-gray-800 rounded-lg p-6">
                <h2 class="text-xl font-semibold mb-4 flex items-center">
                    <i class="fas fa-chart-bar mr-2 text-orange-400"></i> ایستگاه تشخیص

                </h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="bg-gray-700 p-4 rounded-lg text-center">
                        <div class="text-3xl font-bold text-orange-400" id="colorPixelCount">0</div>
                        <div class="text-sm text-gray-400">پیکسل‌های نارنجی
                            </div>
                    </div>
                    <div class="bg-gray-700 p-4 rounded-lg text-center">
                        <div class="text-3xl font-bold text-green-400" id="colorCoverage">0%</div>
                        <div class="text-sm text-gray-400">پوشش صفحه نمایش
                        </div>
                    </div>
                    <div class="bg-gray-700 p-4 rounded-lg text-center">
                        <div class="text-3xl font-bold text-purple-400" id="frameRate">0</div>
                        <div class="text-sm text-gray-400">FPS</div>
                    </div>
                    <div class="bg-gray-700 p-4 rounded-lg text-center">
                        <div class="text-3xl font-bold text-yellow-400" id="detectionTime">0ms</div>
                        <div class="text-sm text-gray-400">زمان پردازش</div>
                    </div>
                </div>
            </div>
        </main>

    
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const video = document.getElementById('video');
            const canvas = document.getElementById('canvas');
            const ctx = canvas.getContext('2d');
            const trackingCube = document.getElementById('trackingCube');
            const workerInfo = document.getElementById('workerInfo');
            const workerImage = document.getElementById('workerImage');
            const workerName = document.getElementById('workerName');
            const workerDetails = document.getElementById('workerDetails');
            const workerConfidence = document.getElementById('workerConfidence');
            const startBtn = document.getElementById('startBtn');
            const stopBtn = document.getElementById('stopBtn');
            

            const cameraOption = document.getElementById('cameraOption');
            const videoOption = document.getElementById('videoOption');
            const videoFileInputDiv = document.getElementById('videoFileInput');
            const videoUpload = document.getElementById('videoUpload');


            const minRSlider = document.getElementById('minR');
            const maxRSlider = document.getElementById('maxR');
            const minGSlider = document.getElementById('minG');
            const maxGSlider = document.getElementById('maxG');
            const minBSlider = document.getElementById('minB');
            const maxBSlider = document.getElementById('maxB');
            const minPixelCountSlider = document.getElementById('minPixelCount');


            const minRVal = document.getElementById('minRVal');
            const maxRVal = document.getElementById('maxRVal');
            const minGVal = document.getElementById('minGVal');
            const maxGVal = document.getElementById('maxGVal');
            const minBVal = document.getElementById('minBVal');
            const maxBVal = document.getElementById('maxBVal');


            const colorPixelCountEl = document.getElementById('colorPixelCount');
            const colorCoverageEl = document.getElementById('colorCoverage');
            const frameRateEl = document.getElementById('frameRate');
            const detectionTimeEl = document.getElementById('detectionTime');


            let stream = null;
            let isDetecting = false;
            let animationId = null;
            let frameCount = 0;
            let lastFpsUpdate = 0;
            let fps = 0;
            let inputMode = 'camera';
            

            let colorSettings = {
                minR: parseInt(minRSlider.value),
                maxR: parseInt(maxRSlider.value),
                minG: parseInt(minGSlider.value),
                maxG: parseInt(maxGSlider.value),
                minB: parseInt(minBSlider.value),
                maxB: parseInt(maxBSlider.value),
                minPixelCount: parseInt(minPixelCountSlider.value),
                cubeSize: 150,
                scanResolution: 5
            };


            let paths = [];
            let workers = [];
            
            let confirmedWorker = null;
            let confirmedWorkerStartTime = null;
            let lastConfirmedPosition = null;
            let isWorkerInFrame = false;
            let workerMotionHistory = [];
            let confidenceScore = 0;
            let consecutiveDetections = 0;
            
            const CONFIRMATION_TIMEOUT = 3000;
            const POSITION_THRESHOLD = 120;
            const MOTION_HISTORY_LENGTH = 10;
            const MIN_CONFIDENCE_THRESHOLD = 0.7;
            const CONFIDENCE_DECAY_RATE = 0.95;
            

            function predictNextPosition(history) {
                if (history.length < 3) return null;
                
                const recent = history.slice(-3);
                const velocityX = (recent[2].x - recent[0].x) / 2;
                const velocityY = (recent[2].y - recent[0].y) / 2;
                
                return {
                    x: recent[2].x + velocityX,
                    y: recent[2].y + velocityY,
                    confidence: Math.min(1, history.length / MOTION_HISTORY_LENGTH)
                };
            }
            

            function isWorkerInExpectedPosition(currentX, currentY) {
                if (!lastConfirmedPosition) return false;
                

                const directDistance = Math.sqrt(
                    Math.pow(currentX - lastConfirmedPosition.x, 2) + 
                    Math.pow(currentY - lastConfirmedPosition.y, 2)
                );
                
                if (directDistance <= POSITION_THRESHOLD) {
                    return { match: true, confidence: 1 - (directDistance / POSITION_THRESHOLD) };
                }
                

                const prediction = predictNextPosition(workerMotionHistory);
                if (prediction) {
                    const predictedDistance = Math.sqrt(
                        Math.pow(currentX - prediction.x, 2) + 
                        Math.pow(currentY - prediction.y, 2)
                    );
                    
                    if (predictedDistance <= POSITION_THRESHOLD * 1.5) {
                        return { 
                            match: true, 
                            confidence: (1 - (predictedDistance / (POSITION_THRESHOLD * 1.5))) * prediction.confidence 
                        };
                    }
                }
                
                return { match: false, confidence: 0 };
            }
            
            function updateMotionHistory(x, y) {
                const timestamp = Date.now();
                workerMotionHistory.push({ x, y, timestamp });
                
                if (workerMotionHistory.length > MOTION_HISTORY_LENGTH) {
                    workerMotionHistory.shift();
                }
            }
            
            function calculateConfidence(faceDistance, motionStability, detectionConsistency) {
                const faceConfidence = faceDistance ? (1 - faceDistance) : 0.5;
                const motionConfidence = motionStability || 0.5;
                const consistencyBonus = Math.min(1, detectionConsistency / 5) * 0.2;
                
                return Math.min(1, faceConfidence * 0.6 + motionConfidence * 0.3 + consistencyBonus);
            }

            function initializeData() {
                if (window.INIT_DATA) {
                    paths = window.INIT_DATA.paths || [];
                    workers = window.INIT_DATA.workers || [];
                    
                    workers.forEach(worker => {
                        if (worker.image_url) {
                            const img = new Image();
                            img.src = worker.image_url;
                            worker.img = img;
                        }
                    });
                }
            }

            let faceModelsLoaded = false;
            let faceMatcher = null;
            let labeledDescriptors = [];

            const faceRecognitionConfig = {
                detectorOptions: {
                    tiny: new faceapi.TinyFaceDetectorOptions({ 
                        inputSize: 416,
                        scoreThreshold: 0.3
                    }),
                    ssd: new faceapi.SsdMobilenetv1Options({
                        minConfidence: 0.3,
                        maxResults: 5
                    })
                },
                matchThreshold: 0.45,
                multipleDescriptors: true,
                qualityThreshold: 0.8
            };

            async function loadFaceModelsAndDescriptors(){
                try{
                    console.log('Loading face recognition models...');
                    
                    await Promise.all([
                        faceapi.nets.tinyFaceDetector.loadFromUri('https://justadudewhohacks.github.io/face-api.js/models'),
                        faceapi.nets.ssdMobilenetv1.loadFromUri('https://justadudewhohacks.github.io/face-api.js/models'),
                        faceapi.nets.faceLandmark68Net.loadFromUri('https://justadudewhohacks.github.io/face-api.js/models'),
                        faceapi.nets.faceRecognitionNet.loadFromUri('https://justadudewhohacks.github.io/face-api.js/models'),
                        faceapi.nets.ageGenderNet.loadFromUri('https://justadudewhohacks.github.io/face-api.js/models')
                    ]);
                    
                    console.log('Models loaded, building descriptors...');
                    
                    for(const w of workers){
                        if(!w.image_url || !w.img) continue;
                        
                        const descriptors = [];
                        
                        try{
                            const detections = await Promise.all([
                                faceapi.detectSingleFace(w.img, faceRecognitionConfig.detectorOptions.tiny)
                                    .withFaceLandmarks().withFaceDescriptor(),
                                faceapi.detectSingleFace(w.img, faceRecognitionConfig.detectorOptions.ssd)
                                    .withFaceLandmarks().withFaceDescriptor()
                            ]);
                            
                            for(const detection of detections) {
                                if(detection && detection.descriptor) {
                                    const quality = detection.landmarks ? 
                                        detection.landmarks.positions.length / 68 : 0.5;
                                    
                                    if(quality >= faceRecognitionConfig.qualityThreshold) {
                                        descriptors.push(detection.descriptor);
                                    }
                                }
                            }
                            
                            if(descriptors.length > 0) {
                                const baseDescriptor = descriptors[0];
                                for(let i = 0; i < 2; i++) {
                                    const augmented = new Float32Array(baseDescriptor.length);
                                    for(let j = 0; j < baseDescriptor.length; j++) {
                                        augmented[j] = baseDescriptor[j] + (Math.random() - 0.5) * 0.01;
                                    }
                                    descriptors.push(augmented);
                                }
                            }
                            
                            if(descriptors.length > 0) {
                                labeledDescriptors.push(new faceapi.LabeledFaceDescriptors(String(w.id), descriptors));
                                console.log(`Generated ${descriptors.length} descriptors for worker ${w.name}`);
                            }
                            
                        }catch(e){ 
                            console.warn('Worker image processing failed:', w.id, w.name, e); 
                        }
                    }
                    
                    if(labeledDescriptors.length) {
                        faceMatcher = new faceapi.FaceMatcher(labeledDescriptors, faceRecognitionConfig.matchThreshold);
                        console.log(`Face matcher created with ${labeledDescriptors.length} workers`);
                    }
                    
                    faceModelsLoaded = true;
                    console.log('Enhanced face recognition system ready!');
                    
                }catch(e){ 
                    console.error('Face recognition models failed to load:', e); 
                    faceModelsLoaded = false; 
                }
            }

            async function matchFaceInBox(box){
                if(!faceModelsLoaded || !faceMatcher) return null;
                
                try{
                    const padding = 20;
                    const oc = document.createElement('canvas'); 
                    oc.width = box.w + padding * 2; 
                    oc.height = box.h + padding * 2; 
                    const ocCtx = oc.getContext('2d');
                    
                    ocCtx.filter = 'contrast(1.2) brightness(1.1)';
                    ocCtx.drawImage(video, 
                        Math.max(0, box.x - padding), 
                        Math.max(0, box.y - padding), 
                        box.w + padding * 2, 
                        box.h + padding * 2, 
                        0, 0, oc.width, oc.height
                    );
                    
                    const detections = await Promise.all([
                        faceapi.detectSingleFace(oc, faceRecognitionConfig.detectorOptions.tiny)
                            .withFaceLandmarks().withFaceDescriptor(),
                        faceapi.detectSingleFace(oc, faceRecognitionConfig.detectorOptions.ssd)
                            .withFaceLandmarks().withFaceDescriptor()
                    ]);
                    
                    const matches = [];
                    
                    for(const detection of detections) {
                        if(detection && detection.descriptor){
                            const best = faceMatcher.findBestMatch(detection.descriptor);
                            if(best && best.label && best.distance < faceRecognitionConfig.matchThreshold){
                                matches.push({ 
                                    id: best.label, 
                                    distance: best.distance,
                                    confidence: 1 - best.distance,
                                    landmarks: detection.landmarks
                                });
                            }
                        }
                    }
                    
                    if(matches.length > 0) {
                        matches.sort((a, b) => a.distance - b.distance);
                        const bestMatch = matches[0];
                        
                        console.log(`Face matched: ID=${bestMatch.id}, confidence=${(bestMatch.confidence * 100).toFixed(1)}%`);
                        return bestMatch;
                    }
                    
                }catch(e){ 
                    console.warn('Face matching failed:', e); 
                }
                
                return null;
            }
            
            function preprocessImageForDetection(canvas, ctx) {
                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const data = imageData.data;
                
                for(let i = 0; i < data.length; i += 4) {
                    const avg = (data[i] + data[i+1] + data[i+2]) / 3;
                    const factor = 1.2;
                    data[i] = Math.min(255, data[i] * factor);
                    data[i+1] = Math.min(255, data[i+1] * factor);
                    data[i+2] = Math.min(255, data[i+2] * factor);
                }
                
                ctx.putImageData(imageData, 0, 0);
            }

            initializeData();
            loadFaceModelsAndDescriptors();

            cameraOption.addEventListener('click', () => {
                inputMode = 'camera';
                cameraOption.classList.add('active');
                videoOption.classList.remove('active');
                videoFileInputDiv.classList.add('hidden');
                resetVideo();
            });

            videoOption.addEventListener('click', () => {
                inputMode = 'video';
                videoOption.classList.add('active');
                cameraOption.classList.remove('active');
                videoFileInputDiv.classList.remove('hidden');
                resetVideo();
            });
            
            function resetVideo() {
                stopTracking();
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                    stream = null;
                }
                video.srcObject = null;
                video.src = '';
                video.load();
            }

            videoUpload.addEventListener('change', (e) => {
                const file = e.target.files[0];
                if (file) {
                    const fileURL = URL.createObjectURL(file);
                    video.src = fileURL;
                    video.load();
                }
            });

            const setupSliderListener = (slider, valueDisplay, settingKey) => {
                slider.addEventListener('input', () => {
                    const value = parseInt(slider.value);
                    colorSettings[settingKey] = value;
                    if (valueDisplay) valueDisplay.textContent = value;
                });
            };

            setupSliderListener(minRSlider, minRVal, 'minR');
            setupSliderListener(maxRSlider, maxRVal, 'maxR');
            setupSliderListener(minGSlider, minGVal, 'minG');
            setupSliderListener(maxGSlider, maxGVal, 'maxG');
            setupSliderListener(minBSlider, minBVal, 'minB');
            setupSliderListener(maxBSlider, maxBVal, 'maxB');
            setupSliderListener(minPixelCountSlider, null, 'minPixelCount');

            async function logActivity(eventType, description, workerId = null, coordinates = null) {
                try {
                    await fetch('public/log_activity.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({
                            event_type: eventType,
                            description: description,
                            worker_id: workerId,
                            coordinates: coordinates
                        })
                    });
                } catch (e) {
                    console.error('Logging failed:', e);
                }
            }
            
            startBtn.addEventListener('click', async () => {
                await logActivity('CAMERA_STARTED', 'دوربین روشن شد');
                if (inputMode === 'camera') {
                    try {
                        stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
                        video.srcObject = stream;
                        await video.play();
                    } catch (err) {
                        console.error('Error accessing camera:', err);
                        alert('Could not access the camera. Please make sure you have granted camera permissions.');
                        return;
                    }
                } else if (inputMode === 'video') {
                    if (!video.src) {
                        alert('Please select a video file first.');
                        return;
                    }
                    await video.play();
                }
                
                if (canvas.width !== video.videoWidth || canvas.height !== video.videoHeight) {
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                }

                isDetecting = true;
                startBtn.disabled = true;
                document.getElementById('scanBtn').disabled = false;
                document.getElementById('clearCacheBtn').disabled = false;
                stopBtn.disabled = false;
                startBtn.classList.remove('bg-orange-600', 'hover:bg-orange-700');
                startBtn.classList.add('bg-orange-900', 'cursor-not-allowed');
                stopBtn.classList.remove('bg-gray-700', 'cursor-not-allowed');
                stopBtn.classList.add('bg-red-600', 'hover:bg-red-700');
                
                startDetection();
            });
            
            function stopTracking() {
                logActivity('CAMERA_STOPPED', 'دوربین خاموش شد');
                isDetecting = false;
                if (animationId) {
                    cancelAnimationFrame(animationId);
                    animationId = null;
                }
                video.pause();
                
                startBtn.disabled = false;
                document.getElementById('scanBtn').disabled = true;
                document.getElementById('clearCacheBtn').disabled = true;
                stopBtn.disabled = true;
                startBtn.classList.add('bg-orange-600', 'hover:bg-orange-700');
                startBtn.classList.remove('bg-orange-900', 'cursor-not-allowed');
                stopBtn.classList.add('bg-gray-700', 'cursor-not-allowed');
                stopBtn.classList.remove('bg-red-600', 'hover:bg-red-700');
                
                trackingCube.style.display = 'none';
                workerInfo.style.display = 'none';
                colorPixelCountEl.textContent = '0';
                colorCoverageEl.textContent = '0%';
            }
            stopBtn.addEventListener('click', stopTracking);
            
            // Work time tracking variables
            let activeWorkerSessions = new Map(); // workerId -> startTime
            
            // Scan button functionality - Now shows automatic face recognition status
            document.getElementById('scanBtn').addEventListener('click', async () => {
                if (!isDetecting) {
                    alert('ابتدا تشخیص را شروع کنید');
                    return;
                }
                
                let statusMessage = 'تشخیص خودکار چهره فعال است!\n\n';
                statusMessage += `مدل‌های تشخیص چهره: ${faceModelsLoaded ? 'بارگذاری شده' : 'در حال بارگذاری'}\n`;
                statusMessage += `تعداد چهره‌های ثبت شده: ${labeledDescriptors.length}\n`;
                
                if (confirmedWorker) {
                    statusMessage += `\n✅ کارگر تایید شده: ${confirmedWorker.name}\n`;
                    statusMessage += `زمان تایید: ${new Date(confirmedWorkerStartTime).toLocaleTimeString('fa-IR')}\n`;
                    statusMessage += 'برای پاک کردن تایید از دکمه "پاک کردن تایید" استفاده کنید.';
                } else {
                    statusMessage += '\n⏳ هیچ کارگری تایید نشده است.';
                }
                
                alert(statusMessage);
            });
            
            // Clear cache button functionality
            document.getElementById('clearCacheBtn').addEventListener('click', async () => {
                if (confirmedWorker) {
                    const confirmed = confirm(`آیا مطمئن هستید که می‌خواهید تایید کارگر "${confirmedWorker.name}" را پاک کنید؟\n\nپس از پاک کردن، سیستم مجدداً باید چهره را تشخیص دهد.`);
                    if (confirmed) {
                        await logActivity('CONFIRMATION_CLEARED', `تایید کارگر ${confirmedWorker.name} پاک شد`, confirmedWorker.id);
                        
                        confirmedWorker = null;
                        confirmedWorkerStartTime = null;
                        lastConfirmedPosition = null;
                        isWorkerInFrame = false;
                        
                        updateStatus(true, isDetecting, null);
                        alert('تایید کارگر پاک شد. سیستم مجدداً تشخیص چهره را انجام خواهد داد.');
                    }
                } else {
                    alert('هیچ کارگری تایید نشده است.');
                }
            });
            
            // Handle work time logging
            async function handleWorkTimeLogging(worker) {
                const workerId = worker.id;
                const isActive = activeWorkerSessions.has(workerId);
                
                if (isActive) {
                    // End work session
                    const confirmed = confirm(`پایان کار ${worker.name}؟`);
                    if (confirmed) {
                        try {
                            const response = await fetch('public/log_work_time.php', {
                                method: 'POST',
                                headers: {'Content-Type': 'application/json'},
                                body: JSON.stringify({worker_id: workerId, action: 'end'})
                            });
                            const result = await response.json();
                            if (result.success) {
                                activeWorkerSessions.delete(workerId);
                                alert(`کار ${worker.name} پایان یافت`);
                            }
                        } catch (e) {
                            console.error('Error ending work session:', e);
                        }
                    }
                } else {
                    // Start work session
                    const confirmed = confirm(`شروع کار ${worker.name}؟`);
                    if (confirmed) {
                        try {
                            const response = await fetch('public/log_work_time.php', {
                                method: 'POST',
                                headers: {'Content-Type': 'application/json'},
                                body: JSON.stringify({worker_id: workerId, action: 'start'})
                            });
                            const result = await response.json();
                            if (result.success) {
                                activeWorkerSessions.set(workerId, new Date());
                                alert(`کار ${worker.name} شروع شد`);
                            }
                        } catch (e) {
                            console.error('Error starting work session:', e);
                        }
                    }
                }
            }

            // Stop tracking when video ends
            video.addEventListener('ended', () => {
                stopTracking();
            });

            // Main detection function
            function startDetection() {
                if (!isDetecting || video.paused || video.ended) return;
                
                const startTime = performance.now();
                
                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                const currentFrame = ctx.getImageData(0, 0, canvas.width, canvas.height);
                
                const colorData = detectColor(currentFrame);
                updateTrackingUI(colorData);
                
                // Calculate FPS
                frameCount++;
                const now = performance.now();
                if (now - lastFpsUpdate >= 1000) {
                    fps = Math.round((frameCount * 1000) / (now - lastFpsUpdate));
                    frameCount = 0;
                    lastFpsUpdate = now;
                    frameRateEl.textContent = fps;
                }
                
                // Update processing time
                const processingTime = performance.now() - startTime;
                detectionTimeEl.textContent = `${Math.round(processingTime)}ms`;
                
                animationId = requestAnimationFrame(startDetection);
            }
            
            // Enhanced color detection algorithm with multiple methods
            function detectColor(frame) {
                const data = frame.data;
                const width = frame.width;
                const height = frame.height;
                
                // Multi-pass detection for better accuracy
                const detectionMethods = [
                    detectColorRGB,
                    detectColorHSV,
                    detectColorLAB
                ];
                
                let bestResult = null;
                let maxConfidence = 0;
                
                for(const method of detectionMethods) {
                    const result = method(data, width, height);
                    if(result.confidence > maxConfidence) {
                        maxConfidence = result.confidence;
                        bestResult = result;
                    }
                }
                
                return bestResult || {
                    found: false, 
                    centerX: 0, 
                    centerY: 0, 
                    colorPixelCount: 0, 
                    width, 
                    height, 
                    bbox: null,
                    confidence: 0
                };
            }
            
            // RGB-based color detection (original method, enhanced)
            function detectColorRGB(data, width, height) {
                let sumX = 0, sumY = 0, colorPixelCount = 0;
                let minX = width, minY = height, maxX = 0, maxY = 0;
                let totalIntensity = 0;
                
                // Adaptive scanning resolution based on frame size
                const adaptiveResolution = Math.max(1, Math.floor(Math.min(width, height) / 200));
                
                for (let y = 0; y < height; y += adaptiveResolution) {
                    for (let x = 0; x < width; x += adaptiveResolution) {
                        const pos = (y * width + x) * 4;
                        const r = data[pos];
                        const g = data[pos + 1];
                        const b = data[pos + 2];
                        
                        // Enhanced color matching with tolerance
                        const rMatch = r >= colorSettings.minR && r <= colorSettings.maxR;
                        const gMatch = g >= colorSettings.minG && g <= colorSettings.maxG;
                        const bMatch = b >= colorSettings.minB && b <= colorSettings.maxB;
                        
                        if (rMatch && gMatch && bMatch) {
                            // Weight pixels by color intensity (closer to target = higher weight)
                            const targetR = (colorSettings.minR + colorSettings.maxR) / 2;
                            const targetG = (colorSettings.minG + colorSettings.maxG) / 2;
                            const targetB = (colorSettings.minB + colorSettings.maxB) / 2;
                            
                            const colorDistance = Math.sqrt(
                                Math.pow(r - targetR, 2) + 
                                Math.pow(g - targetG, 2) + 
                                Math.pow(b - targetB, 2)
                            );
                            
                            const weight = Math.max(0.1, 1 - (colorDistance / 255));
                            
                            sumX += x * weight;
                            sumY += y * weight;
                            colorPixelCount += weight;
                            totalIntensity += (r + g + b) / 3;
                            
                            if (x < minX) minX = x;
                            if (y < minY) minY = y;
                            if (x > maxX) maxX = x;
                            if (y > maxY) maxY = y;
                        }
                    }
                }
                
                if (colorPixelCount > colorSettings.minPixelCount) {
                    const centerX = Math.round(sumX / colorPixelCount);
                    const centerY = Math.round(sumY / colorPixelCount);
                    const avgIntensity = totalIntensity / colorPixelCount;
                    
                    // Calculate confidence based on pixel count and intensity
                    const confidence = Math.min(1, (colorPixelCount / (width * height * 0.01)) * (avgIntensity / 255));
                    
                    return {
                        found: true,
                        centerX,
                        centerY,
                        colorPixelCount: Math.round(colorPixelCount),
                        width,
                        height,
                        bbox: { 
                            x: Math.max(0, minX - 10), 
                            y: Math.max(0, minY - 10), 
                            w: Math.min(width, maxX - minX + 20), 
                            h: Math.min(height, maxY - minY + 20) 
                        },
                        confidence
                    };
                }
                
                return { found: false, confidence: 0 };
            }
            
            // HSV-based color detection for better color accuracy
            function detectColorHSV(data, width, height) {
                let sumX = 0, sumY = 0, colorPixelCount = 0;
                let minX = width, minY = height, maxX = 0, maxY = 0;
                
                // Convert RGB target to HSV
                const targetHSV = rgbToHsv(
                    (colorSettings.minR + colorSettings.maxR) / 2,
                    (colorSettings.minG + colorSettings.maxG) / 2,
                    (colorSettings.minB + colorSettings.maxB) / 2
                );
                
                for (let y = 0; y < height; y += 3) {
                    for (let x = 0; x < width; x += 3) {
                        const pos = (y * width + x) * 4;
                        const r = data[pos];
                        const g = data[pos + 1];
                        const b = data[pos + 2];
                        
                        const hsv = rgbToHsv(r, g, b);
                        
                        // HSV-based matching (more robust for color variations)
                        const hDiff = Math.abs(hsv.h - targetHSV.h);
                        const sDiff = Math.abs(hsv.s - targetHSV.s);
                        const vDiff = Math.abs(hsv.v - targetHSV.v);
                        
                        if (hDiff < 30 && sDiff < 0.3 && vDiff < 0.3) {
                            sumX += x;
                            sumY += y;
                            colorPixelCount++;
                            
                            if (x < minX) minX = x;
                            if (y < minY) minY = y;
                            if (x > maxX) maxX = x;
                            if (y > maxY) maxY = y;
                        }
                    }
                }
                
                if (colorPixelCount > colorSettings.minPixelCount * 0.8) {
                    return {
                        found: true,
                        centerX: Math.round(sumX / colorPixelCount),
                        centerY: Math.round(sumY / colorPixelCount),
                        colorPixelCount,
                        width,
                        height,
                        bbox: { 
                            x: Math.max(0, minX - 8), 
                            y: Math.max(0, minY - 8), 
                            w: Math.min(width, maxX - minX + 16), 
                            h: Math.min(height, maxY - minY + 16) 
                        },
                        confidence: Math.min(1, colorPixelCount / (width * height * 0.005))
                    };
                }
                
                return { found: false, confidence: 0 };
            }
            
            // LAB color space detection for perceptual accuracy
            function detectColorLAB(data, width, height) {
                // Simplified LAB detection (focusing on luminance and color differences)
                let sumX = 0, sumY = 0, colorPixelCount = 0;
                let minX = width, minY = height, maxX = 0, maxY = 0;
                
                for (let y = 0; y < height; y += 4) {
                    for (let x = 0; x < width; x += 4) {
                        const pos = (y * width + x) * 4;
                        const r = data[pos];
                        const g = data[pos + 1];
                        const b = data[pos + 2];
                        
                        // Simple LAB-like calculation
                        const l = 0.299 * r + 0.587 * g + 0.114 * b;
                        const a = (r - g) / 2;
                        const b_lab = (r + g - 2 * b) / 4;
                        
                        // Target LAB values
                        const targetL = 0.299 * ((colorSettings.minR + colorSettings.maxR) / 2) + 
                                       0.587 * ((colorSettings.minG + colorSettings.maxG) / 2) + 
                                       0.114 * ((colorSettings.minB + colorSettings.maxB) / 2);
                        
                        const lDiff = Math.abs(l - targetL);
                        
                        if (lDiff < 50 && Math.abs(a) < 30 && Math.abs(b_lab) < 30) {
                            sumX += x;
                            sumY += y;
                            colorPixelCount++;
                            
                            if (x < minX) minX = x;
                            if (y < minY) minY = y;
                            if (x > maxX) maxX = x;
                            if (y > maxY) maxY = y;
                        }
                    }
                }
                
                if (colorPixelCount > colorSettings.minPixelCount * 0.6) {
                    return {
                        found: true,
                        centerX: Math.round(sumX / colorPixelCount),
                        centerY: Math.round(sumY / colorPixelCount),
                        colorPixelCount,
                        width,
                        height,
                        bbox: { 
                            x: Math.max(0, minX - 6), 
                            y: Math.max(0, minY - 6), 
                            w: Math.min(width, maxX - minX + 12), 
                            h: Math.min(height, maxY - minY + 12) 
                        },
                        confidence: Math.min(1, colorPixelCount / (width * height * 0.003))
                    };
                }
                
                return { found: false, confidence: 0 };
            }
            
            // Helper function: RGB to HSV conversion
            function rgbToHsv(r, g, b) {
                r /= 255; g /= 255; b /= 255;
                const max = Math.max(r, g, b);
                const min = Math.min(r, g, b);
                const diff = max - min;
                
                let h = 0;
                if (diff !== 0) {
                    if (max === r) h = ((g - b) / diff) % 6;
                    else if (max === g) h = (b - r) / diff + 2;
                    else h = (r - g) / diff + 4;
                }
                h = Math.round(h * 60);
                if (h < 0) h += 360;
                
                const s = max === 0 ? 0 : diff / max;
                const v = max;
                
                return { h, s, v };
            }
            
            // Update UI with color data (integrated with automatic face recognition)
            async function updateTrackingUI(colorData) {
                const { found, centerX, centerY, colorPixelCount, width, height, bbox } = colorData;
                
                // Update statistics
                colorPixelCountEl.textContent = colorPixelCount;
                const totalPixelsScanned = Math.ceil(width / colorSettings.scanResolution) * Math.ceil(height / colorSettings.scanResolution);
                const coverage = totalPixelsScanned > 0 ? Math.round((colorPixelCount / totalPixelsScanned) * 100) : 0;
                colorCoverageEl.textContent = `${coverage}%`;
                
                // Update tracking cube
                if (found) {
                    trackingCube.style.display = 'block';
                    const cubeSizePercent = (colorSettings.cubeSize / width) * 100;
                    const leftPercent = ((centerX - colorSettings.cubeSize/2) / width) * 100;
                    const topPercent = ((centerY - colorSettings.cubeSize/2) / height) * 100;
                    
                    trackingCube.style.width = `${cubeSizePercent}%`;
                    trackingCube.style.height = `${cubeSizePercent * (height/width)}%`;
                    trackingCube.style.left = `${leftPercent}%`;
                    trackingCube.style.top = `${topPercent}%`;
                    
                    // Log detection event
                    const coords = `X:${centerX}, Y:${centerY}`;
                    logActivity('MOTION_DETECTED', 'حرکت تشخیص داده شد', null, coords);
                    
                    // Enhanced worker tracking with confidence-based caching
                    let matchedWorker = null;
                    const currentTime = Date.now();
                    
                    // Decay confidence over time
                    if (confirmedWorker) {
                        confidenceScore *= CONFIDENCE_DECAY_RATE;
                    }
                    
                    if (confirmedWorker && lastConfirmedPosition) {
                        const positionCheck = isWorkerInExpectedPosition(centerX, centerY);
                        
                        if (positionCheck.match && confidenceScore > MIN_CONFIDENCE_THRESHOLD) {
                            // Worker is in expected position and confidence is high enough
                            matchedWorker = confirmedWorker;
                            isWorkerInFrame = true;
                            consecutiveDetections++;
                            
                            // Update motion history and position
                            updateMotionHistory(centerX, centerY);
                            lastConfirmedPosition = { x: centerX, y: centerY };
                            
                            // Boost confidence for consistent detections
                            const motionStability = workerMotionHistory.length >= 3 ? 
                                1 - (Math.abs(workerMotionHistory[workerMotionHistory.length-1].x - workerMotionHistory[0].x) / width) : 0.5;
                            
                            confidenceScore = Math.min(1, confidenceScore + 0.05); // Gradual confidence boost
                            
                            displayWorkerInfo(matchedWorker, centerX, centerY, null, 'known');
                            updateStatus(true, true, matchedWorker.name);
                            
                            console.log(`Using cached confirmation for ${matchedWorker.name} (confidence: ${(confidenceScore*100).toFixed(1)}%, detections: ${consecutiveDetections})`);
                        } else {
                            // Worker moved too far or confidence too low, clear cache
                            const reason = !positionCheck.match ? 'position' : 'confidence';
                            console.log(`Clearing cache due to ${reason} (confidence: ${(confidenceScore*100).toFixed(1)}%)`);
                            
                            confirmedWorker = null;
                            confirmedWorkerStartTime = null;
                            lastConfirmedPosition = null;
                            isWorkerInFrame = false;
                            workerMotionHistory = [];
                            confidenceScore = 0;
                            consecutiveDetections = 0;
                        }
                    }
                    
                    // AUTOMATIC FACE RECOGNITION - Only if no confirmed worker in cache
                    
                    // Enhanced face recognition with multi-stage verification
                    if (!matchedWorker && bbox && faceModelsLoaded && faceMatcher) {
                        try {
                            const faceMatch = await matchFaceInBox(bbox);
                            if (faceMatch && faceMatch.confidence > 0.6) {
                                matchedWorker = workers.find(w => String(w.id) === String(faceMatch.id));
                                if (matchedWorker) {
                                    // Calculate initial confidence
                                    const initialConfidence = calculateConfidence(
                                        faceMatch.distance, 
                                        0.5, // No motion history yet
                                        1 // First detection
                                    );
                                    
                                    // Only cache if confidence is high enough
                                    if (initialConfidence > MIN_CONFIDENCE_THRESHOLD) {
                                        confirmedWorker = matchedWorker;
                                        confirmedWorkerStartTime = currentTime;
                                        lastConfirmedPosition = { x: centerX, y: centerY };
                                        isWorkerInFrame = true;
                                        confidenceScore = initialConfidence;
                                        consecutiveDetections = 1;
                                        
                                        // Initialize motion history
                                        workerMotionHistory = [{ x: centerX, y: centerY, timestamp: currentTime }];
                                        
                                        displayWorkerInfo(matchedWorker, centerX, centerY, faceMatch.distance, 'known');
                                        updateStatus(true, true, matchedWorker.name);
                                        
                                        // Log successful face recognition with confidence
                                        await logActivity('FACE_RECOGNIZED', 
                                            `چهره ${matchedWorker.name} تشخیص داده شد (اعتماد: ${(initialConfidence*100).toFixed(1)}%)`, 
                                            matchedWorker.id, coords);
                                        console.log(`Face confirmed and cached: ${matchedWorker.name} (confidence: ${(initialConfidence*100).toFixed(1)}%)`);
                                    } else {
                                        console.log(`Face match found but confidence too low: ${matchedWorker.name} (${(initialConfidence*100).toFixed(1)}%)`);
                                    }
                                }
                            }
                        } catch (e) {
                            console.warn('Face matching failed:', e);
                        }
                    }
                    
                    // Secondary: Enhanced face recognition with full comparison
                    let unknownFaceDetected = false;
                    if (!matchedWorker && bbox && faceModelsLoaded) {
                        try {
                            const faceCanvas = document.createElement('canvas');
                            const faceCtx = faceCanvas.getContext('2d');
                            faceCanvas.width = bbox.w;
                            faceCanvas.height = bbox.h;
                            faceCtx.drawImage(video, bbox.x, bbox.y, bbox.w, bbox.h, 0, 0, bbox.w, bbox.h);
                            
                            const detection = await faceapi.detectSingleFace(faceCanvas, new faceapi.TinyFaceDetectorOptions())
                                .withFaceLandmarks()
                                .withFaceDescriptor();
                            
                            if (detection) {
                                // Compare with all worker face descriptors
                                let bestMatch = null;
                                let bestDistance = 1.0;
                                
                                for (const worker of workers) {
                                    if (worker.img && worker.img.complete) {
                                        try {
                                            const workerDetection = await faceapi.detectSingleFace(worker.img, new faceapi.TinyFaceDetectorOptions())
                                                .withFaceLandmarks()
                                                .withFaceDescriptor();
                                            
                                            if (workerDetection) {
                                                const distance = faceapi.euclideanDistance(detection.descriptor, workerDetection.descriptor);
                                                if (distance < 0.6 && distance < bestDistance) {
                                                    bestMatch = worker;
                                                    bestDistance = distance;
                                                }
                                            }
                                        } catch (e) {
                                            console.warn('Face comparison failed for worker:', worker.name, e);
                                        }
                                    }
                                }
                                
                                if (bestMatch) {
                                    matchedWorker = bestMatch;
                                    
                                    // Cache the confirmed worker
                                    confirmedWorker = matchedWorker;
                                    confirmedWorkerStartTime = currentTime;
                                    lastConfirmedPosition = { x: centerX, y: centerY };
                                    isWorkerInFrame = true;
                                    
                                    displayWorkerInfo(matchedWorker, centerX, centerY, bestDistance, 'known');
                                    updateStatus(true, true, matchedWorker.name);
                                    
                                    // Log successful face recognition
                                    await logActivity('FACE_RECOGNIZED', `چهره ${matchedWorker.name} تشخیص داده شد (تطبیق کامل)`, matchedWorker.id, coords);
                                    console.log(`Face confirmed and cached (full match): ${matchedWorker.name}`);
                                } else {
                                    // Face detected but not recognized - unknown person
                                    unknownFaceDetected = true;
                                    displayUnknownFaceInfo(centerX, centerY);
                                    updateStatus(true, true, 'چهره ناشناس');
                                    
                                    // Log unknown face detection
                                    await logActivity('UNKNOWN_FACE_DETECTED', 'چهره ناشناس تشخیص داده شد', null, coords);
                                }
                            }
                        } catch (e) {
                            console.warn('Enhanced face detection failed:', e);
                        }
                    }
                    
                    // Tertiary: Path-based worker identification (fallback)
                    if (!matchedWorker && paths.length > 0) {
                        const foundPath = paths.find(p => {
                            const px = p.x * width, py = p.y * height, pw = p.width * width, ph = p.height * height;
                            return centerX >= px && centerX <= px + pw && centerY >= py && centerY <= py + ph;
                        });
                        
                        if (foundPath) {
                            // Find a worker associated with this path
                            matchedWorker = workers.find(w => String(w.path_id) === String(foundPath.id));
                            if (matchedWorker) {
                                // Cache the confirmed worker (path-based)
                                confirmedWorker = matchedWorker;
                                confirmedWorkerStartTime = currentTime;
                                lastConfirmedPosition = { x: centerX, y: centerY };
                                isWorkerInFrame = true;
                                
                                displayWorkerInfo(matchedWorker, centerX, centerY);
                                updateStatus(true, true, matchedWorker.name);
                                
                                // Log path-based identification
                                await logActivity('PATH_IDENTIFIED', `کارگر ${matchedWorker.name} در مسیر ${foundPath.name} شناسایی شد`, matchedWorker.id, coords);
                                console.log(`Worker confirmed via path and cached: ${matchedWorker.name}`);
                            }
                        }
                    }
                    
                    // If no worker identified and no unknown face detected, show generic detection
                    if (!matchedWorker && !unknownFaceDetected) {
                        displayGenericWorkerInfo(centerX, centerY);
                        updateStatus(true, true, null);
                    }
                    
                    // Update tracking cube color based on detection type
                    trackingCube.className = 'tracking-cube';
                    if (matchedWorker) {
                        trackingCube.classList.add('known-worker');
                    } else if (unknownFaceDetected) {
                        trackingCube.classList.add('unknown-face');
                    }
                    
                } else {
                    trackingCube.style.display = 'none';
                    workerInfo.style.display = 'none';
                    // Clear motion history when no detection
                    previousPositions = [];
                    updateStatus(true, false, null);
                    
                    // Handle worker leaving frame
                    if (confirmedWorker && isWorkerInFrame) {
                        isWorkerInFrame = false;
                        const timeOutOfFrame = currentTime - (confirmedWorkerStartTime || 0);
                        
                        if (timeOutOfFrame > CONFIRMATION_TIMEOUT) {
                            console.log(`Worker ${confirmedWorker.name} left frame, clearing cache after ${timeOutOfFrame}ms`);
                            confirmedWorker = null;
                            confirmedWorkerStartTime = null;
                            lastConfirmedPosition = null;
                            
                            // Log worker leaving
                            await logActivity('WORKER_LEFT_FRAME', `کارگر ${confirmedWorker?.name || 'ناشناس'} از کادر خارج شد`);
                        }
                    }
                }
            }
            
            // Display worker information with movement analysis
            function displayWorkerInfo(worker, centerX, centerY, faceDistance = null, faceType = 'generic') {
                workerInfo.style.display = 'flex';
                
                // Reset classes and apply appropriate styling
                workerInfo.className = 'worker-info-card';
                if (faceType === 'known') {
                    workerInfo.classList.add('known-worker');
                }
                
                // Position the info card near the detected worker
                const videoRect = video.getBoundingClientRect();
                const containerRect = document.querySelector('.video-container').getBoundingClientRect();
                
                // Calculate position relative to the video container
                const leftPercent = ((centerX + 20) / canvas.width) * 100;
                const topPercent = ((centerY - 40) / canvas.height) * 100;
                
                // Ensure the info card stays within the video bounds
                const adjustedLeftPercent = Math.min(leftPercent, 85);
                const adjustedTopPercent = Math.max(topPercent, 5);
                
                workerInfo.style.left = `${adjustedLeftPercent}%`;
                workerInfo.style.top = `${adjustedTopPercent}%`;
                
                // Analyze movement
                const movementDescription = analyzeMovement(centerX, centerY);
                
                // Update worker information
                workerName.textContent = worker.name;
                workerDetails.textContent = `${worker.details || 'کارگر'} - ${movementDescription}`;
                
                if (faceDistance !== null) {
                    const confidence = (1 - faceDistance).toFixed(2);
                    workerConfidence.textContent = `تشخیص چهره: ${confidence} | موقعیت: X=${Math.round(centerX)}, Y=${Math.round(centerY)}`;
                    workerConfidence.style.display = 'block';
                } else {
                    workerConfidence.textContent = `موقعیت: X=${Math.round(centerX)}, Y=${Math.round(centerY)}`;
                    workerConfidence.style.display = 'block';
                }
                
                // Update worker image if available
                if (worker.img && worker.img.complete) {
                    workerImage.src = worker.image_url;
                    workerImage.style.display = 'block';
                } else {
                    workerImage.style.display = 'none';
                }
            }
            
            // Display unknown face information
            function displayUnknownFaceInfo(centerX, centerY) {
                workerInfo.style.display = 'flex';
                
                // Apply unknown face styling
                workerInfo.className = 'worker-info-card unknown-face';
                
                // Position the info card near the detected face
                const leftPercent = ((centerX + 20) / canvas.width) * 100;
                const topPercent = ((centerY - 40) / canvas.height) * 100;
                
                // Ensure the info card stays within the video bounds
                const adjustedLeftPercent = Math.min(leftPercent, 85);
                const adjustedTopPercent = Math.max(topPercent, 5);
                
                workerInfo.style.left = `${adjustedLeftPercent}%`;
                workerInfo.style.top = `${adjustedTopPercent}%`;
                
                // Analyze movement
                const movementDescription = analyzeMovement(centerX, centerY);
                
                // Update information for unknown face
                workerName.textContent = '⚠️ چهره ناشناس';
                workerName.style.color = '#ef4444'; // Red color
                workerDetails.textContent = `فرد غیرمجاز - ${movementDescription}`;
                workerDetails.style.color = '#fca5a5'; // Light red
                workerConfidence.textContent = `هشدار امنیتی | موقعیت: X=${Math.round(centerX)}, Y=${Math.round(centerY)}`;
                workerConfidence.style.color = '#ef4444'; // Red color
                workerConfidence.style.display = 'block';
                
                // Hide worker image for unknown faces
                workerImage.style.display = 'none';
            }
            
            // Motion tracking variables (legacy - now using enhanced system above)
            let previousPositions = [];
            let motionHistory = [];
            const LEGACY_MOTION_HISTORY_LENGTH = 10;
            
            // Analyze worker movement
            function analyzeMovement(centerX, centerY) {
                const currentTime = Date.now();
                const currentPos = { x: centerX, y: centerY, time: currentTime };
                
                // Add to position history
                previousPositions.push(currentPos);
                if (previousPositions.length > LEGACY_MOTION_HISTORY_LENGTH) {
                    previousPositions.shift();
                }
                
                if (previousPositions.length < 3) {
                    return 'در حال تشخیص حرکت...';
                }
                
                // Calculate movement metrics
                const recent = previousPositions.slice(-5);
                let totalDistance = 0;
                let velocities = [];
                
                for (let i = 1; i < recent.length; i++) {
                    const dx = recent[i].x - recent[i-1].x;
                    const dy = recent[i].y - recent[i-1].y;
                    const distance = Math.sqrt(dx*dx + dy*dy);
                    const timeDiff = recent[i].time - recent[i-1].time;
                    
                    totalDistance += distance;
                    if (timeDiff > 0) {
                        velocities.push(distance / timeDiff * 1000); // pixels per second
                    }
                }
                
                const avgVelocity = velocities.length > 0 ? velocities.reduce((a,b) => a+b) / velocities.length : 0;
                
                // Determine movement type
                if (avgVelocity < 5) {
                    return 'ایستاده - بدون حرکت';
                } else if (avgVelocity < 20) {
                    return 'حرکت آرام - در حال کار';
                } else if (avgVelocity < 50) {
                    return 'راه رفتن معمولی';
                } else {
                    return 'حرکت سریع - در حال دویدن';
                }
            }
            
            // Display generic worker information with movement analysis
            function displayGenericWorkerInfo(centerX, centerY) {
                workerInfo.style.display = 'flex';
                
                // Reset to default styling
                workerInfo.className = 'worker-info-card';
                
                // Position the info card near the detected worker
                const leftPercent = ((centerX + 20) / canvas.width) * 100;
                const topPercent = ((centerY - 40) / canvas.height) * 100;
                
                // Ensure the info card stays within the video bounds
                const adjustedLeftPercent = Math.min(leftPercent, 85);
                const adjustedTopPercent = Math.max(topPercent, 5);
                
                workerInfo.style.left = `${adjustedLeftPercent}%`;
                workerInfo.style.top = `${adjustedTopPercent}%`;
                
                // Analyze movement
                const movementDescription = analyzeMovement(centerX, centerY);
                
                // Reset text colors to default
                workerName.style.color = '#fff';
                workerDetails.style.color = '#ddd';
                workerConfidence.style.color = '#f97316';
                
                // Update worker information
                workerName.textContent = 'کارگر شناسایی نشده';
                workerDetails.textContent = movementDescription;
                workerConfidence.textContent = `موقعیت: X=${Math.round(centerX)}, Y=${Math.round(centerY)}`;
                workerConfidence.style.display = 'block';
                
                // Hide worker image
                workerImage.style.display = 'none';
            }
            
            // Clean up when page is closed
            window.addEventListener('beforeunload', () => {
                if (stream) {
                    stream.getTracks().forEach(track => track.stop());
                }
                if (animationId) {
                    cancelAnimationFrame(animationId);
                }
            });
        });
    </script>
</body>
</html>