<?php
require_once __DIR__ . '/../../../controller/ServiceController.php';
require_once __DIR__ . '/../../../controller/CategorieController.php';

$serviceController = new ServiceController();
$categorieController = new CategorieController();

$services = $serviceController->listServicesWithCategories();
$allServices = $services;

$validatedServices = array_filter($services, function ($service) {
    $status = trim((string) ($service['statut'] ?? ''));
    $normalized = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $status) ?: $status);
    return in_array($normalized, ['valide', 'validã©'], true);
});

$services = !empty($validatedServices) ? array_values($validatedServices) : array_values($allServices);
$categories = $categorieController->listCategories();

$categorieActive = isset($_GET['categorie']) ? (int) $_GET['categorie'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$prixMax = isset($_GET['prix_max']) ? (float) $_GET['prix_max'] : 500;
$dispoOnly = isset($_GET['dispo']) ? 1 : 0;
$tri = isset($_GET['tri']) ? trim($_GET['tri']) : 'pertinence';

function srvCategorySlug(string $name): string
{
    $name = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', trim($name)) ?: trim($name));
    $map = [
        'plomberie' => 'plomberie',
        'electricite' => 'electricite',
        'peinture' => 'peinture',
        'informatique' => 'informatique',
        'jardinage' => 'jardinage',
        'menage' => 'menage',
        'beaute' => 'beaute',
        'secretariat' => 'secretariat',
        'sport' => 'sport',
        'animation evenementielle' => 'animation',
    ];

    return $map[$name] ?? 'default';
}

function srvIsAvailable(array $service): bool
{
    $availability = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', trim((string) ($service['disponibilite'] ?? ''))) ?: trim((string) ($service['disponibilite'] ?? '')));
    return $availability === 'disponible';
}

function srvRating(array $service): array
{
    $id = (int) ($service['id_service'] ?? 1);
    $rating = min(4.9, 4.0 + (($id % 7) * 0.1));
    $reviews = 20 + ($id * 7 % 35);
    return [number_format($rating, 1), $reviews];
}

$servicesFiltres = array_filter($services, function ($service) use ($categorieActive, $search, $prixMax, $dispoOnly) {
    $matchCategorie = $categorieActive === 0 || (int) ($service['id_categorie'] ?? 0) === $categorieActive;

    $texte = strtolower(iconv(
        'UTF-8',
        'ASCII//TRANSLIT//IGNORE',
        (($service['titre'] ?? '') . ' ' . ($service['description'] ?? '') . ' ' . ($service['nom_categorie'] ?? ''))
    ) ?: (($service['titre'] ?? '') . ' ' . ($service['description'] ?? '') . ' ' . ($service['nom_categorie'] ?? '')));

    $searchNormalized = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $search) ?: $search);
    $matchSearch = $search === '' || strpos($texte, $searchNormalized) !== false;

    $prixService = isset($service['prix']) ? (float) $service['prix'] : 0;
    $matchPrix = $prixService <= $prixMax;
    $matchDispo = !$dispoOnly || srvIsAvailable($service);

    return $matchCategorie && $matchSearch && $matchPrix && $matchDispo;
});

$servicesFiltres = array_values($servicesFiltres);

usort($servicesFiltres, function ($a, $b) use ($tri) {
    switch ($tri) {
        case 'prix_asc':
            return (float) ($a['prix'] ?? 0) <=> (float) ($b['prix'] ?? 0);
        case 'prix_desc':
            return (float) ($b['prix'] ?? 0) <=> (float) ($a['prix'] ?? 0);
        case 'avis_desc':
            $rA = 4.0 + (((int) ($a['id_service'] ?? 1) % 7) * 0.1);
            $rB = 4.0 + (((int) ($b['id_service'] ?? 1) % 7) * 0.1);
            return $rB <=> $rA;
        case 'az':
            return strcmp((string) ($a['titre'] ?? ''), (string) ($b['titre'] ?? ''));
        case 'pertinence':
        default:
            return 0;
    }
});

$perPage = 6;
$totalFiltres = count($servicesFiltres);
$totalPages = max(1, (int) ceil($totalFiltres / $perPage));
$currentPage = isset($_GET['p']) ? max(1, min((int) $_GET['p'], $totalPages)) : 1;
$offset = ($currentPage - 1) * $perPage;
$servicePage = array_slice($servicesFiltres, $offset, $perPage);

$totalServices = count($services);
$totalCategories = count($categories);
$totalDisponibles = count(array_filter($services, fn($s) => srvIsAvailable($s)));
?>

<style>
.srv-wrap{
    display:grid !important;
    grid-template-columns: 320px minmax(0, 1fr) !important;
    gap: 28px !important;
    align-items:start !important;
    margin: 0 24px 40px !important;
}

.srv-filter-box,
.srv-content-box{
    background: rgba(15, 30, 46, 0.96) !important;
    border: 1px solid rgba(255,255,255,0.08) !important;
    border-radius: 24px !important;
    padding: 24px !important;
    box-shadow: 0 18px 40px rgba(0,0,0,0.18) !important;
}

.srv-filter-form,
.srv-category-list{
    display:flex !important;
    flex-direction:column !important;
    gap: 10px !important;
}

.srv-category-pill{
    display:flex !important;
    align-items:center !important;
    gap:10px !important;
    color:#fff !important;
    text-decoration:none !important;
    padding: 10px 12px !important;
    border-radius: 14px !important;
    transition: background .18s ease, color .18s ease !important;
}

.srv-category-pill:hover,
.srv-category-pill.active{
    background: rgba(238,88,40,.14) !important;
    color:#fff !important;
}

.srv-filter-title{
    color:#fff !important;
    font-size: 1.1rem !important;
    font-weight: 800 !important;
    margin-bottom: 12px !important;
}

.srv-range,
.srv-switch input{
    cursor:pointer !important;
}

.srv-price-values,
.srv-price-current,
.srv-toggle-row,
.srv-stat-item span,
.srv-empty p{
    color:#d8dee7 !important;
}

.srv-stats-strip{
    display:grid !important;
    grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
    gap: 16px !important;
    margin-bottom: 24px !important;
}

.srv-stat-item{
    background: rgba(255,255,255,0.04) !important;
    border-radius: 18px !important;
    padding: 16px 18px !important;
}

.srv-stat-item strong{
    display:block !important;
    color:#fff !important;
    font-size: 1.6rem !important;
    margin-bottom: 4px !important;
}

.srv-grid{
    display:grid !important;
    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    gap: 22px !important;
}

.srv-card{
    display:flex !important;
    flex-direction:column !important;
    overflow:hidden !important;
    background: linear-gradient(180deg, rgba(20, 41, 65, 0.98), rgba(12, 27, 42, 0.98)) !important;
    border: 1px solid rgba(255,255,255,0.08) !important;
    border-radius: 24px !important;
    min-height: 420px !important;
}

.srv-card-top{
    position:relative !important;
    min-height: 210px !important;
    background: rgba(255,255,255,0.03) !important;
}

.srv-card-image{
    width:100% !important;
    height:210px !important;
    object-fit:cover !important;
    display:block !important;
}

.srv-card-badges{
    position:absolute !important;
    top: 16px !important;
    left: 16px !important;
    display:flex !important;
    gap: 8px !important;
    flex-wrap:wrap !important;
}

.srv-badge{
    padding: 8px 12px !important;
    border-radius: 999px !important;
    font-size: .85rem !important;
    font-weight: 700 !important;
}

.srv-badge-pop{
    background: #ffd8a8 !important;
    color:#b15a00 !important;
}

.srv-badge-status.available{
    background:#d7f5df !important;
    color:#16803f !important;
}

.srv-badge-status.unavailable{
    background:#ffe0e0 !important;
    color:#b42318 !important;
}

.srv-dot{
    position:absolute !important;
    top: 18px !important;
    right: 18px !important;
    width: 14px !important;
    height: 14px !important;
    border-radius: 999px !important;
    box-shadow: 0 0 0 6px rgba(255,255,255,0.12) !important;
}

.srv-card-body{
    display:flex !important;
    flex-direction:column !important;
    gap: 14px !important;
    padding: 18px 18px 20px !important;
    color:#fff !important;
    flex:1 !important;
}

.srv-card-title,
.srv-card-category span,
.srv-rating-row,
.srv-price-main,
.srv-price-unit{
    color:#fff !important;
}

.srv-card-footer{
    display:flex !important;
    align-items:end !important;
    justify-content:space-between !important;
    gap: 16px !important;
    margin-top:auto !important;
}

.srv-details-btn{
    display:inline-flex !important;
    align-items:center !important;
    justify-content:center !important;
    text-decoration:none !important;
    padding: 12px 18px !important;
    border-radius: 16px !important;
    background: linear-gradient(135deg, #ee5828, #31a24c) !important;
    color:#fff !important;
    font-weight:700 !important;
    min-width: 150px !important;
}

.srv-empty{
    grid-column: 1 / -1 !important;
    background: rgba(255,255,255,0.04) !important;
    border-radius: 22px !important;
    padding: 28px !important;
}

.srv-top-search{
    display:flex !important;
    align-items:center !important;
    gap: 14px !important;
    flex-wrap:wrap !important;
}

.srv-top-search .icon-actions{
    display:flex !important;
    align-items:center !important;
    gap: 12px !important;
    margin-left:auto !important;
    flex-wrap:wrap !important;
}

.srv-top-search .solid-btn,
.srv-filter-box .solid-btn{
    display:inline-flex !important;
    align-items:center !important;
    justify-content:center !important;
    padding: 12px 18px !important;
    border-radius: 999px !important;
    text-decoration:none !important;
    font-weight:700 !important;
}

@media (max-width: 1200px){
    .srv-wrap{
        grid-template-columns: 1fr !important;
    }

    .srv-grid{
        grid-template-columns: 1fr !important;
    }

    .srv-stats-strip{
        grid-template-columns: 1fr !important;
    }
}

.cat-icon{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    font-size:18px;
    line-height:1;
    margin-right:8px;
    flex-shrink:0;
}

.srv-category-pill{
    display:flex;
    align-items:center;
    gap:8px;
}

.srv-card-category{
    display:flex;
    align-items:center;
    gap:8px;
}

.srv-pagination{
    display:flex;
    align-items:center;
    justify-content:center;
    gap:6px;
    padding:24px 0 8px;
    flex-wrap:wrap;
}

.pg-btn{
    padding:8px 14px;
    border-radius:10px;
    border:1px solid var(--line);
    background:var(--card);
    color:var(--text);
    font-size:13px;
    font-weight:600;
    text-decoration:none;
    transition:all .18s;
}

.pg-btn:hover{
    border-color:#ee5828;
    color:#ee5828;
    background:rgba(238,88,40,.07);
}

.pg-active{
    background:#ee5828 !important;
    color:#fff !important;
    border-color:#ee5828 !important;
}

.pg-dots{
    padding:8px 4px;
    color:var(--muted);
    font-size:13px;
}
</style>

<section class="page-hero reveal">
    <span class="section-badge">Services</span>
    <h1 class="page-title">Services & Categories</h1>
    <p class="page-intro">
        Consultez les services disponibles, explorez les categories et trouvez rapidement la prestation adaptee a votre besoin.
    </p>
</section>

<section class="action-bar reveal">
    <form class="search-box srv-top-search" method="GET" action="index.php" id="srvTopFilterForm">
        <input type="hidden" name="page" value="services">
        <input type="hidden" name="prix_max" value="<?php echo (int) $prixMax; ?>">
        <?php if ($categorieActive): ?>
            <input type="hidden" name="categorie" value="<?php echo $categorieActive; ?>">
        <?php endif; ?>
        <?php if ($dispoOnly): ?>
            <input type="hidden" name="dispo" value="1">
        <?php endif; ?>

        <input type="text" name="search" placeholder="Rechercher un service..." value="<?php echo htmlspecialchars($search); ?>">

        <select name="tri" onchange="document.getElementById('srvTopFilterForm').submit()">
            <option value="pertinence" <?php echo $tri === 'pertinence' ? 'selected' : ''; ?>>Pertinence</option>
            <option value="prix_asc" <?php echo $tri === 'prix_asc' ? 'selected' : ''; ?>>Prix croissant</option>
            <option value="prix_desc" <?php echo $tri === 'prix_desc' ? 'selected' : ''; ?>>Prix decroissant</option>
            <option value="az" <?php echo $tri === 'az' ? 'selected' : ''; ?>>Nom A-Z</option>
        </select>

        <div class="icon-actions">
            <a class="solid-btn" href="index.php?page=services">Tous les services</a>
            <a class="solid-btn alt-btn" href="index.php?page=myServices">Mes services</a>
            <a class="solid-btn" href="index.php?page=myReservations" style="background:var(--orange-dark,#c94c14);">
                Mes reservations
            </a>
        </div>
    </form>
</section>

<section class="srv-wrap">
    <aside class="srv-filter-box">
        <form method="GET" action="index.php" class="srv-filter-form" id="srvFilterForm">
            <input type="hidden" name="page" value="services">
            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">

            <div class="srv-filter-block">
                <div class="srv-filter-title">CATEGORIES</div>
                <div class="srv-category-list">
                    <a href="index.php?page=services&search=<?php echo urlencode($search); ?>&prix_max=<?php echo (int) $prixMax; ?><?php echo $dispoOnly ? '&dispo=1' : ''; ?>"
                       class="srv-category-pill <?php echo $categorieActive === 0 ? 'active' : ''; ?>">
                        <span class="cat-icon">Dossier</span>
                        <span>Toutes</span>
                    </a>

                    <?php foreach ($categories as $cat): ?>
                        <a
                            href="index.php?page=services&categorie=<?php echo (int) $cat['id_categorie']; ?>&search=<?php echo urlencode($search); ?>&prix_max=<?php echo (int) $prixMax; ?><?php echo $dispoOnly ? '&dispo=1' : ''; ?>"
                            class="srv-category-pill <?php echo $categorieActive === (int) $cat['id_categorie'] ? 'active' : ''; ?>"
                        >
                            <span class="cat-icon"><?php echo htmlspecialchars((string) ($cat['icone'] ?? '')); ?></span>
                            <span><?php echo htmlspecialchars((string) ($cat['nom'] ?? '')); ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="srv-filter-block">
                <div class="srv-filter-title">PRIX MAXIMUM</div>
                <?php if ($categorieActive > 0): ?>
                    <input type="hidden" name="categorie" value="<?php echo $categorieActive; ?>">
                <?php endif; ?>

                <div class="srv-price-values">
                    <span>0 EUR</span>
                    <span id="srvPrixTop"><?php echo (int) $prixMax; ?> EUR</span>
                </div>

                <input
                    type="range"
                    name="prix_max"
                    min="0"
                    max="500"
                    step="10"
                    value="<?php echo (int) $prixMax; ?>"
                    class="srv-range"
                    id="srvPrixRange"
                >

                <div class="srv-price-current" id="srvPrixValue">
                    <?php echo (int) $prixMax; ?> EUR
                </div>
            </div>

            <div class="srv-filter-block">
                <div class="srv-filter-title">DISPONIBILITE</div>
                <div class="srv-toggle-row">
                    <span>Disponible maintenant</span>
                    <label class="srv-switch">
                        <input type="checkbox" name="dispo" value="1" id="srvDispoToggle" <?php echo $dispoOnly ? 'checked' : ''; ?>>
                        <span class="srv-slider"></span>
                    </label>
                </div>

                <div class="icon-actions" style="margin-top:18px;">
                    <a class="solid-btn" href="index.php?page=addService">+ Ajouter service</a>
                </div>
            </div>
        </form>
    </aside>

    <main class="srv-content-box">
        <div class="srv-stats-strip">
            <div class="srv-stat-item">
                <strong><?php echo $totalServices; ?></strong>
                <span>services</span>
            </div>
            <div class="srv-stat-item">
                <strong><?php echo $totalCategories; ?></strong>
                <span>categories</span>
            </div>
            <div class="srv-stat-item">
                <strong><?php echo $totalDisponibles; ?></strong>
                <span>disponibles maintenant</span>
            </div>
        </div>

        <div class="srv-grid">
            <?php if (!empty($servicePage)): ?>
                <?php foreach ($servicePage as $service): ?>
                    <?php
                    $img = !empty($service['image'])
                        ? '../../' . ltrim((string) $service['image'], '/')
                        : '../../assets/images/services/default.jpg';

                    $catSlug = srvCategorySlug((string) ($service['nom_categorie'] ?? ''));
                    $isAvailable = srvIsAvailable($service);
                    [$rating, $reviews] = srvRating($service);
                    ?>

                    <article class="srv-card">
                        <div class="srv-card-top">
                            <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars((string) ($service['titre'] ?? 'Service')); ?>" class="srv-card-image">

                            <div class="srv-card-badges">
                                <span class="srv-badge srv-badge-pop">Populaire</span>
                                <span class="srv-badge srv-badge-status <?php echo $isAvailable ? 'available' : 'unavailable'; ?>">
                                    <?php echo $isAvailable ? 'Disponible' : 'Indisponible'; ?>
                                </span>
                            </div>

                            <span class="srv-dot srv-dot-<?php echo $catSlug; ?>"></span>
                        </div>

                        <div class="srv-card-body">
                            <div class="srv-card-category">
                                <span class="cat-icon"><?php echo htmlspecialchars((string) ($service['icone_categorie'] ?? '')); ?></span>
                                <span><?php echo htmlspecialchars((string) ($service['nom_categorie'] ?? '')); ?></span>
                            </div>

                            <h3 class="srv-card-title"><?php echo htmlspecialchars((string) ($service['titre'] ?? '')); ?></h3>

                            <div class="srv-rating-row">
                                <span class="srv-stars">★★★★★</span>
                                <span><?php echo $rating; ?> · <?php echo $reviews; ?> avis</span>
                            </div>

                            <div class="srv-card-divider"></div>

                            <div class="srv-card-footer">
                                <div class="srv-price-wrap">
                                    <span class="srv-price-main">
                                        <?php echo number_format((float) ($service['prix'] ?? 0), 2, ',', ''); ?> EUR
                                    </span>
                                    <span class="srv-price-unit">/ seance</span>
                                </div>

                                <a href="index.php?page=serviceDetails&id=<?php echo (int) ($service['id_service'] ?? 0); ?>" class="srv-details-btn">
                                    Voir details →
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="srv-empty">
                    <span class="section-badge">Aucun resultat</span>
                    <h3>Aucun service trouve</h3>
                    <p>Essayez une autre categorie, un autre mot-cle ou modifiez les filtres.</p>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <?php
            $paginationBase = 'index.php?page=services'
                . ($search ? '&search=' . urlencode($search) : '')
                . ($categorieActive ? '&categorie=' . $categorieActive : '')
                . ($prixMax < 500 ? '&prix_max=' . (int) $prixMax : '')
                . ($dispoOnly ? '&dispo=1' : '')
                . ($tri !== 'pertinence' ? '&tri=' . urlencode($tri) : '');
            ?>

            <nav class="srv-pagination" aria-label="Pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="<?php echo $paginationBase; ?>&p=<?php echo $currentPage - 1; ?>" class="pg-btn">← Prec.</a>
                <?php endif; ?>

                <?php for ($pg = 1; $pg <= $totalPages; $pg++): ?>
                    <?php if ($pg === 1 || $pg === $totalPages || abs($pg - $currentPage) <= 1): ?>
                        <a href="<?php echo $paginationBase; ?>&p=<?php echo $pg; ?>" class="pg-btn <?php echo $pg === $currentPage ? 'pg-active' : ''; ?>">
                            <?php echo $pg; ?>
                        </a>
                    <?php elseif (abs($pg - $currentPage) === 2): ?>
                        <span class="pg-dots">...</span>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="<?php echo $paginationBase; ?>&p=<?php echo $currentPage + 1; ?>" class="pg-btn">Suiv. →</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </main>
</section>

<?php
$servicesMap = $serviceController->listServicesWithCoords();
if (!empty($servicesMap)):
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>
<style>
.all-map-section { padding: 0 0 48px; }
.all-map-card {
    margin: 0 24px;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 20px 50px rgba(7,20,34,.13);
    border: 1px solid var(--border);
    background: var(--panel);
}
.all-map-header {
    padding: 22px 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
    border-bottom: 1px solid var(--border);
}
.all-map-title {
    font-size: 20px;
    font-weight: 900;
    color: var(--text);
    margin: 0;
}
.all-map-sub {
    font-size: 13px;
    color: var(--muted);
    margin-top: 3px;
}
.all-map-badge {
    background: rgba(238,88,40,.1);
    color: #ee5828;
    border: 1px solid rgba(238,88,40,.2);
    padding: 6px 14px;
    border-radius: 999px;
    font-size: 13px;
    font-weight: 700;
}
#allServicesMap { height: 440px; width: 100%; }
@media(max-width:640px){
    .all-map-card { margin: 0 12px; }
    #allServicesMap { height: 300px; }
}
</style>

<section class="all-map-section">
    <div class="all-map-card">
        <div class="all-map-header">
            <div>
                <div class="all-map-title">Carte des prestataires</div>
                <div class="all-map-sub">Tous les services disponibles pres de chez vous</div>
            </div>
            <span class="all-map-badge"><?= count($servicesMap) ?> prestataire<?= count($servicesMap) > 1 ? 's' : '' ?> localise<?= count($servicesMap) > 1 ? 's' : '' ?></span>
        </div>
        <div id="allServicesMap"></div>
    </div>
</section>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function(){
    const services = <?= json_encode(array_map(fn($s) => [
        'id'       => (int)$s['id_service'],
        'titre'    => $s['titre'],
        'cat'      => $s['nom_categorie'],
        'icone'    => $s['icone_categorie'] ?? '',
        'prix'     => number_format((float)$s['prix'], 2, ',', ''),
        'dispo'    => $s['disponibilite'],
        'adresse'  => $s['adresse'] ?? '',
        'lat'      => (float)$s['latitude'],
        'lng'      => (float)$s['longitude'],
        'url'      => 'index.php?page=serviceDetails&id=' . (int)$s['id_service'],
    ], $servicesMap)) ?>;

    if (!services.length) return;

    const avgLat = services.reduce((s,v) => s + v.lat, 0) / services.length;
    const avgLng = services.reduce((s,v) => s + v.lng, 0) / services.length;

    const map = L.map('allServicesMap', { scrollWheelZoom: false }).setView([avgLat, avgLng], 7);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://openstreetmap.org">OpenStreetMap</a>',
        maxZoom: 19
    }).addTo(map);

    const bounds = [];

    services.forEach(s => {
        const dispo = s.dispo === 'Disponible';
        const icon = L.divIcon({
            html: `<div style="
                background:${dispo ? 'linear-gradient(135deg,#4cd774,#28a745)' : 'linear-gradient(135deg,#ff8b8b,#e05555)'};
                width:32px;height:32px;
                border-radius:50% 50% 50% 0;
                transform:rotate(-45deg);
                border:3px solid #fff;
                box-shadow:0 4px 14px rgba(0,0,0,.25);
            "></div>`,
            iconSize: [32, 32],
            iconAnchor: [16, 32],
            className: ''
        });

        const popup = `
            <div style="font-family:'Poppins',sans-serif;min-width:190px;padding:6px 2px;">
                <div style="font-weight:800;font-size:14px;color:#0e2941;margin-bottom:5px;">${s.icone} ${s.titre}</div>
                <div style="font-size:12px;color:#617084;margin-bottom:4px;">${s.cat}</div>
                <div style="font-size:13px;font-weight:700;color:#ee5828;margin-bottom:5px;">${s.prix} EUR</div>
                <div style="font-size:11px;margin-bottom:8px;">
                    <span style="background:${dispo?'rgba(76,215,116,.15)':'rgba(255,139,139,.15)'};
                                 color:${dispo?'#28a745':'#e05555'};
                                 padding:2px 8px;border-radius:99px;font-weight:700;">
                        ${s.dispo}
                    </span>
                </div>
                <a href="${s.url}" style="display:inline-block;background:linear-gradient(135deg,#ee5828,#c94718);
                   color:#fff;padding:7px 14px;border-radius:8px;font-size:12px;font-weight:700;
                   text-decoration:none;">Voir le service →</a>
            </div>`;

        L.marker([s.lat, s.lng], { icon }).addTo(map).bindPopup(popup, { maxWidth: 240 });
        bounds.push([s.lat, s.lng]);
    });

    if (bounds.length > 1) {
        map.fitBounds(bounds, { padding: [40, 40] });
    }
})();
</script>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('srvFilterForm');
    const prixRange = document.getElementById('srvPrixRange');
    const prixValue = document.getElementById('srvPrixValue');
    const prixTop = document.getElementById('srvPrixTop');
    const dispoToggle = document.getElementById('srvDispoToggle');

    if (prixRange) {
        prixRange.addEventListener('input', function () {
            prixValue.textContent = this.value + ' EUR';
            prixTop.textContent = this.value + ' EUR';
        });

        prixRange.addEventListener('change', function () {
            form.submit();
        });
    }

    if (dispoToggle) {
        dispoToggle.addEventListener('change', function () {
            form.submit();
        });
    }
});
</script>
