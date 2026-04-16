<?php
require_once dirname(__DIR__) . '/config.php';

class Reponse {
    private $conn;
    public $id_reponse;
    public $id_reclamation;
    public $content;
    public $created_at;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function create() {
        $query = "INSERT INTO reponse (id_reclamation, content) VALUES (:id_reclamation, :content)";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id_reclamation", $this->id_reclamation);
            $stmt->bindParam(":content", $this->content);

            if($stmt->execute()) {
                return true;
            }
            return false;
        } catch(PDOException $e) {
            throw $e;
        }
    }

    public function readAllAdmin() {
        $query = "SELECT rep.*, rec.subject as rec_subject, rec.status as rec_status, 
                         u.nom as user_nom, u.prenom as user_prenom 
                  FROM reponse rep
                  JOIN reclamation rec ON rep.id_reclamation = rec.id_reclamation
                  JOIN users u ON rec.id_user = u.id_user
                  ORDER BY rep.created_at DESC";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            throw $e;
        }
    }

    public function delete() {
        $query = "DELETE FROM reponse WHERE id_reponse = :id_reponse";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id_reponse", $this->id_reponse);
            if($stmt->execute()) {
                return true;
            }
            return false;
        } catch(PDOException $e) {
            throw $e;
        }
    }
}
?>
