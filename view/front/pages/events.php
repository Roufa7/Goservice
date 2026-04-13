<section class="page-hero reveal">
    <span class="section-badge">Événements</span>
    <h1 class="page-title">Événements & participation</h1>
    <p class="page-intro">
        Une page immersive prête pour afficher les événements, consulter les détails,
        s’inscrire et gérer les participations.
    </p>
</section>

<section class="action-bar reveal">
    <div class="search-box">
        <input type="text" placeholder="Rechercher un événement...">
        <select>
            <option>Tous les événements</option>
            <option>À venir</option>
            <option>En cours</option>
            <option>Terminés</option>
        </select>
        <select>
            <option>Tous les types</option>
            <option>Atelier</option>
            <option>Formation</option>
            <option>Promotion</option>
            <option>Service</option>
        </select>
    </div>

    <div class="icon-actions">
        <a class="solid-btn" href="#ajout-evenement">+ Ajouter un événement</a>
        <button class="outline-btn">Mes participations</button>
    </div>
</section>

<section class="grid-3 section reveal">
   <article class="card event-card-popup">
    <div class="event-media-wrap">
        <div class="card-media event-thumb-visible" style="background-image:url('https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1200&q=80');"></div>

        <div class="event-hover-popup centered-popup">
            <div class="event-popup-image" style="background-image:url('https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=1200&q=80');">
                <div class="event-popup-overlay-text">
                    <h4>Atelier UI/UX Providers</h4>
                    <p>Design thinking, expérience utilisateur et optimisation des profils providers.</p>
                    <div class="meta-row">
                        <span>24/04 • 09:00</span>
                        <span>Lac 1</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <h3>Atelier UI/UX Providers</h3>
    <p>Bloc événement prêt pour titre, description, date début, date fin, lieu, type, image et statut.</p>
    <div class="meta-row">
        <span>24/04 • 09:00</span>
        <span>Lac 1</span>
    </div>
    <div class="meta-row" style="margin-top: 10px;">
        <span class="status-badge status-pending">Prévu</span>
        <span>35 places</span>
    </div>
    <div class="icon-actions" style="margin-top: 14px;">
        <button class="solid-btn">Participer</button>
        <button class="outline-btn">Détails</button>
    </div>
</article>

  <article class="card event-card-popup">
    <div class="event-media-wrap">
        <div class="card-media event-thumb-visible" style="background-image:url('https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=1200&q=80');"></div>

        <div class="event-hover-popup centered-popup">
            <div class="event-popup-image" style="background-image:url('https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=1200&q=80');">
                <div class="event-popup-overlay-text">
                    <h4>Formation outils digitaux</h4>
                    <p>Découverte des outils numériques, stratégie digitale et bonnes pratiques terrain.</p>
                    <div class="meta-row">
                        <span>02/05 • 14:00</span>
                        <span>En ligne</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <h3>Formation outils digitaux</h3>
    <p>Un deuxième exemple avec une hiérarchie claire pour faciliter l’intégration future du module.</p>
    <div class="meta-row">
        <span>02/05 • 14:00</span>
        <span>En ligne</span>
    </div>
    <div class="meta-row" style="margin-top: 10px;">
        <span class="status-badge status-open">En cours</span>
        <span>80 places</span>
    </div>
    <div class="icon-actions" style="margin-top: 14px;">
        <button class="solid-btn">Participer</button>
        <button class="outline-btn">Détails</button>
    </div>
</article>

    <article class="card event-card-popup">
    <div class="event-media-wrap">
        <div class="card-media event-thumb-visible" style="background-image:url('https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=1200&q=80');"></div>

        <div class="event-hover-popup centered-popup">
            <div class="event-popup-image" style="background-image:url('https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&w=1200&q=80');">
                <div class="event-popup-overlay-text">
                    <h4>Promotion services premium</h4>
                    <p>Présentation des services premium, mise en avant visuelle et interaction avec les participants.</p>
                    <div class="meta-row">
                        <span>11/05 • 10:30</span>
                        <span>Centre-ville</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <h3>Promotion services premium</h3>
    <p>Carte événement moderne avec CTA, badges et métadonnées prêtes pour le CRUD.</p>
    <div class="meta-row">
        <span>11/05 • 10:30</span>
        <span>Centre-ville</span>
    </div>
    <div class="meta-row" style="margin-top: 10px;">
        <span class="status-badge status-done">Confirmé</span>
        <span>50 places</span>
    </div>
    <div class="icon-actions" style="margin-top: 14px;">
        <button class="solid-btn">Participer</button>
        <button class="outline-btn">Détails</button>
    </div>
</article>
</section>

<section class="module-split reveal">
    <div class="panel">
        <span class="section-badge">Formulaire de participation</span>
        <div class="form-grid">
            <input type="text" placeholder="Nom du participant">
            <input type="email" placeholder="Email du participant">
            <input type="text" placeholder="Téléphone">
            <select>
                <option>Statut participation</option>
                <option>Confirmé</option>
                <option>En attente</option>
                <option>Annulé</option>
            </select>
<div class="field-block">
    <label for="date_inscription">Date d'inscription</label>
    <input type="datetime-local" id="date_inscription" name="date_inscription">
</div>
        </div>

        <div class="icon-actions" style="margin-top: 14px;">
            <button class="solid-btn">Valider l’inscription</button>
            <button class="outline-btn">Modifier</button>
            <button class="danger-btn">Annuler</button>
        </div>
    </div>

    <div class="panel">
        <span class="section-badge">Ce que la page couvre déjà</span>
        <div class="feature-list">
            <div class="feature-item">Liste des événements</div>
            <div class="feature-item">Recherche / filtre / type / statut</div>
            <div class="feature-item">Inscription à un événement</div>
            <div class="feature-item">Gestion des participations</div>
            <div class="feature-item">Affichage détails au survol</div>
        </div>
    </div>
</section>

<section class="section reveal">
    <article class="panel" id="ajout-evenement">
        <span class="section-badge">Ajouter un événement</span>

        <div class="form-grid">
            <input type="text" placeholder="Titre de l’événement">
            <input type="text" placeholder="Lieu">

            <div class="field-block">
    <label for="date_debut">Date de début</label>
    <input type="datetime-local" id="date_debut" name="date_debut" required>
</div>

<div class="field-block">
    <label for="date_fin">Date de fin</label>
    <input type="datetime-local" id="date_fin" name="date_fin" required>
</div>

            <select>
                <option>Type d’événement</option>
                <option>Atelier</option>
                <option>Formation</option>
                <option>Promotion</option>
                <option>Service</option>
            </select>

            <input type="number" placeholder="Nombre de places">

            <input type="file" accept="image/*">
            <select>
                <option>Statut</option>
                <option>Prévu</option>
                <option>En cours</option>
                <option>Terminé</option>
                <option>Annulé</option>
            </select>

            <textarea placeholder="Description de l’événement..."></textarea>
        </div>

        <div class="icon-actions" style="margin-top:14px;">
            <button class="solid-btn">Ajouter</button>
            <button class="outline-btn">Mettre à jour</button>
            <button class="danger-btn">Supprimer</button>
        </div>
    </article>
</section>