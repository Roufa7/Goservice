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
    'offers',
    'forum',
    'reclamation',
    'events',
    'user_edit',
    'user_add',
    'user_read',
];

if (!in_array($page, $allowedPages, true)) {
    $page = 'dashboard';
}

$view = __DIR__ . '/pages/' . $page . '.php';

require __DIR__ . '/layouts/main.php';