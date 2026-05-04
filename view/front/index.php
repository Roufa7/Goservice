<?php
$page = $_GET['page'] ?? 'home';

$allowedPages = [
    'home',
    'services',
    'offre',
    'forum',
    'reclamation',
    'events',
    'profile',
    'register',
];

if (!in_array($page, $allowedPages, true)) {
    $page = 'home';
}

$eventFrontData = [];
if ($page === 'events') {
    require_once __DIR__ . '/../../controller/EventFrontController.php';
    $eventFrontController = new EventFrontController();
    $eventFrontController->handleRequest();
    $eventFrontData = $eventFrontController->getPageData($_GET);
}

$view = __DIR__ . '/pages/' . $page . '.php';

require __DIR__ . '/layouts/main.php';
