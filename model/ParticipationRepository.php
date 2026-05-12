<?php

class ParticipationRepository
{
    public function __construct(private PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public function findAll(array $filters = []): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['search'])) {
            $conditions[] = '(
                p.nom_participant LIKE :search
                OR p.email_participant LIKE :search
                OR p.telephone LIKE :search
                OR e.titre LIKE :search
            )';
            $params['search'] = '%' . trim((string) $filters['search']) . '%';
        }

        if (!empty($filters['status'])) {
            $conditions[] = 'p.statut_participation = :statut_participation';
            $params['statut_participation'] = trim((string) $filters['status']);
        }

        if (!empty($filters['event_id'])) {
            $conditions[] = 'p.id_evenement = :id_evenement';
            $params['id_evenement'] = (int) $filters['event_id'];
        }

        if (!empty($filters['date_from'])) {
            $conditions[] = 'p.date_inscription >= :date_from';
            $params['date_from'] = $this->normalizeDateFilter((string) $filters['date_from'], false);
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = 'p.date_inscription <= :date_to';
            $params['date_to'] = $this->normalizeDateFilter((string) $filters['date_to'], true);
        }

        $sql = $this->participationSelectSql();
        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= ' ORDER BY ' . $this->participationOrderSql((string) ($filters['sort'] ?? 'registered_desc'));

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll() ?: [];
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare($this->participationSelectSql() . ' WHERE p.id_participation = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $participation = $statement->fetch();

        return $participation ?: null;
    }

    public function findByEventId(int $eventId, bool $activeOnly = false, int $limit = 0): array
    {
        $sql = $this->participationSelectSql() . ' WHERE p.id_evenement = :id_evenement';
        $params = ['id_evenement' => $eventId];

        if ($activeOnly) {
            $sql .= " AND p.statut_participation IN ('confirme', 'en attente')";
        }

        $sql .= ' ORDER BY p.date_inscription DESC, p.id_participation DESC';

        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int) $limit;
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll() ?: [];
    }

    public function create(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO participation (
                id_evenement,
                nom_participant,
                email_participant,
                telephone,
                date_inscription,
                statut_participation
            ) VALUES (
                :id_evenement,
                :nom_participant,
                :email_participant,
                :telephone,
                :date_inscription,
                :statut_participation
            )'
        );

        $statement->execute([
            'id_evenement' => $data['id_evenement'],
            'nom_participant' => $data['nom_participant'],
            'email_participant' => $data['email_participant'],
            'telephone' => $data['telephone'],
            'date_inscription' => $data['date_inscription'],
            'statut_participation' => $data['statut_participation'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE participation
             SET id_evenement = :id_evenement,
                 nom_participant = :nom_participant,
                 email_participant = :email_participant,
                 telephone = :telephone,
                 statut_participation = :statut_participation
             WHERE id_participation = :id_participation'
        );

        return $statement->execute([
            'id_participation' => $id,
            'id_evenement' => $data['id_evenement'],
            'nom_participant' => $data['nom_participant'],
            'email_participant' => $data['email_participant'],
            'telephone' => $data['telephone'],
            'statut_participation' => $data['statut_participation'],
        ]);
    }

    public function delete(int $id): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM participation WHERE id_participation = :id_participation');
        return $statement->execute(['id_participation' => $id]);
    }

    public function deleteByEventId(int $eventId): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM participation WHERE id_evenement = :id_evenement');
        return $statement->execute(['id_evenement' => $eventId]);
    }

    public function updateStatusForEvent(int $eventId, string $newStatus, array $fromStatuses = ['en attente']): int
    {
        $placeholders = [];
        $params = [
            'id_evenement' => $eventId,
            'new_status' => $newStatus,
        ];

        foreach (array_values($fromStatuses) as $index => $status) {
            $placeholder = ':status_' . $index;
            $placeholders[] = $placeholder;
            $params['status_' . $index] = $status;
        }

        $sql = 'UPDATE participation
                SET statut_participation = :new_status
                WHERE id_evenement = :id_evenement';

        if ($placeholders) {
            $sql .= ' AND statut_participation IN (' . implode(', ', $placeholders) . ')';
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement->rowCount();
    }

    public function countActiveByEventId(int $eventId): int
    {
        $statement = $this->pdo->prepare(
            "SELECT COUNT(*)
             FROM participation
             WHERE id_evenement = :id_evenement
               AND statut_participation IN ('confirme', 'en attente')"
        );
        $statement->execute(['id_evenement' => $eventId]);

        return (int) $statement->fetchColumn();
    }

    public function existsForEventAndEmail(int $eventId, string $email, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM participation WHERE id_evenement = :id_evenement AND email_participant = :email_participant';
        $params = [
            'id_evenement' => $eventId,
            'email_participant' => $email,
        ];

        if ($excludeId !== null) {
            $sql .= ' AND id_participation != :id_participation';
            $params['id_participation'] = $excludeId;
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return (int) $statement->fetchColumn() > 0;
    }

    public function getStats(): array
    {
        $statement = $this->pdo->query(
            "SELECT
                COUNT(*) AS total_participations,
                SUM(CASE WHEN statut_participation = 'confirme' THEN 1 ELSE 0 END) AS confirmed_participations,
                SUM(CASE WHEN statut_participation = 'en attente' THEN 1 ELSE 0 END) AS pending_participations,
                SUM(CASE WHEN statut_participation = 'annule' THEN 1 ELSE 0 END) AS cancelled_participations
             FROM participation"
        );

        return $statement->fetch() ?: [
            'total_participations' => 0,
            'confirmed_participations' => 0,
            'pending_participations' => 0,
            'cancelled_participations' => 0,
        ];
    }

    public function getStatsByEventId(int $eventId): array
    {
        $statement = $this->pdo->prepare(
            "SELECT
                COUNT(*) AS total_participations,
                SUM(CASE WHEN statut_participation = 'confirme' THEN 1 ELSE 0 END) AS confirmed_participations,
                SUM(CASE WHEN statut_participation = 'en attente' THEN 1 ELSE 0 END) AS pending_participations,
                SUM(CASE WHEN statut_participation = 'annule' THEN 1 ELSE 0 END) AS cancelled_participations,
                MAX(date_inscription) AS latest_registration
             FROM participation
             WHERE id_evenement = :id_evenement"
        );
        $statement->execute(['id_evenement' => $eventId]);

        return $statement->fetch() ?: [
            'total_participations' => 0,
            'confirmed_participations' => 0,
            'pending_participations' => 0,
            'cancelled_participations' => 0,
            'latest_registration' => null,
        ];
    }

    private function participationSelectSql(): string
    {
        return "SELECT
                    p.*,
                    e.titre AS evenement_titre,
                    e.date_debut AS evenement_date_debut,
                    e.date_fin AS evenement_date_fin,
                    e.lieu AS evenement_lieu
                FROM participation p
                INNER JOIN evenement e ON e.id_evenement = p.id_evenement";
    }

    private function participationOrderSql(string $sort): string
    {
        return match ($sort) {
            'registered_asc' => 'p.date_inscription ASC, p.id_participation ASC',
            'name_asc' => 'p.nom_participant ASC, p.date_inscription DESC',
            'name_desc' => 'p.nom_participant DESC, p.date_inscription DESC',
            'event_asc' => 'e.titre ASC, p.date_inscription DESC',
            'event_desc' => 'e.titre DESC, p.date_inscription DESC',
            'status_asc' => 'p.statut_participation ASC, p.date_inscription DESC',
            'status_desc' => 'p.statut_participation DESC, p.date_inscription DESC',
            default => 'p.date_inscription DESC, p.id_participation DESC',
        };
    }

    private function normalizeDateFilter(string $value, bool $endOfDay): string
    {
        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return $endOfDay ? '9999-12-31 23:59:59' : '1970-01-01 00:00:00';
        }

        return date($endOfDay ? 'Y-m-d 23:59:59' : 'Y-m-d 00:00:00', $timestamp);
    }
}
