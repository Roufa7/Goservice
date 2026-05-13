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
        <button class="outline-btn">Exporter</button>
    </div>
</section>

<section class="admin-stats reveal">
    <article class="admin-stat"><strong>42</strong><span>Réclamations</span></article>
    <article class="admin-stat"><strong>11</strong><span>Nouvelles</span></article>
    <article class="admin-stat"><strong>19</strong><span>En cours</span></article>
    <article class="admin-stat"><strong>12</strong><span>Traitées</span></article>
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
                <th>Priorité</th>
                <th>Date</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Ahmed Ben Salah</td>
                <td>Retard de service</td>
                <td>Le provider n’a pas respecté l’horaire convenu.</td>
                <td>Haute</td>
                <td>12/04/2026</td>
                <td>Nouvelle</td>
                <td class="admin-tools">
                    <button class="small-btn">Voir</button>
                    <button class="success-btn">Traiter</button>
                    <button class="small-btn">Répondre</button>
                </td>
            </tr>
            <tr>
                <td>Sarra Jaziri</td>
                <td>Problème de paiement</td>
                <td>Le montant affiché ne correspond pas au montant payé.</td>
                <td>Moyenne</td>
                <td>10/04/2026</td>
                <td>En cours</td>
                <td class="admin-tools">
                    <button class="small-btn">Voir</button>
                    <button class="small-btn">Répondre</button>
                    <button class="danger-btn">Fermer</button>
                </td>
            </tr>
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
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Amine Trabelsi</td>
                <td>Compte bloqué</td>
                <td>Votre demande a été vérifiée et le compte a été réactivé avec succès.</td>
                <td>08/04/2026</td>
                <td>Traitée</td>
            </tr>
            <tr>
                <td>Rym Ben Salah</td>
                <td>Service non conforme</td>
                <td>Nous avons transmis votre réclamation à l’équipe concernée et pris les mesures nécessaires.</td>
                <td>06/04/2026</td>
                <td>Fermée</td>
            </tr>
        </tbody>
    </table>
    </div>

</section>

<section class="admin-panel reveal" style="margin-top: 22px;">
    <span class="section-badge">Réponse rapide</span>
    <div class="form-grid">
        <input type="text" placeholder="Nom du client">
        <input type="text" placeholder="Sujet de la réclamation">
        <select>
            <option>Statut</option>
            <option>Nouvelle</option>
            <option>En cours</option>
            <option>Traitée</option>
            <option>Fermée</option>
        </select>
        <input type="text" placeholder="Priorité">
        <textarea placeholder="Rédiger une réponse à la réclamation..."></textarea>
    </div>

    <div class="icon-actions" style="margin-top: 14px;">
        <button class="solid-btn">Envoyer la réponse</button>
        <button class="outline-btn">Mettre à jour</button>
    </div>
</section>