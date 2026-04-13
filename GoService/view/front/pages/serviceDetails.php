<?php
require_once __DIR__ . '/../../../controller/ServiceController.php';

$controller = new ServiceController();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$service = $controller->getServiceWithCategory($id);

if (!$service) {
    die("Service introuvable");
}

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
    $id = (int)($service['id_service'] ?? 1);
    $delays = ['24h', '24 - 48h', '48h', '1 - 2 jours'];
    return $delays[$id % count($delays)];
}

function svcRating(array $service): array {
    $id = (int)($service['id_service'] ?? 1);
    $rating = 4.0 + (($id % 7) * 0.1);
    if ($rating > 4.9) {
        $rating = 4.9;
    }
    $reviews = 20 + (($id * 7) % 35);
    return [number_format($rating, 1), $reviews];
}

$img = !empty($service['image'])
    ? '/GoService/' . ltrim($service['image'], '/')
    : '/GoService/assets/images/service/default.jpg';

$isAvailable = svcIsAvailable($service);
$statusLabel = svcStatusLabel($service);
$delay = svcDelay($service);
[$rating, $reviews] = svcRating($service);

$categorie = !empty($service['nom_categorie']) ? $service['nom_categorie'] : 'Service';
$titre = !empty($service['titre']) ? $service['titre'] : 'Sans titre';
$description = !empty($service['description']) ? $service['description'] : 'Aucune description disponible.';
$prix = isset($service['prix']) ? number_format((float)$service['prix'], 2, ',', '') : '0,00';
?>

<style>
    .svc-page {
        padding: 28px 32px 40px;
    }

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
        box-shadow: 0 18px 45px rgba(7, 20, 34, 0.20);
    }

    .svc-media-card {
        position: relative;
        min-height: 520px;
    }

    .svc-media-card img {
        width: 100%;
        height: 100%;
        min-height: 520px;
        object-fit: cover;
        display: block;
    }

    .svc-badges {
        position: absolute;
        top: 18px;
        left: 18px;
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
        z-index: 2;
    }

    .svc-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 8px 14px;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 700;
        line-height: 1;
        backdrop-filter: blur(8px);
    }

    .svc-badge-cat {
        background: rgba(242, 106, 46, 0.95);
        color: #fff;
    }

    .svc-badge-dispo {
        background: rgba(51, 175, 87, 0.92);
        color: #fff;
    }

    .svc-badge-status {
        background: rgba(138, 96, 214, 0.92);
        color: #fff;
    }

    .svc-info-card {
        padding: 28px 30px;
        color: #e9f4ff;
        position: relative;
    }

    .svc-category {
        color: #78aeda;
        text-transform: uppercase;
        letter-spacing: 1.8px;
        font-size: 13px;
        font-weight: 800;
        margin-bottom: 10px;
    }

    .svc-title {
        margin: 0;
        color: #ffffff;
        font-size: 54px;
        line-height: 0.95;
        font-weight: 900;
        letter-spacing: -1px;
    }

    .svc-rating {
        margin-top: 18px;
        display: flex;
        align-items: center;
        gap: 12px;
        color: #a8bfd6;
        font-size: 15px;
    }

    .svc-stars {
        color: #ffb638;
        letter-spacing: 1px;
        font-size: 16px;
    }

    .svc-price {
        margin-top: 18px;
        font-size: 56px;
        font-weight: 900;
        line-height: 1;
        color: #f26a2e;
        letter-spacing: -1px;
    }

    .svc-price span {
        font-size: 0.9em;
    }

    .svc-separator {
        height: 1px;
        background: rgba(255,255,255,0.10);
        margin: 24px 0;
    }

    .svc-meta {
        display: grid;
        grid-template-columns: 1fr auto;
        gap: 16px 18px;
        margin-bottom: 24px;
    }

    .svc-meta-label {
        color: #8fb0ca;
        font-size: 16px;
    }

    .svc-meta-value {
        color: #ffffff;
        font-weight: 700;
        font-size: 16px;
        text-align: right;
    }

    .svc-meta-value.available {
        color: #41d16f;
    }

    .svc-meta-value.unavailable {
        color: #ff7f7f;
    }

    .svc-section-title {
        margin: 0 0 14px;
        font-size: 30px;
        color: #ffffff;
        font-weight: 800;
    }

    .svc-description {
        margin: 0;
        color: #9bb4ca;
        font-size: 17px;
        line-height: 1.8;
    }

    .svc-actions {
        display: flex;
        gap: 14px;
        flex-wrap: wrap;
        margin-top: 28px;
    }

    .svc-btn-primary,
    .svc-btn-secondary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 14px 22px;
        border-radius: 999px;
        text-decoration: none;
        font-weight: 800;
        font-size: 15px;
        transition: 0.25s ease;
    }

    .svc-btn-primary {
        background: linear-gradient(135deg, #ff7b39 0%, #f15a24 100%);
        color: #fff;
        box-shadow: 0 12px 28px rgba(242, 106, 46, 0.25);
    }

    .svc-btn-primary:hover {
        transform: translateY(-2px);
        color: #fff;
    }

    .svc-btn-secondary {
        border: 1px solid rgba(138, 176, 202, 0.22);
        color: #9fc0db;
        background: rgba(255,255,255,0.02);
    }

    .svc-btn-secondary:hover {
        color: #fff;
        border-color: rgba(255,255,255,0.30);
        transform: translateY(-2px);
    }

    @media (max-width: 1100px) {
        .svc-layout {
            grid-template-columns: 1fr;
        }

        .svc-title {
            font-size: 44px;
        }

        .svc-price {
            font-size: 44px;
        }

        .svc-media-card,
        .svc-media-card img {
            min-height: 380px;
        }
    }

    @media (max-width: 640px) {
        .svc-page {
            padding: 18px 14px 28px;
        }

        .svc-info-card {
            padding: 22px 18px;
        }

        .svc-title {
            font-size: 34px;
        }

        .svc-price {
            font-size: 34px;
        }

        .svc-meta {
            grid-template-columns: 1fr;
        }

        .svc-meta-value {
            text-align: left;
        }
    }
</style>

<section class="svc-page">


    <div class="svc-layout">
        <div class="svc-media-card">
            <div class="svc-badges">
                <span class="svc-badge svc-badge-cat"><?php echo htmlspecialchars($categorie); ?></span>
                <span class="svc-badge svc-badge-dispo">
                    <?php echo $isAvailable ? 'Disponible' : 'Indisponible'; ?>
                </span>
                <span class="svc-badge svc-badge-status"><?php echo htmlspecialchars($statusLabel); ?></span>
            </div>

            <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($titre); ?>">
        </div>

        <div class="svc-info-card">
            <div class="svc-category"><?php echo htmlspecialchars($categorie); ?></div>

            <h1 class="svc-title"><?php echo htmlspecialchars($titre); ?></h1>

            <div class="svc-rating">
                <span class="svc-stars">★★★★★</span>
                <span><?php echo $rating; ?> · <?php echo $reviews; ?> avis</span>
            </div>

            <div class="svc-price"><?php echo $prix; ?> <span>€</span></div>

            <div class="svc-separator"></div>

            <div class="svc-meta">
                <div class="svc-meta-label">Catégorie</div>
                <div class="svc-meta-value"><?php echo htmlspecialchars($categorie); ?></div>

                <div class="svc-meta-label">Disponibilité</div>
                <div class="svc-meta-value <?php echo $isAvailable ? 'available' : 'unavailable'; ?>">
                    <?php echo $isAvailable ? 'Disponible' : 'Indisponible'; ?>
                </div>

                <div class="svc-meta-label">Statut</div>
                <div class="svc-meta-value"><?php echo htmlspecialchars($statusLabel); ?></div>

                <div class="svc-meta-label">Délai moyen</div>
                <div class="svc-meta-value"><?php echo htmlspecialchars($delay); ?></div>
            </div>

            <div class="svc-separator"></div>

            <h2 class="svc-section-title">Description</h2>
            <p class="svc-description"><?php echo htmlspecialchars($description); ?></p>

            <div class="svc-actions">
                <a href="#" class="svc-btn-primary">Réserver ce service</a>
                <a href="index.php?page=services" class="svc-btn-secondary">← Retour services</a>

            </div>
        </div>
    </div>
</section>