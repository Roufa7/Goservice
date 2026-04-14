<?php
class Application {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        $this->ensureTableExists();
    }

    private function ensureTableExists(): void {
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    offer_id INT NOT NULL,
    user_id INT DEFAULT NULL,
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
SQL;
        $this->pdo->exec($sql);
    }

    public function create(array $data): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO applications (offer_id, user_id, nom, email, experience, competences, cv_path, message, statut)
             VALUES (:offer_id, :user_id, :nom, :email, :experience, :competences, :cv_path, :message, :statut)'
        );
        $stmt->execute([
            'offer_id' => intval($data['offer_id']),
            'user_id' => $data['user_id'] ?? null,
            'nom' => $data['nom'],
            'email' => $data['email'],
            'experience' => $data['experience'] ?? null,
            'competences' => $data['competences'] ?? null,
            'cv_path' => $data['cv_path'] ?? null,
            'message' => $data['message'] ?? null,
            'statut' => $data['statut'] ?? 'en_attente',
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function findByEmail(string $email): ?array {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM applications WHERE email = :email ORDER BY created_at DESC'
        );
        $stmt->execute(['email' => $email]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findByOffer(int $offerId): array {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM applications WHERE offer_id = :offer_id ORDER BY created_at DESC'
        );
        $stmt->execute(['offer_id' => $offerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findAll(): array {
        $stmt = $this->pdo->prepare(
            'SELECT a.*, o.titre AS offer_titre FROM applications a
             LEFT JOIN offers o ON a.offer_id = o.id
             ORDER BY a.created_at DESC'
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getStats(): array {
        $stmt = $this->pdo->prepare(
            'SELECT
                COUNT(*) AS total,
                SUM(statut = "en_attente") AS pending,
                SUM(statut = "acceptee") AS accepted,
                SUM(statut = "rejetee") AS rejected
             FROM applications'
        );
        $stmt->execute();
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total' => (int) ($stats['total'] ?? 0),
            'pending' => (int) ($stats['pending'] ?? 0),
            'accepted' => (int) ($stats['accepted'] ?? 0),
            'rejected' => (int) ($stats['rejected'] ?? 0),
        ];
    }

    public function updateStatus(int $id, string $status): bool {
        $stmt = $this->pdo->prepare(
            'UPDATE applications SET statut = :statut, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
        );
        return $stmt->execute(['statut' => $status, 'id' => $id]);
    }

    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare('DELETE FROM applications WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
