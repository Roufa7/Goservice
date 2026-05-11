<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
    'login',
    'forgot_password',
    'reset_password',
    'serviceDetails',
    'addService',
    'myServices',
    'editMyService',
    'saveService',
    'reserver',
    'confirmation',
    'myReservations',
    'savedPosts',
];

if (!in_array($page, $allowedPages, true)) {
    $page = 'home';
}

$view = __DIR__ . '/pages/' . $page . '.php';

require __DIR__ . '/layouts/main.php';
