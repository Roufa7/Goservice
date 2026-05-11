<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../view/i18n.php';
require_once __DIR__ . '/../../../model/Offer.php';
require_once __DIR__ . '/../../../model/Candidature.php';
require_once __DIR__ . '/../../../controller/OfferController.php';
require_once __DIR__ . '/../../../controller/CandidatureController.php';
require_once __DIR__ . '/../../../controller/OfferPdfExporter.php';
require_once __DIR__ . '/../../../controller/PredictionController.php';

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
    } else {
        // Accept either address text (letters, numbers, spaces, punctuation) or coordinates (lat,lng format)
        $isCoordinates = preg_match('/^\s*-?\d+(?:\.\d+)?\s*,\s*-?\d+(?:\.\d+)?\s*$/', $localisation);
        $isAddress = preg_match('/^[a-zA-ZÀ-ÿ0-9\.,\-\s]+$/u', $localisation);
        
        if (!$isCoordinates && !$isAddress) {
            $errors['localisation'] = 'La localisation doit être une adresse ou des coordonnées valides (ex: Tunis ou 36.806389,10.182778).';
        } elseif (mb_strlen($localisation) > 250) {
            $errors['localisation'] = 'La localisation ne doit pas depasser 250 caracteres.';
        }
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
$selectedService = cleanInput((string) ($_GET['type'] ?? ''));
$sortOption = cleanInput((string) ($_GET['sort'] ?? 'date_desc'));
$sortLabels = [
    'date_desc' => 'Plus recentes',
    'date_asc' => 'Plus anciennes',
    'titre_asc' => 'Titre: A a Z',
    'titre_desc' => 'Titre: Z a A',
    'type_asc' => 'Type: A a Z',
    'prix_asc' => 'Prix: croissant',
    'prix_desc' => 'Prix: décroissant',
];
$visibleOffers = sortOffers($offers, $sortOption);
$exportOffers = sortOffers(filterOffersByService(filterOffersBySearch($offers, $searchTerm), $selectedService), 'date_desc');
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

// PREDICTION SYSTEM: Uses PredictionController for trending analysis
$predictionWindowDays = 30;
$predictedTop = [];
$predictionData = [];
$predictionInsight = '';

try {
    $predictionController = new PredictionController();
    $predictions = $predictionController->getPredictions($predictionWindowDays);
    
    if ($predictions['success'] && !empty($predictions['trendingOffers'])) {
        // Convert API response to display format
        foreach (array_slice($predictions['trendingOffers'], 0, 3) as $trend) {
            $predictedTop[] = [
                'id_offre' => (int)$trend['id'],
                'titre' => $trend['title'],
                'type_service' => $trend['service'],
                'localisation' => $trend['service'], // Use service as fallback
                'recent_count' => (int)$trend['candidatureCount'],
                'trending_score' => (float)$trend['trendingScore'],
                'days_since_post' => (int)$trend['daysSincePost'],
            ];
        }
        $predictionInsight = $predictions['insight'] ?? '';
    }
} catch (Exception $e) {
    // Fallback: Use basic logic if controller fails
    error_log("Prediction error: " . $e->getMessage());
    $recentCounts = [];
    $nowTs = time();
    $windowStart = $nowTs - ($predictionWindowDays * 24 * 3600);
    foreach ($allApplications as $appItem) {
        $offerId = (int) ($appItem['offer_id'] ?? $appItem['offre_id'] ?? $appItem['id_offre'] ?? 0);
        if ($offerId <= 0) continue;
        $dateStr = $appItem['date_candidature'] ?? $appItem['created_at'] ?? null;
        $ts = $dateStr ? strtotime((string)$dateStr) : 0;
        if ($ts >= $windowStart) {
            $recentCounts[$offerId] = ($recentCounts[$offerId] ?? 0) + 1;
        }
    }
    arsort($recentCounts);
    foreach (array_slice($recentCounts, 0, 3, true) as $oid => $count) {
        $found = array_values(array_filter($offers, static fn($o) => (int)($o['id_offre'] ?? 0) === (int)$oid));
        if (!empty($found)) {
            $it = reset($found);
            $predictedTop[] = [
                'id_offre' => (int)$oid,
                'titre' => $it['titre'] ?? '—',
                'type_service' => $it['type_service'] ?? '—',
                'localisation' => $it['localisation'] ?? '—',
                'recent_count' => (int)$count,
            ];
        }
    }
}

if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    OfferPdfExporter::download($exportOffers, [
        'generatedAt' => new DateTimeImmutable('now'),
        'title' => 'Gestion des offres',
        'searchLabel' => $searchTerm,
        'sortLabel' => $sortLabels[$sortOption] ?? 'Plus recentes',
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
        'prix_asc', 'prix_desc' => isset($offer['prix']) && $offer['prix'] !== '' ? (float) $offer['prix'] : null,
        default => !empty($offer['date_publication']) ? strtotime((string) $offer['date_publication']) : null,
    };
}

function sortOffers(array $offers, string $sort): array {
    $sort = in_array($sort, [
        'date_asc',
        'date_desc',
        'date_publication_asc',
        'date_publication_desc',
        'titre_asc',
        'titre_desc',
        'type_asc',
        'type_desc',
        'prix_asc',
        'prix_desc',
    ], true) ? $sort : 'date_desc';

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

        return (str_ends_with($sort, '_desc') || $sort === 'date_desc') ? -$comparison : $comparison;
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

function filterOffersByService(array $offers, string $service): array {
    $service = trim(mb_strtolower($service, 'UTF-8'));

    if ($service === '') {
        return array_values($offers);
    }

    return array_values(array_filter($offers, function (array $offer) use ($service): bool {
        $offerService = trim(mb_strtolower((string) ($offer['type_service'] ?? ''), 'UTF-8'));
        return $offerService === $service;
    }));
}

function buildOfferExportUrl(string $searchTerm, string $sortOption, string $selectedService = ''): string {
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

    if ($selectedService !== '') {
        $params['type'] = $selectedService;
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
    try {
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
    } catch (e) {
        console.error('Offers notifications/lang script error:', e);
    }
    </script>

<section class="action-bar reveal offers-page-toolbar" style="position:relative; z-index:20; overflow:visible;"> 
    <form class="search-box offers-toolbar-form" id="offersToolbarForm" method="GET" action="index.php">
        <input type="hidden" name="page" value="offers">
        <input type="search" id="offersSearchInput" name="q" value="<?php echo htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8'); ?>" placeholder="<?php echo app_text('Rechercher une offre...','...Search offers','...ابحث عن عرض'); ?>" autocomplete="off" aria-label="<?php echo app_text('Rechercher une offre','Search an offer','ابحث عن عرض'); ?>" style="min-width: 220px; max-width: 320px; min-height: 44px; padding: 0 14px; font-size: 0.95rem;">
        <select id="offersServiceFilter" name="type" aria-label="<?php echo app_text('Filtrer par service','Filter by service','تصفية حسب الخدمة'); ?>" style="min-width: 230px; max-width: 280px; min-height: 44px; padding-right: 34px; font-size: 0.92rem;">
            <option value=""><?php echo app_text('Tous les services','All services','كل الخدمات'); ?></option>
            <?php foreach ($typeServiceOptions as $option): ?>
                <option value="<?php echo htmlspecialchars((string) $option, ENT_QUOTES, 'UTF-8'); ?>" <?php echo strcasecmp($selectedService, (string) $option) === 0 ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars(ucfirst((string) $option), ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="sort" id="offerSortSelect" style="min-width: 230px; max-width: 280px; min-height: 44px; padding-right: 34px; font-size: 0.92rem;">
            <option value="date_desc" <?php echo $sortOption === 'date_desc' ? 'selected' : ''; ?>><?php echo app_text('Plus Récentes','Most recent','الأحدث'); ?></option>
            <option value="date_asc" <?php echo $sortOption === 'date_asc' ? 'selected' : ''; ?>><?php echo app_text('Plus Anciennes','Oldest','الأقدم'); ?></option>
            <option value="titre_asc" <?php echo $sortOption === 'titre_asc' ? 'selected' : ''; ?>><?php echo app_text('Titre: A à Z','Title: A to Z','العنوان: أ إلى ي'); ?></option>
            <option value="titre_desc" <?php echo $sortOption === 'titre_desc' ? 'selected' : ''; ?>><?php echo app_text('Titre: Z à A','Title: Z to A','العنوان: ي إلى أ'); ?></option>
            <option value="prix_asc" <?php echo $sortOption === 'prix_asc' ? 'selected' : ''; ?>><?php echo app_text('Prix: Croissant','Price: Low to High','السعر: من الأقل للأعلى'); ?></option>
            <option value="prix_desc" <?php echo $sortOption === 'prix_desc' ? 'selected' : ''; ?>><?php echo app_text('Prix: Décroissant','Price: High to Low','السعر: من الأعلى للأقل'); ?></option>
            <option value="type_asc" <?php echo $sortOption === 'type_asc' ? 'selected' : ''; ?>><?php echo app_text('Type: A à Z','Type: A to Z','النوع: أ إلى ي'); ?></option>
        </select>
        <noscript><button type="submit" class="outline-btn"><?php echo app_text('Trier','Sort','ترتيب'); ?></button></noscript>
    </form>

    <div class="export-bar" style="position:relative; z-index:30; overflow:visible; gap:10px; flex-wrap:nowrap;">
        <button type="button" class="outline-btn" id="offersStatsToggle" style="min-height: 44px; padding: 0 16px; font-size: 0.92rem;" data-text-open="<?php echo app_text('Statistiques','Statistics','الإحصاءات'); ?>" data-text-close="<?php echo app_text('Masquer les statistiques','Hide statistics','إخفاء الإحصاءات'); ?>"><?php echo app_text('Statistiques','Statistics','الإحصاءات'); ?></button>
        <a class="solid-btn" href="<?php echo htmlspecialchars(buildOfferExportUrl($searchTerm, $sortOption, $selectedService), ENT_QUOTES, 'UTF-8'); ?>" style="min-height: 44px; padding: 0 16px; font-size: 0.92rem;"><?php echo app_text('Exporter PDF','Export PDF','تصدير PDF'); ?></a>
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

<?php $viewApplicationsLabel = app_text('Voir condidature','View applications','عرض الطلبات'); ?>
<!-- Predictions: offers likely to receive most applications based on recent activity -->
<section class="admin-stats reveal offers-predictions-launcher" id="offersPredictionsLauncher">
    <button type="button" class="outline-btn" id="offersPredictionsToggle" data-text-open="<?php echo app_text('Voir prédictions','View predictions','عرض التنبؤات'); ?>" data-text-close="<?php echo app_text('Masquer prédictions','Hide predictions','إخفاء التنبؤات'); ?>" aria-controls="offersPredictions" aria-expanded="false">
        <?php echo app_text('Voir prédictions','View predictions','عرض التنبؤات'); ?>
    </button>
</section>

<section class="admin-stats reveal offers-predictions-panel" id="offersPredictions" hidden>
    <header style="display:flex; align-items:center; justify-content:space-between; gap:12px;">
        <div>
            <h3 style="margin:0; font-size:1rem;">🔥 <?php echo app_text('Prédictions (tendances)','Predictions (trending)','التنبؤات (الاتجاهات)'); ?></h3>
            <small class="muted"><?php echo sprintf(app_text('Offres les plus demandées — %d derniers jours','Most trending offers — last %d days','أكثر العروض طلبًا — آخر %d يومًا'), $predictionWindowDays); ?></small>
        </div>
    </header>
    <div class="offers-predictions-cards">
        <?php if (empty($predictedTop)): ?>
            <div class="offers-prediction-empty">
                <?php if (isset($totalRecentApplications) && $totalRecentApplications === 0): ?>
                    <p class="offers-prediction-empty__p"><?php echo app_text('Pas de candidatures dans la période sélectionnée.','No candidatures in the selected period.','لا توجد طلبات في الفترة المحددة.'); ?></p>
                    <p class="offers-prediction-empty__p is-secondary"><?php echo sprintf(app_text('Total candidatures (dernier %d jours): %d','Total candidatures (last %d days): %d','إجمالي الطلبات (آخر %d يومًا): %d'), $predictionWindowDays, $totalRecentApplications); ?></p>
                    <p class="offers-prediction-empty__p is-secondary"><?php echo app_text('Vous pouvez ajouter des candidatures de test via','You can add test candidatures via','يمكنك إضافة طلبات اختبار عبر'); ?> <a href="/goservice/test-predictions.php">test-predictions.php</a></p>
                <?php else: ?>
                    <p class="offers-prediction-empty__p"><?php echo app_text('Des candidatures existent mais aucune offre n a été identifiée comme tendance.','There are candidatures but no trending offers were identified.','هناك طلبات ولكن لم يتم تحديد أي عروض شائعة.'); ?></p>
                    <p class="offers-prediction-empty__p is-secondary"><?php echo sprintf(app_text('Total candidatures (dernier %d jours): %d','Total candidatures (last %d days): %d','إجمالي الطلبات (آخر %d يومًا): %d'), $predictionWindowDays, $totalRecentApplications); ?></p>
                    <?php if (isset($_GET['pred_debug']) && $_GET['pred_debug'] === '1'): ?>
                        <div class="offers-prediction-debug">
                            <strong><?php echo app_text('Détails par offre','Details per offer','تفاصيل لكل عرض'); ?>:</strong>
                            <ul class="offers-prediction-debug__list">
                                <?php foreach ($recentCountsPerOffer as $oid => $cnt): ?>
                                    <li><?php echo htmlspecialchars((string)$oid); ?>: <?php echo (int)$cnt; ?> <?php echo app_text('candidatures','applications','الطلبات'); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php else: ?>
                        <p class="offers-prediction-empty__p is-hint"><?php echo app_text('Pour plus de détails, ajoutez ?pred_debug=1 à l URL','For more details, add ?pred_debug=1 to the URL','لمزيد من التفاصيل، أضف ?pred_debug=1 إلى عنوان URL'); ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <?php foreach ($predictedTop as $index => $p): ?>
                <div class="offers-prediction-card">
                    <!-- Rank Badge -->
                    <div class="offers-prediction-card__rank" style="background:<?php echo $index === 0 ? '#FFD700' : ($index === 1 ? '#C0C0C0' : '#CD7F32'); ?>;">
                        <?php echo match($index) { 0 => '🥇', 1 => '🥈', 2 => '🥉', default => $index + 1 }; ?>
                    </div>
                    
                    <!-- Content -->
                    <div class="offers-prediction-card__title"><?php echo htmlspecialchars($p['titre'], ENT_QUOTES, 'UTF-8'); ?></div>
                    
                    <div class="offers-prediction-card__meta">
                        <span class="offers-prediction-tag"><?php echo htmlspecialchars($p['type_service'] ?? '—', ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    
                    <!-- Stats Row -->
                    <div class="offers-prediction-stat-row">
                        <div>
                            <div class="offers-prediction-stat__value"><?php echo (int)$p['recent_count']; ?></div>
                            <div class="offers-prediction-stat__label"><?php echo app_text('candidatures','applications','الطلبات'); ?></div>
                        </div>
                        <?php if (isset($p['trending_score'])): ?>
                        <div>
                            <div class="offers-prediction-stat__value is-accent"><?php echo number_format((float)$p['trending_score'], 2); ?></div>
                            <div class="offers-prediction-stat__label"><?php echo app_text('score/jour','score/day','النقاط/اليوم'); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (isset($p['days_since_post'])): ?>
                        <div>
                            <div class="offers-prediction-stat__value is-muted"><?php echo (int)$p['days_since_post']; ?></div>
                            <div class="offers-prediction-stat__label"><?php echo app_text('jours','days','الأيام'); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Action Button -->
                    <a class="small-btn offers-prediction-card__action" href="?page=offer_applications&offer_id=<?php echo urlencode((string)$p['id_offre']); ?>"><?php echo $viewApplicationsLabel; ?></a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($predictionInsight)): ?>
    <div class="offers-prediction-insight">
        <strong class="offers-prediction-insight__title">💡 <?php echo app_text('Insight','Insight','رؤية'); ?>:</strong>
        <p class="offers-prediction-insight__text"><?php echo htmlspecialchars($predictionInsight, ENT_QUOTES, 'UTF-8'); ?></p>
    </div>
    <?php endif; ?>
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

.offers-predictions-launcher {
    display: flex;
    justify-content: flex-start;
}

#offersPredictions[hidden] {
    display: none !important;
}

#offersPredictions .small-btn {
    display: inline-flex;
    width: auto;
    min-width: 200px;
    min-height: 40px;
    padding: 10px 14px 0;
    align-items: center;
    justify-content: center;
    text-align: center;
    line-height: 1.2;
    font-size: 0.95rem;
    margin-top: 8px;
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
            <tbody id="offersTableBody">
                <?php if (empty($visibleOffers)): ?>
                    <tr>
                        <td colspan="7">Aucune offre trouvée.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($visibleOffers as $offer): ?>
                        <?php
                            $searchBlob = (string) ($offer['titre'] ?? '');
                        ?>
                        <tr data-offer-searchable="1" data-offer-service="<?php echo htmlspecialchars((string) ($offer['type_service'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" data-search-text="<?php echo htmlspecialchars($searchBlob, ENT_QUOTES, 'UTF-8'); ?>">
                            <td><?php echo htmlspecialchars($offer['titre'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($offer['type_service'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($offer['localisation'] ?: 'N/A', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(formatDate($offer['date_publication']), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(formatDate($offer['date_expiration']), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars(ucfirst($offer['statut']), ENT_QUOTES, 'UTF-8'); ?></td>
                            <td class="admin-tools">
                                <a href="?page=offer_applications&offer_id=<?php echo urlencode((string) ($offer['id_offre'] ?? '')); ?>" class="small-btn"><?php echo $viewApplicationsLabel; ?></a>
                                <?php
                                    // Use front-office URL (remove /view/back from the path)
                                    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                                    $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? '');
                                    // Navigate to root and then to index.php (front office entry point)
                                    $rootPath = rtrim(dirname(dirname(dirname(__DIR__))), '\\/');
                                    $base =  $host;
                                    $offerUrl = 'http://localhost/goservice/view/front/index.php?page=offre&sort=date_des&q=' . urlencode((string)($offer['titre'] ?? ''));
                                    $offerTitle = htmlspecialchars((string)($offer['titre'] ?? ''), ENT_QUOTES, 'UTF-8');
                                    $offerType = htmlspecialchars((string)($offer['type_service'] ?? 'Service'), ENT_QUOTES, 'UTF-8');
                                    $offerLocation = htmlspecialchars((string)($offer['localisation'] ?? 'N/A'), ENT_QUOTES, 'UTF-8');
                                    $offerPrice = isset($offer['prix']) ? number_format((float)$offer['prix'], 2, '.', '') : 'N/A';
                                ?>
                                <button type="button" class="small-btn" onclick="openLinkedinShareModal('<?php echo addslashes($offerTitle); ?>', '<?php echo addslashes($offerType); ?>', '<?php echo addslashes($offerLocation); ?>', '<?php echo $offerPrice; ?>', '<?php echo addslashes($offerUrl); ?>');"><?php echo app_text('Partager','Share','مشاركة'); ?></button>
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
                    <tr id="offersFilterEmpty" hidden>
                        <td colspan="7"><?php echo app_text('Aucune offre trouvée pour ce filtre.','No offers match this filter.','لا توجد عروض مطابقة لهذا الفلتر.'); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
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
                    <div style="display:flex; gap:8px; align-items:center;">
                        <input type="text" name="localisation" id="localisationField" placeholder="<?php echo app_text('Localisation','Location','الموقع'); ?>" value="<?php echo htmlspecialchars($formData['localisation'], ENT_QUOTES, 'UTF-8'); ?>" aria-invalid="<?php echo $fieldErrors['localisation'] !== '' ? 'true' : 'false'; ?>" style="flex:1;">
                        <button type="button" id="chooseOnMapBtn" class="outline-btn" title="<?php echo app_text('Choisir sur la carte','Choose on map','اختر على الخريطة'); ?>"><?php echo app_text('Carte','Map','خريطة'); ?></button>
                    </div>
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

<!-- Leaflet for map-based localisation picker -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- LinkedIn Share Modal -->
<div class="modal-overlay" id="linkedinShareModal" role="dialog" aria-modal="true" aria-labelledby="linkedinShareTitle" hidden>
    <div class="modal" role="document" style="max-width:720px;">
        <button type="button" class="modal-close top-right" aria-label="Fermer" id="linkedinShareClose">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M18 6L6 18M6 6l12 12" stroke="#142738" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
        <header class="modal-header">
            <div>
                <h2 id="linkedinShareTitle"><?php echo app_text('Partager sur LinkedIn','Share on LinkedIn','مشاركة على لينكدإن'); ?></h2>
                <p class="muted" style="margin-top:6px;"><?php echo app_text('Partagez cette offre professionnellement sur LinkedIn','Share this offer professionally on LinkedIn','شارك هذا العرض بشكل احترافي على لينكدإن'); ?></p>
            </div>
        </header>
        <div class="modal-body" style="padding:18px;">
            <div style="background:#f5f7fa; padding:16px; border-radius:12px; border:1px solid rgba(20,39,56,.1); margin-bottom:16px;">
                <textarea id="linkedinMessageText" style="width:100%; min-height:240px; padding:12px; border-radius:10px; border:1px solid rgba(148,163,184,.42); font-family:inherit; font-size:0.95rem; resize:vertical;" readonly></textarea>
            </div>
            <div style="display:flex; gap:12px; margin-bottom:16px;">
                <button type="button" id="linkedinCopyBtn" class="outline-btn" style="flex:1;"><?php echo app_text('Copier le message','Copy message','نسخ الرسالة'); ?></button>
                <button type="button" id="linkedinCopyLinkBtn" class="outline-btn" style="flex:1;"><?php echo app_text('Copier le lien','Copy link','نسخ الرابط'); ?></button>
            </div>
            <div style="text-align:center; margin-top:16px;">
                <button type="button" id="linkedinOpenBtn" class="solid-btn" style="display:inline-block; font-size:1rem;"><?php echo app_text('🔗 Ouvrir LinkedIn & Copier','🔗 Open LinkedIn & Copy','🔗 فتح لينكدإن والنسخ'); ?></button>
            </div>
            <div id="linkedinCopyFeedback" style="margin-top:12px; padding:10px; border-radius:8px; background:rgba(76,175,80,0.1); color:#4CAF50; text-align:center; display:none; font-weight:600;" hidden></div>
        </div>
    </div>
</div>

<!-- Map picker modal (Leaflet) -->
<div class="modal-overlay" id="localisationMapModal" role="dialog" aria-modal="true" aria-labelledby="localisationMapTitle" hidden>
    <div class="modal" role="document" style="max-width:920px; width:95%;">
        <button type="button" class="modal-close top-right" aria-label="Fermer" id="localisationMapClose">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M18 6L6 18M6 6l12 12" stroke="#142738" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
        <header class="modal-header">
            <div>
                <h2 id="localisationMapTitle" style="font-size:2rem; line-height:1.15;"><?php echo app_text('Choisir la localisation','Choose localisation','اختر الموقع'); ?></h2>
                <p class="muted" style="font-size:1.2rem; line-height:1.45;"><?php echo app_text('Cliquez sur la carte pour définir la localisation. Enregistrez pour appliquer aux coordonnées du champ.','Click on the map to pick a location. Save to apply coordinates to the field.','انقر على الخريطة لتحديد الموقع. احفظ لتطبيق الإحداثيات على الحقل.'); ?></p>
            </div>
            <div class="modal-meta"><small style="font-size:1rem;"><?php echo (new DateTimeImmutable('now'))->format('d/m/Y H:i'); ?></small></div>
        </header>
        <div class="modal-body" style="padding:12px 18px; flex:1; overflow:hidden; min-height:480px;">
            <div style="display:flex; gap:10px; align-items:center; margin:0 0 12px 0;">
                <input type="text" id="localisationMapSearchInput" placeholder="<?php echo app_text('Rechercher un lieu, une ville, une adresse...','Search a place, city, address...','ابحث عن مكان أو مدينة أو عنوان...'); ?>" style="flex:1; min-height:46px; font-size:1.04rem; padding:0 14px; border-radius:10px; border:1px solid rgba(20,39,56,.2);">
                <button type="button" class="outline-btn" id="localisationMapSearchBtn" style="min-height:46px; font-size:1rem; padding:0 14px;"><?php echo app_text('Rechercher','Search','بحث'); ?></button>
            </div>
            <div id="localisationMapSearchStatus" class="muted" style="min-height:24px; margin:0 0 10px 2px; font-size:0.98rem;"></div>
            <div id="localisationMapContainer" style="width:100%; height:100%; min-height:480px; border-radius:12px; overflow:hidden; border:1px solid rgba(20,39,56,.06); background:#e8eef5; position:relative; z-index:1;"></div>
        </div>
        <footer class="modal-footer" style="display:flex; gap:8px; justify-content:flex-end;">
            <button type="button" class="outline-btn" id="localisationMapCancel" style="font-size:1rem; min-height:44px;"><?php echo app_text('Annuler','Cancel','إلغاء'); ?></button>
            <button type="button" class="solid-btn" id="localisationMapSave" style="font-size:1rem; min-height:44px;"><?php echo app_text('Enregistrer la localisation','Save location','حفظ الموقع'); ?></button>
        </footer>
    </div>
</div>
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
            var predictionsToggle = document.getElementById('offersPredictionsToggle') || document.querySelector('#offersPredictionsToggle');
            var predictionsPanel = document.getElementById('offersPredictions') || document.querySelector('#offersPredictions');
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
                            var colors = ['#1F2937', '#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#06B6D4', '#EC4899', '#6366F1', '#14B8A6', '#F97316', '#6B7280', '#0EA5E9', '#D97706', '#DC2626', '#7C3AED', '#059669', '#64748B'];
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
                                        backgroundColor: ['#10B981', '#EF4444'],
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
                                        backgroundColor: '#3B82F6',
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

            if (predictionsToggle && predictionsPanel) {
                function openPredictionsPanel() {
                    predictionsPanel.removeAttribute('hidden');
                    predictionsToggle.textContent = predictionsToggle.getAttribute('data-text-close') || 'Masquer prédictions';
                    predictionsToggle.setAttribute('aria-expanded', 'true');
                    try { predictionsPanel.scrollIntoView({ behavior: 'smooth', block: 'start' }); } catch (e) {}
                }

                function closePredictionsPanel() {
                    predictionsPanel.setAttribute('hidden', 'hidden');
                    predictionsToggle.textContent = predictionsToggle.getAttribute('data-text-open') || 'Voir prédictions';
                    predictionsToggle.setAttribute('aria-expanded', 'false');
                }

                closePredictionsPanel();

                predictionsToggle.addEventListener('click', function() {
                    if (predictionsPanel.hasAttribute('hidden')) {
                        openPredictionsPanel();
                    } else {
                        closePredictionsPanel();
                    }
                });
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

<script>
(function() {
    var offersToolbarForm = document.getElementById('offersToolbarForm');
    var offersSearchInput = document.getElementById('offersSearchInput');
    var offersServiceFilter = document.getElementById('offersServiceFilter');
    var offersTableBody = document.getElementById('offersTableBody');
    var offersFilterEmpty = document.getElementById('offersFilterEmpty');

    function normalizeOfferSearchValue(value) {
        return String(value || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }

    function syncOffersToolbarUrl(searchValue, serviceValue) {
        var params = new URLSearchParams(window.location.search);
        params.set('page', 'offers');

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

    function applyOffersTableFilters() {
        if (!offersTableBody) return;

        var searchValue = offersSearchInput ? offersSearchInput.value : '';
        var serviceValue = offersServiceFilter ? offersServiceFilter.value : '';
        var query = normalizeOfferSearchValue(searchValue);
        var serviceQuery = normalizeOfferSearchValue(serviceValue);

        var rows = offersTableBody.querySelectorAll('tr[data-offer-searchable="1"]');
        var visible = 0;

        rows.forEach(function(row) {
            var hay = normalizeOfferSearchValue(row.getAttribute('data-search-text') || '');
            var svc = normalizeOfferSearchValue(row.getAttribute('data-offer-service') || '');
            var matchesSearch = query === '' || hay.indexOf(query) !== -1;
            var matchesService = serviceQuery === '' || svc === serviceQuery;
            var show = matchesSearch && matchesService;

            row.hidden = !show;
            if (show) visible++;
        });

        if (offersFilterEmpty) {
            offersFilterEmpty.hidden = rows.length === 0 || visible > 0;
        }

        syncOffersToolbarUrl(searchValue, serviceValue);
    }

    if (offersToolbarForm) {
        offersToolbarForm.addEventListener('submit', function(e) {
            e.preventDefault();
        });
    }

    if (offersSearchInput) {
        offersSearchInput.addEventListener('input', applyOffersTableFilters);
        offersSearchInput.addEventListener('search', applyOffersTableFilters);
    }

    if (offersServiceFilter) {
        offersServiceFilter.addEventListener('change', applyOffersTableFilters);
    }

    if (offersTableBody && offersTableBody.querySelector('tr[data-offer-searchable="1"]')) {
        applyOffersTableFilters();
    }
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

body.admin-body.dark .module-table thead th {
    background: #1a2836;
    color: #e8eef5;
    border-bottom-color: rgba(255, 255, 255, 0.14);
}

body.admin-body.dark .module-table tbody tr:hover {
    background: rgba(238, 88, 40, 0.14);
}

/* Predictions panel: theme-aware cards and insight */
.offers-predictions-cards {
    margin-top: 12px;
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.offers-prediction-empty {
    padding: 14px;
    border-radius: 12px;
    background: #fff;
    border: 1px solid rgba(20, 39, 56, 0.06);
    width: 100%;
}

.offers-prediction-empty__p {
    margin: 0;
    color: #555;
}

.offers-prediction-empty__p.is-secondary {
    margin: 8px 0 0 0;
    font-size: 0.9rem;
    color: #666;
}

.offers-prediction-empty__p.is-hint {
    margin: 8px 0 0 0;
    font-size: 0.85rem;
    color: #666;
}

.offers-prediction-debug {
    margin-top: 8px;
}

.offers-prediction-debug__list {
    margin: 6px 0 0 16px;
    color: #444;
}

.offers-prediction-card {
    flex: 1;
    min-width: 240px;
    padding: 16px;
    border-radius: 12px;
    background: linear-gradient(135deg, #fff 0%, #f8fafb 100%);
    border: 1px solid rgba(20, 39, 56, 0.08);
    position: relative;
    overflow: hidden;
}

.offers-prediction-card__rank {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    color: #fff;
    font-weight: bold;
    font-size: 16px;
}

.offers-prediction-card__title {
    font-weight: 700;
    font-size: 0.98rem;
    margin-bottom: 6px;
    margin-right: 40px;
    color: #142738;
}

.offers-prediction-card__meta {
    font-size: 0.85rem;
    color: #555;
    margin-bottom: 10px;
}

.offers-prediction-tag {
    display: inline-block;
    margin-right: 8px;
    padding: 2px 6px;
    background: #e8eef5;
    border-radius: 4px;
    font-size: 0.8rem;
    color: #333;
}

.offers-prediction-stat-row {
    display: flex;
    gap: 12px;
    margin-bottom: 10px;
}

.offers-prediction-stat__value {
    font-weight: 700;
    color: #142738;
    font-size: 1.2rem;
}

.offers-prediction-stat__value.is-accent {
    color: #007bff;
}

.offers-prediction-stat__value.is-muted {
    color: #555;
}

.offers-prediction-stat__label {
    font-size: 0.75rem;
    color: #888;
}

.offers-prediction-card__action {
    display: inline-block;
    margin-top: 8px;
}

.offers-prediction-insight {
    margin-top: 16px;
    padding: 12px 14px;
    border-radius: 10px;
    background: #f0f8ff;
    border-left: 3px solid #007bff;
}

.offers-prediction-insight__title {
    color: #0056b3;
    font-size: 0.9rem;
}

.offers-prediction-insight__text {
    margin: 4px 0 0 0;
    color: #0b4f8c;
    font-size: 0.85rem;
}

body.admin-body.dark .offers-prediction-empty {
    background: rgba(255, 255, 255, 0.06);
    border-color: rgba(255, 255, 255, 0.1);
}

body.admin-body.dark .offers-prediction-empty__p,
body.admin-body.dark .offers-prediction-debug,
body.admin-body.dark .offers-prediction-debug__list {
    color: #c5d0e0;
}

body.admin-body.dark .offers-prediction-empty a {
    color: #8ec5ff;
}

body.admin-body.dark .offers-prediction-card {
    background: linear-gradient(145deg, rgba(30, 45, 60, 0.98) 0%, rgba(18, 30, 45, 0.99) 100%);
    border-color: rgba(255, 255, 255, 0.1);
}

body.admin-body.dark .offers-prediction-card__title {
    color: #f0f4f8;
}

body.admin-body.dark .offers-prediction-card__meta {
    color: #a8b8cc;
}

body.admin-body.dark .offers-prediction-tag {
    background: rgba(255, 255, 255, 0.1);
    color: #e2eaf4;
}

body.admin-body.dark .offers-prediction-stat__value {
    color: #f0f4f8;
}

body.admin-body.dark .offers-prediction-stat__value.is-accent {
    color: #8ec5ff;
}

body.admin-body.dark .offers-prediction-stat__value.is-muted {
    color: #b8c8dc;
}

body.admin-body.dark .offers-prediction-stat__label {
    color: #8a9bad;
}

body.admin-body.dark .offers-prediction-insight {
    background: rgba(0, 123, 255, 0.14);
    border-left-color: #4a9eff;
}

body.admin-body.dark .offers-prediction-insight__title,
body.admin-body.dark .offers-prediction-insight__text {
    color: #d6ebff;
}

/* Ensure action buttons in the Actions column appear on a single row */
.admin-tools {
    display: flex;
    gap: 8px;
    align-items: center;
    flex-wrap: nowrap;
}
.admin-tools form {
    margin: 0;
    display: inline-flex;
}
.admin-tools .small-btn,
.admin-tools .danger-btn,
.admin-tools .outline-btn,
.admin-tools .solid-btn,
.admin-tools .success-btn,
.admin-tools .action-pill {
    margin: 0;
    padding: 8px 12px !important;
    font-size: 12px !important;
    min-height: 36px !important;
    white-space: nowrap;
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
    align-items: center;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 12px;
    border-radius: 14px;
    background: rgba(20, 39, 56, 0.04);
}

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

body.admin-body.dark .status-en_attente {
    background: rgba(255, 209, 102, 0.22);
    color: #ffe9a8;
    border-color: rgba(255, 209, 102, 0.5);
}

body.admin-body.dark .status-acceptee {
    background: rgba(129, 199, 132, 0.22);
    color: #b9f6b0;
    border-color: rgba(129, 199, 132, 0.45);
}

body.admin-body.dark .status-refusee {
    background: rgba(255, 138, 101, 0.2);
    color: #ffc4b0;
    border-color: rgba(255, 138, 101, 0.45);
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

<script>
(function(){
    var chooseBtn = document.getElementById('chooseOnMapBtn');
    var modal = document.getElementById('localisationMapModal');
    var mapContainer = document.getElementById('localisationMapContainer');
    var closeBtn = document.getElementById('localisationMapClose');
    var saveBtn = document.getElementById('localisationMapSave');
    var cancelBtn = document.getElementById('localisationMapCancel');
    var searchInput = document.getElementById('localisationMapSearchInput');
    var searchBtn = document.getElementById('localisationMapSearchBtn');
    var searchStatus = document.getElementById('localisationMapSearchStatus');
    var locField = document.getElementById('localisationField');

    if (!chooseBtn || !modal || !mapContainer || !locField) return;

    var mapInstance = null;
    var marker = null;
    var selectedLatLng = null;
    var mapInitialized = false;

    function setSearchStatus(msg, isError) {
        if (!searchStatus) return;
        searchStatus.textContent = msg || '';
        searchStatus.style.color = isError ? '#b42318' : '#516173';
    }

    function setMarkerAt(lat, lng, zoom) {
        if (!mapInstance) return;
        var target = L.latLng(lat, lng);
        if (marker) {
            marker.setLatLng(target);
        } else {
            marker = L.marker(target, { draggable: false }).addTo(mapInstance);
        }
        selectedLatLng = { lat: lat, lng: lng };
        mapInstance.setView(target, zoom || 13);
    }

    function initMap() {
        if (mapInitialized || mapInstance) return;
        mapInitialized = true;
        
        try {
            if (typeof L === 'undefined') {
                console.error('Leaflet not loaded');
                return;
            }
            
            // Clear any previous map
            if (mapContainer.innerHTML) {
                mapContainer.innerHTML = '';
            }
            
            // Force container dimensions
            mapContainer.style.width = '100%';
            mapContainer.style.height = '480px';
            mapContainer.style.minHeight = '480px';
            mapContainer.style.display = 'block';
            
            // Small delay to ensure CSS is applied
            setTimeout(function(){
                try {
                    // Initialize map centered on Tunisia
                    mapInstance = L.map(mapContainer, {
                        center: [33.8869, 9.5375],
                        zoom: 9,
                        zoomControl: true,
                        scrollWheelZoom: true
                    });
                    
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '&copy; OpenStreetMap contributors',
                        detectRetina: true,
                        crossOrigin: 'anonymous'
                    }).addTo(mapInstance);
                    
                    // Ensure tiles are visible
                    mapInstance.on('tileerror', function(e) {
                        console.warn('Tile error:', e);
                    });

                    mapInstance.on('click', function(e){
                        var lat = e.latlng.lat;
                        var lng = e.latlng.lng;
                        setMarkerAt(lat, lng, mapInstance.getZoom() < 13 ? 13 : mapInstance.getZoom());
                        setSearchStatus('<?php echo addslashes(app_text('Position sélectionnée sur la carte.','Position selected on map.','تم تحديد الموقع على الخريطة.')); ?>', false);
                    });
                    
                    // If localisation field already has coordinates, show marker
                    var val = (locField.value || '').trim();
                    if (val && val.match(/^\s*-?\d+(?:\.\d+)?\s*,\s*-?\d+(?:\.\d+)?\s*$/)) {
                        var parts = val.split(',');
                        var lat = parseFloat(parts[0]);
                        var lng = parseFloat(parts[1]);
                        if (!isNaN(lat) && !isNaN(lng)) {
                            setMarkerAt(lat, lng, 13);
                        }
                    }
                    
                    // Trigger resize to render properly
                    mapInstance.invalidateSize(true);
                } catch(e) {
                    console.error('Map init error 2:', e);
                }
            }, 100);
        } catch(e) {
            console.error('Map init error:', e);
        }
    }

    function openModal() {
        modal.removeAttribute('hidden');
        document.body.classList.add('modal-open');
        
        // Initialize map with proper timing
        setTimeout(function(){
            initMap();
            // Call invalidateSize again after a delay to ensure tiles render
            setTimeout(function(){
                if (mapInstance) {
                    mapInstance.invalidateSize(true);
                }
            }, 300);
        }, 200);
    }

    function closeModal() {
        modal.setAttribute('hidden','');
        document.body.classList.remove('modal-open');
    }

    function searchPlace() {
        var q = (searchInput && searchInput.value ? searchInput.value : '').trim();
        if (!q) {
            setSearchStatus('<?php echo addslashes(app_text('Entrez un lieu à rechercher.','Enter a place to search.','اكتب مكانًا للبحث.')); ?>', true);
            return;
        }
        if (typeof fetch !== 'function') {
            setSearchStatus('<?php echo addslashes(app_text('Recherche indisponible sur ce navigateur.','Search unavailable in this browser.','البحث غير متاح في هذا المتصفح.')); ?>', true);
            return;
        }

        setSearchStatus('<?php echo addslashes(app_text('Recherche en cours...','Searching...','جاري البحث...')); ?>', false);
        var url = 'https://nominatim.openstreetmap.org/search?format=json&limit=1&q=' + encodeURIComponent(q);
        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function(resp){ return resp.json(); })
            .then(function(items){
                if (!Array.isArray(items) || items.length === 0) {
                    setSearchStatus('<?php echo addslashes(app_text('Aucun résultat trouvé.','No result found.','لم يتم العثور على نتائج.')); ?>', true);
                    return;
                }
                var first = items[0];
                var lat = parseFloat(first.lat);
                var lon = parseFloat(first.lon);
                if (isNaN(lat) || isNaN(lon)) {
                    setSearchStatus('<?php echo addslashes(app_text('Résultat invalide reçu.','Invalid result received.','تم استلام نتيجة غير صالحة.')); ?>', true);
                    return;
                }
                setMarkerAt(lat, lon, 14);
                setSearchStatus('<?php echo addslashes(app_text('Lieu trouvé. Vous pouvez ajuster en cliquant sur la carte.','Place found. You can adjust by clicking on map.','تم العثور على المكان. يمكنك التعديل بالنقر على الخريطة.')); ?>', false);
            })
            .catch(function(){
                setSearchStatus('<?php echo addslashes(app_text('Erreur pendant la recherche.','Search error.','حدث خطأ أثناء البحث.')); ?>', true);
            });
    }

    chooseBtn.addEventListener('click', function(){ openModal(); });
    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    if (searchBtn) searchBtn.addEventListener('click', searchPlace);
    if (searchInput) {
        searchInput.addEventListener('keydown', function(e){
            if (e.key === 'Enter') {
                e.preventDefault();
                searchPlace();
            }
        });
    }

    if (saveBtn) saveBtn.addEventListener('click', function(){
        if (selectedLatLng) {
            locField.value = (selectedLatLng.lat.toFixed(6) + ',' + selectedLatLng.lng.toFixed(6));
        } else if (marker && marker.getLatLng) {
            var p = marker.getLatLng();
            locField.value = (p.lat.toFixed(6) + ',' + p.lng.toFixed(6));
        }
        closeModal();
    });
})();
</script>

<script>
// LinkedIn Share Modal Handler
function openLinkedinShareModal(offerTitle, offerType, offerLocation, offerPrice, offerUrl) {
    var modal = document.getElementById('linkedinShareModal');
    var messageText = document.getElementById('linkedinMessageText');
    var copyBtn = document.getElementById('linkedinCopyBtn');
    var copyLinkBtn = document.getElementById('linkedinCopyLinkBtn');
    var openBtn = document.getElementById('linkedinOpenBtn');
    var feedback = document.getElementById('linkedinCopyFeedback');
    var closeBtn = document.getElementById('linkedinShareClose');
    
    if (!modal || !messageText) return;
    
    // Generate professional message
    var message = "🎯 Nous recrutons !\n\n" +
        "Poste : " + offerTitle + "\n" +
        "Type : " + offerType + "\n" +
        "Localisation : " + offerLocation + "\n" +
        "Budget : " + offerPrice + " TND\n\n" +
        "Rejoignez notre équipe ! Consultez l'offre complète et postulez via le lien ci-dessous.\n\n" +
        "👉 " + offerUrl + "\n\n" +
        "#Recrutement #Embauche #Offre #Emploi";
    
    messageText.value = message;
    messageText.textContent = message;
    
    // Auto copy message to clipboard and open LinkedIn
    function copyToClipboardAndOpen() {
        var textarea = document.createElement('textarea');
        textarea.value = message;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
            feedback.textContent = "✓ Message copié ! LinkedIn s'ouvre...";
            feedback.removeAttribute('hidden');
            feedback.style.display = 'block';
        } catch(e) {
            console.log('Copy failed, but opening LinkedIn anyway');
        }
        document.body.removeChild(textarea);
        
        // Open LinkedIn after copying
        setTimeout(function(){
            var linkedinShareUrl = 'https://www.linkedin.com/sharing/share-offsite/?url=' + encodeURIComponent(offerUrl);
            window.open(linkedinShareUrl, 'linkedin_share', 'width=600,height=600');
        }, 300);
    }
    
    // Set LinkedIn share link
    openBtn.href = 'javascript:void(0)';
    openBtn.onclick = function(e){
        e.preventDefault();
        copyToClipboardAndOpen();
        return false;
    };
    
    // Copy message to clipboard
    copyBtn.onclick = function() {
        var textarea = document.createElement('textarea');
        textarea.value = message;
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
            feedback.textContent = "✓ Message copié !";
            feedback.removeAttribute('hidden');
            feedback.style.display = 'block';
            setTimeout(function() { feedback.setAttribute('hidden', ''); feedback.style.display = 'none'; }, 3000);
        } catch(e) {
            alert('Erreur lors de la copie');
        }
        document.body.removeChild(textarea);
    };
    
    // Copy link to clipboard
    copyLinkBtn.onclick = function() {
        var textarea = document.createElement('textarea');
        textarea.value = offerUrl;
        document.body.appendChild(textarea);
        textarea.select();
        try {
            document.execCommand('copy');
            feedback.textContent = "✓ Lien copié !";
            feedback.removeAttribute('hidden');
            feedback.style.display = 'block';
            setTimeout(function() { feedback.setAttribute('hidden', ''); feedback.style.display = 'none'; }, 3000);
        } catch(e) {
            alert('Erreur lors de la copie');
        }
        document.body.removeChild(textarea);
    };
    
    // Close modal
    function closeModal() {
        modal.setAttribute('hidden', '');
        document.body.classList.remove('modal-open');
    }
    
    if (closeBtn) closeBtn.onclick = closeModal;
    
    // Open modal
    modal.removeAttribute('hidden');
    document.body.classList.add('modal-open');
    
    // Auto-focus on the primary action
    setTimeout(function(){
        if (openBtn) openBtn.focus();
    }, 100);
}

// Close modal when clicking overlay
document.addEventListener('click', function(e) {
    var modal = document.getElementById('linkedinShareModal');
    if (modal && !modal.hasAttribute('hidden') && e.target === modal.parentElement) {
        modal.setAttribute('hidden', '');
        document.body.classList.remove('modal-open');
    }
});
</script>