<?php
require_once __DIR__ . '/config.php';

try {
    $pdo = config::getConnexion();

    $sql = "CREATE TABLE IF NOT EXISTS `user_face_data` (
              `id_user` INT PRIMARY KEY,
              `face_descriptor` JSON NOT NULL,
              `confidence_threshold` FLOAT DEFAULT 0.45,
              `enrollment_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
              `last_verified_at` DATETIME NULL,
              `verification_attempts` INT DEFAULT 0,
              `failed_attempts` INT DEFAULT 0,
              `is_active` TINYINT(1) DEFAULT 1,
              `device_info` VARCHAR(255),
              `enrollment_ip` VARCHAR(45),
              `last_verified_ip` VARCHAR(45),
              FOREIGN KEY (`id_user`) REFERENCES `user`(`id_user`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql);
    echo "Table user_face_data créée ou déjà existante avec succès !";
} catch (PDOException $e) {
    echo "Erreur lors de la création de la table : " . $e->getMessage();
}
?>