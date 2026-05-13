<?php
session_start();

require_once __DIR__ . '/../model/User.php';
require_once __DIR__ . '/../lib/MailService.php';
require_once __DIR__ . '/../view/i18n.php';

function authAppRoot(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $root = dirname(dirname($script));

    if ($root === '/' || $root === '\\' || $root === '.') {
        return '';
    }

    return rtrim($root, '/');
}

function authFrontUrl(string $query = ''): string
{
    $url = authAppRoot() . '/view/front/index.php';

    if ($query !== '') {
        $url .= '?' . ltrim($query, '?');
    }

    return $url;
}

function authBackUrl(string $query = ''): string
{
    $url = authAppRoot() . '/view/back/index.php';

    if ($query !== '') {
        $url .= '?' . ltrim($query, '?');
    }

    return $url;
}

function authSetFlashSuccess(string $name): void
{
    $name = trim($name);
    $lang = $_SESSION['app_lang'] ?? 'fr';

    $_SESSION['flash_success'] = match ($lang) {
        'en' => 'Welcome back' . ($name !== '' ? ', ' . $name : '') . '.',
        'ar' => 'مرحباً بعودتك' . ($name !== '' ? '، ' . $name : '') . '.',
        default => 'Bienvenue' . ($name !== '' ? ', ' . $name : '') . ' !',
    };
}

function authHydrateSession(array $user): void
{
    $firstName = trim((string) ($user['prenom'] ?? ''));
    $lastName = trim((string) ($user['nom'] ?? ''));

    $_SESSION['user_id'] = (int) ($user['id_user'] ?? 0);
    $_SESSION['id_user'] = (int) ($user['id_user'] ?? 0);
    $_SESSION['user_role'] = (string) ($user['role'] ?? 'user');
    $_SESSION['role'] = (string) ($user['role'] ?? 'user');
    $_SESSION['user_name'] = trim($firstName . ' ' . $lastName);
    $_SESSION['prenom'] = $firstName;
    $_SESSION['nom'] = $lastName;
    $_SESSION['user_photo'] = trim((string) ($user['photo'] ?? ''));
}

$action = $_GET['action'] ?? '';
$userModel = new User();

switch ($action) {
    case 'register':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nom = trim((string) ($_POST['nom'] ?? ''));
            $prenom = trim((string) ($_POST['prenom'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            $telephone = trim((string) ($_POST['telephone'] ?? ''));
            $adresse = trim((string) ($_POST['adresse'] ?? ''));
            $role = (string) ($_POST['role'] ?? 'user');

            $photoPath = '';
            if (isset($_FILES['photo']) && ($_FILES['photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $uploadDir = __DIR__ . '/../assets/uploads/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $fileName = time() . '_' . basename((string) $_FILES['photo']['name']);
                $targetFilePath = $uploadDir . $fileName;
                $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                $fileType = mime_content_type($_FILES['photo']['tmp_name']);

                if (in_array($fileType, $allowedTypes, true) && move_uploaded_file($_FILES['photo']['tmp_name'], $targetFilePath)) {
                    $photoPath = 'assets/uploads/' . $fileName;
                }
            }

            $result = $userModel->register($nom, $prenom, $email, $password, $telephone, $adresse, $role, $photoPath);

            if ($result['success']) {
                $loginResult = $userModel->login($email, $password);
                if ($loginResult['success']) {
                    authHydrateSession($loginResult['user']);
                    authSetFlashSuccess($prenom);
                }

                header('Location: ' . authFrontUrl('page=home&success=registered'));
                exit;
            }

            header('Location: ' . authFrontUrl('page=register&error=' . urlencode((string) $result['message'])));
            exit;
        }
        break;

    case 'login':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim((string) ($_POST['email'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');

            $result = $userModel->login($email, $password);

            if ($result['success']) {
                authHydrateSession($result['user']);
                authSetFlashSuccess((string) ($result['user']['prenom'] ?? ''));

                if (($result['user']['role'] ?? 'user') === 'admin') {
                    header('Location: ' . authBackUrl('page=dashboard'));
                } else {
                    header('Location: ' . authFrontUrl('page=home'));
                }
                exit;
            }

            header('Location: ' . authFrontUrl('page=login&error=' . urlencode((string) $result['message'])));
            exit;
        }
        break;

    case 'forgot_password':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim((string) ($_POST['email'] ?? ''));
            $rawToken = $userModel->createPasswordResetToken($email);

            if ($rawToken) {
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $resetLink = $protocol . '://' . $host . authFrontUrl('page=reset_password&token=' . urlencode($rawToken));
                MailService::sendPasswordResetEmail($email, $resetLink);
            }

            header('Location: ' . authFrontUrl('page=forgot_password&status=sent'));
            exit;
        }
        break;

    case 'reset_password':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = (string) ($_POST['token'] ?? '');
            $password = (string) ($_POST['password'] ?? '');
            $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

            if ($password !== $confirmPassword) {
                header('Location: ' . authFrontUrl('page=reset_password&token=' . urlencode($token) . '&error=' . urlencode('Les mots de passe ne correspondent pas.')));
                exit;
            }

            $userId = $userModel->validateResetToken($token);
            if ($userId && $userModel->resetPasswordWithToken($userId, $password)) {
                unset($_SESSION['mock_reset_link']);
                header('Location: ' . authFrontUrl('page=login&success=password_reset'));
                exit;
            }

            header('Location: ' . authFrontUrl('page=login&error=' . urlencode('Le lien de réinitialisation est invalide ou a expiré.')));
            exit;
        }
        break;

    case 'logout':
        session_unset();
        session_destroy();
        header('Location: ' . authFrontUrl('page=home'));
        exit;

    default:
        header('Location: ' . authFrontUrl('page=home'));
        exit;
}
