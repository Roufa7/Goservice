<?php

class Categorie
{
    private $id_categorie;
    private $nom;
    private $description;
    private $icone;

    public function __construct(
        $nom,
        $description,
        $icone
    ) {
        $this->nom         = $nom;
        $this->description = $description;
        $this->icone       = $icone;
    }

    // GETTERS
    public function getIdCategorie()  { return $this->id_categorie; }
    public function getNom()          { return $this->nom; }
    public function getDescription()  { return $this->description; }
    public function getIcone()        { return $this->icone; }

    // SETTERS
    public function setNom($nom)                   { $this->nom = $nom; }
    public function setDescription($description)   { $this->description = $description; }
    public function setIcone($icone)               { $this->icone = $icone; }
}
?>