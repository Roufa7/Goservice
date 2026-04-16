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

$activeOffers = $offerModel->findActive(); //recupere les donnees depuis BD
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

function validateApplicationForm(array $post, array $files, OfferController $offerController): array {
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

    if (!isset($files['cv']) || !is_array($files['cv']) || ($files['cv']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        $errors['cv'] = 'Le CV est obligatoire.';
    } elseif (($files['cv']['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
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

    return $errors;
}

// Traiter la soumission du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_application') {
    $formData['offer_id'] = (string) ($_POST['offer_id'] ?? $formData['offer_id']);
    $formData['experience'] = cleanInput($_POST['experience'] ?? '');
    $formData['competences'] = cleanInput($_POST['competences'] ?? '');
    $formData['message'] = cleanInput($_POST['message'] ?? '');

    try {
        $fieldErrors = validateApplicationForm($_POST, $_FILES, $offerController);
        $hasValidationErrors = implode('', $fieldErrors) !== '';

        if ($hasValidationErrors) {
            $message = 'Veuillez corriger les erreurs du formulaire avant de soumettre votre candidature.';
            $messageType = 'error';
            throw new RuntimeException('validation_error');
        }

        $offerId = intval($_POST['offer_id'] ?? 0);

        // Assume user is logged in, get id_user from session
        $userId = $_SESSION['user_id'] ?? 1; // Placeholder

        $cvPath = $applicationController->uploadCV($_FILES['cv']);

        $applicationController->submitApplication([
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
        $formData['experience'] = '';
        $formData['competences'] = '';
        $formData['message'] = '';
        $formData['offer_id'] = (string) ($activeOffers[0]['id_offre'] ?? '');
    } catch (Exception $e) {
        if ($e->getMessage() !== 'validation_error') {
            $message = '❌ Erreur : ' . htmlspecialchars($e->getMessage());
            $messageType = 'error';
        }
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
    <div style="flex: 1;">
        <?php if (empty($activeOffers)): ?>
            <article class="card" style="text-align: center; padding: 40px;">
                <h3>Aucune offre disponible pour le moment</h3>
                <p style="color: #666; margin-top: 10px;">Revenez bientôt pour découvrir de nouvelles offres exclusives !</p>
            </article>
        <?php else: ?>
            <?php foreach ($activeOffers as $offer): ?>
                <article class="card offer-card">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                        <span class="section-badge"><?php echo htmlspecialchars($offer['type_service'] ?: 'Service', ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="badge <?php echo htmlspecialchars(getOfferBadgeClass($offer['statut']), ENT_QUOTES, 'UTF-8'); ?>" style="font-size: 11px; padding: 4px 8px; border-radius: 4px; background: #4CAF50; color: white;">
                            <?php echo htmlspecialchars(ucfirst($offer['statut']), ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </div>

                    <h3 style="margin: 10px 0; color: #333;">
                        <?php echo htmlspecialchars($offer['titre'], ENT_QUOTES, 'UTF-8'); ?>
                    </h3>

                    <p style="color: #666; margin: 10px 0; line-height: 1.6;">
                        <?php echo htmlspecialchars(substr($offer['description'], 0, 120) . (strlen($offer['description']) > 120 ? '...' : ''), ENT_QUOTES, 'UTF-8'); ?>
                    </p>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 15px 0; padding: 12px; background: #f5f5f5; border-radius: 6px;">
                        <div>
                            <strong style="font-size: 12px; color: #999;">Localisation</strong>
                            <p style="font-size: 14px; color: #333; margin: 5px 0;">
                                <?php echo htmlspecialchars($offer['localisation'] ?: 'Non spécifiée', ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        </div>
                        <div>
                            <strong style="font-size: 12px; color: #999;">Valide jusqu'au</strong>
                            <p style="font-size: 14px; color: #333; margin: 5px 0;">
                                <?php echo htmlspecialchars(formatOfferDate($offer['date_expiration']), ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        </div>
                    </div>

                    <div class="meta-row" style="font-size: 13px; color: #999; margin: 10px 0;">
                        <span>📅 Publié : <?php echo htmlspecialchars(formatOfferDate($offer['date_publication']), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>

                    <div class="icon-actions" style="margin-top: 14px;">
                        <a class="solid-btn postuler-btn" href="#postuler-offre" data-offer-id="<?php echo htmlspecialchars($offer['id_offre'], ENT_QUOTES, 'UTF-8'); ?>" data-offer-title="<?php echo htmlspecialchars($offer['titre'], ENT_QUOTES, 'UTF-8'); ?>">✓ Postuler</a>
                        <button class="small-btn" onclick="toggleDetails(this)">Voir détails</button>
                    </div>

                    <div class="offer-details" style="display: none; margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                        <p><strong>Description complète :</strong></p>
                        <p style="color: #666; line-height: 1.6;">
                            <?php echo htmlspecialchars($offer['description'], ENT_QUOTES, 'UTF-8'); ?>
                        </p>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div style="flex: 0.3; min-width: 300px;">
        <?php if (!empty($activeOffers)): ?>
            <article class="panel" id="postuler-offre">
                <span class="section-badge">📨 Postuler</span>

                <form method="POST" enctype="multipart/form-data" id="applicationForm" style="margin-top: 15px;" novalidate>
                    <input type="hidden" name="action" value="submit_application">
                    <select name="offer_id" id="offerIdField" style="display:none;">
                        <?php foreach ($activeOffers as $offerOption): ?>
                            <option value="<?php echo htmlspecialchars($offerOption['id_offre'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo (string) $offerOption['id_offre'] === $selectedOfferId ? ' selected' : ''; ?>><?php echo htmlspecialchars($offerOption['titre'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <small class="field-error" data-error-for="offer_id"><?php echo htmlspecialchars($fieldErrors['offer_id'], ENT_QUOTES, 'UTF-8'); ?></small>
                    <p id="selectedOfferLabel" style="font-weight:700; margin-bottom:0.75rem;">
                        Offre sélectionnée : <span><?php echo htmlspecialchars($selectedOfferTitle, ENT_QUOTES, 'UTF-8'); ?></span>
                    </p>
                    <div class="form-grid" style="gap: 10px;">
                        <input type="text" name="experience" id="experienceField" placeholder="Années d'experience" value="<?php echo htmlspecialchars($formData['experience'], ENT_QUOTES, 'UTF-8'); ?>" aria-invalid="<?php echo $fieldErrors['experience'] !== '' ? 'true' : 'false'; ?>" style="padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <input type="text" name="competences" id="competencesField" placeholder="Vos competences cles" value="<?php echo htmlspecialchars($formData['competences'], ENT_QUOTES, 'UTF-8'); ?>" aria-invalid="<?php echo $fieldErrors['competences'] !== '' ? 'true' : 'false'; ?>" style="padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <small class="field-error" data-error-for="experience"><?php echo htmlspecialchars($fieldErrors['experience'], ENT_QUOTES, 'UTF-8'); ?></small>
                        <small class="field-error" data-error-for="competences"><?php echo htmlspecialchars($fieldErrors['competences'], ENT_QUOTES, 'UTF-8'); ?></small>

                        <input type="file" name="cv" id="cvField" accept=".pdf,.doc,.docx" aria-invalid="<?php echo $fieldErrors['cv'] !== '' ? 'true' : 'false'; ?>" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <small class="field-error" data-error-for="cv" style="grid-column: 1 / -1;"><?php echo htmlspecialchars($fieldErrors['cv'], ENT_QUOTES, 'UTF-8'); ?></small>

                        <textarea name="message" id="messageField" placeholder="Lettre de motivation..." rows="4" aria-invalid="<?php echo $fieldErrors['message'] !== '' ? 'true' : 'false'; ?>" style="padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: Arial, sans-serif;"><?php echo htmlspecialchars($formData['message'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                        <small class="field-error" data-error-for="message" style="grid-column: 1 / -1;"><?php echo htmlspecialchars($fieldErrors['message'], ENT_QUOTES, 'UTF-8'); ?></small>
                    </div>

                    <div class="icon-actions" style="margin-top: 14px; flex-direction: column;">
                        <button type="submit" class="solid-btn" style="width: 100%;">Envoyer ma candidature</button>
                        <button type="reset" class="outline-btn" style="width: 100%; margin-top: 8px;">Réinitialiser</button>
                    </div>
                </form>
            </article>
        <?php else: ?>
            <article class="panel" id="postuler-offre">
                <span class="section-badge">📨 Postuler</span>
                <div style="margin-top: 15px; padding: 20px; background: #fff6f6; border: 1px solid #f5c6cb; border-radius: 12px; color: #721c24;">
                    <strong>Aucune offre active disponible</strong>
                    <p style="margin: 10px 0 0; font-size: 14px; line-height: 1.5;">Vous ne pouvez pas postuler pour le moment car il n’y a aucune offre active dans le catalogue.</p>
                </div>
            </article>
        <?php endif; ?>

        <div class="panel" style="margin-top: 18px;">
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

<script>
function toggleDetails(button) {
    const card = button.closest('.card');
    const details = card.querySelector('.offer-details');
    if (details) {
        details.style.display = details.style.display === 'none' ? 'block' : 'none';
        button.textContent = details.style.display === 'none' ? 'Voir détails' : 'Masquer détails';
    }
}

document.querySelectorAll('.postuler-btn').forEach(button => {
    button.addEventListener('click', function(event) {
        event.preventDefault();
        const offerId = this.dataset.offerId;
        const offerTitle = this.dataset.offerTitle;
        const offerField = document.getElementById('offerIdField');
        const selectedLabel = document.getElementById('selectedOfferLabel');

        if (offerField) {
            offerField.value = offerId;
        }

        if (selectedLabel) {
            selectedLabel.querySelector('span').textContent = offerTitle;
        }

        location.hash = '#postuler-offre';
    });
});

document.getElementById('searchOffers')?.addEventListener('input', function(e) {
    const search = e.target.value.toLowerCase();
    document.querySelectorAll('.offer-card').forEach(card => {
        const text = card.textContent.toLowerCase();
        card.style.display = text.includes(search) ? 'block' : 'none';
    });
});

function setFieldError(fieldName, message) {
    const errorNode = document.querySelector([data-error-for="${fieldName}"]);
    if (errorNode) {
        errorNode.textContent = message;
    }
}

function clearFieldErrors() {
    document.querySelectorAll('.field-error').forEach(node => {
        node.textContent = '';
    });
}

document.getElementById('applicationForm')?.addEventListener('submit', function(event) {
    clearFieldErrors();

    const experience = document.getElementById('experienceField')?.value.trim() ?? '';
    const competences = document.getElementById('competencesField')?.value.trim() ?? '';
    const message = document.getElementById('messageField')?.value.trim() ?? '';
    const cvInput = document.getElementById('cvField');

    const errors = {};

    if (!/^\d{1,2}$/.test(experience)) {
        errors.experience = "Saisissez une valeur numerique valide (0 a 50).";
    } else if (parseInt(experience, 10) > 50) {
        errors.experience = "La valeur maximale autorisee est 50 ans.";
    }

    if (competences.length < 3) {
        errors.competences = "Ajoutez au moins 3 caracteres.";
    } else if (competences.length > 255) {
        errors.competences = "Maximum 255 caracteres.";
    }

    if (message.length < 20) {
        errors.message = "La lettre de motivation doit contenir au moins 20 caracteres.";
    } else if (message.length > 2000) {
        errors.message = "Maximum 2000 caracteres.";
    }

    if (!cvInput || !cvInput.files || cvInput.files.length === 0) {
        errors.cv = "Le CV est obligatoire.";
    } else {
        const file = cvInput.files[0];
        const extension = (file.name.split('.').pop() || '').toLowerCase();
        const allowed = ['pdf', 'doc', 'docx'];

        if (!allowed.includes(extension)) {
            errors.cv = "Formats autorises: PDF, DOC, DOCX.";
        } else if (file.size > 5 * 1024 * 1024) {
            errors.cv = "Le CV depasse 5 Mo.";
        }
    }

    if (Object.keys(errors).length > 0) {
        event.preventDefault();
        Object.entries(errors).forEach(([field, error]) => setFieldError(field, error));
    }
});
</script>

<style>
.offer-card {
    border-left: 4px solid #4CAF50 !important;
    transition: transform 0.2s, box-shadow 0.2s;
}

.offer-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
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
</style>
