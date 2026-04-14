<?php
class Offer {
    private $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function findAll(): array {
        $stmt = $this->pdo->prepare(
            'SELECT o.id, o.titre, o.description, o.prix, o.statut, o.date_debut, o.date_fin, o.service_id, s.titre AS service_title, s.categorie AS service_categorie
             FROM offers o
             LEFT JOIN services s ON o.service_id = s.id
             ORDER BY o.date_debut DESC'
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findActive(): array {
        $stmt = $this->pdo->prepare(
            'SELECT o.id, o.titre, o.description, o.prix, o.statut, o.date_debut, o.date_fin, o.service_id, s.titre AS service_title, s.categorie AS service_categorie
             FROM offers o
             LEFT JOIN services s ON o.service_id = s.id
             WHERE o.statut = :statut
             ORDER BY o.date_debut DESC'
        );
        $stmt->execute(['statut' => 'active']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getStats(): array {
        $stmt = $this->pdo->prepare(
            'SELECT
                COUNT(*) AS total,
                SUM(statut = "active") AS active,
                SUM(statut = "inactive") AS inactive,
                SUM(statut = "expiree") AS expiree
             FROM offers'
        );
        $stmt->execute();
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total' => (int) ($stats['total'] ?? 0),
            'active' => (int) ($stats['active'] ?? 0),
            'inactive' => (int) ($stats['inactive'] ?? 0),
            'expiree' => (int) ($stats['expiree'] ?? 0),
        ];
    }

    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM offers WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO offers (titre, description, prix, service_id, creator_id, statut, date_debut, date_fin, image)
             VALUES (:titre, :description, :prix, :service_id, :creator_id, :statut, :date_debut, :date_fin, :image)'
        );
        $stmt->execute([
            'titre' => $data['titre'],
            'description' => $data['description'] ?? null,
            'prix' => $data['prix'] ?? 0,
            'service_id' => $data['service_id'] ?? null,
            'creator_id' => $data['creator_id'],
            'statut' => $data['statut'] ?? 'active',
            'date_debut' => $data['date_debut'] ?? null,
            'date_fin' => $data['date_fin'] ?? null,
            'image' => $data['image'] ?? null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $fields = [];
        $values = ['id' => $id];
        
        foreach (['titre', 'description', 'prix', 'service_id', 'statut', 'date_debut', 'date_fin', 'image'] as $field) {
            if (isset($data[$field])) {
                $fields[] = $field . ' = :' . $field;
                $values[$field] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = 'UPDATE offers SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($values);
    }

    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare('DELETE FROM offers WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}

