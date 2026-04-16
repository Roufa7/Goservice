<?php
require 'config.php';
try {
    $pdo = config::getConnexion();
    echo "<h1>Connexion réussie !</h1>";
    
    // Check if tables exist
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "<h3>Tables dans la base de données :</h3><ul>";
    foreach($tables as $table) echo "<li>$table</li>";
    echo "</ul>";

    // Show users
    if (in_array('users', $tables)) {
        $stmt = $pdo->query("SELECT * FROM users");
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h3>Nombre d'utilisateurs : " . count($users) . "</h3>";
        if (count($users) > 0) {
            echo "<pre>";
            print_r(end($users));
            echo "</pre>";
        }
    } else {
        echo "<h3 style='color:red;'>La table 'users' n'existe pas !</h3>";
    }

} catch (Exception $e) {
    echo "<h1 style='color:red;'>Erreur : " . $e->getMessage() . "</h1>";
}
?>
