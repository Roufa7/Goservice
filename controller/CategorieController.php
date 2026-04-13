<?php

require_once __DIR__ . '/../config.php';

class CategorieController
{
    // Liste toutes les catégories
    public function listCategories()
    {
        $db = config::getConnexion();
        $sql = "SELECT * FROM categorie ORDER BY nom ASC";
        $query = $db->query($sql);
        return $query->fetchAll();
    }

    // Récupérer une catégorie par ID
    public function getCategorie($id)
    {
        $db = config::getConnexion();
        $sql = "SELECT * FROM categorie WHERE id_categorie = :id";
        $query = $db->prepare($sql);
        $query->execute(['id' => $id]);
        return $query->fetch();
    }
}
?>