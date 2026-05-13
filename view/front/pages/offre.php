<section class="page-hero reveal">
    <span class="section-badge">Offres</span>
    <h1 class="page-title">Offres & candidatures</h1>
    <p class="page-intro">
        Une page intégrée pour consulter les offres, chercher, postuler et suivre les candidatures.
    </p>
</section>

<section class="action-bar reveal">
    <div class="search-box">
        <input type="text" placeholder="Rechercher une offre...">
        <select>
            <option>Toutes les offres</option>
            <option>Ouvertes</option>
            <option>Fermées</option>
            <option>Récentes</option>
        </select>
    </div>

    <div class="icon-actions">
        <a class="solid-btn" href="#ajout-candidature">+ Ajouter candidature</a>
        <button class="outline-btn">Mes candidatures</button>
    </div>
</section>

<section class="module-split reveal">
    <div>
        <article class="card">
            <span class="section-badge">Offre</span>
            <h3>UX Designer pour onboarding providers</h3>
            <p>Carte déjà prête pour titre, description, localisation, date publication, expiration, statut et type service.</p>
            <div class="meta-row">
                <span>Tunis</span>
                <span>Publié le 10/05</span>
                <span>Expire le 30/05</span>
            </div>
            <div class="icon-actions" style="margin-top: 14px;">
                <a class="solid-btn" href="#ajout-candidature">Postuler</a>
                <button class="small-btn">Voir détails</button>
            </div>
        </article>

        <article class="card" style="margin-top: 18px;">
            <span class="section-badge">Offre</span>
            <h3>Community Manager digital</h3>
            <p>Deuxième bloc prêt pour la consultation détaillée et le flux de candidature.</p>
            <div class="meta-row">
                <span>Sousse</span>
                <span>Publié le 15/05</span>
                <span>Expire le 12/06</span>
            </div>
            <div class="icon-actions" style="margin-top: 14px;">
                <a class="solid-btn" href="#ajout-candidature">Postuler</a>
                <button class="small-btn">Voir détails</button>
            </div>
        </article>
    </div>

    <div>
        <article class="panel" id="ajout-candidature">
            <span class="section-badge">Candidature</span>

            <div class="form-grid">
                <input type="text" id="experience_candidature" name="experience_candidature" placeholder="Expérience">
                <input type="text" id="competences_candidature" name="competences_candidature" placeholder="Compétences">

                <input type="file" id="cv_candidature" name="cv_candidature" accept=".pdf,.doc,.docx">
                <select id="statut_candidature" name="statut_candidature">
                    <option>Statut candidature</option>
                    <option>En attente</option>
                    <option>Acceptée</option>
                    <option>Refusée</option>
                </select>

                <textarea id="message_candidature" name="message_candidature" placeholder="Message de candidature..."></textarea>
            </div>

            <div class="icon-actions" style="margin-top:14px;">
                <button class="solid-btn">Envoyer</button>
                <button class="outline-btn">Mettre à jour</button>
                <button class="danger-btn">Supprimer</button>
            </div>
        </article>

        <div class="panel" style="margin-top: 18px;">
            <span class="section-badge">Fonctions prêtes</span>
            <div class="feature-list">
                <div class="feature-item">Consulter les offres</div>
                <div class="feature-item">Rechercher une offre</div>
                <div class="feature-item">Voir les détails</div>
                <div class="feature-item">Envoyer une candidature</div>
                <div class="feature-item">Consulter ses candidatures</div>
            </div>
        </div>
    </div>
</section>