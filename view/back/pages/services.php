<?php
require_once __DIR__ . '/../../../controller/ServiceController.php';

$serviceController = new ServiceController();

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $serviceController->deleteService((int) $_GET['delete']);
    header('Location: index.php?page=services');
    exit;
}

$services = $serviceController->listServicesWithCategories();

$totalServices = count($services);
$totalCategories = count(array_unique(array_filter(array_map(fn($s) => $s['nom_categorie'] ?? '', $services))));
$totalDisponibles = count(array_filter($services, fn($s) => trim((string)($s['disponibilite'] ?? '')) === 'Disponible'));
$totalValides = count(array_filter($services, fn($s) => trim((string)($s['statut'] ?? '')) === 'Validé'));
$totalEnAttente = count(array_filter($services, fn($s) => trim((string)($s['statut'] ?? '')) === 'En attente'));

function dispoBadgeStyle(string $dispo): string {
    $dispo = trim($dispo);
    if ($dispo === 'Disponible') {
        return 'background:rgba(41,180,99,0.14); color:#4cd774;';
    }
    return 'background:rgba(255,95,95,0.14); color:#ff8b8b;';
}

function statutBadgeStyle(string $statut): string {
    $statut = trim($statut);
    if ($statut === 'Validé') {
        return 'background:rgba(76,138,255,0.14); color:#6ea8ff;';
    }
    if ($statut === 'En attente') {
        return 'background:rgba(255,193,7,0.14); color:#ffd04d;';
    }
    return 'background:rgba(160,160,160,0.15); color:#d7d7d7;';
}
?>

<?php if (isset($_GET['added'])): ?>
    <div style="background:rgba(76,175,80,0.12); border:1px solid rgba(76,175,80,0.3); color:#4cd774; border-radius:12px; padding:14px 20px; margin-bottom:20px; font-size:14px; font-weight:500; display:flex; align-items:center; gap:10px;">
        ✓ Service ajouté avec succès.
    </div>
<?php endif; ?>
<?php if (isset($_GET['updated'])): ?>
    <div style="background:rgba(76,175,80,0.12); border:1px solid rgba(76,175,80,0.3); color:#4cd774; border-radius:12px; padding:14px 20px; margin-bottom:20px; font-size:14px; font-weight:500; display:flex; align-items:center; gap:10px;">
        ✓ Service mis à jour avec succès.
    </div>
<?php endif; ?>

<section class="action-bar reveal">
    <div class="search-box">
        <input type="text" placeholder="Rechercher un service ou une catégorie...">
        <select>
            <option>Tous les statuts</option>
            <option>Validé</option>
            <option>En attente</option>
            <option>Désactivé</option>
        </select>
        <select>
            <option>Trier par</option>
            <option>Nom</option>
            <option>Prix</option>
            <option>Date</option>
        </select>
    </div>

    <div class="export-bar" style="display:flex; gap:12px; align-items:center;">
        <a href="index.php?page=addService" class="outline-btn" style="text-decoration:none; display:inline-flex; align-items:center; justify-content:center;">
            + Ajouter service
        </a>
        <button class="outline-btn" type="button">Exporter</button>
    </div>
</section>

<section class="admin-stats reveal">
    <article class="admin-stat"><strong><?php echo $totalServices; ?></strong><span>Services</span></article>
    <article class="admin-stat"><strong><?php echo $totalCategories; ?></strong><span>Catégories</span></article>
    <article class="admin-stat"><strong><?php echo $totalValides; ?></strong><span>Validés</span></article>
    <article class="admin-stat"><strong><?php echo $totalEnAttente; ?></strong><span>En attente</span></article>
</section>

<section class="admin-panel reveal" style="margin-top: 22px;">
    <div class="table-container" style="
    border-radius: 22px;
    overflow: hidden;
    box-shadow: 0 18px 40px rgba(7, 20, 34, 0.18);
">
        <table class="module-table" style="width:100%; border-collapse:collapse;">
            <thead>
                <tr style="background: rgba(255,255,255,0.02);">
                    <th style="color: rgba(255,255,255,0.72); padding: 18px 18px; text-align:left; font-size:13px; text-transform:uppercase;">Image</th>
                    <th style="color: rgba(255,255,255,0.72); padding: 18px 18px; text-align:left; font-size:13px; text-transform:uppercase;">Titre</th>
                    <th style="color: rgba(255,255,255,0.72); padding: 18px 18px; text-align:left; font-size:13px; text-transform:uppercase;">Catégorie</th>
                    <th style="color: rgba(255,255,255,0.72); padding: 18px 18px; text-align:left; font-size:13px; text-transform:uppercase;">Prix</th>
                    <th style="color: rgba(255,255,255,0.72); padding: 18px 18px; text-align:left; font-size:13px; text-transform:uppercase;">Disponibilité</th>
                    <th style="color: rgba(255,255,255,0.72); padding: 18px 18px; text-align:left; font-size:13px; text-transform:uppercase;">Statut</th>
                    <th style="color: rgba(255,255,255,0.72); padding: 18px 18px; text-align:left; font-size:13px; text-transform:uppercase;">Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php if (!empty($services)): ?>
                    <?php foreach ($services as $service): ?>
                        <?php
                        $img = !empty($service['image'])
                            ? '/GoService/' . ltrim($service['image'], '/')
                            : '/GoService/assets/images/service/default.jpg';
                        ?>
                        <tr style="border-top:1px solid rgba(255,255,255,0.06);">
                            <td style="padding: 14px 18px;">
                                <img
                                    src="<?php echo htmlspecialchars($img); ?>"
                                    alt="<?php echo htmlspecialchars($service['titre'] ?? 'Service'); ?>"
                                    style="width:64px; height:48px; object-fit:cover; border-radius:10px; display:block;"
                                >
                            </td>

                            <td class="table-text table-text-strong" style="padding:18px 18px; font-size:15px; font-weight:600;">
    <?php echo htmlspecialchars($service['titre'] ?? ''); ?>
</td>

<td class="table-text" style="padding:18px 18px; font-size:15px;">
    <?php echo htmlspecialchars($service['nom_categorie'] ?? ''); ?>
</td>

<td class="table-text" style="padding:18px 18px; font-size:15px;">
    <?php echo number_format((float)($service['prix'] ?? 0), 2, '.', ''); ?> €
</td>

                            <td style="padding:18px 18px;">
                                <span style="display:inline-block; padding:7px 14px; border-radius:999px; font-size:13px; font-weight:700; <?php echo dispoBadgeStyle((string)($service['disponibilite'] ?? '')); ?>">
                                    <?php echo htmlspecialchars($service['disponibilite'] ?? ''); ?>
                                </span>
                            </td>

                            <td style="padding:18px 18px;">
                                <span style="display:inline-block; padding:7px 14px; border-radius:999px; font-size:13px; font-weight:700; <?php echo statutBadgeStyle((string)($service['statut'] ?? '')); ?>">
                                    <?php echo htmlspecialchars($service['statut'] ?? ''); ?>
                                </span>
                            </td>

                            <td style="padding:18px 18px;">
                                <div style="display:flex; gap:10px; flex-wrap:wrap;">
        
                                    <a
                                        href="index.php?page=editService&id=<?php echo (int)$service['id_service']; ?>"
                                        style="text-decoration:none; padding:9px 14px; border-radius:10px; background:rgba(76,138,255,0.14); color:#8ab7ff; font-size:14px; font-weight:700;"
                                    >
                                        Modifier
                                    </a>

                                    <a
                                        href="index.php?page=services&delete=<?php echo (int)$service['id_service']; ?>"
                                        onclick="return confirm('Voulez-vous vraiment supprimer ce service ?');"
                                        style="text-decoration:none; padding:9px 14px; border-radius:10px; background:rgba(255,95,95,0.14); color:#ff8b8b; font-size:14px; font-weight:700;"
                                    >
                                        Supprimer
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="table-text" style="padding:24px 18px; text-align:center;">
    Aucun service trouvé.
</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>