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
    <div>
        <article class="post-card">
            <span class="section-badge">Ma réclamation</span>
            <h3>Retard dans la prise en charge du service</h3>
            <p>Réclamation personnelle liée à un délai de traitement trop long après validation de la demande.</p>
            <div class="meta-row">
                <span>12/04/2026</span>
                <span class="status-badge status-pending">En attente</span>
            </div>
            <div class="icon-actions" style="margin-top:14px;">
                <button class="small-btn">Modifier</button>
                <button class="danger-btn">Supprimer</button>
            </div>
        </article>

        <article class="post-card" style="margin-top:18px;">
            <span class="section-badge">Réponse</span>
            <h3>Réponse du support</h3>
            <p>Nous avons bien reçu votre réclamation. Une vérification est en cours avec le provider concerné.</p>
            <div class="meta-row">
                <span>13/04/2026</span>
                <span class="status-badge status-open">Traitement</span>
            </div>
        </article>

        <article class="post-card" style="margin-top:18px;">
            <span class="section-badge">Publier un avis</span>
            <div class="review-stars" style="margin-bottom:12px;">
                <span class="star">★</span><span class="star">★</span><span class="star">★</span><span class="star">★</span><span class="star">☆</span>
            </div>
            <div class="form-grid">
                <textarea placeholder="Écrire votre avis après traitement de la réclamation..."></textarea>
            </div>
            <div class="icon-actions" style="margin-top:14px;">
                <button class="solid-btn">Publier l’avis</button>
                <button class="outline-btn">Mettre à jour</button>
                <button class="danger-btn">Supprimer</button>
            </div>
        </article>
    </div>

    <div>
        <article class="panel" id="deposer-reclamation">
            <span class="section-badge">Déposer / modifier</span>
            <div class="form-grid">
                <input type="text" placeholder="Sujet de la réclamation">
                <select>
                    <option>Statut</option>
                    <option>En attente</option>
                    <option>Résolue</option>
                    <option>Rejetée</option>
                </select>
                <textarea placeholder="Décrivez votre réclamation..."></textarea>
            </div>
            <div class="icon-actions" style="margin-top:14px;">
                <button class="solid-btn">Envoyer</button>
                <button class="outline-btn">Mettre à jour</button>
                <button class="danger-btn">Supprimer</button>
            </div>
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