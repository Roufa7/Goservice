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
    'serviceDetails',
    'addService',
    'myServices',
    'editMyService',
    'saveService',
    'reserver',
    'confirmation',
    'myReservations',
];

if (!in_array($page, $allowedPages, true)) {
    $page = 'home';
}

$view = __DIR__ . '/pages/' . $page . '.php';

require __DIR__ . '/layouts/main.php';