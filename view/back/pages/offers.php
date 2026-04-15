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
$messageType = 'success';
$currentOffer = null;
$fieldErrors = [
    'titre' => '',
    'type_service' => '',
    'localisation' => '',
    'date_expiration' => '',
    'statut' => '',
    'prix' => '',
    'description' => '',
];
$formData = [
    'titre' => '',
    'type_service' => '',
    'localisation' => '',
    'date_expiration' => '',
    'statut' => 'ouverte',
    'prix' => '',
    'description' => '',
];

function cleanInput(?string $value): string {
    return trim((string) $value);
}

function validateOfferPayload(array $input, array $typeServiceOptions): array {
    $errors = [
        'titre' => '',
        'type_service' => '',
        'localisation' => '',
        'date_expiration' => '',
        'statut' => '',
        'prix' => '',
        'description' => '',
    ];

    $titre = cleanInput($input['titre'] ?? '');
    $typeService = cleanInput($input['type_service'] ?? '');
    $localisation = cleanInput($input['localisation'] ?? '');
    $dateExpiration = cleanInput($input['date_expiration'] ?? '');
    $statut = cleanInput($input['statut'] ?? '');
    $prix = cleanInput($input['prix'] ?? '');
    $description = cleanInput($input['description'] ?? '');

    if ($titre === '') {
        $errors['titre'] = 'Le titre est obligatoire.';
    } elseif (mb_strlen($titre) < 3 || mb_strlen($titre) > 150) {
        $errors['titre'] = 'Le titre doit contenir entre 3 et 150 caracteres.';
    }

    if ($typeService === '') {
        $errors['type_service'] = 'Le type de service est obligatoire.';
    } elseif (!in_array($typeService, $typeServiceOptions, true)) {
        $errors['type_service'] = 'Le type de service selectionne est invalide.';
    }

    if ($localisation !== '' && mb_strlen($localisation) > 150) {
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

    if (!in_array($statut, ['ouverte', 'fermee'], true)) {
        $errors['statut'] = 'Le statut est invalide.';
    }

    if ($prix !== '') {
        if (!is_numeric($prix)) {
            $errors['prix'] = 'Le prix doit etre numerique.';
        } else {
            $prixValue = (float) $prix;
            if ($prixValue <= 0 || $prixValue > 1000000) {
                $errors['prix'] = 'Le prix doit etre superieur a 0 et inferieur a 1 000 000.';
            }
        }
    }

    if ($description !== '' && mb_strlen($description) > 2000) {
        $errors['description'] = 'La description ne doit pas depasser 2000 caracteres.';
    }

    return $errors;
}

// Traiter les actions CRUD
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update_application_status' && !empty($_POST['application_id']) && !empty($_POST['status'])) {
            $allowedStatuses = ['en attente', 'en_attente', 'acceptee', 'refusee', 'rejetee'];
            $status = $_POST['status'];

            if (in_array($status, $allowedStatuses, true)) {
                $applicationController->updateApplicationStatus(intval($_POST['application_id']), $status);
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
        } elseif ($_POST['action'] === 'create' || ($_POST['action'] === 'update' && !empty($_POST['offer_id']))) {
            $formData = [
                'titre' => cleanInput($_POST['titre'] ?? ''),
                'type_service' => cleanInput($_POST['type_service'] ?? ''),
                'localisation' => cleanInput($_POST['localisation'] ?? ''),
                'date_expiration' => cleanInput($_POST['date_expiration'] ?? ''),
                'statut' => cleanInput($_POST['statut'] ?? 'ouverte'),
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
                            'statut' => $formData['statut'],
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
                    'statut' => $formData['statut'],
                    'type_service' => $formData['type_service'],
                    'prix' => $formData['prix'] !== '' ? floatval($formData['prix']) : null,
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
                        'statut' => 'ouverte',
                        'prix' => '',
                        'description' => '',
                    ];
                } else {
                    $offerController->updateOffer(intval($_POST['offer_id']), $payload);
                    $message = 'Offre mise à jour !';
                    $messageType = 'success';
                    $currentOffer = null;
                }
            }
        } elseif ($_POST['action'] === 'update' && !empty($_POST['offer_id'])) {
            // no-op branch kept intentionally for compatibility with previous flow
        } elseif ($_POST['action'] === 'delete' && !empty($_POST['offer_id'])) {
            $offerController->deleteOffer(intval($_POST['offer_id']));
            $message = 'Offre supprimée !';
            $messageType = 'success';
            $currentOffer = null;
        }
    }
}

// Charger une offre si "Voir/Modifier" est cliqué
if (isset($_GET['edit'])) {
    $currentOffer = $offerController->getOffer(intval($_GET['edit']));
}

$offers = $offerController->listOffers();
$typeServiceOptions = Offer::getTypeServiceOptions();
$stats = $offerController->getStats();
$applicationStats = $applicationController->getApplicationStats();
$recentApplications = array_slice($applicationController->getAllApplications(), 0, 10);
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
        'statut' => (string) ($currentOffer['statut'] ?? 'ouverte'),
        'prix' => (string) ($currentOffer['prix'] ?? ''),
        'description' => (string) ($currentOffer['description'] ?? ''),
    ];
}
?>

<?php if (!empty($message)): ?>
    <div style="padding: 10px; background: <?php echo $messageType === 'error' ? '#c62828' : '#2e7d32'; ?>; color: white; margin-bottom: 20px; border-radius: 4px;">
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
    <article class="admin-stat"><strong><?php echo htmlspecialchars($stats['ouverte'], ENT_QUOTES, 'UTF-8'); ?></strong><span>Ouvertes</span></article>
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

<section class="admin-panel reveal" style="margin-top: 22px;">
    <span class="section-badge"><?php echo $currentOffer ? 'Modifier l\'offre' : 'Publier une offre'; ?></span>

    <form method="POST" id="form-grid" class="form-grid" novalidate>
        <input type="hidden" name="action" value="<?php echo $currentOffer ? 'update' : 'create'; ?>">
        <?php if ($currentOffer): ?>
            <input type="hidden" name="offer_id" value="<?php echo htmlspecialchars($currentOffer['id_offre'], ENT_QUOTES, 'UTF-8'); ?>">
        <?php endif; ?>

        <input type="text" name="titre" id="titreField" placeholder="Titre de l'offre" value="<?php echo htmlspecialchars($formData['titre'], ENT_QUOTES, 'UTF-8'); ?>" aria-invalid="<?php echo $fieldErrors['titre'] !== '' ? 'true' : 'false'; ?>">
        <select name="type_service" id="typeServiceField" aria-invalid="<?php echo $fieldErrors['type_service'] !== '' ? 'true' : 'false'; ?>">
            <option value="">Sélectionnez le type de service</option>
            <?php foreach ($typeServiceOptions as $option): ?>
                <option value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $formData['type_service'] === $option ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars(ucfirst($option), ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <small class="field-error" data-error-for="titre"><?php echo htmlspecialchars($fieldErrors['titre'], ENT_QUOTES, 'UTF-8'); ?></small>
        <small class="field-error" data-error-for="type_service"><?php echo htmlspecialchars($fieldErrors['type_service'], ENT_QUOTES, 'UTF-8'); ?></small>

        <input type="text" name="localisation" id="localisationField" placeholder="Localisation" value="<?php echo htmlspecialchars($formData['localisation'], ENT_QUOTES, 'UTF-8'); ?>" aria-invalid="<?php echo $fieldErrors['localisation'] !== '' ? 'true' : 'false'; ?>">
        <small class="field-error" data-error-for="localisation"><?php echo htmlspecialchars($fieldErrors['localisation'], ENT_QUOTES, 'UTF-8'); ?></small>

        <div class="field-block">
            <label for="date_expiration">Date d'expiration</label>
            <input type="date" id="date_expiration" name="date_expiration" value="<?php echo htmlspecialchars($formData['date_expiration'], ENT_QUOTES, 'UTF-8'); ?>" aria-invalid="<?php echo $fieldErrors['date_expiration'] !== '' ? 'true' : 'false'; ?>">
            <small class="field-error" data-error-for="date_expiration"><?php echo htmlspecialchars($fieldErrors['date_expiration'], ENT_QUOTES, 'UTF-8'); ?></small>
        </div>

        <select name="statut" id="statutField" aria-invalid="<?php echo $fieldErrors['statut'] !== '' ? 'true' : 'false'; ?>">
            <option value="ouverte" <?php echo $formData['statut'] === 'ouverte' ? 'selected' : ''; ?>>Ouverte</option>
            <option value="fermee" <?php echo $formData['statut'] === 'fermee' ? 'selected' : ''; ?>>Fermée</option>
        </select>
        <small class="field-error" data-error-for="statut"><?php echo htmlspecialchars($fieldErrors['statut'], ENT_QUOTES, 'UTF-8'); ?></small>

        <input type="number" name="prix" id="prixField" step="0.01" placeholder="Prix" value="<?php echo htmlspecialchars($formData['prix'], ENT_QUOTES, 'UTF-8'); ?>" aria-invalid="<?php echo $fieldErrors['prix'] !== '' ? 'true' : 'false'; ?>">
        <small class="field-error" data-error-for="prix"><?php echo htmlspecialchars($fieldErrors['prix'], ENT_QUOTES, 'UTF-8'); ?></small>

        <textarea name="description" id="descriptionField" placeholder="Description de l'offre..." rows="4" aria-invalid="<?php echo $fieldErrors['description'] !== '' ? 'true' : 'false'; ?>"><?php echo htmlspecialchars($formData['description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
        <small class="field-error" data-error-for="description" style="grid-column: 1 / -1;"><?php echo htmlspecialchars($fieldErrors['description'], ENT_QUOTES, 'UTF-8'); ?></small>

        <div class="icon-actions" style="margin-top: 14px;">
            <button type="submit" class="solid-btn"><?php echo $currentOffer ? 'Mettre à jour' : 'Publier'; ?></button>
            <?php if ($currentOffer): ?>
                <a href="?page=offers" class="outline-btn">Annuler</a>
            <?php endif; ?>
        </div>
    </form>
</section>

<script>
function setAdminFieldError(fieldName, message) {
    const node = document.querySelector([data-error-for="${fieldName}"]);
    if (node) {
        node.textContent = message;
    }
}

function clearAdminFieldErrors() {
    document.querySelectorAll('.field-error').forEach(node => {
        node.textContent = '';
    });
}

document.getElementById('form-grid')?.addEventListener('submit', function(event) {
    clearAdminFieldErrors();

    const titre = document.getElementById('titreField')?.value.trim() ?? '';
    const typeService = document.getElementById('typeServiceField')?.value.trim() ?? '';
    const localisation = document.getElementById('localisationField')?.value.trim() ?? '';
    const dateExpiration = document.getElementById('date_expiration')?.value.trim() ?? '';
    const statut = document.getElementById('statutField')?.value.trim() ?? '';
    const prix = document.getElementById('prixField')?.value.trim() ?? '';
    const description = document.getElementById('descriptionField')?.value.trim() ?? '';

    const errors = {};
    const allowedServices = [<?php echo implode(',', array_map(fn($v) => '"' . addslashes($v) . '"', $typeServiceOptions)); ?>];

    if (titre.length < 3 || titre.length > 150) {
        errors.titre = 'Le titre doit contenir entre 3 et 150 caracteres.';
    }

    if (!allowedServices.includes(typeService)) {
        errors.type_service = 'Le type de service est obligatoire.';
    }

    if (localisation.length > 150) {
        errors.localisation = 'La localisation ne doit pas depasser 150 caracteres.';
    }

    if (dateExpiration) {
        const selected = new Date(dateExpiration + 'T00:00:00');
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        if (selected < today) {
            errors.date_expiration = 'La date d expiration doit etre aujourd hui ou dans le futur.';
        }
    }

    if (!['ouverte', 'fermee'].includes(statut)) {
        errors.statut = 'Le statut est invalide.';
    }

    if (prix !== '') {
        const numericPrice = Number(prix);
        if (Number.isNaN(numericPrice) || numericPrice <= 0 || numericPrice > 1000000) {
            errors.prix = 'Le prix doit etre superieur a 0 et inferieur a 1 000 000.';
        }
    }

    if (description.length > 2000) {
        errors.description = 'La description ne doit pas depasser 2000 caracteres.';
    }

    if (Object.keys(errors).length > 0) {
        event.preventDefault();
        Object.entries(errors).forEach(([field, error]) => setAdminFieldError(field, error));
    }
});
</script>

<style>
.field-error {
    display: block;
    min-height: 18px;
    margin-top: -2px;
    color: #b42318;
    font-size: 12px;
    font-weight: 600;
}

#form-grid [aria-invalid="true"] {
    border-color: #f04438 !important;
    box-shadow: 0 0 0 2px rgba(240, 68, 56, 0.12);
}
</style>

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
                                <form method="POST" style="display:inline;" class="js-status-form" data-target-status="acceptee">
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
                                <form method="POST" style="display:inline;" class="js-status-form" data-target-status="refusee">
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

<script>
function updateActionButtons(row, selectedStatus) {
    row.querySelectorAll('.js-status-form').forEach(form => {
        const button = form.querySelector('button[type="submit"]');
        if (!button) return;

        const targetStatus = form.dataset.targetStatus || '';
        const isSelected = targetStatus === selectedStatus;

        button.disabled = isSelected;
        button.setAttribute('aria-disabled', isSelected ? 'true' : 'false');
        button.classList.toggle('is-selected', isSelected);

        if (targetStatus === 'acceptee') {
            button.textContent = isSelected ? 'Acceptée' : 'Accepter';
        } else if (targetStatus === 'refusee') {
            button.textContent = isSelected ? 'Refusée' : 'Refuser';
        }
    });
}

function setStatusChip(row, status) {
    const chip = row.querySelector('[data-status-chip]');
    if (!chip) return;

    chip.classList.remove('status-acceptee', 'status-refusee', 'status-en_attente');
    const normalized = ['acceptee', 'refusee'].includes(status) ? status : 'en_attente';
    chip.classList.add(status-${normalized});

    if (normalized === 'acceptee') {
        chip.textContent = 'Acceptée';
    } else if (normalized === 'refusee') {
        chip.textContent = 'Refusée';
    } else {
        chip.textContent = 'En attente';
    }
}

document.querySelectorAll('.js-status-form').forEach(form => {
    form.addEventListener('submit', async function(event) {
        event.preventDefault();

        const row = this.closest('tr');
        if (!row) {
            this.submit();
            return;
        }

        const submitButton = this.querySelector('button[type="submit"]');
        if (!submitButton || submitButton.disabled) {
            return;
        }

        const originalLabel = submitButton.textContent;
        submitButton.disabled = true;
        submitButton.textContent = '...';

        try {
            const response = await fetch(window.location.href, {
                method: 'POST',
                body: new FormData(this),
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const rawText = await response.text();
            let data;

            try {
                data = JSON.parse(rawText);
            } catch (_) {
                const start = rawText.lastIndexOf('{');
                const end = rawText.lastIndexOf('}');
                if (start !== -1 && end !== -1 && end > start) {
                    data = JSON.parse(rawText.slice(start, end + 1));
                } else {
                    throw new Error('Reponse serveur invalide.');
                }
            }

            if (!response.ok || !data.ok) {
                throw new Error(data.message || 'Mise à jour impossible.');
            }

            const statusValue = String(data.status || '').toLowerCase().trim();
            const normalized = (statusValue === 'acceptee' || statusValue === 'accepted')
                ? 'acceptee'
                : (statusValue === 'refusee' || statusValue === 'rejetee' || statusValue === 'rejected'
                    ? 'refusee'
                    : 'en_attente');

            setStatusChip(row, normalized);
            updateActionButtons(row, normalized);
        } catch (error) {
            submitButton.disabled = false;
            submitButton.textContent = originalLabel;
            alert(error.message || 'Une erreur est survenue.');
        }
    });
});
</script>

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