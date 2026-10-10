(function(){
    const video = document.getElementById('video');
    const canvas = document.getElementById('canvas');
    const ctx = canvas.getContext('2d');
    const trackingCube = document.getElementById('trackingCube');
    const workerInfo = document.getElementById('workerInfo');
    
    let stream = null;
    let running = false;
    let paths = window.INIT_DATA?.paths || [];
    let workers = window.INIT_DATA?.workers || [];
    let currentWorker = null;
    let lastLookup = { pathId: null, ts: 0, data: null };
    const LOOKUP_CACHE_MS = 2000;

    // Color detection settings
    let colorSettings = {
        minR: 150, maxR: 255,
        minG: 50, maxG: 150,
        minB: 0, maxB: 100
    };

    function updateColorSettings() {
        colorSettings.minR = parseInt(document.getElementById('minR').value);
        colorSettings.maxR = parseInt(document.getElementById('maxR').value);
        colorSettings.minG = parseInt(document.getElementById('minG').value);
        colorSettings.maxG = parseInt(document.getElementById('maxG').value);
        colorSettings.minB = parseInt(document.getElementById('minB').value);
        colorSettings.maxB = parseInt(document.getElementById('maxB').value);
    }

    function isOrange(r, g, b) {
        return (r >= colorSettings.minR && r <= colorSettings.maxR &&
                g >= colorSettings.minG && g <= colorSettings.maxG &&
                b >= colorSettings.minB && b <= colorSettings.maxB);
    }

    function showWorkerInfo(worker, x, y) {
        if (!worker) {
            workerInfo.style.display = 'none';
            currentWorker = null;
            updateStatus(running, false, null);
            return;
        }

        currentWorker = worker;
        document.getElementById('workerName').textContent = worker.name;
        document.getElementById('workerDetails').textContent = worker.details || '';
        document.getElementById('workerConfidence').textContent = 'تشخیص داده شد';
        
        if (worker.image_url) {
            document.getElementById('workerImage').src = worker.image_url;
            document.getElementById('workerImage').style.display = 'block';
        } else {
            document.getElementById('workerImage').style.display = 'none';
        }

        workerInfo.style.left = Math.min(x + 20, window.innerWidth - 300) + 'px';
        workerInfo.style.top = Math.max(y - 50, 10) + 'px';
        workerInfo.style.display = 'block';
        
        updateStatus(running, true, worker.name);
    }

    async function tick() {
        if (!running) return;
        
        const w = video.videoWidth;
        const h = video.videoHeight;
        
        if (w === 0 || h === 0) {
            requestAnimationFrame(tick);
            return;
        }

        canvas.width = w;
        canvas.height = h;
        ctx.drawImage(video, 0, 0, w, h);
        
        const imageData = ctx.getImageData(0, 0, w, h);
        const data = imageData.data;
        
        let orangePixels = [];
        
        // Detect orange pixels
        for (let y = 0; y < h; y += 2) {
            for (let x = 0; x < w; x += 2) {
                const i = (y * w + x) * 4;
                const r = data[i];
                const g = data[i + 1];
                const b = data[i + 2];
                
                if (isOrange(r, g, b)) {
                    orangePixels.push({ x, y });
                }
            }
        }

        // Clear previous tracking
        trackingCube.style.display = 'none';
        
        if (orangePixels.length > 50) {
            // Calculate bounding box
            let minX = w, minY = h, maxX = 0, maxY = 0;
            let sumX = 0, sumY = 0;
            
            orangePixels.forEach(pixel => {
                minX = Math.min(minX, pixel.x);
                minY = Math.min(minY, pixel.y);
                maxX = Math.max(maxX, pixel.x);
                maxY = Math.max(maxY, pixel.y);
                sumX += pixel.x;
                sumY += pixel.y;
            });
            
            const centerX = sumX / orangePixels.length;
            const centerY = sumY / orangePixels.length;
            const width = maxX - minX;
            const height = maxY - minY;
            
            // Show tracking cube
            const videoRect = video.getBoundingClientRect();
            const scaleX = videoRect.width / w;
            const scaleY = videoRect.height / h;
            
            trackingCube.style.left = (minX * scaleX) + 'px';
            trackingCube.style.top = (minY * scaleY) + 'px';
            trackingCube.style.width = (width * scaleX) + 'px';
            trackingCube.style.height = (height * scaleY) + 'px';
            trackingCube.style.display = 'block';
            
            // Check if center is in any path
            const foundPath = paths.find(path => {
                const pathX = path.x * w;
                const pathY = path.y * h;
                const pathW = path.width * w;
                const pathH = path.height * h;
                
                return centerX >= pathX && centerX <= pathX + pathW &&
                       centerY >= pathY && centerY <= pathY + pathH;
            });
            
            if (foundPath) {
                const foundWorker = workers.find(w => w.path_id == foundPath.id);
                if (foundWorker) {
                    showWorkerInfo(foundWorker, minX * scaleX + videoRect.left, minY * scaleY + videoRect.top);
                } else {
                    showWorkerInfo(null);
                }
            } else {
                showWorkerInfo(null);
            }
        } else {
            showWorkerInfo(null);
        }
        
        requestAnimationFrame(tick);
    }

    // Control functions
    async function startCamera() {
        if (running) return;
        
        try {
            stream = await navigator.mediaDevices.getUserMedia({
                video: { width: 640, height: 480 },
                audio: false
            });
            
            video.srcObject = stream;
            running = true;
            updateStatus(true, false, null);
            
            video.onloadedmetadata = () => {
                video.play();
                canvas.style.display = 'block';
                requestAnimationFrame(tick);
            };
            
        } catch (e) {
            alert('خطا در دسترسی به دوربین: ' + e.message);
            updateStatus(false, false, null);
        }
    }

    function stopCamera() {
        running = false;
        updateStatus(false, false, null);
        
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
            video.srcObject = null;
        }
        
        canvas.style.display = 'none';
        trackingCube.style.display = 'none';
        workerInfo.style.display = 'none';
    }

    // Event listeners
    document.addEventListener('DOMContentLoaded', function() {
        // Color range sliders
        ['minR', 'maxR', 'minG', 'maxG', 'minB', 'maxB'].forEach(id => {
            const slider = document.getElementById(id);
            const display = document.getElementById(id + 'Val');
            if (slider && display) {
                slider.addEventListener('input', function() {
                    display.textContent = this.value;
                    updateColorSettings();
                });
            }
        });

        // Camera controls
        const startBtn = document.getElementById('startBtn');
        const stopBtn = document.getElementById('stopBtn');
        
        if (startBtn) startBtn.addEventListener('click', startCamera);
        if (stopBtn) stopBtn.addEventListener('click', stopCamera);
    });

    // Make functions available globally
    window.cameraControl = {
        start: startCamera,
        stop: stopCamera,
        isRunning: () => running
    };
})();