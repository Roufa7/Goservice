<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/model/Reclamation.php';

class ReclamationController
{
    private $db;

    public function __construct()
    {
        $this->db = config::getConnexion();
    }

    public function handleRequest()
    {
        $action = $_GET['action'] ?? ($_POST['action'] ?? '');
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
        $userId = (int) ($_SESSION['user_id'] ?? $_SESSION['id_user'] ?? 0);

        if ($userId <= 0) {
            if ($isAjax || $action !== '') {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Veuillez vous connecter pour effectuer cette action.']);
                exit;
            }
            return;
        }

        // Fetch data for the view if it's a normal page load
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($action)) {
            $query = "SELECT r.*, a.rating, a.commentaire as avis_commentaire, rep.content as reponse_content 
                      FROM reclamation r 
                      LEFT JOIN avis a ON r.id_reclamation = a.id_reclamation 
                      LEFT JOIN reponse rep ON r.id_reclamation = rep.id_reclamation
                      WHERE r.id_user = :id_user 
                      ORDER BY r.created_at DESC";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(":id_user", $userId);
            $stmt->execute();
            $GLOBALS['userReclamations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if ($action === 'get_all') {
                try {
                    $query = "SELECT r.*, a.rating, a.commentaire as avis_commentaire, rep.content as reponse_content 
                              FROM reclamation r 
                              LEFT JOIN avis a ON r.id_reclamation = a.id_reclamation 
                              LEFT JOIN reponse rep ON r.id_reclamation = rep.id_reclamation
                              WHERE r.id_user = :id_user 
                              ORDER BY r.created_at DESC";
                    $stmt = $this->db->prepare($query);
                    $stmt->bindParam(":id_user", $userId);
                    $stmt->execute();
                    $reclamations = $stmt->fetchAll(PDO::FETCH_ASSOC);

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

                    if (!$id)
                        throw new Exception("ID manquant.");

                    $query = "DELETE FROM reclamation WHERE id_reclamation = :id_reclamation AND id_user = :id_user";
                    $stmt = $this->db->prepare($query);
                    $stmt->bindParam(":id_reclamation", $id);
                    $stmt->bindParam(":id_user", $userId);

                    if ($stmt->execute()) {
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
                $r->setIdUser($userId);
                $r->setSubject(trim($_POST['subject'] ?? ''));
                $r->setDescription(trim($_POST['description'] ?? ''));
                $r->setStatus('pending');

                $errors = [];
                if (empty($r->getSubject())) {
                    $errors[] = "Le sujet est obligatoire.";
                }
                if (empty($r->getDescription())) {
                    $errors[] = "La description est obligatoire.";
                }

                if (empty($errors)) {
                    try {
                        if (!empty($id_rec)) {
                            $query = "UPDATE reclamation SET subject = :subject, description = :description WHERE id_reclamation = :id_reclamation AND id_user = :id_user";
                            $stmt = $this->db->prepare($query);
                            $subject = $r->getSubject();
                            $description = $r->getDescription();
                            $stmt->bindParam(":subject", $subject);
                            $stmt->bindParam(":description", $description);
                            $stmt->bindParam(":id_reclamation", $id_rec);
                            $stmt->bindParam(":id_user", $userId);
                            $stmt->execute();
                            $msg = "Réclamation modifiée avec succès!";
                        } else {
                            $query = "INSERT INTO reclamation (id_user, subject, description, status) VALUES (:id_user, :subject, :description, :status)";
                            $stmt = $this->db->prepare($query);
                            $id_user = $r->getIdUser();
                            $subject = $r->getSubject();
                            $description = $r->getDescription();
                            $status = $r->getStatus();
                            $stmt->bindParam(":id_user", $id_user);
                            $stmt->bindParam(":subject", $subject);
                            $stmt->bindParam(":description", $description);
                            $stmt->bindParam(":status", $status);
                            $stmt->execute();
                            $msg = "Réclamation ajoutée avec succès!";

                            // Envoyer notification mail à l'admin
                            require_once dirname(__DIR__) . '/model/MailService.php';
                            MailService::sendAdminNotification([
                                'id_user' => $userId,
                                'subject' => $subject,
                                'description' => $description
                            ]);
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

