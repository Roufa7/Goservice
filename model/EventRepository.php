<?php

class EventRepository
{
    public function __construct(private PDO $pdo)
    {
        $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public function findAll(array $filters = []): array
    {
        $conditions = [];
        $params = [];
        $participantsCountSql = $this->participantsCountSql();

        if (!empty($filters['search'])) {
            $conditions[] = '(e.titre LIKE :search OR e.description LIKE :search OR e.lieu LIKE :search)';
            $params['search'] = '%' . trim((string) $filters['search']) . '%';
        }

        if (!empty($filters['type'])) {
            $conditions[] = 'e.type_evenement = :type_evenement';
            $params['type_evenement'] = trim((string) $filters['type']);
        }

        if (!empty($filters['status'])) {
            $conditions[] = 'e.statut = :statut';
            $params['statut'] = trim((string) $filters['status']);
        }

        if (!empty($filters['date_from'])) {
            $conditions[] = 'e.date_debut >= :date_from';
            $params['date_from'] = $this->normalizeDateFilter((string) $filters['date_from'], false);
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = 'e.date_debut <= :date_to';
            $params['date_to'] = $this->normalizeDateFilter((string) $filters['date_to'], true);
        }

        if (!empty($filters['availability'])) {
            $availability = trim((string) $filters['availability']);

            if ($availability === 'open') {
                $conditions[] = "e.statut != 'annule' AND e.date_fin >= NOW() AND e.nb_places > {$participantsCountSql}";
            } elseif ($availability === 'full') {
                $conditions[] = "{$participantsCountSql} >= e.nb_places";
            } elseif ($availability === 'almost_full') {
                $conditions[] = "{$participantsCountSql} < e.nb_places AND ({$participantsCountSql} / NULLIF(e.nb_places, 0)) >= 0.8";
            } elseif ($availability === 'closed') {
                $conditions[] = "(e.statut = 'annule' OR e.date_fin < NOW())";
            }
        }

        $sql = $this->eventSelectSql();
        if ($conditions) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }
        $sql .= ' ORDER BY ' . $this->eventOrderSql((string) ($filters['sort'] ?? 'date_asc'));

        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll() ?: [];
    }

    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare($this->eventSelectSql() . ' WHERE e.id_evenement = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $event = $statement->fetch();

        return $event ?: null;
    }

    public function create(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO evenement (
                titre,
                description,
                date_debut,
                date_fin,
                lieu,
                type_evenement,
                nb_places,
                image,
                statut,
                date_creation
            ) VALUES (
                :titre,
                :description,
                :date_debut,
                :date_fin,
                :lieu,
                :type_evenement,
                :nb_places,
                :image,
                :statut,
                :date_creation
            )'
        );

        $statement->execute([
            'titre' => $data['titre'],
            'description' => $data['description'],
            'date_debut' => $data['date_debut'],
            'date_fin' => $data['date_fin'],
            'lieu' => $data['lieu'],
            'type_evenement' => $data['type_evenement'],
            'nb_places' => $data['nb_places'],
            'image' => $data['image'],
            'statut' => $data['statut'],
            'date_creation' => $data['date_creation'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $statement = $this->pdo->prepare(
            'UPDATE evenement
             SET titre = :titre,
                 description = :description,
                 date_debut = :date_debut,
                 date_fin = :date_fin,
                 lieu = :lieu,
                 type_evenement = :type_evenement,
                 nb_places = :nb_places,
                 image = :image,
                 statut = :statut
             WHERE id_evenement = :id_evenement'
        );

        return $statement->execute([
            'id_evenement' => $id,
            'titre' => $data['titre'],
            'description' => $data['description'],
            'date_debut' => $data['date_debut'],
            'date_fin' => $data['date_fin'],
            'lieu' => $data['lieu'],
            'type_evenement' => $data['type_evenement'],
            'nb_places' => $data['nb_places'],
            'image' => $data['image'],
            'statut' => $data['statut'],
        ]);
    }

    public function delete(int $id): bool
    {
        $statement = $this->pdo->prepare('DELETE FROM evenement WHERE id_evenement = :id_evenement');
        return $statement->execute(['id_evenement' => $id]);
    }

    public function getStats(): array
    {
        $statement = $this->pdo->query(
            "SELECT
                COUNT(*) AS total_events,
                COALESCE(SUM(nb_places), 0) AS total_places,
                SUM(CASE WHEN statut = 'prevu' THEN 1 ELSE 0 END) AS planned_events,
                SUM(CASE WHEN statut = 'en cours' THEN 1 ELSE 0 END) AS live_events,
                SUM(CASE WHEN statut = 'annule' THEN 1 ELSE 0 END) AS cancelled_events,
                SUM(CASE WHEN date_debut >= NOW() THEN 1 ELSE 0 END) AS upcoming_events
             FROM evenement"
        );

        return $statement->fetch() ?: [
            'total_events' => 0,
            'total_places' => 0,
            'planned_events' => 0,
            'live_events' => 0,
            'cancelled_events' => 0,
            'upcoming_events' => 0,
        ];
    }

    private function eventSelectSql(): string
    {
        $participantsCountSql = $this->participantsCountSql();

        return "SELECT
                    e.*,
                    {$participantsCountSql} AS participants_count
                FROM evenement e";
    }

    private function participantsCountSql(): string
    {
        return "(
                    SELECT COUNT(*)
                    FROM participation p
                    WHERE p.id_evenement = e.id_evenement
                      AND p.statut_participation IN ('confirme', 'en attente')
                )";
    }

    private function eventOrderSql(string $sort): string
    {
        return match ($sort) {
            'priority' => "CASE
                    WHEN e.statut != 'annule' AND e.date_fin >= NOW() AND e.nb_places > participants_count THEN 0
                    WHEN e.statut = 'en cours' THEN 1
                    WHEN e.statut = 'prevu' THEN 2
                    ELSE 3
                END ASC, e.date_debut ASC, participants_count DESC",
            'date_desc' => 'e.date_debut DESC, e.id_evenement DESC',
            'title_asc' => 'e.titre ASC, e.date_debut ASC',
            'title_desc' => 'e.titre DESC, e.date_debut ASC',
            'places_desc' => 'e.nb_places DESC, e.date_debut ASC',
            'places_asc' => 'e.nb_places ASC, e.date_debut ASC',
            'popular_desc' => 'participants_count DESC, e.date_debut ASC',
            'popular_asc' => 'participants_count ASC, e.date_debut ASC',
            'created_desc' => 'e.date_creation DESC, e.id_evenement DESC',
            default => 'e.date_debut ASC, e.id_evenement DESC',
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
