<?php
if (!isset($_SESSION['user_id'])) {
    echo "<section class='page-hero reveal'><h2>Veuillez vous connecter pour voir votre profil.</h2></section>";
    return;
}

require_once __DIR__ . '/../../../model/User.php';
$userModel = new User();
$userData = $userModel->getUserById($_SESSION['user_id']);

if (!$userData) {
    echo "<section class='page-hero reveal'><h2>Profil introuvable.</h2></section>";
    return;
}
?>
<section class="page-hero reveal">
    <span class="section-badge">Profil</span>
    <h1 class="page-title">Espace utilisateur</h1>
    <p class="page-intro">
        Gérez vos informations personnelles et mettez à jour votre compte.
    </p>
</section>

<section class="profile-wrap reveal">
    <div class="profile-top">
        <article class="profile-card main">
            <?php if (isset($_GET['success'])): ?>
                <div style="color: green; margin-bottom:15px; font-weight:bold;">Profil mis à jour avec succès !</div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div style="color: red; margin-bottom:15px; font-weight:bold;">Une erreur est survenue.</div>
            <?php endif; ?>

            <div class="avatar-large"><?php echo strtoupper(substr($userData['prenom'], 0, 1) . substr($userData['nom'], 0, 1)); ?></div>
            <h2><?php echo htmlspecialchars($userData['prenom'] . ' ' . $userData['nom']); ?></h2>
            <p>Rôle: <?php echo htmlspecialchars(ucfirst($userData['role'])); ?></p>

            <form class="auth-form" action="../../controller/ProfileController.php?action=update" method="POST" style="margin-top: 20px; text-align: left;">
                <div class="form-grid">
                    <div class="field-block">
                        <label>Nom</label>
                        <input type="text" name="nom" value="<?php echo htmlspecialchars($userData['nom']); ?>" required>
                    </div>
                    <div class="field-block">
                        <label>Prénom</label>
                        <input type="text" name="prenom" value="<?php echo htmlspecialchars($userData['prenom']); ?>" required>
                    </div>
                    <div class="field-block">
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                    </div>
                    <div class="field-block">
                        <label>Téléphone</label>
                        <input type="text" name="telephone" value="<?php echo htmlspecialchars($userData['telephone'] ?? ''); ?>">
                    </div>
                    <div class="field-block" style="grid-column: 1 / -1;">
                        <label>Adresse</label>
                        <input type="text" name="adresse" value="<?php echo htmlspecialchars($userData['adresse'] ?? ''); ?>">
                    </div>
                </div>

                <div class="icon-actions" style="margin-top:20px;">
                    <button type="submit" class="solid-btn">Mettre à jour le profil</button>
                    
                </div>
            </form>
            
            <form action="../../controller/ProfileController.php?action=delete" method="POST" onsubmit="return confirm('Attention ! Voulez-vous vraiment supprimer définitivement votre compte ?');" style="margin-top: 15px;">
                <button type="submit" class="danger-btn">Supprimer mon compte</button>
            </form>
        </article>

        <div>
            <?php $profileStats = $userModel->getProfileStats($_SESSION['user_id']); ?>
            <div class="profile-stats">
                <article class="stat-card">
                    <strong class="stat-animate" data-target="<?php echo $profileStats['services_count'] ?? 0; ?>">0</strong>
                    <span>Services publiés</span>
                </article>
                <article class="stat-card">
                    <strong class="stat-animate" data-target="<?php echo $profileStats['offers_count'] ?? 0; ?>">0</strong>
                    <span>Candidatures</span>
                </article>
                <article class="stat-card">
                    <strong class="stat-animate" data-target="0">0</strong>
                    <span>Demandes actives</span>
                </article>
                <article class="stat-card">
                    <strong class="stat-animate" data-target="5">0</strong>
                    <span>Note moyenne</span>
                </article>
            </div>

            <div class="profile-grid" style="margin-top:20px;">
                <article class="profile-card" style="border: 1px solid var(--orange);">
                    <span class="section-badge">Biométrie</span>
                    <h3>Face ID</h3>
                    <p>Sécurisez votre accès avec la reconnaissance faciale.</p>
                    <button id="btnEnrollFace" class="solid-btn">Enregistrer mon visage</button>
                    <div id="enroll-feedback" style="margin-top:10px; font-weight:bold;"></div>
                </article>

                <article class="profile-card">
                    <span class="section-badge">À propos</span>
                    <h3>Présentation</h3>
                    <p>
                        Provider orienté qualité visuelle, rapidité d’exécution et expérience client.
                        Ce bloc est prêt pour bio, description et informations détaillées.
                    </p>
                </article>

                <article class="profile-card">
                    <span class="section-badge">Avis récents</span>
                    <h3>Ce que disent les clients</h3>
                    <div class="review-stars">
                        <span class="star">★</span><span class="star">★</span><span class="star">★</span><span class="star">★</span><span class="star">★</span>
                    </div>
                    <p>“Service rapide et professionnel, très bonne qualité.”</p>
                    <p>“Profil clair, communication excellente et travail sérieux.”</p>
                </article>

                <article class="profile-card">
                    <span class="section-badge">Services populaires</span>
                    <h3>Top prestations</h3>
                    <div class="feature-list">
                        <div class="feature-item">Pack identité visuelle</div>
                        <div class="feature-item">Audit UI/UX</div>
                        <div class="feature-item">Création d’interfaces modernes</div>
                    </div>
                </article>

                <article class="profile-card">
                    <span class="section-badge">Activité</span>
                    <h3>Résumé visuel</h3>
                    <div class="feature-list">
                        <div class="feature-item">Dernière connexion : aujourd’hui</div>
                        <div class="feature-item">Nouvelles demandes : 3</div>
                        <div class="feature-item">Événements à venir : 2</div>
                    </div>
                </article>
            </div>
        </div>
    </div>
</section>