<?php
require_once __DIR__ . '/../../../controller/ReservationController.php';

$controller = new ReservationController();

$id_provider = 1;

/* =========================
   ACTIONS
========================= */

if (isset($_POST['action']) && $_POST['action'] === 'statut') {

    $id     = (int)($_POST['id'] ?? 0);
    $statut = $_POST['statut'] ?? '';

    if ($id && in_array($statut, ['En attente', 'Confirmée', 'Annulée'])) {

        $controller->updateStatut($id, $statut);

        header('Location: index.php?page=myReservations&updated=1');
        exit;
    }
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {

    $controller->deleteReservation((int)$_GET['delete']);

    header('Location: index.php?page=myReservations&deleted=1');
    exit;
}

/* =========================
   DATA
========================= */

$reservations = $controller->listReservationsByProvider($id_provider);
$stats        = $controller->getStatsByProvider($id_provider);

/* =========================
   FILTRE
========================= */

$filtreStatut = isset($_GET['statut']) ? trim($_GET['statut']) : '';

if ($filtreStatut && in_array($filtreStatut, ['En attente', 'Confirmée', 'Annulée'])) {

    $reservations = array_filter(
        $reservations,
        fn($r) => $r['statut'] === $filtreStatut
    );

    $reservations = array_values($reservations);
}

/* =========================
   PAGINATION
========================= */

$parPage = 6;

$totalReservations = count($reservations);

$totalPages = ceil($totalReservations / $parPage);

$pageActuelle = max(1, (int)($_GET['p'] ?? 1));

$pageActuelle = min($pageActuelle, max($totalPages, 1));

$offset = ($pageActuelle - 1) * $parPage;

$reservations = array_slice($reservations, $offset, $parPage);

?>

<style>

/* =========================
   CONTAINER
========================= */

.mrsv-grid-container{
    width:92%;
    max-width:1450px;
    margin:0 auto;
}

/* =========================
   FILTRES
========================= */

.mrsv-filters{
    display:flex;
    gap:12px;
    align-items:center;
    flex-wrap:wrap;
    margin:24px auto 10px;
    width:92%;
    max-width:1450px;
}

.mrsv-filter-btn{
    padding:12px 22px;
    border-radius:999px;
    border:1px solid var(--line);
    background:var(--card);
    color:var(--text);
    text-decoration:none;
    font-size:14px;
    font-weight:700;
    transition:.2s ease;
    display:flex;
    align-items:center;
    gap:8px;
    box-shadow:0 4px 12px rgba(0,0,0,0.04);
}

.mrsv-filter-btn:hover{
    transform:translateY(-2px);
}

.mrsv-filter-btn.active{
    background:var(--orange);
    color:white;
    border-color:var(--orange);
    box-shadow:0 10px 24px rgba(255,106,0,0.22);
}

/* =========================
   GRID
========================= */

.mrsv-cards-grid{
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(400px,1fr));
    gap:28px;
    margin-top:24px;
}

/* =========================
   CARD
========================= */

.mrsv-card{
    background:var(--card);
    border:1px solid var(--line);
    border-radius:28px;
    padding:22px;
    box-shadow:0 10px 35px rgba(0,0,0,0.06);
    transition:.25s ease;
}

.mrsv-card:hover{
    transform:translateY(-4px);
    box-shadow:0 18px 45px rgba(0,0,0,0.10);
}

/* =========================
   TOP CARD
========================= */

.mrsv-card-top{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    margin-bottom:18px;
}

.mrsv-client-box{
    display:flex;
    gap:14px;
    align-items:center;
}

.mrsv-avatar{
    width:54px;
    height:54px;
    border-radius:18px;
    background:linear-gradient(135deg,#ffe2d6,#fff1eb);
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:700;
    color:var(--orange);
    font-size:17px;
}

.mrsv-client-name{
    font-size:16px;
    font-weight:700;
    color:var(--text);
}

.mrsv-client-mail,
.mrsv-client-phone{
    font-size:13px;
    color:var(--muted);
}

.mrsv-reservation-id{
    background:rgba(255,106,0,0.08);
    color:var(--orange);
    padding:9px 14px;
    border-radius:14px;
    font-weight:700;
    font-size:15px;
}

/* =========================
   SERVICE
========================= */

.mrsv-service-box{
    border:1px solid var(--line);
    border-radius:22px;
    padding:18px;
    margin-bottom:16px;
}

.mrsv-service-title{
    font-size:19px;
    font-weight:700;
    color:var(--text);
    margin-bottom:4px;
}

.mrsv-service-category{
    color:var(--muted);
    font-size:14px;
}

.mrsv-service-price{
    color:var(--orange);
    font-size:16px;
    font-weight:700;
    margin-top:4px;
}

/* =========================
   INFOS
========================= */

.mrsv-infos{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:12px;
    margin-bottom:16px;
}

.mrsv-info-box{
    border:1px solid var(--line);
    border-radius:18px;
    padding:14px;
}

.mrsv-info-label{
    font-size:11px;
    text-transform:uppercase;
    color:var(--muted);
    font-weight:700;
    margin-bottom:6px;
}

.mrsv-info-value{
    font-size:15px;
    font-weight:700;
    color:var(--text);
}

/* =========================
   MESSAGE
========================= */

.mrsv-message{
    background:rgba(255,106,0,0.05);
    border-radius:18px;
    padding:16px;
    font-size:14px;
    color:var(--muted);
    margin-bottom:16px;
}

/* =========================
   STATUS
========================= */

.badge-statut{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:8px 15px;
    border-radius:999px;
    font-size:13px;
    font-weight:700;
    margin-bottom:16px;
}

.b-attente{
    background:rgba(255,170,0,0.10);
    border:1px solid rgba(255,170,0,0.25);
    color:#d68a00;
}

.b-confirmee{
    background:rgba(0,180,90,0.10);
    border:1px solid rgba(0,180,90,0.25);
    color:#178c4e;
}

.b-annulee{
    background:rgba(255,60,60,0.10);
    border:1px solid rgba(255,60,60,0.25);
    color:#d63b3b;
}

/* =========================
   ACTIONS
========================= */

.mrsv-actions{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    padding-top:16px;
    border-top:1px solid var(--line);
}

.btn-conf,
.btn-ann,
.btn-del{
    border:none;
    border-radius:14px;
    padding:12px 18px;
    font-size:14px;
    font-weight:700;
    cursor:pointer;
    transition:.2s ease;
    font-family:inherit;
}

.btn-conf{
    background:rgba(46,125,50,0.12);
    color:#2e7d32;
    border:1px solid rgba(46,125,50,0.20);
}

.btn-conf:hover{
    background:#2e7d32;
    color:#fff;
}

.btn-ann{
    background:rgba(198,40,40,0.10);
    color:#c62828;
    border:1px solid rgba(198,40,40,0.18);
}

.btn-ann:hover{
    background:#c62828;
    color:#fff;
}

.btn-del{
    background:rgba(120,120,120,0.08);
    color:var(--muted);
    border:1px solid var(--line);
    text-decoration:none;
}

.btn-del:hover{
    background:#555;
    color:#fff;
}

/* =========================
   PAGINATION
========================= */

.mrsv-pagination{
    display:flex;
    justify-content:center;
    align-items:center;
    gap:10px;
    margin-top:40px;
    margin-bottom:30px;
}

.mrsv-pagination a{
    width:42px;
    height:42px;
    border-radius:14px;
    display:flex;
    align-items:center;
    justify-content:center;
    text-decoration:none;
    border:1px solid var(--line);
    background:var(--card);
    color:var(--text);
    font-weight:700;
    transition:.2s ease;
}

.mrsv-pagination a:hover,
.mrsv-pagination a.active{
    background:var(--orange);
    color:white;
    border-color:var(--orange);
}

/* =========================
   RESPONSIVE
========================= */

@media(max-width:768px){

    .mrsv-grid-container{
        width:95%;
    }

    .mrsv-cards-grid{
        grid-template-columns:1fr;
    }

    .mrsv-infos{
        grid-template-columns:1fr;
    }

}

</style>

<section class="page-hero reveal">

    <span class="section-badge">Provider</span>

    <h1 class="page-title">
        Mes réservations reçues
    </h1>

    <p class="page-intro">
        Gérez les demandes de réservation envoyées sur vos services.
    </p>

</section>

<section class="action-bar reveal">

    <div class="search-box">

        <div class="icon-actions">

            <a class="solid-btn" href="index.php?page=services">
                Tous les services
            </a>

            <a class="solid-btn alt-btn" href="index.php?page=myServices">
                Mes services
            </a>

        </div>

    </div>

</section>

<!-- FILTRES -->

<div class="mrsv-filters reveal">

    <a href="index.php?page=myReservations"
       class="mrsv-filter-btn <?php echo $filtreStatut === '' ? 'active' : ''; ?>">
        Toutes
    </a>

    <a href="index.php?page=myReservations&statut=En+attente"
       class="mrsv-filter-btn <?php echo $filtreStatut === 'En attente' ? 'active' : ''; ?>">
        ⏳ En attente
    </a>

    <a href="index.php?page=myReservations&statut=Confirm%C3%A9e"
       class="mrsv-filter-btn <?php echo $filtreStatut === 'Confirmée' ? 'active' : ''; ?>">
        ✓ Confirmées
    </a>

    <a href="index.php?page=myReservations&statut=Annul%C3%A9e"
       class="mrsv-filter-btn <?php echo $filtreStatut === 'Annulée' ? 'active' : ''; ?>">
        ✕ Annulées
    </a>

</div>

<div class="mrsv-grid-container">

<div class="mrsv-cards-grid">

<?php foreach ($reservations as $r): ?>

<?php
$badgeClass = match($r['statut']) {
    'Confirmée' => 'b-confirmee',
    'Annulée'   => 'b-annulee',
    default     => 'b-attente'
};
?>

<div class="mrsv-card">

    <div class="mrsv-card-top">

        <div class="mrsv-client-box">

            <div class="mrsv-avatar">
                <?php
                echo strtoupper(
                    substr($r['prenom_client'],0,1) .
                    substr($r['nom_client'],0,1)
                );
                ?>
            </div>

            <div>

                <div class="mrsv-client-name">
                    <?php
                    echo htmlspecialchars(
                        $r['prenom_client'].' '.$r['nom_client']
                    );
                    ?>
                </div>

                <div class="mrsv-client-mail">
                    <?php echo htmlspecialchars($r['email_client']); ?>
                </div>

                <div class="mrsv-client-phone">
                    📞 <?php echo htmlspecialchars($r['telephone']); ?>
                </div>

            </div>

        </div>

        <div class="mrsv-reservation-id">
            #<?php echo (int)$r['id_reservation']; ?>
        </div>

    </div>

    <div class="mrsv-service-box">

        <div class="mrsv-service-title">
            <?php echo htmlspecialchars($r['titre_service']); ?>
        </div>

        <div class="mrsv-service-category">
            <?php echo htmlspecialchars($r['nom_categorie']); ?>
        </div>

        <div class="mrsv-service-price">
            <?php echo number_format($r['prix_service'],2); ?> €
        </div>

    </div>

    <div class="mrsv-infos">

        <div class="mrsv-info-box">

            <div class="mrsv-info-label">
                Date souhaitée
            </div>

            <div class="mrsv-info-value">
                📅 <?php echo date('d/m/Y', strtotime($r['date_souhaitee'])); ?>
            </div>

        </div>

        <div class="mrsv-info-box">

            <div class="mrsv-info-label">
                Reçue le
            </div>

            <div class="mrsv-info-value">
                🕓 <?php echo date('d/m/Y', strtotime($r['date_creation'])); ?>
            </div>

        </div>

    </div>

    <div class="mrsv-message">

        <?php
        echo !empty($r['message'])
            ? htmlspecialchars($r['message'])
            : 'Aucun message ajouté par le client.';
        ?>

    </div>

    <span class="badge-statut <?php echo $badgeClass; ?>">
        <?php echo htmlspecialchars($r['statut']); ?>
    </span>

    <div class="mrsv-actions">

        <?php if ($r['statut'] === 'En attente'): ?>

        <form method="POST">

            <input type="hidden" name="action" value="statut">
            <input type="hidden" name="id" value="<?php echo $r['id_reservation']; ?>">
            <input type="hidden" name="statut" value="Confirmée">

            <button type="submit" class="btn-conf">
                ✓ Confirmer
            </button>

        </form>

        <form method="POST">

            <input type="hidden" name="action" value="statut">
            <input type="hidden" name="id" value="<?php echo $r['id_reservation']; ?>">
            <input type="hidden" name="statut" value="Annulée">

            <button type="submit" class="btn-ann">
                ✕ Refuser
            </button>

        </form>

        <?php elseif ($r['statut'] === 'Confirmée'): ?>

        <form method="POST">

            <input type="hidden" name="action" value="statut">
            <input type="hidden" name="id" value="<?php echo $r['id_reservation']; ?>">
            <input type="hidden" name="statut" value="Annulée">

            <button type="submit" class="btn-ann">
                ✕ Annuler
            </button>

        </form>

        <?php else: ?>

        <form method="POST">

            <input type="hidden" name="action" value="statut">
            <input type="hidden" name="id" value="<?php echo $r['id_reservation']; ?>">
            <input type="hidden" name="statut" value="En attente">

            <button type="submit" class="btn-conf">
                ↩ Réouvrir
            </button>

        </form>

        <?php endif; ?>

        <a href="index.php?page=myReservations&delete=<?php echo $r['id_reservation']; ?>"
           class="btn-del">
            🗑
        </a>

    </div>

</div>

<?php endforeach; ?>

</div>

<?php if ($totalPages > 1): ?>

<div class="mrsv-pagination">

<?php for($i = 1; $i <= $totalPages; $i++): ?>

<a href="index.php?page=myReservations&p=<?php echo $i; ?><?php echo $filtreStatut ? '&statut='.urlencode($filtreStatut) : ''; ?>"
   class="<?php echo $i == $pageActuelle ? 'active' : ''; ?>">

    <?php echo $i; ?>

</a>

<?php endfor; ?>

</div>

<?php endif; ?>

</div>