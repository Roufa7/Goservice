let loginActive = false;
let video = null;
let canvas = null;
let displaySize = null;
let failedAttempts = 0;
const MAX_ATTEMPTS = 5;

async function initFaceLogin() {
    const loginBtn = document.getElementById('btnFaceID');
    if (!loginBtn) return;

    loginBtn.addEventListener('click', async () => {
        const email = document.getElementById('login_email').value;
        if (!email) {
            alert("Veuillez saisir votre email avant de scanner votre visage.");
            return;
        }

        openFaceModal();
        
        try {
            const modelPath = 'https://raw.githubusercontent.com/justadudewhohacks/face-api.js/master/weights';
            await faceapi.nets.tinyFaceDetector.loadFromUri(modelPath);
            await faceapi.nets.faceLandmark68Net.loadFromUri(modelPath);
            await faceapi.nets.faceRecognitionNet.loadFromUri(modelPath);

            startLoginScanner(email);
        } catch (err) {
            updateStatus("Erreur modèles Face ID.", true);
            console.error(err);
        }
    });
}

function openFaceModal() {
    let modal = document.getElementById('faceModal');
    if (!modal) {
        modal = document.createElement('div');
        modal.id = 'faceModal';
        modal.className = 'face-id-modal';
        modal.innerHTML = `
            <div class="webcam-container">
                <video id="webcam-video" autoplay muted></video>
                <canvas id="face-canvas"></canvas>
                <div id="scan-status" class="scan-status">Initialisation...</div>
                <button id="closeFaceModal" style="position:absolute; top:10px; right:15px; background:none; border:none; color:white; font-size:1.5rem; cursor:pointer;">&times;</button>
            </div>
        `;
        document.body.appendChild(modal);
        
        document.getElementById('closeFaceModal').onclick = stopFaceLogin;
    }
    modal.style.display = 'flex';
}

function updateStatus(text, isError = false) {
    const status = document.getElementById('scan-status');
    if (status) {
        status.innerText = text;
        status.style.color = isError ? "var(--face-id-error)" : "var(--face-id-success)";
    }
}

async function startLoginScanner(email) {
    video = document.getElementById('webcam-video');
    canvas = document.getElementById('face-canvas');
    
    try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: {} });
        video.srcObject = stream;
        loginActive = true;

        video.onplay = () => {
            displaySize = { width: video.offsetWidth, height: video.offsetHeight };
            faceapi.matchDimensions(canvas, displaySize);

            const checkInterval = setInterval(async () => {
                if (!loginActive) {
                    clearInterval(checkInterval);
                    return;
                }

                const options = new faceapi.TinyFaceDetectorOptions();
                const detection = await faceapi.detectSingleFace(video, options)
                    .withFaceLandmarks()
                    .withFaceDescriptor();

                if (detection) {
                    const resizedDetections = faceapi.resizeResults(detection, displaySize);
                    canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
                    faceapi.draw.drawDetections(canvas, resizedDetections);
                    
                    updateStatus("Visage détecté ! Analyse...");
                    
                    // Stop interval immediately and verify
                    clearInterval(checkInterval);
                    verifyFace(email, detection.descriptor);
                } else {
                    canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
                    updateStatus("Positionnez votre visage...");
                }
            }, 200);
        };
    } catch (err) {
        updateStatus("Caméra inaccessible", true);
    }
}

async function verifyFace(email, descriptor) {
    updateStatus("Vérification...");
    
    try {
        const response = await fetch('../../controller/face_auth.php?action=verify', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                email: email, 
                descriptor: Array.from(descriptor) 
            })
        });

        const result = await response.json();
        
        if (result.success) {
            updateStatus("Match réussi ! Redirection...");
            setTimeout(() => {
                window.location.href = result.redirect;
            }, 1000);
        } else {
            failedAttempts++;
            updateStatus(result.message + " (" + failedAttempts + "/" + MAX_ATTEMPTS + ")", true);
            
            if (failedAttempts >= MAX_ATTEMPTS) {
                updateStatus("Compte verrouillé. Cooldown actif.", true);
                loginActive = false;
                setTimeout(() => {
                    stopFaceLogin();
                    failedAttempts = 0;
                }, 3000);
            } else {
                // Restart scanner for another attempt
                setTimeout(() => {
                    if (loginActive) startLoginScanner(email);
                }, 1500);
            }
        }
    } catch (err) {
        updateStatus("Erreur réseau.", true);
    }
}

function stopFaceLogin() {
    loginActive = false;
    if (video && video.srcObject) {
        video.srcObject.getTracks().forEach(track => track.stop());
    }
    const modal = document.getElementById('faceModal');
    if (modal) modal.style.display = 'none';
}

document.addEventListener('DOMContentLoaded', initFaceLogin);
