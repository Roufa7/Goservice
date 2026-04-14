<?php
session_start();
require_once __DIR__ . '/../model/User.php';

$action = $_GET['action'] ?? '';

$userModel = new User();

switch ($action) {
    case 'register':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nom = $_POST['nom'] ?? '';
            $prenom = $_POST['prenom'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $telephone = $_POST['telephone'] ?? '';
            $adresse = $_POST['adresse'] ?? '';
            $role = $_POST['role'] ?? 'user';
            
            // Basic file upload handling for photo
            $photoPath = '';
            if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                // Ensure assets/uploads directory exists
                $uploadDir = __DIR__ . '/../assets/uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $fileName = time() . '_' . basename($_FILES['photo']['name']);
                $targetFilePath = $uploadDir . $fileName;
                
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $targetFilePath)) {
                    $photoPath = 'assets/uploads/' . $fileName;
                }
            }

            $result = $userModel->register($nom, $prenom, $email, $password, $telephone, $adresse, $role, $photoPath);

            if ($result['success']) {
                $loginResult = $userModel->login($email, $password);
                if($loginResult['success']) {
                    $_SESSION['user_id'] = $loginResult['user']['id_user'];
                    $_SESSION['user_role'] = $loginResult['user']['role'];
                    $_SESSION['user_name'] = $loginResult['user']['prenom'] . ' ' . $loginResult['user']['nom'];
                }
                header('Location: ../view/front/index.php?page=home&success=registered');
                exit;
            } else {
                header('Location: ../view/front/index.php?page=register&error=' . urlencode($result['message']));
                exit;
            }
        }
        break;

    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            $result = $userModel->login($email, $password);

            if ($result['success']) {
                $_SESSION['user_id'] = $result['user']['id_user'];
                $_SESSION['user_role'] = $result['user']['role'];
                $_SESSION['user_name'] = $result['user']['prenom'] . ' ' . $result['user']['nom'];
                header('Location: ../view/front/index.php?page=home');
                exit;
            } else {
                header('Location: ../view/front/index.php?page=login&error=' . urlencode($result['message']));
                exit;
            }
        }
        break;

    case 'logout':
        session_unset();
        session_destroy();
        header('Location: ../view/front/index.php?page=home');
        exit;
        break;

    default:
        header('Location: ../view/front/index.php?page=home');
        exit;
}
?>
