<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function layoutSessionValue(array $keys, $default = null) {
    foreach ($keys as $key) {
        if (isset($_SESSION[$key]) && $_SESSION[$key] !== '') return $_SESSION[$key];
    }
    foreach (['user', 'auth_user', 'current_user'] as $container) {
        if (!empty($_SESSION[$container]) && is_array($_SESSION[$container])) {
            foreach ($keys as $key) {
                if (isset($_SESSION[$container][$key]) && $_SESSION[$container][$key] !== '') return $_SESSION[$container][$key];
            }
        }
    }
    return $default;
}

$layoutUserId = (int) layoutSessionValue(['id_user', 'user_id', 'id'], 0);
$layoutPrenom = trim((string) layoutSessionValue(['prenom', 'first_name', 'firstname'], ''));
$layoutNom = trim((string) layoutSessionValue(['nom', 'last_name', 'lastname'], ''));
$layoutUserName = trim($layoutPrenom . ' ' . $layoutNom);
if ($layoutUserName === '' && $layoutUserId > 0) $layoutUserName = 'Utilisateur #' . $layoutUserId;
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
    <?php
if (isset($_GET['page'], $_GET['open_post']) && $_GET['page'] === 'forum') {
    require_once __DIR__ . '/../../../config.php';

    $id = (int) $_GET['open_post'];
    if ($id > 0) {
        try {
            $db = config::getConnexion();
            $query = $db->prepare("SELECT titre, contenu, image, video FROM post WHERE id_post = :id LIMIT 1");
            $query->execute(['id' => $id]);
            $post = $query->fetch(PDO::FETCH_ASSOC);

            if ($post) {
                $ogTitle = htmlspecialchars($post['titre'] ?? 'GoService Forum', ENT_QUOTES, 'UTF-8');
                $ogDescription = trim(strip_tags($post['contenu'] ?? ''));
                if ($ogDescription === '') $ogDescription = 'Découvrez cette publication sur GoService Forum.';
                if (function_exists('mb_substr')) $ogDescription = mb_substr($ogDescription, 0, 180);
                else $ogDescription = substr($ogDescription, 0, 180);
                $ogDescription = htmlspecialchars($ogDescription, ENT_QUOTES, 'UTF-8');

                $baseUrl = 'https://' . $_SERVER['HTTP_HOST'];
                if (!empty($post['image'])) {
                    $previewImage = $baseUrl . '/GoService/' . ltrim($post['image'], '/');
                } else {
                    $previewImage = $baseUrl . '/GoService/assets/images/logo.png';
                }
                $previewImage = htmlspecialchars($previewImage, ENT_QUOTES, 'UTF-8');
                $ogUrl = htmlspecialchars($baseUrl . $_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8');

                echo '\n<meta property="og:type" content="article">\n';
                echo '<meta property="og:site_name" content="GoService Forum">\n';
                echo '<meta property="og:title" content="' . $ogTitle . '">\n';
                echo '<meta property="og:description" content="' . $ogDescription . '">\n';
                echo '<meta property="og:image" content="' . $previewImage . '">\n';
                echo '<meta property="og:image:secure_url" content="' . $previewImage . '">\n';
                echo '<meta property="og:url" content="' . $ogUrl . '">\n';
                echo '<meta name="twitter:card" content="summary_large_image">\n';
                echo '<meta name="twitter:title" content="' . $ogTitle . '">\n';
                echo '<meta name="twitter:description" content="' . $ogDescription . '">\n';
                echo '<meta name="twitter:image" content="' . $previewImage . '">\n';
            }
        } catch (Throwable $e) {
            // Ne pas casser le layout si Open Graph échoue.
        }
    }
}
?>
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
                <?php if ($layoutUserId > 0): ?>
                    <a href="index.php?page=profile" class="ghost-btn"><?php echo htmlspecialchars($layoutUserName, ENT_QUOTES, 'UTF-8'); ?></a>
                <?php else: ?>
                    <a href="#" class="ghost-btn" id="openLoginModal">Connexion</a>
                    <a href="index.php?page=register" class="solid-btn">S’inscrire</a>
                <?php endif; ?>
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