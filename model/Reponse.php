<?php
require_once dirname(__DIR__) . '/config.php';

class Reponse
{
    private $id_reponse;
    private $id_reclamation;
    private $content;
    private $created_at;

    public function __construct($id_reclamation = null, $content = null)
    {
        $this->id_reclamation = $id_reclamation;
        $this->content = $content;
    }

    // Getters
    public function getIdReponse()
    {
        return $this->id_reponse;
    }
    public function getIdReclamation()
    {
        return $this->id_reclamation;
    }
    public function getContent()
    {
        return $this->content;
    }
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    // Setters
    public function setIdReponse($id)
    {
        $this->id_reponse = $id;
    }
    public function setIdReclamation($id)
    {
        $this->id_reclamation = $id;
    }
    public function setContent($content)
    {
        $this->content = $content;
    }
    public function setCreatedAt($date)
    {
        $this->created_at = $date;
    }
}
?>