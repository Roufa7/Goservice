<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../view/i18n.php';
require_once __DIR__ . '/../../../model/Offer.php';
require_once __DIR__ . '/../../../model/Candidature.php';
require_once __DIR__ . '/../../../controller/OfferController.php';
require_once __DIR__ . '/../../../controller/CandidatureController.php';
require_once __DIR__ . '/../../../controller/OfferPdfExporter.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

app_set_language_from_request();

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
                    $message = app_text('Statut de candidature mis à jour !', 'Application status updated!', 'تم تحديث حالة الطلب!');
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
                        'message' => app_text('Statut invalide.','Invalid status.','حالة غير صالحة.'),
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
                    $message = app_text('Veuillez corriger les erreurs du formulaire.','Please fix the form errors.','يرجى تصحيح أخطاء النموذج.');
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
                    $createResult = $offerController->createOffer($payload);
                    
                    if ($createResult === 0) {
                        $message = app_text('Une offre avec ce titre existe déjà. Veuillez utiliser un titre différent.','An offer with this title already exists. Please choose a different title.','هناك عرض بنفس العنوان بالفعل. الرجاء استخدام عنوان مختلف.');
                        $messageType = 'error';
                    } else {
                        $message = app_text('Offre créée avec succès !','Offer created successfully!','تم إنشاء العرض بنجاح!');
                        $messageType = 'success';
                        $formData = [
                            'titre' => '',
                            'type_service' => '',
                            'localisation' => '',
                            'date_expiration' => '',
                            'prix' => '',
                            'description' => '',
                        ];
                    }
                } else {
                    $updateResult = $offerController->updateOffer(intval($_POST['offer_id']), $payload);
                    
                        if ($updateResult === false) {
                        $message = app_text('Une offre avec ce titre existe déjà. Veuillez utiliser un titre différent.','An offer with this title already exists. Please choose a different title.','هناك عرض بنفس العنوان بالفعل. الرجاء استخدام عنوان مختلف.');
                        $messageType = 'error';
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
                    } else {
                        $isAjax = strtolower((string) ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
                        if ($isAjax) {
                            header('Content-Type: application/json; charset=UTF-8');
                            echo json_encode(['ok' => true, 'message' => app_text('Offre mise à jour avec succès !','Offer updated successfully!','تم تحديث العرض بنجاح!')]);
                            exit;
                        }
                        header('Location: ?page=offers');
                        exit;
                    }
                }
            }
        //DELETE OFFER
        } elseif ($_POST['action'] === 'update' && !empty($_POST['offer_id'])) {
        } elseif ($_POST['action'] === 'delete' && !empty($_POST['offer_id'])) {
            $offerController->deleteOffer(intval($_POST['offer_id']));
            $message = app_text('Offre supprimée !','Offer deleted!','تم حذف العرض!');
            $messageType = 'success';
            $currentOffer = null;
        }
    }
}

// Handle marking admin notification as read (remove from session and persisted file)
if (isset($_GET['mark_admin_notification']) && trim((string)($_GET['mark_admin_notification'] ?? '')) !== '') {
    $markId = trim((string) $_GET['mark_admin_notification']);
    $goto = isset($_GET['goto']) ? trim((string) $_GET['goto']) : '';
    $isAjax = (isset($_GET['ajax']) && (string)$_GET['ajax'] === '1') || strtolower((string)($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '')) === 'xmlhttprequest';
    if (session_status() === PHP_SESSION_NONE) { @session_start(); }
    if (isset($_SESSION['admin_notifications']) && is_array($_SESSION['admin_notifications'])) {
        foreach ($_SESSION['admin_notifications'] as $k => $n) {
            if (isset($n['id']) && $n['id'] === $markId) {
                unset($_SESSION['admin_notifications'][$k]);
            }
        }
        $_SESSION['admin_notifications'] = array_values($_SESSION['admin_notifications']);
    }

    // also remove from persisted file
    $notifFile = __DIR__ . '/../../../storage/admin_notifications.json';
    if (is_file($notifFile)) {
        $content = @file_get_contents($notifFile);
        $arr = $content !== false ? json_decode($content, true) : [];
        if (is_array($arr)) {
            $filtered = array_values(array_filter($arr, function($it) use ($markId) { return !isset($it['id']) || $it['id'] !== $markId; }));
            if (count($filtered) !== count($arr)) {
                @file_put_contents($notifFile, json_encode($filtered, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
            }
        }
    }

    if ($isAjax) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode(['ok' => true]);
        exit;
    }

    if ($goto !== '') {
        header('Location: ' . $goto);
    } else {
        header('Location: ?page=offers');
    }
    exit;
}

// Charger une offre si "Voir" est cliqué
if (isset($_GET['edit'])) {
    $currentOffer = $offerController->getOffer(intval($_GET['edit']));
}

$offers = $offerController->listOffers(); // tgoutes les offres
$searchTerm = cleanInput((string) ($_GET['q'] ?? ''));
$sortOption = cleanInput((string) ($_GET['sort'] ?? 'date_publication_desc'));
$sortLabels = [
    'date_publication_desc' => 'Publication: plus recentes',
    'date_publication_asc' => 'Publication: plus anciennes',
    'date_expiration_desc' => 'Expiration: plus recentes',
    'date_expiration_asc' => 'Expiration: plus anciennes',
    'titre_asc' => 'Titre: A a Z',
    'titre_desc' => 'Titre: Z a A',
    'type_asc' => 'Type: A a Z',
    'type_desc' => 'Type: Z a A',
    'localisation_asc' => 'Localisation: A a Z',
    'localisation_desc' => 'Localisation: Z a A',
    'prix_asc' => 'Prix: croissant',
    'prix_desc' => 'Prix: décroissant',
];
$visibleOffers = sortOffers(filterOffersBySearch($offers, $searchTerm), $sortOption);
$exportOffers = sortOffers(filterOffersBySearch($offers, $searchTerm), 'date_publication_desc');
$offerPrices = array_values(array_filter(array_map(static function (array $offer): ?float {
    return isset($offer['prix']) && $offer['prix'] !== '' ? (float) $offer['prix'] : null;
}, $offers), static fn (?float $price): bool => $price !== null));
$averageOfferPrice = !empty($offerPrices) ? array_sum($offerPrices) / count($offerPrices) : null;
$latestOffer = !empty($offers) ? array_reduce($offers, static function (?array $carry, array $offer): ?array {
    if ($carry === null) {
        return $offer;
    }

    $currentDate = !empty($offer['date_publication']) ? strtotime((string) $offer['date_publication']) : 0;
    $carryDate = !empty($carry['date_publication']) ? strtotime((string) $carry['date_publication']) : 0;

    return $currentDate >= $carryDate ? $offer : $carry;
}) : null;
$offerTypeStats = [];
foreach ($offers as $offer) {
    $typeKey = trim((string) ($offer['type_service'] ?? 'autre')) ?: 'autre';
    $offerTypeStats[$typeKey] = ($offerTypeStats[$typeKey] ?? 0) + 1;
}
arsort($offerTypeStats);

// Admin notifications (render only on offers page — persisted across sessions)
$adminNotifs = [];
$adminNotifsCount = 0;
if (session_status() === PHP_SESSION_NONE) { @session_start(); }
// Prefer immediate session notifications (fresh), then merge persisted ones
if (isset($_SESSION['admin_notifications']) && is_array($_SESSION['admin_notifications'])) {
    $adminNotifs = $_SESSION['admin_notifications'];
}
$notifFile = __DIR__ . '/../../../storage/admin_notifications.json';
if (is_file($notifFile)) {
    $content = @file_get_contents($notifFile);
    if ($content !== false) {
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            // merge file notifications, but avoid duplicates by id
            $existingIds = array_column($adminNotifs, 'id');
            foreach ($decoded as $d) {
                if (!in_array($d['id'] ?? '', $existingIds, true)) {
                    $adminNotifs[] = $d;
                }
            }
        }
    }
}
$adminNotifsCount = is_array($adminNotifs) ? count($adminNotifs) : 0;

// Find offer with most candidatures
$allApplications = $candidatureController->getAllApplications();
$applicationCountPerOffer = [];
foreach ($allApplications as $app) {
    // Try multiple possible field names for offer_id
    $offerId = (int) ($app['offer_id'] ?? $app['offre_id'] ?? $app['id_offre'] ?? 0);
    if ($offerId > 0) {
        $applicationCountPerOffer[$offerId] = ($applicationCountPerOffer[$offerId] ?? 0) + 1;
    }
}
$offerWithMostApps = null;
$mostAppsCount = 0;
if (!empty($applicationCountPerOffer)) {
    $maxCount = max($applicationCountPerOffer);
    $maxOfferId = (int) array_search($maxCount, $applicationCountPerOffer, true);
    if ($maxOfferId > 0) {
        $filtered = array_filter($offers, static fn ($o) => (int) ($o['id_offre'] ?? 0) === $maxOfferId);
        $offerWithMostApps = !empty($filtered) ? reset($filtered) : null;
        $mostAppsCount = $offerWithMostApps ? (int) $applicationCountPerOffer[$maxOfferId] : 0;
    }
}

if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    OfferPdfExporter::download($exportOffers, [
        'generatedAt' => new DateTimeImmutable('now'),
        'title' => 'Gestion des offres',
        'searchLabel' => $searchTerm,
        'sortLabel' => 'Publication: plus recentes',
    ]);
}

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

function offerSortKey(array $offer, string $sort): mixed {
    return match ($sort) {
        'titre_asc', 'titre_desc' => mb_strtolower(trim((string) ($offer['titre'] ?? '')), 'UTF-8'),
        'type_asc', 'type_desc' => mb_strtolower(trim((string) ($offer['type_service'] ?? '')), 'UTF-8'),
        'localisation_asc', 'localisation_desc' => mb_strtolower(trim((string) ($offer['localisation'] ?? '')), 'UTF-8'),
        'date_expiration_asc', 'date_expiration_desc' => !empty($offer['date_expiration']) ? strtotime((string) $offer['date_expiration']) : null,
        'prix_asc', 'prix_desc' => isset($offer['prix']) && $offer['prix'] !== '' ? (float) $offer['prix'] : null,
        default => !empty($offer['date_publication']) ? strtotime((string) $offer['date_publication']) : null,
    };
}

function sortOffers(array $offers, string $sort): array {
    $sort = in_array($sort, [
        'date_publication_asc',
        'date_publication_desc',
        'date_expiration_asc',
        'date_expiration_desc',
        'titre_asc',
        'titre_desc',
        'type_asc',
        'type_desc',
        'localisation_asc',
        'localisation_desc',
        'prix_asc',
        'prix_desc',
    ], true) ? $sort : 'date_publication_desc';

    usort($offers, function (array $left, array $right) use ($sort): int {
        $leftKey = offerSortKey($left, $sort);
        $rightKey = offerSortKey($right, $sort);

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

        return str_ends_with($sort, '_desc') ? -$comparison : $comparison;
    });

    return array_values($offers);
}

function filterOffersBySearch(array $offers, string $searchTerm): array {
    $searchTerm = trim(mb_strtolower($searchTerm, 'UTF-8'));

    if ($searchTerm === '') {
        return array_values($offers);
    }

    return array_values(array_filter($offers, function (array $offer) use ($searchTerm): bool {
        $haystack = mb_strtolower(implode(' ', [
            (string) ($offer['titre'] ?? ''),
            (string) ($offer['type_service'] ?? ''),
            (string) ($offer['localisation'] ?? ''),
            (string) ($offer['description'] ?? ''),
            (string) ($offer['statut'] ?? ''),
        ]), 'UTF-8');

        return strpos($haystack, $searchTerm) !== false;
    }));
}

function buildOfferExportUrl(string $searchTerm, string $sortOption): string {
    $params = [
        'page' => 'offers',
        'export' => 'pdf',
    ];

    if ($searchTerm !== '') {
        $params['q'] = $searchTerm;
    }

    if ($sortOption !== '') {
        $params['sort'] = $sortOption;
    }

    return '?' . http_build_query($params);
}

function formatApplicationStatus(string $status): string {
    return match($status) {
        'acceptee', 'accepted' => app_text('Acceptée','Accepted','مقبول'),
        'refusee', 'rejetee', 'rejected' => app_text('Refusée','Rejected','مرفوض'),
        'en_attente', 'en attente' => app_text('En attente','Pending','قيد الانتظار'),
        default => app_text('En attente','Pending','قيد الانتظار')
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

    <?php
    // Render admin notifications using same markup as front-office notifications
    $unreadAdminCount = 0;
    if (is_array($adminNotifs)) {
        foreach ($adminNotifs as $an) {
            if (empty($an['read']) || !$an['read']) { $unreadAdminCount++; }
        }
    }
    ?>
    
    <!-- Notification dropdown (positioned absolutely via JS) -->
    <div class="notif-dropdown" id="adminNotifDropdown" hidden>
            <div class="notif-header"><?php echo app_text('Notifications administrateur','Admin Notifications','إشعارات المشرف'); ?></div>
            <ul class="notif-list">
                <?php if (empty($adminNotifs)): ?>
                    <li class="notif-empty"><?php echo app_text('Aucune notification','No notifications','لا توجد إشعارات'); ?></li>
                <?php else: ?>
                    <?php foreach ($adminNotifs as $note): ?>
                        <?php
                            $nid = (string)($note['id'] ?? '');
                            $offerId = (string)($note['details']['offer_id'] ?? $note['offer_id'] ?? '');
                            $target = isset($note['link']) ? $note['link'] : ('index.php?page=offer_applications&offer_id=' . ((int)$offerId));
                            $markUrl = '?page=offers&mark_admin_notification=' . rawurlencode($nid);
                            // For deletion notifications we don't want the anchor to navigate — use '#' and provide data-mark-url for AJAX
                            $isDeletion = isset($note['type']) && $note['type'] === 'candidature_supprime';
                            if ($isDeletion) {
                                $link = '#';
                            } else {
                                $link = $markUrl . '&goto=' . rawurlencode($target);
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
                            <a class="notif-link admin-markable" href="<?php echo $link; ?>" data-goto="<?php echo htmlspecialchars($target, ENT_QUOTES, 'UTF-8'); ?>" data-mark-url="<?php echo htmlspecialchars($markUrl, ENT_QUOTES, 'UTF-8'); ?>" data-type="<?php echo htmlspecialchars((string)($note['type'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                <div class="notif-title"><?php echo $headline; ?></div>
                                <div style="font-weight:700; color:var(--navy); margin-top:4px;"><?php echo $title; ?></div>
                                <?php if (!empty($details) && is_array($details)): ?>
                                        <div style="margin-top:6px; font-size:0.92rem; color:var(--muted);">
                                        <?php if (!empty($details['offer_titre'])): ?><?php echo app_text('Offre:','Offer:','العرض:'); ?> <?php echo htmlspecialchars($details['offer_titre'], ENT_QUOTES, 'UTF-8'); ?> &middot; <?php endif; ?>
                                        <?php if (!empty($details['offer_id'])): ?><?php echo app_text('ID:','ID:','المعرّف:'); ?> <?php echo htmlspecialchars((string)$details['offer_id'], ENT_QUOTES, 'UTF-8'); ?><?php endif; ?>
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

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var toggle = document.getElementById('adminNotifToggle');
        var dropdown = document.getElementById('adminNotifDropdown');
        if (!toggle || !dropdown) return;

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
            dropdown.style.visibility = 'hidden';
            dropdown.removeAttribute('hidden');
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

        document.addEventListener('click', function(e){
            if (e.target === toggle || toggle.contains(e.target)) return;
            if (dropdown.contains(e.target)) return;
            if (!dropdown.hasAttribute('hidden')) {
                dropdown.setAttribute('hidden','');
                toggle.setAttribute('aria-expanded','false');
            }
        });

        window.addEventListener('resize', function(){ if (!dropdown.hasAttribute('hidden')) positionDropdown(); });
        window.addEventListener('scroll', function(){ if (!dropdown.hasAttribute('hidden')) positionDropdown(); });
    });
    // Intercept clicks to mark notification as read via AJAX then redirect (or remove only for deletion notifications)
    document.addEventListener('click', function(e){
        var a = e.target.closest && e.target.closest('a.admin-markable');
        if (!a) return;
        e.preventDefault();
        var href = a.getAttribute('href');
        var goto = a.getAttribute('data-goto') || '?page=offers';
        var markUrl = a.getAttribute('data-mark-url') || href;
        var ntype = (a.getAttribute('data-type') || '').toLowerCase();
        if (!markUrl || markUrl === '#') { if (ntype === 'candidature_supprime') { var li = a.closest('li.notif-item'); if (li) li.remove(); } else { window.location = goto; } return; }
        // append ajax=1 to signal JSON response
        var sep = markUrl.indexOf('?') === -1 ? '?' : '&';
        fetch(markUrl + sep + 'ajax=1', { credentials: 'same-origin' }).then(function(resp){
            return resp.json().catch(function(){ return { ok: true }; });
        }).then(function(json){
            if (ntype === 'candidature_supprime') {
                // remove the notification item from DOM and update badge
                var li = a.closest('li.notif-item');
                if (li) li.remove();
                try {
                    var btn = document.getElementById('adminNotifToggle') || document.getElementById('offreNotifToggle');
                    if (btn) {
                        var badge = btn.querySelector('.notif-badge');
                        if (badge) {
                            var count = parseInt(badge.textContent || '0', 10) - 1;
                            if (count > 0) { badge.textContent = String(count); } else { badge.remove(); }
                        }
                    }
                } catch (err) { /* ignore */ }
                return;
            }
            window.location = goto;
        }).catch(function(){
            if (ntype === 'candidature_supprime') {
                var li = a.closest('li.notif-item'); if (li) li.remove();
            } else {
                window.location = goto;
            }
        });
    });
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.lang-switch-dropdown').forEach(function(wrapper) {
            var toggle = wrapper.querySelector('.lang-switch-toggle');
            var menu = wrapper.querySelector('.lang-switch-menu');
            if (!toggle || !menu) return;

            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                var rect = toggle.getBoundingClientRect();
                document.querySelectorAll('.lang-switch-menu').forEach(function(other) {
                    if (other !== menu) other.setAttribute('hidden', '');
                });
                if (menu.parentElement !== document.body) {
                    document.body.appendChild(menu);
                }
                menu.style.left = Math.max(8, Math.min(window.innerWidth - 156, rect.right - 132)) + 'px';
                menu.style.top = (rect.bottom + 10) + 'px';
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

<section class="action-bar reveal offers-page-toolbar" style="position:relative; z-index:20; overflow:visible;"> 
    <form class="search-box" method="GET" action="index.php">
        <input type="hidden" name="page" value="offers">
        <input type="text" name="q" value="<?php echo htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo app_text('...Rechercher une offre','...Search offers','...ابحث عن عرض'); ?>" style="min-width: 220px; max-width: 320px; min-height: 44px; padding: 0 14px; font-size: 0.95rem;">
        <button type="submit" class="outline-btn" style="min-height: 44px; padding: 0 16px; font-size: 0.92rem;"><?php echo app_text('Rechercher','Search','بحث'); ?></button>
        <select name="sort" id="offerSortSelect" onchange="this.form.submit()" style="min-width: 230px; max-width: 280px; min-height: 44px; padding-right: 34px; font-size: 0.92rem;">
            <option value="date_publication_desc" <?php echo $sortOption === 'date_publication_desc' ? 'selected' : ''; ?>><?php echo app_text('Date publication: récentes','Publication date: recent','تاريخ النشر: الأحدث'); ?></option>
            <option value="date_publication_asc" <?php echo $sortOption === 'date_publication_asc' ? 'selected' : ''; ?>><?php echo app_text('Date publication: anciennes','Publication date: oldest','تاريخ النشر: الأقدم'); ?></option>
            <option value="date_expiration_desc" <?php echo $sortOption === 'date_expiration_desc' ? 'selected' : ''; ?>><?php echo app_text('Date expiration: récentes','Expiration date: recent','تاريخ الانتهاء: الأحدث'); ?></option>
            <option value="date_expiration_asc" <?php echo $sortOption === 'date_expiration_asc' ? 'selected' : ''; ?>><?php echo app_text('Date expiration: anciennes','Expiration date: oldest','تاريخ الانتهاء: الأقدم'); ?></option>
            <option value="titre_asc" <?php echo $sortOption === 'titre_asc' ? 'selected' : ''; ?>><?php echo app_text('Titre: A à Z','Title: A to Z','العنوان: من الألف إلى الياء'); ?></option>
            <option value="titre_desc" <?php echo $sortOption === 'titre_desc' ? 'selected' : ''; ?>><?php echo app_text('Titre: Z à A','Title: Z to A','العنوان: من الياء إلى الألف'); ?></option>
            <option value="prix_asc" <?php echo $sortOption === 'prix_asc' ? 'selected' : ''; ?>><?php echo app_text('Prix: croissant','Price: ascending','السعر: تصاعدي'); ?></option>
            <option value="prix_desc" <?php echo $sortOption === 'prix_desc' ? 'selected' : ''; ?>><?php echo app_text('Prix: décroissant','Price: descending','السعر: تنازلي'); ?></option>
        </select>
        <noscript><button type="submit" class="outline-btn"><?php echo app_text('Trier','Sort','ترتيب'); ?></button></noscript>
    </form>

    <div class="export-bar" style="position:relative; z-index:30; overflow:visible; gap:10px; flex-wrap:nowrap;">
        <button type="button" class="outline-btn" id="offersStatsToggle" style="min-height: 44px; padding: 0 16px; font-size: 0.92rem;" data-text-open="<?php echo app_text('Statistiques','Statistics','الإحصاءات'); ?>" data-text-close="<?php echo app_text('Masquer les statistiques','Hide statistics','إخفاء الإحصاءات'); ?>"><?php echo app_text('Statistiques','Statistics','الإحصاءات'); ?></button>
        <a class="solid-btn" href="<?php echo htmlspecialchars(buildOfferExportUrl($searchTerm, $sortOption), ENT_QUOTES, 'UTF-8'); ?>" style="min-height: 44px; padding: 0 16px; font-size: 0.92rem;"><?php echo app_text('Exporter PDF','Export PDF','تصدير PDF'); ?></a>
        <div class="lang-switch lang-switch-dropdown" style="display:inline-block; margin-right:12px; position:relative; z-index:40;">
            <button type="button" class="ghost-btn lang-switch-toggle" aria-haspopup="true" aria-expanded="false" aria-label="<?php echo app_text('Choisir la langue','Choose language','اختر اللغة'); ?>">🌐</button>
            <div class="lang-switch-menu" hidden style="position:fixed; min-width:132px; background:#ffffff; border:1px solid rgba(20,39,56,.12); border-radius:16px; box-shadow:0 16px 30px rgba(20,39,56,.16); padding:8px; z-index:99999; backdrop-filter: blur(8px);">
                <a href="<?php echo app_lang_url('fr'); ?>" style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:10px 12px; border-radius:10px; color:#0b2545; font-weight:700; text-decoration:none; transition:background .2s ease;">FR <span style="opacity:.55; font-size:12px;">FR</span></a>
                <a href="<?php echo app_lang_url('en'); ?>" style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:10px 12px; border-radius:10px; color:#0b2545; font-weight:700; text-decoration:none; transition:background .2s ease;">EN <span style="opacity:.55; font-size:12px;">EN</span></a>
                <a href="<?php echo app_lang_url('ar'); ?>" style="display:flex; align-items:center; justify-content:space-between; gap:10px; padding:10px 12px; border-radius:10px; color:#0b2545; font-weight:700; text-decoration:none; transition:background .2s ease;">AR <span style="opacity:.55; font-size:12px;">AR</span></a>
            </div>
        </div>
        <div class="notif-wrap">
            <button id="adminNotifToggle" class="ghost-btn notif-btn" type="button" aria-haspopup="true" aria-expanded="false">🔔<?php if ($unreadAdminCount>0): ?><span class="notif-badge"><?php echo (int)$unreadAdminCount; ?></span><?php endif; ?></button>
        </div>
    </div>
</section>

<style>
.offers-page-toolbar,
.action-bar.reveal,
.offers-page-toolbar .export-bar {
    position: relative;
    z-index: 999;
    overflow: visible;
}

.offers-page-toolbar .lang-switch-menu {
    z-index: 10000 !important;
}
</style>

<section class="admin-stats reveal offers-stats-panel" id="offersStatsSummary">
    <article class="admin-stat"><strong><?php echo htmlspecialchars($stats['total'], ENT_QUOTES, 'UTF-8'); ?></strong><span><?php echo app_text('Offres','Offers','العروض'); ?></span></article>
    <article class="admin-stat"><strong><?php echo htmlspecialchars($stats['ouverte'], ENT_QUOTES, 'UTF-8'); ?></strong><span><?php echo app_text('Ouvertes','Open','مفتوحة'); ?></span></article>
    <article class="admin-stat"><strong><?php echo htmlspecialchars($closedOffers, ENT_QUOTES, 'UTF-8'); ?></strong><span><?php echo app_text('Fermées','Closed','مغلقة'); ?></span></article>
    <article class="admin-stat"><strong><?php echo htmlspecialchars($applicationStats['total'], ENT_QUOTES, 'UTF-8'); ?></strong><span><?php echo app_text('Candidatures','Applications','الطلبات'); ?></span></article>
</section>

<!-- Modal: Statistics -->
<div class="modal-overlay" id="offersStatsModal" role="dialog" aria-modal="true" aria-labelledby="offersStatsTitle" hidden>
  <div class="modal" role="document" aria-describedby="offersStatsDescription">
    <button type="button" class="modal-close top-right" aria-label="Fermer" id="offersStatsModalClose">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
        <path d="M18 6L6 18M6 6l12 12" stroke="#142738" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
      </svg>
    </button>

        <header class="modal-header">
            <div>
                <h2 id="offersStatsTitle"><?php echo app_text('Statistiques des offres','Offers statistics','إحصاءات العروض'); ?></h2>
                <p id="offersStatsDescription" class="muted"><?php echo app_text('Vue synthétique — performance, répartition et dernières publications','Overview — performance, distribution and recent posts','نظرة عامة — الأداء والتوزيع وآخر المنشورات'); ?></p>
            </div>
      <div class="modal-meta">
        <small><?php echo (new DateTimeImmutable('now'))->format('d/m/Y H:i'); ?></small>
      </div>
    </header>

        <div class="modal-body offers-stats-modal">
                <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-number"><?php echo htmlspecialchars((string) count($offers), ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="stat-label"><?php echo app_text('Total offres','Total offers','إجمالي العروض'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo htmlspecialchars((string) $stats['ouverte'], ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="stat-label"><?php echo app_text('Ouvertes','Open','مفتوحة'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo htmlspecialchars((string) $closedOffers, ENT_QUOTES, 'UTF-8'); ?></div>
                    <div class="stat-label"><?php echo app_text('Fermées','Closed','مغلقة'); ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo htmlspecialchars($averageOfferPrice !== null ? number_format($averageOfferPrice, 2, ',', ' ') : 'N/A', ENT_QUOTES, 'UTF-8'); ?> <span class="small-currency">DT</span></div>
                    <div class="stat-label"><?php echo app_text('Prix moyen','Average price','متوسط السعر'); ?></div>
                </div>
            </div>

      <div class="charts-container">
                <div class="chart-panel">
                    <h3><?php echo app_text('Répartition par type de service','Distribution by service type','توزيع حسب نوع الخدمة'); ?></h3>
          <canvas id="chartTypeDistribution" width="200" height="200"></canvas>
        </div>
                <div class="chart-panel">
                    <h3><?php echo app_text('État des offres','Offer status','حالة العروض'); ?></h3>
          <canvas id="chartOfferStatus" width="200" height="200"></canvas>
        </div>
      </div>

            <div class="chart-panel full-width">
                <h3><?php echo app_text('Répartition des prix par type','Price distribution by type','توزيع الأسعار حسب النوع'); ?></h3>
        <canvas id="chartPriceByType" height="80"></canvas>
      </div>

      <div class="stats-grid">
        <div class="latest-box most-desired-box">
                        <div class="box-header">
                        <div>
                            <h3><?php echo app_text('Offre la plus convoitée','Most desired offer','العرض الأكثر طلبًا'); ?></h3>
                            <p class="box-subtitle"><?php echo app_text('Offre avec le plus de candidatures','Offer with the most applications','العرض الذي يحتوي على أكبر عدد من الطلبات'); ?></p>
                        </div>
            <span class="crown-badge">★</span>
          </div>
          <div class="offer-card-content">
            <p class="latest-title"><?php echo htmlspecialchars($offerWithMostApps['titre'] ?? 'Aucune offre', ENT_QUOTES, 'UTF-8'); ?></p>
            <div class="offer-meta">
              <span class="meta-badge"><?php echo htmlspecialchars($offerWithMostApps['type_service'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></span>
              <span class="meta-badge secondary"><?php echo htmlspecialchars($offerWithMostApps['localisation'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="candidatures-stat">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="stat-icon">
                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z" fill="#4b96ff"/>
              </svg>
              <div class="stat-content">
                                <strong class="stat-number"><?php echo htmlspecialchars((string) $mostAppsCount, ENT_QUOTES, 'UTF-8'); ?></strong>
                                <span class="stat-label"><?php echo $mostAppsCount !== 1 ? app_text('Candidatures','Applications','طلبات') : app_text('Candidature','Application','طلب'); ?></span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <footer class="modal-footer">
            <button type="button" class="outline-btn" id="offersStatsModalCloseBtn"><?php echo app_text('Fermer','Close','إغلاق'); ?></button>
    </footer>
  </div>
</div>

<section class="admin-panel reveal offers-table-panel">
    <span class="section-badge"><?php echo app_text('Gestion des offres','Offers management','إدارة العروض'); ?></span>

    <div class="table-wrap">
        <table class="module-table">
            <thead>
                <tr>
                    <th><?php echo app_text('Titre','Title','العنوان'); ?></th>
                    <th><?php echo app_text('Type service','Service type','نوع الخدمة'); ?></th>
                    <th><?php echo app_text('Localisation','Location','الموقع'); ?></th>
                    <th><?php echo app_text('Publication','Publication','التاريخ'); ?></th>
                    <th><?php echo app_text('Expiration','Expiration','تاريخ الانتهاء'); ?></th>
                    <th><?php echo app_text('Statut','Status','الحالة'); ?></th>
                    <th><?php echo app_text('Actions','Actions','الإجراءات'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($visibleOffers)): ?>
                    <tr>
                        <td colspan="7">Aucune offre trouvée.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($visibleOffers as $offer): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($offer['titre'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($offer['type_service'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($offer['localisation'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(formatDate($offer['date_publication']), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(formatDate($offer['date_expiration']), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($offer['statut']), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="admin-tools">
                                <a href="?page=offer_applications&offer_id=<?php echo urlencode((string) ($offer['id_offre'] ?? '')); ?>" class="small-btn"><?php echo app_text('Voir candidatures','View applications','عرض الطلبات'); ?></a>
                                <form method="GET" style="display:inline;">
                                    <input type="hidden" name="page" value="offers">
                                    <input type="hidden" name="edit" value="<?php echo htmlspecialchars($offer['id_offre'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit" class="danger-btn"><?php echo app_text('Modifier','Edit','تعديل'); ?></button>
                                </form>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="offer_id" value="<?php echo htmlspecialchars($offer['id_offre'], ENT_QUOTES, 'UTF-8'); ?>">
                                    <button type="submit" class="danger-btn" onclick="return confirm('<?php echo addslashes(app_text("Supprimer cette offre ?","Delete this offer?","حذف هذا العرض؟")); ?>');"><?php echo app_text('Supprimer','Delete','حذف'); ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php if (!empty($_GET['type'])):
    $filterType = trim((string) ($_GET['type'] ?? ''));
    $filteredOffers = array_filter($offers, function($o) use ($filterType) {
        return isset($o['type_service']) && strcasecmp(trim((string)$o['type_service']), $filterType) === 0;
    });
?>
<section class="admin-panel reveal offers-table-panel">
    <span class="section-badge">Offres similaires — Type: <?php echo htmlspecialchars($filterType, ENT_QUOTES, 'UTF-8'); ?></span>

    <div class="table-wrap">
        <table class="module-table">
            <thead>
                <tr>
                    <th>Titre</th>
                    <th>Localisation</th>
                    <th>Publication</th>
                    <th>Expiration</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($filteredOffers)): ?>
                    <tr>
                        <td colspan="6">Aucune offre trouvée pour ce type de service.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($filteredOffers as $fo): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($fo['titre'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($fo['localisation'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(formatDate($fo['date_publication'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(formatDate($fo['date_expiration'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($fo['statut'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <a href="?page=offers&edit=<?php echo htmlspecialchars($fo['id_offre'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="small-btn">Voir</a>
                                <a href="?page=offers&edit=<?php echo htmlspecialchars($fo['id_offre'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" class="small-btn">Editer</a>
                            </td>
                        </tr>
                        <tr class="offer-details-row">
                            <td colspan="6">
                                <div class="offer-details">
                                    <strong><?php echo app_text('Description:','Description:','الوصف:'); ?></strong>
                                    <p><?php echo nl2br(htmlspecialchars($fo['description'] ?? '', ENT_QUOTES, 'UTF-8')); ?></p>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>
<section class="admin-panel reveal offers-form-panel">
    <span class="section-badge"><?php echo $currentOffer ? app_text("Modifier l\'offre","Edit offer","تعديل العرض") : app_text('Publier une offre','Publish an offer','نشر عرض'); ?></span>

    <form method="POST" id="form-grid" class="form-grid offers-form" novalidate data-allowed-services='<?php echo htmlspecialchars(json_encode($typeServiceOptions), ENT_QUOTES, "UTF-8"); ?>'>
        <input type="hidden" name="action" value="<?php echo $currentOffer ? 'update' : 'create'; ?>">
        <?php if ($currentOffer): ?>
            <input type="hidden" name="offer_id" value="<?php echo htmlspecialchars($currentOffer['id_offre'], ENT_QUOTES, 'UTF-8'); ?>">
        <?php endif; ?>

        <div class="field-block form-field">
            <label for="titreField"><?php echo app_text("Titre de l'offre","Offer title","عنوان العرض"); ?></label>
            <input type="text" name="titre" id="titreField" placeholder="<?php echo app_text("Titre de l'offre","Offer title","عنوان العرض"); ?>" value="<?php echo htmlspecialchars($formData['titre'], ENT_QUOTES, 'UTF-8'); ?>" aria-invalid="<?php echo $fieldErrors['titre'] !== '' ? 'true' : 'false'; ?>">
            <small class="field-error" data-error-for="titre"><?php echo htmlspecialchars($fieldErrors['titre'], ENT_QUOTES, 'UTF-8'); ?></small>
        </div>

        <div class="field-block form-field">
            <label for="typeServiceField"><?php echo app_text('Type de service','Service type','نوع الخدمة'); ?></label>
            <select name="type_service" id="typeServiceField" aria-invalid="<?php echo $fieldErrors['type_service'] !== '' ? 'true' : 'false'; ?>">
                <option value=""><?php echo app_text('Sélectionnez le type de service','Select service type','اختر نوع الخدمة'); ?></option>
                <?php foreach ($typeServiceOptions as $option): ?>
                    <option value="<?php echo htmlspecialchars($option, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $formData['type_service'] === $option ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars(ucfirst($option), ENT_QUOTES, 'UTF-8'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small class="field-error" data-error-for="type_service"><?php echo htmlspecialchars($fieldErrors['type_service'], ENT_QUOTES, 'UTF-8'); ?></small>
        </div>

        <div class="field-block form-field localisation-field-block">
            <label for="localisationField"><?php echo app_text('Localisation','Location','الموقع'); ?></label>
            <input type="text" name="localisation" id="localisationField" placeholder="<?php echo app_text('Localisation','Location','الموقع'); ?>" value="<?php echo htmlspecialchars($formData['localisation'], ENT_QUOTES, 'UTF-8'); ?>" aria-invalid="<?php echo $fieldErrors['localisation'] !== '' ? 'true' : 'false'; ?>">
            <small class="field-error" data-error-for="localisation"><?php echo htmlspecialchars($fieldErrors['localisation'], ENT_QUOTES, 'UTF-8'); ?></small>
        </div>

        <div class="field-block form-field">
            <label for="date_expiration"><?php echo app_text("Date d'expiration","Expiration date","تاريخ الانتهاء"); ?></label>
            <input type="date" id="date_expiration" name="date_expiration" value="<?php echo htmlspecialchars($formData['date_expiration'], ENT_QUOTES, 'UTF-8'); ?>" aria-invalid="<?php echo $fieldErrors['date_expiration'] !== '' ? 'true' : 'false'; ?>">
            <small class="field-error" data-error-for="date_expiration"><?php echo htmlspecialchars($fieldErrors['date_expiration'], ENT_QUOTES, 'UTF-8'); ?></small>
        </div>

        <div class="field-block form-field auto-status-block">
            <label><?php echo app_text('Statut','Status','الحالة'); ?></label>
            <input type="text" value="<?php echo app_text('Automatique selon date d\'expiration','Automatic based on expiration date','تلقائي حسب تاريخ الانتهاء'); ?>" readonly aria-readonly="true" class="readonly-status-input">
            <small class="status-auto-note"><?php echo app_text("Le statut passe automatiquement a Fermee quand la date est depassee.","Status automatically becomes Closed when the date is passed.","الحالة تتحول تلقائيًا إلى مغلقة عندما تتجاوز التاريخ."); ?></small>
        </div>

        <div class="field-block form-field price-field-block">
            <label for="prixField"><?php echo app_text('Prix','Price','السعر'); ?></label>
            <input type="text" name="prix" id="prixField" inputmode="decimal" pattern="^\d+(?:[\.,]\d{1,2})?$" placeholder="<?php echo app_text('Prix','Price','السعر'); ?>" value="<?php echo htmlspecialchars($formData['prix'], ENT_QUOTES, 'UTF-8'); ?>" aria-invalid="<?php echo $fieldErrors['prix'] !== '' ? 'true' : 'false'; ?>">
            <small class="field-error" data-error-for="prix"><?php echo htmlspecialchars($fieldErrors['prix'], ENT_QUOTES, 'UTF-8'); ?></small>
        </div>

        <div class="field-block form-field full-span">
            <label for="descriptionField"><?php echo app_text('Description','Description','الوصف'); ?></label>
            <textarea name="description" id="descriptionField" placeholder="<?php echo app_text("Description de l'offre...","Offer description...","وصف العرض..."); ?>" rows="4" aria-invalid="<?php echo $fieldErrors['description'] !== '' ? 'true' : 'false'; ?>"><?php echo htmlspecialchars($formData['description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
            <small class="field-error" data-error-for="description"><?php echo htmlspecialchars($fieldErrors['description'], ENT_QUOTES, 'UTF-8'); ?></small>
        </div>

        <div class="icon-actions offers-form-actions">
            <button type="submit" class="solid-btn"><?php echo $currentOffer ? app_text('Mettre à jour','Update','تحديث') : app_text('Publier','Publish','نشر'); ?></button>
            <?php if ($currentOffer): ?>
                <a href="?page=offers" class="outline-btn"><?php echo app_text('Annuler','Cancel','إلغاء'); ?></a>
            <?php endif; ?>
        </div>
    </form>
</section>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function(){
    try{
        var params = new URLSearchParams(window.location.search);
        if (params.has('edit')) {
            var el = document.getElementById('form-grid');
            if (el) {
                try {
                    var rect = el.getBoundingClientRect();
                    var offset = 100; // fixed 100px offset so the full form and update button are visible
                    var target = window.scrollY + rect.top - (window.innerHeight / 2) + offset;
                    window.scrollTo({ top: Math.max(0, target), behavior: 'smooth' });
                } catch(e) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                var first = el.querySelector('input, textarea, select');
                if (first) try { first.focus({preventScroll: true}); } catch(e){ first.focus(); }
                var url = new URL(window.location);
                url.searchParams.delete('edit');
                window.history.replaceState({}, document.title, url.pathname + url.search + url.hash);
            }
        }

            var statsToggle = document.getElementById('offersStatsToggle') || document.querySelector('#offersStatsToggle');
            var statsModal = document.getElementById('offersStatsModal') || document.querySelector('#offersStatsModal');
            var statsModalClose = document.getElementById('offersStatsModalClose') || document.querySelector('#offersStatsModalClose');
            var statsModalCloseBtn = document.getElementById('offersStatsModalCloseBtn') || document.querySelector('#offersStatsModalCloseBtn');

            // Chart instances
            var chartsInstances = {};

            // Prepare chart data from PHP
            var chartDataFromServer = <?php 
                // Type distribution
                $typeLabelsFr = [];
                $typeCountsFr = [];
                foreach ($offerTypeStats as $type => $count) {
                    $typeLabelsFr[] = ucfirst((string) $type);
                    $typeCountsFr[] = (int) $count;
                }

                // Status counts
                $statusLabels = array('Ouvertes', 'Fermées');
                $statusCounts = array((int) $stats['ouverte'], (int) $closedOffers);

                // Price by type
                $priceByType = [];
                foreach ($offerTypeStats as $type => $count) {
                    $typeOffers = array_filter($offers, function($o) use ($type) {
                        return isset($o['type_service']) && strcasecmp(trim((string)$o['type_service']), $type) === 0;
                    });
                    $typePrices = array_filter(array_map(function($o) {
                        return isset($o['prix']) && $o['prix'] !== '' ? (float)$o['prix'] : null;
                    }, $typeOffers));
                    $avgPrice = !empty($typePrices) ? array_sum($typePrices) / count($typePrices) : 0;
                    $priceByType[] = round($avgPrice, 2);
                }

                echo json_encode([
                    'typeLabels' => $typeLabelsFr,
                    'typeCounts' => $typeCountsFr,
                    'statusLabels' => $statusLabels,
                    'statusCounts' => $statusCounts,
                    'priceLabels' => $typeLabelsFr,
                    'priceValues' => $priceByType
                ]);
            ?>;

            function initCharts() {
                try {
                    if (window.Chart && typeof window.Chart === 'function') {
                        // Chart 1: Type Distribution (Doughnut)
                        var ctxType = document.getElementById('chartTypeDistribution');
                        if (ctxType && !chartsInstances.typeChart) {
                            var colors = ['#EE5828', '#142738', '#4CAF50', '#000000', '#EE5828', '#142738', '#4CAF50', '#000000'];
                            chartsInstances.typeChart = new Chart(ctxType, {
                                type: 'doughnut',
                                data: {
                                    labels: chartDataFromServer.typeLabels,
                                    datasets: [{
                                        data: chartDataFromServer.typeCounts,
                                        backgroundColor: colors.slice(0, chartDataFromServer.typeLabels.length),
                                        borderColor: '#FFFFFF',
                                        borderWidth: 3
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: true,
                                    plugins: {
                                        legend: {
                                            position: 'bottom',
                                            labels: { font: { size: 12 }, padding: 12, color: '#000000' }
                                        }
                                    }
                                }
                            });
                        }

                        // Chart 2: Status (Pie)
                        var ctxStatus = document.getElementById('chartOfferStatus');
                        if (ctxStatus && !chartsInstances.statusChart) {
                            chartsInstances.statusChart = new Chart(ctxStatus, {
                                type: 'pie',
                                data: {
                                    labels: chartDataFromServer.statusLabels,
                                    datasets: [{
                                        data: chartDataFromServer.statusCounts,
                                        backgroundColor: ['#4CAF50', '#EE5828'],
                                        borderColor: '#FFFFFF',
                                        borderWidth: 3
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    maintainAspectRatio: true,
                                    plugins: {
                                        legend: {
                                            position: 'bottom',
                                            labels: { font: { size: 12 }, padding: 12, color: '#000000' }
                                        }
                                    }
                                }
                            });
                        }

                        // Chart 3: Price by Type (Bar)
                        var ctxPrice = document.getElementById('chartPriceByType');
                        if (ctxPrice && !chartsInstances.priceChart) {
                            chartsInstances.priceChart = new Chart(ctxPrice, {
                                type: 'bar',
                                data: {
                                    labels: chartDataFromServer.priceLabels,
                                    datasets: [{
                                        label: 'Prix moyen (DT)',
                                        data: chartDataFromServer.priceValues,
                                        backgroundColor: '#EE5828',
                                        borderRadius: 6,
                                        borderSkipped: false
                                    }]
                                },
                                options: {
                                    responsive: true,
                                    indexAxis: 'x',
                                    plugins: {
                                        legend: { display: true, labels: { font: { size: 12 }, color: '#000000' } }
                                    },
                                    scales: {
                                        y: {
                                            beginAtZero: true,
                                            ticks: { color: '#000000', font: { size: 11 } },
                                            grid: { color: 'rgba(20, 39, 56, 0.14)' }
                                        },
                                        x: {
                                            ticks: { color: '#000000', font: { size: 11 } },
                                            grid: { display: false }
                                        }
                                    }
                                }
                            });
                        }
                    }
                } catch(e) {}
            }

            function escHandler(e) {
                if (e.key === 'Escape' || e.key === 'Esc') {
                    closeStatsModal();
                }
            }

            function openStatsModal() {
                if (!statsModal) return;
                statsModal.removeAttribute('hidden');
                document.body.classList.add('modal-open');
                var closeText = statsToggle.getAttribute('data-text-close');
                statsToggle.textContent = closeText || 'Masquer les statistiques';
                try {
                    var first = statsModal.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
                    if (first) first.focus();
                } catch (e) {}
                document.addEventListener('keydown', escHandler);
                // Initialize charts when modal opens
                initCharts();
            }

            function closeStatsModal() {
                if (!statsModal) return;
                statsModal.setAttribute('hidden', 'hidden');
                document.body.classList.remove('modal-open');
                var openText = statsToggle.getAttribute('data-text-open');
                statsToggle.textContent = openText || 'Statistiques';
                try { statsToggle.focus(); } catch (e) {}
                document.removeEventListener('keydown', escHandler);
            }

            if (statsToggle && statsModal) {
                statsToggle.addEventListener('click', function () {
                    if (statsModal.hasAttribute('hidden')) {
                        openStatsModal();
                    } else {
                        closeStatsModal();
                    }
                });

                statsModal.addEventListener('click', function (e) {
                    if (e.target === statsModal) closeStatsModal();
                });

                if (statsModalClose) statsModalClose.addEventListener('click', closeStatsModal);
                if (statsModalCloseBtn) statsModalCloseBtn.addEventListener('click', closeStatsModal);
            }

        var sortSelect = document.getElementById('offerSortSelect');
        if (sortSelect && sortSelect.form) {
            sortSelect.addEventListener('change', function () {
                this.form.submit();
            });
        }
    } catch(e){}
})();
</script>

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
    background: #FFFFFF;
    font-size: 14px;
    font-weight: 700;
    letter-spacing: 0.01em;
    border-bottom: 2px solid #142738;
    color: #000000;
}

.module-table tbody tr {
    transition: background 0.2s ease;
}

.module-table tbody tr:hover {
    background: rgba(238, 88, 40, 0.06);
}

.offers-stats-panel {
    margin-top: 22px;
}

.offers-stats-grid {
    margin-top: 14px;
}

.offer-stats-details {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 18px;
    margin-top: 18px;
}

.offer-stats-details > div {
    padding: 18px;
    border: 1px solid rgba(20, 39, 56, 0.08);
    border-radius: 18px;
    background: rgba(255, 255, 255, 0.56);
}

body.admin-body.dark .offer-stats-details > div {
    background: rgba(255, 255, 255, 0.04);
    border-color: rgba(255, 255, 255, 0.08);
}

.offer-stats-details h3 {
    margin-bottom: 12px;
    font-size: 1rem;
}

.offer-stats-details p {
    margin: 6px 0;
}

.offer-stats-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    gap: 10px;
}

.offer-stats-list li {
    display: flex;

/* Offers stats panel palette lock: only orange / navy / green / white / black */
.offers-stats-panel .admin-stat,
.offers-stats-modal .stat-card,
.offers-stats-modal .latest-box,
.offers-stats-modal .chart-panel,
.offers-stats-modal .distribution {
    background: #FFFFFF;
    border: 1px solid #142738;
    box-shadow: none;
}

.offers-stats-panel .admin-stat {
    border-top: 6px solid #EE5828;
}

.offers-stats-panel .admin-stat:nth-child(3n + 2) {
    border-top-color: #142738;
}

.offers-stats-panel .admin-stat:nth-child(3n + 3) {
    border-top-color: #4CAF50;
}

.offers-stats-panel .admin-stat strong,
.offers-stats-modal .stat-number {
    color: #000000;
}

.offers-stats-panel .admin-stat span,
.offers-stats-modal .stat-label,
.offers-stats-modal .box-subtitle,
.offers-stats-modal .latest-title,
.offers-stats-modal .latest-box h3,
.offers-stats-modal .distribution h3,
.offers-stats-modal .chart-panel h3,
.offers-stats-modal .offer-stats-details h3,
.offers-stats-modal .modal-header h2,
.offers-stats-modal .modal-header .muted,
.offers-stats-modal .modal-meta small {
    color: #142738;
}

.offers-stats-modal .offer-stats-details > div {
    background: #FFFFFF;
    border: 1px solid #142738;
    box-shadow: none;
}

.offers-stats-modal .offer-stats-details p {
    color: #000000;
}

.offers-stats-modal .small-currency {
    color: #000000;
}

.offers-stats-modal .most-desired-box {
    background: #FFFFFF;
    border: 2px solid #EE5828;
    box-shadow: none;
}

.offers-stats-modal .most-desired-box::before {
    display: none;
}

.offers-stats-modal .most-desired-box:hover {
    transform: none;
    box-shadow: none;
    border-color: #4CAF50;
}

.offers-stats-modal .most-desired-box .box-header {
    border-bottom: 1px solid #142738;
}

.offers-stats-modal .crown-badge {
    color: #EE5828;
    text-shadow: none;
}

.offers-stats-modal .meta-badge,
.offers-stats-modal .meta-badge.secondary {
    background: #FFFFFF;
    color: #142738;
    border: 1px solid #142738;
}

.offers-stats-modal .candidatures-stat {
    background: #FFFFFF;
    border: 1px solid #4CAF50;
    box-shadow: none;
}

.offers-stats-modal .stat-icon path {
    fill: #4CAF50;
}

.offers-stats-modal .offer-stats-list li {
    background: #FFFFFF;
    color: #000000;
    border: 1px solid #142738;
}

.offers-stats-modal .distribution {
    box-shadow: none;
}
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 12px;
    border-radius: 14px;
    background: rgba(20, 39, 56, 0.04);
}

body.admin-body.dark .offer-stats-list li {
    background: rgba(255, 255, 255, 0.06);
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

    .offer-stats-details {
        grid-template-columns: 1fr;
    }
}
</style>

<section class="admin-panel reveal applications-panel">
    <span class="section-badge"><?php echo app_text('Candidatures récentes','Recent applications','الطلبات الأخيرة'); ?></span>

    <div class="table-wrap">
        <table class="module-table">
            <thead>
                <tr>
                    <th><?php echo app_text('Candidat','Candidate','المرشح'); ?></th>
                    <th><?php echo app_text('Offre','Offer','العرض'); ?></th>
                    <th><?php echo app_text('Date candidature','Application date','تاريخ التقديم'); ?></th>
                    <th><?php echo app_text('Statut','Status','الحالة'); ?></th>
                    <th><?php echo app_text('Expérience','Experience','الخبرة'); ?></th>
                    <th><?php echo app_text('Compétences','Skills','المهارات'); ?></th>
                    <th><?php echo app_text('Actions','Actions','الإجراءات'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentApplications)): ?>
                    <tr>
                        <td colspan="7"><?php echo app_text('Aucune candidature pour le moment.','No applications at the moment.','لا توجد طلبات حالياً.'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentApplications as $application): ?> 
                        <?php $normalizedStatus = normalizeApplicationStatus((string) ($application['statut'] ?? '')); ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string) ($application['id'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($application['offer_titre'] ?? app_text('Offre supprimée','Offer deleted','تم حذف العرض')), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(formatDate((string) ($application['created_at'] ?? null)), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <span class="status-chip status-<?php echo htmlspecialchars($normalizedStatus, ENT_QUOTES, 'UTF-8'); ?>" data-status-chip>
                                    <?php echo htmlspecialchars(formatApplicationStatus((string) ($application['statut'] ?? 'en attente')), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars((string) ($application['experience'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars((string) ($application['competences'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="admin-tools">
                                <form method="POST" class="js-status-form inline-form" data-target-status="acceptee">
                                    <input type="hidden" name="action" value="update_application_status">
                                    <input type="hidden" name="application_id" value="<?php echo htmlspecialchars((string) ($application['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="status" value="acceptee">
                                    <button
                                        type="submit"
                                        class="success-btn action-pill <?php echo $normalizedStatus === 'acceptee' ? 'is-selected' : ''; ?>"
                                        <?php echo $normalizedStatus === 'acceptee' ? 'disabled' : ''; ?>
                                        aria-disabled="<?php echo $normalizedStatus === 'acceptee' ? 'true' : 'false'; ?>"
                                    >
                                        <?php echo $normalizedStatus === 'acceptee' ? app_text('Acceptée','Accepted','مقبول') : app_text('Accepter','Accept','قبول'); ?>
                                    </button>
                                </form>
                                <form method="POST" class="js-status-form inline-form" data-target-status="refusee">
                                    <input type="hidden" name="action" value="update_application_status">
                                    <input type="hidden" name="application_id" value="<?php echo htmlspecialchars((string) ($application['id'] ?? 0), ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="status" value="refusee">
                                    <button
                                        type="submit"
                                        class="danger-btn action-pill <?php echo $normalizedStatus === 'refusee' ? 'is-selected' : ''; ?>"
                                        <?php echo $normalizedStatus === 'refusee' ? 'disabled' : ''; ?>
                                        aria-disabled="<?php echo $normalizedStatus === 'refusee' ? 'true' : 'false'; ?>"
                                    >
                                        <?php echo $normalizedStatus === 'refusee' ? app_text('Refusée','Rejected','مرفوض') : app_text('Refuser','Reject','رفض'); ?>
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
    background: rgba(76, 175, 80, 0.2);
    color: #4CAF50;
    border: 1px solid rgba(76, 175, 80, 0.35);
}

.status-refusee {
    background: rgba(238, 88, 40, 0.2);
    color: #EE5828;
    border: 1px solid rgba(238, 88, 40, 0.35);
}

.status-en_attente {
    background: rgba(238, 88, 40, 0.18);
    color: #EE5828;
    border: 1px solid rgba(238, 88, 40, 0.35);
}
</style>

<style>
/* Polished modal styles */
.modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(10, 18, 28, 0.56);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1200;
  padding: 28px;
  animation: overlayFade .18s ease;
}

.modal {
  background: #ffffff;
  color: #132434;
  border-radius: 14px;
  max-width: 980px;
  width: 100%;
  max-height: 92vh;
  overflow: auto;
  box-shadow: 0 30px 80px rgba(8, 20, 40, 0.45);
  padding: 22px;
  transform-origin: center;
  animation: modalEnter .22s cubic-bezier(.2,.9,.2,1);
  border: 1px solid rgba(20,39,56,0.04);
}

/* Top-right close button */
.modal-close.top-right {
  position: absolute;
  right: 16px;
  top: 16px;
  background: #FFFFFF;
  border: 1px solid #142738;
  width: 40px;
  height: 40px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  box-shadow: 0 6px 18px rgba(6,18,36,0.06);
  transition: all 0.2s ease;
}

.modal-close.top-right:hover {
  background: #FFFFFF;
  box-shadow: 0 8px 24px rgba(6,18,36,0.1);
  border-color: #EE5828;
}

/* Header */
.modal-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 18px;
  margin-bottom: 14px;
}

.modal-header h2 {
  margin: 0;
  font-size: 1.125rem;
  color: #000000;
  font-weight: 800;
}

.modal-header .muted {
  margin: 6px 0 0;
  color: #142738;
  font-size: 0.95rem;
}

.modal-meta small {
  color: #142738;
  font-size: 0.9rem;
}

/* Cards */
.stats-cards {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 14px;
  margin-bottom: 18px;
}

.stat-card {
  background: #FFFFFF;
  border-radius: 12px;
  padding: 18px;
  text-align: center;
  box-shadow: 0 8px 20px rgba(8,20,40,0.04);
  border: 1px solid #142738;
  transition: all 0.2s ease;
}

.stat-card:hover {
  box-shadow: 0 12px 28px rgba(8,20,40,0.08);
  border-color: #142738;
}

.stat-number {
  font-size: 34px;
  font-weight: 800;
  color: #000000;
  line-height: 1;
}

.stat-label {
  margin-top: 8px;
  color: #142738;
  font-weight: 700;
  font-size: 0.95rem;
}

.small-currency {
  font-size: 0.6em;
  font-weight: 700;
  color: #000000;
  margin-left: 6px;
}

/* Grid below charts */
.stats-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 18px;
}

.latest-box {
  padding: 18px;
  border-radius: 12px;
  background: #FFFFFF;
  border: 1px solid #142738;
  box-shadow: 0 8px 18px rgba(8,20,40,0.04);
}

.latest-box h3 {
  margin: 0 0 8px 0;
  color: #000000;
  font-size: 1rem;
  font-weight: 700;
}

.latest-title {
  margin: 6px 0;
  font-weight: 700;
  color: #000000;
}

.latest-date {
  color: #142738;
  margin-top: 6px;
  font-size: 0.95rem;
}

/* Most desired offer styling */
.most-desired-box {
  background: linear-gradient(135deg, #FFFFFF 0%, #FFFFFF 100%);
  border: 2px solid #EE5828;
  box-shadow: 0 12px 32px rgba(238, 88, 40, 0.15);
  transition: all 0.3s cubic-bezier(0.2, 0.9, 0.2, 1);
  position: relative;
  overflow: hidden;
}

.most-desired-box::before {
  content: '';
  position: absolute;
  top: 0;
  left: -100%;
  width: 100%;
  height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
  transition: left 0.6s ease;
}

.most-desired-box:hover {
  box-shadow: 0 16px 40px rgba(238, 88, 40, 0.25);
  transform: translateY(-2px);
  border-color: #4CAF50;
}

.most-desired-box:hover::before {
  left: 100%;
}

.most-desired-box .box-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 16px;
  padding-bottom: 12px;
  border-bottom: 1px solid #142738;
}

.most-desired-box h3 {
  margin: 0;
  color: #000000;
  font-size: 1.15rem;
  font-weight: 800;
  letter-spacing: -0.02em;
}

.box-subtitle {
  margin: 4px 0 0;
  color: #142738;
  font-size: 0.85rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.crown-badge {
  font-size: 1.8rem;
  color: #EE5828;
  text-shadow: 0 2px 8px rgba(238, 88, 40, 0.4);
  animation: wobble 3s ease-in-out infinite;
  flex-shrink: 0;
}

@keyframes wobble {
  0%, 100% { transform: rotate(0deg); }
  25% { transform: rotate(-2deg); }
  75% { transform: rotate(2deg); }
}

.offer-card-content {
  display: flex;
  flex-direction: column;
  gap: 12px;
}

.most-desired-box .latest-title {
  font-size: 1.08rem;
  margin: 0;
  color: #000000;
  font-weight: 800;
  line-height: 1.4;
}

.offer-meta {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.meta-badge {
  display: inline-block;
  padding: 6px 12px;
  background: rgba(238, 88, 40, 0.1);
  color: #142738;
  border-radius: 999px;
  font-size: 0.85rem;
  font-weight: 700;
  border: 1px solid #142738;
  transition: all 0.2s ease;
}

.meta-badge:hover {
  background: rgba(238, 88, 40, 0.2);
  border-color: #EE5828;
}

.meta-badge.secondary {
  background: rgba(238, 88, 40, 0.08);
  color: #000000;
  border-color: #142738;
}

.meta-badge.secondary:hover {
  background: rgba(238, 88, 40, 0.15);
  border-color: #EE5828;
}

.candidatures-stat {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  background: #FFFFFF;
  border-radius: 12px;
  border: 2px solid #4CAF50;
  transition: all 0.2s ease;
  margin-top: 4px;
}

.candidatures-stat:hover {
  border-color: #EE5828;
  box-shadow: 0 6px 16px rgba(238, 88, 40, 0.1);
  background: #FFFFFF;
}

.stat-icon {
  flex-shrink: 0;
  animation: fadeInScale 0.6s ease-out;
}

.stat-content {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.stat-number {
  color: #EE5828;
  font-size: 1.3rem;
  font-weight: 900;
  letter-spacing: -0.01em;
}

.stat-label {
  color: #142738;
  font-weight: 700;
  font-size: 0.9rem;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}

@keyframes fadeInScale {
  from {
    opacity: 0;
    transform: scale(0.8);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}

/* Distribution list */
.distribution {
  background: #FFFFFF;
  padding: 18px;
  border-radius: 12px;
  border: 1px solid #142738;
  box-shadow: 0 8px 18px rgba(8,20,40,0.04);
}

.distribution h3 {
  margin-top: 0;
  color: #000000;
  font-size: 1rem;
  font-weight: 700;
}

.offer-stats-list {
  list-style: none;
  padding: 0;
  margin: 12px 0 0;
  display: grid;
  gap: 10px;
}

.offer-stats-list li {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 14px;
  border-radius: 999px;
  background: #FFFFFF;
  color: #000000;
  font-weight: 700;
  font-size: 0.95rem;
  transition: all 0.15s ease;
  border: 1px solid #142738;
}

.offer-stats-list li:hover {
  background: #FFFFFF;
  border-color: #EE5828;
}

.type-name {
  flex: 1;
}

.type-count {
  color: #EE5828;
  font-size: 1.1em;
}

/* Footer */
.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 18px;
}

/* Accessibility & small screens */
.modal:focus {
  outline: none;
}

.modal-overlay[hidden] {
  display: none !important;
}

body.modal-open {
  overflow: hidden;
}

/* Animations */
@keyframes overlayFade {
  from { opacity: 0; }
  to { opacity: 1; }
}

@keyframes modalEnter {
  from {
    transform: translateY(6px) scale(0.995);
    opacity: 0;
  }
  to {
    transform: translateY(0) scale(1);
    opacity: 1;
  }
}

/* Charts container */
.charts-container {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 18px;
  margin: 18px 0;
}

.chart-panel {
  background: #fff;
  padding: 18px;
  border-radius: 12px;
  border: 1px solid rgba(20,39,56,0.04);
  box-shadow: 0 8px 18px rgba(8,20,40,0.04);
}

.chart-panel h3 {
  margin: 0 0 14px 0;
  color: #0f2a3a;
  font-size: 1rem;
  font-weight: 700;
}

.chart-panel.full-width {
  grid-column: 1 / -1;
}

@media (max-width: 880px) {
  .stats-cards {
    grid-template-columns: repeat(2, 1fr);
  }

  .modal {
    padding: 16px;
  }

  .charts-container {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 640px) {
  .modal-overlay {
    padding: 16px;
  }

  .modal-header {
    flex-direction: column;
  }

  .stats-cards {
    grid-template-columns: 1fr;
  }
}
</style>