<?php
require_once __DIR__ . '/../../../controller/ReservationController.php';

$controller = new ReservationController();

// Provisoire — remplacer par la session du provider connecté
$id_provider = 1;

// ── Confirmer ou Annuler une réservation ──
if (isset($_POST['action']) && $_POST['action'] === 'statut') {
    $id     = (int)($_POST['id'] ?? 0);
    $statut = $_POST['statut'] ?? '';
    if ($id && in_array($statut, ['En attente', 'Confirmée', 'Annulée'])) {
        $controller->updateStatut($id, $statut);
        header('Location: index.php?page=myReservations&updated=1');
        exit;
    }
}

// ── Supprimer une réservation ──
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $controller->deleteReservation((int)$_GET['delete']);
    header('Location: index.php?page=myReservations&deleted=1');
    exit;
}

$reservations = $controller->listReservationsByProvider($id_provider);
$stats        = $controller->getStatsByProvider($id_provider);

// Filtre statut (optionnel)
$filtreStatut = isset($_GET['statut']) ? trim($_GET['statut']) : '';
if ($filtreStatut && in_array($filtreStatut, ['En attente', 'Confirmée', 'Annulée'])) {
    $reservations = array_filter($reservations, fn($r) => $r['statut'] === $filtreStatut);
    $reservations = array_values($reservations);
}
?>

<style>
/* ── Stats ── */
.mrsv-stats {
    display: flex;
    gap: 14px;
    margin-bottom: 28px;
    flex-wrap: wrap;
}
.mrsv-stat {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 14px;
    padding: 16px 22px;
    display: flex;
    align-items: center;
    gap: 14px;
    flex: 1;
    min-width: 140px;
}
.mrsv-stat-ic {
    width: 40px; height: 40px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; flex-shrink: 0;
}
.ic-total  { background: rgba(238,88,40,0.12); }
.ic-wait   { background: rgba(245,166,35,0.12); }
.ic-ok     { background: rgba(76,175,80,0.12); }
.ic-no     { background: rgba(239,68,68,0.10); }
.mrsv-stat-num { font-size: 22px; font-weight: 700; color: var(--text); font-family: 'Poppins', sans-serif; }
.mrsv-stat-lbl { font-size: 11px; color: var(--muted); }

/* ── Filtres ── */
.mrsv-filters {
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
    flex-wrap: wrap;
    align-items: center;
}
.mrsv-filter-btn {
    padding: 7px 16px;
    border-radius: 99px;
    border: 1px solid var(--line);
    background: var(--card);
    color: var(--text);
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    transition: all .2s;
}
.mrsv-filter-btn:hover,
.mrsv-filter-btn.active {
    background: var(--orange);
    color: #fff;
    border-color: var(--orange);
}
.mrsv-filter-btn.f-wait.active  { background: #d97706; border-color: #d97706; }
.mrsv-filter-btn.f-ok.active    { background: #2e7d32; border-color: #2e7d32; }
.mrsv-filter-btn.f-no.active    { background: #c62828; border-color: #c62828; }

/* ── Table ── */
.mrsv-table-wrap {
    background: var(--card);
    border: 1px solid var(--line);
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 18px 40px rgba(7,20,34,0.14);
}
.mrsv-table { width: 100%; border-collapse: collapse; }
.mrsv-table thead tr {
    background: rgba(255,255,255,0.03);
    border-bottom: 1px solid var(--line);
}
.mrsv-table th {
    padding: 12px 14px;
    font-size: 11px;
    font-weight: 700;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: .07em;
    text-align: left;
}
.mrsv-table td {
    padding: 14px 14px;
    font-size: 13px;
    color: var(--text);
    border-bottom: 1px solid var(--line);
    vertical-align: middle;
}
.mrsv-table tbody tr:last-child td { border: none; }
.mrsv-table tbody tr:hover { background: rgba(238,88,40,0.03); }

.mrsv-id    { font-family: monospace; font-weight: 700; color: var(--orange); }
.mrsv-name  { font-weight: 600; }
.mrsv-email { font-size: 12px; color: var(--muted); }
.mrsv-service { font-weight: 600; }
.mrsv-cat   { font-size: 11px; color: var(--muted); }

/* ── Badges statut ── */
.badge-statut { display: inline-block; padding: 4px 12px; border-radius: 99px; font-size: 11px; font-weight: 700; }
.b-attente  { background: rgba(245,166,35,0.12); color: #d97706; border: 1px solid rgba(245,166,35,0.25); }
.b-confirmee{ background: rgba(76,175,80,0.12);  color: #2e7d32; border: 1px solid rgba(76,175,80,0.25); }
.b-annulee  { background: rgba(239,68,68,0.10);  color: #c62828; border: 1px solid rgba(239,68,68,0.20); }

/* ── Actions ── */
.mrsv-actions { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
.btn-conf {
    padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 600;
    background: rgba(76,175,80,0.10); color: #2e7d32;
    border: 1px solid rgba(76,175,80,0.22); cursor: pointer; font-family: inherit;
    transition: background .2s;
}
.btn-conf:hover { background: rgba(76,175,80,0.20); }
.btn-ann {
    padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 600;
    background: rgba(239,68,68,0.09); color: #c62828;
    border: 1px solid rgba(239,68,68,0.20); cursor: pointer; font-family: inherit;
    transition: background .2s;
}
.btn-ann:hover { background: rgba(239,68,68,0.18); }
.btn-del {
    padding: 6px 12px; border-radius: 8px; font-size: 11px; font-weight: 600;
    background: rgba(100,100,100,0.08); color: var(--muted);
    border: 1px solid var(--line); cursor: pointer; font-family: inherit;
    text-decoration: none; display: inline-block;
}
.btn-del:hover { background: rgba(100,100,100,0.15); }

/* ── Message ── */
.mrsv-msg-ok {
    background: rgba(76,175,80,0.10);
    border: 1px solid rgba(76,175,80,0.28);
    color: #2e7d32;
    border-radius: 12px;
    padding: 13px 18px;
    margin-bottom: 20px;
    font-size: 14px;
}
.mrsv-empty {
    text-align: center;
    padding: 48px 20px;
    color: var(--muted);
    font-size: 14px;
}
.mrsv-empty .empty-icon { font-size: 40px; margin-bottom: 12px; }
</style>

<?php if (isset($_GET['updated'])): ?>
<div class="mrsv-msg-ok">✓ Statut de la réservation mis à jour avec succès.</div>
<?php elseif (isset($_GET['deleted'])): ?>
<div class="mrsv-msg-ok">✓ Réservation supprimée.</div>
<?php endif; ?>

<!-- Hero -->
<section class="page-hero reveal">
    <span class="section-badge">Provider</span>
    <h1 class="page-title">Mes réservations reçues</h1>
    <p class="page-intro">Gérez les demandes de réservation envoyées sur vos services — confirmez ou refusez chaque demande.</p>
</section>

<!-- Barre d'actions -->
<section class="action-bar reveal">
    <div class="search-box">
        <div class="icon-actions">
            <a class="solid-btn" href="index.php?page=services">Tous les services</a>
            <a class="solid-btn alt-btn" href="index.php?page=myServices">Mes services</a>
        </div>
    </div>
</section>

<!-- Stats -->
<div class="mrsv-stats reveal">
    <div class="mrsv-stat">
        <div class="mrsv-stat-ic ic-total">📋</div>
        <div>
            <div class="mrsv-stat-num"><?php echo (int)($stats['total'] ?? 0); ?></div>
            <div class="mrsv-stat-lbl">Total reçues</div>
        </div>
    </div>
    <div class="mrsv-stat">
        <div class="mrsv-stat-ic ic-wait">⏳</div>
        <div>
            <div class="mrsv-stat-num"><?php echo (int)($stats['en_attente'] ?? 0); ?></div>
            <div class="mrsv-stat-lbl">En attente</div>
        </div>
    </div>
    <div class="mrsv-stat">
        <div class="mrsv-stat-ic ic-ok">✓</div>
        <div>
            <div class="mrsv-stat-num"><?php echo (int)($stats['confirmees'] ?? 0); ?></div>
            <div class="mrsv-stat-lbl">Confirmées</div>
        </div>
    </div>
    <div class="mrsv-stat">
        <div class="mrsv-stat-ic ic-no">✕</div>
        <div>
            <div class="mrsv-stat-num"><?php echo (int)($stats['annulees'] ?? 0); ?></div>
            <div class="mrsv-stat-lbl">Annulées</div>
        </div>
    </div>
</div>

<!-- Filtres rapides -->
<div class="mrsv-filters reveal">
    <a href="index.php?page=myReservations"
       class="mrsv-filter-btn <?php echo $filtreStatut === '' ? 'active' : ''; ?>">
        Toutes
    </a>
    <a href="index.php?page=myReservations&statut=En+attente"
       class="mrsv-filter-btn f-wait <?php echo $filtreStatut === 'En attente' ? 'active' : ''; ?>">
        ⏳ En attente
    </a>
    <a href="index.php?page=myReservations&statut=Confirm%C3%A9e"
       class="mrsv-filter-btn f-ok <?php echo $filtreStatut === 'Confirmée' ? 'active' : ''; ?>">
        ✓ Confirmées
    </a>
    <a href="index.php?page=myReservations&statut=Annul%C3%A9e"
       class="mrsv-filter-btn f-no <?php echo $filtreStatut === 'Annulée' ? 'active' : ''; ?>">
        ✕ Annulées
    </a>
</div>

<!-- Tableau -->
<div class="mrsv-table-wrap reveal">
    <?php if (!empty($reservations)): ?>
    <table class="mrsv-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Client</th>
                <th>Service</th>
                <th>Date souhaitée</th>
                <th>Message</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($reservations as $r): ?>
            <tr>
                <!-- ID -->
                <td><span class="mrsv-id">#<?php echo (int)$r['id_reservation']; ?></span></td>

                <!-- Client -->
                <td>
                    <div class="mrsv-name">
                        <?php echo htmlspecialchars($r['prenom_client'] . ' ' . $r['nom_client']); ?>
                    </div>
                    <div class="mrsv-email"><?php echo htmlspecialchars($r['email_client']); ?></div>
                    <?php if (!empty($r['telephone'])): ?>
                    <div class="mrsv-email">📞 <?php echo htmlspecialchars($r['telephone']); ?></div>
                    <?php endif; ?>
                </td>

                <!-- Service -->
                <td>
                    <div class="mrsv-service"><?php echo htmlspecialchars($r['titre_service']); ?></div>
                    <div class="mrsv-cat"><?php echo htmlspecialchars($r['nom_categorie']); ?></div>
                    <div class="mrsv-cat" style="color:var(--orange);font-weight:600;">
                        <?php echo number_format($r['prix_service'], 2); ?> €
                    </div>
                </td>

                <!-- Date souhaitée -->
                <td>
                    <?php echo !empty($r['date_souhaitee'])
                        ? htmlspecialchars(date('d/m/Y', strtotime($r['date_souhaitee'])))
                        : '<span style="color:var(--muted)">—</span>'; ?>
                    <div class="mrsv-email">
                        Reçue le <?php echo date('d/m/Y', strtotime($r['date_creation'])); ?>
                    </div>
                </td>

                <!-- Message -->
                <td style="max-width:180px;">
                    <?php if (!empty($r['message'])): ?>
                        <span title="<?php echo htmlspecialchars($r['message']); ?>"
                              style="display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:170px;font-size:12px;color:var(--muted);">
                            <?php echo htmlspecialchars($r['message']); ?>
                        </span>
                    <?php else: ?>
                        <span style="color:var(--muted);font-size:12px;">—</span>
                    <?php endif; ?>
                </td>

                <!-- Statut -->
                <td>
                    <?php
                    $badgeClass = match($r['statut']) {
                        'Confirmée' => 'b-confirmee',
                        'Annulée'   => 'b-annulee',
                        default     => 'b-attente',
                    };
                    ?>
                    <span class="badge-statut <?php echo $badgeClass; ?>">
                        <?php echo htmlspecialchars($r['statut']); ?>
                    </span>
                </td>

                <!-- Actions -->
                <td>
                    <div class="mrsv-actions">
                        <?php if ($r['statut'] === 'En attente'): ?>
                            <!-- Confirmer -->
                            <form method="POST" action="index.php?page=myReservations" style="display:inline;">
                                <input type="hidden" name="action" value="statut">
                                <input type="hidden" name="id" value="<?php echo (int)$r['id_reservation']; ?>">
                                <input type="hidden" name="statut" value="Confirmée">
                                <button type="submit" class="btn-conf">✓ Confirmer</button>
                            </form>
                            <!-- Annuler -->
                            <form method="POST" action="index.php?page=myReservations" style="display:inline;">
                                <input type="hidden" name="action" value="statut">
                                <input type="hidden" name="id" value="<?php echo (int)$r['id_reservation']; ?>">
                                <input type="hidden" name="statut" value="Annulée">
                                <button type="submit" class="btn-ann"
                                        onclick="return confirm('Refuser cette réservation ?');">
                                    ✕ Refuser
                                </button>
                            </form>
                        <?php elseif ($r['statut'] === 'Confirmée'): ?>
                            <!-- Repasser en attente si besoin -->
                            <form method="POST" action="index.php?page=myReservations" style="display:inline;">
                                <input type="hidden" name="action" value="statut">
                                <input type="hidden" name="id" value="<?php echo (int)$r['id_reservation']; ?>">
                                <input type="hidden" name="statut" value="Annulée">
                                <button type="submit" class="btn-ann"
                                        onclick="return confirm('Annuler cette réservation confirmée ?');">
                                    ✕ Annuler
                                </button>
                            </form>
                        <?php else: ?>
                            <!-- Remettre en attente -->
                            <form method="POST" action="index.php?page=myReservations" style="display:inline;">
                                <input type="hidden" name="action" value="statut">
                                <input type="hidden" name="id" value="<?php echo (int)$r['id_reservation']; ?>">
                                <input type="hidden" name="statut" value="En attente">
                                <button type="submit" class="btn-conf">↩ Réouvrir</button>
                            </form>
                        <?php endif; ?>

                        <!-- Supprimer -->
                        <a href="index.php?page=myReservations&delete=<?php echo (int)$r['id_reservation']; ?>"
                           class="btn-del"
                           onclick="return confirm('Supprimer définitivement cette réservation ?');">
                            🗑
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="mrsv-empty">
        <div class="empty-icon">📭</div>
        <p>
            <?php if ($filtreStatut): ?>
                Aucune réservation avec le statut <strong><?php echo htmlspecialchars($filtreStatut); ?></strong>.
                <a href="index.php?page=myReservations" style="color:var(--orange);">Voir toutes</a>
            <?php else: ?>
                Vous n'avez encore reçu aucune réservation sur vos services.
            <?php endif; ?>
        </p>
    </div>
    <?php endif; ?>
</div>
