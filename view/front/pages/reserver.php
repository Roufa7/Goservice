<?php
// view/front/pages/reserver.php
// Page de réservation d'un service

require_once __DIR__ . '/../../../controller/ServiceController.php';
require_once __DIR__ . '/../../../controller/ReservationController.php';
require_once __DIR__ . '/../../../model/Reservation.php';
require_once __DIR__ . '/../../../service/MailService.php';

$serviceController     = new ServiceController();
$reservationController = new ReservationController();

// Récupérer le service
$id_service = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$service    = $serviceController->getServiceWithCategory($id_service);

if (!$service) {
    header('Location: index.php?page=services');
    exit;
}

$errors  = [];
$success = false;

// ── Traitement du formulaire ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom           = trim($_POST['nom']           ?? '');
    $prenom        = trim($_POST['prenom']        ?? '');
    $email         = trim($_POST['email']         ?? '');
    $telephone     = trim($_POST['telephone']     ?? '');
    $date_souhaitee= trim($_POST['date_souhaitee']?? '');
    $message       = trim($_POST['message']       ?? '');

    // Validation PHP (backup si JS désactivé)
    if (strlen($nom) < 2)
        $errors[] = "Le nom doit contenir au moins 2 caractères.";
    if (strlen($prenom) < 2)
        $errors[] = "Le prénom doit contenir au moins 2 caractères.";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        $errors[] = "L'adresse email est invalide.";
    if (!preg_match('/^[0-9+\s]{8,15}$/', $telephone))
        $errors[] = "Le numéro de téléphone est invalide.";
    if (empty($date_souhaitee) || strtotime($date_souhaitee) < strtotime('today'))
        $errors[] = "La date doit être aujourd'hui ou dans le futur.";

    if (empty($errors)) {
        $reservation = new Reservation(
            $id_service,
            $nom,
            $prenom,
            $email,
            $telephone,
            $date_souhaitee,
            $message,
            'En attente'
        );
        $id_new = $reservationController->addReservation($reservation);

        // ── Email de confirmation au client ──────────────────
        MailService::sendConfirmationReservation([
            'email_client'   => $email,
            'prenom_client'  => $prenom,
            'nom_client'     => $nom,
            'titre_service'  => $service['titre'],
            'prix'           => number_format((float)$service['prix'], 2, ',', ''),
            'categorie'      => $service['nom_categorie'] ?? 'Service',
            'date_souhaitee' => $date_souhaitee,
            'id_reservation' => $id_new,
        ]);

        header('Location: index.php?page=confirmation&id=' . $id_new);
        exit;
    }
}

$titre     = htmlspecialchars($service['titre']);
$categorie = htmlspecialchars($service['nom_categorie'] ?? 'Service');
$prix      = number_format((float)$service['prix'], 2, ',', '');
$img       = !empty($service['image'])
    ? '/GoService_v3/' . ltrim($service['image'], '/')
    : '/GoService/assets/images/service/default.jpg';
?>

<style>
.rsv-wrap{padding:28px 32px 48px;max-width:900px;margin:0 auto;}
.rsv-head{margin-bottom:28px;}
.rsv-head-top{display:flex;align-items:center;gap:8px;margin-bottom:6px;}
.rsv-back{color:var(--muted);font-size:13px;text-decoration:none;display:flex;align-items:center;gap:5px;transition:color .2s;}
.rsv-back:hover{color:var(--orange);}
.rsv-head h1{font-family:'Poppins',sans-serif;font-size:26px;font-weight:700;color:var(--text);}
.rsv-head p{font-size:14px;color:var(--muted);margin-top:4px;}

.rsv-grid{display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start;}

/* Formulaire */
.rsv-form-card{background:var(--card);border:1px solid var(--line);border-radius:var(--radius-lg);padding:28px;}
.rsv-form-title{font-family:'Poppins',sans-serif;font-size:17px;font-weight:700;color:var(--text);margin-bottom:20px;}
.rsv-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px;}
.rsv-row.single{grid-template-columns:1fr;}
.rsv-field{display:flex;flex-direction:column;gap:5px;}
.rsv-label{font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;}
.rsv-input,.rsv-textarea{padding:11px 14px;border:1px solid var(--line);border-radius:10px;background:var(--bg);color:var(--text);font-size:14px;font-family:inherit;outline:none;transition:border-color .2s,box-shadow .2s;}
.rsv-input:focus,.rsv-textarea:focus{border-color:var(--orange);box-shadow:0 0 0 3px rgba(238,88,40,0.12);}
.rsv-input.invalid,.rsv-textarea.invalid{border-color:#e53935;}
.rsv-input.valid,.rsv-textarea.valid{border-color:#4CAF50;}
.rsv-textarea{resize:vertical;min-height:100px;}
.rsv-field-err{display:none;font-size:12px;color:#e53935;margin-top:3px;}
.rsv-field-err::before{content:"✕ ";}
.rsv-field-err.show{display:block;}
.rsv-field-ok{display:none;font-size:12px;color:#2e7d32;margin-top:3px;}
.rsv-field-ok::before{content:"✓ ";}
.rsv-field-ok.show{display:block;}

.rsv-submit{width:100%;background:var(--orange);color:white;border:none;border-radius:12px;padding:14px;font-size:15px;font-weight:700;font-family:'Poppins',sans-serif;cursor:pointer;margin-top:20px;transition:all .2s;}
.rsv-submit:hover{background:#d44a1a;transform:translateY(-1px);box-shadow:0 6px 20px rgba(238,88,40,0.3);}

/* Carte service résumé */
.rsv-summary{background:linear-gradient(180deg,#0e2941,#102d47);border-radius:var(--radius-lg);overflow:hidden;position:sticky;top:24px;}
.rsv-summary-img{width:100%;height:180px;object-fit:cover;display:block;}
.rsv-summary-body{padding:20px;}
.rsv-summary-cat{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--orange);margin-bottom:6px;}
.rsv-summary-title{font-family:'Poppins',sans-serif;font-size:18px;font-weight:700;color:#fff;margin-bottom:14px;}
.rsv-summary-price{font-size:28px;font-weight:800;color:var(--orange);margin-bottom:4px;}
.rsv-summary-price span{font-size:14px;font-weight:400;color:rgba(255,255,255,0.5);}
.rsv-info-row{display:flex;justify-content:space-between;font-size:13px;padding:8px 0;border-bottom:1px solid rgba(255,255,255,0.06);}
.rsv-info-row:last-child{border:none;}
.rsv-info-lbl{color:rgba(255,255,255,0.5);}
.rsv-info-val{color:#fff;font-weight:600;}
.rsv-info-val.ok{color:#4CAF50;}

.rsv-alert-err{background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.25);color:#c62828;border-radius:10px;padding:14px 16px;margin-bottom:20px;font-size:14px;}
.rsv-alert-err div{margin-bottom:4px;}
.rsv-alert-err div:last-child{margin:0;}

@media(max-width:768px){.rsv-grid{grid-template-columns:1fr;}.rsv-row{grid-template-columns:1fr;}}
</style>

<div class="rsv-wrap">
    <div class="rsv-head">
        <div class="rsv-head-top">
            <a href="index.php?page=serviceDetails&id=<?php echo $id_service; ?>" class="rsv-back">← Retour au service</a>
        </div>
        <h1>Réserver ce service</h1>
        <p>Remplissez le formulaire ci-dessous. Le prestataire vous confirmera votre réservation sous 24h.</p>
    </div>

    <div class="rsv-grid">

        <!-- FORMULAIRE -->
        <div class="rsv-form-card">
            <div class="rsv-form-title">Vos informations</div>

            <?php if (!empty($errors)): ?>
            <div class="rsv-alert-err">
                <?php foreach ($errors as $e): ?>
                <div>✕ <?php echo htmlspecialchars($e); ?></div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <form method="POST" id="rsvForm" novalidate>
                <input type="hidden" name="id_service" value="<?php echo $id_service; ?>">

                <div class="rsv-row">
                    <div class="rsv-field">
                        <label class="rsv-label" for="nom">Nom</label>
                        <input type="text" id="nom" name="nom" class="rsv-input"
                               placeholder="Ex : Ben Ali"
                               value="<?php echo htmlspecialchars($_POST['nom'] ?? ''); ?>"
                               autocomplete="family-name">
                        <div class="rsv-field-err" id="err-nom"></div>
                        <div class="rsv-field-ok"  id="ok-nom">Nom valide</div>
                    </div>
                    <div class="rsv-field">
                        <label class="rsv-label" for="prenom">Prénom</label>
                        <input type="text" id="prenom" name="prenom" class="rsv-input"
                               placeholder="Ex : Amine"
                               value="<?php echo htmlspecialchars($_POST['prenom'] ?? ''); ?>"
                               autocomplete="given-name">
                        <div class="rsv-field-err" id="err-prenom"></div>
                        <div class="rsv-field-ok"  id="ok-prenom">Prénom valide</div>
                    </div>
                </div>

                <div class="rsv-row">
                    <div class="rsv-field">
                        <label class="rsv-label" for="email">Email</label>
                        <input type="text" id="email" name="email" class="rsv-input"
                               placeholder="Ex : amine@gmail.com"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               autocomplete="email">
                        <div class="rsv-field-err" id="err-email"></div>
                        <div class="rsv-field-ok"  id="ok-email">Email valide</div>
                    </div>
                    <div class="rsv-field">
                        <label class="rsv-label" for="telephone">Téléphone</label>
                        <input type="text" id="telephone" name="telephone" class="rsv-input"
                               placeholder="Ex : +216 55 123 456"
                               value="<?php echo htmlspecialchars($_POST['telephone'] ?? ''); ?>"
                               autocomplete="tel">
                        <div class="rsv-field-err" id="err-telephone"></div>
                        <div class="rsv-field-ok"  id="ok-telephone">Téléphone valide</div>
                    </div>
                </div>

                <div class="rsv-row single">
                    <div class="rsv-field">
                        <label class="rsv-label" for="date_souhaitee">Date souhaitée</label>
                        <input type="text" id="date_souhaitee" name="date_souhaitee" class="rsv-input"
                               placeholder="AAAA-MM-JJ (ex : 2026-05-15)"
                               value="<?php echo htmlspecialchars($_POST['date_souhaitee'] ?? ''); ?>"
                               autocomplete="off">
                        <div class="rsv-field-err" id="err-date"></div>
                        <div class="rsv-field-ok"  id="ok-date">Date valide</div>
                    </div>
                </div>

                <div class="rsv-row single">
                    <div class="rsv-field">
                        <label class="rsv-label" for="message">Message (optionnel)</label>
                        <textarea id="message" name="message" class="rsv-textarea"
                                  placeholder="Décrivez votre besoin, adresse d'intervention..."><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                    </div>
                </div>

                <button type="submit" class="rsv-submit">Confirmer la réservation →</button>
            </form>
        </div>

        <!-- RÉSUMÉ SERVICE -->
        <div class="rsv-summary">
            <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo $titre; ?>"
                 class="rsv-summary-img"
                 onerror="this.src='/GoService_v3/assets/images/service/default.jpg'">
            <div class="rsv-summary-body">
                <div class="rsv-summary-cat"><?php echo $categorie; ?></div>
                <div class="rsv-summary-title"><?php echo $titre; ?></div>
                <div class="rsv-summary-price"><?php echo $prix; ?> <span>€ / séance</span></div>
                <div style="margin-top:14px;">
                    <div class="rsv-info-row">
                        <span class="rsv-info-lbl">Disponibilité</span>
                        <span class="rsv-info-val ok">Disponible</span>
                    </div>
                    <div class="rsv-info-row">
                        <span class="rsv-info-lbl">Délai de réponse</span>
                        <span class="rsv-info-val">24 - 48h</span>
                    </div>
                    <div class="rsv-info-row">
                        <span class="rsv-info-lbl">Statut réservation</span>
                        <span class="rsv-info-val" style="color:#F5A623;">En attente</span>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('rsvForm');

    // Règles JS — aucun attribut HTML5
    var rules = {
        nom:      { validate: function(v){ return v.trim().length >= 2; },
                    msg: "Au moins 2 caractères." },
        prenom:   { validate: function(v){ return v.trim().length >= 2; },
                    msg: "Au moins 2 caractères." },
        email:    { validate: function(v){ return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim()); },
                    msg: "Email invalide (ex: nom@domaine.com)." },
        telephone:{ validate: function(v){ return /^[0-9+\s]{8,15}$/.test(v.trim()); },
                    msg: "Numéro invalide (8-15 chiffres)." },
        date:     { validate: function(v){
                        if (!v.trim()) return false;
                        var d = new Date(v.trim());
                        var today = new Date(); today.setHours(0,0,0,0);
                        return !isNaN(d.getTime()) && d >= today;
                    },
                    msg: "Date invalide ou passée." }
    };

    function setInvalid(el, errId, msg) {
        el.classList.add('invalid'); el.classList.remove('valid');
        var e = document.getElementById(errId);
        if (e) { e.textContent = '✕ ' + msg; e.classList.add('show'); }
        var o = document.getElementById(errId.replace('err-','ok-'));
        if (o) o.classList.remove('show');
    }
    function setValid(el, errId) {
        el.classList.remove('invalid'); el.classList.add('valid');
        var e = document.getElementById(errId);
        if (e) e.classList.remove('show');
        var o = document.getElementById(errId.replace('err-','ok-'));
        if (o) o.classList.add('show');
    }

    function validate(id, errId, ruleKey) {
        var el = document.getElementById(id);
        if (!el) return true;
        if (rules[ruleKey].validate(el.value)) {
            setValid(el, errId); return true;
        }
        setInvalid(el, errId, rules[ruleKey].msg); return false;
    }

    // Listeners temps réel
    document.getElementById('nom').addEventListener('input',        function(){ validate('nom','err-nom','nom'); });
    document.getElementById('prenom').addEventListener('input',     function(){ validate('prenom','err-prenom','prenom'); });
    document.getElementById('email').addEventListener('input',      function(){ validate('email','err-email','email'); });
    document.getElementById('telephone').addEventListener('input',  function(){ validate('telephone','err-telephone','telephone'); });
    document.getElementById('date_souhaitee').addEventListener('input', function(){ validate('date_souhaitee','err-date','date'); });

    // Blur pour confirmer
    ['nom','prenom','email','telephone'].forEach(function(id) {
        document.getElementById(id).addEventListener('blur', function(){
            validate(id, 'err-'+id, id);
        });
    });

    // Submit
    form.addEventListener('submit', function(e) {
        var ok = true;
        if (!validate('nom',           'err-nom',       'nom'))       ok = false;
        if (!validate('prenom',        'err-prenom',    'prenom'))    ok = false;
        if (!validate('email',         'err-email',     'email'))     ok = false;
        if (!validate('telephone',     'err-telephone', 'telephone')) ok = false;
        if (!validate('date_souhaitee','err-date',      'date'))      ok = false;
        if (!ok) {
            e.preventDefault();
            var first = form.querySelector('.invalid');
            if (first) first.scrollIntoView({behavior:'smooth', block:'center'});
        }
    });
});
</script>