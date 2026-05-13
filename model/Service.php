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
    private $adresse;
    private $latitude;
    private $longitude;

    public function __construct(
        $titre,
        $description,
        $prix,
        $disponibilite,
        $statut,
        $image,
        $id_provider,
        $id_categorie,
        $adresse   = null,
        $latitude  = null,
        $longitude = null
    ) {
        $this->titre         = $titre;
        $this->description   = $description;
        $this->prix          = $prix;
        $this->disponibilite = $disponibilite;
        $this->statut        = $statut;
        $this->image         = $image;
        $this->id_provider   = $id_provider;
        $this->id_categorie  = $id_categorie;
        $this->adresse       = $adresse;
        $this->latitude      = $latitude;
        $this->longitude     = $longitude;
    }

    public function getId()            { return $this->id_service;   }
    public function getTitre()         { return $this->titre;         }
    public function getDescription()   { return $this->description;   }
    public function getPrix()          { return $this->prix;          }
    public function getDisponibilite() { return $this->disponibilite; }
    public function getStatut()        { return $this->statut;        }
    public function getImage()         { return $this->image;         }
    public function getIdProvider()    { return $this->id_provider;   }
    public function getIdCategorie()   { return $this->id_categorie;  }
    public function getAdresse()       { return $this->adresse;       }
    public function getLatitude()      { return $this->latitude;      }
    public function getLongitude()     { return $this->longitude;     }

    public function setTitre($v)         { $this->titre         = $v; }
    public function setDescription($v)   { $this->description   = $v; }
    public function setPrix($v)          { $this->prix          = $v; }
    public function setDisponibilite($v) { $this->disponibilite = $v; }
    public function setStatut($v)        { $this->statut        = $v; }
    public function setImage($v)         { $this->image         = $v; }
    public function setIdCategorie($v)   { $this->id_categorie  = $v; }
    public function setAdresse($v)       { $this->adresse       = $v; }
    public function setLatitude($v)      { $this->latitude      = $v; }
    public function setLongitude($v)     { $this->longitude     = $v; }
}
?>