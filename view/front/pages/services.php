<?php
require_once __DIR__ . '/../../../controller/ServiceController.php';
require_once __DIR__ . '/../../../controller/CategorieController.php';

$serviceController = new ServiceController();
$categorieController = new CategorieController();

$services = $serviceController->listServicesWithCategories();

/* 🔥 AFFICHER SEULEMENT LES SERVICES VALIDÉS */
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

$totalServices = count($services);
$totalCategories = count($categories);
$totalDisponibles = count(array_filter($services, fn($s) => srvIsAvailable($s)));
?>

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
        <?php if ($dispoOnly): ?>
            <input type="hidden" name="dispo" value="1">
        <?php endif; ?>

        <input
            type="text"
            name="search"
            placeholder="Rechercher un service..."
            value="<?php echo htmlspecialchars($search); ?>"
        >

        <select name="categorie" onchange="document.getElementById('srvTopFilterForm').submit()">
            <option value="0" <?php echo $categorieActive === 0 ? 'selected' : ''; ?>>Toutes les catégories</option>
            <?php foreach ($categories as $cat): ?>
                <option value="<?php echo (int)$cat['id_categorie']; ?>" <?php echo $categorieActive === (int)$cat['id_categorie'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat['nom']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div class="icon-actions">
    <a class="solid-btn" href="index.php?page=services">+ Tous les services</a>
    <a class="solid-btn alt-btn" href="index.php?page=myServices">Mes services</a>
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
                        Toutes
                    </a>

                    <?php foreach ($categories as $cat): ?>
                        <a
                            href="index.php?page=services&categorie=<?php echo (int)$cat['id_categorie']; ?>&search=<?php echo urlencode($search); ?>&prix_max=<?php echo (int)$prixMax; ?><?php echo $dispoOnly ? '&dispo=1' : ''; ?>"
                            class="srv-category-pill <?php echo $categorieActive === (int)$cat['id_categorie'] ? 'active' : ''; ?>"
                        >
                            <?php echo htmlspecialchars($cat['nom']); ?>
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

    <div class="icon-actions" style="margin-top: 18px;">
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

        <div class="srv-content-topbar">
            <div class="srv-results-count">
                <?php echo count($servicesFiltres); ?> service(s) trouvé(s)
            </div>
        </div>

        <div class="srv-grid">
            <?php if (!empty($servicesFiltres)): ?>
                <?php foreach ($servicesFiltres as $service): ?>
                    <?php
                    $img = !empty($service['image'])
                        ? '/GoService/' . ltrim($service['image'], '/')
                        : '/GoService/assets/images/service/default.jpg';

                    $catSlug = srvCategorySlug($service['nom_categorie'] ?? '');
                    $isAvailable = srvIsAvailable($service);
                    [$rating, $reviews] = srvRating($service);
                    ?>

                    <article class="srv-card">
                        <div class="srv-card-top">
                            <img src="<?php echo $img; ?>" alt="<?php echo htmlspecialchars($service['titre']); ?>" class="srv-card-image">

                            <div class="srv-card-badges">
                                <span class="srv-badge srv-badge-pop">Populaire</span>
                                <span class="srv-badge srv-badge-status <?php echo $isAvailable ? 'available' : 'unavailable'; ?>">
                                    <?php echo $isAvailable ? 'Disponible' : 'Indisponible'; ?>
                                </span>
                            </div>

                            <span class="srv-dot srv-dot-<?php echo $catSlug; ?>"></span>
                        </div>

                        <div class="srv-card-body">
                            <div class="srv-card-category"><?php echo htmlspecialchars($service['nom_categorie']); ?></div>
                            <h3 class="srv-card-title"><?php echo htmlspecialchars($service['titre']); ?></h3>

                            <div class="srv-rating-row">
                                <span class="srv-stars">★★★★★</span>
                                <span><?php echo $rating; ?> · <?php echo $reviews; ?> avis</span>
                            </div>

                            <div class="srv-card-divider"></div>

                            <div class="srv-card-footer">
                                <div class="srv-price-wrap">
                                    <span class="srv-price-main"><?php echo number_format((float)$service['prix'], 2, ',', ''); ?> €</span>
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
    </main>
</section>

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