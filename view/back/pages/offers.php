<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../model/Offer.php';
require_once __DIR__ . '/../../../model/Candidature.php';
require_once __DIR__ . '/../../../controller/OfferController.php';
require_once __DIR__ . '/../../../controller/CandidatureController.php';

$offerController = new OfferController();
$candidatureController = new CandidatureController();

$message = '';
$messageType = 'success';
$currentOffer = null;
$fieldErrors = [
    'titre' => '',
    'type_service' => '',
    'localisation' => '',
    'date_expiration' => '',
    'prix' => '',
    'description' => '',
];
$formData = [
    'titre' => '',
    'type_service' => '',
    'localisation' => '',
    'date_expiration' => '',
    'prix' => '',
    'description' => '',
];

function cleanInput(?string $value): string { //supprimer espaces inutiles
    return trim((string) $value);
}

//VALIDATION BACKEND
function validateOfferPayload(array $input, array $typeServiceOptions): array {
    $errors = [
        'titre' => '',
        'type_service' => '',
        'localisation' => '',
        'date_expiration' => '',
        'prix' => '',
        'description' => '',
    ];
    $titre = cleanInput($input['titre'] ?? '');
    $typeService = cleanInput($input['type_service'] ?? '');
    $localisation = cleanInput($input['localisation'] ?? '');
    $dateExpiration = cleanInput($input['date_expiration'] ?? '');
    $prix = cleanInput($input['prix'] ?? '');
    $description = cleanInput($input['description'] ?? '');

   if ($titre === '') {
    $errors['titre'] = 'Le titre est obligatoire.';
    } elseif (!preg_match('/^[a-zA-ZÀ-ÿ\s]+$/u', $titre)) {
        $errors['titre'] = 'Le titre ne doit contenir que des lettres.';
    } elseif (mb_strlen($titre) < 3 || mb_strlen($titre) > 150) {
        $errors['titre'] = 'Le titre doit contenir entre 3 et 150 caracteres.';
    }

    if ($typeService === '') {
        $errors['type_service'] = 'Le type de service est obligatoire.';
    } elseif (!in_array($typeService, $typeServiceOptions, true)) {
        $errors['type_service'] = 'Le type de service selectionne est invalide.';
    }

   if ($localisation === '') {
    $errors['localisation'] = 'La localisation est obligatoire.';
    } elseif (!preg_match('/^[a-zA-ZÀ-ÿ\s]+$/u', $localisation)) {
        $errors['localisation'] = 'La localisation ne doit contenir que des lettres.';
    } elseif (mb_strlen($localisation) > 150) {
        $errors['localisation'] = 'La localisation ne doit pas depasser 150 caracteres.';
    }
    if ($dateExpiration !== '') {
        $date = DateTime::createFromFormat('Y-m-d', $dateExpiration);
        $isValidDate = $date instanceof DateTime && $date->format('Y-m-d') === $dateExpiration;

        if (!$isValidDate) {
            $errors['date_expiration'] = 'La date d expiration est invalide.';
        } else {
            $today = new DateTime('today');
            if ($date < $today) {
                $errors['date_expiration'] = 'La date d expiration doit etre aujourd hui ou dans le futur.';
            }
        }
    }

    if ($prix === '') {
        $errors['prix'] = 'Le prix est obligatoire.';
    } elseif (!preg_match('/^\d+(?:[\.,]\d{1,2})?$/', $prix)) {
        $errors['prix'] = 'Le prix doit contenir uniquement des chiffres (ex: 120 ou 120.50).';
    } else {
        $prixValue = (float) str_replace(',', '.', $prix);
        if ($prixValue <= 0 || $prixValue > 1000000) {
            $errors['prix'] = 'Le prix doit etre superieur a 0 et inferieur a 1 000 000.';
        }
    }

    if ($description === '') {
        $errors['description'] = 'La description est obligatoire.';
    } elseif (!preg_match('/^[a-zA-ZÀ-ÿ\s]+$/u', $description)) {
        $errors['description'] = 'La description ne doit contenir que des lettres.';
    } elseif (mb_strlen($description) > 2000) {
        $errors['description'] = 'La description ne doit pas depasser 2000 caracteres.';
    }
    return $errors;
}

// Traiter les actions CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') { //exécute uniquement si formulaire envoyé
    if (isset($_POST['action'])) {
        //UPDATE STATUT CANDIDATURE
        if ($_POST['action'] === 'update_application_status' && !empty($_POST['application_id']) && !empty($_POST['status'])) {
            $allowedStatuses = ['en attente', 'en_attente', 'acceptee', 'refusee', 'rejetee'];
            $status = $_POST['status'];

            if (in_array($status, $allowedStatuses, true)) {
                $candidatureController->updateApplicationStatus(intval($_POST['application_id']), $status);
                $message = 'Statut de candidature mis à jour !';
                $messageType = 'success';

                $isAjax = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
                if ($isAjax) {
                    header('Content-Type: application/json; charset=UTF-8');
                    echo json_encode([
                        'ok' => true,
                        'status' => $status,
                        'label' => formatApplicationStatus($status),
                    ]);
                    exit;
                }

                header('Location: ?page=offers');
                exit;
            }

            $isAjax = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
            if ($isAjax) {
                header('Content-Type: application/json; charset=UTF-8');
                http_response_code(422);
                echo json_encode([
                    'ok' => false,
                    'message' => 'Statut invalide.',
                ]);
                exit;
            }
        //CREATE / UPDATE OFFER    
        } elseif ($_POST['action'] === 'create' || ($_POST['action'] === 'update' && !empty($_POST['offer_id']))) {
            $formData = [
                'titre' => cleanInput($_POST['titre'] ?? ''),
                'type_service' => cleanInput($_POST['type_service'] ?? ''),
                'localisation' => cleanInput($_POST['localisation'] ?? ''),
                'date_expiration' => cleanInput($_POST['date_expiration'] ?? ''),
                'prix' => cleanInput($_POST['prix'] ?? ''),
                'description' => cleanInput($_POST['description'] ?? ''),
            ];

            $fieldErrors = validateOfferPayload($formData, Offer::getTypeServiceOptions());
            $hasErrors = implode('', $fieldErrors) !== '';

            if ($hasErrors) {
                $message = 'Veuillez corriger les erreurs du formulaire.';
                $messageType = 'error';
                if ($_POST['action'] === 'update' && !empty($_POST['offer_id'])) {
                    $currentOffer = array_merge(
                        $offerController->getOffer(intval($_POST['offer_id'])) ?? [],
                        [
                            'id_offre' => intval($_POST['offer_id']),
                            'titre' => $formData['titre'],
                            'description' => $formData['description'],
                            'localisation' => $formData['localisation'],
                            'date_expiration' => $formData['date_expiration'],
                            'type_service' => $formData['type_service'],
                            'prix' => $formData['prix'],
                        ]
                    );
                }
            } else {
                $payload = [
                    'titre' => $formData['titre'],
                    'description' => $formData['description'],
                    'localisation' => $formData['localisation'],
                    'date_expiration' => $formData['date_expiration'] !== '' ? $formData['date_expiration'] : null,
                    'type_service' => $formData['type_service'],
                    'prix' => $formData['prix'] !== '' ? (float) str_replace(',', '.', $formData['prix']) : null,
                ];

                if ($_POST['action'] === 'create') {
                    $payload['id_admin'] = session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['user_id']) ? intval($_SESSION['user_id']) : null;
                    $offerController->createOffer($payload);
                    $message = 'Offre créée avec succès !';
                    $messageType = 'success';
                    $formData = [
                        'titre' => '',
                        'type_service' => '',
                        'localisation' => '',
                        'date_expiration' => '',
                        'prix' => '',
                        'description' => '',
                    ];
                } else {
                    $offerController->updateOffer(intval($_POST['offer_id']), $payload);
                    header('Location: ?page=offers');
                    exit;
                }
            }
        //DELETE OFFER
        } elseif ($_POST['action'] === 'update' && !empty($_POST['offer_id'])) {
        } elseif ($_POST['action'] === 'delete' && !empty($_POST['offer_id'])) {
            $offerController->deleteOffer(intval($_POST['offer_id']));
            $message = 'Offre supprimée !';
            $messageType = 'success';
            $currentOffer = null;
        }
    }
}

// Charger une offre si "Voir" est cliqué
if (isset($_GET['edit'])) {
    $currentOffer = $offerController->getOffer(intval($_GET['edit']));
}

$offers = $offerController->listOffers(); // tgoutes les offres
$typeServiceOptions = Offer::getTypeServiceOptions(); //liste des services
$stats = $offerController->getStats(); //statistiques
$applicationStats = $candidatureController->getApplicationStats();
$recentApplications = array_slice($candidatureController->getAllApplications(), 0, 10);
$closedOffers = $stats['fermee'];

function formatDate(?string $value): string {
    return $value ? date('d/m/Y', strtotime($value)) : 'N/A';
}

function formatDateInput(?string $value): string {
    return $value ? date('Y-m-d', strtotime($value)) : '';
}

function formatApplicationStatus(string $status): string {
    return match($status) {
        'acceptee' => 'Acceptée',
        'accepted' => 'Acceptée',
        'refusee' => 'Refusée',
        'rejetee' => 'Refusée',
        'rejected' => 'Refusée',
        'en_attente' => 'En attente',
        default => 'En attente'
    };
}

function normalizeApplicationStatus(string $status): string {
    $normalized = strtolower(trim($status));
    return match($normalized) {
        'accepted', 'acceptee' => 'acceptee',
        'rejected', 'refusee', 'rejetee' => 'refusee',
        default => 'en_attente'
    };
}

if ($currentOffer) {
    $formData = [
        'titre' => (string) ($currentOffer['titre'] ?? ''),
        'type_service' => (string) ($currentOffer['type_service'] ?? ''),
        'localisation' => (string) ($currentOffer['localisation'] ?? ''),
        'date_expiration' => formatDateInput($currentOffer['date_expiration'] ?? null),
        'prix' => (string) ($currentOffer['prix'] ?? ''),
        'description' => (string) ($currentOffer['description'] ?? ''),
    ];
}
?>

<?php if (!empty($message)): ?> 
    <div class="offers-alert <?php echo $messageType === 'error' ? 'is-error' : 'is-success'; ?>">
        <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?> 
    </div>
<?php endif; ?>

<section class="action-bar reveal"> 
    <div class="search-box">
        <input type="text" placeholder="Rechercher une offre...">
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
    <article class="admin-stat"><strong><?php echo htmlspecialchars($stats['ouverte'], ENT_QUOTES, 'UTF-8'); ?></strong><span>Ouvertes</span></article>
    <article class="admin-stat"><strong><?php echo htmlspecialchars($closedOffers, ENT_QUOTES, 'UTF-8'); ?></strong><span>Fermées</span></article>
    <article class="admin-stat"><strong><?php echo htmlspecialchars($applicationStats['total'], ENT_QUOTES, 'UTF-8'); ?></strong><span>Candidatures</span></article>
</section>

<section class="admin-panel reveal offers-table-panel">
    <span class="section-badge">Gestion des offres</span>

    <div class="table-wrap">
        <table class="module-table">
            <thead>
                <tr>
                    <th>Titre</th>
                    <th>Type service</th>
                    <th>Localisation</th>
                    <th>Publication</th>
                    <th>Expiration</th>
                    <th>Statut</th>
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
                            <td><?php echo htmlspecialchars($offer['type_service'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($offer['localisation'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(formatDate($offer['date_publication']), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(formatDate($offer['date_expiration']), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($offer['statut']), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="admin-tools">
                                <a href="?page=offers&edit=<?php echo htmlspecialchars($offer['id_offre'], ENT_QUOTES, 'UTF-8'); ?>" class="small-btn">Voir</a>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="offer_id" value="<?php echo htmlspecialchars($offer['id_offre'], ENT_QUOTES, 'UTF-8'); ?>">
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

<section class="admin-panel reveal offers-form-panel">
    <span class="section-badge"><?php echo $currentOffer ? 'Modifier l\'offre' : 'Publier une offre'; ?></span>

    <form method="POST" id="form-grid" class="form-grid offers-form" novalidate data-allowed-services='<?php echo htmlspecialchars(json_encode($typeServiceOptions), ENT_QUOTES, "UTF-8"); ?>'>
        <input type="hidden" name="action" value="<?php echo $currentOffer ? 'update' : 'create'; ?>">
        <?php if ($currentOffer): ?>
            <input type="hidden" name="offer_id" value="<?php echo htmlspecialchars($currentOffer['id_offre'], ENT_QUOTES, 'UTF-8'); ?>">
        <?php endif; ?>

        <div class="field-block form-field">
            <label for="titreField">Titre de l'offre</label>
            <input type="text" name="titre" id="titreField" placeholder="Titre de l'offre" value="<?php echo htmlspecialchars($formData['titre'], ENT_QUOTES, 'UTF-8'); ?>" aria-invalid="<?php echo $fieldErrors['titre'] !== '' ? 'true' : 'false'; ?>">
            <small class="field-error" data-error-for="titre"><?php echo htmlspecialchars($fieldErrors['titre'], ENT_QUOTES, 'UTF-8'); ?></small>
        </div>

        <div class="field-block form-field">
            <label for="typeServiceField">Type de service</label>
            <select name="type_service" id="typeServiceField" aria-invalid="<?php echo $fieldErrors['type_service'] !== '' ? 'true' : 'false'; ?>">
                <option value="">Sélectionnez le type de service</option>
                <?php foreach ($typeServiceOptions as $option): ?>
                    <option value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $formData['type_service'] === $option ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(ucfirst($option), ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small class="field-error" data-error-for="type_service"><?php echo htmlspecialchars($fieldErrors['type_service'], ENT_QUOTES, 'UTF-8'); ?></small>
        </div>

        <div class="field-block form-field localisation-field-block">
            <label for="localisationField">Localisation</label>
            <input type="text" name="localisation" id="localisationField" placeholder="Localisation" value="<?php echo htmlspecialchars($formData['localisation'], ENT_QUOTES, 'UTF-8'); ?>" aria-invalid="<?php echo $fieldErrors['localisation'] !== '' ? 'true' : 'false'; ?>">
            <small class="field-error" data-error-for="localisation"><?php echo htmlspecialchars($fieldErrors['localisation'], ENT_QUOTES, 'UTF-8'); ?></small>
        </div>

        <div class="field-block form-field">
            <label for="date_expiration">Date d'expiration</label>
            <input type="date" id="date_expiration" name="date_expiration" value="<?php echo htmlspecialchars($formData['date_expiration'], ENT_QUOTES, 'UTF-8'); ?>" aria-invalid="<?php echo $fieldErrors['date_expiration'] !== '' ? 'true' : 'false'; ?>">
            <small class="field-error" data-error-for="date_expiration"><?php echo htmlspecialchars($fieldErrors['date_expiration'], ENT_QUOTES, 'UTF-8'); ?></small>
        </div>

        <div class="field-block form-field auto-status-block">
            <label>Statut</label>
            <input type="text" value="Automatique selon date d'expiration" readonly aria-readonly="true" class="readonly-status-input">
            <small class="status-auto-note">Le statut passe automatiquement a Fermee quand la date est depassee.</small>
        </div>

        <div class="field-block form-field price-field-block">
            <label for="prixField">Prix</label>
            <input type="text" name="prix" id="prixField" inputmode="decimal" pattern="^\d+(?:[\.,]\d{1,2})?$" placeholder="Prix" value="<?php echo htmlspecialchars($formData['prix'], ENT_QUOTES, 'UTF-8'); ?>" aria-invalid="<?php echo $fieldErrors['prix'] !== '' ? 'true' : 'false'; ?>">
            <small class="field-error" data-error-for="prix"><?php echo htmlspecialchars($fieldErrors['prix'], ENT_QUOTES, 'UTF-8'); ?></small>
        </div>

        <div class="field-block form-field full-span">
            <label for="descriptionField">Description</label>
            <textarea name="description" id="descriptionField" placeholder="Description de l'offre..." rows="4" aria-invalid="<?php echo $fieldErrors['description'] !== '' ? 'true' : 'false'; ?>"><?php echo htmlspecialchars($formData['description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
            <small class="field-error" data-error-for="description"><?php echo htmlspecialchars($fieldErrors['description'], ENT_QUOTES, 'UTF-8'); ?></small>
        </div>

        <div class="icon-actions offers-form-actions">
            <button type="submit" class="solid-btn"><?php echo $currentOffer ? 'Mettre à jour' : 'Publier'; ?></button>
            <?php if ($currentOffer): ?>
                <a href="?page=offers" class="outline-btn">Annuler</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<style>
.offers-alert {
    padding: 12px 14px;
    margin-bottom: 20px;
    border-radius: 12px;
    color: #fff;
    font-weight: 600;
    border: 1px solid rgba(255, 255, 255, 0.12);
}

.offers-alert.is-error {
    background: linear-gradient(135deg, rgba(198, 40, 40, 0.95), rgba(130, 32, 32, 0.95));
}

.offers-alert.is-success {
    background: linear-gradient(135deg, rgba(46, 125, 50, 0.95), rgba(32, 93, 38, 0.95));
}

.offers-table-panel,
.offers-form-panel,
.applications-panel {
    border-radius: 22px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.offers-form-panel,
.applications-panel {
    margin-top: 22px;
}

.offers-form {
    margin-top: 12px;
    gap: 14px;
}

.form-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.form-field label {
    font-size: 13px;
    font-weight: 700;
    color: #2f3f55;
    letter-spacing: 0.01em;
}

.offers-form input,
.offers-form select,
.offers-form textarea {
    border-radius: 14px;
    border: 1px solid rgba(148, 163, 184, 0.42);
    background: rgba(255, 255, 255, 0.68);
    transition: border-color 0.2s ease, box-shadow 0.2s ease, background 0.2s ease;
    min-height: 52px;
    padding: 12px 14px;
}

.offers-form textarea {
    min-height: 150px;
    resize: vertical;
}

.offers-form input:focus,
.offers-form select:focus,
.offers-form textarea:focus {
    outline: none;
    border-color: #4b96ff;
    background: rgba(255, 255, 255, 0.9);
    box-shadow: 0 0 0 3px rgba(75, 150, 255, 0.16);
}

.auto-status-block {
    align-self: end;
}

.price-field-block {
    align-self: start;
}

.price-field-block .field-error {
    margin-top: 2px;
}

.localisation-field-block {
    align-self: start;
}

.localisation-field-block .field-error {
    margin-top: 2px;
}

.readonly-status-input {
    font-weight: 600;
    color: #2c3f58;
}

.status-auto-note {
    display: block;
    margin-top: 6px;
    color: #d92d20;
    font-size: 12px;
    font-weight: 700;
}

.offers-form-actions {
    margin-top: 14px;
    gap: 10px;
}

.full-span {
    grid-column: 1 / -1;
}

.table-wrap {
    border-radius: 14px;
    overflow: hidden;
}

.module-table thead th {
    background: rgba(58, 84, 112, 0.22);
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.module-table tbody tr {
    transition: background 0.2s ease;
}

.module-table tbody tr:hover {
    background: rgba(90, 149, 238, 0.08);
}

.inline-form {
    display: inline;
}

.field-error {
    display: block;
    min-height: 16px;
    margin-top: 0;
    color: #b42318;
    font-size: 12px;
    font-weight: 600;
}

#form-grid [aria-invalid="true"] {
    border-color: #f04438 !important;
    box-shadow: 0 0 0 2px rgba(240, 68, 56, 0.12);
}

@media (max-width: 980px) {
    .offers-form {
        grid-template-columns: 1fr;
    }

    .offers-form-actions {
        width: 100%;
    }

    .offers-form-actions .solid-btn,
    .offers-form-actions .outline-btn {
        width: 100%;
        text-align: center;
        justify-content: center;
    }
}
</style>

<section class="admin-panel reveal applications-panel">
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
                        <?php $normalizedStatus = normalizeApplicationStatus((string) ($application['statut'] ?? '')); ?>
                        <tr>
                            <td><?php echo htmlspecialchars($application['nom'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($application['offer_titre'] ?: 'Offre supprimée', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(formatDate($application['created_at']), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <span class="status-chip status-<?php echo htmlspecialchars($normalizedStatus, ENT_QUOTES, 'UTF-8'); ?>" data-status-chip>
                                    <?php echo htmlspecialchars(formatApplicationStatus($application['statut']), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($application['experience'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($application['competences'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="admin-tools">
                                <form method="POST" class="js-status-form inline-form" data-target-status="acceptee">
                                    <input type="hidden" name="action" value="update_application_status">
                                    <input type="hidden" name="application_id" value="<?php echo htmlspecialchars($application['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="status" value="acceptee">
                                    <button
                                        type="submit"
                                        class="success-btn action-pill <?php echo $normalizedStatus === 'acceptee' ? 'is-selected' : ''; ?>"
                                        <?php echo $normalizedStatus === 'acceptee' ? 'disabled' : ''; ?>
                                        aria-disabled="<?php echo $normalizedStatus === 'acceptee' ? 'true' : 'false'; ?>"
                                    >
                                        <?php echo $normalizedStatus === 'acceptee' ? 'Acceptée' : 'Accepter'; ?>
                                    </button>
                                </form>
                                <form method="POST" class="js-status-form inline-form" data-target-status="refusee">
                                    <input type="hidden" name="action" value="update_application_status">
                                    <input type="hidden" name="application_id" value="<?php echo htmlspecialchars($application['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="status" value="refusee">
                                    <button
                                        type="submit"
                                        class="danger-btn action-pill <?php echo $normalizedStatus === 'refusee' ? 'is-selected' : ''; ?>"
                                        <?php echo $normalizedStatus === 'refusee' ? 'disabled' : ''; ?>
                                        aria-disabled="<?php echo $normalizedStatus === 'refusee' ? 'true' : 'false'; ?>"
                                    >
                                        <?php echo $normalizedStatus === 'refusee' ? 'Refusée' : 'Refuser'; ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<style>
.action-pill[disabled] {
    cursor: not-allowed;
    opacity: 0.9;
}

.action-pill.is-selected {
    box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.2) inset;
}

.status-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 118px;
    padding: 8px 12px;
    border-radius: 999px;
    font-weight: 700;
    font-size: 13px;
}

.status-acceptee {
    background: rgba(46, 125, 50, 0.2);
    color: #78e08f;
    border: 1px solid rgba(120, 224, 143, 0.35);
}

.status-refusee {
    background: rgba(198, 40, 40, 0.2);
    color: #ff8a80;
    border: 1px solid rgba(255, 138, 128, 0.35);
}

.status-en_attente {
    background: rgba(237, 108, 2, 0.18);
    color: #ffcc80;
    border: 1px solid rgba(255, 204, 128, 0.35);
}
</style>