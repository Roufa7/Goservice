<?php
require_once __DIR__ . '/../../../controller/ServiceController.php';
require_once __DIR__ . '/../../../view/i18n.php';

$serviceController = new ServiceController();

function serviceAppRoot(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $root = dirname($script, 3);
    if ($root === '/' || $root === '\\') {
        return '';
    }

    return rtrim($root, '/');
}

function serviceAppUrl(string $path = ''): string
{
    return serviceAppRoot() . '/' . ltrim($path, '/');
}

$idProvider = 1;

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $serviceController->deleteServiceByProvider((int) $_GET['delete'], $idProvider);
    header('Location: index.php?page=myServices&deleted=1');
    exit;
}

$services = $serviceController->listServicesByProvider($idProvider);
$totalServices = count($services);
$totalAvailable = count(array_filter($services, static fn(array $service): bool => trim((string) ($service['disponibilite'] ?? '')) === 'Disponible'));
$totalApproved = count(array_filter($services, static fn(array $service): bool => trim((string) ($service['statut'] ?? '')) === 'Validé'));
$totalPending = count(array_filter($services, static fn(array $service): bool => trim((string) ($service['statut'] ?? '')) === 'En attente'));

function myServiceStatusClass(string $status): string
{
    return match ($status) {
        'Validé' => 'my-badge-valide',
        'En attente' => 'my-badge-attente',
        'Désactivé' => 'my-badge-desactive',
        default => 'my-badge-attente',
    };
}
?>

<style>
.my-services-page {
    padding-bottom: 40px;
}

.my-services-panel {
    margin-top: 22px;
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 24px;
    padding: 22px;
    box-shadow: 0 18px 40px rgba(7,20,34,0.18);
}

.my-services-table-wrap {
    width: 100%;
    overflow: hidden;
    border-radius: 20px;
    border: 1px solid var(--line);
    background: var(--card);
}

.my-services-table {
    width: 100%;
    border-collapse: collapse;
}

.my-services-table thead tr {
    background: rgba(238,88,40,0.05);
    border-bottom: 1px solid var(--line);
}

.my-services-table th,
.my-services-table td {
    padding: 16px 18px;
    text-align: left;
    color: var(--text);
    border-bottom: 1px solid var(--line);
    vertical-align: middle;
}

.my-services-table th {
    font-size: 13px;
    font-weight: 900;
}

.my-services-table tbody tr:hover {
    background: rgba(238,88,40,0.06);
}

.my-services-table tbody tr:last-child td {
    border-bottom: none;
}

.my-service-img {
    width: 62px;
    height: 46px;
    border-radius: 10px;
    object-fit: cover;
    display: block;
}

.my-service-title {
    font-weight: 800;
}

.my-service-price {
    color: var(--orange);
    font-weight: 900;
}

.my-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 12px;
    border-radius: 999px;
    font-size: 12px;
    font-weight: 800;
}

.my-badge-dispo {
    background: rgba(41,180,99,0.14);
    color: #4cd774;
    border: 1px solid rgba(41,180,99,0.22);
}

.my-badge-indispo {
    background: rgba(239,68,68,0.12);
    color: #ff7b7b;
    border: 1px solid rgba(239,68,68,0.22);
}

.my-badge-valide {
    background: rgba(76,138,255,0.14);
    color: #6ea8ff;
    border: 1px solid rgba(76,138,255,0.22);
}

.my-badge-attente {
    background: rgba(245,166,35,0.14);
    color: #f5b84b;
    border: 1px solid rgba(245,166,35,0.25);
}

.my-badge-desactive {
    background: rgba(160,160,160,0.14);
    color: #aaa;
    border: 1px solid rgba(160,160,160,0.22);
}

.my-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
}

.my-btn-edit,
.my-btn-delete {
    padding: 8px 13px;
    border-radius: 10px;
    font-size: 12px;
    font-weight: 800;
    text-decoration: none;
    transition: .2s ease;
}

.my-btn-edit {
    background: rgba(76,138,255,0.14);
    color: #6ea8ff;
    border: 1px solid rgba(76,138,255,0.22);
}

.my-btn-edit:hover {
    background: #6ea8ff;
    color: #fff;
}

.my-btn-delete {
    background: rgba(239,68,68,0.12);
    color: #ff7b7b;
    border: 1px solid rgba(239,68,68,0.22);
}

.my-btn-delete:hover {
    background: #c62828;
    color: #fff;
}

.my-empty {
    text-align: center;
    padding: 35px;
    color: var(--muted);
}

@media (max-width: 900px) {
    .my-services-panel {
        padding: 14px;
    }

    .my-services-table-wrap {
        overflow-x: auto;
    }

    .my-services-table {
        min-width: 900px;
    }
}
</style>

<div class="my-services-page">
    <?php if (isset($_GET['added'])): ?>
        <div class="app-flash app-flash-success"><?php echo htmlspecialchars(app_text('Service ajouté avec succès.', 'Service added successfully.', 'تمت إضافة الخدمة بنجاح.'), ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="app-flash app-flash-success"><?php echo htmlspecialchars(app_text('Service supprimé avec succès.', 'Service deleted successfully.', 'تم حذف الخدمة بنجاح.'), ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['updated'])): ?>
        <div class="app-flash app-flash-success"><?php echo htmlspecialchars(app_text('Service mis à jour avec succès.', 'Service updated successfully.', 'تم تحديث الخدمة بنجاح.'), ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <section class="page-hero reveal">
        <span class="section-badge"><?php echo htmlspecialchars(app_text('Services', 'Services', 'الخدمات'), ENT_QUOTES, 'UTF-8'); ?></span>
        <h1 class="page-title"><?php echo htmlspecialchars(app_text('Mes services', 'My services', 'خدماتي'), ENT_QUOTES, 'UTF-8'); ?></h1>
        <p class="page-intro"><?php echo htmlspecialchars(app_text('Consultez, modifiez et supprimez vos services.', 'Review, edit and remove your services.', 'استعرض خدماتك وعدّلها واحذفها.'), ENT_QUOTES, 'UTF-8'); ?></p>
    </section>

    <section class="action-bar reveal">
        <div class="search-box">
            <div class="icon-actions">
                <a class="solid-btn" href="index.php?page=services"><?php echo htmlspecialchars(app_text('Tous les services', 'All services', 'كل الخدمات'), ENT_QUOTES, 'UTF-8'); ?></a>
                <a class="solid-btn alt-btn" href="index.php?page=addService"><?php echo htmlspecialchars(app_text('+ Ajouter un service', '+ Add service', '+ إضافة خدمة'), ENT_QUOTES, 'UTF-8'); ?></a>
            </div>
        </div>
    </section>

    <section class="admin-stats reveal">
        <article class="admin-stat"><strong><?php echo $totalServices; ?></strong><span><?php echo htmlspecialchars(app_text('Mes services', 'My services', 'خدماتي'), ENT_QUOTES, 'UTF-8'); ?></span></article>
        <article class="admin-stat"><strong><?php echo $totalAvailable; ?></strong><span><?php echo htmlspecialchars(app_text('Disponibles', 'Available', 'المتاحة'), ENT_QUOTES, 'UTF-8'); ?></span></article>
        <article class="admin-stat"><strong><?php echo $totalApproved; ?></strong><span><?php echo htmlspecialchars(app_text('Validés', 'Approved', 'المعتمدة'), ENT_QUOTES, 'UTF-8'); ?></span></article>
        <article class="admin-stat"><strong><?php echo $totalPending; ?></strong><span><?php echo htmlspecialchars(app_text('En attente', 'Pending', 'قيد الانتظار'), ENT_QUOTES, 'UTF-8'); ?></span></article>
    </section>

    <section class="my-services-panel reveal">
        <div class="my-services-table-wrap">
            <table class="my-services-table">
                <thead>
                    <tr>
                        <th><?php echo htmlspecialchars(app_text('Image', 'Image', 'الصورة'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(app_text('Titre', 'Title', 'العنوان'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(app_text('Catégorie', 'Category', 'الفئة'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(app_text('Prix', 'Price', 'السعر'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(app_text('Disponibilité', 'Availability', 'التوفر'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(app_text('Statut', 'Status', 'الحالة'), ENT_QUOTES, 'UTF-8'); ?></th>
                        <th><?php echo htmlspecialchars(app_text('Actions', 'Actions', 'الإجراءات'), ENT_QUOTES, 'UTF-8'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($services)): ?>
                        <?php foreach ($services as $service): ?>
                            <?php
                            $status = trim((string) ($service['statut'] ?? 'En attente'));
                            $availability = trim((string) ($service['disponibilite'] ?? 'Indisponible'));
                            $img = !empty($service['image'])
                                ? serviceAppUrl($service['image'])
                                : serviceAppUrl('assets/images/service/default.jpg');
                            ?>
                            <tr>
                                <td><img class="my-service-img" src="<?php echo htmlspecialchars($img, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) ($service['titre'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"></td>
                                <td class="my-service-title"><?php echo htmlspecialchars((string) ($service['titre'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars((string) ($service['nom_categorie'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="my-service-price"><?php echo number_format((float) ($service['prix'] ?? 0), 2, ',', ' '); ?> €</td>
                                <td>
                                    <span class="my-badge <?php echo $availability === 'Disponible' ? 'my-badge-dispo' : 'my-badge-indispo'; ?>">
                                        <?php echo htmlspecialchars($availability === 'Disponible' ? app_text('Disponible', 'Available', 'متاح') : app_text('Indisponible', 'Unavailable', 'غير متاح'), ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="my-badge <?php echo htmlspecialchars(myServiceStatusClass($status), ENT_QUOTES, 'UTF-8'); ?>">
                                        <?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="my-actions">
                                        <a class="my-btn-edit" href="index.php?page=editMyService&id=<?php echo (int) ($service['id_service'] ?? 0); ?>">
                                            <?php echo htmlspecialchars(app_text('Modifier', 'Edit', 'تعديل'), ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                        <a class="my-btn-delete" href="index.php?page=myServices&delete=<?php echo (int) ($service['id_service'] ?? 0); ?>" onclick="return confirm('<?php echo addslashes(app_text('Supprimer ce service ?', 'Delete this service?', 'حذف هذه الخدمة؟')); ?>');">
                                            <?php echo htmlspecialchars(app_text('Supprimer', 'Delete', 'حذف'), ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="my-empty"><?php echo htmlspecialchars(app_text('Aucun service trouvé.', 'No services found.', 'لم يتم العثور على أي خدمات.'), ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>
