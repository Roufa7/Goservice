<?php

require_once __DIR__ . '/../config.php';

class RecommendationController
{
    private $db;

    public function __construct()
    {
        $this->db = config::getConnexion();
    }

    /**
     * Open offers the user has not applied to yet, ranked by profile/history heuristics.
     *
     * @return array<int, array{id: int, titre: string, type_service: string, localisation: string, score: int}>
     */
    public function recommendForUser($userId, $limit)
    {
        $userId = (int) $userId;
        $limit = (int) $limit;
        $limit = max(1, min(50, $limit));

        if ($userId <= 0) {
            return [];
        }

        try {
            $sql = '
                SELECT
                    o.id_offre AS id,
                    o.titre,
                    o.type_service,
                    o.localisation,
                    o.date_publication,
                    (
                        CASE WHEN EXISTS (
                            SELECT 1 FROM candidature ch
                            INNER JOIN offre oh ON oh.id_offre = ch.id_offre
                            WHERE ch.id_user = :uid_hist
                              AND oh.type_service = o.type_service
                        ) THEN 45 ELSE 0 END
                    )
                    + (
                        CASE
                            WHEN u.adresse IS NOT NULL
                                 AND TRIM(u.adresse) <> \'\'
                                 AND o.localisation IS NOT NULL
                                 AND TRIM(o.localisation) <> \'\'
                                 AND CHAR_LENGTH(SUBSTRING_INDEX(TRIM(u.adresse), \' \', 1)) >= 2
                                 AND CHAR_LENGTH(SUBSTRING_INDEX(TRIM(o.localisation), \' \', 1)) >= 2
                                 AND (
                                     o.localisation LIKE CONCAT(\'%\', SUBSTRING_INDEX(TRIM(u.adresse), \' \', 1), \'%\')
                                     OR u.adresse LIKE CONCAT(\'%\', SUBSTRING_INDEX(TRIM(o.localisation), \' \', 1), \'%\')
                                 )
                            THEN 35
                            ELSE 0
                        END
                    )
                    + LEAST(25, GREATEST(0, 25 - DATEDIFF(CURDATE(), DATE(o.date_publication))))
                    AS score
                FROM offre o
                LEFT JOIN users u ON u.id_user = :uid_join
                WHERE o.statut = \'ouverte\'
                  AND o.id_offre NOT IN (
                      SELECT c.id_offre FROM candidature c WHERE c.id_user = :uid_sub
                  )
                ORDER BY score DESC, o.date_publication DESC
                LIMIT ' . $limit;

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'uid_hist' => $userId,
                'uid_join' => $userId,
                'uid_sub' => $userId,
            ]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!$rows) {
                $fallbackSql = '
                    SELECT
                        o.id_offre AS id,
                        o.titre,
                        o.type_service,
                        o.localisation,
                        o.date_publication,
                        15 AS score
                    FROM offre o
                    WHERE o.statut = \'ouverte\'
                    ORDER BY o.date_publication DESC, o.id_offre DESC
                    LIMIT ' . $limit;
                $rows = $this->db->query($fallbackSql)->fetchAll(PDO::FETCH_ASSOC);
            }

            $out = [];
            foreach ($rows as $r) {
                $out[] = [
                    'id' => (int) $r['id'],
                    'titre' => (string) ($r['titre'] ?? ''),
                    'type_service' => (string) ($r['type_service'] ?? ''),
                    'localisation' => (string) ($r['localisation'] ?? ''),
                    'score' => (int) ($r['score'] ?? 0),
                ];
            }

            return $out;
        } catch (Throwable $e) {
            error_log('recommendForUser: ' . $e->getMessage());

            return [];
        }
    }
}
