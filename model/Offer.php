<?php
class Offer {
    private $pdo;
    private string $offerTable;
    private string $offerPk; //cle primaire 
    private string $userPk;

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
        $this->offerTable = $this->tableExists('offers') ? 'offers' : 'offre';
        $this->offerPk = $this->offerTable === 'offers' ? 'id' : 'id_offre';
        $this->userPk = $this->columnExists('users', 'id') ? 'id' : 'id_user';
    }

    private function tableExists(string $table): bool {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*)
             FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = :table'
        );
        $stmt->execute(['table' => $table]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function columnExists(string $table, string $column): bool {
        $stmt = $this->pdo->prepare( //requête SQL préparée.
            'SELECT COUNT(*)
             FROM information_schema.columns
             WHERE table_schema = DATABASE()
               AND table_name = :table
               AND column_name = :column'
        );
        $stmt->execute([
            'table' => $table,
            'column' => $column,
        ]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function normalizeStatusForWrite(?string $status): string {
        $status = strtolower(trim((string) $status));

        if ($this->offerTable === 'offers') {
            return $status === 'fermee' || $status === 'inactive' ? 'inactive' : 'active';
        }

        return $status === 'inactive' || $status === 'fermee' ? 'fermee' : 'ouverte';
    }

    private function getServiceIdByType(?string $type): ?int {
        if (!$this->tableExists('services')) {
            return null;
        }

        $normalized = $type ? strtolower(trim($type)) : null;

        if ($normalized !== null && $normalized !== '') {
            $stmt = $this->pdo->prepare('SELECT id FROM services WHERE LOWER(categorie) = :cat LIMIT 1');
            $stmt->execute(['cat' => $normalized]);
            $serviceId = $stmt->fetchColumn();
            if ($serviceId !== false) {
                return (int) $serviceId;
            }
        }

        $fallback = $this->pdo->query('SELECT id FROM services ORDER BY id ASC LIMIT 1')->fetchColumn();
        return $fallback !== false ? (int) $fallback : null;
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
        if ($this->offerTable === 'offers') {
            $stmt = $this->pdo->prepare(
                'SELECT
                    o.id AS id_offre,
                    o.titre,
                    o.description,
                    NULL AS localisation,
                    COALESCE(o.created_at, o.date_debut) AS date_publication,
                    o.date_fin AS date_expiration,
                    CASE
                        WHEN o.statut = "active" THEN "ouverte"
                        WHEN o.statut = "inactive" THEN "fermee"
                        ELSE "fermee"
                    END AS statut,
                    LOWER(COALESCE(s.categorie, "cuisine")) AS type_service,
                    o.prix,
                    o.creator_id AS id_admin,
                    u.nom AS admin_nom,
                    u.prenom AS admin_prenom
                 FROM offers o
                 LEFT JOIN services s ON o.service_id = s.id
                 LEFT JOIN users u ON o.creator_id = u.' . $this->userPk . '
                 ORDER BY COALESCE(o.created_at, o.date_debut) DESC'
            );
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []; //récupérer toutes les lignes en tab
        }

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
        if ($this->offerTable === 'offers') {
            $stmt = $this->pdo->prepare(
                'SELECT
                    o.id AS id_offre,
                    o.titre,
                    o.description,
                    NULL AS localisation,
                    COALESCE(o.created_at, o.date_debut) AS date_publication,
                    o.date_fin AS date_expiration,
                    CASE
                        WHEN o.statut = "active" THEN "ouverte"
                        WHEN o.statut = "inactive" THEN "fermee"
                        ELSE "fermee"
                    END AS statut,
                    LOWER(COALESCE(s.categorie, "cuisine")) AS type_service,
                    o.prix,
                    o.creator_id AS id_admin,
                    u.nom AS admin_nom,
                    u.prenom AS admin_prenom
                 FROM offers o
                 LEFT JOIN services s ON o.service_id = s.id
                 LEFT JOIN users u ON o.creator_id = u.' . $this->userPk . '
                 WHERE o.statut = :statut
                 ORDER BY COALESCE(o.created_at, o.date_debut) DESC'
            );
            $stmt->execute(['statut' => 'active']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

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
        if ($this->offerTable === 'offers') {
            $stmt = $this->pdo->prepare(
                'SELECT
                    COUNT(*) AS total,
                    SUM(statut = "active") AS ouverte,
                    SUM(statut <> "active") AS fermee
                 FROM offers'
            );
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            return [
                'total' => (int) ($stats['total'] ?? 0),
                'ouverte' => (int) ($stats['ouverte'] ?? 0),
                'fermee' => (int) ($stats['fermee'] ?? 0),
            ];
        }

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
        if ($this->offerTable === 'offers') {
            $stmt = $this->pdo->prepare(
                'SELECT
                    o.id AS id_offre,
                    o.titre,
                    o.description,
                    NULL AS localisation,
                    COALESCE(o.created_at, o.date_debut) AS date_publication,
                    o.date_fin AS date_expiration,
                    CASE
                        WHEN o.statut = "active" THEN "ouverte"
                        WHEN o.statut = "inactive" THEN "fermee"
                        ELSE "fermee"
                    END AS statut,
                    LOWER(COALESCE(s.categorie, "cuisine")) AS type_service,
                    o.prix,
                    o.creator_id AS id_admin,
                    u.nom AS admin_nom,
                    u.prenom AS admin_prenom
                 FROM offers o
                 LEFT JOIN services s ON o.service_id = s.id
                 LEFT JOIN users u ON o.creator_id = u.' . $this->userPk . '
                 WHERE o.id = :id'
            );
            $stmt->execute(['id' => $id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result ?: null;
        }

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
        if ($this->offerTable === 'offers') {
            $serviceId = $this->getServiceIdByType($data['type_service'] ?? null);
            $stmt = $this->pdo->prepare(
                'INSERT INTO offers (titre, description, prix, service_id, creator_id, statut, date_debut, date_fin, image)
                 VALUES (:titre, :description, :prix, :service_id, :creator_id, :statut, NOW(), :date_fin, :image)'
            );
            $stmt->execute([
                'titre' => $data['titre'],
                'description' => $data['description'] ?? null,
                'prix' => $data['prix'] ?? null,
                'service_id' => $serviceId,
                'creator_id' => isset($data['id_admin']) && is_numeric($data['id_admin']) ? intval($data['id_admin']) : null,
                'statut' => $this->normalizeStatusForWrite($data['statut'] ?? 'active'),
                'date_fin' => $data['date_expiration'] ?? null,
                'image' => $data['image'] ?? null,
            ]);
            return (int) $this->pdo->lastInsertId();
        }

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
        if ($this->offerTable === 'offers') {
            $fields = [];
            $values = ['id' => $id];

            $mapping = [ //correspondance champs formulaire
                'titre' => 'titre',
                'description' => 'description',
                'prix' => 'prix',
                'date_expiration' => 'date_fin',
            ];

            foreach ($mapping as $input => $column) {
                if (!array_key_exists($input, $data)) {
                    continue;
                }
                $fields[] = $column . ' = :' . $input;
                $values[$input] = $data[$input];
            }

            if (array_key_exists('statut', $data)) {
                $fields[] = 'statut = :statut';
                $values['statut'] = $this->normalizeStatusForWrite($data['statut']);
            }

            if (array_key_exists('type_service', $data)) {
                $fields[] = 'service_id = :service_id';
                $values['service_id'] = $this->getServiceIdByType($data['type_service']);
            }

            if (array_key_exists('id_admin', $data)) {
                $fields[] = 'creator_id = :creator_id';
                $values['creator_id'] = is_numeric($data['id_admin']) ? intval($data['id_admin']) : null;
            }

            if (empty($fields)) {
                return false;
            }

            $sql = 'UPDATE offers SET ' . implode(', ', $fields) . ' WHERE id = :id';
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($values);
        }

        $fields = [];
        $values = ['id' => $id];
        
        foreach (['titre', 'description', 'localisation', 'date_expiration', 'statut', 'type_service', 'prix', 'id_admin'] as $field) {
            //parcourir un tableau et le traite un par un
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
        if ($this->offerTable === 'offers') {
            $stmt = $this->pdo->prepare('DELETE FROM offers WHERE id = :id');
            return $stmt->execute(['id' => $id]);
        }

        $stmt = $this->pdo->prepare('DELETE FROM offre WHERE id_offre = :id');
        return $stmt->execute(['id' => $id]);
    }
}
