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
];

if (!in_array($page, $allowedPages, true)) {
    $page = 'dashboard';
}

$view = __DIR__ . '/pages/' . $page . '.php';

require __DIR__ . '/layouts/main.php';