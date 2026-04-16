<?php
require_once dirname(__DIR__, 2) . '/model/Reclamation.php';
require_once dirname(__DIR__, 2) . '/model/Reponse.php';

class AdminReclamationController {
    public function handleRequest() {
        $action = $_GET['action'] ?? ($_POST['action'] ?? '');
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if ($action === 'get_all_reclamations') {
                try {
                    $reclamationModel = new Reclamation();
                    $reclamations = $reclamationModel->readAllAdmin();
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'reclamations' => $reclamations]);
                    exit;
                } catch (Exception $e) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                    exit;
                }
            } elseif ($action === 'get_all_responses') {
                try {
                    $reponseModel = new Reponse();
                    $reponses = $reponseModel->readAllAdmin();
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'reponses' => $reponses]);
                    exit;
                } catch (Exception $e) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                    exit;
                }
            }
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($action === 'process_reclamation') {
                try {
                    $id_reclamation = trim($_POST['id_reclamation'] ?? '');
                    $status = trim($_POST['status'] ?? '');
                    $content = trim($_POST['content'] ?? '');

                    if (empty($id_reclamation)) throw new Exception("ID Réclamation introuvable.");

                    if (!empty($status)) {
                        $reclamationModel = new Reclamation();
                        $reclamationModel->updateStatus($id_reclamation, $status);
                    }

                    if (!empty($content)) {
                        $reponseModel = new Reponse();
                        $reponseModel->id_reclamation = $id_reclamation;
                        $reponseModel->content = $content;
                        $reponseModel->create();
                    }

                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Traitement effectué avec succès.']);
                    exit;

                } catch (Exception $e) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                    exit;
                }
            } elseif ($action === 'delete_response') {
                try {
                    $data = json_decode(file_get_contents('php://input'), true);
                    $id = $data['id'] ?? null;
                    if (!$id) throw new Exception("ID Réponse manquant.");

                    $reponseModel = new Reponse();
                    $reponseModel->id_reponse = $id;

                    if ($reponseModel->delete()) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'message' => 'Réponse supprimée.']);
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
        }
    }
}

$controller = new AdminReclamationController();
$controller->handleRequest();
?>
