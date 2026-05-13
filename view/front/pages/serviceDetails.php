<?php
// ──────────────────────────────────────────────────────────────────────────────
// view/front/pages/serviceDetails.php
// Détail d'un service — avec QR Code "Partager ce service"
// ──────────────────────────────────────────────────────────────────────────────

require_once __DIR__ . '/../../../controller/ServiceController.php';
require_once __DIR__ . '/../../../service/QrCodeService.php';
require_once __DIR__ . '/../../../service/RecommendationService.php';

$controller = new ServiceController();

$id      = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$service = $controller->getServiceWithCategory($id);

if (!$service) {
    die("Service introuvable");
}

// ── IA : Recommandations KNN (php-ai/php-ml via Composer) ──
$tousServices    = $controller->listServicesWithCategories();
$recommandations = RecommendationService::getRecommandations($service, $tousServices, 3);

// ── Helpers ──────────────────────────────────────────────────────────────────

function svcIsAvailable(array $service): bool {
    if (isset($service['disponibilite'])) {
        $d = mb_strtolower(trim((string)$service['disponibilite']));
        return in_array($d, ['1', 'disponible', 'oui', 'true', 'active', 'actif'], true);
    }
    if (isset($service['statut'])) {
        $s = mb_strtolower(trim((string)$service['statut']));
        return in_array($s, ['disponible', 'active', 'actif', 'validé', 'valide'], true);
    }
    return false;
}

function svcStatusLabel(array $service): string {
    return !empty($service['statut']) ? (string)$service['statut'] : 'Validé';
}

function svcDelay(array $service): string {
    $id     = (int)($service['id_service'] ?? 1);
    $delays = ['24h', '24 - 48h', '48h', '1 - 2 jours'];
    return $delays[$id % count($delays)];
}

function svcRating(array $service): array {
    $id      = (int)($service['id_service'] ?? 1);
    $rating  = 4.0 + (($id % 7) * 0.1);
    if ($rating > 4.9) { $rating = 4.9; }
    $reviews = 20 + (($id * 7) % 35);
    return [number_format($rating, 1), $reviews];
}

// ── Variables de vue ─────────────────────────────────────────────────────────

$img = !empty($service['image'])
    ? '/GoService_v3/' . ltrim($service['image'], '/')
    : '/GoService_v3/assets/images/services/default.jpg';

$isAvailable = svcIsAvailable($service);
$statusLabel = svcStatusLabel($service);
$delay       = svcDelay($service);
[$rating, $reviews] = svcRating($service);

$categorie   = !empty($service['nom_categorie']) ? $service['nom_categorie'] : 'Service';
$titre       = !empty($service['titre'])         ? $service['titre']         : 'Sans titre';
$description = !empty($service['description'])   ? $service['description']   : 'Aucune description disponible.';
$prix        = isset($service['prix'])           ? number_format((float)$service['prix'], 2, ',', '') : '0,00';

// ── QR Code ──────────────────────────────────────────────────────────────────
// Construire la base URL dynamiquement selon l'environnement XAMPP
$protocol   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host       = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl    = $protocol . '://' . $host . '/GoService_v3/view/front/index.php';

$qrDataUri   = QrCodeService::generateDataUri($id, $baseUrl);
$serviceUrl  = QrCodeService::getServiceUrl($id, $baseUrl);
?>

<!-- ══════════════════════════════════════════════════════════════════════════
     STYLES — page détail + popup QR Code
     ══════════════════════════════════════════════════════════════════════════ -->
<style>
    /* ── Mise en page principale ── */
    .svc-page   { padding: 28px 32px 40px; }
    .svc-layout {
        display: grid;
        grid-template-columns: 1.1fr 0.95fr;
        gap: 24px;
        align-items: stretch;
    }
    .svc-media-card,
    .svc-info-card {
        background: linear-gradient(180deg, #0e2941 0%, #102d47 100%);
        border-radius: 22px;
        overflow: hidden;
        box-shadow: 0 18px 45px rgba(7,20,34,.20);
    }
    .svc-media-card          { position: relative; min-height: 520px; }
    .svc-media-card img      { width:100%; height:100%; min-height:520px; object-fit:cover; display:block; }
    .svc-badges              { position:absolute; top:18px; left:18px; display:flex; gap:10px; flex-wrap:wrap; z-index:2; }
    .svc-badge               { display:inline-flex; align-items:center; justify-content:center; padding:8px 14px; border-radius:999px; font-size:13px; font-weight:700; line-height:1; backdrop-filter:blur(8px); }
    .svc-badge-cat           { background:rgba(242,106,46,.95); color:#fff; }
    .svc-badge-dispo         { background:rgba(51,175,87,.92); color:#fff; }
    .svc-badge-status        { background:rgba(138,96,214,.92); color:#fff; }
    .svc-info-card           { padding:28px 30px; color:#e9f4ff; position:relative; }
    .svc-category            { color:#78aeda; text-transform:uppercase; letter-spacing:1.8px; font-size:13px; font-weight:800; margin-bottom:10px; }
    .svc-title               { margin:0; color:#ffffff; font-size:54px; line-height:.95; font-weight:900; letter-spacing:-1px; }
    .svc-rating              { margin-top:18px; display:flex; align-items:center; gap:12px; color:#a8bfd6; font-size:15px; }
    .svc-stars               { color:#ffb638; letter-spacing:1px; font-size:16px; }
    .svc-price               { margin-top:18px; font-size:56px; font-weight:900; line-height:1; color:#f26a2e; letter-spacing:-1px; }
    .svc-price span          { font-size:.9em; }
    .svc-separator           { height:1px; background:rgba(255,255,255,.10); margin:24px 0; }
    .svc-meta                { display:grid; grid-template-columns:1fr auto; gap:16px 18px; margin-bottom:24px; }
    .svc-meta-label          { color:#8fb0ca; font-size:16px; }
    .svc-meta-value          { color:#ffffff; font-weight:700; font-size:16px; text-align:right; }
    .svc-meta-value.available   { color:#41d16f; }
    .svc-meta-value.unavailable { color:#ff7f7f; }
    .svc-section-title       { margin:0 0 14px; font-size:30px; color:#ffffff; font-weight:800; }
    .svc-description         { margin:0; color:#9bb4ca; font-size:17px; line-height:1.8; }
    .svc-actions             { display:flex; gap:14px; flex-wrap:wrap; margin-top:28px; }
    .svc-btn-primary,
    .svc-btn-secondary,
    .svc-btn-share {
        display:inline-flex; align-items:center; justify-content:center;
        padding:14px 22px; border-radius:999px; text-decoration:none;
        font-weight:800; font-size:15px; transition:.25s ease;
        cursor:pointer; border:none;
    }
    .svc-btn-primary {
        background:linear-gradient(135deg,#ff7b39 0%,#f15a24 100%);
        color:#fff; box-shadow:0 12px 28px rgba(242,106,46,.25);
    }
    .svc-btn-primary:hover { transform:translateY(-2px); color:#fff; }
    .svc-btn-secondary {
        border:1px solid rgba(138,176,202,.22);
        color:#9fc0db; background:rgba(255,255,255,.02);
    }
    .svc-btn-secondary:hover { color:#fff; border-color:rgba(255,255,255,.30); transform:translateY(-2px); }

    /* ── Bouton Partager ── */
    .svc-btn-share {
        background: linear-gradient(135deg, #1d7ecf 0%, #1258a0 100%);
        color: #fff;
        box-shadow: 0 12px 28px rgba(29,126,207,.25);
        gap: 8px;
    }
    .svc-btn-share:hover { transform: translateY(-2px); color: #fff; }
    .svc-btn-share svg   { width: 18px; height: 18px; fill: currentColor; flex-shrink: 0; }

    /* ── Overlay popup ── */
    .qr-overlay {
        display: none;
        position: fixed; inset: 0; z-index: 9999;
        background: rgba(7, 18, 32, 0.78);
        backdrop-filter: blur(6px);
        align-items: center;
        justify-content: center;
        padding: 20px;
    }
    .qr-overlay.active { display: flex; }

    /* ── Popup card ── */
    .qr-popup {
        background: linear-gradient(160deg, #0e2941 0%, #0d2235 100%);
        border: 1px solid rgba(255,255,255,.10);
        border-radius: 24px;
        box-shadow: 0 32px 80px rgba(0,0,0,.55);
        padding: 36px 32px 30px;
        max-width: 420px;
        width: 100%;
        text-align: center;
        position: relative;
        animation: qrPopIn .25s ease;
    }
    @keyframes qrPopIn {
        from { transform: scale(.88) translateY(20px); opacity: 0; }
        to   { transform: scale(1)   translateY(0);    opacity: 1; }
    }

    .qr-popup-close {
        position: absolute; top: 16px; right: 16px;
        background: rgba(255,255,255,.08); border: none; color: #aac;
        width: 34px; height: 34px; border-radius: 50%;
        font-size: 18px; line-height: 1; cursor: pointer;
        display: flex; align-items: center; justify-content: center;
        transition: background .2s;
    }
    .qr-popup-close:hover { background: rgba(255,255,255,.18); color: #fff; }

    .qr-popup-title {
        color: #fff; font-size: 22px; font-weight: 900;
        margin: 0 0 4px;
        letter-spacing: -.4px;
    }
    .qr-popup-subtitle {
        color: #78aeda; font-size: 14px; margin: 0 0 24px;
    }

    .qr-img-wrap {
        display: inline-block;
        background: #fff;
        border-radius: 18px;
        padding: 14px;
        box-shadow: 0 10px 30px rgba(0,0,0,.30);
        margin-bottom: 24px;
    }
    .qr-img-wrap img { display: block; width: 220px; height: 220px; border-radius: 6px; }

    .qr-url-row {
        display: flex;
        align-items: center;
        gap: 10px;
        background: rgba(255,255,255,.05);
        border: 1px solid rgba(255,255,255,.10);
        border-radius: 12px;
        padding: 12px 14px;
        margin-bottom: 20px;
    }
    .qr-url-text {
        flex: 1; min-width: 0;
        color: #9bb4ca; font-size: 13px;
        white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
        text-align: left;
    }
    .qr-copy-btn {
        flex-shrink: 0;
        background: linear-gradient(135deg, #ff7b39, #f15a24);
        color: #fff; border: none; border-radius: 8px;
        padding: 8px 14px; font-size: 13px; font-weight: 700;
        cursor: pointer; white-space: nowrap; transition: opacity .2s;
    }
    .qr-copy-btn:hover   { opacity: .85; }
    .qr-copy-btn.copied  { background: linear-gradient(135deg, #33af57, #27914a); }

    .qr-hint { color: #5a7a94; font-size: 12px; margin: 0; }

    /* ── Responsive ── */
    @media (max-width: 1100px) {
        .svc-layout              { grid-template-columns: 1fr; }
        .svc-title               { font-size: 44px; }
        .svc-price               { font-size: 44px; }
        .svc-media-card,
        .svc-media-card img      { min-height: 380px; }
    }
    @media (max-width: 640px) {
        .svc-page                { padding: 18px 14px 28px; }
        .svc-info-card           { padding: 22px 18px; }
        .svc-title               { font-size: 34px; }
        .svc-price               { font-size: 34px; }
        .svc-meta                { grid-template-columns: 1fr; }
        .svc-meta-value          { text-align: left; }
        .qr-popup                { padding: 28px 20px 22px; }
        .qr-img-wrap img         { width: 190px; height: 190px; }
    }
</style>

<!-- ══════════════════════════════════════════════════════════════════════════
     SECTION PRINCIPALE
     ══════════════════════════════════════════════════════════════════════════ -->
<section class="svc-page">

    <div class="svc-layout">

        <!-- Colonne image -->
        <div class="svc-media-card">
            <div class="svc-badges">
                <span class="svc-badge svc-badge-cat"><?= htmlspecialchars($categorie) ?></span>
                <span class="svc-badge svc-badge-dispo">
                    <?= $isAvailable ? 'Disponible' : 'Indisponible' ?>
                </span>
                <span class="svc-badge svc-badge-status"><?= htmlspecialchars($statusLabel) ?></span>
            </div>
            <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($titre) ?>">
        </div>

        <!-- Colonne info -->
        <div class="svc-info-card">
            <div class="svc-category"><?= htmlspecialchars($categorie) ?></div>
            <h1 class="svc-title"><?= htmlspecialchars($titre) ?></h1>

            <div class="svc-rating">
                <span class="svc-stars">★★★★★</span>
                <span><?= $rating ?> · <?= $reviews ?> avis</span>
            </div>

            <div class="svc-price"><?= $prix ?> <span>€</span></div>

            <div class="svc-separator"></div>

            <div class="svc-meta">
                <div class="svc-meta-label">Catégorie</div>
                <div class="svc-meta-value"><?= htmlspecialchars($categorie) ?></div>

                <div class="svc-meta-label">Disponibilité</div>
                <div class="svc-meta-value <?= $isAvailable ? 'available' : 'unavailable' ?>">
                    <?= $isAvailable ? 'Disponible' : 'Indisponible' ?>
                </div>

                <div class="svc-meta-label">Statut</div>
                <div class="svc-meta-value"><?= htmlspecialchars($statusLabel) ?></div>

                <div class="svc-meta-label">Délai moyen</div>
                <div class="svc-meta-value"><?= htmlspecialchars($delay) ?></div>
            </div>

            <div class="svc-separator"></div>

            <h2 class="svc-section-title">Description</h2>
            <p class="svc-description"><?= htmlspecialchars($description) ?></p>

            <div class="svc-actions">
                <a href="index.php?page=reserver&id=<?= $id ?>" class="svc-btn-primary">
                    Réserver ce service
                </a>

                <!-- ★ Bouton Partager ★ -->
                <button class="svc-btn-share" id="btnShare" type="button">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11A2.99 2.99 0 0 0 18 8c1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81A2.99 2.99 0 0 0 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3a2.99 2.99 0 0 0 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92s2.92-1.31 2.92-2.92-1.31-2.92-2.92-2.92z"/>
                    </svg>
                    Partager ce service
                </button>

                <a href="index.php?page=services" class="svc-btn-secondary">← Retour services</a>
            </div>
        </div>
    </div>

    <!-- ══════════════════════════════════════════════════════════════════════
         CARTE LEAFLET — Localisation du prestataire
         ══════════════════════════════════════════════════════════════════════ -->
    <?php if (!empty($service['latitude']) && !empty($service['longitude'])): ?>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
    <style>
        .svc-map-section     { padding: 0 32px 40px; }
        .svc-map-card        { background:linear-gradient(180deg,#0e2941,#102d47); border-radius:22px; overflow:hidden; box-shadow:0 18px 45px rgba(7,20,34,.20); }
        .svc-map-header      { padding:22px 28px 16px; display:flex; align-items:center; gap:14px; border-bottom:1px solid rgba(255,255,255,.07); }
        .svc-map-header-icon { width:46px; height:46px; background:rgba(238,88,40,.15); border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0; }
        .svc-map-header-title{ color:#fff; font-size:20px; font-weight:800; margin:0; }
        .svc-map-header-addr { color:#78aeda; font-size:13px; margin-top:3px; }
        .svc-map-container   { height:380px; width:100%; }
        @media(max-width:640px){ .svc-map-section{ padding:0 14px 28px; } .svc-map-container{ height:260px; } }
    </style>
    <section class="svc-map-section">
        <div class="svc-map-card">
            <div class="svc-map-header">
                <div class="svc-map-header-icon">📍</div>
                <div>
                    <div class="svc-map-header-title">Zone d’intervention du service</div>
                    <div class="svc-map-header-addr"><?= htmlspecialchars($service['adresse'] ?? 'Adresse non précisée') ?></div>
                </div>
            </div>
            <div id="serviceMap" class="svc-map-container"></div>
        </div>
    </section>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
    (function(){
        const lat = <?= (float)$service['latitude'] ?>;
        const lng = <?= (float)$service['longitude'] ?>;
        const titre   = <?= json_encode($titre) ?>;
        const adresse = <?= json_encode($service['adresse'] ?? '') ?>;
        const prix    = <?= json_encode($prix . ' €') ?>;
        const cat     = <?= json_encode($categorie) ?>;
        const map = L.map('serviceMap',{zoomControl:true,scrollWheelZoom:false}).setView([lat,lng],15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'© OpenStreetMap',maxZoom:19}).addTo(map);
        const icon = L.divIcon({html:`<div style="background:linear-gradient(135deg,#ff7b39,#f15a24);width:36px;height:36px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:4px solid #fff;box-shadow:0 6px 20px rgba(238,88,40,.5)"></div>`,iconSize:[36,36],iconAnchor:[18,36],className:''});
        const popup=`<div style="font-family:'Poppins',sans-serif;min-width:180px;padding:4px"><div style="font-weight:800;font-size:14px;color:#0e2941;margin-bottom:4px">${titre}</div><div style="font-size:12px;color:#617084;margin-bottom:6px">📂 ${cat}</div><div style="font-size:13px;color:#ee5828;font-weight:700;margin-bottom:6px">💶 ${prix}</div><div style="font-size:11px;color:#617084">📍 ${adresse}</div></div>`;
        L.marker([lat,lng],{icon}).addTo(map).bindPopup(popup,{maxWidth:240}).openPopup();
        L.circle([lat,lng],{color:'#ee5828',fillColor:'#ee5828',fillOpacity:.06,weight:1.5,radius:1500}).addTo(map);
    })();
    </script>
    <?php endif; ?>

</section><!-- /svc-page -->


<!-- ══════════════════════════════════════════════════════════════════════════
     POPUP QR CODE
     ══════════════════════════════════════════════════════════════════════════ -->
<div class="qr-overlay" id="qrOverlay" role="dialog" aria-modal="true" aria-labelledby="qrPopupTitle">

    <div class="qr-popup">

        <!-- Fermer -->
        <button class="qr-popup-close" id="qrClose" aria-label="Fermer">✕</button>

        <!-- Titre -->
        <h2 class="qr-popup-title" id="qrPopupTitle">📲 Partager ce service</h2>
        <p class="qr-popup-subtitle">Scannez le QR Code pour accéder directement à cette page</p>

        <!-- QR Code image (générée en PHP côté serveur, encodée en base64) -->
        <div class="qr-img-wrap">
            <img
                src="<?= htmlspecialchars($qrDataUri) ?>"
                alt="QR Code — <?= htmlspecialchars($titre) ?>"
                loading="lazy"
            >
        </div>

        <!-- URL + bouton copier -->
        <div class="qr-url-row">
            <span class="qr-url-text" id="qrUrlText"><?= htmlspecialchars($serviceUrl) ?></span>
            <button class="qr-copy-btn" id="qrCopyBtn" type="button">Copier</button>
        </div>

        <p class="qr-hint">💡 Flashez ce code avec l'appareil photo de votre smartphone</p>

    </div>
</div>

<!-- ══════════════════════════════════════════════════════════════════════════
     JAVASCRIPT — ouverture / fermeture popup + copie lien
     ══════════════════════════════════════════════════════════════════════════ -->
<script>
(function () {
    const overlay  = document.getElementById('qrOverlay');
    const btnShare = document.getElementById('btnShare');
    const btnClose = document.getElementById('qrClose');
    const btnCopy  = document.getElementById('qrCopyBtn');
    const urlText  = document.getElementById('qrUrlText');

    // ── Ouvrir popup ────────────────────────────────────────────────────────
    btnShare.addEventListener('click', function () {
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    });

    // ── Fermer popup (bouton ✕) ─────────────────────────────────────────────
    btnClose.addEventListener('click', closePopup);

    // ── Fermer popup (clic sur l'overlay) ──────────────────────────────────
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) { closePopup(); }
    });

    // ── Fermer popup (touche Echap) ─────────────────────────────────────────
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && overlay.classList.contains('active')) {
            closePopup();
        }
    });

    function closePopup() {
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    // ── Copier l'URL ────────────────────────────────────────────────────────
    btnCopy.addEventListener('click', function () {
        const url = urlText.textContent.trim();

        // Utiliser l'API Clipboard moderne si disponible
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(url).then(showCopied).catch(fallbackCopy);
        } else {
            fallbackCopy();
        }
    });

    function showCopied() {
        btnCopy.textContent = '✓ Copié !';
        btnCopy.classList.add('copied');
        setTimeout(function () {
            btnCopy.textContent = 'Copier';
            btnCopy.classList.remove('copied');
        }, 2200);
    }

    function fallbackCopy() {
        // Fallback pour les contextes non-HTTPS (localhost XAMPP)
        const ta = document.createElement('textarea');
        ta.value = urlText.textContent.trim();
        ta.style.cssText = 'position:fixed;top:-999px;left:-999px;opacity:0';
        document.body.appendChild(ta);
        ta.focus();
        ta.select();
        try { document.execCommand('copy'); showCopied(); } catch (e) { console.warn('Copy failed', e); }
        document.body.removeChild(ta);
    }
})();
</script>
<?php if (!empty($recommandations)): ?>
<!-- ════════════════════════════════════════════════
     IA — Services recommandés · KNN (php-ai/php-ml)
     ════════════════════════════════════════════════ -->
<style>
.reco-section{padding:0 32px 48px;}
@media(max-width:640px){.reco-section{padding:0 14px 32px;}}
.reco-head{display:flex;align-items:center;gap:12px;margin-bottom:8px;flex-wrap:wrap;}
.reco-badge{background:linear-gradient(135deg,rgba(238,88,40,.15),rgba(185,122,255,.15));
            border:1px solid rgba(238,88,40,.25);color:#ee5828;
            font-size:11px;font-weight:700;padding:5px 13px;border-radius:99px;
            text-transform:uppercase;letter-spacing:.06em;white-space:nowrap;}
.reco-title{font-size:20px;font-weight:900;color:var(--text);margin:0;}
.reco-algo{font-size:11px;color:var(--muted);margin-bottom:18px;display:flex;align-items:center;gap:6px;}
.reco-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;}
@media(max-width:900px){.reco-grid{grid-template-columns:repeat(2,1fr);}}
@media(max-width:560px){.reco-grid{grid-template-columns:1fr;}}
.reco-card{background:var(--panel);border:1px solid var(--border);border-radius:18px;
           overflow:hidden;text-decoration:none;display:block;
           transition:transform .2s,box-shadow .2s;}
.reco-card:hover{transform:translateY(-4px);box-shadow:0 16px 40px rgba(7,20,34,.13);}
.reco-img{width:100%;height:145px;object-fit:cover;}
.reco-img-ph{width:100%;height:145px;background:linear-gradient(135deg,#0e2941,#1a3a57);
             display:flex;align-items:center;justify-content:center;font-size:38px;}
.reco-body{padding:14px 16px;}
.reco-cat{font-size:10px;font-weight:700;color:#ee5828;text-transform:uppercase;
          letter-spacing:.08em;margin-bottom:5px;}
.reco-name{font-size:14px;font-weight:800;color:var(--text);margin-bottom:8px;
           white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.reco-dispo{display:inline-block;font-size:10px;font-weight:700;padding:3px 9px;
            border-radius:99px;margin-bottom:10px;}
.dispo-y{background:rgba(76,215,116,.12);color:#4cd774;}
.dispo-n{background:rgba(255,139,139,.12);color:#ff8b8b;}
.reco-foot{display:flex;align-items:center;justify-content:space-between;}
.reco-prix{font-size:16px;font-weight:900;color:#ee5828;}
.reco-score{display:flex;align-items:center;gap:6px;}
.reco-bar-bg{width:46px;height:5px;background:var(--border);border-radius:99px;overflow:hidden;}
.reco-bar{height:5px;border-radius:99px;background:linear-gradient(90deg,#ee5828,#b97aff);}
.reco-pct{font-size:10px;color:var(--muted);font-weight:600;}
</style>

<section class="reco-section">
    <div class="reco-head">
        <span class="reco-badge">🤖 IA</span>
        <h2 class="reco-title">Services recommandés pour vous</h2>
    </div>
    <div class="reco-algo">
        🧠 Algorithme <strong>KNN (K-Nearest Neighbors)</strong> — bibliothèque <strong>php-ai/php-ml</strong> · Composer
    </div>
    <div class="reco-grid">
        <?php foreach ($recommandations as $r):
            $img   = !empty($r['image']) ? '/GoService_v3/' . ltrim($r['image'], '/') : null;
            $prix  = number_format((float)$r['prix'], 2, ',', '');
            $dispo = $r['disponibilite'] === 'Disponible';
            $score = (int)($r['score_similarite'] ?? 0);
            $cat   = htmlspecialchars(($r['icone_categorie'] ?? '') . ' ' . ($r['nom_categorie'] ?? ''));
        ?>
        <a href="index.php?page=serviceDetails&id=<?= (int)$r['id_service'] ?>" class="reco-card">
            <?php if ($img): ?>
                <img src="<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($r['titre']) ?>" class="reco-img">
            <?php else: ?>
                <div class="reco-img-ph"><?= htmlspecialchars($r['icone_categorie'] ?? '🛠️') ?></div>
            <?php endif; ?>
            <div class="reco-body">
                <div class="reco-cat"><?= $cat ?></div>
                <div class="reco-name"><?= htmlspecialchars($r['titre']) ?></div>
                <span class="reco-dispo <?= $dispo ? 'dispo-y' : 'dispo-n' ?>">
                    <?= $dispo ? '● Disponible' : '● Indisponible' ?>
                </span>
                <div class="reco-foot">
                    <span class="reco-prix"><?= $prix ?> €</span>
                    <div class="reco-score">
                        <div class="reco-bar-bg"><div class="reco-bar" style="width:<?= $score ?>%;"></div></div>
                        <span class="reco-pct"><?= $score ?>%</span>
                    </div>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>