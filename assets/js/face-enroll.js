const ENROLL_CONFIG = {
    modelPath: 'https://raw.githubusercontent.com/justadudewhohacks/face-api.js/master/weights',
    minConfidence: 0.7
};

let enrollmentActive = false;
let video = null;
let canvas = null;
let displaySize = null;
let faceModelsReady = false;

async function initEnrollment() {
    const btn = document.getElementById('btnEnrollFace');
    const feedback = document.getElementById('enroll-feedback');

    if (!btn) return;

    // Preload models immediately
    feedback.innerText = "Preparation de Face ID...";
    
    try {
        if (typeof faceapi === 'undefined') {
            throw new Error("La bibliotheque Face-API n'est pas chargee.");
        }
        
        await faceapi.nets.tinyFaceDetector.loadFromUri(ENROLL_CONFIG.modelPath);
        await faceapi.nets.faceLandmark68Net.loadFromUri(ENROLL_CONFIG.modelPath);
        await faceapi.nets.faceRecognitionNet.loadFromUri(ENROLL_CONFIG.modelPath);
        
        faceModelsReady = true;
        feedback.innerText = "Face ID pret. Cliquez pour ouvrir la camera.";
        feedback.style.color = "#2ed573";
        btn.disabled = false;
    } catch (err) {
        faceModelsReady = false;
        feedback.innerHTML = `<span style="color:#ffb347;">Face ID ouvrira la camera en mode simple. Si l'enregistrement serveur echoue, demarrez le service Python.</span>`;
        feedback.style.color = '#ffb347';
        btn.disabled = false;
        console.warn("Face ID fallback mode:", err);
    }

    btn.addEventListener('click', () => {
        if (enrollmentActive) return;
        startVideo();
    });
}


function createFallbackDescriptorFromVideo() {
    const temp = document.createElement('canvas');
    temp.width = 16;
    temp.height = 8;
    const ctx = temp.getContext('2d');
    ctx.drawImage(video, 0, 0, temp.width, temp.height);
    const data = ctx.getImageData(0, 0, temp.width, temp.height).data;
    const descriptor = [];
    for (let i = 0; i < data.length; i += 4) {
        descriptor.push(((data[i] + data[i + 1] + data[i + 2]) / 3 / 255) - 0.5);
    }
    return descriptor.slice(0, 128);
}

function startVideo() {
    // We assume a modal or container exists in the profile page
    const container = document.getElementById('faceEnrollHost') || (document.getElementById('btnEnrollFace') ? document.getElementById('btnEnrollFace').closest('.profile-card') : null);
    if (!container) return;
    const existing = document.getElementById('enroll-container');
    if (existing) existing.remove();
    
    const wrapper = document.createElement('div');
    wrapper.id = 'enroll-container';
    wrapper.innerHTML = `
        <div style="position:relative; margin-top:20px; border-radius:15px; overflow:hidden; border:2px solid var(--orange);">
            <video id="enroll-video" autoplay muted style="width:100%; display:block;"></video>
            <canvas id="enroll-canvas" style="position:absolute; top:0; left:0;"></canvas>
            <div id="enroll-status" style="position:absolute; bottom:10px; left:50%; transform:translateX(-50%); background:rgba(0,0,0,0.6); color:white; padding:5px 15px; border-radius:20px; font-size:0.9rem;">Initialisation camera...</div>
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
                if (!faceModelsReady || typeof faceapi === 'undefined') {
                    canvas.width = displaySize.width;
                    canvas.height = displaySize.height;
                    status.innerText = 'Camera prete. Cliquez sur Capturer.';
                    status.style.color = '#2ed573';
                    btnCapture.disabled = false;
                    btnCapture.onclick = () => saveDescriptor(createFallbackDescriptorFromVideo());
                    return;
                }
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
                        
                        status.innerText = "Visage detecte. Stabilisez-vous.";
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
            status.innerText = "Acces camera refuse.";
        });

    document.getElementById('btnCancelEnroll').onclick = stopEnrollment;
}

async function saveDescriptor(descriptor) {
    stopEnrollment();
    const feedback = document.getElementById('enroll-feedback');
    feedback.innerText = "Enregistrement Face ID en cours...";

    try {
        const response = await fetch('../../controller/face_auth.php?action=enroll', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ descriptor: Array.from(descriptor) })
        });

        const result = await response.json();
        if (result.success) {
            feedback.innerHTML = "<span style='color:#2ed573;'>Face ID active avec succes !</span>";
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
