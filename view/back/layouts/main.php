<?php
$pageTitles = [
    'dashboard'    => 'Dashboard',
    'users'        => 'Gestion des utilisateurs',
    'services'     => 'Gestion des services',
    'categories'   => 'Gestion des catégories',
    'reservations' => 'Gestion des réservations',
    'offers'       => 'Gestion des offres',
    'offer_applications' => 'Candidatures de l\'offre',
    'forum'        => 'Modération du forum',
    'reclamation'  => 'Gestion des réclamations',
    'events'       => 'Gestion des événements',
];

$title = $pageTitles[$page] ?? 'Dashboard';

$adminNav = [
    'Dashboard'    => 'index.php?page=dashboard',
    'Utilisateurs' => 'index.php?page=users',
    'Services'     => 'index.php?page=services',
    'Catégories'   => 'index.php?page=categories',
    'Réservations' => 'index.php?page=reservations',
    'Offres'       => 'index.php?page=offers',
    'Forum'        => 'index.php?page=forum',
    'Réclamations' => 'index.php?page=reclamation',
    'Événements'   => 'index.php?page=events',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="admin-body dark">
    <div class="admin-shell">
        <aside class="admin-sidebar">
            <a href="../front/index.php?page=home" class="admin-brand">
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
                        <?php echo $label; ?>
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
                        <a class="ghost-btn" href="../front/index.php?page=home">Voir le site</a>
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
    <script src="../../assets/js/offers-admin.js"></script>
</body>
</html>
