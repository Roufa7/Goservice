<?php
// Display messages if any
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

require_once dirname(__DIR__, 3) . '/model/Reclamation.php';
$reclamationModel = new Reclamation();
$userReclamations = $reclamationModel->readAllByUserId($_SESSION['user_id'] ?? 1);
?>

<!-- Add this right after the opening <body> tag or before the form -->
<?php if ($success_message): ?>
    <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 10px; margin: 10px 0; border-radius: 5px;">
        <?php echo htmlspecialchars($success_message); ?>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-error" style="background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 5px;">
        <?php echo htmlspecialchars($error_message); ?>
    </div>
<?php endif; ?>

<section class="page-hero reveal">
    <span class="section-badge">Réclamations & Avis</span>
    <h1 class="page-title">Mes réclamations, réponses et avis</h1>
    <p class="page-intro">
        Une page claire pour déposer une réclamation, suivre la réponse associée
        et consulter ou publier des avis dans une interface structurée.
    </p>
</section>

<section class="action-bar reveal">
    <div class="search-box">
        <input type="text" placeholder="Rechercher dans mes réclamations ou dans les avis...">
        <select>
            <option>Tous les statuts</option>
            <option>En attente</option>
            <option>Résolue</option>
            <option>Rejetée</option>
        </select>
    </div>

    <div class="icon-actions">
        <a class="solid-btn" href="#deposer-reclamation">+ Déposer une réclamation</a>
        <a class="outline-btn" href="#deposer-avis">+ Donner un avis</a>
    </div>
</section>

<section class="module-split reveal">
    <div id="reclamations-list">
        <!-- Reclamations will be loaded here via AJAX -->
        <p class="page-intro">Chargement de vos réclamations...</p>
    </div>

    <div>
        <article class="panel" id="deposer-reclamation">
            <span class="section-badge">Déposer / modifier</span>
            <form id="form-reclamation" method="POST">
                <input type="hidden" id="id-reclamation" name="id_reclamation" value="">
                <div class="form-grid">
                    <input type="text" id="sujet-reclamation" name="subject" placeholder="Sujet de la réclamation">
                    <textarea id="desc-reclamation" name="description" placeholder="Décrivez votre réclamation..."></textarea>
                </div>
                <div id="error-message" style="color: red; margin-top: 10px; display: none;"></div>
                <div id="success-message" style="color: green; margin-top: 10px; display: none;"></div>
                <div class="icon-actions" style="margin-top:14px; display:flex; gap:10px;">
                    <button type="submit" class="solid-btn" name="submit_reclamation">Envoyer</button>
                    <button type="button" class="outline-btn" id="btn-cancel-edit" style="display:none;" onclick="cancelEdit()">Annuler</button>
                </div>
            </form>
        </article>

        <article class="panel" style="margin-top:18px;">
            <span class="section-badge">Avis des utilisateurs</span>
            <div id="avis-list" class="comment-box">
                <p class="page-intro">Chargement des avis...</p>
            </div>
        </article>

        <article class="panel" style="margin-top:18px;" id="deposer-avis">
            <span class="section-badge">Donner / Modifier un avis</span>
            <form id="form-avis">
                <input type="hidden" id="id-avis" name="id_avis" value="">
                <input type="hidden" id="avis-rating" name="rating" value="5">
                
                <div class="form-grid">
                    <select id="avis-reclamation-id" name="id_reclamation" style="padding: 12px; border: 1px solid #e1e4e8; border-radius: 8px; font-size: 14px; margin-bottom: 10px; width: 100%;">
                        <option value="">-- Sélectionner la réclamation associée --</option>
                        <?php foreach($userReclamations as $rec): ?>
                            <option value="<?php echo $rec['id_reclamation']; ?>">
                                <?php echo htmlspecialchars($rec['subject']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <div class="star-input-group" style="font-size: 24px; cursor: pointer; user-select: none; margin-bottom: 10px;">
                        <span class="star" data-value="1" style="color:#ffd700">★</span>
                        <span class="star" data-value="2" style="color:#ffd700">★</span>
                        <span class="star" data-value="3" style="color:#ffd700">★</span>
                        <span class="star" data-value="4" style="color:#ffd700">★</span>
                        <span class="star" data-value="5" style="color:#ffd700">★</span>
                    </div>
                    
                    <textarea id="avis-commentaire" name="commentaire" placeholder="Votre avis..."></textarea>
                </div>
                <div id="avis-error-message" style="color: red; margin-top: 10px; display: none;"></div>
                <div id="avis-success-message" style="color: green; margin-top: 10px; display: none;"></div>
                <div class="icon-actions" style="margin-top:14px; display:flex; gap:10px;">
                    <button type="submit" class="solid-btn">Envoyer l'avis</button>
                    <button type="button" class="outline-btn" id="btn-cancel-avis" style="display:none;" onclick="cancelAvisEdit()">Annuler</button>
                </div>
            </form>
        </article>
    </div>
</section>

<script src="../../controller/reclamation.js?v=<?= time() ?>"></script>
<script src="../../controller/avis.js?v=<?= time() ?>"></script>