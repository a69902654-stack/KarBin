(function(){
    const video = document.getElementById('video');
    const overlay = document.getElementById('overlay');
    const ctx = overlay.getContext('2d');
    let stream = null;
    let running = false;
    let paths = [];
    let lastLookup = { pathId: null, ts: 0, data: null };
    const LOOKUP_CACHE_MS = 2000;

    async function loadPaths(){
        try{
            const res = await fetch('../api/paths.php');
            paths = await res.json();
        }catch(e){ console.error(e); paths = []; }
    }

    function isOrange(r,g,b){

        return (r > 170 && g > 70 && b < 140 && r > g && g > b);
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

        ctx.strokeStyle = 'rgba(0,128,255,0.8)'; ctx.lineWidth = 2;
        paths.forEach(p=>{
            const px = Math.round(p.x * w), py = Math.round(p.y * h), pw = Math.round(p.width*w), ph = Math.round(p.height*h);
            ctx.strokeRect(px,py,pw,ph);
            ctx.fillStyle = 'rgba(0,128,255,0.06)'; ctx.fillRect(px,py,pw,ph);
        });

        if(c > 100){
            const cx = Math.round(sumX / c), cy = Math.round(sumY / c);

            ctx.strokeStyle = 'orange'; ctx.lineWidth = 3;
            ctx.strokeRect(minX,minY,maxX-minX,maxY-minY);
            ctx.fillStyle = 'orange'; ctx.beginPath(); ctx.arc(cx,cy,6,0,Math.PI*2); ctx.fill();

            const found = paths.find(p=>{
                const px = p.x*w, py=p.y*h, pw=p.width*w, ph=p.height*h;
                return cx >= px && cx <= px+pw && cy >= py && cy <= py+ph;
            });
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