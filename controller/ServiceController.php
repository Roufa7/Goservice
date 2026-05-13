<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../model/Service.php';

class ServiceController
{
    public function listServices()
    {
        $db  = config::getConnexion();
        $sql = "SELECT s.*, c.nom AS nom_categorie, c.icone AS icone_categorie
                FROM service s
                JOIN categorie c ON s.id_categorie = c.id_categorie";
        return $db->query($sql)->fetchAll();
    }

    public function listServicesWithCategories()
    {
        return $this->listServices();
    }

    public function listServicesWithCoords(): array
    {
        $db  = config::getConnexion();
        $sql = "SELECT s.id_service, s.titre, s.prix, s.disponibilite, s.statut,
                       s.adresse, s.latitude, s.longitude, s.image,
                       c.nom AS nom_categorie, c.icone AS icone_categorie
                FROM service s
                JOIN categorie c ON s.id_categorie = c.id_categorie
                WHERE s.latitude IS NOT NULL AND s.longitude IS NOT NULL
                ORDER BY s.id_service DESC";
        return $db->query($sql)->fetchAll();
    }

    public function listServicesByProvider($id_provider)
    {
        $db  = config::getConnexion();
        $sql = "SELECT s.*, c.nom AS nom_categorie, c.icone AS icone_categorie
                FROM service s
                JOIN categorie c ON s.id_categorie = c.id_categorie
                WHERE s.id_provider = :id_provider
                ORDER BY s.id_service DESC";
        $q = $db->prepare($sql);
        $q->execute(['id_provider' => $id_provider]);
        return $q->fetchAll();
    }

    public function addService($service)
    {
        $db  = config::getConnexion();
        $sql = "INSERT INTO service
                (titre, description, prix, disponibilite, statut, image,
                 id_provider, id_categorie, adresse, latitude, longitude)
                VALUES
                (:titre, :description, :prix, :disponibilite, :statut, :image,
                 :id_provider, :id_categorie, :adresse, :latitude, :longitude)";
        $q = $db->prepare($sql);
        $q->execute([
            'titre'         => $service->getTitre(),
            'description'   => $service->getDescription(),
            'prix'          => $service->getPrix(),
            'disponibilite' => $service->getDisponibilite(),
            'statut'        => $service->getStatut(),
            'image'         => $service->getImage(),
            'id_provider'   => $service->getIdProvider(),
            'id_categorie'  => $service->getIdCategorie(),
            'adresse'       => $service->getAdresse(),
            'latitude'      => $service->getLatitude(),
            'longitude'     => $service->getLongitude(),
        ]);
        return (int)$db->lastInsertId();
    }

    public function deleteService($id)
    {
        $db = config::getConnexion();
        $db->prepare("DELETE FROM service WHERE id_service = :id")->execute(['id' => $id]);
    }

    public function deleteServiceByProvider($id_service, $id_provider)
    {
        $db = config::getConnexion();
        $db->prepare("DELETE FROM service WHERE id_service = :id_service AND id_provider = :id_provider")
           ->execute(['id_service' => $id_service, 'id_provider' => $id_provider]);
    }

    public function getService($id)
    {
        $db = config::getConnexion();
        $q  = $db->prepare("SELECT * FROM service WHERE id_service = :id");
        $q->execute(['id' => $id]);
        return $q->fetch();
    }

    public function getServiceWithCategory($id)
    {
        $db  = config::getConnexion();
        $sql = "SELECT s.*, c.nom AS nom_categorie, c.icone AS icone_categorie
                FROM service s
                JOIN categorie c ON s.id_categorie = c.id_categorie
                WHERE s.id_service = :id";
        $q = $db->prepare($sql);
        $q->execute(['id' => $id]);
        return $q->fetch();
    }

    public function getServiceByIdAndProvider($id_service, $id_provider)
    {
        $db  = config::getConnexion();
        $sql = "SELECT s.*, c.nom AS nom_categorie, c.icone AS icone_categorie
                FROM service s
                JOIN categorie c ON s.id_categorie = c.id_categorie
                WHERE s.id_service = :id_service AND s.id_provider = :id_provider";
        $q = $db->prepare($sql);
        $q->execute(['id_service' => $id_service, 'id_provider' => $id_provider]);
        return $q->fetch();
    }

    public function updateService($service, $id)
    {
        $db  = config::getConnexion();
        $sql = "UPDATE service SET
                titre         = :titre,
                description   = :description,
                prix          = :prix,
                disponibilite = :disponibilite,
                statut        = :statut,
                image         = :image,
                id_categorie  = :id_categorie,
                adresse       = :adresse,
                latitude      = :latitude,
                longitude     = :longitude
                WHERE id_service = :id";
        $q = $db->prepare($sql);
        $q->execute([
            'titre'         => $service->getTitre(),
            'description'   => $service->getDescription(),
            'prix'          => $service->getPrix(),
            'disponibilite' => $service->getDisponibilite(),
            'statut'        => $service->getStatut(),
            'image'         => $service->getImage(),
            'id_categorie'  => $service->getIdCategorie(),
            'adresse'       => $service->getAdresse(),
            'latitude'      => $service->getLatitude(),
            'longitude'     => $service->getLongitude(),
            'id'            => $id,
        ]);
    }

    public function updateServiceByProvider($service, $id_service, $id_provider)
    {
        $db  = config::getConnexion();
        $sql = "UPDATE service SET
                titre         = :titre,
                description   = :description,
                prix          = :prix,
                disponibilite = :disponibilite,
                statut        = :statut,
                image         = :image,
                id_categorie  = :id_categorie,
                adresse       = :adresse,
                latitude      = :latitude,
                longitude     = :longitude
                WHERE id_service  = :id_service
                AND   id_provider = :id_provider";
        $q = $db->prepare($sql);
        $q->execute([
            'titre'         => $service->getTitre(),
            'description'   => $service->getDescription(),
            'prix'          => $service->getPrix(),
            'disponibilite' => $service->getDisponibilite(),
            'statut'        => $service->getStatut(),
            'image'         => $service->getImage(),
            'id_categorie'  => $service->getIdCategorie(),
            'adresse'       => $service->getAdresse(),
            'latitude'      => $service->getLatitude(),
            'longitude'     => $service->getLongitude(),
            'id_service'    => $id_service,
            'id_provider'   => $id_provider,
        ]);
    }

    public function getStatsGlobales(): array
    {
        $db  = config::getConnexion();
        $sql = "SELECT COUNT(*) AS total,
                       SUM(statut='Validé')            AS valides,
                       SUM(statut='En attente')        AS en_attente,
                       SUM(statut='Désactivé')         AS desactives,
                       SUM(disponibilite='Disponible') AS disponibles,
                       ROUND(AVG(prix),2)              AS prix_moyen
                FROM service";
        return $db->query($sql)->fetch();
    }

    public function getServicesParCategorie(): array
    {
        $db  = config::getConnexion();
        $sql = "SELECT c.nom, COUNT(s.id_service) AS nb
                FROM categorie c
                LEFT JOIN service s ON c.id_categorie = s.id_categorie
                GROUP BY c.id_categorie ORDER BY nb DESC";
        return $db->query($sql)->fetchAll();
    }

    public function getTopServicesWithReservations(int $limit = 5): array
    {
        $db  = config::getConnexion();
        $sql = "SELECT s.id_service, s.titre, s.prix, s.disponibilite,
                       c.nom AS nom_categorie, COUNT(r.id_reservation) AS nb_reservations
                FROM service s
                LEFT JOIN categorie c   ON s.id_categorie = c.id_categorie
                LEFT JOIN reservation r ON s.id_service   = r.id_service
                GROUP BY s.id_service ORDER BY nb_reservations DESC LIMIT :lim";
        $q = $db->prepare($sql);
        $q->bindValue(':lim', $limit, PDO::PARAM_INT);
        $q->execute();
        return $q->fetchAll();
    }
}
?>