<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
    'savedPosts',
];

if (!in_array($page, $allowedPages, true)) {
    $page = 'dashboard';
}

$eventAdminData = [];
if ($page === 'events') {
    require_once dirname(__DIR__, 2) . '/controller/EventAdminController.php';
    $eventAdminController = new EventAdminController();
    $eventAdminController->handleViewActions($_GET);
    $eventAdminController->handleRequest();
    $eventAdminData = $eventAdminController->getPageData($_GET);
} else {
    $controllerFile = dirname(__DIR__, 2) . '/controller/back/' . ucfirst($page) . 'Controller.php';
    if (file_exists($controllerFile)) {
        require_once $controllerFile;
    }
}

$view = __DIR__ . '/pages/' . $page . '.php';

$standalonePages = ['exportServicesPdf', 'exportCategoriesPdf'];
if (in_array($page, $standalonePages, true)) {
    require $view;
    exit;
}

require __DIR__ . '/layouts/main.php';
