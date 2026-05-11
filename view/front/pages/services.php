<?php
require_once __DIR__ . '/../../../controller/ServiceController.php';
require_once __DIR__ . '/../../../controller/CategorieController.php';

$serviceController = new ServiceController();
$categorieController = new CategorieController();

$services = $serviceController->listServicesWithCategories();

/* afficher seulement les services validés */
$services = array_filter($services, function ($service) {
    return isset($service['statut']) && trim((string)$service['statut']) === 'Validé';
});

$services = array_values($services);

$categories = $categorieController->listCategories();

$categorieActive = isset($_GET['categorie']) ? (int)$_GET['categorie'] : 0;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$prixMax = isset($_GET['prix_max']) ? (float)$_GET['prix_max'] : 500;
$dispoOnly = isset($_GET['dispo']) ? 1 : 0;
$tri = isset($_GET['tri']) ? trim($_GET['tri']) : 'pertinence';

function srvCategorySlug(string $name): string {
    $name = mb_strtolower(trim($name));
    $map = [
        'plomberie' => 'plomberie',
        'électricité' => 'electricite',
        'electricite' => 'electricite',
        'peinture' => 'peinture',
        'informatique' => 'informatique',
        'jardinage' => 'jardinage',
        'ménage' => 'menage',
        'menage' => 'menage',
        'beauté' => 'beaute',
        'beaute' => 'beaute',
        'secrétariat' => 'secretariat',
        'secretariat' => 'secretariat',
    ];
    return $map[$name] ?? 'default';
}

function srvIsAvailable(array $service): bool {
    if (isset($service['disponibilite'])) {
        return mb_strtolower(trim((string)$service['disponibilite'])) === 'disponible';
    }

    if (isset($service['statut'])) {
        $s = mb_strtolower(trim((string)$service['statut']));
        return in_array($s, ['disponible', 'active', 'actif', 'ouvert', 'ouverte'], true);
    }

    return false;
}

function srvRating(array $service): array {
    $id = (int)($service['id_service'] ?? 1);
    $rating = 4.0 + (($id % 7) * 0.1);

    if ($rating > 4.9) {
        $rating = 4.9;
    }

    $reviews = 20 + ($id * 7 % 35);
    return [number_format($rating, 1), $reviews];
}

$servicesFiltres = array_filter($services, function ($service) use ($categorieActive, $search, $prixMax, $dispoOnly) {
    $matchCategorie = ($categorieActive === 0 || (int)$service['id_categorie'] === $categorieActive);

    $texte = mb_strtolower(
        ($service['titre'] ?? '') . ' ' .
        ($service['description'] ?? '') . ' ' .
        ($service['nom_categorie'] ?? '')
    );

    $matchSearch = ($search === '' || mb_strpos($texte, mb_strtolower($search)) !== false);

    $prixService = isset($service['prix']) ? (float)$service['prix'] : 0;
    $matchPrix = ($prixService <= $prixMax);

    $matchDispo = (!$dispoOnly || srvIsAvailable($service));

    return $matchCategorie && $matchSearch && $matchPrix && $matchDispo;
});

$servicesFiltres = array_values($servicesFiltres);

// ── TRI ──
usort($servicesFiltres, function ($a, $b) use ($tri) {
    switch ($tri) {
        case 'prix_asc':
            return (float)($a['prix'] ?? 0) <=> (float)($b['prix'] ?? 0);
        case 'prix_desc':
            return (float)($b['prix'] ?? 0) <=> (float)($a['prix'] ?? 0);
        case 'avis_desc':
            $rA = 4.0 + (((int)($a['id_service'] ?? 1) % 7) * 0.1);
            $rB = 4.0 + (((int)($b['id_service'] ?? 1) % 7) * 0.1);
            return $rB <=> $rA;
        case 'az':
            return strcmp($a['titre'] ?? '', $b['titre'] ?? '');
        case 'pertinence':
        default:
            return 0;
    }
});

// ── PAGINATION ──
$perPage = 6;
$totalFiltres = count($servicesFiltres);
$totalPages = max(1, (int)ceil($totalFiltres / $perPage));
$currentPage = isset($_GET['p']) ? max(1, min((int)$_GET['p'], $totalPages)) : 1;
$offset = ($currentPage - 1) * $perPage;
$servicePage = array_slice($servicesFiltres, $offset, $perPage);

$totalServices = count($services);
$totalCategories = count($categories);
$totalDisponibles = count(array_filter($services, fn($s) => srvIsAvailable($s)));
?>

<style>
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
    <h1 class="page-title">Services & catégories</h1>
    <p class="page-intro">
        Consultez les services disponibles, explorez les catégories et trouvez rapidement la prestation adaptée à votre besoin.
    </p>
</section>

<section class="action-bar reveal">
    <form class="search-box srv-top-search" method="GET" action="index.php" id="srvTopFilterForm">
        <input type="hidden" name="page" value="services">
        <input type="hidden" name="prix_max" value="<?php echo (int)$prixMax; ?>">
        <?php if ($categorieActive): ?>
    <input type="hidden" name="categorie" value="<?php echo $categorieActive; ?>">
<?php endif; ?>

        <?php if ($dispoOnly): ?>
            <input type="hidden" name="dispo" value="1">
        <?php endif; ?>

        <input
            type="text"
            name="search"
            placeholder="Rechercher un service..."
            value="<?php echo htmlspecialchars($search); ?>"
        >

        <select name="tri" onchange="document.getElementById('srvTopFilterForm').submit()">
    <option value="pertinence" <?php echo $tri === 'pertinence' ? 'selected' : ''; ?>>Pertinence</option>
    <option value="prix_asc" <?php echo $tri === 'prix_asc' ? 'selected' : ''; ?>>Prix ↑</option>
    <option value="prix_desc" <?php echo $tri === 'prix_desc' ? 'selected' : ''; ?>>Prix ↓</option>
    <option value="az" <?php echo $tri === 'az' ? 'selected' : ''; ?>>Nom A → Z</option>
</select>

        <div class="icon-actions">
            <a class="solid-btn" href="index.php?page=services">+ Tous les services</a>
            <a class="solid-btn alt-btn" href="index.php?page=myServices">Mes services</a>
            <a class="solid-btn" href="index.php?page=myReservations" style="background:var(--orange-dark,#c94c14);">
                📋 Mes réservations
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
                <div class="srv-filter-title">CATÉGORIES</div>

                <div class="srv-category-list">
                    <a href="index.php?page=services&search=<?php echo urlencode($search); ?>&prix_max=<?php echo (int)$prixMax; ?><?php echo $dispoOnly ? '&dispo=1' : ''; ?>"
                       class="srv-category-pill <?php echo $categorieActive === 0 ? 'active' : ''; ?>">
                        <span class="cat-icon">📂</span>
                        <span>Toutes</span>
                    </a>

                    <?php foreach ($categories as $cat): ?>
                        <a
                            href="index.php?page=services&categorie=<?php echo (int)$cat['id_categorie']; ?>&search=<?php echo urlencode($search); ?>&prix_max=<?php echo (int)$prixMax; ?><?php echo $dispoOnly ? '&dispo=1' : ''; ?>"
                            class="srv-category-pill <?php echo $categorieActive === (int)$cat['id_categorie'] ? 'active' : ''; ?>"
                        >
                            <span class="cat-icon"><?php echo htmlspecialchars($cat['icone'] ?? '📂'); ?></span>
                            <span><?php echo htmlspecialchars($cat['nom']); ?></span>
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
                    <span>0 €</span>
                    <span id="srvPrixTop"><?php echo (int)$prixMax; ?> €</span>
                </div>

                <input
                    type="range"
                    name="prix_max"
                    min="0"
                    max="500"
                    step="10"
                    value="<?php echo (int)$prixMax; ?>"
                    class="srv-range"
                    id="srvPrixRange"
                >

                <div class="srv-price-current" id="srvPrixValue">
                    <?php echo (int)$prixMax; ?> €
                </div>
            </div>

            <div class="srv-filter-block">
                <div class="srv-filter-title">DISPONIBILITÉ</div>

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
                <span>catégories</span>
            </div>

            <div class="srv-stat-item">
                <strong><?php echo $totalDisponibles; ?></strong>
                <span>disponibles maintenant</span>
            </div>
        </div>

        <div class="srv-content-topbar" style="justify-content:flex-end;">
            
        </div>

        <div class="srv-grid">
            <?php if (!empty($servicePage)): ?>
                <?php foreach ($servicePage as $service): ?>
                    <?php
                    $img = !empty($service['image'])
                        ? '/GoService_v3/' . ltrim($service['image'], '/')
                        : '/GoService/assets/images/service/default.jpg';

                    $catSlug = srvCategorySlug($service['nom_categorie'] ?? '');
                    $isAvailable = srvIsAvailable($service);
                    [$rating, $reviews] = srvRating($service);
                    ?>

                    <article class="srv-card">
                        <div class="srv-card-top">
                            <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($service['titre']); ?>" class="srv-card-image">

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
                                <span class="cat-icon"><?php echo htmlspecialchars($service['icone_categorie'] ?? '📂'); ?></span>
                                <span><?php echo htmlspecialchars($service['nom_categorie']); ?></span>
                            </div>

                            <h3 class="srv-card-title">
                                <?php echo htmlspecialchars($service['titre']); ?>
                            </h3>

                            <div class="srv-rating-row">
                                <span class="srv-stars">★★★★★</span>
                                <span><?php echo $rating; ?> · <?php echo $reviews; ?> avis</span>
                            </div>

                            <div class="srv-card-divider"></div>

                            <div class="srv-card-footer">
                                <div class="srv-price-wrap">
                                    <span class="srv-price-main">
                                        <?php echo number_format((float)$service['prix'], 2, ',', ''); ?> €
                                    </span>
                                    <span class="srv-price-unit">/ séance</span>
                                </div>

                                <a href="index.php?page=serviceDetails&id=<?php echo (int)$service['id_service']; ?>" class="srv-details-btn">
                                    Voir détails →
                                </a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="srv-empty">
                    <span class="section-badge">Aucun résultat</span>
                    <h3>Aucun service trouvé</h3>
                    <p>Essayez une autre catégorie, un autre mot-clé ou modifiez les filtres.</p>
                </div>
            <?php endif; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <?php
            $paginationBase = 'index.php?page=services'
                . ($search ? '&search=' . urlencode($search) : '')
                . ($categorieActive ? '&categorie=' . $categorieActive : '')
                . ($prixMax < 500 ? '&prix_max=' . (int)$prixMax : '')
                . ($dispoOnly ? '&dispo=1' : '')
                . ($tri !== 'pertinence' ? '&tri=' . urlencode($tri) : '');
            ?>

            <nav class="srv-pagination" aria-label="Pagination">
                <?php if ($currentPage > 1): ?>
                    <a href="<?php echo $paginationBase; ?>&p=<?php echo $currentPage - 1; ?>" class="pg-btn">← Préc.</a>
                <?php endif; ?>

                <?php for ($pg = 1; $pg <= $totalPages; $pg++): ?>
                    <?php if ($pg === 1 || $pg === $totalPages || abs($pg - $currentPage) <= 1): ?>
                        <a href="<?php echo $paginationBase; ?>&p=<?php echo $pg; ?>"
                           class="pg-btn <?php echo $pg === $currentPage ? 'pg-active' : ''; ?>">
                            <?php echo $pg; ?>
                        </a>
                    <?php elseif (abs($pg - $currentPage) === 2): ?>
                        <span class="pg-dots">…</span>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a href="<?php echo $paginationBase; ?>&p=<?php echo $currentPage + 1; ?>" class="pg-btn">Suiv. →</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </main>
</section>

<!-- ══════════════════════════════════════════════════════
     CARTE GLOBALE — Tous les prestataires géolocalisés
     ══════════════════════════════════════════════════════ -->
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
                <div class="all-map-title">🗺️ Carte des prestataires</div>
                <div class="all-map-sub">Tous les services disponibles près de chez vous</div>
            </div>
            <span class="all-map-badge"><?= count($servicesMap) ?> prestataire<?= count($servicesMap) > 1 ? 's' : '' ?> localisé<?= count($servicesMap) > 1 ? 's' : '' ?></span>
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

    // Centre sur le barycentre des points
    const avgLat = services.reduce((s,v) => s + v.lat, 0) / services.length;
    const avgLng = services.reduce((s,v) => s + v.lng, 0) / services.length;

    const map = L.map('allServicesMap', { scrollWheelZoom: false })
                 .setView([avgLat, avgLng], 7);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://openstreetmap.org">OpenStreetMap</a>',
        maxZoom: 19
    }).addTo(map);

    const bounds = [];

    services.forEach(s => {
        const dispo = s.dispo === 'Disponible';
        const color = dispo ? '#4cd774' : '#ff8b8b';

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
                <div style="font-size:12px;color:#617084;margin-bottom:4px;">📂 ${s.cat}</div>
                <div style="font-size:13px;font-weight:700;color:#ee5828;margin-bottom:5px;">💶 ${s.prix} €</div>
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

        L.marker([s.lat, s.lng], { icon })
         .addTo(map)
         .bindPopup(popup, { maxWidth: 240 });

        bounds.push([s.lat, s.lng]);
    });

    // Ajuster le zoom pour voir tous les marqueurs
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
            prixValue.textContent = this.value + ' €';
            prixTop.textContent = this.value + ' €';
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