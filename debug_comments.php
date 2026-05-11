<?php
require_once 'config.php';

try {
    $db = config::getConnexion();
    
    // Check table structure
    echo "<h2>Structure de la table commentaire:</h2>";
    $query = $db->query("DESC commentaire");
    $columns = $query->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
    
    // Check first comment
    echo "<h2>Premier commentaire:</h2>";
    $query = $db->query("SELECT * FROM commentaire LIMIT 1");
    $comment = $query->fetch(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($comment);
    echo "</pre>";
    
    // Check all comments count
    echo "<h2>Total de commentaires:</h2>";
    $query = $db->query("SELECT COUNT(*) as total FROM commentaire");
    $count = $query->fetch(PDO::FETCH_ASSOC);
    echo $count['total'] . " commentaires";
    
} catch (Exception $e) {
    echo "Erreur: " . $e->getMessage();
}
?>
