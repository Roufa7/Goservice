<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../model/Offer.php';
require_once __DIR__ . '/../../../model/Candidature.php';
require_once __DIR__ . '/../../../controller/OfferController.php';
require_once __DIR__ . '/../../../controller/CandidatureController.php';

$offerController = new OfferController();
$candidatureController = new CandidatureController();

//Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_application_status' && !empty($_POST['application_id']) && !empty($_POST['status'])) {
    $allowedStatuses = ['en attente', 'en_attente', 'acceptee', 'refusee', 'rejetee'];
    $status = (string) $_POST['status'];

    if (in_array($status, $allowedStatuses, true)) {
        $candidatureController->updateApplicationStatus((int) $_POST['application_id'], $status);
        $offerIdForRedirect = isset($_GET['offer_id']) && is_numeric($_GET['offer_id']) ? (int) $_GET['offer_id'] : 0;
        header('Location: ?page=offer_applications&offer_id=' . $offerIdForRedirect . '&updated=1');
        exit;
    }

    $offerIdForRedirect = isset($_GET['offer_id']) && is_numeric($_GET['offer_id']) ? (int) $_GET['offer_id'] : 0;
    header('Location: ?page=offer_applications&offer_id=' . $offerIdForRedirect . '&updated=0');
    exit;
}

//Récupération des données
$offerId = isset($_GET['offer_id']) && is_numeric($_GET['offer_id']) ? (int) $_GET['offer_id'] : 0;
$offer = $offerId > 0 ? $offerController->getOffer($offerId) : null;
$applications = $offerId > 0 ? $candidatureController->getApplicationsByOffer($offerId) : [];
$updateSuccess = isset($_GET['updated']) && $_GET['updated'] === '1';

function formatDate(?string $value): string {
    return $value ? date('d/m/Y', strtotime($value)) : 'N/A';
}

function formatApplicationStatus(string $status): string {
    return match($status) {
        'acceptee' => 'Acceptée',
        'accepted' => 'Acceptée',
        'refusee' => 'Refusée',
        'rejetee' => 'Refusée',
        'en_attente' => 'En attente',
        default => 'En attente'
    };
}

function normalizeApplicationStatus(string $status): string {
    $normalized = strtolower(trim($status));

    return match ($normalized) {
        'accepted', 'acceptee' => 'acceptee',
        'rejected', 'refusee', 'rejetee' => 'refusee',
        default => 'en_attente'
    };
}
?>

<section class="admin-panel reveal offer-applications-page">
    <a class="back-link" href="?page=offers">← Retour aux offres</a>
    <h2 class="page-title">Candidatures par offre</h2>

    <?php if ($updateSuccess): ?>
        <div class="notice notice-success">Statut de candidature mis à jour avec succès.</div>
    <?php endif; ?>

    <?php if ($offer === null): ?>
        <div class="no-results">Offre introuvable ou identifiant invalide.</div>
    <?php else: ?>
        <div class="offer-summary-card">
            <div class="offer-summary-main">
                <span class="section-badge offer-type-badge"><?php echo htmlspecialchars((string) ($offer['type_service'] ?? 'Service'), ENT_QUOTES, 'UTF-8'); ?></span>
                <h3><?php echo htmlspecialchars((string) ($offer['titre'] ?? '(Sans titre)'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?php echo htmlspecialchars((string) ($offer['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                <div class="offer-summary-stats">
                    <div class="summary-stat">
                        <span>Statut</span>
                        <strong><?php echo htmlspecialchars(ucfirst((string) ($offer['statut'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></strong>
                    </div>
                    <div class="summary-stat">
                        <span>Prix</span>
                        <strong><?php echo htmlspecialchars(isset($offer['prix']) && $offer['prix'] !== null ? number_format((float) $offer['prix'], 2, '.', ' ') . ' TND' : 'N/A', ENT_QUOTES, 'UTF-8'); ?></strong>
                    </div>
                    <div class="summary-stat">
                        <span>Candidatures</span>
                        <strong><?php echo htmlspecialchars((string) count($applications), ENT_QUOTES, 'UTF-8'); ?></strong>
                    </div>
                </div>
            </div>

            <div class="offer-summary-meta">
                <div><strong>Localisation :</strong> <?php echo htmlspecialchars((string) ($offer['localisation'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></div>
                <div><strong>Publié :</strong> <?php echo htmlspecialchars(formatDate($offer['date_publication'] ?? null), ENT_QUOTES, 'UTF-8'); ?></div>
                <div><strong>Expiration :</strong> <?php echo htmlspecialchars(formatDate($offer['date_expiration'] ?? null), ENT_QUOTES, 'UTF-8'); ?></div>
                <div><strong>Offre ID :</strong> <?php echo htmlspecialchars((string) $offerId, ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        </div>

        <div class="table-wrap candidatures-table-wrap">
            <table class="module-table">
                <thead>
                    <tr>
                        <th>Candidat</th>
                        <th>Date candidature</th>
                        <th>Statut</th>
                        <th>Expérience</th>
                        <th>Compétences</th>
                        <th>Message</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($applications)): ?>
                        <tr>
                            <td colspan="7" class="empty-table-cell">Aucune candidature pour cette offre.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($applications as $application): ?>
                            <?php $normalizedStatus = normalizeApplicationStatus((string) ($application['statut'] ?? '')); ?>
                            <tr>
                                <td class="candidate-cell">
                                    <strong><?php echo htmlspecialchars((string) ($application['id'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars(formatDate($application['created_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <span class="status-chip status-<?php echo htmlspecialchars($normalizedStatus, ENT_QUOTES, 'UTF-8'); ?>" data-status-chip>
                                        <?php echo htmlspecialchars(formatApplicationStatus((string) ($application['statut'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars((string) ($application['experience'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?> ans</td>
                                <td class="text-compact"><?php echo htmlspecialchars((string) ($application['competences'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="text-compact message-cell"><?php echo htmlspecialchars((string) ($application['message'] ?? 'N/A'), ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="admin-tools action-cell">
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
                                            <?php echo $normalizedStatus === 'acceptee' ? 'Acceptée' : 'Accepter'; ?>
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
    <?php endif; ?>
</section>

    <style>
    .offer-applications-page {
        padding: 20px;
        background: linear-gradient(180deg, rgba(255,255,255,0.86), rgba(247,248,251,0.78));
    }

    .page-title {
        margin: 0 0 10px;
        color: #0b2545;
        font-size: 24px;
        line-height: 1.15;
        letter-spacing: -0.02em;
    }

    .offer-summary-card {
        display: grid;
        grid-template-columns: minmax(0, 1.35fr) minmax(280px, 0.65fr);
        gap: 22px;
        margin-top: 10px;
        padding: 22px;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 20px;
        background: rgba(255,255,255,0.96);
        box-shadow: 0 12px 30px rgba(20,39,56,0.05);
    }

    .offer-summary-main {
        min-width: 0;
    }

    .offer-summary-card h3 {
        margin: 12px 0 8px;
        font-size: 26px;
        line-height: 1.12;
    }

    .offer-summary-card p {
        margin: 0;
        line-height: 1.6;
        max-width: 850px;
        color: #4b5563;
    }

    .offer-summary-meta {
        display: grid;
        gap: 12px;
        align-content: start;
        font-size: 14px;
        color: #334155;
        padding: 18px;
        border-radius: 18px;
        background: #f8fafc;
        border: 1px solid rgba(20, 39, 56, 0.06);
    }

    .offer-type-badge {
        background: rgba(238, 88, 40, 0.12);
        color: #d94d1f;
    }

    .offer-summary-stats {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        margin-top: 18px;
    }

    .summary-stat {
        padding: 14px 16px;
        border-radius: 16px;
        background: #f8fafc;
        border: 1px solid rgba(20, 39, 56, 0.06);
    }

    .summary-stat span {
        display: block;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        color: #64748b;
        margin-bottom: 4px;
    }

    .summary-stat strong {
        font-size: 16px;
        color: #0f172a;
    }

    .notice {
        margin: 14px 0 0;
        padding: 14px 16px;
        border-radius: 14px;
        font-weight: 600;
        box-shadow: 0 10px 24px rgba(20,39,56,0.04);
    }

    .notice-success {
        background: #ecfdf3;
        color: #027a48;
        border: 1px solid #b7ebc6;
    }

    .candidatures-table-wrap {
        margin-top: 18px;
        border-radius: 18px;
        overflow: auto;
        border: 1px solid rgba(20, 39, 56, 0.08);
        box-shadow: 0 12px 30px rgba(20,39,56,0.05);
        background: #ffffff;
    }

    .candidatures-table-wrap .module-table {
        min-width: 980px;
        border-collapse: separate;
        border-spacing: 0;
    }

    .candidatures-table-wrap .module-table th,
    .candidatures-table-wrap .module-table td {
        vertical-align: top;
    }

    .candidatures-table-wrap .module-table thead th {
        background: #eef2f7;
        color: #1f2937;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        padding-top: 18px;
        padding-bottom: 18px;
    }

    .candidatures-table-wrap .module-table tbody tr:hover {
        background: rgba(238, 88, 40, 0.03);
    }

    .candidatures-table-wrap .module-table tbody td {
        padding-top: 6px;
        padding-bottom: 6px;
        border-bottom: 1px solid rgba(20, 39, 56, 0.06);
    }

    .candidate-cell strong {
        display: block;
        color: #0f172a;
        margin-bottom: 0;
        font-size: 16px;
        line-height: 1.1;
    }

    .candidate-cell span {
        display: block;
        color: #64748b;
        font-size: 12px;
    }

    .cv-pill {
        display: inline-flex;
        align-items: center;
        padding: 8px 12px;
        border-radius: 999px;
        background: rgba(20, 39, 56, 0.06);
        color: #0b2545;
        font-weight: 700;
    }

    .text-compact {
        max-width: 240px;
        white-space: normal;
        line-height: 1.35;
        color: #334155;
        font-size: 13px;
    }

    .message-cell {
        max-width: 280px;
    }

    .action-cell {
        display: flex;
        flex-direction: column;
        gap: 6px;
        min-width: 150px;
        align-items: stretch;
    }

    .action-cell .inline-form {
        margin: 0;
    }

    .action-cell .action-pill {
        width: 100%;
        min-height: 38px;
        padding: 8px 12px;
        font-size: 14px;
    }

    .empty-table-cell {
        text-align: center;
        padding: 28px 18px;
        color: #64748b;
    }

    .status-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 100px;
        padding: 6px 10px;
        border-radius: 999px;
        font-weight: 700;
        font-size: 12px;
    }

    .status-acceptee {
        background: rgba(46, 125, 50, 0.2);
        color: #78e08f;
        border: 1px solid rgba(120, 224, 143, 0.35);
    }

    .status-refusee {
        background: rgba(198, 40, 40, 0.18);
        color: #ff8a80;
        border: 1px solid rgba(255, 138, 128, 0.28);
    }

    .status-en_attente {
        background: rgba(245, 158, 11, 0.16);
        color: #fbbf24;
        border: 1px solid rgba(251, 191, 36, 0.28);
    }

    .action-pill[disabled] {
        cursor: not-allowed;
        opacity: 0.9;
    }

    .action-pill.is-selected {
        box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.2) inset;
    }

    .back-link {
        display: inline-flex;
        align-items: center;
        padding: 8px 0;
        color: #4b5563;
        font-weight: 700;
    }

    .back-link:hover {
        color: #0b2545;
    }

    .section-badge.offer-type-badge {
        margin-bottom: 12px;
    }

    body.admin-body.dark .offer-applications-page {
        background: linear-gradient(180deg, rgba(14,26,39,0.6), rgba(10,18,28,0.7));
    }

    body.admin-body.dark .page-title {
        color: #ffffff;
    }

    body.admin-body.dark .offer-summary-card {
        background: rgba(20, 39, 56, 0.75);
        border-color: rgba(255, 255, 255, 0.08);
    }

    body.admin-body.dark .offer-summary-card h3 {
        color: #ffffff;
    }

    body.admin-body.dark .offer-summary-card p {
        color: #c8d0da;
    }

    body.admin-body.dark .offer-summary-meta {
        background: rgba(255, 255, 255, 0.04);
        border-color: rgba(255, 255, 255, 0.08);
        color: #c8d0da;
    }

    body.admin-body.dark .offer-summary-meta strong {
        color: #ffffff;
    }

    body.admin-body.dark .summary-stat {
        background: rgba(255, 255, 255, 0.04);
        border-color: rgba(255, 255, 255, 0.08);
    }

    body.admin-body.dark .summary-stat span {
        color: #a0adc8;
    }

    body.admin-body.dark .summary-stat strong {
        color: #ffffff;
    }

    body.admin-body.dark .notice-success {
        background: rgba(16, 100, 65, 0.2);
        color: #86efac;
        border-color: rgba(134, 239, 172, 0.3);
    }

    body.admin-body.dark .candidatures-table-wrap {
        background: rgba(255, 255, 255, 0.03);
        border-color: rgba(255, 255, 255, 0.08);
    }

    body.admin-body.dark .candidatures-table-wrap .module-table thead th {
        background: rgba(255, 255, 255, 0.08);
        color: #ffffff;
    }

    body.admin-body.dark .candidatures-table-wrap .module-table tbody tr:hover {
        background: rgba(238, 88, 40, 0.08);
    }

    body.admin-body.dark .candidatures-table-wrap .module-table tbody td {
        border-bottom-color: rgba(255, 255, 255, 0.06);
        color: #ffffff;
    }

    body.admin-body.dark .candidate-cell strong {
        color: #ffffff;
    }

    body.admin-body.dark .candidate-cell span {
        color: #a0adc8;
    }

    body.admin-body.dark .text-compact {
        color: #c8d0da;
    }

    body.admin-body.dark .empty-table-cell {
        color: #a0adc8;
    }

    body.admin-body.dark .back-link {
        color: #a0adc8;
    }

    body.admin-body.dark .back-link:hover {
        color: #ffffff;
    }

    @media (max-width: 900px) {
        .offer-applications-page {
            padding: 18px;
        }

        .offer-summary-card {
            grid-template-columns: 1fr;
        }

        .offer-summary-stats {
            grid-template-columns: 1fr;
        }
    }
    </style>

