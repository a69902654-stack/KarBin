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