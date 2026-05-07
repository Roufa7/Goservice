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
    <link rel="stylesheet" href="../../assets/css/face-id.css">
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
                <?php if (isset($_SESSION['user_id'])): ?>
                    <span style="font-weight: bold; margin-right: 10px;">Bienvenue, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <a href="index.php?page=profile" class="solid-btn" style="margin-right: 10px;">Mon Profil</a>
                    <a href="../../controller/AuthController.php?action=logout" class="ghost-btn">Déconnexion</a>
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
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <a href="../back/index.php?page=dashboard" style="color: var(--orange); font-weight: bold;">Back Office</a>
                <?php endif; ?>
            </div>
        </div>
    </footer>

    <script src="../../assets/js/theme.js"></script>
    <script src="../../assets/js/main.js"></script>
    <div class="auth-modal" id="loginModal">
    <div class="auth-modal-box">
        <button class="auth-close" id="closeLoginModal">&times;</button>

        <h2>Connexion</h2>
        <?php if(isset($_GET['error'])): ?>
            <p style="color: #ff4757; font-weight: bold; background: rgba(255, 71, 87, 0.1); padding: 10px; border-radius: 6px; text-align: center;">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </p>
        <?php endif; ?>
        <p>Connectez-vous avec votre email et votre mot de passe.</p>

        <form class="auth-form" action="../../controller/AuthController.php?action=login" method="POST">
            <div class="field-block">
                <label for="login_email">Email</label>
                <input type="email" id="login_email" name="email" required>
            </div>

            <div class="field-block">
                <label for="login_password">Mot de passe</label>
                <input type="password" id="login_password" name="password" required>
            </div>

            <!-- SECURITY VERIFICATION (CSS Stylisé) -->
            <div class="captcha-wrap" style="text-align: center; margin-bottom: 15px; background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #eee;">
                <span style="display: block; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; color: #555; font-weight: bold;">Security Verification</span>
                
                <div id="captchaBox" style="
                    display: inline-block; 
                    background: #fff; 
                    padding: 10px 20px; 
                    border-radius: 4px; 
                    border: 1px dashed #ccc; 
                    font-family: 'Courier New', Courier, monospace; 
                    font-size: 24px; 
                    font-weight: bold; 
                    color: #2c3e50; 
                    letter-spacing: 8px; 
                    user-select: none;
                    position: relative;
                    overflow: hidden;
                    margin-bottom: 15px;
                ">
                    <?php 
                    if(!isset($_SESSION['captcha_code'])) {
                        $permitted_chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
                        $_SESSION['captcha_code'] = '';
                        for($i = 0; $i < 6; $i++) { $_SESSION['captcha_code'] .= $permitted_chars[rand(0, strlen($permitted_chars) - 1)]; }
                    }
                    echo $_SESSION['captcha_code']; 
                    ?>
                    <!-- Noise lines inside captcha -->
                    <div style="position:absolute; top:40%; left:0; width:100%; height:1px; background:rgba(0,0,0,0.1); transform:rotate(5deg);"></div>
                    <div style="position:absolute; top:60%; left:0; width:100%; height:1px; background:rgba(0,0,0,0.1); transform:rotate(-5deg);"></div>
                </div>

                <button type="button" onclick="window.location.reload();" style="border:none; background:none; cursor:pointer; color: var(--orange); font-size: 1.2rem; vertical-align: middle;" title="Rafraîchir"> 🔄</button>
                <input type="text" name="captcha_input" placeholder="ENTER THE CODE ABOVE" required style="width: 100%; text-align: center; font-weight: bold; letter-spacing: 2px;">
            </div>

            <button type="submit" class="solid-btn auth-submit">Se connecter</button>
            
            <div style="text-align:center; margin:15px 0; color:#888;">— OU —</div>
            
            <button type="button" id="btnFaceID" class="face-id-btn">
                <span style="font-size:1.2rem;">👤</span> Se connecter avec Face ID
            </button>
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

<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script src="../../assets/js/face-enroll.js"></script>
<script src="../../assets/js/face-login.js"></script>
</body>

</html>