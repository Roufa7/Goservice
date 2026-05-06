<?php
require_once dirname(__DIR__) . '/config.php';

class Reclamation {
    private $id_reclamation;
    private $id_user;
    private $subject;
    private $description;
    private $status;
    private $created_at;

    public function __construct($id_user = null, $subject = null, $description = null, $status = 'pending') {
        $this->id_user = $id_user;
        $this->subject = $subject;
        $this->description = $description;
        $this->status = $status;
    }

    // Getters
    public function getIdReclamation() { return $this->id_reclamation; }
    public function getIdUser() { return $this->id_user; }
    public function getSubject() { return $this->subject; }
    public function getDescription() { return $this->description; }
    public function getStatus() { return $this->status; }
    public function getCreatedAt() { return $this->created_at; }

    // Setters
    public function setIdReclamation($id) { $this->id_reclamation = $id; }
    public function setIdUser($id) { $this->id_user = $id; }
    public function setSubject($subject) { $this->subject = $subject; }
    public function setDescription($description) { $this->description = $description; }
    public function setStatus($status) { $this->status = $status; }
    public function setCreatedAt($date) { $this->created_at = $date; }
}
?>
