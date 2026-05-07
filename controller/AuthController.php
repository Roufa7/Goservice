<?php
session_start();
require_once __DIR__ . '/../model/User.php';
require_once __DIR__ . '/../lib/MailService.php';

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

                // Validation du type de fichier (images uniquement)
                $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                $fileType = mime_content_type($_FILES['photo']['tmp_name']);

                if (in_array($fileType, $allowedTypes) && move_uploaded_file($_FILES['photo']['tmp_name'], $targetFilePath)) {
                    $photoPath = 'assets/uploads/' . $fileName;
                }
            }

            $result = $userModel->register($nom, $prenom, $email, $password, $telephone, $adresse, $role, $photoPath);

            if ($result['success']) {
                $loginResult = $userModel->login($email, $password);
                if ($loginResult['success']) {
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
                
                if ($result['user']['role'] === 'admin') {
                    header('Location: ../view/back/index.php');
                } else {
                    header('Location: ../view/front/index.php?page=home');
                }
                exit;
            } else {
                header('Location: ../view/front/index.php?page=login&error=' . urlencode($result['message']));
                exit;
            }
        }
        break;

    case 'forgot_password':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $rawToken = $userModel->createPasswordResetToken($email);

            if ($rawToken) {
                // Determine the protocol and host automatically
                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'];
                $resetLink = "$protocol://$host/view/front/index.php?page=reset_password&token=$rawToken";
                
                // Real Email sending
                MailService::sendPasswordResetEmail($email, $resetLink);
            }
            
            header('Location: ../view/front/index.php?page=forgot_password&status=sent');
            exit;
        }
        break;

    case 'reset_password':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['token'] ?? '';
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if ($password !== $confirmPassword) {
                header('Location: ../view/front/index.php?page=reset_password&token=' . $token . '&error=' . urlencode('Les mots de passe ne correspondent pas.'));
                exit;
            }

            $userId = $userModel->validateResetToken($token);
            if ($userId) {
                if ($userModel->resetPasswordWithToken($userId, $password)) {
                    unset($_SESSION['mock_reset_link']);
                    header('Location: ../view/front/index.php?page=login&success=password_reset');
                    exit;
                }
            } else {
                header('Location: ../view/front/index.php?page=login&error=' . urlencode('Le lien de réinitialisation est invalide ou a expiré.'));
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