<section class="action-bar reveal">
    <div class="search-box">
        <input type="text" placeholder="Rechercher un événement...">
        <select>
            <option>Tous les statuts</option>
            <option>Prévu</option>
            <option>En cours</option>
            <option>Terminé</option>
            <option>Annulé</option>
        </select>
        <select>
            <option>Trier par</option>
            <option>Date début</option>
            <option>Date fin</option>
            <option>Nombre de places</option>
        </select>
    </div>

    <div class="export-bar">
        <button class="outline-btn">Exporter</button>
    </div>
</section>

<section class="admin-stats reveal">
    <article class="admin-stat"><strong>24</strong><span>Événements</span></article>
    <article class="admin-stat"><strong>146</strong><span>Participations</span></article>
    <article class="admin-stat"><strong>07</strong><span>À venir</span></article>
    <article class="admin-stat"><strong>03</strong><span>Annulés</span></article>
</section>

<section class="admin-panel reveal">
    <span class="section-badge">Gestion des événements</span>
    <table class="module-table">
        <thead>
            <tr>
                <th>Titre</th>
                <th>Date début</th>
                <th>Date fin</th>
                <th>Lieu</th>
                <th>Type</th>
                <th>Places</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Atelier UI/UX Providers</td>
                <td>24/04/2026 09:00</td>
                <td>24/04/2026 13:00</td>
                <td>Lac 1</td>
                <td>Atelier</td>
                <td>35</td>
                <td>Prévu</td>
                <td class="admin-tools">
                    <button class="small-btn">Voir</button>
                    <button class="small-btn">Modifier</button>
                    <button class="danger-btn">Supprimer</button>
                </td>
            </tr>
            <tr>
                <td>Formation outils digitaux</td>
                <td>02/05/2026 14:00</td>
                <td>02/05/2026 18:00</td>
                <td>En ligne</td>
                <td>Formation</td>
                <td>80</td>
                <td>En cours</td>
                <td class="admin-tools">
                    <button class="small-btn">Voir</button>
                    <button class="small-btn">Modifier</button>
                    <button class="danger-btn">Supprimer</button>
                </td>
            </tr>
        </tbody>
    </table>
</section>

<section class="admin-panel reveal" style="margin-top: 22px;">
    <span class="section-badge">Participations</span>
    <table class="module-table">
        <thead>
            <tr>
                <th>Participant</th>
                <th>Email</th>
                <th>Téléphone</th>
                <th>Date inscription</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Ahmed Ben Salah</td>
                <td>ahmed@email.com</td>
                <td>22 111 333</td>
                <td>20/04/2026</td>
                <td>Confirmé</td>
                <td class="admin-tools">
                    <button class="small-btn">Modifier</button>
                    <button class="danger-btn">Annuler</button>
                </td>
            </tr>
            <tr>
                <td>Sarra Jaziri</td>
                <td>sarra@email.com</td>
                <td>55 777 121</td>
                <td>21/04/2026</td>
                <td>En attente</td>
                <td class="admin-tools">
                    <button class="success-btn">Confirmer</button>
                    <button class="danger-btn">Annuler</button>
                </td>
            </tr>
        </tbody>
    </table>
</section>