<?php
$pageTitles = [
    'dashboard' => 'Dashboard',
    'users' => 'Gestion des utilisateurs',
    'services' => 'Gestion des services',
    'offers' => 'Gestion des offres',
    'forum' => 'Modération du forum',
    'reclamation' => 'Gestion des réclamations',
    'events' => 'Gestion des événements',
];

$title = $pageTitles[$page] ?? 'Dashboard';

$adminNav = [
    'Dashboard' => 'index.php?page=dashboard',
    'Utilisateurs' => 'index.php?page=users',
    'Services' => 'index.php?page=services',
    'Offres' => 'index.php?page=offers',
    'Forum' => 'index.php?page=forum',
    'Réclamations' => 'index.php?page=reclamation',
    'Événements' => 'index.php?page=events',
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
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <?php if (($page ?? '') === 'events'): ?>
        <link rel="stylesheet" href="../../assets/css/events.css">
    <?php endif; ?>
</head>
<body class="admin-body dark">
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <a href="<?php echo htmlspecialchars($frontSiteLink, ENT_QUOTES, 'UTF-8'); ?>" class="admin-brand">
                <img
                    id="siteLogo"
                    src="../../assets/images/logo-white.png"
                    data-light="../../assets/images/logo.png"
                    data-dark="../../assets/images/logo-white.png"
                    alt="logo"
                >
            </a>

            <nav class="admin-nav">
                <?php foreach ($adminNav as $label => $link): ?>
                    <a href="<?php echo $link; ?>" class="<?php echo $link === 'index.php?page=' . $page ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </aside>

        <div class="admin-main">
            <header class="admin-topbar">
    <div class="admin-topbar-row">
        <span class="section-badge admin-badge">Back Office</span>

        <div class="admin-topbar-actions">
            <button id="themeToggle" class="theme-btn" type="button">☀</button>
            <a class="ghost-btn" href="<?php echo htmlspecialchars($frontSiteLink, ENT_QUOTES, 'UTF-8'); ?>">Voir le site</a>
        </div>
    </div>

    <h1 class="admin-page-title"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h1>
</header>

            <main class="admin-content">
                <?php require $view; ?>
            </main>
        </div>
    </div>

    <script src="../../assets/js/theme.js"></script>
    <script src="../../assets/js/main.js"></script>
    <?php if (($page ?? '') === 'events'): ?>
        <script src="../../assets/js/events.js" defer></script>
    <?php endif; ?>
</body>
</html>