<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../model/Offer.php';
require_once __DIR__ . '/../../../model/Application.php';
require_once __DIR__ . '/../../../controller/OfferController.php';
require_once __DIR__ . '/../../../controller/ApplicationController.php';

$pdo = config::getConnexion();
$offerModel = new Offer($pdo);
$offerController = new OfferController($offerModel);
$applicationModel = new Application($pdo);
$applicationController = new ApplicationController($applicationModel);

$message = '';
$currentOffer = null;

// Traiter les actions CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_application_status' && !empty($_POST['application_id']) && !empty($_POST['status'])) {
            $allowedStatuses = ['en_attente', 'acceptee', 'rejetee'];
            $status = $_POST['status'];

            if (in_array($status, $allowedStatuses, true)) {
                $applicationController->updateApplicationStatus(intval($_POST['application_id']), $status);
                $message = 'Statut de candidature mis à jour !';
            }
        } elseif ($_POST['action'] === 'create') {
            if (!empty($_POST['titre'])) {
                $offerController->createOffer([
                    'titre' => htmlspecialchars($_POST['titre']),
                    'description' => htmlspecialchars($_POST['description'] ?? ''),
                    'prix' => floatval($_POST['prix'] ?? 0),
                    'service_id' => intval($_POST['service_id'] ?? 0),
                    'creator_id' => 1,
                    'statut' => htmlspecialchars($_POST['statut'] ?? 'active'),
                    'date_debut' => $_POST['date_debut'] ?? null,
                    'date_fin' => $_POST['date_fin'] ?? null,
                ]);
                $message = 'Offre créée avec succès !';
            }
        } elseif ($_POST['action'] === 'update' && !empty($_POST['offer_id'])) {
            $offerController->updateOffer(intval($_POST['offer_id']), [
                'titre' => htmlspecialchars($_POST['titre']),
                'description' => htmlspecialchars($_POST['description'] ?? ''),
                'prix' => floatval($_POST['prix'] ?? 0),
                'service_id' => intval($_POST['service_id'] ?? 0),
                'statut' => htmlspecialchars($_POST['statut'] ?? 'active'),
                'date_debut' => $_POST['date_debut'] ?? null,
                'date_fin' => $_POST['date_fin'] ?? null,
            ]);
            $message = 'Offre mise à jour !';
            $currentOffer = null;
        } elseif ($_POST['action'] === 'delete' && !empty($_POST['offer_id'])) {
            $offerController->deleteOffer(intval($_POST['offer_id']));
            $message = 'Offre supprimée !';
            $currentOffer = null;
        }
    }
}

// Charger une offre si "Voir/Modifier" est cliqué
if (isset($_GET['edit'])) {
    $currentOffer = $offerController->getOffer(intval($_GET['edit']));
}

$offers = $offerController->listOffers();
$serviceOptions = $pdo->query('SELECT id, titre, categorie FROM services ORDER BY categorie, titre')->fetchAll(PDO::FETCH_ASSOC);
$stats = $offerController->getStats();
$applicationStats = $applicationController->getApplicationStats();
$recentApplications = array_slice($applicationController->getAllApplications(), 0, 10);
$closedOffers = $stats['inactive'] + $stats['expiree'];

function formatDate(?string $value): string {
    return $value ? date('d/m/Y', strtotime($value)) : 'N/A';
}

function formatDateInput(?string $value): string {
    return $value ? date('Y-m-d', strtotime($value)) : '';
}

function formatApplicationStatus(string $status): string {
    return match($status) {
        'acceptee' => 'Acceptée',
        'rejetee' => 'Refusée',
        default => 'En attente'
    };
}
?>

<?php if (!empty($message)): ?>
    <div style="padding: 10px; background: #4CAF50; color: white; margin-bottom: 20px; border-radius: 4px;">
        <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php endif; ?>

<section class="action-bar reveal"> 
    <div class="search-box">
        <input type="text" placeholder="Rechercher une offre...">
        <select>
            <option>Tous les statuts</option>
            <option>Ouverte</option>
            <option>Fermée</option>
        </select>
        <select>
            <option>Trier par</option>
            <option>Date publication</option>
            <option>Date expiration</option>
        </select>
    </div>

    <div class="export-bar">
        <button class="outline-btn">Exporter</button>
    </div>
</section>

<section class="admin-stats reveal">
    <article class="admin-stat"><strong><?php echo htmlspecialchars($stats['total'], ENT_QUOTES, 'UTF-8'); ?></strong><span>Offres</span></article>
    <article class="admin-stat"><strong><?php echo htmlspecialchars($stats['active'], ENT_QUOTES, 'UTF-8'); ?></strong><span>Ouvertes</span></article>
    <article class="admin-stat"><strong><?php echo htmlspecialchars($closedOffers, ENT_QUOTES, 'UTF-8'); ?></strong><span>Fermées</span></article>
    <article class="admin-stat"><strong><?php echo htmlspecialchars($applicationStats['total'], ENT_QUOTES, 'UTF-8'); ?></strong><span>Candidatures</span></article>
</section>

<section class="admin-panel reveal">
    <span class="section-badge">Gestion des offres</span>

    <div class="table-wrap">
        <table class="module-table">
            <thead>
                <tr>
                    <th>Titre</th>
                    <th>Service</th>
                    <th>Publication</th>
                    <th>Expiration</th>
                    <th>Statut</th>
                    <th>Type service</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($offers)): ?>
                    <tr>
                        <td colspan="7">Aucune offre trouvée.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($offers as $offer): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($offer['titre'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($offer['service_title'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(formatDate($offer['date_debut']), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(formatDate($offer['date_fin']), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($offer['statut']), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($offer['service_categorie'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="admin-tools">
                                <a href="?page=offers&edit=<?php echo htmlspecialchars($offer['id'], ENT_QUOTES, 'UTF-8'); ?>" class="small-btn">Voir</a>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="offer_id" value="<?php echo htmlspecialchars($offer['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit" class="danger-btn" onclick="return confirm('Supprimer cette offre ?');">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="admin-panel reveal" style="margin-top: 22px;">
    <span class="section-badge"><?php echo $currentOffer ? 'Modifier l\'offre' : 'Publier une offre'; ?></span>

    <form method="POST" id="form-grid" class="form-grid">
        <input type="hidden" name="action" value="<?php echo $currentOffer ? 'update' : 'create'; ?>">
        <?php if ($currentOffer): ?>
            <input type="hidden" name="offer_id" value="<?php echo htmlspecialchars($currentOffer['id'], ENT_QUOTES, 'UTF-8'); ?>">
        <?php endif; ?>

        <input type="text" name="titre" placeholder="Titre de l'offre" value="<?php echo $currentOffer ? htmlspecialchars($currentOffer['titre'], ENT_QUOTES, 'UTF-8') : ''; ?>" required>
        <select name="service_id" required>
            <option value="">Sélectionnez le type de service</option>
            <?php foreach ($serviceOptions as $serviceOption): ?>
                <option value="<?php echo htmlspecialchars($serviceOption['id'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $currentOffer && $currentOffer['service_id'] == $serviceOption['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($serviceOption['categorie'] . ' — ' . $serviceOption['titre'], ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div class="field-block">
            <label for="date_publication">Date de publication</label>
            <input type="date" id="date_publication" name="date_debut" value="<?php echo $currentOffer ? formatDateInput($currentOffer['date_debut']) : ''; ?>">
        </div>

        <div class="field-block">
            <label for="date_expiration">Date d'expiration</label>
            <input type="date" id="date_expiration" name="date_fin" value="<?php echo $currentOffer ? formatDateInput($currentOffer['date_fin']) : ''; ?>">
        </div>

        <select name="statut">
            <option value="active" <?php echo $currentOffer && $currentOffer['statut'] === 'active' ? 'selected' : ''; ?>>Ouverte</option>
            <option value="inactive" <?php echo $currentOffer && $currentOffer['statut'] === 'inactive' ? 'selected' : ''; ?>>Fermée</option>
            <option value="expiree" <?php echo $currentOffer && $currentOffer['statut'] === 'expiree' ? 'selected' : ''; ?>>Expirée</option>
        </select>

        <input type="number" name="prix" step="0.01" placeholder="Prix" value="<?php echo $currentOffer ? htmlspecialchars($currentOffer['prix'], ENT_QUOTES, 'UTF-8') : ''; ?>">

        <textarea name="description" placeholder="Description de l'offre..."><?php echo $currentOffer ? htmlspecialchars($currentOffer['description'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>

        <div class="icon-actions" style="margin-top: 14px;">
            <button type="submit" class="solid-btn"><?php echo $currentOffer ? 'Mettre à jour' : 'Publier'; ?></button>
            <?php if ($currentOffer): ?>
                <a href="?page=offers" class="outline-btn">Annuler</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<section class="admin-panel reveal" style="margin-top: 22px;">
    <span class="section-badge">Candidatures récentes</span>

    <div class="table-wrap">
        <table class="module-table">
            <thead>
                <tr>
                    <th>Candidat</th>
                    <th>Offre</th>
                    <th>Date candidature</th>
                    <th>Statut</th>
                    <th>Expérience</th>
                    <th>Compétences</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentApplications)): ?>
                    <tr>
                        <td colspan="7">Aucune candidature pour le moment.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentApplications as $application): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($application['nom'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($application['offer_titre'] ?: 'Offre supprimée', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(formatDate($application['created_at']), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(formatApplicationStatus($application['statut']), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($application['experience'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($application['competences'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="admin-tools">
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="update_application_status">
                                    <input type="hidden" name="application_id" value="<?php echo htmlspecialchars($application['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="status" value="acceptee">
                                    <button type="submit" class="success-btn" <?php echo $application['statut'] === 'acceptee' ? 'disabled' : ''; ?>>Accepter</button>
                                </form>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="update_application_status">
                                    <input type="hidden" name="application_id" value="<?php echo htmlspecialchars($application['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="status" value="rejetee">
                                    <button type="submit" class="danger-btn" <?php echo $application['statut'] === 'rejetee' ? 'disabled' : ''; ?>>Refuser</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
