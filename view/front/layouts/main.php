<?php
$pageTitles = [
    'home' => 'Accueil',
    'services' => 'Services & Catégories',
    'offre' => 'Offres',
    'forum' => 'Forum',
    'reclamation' => 'Réclamations',
    'events' => 'Événements',
    'profile' => 'Profil',
];

$title = $pageTitles[$page] ?? 'Accueil';

$mainNav = [
    'Accueil' => 'index.php?page=home',
    'Services & Catégories' => 'index.php?page=services',
    'Offres' => 'index.php?page=offre',
    'Forum' => 'index.php?page=forum',
    'Réclamations' => 'index.php?page=reclamation',
    'Événements' => 'index.php?page=events',
    'Profil' => 'index.php?page=profile',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <div class="bg-orb orb-1"></div>
    <div class="bg-orb orb-2"></div>
    <div class="bg-orb orb-3"></div>

    <header class="site-header">
        <div class="container nav-wrap">
            <a href="index.php?page=home" class="brand">
                <img
                    id="siteLogo"
                    src="../../assets/images/logo.png"
                    data-light="../../assets/images/logo.png"
                    data-dark="../../assets/images/logo-white.png"
                    alt="logo"
                >
            </a>

            <nav class="main-nav">
                <?php foreach ($mainNav as $label => $link): ?>
                    <a href="<?php echo $link; ?>" class="<?php echo $link === 'index.php?page=' . $page ? 'active' : ''; ?>">
                        <?php echo $label; ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="nav-actions">
                <button id="themeToggle" class="theme-btn" type="button">☾</button>
                <a href="#" class="ghost-btn" id="openLoginModal">Connexion</a>
                <a href="index.php?page=register" class="solid-btn">S’inscrire</a>
            </div>
        </div>
    </header>

    <main class="main-content">
        <?php require $view; ?>
    </main>

    <footer class="site-footer">
        <div class="container footer-grid">
            <div>
                <h3>Plateforme digitale</h3>
                <p>Services, offres, forum, réclamations, événements et administration dans une expérience cohérente.</p>
            </div>

            <div>
                <h4>Navigation</h4>
                <a href="index.php?page=home">Accueil</a>
                <a href="index.php?page=services">Services & Catégories</a>
                <a href="index.php?page=offre">Offres</a>
                <a href="index.php?page=forum">Forum</a>
            </div>

            <div>
                <h4>Espaces</h4>
                <a href="index.php?page=profile">Profil</a>
                <a href="../back/index.php?page=dashboard">Back Office</a>
            </div>
        </div>
    </footer>

    <script src="../../assets/js/theme.js"></script>
    <script src="../../assets/js/main.js"></script>
    <div class="auth-modal" id="loginModal">
    <div class="auth-modal-box">
        <button class="auth-close" id="closeLoginModal">&times;</button>

        <h2>Connexion</h2>
        <p>Connectez-vous avec votre email et votre mot de passe.</p>

        <form class="auth-form">
            <div class="field-block">
                <label for="login_email">Email</label>
                <input type="email" id="login_email" name="email" required>
            </div>

            <div class="field-block">
                <label for="login_password">Mot de passe</label>
                <input type="password" id="login_password" name="password" required>
            </div>

            <button type="submit" class="solid-btn auth-submit">Se connecter</button>
        </form>
    </div>
</div>
<script>
const loginModal = document.getElementById('loginModal');
const openLoginModal = document.getElementById('openLoginModal');
const closeLoginModal = document.getElementById('closeLoginModal');
const openLoginModal2 = document.getElementById('openLoginModal2');

if (openLoginModal && loginModal && closeLoginModal) {
    openLoginModal.addEventListener('click', function(e) {
        e.preventDefault();
        loginModal.classList.add('show');
    });

    closeLoginModal.addEventListener('click', function() {
        loginModal.classList.remove('show');
    });

    loginModal.addEventListener('click', function(e) {
        if (e.target === loginModal) {
            loginModal.classList.remove('show');
        }
    });
}
if (openLoginModal2) {
    openLoginModal2.addEventListener('click', function(e) {
        e.preventDefault();
        loginModal.classList.add('show');
    });
}
</script>
</body>

</html>