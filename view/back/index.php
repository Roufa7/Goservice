<?php
session_start();

// Protection du Back-office : seuls les admins peuvent entrer
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../front/index.php?page=login&error=' . urlencode('Accès restreint aux administrateurs.'));
    exit;
}

$page = $_GET['page'] ?? 'dashboard';

$allowedPages = [
    'dashboard',
    'users',
    'services',
    'categories',
    'reservations',
    'offers',
    'offer_applications',
    'forum',
    'reclamation',
    'events',
    'user_edit',
    'user_add',
    'user_read',
    'addService',
    'editService',
    'exportServicesPdf',
    'exportCategoriesPdf',
];

if (!in_array($page, $allowedPages, true)) {
    $page = 'dashboard';
}

$view = __DIR__ . '/pages/' . $page . '.php';

// Pages export : HTML complet autonome, pas de layout back office
$standalonePages = ['exportServicesPdf', 'exportCategoriesPdf'];
if (in_array($page, $standalonePages, true)) {
    require $view;
    exit;
}

require __DIR__ . '/layouts/main.php';
