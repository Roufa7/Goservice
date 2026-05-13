<?php
class config {
    public static function getConnexion() {
        try {
            $pdo = new PDO(
                'mysql:host=localhost;dbname=goservice;charset=utf8mb4',
                'root',
                ''
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // IMPORTANT : forcer utf8mb4 pour les emojis
            $pdo->exec("SET NAMES utf8mb4");
            $pdo->exec("SET CHARACTER SET utf8mb4");
            return $pdo;
        } catch (Exception $e) {
            die('Erreur de connexion : ' . $e->getMessage());
        }
    }
}
?>