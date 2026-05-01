<?php
include_once(__DIR__ . '/../config.php');
include_once(__DIR__ . '/../model/Candidature.php');

if (!class_exists('CandidatureController')) {
class CandidatureController {
    public function __construct($unused = null) {
    }

    private function tableExists(string $table): bool {
        $db = config::getConnexion();
        $stmt = $db->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table_name');
        $stmt->execute(['table_name' => $table]);
        return (int) $stmt->fetchColumn() > 0;
    }

    private function hasColumn(string $table, string $column): bool {
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

    private function resolveApplicationSchema(): ?string {
        if ($this->tableExists('candidature')) {
            return 'candidature';
        }

        if ($this->tableExists('applications')) {
            return 'applications';
        }

        return null;
    }

    private function resolveOfferTable(): ?string {
        if ($this->tableExists('offre')) {
            return 'offre';
        }

        if ($this->tableExists('offers')) {
            return 'offers';
        }

        return null;
    }

    private function resolveUserPrimaryKey(): ?string {
        if ($this->hasColumn('users', 'id_user')) {
            return 'id_user';
        }

        if ($this->hasColumn('users', 'id')) {
            return 'id';
        }

        return null;
    }

    private function canUseApplicationSchema(): bool {
        return $this->resolveApplicationSchema() !== null;
    }

    private function normalizeStatusForRead(string $status): string {
        $value = strtolower(trim($status));

        if ($value === 'en_attente') {
            return 'en attente';
        }

        if ($value === 'rejetee') {
            return 'refusee';
        }

        if (in_array($value, ['en attente', 'acceptee', 'refusee'], true)) {
            return $value;
        }

        return 'en attente';
    }

    private function normalizeStatus(string $status): string {
        $value = strtolower(trim($status));

        if ($value === 'en_attente') {
            return 'en attente';
        }

        if ($value === 'rejetee') {
            return 'refusee';
        }

        return in_array($value, ['en attente', 'acceptee', 'refusee'], true) ? $value : 'en attente';
    }

    private function normalizeStatusForWrite(string $status): string {
        $normalized = $this->normalizeStatus($status);
        $schema = $this->resolveApplicationSchema();

        if ($schema === 'applications') {
            return match ($normalized) {
                'en attente' => 'en_attente',
                'acceptee' => 'acceptee',
                'refusee' => 'rejetee',
                default => 'en_attente',
            };
        }

        return $normalized;
    }

    private function mapApplicationRow(array $row, string $schema): array {
        if ($schema === 'applications') {
            return [
                'id' => (int) ($row['id'] ?? 0),
                'id_candidature' => (int) ($row['id'] ?? 0),
                'id_user' => isset($row['id_user']) ? (int) $row['id_user'] : null,
                'id_offre' => isset($row['id_offre']) ? (int) $row['id_offre'] : null,
                'date_candidature' => $row['date_candidature'] ?? null,
                'created_at' => $row['created_at'] ?? null,
                'statut' => $this->normalizeStatusForRead((string) ($row['statut'] ?? 'en_attente')),
                'experience' => $row['experience'] ?? null,
                'competences' => $row['competences'] ?? null,
                'cv' => $row['cv'] ?? null,
                'message' => $row['message'] ?? null,
                'nom' => $row['nom'] ?? null,
                'prenom' => $row['prenom'] ?? null,
                'email' => $row['email'] ?? null,
                'offer_titre' => $row['offer_titre'] ?? null,
            ];
        }

        return [
            'id' => (int) ($row['id'] ?? 0),
            'id_candidature' => (int) ($row['id_candidature'] ?? 0),
            'id_user' => isset($row['id_user']) ? (int) $row['id_user'] : null,
            'id_offre' => isset($row['id_offre']) ? (int) $row['id_offre'] : null,
            'date_candidature' => $row['date_candidature'] ?? null,
            'created_at' => $row['created_at'] ?? null,
            'statut' => $this->normalizeStatusForRead((string) ($row['statut'] ?? 'en attente')),
            'experience' => $row['experience'] ?? null,
            'competences' => $row['competences'] ?? null,
            'cv' => $row['cv'] ?? null,
            'message' => $row['message'] ?? null,
            'nom' => $row['nom'] ?? null,
            'prenom' => $row['prenom'] ?? null,
            'email' => $row['email'] ?? null,
            'offer_titre' => $row['offer_titre'] ?? null,
        ];
    }
//// joointure
    private function buildJoinedSelectSql(?string $whereClause = null): ?string {
        $schema = $this->resolveApplicationSchema();
        if ($schema === null) {
            return null;
        }

        $offerTable = $this->resolveOfferTable();
        $userPk = $this->resolveUserPrimaryKey();

        if ($schema === 'candidature') {
            $offerJoin = '';
            if ($offerTable === 'offre') {
                $offerJoin = ' INNER JOIN offre o ON c.id_offre = o.id_offre';
            } elseif ($offerTable === 'offers') {
                $offerJoin = ' INNER JOIN offers o ON c.id_offre = o.id';
            }

            $userJoin = '';
            if ($userPk !== null) {
                $userJoin = ' LEFT JOIN users u ON c.id_user = u.' . $userPk;
            }

            $sql = 'SELECT c.id_candidature AS id, c.id_candidature, c.id_user, c.id_offre, c.date_candidature, c.date_candidature AS created_at, c.statut, c.experience, c.competences, c.cv, c.message, u.nom, u.prenom, u.email, o.titre AS offer_titre FROM candidature c' . $offerJoin . $userJoin;
        } else {
            $offerJoin = '';
            if ($offerTable === 'offers') {
                $offerJoin = ' INNER JOIN offers o ON a.offer_id = o.id';
            } elseif ($offerTable === 'offre') {
                $offerJoin = ' INNER JOIN offre o ON a.offer_id = o.id_offre';
            }

            $userJoin = '';
            if ($userPk !== null) {
                $userJoin = ' LEFT JOIN users u ON a.user_id = u.' . $userPk;
            }

            $sql = 'SELECT a.id, a.id AS id_candidature, a.user_id AS id_user, a.offer_id AS id_offre, a.created_at AS date_candidature, a.created_at, a.statut, a.experience, a.competences, a.cv_path AS cv, a.message, u.nom, u.prenom, u.email, o.titre AS offer_titre FROM applications a' . $offerJoin . $userJoin;
        }

        if (!empty($whereClause)) {
            $sql .= ' WHERE ' . $whereClause;
        }

        if ($schema === 'candidature') {
            $sql .= ' ORDER BY c.date_candidature DESC, c.id_candidature DESC';
        } else {
            $sql .= ' ORDER BY a.created_at DESC, a.id DESC';
        }

        return $sql;
    }

    private function pushAdminNotificationToFile(array $note): void {
        $dir = __DIR__ . '/../storage';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        $file = $dir . '/admin_notifications.json';

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
        // keep a reasonable cap
        if (count($list) > 200) {
            $list = array_slice($list, 0, 200);
        }

        @file_put_contents($file, json_encode($list, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    private function mapPayloadToEntity(array $data): Candidature {
        $dateCandidature = null;
        if (!empty($data['date_candidature'])) {
            $dateCandidature = new DateTime((string) $data['date_candidature']);
        }

        return new Candidature(
            isset($data['id_candidature']) && is_numeric($data['id_candidature']) ? (int) $data['id_candidature'] : null,
            $dateCandidature,
            isset($data['statut']) ? $this->normalizeStatus((string) $data['statut']) : 'en attente',
            isset($data['experience']) ? trim((string) $data['experience']) : null,
            isset($data['competences']) ? trim((string) $data['competences']) : null,
            isset($data['cv']) ? trim((string) $data['cv']) : null,
            isset($data['message']) ? trim((string) $data['message']) : null,
            isset($data['id_user']) && is_numeric($data['id_user']) ? (int) $data['id_user'] : null,
            isset($data['id_offre']) && is_numeric($data['id_offre']) ? (int) $data['id_offre'] : null
        );
    }

    public function listApplications(): array {
        $schema = $this->resolveApplicationSchema();
        if ($schema === null) {
            return [];
        }

        $sql = $this->buildJoinedSelectSql();
        if ($sql === null) {
            return [];
        }

        $db = config::getConnexion();

        try {
            $rows = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
            return array_map(fn($row) => $this->mapApplicationRow($row, $schema), $rows);
        } catch (Exception $e) {
            return [];
        }
    }

    public function deleteApplication($id): bool {
        // capture existing application for notification
        $existing = $this->showApplication((int)$id);
        $schema = $this->resolveApplicationSchema();
        if ($schema === null) {
            return false;
        }

        $sql = $schema === 'candidature'
            ? 'DELETE FROM candidature WHERE id_candidature = :id'
            : 'DELETE FROM applications WHERE id = :id';
        $db = config::getConnexion();
        $req = $db->prepare($sql);
        $req->bindValue(':id', (int) $id, PDO::PARAM_INT);

        try {
            $ok = $req->execute();

            if ($ok && $existing) {
                if (session_status() === PHP_SESSION_NONE) {
                    @session_start();
                }
                $applicantName = trim((string) (($existing['nom'] ?? '') . ' ' . ($existing['prenom'] ?? '')));
                $offerId = (int) ($existing['id_offre'] ?? 0);
                $offerTitle = (string) ($existing['offer_titre'] ?? '');
                $message = $applicantName !== ''
                    ? sprintf('%s a supprimé sa candidature pour l\'offre «%s» (ID %d).', $applicantName, $offerTitle ?: '—', $offerId)
                    : sprintf('Une candidature a été supprimée pour l\'offre «%s» (ID %d).', $offerTitle ?: '—', $offerId);

                $note = [
                    'id' => uniqid('admin_notif_', true),
                    'application_id' => (int)$id,
                    'type' => 'candidature_supprime',
                    'headline' => 'Candidature supprimée',
                    'message' => $message,
                    'details' => [
                        'offer_id' => $offerId,
                        'offer_titre' => $offerTitle,
                    ],
                    'link' => 'index.php?page=offer_applications&offer_id=' . $offerId,
                    'time' => (new DateTimeImmutable('now'))->format('c'),
                    'read' => false,
                ];
                if (!isset($_SESSION['admin_notifications']) || !is_array($_SESSION['admin_notifications'])) {
                    $_SESSION['admin_notifications'] = [];
                }
                array_unshift($_SESSION['admin_notifications'], $note);
                // persist to shared file so admins see it across sessions
                try {
                    $this->pushAdminNotificationToFile($note);
                } catch (Exception $e) {
                    // ignore persistence errors
                }
                // debug logging (record deletion)
                try {
                    $dbg = __DIR__ . '/../storage/admin_notifications_debug.log';
                    $line = sprintf("%s DELETE app_id=%s offer=%s title=%s\n", (new DateTimeImmutable('now'))->format('c'), var_export($id, true), var_export($offerId, true), str_replace("\n", ' ', substr((string)$offerTitle,0,120)));
                    @file_put_contents($dbg, $line, FILE_APPEND | LOCK_EX);
                } catch (Exception $e) { /* ignore */ }
            }

            // also persist admin notifications to a shared file so admins (different sessions) can see them
            try {
                $this->pushAdminNotificationToFile($note);
            } catch (Exception $e) {
                // ignore persistence errors
            }

            return $ok;
        } catch (Exception $e) {
            return false;
        }
    }

    public function addApplication(Candidature $candidature): int {
        $schema = $this->resolveApplicationSchema();
        if ($schema === null) {
            return 0;
        }

        if ($schema === 'candidature') {
            $sql = 'INSERT INTO candidature (id_user, id_offre, experience, competences, cv, message, statut) VALUES (:id_user, :id_offre, :experience, :competences, :cv, :message, :statut)';
            $params = [
                'id_user' => $candidature->getIdUser(),
                'id_offre' => $candidature->getIdOffre(),
                'experience' => $candidature->getExperience(),
                'competences' => $candidature->getCompetences(),
                'cv' => $candidature->getCv(),
                'message' => $candidature->getMessage(),
                'statut' => $this->normalizeStatusForWrite((string) $candidature->getStatut()),
            ];
        } else {
            $sql = 'INSERT INTO applications (offer_id, user_id, experience, competences, cv_path, message, statut) VALUES (:id_offre, :id_user, :experience, :competences, :cv, :message, :statut)';
            $params = [
                'id_user' => $candidature->getIdUser(),
                'id_offre' => $candidature->getIdOffre(),
                'experience' => $candidature->getExperience(),
                'competences' => $candidature->getCompetences(),
                'cv' => $candidature->getCv(),
                'message' => $candidature->getMessage(),
                'statut' => $this->normalizeStatusForWrite((string) $candidature->getStatut()),
            ];
        }

        $db = config::getConnexion();

        try {
            $query = $db->prepare($sql);
            $query->execute($params);

            $newId = (int) $db->lastInsertId();

            // notify admin back-office that a new candidature was submitted
            if ($newId > 0) {
                if (session_status() === PHP_SESSION_NONE) {
                    @session_start();
                }
                // try to fetch some minimal info about the offer title and applicant name if possible
                $offerTitle = null;
                $applicantName = null;
                try {
                    $offerTable = $this->resolveOfferTable();
                    if ($offerTable !== null) {
                        $db2 = config::getConnexion();
                        if ($offerTable === 'offre') {
                            $s = $db2->prepare('SELECT titre FROM offre WHERE id_offre = :id LIMIT 1');
                            $s->execute(['id' => $params['id_offre']]);
                            $row = $s->fetch(PDO::FETCH_ASSOC);
                            $offerTitle = $row['titre'] ?? null;
                        } else {
                            $s = $db2->prepare('SELECT titre FROM offers WHERE id = :id LIMIT 1');
                            $s->execute(['id' => $params['id_offre']]);
                            $row = $s->fetch(PDO::FETCH_ASSOC);
                            $offerTitle = $row['titre'] ?? null;
                        }
                    }
                } catch (Exception $e) {
                    // ignore
                }
                // attempt to resolve applicant name from user id
                try {
                    $userPk = $this->resolveUserPrimaryKey();
                    if ($userPk && !empty($params['id_user'])) {
                        $s2 = $db->prepare('SELECT nom, prenom, email FROM users WHERE ' . $userPk . ' = :id LIMIT 1');
                        $s2->execute(['id' => $params['id_user']]);
                        $urow = $s2->fetch(PDO::FETCH_ASSOC);
                        if ($urow) {
                            $applicantName = trim((string) (($urow['nom'] ?? '') . ' ' . ($urow['prenom'] ?? '')));
                        }
                    }
                } catch (Exception $e) {
                    // ignore
                }

                $offerId = (int) ($params['id_offre'] ?? 0);
                $title = (string) ($offerTitle ?? '');
                $applicantLabel = $applicantName ?: (string) ($params['email'] ?? '');
                $shortMsg = $params['message'] ?? null;

                if ($applicantLabel) {
                    $message = sprintf('%s a postulé à l\'offre «%s» (ID %d).', $applicantLabel, $title ?: '—', $offerId);
                } else {
                    $message = sprintf('Nouvelle candidature reçue pour l\'offre «%s» (ID %d).', $title ?: '—', $offerId);
                }
                if (!empty($shortMsg)) {
                    $snippet = mb_substr(trim((string)$shortMsg), 0, 120);
                    $message .= ' Lettre de motivation: "' . htmlspecialchars($snippet, ENT_QUOTES, 'UTF-8') . '"';
                }

                $note = [
                    'id' => uniqid('admin_notif_', true),
                    'application_id' => $newId,
                    'type' => 'candidature_ajout',
                    'headline' => 'Nouvelle candidature',
                    'message' => $message,
                    'details' => [
                        'offer_id' => $offerId,
                        'offer_titre' => $title,
                    ],
                    'link' => 'index.php?page=offer_applications&offer_id=' . $offerId,
                    'time' => (new DateTimeImmutable('now'))->format('c'),
                    'read' => false,
                ];
                if (!isset($_SESSION['admin_notifications']) || !is_array($_SESSION['admin_notifications'])) {
                    $_SESSION['admin_notifications'] = [];
                }
                array_unshift($_SESSION['admin_notifications'], $note);
            }

            return $newId;
        } catch (Exception $e) {
            echo 'Error: ' . $e->getMessage();
            return 0;
        }
    }

    public function updateApplication($candidatureOrId, $idOrData): bool {
        $schema = $this->resolveApplicationSchema();
        if ($schema === null) {
            return false;
        }

        if ($candidatureOrId instanceof Candidature) {
            $candidature = $candidatureOrId;
            $id = (int) $idOrData;
        } elseif (is_int($candidatureOrId) && is_array($idOrData)) {
            $candidature = $this->mapPayloadToEntity($idOrData);
            $id = $candidatureOrId;
        } else {
            return false;
        }

        try {
            $db = config::getConnexion();
            if ($schema === 'candidature') {
                $query = $db->prepare(
                    'UPDATE candidature SET id_user = :id_user, id_offre = :id_offre, experience = :experience, competences = :competences, cv = :cv, message = :message, statut = :statut WHERE id_candidature = :id'
                );
            } else {
                $query = $db->prepare(
                    'UPDATE applications SET user_id = :id_user, offer_id = :id_offre, experience = :experience, competences = :competences, cv_path = :cv, message = :message, statut = :statut WHERE id = :id'
                );
            }

            return $query->execute([
                'id' => $id,
                'id_user' => $candidature->getIdUser(),
                'id_offre' => $candidature->getIdOffre(),
                'experience' => $candidature->getExperience(),
                'competences' => $candidature->getCompetences(),
                'cv' => $candidature->getCv(),
                'message' => $candidature->getMessage(),
                'statut' => $this->normalizeStatusForWrite((string) $candidature->getStatut()),
            ]);
        } catch (PDOException $e) {
            echo 'Error: ' . $e->getMessage();
            return false;
        }
    }

    public function showApplication($id): ?array {
        $schema = $this->resolveApplicationSchema();
        if ($schema === null) {
            return null;
        }

        $sql = $this->buildJoinedSelectSql(($schema === 'candidature' ? 'c.id_candidature' : 'a.id') . ' = :id');
        if ($sql === null) {
            return null;
        }

        $db = config::getConnexion();
        $query = $db->prepare($sql);

        try {
            $query->execute(['id' => (int) $id]);
            $row = $query->fetch(PDO::FETCH_ASSOC);
            return $row ? $this->mapApplicationRow($row, $schema) : null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function submitApplication(array $data): int {
        $payload = [
            'id_user' => isset($data['id_user']) && is_numeric($data['id_user']) ? (int) $data['id_user'] : null,
            'id_offre' => isset($data['id_offre']) && is_numeric($data['id_offre']) ? (int) $data['id_offre'] : null,
            'experience' => isset($data['experience']) ? trim((string) $data['experience']) : null,
            'competences' => isset($data['competences']) ? trim((string) $data['competences']) : null,
            'cv' => isset($data['cv']) ? trim((string) $data['cv']) : null,
            'message' => isset($data['message']) ? trim((string) $data['message']) : null,
            'statut' => isset($data['statut']) ? $this->normalizeStatus((string) $data['statut']) : 'en attente',
        ];

        if (empty($payload['id_offre']) || empty($payload['id_user'])) {
            throw new InvalidArgumentException('Les champs id_offre et id_user sont obligatoires.');
        }

        return $this->addApplication($this->mapPayloadToEntity($payload));
    }

    public function getApplicationsByOffer(int $offerId): array {
        $schema = $this->resolveApplicationSchema();
        if ($schema === null) {
            return [];
        }

        if ($offerId <= 0) {
            return [];
        }

        $where = ($schema === 'candidature' ? 'c.id_offre' : 'a.offer_id') . ' = :id_offre';
        $sql = $this->buildJoinedSelectSql($where);
        if ($sql === null) {
            return [];
        }

        $db = config::getConnexion();
        $query = $db->prepare($sql);

        try {
            $query->execute(['id_offre' => $offerId]);
            $rows = $query->fetchAll(PDO::FETCH_ASSOC) ?: [];
            return array_map(fn($row) => $this->mapApplicationRow($row, $schema), $rows);
        } catch (Exception $e) {
            return [];
        }
    }

    public function getAllApplications(): array {
        return $this->listApplications();
    }

    public function getApplication(int $id): ?array {
        if ($id <= 0) {
            return null;
        }

        return $this->showApplication($id);
    }

    public function getApplicationStats(): array {
        $schema = $this->resolveApplicationSchema();
        if ($schema === null) {
            return [
                'total' => 0,
                'pending' => 0,
                'accepted' => 0,
                'rejected' => 0,
            ];
        }

        if ($schema === 'candidature') {
            $sql = 'SELECT COUNT(*) AS total, SUM(statut = "en attente") AS pending, SUM(statut = "acceptee") AS accepted, SUM(statut = "refusee") AS rejected FROM candidature';
        } else {
            $sql = 'SELECT COUNT(*) AS total, SUM(statut = "en_attente") AS pending, SUM(statut = "acceptee") AS accepted, SUM(statut = "rejetee") AS rejected FROM applications';
        }

        $db = config::getConnexion();

        try {
            $stats = $db->query($sql)->fetch(PDO::FETCH_ASSOC) ?: [];
            return [
                'total' => (int) ($stats['total'] ?? 0),
                'pending' => (int) ($stats['pending'] ?? 0),
                'accepted' => (int) ($stats['accepted'] ?? 0),
                'rejected' => (int) ($stats['rejected'] ?? 0),
            ];
        } catch (Exception $e) {
            return [
                'total' => 0,
                'pending' => 0,
                'accepted' => 0,
                'rejected' => 0,
            ];
        }
    }

    public function updateApplicationStatus(int $id, string $status): bool {
        $schema = $this->resolveApplicationSchema();
        if ($schema === null) {
            return false;
        }

        if ($id <= 0) {
            return false;
        }

        $sql = $schema === 'candidature'
            ? 'UPDATE candidature SET statut = :statut WHERE id_candidature = :id'
            : 'UPDATE applications SET statut = :statut WHERE id = :id';
        $db = config::getConnexion();
        $query = $db->prepare($sql);

        try {
            return $query->execute([
                'id' => $id,
                'statut' => $this->normalizeStatusForWrite($status),
            ]);
        } catch (Exception $e) {
            return false;
        }
    }

    public function uploadCV(array $file): string {
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            throw new Exception('Aucun fichier CV téléchargé');
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new Exception('Fichier CV invalide.');
        }

        $uploadDir = __DIR__ . '/../assets/uploads/cv/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $allowedExtensions = ['pdf', 'doc', 'docx'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($fileExtension, $allowedExtensions, true)) {
            throw new Exception('Format de fichier non autorisé. PDF, DOC, DOCX seulement.');
        }

        if ((int) $file['size'] > 5 * 1024 * 1024) {
            throw new Exception('Fichier trop volumineux (max 5MB)');
        }

        $fileName = 'cv_' . time() . '_' . rand(1000, 9999) . '.' . $fileExtension;
        $filePath = $uploadDir . $fileName;

        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new Exception('Erreur lors du téléchargement du fichier');
        }

        return 'assets/uploads/cv/' . $fileName;
    }
}
}
