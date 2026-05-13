<?php
require_once __DIR__ . '/../../i18n.php';
app_set_language_from_request();

$currentLanguage = app_lang();
$pageTitles = [
    'dashboard' => app_text('Dashboard', 'Dashboard', 'لوحة التحكم'),
    'users' => app_text('Gestion des utilisateurs', 'User management', 'إدارة المستخدمين'),
    'services' => app_text('Gestion des services', 'Service management', 'إدارة الخدمات'),
    'categories' => app_text('Gestion des catégories', 'Category management', 'إدارة الفئات'),
    'reservations' => app_text('Gestion des réservations', 'Reservation management', 'إدارة الحجوزات'),
    'offers' => app_text('Gestion des offres', 'Offer management', 'إدارة العروض'),
    'offer_applications' => app_text('Candidatures de l\'offre', 'Offer applications', 'طلبات العرض'),
    'forum' => app_text('Modération du forum', 'Forum moderation', 'إدارة المنتدى'),
    'reclamation' => app_text('Gestion des réclamations', 'Claims management', 'إدارة الشكاوى'),
    'events' => app_text('Gestion des événements', 'Event management', 'إدارة الفعاليات'),
];
$title = $pageTitles[$page] ?? app_text('Dashboard', 'Dashboard', 'لوحة التحكم');

$adminNav = [
    app_text('Dashboard', 'Dashboard', 'لوحة التحكم') => 'index.php?page=dashboard',
    app_text('Utilisateurs', 'Users', 'المستخدمون') => 'index.php?page=users',
    app_text('Services', 'Services', 'الخدمات') => 'index.php?page=services',
    app_text('Catégories', 'Categories', 'الفئات') => 'index.php?page=categories',
    app_text('Réservations', 'Reservations', 'الحجوزات') => 'index.php?page=reservations',
    app_text('Offres', 'Offers', 'العروض') => 'index.php?page=offers',
    app_text('Forum', 'Forum', 'المنتدى') => 'index.php?page=forum',
    app_text('Réclamations', 'Claims', 'الشكاوى') => 'index.php?page=reclamation',
    app_text('Événements', 'Events', 'الفعاليات') => 'index.php?page=events',
];

$frontSiteLink = '../front/index.php?page=home';
if (($page ?? '') === 'events') {
    $selectedEventId = (int) (($eventAdminData['selectedManagementEvent']['id_evenement'] ?? 0));
    $frontSiteLink = '../front/index.php?page=events';
    if ($selectedEventId > 0) {
        $frontSiteLink .= '&event_id=' . $selectedEventId . '#event-focus';
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($currentLanguage, ENT_QUOTES, 'UTF-8'); ?>" dir="<?php echo app_is_rtl() ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <?php if (($page ?? '') === 'events'): ?>
        <link rel="stylesheet" href="../../assets/css/events.css">
    <?php endif; ?>
</head>
<body class="admin-body dark <?php echo app_is_rtl() ? 'rtl-ui' : ''; ?>">
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <a href="<?php echo htmlspecialchars($frontSiteLink, ENT_QUOTES, 'UTF-8'); ?>" class="admin-brand">
                <img id="siteLogo" src="../../assets/images/logo-white.png" data-light="../../assets/images/logo.png" data-dark="../../assets/images/logo-white.png" alt="logo">
            </a>
            <nav class="admin-nav">
                <?php foreach ($adminNav as $label => $link): ?>
                    <a href="<?php echo htmlspecialchars($link, ENT_QUOTES, 'UTF-8'); ?>" class="<?php echo $link === 'index.php?page=' . $page ? 'active' : ''; ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></a>
                <?php endforeach; ?>
            </nav>
        </aside>

        <div class="admin-main">
            <header class="admin-topbar">
                <div class="admin-topbar-row">
                    <span class="section-badge admin-badge"><?php echo htmlspecialchars(app_text('Back Office', 'Back Office', 'لوحة الإدارة'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <div class="admin-topbar-actions">
                        <div class="app-lang-wrap" translate="no">
                            <button type="button" id="appLangToggle" class="ghost-btn app-lang-btn" aria-haspopup="true" aria-expanded="false"><?php echo htmlspecialchars(app_lang_label($currentLanguage), ENT_QUOTES, 'UTF-8'); ?></button>
                            <div class="app-lang-menu" id="appLangMenu" hidden>
                                <a href="<?php echo htmlspecialchars(app_lang_url('fr'), ENT_QUOTES, 'UTF-8'); ?>">Français</a>
                                <a href="<?php echo htmlspecialchars(app_lang_url('en'), ENT_QUOTES, 'UTF-8'); ?>">English</a>
                                <a href="<?php echo htmlspecialchars(app_lang_url('ar'), ENT_QUOTES, 'UTF-8'); ?>">العربية</a>
                            </div>
                        </div>
                        <button id="themeToggle" class="theme-btn" type="button" aria-label="<?php echo htmlspecialchars(app_text('Changer le thème', 'Change theme', 'تغيير المظهر'), ENT_QUOTES, 'UTF-8'); ?>">☀</button>
                        <a class="ghost-btn" href="<?php echo htmlspecialchars($frontSiteLink, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars(app_text('Voir le site', 'View site', 'عرض الموقع'), ENT_QUOTES, 'UTF-8'); ?></a>
                    </div>
                </div>
                <h1 class="admin-page-title"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>
            </header>

            <main class="admin-content">
                <?php ob_start(); require $view; $renderedView = ob_get_clean(); echo app_fix_mojibake($renderedView); ?>
            </main>
        </div>
    </div>

    <script src="../../assets/js/text-cleanup.js"></script>
    <script src="../../assets/js/theme.js"></script>
    <script src="../../assets/js/main.js"></script>
    <script src="../../assets/js/offers-admin.js"></script>
    <?php if (($page ?? '') === 'events'): ?>
        <script src="../../assets/js/events.js" defer></script>
    <?php endif; ?>
    <script>
        (() => {
            const appLangToggle = document.getElementById('appLangToggle');
            const appLangMenu = document.getElementById('appLangMenu');
            if (appLangToggle && appLangMenu) {
                appLangToggle.addEventListener('click', () => {
                    const hidden = appLangMenu.hasAttribute('hidden');
                    if (hidden) {
                        appLangMenu.removeAttribute('hidden');
                        appLangToggle.setAttribute('aria-expanded', 'true');
                    } else {
                        appLangMenu.setAttribute('hidden', 'hidden');
                        appLangToggle.setAttribute('aria-expanded', 'false');
                    }
                });
                document.addEventListener('click', (event) => {
                    if (!appLangToggle.contains(event.target) && !appLangMenu.contains(event.target)) {
                        appLangMenu.setAttribute('hidden', 'hidden');
                        appLangToggle.setAttribute('aria-expanded', 'false');
                    }
                });
            }
        })();
    </script>
</body>
</html>