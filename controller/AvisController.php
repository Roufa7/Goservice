<?php
require_once dirname(__DIR__) . '/model/Avis.php';
require_once dirname(__DIR__) . '/model/Reclamation.php';

class AvisController {
    public function handleRequest() {
        $action = $_GET['action'] ?? ($_POST['action'] ?? '');
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        $userId = $_SESSION['user_id'] ?? 1;

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if ($action === 'get_all_global') {
                try {
                    $avisModel = new Avis();
                    $avisList = $avisModel->readAll();
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'avis' => $avisList]);
                    exit;
                } catch (Exception $e) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                    exit;
                }
            } elseif ($action === 'get_my_avis') {
                try {
                    $avisModel = new Avis();
                    $avisList = $avisModel->readAllByUserId($userId);
                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'avis' => $avisList]);
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
                    if (!$id) throw new Exception("ID d'avis manquant.");
                    
                    $avisModel = new Avis();
                    $avisModel->id_avis = $id;

                    if ($avisModel->delete()) {
                        header('Content-Type: application/json');
                        echo json_encode(['success' => true, 'message' => 'Avis supprimé.']);
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

            if (isset($_POST['submit_avis'])) {
                $avis = new Avis();
                $id_avis = trim($_POST['id_avis'] ?? '');
                $avis->id_reclamation = trim($_POST['id_reclamation'] ?? '');
                $avis->rating = (int)trim($_POST['rating'] ?? 5);
                $avis->commentaire = trim($_POST['commentaire'] ?? '');

                $errors = [];
                if (empty($avis->id_reclamation)) {
                    $errors[] = "Veuillez sélectionner une réclamation associée.";
                }
                if (empty($avis->commentaire)) {
                    $errors[] = "Le commentaire est obligatoire.";
                }

                if (empty($errors)) {
                    try {
                        $success = false;
                        $msg = "";
                        if (!empty($id_avis)) {
                            $avis->id_avis = $id_avis;
                            if ($avis->update()) {
                                $success = true;
                                $msg = "Avis modifié avec succès !";
                            } else {
                                throw new Exception("La modification a échoué.");
                            }
                        } else {
                            if ($avis->create()) {
                                $success = true;
                                $msg = "Avis ajouté avec succès !";
                            } else {
                                throw new Exception("L'insertion a échoué.");
                            }
                        }

                        if ($success) {
                            if ($isAjax) {
                                header('Content-Type: application/json');
                                echo json_encode(['success' => true, 'message' => $msg]);
                                exit;
                            }
                            $_SESSION['success_message'] = $msg;
                            header("Location: index.php?page=reclamation");
                            exit;
                        }
                    } catch (Exception $e) {
                        $msg = "Erreur : " . $e->getMessage();
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
}

$avisController = new AvisController();
$avisController->handleRequest();
?>
