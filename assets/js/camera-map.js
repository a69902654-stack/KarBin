(function(){
    const video = document.getElementById('video');
    const overlay = document.getElementById('overlay');
    const ctx = overlay.getContext('2d');
    let stream = null;
    let running = false;
    let paths = [];
    let lastLookup = { pathId: null, ts: 0, data: null };
    const LOOKUP_CACHE_MS = 2000;
    
    // Simulated GPS coordinates (in real implementation, this would come from GPS)
    let currentLat = 35.6892;
    let currentLng = 51.3890;

    async function loadPaths(){
        try{
            const res = await fetch('../api/paths.php');
            paths = await res.json();
        }catch(e){ console.error(e); paths = []; }
    }

    function isOrange(r,g,b){
        return (r > 170 && g > 70 && b < 140 && r > g && g > b);
    }

    function isInPath(lat, lng, path) {
        // Simple bounding box check - in real implementation use proper geofencing
        const minLat = Math.min(path.start_lat, path.end_lat);
        const maxLat = Math.max(path.start_lat, path.end_lat);
        const minLng = Math.min(path.start_lng, path.end_lng);
        const maxLng = Math.max(path.start_lng, path.end_lng);
        
        return lat >= minLat && lat <= maxLat && lng >= minLng && lng <= maxLng;
    }

    async function tick(){
        if(!running) return;
        const w = video.videoWidth;
        const h = video.videoHeight;
        overlay.width = w; overlay.height = h;
        ctx.drawImage(video,0,0,w,h);
        const data = ctx.getImageData(0,0,w,h);
        let sumX=0,sumY=0,c=0,minX=w,minY=h,maxX=0,maxY=0;
        
        for(let y=0;y<h;y+=2){
            for(let x=0;x<w;x+=2){
                const i = (y*w + x) * 4;
                const r = data.data[i], g = data.data[i+1], b = data.data[i+2];
                if(isOrange(r,g,b)){
                    c++; sumX += x; sumY += y;
                    if(x<minX) minX=x; if(y<minY) minY=y; if(x>maxX) maxX=x; if(y>maxY) maxY=y;
                    data.data[i] = 255; data.data[i+1]=0; data.data[i+2]=0;
                }
            }
        }
        ctx.putImageData(data,0,0);
        
        // Draw path indicators
        ctx.fillStyle = 'rgba(0,128,255,0.3)';
        ctx.strokeStyle = 'rgba(0,128,255,0.8)';
        ctx.lineWidth = 2;
        paths.forEach((p, idx) => {
            const x = 50 + (idx * 150);
            const y = 20;
            ctx.fillRect(x, y, 140, 30);
            ctx.strokeRect(x, y, 140, 30);
            ctx.fillStyle = 'white';
            ctx.font = '12px Arial';
            ctx.fillText(p.name, x + 5, y + 20);
            ctx.fillStyle = 'rgba(0,128,255,0.3)';
        });

        if(c > 100){
            const cx = Math.round(sumX / c), cy = Math.round(sumY / c);
            ctx.strokeStyle = 'orange'; ctx.lineWidth = 3;
            ctx.strokeRect(minX,minY,maxX-minX,maxY-minY);
            ctx.fillStyle = 'orange'; ctx.beginPath(); ctx.arc(cx,cy,6,0,Math.PI*2); ctx.fill();
            
            // Simulate GPS movement based on orange object position
            currentLat += (cy - h/2) * 0.00001;
            currentLng += (cx - w/2) * 0.00001;
            
            const found = paths.find(p => isInPath(currentLat, currentLng, p));
            
            if(found){
                const now = Date.now();
                if (lastLookup.pathId !== found.id || now - lastLookup.ts > LOOKUP_CACHE_MS) {
                    lastLookup.pathId = found.id; lastLookup.ts = now; lastLookup.data = null;
                    try{
                        const res = await fetch(`../api/lookup.php?path_id=${found.id}`);
                        const j = await res.json();
                        if(j.ok){
                            lastLookup.data = j.worker;
                            if(lastLookup.data.image_url){
                                const img = new Image(); img.src = lastLookup.data.image_url; lastLookup.data.img = img;
                            }
                        }
                    }catch(e){ console.error(e); }
                }
                if(lastLookup.data){
                    const boxX = Math.min(maxX+8, w-220);
                    const boxY = minY;
                    ctx.fillStyle = 'rgba(0,0,0,0.6)'; ctx.fillRect(boxX, boxY, 200, 80);
                    if(lastLookup.data.img && lastLookup.data.img.complete){
                        ctx.drawImage(lastLookup.data.img, boxX+6, boxY+6, 64,64);
                        ctx.fillStyle='white'; ctx.font='14px sans-serif'; ctx.fillText(lastLookup.data.name, boxX+76, boxY+26);
                        ctx.font='12px sans-serif'; ctx.fillStyle='#ddd'; ctx.fillText(lastLookup.data.details || '', boxX+76, boxY+46);
                    } else {
                        ctx.fillStyle='white'; ctx.font='14px sans-serif'; ctx.fillText(lastLookup.data.name, boxX+8, boxY+22);
                        ctx.font='12px sans-serif'; ctx.fillStyle='#ddd'; ctx.fillText(lastLookup.data.details || '', boxX+8, boxY+40);
                    }
                }
            }
            
            // Show current coordinates
            ctx.fillStyle = 'rgba(0,0,0,0.6)'; ctx.fillRect(10, h-50, 200, 40);
            ctx.fillStyle = 'white'; ctx.font = '12px Arial';
            ctx.fillText(`Lat: ${currentLat.toFixed(6)}`, 15, h-30);
            ctx.fillText(`Lng: ${currentLng.toFixed(6)}`, 15, h-15);
        }
        requestAnimationFrame(tick);
    }

    document.getElementById('startBtn').addEventListener('click', async ()=>{
        if(running) return;
        await loadPaths();
        try{
            stream = await navigator.mediaDevices.getUserMedia({video:{width:640,height:480}, audio:false});
            video.srcObject = stream;
            running = true;
            video.onloadedmetadata = ()=>{ video.play(); requestAnimationFrame(tick); };
        }catch(e){ alert('دسترسی به دوربین ناموفق است: '+e.message); }
    });
    
    document.getElementById('stopBtn').addEventListener('click', ()=>{
        running = false; if(stream){ stream.getTracks().forEach(t=>t.stop()); video.srcObject = null; }
    });
})();