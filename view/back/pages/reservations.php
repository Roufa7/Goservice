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

.rsv-table-wrap{background:var(--card);border:1px solid var(--line);border-radius:var(--radius-lg);overflow:hidden;}
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
        <?php if (empty($reservations)): ?>
            <tr>
                <td colspan="6" style="text-align:center;padding:40px;color:var(--muted);">
                    Aucune réservation pour l'instant.
                </td>
            </tr>
        <?php else: ?>
            <?php foreach ($reservations as $r): ?>
                <?php
                $bClass = match($r['statut']) {
                    'Confirmée' => 'b-confirmee',
                    'Annulée'   => 'b-annulee',
                    default     => 'b-attente',
                };
                ?>
                <tr>
                    <td>
                        <span class="rsv-id">#<?php echo $r['id_reservation']; ?></span>
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
                        <div><?php echo date('d/m/Y', strtotime($r['date_souhaitee'])); ?></div>
                        <div class="rsv-date"><?php echo date('d/m/Y H:i', strtotime($r['date_creation'])); ?></div>
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
                                <input type="hidden" name="delete" value="<?php echo $r['id_reservation']; ?>">
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