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

    // SMTP Configuration
    const MAIL_HOST = 'smtp-relay.brevo.com';
    const MAIL_PORT = 587;
    const MAIL_USERNAME = 'aa7b80001@smtp-brevo.com';
    const MAIL_PASSWORD = 'VOTRE_MOT_DE_PASSE_SMTP_ICI';
    const MAIL_FROM = 'barrani.makram1@gmail.com';
    const MAIL_FROM_NAME = 'GoService';
}
?>