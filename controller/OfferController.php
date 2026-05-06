<?php
include_once(__DIR__ . '/../config.php');
include_once(__DIR__ . '/../model/Offer.php');
// Load i18n helper so controller can store translated notification headlines
if (is_file(__DIR__ . '/../view/i18n.php')) {
    include_once __DIR__ . '/../view/i18n.php';
}

if (!class_exists('OfferController')) {
class OfferController {
    public function __construct($unused = null) {
    }

    private function pushOfferNotificationToFile(array $note): void {
        $dir = __DIR__ . '/../storage';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $file = $dir . '/offer_notifications.json';

        $list = [];
        if (is_file($file)) {
            $content = @file_get_contents($file);
            if ($content !== false) {
                $decoded = json_decode($content, true);
                if (is_array($decoded)) {
                    $list = $decoded;
                }
            }
        }

        array_unshift($list, $note);
        if (count($list) > 300) {
            $list = array_slice($list, 0, 300);
        }

        @file_put_contents($file, json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    private function addOfferNotification(array $note): void {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (!isset($_SESSION['offer_notifications']) || !is_array($_SESSION['offer_notifications'])) {
            $_SESSION['offer_notifications'] = [];
        }
        array_unshift($_SESSION['offer_notifications'], $note);

        try {
            $this->pushOfferNotificationToFile($note);
        } catch (Exception $e) {
            // ignore persistence errors
        }
    }

    private function resolveStatusFromExpiration(?DateTime $dateExpiration): string {
        $today = new DateTime('today');
        $isExpired = false;
        if ($dateExpiration !== null) {
            $expirationDay = (clone $dateExpiration)->setTime(0, 0, 0);
            $isExpired = $expirationDay < $today;
        }

        if ($this->useFrenchSchema()) {
            return $isExpired ? 'fermee' : 'ouverte';
        }

        return $isExpired ? 'expiree' : 'active';
    }

    private function syncOfferStatusesByExpiration(): void {
        $db = config::getConnexion();

        if ($this->useFrenchSchema()) {
            $db->exec('UPDATE offre SET statut = "fermee" WHERE date_expiration IS NOT NULL AND DATE(date_expiration) < CURDATE()');
            $db->exec('UPDATE offre SET statut = "ouverte" WHERE date_expiration IS NULL OR DATE(date_expiration) >= CURDATE()');
            return;
        }

        $db->exec('UPDATE offers SET statut = "expiree" WHERE date_fin IS NOT NULL AND DATE(date_fin) < CURDATE()');
        $db->exec('UPDATE offers SET statut = "active" WHERE date_fin IS NULL OR DATE(date_fin) >= CURDATE()');
    }

    private function tableExists(string $table): bool {
        $db = config::getConnexion();
        $stmt = $db->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table_name');
        $stmt->execute(['table_name' => $table]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function columnExists(string $table, string $column): bool {
        if (!$this->tableExists($table)) {
            return false;
        }

        $db = config::getConnexion();
        $stmt = $db->prepare('SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table_name AND column_name = :column_name');
        $stmt->execute([
            'table_name' => $table,
            'column_name' => $column,
        ]);

        return (int) $stmt->fetchColumn() > 0;
    }

    private function resolveMetricColumn(string $table, array $candidates): ?string {
        foreach ($candidates as $candidate) {
            if ($this->columnExists($table, $candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function useFrenchSchema(): bool {
        return $this->tableExists('offre');
    }

    private function normalizeStatusForRead(?string $status): string {
        $value = strtolower(trim((string) $status));

        if (in_array($value, ['ouverte', 'active'], true)) {
            return 'ouverte';
        }

        return 'fermee';
    }

    private function normalizeStatusForWrite(?string $status): string {
        $value = strtolower(trim((string) $status));

        if ($this->useFrenchSchema()) {
            return ($value === 'fermee' || $value === 'fermée' || $value === 'inactive' || $value === 'expiree') ? 'fermee' : 'ouverte';
        }

        return ($value === 'fermee' || $value === 'fermée' || $value === 'inactive' || $value === 'expiree') ? 'inactive' : 'active';
    }

    private function mapPayloadToEntity(array $data): Offer {
        $datePublication = null;
        if (!empty($data['date_publication'])) {
            $datePublication = new DateTime((string) $data['date_publication']);
        }

        $dateExpiration = null;
        if (!empty($data['date_expiration'])) {
            $dateExpiration = new DateTime((string) $data['date_expiration']);
        }

        return new Offer(
            isset($data['id_offre']) && is_numeric($data['id_offre']) ? (int) $data['id_offre'] : null,
            isset($data['titre']) ? trim((string) $data['titre']) : null,
            isset($data['description']) ? trim((string) $data['description']) : null,
            isset($data['localisation']) ? trim((string) $data['localisation']) : null,
            $datePublication,
            $dateExpiration,
            isset($data['statut']) ? $this->normalizeStatusForRead((string) $data['statut']) : 'ouverte',
            isset($data['type_service']) ? trim((string) $data['type_service']) : null,
            isset($data['id_admin']) && is_numeric($data['id_admin']) ? (int) $data['id_admin'] : null,
            isset($data['prix']) && is_numeric($data['prix']) ? (float) $data['prix'] : null
        );
    }

    public function listOffers(): array {
        $this->syncOfferStatusesByExpiration();

        if ($this->useFrenchSchema()) {
            $sql = 'SELECT o.id_offre, o.titre, o.description, o.localisation, o.date_publication, o.date_expiration, o.statut, o.type_service, o.prix, o.id_admin, u.nom AS admin_nom, u.prenom AS admin_prenom FROM offre o LEFT JOIN users u ON o.id_admin = u.id_user ORDER BY o.date_publication DESC, o.id_offre DESC';
        } else {
            $sql = 'SELECT o.id AS id_offre, o.titre, o.description, "" AS localisation, o.created_at AS date_publication, o.date_fin AS date_expiration, CASE WHEN o.statut = "active" THEN "ouverte" ELSE "fermee" END AS statut, COALESCE(s.titre, "autre") AS type_service, o.prix, o.creator_id AS id_admin, u.nom AS admin_nom, u.prenom AS admin_prenom FROM offers o LEFT JOIN users u ON o.creator_id = u.id LEFT JOIN services s ON o.service_id = s.id ORDER BY o.created_at DESC, o.id DESC';
        }
        $db = config::getConnexion();

        try {
            return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            die('Error:' . $e->getMessage());
        }
    }

    public function listActiveOffers(): array {
        $this->syncOfferStatusesByExpiration();

        if ($this->useFrenchSchema()) {
            $sql = 'SELECT o.id_offre, o.titre, o.description, o.localisation, o.date_publication, o.date_expiration, o.statut, o.type_service, o.prix, o.id_admin, u.nom AS admin_nom, u.prenom AS admin_prenom FROM offre o LEFT JOIN users u ON o.id_admin = u.id_user WHERE o.statut = :statut ORDER BY o.date_publication DESC, o.id_offre DESC';
            $params = ['statut' => 'ouverte'];
        } else {
            $sql = 'SELECT o.id AS id_offre, o.titre, o.description, "" AS localisation, o.created_at AS date_publication, o.date_fin AS date_expiration, CASE WHEN o.statut = "active" THEN "ouverte" ELSE "fermee" END AS statut, COALESCE(s.titre, "autre") AS type_service, o.prix, o.creator_id AS id_admin, u.nom AS admin_nom, u.prenom AS admin_prenom FROM offers o LEFT JOIN users u ON o.creator_id = u.id LEFT JOIN services s ON o.service_id = s.id WHERE o.statut = :statut ORDER BY o.created_at DESC, o.id DESC';
            $params = ['statut' => 'active'];
        }
        $db = config::getConnexion();
        $req = $db->prepare($sql);

        try {
            $req->execute($params);
            return $req->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            die('Error:' . $e->getMessage());
        }
    }

    public function getStats(): array {
        $this->syncOfferStatusesByExpiration();

        $table = $this->useFrenchSchema() ? 'offre' : 'offers';

        if ($this->useFrenchSchema()) {
            $sql = 'SELECT COUNT(*) AS total, SUM(statut = "ouverte") AS ouverte, SUM(statut = "fermee") AS fermee FROM offre';
        } else {
            $sql = 'SELECT COUNT(*) AS total, SUM(statut = "active") AS ouverte, SUM(statut <> "active") AS fermee FROM offers';
        }
        $db = config::getConnexion();

        try {
            $stats = $db->query($sql)->fetch(PDO::FETCH_ASSOC) ?: [];

            return [
                'total' => (int) ($stats['total'] ?? 0),
                'ouverte' => (int) ($stats['ouverte'] ?? 0),
                'fermee' => (int) ($stats['fermee'] ?? 0),
                'places' => (int) ($stats['places'] ?? 0),
            ];
        } catch (Exception $e) {
            die('Error:' . $e->getMessage());
        }
    }


    public function deleteOffer($id): bool {
        // fetch current offer to possibly notify
        $existing = $this->showOffer((int)$id);

        $sql = $this->useFrenchSchema()
            ? 'DELETE FROM offre WHERE id_offre = :id'
            : 'DELETE FROM offers WHERE id = :id';
        $db = config::getConnexion();
        $req = $db->prepare($sql);
        $req->bindValue(':id', (int) $id, PDO::PARAM_INT);

        try {
            $ok = $req->execute();

            if ($ok && $existing && isset($existing['statut']) && $this->normalizeStatusForRead($existing['statut']) === 'ouverte') {
                if (session_status() === PHP_SESSION_NONE) {
                    @session_start();
                }
                $note = [
                    'id' => uniqid('notif_', true),
                    'offer_id' => (int)$id,
                    'type' => 'suppression',
                    'headline' => is_callable('app_text') ? [
                        'fr' => 'Offre supprimée',
                        'en' => 'Offer deleted',
                        'ar' => 'تم حذف العرض',
                    ] : 'Offre supprimée',
                    'message' => (string) ($existing['titre'] ?? 'Offre'),
                    'details' => [
                        'type_service' => (string) ($existing['type_service'] ?? ''),
                        'localisation' => (string) ($existing['localisation'] ?? ''),
                        'prix' => isset($existing['prix']) ? (string) $existing['prix'] : 'N/A',
                    ],
                    'link' => 'index.php?page=offre',
                    'time' => (new DateTimeImmutable('now'))->format('c'),
                    'read' => false,
                ];
                $this->addOfferNotification($note);
            }

            return $ok;
        } catch (Exception $e) {
            die('Error:' . $e->getMessage());
        }
    }

    public function addOffer(Offer $offer): int {
        $computedStatus = $this->resolveStatusFromExpiration($offer->getDateExpiration());

        // Direct check for duplicate title
        $offerTitle = trim((string) $offer->getTitre());
        $db = config::getConnexion();
        
        if ($this->useFrenchSchema()) {
            $checkStmt = $db->prepare('SELECT COUNT(*) FROM offre WHERE LOWER(titre) = LOWER(?)');
            $checkStmt->execute([$offerTitle]);
            $existingCount = $checkStmt->fetchColumn();
            
            if ($existingCount > 0) {
                error_log("DUPLICATE TITLE BLOCKED: '$offerTitle' already exists");
                return 0;
            }
            
            $sql = 'INSERT INTO offre (titre, description, localisation, date_expiration, statut, type_service, prix, id_admin) VALUES (:titre, :description, :localisation, :date_expiration, :statut, :type_service, :prix, :id_admin)';
            $params = [
                'titre' => $offer->getTitre(),
                'description' => $offer->getDescription(),
                'localisation' => $offer->getLocalisation(),
                'date_expiration' => $offer->getDateExpiration() ? $offer->getDateExpiration()->format('Y-m-d H:i:s') : null,
                'statut' => $computedStatus,
                'type_service' => $offer->getTypeService(),
                'prix' => $offer->getPrix(),
                'id_admin' => $offer->getIdAdmin(),
            ];
        } else {
            $checkStmt = $db->prepare('SELECT COUNT(*) FROM offers WHERE LOWER(titre) = LOWER(?)');
            $checkStmt->execute([$offerTitle]);
            $existingCount = $checkStmt->fetchColumn();
            
            if ($existingCount > 0) {
                error_log("DUPLICATE TITLE BLOCKED: '$offerTitle' already exists");
                return 0;
            }
            
            $sql = 'INSERT INTO offers (titre, description, prix, service_id, creator_id, image, statut, date_debut, date_fin) VALUES (:titre, :description, :prix, :service_id, :creator_id, :image, :statut, :date_debut, :date_fin)';
            $params = [
                'titre' => $offer->getTitre(),
                'description' => $offer->getDescription(),
                'prix' => $offer->getPrix(),
                'service_id' => null,
                'creator_id' => $offer->getIdAdmin() ?: 1,
                'image' => null,
                'statut' => $computedStatus,
                'date_debut' => null,
                'date_fin' => $offer->getDateExpiration() ? $offer->getDateExpiration()->format('Y-m-d H:i:s') : null,
            ];
        }

        try {
            $query = $db->prepare($sql);
            $query->execute($params);

            $newId = (int) $db->lastInsertId();



            return $newId;
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage();
            return 0;
        }
    }

    public function updateOffer($offerOrId, $idOrData): bool {
        if ($offerOrId instanceof Offer) {
            $offer = $offerOrId;
            $id = (int) $idOrData;
        } elseif (is_int($offerOrId) && is_array($idOrData)) {
            $offer = $this->mapPayloadToEntity($idOrData);
            $id = $offerOrId;
        } else {
            return false;
        }

        // Check if offer title already exists (excluding current offer)
        $offerTitle = trim((string) $offer->getTitre());
        $db = config::getConnexion();
        
        if ($this->useFrenchSchema()) {
            $checkStmt = $db->prepare('SELECT COUNT(*) FROM offre WHERE LOWER(titre) = LOWER(?) AND id_offre != ?');
            $checkStmt->execute([$offerTitle, $id]);
            $existingCount = $checkStmt->fetchColumn();
            
            if ($existingCount > 0) {
                error_log("DUPLICATE TITLE BLOCKED ON UPDATE: '$offerTitle' already exists");
                return false;
            }
        } else {
            $checkStmt = $db->prepare('SELECT COUNT(*) FROM offers WHERE LOWER(titre) = LOWER(?) AND id != ?');
            $checkStmt->execute([$offerTitle, $id]);
            $existingCount = $checkStmt->fetchColumn();
            
            if ($existingCount > 0) {
                error_log("DUPLICATE TITLE BLOCKED ON UPDATE: '$offerTitle' already exists");
                return false;
            }
        }

        $computedStatus = $this->resolveStatusFromExpiration($offer->getDateExpiration());

        try {
            if ($this->useFrenchSchema()) {
                // capture previous state to compute changes
                $before = $this->showOffer($id);
                $query = $db->prepare(
                    'UPDATE offre SET titre = :titre, description = :description, localisation = :localisation, date_expiration = :date_expiration, statut = :statut, type_service = :type_service, prix = :prix, id_admin = :id_admin WHERE id_offre = :id'
                );

                $ok = $query->execute([
                    'id' => $id,
                    'titre' => $offer->getTitre(),
                    'description' => $offer->getDescription(),
                    'localisation' => $offer->getLocalisation(),
                    'date_expiration' => $offer->getDateExpiration() ? $offer->getDateExpiration()->format('Y-m-d H:i:s') : null,
                    'statut' => $computedStatus,
                    'type_service' => $offer->getTypeService(),
                    'prix' => $offer->getPrix(),
                    'id_admin' => $offer->getIdAdmin(),
                ]);

                if ($ok) {
                    // determine after state and only notify when offer remains 'ouverte'
                    $after = $this->showOffer($id);
                    $shouldNotify = $after && ($this->normalizeStatusForRead($after['statut'] ?? '') === 'ouverte');

                    if ($shouldNotify) {
                        // compute changed fields
                        $changes = [];
                        $fields = ['titre','description','localisation','date_expiration','statut','type_service','prix'];
                        foreach ($fields as $f) {
                            $beforeVal = isset($before[$f]) ? (string)$before[$f] : '';
                            $afterVal = isset($after[$f]) ? (string)$after[$f] : '';
                            if ($beforeVal !== $afterVal) {
                                $changes[$f] = ['from' => $beforeVal, 'to' => $afterVal];
                            }
                        }

                        if (!empty($changes)) {
                            // skip notifications that are only an automatic expiration (statut changed to 'fermee')
                            $onlyStatusChangeToClosed = false;
                            if (count($changes) === 1 && isset($changes['statut'])) {
                                $toVal = (string) ($changes['statut']['to'] ?? '');
                                if ($this->normalizeStatusForRead($toVal) === 'fermee') {
                                    $onlyStatusChangeToClosed = true;
                                }
                            }
                            if ($onlyStatusChangeToClosed) {
                                // do not push a notification for automatic expiration
                                // but still return success
                                return $ok;
                            }
                            if (session_status() === PHP_SESSION_NONE) {
                                @session_start();
                            }
                            $note = [
                                'id' => uniqid('notif_', true),
                                'offer_id' => $id,
                                'type' => 'modification',
                                'headline' => is_callable('app_text') ? [
                                    'fr' => 'Offre modifiée',
                                    'en' => 'Offer updated',
                                    'ar' => 'تم تعديل العرض',
                                ] : 'Offre modifiée',
                                'message' => (string) $offer->getTitre(),
                                'details' => [
                                    'type_service' => (string) $offer->getTypeService(),
                                    'localisation' => (string) $offer->getLocalisation(),
                                    'prix' => $offer->getPrix() !== null ? number_format((float)$offer->getPrix(), 2, '.', ' ') . ' TND' : 'N/A',
                                ],
                                'changes' => $changes,
                                'link' => 'index.php?page=offre&offer_id=' . $id,
                                'time' => (new DateTimeImmutable('now'))->format('c'),
                                'read' => false,
                            ];
                            $this->addOfferNotification($note);
                        }
                    }
                }

                return $ok;
            }

            $query = $db->prepare(
                'UPDATE offers SET titre = :titre, description = :description, prix = :prix, date_fin = :date_fin, statut = :statut, creator_id = :creator_id WHERE id = :id'
            );

            $ok = $query->execute([
                'id' => $id,
                'titre' => $offer->getTitre(),
                'description' => $offer->getDescription(),
                'prix' => $offer->getPrix(),
                'date_fin' => $offer->getDateExpiration() ? $offer->getDateExpiration()->format('Y-m-d H:i:s') : null,
                'statut' => $computedStatus,
                'creator_id' => $offer->getIdAdmin() ?: 1,
            ]);

            if ($ok) {
                // For non-French schema, also compute before/after and only notify when open
                $before = $this->showOffer($id);
                $after = $this->showOffer($id);
                $shouldNotify = $after && ($this->normalizeStatusForRead($after['statut'] ?? '') === 'ouverte');
                if ($shouldNotify) {
                    $changes = [];
                    $fields = ['titre','description','localisation','date_expiration','statut','type_service','prix'];
                    foreach ($fields as $f) {
                        $beforeVal = isset($before[$f]) ? (string)$before[$f] : '';
                        $afterVal = isset($after[$f]) ? (string)$after[$f] : '';
                        if ($beforeVal !== $afterVal) {
                            $changes[$f] = ['from' => $beforeVal, 'to' => $afterVal];
                        }
                    }
                    if (!empty($changes)) {
                        // skip notifications that are only an automatic expiration (statut changed to 'fermee')
                        $onlyStatusChangeToClosed = false;
                        if (count($changes) === 1 && isset($changes['statut'])) {
                            $toVal = (string) ($changes['statut']['to'] ?? '');
                            if ($this->normalizeStatusForRead($toVal) === 'fermee') {
                                $onlyStatusChangeToClosed = true;
                            }
                        }
                        if ($onlyStatusChangeToClosed) {
                            return $ok;
                        }
                        if (session_status() === PHP_SESSION_NONE) {
                            @session_start();
                        }
                        $note = [
                            'id' => uniqid('notif_', true),
                            'offer_id' => $id,
                            'type' => 'modification',
                            'headline' => is_callable('app_text') ? [
                                'fr' => 'Offre modifiée',
                                'en' => 'Offer updated',
                                'ar' => 'تم تعديل العرض',
                            ] : 'Offre modifiée',
                            'message' => (string) $offer->getTitre(),
                            'details' => [
                                'type_service' => (string) $offer->getTypeService(),
                                'localisation' => (string) $offer->getLocalisation(),
                                'prix' => $offer->getPrix() !== null ? number_format((float)$offer->getPrix(), 2, '.', ' ') . ' TND' : 'N/A',
                            ],
                            'changes' => $changes,
                            'link' => 'index.php?page=offre&offer_id=' . $id,
                            'time' => (new DateTimeImmutable('now'))->format('c'),
                            'read' => false,
                        ];
                        $this->addOfferNotification($note);
                    }
                }
            }

            return $ok;
        } catch (PDOException $e) {
            echo 'Error: ' . $e->getMessage();
            return false;
        }
    }

    public function showOffer($id): ?array {
        $this->syncOfferStatusesByExpiration();

        if ($this->useFrenchSchema()) {
            $sql = 'SELECT o.id_offre, o.titre, o.description, o.localisation, o.date_publication, o.date_expiration, o.statut, o.type_service, o.prix, o.id_admin, u.nom AS admin_nom, u.prenom AS admin_prenom FROM offre o LEFT JOIN users u ON o.id_admin = u.id_user WHERE o.id_offre = :id LIMIT 1';
        } else {
            $sql = 'SELECT o.id AS id_offre, o.titre, o.description, "" AS localisation, o.created_at AS date_publication, o.date_fin AS date_expiration, CASE WHEN o.statut = "active" THEN "ouverte" ELSE "fermee" END AS statut, COALESCE(s.titre, "autre") AS type_service, o.prix, o.creator_id AS id_admin, u.nom AS admin_nom, u.prenom AS admin_prenom FROM offers o LEFT JOIN users u ON o.creator_id = u.id LEFT JOIN services s ON o.service_id = s.id WHERE o.id = :id LIMIT 1';
        }
        $db = config::getConnexion();
        $query = $db->prepare($sql);

        try {
            $query->execute(['id' => (int) $id]);
            $offer = $query->fetch(PDO::FETCH_ASSOC);
            return $offer ?: null;
        } catch (Exception $e) {
            die('Error: ' . $e->getMessage());
        }
    }

    public function getOffer(int $id): ?array {
        if ($id <= 0) {
            return null;
        }

        return $this->showOffer($id);
    }

    public function createOffer(array $data): int {
        return $this->addOffer($this->mapPayloadToEntity($data));
    }
}
}

