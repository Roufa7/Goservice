-- Applications Table (Candidatures aux offres)
CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    offer_id INT NOT NULL,
    user_id INT,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    experience VARCHAR(100),
    competences TEXT,
    cv_path VARCHAR(255),
    message TEXT,
    statut ENUM('en_attente', 'acceptee', 'rejetee') DEFAULT 'en_attente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (offer_id) REFERENCES offers(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_applications_offer ON applications(offer_id);
CREATE INDEX idx_applications_user ON applications(user_id);
CREATE INDEX idx_applications_email ON applications(email);
