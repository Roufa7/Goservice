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
    if ($rating > 4.9) $rating = 4.9;
    $reviews = 20 + ($id * 7 % 35);
    return [number_format($rating, 1), $reviews];
}

// ── Filtrage PHP (catégorie, prix, dispo, tri) — la recherche texte se fait en JS ──
$servicesFiltres = array_filter($services, function ($service) use ($categorieActive, $prixMax, $dispoOnly) {
    $matchCategorie = ($categorieActive === 0 || (int)$service['id_categorie'] === $categorieActive);
    $prixService = isset($service['prix']) ? (float)$service['prix'] : 0;
    $matchPrix = ($prixService <= $prixMax);
    $matchDispo = (!$dispoOnly || srvIsAvailable($service));
    return $matchCategorie && $matchPrix && $matchDispo;
});

$servicesFiltres = array_values($servicesFiltres);

// ── TRI ──
usort($servicesFiltres, function ($a, $b) use ($tri) {
    switch ($tri) {
        case 'prix_asc':  return (float)($a['prix'] ?? 0) <=> (float)($b['prix'] ?? 0);
        case 'prix_desc': return (float)($b['prix'] ?? 0) <=> (float)($a['prix'] ?? 0);
        case 'avis_desc':
            $rA = 4.0 + (((int)($a['id_service'] ?? 1) % 7) * 0.1);
            $rB = 4.0 + (((int)($b['id_service'] ?? 1) % 7) * 0.1);
            return $rB <=> $rA;
        case 'az': return strcmp($a['titre'] ?? '', $b['titre'] ?? '');
        default:   return 0;
    }
});

// ── On prépare TOUS les services filtrés (hors search) en JSON pour le JS ──
$allServicesJson = [];
foreach ($servicesFiltres as $service) {
    [$rating, $reviews] = srvRating($service);
    $img = !empty($service['image'])
        ? '/GoService_v3/' . ltrim($service['image'], '/')
        : '/GoService/assets/images/service/default.jpg';
    $isAvailable = srvIsAvailable($service);
    $catSlug = srvCategorySlug($service['nom_categorie'] ?? '');
    $allServicesJson[] = [
        'id'          => (int)$service['id_service'],
        'titre'       => $service['titre'] ?? '',
        'description' => $service['description'] ?? '',
        'nom_categorie' => $service['nom_categorie'] ?? '',
        'icone_categorie' => $service['icone_categorie'] ?? '📂',
        'cat_slug'    => $catSlug,
        'prix'        => number_format((float)($service['prix'] ?? 0), 2, ',', ''),
        'rating'      => $rating,
        'reviews'     => $reviews,
        'available'   => $isAvailable,
        'img'         => $img,
        'url'         => 'index.php?page=serviceDetails&id=' . (int)$service['id_service'],
        // texte de recherche pré-normalisé côté serveur
        'search_text' => mb_strtolower(
            ($service['titre'] ?? '') . ' ' .
            ($service['description'] ?? '') . ' ' .
            ($service['nom_categorie'] ?? '')
        ),
    ];
}

$totalServices    = count($services);
$totalCategories  = count($categories);
$totalDisponibles = count(array_filter($services, fn($s) => srvIsAvailable($s)));
$perPage          = 6;
?>

<style>
/* ── Base ── */
.cat-icon{display:inline-flex;align-items:center;justify-content:center;font-size:18px;line-height:1;margin-right:8px;flex-shrink:0}
.srv-category-pill{display:flex;align-items:center;gap:8px}
.srv-card-category{display:flex;align-items:center;gap:8px}

/* ── Pagination ── */
.srv-pagination{display:flex;align-items:center;justify-content:center;gap:6px;padding:24px 0 8px;flex-wrap:wrap}
.pg-btn{padding:8px 14px;border-radius:10px;border:1px solid var(--line);background:var(--card);color:var(--text);font-size:13px;font-weight:600;cursor:pointer;transition:all .18s;text-decoration:none;font-family:inherit}
.pg-btn:hover{border-color:#ee5828;color:#ee5828;background:rgba(238,88,40,.07)}
.pg-active{background:#ee5828!important;color:#fff!important;border-color:#ee5828!important}
.pg-dots{padding:8px 4px;color:var(--muted);font-size:13px}

/* ── Search highlight ── */
mark.srv-hl{background:rgba(238,88,40,.18);color:inherit;border-radius:3px;padding:0 2px;font-weight:700}

/* ── Searchbox live indicator ── */
.srv-search-wrap{position:relative;flex:1}
.srv-search-wrap input{width:100%;box-sizing:border-box;padding-right:38px}
.srv-search-clear{position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--muted);font-size:18px;line-height:1;display:none;padding:0}
.srv-search-clear.visible{display:block}
.srv-search-spinner{position:absolute;right:10px;top:50%;transform:translateY(-50%);width:16px;height:16px;border:2px solid rgba(238,88,40,.2);border-top-color:#ee5828;border-radius:50%;animation:spin .6s linear infinite;display:none}
.srv-search-spinner.visible{display:block}
@keyframes spin{to{transform:translateY(-50%) rotate(360deg)}}

/* ── Live results count badge ── */
#srvResultCount{font-size:13px;color:var(--muted);font-weight:600;transition:all .2s}
#srvResultCount span{color:#ee5828}

/* ── No results ── */
.srv-no-results{display:none;text-align:center;padding:60px 20px;grid-column:1/-1}
.srv-no-results.visible{display:block}
.srv-no-results-icon{font-size:48px;margin-bottom:12px}
.srv-no-results h3{font-size:20px;font-weight:800;color:var(--text);margin:0 0 8px}
.srv-no-results p{color:var(--muted);font-size:14px;margin:0 0 16px}
.srv-no-results button{background:#ee5828;color:#fff;border:none;padding:10px 22px;border-radius:12px;font-size:14px;font-weight:700;cursor:pointer}

/* ── Card fade animation ── */
.srv-card{animation:cardIn .25s ease both}
@keyframes cardIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:none}}
</style>

<section class="page-hero reveal">
    <span class="section-badge">Services</span>
    <h1 class="page-title">Services & catégories</h1>
    <p class="page-intro">
        Consultez les services disponibles, explorez les catégories et trouvez rapidement la prestation adaptée à votre besoin.
    </p>
</section>

<section class="action-bar reveal">
    <div class="search-box srv-top-search">
        <!-- Champ recherche temps réel -->
        <div class="srv-search-wrap">
            <input
                type="text"
                id="liveServiceSearch"
                placeholder="Rechercher un service..."
                autocomplete="off"
                value="<?php echo htmlspecialchars($search); ?>"
            >
            <button class="srv-search-clear" id="srvSearchClear" title="Effacer">✕</button>
            <div class="srv-search-spinner" id="srvSearchSpinner"></div>
        </div>

        <!-- Tri (recharge la page) -->
        <form id="srvSortForm" method="GET" action="index.php" style="display:contents">
            <input type="hidden" name="page" value="services">
            <input type="hidden" name="prix_max" id="srvSortPrix" value="<?php echo (int)$prixMax; ?>">
            <?php if ($categorieActive): ?><input type="hidden" name="categorie" value="<?php echo $categorieActive; ?>"><?php endif; ?>
            <?php if ($dispoOnly): ?><input type="hidden" name="dispo" value="1"><?php endif; ?>
            <select name="tri" onchange="document.getElementById('srvSortForm').submit()">
                <option value="pertinence" <?php echo $tri === 'pertinence' ? 'selected' : ''; ?>>Pertinence</option>
                <option value="prix_asc"   <?php echo $tri === 'prix_asc'   ? 'selected' : ''; ?>>Prix ↑</option>
                <option value="prix_desc"  <?php echo $tri === 'prix_desc'  ? 'selected' : ''; ?>>Prix ↓</option>
                <option value="az"         <?php echo $tri === 'az'         ? 'selected' : ''; ?>>Nom A → Z</option>
            </select>
        </form>

        <div class="icon-actions">
            <a class="solid-btn" href="index.php?page=services">+ Tous les services</a>
            <a class="solid-btn alt-btn" href="index.php?page=myServices">Mes services</a>
            <a class="solid-btn" href="index.php?page=myReservations" style="background:var(--orange-dark,#c94c14);">
                📋 Mes réservations
            </a>
        </div>
    </div>
</section>

<section class="srv-wrap">
    <aside class="srv-filter-box">
        <form method="GET" action="index.php" class="srv-filter-form" id="srvFilterForm">
            <input type="hidden" name="page" value="services">
            <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">

            <div class="srv-filter-block">
                <div class="srv-filter-title">CATÉGORIES</div>
                <div class="srv-category-list">
                    <a href="index.php?page=services&prix_max=<?php echo (int)$prixMax; ?><?php echo $dispoOnly ? '&dispo=1' : ''; ?>&tri=<?php echo urlencode($tri); ?>"
                       class="srv-category-pill <?php echo $categorieActive === 0 ? 'active' : ''; ?>">
                        <span class="cat-icon">📂</span><span>Toutes</span>
                    </a>
                    <?php foreach ($categories as $cat): ?>
                        <a href="index.php?page=services&categorie=<?php echo (int)$cat['id_categorie']; ?>&prix_max=<?php echo (int)$prixMax; ?><?php echo $dispoOnly ? '&dispo=1' : ''; ?>&tri=<?php echo urlencode($tri); ?>"
                           class="srv-category-pill <?php echo $categorieActive === (int)$cat['id_categorie'] ? 'active' : ''; ?>">
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
                <input type="range" name="prix_max" min="0" max="500" step="10"
                       value="<?php echo (int)$prixMax; ?>" class="srv-range" id="srvPrixRange">
                <div class="srv-price-current" id="srvPrixValue"><?php echo (int)$prixMax; ?> €</div>
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
            <div class="srv-stat-item"><strong><?php echo $totalServices; ?></strong><span>services</span></div>
            <div class="srv-stat-item"><strong><?php echo $totalCategories; ?></strong><span>catégories</span></div>
            <div class="srv-stat-item"><strong><?php echo $totalDisponibles; ?></strong><span>disponibles maintenant</span></div>
        </div>

        <div class="srv-content-topbar" style="justify-content:flex-end;padding:8px 0;">
            <div id="srvResultCount"></div>
        </div>

        <!-- Grille rendue dynamiquement par JS -->
        <div class="srv-grid" id="srvGrid">
            <!-- remplie par renderServices() -->
        </div>

        <!-- "Aucun résultat" -->
        <div class="srv-no-results" id="srvNoResults">
            <div class="srv-no-results-icon">🔍</div>
            <h3>Aucun service trouvé</h3>
            <p id="srvNoResultsMsg">Essayez un autre mot-clé ou modifiez les filtres.</p>
            <button onclick="clearSearch()">Effacer la recherche</button>
        </div>

        <!-- Pagination rendue dynamiquement par JS -->
        <nav class="srv-pagination" id="srvPagination" aria-label="Pagination"></nav>
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
.all-map-section{padding:0 0 48px}
.all-map-card{margin:0 24px;border-radius:24px;overflow:hidden;box-shadow:0 20px 50px rgba(7,20,34,.13);border:1px solid var(--border);background:var(--panel)}
.all-map-header{padding:22px 28px;display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;border-bottom:1px solid var(--border)}
.all-map-title{font-size:20px;font-weight:900;color:var(--text);margin:0}
.all-map-sub{font-size:13px;color:var(--muted);margin-top:3px}
.all-map-badge{background:rgba(238,88,40,.1);color:#ee5828;border:1px solid rgba(238,88,40,.2);padding:6px 14px;border-radius:999px;font-size:13px;font-weight:700}
#allServicesMap{height:440px;width:100%}
@media(max-width:640px){.all-map-card{margin:0 12px}#allServicesMap{height:300px}}
</style>
<section class="all-map-section">
    <div class="all-map-card">
        <div class="all-map-header">
            <div>
                <div class="all-map-title">🗺️ Carte des services disponibles</div>
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
        'id'      => (int)$s['id_service'],
        'titre'   => $s['titre'],
        'cat'     => $s['nom_categorie'],
        'icone'   => $s['icone_categorie'] ?? '',
        'prix'    => number_format((float)$s['prix'], 2, ',', ''),
        'dispo'   => $s['disponibilite'],
        'adresse' => $s['adresse'] ?? '',
        'lat'     => (float)$s['latitude'],
        'lng'     => (float)$s['longitude'],
        'url'     => 'index.php?page=serviceDetails&id=' . (int)$s['id_service'],
    ], $servicesMap)) ?>;
    if (!services.length) return;
    const avgLat = services.reduce((s,v)=>s+v.lat,0)/services.length;
    const avgLng = services.reduce((s,v)=>s+v.lng,0)/services.length;
    const map = L.map('allServicesMap',{scrollWheelZoom:false}).setView([avgLat,avgLng],7);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',{attribution:'© <a href="https://openstreetmap.org">OpenStreetMap</a>',maxZoom:19}).addTo(map);
    const bounds=[];
    services.forEach(s=>{
        const dispo=s.dispo==='Disponible';
        const icon=L.divIcon({html:`<div style="background:${dispo?'linear-gradient(135deg,#4cd774,#28a745)':'linear-gradient(135deg,#ff8b8b,#e05555)'};width:32px;height:32px;border-radius:50% 50% 50% 0;transform:rotate(-45deg);border:3px solid #fff;box-shadow:0 4px 14px rgba(0,0,0,.25);"></div>`,iconSize:[32,32],iconAnchor:[16,32],className:''});
        const popup=`<div style="font-family:'Poppins',sans-serif;min-width:190px;padding:6px 2px;"><div style="font-weight:800;font-size:14px;color:#0e2941;margin-bottom:5px;">${s.icone} ${s.titre}</div><div style="font-size:12px;color:#617084;margin-bottom:4px;">📂 ${s.cat}</div><div style="font-size:13px;font-weight:700;color:#ee5828;margin-bottom:5px;">💶 ${s.prix} €</div><div style="font-size:11px;margin-bottom:8px;"><span style="background:${dispo?'rgba(76,215,116,.15)':'rgba(255,139,139,.15)'};color:${dispo?'#28a745':'#e05555'};padding:2px 8px;border-radius:99px;font-weight:700;">${s.dispo}</span></div><a href="${s.url}" style="display:inline-block;background:linear-gradient(135deg,#ee5828,#c94718);color:#fff;padding:7px 14px;border-radius:8px;font-size:12px;font-weight:700;text-decoration:none;">Voir le service →</a></div>`;
        L.marker([s.lat,s.lng],{icon}).addTo(map).bindPopup(popup,{maxWidth:240});
        bounds.push([s.lat,s.lng]);
    });
    if(bounds.length>1) map.fitBounds(bounds,{padding:[40,40]});
})();
</script>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════════
     MOTEUR DE RECHERCHE TEMPS RÉEL + PAGINATION JS
     ══════════════════════════════════════════════════════ -->
<script>
(function(){
    // ── Données serveur ──────────────────────────────────────────────
    const ALL_SERVICES = <?php echo json_encode($allServicesJson, JSON_UNESCAPED_UNICODE); ?>;
    const PER_PAGE = <?php echo $perPage; ?>;
    const INITIAL_SEARCH = <?php echo json_encode($search); ?>;

    // ── État ─────────────────────────────────────────────────────────
    let currentQuery = INITIAL_SEARCH;
    let currentPage  = 1;
    let filteredData = [];
    let debounceTimer = null;

    // ── Éléments DOM ─────────────────────────────────────────────────
    const input      = document.getElementById('liveServiceSearch');
    const clearBtn   = document.getElementById('srvSearchClear');
    const spinner    = document.getElementById('srvSearchSpinner');
    const grid       = document.getElementById('srvGrid');
    const paginNav   = document.getElementById('srvPagination');
    const noResults  = document.getElementById('srvNoResults');
    const noResMsg   = document.getElementById('srvNoResultsMsg');
    const countBadge = document.getElementById('srvResultCount');

    // ── Utilitaires ──────────────────────────────────────────────────
    function normalize(str){
        return str.toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g,'')
            .replace(/['']/g,"'");
    }

    function highlight(text, query){
        if(!query) return escHtml(text);
        const safe = escHtml(text);
        if(!query.trim()) return safe;
        const pattern = normalize(query).replace(/[.*+?^${}()|[\]\\]/g,'\\$&');
        // On opère sur le texte normalisé pour matcher, mais on affiche l'original
        const regex = new RegExp(pattern,'gi');
        const normSafe = normalize(safe);
        let result = '';
        let lastIdx = 0;
        let m;
        while((m = regex.exec(normSafe)) !== null){
            result += safe.slice(lastIdx, m.index);
            result += '<mark class="srv-hl">' + safe.slice(m.index, m.index + m[0].length) + '</mark>';
            lastIdx = m.index + m[0].length;
        }
        result += safe.slice(lastIdx);
        return result;
    }

    function escHtml(str){
        return String(str)
            .replace(/&/g,'&amp;')
            .replace(/</g,'&lt;')
            .replace(/>/g,'&gt;')
            .replace(/"/g,'&quot;');
    }

    // ── Filtrage ─────────────────────────────────────────────────────
    function filterServices(query){
        const q = normalize(query.trim());
        if(!q) return ALL_SERVICES;
        const terms = q.split(/\s+/).filter(Boolean);
        return ALL_SERVICES.filter(s => {
            const text = normalize(s.search_text);
            return terms.every(term => text.includes(term));
        });
    }

    // ── Rendu d'une carte ─────────────────────────────────────────────
    function renderCard(s, query){
        const availClass = s.available ? 'available' : 'unavailable';
        const availText  = s.available ? 'Disponible' : 'Indisponible';
        const stars = '★★★★★';
        const titleHl = highlight(s.titre, query);
        const catHl   = highlight(s.nom_categorie, query);
        return `
        <article class="srv-card">
            <div class="srv-card-top">
                <img src="${escHtml(s.img)}" alt="${escHtml(s.titre)}" class="srv-card-image">
                <div class="srv-card-badges">
                    <span class="srv-badge srv-badge-pop">Populaire</span>
                    <span class="srv-badge srv-badge-status ${availClass}">${availText}</span>
                </div>
                <span class="srv-dot srv-dot-${escHtml(s.cat_slug)}"></span>
            </div>
            <div class="srv-card-body">
                <div class="srv-card-category">
                    <span class="cat-icon">${s.icone_categorie}</span>
                    <span>${catHl}</span>
                </div>
                <h3 class="srv-card-title">${titleHl}</h3>
                <div class="srv-rating-row">
                    <span class="srv-stars">${stars}</span>
                    <span>${escHtml(s.rating)} · ${s.reviews} avis</span>
                </div>
                <div class="srv-card-divider"></div>
                <div class="srv-card-footer">
                    <div class="srv-price-wrap">
                        <span class="srv-price-main">${escHtml(s.prix)} €</span>
                        <span class="srv-price-unit">/ séance</span>
                    </div>
                    <a href="${escHtml(s.url)}" class="srv-details-btn">Voir détails →</a>
                </div>
            </div>
        </article>`;
    }

    // ── Rendu de la pagination ────────────────────────────────────────
    function renderPagination(total, page){
        const totalPages = Math.max(1, Math.ceil(total / PER_PAGE));
        paginNav.innerHTML = '';
        if(totalPages <= 1) return;

        const mk = (label, p, active, disabled) => {
            const btn = document.createElement('button');
            btn.className = 'pg-btn' + (active ? ' pg-active' : '') + (disabled ? ' pg-disabled' : '');
            btn.textContent = label;
            btn.disabled = disabled;
            if(!disabled) btn.addEventListener('click', () => goToPage(p));
            return btn;
        };

        if(page > 1) paginNav.appendChild(mk('← Préc.', page-1, false, false));

        for(let pg = 1; pg <= totalPages; pg++){
            if(pg === 1 || pg === totalPages || Math.abs(pg - page) <= 1){
                paginNav.appendChild(mk(String(pg), pg, pg === page, false));
            } else if(Math.abs(pg - page) === 2){
                const dots = document.createElement('span');
                dots.className = 'pg-dots';
                dots.textContent = '…';
                paginNav.appendChild(dots);
            }
        }

        if(page < totalPages) paginNav.appendChild(mk('Suiv. →', page+1, false, false));
    }

    // ── Rendu global ─────────────────────────────────────────────────
    function renderServices(){
        const offset = (currentPage - 1) * PER_PAGE;
        const page   = filteredData.slice(offset, offset + PER_PAGE);

        // Grille
        if(page.length === 0){
            grid.innerHTML = '';
            noResults.classList.add('visible');
            noResMsg.textContent = currentQuery.trim()
                ? `Aucun résultat pour "${currentQuery}". Essayez un autre mot-clé.`
                : 'Essayez une autre catégorie ou modifiez les filtres.';
        } else {
            noResults.classList.remove('visible');
            grid.innerHTML = page.map(s => renderCard(s, currentQuery)).join('');
        }

        // Compteur
        const total = filteredData.length;
        countBadge.innerHTML = currentQuery.trim()
            ? `<span>${total}</span> résultat${total > 1 ? 's' : ''} pour "<strong>${escHtml(currentQuery)}</strong>"`
            : `<span>${total}</span> service${total > 1 ? 's' : ''}`;

        // Pagination
        renderPagination(total, currentPage);
    }

    // ── Changement de page ────────────────────────────────────────────
    function goToPage(p){
        currentPage = p;
        renderServices();
        // Scroll doux vers le haut de la grille
        grid.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    // ── Lancement de la recherche ─────────────────────────────────────
    function applySearch(query){
        currentQuery  = query;
        currentPage   = 1;
        filteredData  = filterServices(query);
        renderServices();
        // Bouton clear
        clearBtn.classList.toggle('visible', query.length > 0);
        spinner.classList.remove('visible');
    }

    // ── Effacer la recherche ──────────────────────────────────────────
    window.clearSearch = function(){
        input.value = '';
        applySearch('');
        input.focus();
    };

    // ── Événements ───────────────────────────────────────────────────
    input.addEventListener('input', function(){
        const q = this.value;
        clearBtn.classList.toggle('visible', q.length > 0);
        spinner.classList.add('visible');
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => applySearch(q), 200);
    });

    clearBtn.addEventListener('click', window.clearSearch);

    // ── Init ─────────────────────────────────────────────────────────
    applySearch(INITIAL_SEARCH);

    // ── Filtres PHP (prix, dispo) ─────────────────────────────────────
    const form       = document.getElementById('srvFilterForm');
    const prixRange  = document.getElementById('srvPrixRange');
    const prixValue  = document.getElementById('srvPrixValue');
    const prixTop    = document.getElementById('srvPrixTop');
    const dispoToggle= document.getElementById('srvDispoToggle');

    if(prixRange){
        prixRange.addEventListener('input', function(){
            prixValue.textContent = this.value + ' €';
            prixTop.textContent   = this.value + ' €';
        });
        prixRange.addEventListener('change', () => form.submit());
    }
    if(dispoToggle){
        dispoToggle.addEventListener('change', () => form.submit());
    }
})();
</script>