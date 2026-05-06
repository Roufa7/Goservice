<?php
$page = $_GET['page'] ?? 'dashboard';

$allowedPages = [
    'dashboard',
    'users',
    'services',
    'offers',
    'forum',
    'reclamation',
    'events',
];

if (!in_array($page, $allowedPages, true)) {
    $page = 'dashboard';
}

$eventAdminData = [];
if ($page === 'events') {
    require_once __DIR__ . '/../../controller/EventAdminController.php';
    $eventAdminController = new EventAdminController();
    $eventAdminController->handleViewActions($_GET);
    $eventAdminController->handleRequest();
    $eventAdminData = $eventAdminController->getPageData($_GET);
}

$view = __DIR__ . '/pages/' . $page . '.php';

require __DIR__ . '/layouts/main.php';
