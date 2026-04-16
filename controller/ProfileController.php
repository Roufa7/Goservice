<?php
session_start();

// Ensure only authenticated users can access this controller
if (!isset($_SESSION['user_id'])) {
    header('Location: ../view/front/index.php?page=login');
    exit;
}

require_once __DIR__ . '/../model/User.php';

$action = $_GET['action'] ?? '';
$userModel = new User();
$userId = $_SESSION['user_id'];

switch ($action) {
    case 'update':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nom = $_POST['nom'] ?? '';
            $prenom = $_POST['prenom'] ?? '';
            $email = $_POST['email'] ?? '';
            $telephone = $_POST['telephone'] ?? '';
            $adresse = $_POST['adresse'] ?? '';
            
            $success = $userModel->updateProfile($userId, $nom, $prenom, $email, $telephone, $adresse);
            
            if ($success) {
                // Update session name just in case it changed
                $_SESSION['user_name'] = $prenom . ' ' . $nom;
                header('Location: ../view/front/index.php?page=profile&success=1');
            } else {
                header('Location: ../view/front/index.php?page=profile&error=1');
            }
            exit;
        }
        break;

    case 'delete':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $success = $userModel->deleteUser($userId);
            if ($success) {
                // Destroy session if deleted successfully
                session_unset();
                session_destroy();
                header('Location: ../view/front/index.php?page=home&deleted=1');
            } else {
                header('Location: ../view/front/index.php?page=profile&error=delete_failed');
            }
            exit;
        }
        break;
        
    default:
        header('Location: ../view/front/index.php?page=profile');
        exit;
}
?>
