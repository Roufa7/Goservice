<?php
require_once dirname(__DIR__) . '/model/Reclamation.php';

class ReclamationController {
    public function handleRequest() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_reclamation'])) {
            $reclamation = new Reclamation();
            
            // Getting values
            $reclamation->id_user = $_SESSION['user_id'] ?? 1;
            $reclamation->subject = trim($_POST['subject'] ?? '');
            $reclamation->description = trim($_POST['description'] ?? '');
            $reclamation->status = 'pending'; // Default status
            
            $errors = [];
            
            // Validation
            if (empty($reclamation->subject)) {
                $errors[] = "Le sujet est obligatoire.";
            }
            if (empty($reclamation->description)) {
                $errors[] = "La description est obligatoire.";
            }
            
            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
            
            if (empty($errors)) {
                try {
                    if ($reclamation->create()) {
                        if ($isAjax) {
                            header('Content-Type: application/json');
                            echo json_encode(['success' => true, 'message' => 'Réclamation ajoutée avec succès !']);
                            exit;
                        }
                        $_SESSION['success_message'] = "Réclamation ajoutée avec succès !";
                        header("Location: index.php?page=reclamation");
                        exit;
                    } else {
                        throw new Exception("L'insertion a échoué.");
                    }
                } catch (Exception $e) {
                    $msg = "Erreur lors de l'ajout : " . $e->getMessage();
                    if ($isAjax) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => false, 'message' => $msg]);
                        exit;
                    }
                    $_SESSION['error_message'] = $msg;
                }
            } else {
                $msg = implode('<br>', $errors);
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $msg]);
                    exit;
                }
                $_SESSION['error_message'] = $msg;
            }
        }
    }
}

// Automatically instantiate and handle the request when included by the router
$reclamationController = new ReclamationController();
$reclamationController->handleRequest();
?>
