<?php
require_once __DIR__ . '/../config.php';

session_start();
$pdo = getPDO();
$workers = $pdo->query('SELECT w.*, p.name AS path_name FROM workers w LEFT JOIN paths p ON w.path_id = p.id ORDER BY w.id DESC')->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="fa">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>تشخیص چهره - سیستم کارگران</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs"></script>
    <script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/blazeface"></script>
    <style>
        body{direction:rtl; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;}
        #videoWrapper{position:relative; display:inline-block}
        #overlay {position:absolute; left:0; top:0}
        .worker-match{background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 15px; border-radius: 10px; margin: 10px 0;}
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="text-center mb-4">
        <h2>تشخیص چهره کارگران</h2>
        <p class="text-muted">شناسایی خودکار کارگران از روی چهره</p>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card p-3">
                <div id="videoWrapper" class="text-center mb-3">
                    <video id="video" width="640" height="480" autoplay playsinline class="border rounded"></video>
                    <canvas id="overlay" width="640" height="480"></canvas>
                </div>
                <div class="text-center">
                    <button id="startBtn" class="btn btn-primary">شروع تشخیص</button>
                    <button id="scanBtn" class="btn btn-success" disabled>اسکن چهره</button>
                    <button id="stopBtn" class="btn btn-secondary">توقف</button>
                </div>
                <div id="status" class="text-center mt-3 text-muted">برای شروع دکمه "شروع تشخیص" را کلیک کنید</div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card p-3">
                <h5>نتیجه تشخیص</h5>
                <div id="recognitionResult">
                    <p class="text-muted">هنوز چهرهای تشخیص داده نشده</p>
                </div>
            </div>
            
            <div class="card p-3 mt-3">
                <h6>کارگران ثبت شده</h6>
                <div class="small">
                    <?php foreach($workers as $worker): ?>
                        <div class="d-flex align-items-center mb-2 p-2 bg-light rounded">
                            <?php if($worker['image']): ?>
                                <img src="../uploads/<?=basename($worker['image'])?>" class="rounded-circle me-2" width="30" height="30">
                            <?php endif; ?>
                            <span><?=htmlspecialchars($worker['name'])?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let video, canvas, ctx, model, stream;
let workers = <?=json_encode($workers)?>;
let isRecognizing = false;

async function setupCamera() {
    video = document.getElementById('video');
    canvas = document.getElementById('overlay');
    ctx = canvas.getContext('2d');
    
    try {
        stream = await navigator.mediaDevices.getUserMedia({video: true});
        video.srcObject = stream;
        document.getElementById('status').textContent = 'در حال بارگذاری مدل...';
        
        model = await blazeface.load();
        document.getElementById('status').textContent = 'آماده تشخیص چهره';
        document.getElementById('scanBtn').disabled = false;
        isRecognizing = true;
        recognizeFaces();
    } catch (error) {
        document.getElementById('status').textContent = 'خطا: ' + error.message;
    }
}

async function recognizeFaces() {
    if (!isRecognizing || !model || !video.videoWidth) {
        requestAnimationFrame(recognizeFaces);
        return;
    }
    
    const predictions = await model.estimateFaces(video, false);
    
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    if (predictions.length > 0) {
        document.getElementById('status').textContent = 'چهره تشخیص داده شد - در حال بررسی...';
        
        predictions.forEach(prediction => {
            const start = prediction.topLeft;
            const end = prediction.bottomRight;
            const size = [end[0] - start[0], end[1] - start[1]];
            
            ctx.strokeStyle = '#007bff';
            ctx.lineWidth = 3;
            ctx.strokeRect(start[0], start[1], size[0], size[1]);
        });
    } else {
        document.getElementById('status').textContent = 'در انتظار تشخیص چهره...';
        document.getElementById('recognitionResult').innerHTML = '<p class="text-muted">هنوز چهرهای تشخیص داده نشده</p>';
    }
    
    requestAnimationFrame(recognizeFaces);
}

function captureFaceForRecognition() {
    const captureCanvas = document.createElement('canvas');
    captureCanvas.width = video.videoWidth;
    captureCanvas.height = video.videoHeight;
    const captureCtx = captureCanvas.getContext('2d');
    
    captureCtx.drawImage(video, 0, 0);
    const imageData = captureCanvas.toDataURL('image/jpeg', 0.8);
    
    // Send to server for comparison
    fetch('compare_face.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({image: imageData})
    })
    .then(response => response.json())
    .then(data => {
        if (data.match) {
            showWorkerMatch(data.worker);
        } else {
            document.getElementById('recognitionResult').innerHTML = '<p class="text-warning">چهره ناشناس</p>';
        }
    })
    .catch(error => {
        console.error('Recognition error:', error);
    });
}

function showWorkerMatch(worker) {
    const resultDiv = document.getElementById('recognitionResult');
    let imageHtml = '';
    if (worker.image) {
        imageHtml = `<img src="../uploads/${worker.image}" class="rounded-circle me-2" width="50" height="50">`;
    }
    
    resultDiv.innerHTML = `
        <div class="worker-match">
            <div class="d-flex align-items-center">
                ${imageHtml}
                <div>
                    <h6 class="mb-1">${worker.name}</h6>
                    <small>مسیر: ${worker.path_name || 'تعریف نشده'}</small>
                </div>
            </div>
        </div>
    `;
}

document.getElementById('startBtn').onclick = setupCamera;
document.getElementById('scanBtn').onclick = () => {
    if (isRecognizing) {
        captureFaceForRecognition();
    }
};
document.getElementById('stopBtn').onclick = () => {
    isRecognizing = false;
    if (stream) {
        stream.getTracks().forEach(track => track.stop());
        document.getElementById('status').textContent = 'تشخیص متوقف شد';
    }
};
</script>
</body>
</html>