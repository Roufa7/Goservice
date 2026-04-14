<?php
session_start();

// For testing, set a user ID (remove in production)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

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

// 1. Controller Routing for MVC backend operations
$controllerFile = dirname(__DIR__, 2) . '/controller/' . ucfirst($page) . 'Controller.php';
if (file_exists($controllerFile)) {
    require_once $controllerFile;
}

// 2. View Definition
$view = __DIR__ . '/pages/' . $page . '.php';

// 3. Layout Rendering
require __DIR__ . '/layouts/main.php';