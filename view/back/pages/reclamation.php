<section class="action-bar reveal">
    <div class="search-box">
        <input type="text" placeholder="Rechercher une réclamation, client ou sujet...">
        <select>
            <option>Tous les statuts</option>
            <option>Nouvelle</option>
            <option>En cours</option>
            <option>Traitée</option>
            <option>Fermée</option>
        </select>
        <select>
            <option>Trier par</option>
            <option>Date</option>
            <option>Priorité</option>
            <option>Statut</option>
        </select>
    </div>

    <div class="export-bar">
        <button class="outline-btn" onclick="loadAdminData()">Actualiser</button>
    </div>
</section>

<section class="admin-stats reveal">
    <article class="admin-stat"><strong id="stat-total">0</strong><span>Réclamations</span></article>
    <article class="admin-stat"><strong id="stat-pending">0</strong><span>Nouvelles</span></article>
    <article class="admin-stat"><strong id="stat-progress">0</strong><span>En cours</span></article>
    <article class="admin-stat"><strong id="stat-resolved">0</strong><span>Traitées</span></article>
</section>

<section class="admin-panel reveal">
    <span class="section-badge">Gestion des réclamations</span>
    <div class="table-wrap">

    <table class="module-table">
        <thead>
            <tr>
                <th>Client</th>
                <th>Sujet</th>
                <th>Message</th>
                <th>Date</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="admin-reclamations-list">
            <tr><td colspan="6">Chargement...</td></tr>
        </tbody>
    </table>
    </div>

</section>

<section class="admin-panel reveal" style="margin-top: 22px;">
    <span class="section-badge">Réponses envoyées</span>
    <div class="table-wrap">
    <table class="module-table">
        <thead>
            <tr>
                <th>Client</th>
                <th>Sujet</th>
                <th>Réponse admin</th>
                <th>Date réponse</th>
                <th>Statut final</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="admin-responses-list">
            <tr><td colspan="6">Chargement...</td></tr>
        </tbody>
    </table>
    </div>

</section>

<section class="admin-panel reveal" style="margin-top: 22px;" id="response-panel">
    <span class="section-badge">Réponse / Traitement rapide</span>
    <form id="admin-reclamation-form">
        <input type="hidden" id="admin-rec-id" name="id_reclamation">
        <input type="hidden" name="action" value="process_reclamation">
        
        <div class="form-grid">
            <input type="text" id="admin-client-name" placeholder="Nom du client" readonly style="opacity: 0.6; pointer-events: none;">
            <input type="text" id="admin-rec-subject" placeholder="Sujet de la réclamation" readonly style="opacity: 0.6; pointer-events: none;">
            
            <select name="status" id="admin-rec-status">
                <option value="">-- Mettre à jour le statut --</option>
                <option value="pending">En attente (Nouvelle)</option>
                <option value="in_progress">En cours</option>
                <option value="resolved">Résolue (Traitée)</option>
                <option value="rejected">Rejetée (Fermée)</option>
            </select>
            
            <input type="text" placeholder="Priorité" readonly style="opacity: 0.6; pointer-events: none;" value="Normale">
            
            <textarea name="content" id="admin-rec-response" placeholder="Rédiger une réponse à la réclamation... (Optionnel si vous majez juste le statut)"></textarea>
        </div>

        <div id="admin-form-msg" style="margin-top:10px; display:none;"></div>
        <div class="icon-actions" style="margin-top: 14px;">
            <button type="submit" class="solid-btn">Traiter / Envoyer</button>
            <button type="button" class="outline-btn" style="display:none;" id="btn-cancel-admin" onclick="cancelAdminEdit()">Annuler</button>
        </div>
    </form>
</section>

<script src="../../controller/back/admin_reclamation.js?v=<?= time() ?>"></script>