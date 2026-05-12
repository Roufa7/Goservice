<?php
require_once dirname(__DIR__, 2) . '/model/Reclamation.php';
require_once dirname(__DIR__, 2) . '/model/Reponse.php';

class AdminReclamationController {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function handleRequest() {
        $action = $_GET['action'] ?? ($_POST['action'] ?? '');
        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

        // Fetch data for the view if it's a normal page load
        if ($_SERVER['REQUEST_METHOD'] === 'GET' && empty($action)) {
            // Fetch chart data: reclamations per day
            $chartSql = "SELECT 
                            DATE(created_at) as day,
                            COUNT(*) as count
                         FROM reclamation 
                         GROUP BY day
                         ORDER BY day ASC";
            
            $stmt = $this->db->prepare($chartSql);
            $stmt->execute();
            $GLOBALS['reclamationChartData'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch other stats if needed for the view
            $query = "SELECT r.*, u.nom as user_nom, u.prenom as user_prenom 
                      FROM reclamation r 
                      JOIN users u ON r.id_user = u.id_user 
                      ORDER BY r.created_at DESC";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            $GLOBALS['allReclamations'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Fetch Status Distribution
            $statusSql = "SELECT status, COUNT(*) as count FROM reclamation GROUP BY status";
            $stmt = $this->db->prepare($statusSql);
            $stmt->execute();
            $GLOBALS['statusDistribution'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            if ($action === 'get_all_reclamations') {
                try {
                    $query = "SELECT r.*, u.nom as user_nom, u.prenom as user_prenom 
                              FROM reclamation r 
                              JOIN users u ON r.id_user = u.id_user 
                              ORDER BY r.created_at DESC";
                    $stmt = $this->db->prepare($query);
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
            } elseif ($action === 'get_all_responses') {
                try {
                    $query = "SELECT rep.*, rec.subject as rec_subject, rec.status as rec_status, 
                                     u.nom as user_nom, u.prenom as user_prenom 
                              FROM reponse rep
                              JOIN reclamation rec ON rep.id_reclamation = rec.id_reclamation
                              JOIN users u ON rec.id_user = u.id_user
                              ORDER BY rep.created_at DESC";
                    $stmt = $this->db->prepare($query);
                    $stmt->execute();
                    $reponses = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'reponses' => $reponses]);
                    exit;
                } catch (Exception $e) {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                    exit;
                }
            } elseif ($action === 'get_filtered_stats') {
                try {
                    $month = $_GET['month'] ?? date('m');
                    $year = $_GET['year'] ?? date('Y');
                    
                    $query = "SELECT DATE(created_at) as day, COUNT(*) as count 
                              FROM reclamation 
                              WHERE MONTH(created_at) = :month AND YEAR(created_at) = :year
                              GROUP BY day 
                              ORDER BY day ASC";
                    
                    $stmt = $this->db->prepare($query);
                    $stmt->bindParam(':month', $month);
                    $stmt->bindParam(':year', $year);
                    $stmt->execute();
                    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    header('Content-Type: application/json');
                    echo json_encode(['success' => true, 'stats' => $stats]);
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
                        $query = "UPDATE reclamation SET status = :status WHERE id_reclamation = :id_reclamation";
                        $stmt = $this->db->prepare($query);
                        $stmt->bindParam(":status", $status);
                        $stmt->bindParam(":id_reclamation", $id_reclamation);
                        $stmt->execute();
                    }

                    if (!empty($content)) {
                        $query = "INSERT INTO reponse (id_reclamation, content) VALUES (:id_reclamation, :content)";
                        $stmt = $this->db->prepare($query);
                        $stmt->bindParam(":id_reclamation", $id_reclamation);
                        $stmt->bindParam(":content", $content);
                        $stmt->execute();
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

                    $query = "DELETE FROM reponse WHERE id_reponse = :id_reponse";
                    $stmt = $this->db->prepare($query);
                    $stmt->bindParam(":id_reponse", $id);

                    if ($stmt->execute()) {
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
