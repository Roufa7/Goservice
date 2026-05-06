<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../view/i18n.php';
require_once __DIR__ . '/../../../model/Offer.php';
require_once __DIR__ . '/../../../model/Candidature.php';
require_once __DIR__ . '/../../../controller/OfferController.php';
require_once __DIR__ . '/../../../controller/CandidatureController.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

app_set_language_from_request();

// Handle marking a notification read (only affect this page session scope)
if (isset($_GET['mark_offer_notification'])) {
    $markId = (string) ($_GET['mark_offer_notification'] ?? '');
    if ($markId !== '' && isset($_SESSION['offer_notifications']) && is_array($_SESSION['offer_notifications'])) {
        foreach ($_SESSION['offer_notifications'] as $k => $n) {
            if (!empty($n['id']) && (string) $n['id'] === $markId) {
                unset($_SESSION['offer_notifications'][$k]);
                break;
            }
        }
        $_SESSION['offer_notifications'] = array_values($_SESSION['offer_notifications']);
    }
    $targetOffer = isset($_GET['offer_id']) ? rawurlencode((string)$_GET['offer_id']) : '';
    $redirectUrl = 'index.php?page=offre';
    if ($targetOffer !== '') {
        $redirectUrl .= '&offer_id=' . $targetOffer . '#offer-' . $targetOffer;
    }
    header('Location: ' . $redirectUrl);
    exit;
}

$offerController = new OfferController();
$candidatureController = new CandidatureController();

$activeOffers = $offerController->listActiveOffers(); //recupere les donnees depuis BD

// Get search, service filter, and sort parameters (search + service applied client-side; sort uses GET reload)
$searchTerm = cleanInput((string) ($_GET['q'] ?? ''));
$selectedServiceFront = cleanInput((string) ($_GET['type'] ?? ''));
$sortOption = cleanInput((string) ($_GET['sort'] ?? 'date_desc'));

// Helper functions for sorting (search + service filter are client-side on the offers list)
function offerSortKeyFront(array $offer, string $sort): mixed {
    return match ($sort) {
        'titre_asc', 'titre_desc' => mb_strtolower(trim((string) ($offer['titre'] ?? '')), 'UTF-8'),
        'type_asc', 'type_desc' => mb_strtolower(trim((string) ($offer['type_service'] ?? '')), 'UTF-8'),
        'prix_asc', 'prix_desc' => isset($offer['prix']) && $offer['prix'] !== '' ? (float) $offer['prix'] : null,
        'localisation_asc', 'localisation_desc' => mb_strtolower(trim((string) ($offer['localisation'] ?? '')), 'UTF-8'),
        default => !empty($offer['date_publication']) ? strtotime((string) $offer['date_publication']) : null,
    };
}

function sortOffersFront(array $offers, string $sort): array {
    $sort = in_array($sort, [
        'date_asc', 'date_desc', 'date_publication_asc', 'date_publication_desc',
        'titre_asc', 'titre_desc', 'type_asc', 'type_desc',
        'localisation_asc', 'localisation_desc', 'prix_asc', 'prix_desc',
    ], true) ? $sort : 'date_publication_desc';

    usort($offers, function (array $left, array $right) use ($sort): int {
        $leftKey = offerSortKeyFront($left, $sort);
        $rightKey = offerSortKeyFront($right, $sort);

        if ($leftKey === $rightKey) {
            return 0;
        }
        if ($leftKey === null) {
            return 1;
        }
        if ($rightKey === null) {
            return -1;
        }

        $comparison = is_string($leftKey) && is_string($rightKey)
            ? strcasecmp($leftKey, $rightKey)
            : ($leftKey <=> $rightKey);

        return (str_ends_with($sort, '_desc') || $sort === 'date_desc') ? -$comparison : $comparison;
    });

    return array_values($offers);
}

// All active offers for the list (sorted); search and service filter run in the browser without reload
$sortedOffersForList = sortOffersFront($activeOffers, $sortOption);
$typeServiceOptionsFront = array_values(array_unique(array_filter(array_map(
    static fn (array $o): string => trim((string) ($o['type_service'] ?? '')),
    $activeOffers
), static fn (string $s): bool => $s !== '')));
natcasesort($typeServiceOptionsFront);

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
    $message = app_text('✅ Candidature modifiée avec succès.','✅ Application updated successfully.','✅ تم تعديل الطلب بنجاح.');
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
        $errors['offer_id'] = app_text('Veuillez choisir une offre valide.','Please choose a valid offer.','يرجى اختيار عرض صالح.');
    }

    if ($experience === '') {
        $errors['experience'] = app_text('Le nombre d\'annees d\'experience est obligatoire.','Years of experience is required.','سنة الخبرة مطلوبة.');
    } elseif (!preg_match('/^\d{1,2}$/', $experience)) {
        $errors['experience'] = app_text('Saisissez uniquement un nombre entier (ex: 3).','Please enter an integer number (ex: 3).','الرجاء إدخال عدد صحيح (مثال: 3).');
    } elseif ((int) $experience > 50) {
        $errors['experience'] = app_text('La valeur maximale autorisee est 50 ans.','Maximum allowed value is 50.','الحد الأقصى المسموح به هو 50.');
    }

    if ($competences === '') {
        $errors['competences'] = app_text('Le champ competences est obligatoire.','The skills field is required.','حقل المهارات مطلوب.');
    } else {
        $competencesLength = mb_strlen($competences);
        if ($competencesLength < 3) {
            $errors['competences'] = app_text('Ajoutez au moins 3 caracteres pour decrire vos competences.','Add at least 3 characters to describe your skills.','أضف 3 أحرف على الأقل لوصف مهاراتك.');
        } elseif ($competencesLength > 255) {
            $errors['competences'] = app_text('Le champ competences ne doit pas depasser 255 caracteres.','The skills field must not exceed 255 characters.','يجب ألا يتجاوز حقل المهارات 255 حرفًا.');
        }
    }

    if ($message === '') {
        $errors['message'] = app_text('La lettre de motivation est obligatoire.','The cover letter is required.','رسالة التحفيز مطلوبة.');
    } else {
        $messageLength = mb_strlen($message);
        if ($messageLength < 20) {
            $errors['message'] = app_text('La lettre de motivation doit contenir au moins 20 caracteres.','The cover letter must contain at least 20 characters.','يجب أن تحتوي رسالة التحفيز على 20 حرفًا على الأقل.');
        } elseif ($messageLength > 2000) {
            $errors['message'] = app_text('La lettre de motivation ne doit pas depasser 2000 caracteres.','The cover letter must not exceed 2000 characters.','يجب ألا تتجاوز رسالة التحفيز 2000 حرف.');
        }
    }

    $cvError = $files['cv']['error'] ?? UPLOAD_ERR_NO_FILE;
    if ($requireCv && $cvError === UPLOAD_ERR_NO_FILE) {
        $errors['cv'] = app_text('Le CV est obligatoire.','CV is required.','السيرة الذاتية مطلوبة.');
    } elseif ($cvError !== UPLOAD_ERR_NO_FILE) {
        if ($cvError !== UPLOAD_ERR_OK) {
            $errors['cv'] = app_text('Erreur lors du telechargement du CV. Veuillez reessayer.','Error uploading the CV. Please try again.','حدث خطأ أثناء تحميل السيرة الذاتية. حاول مرة أخرى.');
        } else {
            $allowedExtensions = ['pdf', 'doc', 'docx'];
            $extension = strtolower(pathinfo((string) ($files['cv']['name'] ?? ''), PATHINFO_EXTENSION));
            $size = (int) ($files['cv']['size'] ?? 0);

            if (!in_array($extension, $allowedExtensions, true)) {
                $errors['cv'] = app_text('Format de CV invalide. Formats autorises: PDF, DOC, DOCX.','Invalid CV format. Allowed formats: PDF, DOC, DOCX.','تنسيق سيرة ذاتية غير صالح. الصيغ المسموح بها: PDF، DOC، DOCX.');
            } elseif ($size > 5 * 1024 * 1024) {
                $errors['cv'] = app_text('Le CV depasse la taille maximale autorisee (5 Mo).','The CV exceeds the maximum allowed size (5 MB).','تتجاوز السيرة الذاتية الحد الأقصى المسموح به (5 ميغابايت).');
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
            $message = app_text('✅ Candidature supprimée avec succès.','✅ Application deleted successfully.','✅ تم حذف الطلب بنجاح.');
            $messageType = 'success';
            $userApplications = $loadUserApplications($candidatureController, $userId);
            $userApplicationsById = [];
            foreach ($userApplications as $application) {
                $userApplicationsById[(int) ($application['id'] ?? 0)] = $application;
            }
        } else {
            $message = app_text('❌ Impossible de supprimer cette candidature.','❌ Unable to delete this application.','❌ غير قادر على حذف هذا الطلب.');
            $messageType = 'error';
        }
    }

    if (in_array($action, ['submit_application', 'update_application'], true)) {
        $isUpdate = $action === 'update_application';
        $applicationId = $isUpdate ? (int) ($_POST['application_id'] ?? 0) : 0;

        if ($isUpdate && !isset($userApplicationsById[$applicationId])) {
            $message = app_text('❌ Cette candidature est introuvable ou inaccessible.','❌ This application was not found or is inaccessible.','❌ لم يتم العثور على هذا الطلب أو غير متاح.');
            $messageType = 'error';
            $isUpdate = false;
        }

        if (!$isUpdate || isset($userApplicationsById[$applicationId])) {
            try {
                $fieldErrors = validateApplicationForm($_POST, $_FILES, $offerController, !$isUpdate);
                $hasValidationErrors = implode('', $fieldErrors) !== '';

                if ($hasValidationErrors) {
                    $message = app_text('Veuillez corriger les erreurs du formulaire avant de continuer.','Please correct the form errors before continuing.','يرجى تصحيح أخطاء النموذج قبل المتابعة.');
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

function getApplicationStatusClass(string $statut): string {
    $normalized = strtolower(trim($statut));

    return match($normalized) {
        'acceptée', 'acceptee', 'accepted' => 'application-status-success',
        'refusée', 'refusee', 'rejected' => 'application-status-danger',
        default => 'application-status-waiting',
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
    <span class="section-badge"><?php echo app_text('Offres Spéciales', 'Special Offers', 'عروض خاصة'); ?></span>
    <h1 class="page-title"><?php echo app_text('Découvrez nos meilleures offres', 'Discover our top offers', 'اكتشف أفضل عروضنا'); ?></h1>
    <p class="page-intro">
        <?php echo app_text('Des offres exclusives et des réductions exceptionnelles sur les services de nos meilleurs prestataires.', 'Exclusive offers and special discounts from top providers.', 'عروض حصرية وخصومات مميزة من أفضل مقدمي الخدمات.'); ?>
    </p>
</section>

<script>

document.addEventListener('DOMContentLoaded', function() {
    var toggle = document.getElementById('offreNotifToggle');
    var dropdown = document.getElementById('offreNotifDropdown');
    if (!toggle || !dropdown) return;

    // Move dropdown to body to avoid clipping by parent containers
    function ensureAppended() {
        if (dropdown.parentElement !== document.body) {
            document.body.appendChild(dropdown);
        }
        dropdown.style.position = 'absolute';
        dropdown.style.zIndex = 99999;
        dropdown.style.left = '-9999px';
        dropdown.style.top = '-9999px';
    }

    function positionDropdown() {
        var rect = toggle.getBoundingClientRect();
        // ensure dropdown is visible to measure
        dropdown.style.visibility = 'hidden';
        dropdown.removeAttribute('hidden');
        // allow browser to compute sizes
        var ddRect = dropdown.getBoundingClientRect();
        var left = Math.min(window.innerWidth - ddRect.width - 12, Math.max(8, rect.left + (rect.width/2) - (ddRect.width/2)));
        var top = rect.bottom + 8 + window.scrollY;
        dropdown.style.left = (left + window.scrollX) + 'px';
        dropdown.style.top = top + 'px';
        dropdown.style.visibility = '';
    }

    ensureAppended();

    toggle.addEventListener('click', function(e){
        e.preventDefault();
        var isOpen = !dropdown.hasAttribute('hidden');
        if (isOpen) {
            dropdown.setAttribute('hidden','');
            toggle.setAttribute('aria-expanded','false');
        } else {
            positionDropdown();
            toggle.setAttribute('aria-expanded','true');
        }
    });

    // Close when clicking outside
    document.addEventListener('click', function(e){
        if (e.target === toggle || toggle.contains(e.target)) return;
        if (dropdown.contains(e.target)) return;
        if (!dropdown.hasAttribute('hidden')) {
            dropdown.setAttribute('hidden','');
            toggle.setAttribute('aria-expanded','false');
        }
    });

    // Reposition on resize/scroll
    window.addEventListener('resize', function(){ if (!dropdown.hasAttribute('hidden')) positionDropdown(); });
    window.addEventListener('scroll', function(){ if (!dropdown.hasAttribute('hidden')) positionDropdown(); });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.lang-switch-dropdown').forEach(function(wrapper) {
        var toggle = wrapper.querySelector('.lang-switch-toggle');
        var menu = wrapper.querySelector('.lang-switch-menu');
        if (!toggle || !menu) return;

        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('.lang-switch-menu').forEach(function(other) {
                if (other !== menu) other.setAttribute('hidden', '');
            });
            menu.toggleAttribute('hidden');
            toggle.setAttribute('aria-expanded', menu.hasAttribute('hidden') ? 'false' : 'true');
        });
    });

    document.addEventListener('click', function(e) {
        document.querySelectorAll('.lang-switch-dropdown').forEach(function(wrapper) {
            var toggle = wrapper.querySelector('.lang-switch-toggle');
            var menu = wrapper.querySelector('.lang-switch-menu');
            if (!toggle || !menu) return;
            if (wrapper.contains(e.target)) return;
            menu.setAttribute('hidden', '');
            toggle.setAttribute('aria-expanded', 'false');
        });
    });
});
</script>

<section class="action-bar reveal" style="position:relative; z-index:20; overflow:visible;">
    <form class="search-box" id="offreToolbarForm" method="GET" action="index.php">
        <input type="hidden" name="page" value="offre">
        <input type="search" id="offreSearchInput" name="q" value="<?php echo htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo app_text('Rechercher une offre...','Search offers...','ابحث عن عرض...'); ?>" autocomplete="off" aria-label="<?php echo app_text('Rechercher une offre','Search an offer','ابحث عن عرض'); ?>">
        <select id="offreServiceFilter" name="type" aria-label="<?php echo app_text('Filtrer par service','Filter by service','تصفية حسب الخدمة'); ?>">
            <option value=""><?php echo app_text('Tous les services','All services','كل الخدمات'); ?></option>
            <?php foreach ($typeServiceOptionsFront as $svc): ?>
                <option value="<?php echo htmlspecialchars($svc, ENT_QUOTES, 'UTF-8'); ?>" <?php echo strcasecmp($selectedServiceFront, $svc) === 0 ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($svc, ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="sort" id="offerSortSelect">
            <option value="date_desc" <?php echo $sortOption === 'date_desc' ? 'selected' : ''; ?>><?php echo app_text('Plus Récentes','Most recent','الأحدث'); ?></option>
            <option value="date_asc" <?php echo $sortOption === 'date_asc' ? 'selected' : ''; ?>><?php echo app_text('Plus Anciennes','Oldest','الأقدم'); ?></option>
            <option value="titre_asc" <?php echo $sortOption === 'titre_asc' ? 'selected' : ''; ?>><?php echo app_text('Titre: A à Z','Title: A to Z','العنوان: أ إلى ي'); ?></option>
            <option value="titre_desc" <?php echo $sortOption === 'titre_desc' ? 'selected' : ''; ?>><?php echo app_text('Titre: Z à A','Title: Z to A','العنوان: ي إلى أ'); ?></option>
            <option value="prix_asc" <?php echo $sortOption === 'prix_asc' ? 'selected' : ''; ?>><?php echo app_text('Prix: Croissant','Price: Low to High','السعر: من الأقل للأعلى'); ?></option>
            <option value="prix_desc" <?php echo $sortOption === 'prix_desc' ? 'selected' : ''; ?>><?php echo app_text('Prix: Décroissant','Price: High to Low','السعر: من الأعلى للأقل'); ?></option>
            <option value="type_asc" <?php echo $sortOption === 'type_asc' ? 'selected' : ''; ?>><?php echo app_text('Type: A à Z','Type: A to Z','النوع: أ إلى ي'); ?></option>
        </select>
    </form>

    <?php
    $notifications = $_SESSION['offer_notifications'] ?? [];
    $unreadCount = 0;
    if (is_array($notifications)) {
        foreach ($notifications as $n) {
            if (!empty($n['read']) && $n['read']) continue;
            $unreadCount++;
        }
    }
    ?>

    <div class="icon-actions">
        <div class="lang-switch lang-switch-dropdown" style="display:inline-block; margin-right:12px; position:relative; z-index:40;">
            <button type="button" class="ghost-btn lang-switch-toggle" aria-haspopup="true" aria-expanded="false" aria-label="<?php echo app_text('Choisir la langue','Choose language','اختر اللغة'); ?>">🌐</button>
            <div class="lang-switch-menu" hidden style="position:absolute; top:calc(100% + 10px); right:0; min-width:132px; background:#ffffff; border:1px solid rgba(20,39,56,.12); border-radius:16px; box-shadow:0 16px 30px rgba(20,39,56,.16); padding:8px; z-index:9999; backdrop-filter: blur(8px);">
                <a href="<?php echo app_lang_url('fr'); ?>" style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:10px 12px; border-radius:10px; color:#0b2545; font-weight:700; text-decoration:none; transition:background .2s ease;">FR <span style="opacity:.55; font-size:12px;">FR</span></a>
                <a href="<?php echo app_lang_url('en'); ?>" style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:10px 12px; border-radius:10px; color:#0b2545; font-weight:700; text-decoration:none; transition:background .2s ease;">EN <span style="opacity:.55; font-size:12px;">EN</span></a>
                <a href="<?php echo app_lang_url('ar'); ?>" style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:10px 12px; border-radius:10px; color:#0b2545; font-weight:700; text-decoration:none; transition:background .2s ease;">AR <span style="opacity:.55; font-size:12px;">AR</span></a>
            </div>
        </div>
        <div class="notif-wrap" style="margin-right:12px;">
            <button id="offreNotifToggle" class="ghost-btn notif-btn" type="button" aria-haspopup="true" aria-expanded="false">🔔<?php if ($unreadCount>0): ?><span class="notif-badge"><?php echo (int)$unreadCount; ?></span><?php endif; ?></button>
            <div class="notif-dropdown" id="offreNotifDropdown" hidden>
                <div class="notif-header"><?php echo app_text('Notifications','Notifications','الإشعارات'); ?></div>
                <ul class="notif-list">
                    <?php if (empty($notifications)): ?>
                        <li class="notif-empty"><?php echo app_text('Aucune notification','No notifications','لا توجد إشعارات'); ?></li>
                    <?php else: ?>
                        <?php foreach ($notifications as $note): ?>
                            <?php
                                $nid = htmlspecialchars((string)($note['id'] ?? ''), ENT_QUOTES, 'UTF-8');
                                $offerId = htmlspecialchars((string)($note['offer_id'] ?? ''), ENT_QUOTES, 'UTF-8');
                                $link = 'index.php?page=offre&mark_offer_notification=' . rawurlencode($nid);
                                if ($offerId !== '') {
                                    $link .= '&offer_id=' . rawurlencode($offerId);
                                }
                                $headlineRaw = $note['headline'] ?? ($note['type'] ?? 'Notification');
                                if (is_array($headlineRaw) && function_exists('app_text')) {
                                    $headlineText = app_text($headlineRaw['fr'] ?? '', $headlineRaw['en'] ?? '', $headlineRaw['ar'] ?? null);
                                } else {
                                    $headlineText = (string) $headlineRaw;
                                }
                                $headline = htmlspecialchars($headlineText, ENT_QUOTES, 'UTF-8');
                                $title = htmlspecialchars((string)($note['message'] ?? ''), ENT_QUOTES, 'UTF-8');
                                $time = '';
                                if (!empty($note['time'])) {
                                    try { $time = (new DateTimeImmutable($note['time']))->format('d/m/Y H:i'); } catch (Exception $e) { $time = htmlspecialchars((string)$note['time'], ENT_QUOTES, 'UTF-8'); }
                                }
                                $details = $note['details'] ?? [];
                            ?>
                            <li class="notif-item<?php echo empty($note['read']) ? ' is-unread' : ''; ?>">
                                <a class="notif-link" href="<?php echo $link; ?>">
                                    <div class="notif-title"><?php echo $headline; ?></div>
                                    <div style="font-weight:700; color:var(--navy); margin-top:4px;"><?php echo $title; ?></div>
                                    <?php if (!empty($details) && is_array($details)): ?>
                                        <div style="margin-top:6px; font-size:0.92rem; color:var(--muted);">
                                            <?php if (!empty($details['type_service'])): ?><?php echo app_text('Type:','Type:','النوع:'); ?> <?php echo htmlspecialchars($details['type_service'], ENT_QUOTES, 'UTF-8'); ?> &middot; <?php endif; ?>
                                            <?php if (!empty($details['localisation'])): ?><?php echo app_text('Lieu:','Location:','الموقع:'); ?> <?php echo htmlspecialchars($details['localisation'], ENT_QUOTES, 'UTF-8'); ?> &middot; <?php endif; ?>
                                            <?php if (!empty($details['prix'])): ?><?php echo app_text('Prix:','Price:','السعر:'); ?> <?php echo htmlspecialchars($details['prix'], ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($note['changes']) && is_array($note['changes'])): ?>
                                        <div style="margin-top:8px; font-size:0.9rem; color:var(--muted);">
                                            <strong><?php echo app_text('Changements:','Changes:','التغييرات:'); ?></strong>
                                            <ul style="margin:6px 0 0 18px;padding:0;">
                                            <?php foreach ($note['changes'] as $field => $chg): ?>
                                                <li><?php echo htmlspecialchars($field, ENT_QUOTES, 'UTF-8'); ?>: <em><?php echo htmlspecialchars((string)($chg['from'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></em> → <em><?php echo htmlspecialchars((string)($chg['to'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></em></li>
                                            <?php endforeach; ?>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                    <div class="notif-time muted"><?php echo htmlspecialchars($time, ENT_QUOTES, 'UTF-8'); ?></div>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>

        <a class="solid-btn" href="#mes-candidatures">📋 <?php echo app_text('Mes Candidatures','My applications','طلباتي'); ?></a>
    </div>
</section>

<section class="admin-stats reveal" id="offreStatsBar">
    <article class="admin-stat">
        <strong id="offreStatCount"><?php echo htmlspecialchars((string) count($sortedOffersForList), ENT_QUOTES, 'UTF-8'); ?></strong>
        <span id="offreStatCountLabel" data-label-all="<?php echo htmlspecialchars(app_text('Offres Actives','Active Offers','العروض النشطة'), ENT_QUOTES, 'UTF-8'); ?>" data-label-results="<?php echo htmlspecialchars(app_text('Résultats','Results','النتائج'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(app_text('Offres Actives','Active Offers','العروض النشطة'), ENT_QUOTES, 'UTF-8'); ?></span>
    </article>
    <article class="admin-stat">
        <strong id="offreStatLocalized"><?php echo htmlspecialchars((string) count(array_filter($sortedOffersForList, static fn ($o) => !empty($o['localisation']))), ENT_QUOTES, 'UTF-8'); ?></strong>
        <span><?php echo app_text('Offres Localisées','Localized Offers','العروض المحلية'); ?></span>
    </article>
    <article class="admin-stat">
        <strong id="offreStatTypes"><?php echo htmlspecialchars((string) count(array_unique(array_column($sortedOffersForList, 'type_service'))), ENT_QUOTES, 'UTF-8'); ?></strong>
        <span><?php echo app_text('Types de Services','Service Types','أنواع الخدمات'); ?></span>
    </article>
</section>

<section class="module-split reveal">
    <div class="offers-column" id="offresOffersColumn">
        <?php if (empty($activeOffers)): ?>
            <article class="card offers-empty-state">
                <h3><?php echo app_text('Aucune offre disponible pour le moment','No offers available at the moment','لا توجد عروض متاحة حالياً'); ?></h3>
                <p><?php echo app_text('Revenez bientôt pour découvrir de nouvelles offres exclusives !','Check back soon for new exclusive offers!','عد لاحقاً لاكتشاف عروض حصرية جديدة!'); ?></p>
            </article>
        <?php else: ?>
            <article class="card offers-empty-state js-offres-filter-empty" id="offresFilterEmpty" hidden>
                <h3><?php echo app_text('Aucune offre ne correspond à votre recherche','No offers match your search','لا توجد عروض تطابق بحثك'); ?></h3>
                <p><?php echo app_text('Essayez un autre mot-clé ou un autre service.','Try another keyword or another service.','جرّب كلمة مفتاحية أخرى أو خدمة أخرى.'); ?></p>
            </article>
            <?php foreach ($sortedOffersForList as $offer): ?>
                <?php
                    $searchBlob = (string) ($offer['titre'] ?? '');
                ?>
                <article class="card offer-card" data-offer-searchable="1" data-offer-service="<?php echo htmlspecialchars((string) ($offer['type_service'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-search-text="<?php echo htmlspecialchars($searchBlob, ENT_QUOTES, 'UTF-8'); ?>" data-offer-has-loc="<?php echo !empty($offer['localisation']) ? '1' : '0'; ?>">
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
                            <strong class="offer-glance-label"><?php echo app_text('Localisation','Location','الموقع'); ?></strong>
                            <p class="offer-glance-value">
                                <?php echo htmlspecialchars($offer['localisation'] ?: 'Non spécifiée', ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        </div>
                        <div>
                            <strong class="offer-glance-label"><?php echo app_text("Valide jusqu\'au",'Valid until','صالحة حتى'); ?></strong>
                            <p class="offer-glance-value">
                                <?php echo htmlspecialchars(formatOfferDate($offer['date_expiration']), ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        </div>
                    </div>

                    <div class="meta-row offer-meta-row">
                        <span>📅 <?php echo app_text('Publié','Published','نشر'); ?> : <?php echo htmlspecialchars(formatOfferDate($offer['date_publication']), ENT_QUOTES, 'UTF-8'); ?></span>
                        <span>
                            💰 <?php echo app_text('Prix','Price','السعر'); ?> :
                            <?php echo htmlspecialchars(isset($offer['prix']) && $offer['prix'] !== null ? number_format((float) $offer['prix'], 2, '.', ' ') . ' TND' : 'N/A', ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </div>

                    <div class="icon-actions offer-card-actions">
                        <a class="solid-btn postuler-btn" href="#postuler-offre" data-offer-id="<?php echo htmlspecialchars($offer['id_offre'], ENT_QUOTES, 'UTF-8'); ?>" data-offer-title="<?php echo htmlspecialchars($offer['titre'], ENT_QUOTES, 'UTF-8'); ?>">✓ <?php echo app_text('Postuler','Apply','التقديم'); ?></a>
                        <button type="button" class="small-btn toggle-details-btn" aria-expanded="false" data-text-show="<?php echo app_text('Voir détails','View details','عرض التفاصيل'); ?>" data-text-hide="<?php echo app_text('Masquer détails','Hide details','إخفاء التفاصيل'); ?>"><?php echo app_text('Voir détails','View details','عرض التفاصيل'); ?></button>
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
                        <p class="offer-description-title"><strong><?php echo app_text('Description complète :','Full description:','الوصف الكامل:'); ?></strong></p>
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
                <span class="section-badge"><?php echo $editingApplicationId > 0 ? app_text('✏️ Modifier candidature','✏️ Edit application','✏️ تعديل الطلب') : app_text('📨 Postuler','📨 Apply','📨 التقديم'); ?></span>

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
                        <?php echo app_text('Offre sélectionnée :','Selected offer:','العرض المحدد:'); ?> <span><?php echo htmlspecialchars($selectedOfferTitle, ENT_QUOTES, 'UTF-8'); ?></span>
                    </p>
                    <div class="form-grid application-grid">
                        <input type="text" name="experience" id="experienceField" placeholder="<?php echo app_text('Années d\'experience','Years of experience','سنوات الخبرة'); ?>" value="<?php echo htmlspecialchars($formData['experience'], ENT_QUOTES, 'UTF-8'); ?>" aria-invalid="<?php echo $fieldErrors['experience'] !== '' ? 'true' : 'false'; ?>">
                            <input type="text" name="competences" id="competencesField" placeholder="<?php echo app_text('Vos competences cles','Your key skills','مهاراتك الرئيسية'); ?>" value="<?php echo htmlspecialchars($formData['competences'], ENT_QUOTES, 'UTF-8'); ?>" aria-invalid="<?php echo $fieldErrors['competences'] !== '' ? 'true' : 'false'; ?>">
                        <small class="field-error" data-error-for="experience"><?php echo htmlspecialchars($fieldErrors['experience'], ENT_QUOTES, 'UTF-8'); ?></small>
                        <small class="field-error" data-error-for="competences"><?php echo htmlspecialchars($fieldErrors['competences'], ENT_QUOTES, 'UTF-8'); ?></small>

                        <input type="file" name="cv" id="cvField" accept=".pdf,.doc,.docx" aria-invalid="<?php echo $fieldErrors['cv'] !== '' ? 'true' : 'false'; ?>">
                        <?php if ($editingApplicationId > 0 && $existingCvPath !== ''): ?>
                            <small class="cv-helper full-row">CV actuel: <?php echo htmlspecialchars(basename($existingCvPath), ENT_QUOTES, 'UTF-8'); ?>. Laissez vide pour le conserver.</small>
                        <?php endif; ?>
                        <small class="field-error full-row" data-error-for="cv"><?php echo htmlspecialchars($fieldErrors['cv'], ENT_QUOTES, 'UTF-8'); ?></small>

                        <textarea name="message" id="messageField" placeholder="<?php echo app_text('Lettre de motivation...','Cover letter...','رسالة التحفيز...'); ?>" rows="4" aria-invalid="<?php echo $fieldErrors['message'] !== '' ? 'true' : 'false'; ?>"><?php echo htmlspecialchars($formData['message'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                        <small class="field-error full-row" data-error-for="message"><?php echo htmlspecialchars($fieldErrors['message'], ENT_QUOTES, 'UTF-8'); ?></small>
                    </div>

                    <div class="icon-actions apply-actions">
                        <button type="submit" class="solid-btn full-width-btn"><?php echo $editingApplicationId > 0 ? app_text('Enregistrer les modifications','Save changes','حفظ التعديلات') : app_text('Envoyer ma candidature','Send my application','إرسال الطلب'); ?></button>
                        <button type="reset" class="outline-btn full-width-btn"><?php echo app_text('Réinitialiser','Reset','إعادة تعيين'); ?></button>
                        <?php if ($editingApplicationId > 0): ?>
                            <a href="index.php?page=offre#postuler-offre" class="outline-btn full-width-btn"><?php echo app_text('Annuler la modification','Cancel edit','إلغاء التعديل'); ?></a>
                        <?php endif; ?>
                    </div>
                </form>
            </article>
        <?php else: ?>
            <article class="panel apply-panel" id="postuler-offre">
                <span class="section-badge"><?php echo app_text('📨 Postuler','📨 Apply','📨 التقديم'); ?></span>
                <div class="application-empty-state">
                    <strong><?php echo app_text('Aucune offre active disponible','No active offers available','لا توجد عروض نشطة متاحة'); ?></strong>
                    <p><?php echo app_text('Vous ne pouvez pas postuler pour le moment car il n\'y a aucune offre active dans le catalogue.','You cannot apply at the moment as there are no active offers in the catalog.','لا يمكنك التقديم في الوقت الحالي لأنه لا توجد عروض نشطة في الكتالوج.'); ?></p>
                </div>
            </article>
        <?php endif; ?>

        <div class="panel benefits-panel">
            <span class="section-badge">✨ <?php echo app_text('Avantages','Benefits','المميزات'); ?></span>
            <div class="feature-list">
                <div class="feature-item">✓ <?php echo app_text('Offres exclusives','Exclusive offers','عروض حصرية'); ?></div>
                <div class="feature-item">✓ <?php echo app_text('Prestataires vérifiés','Verified providers','مقدمو خدمات موثوقون'); ?></div>
                <div class="feature-item">✓ <?php echo app_text('Paiement sécurisé','Secure payment','دفع آمن'); ?></div>
                <div class="feature-item">✓ <?php echo app_text('Support client 24/7','24/7 Customer support','دعم العملاء 24/7'); ?></div>
                <div class="feature-item">✓ <?php echo app_text('Garantie satisfaction','Satisfaction guarantee','ضمان الرضا'); ?></div>
            </div>
        </div>
    </div>
</section>

<section class="admin-panel reveal my-applications-panel" id="mes-candidatures">
    <span class="section-badge">📋 <?php echo app_text('Mes Candidatures','My applications','طلباتي'); ?></span>

    <?php if (empty($userApplications)): ?>
        <div class="application-empty-state" style="margin-top: 14px;">
            <strong><?php echo app_text('Aucune candidature enregistrée','No applications recorded','لا توجد طلبات مسجلة'); ?></strong>
            <p><?php echo app_text("Commencez par postuler à une offre pour voir vos candidatures ici.",'Start by applying to an offer to see your applications here.','ابدأ بالتقديم على عرض لرؤية طلباتك هنا.'); ?></p>
        </div>
    <?php else: ?>
        <div class="application-card-list" style="margin-top: 14px;">
            <?php foreach ($userApplications as $application): ?>
                <?php
                    $applicationStatus = (string) ($application['statut'] ?? 'en attente');
                    $applicationStatusLabel = ucfirst($applicationStatus);
                ?>
                <article class="application-card">
                    <div class="application-card-head">
                        <div>
                            <span class="application-chip"><?php echo app_text('Candidature','Application','الطلب'); ?></span>
                            <h3 class="application-title"><?php echo htmlspecialchars((string) ($application['offer_titre'] ?? app_text('Offre supprimée','Offer deleted','تم حذف العرض')), ENT_QUOTES, 'UTF-8'); ?></h3>
                        </div>
                        <span class="application-status <?php echo htmlspecialchars(getApplicationStatusClass($applicationStatus), ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($applicationStatusLabel, ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </div>

                    <div class="application-card-grid">
                        <div class="application-info-block">
                            <span><?php echo app_text('Date','Date','التاريخ'); ?></span>
                            <strong><?php echo htmlspecialchars(formatOfferDate($application['created_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?></strong>
                        </div>
                        <div class="application-info-block">
                            <span><?php echo app_text('Expérience','Experience','الخبرة'); ?></span>
                            <strong><?php echo htmlspecialchars((string) ($application['experience'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></strong>
                        </div>
                        <div class="application-info-block application-info-full">
                            <span><?php echo app_text('Compétences','Skills','المهارات'); ?></span>
                            <strong><?php echo htmlspecialchars((string) ($application['competences'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></strong>
                        </div>
                        <?php if (!empty($application['message'])): ?>
                            <div class="application-info-block application-info-full" style="margin-top:12px;">
                                <span><?php echo app_text('Lettre de motivation','Cover letter','رسالة التحفيز'); ?></span>
                                <div style="background:var(--card); padding:12px; border-radius:8px; margin-top:6px; color:var(--text);">
                                    <?php echo nl2br(htmlspecialchars((string) $application['message'], ENT_QUOTES, 'UTF-8')); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="application-card-actions">
                        <a class="small-btn application-edit-btn" href="index.php?page=offre&edit_application=<?php echo htmlspecialchars((string) ($application['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>#postuler-offre"><?php echo app_text('Modifier','Edit','تعديل'); ?></a>
                        <form method="POST" class="application-delete-form">
                            <input type="hidden" name="action" value="delete_application">
                            <input type="hidden" name="application_id" value="<?php echo htmlspecialchars((string) ($application['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit" class="danger-btn application-delete-btn" onclick="return confirm('<?php echo addslashes(app_text("Supprimer cette candidature ?","Delete this application?","حذف هذا الطلب؟")); ?>');"><?php echo app_text('Supprimer','Delete','حذف'); ?></button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
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
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: center;
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

.application-card-list {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 16px;
}

.application-card {
    position: relative;
    overflow: hidden;
    padding: 22px;
    border-radius: 24px;
    background: rgba(255,255,255,0.96);
    border: 1px solid rgba(20,39,56,0.10);
    border-left: 4px solid #4CAF50;
    box-shadow: 0 14px 32px rgba(20,39,56,0.06);
}

.application-card-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 14px;
    margin-bottom: 16px;
}

.application-chip {
    display: inline-flex;
    align-items: center;
    padding: 8px 14px;
    border-radius: 999px;
    background: rgba(238,88,40,0.10);
    color: #EE5828;
    font-weight: 800;
    font-size: 0.95rem;
}

.application-title {
    margin: 12px 0 0;
    font-size: 1.45rem;
    line-height: 1.2;
}

.application-status {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 8px 14px;
    border-radius: 999px;
    font-size: 0.92rem;
    font-weight: 800;
    white-space: nowrap;
}

.application-status-waiting {
    background: rgba(238,88,40,0.12);
    color: #EE5828;
}

.application-status-success {
    background: rgba(76,175,80,0.14);
    color: #2f8f35;
}

.application-status-danger {
    background: rgba(244,67,54,0.12);
    color: #c92f24;
}

.application-card-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
    padding: 16px;
    border-radius: 18px;
    background: rgba(20,39,56,0.04);
}

.application-info-block {
    display: grid;
    grid-template-columns: 140px 1fr;
    align-items: center;
    gap: 8px 12px;
}

.application-info-block span {
    font-size: 0.88rem;
    font-weight: 700;
    color: #7b8796;
    text-align: left;
}

.application-info-block strong {
    font-size: 1rem;
    color: var(--text);
    font-weight: 700;
    word-break: break-word;
}

/* Full-width info blocks (message) keep vertical layout */
.application-info-full {
    display: block;
}

@media (max-width: 720px) {
    .application-card-list {
        grid-template-columns: 1fr;
    }

    .application-info-block {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
}

.application-info-full {
    grid-column: 1 / -1;
}

.application-card-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 16px;
}

.application-delete-form {
    margin: 0;
}

.application-card .small-btn,
.application-card .danger-btn {
    min-width: 132px;
}

.application-card .danger-btn {
    background: linear-gradient(135deg, #EE5828, #D84A1E);
    color: #fff;
    border-color: transparent;
}

.application-card .danger-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 14px 24px rgba(238,88,40,0.22);
}

body.dark .application-card {
    background: rgba(20,39,56,0.88);
    border-color: rgba(255,255,255,0.08);
}

body.dark .application-card-grid {
    background: rgba(255,255,255,0.05);
}

body.dark .application-info-block span {
    color: #b7c2cf;
}

body.dark .application-status-waiting {
    background: rgba(238,88,40,0.18);
    color: #ff9d7d;
}

body.dark .application-status-success {
    background: rgba(76,175,80,0.18);
    color: #9bd79e;
}

body.dark .application-status-danger {
    background: rgba(244,67,54,0.18);
    color: #ff9b93;
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

<script>
(function() {
    // Direct function to toggle details for a button
    function toggleDetails(btn) {
        var card = btn.closest('.offer-card');
        if (!card) return false;
        var details = card.querySelector('.offer-details');
        if (!details) return false;
        
        var isExpanded = btn.getAttribute('aria-expanded') === 'true';
        var textShow = btn.getAttribute('data-text-show');
        var textHide = btn.getAttribute('data-text-hide');
        
        if (isExpanded) {
            details.style.display = 'none';
            btn.setAttribute('aria-expanded', 'false');
            btn.textContent = textShow;
        } else {
            details.style.display = 'block';
            btn.setAttribute('aria-expanded', 'true');
            btn.textContent = textHide;
        }
        return true;
    }
    
    // Attach listeners to toggle details buttons
    var detailsBtns = document.querySelectorAll('.toggle-details-btn');
    for (var i = 0; i < detailsBtns.length; i++) {
        (function(btn) {
            btn.addEventListener('click', function(e) {
                console.log('>>> Toggle button clicked! <<<');
                e.preventDefault();
                e.stopPropagation();
                console.log('About to toggle...');
                toggleDetails(btn);
                console.log('Toggle complete');
            }, false);
        })(detailsBtns[i]);
    }
    
    // Client-side search + service filter (no reload); sort still submits the form
    var offreToolbarForm = document.getElementById('offreToolbarForm');
    var offreSearchInput = document.getElementById('offreSearchInput');
    var offreServiceFilter = document.getElementById('offreServiceFilter');
    var offresColumn = document.getElementById('offresOffersColumn');
    var offresFilterEmpty = document.getElementById('offresFilterEmpty');

    function normalizeOfferSearchValue(value) {
        return String(value || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }

    function syncOffreToolbarUrl(searchValue, serviceValue) {
        var params = new URLSearchParams(window.location.search);
        params.set('page', 'offre');
        var sortSelectEl = document.getElementById('offerSortSelect');
        if (sortSelectEl && sortSelectEl.value) {
            params.set('sort', sortSelectEl.value);
        }
        if (searchValue) {
            params.set('q', searchValue);
        } else {
            params.delete('q');
        }
        if (serviceValue) {
            params.set('type', serviceValue);
        } else {
            params.delete('type');
        }
        window.history.replaceState({}, document.title, window.location.pathname + '?' + params.toString() + window.location.hash);
    }

    function updateOffreStatsFromVisible(cards) {
        var n = cards.length;
        var countEl = document.getElementById('offreStatCount');
        var labelEl = document.getElementById('offreStatCountLabel');
        var locEl = document.getElementById('offreStatLocalized');
        var typesEl = document.getElementById('offreStatTypes');
        if (countEl) countEl.textContent = String(n);

        var searchVal = offreSearchInput ? offreSearchInput.value : '';
        var serviceVal = offreServiceFilter ? offreServiceFilter.value : '';
        var filtersActive = normalizeOfferSearchValue(searchVal) !== '' || (serviceVal && String(serviceVal).trim() !== '');
        if (labelEl) {
            labelEl.textContent = filtersActive
                ? (labelEl.getAttribute('data-label-results') || '')
                : (labelEl.getAttribute('data-label-all') || '');
        }

        var loc = 0;
        var types = {};
        for (var i = 0; i < cards.length; i++) {
            var c = cards[i];
            if (c.getAttribute('data-offer-has-loc') === '1') loc++;
            var t = (c.getAttribute('data-offer-service') || '').trim();
            if (t) types[t] = true;
        }
        if (locEl) locEl.textContent = String(loc);
        if (typesEl) typesEl.textContent = String(Object.keys(types).length);
    }

    function applyOffreListFilters() {
        if (!offresColumn) return;

        var searchValue = offreSearchInput ? offreSearchInput.value : '';
        var serviceValue = offreServiceFilter ? offreServiceFilter.value : '';
        var query = normalizeOfferSearchValue(searchValue);
        var serviceQuery = normalizeOfferSearchValue(serviceValue);

        var cards = offresColumn.querySelectorAll('.offer-card[data-offer-searchable="1"]');
        var visible = [];
        cards.forEach(function(card) {
            var hay = normalizeOfferSearchValue(card.getAttribute('data-search-text') || '');
            var svc = normalizeOfferSearchValue(card.getAttribute('data-offer-service') || '');
            var matchesSearch = query === '' || hay.indexOf(query) !== -1;
            var matchesService = serviceQuery === '' || svc === serviceQuery;
            var show = matchesSearch && matchesService;
            card.hidden = !show;
            if (show) visible.push(card);
        });

        if (offresFilterEmpty) {
            offresFilterEmpty.hidden = cards.length === 0 || visible.length > 0;
        }

        syncOffreToolbarUrl(searchValue, serviceValue);
        updateOffreStatsFromVisible(visible);
    }

    if (offreToolbarForm) {
        offreToolbarForm.addEventListener('submit', function(e) {
            e.preventDefault();
        });
    }

    if (offreSearchInput) {
        offreSearchInput.addEventListener('input', applyOffreListFilters);
        offreSearchInput.addEventListener('search', applyOffreListFilters);
    }
    if (offreServiceFilter) {
        offreServiceFilter.addEventListener('change', applyOffreListFilters);
    }

    if (offresColumn && offresColumn.querySelector('.offer-card[data-offer-searchable="1"]')) {
        applyOffreListFilters();
    }

    // Auto-submit sort select
    var sortSelect = document.getElementById('offerSortSelect');
    if (sortSelect && sortSelect.form) {
        sortSelect.addEventListener('change', function() {
            this.form.submit();
        });
    }
    
    // Handle "Postuler" button click
    var postulerBtns = document.querySelectorAll('.postuler-btn');
    for (var j = 0; j < postulerBtns.length; j++) {
        (function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                var offerId = btn.getAttribute('data-offer-id');
                var selectField = document.querySelector('select[name="offer_id"]');
                if (selectField) {
                    selectField.value = offerId;
                    var form = document.querySelector('#postuler-offre');
                    if (form) {
                        form.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    selectField.focus();
                }
            }, false);
        })(postulerBtns[j]);
    }
})();
</script>
