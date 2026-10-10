<?php
session_start();
require_once __DIR__ . '/../config.php';
if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$pdo = getPDO();
$stmt = $pdo->prepare('SELECT username FROM admins WHERE id = ?');
$stmt->execute([$_SESSION['user_id']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html class="dark" lang="fa" dir="rtl">
<head>
<meta charset="utf-8"/>
<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
<title>اسکن چهره - سیستم تشخیص</title>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;700;800&display=swap" rel="stylesheet"/>
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
<script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
<script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs"></script>
<script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface"></script>
<script src="https://cdn.jsdelivr.net/npm/@vladmandic/face-api/dist/face-api.min.js"></script>
<style>
body{direction:rtl; font-family: 'Vazirmatn', sans-serif; background: #111827; color: white;}
#videoWrapper{position:relative; display:inline-block}
#overlay {position:absolute; left:0; top:0}
</style>
</head>
<body class="bg-gray-900 text-white">
<div class="container mx-auto p-4">
<div class="text-center mb-6">
<h1 class="text-3xl font-bold mb-2">اسکن چهره کارگران</h1>
<p class="text-gray-400">تشخیص و ذخیره چهره افراد</p>
</div>

<div class="bg-gray-800 rounded-lg p-6 mb-6">
<div id="videoWrapper" class="text-center mb-4">
<video id="video" width="640" height="480" autoplay playsinline class="rounded border"></video>
<canvas id="overlay" width="640" height="480" class="absolute top-0 left-0 rounded"></canvas>
</div>
<div class="flex gap-3 justify-center mb-4">
<button id="startBtn" class="bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded text-white">شروع دوربین</button>
<button id="captureBtn" class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded text-white" disabled>ثبت چهره</button>
<button id="stopBtn" class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded text-white">توقف</button>
</div>
<div id="status" class="text-center text-gray-400">برای شروع، دکمه "شروع دوربین" را کلیک کنید</div>
</div>

<div class="bg-gray-800 rounded-lg p-6">
<h3 class="text-xl font-bold mb-4">چهره‌های ثبت شده</h3>
<div id="capturedFaces" class="grid grid-cols-2 md:grid-cols-4 gap-4">
<!-- Captured faces will appear here -->
</div>
</div>
</div>

<script>
let video, canvas, ctx, model, stream, faceApiModel;
let faceDetected = false;
let capturedCount = 0;
let faceDescriptor = null;

async function setupCamera() {
    video = document.getElementById('video');
    canvas = document.getElementById('overlay');
    ctx = canvas.getContext('2d');
    
    try {
        stream = await navigator.mediaDevices.getUserMedia({video: true});
        video.srcObject = stream;
        document.getElementById('status').textContent = 'در حال بارگذاری مدل تشخیص چهره...';
        
        model = await blazeface.load();
        // Skip face-api models for now
        // await faceapi.nets.ssdMobilenetv1.loadFromUri('/kar/models/');
        // await faceapi.nets.faceLandmark68Net.loadFromUri('/kar/models/');
        // await faceapi.nets.faceRecognitionNet.loadFromUri('/kar/models/');
        document.getElementById('status').textContent = 'آماده تشخیص چهره';
        document.getElementById('captureBtn').disabled = false;
        
        detectFaces();
    } catch (error) {
        document.getElementById('status').textContent = 'خطا در دسترسی به دوربین: ' + error.message;
    }
}

async function detectFaces() {
    if (!model || !video.videoWidth) {
        requestAnimationFrame(detectFaces);
        return;
    }
    
    const predictions = await model.estimateFaces(video, false);
    
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    if (predictions.length > 0) {
        faceDetected = true;
        document.getElementById('status').textContent = `${predictions.length} چهره تشخیص داده شد - آماده ثبت`;
        
        predictions.forEach(prediction => {
            const start = prediction.topLeft;
            const end = prediction.bottomRight;
            const size = [end[0] - start[0], end[1] - start[1]];
            
            ctx.strokeStyle = '#00ff00';
            ctx.lineWidth = 3;
            ctx.strokeRect(start[0], start[1], size[0], size[1]);
            
            ctx.fillStyle = '#00ff00';
            ctx.font = '16px Arial';
            ctx.fillText('چهره تشخیص داده شد', start[0], start[1] - 10);
        });
    } else {
        faceDetected = false;
        document.getElementById('status').textContent = 'چهره‌ای تشخیص داده نشد';
    }
    
    requestAnimationFrame(detectFaces);
}

async function captureFace() {
    if (!faceDetected) {
        alert('ابتدا چهره را در مقابل دوربین قرار دهید');
        return;
    }
    
    const captureCanvas = document.createElement('canvas');
    captureCanvas.width = video.videoWidth;
    captureCanvas.height = video.videoHeight;
    const captureCtx = captureCanvas.getContext('2d');
    
    captureCtx.drawImage(video, 0, 0);
    
    const imageData = captureCanvas.toDataURL('image/jpeg', 0.8);
    
    // Simple face data (coordinates and size)
    faceDescriptor = {
        timestamp: Date.now(),
        videoWidth: video.videoWidth,
        videoHeight: video.videoHeight,
        captureTime: new Date().toISOString()
    };
    
    // Save to server
    fetch('save_face.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            image: imageData, 
            timestamp: Date.now(),
            descriptor: faceDescriptor
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            capturedCount++;
            addCapturedFace(imageData, capturedCount);
            document.getElementById('status').textContent = `چهره ${capturedCount} ذخیره شد`;
        } else {
            alert('خطا در ذخیره: ' + data.error);
        }
    })
    .catch(error => {
        alert('خطا در ارسال: ' + error.message);
    });
}

function addCapturedFace(imageData, count) {
    const container = document.getElementById('capturedFaces');
    const div = document.createElement('div');
    div.className = 'bg-gray-700 p-2 rounded';
    div.innerHTML = `
        <img src="${imageData}" class="w-full h-32 object-cover rounded mb-2">
        <p class="text-sm text-center">چهره ${count}</p>
    `;
    container.appendChild(div);
}

document.getElementById('startBtn').onclick = setupCamera;
document.getElementById('captureBtn').onclick = captureFace;
document.getElementById('stopBtn').onclick = () => {
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        document.getElementById('status').textContent = 'دوربین متوقف شد';
    }
};
</script>
</body>
</html>