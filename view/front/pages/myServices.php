<?php
require_once __DIR__ . '/../../../controller/ServiceController.php';

$serviceController = new ServiceController();

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

<style>
/* =========================
   FIX DARK MODE MY SERVICES
========================= */
.my-services-page{
    padding-bottom:40px;
}

.my-services-page .admin-stats{
    gap:18px;
}

.my-services-page .admin-stat{
    background:var(--card) !important;
    border:1px solid var(--line) !important;
    color:var(--text) !important;
    box-shadow:0 14px 35px rgba(7,20,34,0.18);
}

.my-services-page .admin-stat strong{
    color:var(--text) !important;
}

.my-services-page .admin-stat span{
    color:var(--muted) !important;
}

.my-services-panel{
    margin-top:22px;
    background:var(--card);
    border:1px solid var(--line);
    border-radius:24px;
    padding:22px;
    box-shadow:0 18px 40px rgba(7,20,34,0.18);
}

.my-services-table-wrap{
    width:100%;
    overflow:hidden;
    border-radius:20px;
    border:1px solid var(--line);
    background:var(--card);
}

.my-services-table{
    width:100%;
    border-collapse:collapse;
    background:var(--card) !important;
    color:var(--text) !important;
}

.my-services-table thead tr{
    background:rgba(238,88,40,0.05) !important;
    border-bottom:1px solid var(--line);
}

.my-services-table th{
    padding:17px 18px;
    text-align:left;
    font-size:13px;
    font-weight:900;
    color:var(--text) !important;
}

.my-services-table td{
    padding:16px 18px;
    border-bottom:1px solid var(--line);
    font-size:14px;
    color:var(--text) !important;
    vertical-align:middle;
    background:transparent !important;
}

.my-services-table tbody tr{
    background:transparent !important;
    transition:.2s ease;
}

.my-services-table tbody tr:hover{
    background:rgba(238,88,40,0.06) !important;
}

.my-services-table tbody tr:last-child td{
    border-bottom:none;
}

.my-service-img{
    width:62px;
    height:46px;
    border-radius:10px;
    object-fit:cover;
    display:block;
}

.my-service-title{
    font-weight:800;
    color:var(--text);
}

.my-service-price{
    color:var(--orange);
    font-weight:900;
}

.my-badge{
    display:inline-flex;
    align-items:center;
    padding:6px 12px;
    border-radius:999px;
    font-size:12px;
    font-weight:800;
}

.my-badge-dispo{
    background:rgba(41,180,99,0.14);
    color:#4cd774;
    border:1px solid rgba(41,180,99,0.22);
}

.my-badge-indispo{
    background:rgba(239,68,68,0.12);
    color:#ff7b7b;
    border:1px solid rgba(239,68,68,0.22);
}

.my-badge-valide{
    background:rgba(76,138,255,0.14);
    color:#6ea8ff;
    border:1px solid rgba(76,138,255,0.22);
}

.my-badge-attente{
    background:rgba(245,166,35,0.14);
    color:#f5b84b;
    border:1px solid rgba(245,166,35,0.25);
}

.my-badge-desactive{
    background:rgba(160,160,160,0.14);
    color:#aaa;
    border:1px solid rgba(160,160,160,0.22);
}

.my-actions{
    display:flex;
    gap:8px;
    align-items:center;
    flex-wrap:wrap;
}

.my-btn-edit,
.my-btn-delete{
    padding:8px 13px;
    border-radius:10px;
    font-size:12px;
    font-weight:800;
    text-decoration:none;
    transition:.2s ease;
}

.my-btn-edit{
    background:rgba(76,138,255,0.14);
    color:#6ea8ff;
    border:1px solid rgba(76,138,255,0.22);
}

.my-btn-edit:hover{
    background:#6ea8ff;
    color:#fff;
}

.my-btn-delete{
    background:rgba(239,68,68,0.12);
    color:#ff7b7b;
    border:1px solid rgba(239,68,68,0.22);
}

.my-btn-delete:hover{
    background:#c62828;
    color:#fff;
}

.my-empty{
    text-align:center;
    padding:35px;
    color:var(--muted) !important;
}

body:not(.dark) .my-services-table-wrap,
body:not(.dark) .my-services-panel,
body:not(.dark) .my-services-table{
    background:#fff !important;
}

body.dark .my-services-table-wrap,
body.dark .my-services-panel,
body.dark .my-services-table,
body.admin-body.dark .my-services-table-wrap,
body.admin-body.dark .my-services-panel,
body.admin-body.dark .my-services-table{
    background:#112233 !important;
}

body.dark .my-services-table th,
body.dark .my-services-table td,
body.admin-body.dark .my-services-table th,
body.admin-body.dark .my-services-table td{
    color:#eaf1f8 !important;
}

body.dark .my-services-table thead tr,
body.admin-body.dark .my-services-table thead tr{
    background:rgba(255,255,255,0.04) !important;
}

@media(max-width:900px){
    .my-services-panel{
        padding:14px;
    }

    .my-services-table-wrap{
        overflow-x:auto;
    }

    .my-services-table{
        min-width:900px;
    }
}
</style>

<div class="my-services-page">

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

<section class="my-services-panel reveal">
    <div class="my-services-table-wrap">
        <table class="my-services-table">
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
                            ? '/GoService_v3/' . ltrim($service['image'], '/')
                            : '/GoService/assets/images/service/default.jpg';

                        $dispo = trim((string)($service['disponibilite'] ?? ''));
                        $statut = trim((string)($service['statut'] ?? ''));

                        $dispoClass = $dispo === 'Disponible' ? 'my-badge-dispo' : 'my-badge-indispo';

                        $statutClass = match($statut) {
                            'Validé' => 'my-badge-valide',
                            'En attente' => 'my-badge-attente',
                            default => 'my-badge-desactive'
                        };
                        ?>

                        <tr>
                            <td>
                                <img src="<?php echo htmlspecialchars($img); ?>"
                                     class="my-service-img"
                                     alt="<?php echo htmlspecialchars($service['titre'] ?? 'Service'); ?>">
                            </td>

                            <td>
                                <span class="my-service-title">
                                    <?php echo htmlspecialchars($service['titre']); ?>
                                </span>
                            </td>

                            <td><?php echo htmlspecialchars($service['nom_categorie']); ?></td>

                            <td>
                                <span class="my-service-price">
                                    <?php echo number_format((float)$service['prix'], 2); ?> €
                                </span>
                            </td>

                            <td>
                                <span class="my-badge <?php echo $dispoClass; ?>">
                                    <?php echo htmlspecialchars($dispo); ?>
                                </span>
                            </td>

                            <td>
                                <span class="my-badge <?php echo $statutClass; ?>">
                                    <?php echo htmlspecialchars($statut); ?>
                                </span>
                            </td>

                            <td>
                                <div class="my-actions">
                                    <a class="my-btn-edit" href="index.php?page=editMyService&id=<?php echo (int)$service['id_service']; ?>">
                                        Modifier
                                    </a>

                                    <a class="my-btn-delete"
                                       href="index.php?page=myServices&delete=<?php echo (int)$service['id_service']; ?>"
                                       onclick="return confirm('Supprimer ce service ?');">
                                        Supprimer
                                    </a>
                                </div>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="7" class="my-empty">Aucun service trouvé</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

</div>
