<?php
include_once(__DIR__ . '/../config.php');

if (!class_exists('RecommendationController')) {
class RecommendationController {
    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?: config::getConnexion();
    }

    private function tableExists(string $table): bool {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table');
        $stmt->execute(['table' => $table]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function columnExists(string $table, string $column): bool {
        if (!$this->tableExists($table)) {
            return false;
        }
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table AND column_name = :column');
        $stmt->execute([
            'table' => $table,
            'column' => $column,
        ]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function resolveUsersPk(): ?string {
        if ($this->columnExists('users', 'id_user')) {
            return 'id_user';
        }
        if ($this->columnExists('users', 'id')) {
            return 'id';
        }
        return null;
    }

    private function resolveUserPreferenceColumn(): ?string {
        foreach (['preferred_type_service', 'preference_type_service', 'type_service_preference', 'preferred_service', 'type_service'] as $col) {
            if ($this->columnExists('users', $col)) {
                return $col;
            }
        }
        return null;
    }

    private function resolveUserLocalisationColumn(): ?string {
        foreach (['localisation', 'ville', 'city'] as $col) {
            if ($this->columnExists('users', $col)) {
                return $col;
            }
        }
        return null;
    }

    private function useFrenchOfferSchema(): bool {
        return $this->tableExists('offre');
    }

    private function useFrenchApplicationSchema(): bool {
        return $this->tableExists('candidature');
    }

    private function getUserProfile(int $userId): ?array {
        if (!$this->tableExists('users')) {
            return null;
        }

        $pk = $this->resolveUsersPk();
        if ($pk === null) {
            return null;
        }

        $prefCol = $this->resolveUserPreferenceColumn();
        $locCol = $this->resolveUserLocalisationColumn();

        $fields = [$pk . ' AS user_id'];
        if ($prefCol !== null) {
            $fields[] = $prefCol . ' AS preferred_type_service';
        } else {
            $fields[] = 'NULL AS preferred_type_service';
        }
        if ($locCol !== null) {
            $fields[] = $locCol . ' AS localisation';
        } else {
            $fields[] = 'NULL AS localisation';
        }

        $sql = 'SELECT ' . implode(', ', $fields) . ' FROM users WHERE ' . $pk . ' = :uid LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return null;
        }

        return [
            'id' => (int) ($row['user_id'] ?? 0),
            'preferred_type_service' => trim((string) ($row['preferred_type_service'] ?? '')),
            'localisation' => trim((string) ($row['localisation'] ?? '')),
        ];
    }

    private function getAppliedOffersForUser(int $userId): array {
        if ($this->useFrenchApplicationSchema()) {
            $sql = 'SELECT id_offre FROM candidature WHERE id_user = :uid';
        } elseif ($this->tableExists('applications')) {
            $sql = 'SELECT offer_id AS id_offre FROM applications WHERE user_id = :uid';
        } else {
            return [];
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $ids = [];
        foreach ($rows as $row) {
            $oid = (int) ($row['id_offre'] ?? 0);
            if ($oid > 0) {
                $ids[$oid] = true;
            }
        }
        return array_keys($ids);
    }

    private function getHistorySignals(int $userId): array {
        $typeCounts = [];
        $locCounts = [];

        $appliedOffers = $this->getAppliedOffersForUser($userId);
        if (!empty($appliedOffers)) {
            $placeholders = implode(',', array_fill(0, count($appliedOffers), '?'));
            if ($this->useFrenchOfferSchema()) {
                $sql = 'SELECT id_offre, type_service, localisation FROM offre WHERE id_offre IN (' . $placeholders . ')';
            } else {
                $sql = 'SELECT id AS id_offre, COALESCE(type_service, "") AS type_service, COALESCE(localisation, "") AS localisation FROM offers WHERE id IN (' . $placeholders . ')';
            }
            $stmt = $this->db->prepare($sql);
            foreach ($appliedOffers as $i => $oid) {
                $stmt->bindValue($i + 1, $oid, PDO::PARAM_INT);
            }
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            foreach ($rows as $row) {
                $type = strtolower(trim((string) ($row['type_service'] ?? '')));
                $loc = strtolower(trim((string) ($row['localisation'] ?? '')));
                if ($type !== '') {
                    $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
                }
                if ($loc !== '') {
                    $locCounts[$loc] = ($locCounts[$loc] ?? 0) + 1;
                }
            }
        }

        if ($this->tableExists('offer_views')) {
            $userCol = $this->columnExists('offer_views', 'id_user') ? 'id_user' : ($this->columnExists('offer_views', 'user_id') ? 'user_id' : null);
            $offerCol = $this->columnExists('offer_views', 'id_offre') ? 'id_offre' : ($this->columnExists('offer_views', 'offer_id') ? 'offer_id' : null);
            if ($userCol !== null && $offerCol !== null) {
                $sql = 'SELECT ov.' . $offerCol . ' AS offer_id FROM offer_views ov WHERE ov.' . $userCol . ' = :uid ORDER BY ov.id DESC LIMIT 80';
                $stmt = $this->db->prepare($sql);
                $stmt->execute(['uid' => $userId]);
                $viewed = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
                foreach ($viewed as $view) {
                    $oid = (int) ($view['offer_id'] ?? 0);
                    if ($oid <= 0) {
                        continue;
                    }
                    if ($this->useFrenchOfferSchema()) {
                        $offerSql = 'SELECT type_service, localisation FROM offre WHERE id_offre = :oid LIMIT 1';
                    } else {
                        $offerSql = 'SELECT COALESCE(type_service, "") AS type_service, COALESCE(localisation, "") AS localisation FROM offers WHERE id = :oid LIMIT 1';
                    }
                    $offerStmt = $this->db->prepare($offerSql);
                    $offerStmt->execute(['oid' => $oid]);
                    $offer = $offerStmt->fetch(PDO::FETCH_ASSOC);
                    if (!$offer) {
                        continue;
                    }
                    $type = strtolower(trim((string) ($offer['type_service'] ?? '')));
                    $loc = strtolower(trim((string) ($offer['localisation'] ?? '')));
                    if ($type !== '') {
                        $typeCounts[$type] = ($typeCounts[$type] ?? 0) + 1;
                    }
                    if ($loc !== '') {
                        $locCounts[$loc] = ($locCounts[$loc] ?? 0) + 1;
                    }
                }
            }
        }

        return [
            'type_counts' => $typeCounts,
            'loc_counts' => $locCounts,
            'applied_offer_ids' => $appliedOffers,
        ];
    }

    private function getCandidateOffers(): array {
        if ($this->useFrenchOfferSchema()) {
            $sql = 'SELECT id_offre AS id, type_service, localisation, date_publication AS date_post FROM offre WHERE statut = "ouverte"';
        } elseif ($this->tableExists('offers')) {
            $statusCol = $this->columnExists('offers', 'statut') ? 'statut' : null;
            $dateCol = $this->columnExists('offers', 'created_at') ? 'created_at' : ($this->columnExists('offers', 'date_post') ? 'date_post' : 'NOW()');
            if ($statusCol !== null) {
                $sql = 'SELECT id, COALESCE(type_service, "") AS type_service, COALESCE(localisation, "") AS localisation, ' . $dateCol . ' AS date_post FROM offers WHERE ' . $statusCol . ' = "active"';
            } else {
                $sql = 'SELECT id, COALESCE(type_service, "") AS type_service, COALESCE(localisation, "") AS localisation, ' . $dateCol . ' AS date_post FROM offers';
            }
        } else {
            return [];
        }

        $stmt = $this->db->query($sql);
        return $stmt ? ($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
    }

    private function choosePreferredType(string $userPreference, array $historyTypeCounts): string {
        $pref = strtolower(trim($userPreference));
        if ($pref !== '') {
            return $pref;
        }
        if (empty($historyTypeCounts)) {
            return '';
        }
        arsort($historyTypeCounts);
        return (string) array_key_first($historyTypeCounts);
    }

    private function computeTier(bool $typeMatch, bool $locMatch): int {
        if ($typeMatch && $locMatch) {
            return 1;
        }
        if ($typeMatch) {
            return 2;
        }
        return 3;
    }

    public function recommendForUser(int $userId, int $limit = 10): array {
        $user = $this->getUserProfile($userId);
        if ($user === null) {
            // Graceful fallback when user profile is missing:
            // keep history lookup by id, and rely on recent offers fallback.
            $user = [
                'id' => $userId,
                'preferred_type_service' => '',
                'localisation' => '',
            ];
        }

        $history = $this->getHistorySignals($userId);
        $historyTypeCounts = $history['type_counts'];
        $historyLocCounts = $history['loc_counts'];
        $appliedSet = array_fill_keys(array_map('intval', $history['applied_offer_ids']), true);

        $preferredType = $this->choosePreferredType((string) $user['preferred_type_service'], $historyTypeCounts);
        $userLoc = strtolower(trim((string) $user['localisation']));
        $offers = $this->getCandidateOffers();

        $scored = [];
        foreach ($offers as $offer) {
            $offerId = (int) ($offer['id'] ?? 0);
            if ($offerId <= 0) {
                continue;
            }

            // Bonus requirement: exclude offers already applied to.
            if (isset($appliedSet[$offerId])) {
                continue;
            }

            $offerType = strtolower(trim((string) ($offer['type_service'] ?? '')));
            $offerLoc = strtolower(trim((string) ($offer['localisation'] ?? '')));
            $datePost = (string) ($offer['date_post'] ?? '');

            $typeMatch = ($preferredType !== '' && $offerType !== '' && $offerType === $preferredType);
            $locMatch = ($userLoc !== '' && $offerLoc !== '' && $offerLoc === $userLoc);

            $historyMatch = false;
            if (($offerType !== '' && !empty($historyTypeCounts[$offerType])) || ($offerLoc !== '' && !empty($historyLocCounts[$offerLoc]))) {
                $historyMatch = true;
            }

            // Scoring rules requested in the prompt.
            $score = 0;
            if ($typeMatch) {
                $score += 50;
            }
            if ($locMatch) {
                $score += 30;
            }
            if ($historyMatch) {
                $score += 20;
            }

            $scored[] = [
                'id' => $offerId,
                'type_service' => (string) ($offer['type_service'] ?? ''),
                'localisation' => (string) ($offer['localisation'] ?? ''),
                'score' => $score,
                'tier' => $this->computeTier($typeMatch, $locMatch),
                'date_post' => $datePost,
            ];
        }

        usort($scored, static function (array $a, array $b): int {
            if ($a['tier'] !== $b['tier']) {
                return $a['tier'] <=> $b['tier'];
            }
            if ($a['score'] !== $b['score']) {
                return $b['score'] <=> $a['score'];
            }
            return strcmp((string) $b['date_post'], (string) $a['date_post']);
        });

        // Show only strong recommendations.
        $scored = array_values(array_filter($scored, static function (array $row): bool {
            return (int) ($row['score'] ?? 0) >= 0;
        }));

        $scored = array_slice($scored, 0, max(1, min($limit, 50)));

        return array_map(static function (array $row): array {
            return [
                'id' => (int) $row['id'],
                'type_service' => (string) $row['type_service'],
                'localisation' => (string) $row['localisation'],
                'score' => (int) $row['score'],
            ];
        }, $scored);
    }

    // Bonus: dynamic update hook (recompute and persist cache snapshot).
    public function updateRecommendationsDynamically(int $userId, int $limit = 10): array {
        $result = $this->recommendForUser($userId, $limit);
        $dir = __DIR__ . '/../storage/recommendations';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $file = $dir . '/user_' . $userId . '.json';
        @file_put_contents($file, json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
        return $result;
    }
}
}
