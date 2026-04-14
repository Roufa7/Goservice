<?php
// Display messages if any
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
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
    <span class="section-badge">Réclamations</span>
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
        <button class="outline-btn">Mes avis</button>
    </div>
</section>

<section class="module-split reveal">
    <div id="reclamations-list">
        <!-- Reclamations will be loaded here via AJAX -->
        <p class="page-intro">Chargement de vos réclamations...</p>
    </div>

    <div>
        <!-- Replace the form section in reclamation.php with this -->
<article class="panel" id="deposer-reclamation">
    <span class="section-badge">Déposer / modifier</span>
    <form id="form-reclamation" method="POST">
        <div class="form-grid">
            <input type="text" id="sujet-reclamation" name="subject" placeholder="Sujet de la réclamation">
            <textarea id="desc-reclamation" name="description" placeholder="Décrivez votre réclamation..."></textarea>
        </div>
        <div id="error-message" style="color: red; margin-top: 10px; display: none;"></div>
        <div id="success-message" style="color: green; margin-top: 10px; display: none;"></div>
        <div class="icon-actions" style="margin-top:14px;">
            <button type="submit" class="solid-btn" name="submit_reclamation">Envoyer</button>
        </div>
    </form>
</article>

        <article class="panel" style="margin-top:18px;">
            <span class="section-badge">Avis des utilisateurs</span>

            <div class="comment-box">
                <div class="comment-item">
                    <strong>Amira K.</strong>
                    <div class="review-stars" style="margin:6px 0 10px;">
                        <span class="star">★</span><span class="star">★</span><span class="star">★</span><span class="star">★</span><span class="star">☆</span>
                    </div>
                    <p>Support réactif, mais le délai de traitement aurait pu être plus court.</p>
                    <span class="page-intro">Publié le 10/04/2026</span>
                </div>

                <div class="comment-item">
                    <strong>Walid B.</strong>
                    <div class="review-stars" style="margin:6px 0 10px;">
                        <span class="star">★</span><span class="star">★</span><span class="star">★</span><span class="star">★</span><span class="star">★</span>
                    </div>
                    <p>Bonne prise en charge après réclamation, communication claire et suivi rassurant.</p>
                    <span class="page-intro">Publié le 08/04/2026</span>
                </div>
            </div>
        </article>

        <article class="panel" style="margin-top:18px;">
            <span class="section-badge">Fonctions prêtes</span>
            <div class="feature-list">
                <div class="feature-item">Déposer une réclamation</div>
                <div class="feature-item">Modifier / supprimer sa réclamation</div>
                <div class="feature-item">Suivre la réponse liée</div>
                <div class="feature-item">Publier un avis</div>
                <div class="feature-item">Consulter les avis des utilisateurs</div>
            </div>
        </article>
    </div>
</section>

<script src="../../controller/reclamation.js"></script>