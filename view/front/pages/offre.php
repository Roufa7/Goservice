<?php
require_once __DIR__ . '/../../../config.php';
require_once __DIR__ . '/../../../model/Offer.php';
require_once __DIR__ . '/../../../model/Application.php';
require_once __DIR__ . '/../../../controller/OfferController.php';
require_once __DIR__ . '/../../../controller/ApplicationController.php';

$pdo = config::getConnexion();
$offerModel = new Offer($pdo);
$offerController = new OfferController($offerModel);
$applicationModel = new Application($pdo);
$applicationController = new ApplicationController($applicationModel);

$activeOffers = $offerModel->findActive();
$message = '';
$messageType = '';

// Traiter la soumission du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_application') {
    try {
        $offerId = intval($_POST['offer_id'] ?? 0);
        if ($offerId <= 0 || !$offerController->getOffer($offerId)) {
            throw new Exception('Offre invalide. Veuillez sélectionner une offre existante.');
        }

        $cvPath = null;
        if (isset($_FILES['cv']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
            $cvPath = $applicationController->uploadCV($_FILES['cv']);
        }

        $applicationController->submitApplication([
            'offer_id' => $offerId,
            'user_id' => null,
            'nom' => htmlspecialchars($_POST['nom'] ?? ''),
            'email' => filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL),
            'experience' => htmlspecialchars($_POST['experience'] ?? ''),
            'competences' => htmlspecialchars($_POST['competences'] ?? ''),
            'cv_path' => $cvPath,
            'message' => htmlspecialchars($_POST['message'] ?? ''),
            'statut' => 'en_attente',
        ]);

        $message = '✅ Candidature soumise avec succès ! Nous vous recontacterons bientôt.';
        $messageType = 'success';
    } catch (Exception $e) {
        $message = '❌ Erreur : ' . htmlspecialchars($e->getMessage());
        $messageType = 'error';
    }
}

function formatOfferDate(?string $value): string {
    return $value ? date('d/m/Y', strtotime($value)) : 'N/A';
}

function getOfferBadgeClass(string $statut): string {
    return match($statut) {
        'active' => 'badge-active',
        'inactive' => 'badge-inactive',
        'expiree' => 'badge-expired',
        default => 'badge-default'
    };
}
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
    <span class="section-badge">Offres Spéciales</span>
    <h1 class="page-title">Découvrez nos meilleures offres</h1>
    <p class="page-intro">
        Des offres exclusives et des réductions exceptionnelles sur les services de nos meilleurs prestataires.
    </p>
</section>

<section class="action-bar reveal">
    <div class="search-box">
        <input type="text" id="searchOffers" placeholder="Rechercher une offre...">
        <select id="filterOffers">
            <option value="">Toutes les offres</option>
            <option value="active">Offres Actives</option>
            <option value="prix">Trier par Prix</option>
            <option value="date">Plus Récentes</option>
        </select>
    </div>

    <div class="icon-actions">
        <a class="solid-btn" href="#mes-candidatures">📋 Mes Candidatures</a>
    </div>
</section>

<section class="admin-stats reveal">
    <article class="admin-stat">
        <strong><?php echo htmlspecialchars(count($activeOffers), ENT_QUOTES, 'UTF-8'); ?></strong>
        <span>Offres Actives</span>
    </article>
    <article class="admin-stat">
        <strong><?php echo htmlspecialchars(count(array_filter($activeOffers, fn($o) => $o['prix'] > 0)), ENT_QUOTES, 'UTF-8'); ?></strong>
        <span>Réductions Disponibles</span>
    </article>
    <article class="admin-stat">
        <strong><?php echo number_format(array_reduce($activeOffers, fn($sum, $o) => $sum + ($o['prix'] ?? 0), 0), 2); ?> €</strong>
        <span>Économies Potentielles</span>
    </article>
</section>

<section class="module-split reveal">
    <div style="flex: 1;">
        <?php if (empty($activeOffers)): ?>
            <article class="card" style="text-align: center; padding: 40px;">
                <h3>Aucune offre disponible pour le moment</h3>
                <p style="color: #666; margin-top: 10px;">Revenez bientôt pour découvrir de nouvelles offres exclusives !</p>
            </article>
        <?php else: ?>
            <?php foreach ($activeOffers as $offer): ?>
                <article class="card offer-card">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 12px;">
                        <span class="section-badge"><?php echo htmlspecialchars($offer['service_categorie'] ?: 'Service', ENT_QUOTES, 'UTF-8'); ?></span>
                        <span class="badge <?php echo htmlspecialchars(getOfferBadgeClass($offer['statut']), ENT_QUOTES, 'UTF-8'); ?>" style="font-size: 11px; padding: 4px 8px; border-radius: 4px; background: #4CAF50; color: white;">
                            <?php echo htmlspecialchars(ucfirst($offer['statut']), ENT_QUOTES, 'UTF-8'); ?>
                        </span>
                    </div>

                    <h3 style="margin: 10px 0; color: #333;">
                        <?php echo htmlspecialchars($offer['titre'], ENT_QUOTES, 'UTF-8'); ?>
                    </h3>

                    <p style="color: #666; margin: 10px 0; line-height: 1.6;">
                        <?php echo htmlspecialchars(substr($offer['description'], 0, 120) . (strlen($offer['description']) > 120 ? '...' : ''), ENT_QUOTES, 'UTF-8'); ?>
                    </p>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin: 15px 0; padding: 12px; background: #f5f5f5; border-radius: 6px;">
                        <div>
                            <strong style="font-size: 12px; color: #999;">Prix</strong>
                            <p style="font-size: 18px; color: #4CAF50; margin: 5px 0; font-weight: bold;">
                                <?php echo htmlspecialchars($offer['prix'] > 0 ? $offer['prix'] . '€' : 'À déterminer', ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        </div>
                        <div>
                            <strong style="font-size: 12px; color: #999;">Valide jusqu'au</strong>
                            <p style="font-size: 14px; color: #333; margin: 5px 0;">
                                <?php echo htmlspecialchars(formatOfferDate($offer['date_fin']), ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                        </div>
                    </div>

                    <div class="meta-row" style="font-size: 13px; color: #999; margin: 10px 0;">
                        <span>📅 Publié : <?php echo htmlspecialchars(formatOfferDate($offer['date_debut']), ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>

                    <div class="icon-actions" style="margin-top: 14px;">
                        <a class="solid-btn postuler-btn" href="#postuler-offre" data-offer-id="<?php echo htmlspecialchars($offer['id'], ENT_QUOTES, 'UTF-8'); ?>" data-offer-title="<?php echo htmlspecialchars($offer['titre'], ENT_QUOTES, 'UTF-8'); ?>">✓ Postuler</a>
                        <button class="small-btn" onclick="toggleDetails(this)">Voir détails</button>
                    </div>

                    <div class="offer-details" style="display: none; margin-top: 15px; padding-top: 15px; border-top: 1px solid #eee;">
                        <p><strong>Description complète :</strong></p>
                        <p style="color: #666; line-height: 1.6;">
                            <?php echo htmlspecialchars($offer['description'], ENT_QUOTES, 'UTF-8'); ?>
                        </p>
                    </div>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div style="flex: 0.3; min-width: 300px;">
        <?php if (!empty($activeOffers)): ?>
            <article class="panel" id="postuler-offre">
                <span class="section-badge">📨 Postuler</span>

                <form method="POST" enctype="multipart/form-data" id="applicationForm" style="margin-top: 15px;">
                    <input type="hidden" name="action" value="submit_application">
                    <select name="offer_id" id="offerIdField" style="display:none;">
                        <?php foreach ($activeOffers as $offerOption): ?>
                            <option value="<?php echo htmlspecialchars($offerOption['id'], ENT_QUOTES, 'UTF-8'); ?>"<?php echo (string) $offerOption['id'] === (string) ($activeOffers[0]['id'] ?? '') ? ' selected' : ''; ?>><?php echo htmlspecialchars($offerOption['titre'], ENT_QUOTES, 'UTF-8'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p id="selectedOfferLabel" style="font-weight:700; margin-bottom:0.75rem;">
                        Offre sélectionnée : <span><?php echo htmlspecialchars($activeOffers[0]['titre'] ?? 'Aucune offre', ENT_QUOTES, 'UTF-8'); ?></span>
                    </p>
                    <div class="form-grid" style="gap: 10px;">
                        <input type="text" name="nom" placeholder="Votre nom" required style="padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <input type="email" name="email" placeholder="Votre email" required style="padding: 10px; border: 1px solid #ddd; border-radius: 4px;">

                        <input type="text" name="experience" placeholder="Années d'expérience" required style="padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                        <input type="text" name="competences" placeholder="Vos compétences clés" required style="padding: 10px; border: 1px solid #ddd; border-radius: 4px;">

                        <input type="file" name="cv" accept=".pdf,.doc,.docx" required style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">

                        <textarea name="message" placeholder="Lettre de motivation..." rows="4" style="padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: Arial, sans-serif;"></textarea>
                    </div>

                    <div class="icon-actions" style="margin-top: 14px; flex-direction: column;">
                        <button type="submit" class="solid-btn" style="width: 100%;">Envoyer ma candidature</button>
                        <button type="reset" class="outline-btn" style="width: 100%; margin-top: 8px;">Réinitialiser</button>
                    </div>
                </form>
            </article>
        <?php else: ?>
            <article class="panel" id="postuler-offre">
                <span class="section-badge">📨 Postuler</span>
                <div style="margin-top: 15px; padding: 20px; background: #fff6f6; border: 1px solid #f5c6cb; border-radius: 12px; color: #721c24;">
                    <strong>Aucune offre active disponible</strong>
                    <p style="margin: 10px 0 0; font-size: 14px; line-height: 1.5;">Vous ne pouvez pas postuler pour le moment car il n’y a aucune offre active dans le catalogue.</p>
                </div>
            </article>
        <?php endif; ?>

        <div class="panel" style="margin-top: 18px;">
            <span class="section-badge">✨ Avantages</span>
            <div class="feature-list">
                <div class="feature-item">✓ Offres exclusives</div>
                <div class="feature-item">✓ Prestataires vérifiés</div>
                <div class="feature-item">✓ Paiement sécurisé</div>
                <div class="feature-item">✓ Support client 24/7</div>
                <div class="feature-item">✓ Garantie satisfaction</div>
            </div>
        </div>
    </div>
</section>

<script>
function toggleDetails(button) {
    const card = button.closest('.card');
    const details = card.querySelector('.offer-details');
    if (details) {
        details.style.display = details.style.display === 'none' ? 'block' : 'none';
        button.textContent = details.style.display === 'none' ? 'Voir détails' : 'Masquer détails';
    }
}

document.querySelectorAll('.postuler-btn').forEach(button => {
    button.addEventListener('click', function(event) {
        event.preventDefault();
        const offerId = this.dataset.offerId;
        const offerTitle = this.dataset.offerTitle;
        const offerField = document.getElementById('offerIdField');
        const selectedLabel = document.getElementById('selectedOfferLabel');

        if (offerField) {
            offerField.value = offerId;
        }

        if (selectedLabel) {
            selectedLabel.querySelector('span').textContent = offerTitle;
        }

        location.hash = '#postuler-offre';
    });
});

document.getElementById('searchOffers')?.addEventListener('input', function(e) {
    const search = e.target.value.toLowerCase();
    document.querySelectorAll('.offer-card').forEach(card => {
        const text = card.textContent.toLowerCase();
        card.style.display = text.includes(search) ? 'block' : 'none';
    });
});
</script>

<style>
.offer-card {
    border-left: 4px solid #4CAF50 !important;
    transition: transform 0.2s, box-shadow 0.2s;
}

.offer-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
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
</style>