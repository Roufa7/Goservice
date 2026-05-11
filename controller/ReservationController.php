<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../model/Reservation.php';

class ReservationController
{
    // ── AJOUTER une réservation ──
    public function addReservation($reservation)
    {
        $db  = config::getConnexion();
        $sql = "INSERT INTO reservation
                    (id_service, nom_client, prenom_client, email_client,
                     telephone, date_souhaitee, message, statut)
                VALUES
                    (:id_service, :nom_client, :prenom_client, :email_client,
                     :telephone, :date_souhaitee, :message, :statut)";
        $query = $db->prepare($sql);
        $query->execute([
            'id_service'     => $reservation->getIdService(),
            'nom_client'     => $reservation->getNomClient(),
            'prenom_client'  => $reservation->getPrenomClient(),
            'email_client'   => $reservation->getEmailClient(),
            'telephone'      => $reservation->getTelephone(),
            'date_souhaitee' => $reservation->getDateSouhaitee(),
            'message'        => $reservation->getMessage(),
            'statut'         => $reservation->getStatut(),
        ]);
        return $db->lastInsertId();
    }

    // ── LISTE toutes les réservations avec info service (jointure) ──
    public function listReservations()
    {
        $db  = config::getConnexion();
        $sql = "SELECT reservation.*,
                       service.titre      AS titre_service,
                       service.prix       AS prix_service,
                       categorie.nom      AS nom_categorie
                FROM reservation
                JOIN service   ON reservation.id_service   = service.id_service
                JOIN categorie ON service.id_categorie     = categorie.id_categorie
                ORDER BY reservation.date_creation DESC";
        $query = $db->query($sql);
        return $query->fetchAll();
    }

    // ── LISTE réservations reçues par un provider (sur tous ses services) ──
    public function listReservationsByProvider($id_provider)
    {
        $db  = config::getConnexion();
        $sql = "SELECT reservation.*,
                       service.titre      AS titre_service,
                       service.prix       AS prix_service,
                       categorie.nom      AS nom_categorie
                FROM reservation
                JOIN service   ON reservation.id_service   = service.id_service
                JOIN categorie ON service.id_categorie     = categorie.id_categorie
                WHERE service.id_provider = :id_provider
                ORDER BY reservation.date_creation DESC";
        $query = $db->prepare($sql);
        $query->execute(['id_provider' => $id_provider]);
        return $query->fetchAll();
    }

    // ── STATS réservations d'un provider ──
    public function getStatsByProvider($id_provider)
    {
        $db  = config::getConnexion();
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(reservation.statut = 'En attente')  AS en_attente,
                    SUM(reservation.statut = 'Confirmée')   AS confirmees,
                    SUM(reservation.statut = 'Annulée')     AS annulees
                FROM reservation
                JOIN service ON reservation.id_service = service.id_service
                WHERE service.id_provider = :id_provider";
        $query = $db->prepare($sql);
        $query->execute(['id_provider' => $id_provider]);
        return $query->fetch();
    }

    // ── LISTE réservations d'un service précis ──
    public function listReservationsByService($id_service)
    {
        $db    = config::getConnexion();
        $sql   = "SELECT * FROM reservation
                  WHERE id_service = :id_service
                  ORDER BY date_creation DESC";
        $query = $db->prepare($sql);
        $query->execute(['id_service' => $id_service]);
        return $query->fetchAll();
    }

    // ── RÉCUPÉRER une réservation par ID ──
    public function getReservation($id)
    {
        $db    = config::getConnexion();
        $sql   = "SELECT reservation.*,
                         service.titre  AS titre_service,
                         categorie.nom  AS nom_categorie
                  FROM reservation
                  JOIN service   ON reservation.id_service   = service.id_service
                  JOIN categorie ON service.id_categorie     = categorie.id_categorie
                  WHERE reservation.id_reservation = :id";
        $query = $db->prepare($sql);
        $query->execute(['id' => $id]);
        return $query->fetch();
    }

    // ── MODIFIER le statut d'une réservation ──
    public function updateStatut($id, $statut)
    {
        $db    = config::getConnexion();
        $sql   = "UPDATE reservation SET statut = :statut WHERE id_reservation = :id";
        $query = $db->prepare($sql);
        $query->execute(['statut' => $statut, 'id' => $id]);
    }

    // ── SUPPRIMER une réservation ──
    public function deleteReservation($id)
    {
        $db    = config::getConnexion();
        $sql   = "DELETE FROM reservation WHERE id_reservation = :id";
        $query = $db->prepare($sql);
        $query->execute(['id' => $id]);
    }

    // ── STATS pour le back office ──
    public function getStats()
    {
        $db  = config::getConnexion();
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(statut = 'En attente')  AS en_attente,
                    SUM(statut = 'Confirmée')   AS confirmees,
                    SUM(statut = 'Annulée')     AS annulees
                FROM reservation";
        return $db->query($sql)->fetch();
    }
}
?>
