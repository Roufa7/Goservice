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

// 1. Controller Routing for MVC backend operations
$controllerFile = dirname(__DIR__, 2) . '/controller/back/' . ucfirst($page) . 'Controller.php';
if (file_exists($controllerFile)) {
    require_once $controllerFile;
}

$view = __DIR__ . '/pages/' . $page . '.php';

require __DIR__ . '/layouts/main.php';