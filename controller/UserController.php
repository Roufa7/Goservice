<?php
session_start();

// Ensure only admins can access this controller
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../view/front/index.php');
    exit;
}

require_once __DIR__ . '/../model/User.php';

$action = $_GET['action'] ?? '';
$userModel = new User();

switch ($action) {
    case 'delete':
        $id = $_GET['id'] ?? null;
        if ($id) {
            $userModel->deleteUser($id);
        }
        header('Location: ../view/back/index.php?page=users');
        exit;
        
    case 'update':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id_user'] ?? null;
            $nom = $_POST['nom'] ?? '';
            $prenom = $_POST['prenom'] ?? '';
            $email = $_POST['email'] ?? '';
            $role = $_POST['role'] ?? 'user';
            
            if ($id) {
                // If user changes role to provider, ideally we should create a provider record if it doesn't exist
                // but this is an edge case. For now, simple update is fine.
                $userModel->updateUser($id, $nom, $prenom, $email, $role);
            }
        }
        header('Location: ../view/back/index.php?page=users');
        exit;
        
    default:
        header('Location: ../view/back/index.php?page=users');
        exit;
}
?>
