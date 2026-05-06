<?php
// Display messages if any
$success_message = $_SESSION['success_message'] ?? '';
$error_message = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);

require_once dirname(__DIR__, 3) . '/model/Reclamation.php';
$userReclamations = $GLOBALS['userReclamations'] ?? [];
?>

<!-- Add this right after the opening <body> tag or before the form -->
<?php if ($success_message): ?>
    <div class="alert alert-success"
        style="background: #d4edda; color: #155724; padding: 10px; margin: 10px 0; border-radius: 5px;">
        <?php echo htmlspecialchars($success_message); ?>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div class="alert alert-error"
        style="background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 5px;">
        <?php echo htmlspecialchars($error_message); ?>
    </div>
<?php endif; ?>

<style>
    .pagination-container {
        margin-top: 30px;
        display: flex;
        justify-content: center;
        gap: 12px;
        padding: 10px;
    }
    .pagination-container button {
        min-width: 45px;
        height: 45px;
        padding: 0 15px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 999px;
    }
    .pagination-container button.outline-btn {
        background: rgba(255, 255, 255, 0.5);
        border: 1px solid var(--line);
    }
    .pagination-container button.outline-btn:hover:not(:disabled) {
        background: var(--grad-soft);
        transform: translateY(-2px);
    }
    .pagination-container button.solid-btn {
        background: var(--grad-main);
        color: white;
        border: none;
        box-shadow: 0 8px 20px rgba(238, 88, 40, 0.2);
    }
    .pagination-container button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none !important;
    }

    #reclamations-list {
        max-height: 800px;
        overflow-y: auto;
        padding-right: 15px;
        scrollbar-width: thin;
        scrollbar-color: var(--orange) transparent;
        margin-bottom: 20px;
    }
    #reclamations-list::-webkit-scrollbar {
        width: 6px;
    }
    #reclamations-list::-webkit-scrollbar-track {
        background: transparent;
    }
    #reclamations-list::-webkit-scrollbar-thumb,
    #avis-list::-webkit-scrollbar-thumb {
        background-color: var(--orange);
        border-radius: 20px;
    }

    #avis-list {
        max-height: 400px;
        overflow-y: auto;
        padding-right: 10px;
        scrollbar-width: thin;
        scrollbar-color: var(--orange) transparent;
    }
    #avis-list::-webkit-scrollbar {
        width: 6px;
    }
    #avis-list::-webkit-scrollbar-track {
        background: transparent;
    }
</style>

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
    <div>
        <div id="reclamations-list">
            <!-- Reclamations will be loaded here via AJAX -->
            <p class="page-intro">Chargement de vos réclamations...</p>
        </div>
        <div id="reclamation-pagination" class="pagination-container"></div>
    </div>

    <div>
        <article class="panel" id="deposer-reclamation">
            <span class="section-badge">Déposer / modifier</span>
            <form id="form-reclamation" method="POST">
                <input type="hidden" id="id-reclamation" name="id_reclamation" value="">
                <div class="form-grid">
                    <input type="text" id="sujet-reclamation" name="subject" placeholder="Sujet de la réclamation">
                    <textarea id="desc-reclamation" name="description"
                        placeholder="Décrivez votre réclamation..."></textarea>
                </div>
                <div id="error-message" style="color: red; margin-top: 10px; display: none;"></div>
                <div id="success-message" style="color: green; margin-top: 10px; display: none;"></div>
                <div class="icon-actions" style="margin-top:14px; display:flex; gap:10px;">
                    <button type="submit" class="solid-btn" name="submit_reclamation">Envoyer</button>
                    <button type="button" class="outline-btn" id="btn-cancel-edit" style="display:none;"
                        onclick="cancelEdit()">Annuler</button>
                </div>
            </form>
        </article>

        <article class="panel" style="margin-top:18px;">
            <span class="section-badge">Avis des utilisateurs</span>
            <div id="avis-list" class="comment-box">
                <p class="page-intro">Chargement des avis...</p>
            </div>
            <div id="avis-pagination" class="pagination-container"></div>
        </article>

        <article class="panel" style="margin-top:18px;" id="deposer-avis">
            <span class="section-badge">Donner / Modifier un avis</span>
            <form id="form-avis">
                <input type="hidden" id="id-avis" name="id_avis" value="">
                <input type="hidden" id="avis-rating" name="rating" value="5">

                <div class="form-grid">
                    <select id="avis-reclamation-id" name="id_reclamation"
                        style="padding: 12px; border: 1px solid #e1e4e8; border-radius: 8px; font-size: 14px; margin-bottom: 10px; width: 100%;">
                        <option value="">-- Sélectionner la réclamation associée --</option>
                        <?php foreach ($userReclamations as $rec): ?>
                            <option value="<?php echo $rec['id_reclamation']; ?>">
                                <?php echo htmlspecialchars($rec['subject']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <div class="star-input-group"
                        style="font-size: 24px; cursor: pointer; user-select: none; margin-bottom: 10px;">
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
                    <button type="button" class="outline-btn" id="btn-cancel-avis" style="display:none;"
                        onclick="cancelAvisEdit()">Annuler</button>
                </div>
            </form>
        </article>
    </div>
</section>

<script src="../../controller/reclamation.js?v=<?= time() ?>"></script>
<script src="../../controller/avis.js?v=<?= time() ?>"></script>