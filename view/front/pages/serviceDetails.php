<?php
require_once __DIR__ . '/../../../controller/ServiceController.php';
require_once __DIR__ . '/../../../service/QrCodeService.php';

$controller = new ServiceController();

function serviceAppRoot(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $root = dirname($script, 3);
    return ($root === '/' || $root === '\\') ? '' : rtrim($root, '/');
}

function serviceAppUrl(string $path = ''): string
{
    return serviceAppRoot() . '/' . ltrim($path, '/');
}

function serviceResolveMedia(?string $path, string $fallback = 'assets/images/services/default.jpg'): string
{
    $path = trim((string) $path);
    if ($path === '') {
        $path = $fallback;
    }
    if (preg_match('~^https?://~i', $path)) {
        return $path;
    }
    return serviceAppUrl($path);
}

function svcIsAvailable(array $service): bool
{
    $availability = mb_strtolower(trim((string) ($service['disponibilite'] ?? '')), 'UTF-8');
    if ($availability !== '') {
        return in_array($availability, ['1', 'disponible', 'oui', 'true', 'active', 'actif'], true);
    }

    $status = mb_strtolower(trim((string) ($service['statut'] ?? '')), 'UTF-8');
    return in_array($status, ['disponible', 'active', 'actif', 'validé', 'valide'], true);
}

function svcStatusLabel(array $service): string
{
    return trim((string) ($service['statut'] ?? '')) ?: app_text('Validé', 'Validated', 'مؤكد');
}

function svcDelay(array $service): string
{
    $id = (int) ($service['id_service'] ?? 1);
    $delays = ['24h', '24 - 48h', '48h', '1 - 2 jours'];
    return $delays[$id % count($delays)];
}

function svcRating(array $service): array
{
    $id = (int) ($service['id_service'] ?? 1);
    $rating = min(4.9, 4.0 + (($id % 7) * 0.1));
    $reviews = 20 + (($id * 7) % 35);
    return [number_format($rating, 1), $reviews];
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$service = $controller->getServiceWithCategory($id);

if (!$service) {
    echo '<section class="page-hero reveal"><h2>' . htmlspecialchars(app_text('Service introuvable.', 'Service not found.', 'الخدمة غير موجودة.'), ENT_QUOTES, 'UTF-8') . '</h2></section>';
    return;
}

$allServices = $controller->listServicesWithCategories();
$relatedServices = array_values(array_filter($allServices, static function (array $row) use ($service, $id): bool {
    if ((int) ($row['id_service'] ?? 0) === $id) {
        return false;
    }
    if ((int) ($row['id_categorie'] ?? 0) !== (int) ($service['id_categorie'] ?? 0)) {
        return false;
    }
    return trim((string) ($row['statut'] ?? '')) !== 'En attente';
}));
$relatedServices = array_slice($relatedServices, 0, 3);

$img = serviceResolveMedia($service['image'] ?? '');
$isAvailable = svcIsAvailable($service);
$statusLabel = svcStatusLabel($service);
$delay = svcDelay($service);
[$rating, $reviews] = svcRating($service);

$categorie = (string) ($service['nom_categorie'] ?? app_text('Service', 'Service', 'خدمة'));
$titre = (string) ($service['titre'] ?? app_text('Sans titre', 'Untitled', 'بدون عنوان'));
$description = trim((string) ($service['description'] ?? '')) ?: app_text('Aucune description disponible.', 'No description available.', 'لا يوجد وصف متاح.');
$prix = isset($service['prix']) ? number_format((float) $service['prix'], 2, ',', '') : '0,00';

$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = $protocol . '://' . $host . serviceAppUrl('view/front/index.php');
$qrDataUri = QrCodeService::generateDataUri($id, $baseUrl);
$serviceUrl = QrCodeService::getServiceUrl($id, $baseUrl);
?>
<style>
.svc-page{padding:28px 32px 40px}.svc-layout{display:grid;grid-template-columns:1.1fr .95fr;gap:24px;align-items:stretch}.svc-media-card,.svc-info-card{background:linear-gradient(180deg,#0e2941 0%,#102d47 100%);border-radius:22px;overflow:hidden;box-shadow:0 18px 45px rgba(7,20,34,.20)}.svc-media-card{position:relative;min-height:520px}.svc-media-card img{width:100%;height:100%;min-height:520px;object-fit:cover;display:block}.svc-badges{position:absolute;top:18px;left:18px;display:flex;gap:10px;flex-wrap:wrap;z-index:2}.svc-badge{display:inline-flex;align-items:center;justify-content:center;padding:8px 14px;border-radius:999px;font-size:13px;font-weight:700;line-height:1;backdrop-filter:blur(8px)}.svc-badge-cat{background:rgba(242,106,46,.95);color:#fff}.svc-badge-dispo{background:rgba(51,175,87,.92);color:#fff}.svc-badge-status{background:rgba(138,96,214,.92);color:#fff}.svc-info-card{padding:28px 30px;color:#e9f4ff;position:relative}.svc-category{color:#78aeda;text-transform:uppercase;letter-spacing:1.8px;font-size:13px;font-weight:800;margin-bottom:10px}.svc-title{margin:0;color:#fff;font-size:54px;line-height:.95;font-weight:900;letter-spacing:-1px}.svc-rating{margin-top:18px;display:flex;align-items:center;gap:12px;color:#a8bfd6;font-size:15px}.svc-stars{color:#ffb638;letter-spacing:1px;font-size:16px}.svc-price{margin-top:18px;font-size:56px;font-weight:900;line-height:1;color:#f26a2e;letter-spacing:-1px}.svc-price span{font-size:.9em}.svc-separator{height:1px;background:rgba(255,255,255,.10);margin:24px 0}.svc-meta{display:grid;grid-template-columns:1fr auto;gap:16px 18px;margin-bottom:24px}.svc-meta-label{color:#8fb0ca;font-size:16px}.svc-meta-value{color:#fff;font-weight:700;font-size:16px;text-align:right}.svc-meta-value.available{color:#41d16f}.svc-meta-value.unavailable{color:#ff7f7f}.svc-section-title{margin:0 0 14px;font-size:30px;color:#fff;font-weight:800}.svc-description{margin:0;color:#9bb4ca;font-size:17px;line-height:1.8}.svc-actions{display:flex;gap:14px;flex-wrap:wrap;margin-top:28px}.svc-btn-primary,.svc-btn-secondary,.svc-btn-share{display:inline-flex;align-items:center;justify-content:center;padding:14px 22px;border-radius:999px;text-decoration:none;font-weight:800;font-size:15px;transition:.25s ease;cursor:pointer;border:none}.svc-btn-primary{background:linear-gradient(135deg,#ff7b39 0%,#f15a24 100%);color:#fff;box-shadow:0 12px 28px rgba(242,106,46,.25)}.svc-btn-primary:hover{transform:translateY(-2px);color:#fff}.svc-btn-secondary{border:1px solid rgba(138,176,202,.22);color:#9fc0db;background:rgba(255,255,255,.02)}.svc-btn-secondary:hover{color:#fff;border-color:rgba(255,255,255,.30);transform:translateY(-2px)}.svc-btn-share{background:linear-gradient(135deg,#1d7ecf 0%,#1258a0 100%);color:#fff;box-shadow:0 12px 28px rgba(29,126,207,.25);gap:8px}.svc-btn-share:hover{transform:translateY(-2px);color:#fff}.svc-btn-share svg{width:18px;height:18px;fill:currentColor;flex-shrink:0}.qr-overlay{display:none;position:fixed;inset:0;z-index:9999;background:rgba(7,18,32,.78);backdrop-filter:blur(6px);align-items:center;justify-content:center;padding:20px}.qr-overlay.active{display:flex}.qr-popup{background:linear-gradient(160deg,#0e2941 0%,#0d2235 100%);border:1px solid rgba(255,255,255,.10);border-radius:24px;box-shadow:0 32px 80px rgba(0,0,0,.55);padding:36px 32px 30px;max-width:420px;width:100%;text-align:center;position:relative;animation:qrPopIn .25s ease}@keyframes qrPopIn{from{transform:scale(.88) translateY(20px);opacity:0}to{transform:scale(1) translateY(0);opacity:1}}.qr-popup-close{position:absolute;top:16px;right:16px;background:rgba(255,255,255,.08);border:none;color:#aac;width:34px;height:34px;border-radius:50%;font-size:18px;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center;transition:background .2s}.qr-popup-close:hover{background:rgba(255,255,255,.18);color:#fff}.qr-popup-title{color:#fff;font-size:22px;font-weight:900;margin:0 0 4px;letter-spacing:-.4px}.qr-popup-subtitle{color:#78aeda;font-size:14px;margin:0 0 24px}.qr-img-wrap{display:inline-block;background:#fff;border-radius:18px;padding:14px;box-shadow:0 10px 30px rgba(0,0,0,.30);margin-bottom:24px}.qr-img-wrap img{display:block;width:220px;height:220px;border-radius:6px}.qr-url-row{display:flex;align-items:center;gap:10px;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.10);border-radius:12px;padding:12px 14px;margin-bottom:20px}.qr-url-text{flex:1;min-width:0;color:#9bb4ca;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;text-align:left}.qr-copy-btn{flex-shrink:0;background:linear-gradient(135deg,#ff7b39,#f15a24);color:#fff;border:none;border-radius:8px;padding:8px 14px;font-size:13px;font-weight:700;cursor:pointer;white-space:nowrap;transition:opacity .2s}.qr-copy-btn:hover{opacity:.85}.qr-copy-btn.copied{background:linear-gradient(135deg,#33af57,#27914a)}.qr-hint{color:#5a7a94;font-size:12px;margin:0}.svc-map-section,.svc-related-section{padding:0 32px 40px}.svc-map-card{background:linear-gradient(180deg,#0e2941,#102d47);border-radius:22px;overflow:hidden;box-shadow:0 18px 45px rgba(7,20,34,.20)}.svc-map-header{padding:22px 28px 16px;display:flex;align-items:center;gap:14px;border-bottom:1px solid rgba(255,255,255,.07)}.svc-map-header-icon{width:46px;height:46px;background:rgba(238,88,40,.15);border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;flex-shrink:0}.svc-map-header-title{color:#fff;font-size:20px;font-weight:800;margin:0}.svc-map-header-addr{color:#78aeda;font-size:13px;margin-top:3px}.svc-map-container{height:380px;width:100%}.svc-related-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}.svc-related-card{background:linear-gradient(180deg,#0e2941,#102d47);border-radius:20px;overflow:hidden;box-shadow:0 18px 45px rgba(7,20,34,.18);border:1px solid rgba(255,255,255,.06)}.svc-related-card img{width:100%;height:180px;object-fit:cover;display:block}.svc-related-body{padding:18px}.svc-related-cat{color:#78aeda;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:1px}.svc-related-title{margin:8px 0 6px;color:#fff;font-size:22px;font-weight:800}.svc-related-desc{color:#9bb4ca;font-size:14px;line-height:1.6;min-height:44px}.svc-related-meta{display:flex;justify-content:space-between;align-items:center;margin-top:14px;gap:12px}.svc-related-price{color:#f26a2e;font-size:22px;font-weight:900}.svc-related-link{display:inline-flex;align-items:center;justify-content:center;height:42px;padding:0 16px;border-radius:999px;background:rgba(255,255,255,.06);color:#fff;text-decoration:none;font-weight:700}.svc-related-link:hover{background:rgba(255,255,255,.12);color:#fff}@media (max-width:1100px){.svc-layout{grid-template-columns:1fr}.svc-title{font-size:44px}.svc-price{font-size:44px}.svc-media-card,.svc-media-card img{min-height:380px}.svc-related-grid{grid-template-columns:1fr}.svc-related-section,.svc-map-section{padding:0 14px 28px}}@media (max-width:640px){.svc-page{padding:18px 14px 28px}.svc-info-card{padding:22px 18px}.svc-title{font-size:34px}.svc-price{font-size:34px}.svc-meta{grid-template-columns:1fr}.svc-meta-value{text-align:left}.qr-popup{padding:28px 20px 22px}.qr-img-wrap img{width:190px;height:190px}.svc-map-container{height:260px}}
</style>

<section class="svc-page">
    <div class="svc-layout">
        <div class="svc-media-card">
            <div class="svc-badges">
                <span class="svc-badge svc-badge-cat"><?php echo htmlspecialchars($categorie, ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="svc-badge svc-badge-dispo"><?php echo htmlspecialchars($isAvailable ? app_text('Disponible', 'Available', 'متاح') : app_text('Indisponible', 'Unavailable', 'غير متاح'), ENT_QUOTES, 'UTF-8'); ?></span>
                <span class="svc-badge svc-badge-status"><?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <img src="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($titre, ENT_QUOTES, 'UTF-8'); ?>">
        </div>
        <div class="svc-info-card">
            <div class="svc-category"><?php echo htmlspecialchars($categorie, ENT_QUOTES, 'UTF-8'); ?></div>
            <h1 class="svc-title"><?php echo htmlspecialchars($titre, ENT_QUOTES, 'UTF-8'); ?></h1>
            <div class="svc-rating">
                <span class="svc-stars">★★★★★</span>
                <span><?php echo htmlspecialchars($rating . ' · ' . $reviews . ' ' . app_text('avis', 'reviews', 'تقييمات'), ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="svc-price"><?php echo htmlspecialchars($prix, ENT_QUOTES, 'UTF-8'); ?> <span>€</span></div>
            <div class="svc-separator"></div>
            <div class="svc-meta">
                <div class="svc-meta-label"><?php echo htmlspecialchars(app_text('Catégorie', 'Category', 'الفئة'), ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="svc-meta-value"><?php echo htmlspecialchars($categorie, ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="svc-meta-label"><?php echo htmlspecialchars(app_text('Disponibilité', 'Availability', 'التوفر'), ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="svc-meta-value <?php echo $isAvailable ? 'available' : 'unavailable'; ?>"><?php echo htmlspecialchars($isAvailable ? app_text('Disponible', 'Available', 'متاح') : app_text('Indisponible', 'Unavailable', 'غير متاح'), ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="svc-meta-label"><?php echo htmlspecialchars(app_text('Statut', 'Status', 'الحالة'), ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="svc-meta-value"><?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="svc-meta-label"><?php echo htmlspecialchars(app_text('Délai moyen', 'Typical delay', 'المهلة المتوسطة'), ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="svc-meta-value"><?php echo htmlspecialchars($delay, ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <div class="svc-separator"></div>
            <h2 class="svc-section-title"><?php echo htmlspecialchars(app_text('Description', 'Description', 'الوصف'), ENT_QUOTES, 'UTF-8'); ?></h2>
            <p class="svc-description"><?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?></p>
            <div class="svc-actions">
                <a href="index.php?page=reserver&id=<?php echo $id; ?>" class="svc-btn-primary"><?php echo htmlspecialchars(app_text('Réserver ce service', 'Book this service', 'احجز هذه الخدمة'), ENT_QUOTES, 'UTF-8'); ?></a>
                <button class="svc-btn-share" id="btnShare" type="button">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path d="M18 16.08c-.76 0-1.44.3-1.96.77L8.91 12.7c.05-.23.09-.46.09-.7s-.04-.47-.09-.7l7.05-4.11A2.99 2.99 0 0 0 18 8c1.66 0 3-1.34 3-3s-1.34-3-3-3-3 1.34-3 3c0 .24.04.47.09.7L8.04 9.81A2.99 2.99 0 0 0 6 9c-1.66 0-3 1.34-3 3s1.34 3 3 3a2.99 2.99 0 0 0 2.04-.81l7.12 4.16c-.05.21-.08.43-.08.65 0 1.61 1.31 2.92 2.92 2.92s2.92-1.31 2.92-2.92-1.31-2.92-2.92-2.92z"/></svg>
                    <?php echo htmlspecialchars(app_text('Partager ce service', 'Share this service', 'شارك هذه الخدمة'), ENT_QUOTES, 'UTF-8'); ?>
                </button>
                <a href="index.php?page=services" class="svc-btn-secondary"><?php echo htmlspecialchars(app_text('← Retour services', '← Back to services', '← العودة إلى الخدمات'), ENT_QUOTES, 'UTF-8'); ?></a>
            </div>
        </div>
    </div>
</section>

<?php if (!empty($service['latitude']) && !empty($service['longitude'])): ?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<section class="svc-map-section">
    <div class="svc-map-card">
        <div class="svc-map-header">
            <div class="svc-map-header-icon">📍</div>
            <div>
                <div class="svc-map-header-title"><?php echo htmlspecialchars(app_text('Zone d’intervention du service', 'Service area', 'منطقة تدخل الخدمة'), ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="svc-map-header-addr"><?php echo htmlspecialchars((string) ($service['adresse'] ?: app_text('Adresse non précisée', 'Address not specified', 'العنوان غير محدد')), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        </div>
        <div id="serviceMap" class="svc-map-container"></div>
    </div>
</section>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(() => {
    const lat = <?php echo json_encode((float) $service['latitude']); ?>;
    const lng = <?php echo json_encode((float) $service['longitude']); ?>;
    const map = L.map('serviceMap').setView([lat, lng], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://openstreetmap.org">OpenStreetMap</a>',
        maxZoom: 19,
    }).addTo(map);
    L.marker([lat, lng]).addTo(map);
})();
</script>
<?php endif; ?>

<?php if (!empty($relatedServices)): ?>
<section class="svc-related-section">
    <div class="svc-map-card" style="padding:26px 28px;">
        <div class="svc-map-header" style="padding:0 0 18px;border-bottom:0;">
            <div class="svc-map-header-icon">★</div>
            <div>
                <div class="svc-map-header-title"><?php echo htmlspecialchars(app_text('Autres services dans cette catégorie', 'More services in this category', 'خدمات أخرى في الفئة نفسها'), ENT_QUOTES, 'UTF-8'); ?></div>
                <div class="svc-map-header-addr"><?php echo htmlspecialchars(app_text('Continuez votre navigation avec des prestations proches de celle-ci.', 'Continue browsing with related services.', 'واصل التصفح مع خدمات مشابهة لهذه الخدمة.'), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        </div>
        <div class="svc-related-grid">
            <?php foreach ($relatedServices as $related): ?>
                <article class="svc-related-card">
                    <img src="<?php echo htmlspecialchars(serviceResolveMedia((string) ($related['image'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) ($related['titre'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                    <div class="svc-related-body">
                        <div class="svc-related-cat"><?php echo htmlspecialchars((string) ($related['nom_categorie'] ?? $categorie), ENT_QUOTES, 'UTF-8'); ?></div>
                        <h3 class="svc-related-title"><?php echo htmlspecialchars((string) ($related['titre'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></h3>
                        <p class="svc-related-desc"><?php echo htmlspecialchars(mb_strimwidth((string) ($related['description'] ?? ''), 0, 110, '...'), ENT_QUOTES, 'UTF-8'); ?></p>
                        <div class="svc-related-meta">
                            <span class="svc-related-price"><?php echo htmlspecialchars(number_format((float) ($related['prix'] ?? 0), 2, ',', '') . ' €', ENT_QUOTES, 'UTF-8'); ?></span>
                            <a class="svc-related-link" href="index.php?page=serviceDetails&id=<?php echo (int) ($related['id_service'] ?? 0); ?>"><?php echo htmlspecialchars(app_text('Voir détails', 'View details', 'عرض التفاصيل'), ENT_QUOTES, 'UTF-8'); ?></a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<div class="qr-overlay" id="qrOverlay" aria-hidden="true">
    <div class="qr-popup" role="dialog" aria-modal="true" aria-labelledby="qrTitle">
        <button type="button" class="qr-popup-close" id="closeQrOverlay">×</button>
        <h2 class="qr-popup-title" id="qrTitle"><?php echo htmlspecialchars(app_text('Partager ce service', 'Share this service', 'شارك هذه الخدمة'), ENT_QUOTES, 'UTF-8'); ?></h2>
        <p class="qr-popup-subtitle"><?php echo htmlspecialchars(app_text('Scannez ce QR code ou copiez le lien direct.', 'Scan this QR code or copy the direct link.', 'امسح رمز الاستجابة أو انسخ الرابط المباشر.'), ENT_QUOTES, 'UTF-8'); ?></p>
        <div class="qr-img-wrap"><img src="<?php echo htmlspecialchars($qrDataUri, ENT_QUOTES, 'UTF-8'); ?>" alt="QR code"></div>
        <div class="qr-url-row">
            <div class="qr-url-text" id="qrUrlText"><?php echo htmlspecialchars($serviceUrl, ENT_QUOTES, 'UTF-8'); ?></div>
            <button type="button" class="qr-copy-btn" id="qrCopyBtn"><?php echo htmlspecialchars(app_text('Copier', 'Copy', 'نسخ'), ENT_QUOTES, 'UTF-8'); ?></button>
        </div>
        <p class="qr-hint"><?php echo htmlspecialchars(app_text('Vous pouvez partager ce lien par message, mail ou QR code.', 'You can share this link by message, email or QR code.', 'يمكنك مشاركة هذا الرابط عبر الرسائل أو البريد أو رمز الاستجابة.'), ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
</div>
<script>
(() => {
    const btn = document.getElementById('btnShare');
    const overlay = document.getElementById('qrOverlay');
    const closeBtn = document.getElementById('closeQrOverlay');
    const copyBtn = document.getElementById('qrCopyBtn');
    const textNode = document.getElementById('qrUrlText');
    if (btn && overlay) {
        btn.addEventListener('click', () => overlay.classList.add('active'));
        overlay.addEventListener('click', (event) => {
            if (event.target === overlay) overlay.classList.remove('active');
        });
    }
    if (closeBtn) {
        closeBtn.addEventListener('click', () => overlay.classList.remove('active'));
    }
    if (copyBtn && textNode) {
        const idle = <?php echo json_encode(app_text('Copier', 'Copy', 'نسخ')); ?>;
        const done = <?php echo json_encode(app_text('Copié', 'Copied', 'تم النسخ')); ?>;
        const fail = <?php echo json_encode(app_text('Échec', 'Failed', 'فشل')); ?>;
        copyBtn.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(textNode.textContent || '');
                copyBtn.textContent = done;
                copyBtn.classList.add('copied');
            } catch (error) {
                copyBtn.textContent = fail;
            }
            setTimeout(() => {
                copyBtn.textContent = idle;
                copyBtn.classList.remove('copied');
            }, 1600);
        });
    }
})();
</script>
