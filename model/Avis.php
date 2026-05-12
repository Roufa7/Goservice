<?php
require_once dirname(__DIR__) . '/config.php';

class Avis {
    private $id_avis;
    private $id_reclamation;
    private $rating;
    private $commentaire;
    private $created_at;

    public function __construct($id_reclamation = null, $rating = null, $commentaire = null) {
        $this->id_reclamation = $id_reclamation;
        $this->rating = $rating;
        $this->commentaire = $commentaire;
    }

    // Getters
    public function getIdAvis() { return $this->id_avis; }
    public function getIdReclamation() { return $this->id_reclamation; }
    public function getRating() { return $this->rating; }
    public function getCommentaire() { return $this->commentaire; }
    public function getCreatedAt() { return $this->created_at; }

    // Setters
    public function setIdAvis($id) { $this->id_avis = $id; }
    public function setIdReclamation($id) { $this->id_reclamation = $id; }
    public function setRating($rating) { $this->rating = $rating; }
    public function setCommentaire($commentaire) { $this->commentaire = $commentaire; }
    public function setCreatedAt($date) { $this->created_at = $date; }
}
?>
