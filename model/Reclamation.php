<?php
require_once dirname(__DIR__) . '/config.php';

class Reclamation {
    private $conn;
    public $id_reclamation;
    public $id_user;
    public $subject;
    public $description;
    public $status;
    public $created_at;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function create() {
        $query = "INSERT INTO reclamation (id_user, subject, description, status) 
                  VALUES (:id_user, :subject, :description, :status)";
        
        try {
            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(":id_user", $this->id_user);
            $stmt->bindParam(":subject", $this->subject);
            $stmt->bindParam(":description", $this->description);
            $stmt->bindParam(":status", $this->status);

            if($stmt->execute()) {
                return true;
            }
            return false;
        } catch(PDOException $e) {
            throw $e;
        }
    }

    public function readAllByUserId($user_id) {
        $query = "SELECT * FROM reclamation WHERE id_user = :id_user ORDER BY created_at DESC";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id_user", $user_id);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            throw $e;
        }
    }

    public function update() {
        $query = "UPDATE reclamation SET subject = :subject, description = :description WHERE id_reclamation = :id_reclamation AND id_user = :id_user";
        
        try {
            $stmt = $this->conn->prepare($query);

            $stmt->bindParam(":subject", $this->subject);
            $stmt->bindParam(":description", $this->description);
            $stmt->bindParam(":id_reclamation", $this->id_reclamation);
            $stmt->bindParam(":id_user", $this->id_user);

            if($stmt->execute()) {
                return true;
            }
            return false;
        } catch(PDOException $e) {
            throw $e;
        }
    }

    public function delete() {
        $query = "DELETE FROM reclamation WHERE id_reclamation = :id_reclamation AND id_user = :id_user";
        
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id_reclamation", $this->id_reclamation);
            $stmt->bindParam(":id_user", $this->id_user);

            if($stmt->execute()) {
                return true;
            }
            return false;
        } catch(PDOException $e) {
            throw $e;
        }
    }

    public function readAllAdmin() {
        $query = "SELECT r.*, u.nom as user_nom, u.prenom as user_prenom 
                  FROM reclamation r 
                  JOIN users u ON r.id_user = u.id_user 
                  ORDER BY r.created_at DESC";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            throw $e;
        }
    }

    public function updateStatus($id_reclamation, $status) {
        $query = "UPDATE reclamation SET status = :status WHERE id_reclamation = :id_reclamation";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":status", $status);
            $stmt->bindParam(":id_reclamation", $id_reclamation);
            if($stmt->execute()) {
                return true;
            }
            return false;
        } catch(PDOException $e) {
            throw $e;
        }
    }

    public function readById($id_reclamation) {
        $query = "SELECT * FROM reclamation WHERE id_reclamation = :id_reclamation AND id_user = :id_user";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id_reclamation", $id_reclamation);
            $stmt->bindParam(":id_user", $this->id_user);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            throw $e;
        }
    }
}
?>
