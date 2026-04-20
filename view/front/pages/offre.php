<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../model/Offer.php';
require_once __DIR__ . '/../../../model/Candidature.php';
require_once __DIR__ . '/../../../controller/OfferController.php';
require_once __DIR__ . '/../../../controller/CandidatureController.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$offerController = new OfferController();
$candidatureController = new CandidatureController();

$activeOffers = $offerController->listActiveOffers(); //recupere les donnees depuis BD
$message = '';
$messageType = '';
$fieldErrors = [
    'offer_id' => '',
    'experience' => '',
    'competences' => '',
    'cv' => '',
    'message' => '',
];
$formData = [ //stocke les valeurs du formulaire
    'offer_id' => (string) ($activeOffers[0]['id_offre'] ?? ''),
    'experience' => '',
    'competences' => '',
    'message' => '',
];

if (isset($_GET['updated']) && $_GET['updated'] === '1') {
    $message = '✅ Candidature modifiée avec succès.';
    $messageType = 'success';
}

function cleanInput(?string $value): string {
    return trim((string) $value);
}

function findOfferTitle(array $offers, string $offerId): string {
    foreach ($offers as $offer) {
        if ((string) ($offer['id_offre'] ?? '') === $offerId) {
            return (string) ($offer['titre'] ?? 'Aucune offre');
        }
    }
    return 'Aucune offre';
}

function validateApplicationForm(array $post, array $files, OfferController $offerController, bool $requireCv = true): array {
    $errors = [
        'offer_id' => '',
        'experience' => '',
        'competences' => '',
        'cv' => '',
        'message' => '',
    ];

    $offerId = (int) ($post['offer_id'] ?? 0);
    $experience = cleanInput($post['experience'] ?? '');
    $competences = cleanInput($post['competences'] ?? '');
    $message = cleanInput($post['message'] ?? '');

    if ($offerId <= 0 || !$offerController->getOffer($offerId)) {
        $errors['offer_id'] = 'Veuillez choisir une offre valide.';
    }

    if ($experience === '') {
        $errors['experience'] = 'Le nombre d\'annees d\'experience est obligatoire.';
    } elseif (!preg_match('/^\d{1,2}$/', $experience)) {
        $errors['experience'] = 'Saisissez uniquement un nombre entier (ex: 3).';
    } elseif ((int) $experience > 50) {
        $errors['experience'] = 'La valeur maximale autorisee est 50 ans.';
    }

    if ($competences === '') {
        $errors['competences'] = 'Le champ competences est obligatoire.';
    } else {
        $competencesLength = mb_strlen($competences);
        if ($competencesLength < 3) {
            $errors['competences'] = 'Ajoutez au moins 3 caracteres pour decrire vos competences.';
        } elseif ($competencesLength > 255) {
            $errors['competences'] = 'Le champ competences ne doit pas depasser 255 caracteres.';
        }
    }

    if ($message === '') {
        $errors['message'] = 'La lettre de motivation est obligatoire.';
    } else {
        $messageLength = mb_strlen($message);
        if ($messageLength < 20) {
            $errors['message'] = 'La lettre de motivation doit contenir au moins 20 caracteres.';
        } elseif ($messageLength > 2000) {
            $errors['message'] = 'La lettre de motivation ne doit pas depasser 2000 caracteres.';
        }
    }

    $cvError = $files['cv']['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($requireCv && $cvError === UPLOAD_ERR_NO_FILE) {
        $errors['cv'] = 'Le CV est obligatoire.';
    } elseif ($cvError !== UPLOAD_ERR_NO_FILE) {
        if ($cvError !== UPLOAD_ERR_OK) {
            $errors['cv'] = 'Erreur lors du telechargement du CV. Veuillez reessayer.';
        } else {
            $allowedExtensions = ['pdf', 'doc', 'docx'];
            $extension = strtolower(pathinfo((string) ($files['cv']['name'] ?? ''), PATHINFO_EXTENSION));
            $size = (int) ($files['cv']['size'] ?? 0);

            if (!in_array($extension, $allowedExtensions, true)) {
                $errors['cv'] = 'Format de CV invalide. Formats autorises: PDF, DOC, DOCX.';
            } elseif ($size > 5 * 1024 * 1024) {
                $errors['cv'] = 'Le CV depasse la taille maximale autorisee (5 Mo).';
            }
        }
    }

    return $errors;
}

$userId = isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 1;
$editingApplicationId = 0;
$existingCvPath = '';

$loadUserApplications = static function (CandidatureController $controller, int $uid): array {
    $all = $controller->getAllApplications();
    return array_values(array_filter($all, static fn($app) => (int) ($app['id_user'] ?? 0) === $uid));
};

$userApplications = $loadUserApplications($candidatureController, $userId);
$userApplicationsById = [];
foreach ($userApplications as $application) {
    $userApplicationsById[(int) ($application['id'] ?? 0)] = $application;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = (string) $_POST['action'];

    if (in_array($action, ['submit_application', 'update_application'], true)) {
        $formData['offer_id'] = (string) ($_POST['offer_id'] ?? $formData['offer_id']);
        $formData['experience'] = cleanInput($_POST['experience'] ?? '');
        $formData['competences'] = cleanInput($_POST['competences'] ?? '');
        $formData['message'] = cleanInput($_POST['message'] ?? '');
    }

    if ($action === 'delete_application') {
        $applicationId = (int) ($_POST['application_id'] ?? 0);

        if ($applicationId > 0 && isset($userApplicationsById[$applicationId])) {
            $candidatureController->deleteApplication($applicationId);
            $message = '✅ Candidature supprimée avec succès.';
            $messageType = 'success';
            $userApplications = $loadUserApplications($candidatureController, $userId);
            $userApplicationsById = [];
            foreach ($userApplications as $application) {
                $userApplicationsById[(int) ($application['id'] ?? 0)] = $application;
            }
        } else {
            $message = '❌ Impossible de supprimer cette candidature.';
            $messageType = 'error';
        }
    }

    if (in_array($action, ['submit_application', 'update_application'], true)) {
        $isUpdate = $action === 'update_application';
        $applicationId = $isUpdate ? (int) ($_POST['application_id'] ?? 0) : 0;

        if ($isUpdate && !isset($userApplicationsById[$applicationId])) {
            $message = '❌ Cette candidature est introuvable ou inaccessible.';
            $messageType = 'error';
            $isUpdate = false;
        }

        if (!$isUpdate || isset($userApplicationsById[$applicationId])) {
            try {
                $fieldErrors = validateApplicationForm($_POST, $_FILES, $offerController, !$isUpdate);
                $hasValidationErrors = implode('', $fieldErrors) !== '';

                if ($hasValidationErrors) {
                    $message = 'Veuillez corriger les erreurs du formulaire avant de continuer.';
                    $messageType = 'error';
                    $editingApplicationId = $isUpdate ? $applicationId : 0;
                    $existingCvPath = $isUpdate ? (string) ($userApplicationsById[$applicationId]['cv'] ?? '') : '';
                    throw new RuntimeException('validation_error');
                }

                $offerId = (int) ($_POST['offer_id'] ?? 0);
                $hasNewCv = isset($_FILES['cv']) && is_array($_FILES['cv']) && (($_FILES['cv']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE);

                if ($isUpdate) {
                    $cvPath = $hasNewCv
                        ? $candidatureController->uploadCV($_FILES['cv'])
                        : (string) ($_POST['existing_cv'] ?? ($userApplicationsById[$applicationId]['cv'] ?? ''));

                    $candidatureController->updateApplication($applicationId, [
                        'id_user' => $userId,
                        'id_offre' => $offerId,
                        'experience' => $formData['experience'],
                        'competences' => $formData['competences'],
                        'cv' => $cvPath,
                        'message' => $formData['message'],
                        'statut' => (string) ($userApplicationsById[$applicationId]['statut'] ?? 'en attente'),
                    ]);

                    header('Location: index.php?page=offre&updated=1#postuler-offre');
                    exit;
                } else {
                    $cvPath = $candidatureController->uploadCV($_FILES['cv']);

                    $candidatureController->submitApplication([
                        'id_offre' => $offerId,
                        'id_user' => $userId,
                        'experience' => $formData['experience'],
                        'competences' => $formData['competences'],
                        'cv' => $cvPath,
                        'message' => $formData['message'],
                        'statut' => 'en attente',
                    ]);

                    $message = '✅ Candidature soumise avec succès ! Nous vous recontacterons bientôt.';
                    $messageType = 'success';
                }

                $formData['experience'] = '';
                $formData['competences'] = '';
                $formData['message'] = '';
                $formData['offer_id'] = (string) ($activeOffers[0]['id_offre'] ?? '');
                $editingApplicationId = 0;
                $existingCvPath = '';

                $userApplications = $loadUserApplications($candidatureController, $userId);
                $userApplicationsById = [];
                foreach ($userApplications as $application) {
                    $userApplicationsById[(int) ($application['id'] ?? 0)] = $application;
                }
            } catch (Exception $e) {
                if ($e->getMessage() !== 'validation_error') {
                    $message = '❌ Erreur : ' . htmlspecialchars($e->getMessage());
                    $messageType = 'error';
                }
            }
        }
    }
}

if (isset($_GET['edit_application'])) {
    $requestedEditId = (int) $_GET['edit_application'];
    if ($requestedEditId > 0 && isset($userApplicationsById[$requestedEditId])) {
        $editingApplicationId = $requestedEditId;
        $editingApplication = $userApplicationsById[$requestedEditId];
        $formData['offer_id'] = (string) ($editingApplication['id_offre'] ?? $formData['offer_id']);
        $formData['experience'] = (string) ($editingApplication['experience'] ?? '');
        $formData['competences'] = (string) ($editingApplication['competences'] ?? '');
        $formData['message'] = (string) ($editingApplication['message'] ?? '');
        $existingCvPath = (string) ($editingApplication['cv'] ?? '');
    }
}

function formatOfferDate(?string $value): string {
    return $value ? date('d/m/Y', strtotime($value)) : 'N/A';
}

function getOfferBadgeClass(string $statut): string {
    return match($statut) {
        'ouverte' => 'badge-active',
        'fermee' => 'badge-inactive',
        default => 'badge-default'
    };
}

$selectedOfferId = $formData['offer_id'] !== ''
    ? $formData['offer_id']
    : (string) ($activeOffers[0]['id_offre'] ?? '');
$selectedOfferTitle = findOfferTitle($activeOffers, $selectedOfferId);
?>

<?php if (!empty($message)): ?>
    <div style="padding: 12px 16px; margin-bottom: 20px; border-radius: 6px; 
                background: <?php echo $messageType === 'success' ? '#d4edda' : '#f8d7da'; ?>; 
                color: <?php echo $messageType === 'success' ? '#155724' : '#721c24'; ?>; 
                border: 1px solid <?php echo $messageType === 'success' ? '#c3e6cb' : '#f5c6cb'; ?>;">
        <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php endif; ?>

<section class="page-hero reveal">
    <span class="section-badge">Offres Spéciales</span>
    <h1 class="page-title">Découvrez nos meilleures offres</h1>
    <p class="page-intro">
        Des offres exclusives et des réductions exceptionnelles sur les services de nos meilleurs prestataires.
    </p>
</section>

<section class="action-bar reveal">
    <div class="search-box">
        <input type="text" id="searchOffers" placeholder="Rechercher une offre...">
        <select id="filterOffers">
            <option value="">Toutes les offres</option>
            <option value="active">Offres Actives</option>
            <option value="prix">Trier par Prix</option>
            <option value="date">Plus Récentes</option>
        </select>
    </div>

    <div class="icon-actions">
        <a class="solid-btn" href="#mes-candidatures">📋 Mes Candidatures</a>
    </div>
</section>

<section class="admin-stats reveal">
    <article class="admin-stat">
        <strong><?php echo htmlspecialchars(count($activeOffers), ENT_QUOTES, 'UTF-8'); ?></strong>
        <span>Offres Actives</span>
    </article>
    <article class="admin-stat">
        <strong><?php echo htmlspecialchars(count(array_filter($activeOffers, fn($o) => !empty($o['localisation']))), ENT_QUOTES, 'UTF-8'); ?></strong>
        <span>Offres Localisées</span>
    </article>
    <article class="admin-stat">
        <strong><?php echo htmlspecialchars(count(array_unique(array_column($activeOffers, 'type_service'))), ENT_QUOTES, 'UTF-8'); ?></strong>
        <span>Types de Services</span>
    </article>
</section>

<section class="module-split reveal">
    <div class="offers-column">
        <?php if (empty($activeOffers)): ?>
            <article class="card offers-empty-state">
                <h3>Aucune offre disponible pour le moment</h3>
                <p>Revenez bientôt pour découvrir de nouvelles offres exclusives !</p>
            </article>
        <?php else: ?>
            <?php foreach ($activeOffers as $offer): ?>
                <article class="card offer-card">
                    <div class="offer-card-head">
                        <span class="section-badge"><?php echo htmlspecialchars($offer['type_service'] ?: 'Service', ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="badge <?php echo htmlspecialchars(getOfferBadgeClass($offer['statut']), ENT_QUOTES, 'UTF-8'); ?> offer-status-badge">
                            <?php echo htmlspecialchars(ucfirst($offer['statut']), ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </div>

                    <h3 class="offer-title">
                        <?php echo htmlspecialchars($offer['titre'], ENT_QUOTES, 'UTF-8'); ?>
                    </h3>

                    <p class="offer-summary">
                        <?php echo htmlspecialchars(substr($offer['description'], 0, 120) . (strlen($offer['description']) > 120 ? '...' : ''), ENT_QUOTES, 'UTF-8'); ?>
                    </p>

                    <div class="offer-glance-grid">
                        <div>
                            <strong class="offer-glance-label">Localisation</strong>
                            <p class="offer-glance-value">
                                <?php echo htmlspecialchars($offer['localisation'] ?: 'Non spécifiée', ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        </div>
                        <div>
                            <strong class="offer-glance-label">Valide jusqu'au</strong>
                            <p class="offer-glance-value">
                                <?php echo htmlspecialchars(formatOfferDate($offer['date_expiration']), ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        </div>
                    </div>

                    <div class="meta-row offer-meta-row">
                        <span>📅 Publié : <?php echo htmlspecialchars(formatOfferDate($offer['date_publication']), ENT_QUOTES, 'UTF-8'); ?></span>
                        <span>
                            💰 Prix :
                            <?php echo htmlspecialchars(isset($offer['prix']) && $offer['prix'] !== null ? number_format((float) $offer['prix'], 2, '.', ' ') . ' TND' : 'N/A', ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </div>

                    <div class="icon-actions offer-card-actions">
                        <a class="solid-btn postuler-btn" href="#postuler-offre" data-offer-id="<?php echo htmlspecialchars($offer['id_offre'], ENT_QUOTES, 'UTF-8'); ?>" data-offer-title="<?php echo htmlspecialchars($offer['titre'], ENT_QUOTES, 'UTF-8'); ?>">✓ Postuler</a>
                        <button type="button" class="small-btn toggle-details-btn" aria-expanded="false">Voir détails</button>
                    </div>

                    <div class="offer-details">
                        <div class="offer-details-grid">
                            <p><strong>Titre :</strong> <?php echo htmlspecialchars($offer['titre'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
                            <p><strong>Type de service :</strong> <?php echo htmlspecialchars($offer['type_service'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
                            <p><strong>Localisation :</strong> <?php echo htmlspecialchars($offer['localisation'] ?: 'Non spécifiée', ENT_QUOTES, 'UTF-8'); ?></p>
                            <p><strong>Prix :</strong> <?php echo htmlspecialchars(isset($offer['prix']) && $offer['prix'] !== null ? number_format((float) $offer['prix'], 2, '.', ' ') . ' TND' : 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
                            <p><strong>Date publication :</strong> <?php echo htmlspecialchars(formatOfferDate($offer['date_publication']), ENT_QUOTES, 'UTF-8'); ?></p>
                            <p><strong>Date expiration :</strong> <?php echo htmlspecialchars(formatOfferDate($offer['date_expiration']), ENT_QUOTES, 'UTF-8'); ?></p>
                            <p><strong>Statut :</strong> <?php echo htmlspecialchars(ucfirst($offer['statut'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></p>
                            <p><strong>Admin :</strong> <?php echo htmlspecialchars(trim((string) (($offer['admin_nom'] ?? '') . ' ' . ($offer['admin_prenom'] ?? ''))) ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>
                        <p class="offer-description-title"><strong>Description complète :</strong></p>
                        <p class="offer-description-full">
                            <?php echo htmlspecialchars($offer['description'] ?: 'Aucune description', ENT_QUOTES, 'UTF-8'); ?>
                        </p>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="apply-column">
        <?php if (!empty($activeOffers)): ?>
            <article class="panel apply-panel" id="postuler-offre">
                <span class="section-badge"><?php echo $editingApplicationId > 0 ? '✏️ Modifier candidature' : '📨 Postuler'; ?></span>

                <form method="POST" enctype="multipart/form-data" id="applicationForm" class="application-form" novalidate data-require-cv="<?php echo $editingApplicationId > 0 ? 'false' : 'true'; ?>">
                    <input type="hidden" name="action" value="<?php echo $editingApplicationId > 0 ? 'update_application' : 'submit_application'; ?>">
                    <?php if ($editingApplicationId > 0): ?>
                        <input type="hidden" name="application_id" value="<?php echo htmlspecialchars((string) $editingApplicationId, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="existing_cv" value="<?php echo htmlspecialchars($existingCvPath, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php endif; ?>
                    <select name="offer_id" id="offerIdField" style="display:none;">
                        <?php foreach ($activeOffers as $offerOption): ?>
                            <option value="<?php echo htmlspecialchars($offerOption['id_offre'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo (string) $offerOption['id_offre'] === $selectedOfferId ? ' selected' : ''; ?>><?php echo htmlspecialchars($offerOption['titre'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="field-error" data-error-for="offer_id"><?php echo htmlspecialchars($fieldErrors['offer_id'], ENT_QUOTES, 'UTF-8'); ?></small>
                    <p id="selectedOfferLabel" class="selected-offer-label">
                        Offre sélectionnée : <span><?php echo htmlspecialchars($selectedOfferTitle, ENT_QUOTES, 'UTF-8'); ?></span>
                    </p>
                    <div class="form-grid application-grid">
                        <input type="text" name="experience" id="experienceField" placeholder="Années d'experience" value="<?php echo htmlspecialchars($formData['experience'], ENT_QUOTES, 'UTF-8'); ?>" aria-invalid="<?php echo $fieldErrors['experience'] !== '' ? 'true' : 'false'; ?>">
                        <input type="text" name="competences" id="competencesField" placeholder="Vos competences cles" value="<?php echo htmlspecialchars($formData['competences'], ENT_QUOTES, 'UTF-8'); ?>" aria-invalid="<?php echo $fieldErrors['competences'] !== '' ? 'true' : 'false'; ?>">
                        <small class="field-error" data-error-for="experience"><?php echo htmlspecialchars($fieldErrors['experience'], ENT_QUOTES, 'UTF-8'); ?></small>
                        <small class="field-error" data-error-for="competences"><?php echo htmlspecialchars($fieldErrors['competences'], ENT_QUOTES, 'UTF-8'); ?></small>

                        <input type="file" name="cv" id="cvField" accept=".pdf,.doc,.docx" aria-invalid="<?php echo $fieldErrors['cv'] !== '' ? 'true' : 'false'; ?>">
                        <?php if ($editingApplicationId > 0 && $existingCvPath !== ''): ?>
                            <small class="cv-helper full-row">CV actuel: <?php echo htmlspecialchars(basename($existingCvPath), ENT_QUOTES, 'UTF-8'); ?>. Laissez vide pour le conserver.</small>
                        <?php endif; ?>
                        <small class="field-error full-row" data-error-for="cv"><?php echo htmlspecialchars($fieldErrors['cv'], ENT_QUOTES, 'UTF-8'); ?></small>

                        <textarea name="message" id="messageField" placeholder="Lettre de motivation..." rows="4" aria-invalid="<?php echo $fieldErrors['message'] !== '' ? 'true' : 'false'; ?>"><?php echo htmlspecialchars($formData['message'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                        <small class="field-error full-row" data-error-for="message"><?php echo htmlspecialchars($fieldErrors['message'], ENT_QUOTES, 'UTF-8'); ?></small>
                    </div>

                    <div class="icon-actions apply-actions">
                        <button type="submit" class="solid-btn full-width-btn"><?php echo $editingApplicationId > 0 ? 'Enregistrer les modifications' : 'Envoyer ma candidature'; ?></button>
                        <button type="reset" class="outline-btn full-width-btn">Réinitialiser</button>
                        <?php if ($editingApplicationId > 0): ?>
                            <a href="index.php?page=offre#postuler-offre" class="outline-btn full-width-btn">Annuler la modification</a>
                        <?php endif; ?>
                    </div>
                </form>
            </article>
        <?php else: ?>
            <article class="panel apply-panel" id="postuler-offre">
                <span class="section-badge">📨 Postuler</span>
                <div class="application-empty-state">
                    <strong>Aucune offre active disponible</strong>
                    <p>Vous ne pouvez pas postuler pour le moment car il n’y a aucune offre active dans le catalogue.</p>
                </div>
            </article>
        <?php endif; ?>

        <div class="panel benefits-panel">
            <span class="section-badge">✨ Avantages</span>
            <div class="feature-list">
                <div class="feature-item">✓ Offres exclusives</div>
                <div class="feature-item">✓ Prestataires vérifiés</div>
                <div class="feature-item">✓ Paiement sécurisé</div>
                <div class="feature-item">✓ Support client 24/7</div>
                <div class="feature-item">✓ Garantie satisfaction</div>
            </div>
        </div>
    </div>
</section>

<section class="admin-panel reveal my-applications-panel" id="mes-candidatures">
    <span class="section-badge">📋 Mes Candidatures</span>

    <?php if (empty($userApplications)): ?>
        <div class="application-empty-state" style="margin-top: 14px;">
            <strong>Aucune candidature enregistrée</strong>
            <p>Commencez par postuler à une offre pour voir vos candidatures ici.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap" style="margin-top: 14px;">
            <table class="module-table">
                <thead>
                    <tr>
                        <th>Offre</th>
                        <th>Date</th>
                        <th>Statut</th>
                        <th>Expérience</th>
                        <th>Compétences</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($userApplications as $application): ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string) ($application['offer_titre'] ?? 'Offre supprimée'), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(formatOfferDate($application['created_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($application['statut'] ?? 'en attente'), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($application['experience'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($application['competences'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="admin-tools">
                                <a class="small-btn" href="index.php?page=offre&edit_application=<?php echo htmlspecialchars((string) ($application['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>#postuler-offre">Modifier</a>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="delete_application">
                                    <input type="hidden" name="application_id" value="<?php echo htmlspecialchars((string) ($application['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit" class="danger-btn" onclick="return confirm('Supprimer cette candidature ?');">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<style>
.offers-column {
    flex: 1;
    min-width: 0;
}

.apply-column {
    flex: 0.38;
    min-width: 320px;
}

.offers-empty-state {
    text-align: center;
    padding: 40px;
}

.offers-empty-state p {
    margin-top: 10px;
}

.offer-card {
    border-left: 4px solid #4CAF50 !important;
    transition: transform 0.2s, box-shadow 0.2s;
    overflow: hidden;
}

.offer-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.offer-card-head {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 12px;
    gap: 12px;
}

.offer-status-badge {
    font-size: 11px;
    padding: 4px 8px;
    border-radius: 4px;
    white-space: nowrap;
}

.offer-title {
    margin: 10px 0;
    line-height: 1.25;
}

.offer-summary {
    margin: 10px 0;
    line-height: 1.6;
}

.offer-glance-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin: 15px 0;
    padding: 12px;
    background: rgba(148, 163, 184, 0.12);
    border-radius: 10px;
}

.offer-glance-label {
    font-size: 12px;
    color: #8a94a6;
}

.offer-glance-value {
    font-size: 14px;
    margin: 5px 0;
}

.offer-meta-row {
    font-size: 13px;
    margin: 10px 0;
    display: flex;
    justify-content: space-between;
    gap: 12px;
    flex-wrap: wrap;
}

.offer-card-actions {
    margin-top: 14px;
}

.offer-details {
    display: none;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid rgba(148, 163, 184, 0.35);
}

.offer-details-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px 16px;
    margin-bottom: 12px;
}

.offer-details-grid p {
    margin: 0;
    font-size: 14px;
}

.offer-description-title {
    margin: 0 0 6px;
}

.offer-description-full {
    line-height: 1.6;
    margin: 0;
}

.apply-panel {
    position: sticky;
    top: 18px;
}

.application-form {
    margin-top: 15px;
}

.selected-offer-label {
    font-weight: 700;
    margin-bottom: 0.75rem;
}

.application-grid {
    gap: 10px;
}

.application-grid input[type="text"],
.application-grid input[type="file"],
.application-grid textarea {
    padding: 10px;
    border: 1px solid #d5dbe4;
    border-radius: 10px;
    font-family: inherit;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.cv-helper {
    color: #5f6b7c;
    font-size: 12px;
    margin-top: -2px;
}

.application-grid input[type="text"]:focus,
.application-grid input[type="file"]:focus,
.application-grid textarea:focus {
    outline: none;
    border-color: #5a95ee;
    box-shadow: 0 0 0 3px rgba(90, 149, 238, 0.16);
}

.full-row {
    grid-column: 1 / -1;
}

.apply-actions {
    margin-top: 14px;
    flex-direction: column;
}

.full-width-btn {
    width: 100%;
}

.application-empty-state {
    margin-top: 15px;
    padding: 20px;
    background: #fff6f6;
    border: 1px solid #f5c6cb;
    border-radius: 12px;
    color: #721c24;
}

.application-empty-state p {
    margin: 10px 0 0;
    font-size: 14px;
    line-height: 1.5;
}

.benefits-panel {
    margin-top: 18px;
}

.my-applications-panel {
    margin-top: 22px;
}

.my-applications-panel .module-table td,
.my-applications-panel .module-table th {
    vertical-align: middle;
}

.badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
}

.badge-active {
    background: #4CAF50;
    color: white;
}

.badge-inactive {
    background: #f44336;
    color: white;
}

.badge-expired {
    background: #ff9800;
    color: white;
}

.field-error {
    display: block;
    min-height: 18px;
    margin-top: -2px;
    color: #b42318;
    font-size: 12px;
    font-weight: 600;
}

#applicationForm [aria-invalid="true"] {
    border-color: #f04438 !important;
    box-shadow: 0 0 0 2px rgba(240, 68, 56, 0.12);
}

@media (max-width: 1024px) {
    .apply-column {
        min-width: 290px;
    }

    .offer-details-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 780px) {
    .apply-panel {
        position: static;
    }

    .offer-glance-grid {
        grid-template-columns: 1fr;
    }

    .offer-card-actions {
        flex-direction: column;
        align-items: stretch;
    }

    .offer-card-actions .solid-btn,
    .offer-card-actions .small-btn {
        width: 100%;
        text-align: center;
        justify-content: center;
    }
}
</style>
