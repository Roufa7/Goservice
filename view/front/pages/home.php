<section class="hero-section">
    <div class="hero-copy reveal">
        <span class="eyebrow">Plateforme digitale moderne</span>
        <h1>Découvrez un univers de services, d’échanges et d’opportunités dans une expérience visuelle premium.</h1>
        <p>
            Une interface immersive, dynamique et élégante pensée pour les utilisateurs,
            les providers et l’administration, avec une navigation fluide entre les univers du site.
        </p>

        <div class="hero-actions">
            <a class="solid-btn" href="index.php?page=services">Découvrir les services</a>
            <a class="ghost-btn" href="index.php?page=forum">Explorer la communauté</a>
        </div>

        <?php 
        require_once __DIR__ . '/../../../model/User.php';
        $userModel = new User();
        $globalCounts = $userModel->getGlobalCounts();
        ?>
        <div class="mini-stats">
            <div class="glass-card">
                <strong class="stat-animate" data-target="<?php echo $globalCounts['services'] ?? 0; ?>">0</strong>
                <span>Services réels</span>
            </div>
            <div class="glass-card">
                <strong class="stat-animate" data-target="<?php echo $globalCounts['events'] ?? 0; ?>">0</strong>
                <span>Événements</span>
            </div>
            <div class="glass-card">
                <strong class="stat-animate" data-target="<?php echo $globalCounts['users'] ?? 0; ?>">0</strong>
                <span>Membres actifs</span>
            </div>
        </div>
    </div>

    <div class="hero-visual reveal">
        <div class="glass-card hero-float one">
            <span class="section-badge">Services</span>
            <h3>Découverte rapide</h3>
            <p>Cards modernes, navigation claire et expérience premium.</p>
        </div>

        <div class="glass-card hero-float two">
            <span class="section-badge">Forum</span>
            <h3>Communauté active</h3>
            <p>Posts, interactions, retours et échanges dans un design social.</p>
        </div>

        <div class="glass-card hero-float three">
            <span class="section-badge">Events</span>
            <h3>Moments à ne pas manquer</h3>
            <p>Événements, participation et ambiance visuelle immersive.</p>
        </div>
    </div>
</section>

<section class="section reveal">
    <div class="section-head">
        <div>
            <span class="section-badge">Services</span>
            <h2>Explorez les univers les plus demandés</h2>
        </div>
        <a class="ghost-btn" href="index.php?page=services">Voir plus</a>
    </div>

    <div class="grid-4">
        <article class="card service-hover-card">
            <div class="card-media hover-slider">
                <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1607472586893-edb57bdc0e39?auto=format&fit=crop&w=900&q=80');"></div>
                <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1621905252507-b35492cc74b4?auto=format&fit=crop&w=900&q=80');"></div>
                <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1585704032915-c3400ca199e7?auto=format&fit=crop&w=900&q=80');"></div>
                <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1504307651254-35680f356dfd?auto=format&fit=crop&w=900&q=80');"></div>
            </div>
            <h3>Plomberie</h3>
            <p>Interventions rapides, maintenance et support à domicile avec une interface moderne.</p>
        </article>

        <article class="card service-hover-card">
            <div class="card-media hover-slider">
                <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1621905251918-48416bd8575a?auto=format&fit=crop&w=900&q=80');"></div>
                <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1555963966-b7ae5404b6ed?auto=format&fit=crop&w=900&q=80');"></div>
                <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1581092580497-e0d23cbdf1dc?auto=format&fit=crop&w=900&q=80');"></div>
                <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1581092160607-ee22621dd758?auto=format&fit=crop&w=900&q=80');"></div>
            </div>
            <h3>Électricité</h3>
            <p>Services techniques, installation et assistance avec une présentation claire et efficace.</p>
        </article>

        <article class="card service-hover-card">
            <div class="card-media hover-slider">
                <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1522542550221-31fd19575a2d?auto=format&fit=crop&w=900&q=80');"></div>
                <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1455390582262-044cdead277a?auto=format&fit=crop&w=900&q=80');"></div>
                <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=900&q=80');"></div>
                <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=900&q=80');"></div>
            </div>
            <h3>Design</h3>
            <p>Identité visuelle, création digitale et prestations premium dans une mise en page soignée.</p>
        </article>

        <article class="card service-hover-card">
            <div class="card-media hover-slider">
                <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1519003722824-194d4455a60c?auto=format&fit=crop&w=900&q=80');"></div>
                <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1556740749-887f6717d7e4?auto=format&fit=crop&w=900&q=80');"></div>
                <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1526367790999-0150786686a2?auto=format&fit=crop&w=900&q=80');"></div>
                <div class="slide" style="background-image:url('https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=900&q=80');"></div>
            </div>
            <h3>Livraison</h3>
            <p>Expérience fluide entre demande, suivi et exécution dans un environnement visuel cohérent.</p>
        </article>
    </div>
</section>

<section class="section reveal home-stack-section">
    <div class="panel">
        <span class="section-badge">Forum</span>
        <h2 class="page-title">Les échanges récents</h2>

        <div class="forum-post">
            <strong>Comment rendre un profil provider plus convaincant visuellement ?</strong>
            <p>Un aperçu social immersif avec likes, commentaires et partages dans une interface moderne.</p>
            <div class="forum-actions">
                <span>142 J’aime</span>
                <span>36 Commentaires</span>
                <span>12 Partages</span>
            </div>
        </div>

        <div class="forum-post">
            <strong>Quels services attirent le plus de confiance chez les utilisateurs ?</strong>
            <p>Design de publication prêt pour l’intégration du module forum plus tard.</p>
            <div class="forum-actions">
                <span>98 J’aime</span>
                <span>24 Commentaires</span>
                <span>7 Partages</span>
            </div>
        </div>
    </div>

    <div class="panel">
        <span class="section-badge">Événements</span>
        <h2 class="page-title">À venir bientôt</h2>

        <div class="timeline-card">
            <h3>Rencontre providers</h3>
            <p>Une expérience visuelle centrée sur la participation, l’échange et la communauté.</p>
            <div class="meta-row">
                <span>24 Avril</span>
                <span>Lac 1</span>
            </div>
        </div>

        <div class="timeline-card" style="margin-top: 16px;">
            <h3>Atelier satisfaction client</h3>
            <p>Carte événement immersive avec effet premium, profondeur et hiérarchie visuelle claire.</p>
            <div class="meta-row">
                <span>02 Mai</span>
                <span>Hub Digital</span>
            </div>
        </div>
    </div>
</section>

<section class="section reveal home-stack-section">
    <article class="card auth-card">
        <span class="section-badge">Connexion</span>
        <h3>Accéder à votre espace</h3>
        <p>Un bloc visuel simple pour l’entrée utilisateur, provider ou administrateur.</p>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a class="solid-btn" href="index.php?page=profile">Mon Profil</a>
        <?php else: ?>
            <a class="ghost-btn" href="#" id="openLoginModal2">Se Connecter</a>
        <?php endif; ?>
    </article>

    <article class="card auth-card">
        <span class="section-badge">Inscription</span>
        <h3>Rejoindre la plateforme</h3>
        <p>Une expérience d’accueil élégante dès le premier contact avec la plateforme.</p>
        <a class="solid-btn" href="index.php?page=register">Commencer</a>
    </article>
</section>