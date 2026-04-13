<section class="action-bar reveal"> 
    <div class="search-box">
        <input type="text" placeholder="Rechercher une offre...">
        <select>
            <option>Tous les statuts</option>
            <option>Ouverte</option>
            <option>Fermée</option>
        </select>
        <select>
            <option>Trier par</option>
            <option>Date publication</option>
            <option>Date expiration</option>
        </select>
    </div>

    <div class="export-bar">
        <button class="outline-btn">Exporter</button>
    </div>
</section>

<section class="admin-stats reveal">
    <article class="admin-stat"><strong>28</strong><span>Offres</span></article>
    <article class="admin-stat"><strong>19</strong><span>Ouvertes</span></article>
    <article class="admin-stat"><strong>09</strong><span>Fermées</span></article>
    <article class="admin-stat"><strong>46</strong><span>Candidatures</span></article>
</section>

<section class="admin-panel reveal">
    <span class="section-badge">Gestion des offres</span>

    <div class="table-wrap">
        <table class="module-table">
            <thead>
                <tr>
                    <th>Titre</th>
                    <th>Localisation</th>
                    <th>Publication</th>
                    <th>Expiration</th>
                    <th>Statut</th>
                    <th>Type service</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>UX Designer onboarding</td>
                    <td>Tunis</td>
                    <td>12/04/2026</td>
                    <td>30/05/2026</td>
                    <td>Ouverte</td>
                    <td>Design</td>
                    <td class="admin-tools">
                        <button class="small-btn">Voir</button>
                        <button class="small-btn">Modifier</button>
                        <button class="danger-btn">Supprimer</button>
                    </td>
                </tr>
                <tr>
                    <td>Community Manager</td>
                    <td>Sousse</td>
                    <td>10/04/2026</td>
                    <td>12/06/2026</td>
                    <td>Ouverte</td>
                    <td>Communication</td>
                    <td class="admin-tools">
                        <button class="small-btn">Voir</button>
                        <button class="small-btn">Modifier</button>
                        <button class="danger-btn">Supprimer</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</section>

<section class="admin-panel reveal" style="margin-top: 22px;">
    <span class="section-badge">Publier une offre</span>

    <div class="form-grid">
        <input type="text" placeholder="Titre de l'offre">
        <input type="text" placeholder="Localisation">

        <div class="field-block">
    <label for="date_publication">Date de publication</label>
    <input type="date" id="date_publication" name="date_publication">
</div>

<div class="field-block">
    <label for="date_expiration">Date d'expiration</label>
    <input type="date" id="date_expiration" name="date_expiration">
</div>

        <select>
            <option>Statut</option>
            <option>Ouverte</option>
            <option>Fermée</option>
        </select>

        <input type="text" placeholder="Type de service">

        <textarea placeholder="Description de l'offre..."></textarea>
    </div>

    <div class="icon-actions" style="margin-top: 14px;">
        <button class="solid-btn">Publier</button>
        <button class="outline-btn">Mettre à jour</button>
        <button class="danger-btn">Supprimer</button>
    </div>
</section>

<section class="admin-panel reveal" style="margin-top: 22px;">
    <span class="section-badge">Candidatures récentes</span>

    <div class="table-wrap">
        <table class="module-table">
            <thead>
                <tr>
                    <th>Candidat</th>
                    <th>Offre</th>
                    <th>Date candidature</th>
                    <th>Statut</th>
                    <th>Expérience</th>
                    <th>Compétences</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Ahmed Ben Salah</td>
                    <td>UX Designer onboarding</td>
                    <td>15/04/2026</td>
                    <td>En attente</td>
                    <td>2 ans</td>
                    <td>Figma, UI, Branding</td>
                    <td class="admin-tools">
                        <button class="success-btn">Accepter</button>
                        <button class="danger-btn">Refuser</button>
                    </td>
                </tr>
                <tr>
                    <td>Sarra Jaziri</td>
                    <td>Community Manager</td>
                    <td>16/04/2026</td>
                    <td>En attente</td>
                    <td>1 an</td>
                    <td>Social Media, Copywriting</td>
                    <td class="admin-tools">
                        <button class="success-btn">Accepter</button>
                        <button class="danger-btn">Refuser</button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</section>