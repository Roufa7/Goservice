<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../Model/Service.php';

class ServiceController
{
    // LISTE
    public function listServices()
    {
        $db = config::getConnexion();
        $sql = "SELECT service.*, categorie.nom AS nom_categorie
            FROM service
            JOIN categorie ON service.id_categorie = categorie.id_categorie";
        $query = $db->query($sql);
        return $query->fetchAll();
    }


    public function listServicesWithCategories()
    {
    $db = config::getConnexion();
    $sql = "SELECT service.*, categorie.nom AS nom_categorie
            FROM service
            JOIN categorie ON service.id_categorie = categorie.id_categorie";
    $query = $db->query($sql);
    return $query->fetchAll();
    }

    // AJOUT
    public function addService($service)
    {
        $db = config::getConnexion();

        $sql = "INSERT INTO service 
        (titre, description, prix, disponibilite, statut, image, id_provider, id_categorie)
        VALUES (:titre, :description, :prix, :disponibilite, :statut, :image, :id_provider, :id_categorie)";

        $query = $db->prepare($sql);

        $query->execute([
            'titre' => $service->getTitre(),
            'description' => $service->getDescription(),
            'prix' => $service->getPrix(),
            'disponibilite' => $service->getDisponibilite(),
            'statut' => $service->getStatut(),
            'image' => $service->getImage(),
            'id_provider' => $service->getIdProvider(),
            'id_categorie' => $service->getIdCategorie()
        ]);
    }

    // DELETE
    public function deleteService($id)
    {
        $db = config::getConnexion();
        $sql = "DELETE FROM service WHERE id_service = :id";
        $query = $db->prepare($sql);
        $query->execute(['id' => $id]);
    }

    // GET ONE
    public function getService($id)
    {
        $db = config::getConnexion();
        $sql = "SELECT * FROM service WHERE id_service = :id";
        $query = $db->prepare($sql);
        $query->execute(['id' => $id]);
        return $query->fetch();
    }

    public function getServiceWithCategory($id)
    {
    $db = config::getConnexion();
    $sql = "SELECT service.*, categorie.nom AS nom_categorie
            FROM service
            JOIN categorie ON service.id_categorie = categorie.id_categorie
            WHERE service.id_service = :id";
    $query = $db->prepare($sql);
    $query->execute(['id' => $id]);
    return $query->fetch();
    }
    
    // UPDATE
    public function updateService($service, $id)
    {
        $db = config::getConnexion();

        $sql = "UPDATE service SET
            titre = :titre,
            description = :description,
            prix = :prix,
            disponibilite = :disponibilite,
            statut = :statut,
            image = :image,
            id_categorie = :id_categorie
            WHERE id_service = :id";

        $query = $db->prepare($sql);

        $query->execute([
            'titre' => $service->getTitre(),
            'description' => $service->getDescription(),
            'prix' => $service->getPrix(),
            'disponibilite' => $service->getDisponibilite(),
            'statut' => $service->getStatut(),
            'image' => $service->getImage(),
            'id_categorie' => $service->getIdCategorie(),
            'id' => $id
        ]);
    }
}
?>