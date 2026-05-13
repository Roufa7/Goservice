<?php
// view/back/pages/reservations.php
require_once __DIR__ . '/../../../controller/ReservationController.php';

$controller = new ReservationController();

// Suppression uniquement (admin = supervision)
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $controller->deleteReservation((int)$_GET['delete']);
    header('Location: index.php?page=reservations&deleted=1');
    exit;
}

$reservations = $controller->listReservations();
$stats        = $controller->getStats();

// ── Pagination ──
$parPage = 8; // change à 5, 10, 12... si tu veux
$totalReservations = count($reservations);
$totalPages = (int)ceil($totalReservations / $parPage);
$pageActuelle = isset($_GET['p']) ? (int)$_GET['p'] : 1;
$pageActuelle = max(1, $pageActuelle);
$pageActuelle = min($pageActuelle, max($totalPages, 1));
$offset = ($pageActuelle - 1) * $parPage;
$reservationsPage = array_slice($reservations, $offset, $parPage);
?>

<?php if (isset($_GET['deleted'])): ?>
<div style="background:rgba(76,175,80,0.1);border:1px solid rgba(76,175,80,0.25);color:#2e7d32;border-radius:10px;padding:12px 16px;margin-bottom:18px;font-size:14px;">
    ✓ Réservation supprimée.
</div>
<?php endif; ?>

<style>
.rsv-stats{display:flex;gap:12px;margin-bottom:22px;flex-wrap:wrap;}
.rsv-stat{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:14px 20px;display:flex;align-items:center;gap:12px;}
.rsv-stat-ic{width:36px;height:36px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;}
.ic-o{background:rgba(238,88,40,0.12);}
.ic-y{background:rgba(245,166,35,0.12);}
.ic-g{background:rgba(76,175,80,0.12);}
.ic-r{background:rgba(239,68,68,0.1);}
.rsv-stat-num{font-family:'Poppins',sans-serif;font-size:20px;font-weight:700;color:var(--text);}
.rsv-stat-lbl{font-size:11px;color:var(--muted);}

.rsv-toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:14px;flex-wrap:wrap;}
.rsv-count{font-size:13px;color:var(--muted);font-weight:700;}

.rsv-table-wrap{background:var(--card);border:1px solid var(--line);border-radius:var(--radius-lg);overflow:hidden;box-shadow:0 12px 32px rgba(7,20,34,.08);}
.rsv-table{width:100%;border-collapse:collapse;}
.rsv-table thead tr{background:rgba(255,255,255,0.03);border-bottom:1px solid var(--line);}
.rsv-table th{padding:11px 14px;font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.07em;text-align:left;}
.rsv-table td{padding:13px 14px;font-size:13px;color:var(--text);border-bottom:1px solid var(--line);vertical-align:middle;}
.rsv-table tbody tr:last-child td{border:none;}
.rsv-table tbody tr:hover{background:rgba(238,88,40,0.03);}

.rsv-id{font-family:monospace;font-weight:700;color:var(--orange);}
.rsv-client{font-weight:600;}
.rsv-email{font-size:12px;color:var(--muted);}
.rsv-service{font-weight:600;}
.rsv-cat{font-size:11px;color:var(--muted);}
.rsv-date{font-size:12px;color:var(--muted);}

.badge-statut{display:inline-block;padding:4px 10px;border-radius:99px;font-size:11px;font-weight:700;}
.b-attente{background:rgba(245,166,35,0.12);color:#d97706;border:1px solid rgba(245,166,35,0.25);}
.b-confirmee{background:rgba(76,175,80,0.12);color:#2e7d32;border:1px solid rgba(76,175,80,0.25);}
.b-annulee{background:rgba(239,68,68,0.1);color:#c62828;border:1px solid rgba(239,68,68,0.2);}

.rsv-actions{display:flex;gap:6px;align-items:center;flex-wrap:wrap;}
.btn-del{
    padding:6px 12px;
    border-radius:8px;
    font-size:11px;
    font-weight:700;
    background:rgba(239,68,68,0.09);
    color:#c62828;
    border:1px solid rgba(239,68,68,0.18);
    cursor:pointer;
    font-family:inherit;
}

.rsv-pagination{
    display:flex;
    justify-content:center;
    align-items:center;
    gap:8px;
    margin-top:22px;
    flex-wrap:wrap;
}
.rsv-pagination a,
.rsv-pagination span{
    min-width:38px;
    height:38px;
    padding:0 12px;
    border-radius:12px;
    display:flex;
    align-items:center;
    justify-content:center;
    text-decoration:none;
    border:1px solid var(--line);
    background:var(--card);
    color:var(--text);
    font-size:13px;
    font-weight:800;
    transition:.2s ease;
}
.rsv-pagination a:hover,
.rsv-pagination a.active{
    background:var(--orange);
    color:#fff;
    border-color:var(--orange);
    box-shadow:0 8px 20px rgba(238,88,40,.22);
}
.rsv-pagination span.disabled{
    opacity:.45;
    cursor:not-allowed;
}
</style>

<div class="rsv-stats">
    <div class="rsv-stat">
        <div class="rsv-stat-ic ic-o">📋</div>
        <div>
            <div class="rsv-stat-num"><?php echo $stats['total'] ?? 0; ?></div>
            <div class="rsv-stat-lbl">Total</div>
        </div>
    </div>

    <div class="rsv-stat">
        <div class="rsv-stat-ic ic-y">⏳</div>
        <div>
            <div class="rsv-stat-num"><?php echo $stats['en_attente'] ?? 0; ?></div>
            <div class="rsv-stat-lbl">En attente</div>
        </div>
    </div>

    <div class="rsv-stat">
        <div class="rsv-stat-ic ic-g">✓</div>
        <div>
            <div class="rsv-stat-num"><?php echo $stats['confirmees'] ?? 0; ?></div>
            <div class="rsv-stat-lbl">Confirmées</div>
        </div>
    </div>

    <div class="rsv-stat">
        <div class="rsv-stat-ic ic-r">✕</div>
        <div>
            <div class="rsv-stat-num"><?php echo $stats['annulees'] ?? 0; ?></div>
            <div class="rsv-stat-lbl">Annulées</div>
        </div>
    </div>
</div>

<div class="rsv-toolbar">
    <div class="rsv-count">
        <?php echo $totalReservations; ?> réservation(s)
        <?php if ($totalPages > 1): ?>
            — page <?php echo $pageActuelle; ?>/<?php echo $totalPages; ?>
        <?php endif; ?>
    </div>
</div>

<div class="rsv-table-wrap">
    <table class="rsv-table">
        <thead>
            <tr>
                <th>N°</th>
                <th>Client</th>
                <th>Service</th>
                <th>Date souhaitée</th>
                <th>Statut</th>
                <th>Action admin</th>
            </tr>
        </thead>

        <tbody>
        <?php if (empty($reservationsPage)): ?>
            <tr>
                <td colspan="6" style="text-align:center;padding:40px;color:var(--muted);">
                    Aucune réservation pour l'instant.
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($reservationsPage as $r): ?>
                <?php
                $bClass = match($r['statut']) {
                    'Confirmée' => 'b-confirmee',
                    'Annulée'   => 'b-annulee',
                    default     => 'b-attente',
                };
                ?>
                <tr>
                    <td>
                        <span class="rsv-id">#<?php echo (int)$r['id_reservation']; ?></span>
                    </td>

                    <td>
                        <div class="rsv-client">
                            <?php echo htmlspecialchars($r['prenom_client'] . ' ' . $r['nom_client']); ?>
                        </div>
                        <div class="rsv-email"><?php echo htmlspecialchars($r['email_client']); ?></div>
                        <div class="rsv-email"><?php echo htmlspecialchars($r['telephone']); ?></div>
                    </td>

                    <td>
                        <div class="rsv-service"><?php echo htmlspecialchars($r['titre_service']); ?></div>
                        <div class="rsv-cat"><?php echo htmlspecialchars($r['nom_categorie']); ?></div>
                    </td>

                    <td>
                        <div>
                            <?php echo !empty($r['date_souhaitee']) ? date('d/m/Y', strtotime($r['date_souhaitee'])) : '—'; ?>
                        </div>
                        <div class="rsv-date">
                            <?php echo !empty($r['date_creation']) ? date('d/m/Y H:i', strtotime($r['date_creation'])) : '—'; ?>
                        </div>
                    </td>

                    <td>
                        <span class="badge-statut <?php echo $bClass; ?>">
                            <?php echo htmlspecialchars($r['statut']); ?>
                        </span>
                    </td>

                    <td>
                        <div class="rsv-actions">
                            <form method="GET" style="display:inline;" onsubmit="return confirm('Supprimer cette réservation ?')">
                                <input type="hidden" name="page" value="reservations">
                                <input type="hidden" name="delete" value="<?php echo (int)$r['id_reservation']; ?>">
                                <button type="submit" class="btn-del">🗑 Supprimer</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
<div class="rsv-pagination">
    <?php if ($pageActuelle > 1): ?>
        <a href="index.php?page=reservations&p=<?php echo $pageActuelle - 1; ?>">‹</a>
    <?php else: ?>
        <span class="disabled">‹</span>
    <?php endif; ?>

    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <a href="index.php?page=reservations&p=<?php echo $i; ?>"
           class="<?php echo $i === $pageActuelle ? 'active' : ''; ?>">
            <?php echo $i; ?>
        </a>
    <?php endfor; ?>

    <?php if ($pageActuelle < $totalPages): ?>
        <a href="index.php?page=reservations&p=<?php echo $pageActuelle + 1; ?>">›</a>
    <?php else: ?>
        <span class="disabled">›</span>
    <?php endif; ?>
</div>
<?php endif; ?>
