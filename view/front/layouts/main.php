<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../i18n.php';
require_once __DIR__ . '/../../../model/User.php';
require_once __DIR__ . '/../auth_captcha.php';

app_set_language_from_request();

function frontUserSessionValue(string $key, string $default = ''): string
{
    return trim((string) ($_SESSION[$key] ?? $default));
}

function frontResolvePhotoPath(string $photoPath): string
{
    $photoPath = trim($photoPath);
    if ($photoPath === '') {
        return '';
    }
    if (preg_match('~^https?://~i', $photoPath) || str_starts_with($photoPath, 'data:')) {
        return $photoPath;
    }
    if (str_starts_with($photoPath, '/')) {
        return $photoPath;
    }

    return '../../' . ltrim($photoPath, './');
}

$userId = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
$userPhotoPath = frontUserSessionValue('user_photo');

if ($userId > 0 && $userPhotoPath === '') {
    try {
        $userModel = new User();
        $userData = $userModel->getUserById($userId);
        if (is_array($userData) && !empty($userData['photo'])) {
            $userPhotoPath = (string) $userData['photo'];
            $_SESSION['user_photo'] = $userPhotoPath;
        }
    } catch (Throwable $e) {
        // Keep the shared shell resilient even if the photo cannot be loaded.
    }
}

$userPhotoUrl = frontResolvePhotoPath($userPhotoPath);
$userDisplayName = trim(frontUserSessionValue('user_name', frontUserSessionValue('prenom')));
$avatarFallbackText = $userDisplayName !== ''
    ? mb_strtoupper(mb_substr($userDisplayName, 0, 1, 'UTF-8'), 'UTF-8')
    : 'U';
$currentLanguage = app_lang();
$isRtl = app_is_rtl();
$flashSuccess = $_SESSION['flash_success'] ?? '';
unset($_SESSION['flash_success']);

$pageTitles = [
    'home' => app_text('Accueil', 'Home', 'الرئيسية'),
    'services' => app_text('Services', 'Services', 'الخدمات'),
    'offre' => app_text('Offres', 'Offers', 'العروض'),
    'forum' => app_text('Forum', 'Forum', 'المنتدى'),
    'reclamation' => app_text('Réclamations', 'Claims', 'الشكاوى'),
    'events' => app_text('Événements', 'Events', 'الفعاليات'),
    'profile' => app_text('Profil', 'Profile', 'الملف الشخصي'),
    'savedPosts' => app_text('Posts sauvegardés', 'Saved posts', 'المنشورات المحفوظة'),
];

$title = $pageTitles[$page] ?? app_text('Accueil', 'Home', 'الرئيسية');

$backOfficeTargets = [
    'services' => 'services',
    'serviceDetails' => 'services',
    'addService' => 'services',
    'myServices' => 'services',
    'editMyService' => 'services',
    'offre' => 'offers',
    'forum' => 'forum',
    'savedPosts' => 'forum',
    'reclamation' => 'reclamation',
    'avis' => 'reclamation',
    'events' => 'events',
    'profile' => 'users',
];
$backOfficePage = $backOfficeTargets[$page ?? 'home'] ?? 'dashboard';
$mainNav = [
    ['page' => 'home', 'label' => app_text('Accueil', 'Home', 'الرئيسية')],
    ['page' => 'services', 'label' => app_text('Services', 'Services', 'الخدمات')],
    ['page' => 'offre', 'label' => app_text('Offres', 'Offers', 'العروض')],
    ['page' => 'forum', 'label' => app_text('Forum', 'Forum', 'المنتدى')],
    ['page' => 'reclamation', 'label' => app_text('Réclamations', 'Claims', 'الشكاوى')],
    ['page' => 'events', 'label' => app_text('Événements', 'Events', 'الفعاليات')],
];
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($currentLanguage, ENT_QUOTES, 'UTF-8'); ?>" dir="<?php echo $isRtl ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/face-id.css">
    <?php if (($page ?? '') === 'events'): ?>
        <link rel="stylesheet" href="../../assets/css/events.css">
    <?php endif; ?>
</head>
<body class="<?php echo $isRtl ? 'rtl-ui' : ''; ?>">
    <div class="bg-orb orb-1"></div>
    <div class="bg-orb orb-2"></div>
    <div class="bg-orb orb-3"></div>

    <header class="site-header">
        <div class="container nav-wrap nav-wrap-front">
            <a href="index.php?page=home" class="brand" aria-label="GoService">
                <img id="siteLogo" src="../../assets/images/logo.png" data-light="../../assets/images/logo.png" data-dark="../../assets/images/logo-white.png" alt="GoService">
            </a>

            <nav class="main-nav">
                <?php foreach ($mainNav as $item): ?>
                    <a
                        href="index.php?page=<?php echo htmlspecialchars($item['page'], ENT_QUOTES, 'UTF-8'); ?>"
                        class="<?php echo ($item['page'] === ($page ?? 'home')) ? 'active' : ''; ?>"
                    >
                        <?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="nav-actions nav-actions-front">
                <div class="app-lang-wrap" translate="no">
                    <button type="button" id="appLangToggle" class="ghost-btn app-lang-btn" aria-haspopup="true" aria-expanded="false">
                        <?php echo htmlspecialchars(app_lang_label($currentLanguage), ENT_QUOTES, 'UTF-8'); ?>
                    </button>
                    <div class="app-lang-menu" id="appLangMenu" hidden>
                        <a href="<?php echo htmlspecialchars(app_lang_url('fr'), ENT_QUOTES, 'UTF-8'); ?>">Français</a>
                        <a href="<?php echo htmlspecialchars(app_lang_url('en'), ENT_QUOTES, 'UTF-8'); ?>">English</a>
                        <a href="<?php echo htmlspecialchars(app_lang_url('ar'), ENT_QUOTES, 'UTF-8'); ?>">العربية</a>
                    </div>
                </div>

                <button
                    id="themeToggle"
                    class="theme-btn"
                    type="button"
                    aria-label="<?php echo htmlspecialchars(app_text('Changer le thème', 'Change theme', 'تغيير المظهر'), ENT_QUOTES, 'UTF-8'); ?>"
                    title="<?php echo htmlspecialchars(app_text('Changer le thème', 'Change theme', 'تغيير المظهر'), ENT_QUOTES, 'UTF-8'); ?>"
                ></button>

                <?php if ($userId > 0): ?>
                    <a href="index.php?page=profile" class="nav-avatar-link nav-avatar-link-camera" aria-label="<?php echo htmlspecialchars(app_text('Ouvrir le profil', 'Open profile', 'فتح الملف الشخصي'), ENT_QUOTES, 'UTF-8'); ?>">
                        <?php if ($userPhotoUrl !== ''): ?>
                            <img src="<?php echo htmlspecialchars($userPhotoUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars(app_text('Photo de profil', 'Profile picture', 'الصورة الشخصية'), ENT_QUOTES, 'UTF-8'); ?>" class="nav-avatar-img">
                        <?php else: ?>
                            <span class="nav-avatar-fallback"><?php echo htmlspecialchars($avatarFallbackText, ENT_QUOTES, 'UTF-8'); ?></span>
                        <?php endif; ?>
                        <span class="nav-avatar-camera" aria-hidden="true">📷</span>
                    </a>
                    <a href="../../controller/AuthController.php?action=logout" class="ghost-btn">
                        <?php echo htmlspecialchars(app_text('Déconnexion', 'Logout', 'تسجيل الخروج'), ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php else: ?>
                    <a href="#" class="ghost-btn" id="openLoginModal">
                        <?php echo htmlspecialchars(app_text('Connexion', 'Login', 'تسجيل الدخول'), ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                    <a href="index.php?page=register" class="solid-btn">
                        <?php echo htmlspecialchars(app_text("S'inscrire", 'Register', 'إنشاء حساب'), ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main class="main-content">
        <?php if ($flashSuccess !== ''): ?>
            <div class="container">
                <div class="app-flash app-flash-success"><?php echo htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
        <?php endif; ?>

        <?php ob_start(); require $view; $renderedView = ob_get_clean(); echo app_fix_mojibake($renderedView); ?>
    </main>

    <footer class="site-footer">
        <div class="container footer-grid">
            <div>
                <h3><?php echo htmlspecialchars(app_text('Plateforme digitale', 'Digital platform', 'منصة رقمية'), ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?php echo htmlspecialchars(app_text('Services, offres, forum, réclamations, événements et administration dans une expérience cohérente.', 'Services, offers, forum, claims, events and administration in one coherent experience.', 'الخدمات والعروض والمنتدى والشكاوى والفعاليات والإدارة في تجربة واحدة متناسقة.'), ENT_QUOTES, 'UTF-8'); ?></p>
            </div>

            <div>
                <h4><?php echo htmlspecialchars(app_text('Navigation', 'Navigation', 'التنقل'), ENT_QUOTES, 'UTF-8'); ?></h4>
                <?php foreach ($mainNav as $item): ?>
                    <a href="index.php?page=<?php echo htmlspecialchars($item['page'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div>
                <h4><?php echo htmlspecialchars(app_text('Espaces', 'Spaces', 'المساحات'), ENT_QUOTES, 'UTF-8'); ?></h4>
                <a href="index.php?page=profile"><?php echo htmlspecialchars(app_text('Profil', 'Profile', 'الملف الشخصي'), ENT_QUOTES, 'UTF-8'); ?></a>
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                    <a href="../back/index.php?page=<?php echo htmlspecialchars($backOfficePage, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(app_text('Back Office', 'Back Office', 'لوحة الإدارة'), ENT_QUOTES, 'UTF-8'); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </footer>

            <button type="button" class="goservice-chatbot-btn" id="chatbotBtn" aria-label="<?php echo htmlspecialchars(app_text('Ouvrir l’assistant', 'Open assistant', 'فتح المساعد'), ENT_QUOTES, 'UTF-8'); ?>">🤖</button>
        <div class="goservice-chatbot-box" id="chatbotBox">
            <div class="chatbot-head">
                <span><?php echo htmlspecialchars(app_text('Assistant GoService', 'GoService Assistant', 'مساعد GoService'), ENT_QUOTES, 'UTF-8'); ?></span>
                <button type="button" id="chatbotClose">×</button>
            </div>
            <div class="chatbot-messages" id="chatbotMessages">
                <div class="chat-msg bot"><?php echo htmlspecialchars(app_text('Bonjour. Je peux vous aider sur GoService.', 'Hello. I can help you on GoService.', 'مرحباً. يمكنني مساعدتك في GoService.'), ENT_QUOTES, 'UTF-8'); ?></div>
            </div>
            <form class="chatbot-form" id="chatbotForm">
                <input type="text" id="chatbotInput" placeholder="<?php echo htmlspecialchars(app_text('Écrire un message...', 'Write a message...', 'اكتب رسالة...'), ENT_QUOTES, 'UTF-8'); ?>">
                <button type="submit">➤</button>
            </form>
        </div>

    <div class="auth-modal" id="loginModal">
        <div class="auth-modal-box">
            <button class="auth-close" id="closeLoginModal">&times;</button>
            <h2><?php echo htmlspecialchars(app_text('Connexion', 'Login', 'تسجيل الدخول'), ENT_QUOTES, 'UTF-8'); ?></h2>
            <?php if (isset($_GET['error'])): ?>
                <p class="auth-error-inline"><?php echo htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
            <p><?php echo htmlspecialchars(app_text('Connectez-vous avec votre email et votre mot de passe.', 'Sign in with your email and password.', 'سجّل الدخول باستخدام بريدك الإلكتروني وكلمة المرور.'), ENT_QUOTES, 'UTF-8'); ?></p>

            <form class="auth-form" action="../../controller/AuthController.php?action=login" method="POST">
                <div class="field-block">
                    <label for="login_email">Email</label>
                    <input type="email" id="login_email" name="email" required>
                </div>

                <div class="field-block">
                    <label for="login_password"><?php echo htmlspecialchars(app_text('Mot de passe', 'Password', 'كلمة المرور'), ENT_QUOTES, 'UTF-8'); ?></label>
                    <input type="password" id="login_password" name="password" required>
                </div>

                <div class="field-block">
                    <label for="login_captcha">Captcha</label>
                    <div class="auth-captcha-box"><?php echo htmlspecialchars(authCaptchaCode(), ENT_QUOTES, 'UTF-8'); ?></div>
                    <input type="text" id="login_captcha" name="captcha" required autocomplete="off" placeholder="<?php echo htmlspecialchars(app_text('Recopiez le code', 'Copy the code', 'أعد كتابة الرمز'), ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <button type="submit" class="solid-btn auth-submit"><?php echo htmlspecialchars(app_text('Se connecter', 'Sign in', 'تسجيل الدخول'), ENT_QUOTES, 'UTF-8'); ?></button>
            </form>
        </div>
    </div>

    <script src="../../assets/js/text-cleanup.js"></script>
    <script src="../../assets/js/theme.js"></script>
    <?php if (($page ?? '') === 'profile'): ?>
        <script defer src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
        <script defer src="../../assets/js/face-enroll.js"></script>
    <?php endif; ?>
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/offer-validation.js"></script>
    <?php if (($page ?? '') === 'events'): ?>
        <script src="../../assets/js/events.js" defer></script>
    <?php endif; ?>

    <script>
        (() => {
            const loginModal = document.getElementById('loginModal');
            const openLoginModal = document.getElementById('openLoginModal');
            const closeLoginModal = document.getElementById('closeLoginModal');
            const appLangToggle = document.getElementById('appLangToggle');
            const appLangMenu = document.getElementById('appLangMenu');

            if (openLoginModal && loginModal && closeLoginModal) {
                openLoginModal.addEventListener('click', (event) => {
                    event.preventDefault();
                    loginModal.classList.add('show');
                });

                closeLoginModal.addEventListener('click', () => loginModal.classList.remove('show'));
                loginModal.addEventListener('click', (event) => {
                    if (event.target === loginModal) {
                        loginModal.classList.remove('show');
                    }
                });

                if (window.location.search.includes('page=login') || window.location.search.includes('error=')) {
                    loginModal.classList.add('show');
                }
            }

            if (appLangToggle && appLangMenu) {
                appLangToggle.addEventListener('click', (event) => {
                    event.preventDefault();
                    const isHidden = appLangMenu.hasAttribute('hidden');
                    if (isHidden) {
                        appLangMenu.removeAttribute('hidden');
                        appLangToggle.setAttribute('aria-expanded', 'true');
                    } else {
                        appLangMenu.setAttribute('hidden', 'hidden');
                        appLangToggle.setAttribute('aria-expanded', 'false');
                    }
                });

                document.addEventListener('click', (event) => {
                    if (!appLangMenu.contains(event.target) && !appLangToggle.contains(event.target)) {
                        appLangMenu.setAttribute('hidden', 'hidden');
                        appLangToggle.setAttribute('aria-expanded', 'false');
                    }
                });
            }

            const chatbotBtn = document.getElementById('chatbotBtn');
            const chatbotBox = document.getElementById('chatbotBox');
            const chatbotClose = document.getElementById('chatbotClose');
            const chatbotForm = document.getElementById('chatbotForm');
            const chatbotInput = document.getElementById('chatbotInput');
            const chatbotMessages = document.getElementById('chatbotMessages');

            if (chatbotBtn && chatbotBox && chatbotClose && chatbotForm && chatbotInput && chatbotMessages) {
                chatbotBtn.addEventListener('click', () => chatbotBox.classList.toggle('show'));
                chatbotClose.addEventListener('click', () => chatbotBox.classList.remove('show'));

                chatbotForm.addEventListener('submit', async (event) => {
                    event.preventDefault();
                    const message = chatbotInput.value.trim();
                    if (!message) {
                        return;
                    }

                    const userMsg = document.createElement('div');
                    userMsg.className = 'chat-msg user';
                    userMsg.textContent = message;
                    chatbotMessages.appendChild(userMsg);
                    chatbotInput.value = '';

                    const pendingMsg = document.createElement('div');
                    pendingMsg.className = 'chat-msg bot';
                    pendingMsg.textContent = <?php echo json_encode(app_text('Je réfléchis...', 'Thinking...', 'أفكر...'), JSON_UNESCAPED_UNICODE); ?>;
                    chatbotMessages.appendChild(pendingMsg);
                    chatbotMessages.scrollTop = chatbotMessages.scrollHeight;

                    try {
                        const payload = new FormData();
                        payload.append('message', message);
                        const response = await fetch('../../service/OllamaChatbotService.php', {
                            method: 'POST',
                            body: payload,
                        });
                        const data = await response.json();
                        pendingMsg.textContent = data.reply || <?php echo json_encode(app_text('Je n’ai pas pu répondre pour le moment.', 'I could not answer right now.', 'لم أتمكن من الرد حالياً.'), JSON_UNESCAPED_UNICODE); ?>;
                    } catch (error) {
                        pendingMsg.textContent = <?php echo json_encode(app_text('Assistant indisponible pour le moment.', 'Assistant unavailable right now.', 'المساعد غير متاح حالياً.'), JSON_UNESCAPED_UNICODE); ?>;
                    }

                    chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
                });
            }
        })();
    </script>
</body>
</html>
