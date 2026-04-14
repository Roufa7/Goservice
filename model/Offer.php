<?php
class Offer {
    private $pdo;

    public const TYPE_SERVICE_OPTIONS = [
        'cuisine',
        'juridique',
        'plomberie',
        'design',
        'nettoyage',
        'evenementiel'
    ];

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public static function getTypeServiceOptions(): array {
        return self::TYPE_SERVICE_OPTIONS;
    }

    private function normalizeTypeService(?string $type): ?string {
        if ($type === null) {
            return null;
        }
        $value = strtolower(trim($type));
        return in_array($value, self::TYPE_SERVICE_OPTIONS, true) ? $value : null;
    }

    public function findAll(): array {
        $stmt = $this->pdo->prepare(
            'SELECT o.*, u.nom AS admin_nom, u.prenom AS admin_prenom
             FROM offre o
             LEFT JOIN users u ON o.id_admin = u.id_user
             ORDER BY o.date_publication DESC'
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findActive(): array {
        $stmt = $this->pdo->prepare(
            'SELECT o.*, u.nom AS admin_nom, u.prenom AS admin_prenom
             FROM offre o
             LEFT JOIN users u ON o.id_admin = u.id_user
             WHERE o.statut = :statut
             ORDER BY o.date_publication DESC'
        );
        $stmt->execute(['statut' => 'ouverte']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getStats(): array {
        $stmt = $this->pdo->prepare(
            'SELECT
                COUNT(*) AS total,
                SUM(statut = "ouverte") AS ouverte,
                SUM(statut = "fermee") AS fermee
             FROM offre'
        );
        $stmt->execute();
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total' => (int) ($stats['total'] ?? 0),
            'ouverte' => (int) ($stats['ouverte'] ?? 0),
            'fermee' => (int) ($stats['fermee'] ?? 0),
        ];
    }

    public function findById(int $id): ?array {
        $stmt = $this->pdo->prepare(
            'SELECT o.*, u.nom AS admin_nom, u.prenom AS admin_prenom
             FROM offre o
             LEFT JOIN users u ON o.id_admin = u.id_user
             WHERE o.id_offre = :id'
        );
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    public function create(array $data): int {
        $stmt = $this->pdo->prepare(
            'INSERT INTO offre (titre, description, localisation, date_expiration, statut, type_service, prix, id_admin)
             VALUES (:titre, :description, :localisation, :date_expiration, :statut, :type_service, :prix, :id_admin)'
        );
        $stmt->execute([
            'titre' => $data['titre'],
            'description' => $data['description'] ?? null,
            'localisation' => $data['localisation'] ?? null,
            'date_expiration' => $data['date_expiration'] ?? null,
            'statut' => $data['statut'] ?? 'ouverte',
            'type_service' => $this->normalizeTypeService($data['type_service'] ?? null),
            'prix' => $data['prix'] ?? null,
            'id_admin' => isset($data['id_admin']) && is_numeric($data['id_admin']) ? intval($data['id_admin']) : null,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $fields = [];
        $values = ['id' => $id];
        
        foreach (['titre', 'description', 'localisation', 'date_expiration', 'statut', 'type_service', 'prix', 'id_admin'] as $field) {
            if (!array_key_exists($field, $data)) {
                continue;
            }

            $fields[] = $field . ' = :' . $field;
            if ($field === 'type_service') {
                $values[$field] = $this->normalizeTypeService($data[$field]);
            } elseif ($field === 'id_admin') {
                $values[$field] = is_numeric($data[$field]) ? intval($data[$field]) : null;
            } else {
                $values[$field] = $data[$field];
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = 'UPDATE offre SET ' . implode(', ', $fields) . ' WHERE id_offre = :id';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($values);
    }

    public function delete(int $id): bool {
        $stmt = $this->pdo->prepare('DELETE FROM offre WHERE id_offre = :id');
        return $stmt->execute(['id' => $id]);
    }
}

