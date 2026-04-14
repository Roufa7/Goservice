<?php
class Application {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function create(array $data): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO candidature (experience, competences, cv, message, id_user, id_offre, statut)
             VALUES (:experience, :competences, :cv, :message, :id_user, :id_offre, :statut)'
        );
        $stmt->execute([
            'experience' => $data['experience'] ?? null,
            'competences' => $data['competences'] ?? null,
            'cv' => $data['cv'] ?? null,
            'message' => $data['message'] ?? null,
            'id_user' => intval($data['id_user']),
            'id_offre' => intval($data['id_offre']),
            'statut' => $data['statut'] ?? 'en attente',
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function findByOffer(int $offerId): array {
        $stmt = $this->pdo->prepare(
            'SELECT c.*, u.nom, u.prenom, u.email FROM candidature c
             LEFT JOIN users u ON c.id_user = u.id_user
             WHERE c.id_offre = :id_offre ORDER BY c.date_candidature DESC'
        );
        $stmt->execute(['id_offre' => $offerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findAll(): array {
        $stmt = $this->pdo->prepare(
            'SELECT c.*, c.id_candidature AS id, c.date_candidature AS created_at, o.titre AS offer_titre, u.nom, u.prenom FROM candidature c
             LEFT JOIN offre o ON c.id_offre = o.id_offre
             LEFT JOIN users u ON c.id_user = u.id_user
             ORDER BY c.date_candidature DESC'
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getStats(): array {
        $stmt = $this->pdo->prepare(
            'SELECT
                COUNT(*) AS total,
                SUM(statut = "en attente") AS pending,
                SUM(statut = "acceptee") AS accepted,
                SUM(statut = "refusee") AS rejected
             FROM candidature'
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
            'UPDATE candidature SET statut = :statut WHERE id_candidature = :id'
        );
        return $stmt->execute(['statut' => $status, 'id' => $id]);
    }

    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare(
            'SELECT c.*, u.nom, u.prenom, u.email FROM candidature c
             LEFT JOIN users u ON c.id_user = u.id_user
             WHERE c.id_candidature = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare('DELETE FROM candidature WHERE id_candidature = :id');
        return $stmt->execute(['id' => $id]);
    }
}

