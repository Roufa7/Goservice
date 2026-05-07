const ENROLL_CONFIG = {
    modelPath: 'https://raw.githubusercontent.com/justadudewhohacks/face-api.js/master/weights',
    minConfidence: 0.7
};

let enrollmentActive = false;
let video = null;
let canvas = null;
let displaySize = null;

async function initEnrollment() {
    const btn = document.getElementById('btnEnrollFace');
    const feedback = document.getElementById('enroll-feedback');

    if (!btn) return;

    // Preload models immediately
    feedback.innerText = "Initialisation de l'IA...";
    
    try {
        if (typeof faceapi === 'undefined') {
            throw new Error("La bibliothèque Face-API n'est pas chargée.");
        }
        
        await faceapi.nets.tinyFaceDetector.loadFromUri(ENROLL_CONFIG.modelPath);
        await faceapi.nets.faceLandmark68Net.loadFromUri(ENROLL_CONFIG.modelPath);
        await faceapi.nets.faceRecognitionNet.loadFromUri(ENROLL_CONFIG.modelPath);
        
        feedback.innerText = "Appareil prêt pour Face ID.";
        feedback.style.color = "#2ed573";
        btn.disabled = false;
        
        // AUTO START CAMERA
        startVideo();
    } catch (err) {
        feedback.innerHTML = `<span style="color:#ff4757;">⚠️ Erreur : ${err.message}<br>Vérifiez votre connexion internet.</span>`;
        console.error("Face ID Error:", err);
    }

    btn.addEventListener('click', () => {
        if (enrollmentActive) return;
        startVideo();
    });
}

function startVideo() {
    // We assume a modal or container exists in the profile page
    const container = document.querySelector('.profile-card.main');
    
    const wrapper = document.createElement('div');
    wrapper.id = 'enroll-container';
    wrapper.innerHTML = `
        <div style="position:relative; margin-top:20px; border-radius:15px; overflow:hidden; border:2px solid var(--orange);">
            <video id="enroll-video" autoplay muted style="width:100%; display:block;"></video>
            <canvas id="enroll-canvas" style="position:absolute; top:0; left:0;"></canvas>
            <div id="enroll-status" style="position:absolute; bottom:10px; left:50%; transform:translateX(-50%); background:rgba(0,0,0,0.6); color:white; padding:5px 15px; border-radius:20px; font-size:0.9rem;">Initialisation caméra...</div>
        </div>
        <div style="margin-top:15px; display:flex; gap:10px;">
            <button id="btnCapture" class="solid-btn" style="flex:1;" disabled>Capturer mon visage</button>
            <button id="btnCancelEnroll" class="ghost-btn">Annuler</button>
        </div>
    `;
    container.appendChild(wrapper);

    video = document.getElementById('enroll-video');
    canvas = document.getElementById('enroll-canvas');
    const status = document.getElementById('enroll-status');
    const btnCapture = document.getElementById('btnCapture');

    navigator.mediaDevices.getUserMedia({ video: {} })
        .then(stream => {
            video.srcObject = stream;
            enrollmentActive = true;
            
            video.onplay = () => {
                displaySize = { width: video.offsetWidth, height: video.offsetHeight };
                faceapi.matchDimensions(canvas, displaySize);

                const detectionInterval = setInterval(async () => {
                    if (!enrollmentActive) {
                        clearInterval(detectionInterval);
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
                        
                        status.innerText = "Visage détecté ! Stabilisez-vous.";
                        status.style.color = "#2ed573";
                        btnCapture.disabled = false;

                        // Auto capture possible here, but user confirmation requested
                        btnCapture.onclick = () => saveDescriptor(detection.descriptor);
                    } else {
                        canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
                        status.innerText = "Recherche de visage...";
                        status.style.color = "white";
                        btnCapture.disabled = true;
                    }
                }, 100);
            };
        })
        .catch(err => {
            console.error(err);
            status.innerText = "Accès caméra refusé.";
        });

    document.getElementById('btnCancelEnroll').onclick = stopEnrollment;
}

async function saveDescriptor(descriptor) {
    stopEnrollment();
    const feedback = document.getElementById('enroll-feedback');
    feedback.innerText = "Enregistrement cryptographique en cours...";

    try {
        const response = await fetch('../../controller/face_auth.php?action=enroll', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ descriptor: Array.from(descriptor) })
        });

        const result = await response.json();
        if (result.success) {
            feedback.innerHTML = "<span style='color:#2ed573;'>Face ID activé avec succès !</span>";
        } else {
            feedback.innerHTML = "<span style='color:#ff4757;'>Erreur: " + result.message + "</span>";
        }
    } catch (err) {
        feedback.innerText = "Erreur de connexion au serveur.";
    }
}

function stopEnrollment() {
    enrollmentActive = false;
    if (video && video.srcObject) {
        video.srcObject.getTracks().forEach(track => track.stop());
    }
    const container = document.getElementById('enroll-container');
    if (container) container.remove();
    document.getElementById('btnEnrollFace').disabled = false;
}

document.addEventListener('DOMContentLoaded', initEnrollment);
