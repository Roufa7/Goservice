<?php

class Service
{
    private $id_service;
    private $titre;
    private $description;
    private $prix;
    private $disponibilite;
    private $statut;
    private $image;
    private $id_provider;
    private $id_categorie;

    public function __construct(
        $titre,
        $description,
        $prix,
        $disponibilite,
        $statut,
        $image,
        $id_provider,
        $id_categorie
    ) {
        $this->titre = $titre;
        $this->description = $description;
        $this->prix = $prix;
        $this->disponibilite = $disponibilite;
        $this->statut = $statut;
        $this->image = $image;
        $this->id_provider = $id_provider;
        $this->id_categorie = $id_categorie;
    }

    // GETTERS

    public function getId()
    {
        return $this->id_service;
    }

    public function getTitre()
    {
        return $this->titre;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function getPrix()
    {
        return $this->prix;
    }

    public function getDisponibilite()
    {
        return $this->disponibilite;
    }

    public function getStatut()
    {
        return $this->statut;
    }

    public function getImage()
    {
        return $this->image;
    }

    public function getIdProvider()
    {
        return $this->id_provider;
    }

    public function getIdCategorie()
    {
        return $this->id_categorie;
    }

    // SETTERS

    public function setTitre($titre)
    {
        $this->titre = $titre;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function setPrix($prix)
    {
        $this->prix = $prix;
    }

    public function setDisponibilite($disponibilite)
    {
        $this->disponibilite = $disponibilite;
    }

    public function setStatut($statut)
    {
        $this->statut = $statut;
    }

    public function setImage($image)
    {
        $this->image = $image;
    }

    public function setIdCategorie($id_categorie)
    {
        $this->id_categorie = $id_categorie;
    }
}
?>