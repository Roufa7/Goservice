<?php
require_once dirname(__DIR__) . '/config.php';

class Avis {
    private $conn;
    public $id_avis;
    public $id_reclamation;
    public $rating;
    public $commentaire;
    public $created_at;

    public function __construct() {
        $db = new Database();
        $this->conn = $db->getConnection();
    }

    public function create() {
        $query = "INSERT INTO avis (id_reclamation, rating, commentaire) 
                  VALUES (:id_reclamation, :rating, :commentaire)";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id_reclamation", $this->id_reclamation);
            $stmt->bindParam(":rating", $this->rating);
            $stmt->bindParam(":commentaire", $this->commentaire);

            if($stmt->execute()) {
                return true;
            }
            return false;
        } catch(PDOException $e) {
            throw $e;
        }
    }

    public function readAll() {
        $query = "SELECT a.*, u.nom, u.prenom 
                  FROM avis a
                  JOIN reclamation r ON a.id_reclamation = r.id_reclamation
                  JOIN users u ON r.id_user = u.id_user
                  ORDER BY a.created_at DESC";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            throw $e;
        }
    }

    public function readAllByUserId($user_id) {
        $query = "SELECT a.*, r.subject as reclamation_subject 
                  FROM avis a
                  JOIN reclamation r ON a.id_reclamation = r.id_reclamation
                  WHERE r.id_user = :id_user
                  ORDER BY a.created_at DESC";
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
        $query = "UPDATE avis 
                  SET rating = :rating, commentaire = :commentaire 
                  WHERE id_avis = :id_avis";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":rating", $this->rating);
            $stmt->bindParam(":commentaire", $this->commentaire);
            $stmt->bindParam(":id_avis", $this->id_avis);

            if($stmt->execute()) {
                return true;
            }
            return false;
        } catch(PDOException $e) {
            throw $e;
        }
    }

    public function delete() {
        $query = "DELETE FROM avis WHERE id_avis = :id_avis";
        try {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id_avis", $this->id_avis);
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
