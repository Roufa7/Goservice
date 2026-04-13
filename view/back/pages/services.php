<section class="action-bar reveal">
    <div class="search-box">
        <input type="text" placeholder="Rechercher un service ou une catégorie...">
        <select>
            <option>Tous les statuts</option>
            <option>Validé</option>
            <option>En attente</option>
            <option>Désactivé</option>
        </select>
        <select>
            <option>Trier par</option>
            <option>Nom</option>
            <option>Prix</option>
            <option>Date</option>
        </select>
    </div>

    <div class="export-bar">
        <button class="outline-btn">Exporter</button>
    </div>
</section>

<section class="admin-stats reveal">
    <article class="admin-stat"><strong>324</strong><span>Services</span></article>
    <article class="admin-stat"><strong>18</strong><span>Catégories</span></article>
    <article class="admin-stat"><strong>29</strong><span>En attente</span></article>
    <article class="admin-stat"><strong>11</strong><span>Désactivés</span></article>
</section>

<section class="admin-panel reveal">
    <span class="section-badge">Gestion des services</span>
    <table class="module-table">
        <thead>
            <tr>
                <th>Service</th>
                <th>Catégorie</th>
                <th>Prix</th>
                <th>Disponibilité</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Installation électrique</td>
                <td>Électricité</td>
                <td>220 TND</td>
                <td>Disponible</td>
                <td>Validé</td>
                <td class="admin-tools">
                    <button class="small-btn">Voir</button>
                    <button class="small-btn">Modifier</button>
                    <button class="danger-btn">Supprimer</button>
                </td>
            </tr>
            <tr>
                <td>Support plomberie rapide</td>
                <td>Plomberie</td>
                <td>120 TND</td>
                <td>Rapide</td>
                <td>En attente</td>
                <td class="admin-tools">
                    <button class="success-btn">Valider</button>
                    <button class="small-btn">Modifier</button>
                    <button class="danger-btn">Supprimer</button>
                </td>
            </tr>
            <tr>
                <td>Pack identité visuelle</td>
                <td>Design</td>
                <td>350 TND</td>
                <td>Disponible</td>
                <td>Désactivé</td>
                <td class="admin-tools">
                    <button class="success-btn">Réactiver</button>
                    <button class="small-btn">Modifier</button>
                    <button class="danger-btn">Supprimer</button>
                </td>
            </tr>
        </tbody>
    </table>
</section>

<section class="admin-panel reveal" style="margin-top: 22px;">
    <span class="section-badge">Catégories</span>
    <table class="module-table">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Description</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Plomberie</td>
                <td>Services de dépannage et maintenance</td>
                <td class="admin-tools">
                    <button class="small-btn">Modifier</button>
                    <button class="danger-btn">Supprimer</button>
                </td>
            </tr>
            <tr>
                <td>Design</td>
                <td>Création visuelle et branding</td>
                <td class="admin-tools">
                    <button class="small-btn">Modifier</button>
                    <button class="danger-btn">Supprimer</button>
                </td>
            </tr>
        </tbody>
    </table>
</section>