<section class="action-bar reveal"> 
    <div class="search-box">
        <input type="text" placeholder="Rechercher un post ou un commentaire...">
        <select>
            <option>Tous</option>
            <option>Signalés</option>
            <option>Visibles</option>
            <option>Masqués</option>
        </select>
        <select>
            <option>Trier par</option>
            <option>Date</option>
            <option>Popularité</option>
            <option>Signalements</option>
        </select>
    </div>

    <div class="export-bar">
        <button class="outline-btn">Exporter</button>
    </div>
</section>

<section class="admin-stats reveal">
    <article class="admin-stat"><strong>86</strong><span>Posts</span></article>
    <article class="admin-stat"><strong>214</strong><span>Commentaires</span></article>
    <article class="admin-stat"><strong>17</strong><span>Signalements</span></article>
    <article class="admin-stat"><strong>09</strong><span>Masqués</span></article>
</section>

<!-- POSTS -->
<section class="admin-panel reveal">
    <span class="section-badge">Modération des publications</span>
    <table class="module-table">
        <thead>
            <tr>
                <th>Post</th>
                <th>Auteur</th>
                <th>Type</th>
                <th>Statut</th>
                <th>Signalé</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Comment améliorer la confiance dans un profil provider ?</td>
                <td>Rym</td>
                <td>Discussion</td>
                <td>Visible</td>
                <td>Non</td>
                <td class="admin-tools">
                    <button class="small-btn">Voir</button>
                    <button class="outline-btn">Masquer</button>
                    <button class="danger-btn">Supprimer</button>
                </td>
            </tr>
            <tr>
                <td>Service signalé pour contenu trompeur</td>
                <td>Sarra</td>
                <td>Alerte</td>
                <td>Signalé</td>
                <td>Oui</td>
                <td class="admin-tools">
                    <button class="success-btn">Traiter</button>
                    <button class="outline-btn">Masquer</button>
                    <button class="danger-btn">Supprimer</button>
                </td>
            </tr>
        </tbody>
    </table>
</section>
<section class="admin-panel reveal" style="margin-top: 22px;">
    <span class="section-badge">Posts signalés</span>

    <table class="module-table">
        <thead>
            <tr>
                <th>Post</th>
                <th>Auteur</th>
                <th>Motif</th>
                <th>Signalé par</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Service douteux avec prix incohérent</td>
                <td>Karim</td>
                <td>Contenu trompeur</td>
                <td>Sarra</td>
                <td>15/04/2026</td>
                <td class="admin-tools">
                    <button class="success-btn">Traiter</button>
                    <button class="outline-btn">Masquer</button>
                    <button class="danger-btn">Supprimer</button>
                </td>
            </tr>

            <tr>
                <td>Annonce suspecte sans détails</td>
                <td>Ahmed</td>
                <td>Spam</td>
                <td>Amine</td>
                <td>14/04/2026</td>
                <td class="admin-tools">
                    <button class="success-btn">Traiter</button>
                    <button class="outline-btn">Masquer</button>
                    <button class="danger-btn">Supprimer</button>
                </td>
            </tr>
        </tbody>
    </table>
</section>
<!-- COMMENTAIRES (ta7t posts w full width) -->
<section class="admin-panel reveal" style="margin-top: 22px;">
    <span class="section-badge">Commentaires</span>
    <table class="module-table">
        <thead>
            <tr>
                <th>Auteur</th>
                <th>Commentaire</th>
                <th>Post lié</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Amine</td>
                <td>Le portfolio fait vraiment la différence.</td>
                <td>Profil provider</td>
                <td class="admin-tools">
                    <button class="small-btn">Voir</button>
                    <button class="danger-btn">Supprimer</button>
                </td>
            </tr>
        </tbody>
    </table>
</section>