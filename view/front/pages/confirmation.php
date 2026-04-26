<?php
// view/front/pages/confirmation.php
require_once __DIR__ . '/../../../controller/ReservationController.php';

$controller     = new ReservationController();
$id_reservation = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$reservation    = $id_reservation ? $controller->getReservation($id_reservation) : null;

if (!$reservation) {
    header('Location: index.php?page=services');
    exit;
}
?>
<style>
.conf-wrap{padding:60px 32px;max-width:620px;margin:0 auto;text-align:center;}
.conf-icon{font-size:64px;margin-bottom:20px;}
.conf-title{font-family:'Poppins',sans-serif;font-size:28px;font-weight:800;color:var(--text);margin-bottom:10px;}
.conf-subtitle{font-size:15px;color:var(--muted);line-height:1.7;margin-bottom:32px;}
.conf-card{background:var(--card);border:1px solid var(--line);border-radius:var(--radius-lg);padding:24px;text-align:left;margin-bottom:28px;}
.conf-card-title{font-family:'Poppins',sans-serif;font-size:14px;font-weight:700;color:var(--text);margin-bottom:14px;padding-bottom:10px;border-bottom:1px solid var(--line);}
.conf-row{display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--line);font-size:14px;}
.conf-row:last-child{border:none;}
.conf-lbl{color:var(--muted);}
.conf-val{color:var(--text);font-weight:600;}
.conf-val.id{color:var(--orange);font-family:monospace;font-size:16px;}
.conf-val.status{background:rgba(245,166,35,0.12);color:#d97706;border:1px solid rgba(245,166,35,0.25);border-radius:99px;padding:2px 10px;font-size:12px;}
.conf-actions{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;}
</style>

<div class="conf-wrap">
    <div class="conf-icon">✅</div>
    <h1 class="conf-title">Réservation envoyée !</h1>
    <p class="conf-subtitle">
        Votre demande a bien été enregistrée.<br>
        Le prestataire vous contactera sous <strong>24 à 48h</strong> pour confirmer votre réservation.
    </p>

    <div class="conf-card">
        <div class="conf-card-title">Récapitulatif de votre réservation</div>
        <div class="conf-row">
            <span class="conf-lbl">N° de réservation</span>
            <span class="conf-val id">#<?php echo $reservation['id_reservation']; ?></span>
        </div>
        <div class="conf-row">
            <span class="conf-lbl">Service</span>
            <span class="conf-val"><?php echo htmlspecialchars($reservation['titre_service']); ?></span>
        </div>
        <div class="conf-row">
            <span class="conf-lbl">Client</span>
            <span class="conf-val"><?php echo htmlspecialchars($reservation['prenom_client'] . ' ' . $reservation['nom_client']); ?></span>
        </div>
        <div class="conf-row">
            <span class="conf-lbl">Email</span>
            <span class="conf-val"><?php echo htmlspecialchars($reservation['email_client']); ?></span>
        </div>
        <div class="conf-row">
            <span class="conf-lbl">Date souhaitée</span>
            <span class="conf-val"><?php echo date('d/m/Y', strtotime($reservation['date_souhaitee'])); ?></span>
        </div>
        <div class="conf-row">
            <span class="conf-lbl">Statut</span>
            <span class="conf-val status"><?php echo htmlspecialchars($reservation['statut']); ?></span>
        </div>
    </div>

    <div class="conf-actions">
        <a href="index.php?page=services" class="solid-btn">← Voir d'autres services</a>
        <a href="index.php?page=home" class="ghost-btn">Accueil</a>
    </div>
</div>
