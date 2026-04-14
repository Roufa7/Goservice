<?php
session_start();
require_once dirname(__DIR__) . '/model/Reclamation.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
}

try {
    $reclamationModel = new Reclamation();
    $reclamations = $reclamationModel->readAllByUserId($_SESSION['user_id']);
    
    echo json_encode(['success' => true, 'reclamations' => $reclamations]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>