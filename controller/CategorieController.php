<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../model/Categorie.php';

class CategorieController
{
    // ── LISTE toutes les catégories ──
    public function listCategories()
    {
        $db    = config::getConnexion();
        $sql   = "SELECT * FROM categorie ORDER BY nom ASC";
        $query = $db->query($sql);
        return $query->fetchAll();
    }

    // ── LISTE avec nombre de services associés (jointure) ──
    public function listCategoriesWithCount()
    {
        $db  = config::getConnexion();
        $sql = "SELECT categorie.*,
                       COUNT(service.id_service) AS nb_services
                FROM categorie
                LEFT JOIN service ON categorie.id_categorie = service.id_categorie
                GROUP BY categorie.id_categorie
                ORDER BY categorie.nom ASC";
        $query = $db->query($sql);
        return $query->fetchAll();
    }

    // ── RÉCUPÉRER une catégorie par ID ──
    public function getCategorie($id)
    {
        $db    = config::getConnexion();
        $sql   = "SELECT * FROM categorie WHERE id_categorie = :id";
        $query = $db->prepare($sql);
        $query->execute(['id' => $id]);
        return $query->fetch();
    }

    // ── AJOUTER une catégorie ──
    public function addCategorie($categorie)
    {
        $db  = config::getConnexion();
        $sql = "INSERT INTO categorie (nom, description, icone)
                VALUES (:nom, :description, :icone)";
        $query = $db->prepare($sql);
        $query->execute([
            'nom'         => $categorie->getNom(),
            'description' => $categorie->getDescription(),
            'icone'       => $categorie->getIcone(),
        ]);
    }

    // ── MODIFIER une catégorie ──
    public function updateCategorie($categorie, $id)
    {
        $db  = config::getConnexion();
        $sql = "UPDATE categorie SET
                    nom         = :nom,
                    description = :description,
                    icone       = :icone
                WHERE id_categorie = :id";
        $query = $db->prepare($sql);
        $query->execute([
            'nom'         => $categorie->getNom(),
            'description' => $categorie->getDescription(),
            'icone'       => $categorie->getIcone(),
            'id'          => $id,
        ]);
    }

    // ── SUPPRIMER une catégorie ──
    public function deleteCategorie($id)
    {
        $db    = config::getConnexion();
        $sql   = "DELETE FROM categorie WHERE id_categorie = :id";
        $query = $db->prepare($sql);
        $query->execute(['id' => $id]);
    }

    // ── VÉRIFIER si une catégorie a des services liés ──
    public function hasServices($id)
    {
        $db    = config::getConnexion();
        $sql   = "SELECT COUNT(*) FROM service WHERE id_categorie = :id";
        $query = $db->prepare($sql);
        $query->execute(['id' => $id]);
        return (int)$query->fetchColumn() > 0;
    }

    public function getServicesByCategorie($idCategorie)
{
    $db = config::getConnexion();

    $sql = "SELECT service.*, categorie.nom AS nom_categorie
            FROM service
            INNER JOIN categorie ON service.id_categorie = categorie.id_categorie
            WHERE categorie.id_categorie = :id
            ORDER BY service.id_service DESC";

    $query = $db->prepare($sql);
    $query->execute(['id' => $idCategorie]);

    return $query->fetchAll();
}
}
?>