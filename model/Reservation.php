<?php

class Reservation
{
    private $id_reservation;
    private $id_service;
    private $nom_client;
    private $prenom_client;
    private $email_client;
    private $telephone;
    private $date_souhaitee;
    private $message;
    private $statut;
    private $date_creation;

    public function __construct(
        $id_service,
        $nom_client,
        $prenom_client,
        $email_client,
        $telephone,
        $date_souhaitee,
        $message,
        $statut = 'En attente'
    ) {
        $this->id_service     = $id_service;
        $this->nom_client     = $nom_client;
        $this->prenom_client  = $prenom_client;
        $this->email_client   = $email_client;
        $this->telephone      = $telephone;
        $this->date_souhaitee = $date_souhaitee;
        $this->message        = $message;
        $this->statut         = $statut;
    }

    // GETTERS
    public function getIdReservation()  { return $this->id_reservation; }
    public function getIdService()      { return $this->id_service; }
    public function getNomClient()      { return $this->nom_client; }
    public function getPrenomClient()   { return $this->prenom_client; }
    public function getEmailClient()    { return $this->email_client; }
    public function getTelephone()      { return $this->telephone; }
    public function getDateSouhaitee()  { return $this->date_souhaitee; }
    public function getMessage()        { return $this->message; }
    public function getStatut()         { return $this->statut; }
    public function getDateCreation()   { return $this->date_creation; }

    // SETTERS
    public function setStatut($statut)               { $this->statut = $statut; }
    public function setNomClient($nom)               { $this->nom_client = $nom; }
    public function setPrenomClient($prenom)         { $this->prenom_client = $prenom; }
    public function setEmailClient($email)           { $this->email_client = $email; }
    public function setTelephone($tel)               { $this->telephone = $tel; }
    public function setDateSouhaitee($date)          { $this->date_souhaitee = $date; }
    public function setMessage($message)             { $this->message = $message; }
}
?>
