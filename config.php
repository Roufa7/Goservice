<?php
class config {
    public static function getConnexion() {
        try {
            $pdo = new PDO(
                'mysql:host=127.0.0.1;port=8889;dbname=goservice;charset=utf8',
                'root',
                'root'
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $pdo;
        } catch (Exception $e) {
            die('Erreur de connexion : ' . $e->getMessage());
        }
    }
}
?>