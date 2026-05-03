<?php
$page = $_GET['page'] ?? 'dashboard';

$allowedPages = [
    'dashboard',
    'users',
    'services',
    'categories',
    'reservations',
    'offers',
    'forum',
    'reclamation',
    'events',
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
