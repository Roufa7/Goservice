<section class="action-bar reveal">
    <div class="search-box">
        <input type="text" placeholder="Rechercher un utilisateur, email ou provider...">
        <select>
            <option>Tous les rôles</option>
            <option>Utilisateur</option>
            <option>Provider</option>
            <option>Admin</option>
        </select>
        <select>
            <option>Trier par</option>
            <option>Nom</option>
            <option>Email</option>
            <option>Rôle</option>
        </select>
    </div>

    <div class="export-bar">
        <button class="outline-btn">Exporter</button>
    </div>
</section>

<section class="admin-stats reveal">
    <article class="admin-stat"><strong>128</strong><span>Utilisateurs</span></article>
    <article class="admin-stat"><strong>34</strong><span>Providers</span></article>
    <article class="admin-stat"><strong>05</strong><span>Admins</span></article>
    <article class="admin-stat"><strong>17</strong><span>Profils incomplets</span></article>
</section>

<section class="admin-panel reveal">
    <span class="section-badge">Gestion des utilisateurs</span>
    <table class="module-table">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Email</th>
                <th>Rôle</th>
                <th>Téléphone</th>
                <th>Adresse</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Ben Salah</td>
                <td>Ahmed</td>
                <td>ahmed@email.com</td>
                <td>Utilisateur</td>
                <td>22 111 333</td>
                <td>Tunis</td>
                <td class="admin-tools">
                    <button class="small-btn">Voir</button>
                    <button class="small-btn">Modifier</button>
                    <button class="danger-btn">Supprimer</button>
                </td>
            </tr>
            <tr>
                <td>Jaziri</td>
                <td>Sarra</td>
                <td>sarra@email.com</td>
                <td>Provider</td>
                <td>55 777 121</td>
                <td>Sousse</td>
                <td class="admin-tools">
                    <button class="small-btn">Voir</button>
                    <button class="small-btn">Modifier</button>
                    <button class="danger-btn">Supprimer</button>
                </td>
            </tr>
            <tr>
                <td>Trabelsi</td>
                <td>Rym</td>
                <td>rym@email.com</td>
                <td>Admin</td>
                <td>20 456 888</td>
                <td>Nabeul</td>
                <td class="admin-tools">
                    <button class="small-btn">Voir</button>
                    <button class="small-btn">Modifier</button>
                </td>
            </tr>
        </tbody>
    </table>
</section>

<section class="admin-panel reveal" style="margin-top: 22px;">
    <span class="section-badge">Profils providers</span>
    <table class="module-table">
        <thead>
            <tr>
                <th>Provider</th>
                <th>Spécialité</th>
                <th>Description</th>
                <th>Disponibilité</th>
                <th>Portfolio</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Sarra Jaziri</td>
                <td>Design graphique</td>
                <td>Création visuelle et identité de marque</td>
                <td>Disponible</td>
                <td>4 éléments</td>
                <td class="admin-tools">
                    <button class="small-btn">Consulter</button>
                    <button class="small-btn">Modifier</button>
                </td>
            </tr>
            <tr>
                <td>Ahmed Ben Salah</td>
                <td>Développement web</td>
                <td>Sites vitrines et solutions digitales</td>
                <td>Indisponible</td>
                <td>2 éléments</td>
                <td class="admin-tools">
                    <button class="small-btn">Consulter</button>
                    <button class="small-btn">Modifier</button>
                </td>
            </tr>
        </tbody>
    </table>
</section>

<section class="admin-panel reveal" style="margin-top: 22px;">
    <span class="section-badge">Actions administratives</span>
    <div class="feature-list">
        <div class="feature-item">Gérer tous les utilisateurs</div>
        <div class="feature-item">Consulter les profils providers</div>
        <div class="feature-item">Modérer le contenu</div>
        <div class="feature-item">Suivre les comptes par rôle</div>
    </div>
</section>