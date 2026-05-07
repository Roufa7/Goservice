-- SQL Migration Script
-- This script transforms the 3-entity structure (users, provider, portfolio) 
-- into a 2-entity structure (user, profile).

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Create the new 'user' table
CREATE TABLE IF NOT EXISTS `user` (
  `id_user` int(11) NOT NULL AUTO_INCREMENT,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL,
  `reset_token` VARCHAR(255) DEFAULT NULL,
  `reset_expires_at` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id_user`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 2. Create the new 'profile' table
CREATE TABLE IF NOT EXISTS `profile` (
  `id_profile` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) NOT NULL,
  `nom` varchar(50) NOT NULL,
  `prenom` varchar(50) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `disponibilite` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id_profile`),
  KEY `id_user` (`id_user`),
  CONSTRAINT `profile_user_fk` FOREIGN KEY (`id_user`) REFERENCES `user` (`id_user`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- 3. Migrate data from 'users' and 'provider'
INSERT INTO `user` (id_user, email, password, role)
SELECT id_user, email, password, role FROM users;

INSERT INTO `profile` (id_user, nom, prenom, telephone, adresse, photo, disponibilite)
SELECT u.id_user, u.nom, u.prenom, u.telephone, u.adresse, u.photo, p.disponibilite
FROM users u
LEFT JOIN provider p ON u.id_user = p.id_user;

-- 4. Clean up old tables
DROP TABLE IF EXISTS portfolio;
DROP TABLE IF EXISTS provider;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;
