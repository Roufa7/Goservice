<?php
session_start();
$page = $_GET['page'] ?? 'dashboard';

$allowedPages = [
    'dashboard',
    'users',
    'services',
    'offers',
    'forum',
    'reclamation',
    'events',
    'user_edit',
];

if (!in_array($page, $allowedPages, true)) {
    $page = 'dashboard';
}

$view = __DIR__ . '/pages/' . $page . '.php';

require __DIR__ . '/layouts/main.php';