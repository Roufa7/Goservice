<?php
require_once dirname(__DIR__) . '/config.php';
require_once dirname(__DIR__) . '/model/Avis.php';
require_once dirname(__DIR__) . '/model/Reclamation.php';

class AvisController {
    private PDO $db;

    public function __construct() {
        $this->db = config::getConnexion();
    }

    public function handleRequest(): void {
        $action = $_GET['action'] ?? ($_POST['action'] ?? '');
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        $userId = $_SESSION['user_id'] ?? $_SESSION['id_user'] ?? 0;

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if ($action === 'get_all_global') {
                try {
                    $page = isset($_GET['p']) ? (int) $_GET['p'] : 1;
                    $limit = 5;
                    $offset = ($page - 1) * $limit;

                    $countStmt = $this->db->prepare('SELECT COUNT(*) FROM avis');
                    $countStmt->execute();
                    $totalItems = (int) $countStmt->fetchColumn();
                    $totalPages = (int) ceil(max(1, $totalItems) / $limit);

                    $query = "SELECT a.*, u.nom, u.prenom
                              FROM avis a
                              JOIN reclamation r ON a.id_reclamation = r.id_reclamation
                              JOIN user u ON r.id_user = u.id_user
                              ORDER BY a.created_at DESC
                              LIMIT :limit OFFSET :offset";
                    $stmt = $this->db->prepare($query);
                    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
                    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
                    $stmt->execute();
                    $avisList = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'avis' => $avisList,
                        'pagination' => [
                            'currentPage' => $page,
                            'totalPages' => $totalPages,
                            'totalItems' => $totalItems,
                            'limit' => $limit,
                        ],
                    ]);
                    exit;
                } catch (Throwable $e) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                    exit;
                }
            } elseif ($action === 'get_my_avis') {
                try {
                    $query = "SELECT a.*, r.subject AS reclamation_subject
                              FROM avis a
                              JOIN reclamation r ON a.id_reclamation = r.id_reclamation
                              WHERE r.id_user = :id_user
                              ORDER BY a.created_at DESC";
                    $stmt = $this->db->prepare($query);
                    $stmt->bindParam(':id_user', $userId);
                    $stmt->execute();
                    $avisList = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'avis' => $avisList]);
                    exit;
                } catch (Throwable $e) {
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
                    if (!$id) {
                        throw new Exception("ID d'avis manquant.");
                    }

                    $query = 'DELETE FROM avis WHERE id_avis = :id_avis';
                    $stmt = $this->db->prepare($query);
                    $stmt->bindParam(':id_avis', $id);
                    $stmt->execute();

                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'message' => 'Avis supprimé.']);
                    exit;
                } catch (Throwable $e) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                    exit;
                }
            }

            if (isset($_POST['submit_avis'])) {
                $avis = new Avis();
                $id_avis = trim($_POST['id_avis'] ?? '');
                $avis->setIdReclamation(trim($_POST['id_reclamation'] ?? ''));
                $avis->setRating((int) trim($_POST['rating'] ?? 5));
                $avis->setCommentaire(trim($_POST['commentaire'] ?? ''));

                $errors = [];
                if (empty($avis->getIdReclamation())) {
                    $errors[] = 'Veuillez sélectionner une réclamation associée.';
                }
                if (empty($avis->getCommentaire())) {
                    $errors[] = 'Le commentaire est obligatoire.';
                }

                if (empty($errors)) {
                    try {
                        if ($id_avis !== '') {
                            $query = 'UPDATE avis SET rating = :rating, commentaire = :commentaire WHERE id_avis = :id_avis';
                            $stmt = $this->db->prepare($query);
                            $rating = $avis->getRating();
                            $commentaire = $avis->getCommentaire();
                            $stmt->bindParam(':rating', $rating);
                            $stmt->bindParam(':commentaire', $commentaire);
                            $stmt->bindParam(':id_avis', $id_avis);
                            $stmt->execute();
                            $msg = 'Avis modifié avec succès !';
                        } else {
                            $query = 'INSERT INTO avis (id_reclamation, rating, commentaire) VALUES (:id_reclamation, :rating, :commentaire)';
                            $stmt = $this->db->prepare($query);
                            $id_rec = $avis->getIdReclamation();
                            $rating = $avis->getRating();
                            $commentaire = $avis->getCommentaire();
                            $stmt->bindParam(':id_reclamation', $id_rec);
                            $stmt->bindParam(':rating', $rating);
                            $stmt->bindParam(':commentaire', $commentaire);
                            $stmt->execute();
                            $msg = 'Avis ajouté avec succès !';
                        }

                        if ($isAjax) {
                            header('Content-Type: application/json');
                            echo json_encode(['success' => true, 'message' => $msg]);
                            exit;
                        }
                        $_SESSION['success_message'] = $msg;
                        header('Location: index.php?page=reclamation');
                        exit;
                    } catch (Throwable $e) {
                        $msg = 'Erreur : ' . $e->getMessage();
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