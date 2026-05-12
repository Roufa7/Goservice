<?php
require_once __DIR__ . '/../../../controller/ServiceController.php';

$serviceController = new ServiceController();

function serviceAppRoot(): string {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $root = dirname($script, 3);
    if ($root === '/' || $root === '\\') {
        return '';
    }
    return rtrim($root, '/');
}

function serviceAppUrl(string $path = ''): string {
    return serviceAppRoot() . '/' . ltrim($path, '/');
}

/* provisoire pour ton module service */
$id_provider = 1;

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $serviceController->deleteServiceByProvider((int)$_GET['delete'], $id_provider);
    header('Location: index.php?page=myServices&deleted=1');
    exit;
}

$services = $serviceController->listServicesByProvider($id_provider);

$totalServices = count($services);
$totalDisponibles = count(array_filter($services, fn($s) => trim((string)($s['disponibilite'] ?? '')) === 'Disponible'));
$totalValides = count(array_filter($services, fn($s) => trim((string)($s['statut'] ?? '')) === 'Validé'));
$totalEnAttente = count(array_filter($services, fn($s) => trim((string)($s['statut'] ?? '')) === 'En attente'));
?>

<?php if (isset($_GET['added'])): ?>
    <div style="background:rgba(76,175,80,0.12); border:1px solid rgba(76,175,80,0.3); color:#4cd774; border-radius:12px; padding:14px 20px; margin-bottom:20px;">
        ✓ Service ajouté avec succès.
    </div>
<?php endif; ?>

<?php if (isset($_GET['deleted'])): ?>
    <div style="background:rgba(76,175,80,0.12); border:1px solid rgba(76,175,80,0.3); color:#4cd774; border-radius:12px; padding:14px 20px; margin-bottom:20px;">
        ✓ Service supprimé avec succès.
    </div>
<?php endif; ?>

<?php if (isset($_GET['updated'])): ?>
    <div style="background:rgba(76,175,80,0.12); border:1px solid rgba(76,175,80,0.3); color:#4cd774; border-radius:12px; padding:14px 20px; margin-bottom:20px;">
        ✓ Service mis à jour avec succès.
    </div>
<?php endif; ?>

<section class="page-hero reveal">
    <span class="section-badge">Services</span>
    <h1 class="page-title">Mes services</h1>
    <p class="page-intro">
        Consultez, modifiez et supprimez vos services.
    </p>
</section>

<section class="action-bar reveal">
    <div class="search-box">
        <div class="icon-actions">
            <a class="solid-btn" href="index.php?page=services">Tous les services</a>
            <a class="solid-btn alt-btn" href="index.php?page=addService">+ Ajouter service</a>
        </div>
    </div>
</section>

<section class="admin-stats reveal">
    <article class="admin-stat"><strong><?php echo $totalServices; ?></strong><span>Mes services</span></article>
    <article class="admin-stat"><strong><?php echo $totalDisponibles; ?></strong><span>Disponibles</span></article>
    <article class="admin-stat"><strong><?php echo $totalValides; ?></strong><span>Validés</span></article>
    <article class="admin-stat"><strong><?php echo $totalEnAttente; ?></strong><span>En attente</span></article>
</section>

<section class="admin-panel reveal" style="margin-top:22px;">
    <div class="table-container" style="border-radius:22px; overflow:hidden; box-shadow:0 18px 40px rgba(7,20,34,0.18);">

        <table class="module-table" style="width:100%; border-collapse:collapse;">
            <thead>
                <tr>
                    <th>Image</th>
                    <th>Titre</th>
                    <th>Catégorie</th>
                    <th>Prix</th>
                    <th>Disponibilité</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php if (!empty($services)): ?>
                    <?php foreach ($services as $service): ?>

                        <?php
                        $img = !empty($service['image'])
                            ? serviceAppUrl($service['image'])
                            : serviceAppUrl('assets/images/service/default.jpg');
                        ?>

                        <tr>

                            <td>
                                <img src="<?php echo htmlspecialchars($img); ?>"
                                     style="width:60px;height:45px;border-radius:8px;object-fit:cover;">
                            </td>

                            <td><?php echo htmlspecialchars($service['titre']); ?></td>

                            <td><?php echo htmlspecialchars($service['nom_categorie']); ?></td>

                            <td><?php echo number_format($service['prix'], 2); ?> €</td>

                            <td>
                                <span style="color:<?php echo ($service['disponibilite']=='Disponible')?'green':'red'; ?>">
                                    <?php echo $service['disponibilite']; ?>
                                </span>
                            </td>

                            <td>
                                <span>
                                    <?php echo $service['statut']; ?>
                                </span>
                            </td>

                            <td>
                                <a href="index.php?page=editMyService&id=<?php echo $service['id_service']; ?>">
                                    Modifier
                                </a>

                                <a href="index.php?page=myServices&delete=<?php echo $service['id_service']; ?>"
                                   onclick="return confirm('Supprimer ce service ?');">
                                    Supprimer
                                </a>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center;">Aucun service trouvé</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

    </div>
</section>
