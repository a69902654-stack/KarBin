
class PoseDetector {
    constructor() {
        this.net = null;
        this.isLoaded = false;
        this.poses = [];
        this.currentActivity = 'نامشخص';
        this.activityHistory = [];
        this.confidenceThreshold = 0.5;
        this.currentWorkerId = null;
        this.currentPathId = null;
        this.lastSavedActivity = null;
        this.lastSaveTime = 0;
        this.saveInterval = 5000;
        

        this.activityPatterns = {
            'جارو زدن': {
                check: (pose) => this.isSweeping(pose),
                description: 'در حال جارو زدن'
            },
            'استراحت': {
                check: (pose) => this.isResting(pose),
                description: 'در حال استراحت'
            },
            'راه رفتن': {
                check: (pose) => this.isWalking(pose),
                description: 'در حال راه رفتن'
            },
            'ایستادن': {
                check: (pose) => this.isStanding(pose),
                description: 'ایستاده'
            },
            'نشستن': {
                check: (pose) => this.isSitting(pose),
                description: 'نشسته'
            },
            'کار با دست': {
                check: (pose) => this.isHandWork(pose),
                description: 'کار با دست'
            }
        };
    }

    async loadModel() {
        try {

            this.net = await posenet.load({
                architecture: 'MobileNetV1',
                outputStride: 16,
                inputResolution: { width: 640, height: 480 },
                multiplier: 0.75
            });
            this.isLoaded = true;
            console.log('PoseNet model loaded successfully');
            return true;
        } catch (error) {
            console.error('Failed to load PoseNet model:', error);
            return false;
        }
    }

    async detectPoses(video) {
        if (!this.isLoaded || !this.net) return null;
        
        try {
            const poses = await this.net.estimateMultiplePoses(video, {
                flipHorizontal: false,
                maxDetections: 5,
                scoreThreshold: this.confidenceThreshold,
                nmsRadius: 20
            });
            
            this.poses = poses;
            return poses;
        } catch (error) {
            console.error('Pose detection error:', error);
            return null;
        }
    }

    recognizeActivity(poses) {
        if (!poses || poses.length === 0) {
            this.currentActivity = 'نامشخص';
            return this.currentActivity;
        }

        const mainPose = poses[0]; // Use the most confident pose
        if (mainPose.score < this.confidenceThreshold) {
            this.currentActivity = 'نامشخص';
            return this.currentActivity;
        }

        // Check each activity pattern
        for (const [activity, pattern] of Object.entries(this.activityPatterns)) {
            if (pattern.check(mainPose)) {
                this.currentActivity = activity;
                this.addToHistory(activity);
                return activity;
            }
        }

        this.currentActivity = 'نامشخص';
        return this.currentActivity;
    }

    // Activity detection methods
    isSweeping(pose) {
        const kp = this.getKeypoints(pose);
        if (!kp.leftWrist || !kp.rightWrist || !kp.nose) return false;
        
        // جارو زدن: یک دست پایین، بدن خم شده
        const leftHandLow = kp.leftWrist.y > kp.nose.y + 100;
        const rightHandLow = kp.rightWrist.y > kp.nose.y + 100;
        const handsDifferent = Math.abs(kp.leftWrist.y - kp.rightWrist.y) > 80;
        
        return (leftHandLow || rightHandLow) && handsDifferent;
    }

    isResting(pose) {
        const kp = this.getKeypoints(pose);
        if (!kp.leftWrist || !kp.rightWrist || !kp.nose) return false;
        
        // استراحت: دستها نزدیک بدن، حالت آرام
        const handsNearBody = this.calculateDistance(kp.leftWrist, kp.nose) < 120 && 
                             this.calculateDistance(kp.rightWrist, kp.nose) < 120;
        const handsLevel = Math.abs(kp.leftWrist.y - kp.rightWrist.y) < 50;
        
        return handsNearBody && handsLevel;
    }

    isWalking(pose) {
        const kp = this.getKeypoints(pose);
        if (!kp.leftAnkle || !kp.rightAnkle) return false;
        
        // راه رفتن: پاها از هم فاصله دارند
        const legSpread = Math.abs(kp.leftAnkle.x - kp.rightAnkle.x);
        return legSpread > 40;
    }

    isStanding(pose) {
        const kp = this.getKeypoints(pose);
        if (!kp.leftAnkle || !kp.rightAnkle || !kp.nose) return false;
        
        // ایستادن: بدن راست، پاها نزدیک هم
        const legSpread = Math.abs(kp.leftAnkle.x - kp.rightAnkle.x);
        const upright = kp.nose.y < Math.min(kp.leftAnkle.y, kp.rightAnkle.y) - 150;
        
        return legSpread < 40 && upright;
    }

    isSitting(pose) {
        const kp = this.getKeypoints(pose);
        if (!kp.leftHip || !kp.rightHip || !kp.nose) return false;
        
        // نشستن: سر نزدیک لگن
        const hipY = (kp.leftHip.y + kp.rightHip.y) / 2;
        const bodyCompressed = Math.abs(kp.nose.y - hipY) < 120;
        
        return bodyCompressed;
    }

    isHandWork(pose) {
        const kp = this.getKeypoints(pose);
        if (!kp.leftWrist || !kp.rightWrist || !kp.nose) return false;
        
        // کار با دست: دستها بالا و فعال
        const handsUp = kp.leftWrist.y < kp.nose.y || kp.rightWrist.y < kp.nose.y;
        const handsActive = this.calculateDistance(kp.leftWrist, kp.rightWrist) > 100;
        
        return handsUp && handsActive;
    }

    // Helper methods
    getKeypoints(pose) {
        const keypoints = {};
        pose.keypoints.forEach(kp => {
            if (kp.score > this.confidenceThreshold) {
                keypoints[kp.part] = { x: kp.position.x, y: kp.position.y, score: kp.score };
            }
        });
        return keypoints;
    }

    calculateDistance(point1, point2) {
        return Math.sqrt(Math.pow(point1.x - point2.x, 2) + Math.pow(point1.y - point2.y, 2));
    }

    calculateAngle(a, b, c) {
        if (!a || !b || !c) return 0;
        const radians = Math.atan2(c.y - b.y, c.x - b.x) - Math.atan2(a.y - b.y, a.x - b.x);
        let angle = Math.abs(radians * 180.0 / Math.PI);
        if (angle > 180.0) angle = 360 - angle;
        return angle;
    }

    addToHistory(activity) {
        const now = Date.now();
        this.activityHistory.push({ activity, timestamp: now });
        
        // Keep only last 10 activities
        if (this.activityHistory.length > 10) {
            this.activityHistory.shift();
        }
        
        // Save to server if we have worker context
        this.saveActivityToServer(activity);
    }
    
    async saveActivityToServer(activity) {
        try {
            // Only save significant activities (not 'نامشخص')
            if (activity === 'نامشخص') return;
            
            const response = await fetch('api/activities.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    worker_id: this.currentWorkerId || 1, // Default to worker 1 if not set
                    activity: activity,
                    confidence: this.poses[0]?.score * 100 || 0,
                    path_id: this.currentPathId || null
                })
            });
            
            const result = await response.json();
            if (!result.ok) {
                console.warn('Failed to save activity:', result.error);
            }
        } catch (error) {
            console.error('Error saving activity to server:', error);
        }
    }
    
    setWorkerContext(workerId, pathId = null) {
        this.currentWorkerId = workerId;
        this.currentPathId = pathId;
    }

    drawPoses(ctx, poses, width, height) {
        if (!poses || poses.length === 0) return;

        poses.forEach(pose => {
            if (pose.score < this.confidenceThreshold) return;

            // Draw skeleton
            this.drawSkeleton(ctx, pose.keypoints);
            
            // Draw keypoints
            pose.keypoints.forEach(kp => {
                if (kp.score > this.confidenceThreshold) {
                    ctx.beginPath();
                    ctx.arc(kp.position.x, kp.position.y, 5, 0, 2 * Math.PI);
                    ctx.fillStyle = '#00ff00';
                    ctx.fill();
                }
            });
        });

        // Draw activity label
        if (this.currentActivity !== 'نامشخص') {
            ctx.fillStyle = 'rgba(0, 0, 0, 0.7)';
            ctx.fillRect(10, 10, 200, 50);
            ctx.fillStyle = '#ffffff';
            ctx.font = '16px Arial';
            ctx.fillText(`فعالیت: ${this.currentActivity}`, 20, 35);
        }
    }

    drawSkeleton(ctx, keypoints) {
        const adjacentKeyPoints = posenet.getAdjacentKeyPoints(keypoints, this.confidenceThreshold);
        
        adjacentKeyPoints.forEach(kp => {
            ctx.beginPath();
            ctx.moveTo(kp[0].position.x, kp[0].position.y);
            ctx.lineTo(kp[1].position.x, kp[1].position.y);
            ctx.lineWidth = 2;
            ctx.strokeStyle = '#ff0000';
            ctx.stroke();
        });
    }

    getActivityDescription() {
        if (this.currentActivity === 'نامشخص') return 'فعالیت مشخص نیست';
        return this.activityPatterns[this.currentActivity]?.description || this.currentActivity;
    }

    getActivityHistory() {
        return this.activityHistory.slice(-5); // Return last 5 activities
    }
}

// Export for use in main application
window.PoseDetector = PoseDetector;