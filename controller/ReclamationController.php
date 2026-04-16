<?php
require_once dirname(__DIR__) . '/model/Reclamation.php';

class ReclamationController {
    public function handleRequest() {
        $action = $_GET['action'] ?? ($_POST['action'] ?? '');
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        $userId = $_SESSION['user_id'] ?? 1;

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if ($action === 'get_all') {
                try {
                    $reclamationModel = new Reclamation();
                    $reclamations = $reclamationModel->readAllByUserId($userId);
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'reclamations' => $reclamations]);
                    exit;
                } catch (Exception $e) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                    exit;
                }
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($action === 'delete') {
                try {
                    $data = json_decode(file_get_contents('php://input'), true);
                    $id = $data['id'] ?? null;
                    
                    if (!$id) throw new Exception("ID manquant.");
                    
                    $r = new Reclamation();
                    $r->id_user = $userId;
                    $r->id_reclamation = $id;

                    if ($r->delete()) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'message' => 'Supprimé avec succès.']);
                        exit;
                    } else {
                        throw new Exception("Erreur lors de la suppression.");
                    }
                } catch (Exception $e) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                    exit;
                }
            }

            if (isset($_POST['submit_reclamation'])) {
                $r = new Reclamation();
                $id_rec = trim($_POST['id_reclamation'] ?? '');
                $r->id_user = $userId;
                $r->subject = trim($_POST['subject'] ?? '');
                $r->description = trim($_POST['description'] ?? '');
                $r->status = 'pending';

                $errors = [];
                if (empty($r->subject)) {
                    $errors[] = "Le sujet est obligatoire.";
                }
                if (empty($r->description)) {
                    $errors[] = "La description est obligatoire.";
                }

                if (empty($errors)) {
                    try {
                        if (!empty($id_rec)) {
                            $r->id_reclamation = $id_rec;
                            $r->update();
                            $msg = "Réclamation modifiée avec succès!";
                        } else {
                            $r->create();
                            $msg = "Réclamation ajoutée avec succès!";
                        }
                        if ($isAjax) {
                            header('Content-Type: application/json');
                            echo json_encode(['success' => true, 'message' => $msg]);
                            exit;
                        }
                        $_SESSION['success_message'] = $msg;
                        header("Location: index.php?page=reclamation");
                        exit;
                    } catch (Exception $e) {
                        if ($isAjax) {
                            header('Content-Type: application/json');
                            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                            exit;
                        }
                        $_SESSION['error_message'] = $e->getMessage();
                        header("Location: index.php?page=reclamation");
                        exit;
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
}

$controller = new ReclamationController();
$controller->handleRequest();
?>
