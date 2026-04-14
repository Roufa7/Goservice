-- 🔷 CREATION BASE
DROP DATABASE IF EXISTS goservice;
CREATE DATABASE goservice;
USE goservice;

-- 🔷 MODULE USER
CREATE TABLE users (
    id_user INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100),
    prenom VARCHAR(100),
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255),
    telephone VARCHAR(20),
    adresse VARCHAR(255),
    photo VARCHAR(255),
    role ENUM('user', 'admin') DEFAULT 'user'
);

CREATE TABLE provider (
    id_provider INT AUTO_INCREMENT PRIMARY KEY,
    specialite VARCHAR(100),
    description TEXT,
    disponibilite BOOLEAN DEFAULT TRUE,
    id_user INT UNIQUE,
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE
);

CREATE TABLE portfolio (
    id_portfolio INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(150),
    description TEXT,
    image VARCHAR(255),
    id_provider INT,
    FOREIGN KEY (id_provider) REFERENCES provider(id_provider) ON DELETE CASCADE
);

-- 🔷 MODULE SERVICE
CREATE TABLE categorie (
    id_categorie INT(11) AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT NOT NULL
);

CREATE TABLE service (
    id_service INT(11) AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    prix DECIMAL(10,2) NOT NULL,
    disponibilite VARCHAR(50) NOT NULL,
    statut VARCHAR(50) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    id_provider INT(11) NOT NULL,
    id_categorie INT(11) NOT NULL,
    FOREIGN KEY (id_provider) REFERENCES provider(id_provider) ON DELETE CASCADE,
    FOREIGN KEY (id_categorie) REFERENCES categorie(id_categorie) ON DELETE CASCADE
);

-- 🔷 MODULE EVENEMENT
CREATE TABLE evenement (
    id_evenement INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(150),
    description TEXT,
    date_debut DATETIME,
    date_fin DATETIME,
    lieu VARCHAR(150),
    type_evenement VARCHAR(50),
    nb_places INT,
    image VARCHAR(255),
    statut VARCHAR(50),
    date_creation DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE participation (
    id_participation INT AUTO_INCREMENT PRIMARY KEY,
    id_evenement INT,
    nom_participant VARCHAR(100),
    email_participant VARCHAR(150),
    telephone VARCHAR(20),
    date_inscription DATETIME DEFAULT CURRENT_TIMESTAMP,
    statut_participation VARCHAR(50),
    FOREIGN KEY (id_evenement) REFERENCES evenement(id_evenement) ON DELETE CASCADE
);

-- 🔷 MODULE OFFRE
CREATE TABLE offre (
    id_offre INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(150),
    description TEXT,
    localisation VARCHAR(150),
    date_publication DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_expiration DATETIME,
    statut ENUM('ouverte', 'fermee'),
    type_service ENUM('cuisine', 'juridique', 'plomberie', 'design', 'nettoyage', 'evenementiel') DEFAULT 'cuisine',
    prix DECIMAL(10,2) DEFAULT NULL,
    id_admin INT,
    FOREIGN KEY (id_admin) REFERENCES users(id_user) ON DELETE CASCADE
);

CREATE TABLE candidature (
    id_candidature INT AUTO_INCREMENT PRIMARY KEY,
    date_candidature DATETIME DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('en attente', 'acceptee', 'refusee'),
    experience TEXT,
    competences TEXT,
    cv VARCHAR(255),
    message TEXT,
    id_user INT,
    id_offre INT,
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE,
    FOREIGN KEY (id_offre) REFERENCES offre(id_offre) ON DELETE CASCADE
);

-- 🔷 MODULE POST
CREATE TABLE post (
    id_post INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(255),
    contenu TEXT,
    image VARCHAR(255),
    date_publication DATETIME DEFAULT CURRENT_TIMESTAMP,
    type_post VARCHAR(50),
    statut_post VARCHAR(50),
    id_user INT,
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE
);

CREATE TABLE commentaire (
    id_commentaire INT AUTO_INCREMENT PRIMARY KEY,
    contenu_commentaire TEXT,
    date_commentaire DATETIME DEFAULT CURRENT_TIMESTAMP,
    id_post INT,
    id_user INT,
    FOREIGN KEY (id_post) REFERENCES post(id_post) ON DELETE CASCADE,
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE
);

-- 🔷 MODULE RECLAMATION
CREATE TABLE reclamation (
    id_reclamation INT AUTO_INCREMENT PRIMARY KEY,
    id_user INT NOT NULL,
    subject VARCHAR(255),
    description TEXT,
    status ENUM('pending','resolved','rejected') DEFAULT 'pending',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE
);

CREATE TABLE reponse (
    id_reponse INT AUTO_INCREMENT PRIMARY KEY,
    id_reclamation INT NOT NULL,
    content TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_reclamation) REFERENCES reclamation(id_reclamation) ON DELETE CASCADE
);

CREATE TABLE avis (
    id_avis INT AUTO_INCREMENT PRIMARY KEY,
    id_reclamation INT NOT NULL,
    commentaire TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_reclamation) REFERENCES reclamation(id_reclamation) ON DELETE CASCADE
);
