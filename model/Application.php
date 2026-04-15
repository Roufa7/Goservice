<?php
class Application {
    private $pdo;
    private string $applicationTable;
    private string $userPk;
    private string $offerTable;
    private string $offerPk;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        if ($this->tableExists('applications')) {
            $this->applicationTable = 'applications';
        } elseif ($this->tableExists('candidature')) {
            $this->applicationTable = 'candidature';
        } else {
            $this->applicationTable = '';
        }
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
        $stmt = $this->pdo->prepare(
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

    private function normalizeStatusForWrite(string $status): string {
        $value = strtolower(trim($status));

        if ($this->applicationTable === 'applications') {
            if ($value === 'en attente') {
                return 'en_attente';
            }
            if ($value === 'refusee') {
                return 'rejetee';
            }
            return in_array($value, ['acceptee', 'rejetee', 'en_attente'], true) ? $value : 'en_attente';
        }

        if ($value === 'en_attente') {
            return 'en attente';
        }
        if ($value === 'rejetee') {
            return 'refusee';
        }
        return in_array($value, ['en attente', 'acceptee', 'refusee'], true) ? $value : 'en attente';
    }

    private function getUserIdentity(?int $userId, ?string $providedName, ?string $providedEmail): array {
        $name = trim((string) $providedName);
        $email = trim((string) $providedEmail);

        if ($userId !== null && $userId > 0) {
            $stmt = $this->pdo->prepare('SELECT nom, email FROM users WHERE ' . $this->userPk . ' = :id LIMIT 1');
            $stmt->execute(['id' => $userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;

            if ($row) {
                $name = $name !== '' ? $name : (string) ($row['nom'] ?? 'Candidat');
                $email = $email !== '' ? $email : (string) ($row['email'] ?? 'candidat@goservice.local');
            }
        }

        if ($name === '') {
            $name = 'Candidat';
        }
        if ($email === '') {
            $email = 'candidat@goservice.local';
        }

        return [$name, $email];
    }

    public function create(array $data): int {
        if ($this->applicationTable === '') {
            throw new RuntimeException('Table des candidatures introuvable (applications/candidature).');
        }

        if ($this->applicationTable === 'applications') {
            $userId = isset($data['id_user']) && is_numeric($data['id_user']) ? intval($data['id_user']) : null;
            [$name, $email] = $this->getUserIdentity($userId, $data['nom'] ?? null, $data['email'] ?? null);

            $stmt = $this->pdo->prepare(
                'INSERT INTO applications (offer_id, user_id, nom, email, experience, competences, cv_path, message, statut)
                 VALUES (:offer_id, :user_id, :nom, :email, :experience, :competences, :cv_path, :message, :statut)'
            );
            $stmt->execute([
                'offer_id' => intval($data['id_offre']),
                'user_id' => $userId,
                'nom' => $name,
                'email' => $email,
                'experience' => $data['experience'] ?? null,
                'competences' => $data['competences'] ?? null,
                'cv_path' => $data['cv'] ?? null,
                'message' => $data['message'] ?? null,
                'statut' => $this->normalizeStatusForWrite($data['statut'] ?? 'en attente'),
            ]);
            return (int) $this->pdo->lastInsertId();
        }

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
        if ($this->applicationTable === '') {
            return [];
        }

        if ($this->applicationTable === 'applications') {
            $stmt = $this->pdo->prepare(
                'SELECT a.*, a.id AS id_candidature, a.offer_id AS id_offre, a.cv_path AS cv, a.created_at AS date_candidature,
                        u.nom, u.prenom, u.email
                 FROM applications a
                 LEFT JOIN users u ON a.user_id = u.' . $this->userPk . '
                 WHERE a.offer_id = :id_offre
                 ORDER BY a.created_at DESC'
            );
            $stmt->execute(['id_offre' => $offerId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        $stmt = $this->pdo->prepare(
            'SELECT c.*, u.nom, u.prenom, u.email FROM candidature c
             LEFT JOIN users u ON c.id_user = u.id_user
             WHERE c.id_offre = :id_offre ORDER BY c.date_candidature DESC'
        );
        $stmt->execute(['id_offre' => $offerId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findAll(): array {
        if ($this->applicationTable === '') {
            return [];
        }

        if ($this->applicationTable === 'applications') {
            $stmt = $this->pdo->prepare(
                'SELECT
                    a.*,
                    a.id AS id,
                    a.created_at AS created_at,
                    o.titre AS offer_titre,
                    u.nom,
                    u.prenom
                 FROM applications a
                 LEFT JOIN ' . $this->offerTable . ' o ON a.offer_id = o.' . $this->offerPk . '
                 LEFT JOIN users u ON a.user_id = u.' . $this->userPk . '
                 ORDER BY a.created_at DESC'
            );
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

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
        if ($this->applicationTable === '') {
            return [
                'total' => 0,
                'pending' => 0,
                'accepted' => 0,
                'rejected' => 0,
            ];
        }

        if ($this->applicationTable === 'applications') {
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
        if ($this->applicationTable === '') {
            return false;
        }

        if ($this->applicationTable === 'applications') {
            $stmt = $this->pdo->prepare(
                'UPDATE applications SET statut = :statut WHERE id = :id'
            );
            return $stmt->execute([
                'statut' => $this->normalizeStatusForWrite($status),
                'id' => $id,
            ]);
        }

        $stmt = $this->pdo->prepare(
            'UPDATE candidature SET statut = :statut WHERE id_candidature = :id'
        );
        return $stmt->execute(['statut' => $this->normalizeStatusForWrite($status), 'id' => $id]);
    }

    public function findById(int $id): ?array {
        if ($this->applicationTable === '') {
            return null;
        }

        if ($this->applicationTable === 'applications') {
            $stmt = $this->pdo->prepare(
                'SELECT a.*, a.id AS id_candidature, a.offer_id AS id_offre, a.cv_path AS cv, a.created_at AS date_candidature,
                        u.nom, u.prenom, u.email
                 FROM applications a
                 LEFT JOIN users u ON a.user_id = u.' . $this->userPk . '
                 WHERE a.id = :id'
            );
            $stmt->execute(['id' => $id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        $stmt = $this->pdo->prepare(
            'SELECT c.*, u.nom, u.prenom, u.email FROM candidature c
             LEFT JOIN users u ON c.id_user = u.id_user
             WHERE c.id_candidature = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function delete(int $id): bool {
        if ($this->applicationTable === '') {
            return false;
        }

        if ($this->applicationTable === 'applications') {
            $stmt = $this->pdo->prepare('DELETE FROM applications WHERE id = :id');
            return $stmt->execute(['id' => $id]);
        }

        $stmt = $this->pdo->prepare('DELETE FROM candidature WHERE id_candidature = :id');
        return $stmt->execute(['id' => $id]);
    }
}
