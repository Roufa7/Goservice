<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../model/User.php';
$userModel = new User();

// Helper to communicate with Python Flask service
function callFaceService($endpoint, $data) {
    $url = "http://localhost:5001/" . $endpoint;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 && $httpCode !== 404 && $httpCode !== 400) {
        return ['success' => false, 'message' => "Service Python injoignable (HTTP $httpCode)"];
    }
    
    return json_decode($response, true);
}

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);

switch ($action) {
    case 'enroll':
        if (!isset($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'message' => 'Non authentifié']);
            break;
        }
        
        $payload = [
            'user_id' => $_SESSION['user_id'],
            'descriptor' => $input['descriptor']
        ];
        
        $result = callFaceService('enroll', $payload);
        echo json_encode($result);
        break;

    case 'verify':
        $email = $input['email'] ?? '';
        if (empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Email requis']);
            break;
        }

        $user = $userModel->getUserByEmail($email);
        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Compte non trouvé']);
            break;
        }

        $payload = [
            'user_id' => $user['id_user'],
            'descriptor' => $input['descriptor']
        ];

        $pythonResult = callFaceService('verify', $payload);

        if (isset($pythonResult['match']) && $pythonResult['match']) {
            // SUCCESS: Setup session like standard login
            $_SESSION['user_id'] = $user['id_user'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['prenom'] . ' ' . $user['nom'];
            
            echo json_encode([
                'success' => true, 
                'redirect' => 'index.php?page=home'
            ]);
        } else {
            $message = $pythonResult['message'] ?? 'Échec de reconnaissance';
            if (isset($pythonResult['attempts']) && $pythonResult['attempts'] >= 5) {
                $message = "Compte verrouillé. Trop d'échecs.";
            }
            echo json_encode(['success' => false, 'message' => $message]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action invalide']);
        break;
}
?>
