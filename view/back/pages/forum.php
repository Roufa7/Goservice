<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Africa/Tunis');
if (ob_get_level() === 0) {
    ob_start(function (string $html): string {
        $map = [
            'Formats image autorisés' => 'Formats image autorisés',
            "L'image ne doit pas dÃƒÂ©passer 5 Mo." => "L'image ne doit pas dépasser 5 Mo.",
            'Formats vidÃƒÂ©o autorisÃƒÂ©s' => 'Formats vidéo autorisés',
            "La vidÃƒÂ©o ne doit pas dÃƒÂ©passer 25 Mo." => "La vidéo ne doit pas dépasser 25 Mo.",
            "Erreur lors de l'upload de la vidÃƒÂ©o." => "Erreur lors de l'upload de la vidéo.",
            'publication partagÃƒÂ©e' => 'publication partagée',
            'Rejeté' => 'Rejeté',
            'Dompdf retirÃƒÂ©' => 'Dompdf retiré',
            'fenÃƒÂªtre' => 'fenêtre',
            'Post ajoute avec succes.' => 'Post ajouté avec succès.',
            'Post mis a jour avec succes.' => 'Post mis à jour avec succès.',
            'Post supprime avec succes.' => 'Post supprimé avec succès.',
            'Post approuvé avec succès.' => 'Post approuvé avec succès.',
            'Post rejeté avec succès.' => 'Post rejeté avec succès.',
            'Statut du commentaire modifie avec succes.' => 'Statut du commentaire modifié avec succès.',
            'Commentaire supprime avec succes.' => 'Commentaire supprimé avec succès.',
            'Commentaire modifie avec succes.' => 'Commentaire modifié avec succès.',
            'Popularité' => 'Popularité',
            'Schémas dynamiques du forum' => 'Schémas dynamiques du forum',
            'Aucune donnée' => 'Aucune donnée',
            'Activité' => 'Activité',
            'Mise Ã  jour automatique aprÃ¨s chaque action.' => 'Mise à jour automatique après chaque action.',
            'Modération des publications' => 'Modération des publications',
            'Signalé' => 'Signalé',
            'Aucun post trouvé.' => 'Aucun post trouvé.',
            'Réponse' => 'Réponse',
            'Contenu signalé' => 'Contenu signalé',
            'Commentaire signalé' => 'Commentaire signalé',
            '&times;' => '×',
            'Ã°Å¸â€œÅ ' => 'ðŸ“Š',
            'Ã°Å¸â€œÂ¤' => 'ðŸ“¤',
            'Ã°Å¸â€œÂ' => 'ðŸ“',
            'Ã°Å¸â€™Â¬' => 'ðŸ’¬',
            'Ã°Å¸Å¡Â©' => 'ðŸš©',
            'Ã¢Å“â€¦' => '…',
            'Ã°Å¸â„¢Ë†' => 'ðŸ™ˆ',
        ];
        return str_replace(array_keys($map), array_values($map), $html);
    });
}

require_once __DIR__ . '/../../../controller/PostController.php';
require_once __DIR__ . '/../../../controller/CommentController.php';
require_once __DIR__ . '/../../../model/Post.php';

$postController = new PostController();
$commentController = new CommentController();

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function invalidClass($error)
{
    return !empty($error) ? 'field-invalid' : '';
}

function getLettersCount($text): int
{
    $cleaned = preg_replace('/[^a-zA-ZÃƒâ‚¬-ÃƒÂ¿]/u', '', $text);
    return mb_strlen($cleaned);
}

function forumBackAppRoot(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $root = dirname($script, 3);
    return ($root === '/' || $root === '\\') ? '' : rtrim($root, '/');
}

function forumBackUrl(array $extra = []): string
{
    $params = array_merge(['page' => 'forum'], array_filter($extra, static fn($value) => $value !== null && $value !== ''));
    return forumBackAppRoot() . '/view/back/index.php?' . http_build_query($params);
}

function forumMediaUrl($path): string
{
    $path = trim((string) $path);
    if ($path === '') return '';
    if (preg_match('~^https?://~i', $path)) return $path;
    return forumBackAppRoot() . '/' . ltrim($path, '/');
}

function uploadImageFile(array $file, array &$errors, ?string $oldPath = null): ?string
{
    if (empty($file['name'])) return $oldPath;

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions, true)) {
        $errors['image'] = 'Formats image autorisés : JPG, JPEG, PNG, WEBP, GIF.';
        return $oldPath;
    }

    if (($file['size'] ?? 0) > 5 * 1024 * 1024) {
        $errors['image'] = "L'image ne doit pas dépasser 5 Mo.";
        return $oldPath;
    }

    $uploadDir = __DIR__ . '/../../../uploads/posts/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $newName = uniqid('post_img_', true) . '.' . $extension;
    $destination = $uploadDir . $newName;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        if (!empty($oldPath)) {
            $oldFile = __DIR__ . '/../../../' . ltrim($oldPath, '/');
            if (file_exists($oldFile)) {
                @unlink($oldFile);
            }
        }
        return 'uploads/posts/' . $newName;
    }

    $errors['image'] = "Erreur lors de l'upload de l'image.";
    return $oldPath;
}

function uploadVideoFile(array $file, array &$errors, ?string $oldPath = null): ?string
{
    if (empty($file['name'])) return $oldPath;

    $allowedExtensions = ['mp4', 'webm', 'ogg'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions, true)) {
        $errors['video'] = 'Formats vidéo autorisés : MP4, WEBM, OGG.';
        return $oldPath;
    }

    if (($file['size'] ?? 0) > 25 * 1024 * 1024) {
        $errors['video'] = "La vidéo ne doit pas dépasser 25 Mo.";
        return $oldPath;
    }

    $uploadDir = __DIR__ . '/../../../uploads/posts/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $newName = uniqid('post_vid_', true) . '.' . $extension;
    $destination = $uploadDir . $newName;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        if (!empty($oldPath)) {
            $oldFile = __DIR__ . '/../../../' . ltrim($oldPath, '/');
            if (file_exists($oldFile)) {
                @unlink($oldFile);
            }
        }
        return 'uploads/posts/' . $newName;
    }

    $errors['video'] = "Erreur lors de l'upload de la vidéo.";
    return $oldPath;
}

function uploadCommentImageFileBack(array $file, array &$errors, ?string $oldPath = null): ?string
{
    if (empty($file['name'])) return $oldPath;

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions, true)) {
        $errors['comment_image'] = 'Formats image commentaire autorisés : JPG, JPEG, PNG, WEBP, GIF.';
        return $oldPath;
    }

    if (($file['size'] ?? 0) > 3 * 1024 * 1024) {
        $errors['comment_image'] = "L'image du commentaire ne doit pas dépasser 3 Mo.";
        return $oldPath;
    }

    $uploadDir = __DIR__ . '/../../../uploads/comments/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $newName = uniqid('comment_img_', true) . '.' . $extension;
    $destination = $uploadDir . $newName;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        if (!empty($oldPath)) {
            $oldFile = __DIR__ . '/../../../' . ltrim($oldPath, '/');
            if (file_exists($oldFile)) {
                @unlink($oldFile);
            }
        }
        return 'uploads/comments/' . $newName;
    }

    $errors['comment_image'] = "Erreur lors de l'upload de l'image du commentaire.";
    return $oldPath;
}

function forumGetDb(): ?PDO
{
    if (class_exists('config') && method_exists('config', 'getConnexion')) {
        try { return config::getConnexion(); } catch (Throwable $e) { return null; }
    }
    return null;
}

function forumColumnExists(PDO $db, string $table, string $column): bool
{
    try {
        $stmt = $db->prepare("SHOW COLUMNS FROM `$table` LIKE :column_name");
        $stmt->execute(['column_name' => $column]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) { return false; }
}

function forumEnsurePostEmojiColumn(): bool
{
    $db = forumGetDb();
    if (!$db) return false;
    try {
        if (!forumColumnExists($db, 'post', 'emoji_post')) {
            $db->exec("ALTER TABLE post ADD emoji_post VARCHAR(100) NULL DEFAULT NULL");
        }
        return forumColumnExists($db, 'post', 'emoji_post');
    } catch (Throwable $e) { return false; }
}


function forumEnsurePostGifColumn(): bool
{
    $db = forumGetDb();
    if (!$db) return false;
    try {
        if (!forumColumnExists($db, 'post', 'gif_post')) {
            $db->exec("ALTER TABLE post ADD gif_post TEXT NULL");
        }
        return forumColumnExists($db, 'post', 'gif_post');
    } catch (Throwable $e) { return false; }
}

function forumTableExistsBack(PDO $db, string $table): bool
{
    try {
        $stmt = $db->prepare("SHOW TABLES LIKE :table_name");
        $stmt->execute(['table_name' => $table]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) { return false; }
}

function forumGetTableColumnsBack(PDO $db, string $table): array
{
    try {
        $rows = $db->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
        return array_map(fn($r) => $r['Field'] ?? '', $rows ?: []);
    } catch (Throwable $e) { return []; }
}

function forumPickFirstColumnBack(array $row, array $candidates): ?string
{
    foreach ($candidates as $col) {
        if (array_key_exists($col, $row) && $row[$col] !== null && $row[$col] !== '') return $col;
    }
    return null;
}

function forumFindOriginalPostIdForShareBack(array $post): int
{
    $currentPostId = (int)($post['id_post'] ?? 0);

    foreach (['id_post_original','original_post_id','id_original_post','post_original_id','shared_from_post_id','source_post_id','id_source_post','original_id_post'] as $col) {
        if (!empty($post[$col]) && (int)$post[$col] !== $currentPostId) return (int)$post[$col];
    }

    $title = mb_strtolower(trim((string)($post['titre'] ?? '')));
    $looksShared = ($title === 'publication partagée' || str_contains($title, 'partag'));
    if (!$looksShared) return 0;

    $db = forumGetDb();
    if (!$db || !forumTableExistsBack($db, 'share_post')) return 0;

    try {
        $columns = forumGetTableColumnsBack($db, 'share_post');
        if (empty($columns)) return 0;

        $shareIdCol = null;
        foreach (['id_share','id_share_post','id_partage','id'] as $c) {
            if (in_array($c, $columns, true)) { $shareIdCol = $c; break; }
        }

        $possibleOriginalCols = ['id_post_original','original_post_id','id_original_post','post_original_id','shared_from_post_id','source_post_id','id_source_post','id_post'];
        $possibleSharedCols = ['id_post_share','shared_post_id','id_shared_post','new_post_id','id_publication_partagee','id_post_partage'];

        foreach ($possibleSharedCols as $sharedCol) {
            if (!in_array($sharedCol, $columns, true) || $currentPostId <= 0) continue;
            $stmt = $db->prepare("SELECT * FROM share_post WHERE `$sharedCol` = :id LIMIT 1");
            $stmt->execute(['id' => $currentPostId]);
            $share = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$share) continue;
            $originalCol = forumPickFirstColumnBack($share, $possibleOriginalCols);
            if ($originalCol && (int)$share[$originalCol] !== $currentPostId) return (int)$share[$originalCol];
        }

        if (!empty($post['id_share']) && $shareIdCol) {
            $stmt = $db->prepare("SELECT * FROM share_post WHERE `$shareIdCol` = :id LIMIT 1");
            $stmt->execute(['id' => (int)$post['id_share']]);
            $share = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($share) {
                $originalCol = forumPickFirstColumnBack($share, $possibleOriginalCols);
                if ($originalCol && (int)$share[$originalCol] !== $currentPostId) return (int)$share[$originalCol];
            }
        }

        if (in_array('description_share', $columns, true)) {
            $where = ['description_share = :description'];
            $params = ['description' => trim((string)($post['contenu'] ?? ''))];
            if (in_array('id_user', $columns, true) && !empty($post['id_user'])) {
                $where[] = 'id_user = :id_user';
                $params['id_user'] = (int)$post['id_user'];
            }
            $orderCol = in_array('date_share', $columns, true) ? 'date_share' : ($shareIdCol ?: 'id_post');
            $sql = 'SELECT * FROM share_post WHERE '.implode(' AND ', $where).' ORDER BY `'.$orderCol.'` DESC LIMIT 1';
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $share = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($share) {
                $originalCol = forumPickFirstColumnBack($share, $possibleOriginalCols);
                if ($originalCol && (int)$share[$originalCol] !== $currentPostId) return (int)$share[$originalCol];
            }
        }
    } catch (Throwable $e) { return 0; }

    return 0;
}

function forumOriginalPostPayloadBack(array $post): array
{
    $originalId = forumFindOriginalPostIdForShareBack($post);
    if ($originalId <= 0) return [];
    $original = forumGetPostByIdBack($originalId);
    if (!$original) return [];

    $author = trim(($original['prenom'] ?? '') . ' ' . ($original['nom'] ?? ''));
    if ($author === '') $author = $original['auteur'] ?? 'Utilisateur';

    return [
        'id' => $originalId,
        'title' => $original['titre'] ?? 'Post original',
        'content' => $original['contenu'] ?? '',
        'author' => $author,
        'type' => $original['type_post'] ?? 'Discussion',
        'status' => $original['statut_post'] ?? '',
        'emoji' => $original['emoji_post'] ?? '',
        'image' => !empty($original['image']) ? forumMediaUrl($original['image']) : '',
        'video' => !empty($original['video']) ? forumMediaUrl($original['video']) : '',
        'gif' => !empty($original['gif_post']) ? forumMediaUrl($original['gif_post']) : ''
    ];
}

function forumUpdatePostEmoji(int $postId, string $emoji): bool
{
    if ($postId <= 0) return false;
    $db = forumGetDb();
    if (!$db || !forumEnsurePostEmojiColumn()) return false;
    try {
        $stmt = $db->prepare("UPDATE post SET emoji_post = :emoji WHERE id_post = :id");
        return $stmt->execute(['emoji' => $emoji, 'id' => $postId]);
    } catch (Throwable $e) { return false; }
}

function forumFindLastPostId(string $title, string $content, int $userId = 1): int
{
    $db = forumGetDb();
    if (!$db) return 0;
    try {
        $stmt = $db->prepare("SELECT id_post FROM post WHERE titre = :titre AND contenu = :contenu AND id_user = :id_user ORDER BY id_post DESC LIMIT 1");
        $stmt->execute(['titre' => $title, 'contenu' => $content, 'id_user' => $userId]);
        return (int)$stmt->fetchColumn();
    } catch (Throwable $e) { return 0; }
}

function forumEnsureCommentStatusColumn(): bool
{
    $db = forumGetDb();
    if (!$db) return false;
    try {
        if (!forumColumnExists($db, 'commentaire', 'statut_commentaire')) {
            $db->exec("ALTER TABLE commentaire ADD statut_commentaire VARCHAR(30) NOT NULL DEFAULT 'En attente'");
        }
        return forumColumnExists($db, 'commentaire', 'statut_commentaire');
    } catch (Throwable $e) { return false; }
}

function forumEnsureCommentSignalColumn(): bool
{
    $db = forumGetDb();
    if (!$db) return false;
    try {
        if (!forumColumnExists($db, 'commentaire', 'signale_commentaire')) {
            $db->exec("ALTER TABLE commentaire ADD signale_commentaire TINYINT(1) NOT NULL DEFAULT 0");
        }
        return forumColumnExists($db, 'commentaire', 'signale_commentaire');
    } catch (Throwable $e) { return false; }
}

function forumSetCommentReportedBack(int $commentId, int $value): bool
{
    if ($commentId <= 0) return false;
    $db = forumGetDb();
    if (!$db || !forumEnsureCommentSignalColumn()) return false;
    try {
        $stmt = $db->prepare("UPDATE commentaire SET signale_commentaire = :value WHERE id_commentaire = :id");
        return $stmt->execute(['value' => $value, 'id' => $commentId]);
    } catch (Throwable $e) { return false; }
}


function forumEnsureCommentReportTable(): bool
{
    $db = forumGetDb();
    if (!$db) return false;
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS report_comment (
            id_report_comment INT AUTO_INCREMENT PRIMARY KEY,
            id_commentaire INT NOT NULL,
            id_user INT NOT NULL,
            reason VARCHAR(255) NOT NULL,
            date_report DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        return true;
    } catch (Throwable $e) { return false; }
}

function forumListCommentReportsBack(int $commentId): array
{
    if ($commentId <= 0) return [];
    $db = forumGetDb();
    if (!$db || !forumEnsureCommentReportTable()) return [];
    try {
        $stmt = $db->prepare("SELECT * FROM report_comment WHERE id_commentaire = :id ORDER BY date_report DESC");
        $stmt->execute(['id' => $commentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) { return []; }
}

function forumListPostReportsBack(): array
{
    $db = forumGetDb();
    if (!$db) return [];
    try {
        $rows = $db->query("SELECT * FROM report_post ORDER BY date_report DESC")->fetchAll(PDO::FETCH_ASSOC);
        $map = [];
        foreach ($rows as $r) {
            $pid = (int)($r['id_post'] ?? 0);
            if ($pid <= 0) continue;
            if (!isset($map[$pid])) $map[$pid] = [];
            $map[$pid][] = $r;
        }
        return $map;
    } catch (Throwable $e) { return []; }
}

function forumJsonAttr($value): string
{
    return e(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS | JSON_HEX_QUOT));
}

function forumUpdateCommentStatus(int $commentId, string $status): bool
{
    if ($commentId <= 0) return false;
    $db = forumGetDb();
    if (!$db || !forumEnsureCommentStatusColumn()) return false;
    try {
        $stmt = $db->prepare("UPDATE commentaire SET statut_commentaire = :status WHERE id_commentaire = :id");
        return $stmt->execute(['status' => $status, 'id' => $commentId]);
    } catch (Throwable $e) { return false; }
}

function forumGetCommentByIdBack(int $commentId): ?array
{
    if ($commentId <= 0) return null;
    $db = forumGetDb();
    if (!$db) return null;
    try {
        $stmt = $db->prepare("SELECT * FROM commentaire WHERE id_commentaire = :id LIMIT 1");
        $stmt->execute(['id' => $commentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable $e) { return null; }
}

function forumUpdateCommentBack(int $commentId, string $content, string $emoji, ?string $imagePath = null): bool
{
    if ($commentId <= 0) return false;
    $db = forumGetDb();
    if (!$db) return false;
    try {
        $stmt = $db->prepare("UPDATE commentaire SET contenu_commentaire = :content, emoji_commentaire = :emoji, image_commentaire = :image WHERE id_commentaire = :id");
        return $stmt->execute(['content' => $content, 'emoji' => $emoji, 'image' => $imagePath, 'id' => $commentId]);
    } catch (Throwable $e) { return false; }
}

function forumDeleteCommentBack(int $commentId): bool
{
    if ($commentId <= 0) return false;
    $db = forumGetDb();
    if (!$db) return false;
    try {
        $stmtParent = $db->prepare("DELETE FROM commentaire WHERE id_parent_commentaire = :id");
        $stmtParent->execute(['id' => $commentId]);
        $stmt = $db->prepare("DELETE FROM commentaire WHERE id_commentaire = :id");
        return $stmt->execute(['id' => $commentId]);
    } catch (Throwable $e) { return false; }
}

function forumCommentStatus(array $comment): string
{
    return $comment['statut_commentaire'] ?? 'En attente';
}

function forumGetPostByIdBack(int $id): ?array
{
    if ($id <= 0) return null;
    global $postController;
    if (isset($postController) && method_exists($postController, 'getPostById')) {
        try { $r = $postController->getPostById($id); if ($r) return $r; } catch (Throwable $e) {}
    }
    $db = forumGetDb();
    if (!$db) return null;
    try {
        $stmt = $db->prepare("SELECT p.*, COALESCE(pr.nom, SUBSTRING_INDEX(u.email, '@', 1)) AS nom, COALESCE(pr.prenom, '') AS prenom FROM post p LEFT JOIN `user` u ON p.id_user = u.id_user LEFT JOIN profile pr ON pr.id_user = u.id_user WHERE p.id_post = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    } catch (Throwable $e) {
        try {
            $stmt = $db->prepare("SELECT * FROM post WHERE id_post = :id LIMIT 1");
            $stmt->execute(['id' => $id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (Throwable $e2) { return null; }
    }
}

function forumDeletePostBack(int $id): bool
{
    if ($id <= 0) return false;
    global $postController;
    if (isset($postController) && method_exists($postController, 'deletePost')) {
try {
    $postController->deletePost($id);
    return true;
} catch (Throwable $e) {}    }
    if (isset($postController) && method_exists($postController, 'delete')) {
        try { $postController->delete($id); return true; } catch (Throwable $e) {}
    }
    $db = forumGetDb();
    if (!$db) return false;
    try {
        $db->prepare("DELETE FROM commentaire WHERE id_post = :id")->execute(['id' => $id]);
        $db->prepare("DELETE FROM post WHERE id_post = :id")->execute(['id' => $id]);
        return true;
    } catch (Throwable $e) { return false; }
}

function forumUpdatePostStatusBack(int $id, string $status): bool
{
    if ($id <= 0) return false;
    $db = forumGetDb();
    if (!$db) return false;
    try {
        $stmt = $db->prepare("UPDATE post SET statut_post = :status WHERE id_post = :id");
        return $stmt->execute(['status' => $status, 'id' => $id]);
    } catch (Throwable $e) { return false; }
}

function forumAddPostBack(string $titre, string $contenu, ?string $image, ?string $video, string $type, string $statut, int $userId, string $emoji = '', string $gif = ''): int
{
    $db = forumGetDb();
    if (!$db) return 0;
    forumEnsurePostEmojiColumn();
    forumEnsurePostGifColumn();
    try {
        $cols = ['titre','contenu','image','video','type_post','statut_post','id_user','date_publication'];
        $vals = [':titre',':contenu',':image',':video',':type_post',':statut_post',':id_user','NOW()'];
        $params = ['titre'=>$titre,'contenu'=>$contenu,'image'=>$image,'video'=>$video,'type_post'=>$type,'statut_post'=>$statut,'id_user'=>$userId];
        if (forumColumnExists($db, 'post', 'emoji_post')) { $cols[]='emoji_post'; $vals[]=':emoji_post'; $params['emoji_post']=$emoji; }
        if (forumColumnExists($db, 'post', 'gif_post')) { $cols[]='gif_post'; $vals[]=':gif_post'; $params['gif_post']=$gif !== '' ? $gif : null; }
        $sql = 'INSERT INTO post (`'.implode('`,`',$cols).'`) VALUES ('.implode(',',$vals).')';
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (int)$db->lastInsertId();
    } catch (Throwable $e) { return 0; }
}

function forumUpdatePostBack(int $id, string $titre, string $contenu, ?string $image, ?string $video, string $type, string $statut, string $emoji = '', string $gif = ''): bool
{
    if ($id <= 0) return false;
    $db = forumGetDb();
    if (!$db) return false;
    forumEnsurePostEmojiColumn();
    forumEnsurePostGifColumn();
    try {
        $sets = ['titre = :titre','contenu = :contenu','image = :image','video = :video','type_post = :type_post','statut_post = :statut_post'];
        $params = ['titre'=>$titre,'contenu'=>$contenu,'image'=>$image,'video'=>$video,'type_post'=>$type,'statut_post'=>$statut,'id'=>$id];
        if (forumColumnExists($db, 'post', 'emoji_post')) { $sets[] = 'emoji_post = :emoji_post'; $params['emoji_post'] = $emoji; }
        if (forumColumnExists($db, 'post', 'gif_post')) { $sets[] = 'gif_post = :gif_post'; $params['gif_post'] = $gif !== '' ? $gif : null; }
        $stmt = $db->prepare('UPDATE post SET '.implode(', ', $sets).' WHERE id_post = :id');
        return $stmt->execute($params);
    } catch (Throwable $e) { return false; }
}

function forumListPostsBack(): array
{
    global $postController;
    if (isset($postController) && method_exists($postController, 'listPosts')) {
        try {
            $raw = $postController->listPosts();
            if ($raw instanceof PDOStatement) return $raw->fetchAll(PDO::FETCH_ASSOC);
            if (is_array($raw)) return $raw;
        } catch (Throwable $e) {}
    }
    $db = forumGetDb();
    if (!$db) return [];
    try {
        $sql = "SELECT p.*, 
                       COALESCE(pr.nom, SUBSTRING_INDEX(u.email, '@', 1)) AS nom,
                       COALESCE(pr.prenom, '') AS prenom,
                (SELECT COUNT(*) FROM commentaire c WHERE c.id_post = p.id_post) AS comments_count
                FROM post p 
                LEFT JOIN `user` u ON p.id_user = u.id_user
                LEFT JOIN profile pr ON pr.id_user = u.id_user
                ORDER BY p.id_post DESC";
        return $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        try { return $db->query("SELECT * FROM post ORDER BY id_post DESC")->fetchAll(PDO::FETCH_ASSOC); } catch (Throwable $e2) { return []; }
    }
}

function forumListCommentsByPostBack(int $postId): array
{
    global $commentController;
    if (isset($commentController) && method_exists($commentController, 'listCommentsByPost')) {
        try {
            $raw = $commentController->listCommentsByPost($postId);
            if ($raw instanceof PDOStatement) return $raw->fetchAll(PDO::FETCH_ASSOC);
            if (is_array($raw)) return $raw;
        } catch (Throwable $e) {}
    }
    $db = forumGetDb();
    if (!$db) return [];
    try {
        $stmt = $db->prepare("SELECT c.*, COALESCE(pr.nom, SUBSTRING_INDEX(u.email, '@', 1)) AS nom, COALESCE(pr.prenom, '') AS prenom FROM commentaire c LEFT JOIN `user` u ON c.id_user = u.id_user LEFT JOIN profile pr ON pr.id_user = u.id_user WHERE c.id_post = :id ORDER BY c.id_commentaire DESC");
        $stmt->execute(['id' => $postId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) { return []; }
}

forumEnsurePostEmojiColumn();
forumEnsurePostGifColumn();
forumEnsureCommentStatusColumn();
forumEnsureCommentSignalColumn();
forumEnsureCommentReportTable();

$errors = [
    'titre' => '',
    'contenu' => '',
    'type_post' => '',
    'statut_post' => '',
    'emoji_post' => '',
    'image' => '',
    'video' => '',
    'gif_post' => ''
];

$old = [
    'titre' => '',
    'contenu' => '',
    'type_post' => '',
    'statut_post' => 'Approuvé',
    'emoji_post' => '',
    'gif_post' => ''
];

$isEditMode = false;
$editId = 0;
$editPost = null;

$search = trim($_GET['search'] ?? '');
$filter = trim($_GET['filter'] ?? 'Tous');
$sort   = trim($_GET['sort'] ?? 'date');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['toggle_comment_status'])) {
        $commentId = (int)($_POST['comment_id'] ?? 0);
        $postId = (int)($_POST['post_id'] ?? 0);
        $newStatus = trim($_POST['new_status'] ?? 'En attente');
        if ($commentId > 0 && in_array($newStatus, ['Approuvé', 'Rejeté', 'En attente'], true)) {
            forumUpdateCommentStatus($commentId, $newStatus);
        }
        header('Location: ' . forumBackUrl(['comment_status' => 1, 'open_comments' => $postId]));
        exit;
    }

    if (isset($_POST['delete_comment_back'])) {
        $commentId = (int)($_POST['comment_id'] ?? 0);
        $postId = (int)($_POST['post_id'] ?? 0);
        if ($commentId > 0) forumDeleteCommentBack($commentId);
        header('Location: ' . forumBackUrl(['comment_deleted' => 1, 'open_comments' => $postId]));
        exit;
    }

    if (isset($_POST['update_comment_back'])) {
        $commentId = (int)($_POST['comment_id'] ?? 0);
        $postId = (int)($_POST['post_id'] ?? 0);
        $content = trim($_POST['comment_content'] ?? '');
        $emoji = trim($_POST['comment_emoji'] ?? '');
        if ($commentId > 0 && $content !== '' && getLettersCount($content) >= 2) {
            $oldComment = forumGetCommentByIdBack($commentId);
            $imagePath = $oldComment['image_commentaire'] ?? null;
            if (!empty($_FILES['comment_image']['name'])) {
                $tmpErrors = [];
                $imagePath = uploadCommentImageFileBack($_FILES['comment_image'], $tmpErrors, $imagePath);
            }
            forumUpdateCommentBack($commentId, $content, $emoji, $imagePath);
        }
        header('Location: ' . forumBackUrl(['comment_updated' => 1, 'open_comments' => $postId]));
        exit;
    }

    if (isset($_POST['mark_comment_reported'])) {
        $commentId = (int)($_POST['comment_id'] ?? 0);
        $postId = (int)($_POST['post_id'] ?? 0);
        if ($commentId > 0) forumSetCommentReportedBack($commentId, 1);
        header('Location: ' . forumBackUrl(['comment_reported' => 1, 'open_comments' => $postId]));
        exit;
    }

    if (isset($_POST['clear_comment_reported'])) {
        $commentId = (int)($_POST['comment_id'] ?? 0);
        $postId = (int)($_POST['post_id'] ?? 0);
        if ($commentId > 0) forumSetCommentReportedBack($commentId, 0);
        header('Location: ' . forumBackUrl(['comment_unreported' => 1, 'open_comments' => $postId]));
        exit;
    }

    if (isset($_POST['delete_post'])) {
        $id = (int)($_POST['post_id'] ?? 0);
        if ($id > 0) {
            forumDeletePostBack($id);
        }
        header('Location: ' . forumBackUrl(['deleted' => 1]));
        exit;
    }

    if (isset($_POST['approve_post'])) {
        $id = (int)($_POST['post_id'] ?? 0);
        if ($id > 0) {
            $post = forumGetPostByIdBack($id);
            if ($post) {
                $updatedPost = new Post(
                    $id,
                    $post['titre'] ?? '',
                    $post['contenu'] ?? '',
                    $post['image'] ?? null,
                    $post['video'] ?? null,
                    $post['type_post'] ?? 'Discussion',
                    'Approuvé',
                    $post['id_user'] ?? 1
                );
                forumUpdatePostStatusBack($id, isset($_POST['approve_post']) ? 'Approuvé' : 'Rejeté');
            }
        }
        header('Location: ' . forumBackUrl(['approved' => 1]));
        exit;
    }

    if (isset($_POST['reject_post'])) {
        $id = (int)($_POST['post_id'] ?? 0);
        if ($id > 0) {
            $post = forumGetPostByIdBack($id);
            if ($post) {
                $updatedPost = new Post(
                    $id,
                    $post['titre'] ?? '',
                    $post['contenu'] ?? '',
                    $post['image'] ?? null,
                    $post['video'] ?? null,
                    $post['type_post'] ?? 'Discussion',
                    'Rejeté',
                    $post['id_user'] ?? 1
                );
                forumUpdatePostStatusBack($id, 'Rejeté');
            }
        }
        header('Location: ' . forumBackUrl(['rejected' => 1]));
        exit;
    }

    if (isset($_POST['save_post']) || isset($_POST['update_post'])) {
        $old['titre'] = trim($_POST['titre'] ?? '');
        $old['contenu'] = trim($_POST['contenu'] ?? '');
        $old['type_post'] = trim($_POST['type_post'] ?? '');
        $old['statut_post'] = isset($_POST['statut_post']) ? trim($_POST['statut_post']) : 'Approuvé';
        $old['emoji_post'] = trim($_POST['emoji_post'] ?? '');
        $old['gif_post'] = trim($_POST['gif_post'] ?? '');

        if ($old['titre'] === '') {
            $errors['titre'] = 'Le titre est obligatoire.';
        } elseif (getLettersCount($old['titre']) < 3) {
            $errors['titre'] = 'Le titre doit contenir au moins 3 lettres.';
        }

        if ($old['type_post'] === '') {
            $errors['type_post'] = 'Veuillez choisir le type du post.';
        }

        if ($old['gif_post'] !== '' && !preg_match('~^https?://~i', $old['gif_post'])) {
            $errors['gif_post'] = 'Lien GIF invalide.';
        }


        if ($old['contenu'] === '') {
            $errors['contenu'] = 'La description est obligatoire.';
        } elseif (getLettersCount($old['contenu']) < 5) {
            $errors['contenu'] = 'La description doit contenir au moins 5 lettres.';
        }

        $hasErrors = false;
        foreach ($errors as $error) {
            if (!empty($error)) {
                $hasErrors = true;
                break;
            }
        }

        if (isset($_POST['save_post']) && !$hasErrors) {
            $imagePath = null;
            $videoPath = null;

            if (!empty($_FILES['image']['name'])) {
                $imagePath = uploadImageFile($_FILES['image'], $errors);
            }

            if (!empty($_FILES['video']['name'])) {
                $videoPath = uploadVideoFile($_FILES['video'], $errors);
            }

            foreach ($errors as $error) {
                if (!empty($error)) {
                    $hasErrors = true;
                    break;
                }
            }

            if (!$hasErrors) {
                $newPost = new Post(
                    null,
                    $old['titre'],
                    $old['contenu'],
                    $imagePath,
                    $videoPath,
                    $old['type_post'],
                    'Approuvé',
                    1
                );
                $newId = forumAddPostBack($old['titre'], $old['contenu'], $imagePath, $videoPath, $old['type_post'], 'Approuvé', 1, $old['emoji_post'], $old['gif_post']);
                header('Location: ' . forumBackUrl(['created' => 1]));
                exit;
            }
        }

        if (isset($_POST['update_post'])) {
            $editId = (int)($_POST['edit_id'] ?? 0);
            $current = forumGetPostByIdBack($editId);

            if ($current && !$hasErrors) {
                $imagePath = $current['image'] ?? null;
                $videoPath = $current['video'] ?? null;

                if (!empty($_FILES['image']['name'])) {
                    $imagePath = uploadImageFile($_FILES['image'], $errors, $current['image'] ?? null);
                }

                if (!empty($_FILES['video']['name'])) {
                    $videoPath = uploadVideoFile($_FILES['video'], $errors, $current['video'] ?? null);
                }

                foreach ($errors as $error) {
                    if (!empty($error)) {
                        $hasErrors = true;
                        break;
                    }
                }

                if (!$hasErrors) {
                    $updatedPost = new Post(
                        $editId,
                        $old['titre'],
                        $old['contenu'],
                        $imagePath,
                        $videoPath,
                        $old['type_post'],
                        ($old['statut_post'] !== '' ? $old['statut_post'] : ($current['statut_post'] ?? 'Approuvé')),
                        $current['id_user'] ?? 1
                    );
                    forumUpdatePostBack($editId, $old['titre'], $old['contenu'], $imagePath, $videoPath, $old['type_post'], ($old['statut_post'] !== '' ? $old['statut_post'] : ($current['statut_post'] ?? 'Approuvé')), $old['emoji_post'], $old['gif_post']);
                    header('Location: ' . forumBackUrl(['updated' => 1]));
                    exit;
                }
            }
        }
    }
}

if (isset($_GET['edit']) && ctype_digit($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $editPost = forumGetPostByIdBack($editId);

    if ($editPost) {
        $isEditMode = true;
        $old['titre'] = $editPost['titre'] ?? '';
        $old['contenu'] = $editPost['contenu'] ?? '';
        $old['type_post'] = $editPost['type_post'] ?? '';
        $old['statut_post'] = $editPost['statut_post'] ?? 'Approuvé';
        $old['emoji_post'] = $editPost['emoji_post'] ?? '';
        $old['gif_post'] = $editPost['gif_post'] ?? '';
    }
}

$posts = forumListPostsBack();
$postReportsByPost = forumListPostReportsBack();

foreach ($posts as &$post) {
    $post['author_name'] = trim(($post['prenom'] ?? '') . ' ' . ($post['nom'] ?? ''));
    if ($post['author_name'] === '') {
        $post['author_name'] = $post['auteur'] ?? 'Utilisateur';
    }

    $post['comments_count'] = (int)($post['comments_count'] ?? $post['nb_comments'] ?? $post['total_comments'] ?? 0);
    $pidReportTmp = (int)($post['id_post'] ?? 0);
    $dbReportsCount = isset($postReportsByPost[$pidReportTmp]) ? count($postReportsByPost[$pidReportTmp]) : 0;
    $post['reports_count'] = $dbReportsCount > 0 ? $dbReportsCount : (int)($post['reports_count'] ?? $post['nb_signalements'] ?? $post['signalements_count'] ?? 0);
}
unset($post);

$totalPosts = count($posts);
$totalComments = array_sum(array_column($posts, 'comments_count'));
$totalReports = array_sum(array_column($posts, 'reports_count'));
 $totalRejected = count(array_filter($posts, fn($p) => (($p['statut_post'] ?? '') === 'Rejeté')));


$forumStatusStats = [
    'Approuvé' => 0,
    'Rejeté' => 0,
    'En attente' => 0
];
$forumTypeStats = [];
$forumTopPostsByComments = $posts;
$forumTopPostsByReports = $posts;

foreach ($posts as $statPost) {
    $statStatus = trim($statPost['statut_post'] ?? 'En attente');
    if (!isset($forumStatusStats[$statStatus])) $forumStatusStats[$statStatus] = 0;
    $forumStatusStats[$statStatus]++;

    $statType = trim($statPost['type_post'] ?? 'Autre');
    if ($statType === '') $statType = 'Autre';
    if (!isset($forumTypeStats[$statType])) $forumTypeStats[$statType] = 0;
    $forumTypeStats[$statType]++;
}

arsort($forumTypeStats);
usort($forumTopPostsByComments, fn($a, $b) => ((int)($b['comments_count'] ?? 0)) <=> ((int)($a['comments_count'] ?? 0)));
usort($forumTopPostsByReports, fn($a, $b) => ((int)($b['reports_count'] ?? 0)) <=> ((int)($a['reports_count'] ?? 0)));
$forumTopPostsByComments = array_slice($forumTopPostsByComments, 0, 5);
$forumTopPostsByReports = array_slice($forumTopPostsByReports, 0, 5);

$forumApprovedCount = (int)($forumStatusStats['Approuvé'] ?? 0);
$forumPendingCount = (int)($forumStatusStats['En attente'] ?? 0);
$forumRejectedCount = (int)($forumStatusStats['Rejeté'] ?? 0);
$forumModerationTotal = max(1, $totalPosts);
$forumEngagementScore = $totalPosts > 0 ? round((($totalComments + $totalReports) / $totalPosts), 1) : 0;
$forumApprovalRate = $totalPosts > 0 ? round(($forumApprovedCount / $totalPosts) * 100) : 0;
$forumReportRate = $totalPosts > 0 ? round(($totalReports / $totalPosts) * 100) : 0;

$reportedPosts = array_values(array_filter($posts, fn($p) => (($p['reports_count'] ?? 0) > 0)));

$commentsByPost = [];
foreach ($posts as $pItem) {
    $pid = (int)($pItem['id_post'] ?? 0);
    if ($pid > 0) {
        $commentsByPost[$pid] = forumListCommentsByPostBack($pid);
    }
}

$filteredPosts = array_values(array_filter($posts, function ($post) use ($search, $filter) {
    $ok = true;

    if ($search !== '') {
        $needle = mb_strtolower($search);
        $haystack = mb_strtolower(
            ($post['titre'] ?? '') . ' ' .
            ($post['contenu'] ?? '') . ' ' .
            ($post['author_name'] ?? '')
        );
        $ok = $ok && (mb_strpos($haystack, $needle) !== false);
    }

    if ($filter !== 'Tous') {
        if ($filter === 'Signalés') {
            $ok = $ok && (($post['reports_count'] ?? 0) > 0);
        } elseif ($filter === 'Approuvés') {
            $ok = $ok && (($post['statut_post'] ?? '') === 'Approuvé');
        } elseif ($filter === 'Rejetés') {
            $ok = $ok && (($post['statut_post'] ?? '') === 'Rejeté');
        } elseif ($filter === 'En attente') {
            $ok = $ok && (($post['statut_post'] ?? '') === 'En attente');
        }
    }

    return $ok;
}));

usort($filteredPosts, function ($a, $b) use ($sort) {
    if ($sort === 'popularite') {
        return (($b['comments_count'] ?? 0) <=> ($a['comments_count'] ?? 0));
    }

    if ($sort === 'signalements') {
        return (($b['reports_count'] ?? 0) <=> ($a['reports_count'] ?? 0));
    }

    return strtotime($b['date_publication'] ?? 'now') <=> strtotime($a['date_publication'] ?? 'now');
});

$forumRowsPerPage = 10;
$forumTotalFilteredPosts = count($filteredPosts);
$forumTotalPages = max(1, (int)ceil($forumTotalFilteredPosts / $forumRowsPerPage));
$forumCurrentPage = isset($_GET['pg']) && ctype_digit((string)$_GET['pg']) ? (int)$_GET['pg'] : 1;
$forumCurrentPage = max(1, min($forumCurrentPage, $forumTotalPages));
$forumOffset = ($forumCurrentPage - 1) * $forumRowsPerPage;
$paginatedPosts = array_slice($filteredPosts, $forumOffset, $forumRowsPerPage);

/* Export PDF Dompdf retiré : on utilise maintenant la fenêtre d'impression native via exportForumTableOnly(). */
?>

<style>
    .forum-admin-page{display:flex;flex-direction:column;gap:22px}
    .forum-admin-success{padding:14px 18px;border-radius:18px;font-weight:700;background:#eef8f1;color:#237c48;border:1px solid #d6eddc}
    .forum-admin-filter-line{display:flex;align-items:center;gap:14px;flex-wrap:wrap;width:100%;justify-content:space-between}
    .forum-admin-search-grow{flex:1;min-width:420px}
    .forum-admin-search-grow input{width:100%}
    .forum-admin-filter-line select,.forum-admin-filter-line input{min-height:58px;border-radius:18px;border:1px solid var(--line);padding:0 18px;background:rgba(255,255,255,.88);color:var(--text);outline:none;font-size:17px}
    body.dark .forum-admin-filter-line select,body.dark .forum-admin-filter-line input{background:rgba(12,22,34,.88);color:#fff;border:1px solid rgba(255,255,255,.08)}
    .forum-admin-btns{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-left:auto}
    .forum-admin-btns .outline-btn,.forum-admin-btns .solid-btn{display:inline-flex;align-items:center;gap:8px;transition:transform .22s ease,box-shadow .22s ease,filter .22s ease;animation:officialBtnEnter .45s ease both}
    .forum-admin-btns .outline-btn:nth-child(2){animation-delay:.07s}
    .forum-admin-btns .solid-btn{animation-delay:.14s}
    .forum-admin-btns .outline-btn:hover,.forum-admin-btns .solid-btn:hover{transform:translateY(-4px) scale(1.02);filter:brightness(1.04)}


    /* PAGINATION DESIGN PETIT COMME LA CAPTURE */
    .forum-pagination{display:flex;align-items:center;justify-content:center;gap:9px;margin:18px 0 2px;flex-wrap:wrap}
    .forum-page-link{width:38px;height:38px;border-radius:12px;border:1px solid var(--line,#e3e8ef);background:#fff;color:#142738;font-size:16px;font-weight:900;text-decoration:none;display:inline-flex;align-items:center;justify-content:center;box-shadow:0 7px 16px rgba(15,23,42,.06);transition:transform .22s ease,box-shadow .22s ease,filter .22s ease,background .22s ease;color-scheme:light}
    .forum-page-link:hover{transform:translateY(-2px);box-shadow:0 10px 22px rgba(15,23,42,.10)}
    .forum-page-link.active{background:linear-gradient(135deg,#EE5828 0%,#c9471d 45%,#0f2236 72%,#2f9d4a 100%);color:#fff;border-color:transparent;box-shadow:0 10px 22px rgba(238,88,40,.22)}
    .forum-page-dots{height:38px;display:inline-flex;align-items:center;color:var(--muted,#607089);font-weight:900;padding:0 1px;font-size:14px}
    body.dark .forum-page-link{background:#10263b;color:#fff;border-color:rgba(255,255,255,.10)}
    body.dark .forum-page-link.active{background:linear-gradient(135deg,#EE5828 0%,#c9471d 45%,#0f2236 72%,#2f9d4a 100%)}

    /* Pagination compacte */
    .forum-pagination{gap:7px!important;margin:14px 0 0!important;justify-content:flex-start!important}
    .forum-page-link{width:32px!important;height:32px!important;min-width:32px!important;padding:0!important;border-radius:10px!important;font-size:14px!important;box-shadow:0 5px 12px rgba(15,23,42,.05)!important}
    .forum-page-dots{height:32px!important;font-size:13px!important;padding:0 2px!important}
    body.dark .forum-page-dots{color:#d7dfe8}

    /* Ancien export neutralisé : l'impression utilise uniquement #forumPrintOnly plus bas. */

    @keyframes officialBtnEnter{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
    .forum-admin-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:20px}
    .forum-admin-stat-card{background:var(--card);border:1px solid var(--line);box-shadow:var(--shadow);border-radius:28px;padding:28px 24px;min-height:150px;display:flex;flex-direction:column;justify-content:center;position:relative;overflow:hidden}
    .forum-admin-stat-card{animation:officialStatRise .55s ease both;transition:transform .25s ease,box-shadow .25s ease,filter .25s ease}
    .forum-admin-stat-card:nth-child(2){animation-delay:.07s}
    .forum-admin-stat-card:nth-child(3){animation-delay:.14s}
    .forum-admin-stat-card:nth-child(4){animation-delay:.21s}
    .forum-admin-stat-card:hover{transform:translateY(-8px) scale(1.015);box-shadow:0 24px 55px rgba(15,23,42,.14);filter:brightness(1.01)}
    @keyframes officialStatRise{from{opacity:0;transform:translateY(18px) scale(.96)}to{opacity:1;transform:translateY(0) scale(1)}}
    .forum-admin-stat-card::before{display:none!important;content:""!important;}
    .forum-admin-stat-card::before{content:""}
    .forum-admin-stat-card::before{content:""}
    .forum-admin-stat-card::before{content:""}
    .forum-admin-stat-card::before{content:""}
    .forum-admin-stat-card::before{display:none!important;content:""!important;}
    .forum-admin-stat-card:nth-child(2)::before{animation-delay:.18s}
    .forum-admin-stat-card:nth-child(3)::before{animation-delay:.36s}
    .forum-admin-stat-card:nth-child(4)::before{animation-delay:.54s}
    .forum-admin-stat-card:hover::before{transform:scale(1.08) rotate(-5deg)}
    @keyframes officialIconFloat{0%,100%{transform:translateY(0)}50%{transform:translateY(-7px)}}
    .forum-admin-stat-card strong{font-size:3rem;line-height:1;margin-bottom:10px;color:var(--text)}
    .forum-admin-stat-card span{color:var(--muted);font-weight:700;font-size:1.02rem}
    body.dark .forum-admin-stat-card strong{color:#fff}
    body.dark .forum-admin-stat-card span{color:#d7dfe8}
    .forum-admin-head-row{display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:14px}
    .forum-mini-filters{display:flex;gap:8px;flex-wrap:wrap}
    .forum-mini-filter{display:inline-flex;align-items:center;justify-content:center;min-height:36px;padding:0 14px;border-radius:999px;text-decoration:none;font-size:.9rem;font-weight:700;background:rgba(76,175,80,.14);color:#2f9d4a;border:1px solid rgba(76,175,80,.20);transition:.2s ease}
    .forum-mini-filter:hover,.forum-mini-filter.active{background:#4CAF50;color:#fff;border-color:#4CAF50}
    .forum-admin-table-wrap{overflow-x:auto;width:100%}
    .comments-post-modal .forum-admin-table-wrap{overflow-x:auto;width:100%;padding-bottom:8px}
    .forum-admin-table{width:100%;border-collapse:collapse;min-width:1100px}
    .forum-admin-table th,.forum-admin-table td{padding:16px 14px;border-bottom:1px solid var(--line);text-align:left;vertical-align:middle;color:var(--text)}
    body.dark .forum-admin-table th,body.dark .forum-admin-table td{color:#fff;border-bottom-color:rgba(255,255,255,.08)}
    .forum-admin-table th{font-weight:800}
    .forum-post-title{font-weight:800;color:var(--text);margin-bottom:6px}
    .forum-post-sub{color:var(--muted);font-size:.95rem;line-height:1.5}
    body.dark .forum-post-title{color:#fff}
    body.dark .forum-post-sub{color:#d7dfe8}
    .forum-admin-badge{display:inline-flex;align-items:center;justify-content:center;min-height:38px;padding:0 14px;border-radius:999px;font-size:.92rem;font-weight:700;white-space:nowrap}
    .forum-admin-type{background:rgba(238,88,40,.12);color:var(--orange)}
    .forum-admin-visible{background:rgba(76,175,80,.14);color:#2f9d4a}
    .forum-admin-hidden{background:rgba(245,158,11,.14);color:#b97100}
    .forum-admin-reported{background:rgba(238,88,40,.12);color:var(--orange)}
    body.dark .forum-admin-hidden{color:#ffc56b}
    .forum-admin-tools{display:flex;flex-wrap:wrap;gap:8px}
    .forum-admin-view-btn{min-height:44px;padding:0 16px;border-radius:999px;border:1px solid var(--line);background:#fff;color:var(--text);font-weight:700;cursor:pointer}
    body.dark .forum-admin-view-btn{color:#fff;border-color:rgba(255,255,255,.10);background:#10263b}
    .forum-admin-view-btn:hover{background:var(--grad-soft)}
    .forum-post-modal,.forum-form-modal{position:fixed;inset:0;background:rgba(20,39,56,.45);display:none;align-items:center;justify-content:center;z-index:999999;padding:16px}
    .forum-post-modal.show,.forum-form-modal.show{display:flex}
    .forum-post-modal-box{width:min(820px,100%);max-height:92vh;background:#fff;border:1px solid #e7ebf0;border-radius:28px;overflow:hidden;box-shadow:0 24px 60px rgba(20,39,56,.18);display:flex;flex-direction:column}
    .forum-post-modal-head{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:20px 24px;border-bottom:1px solid #eef2f7}
    .forum-post-modal-head h2{margin:0;color:#1b2d45;font-size:2rem}
    .forum-post-modal-close{width:44px;height:44px;border:none;border-radius:50%;background:#f1f3f6;color:#111;font-size:1.8rem;cursor:pointer}
    .forum-post-modal-body{padding:24px;overflow-y:auto}
    .comments-post-modal{align-items:flex-start;padding-top:18px}
    .forum-comments-modal-body{padding:18px;overflow:auto}
    .forum-view-meta{color:#6a7484;margin-bottom:12px}
    .forum-view-title{font-size:1.8rem;font-weight:900;color:#1b2d45;margin-bottom:10px}
    .forum-view-content{color:#1b2d45;line-height:1.75;white-space:pre-wrap}
    .forum-view-actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:18px;padding-top:16px;border-top:1px solid #eef2f7}
    .forum-view-action-btn{width:auto;min-width:120px;border:1px solid rgba(15,23,42,.10);background:#fff;justify-content:center}
    .forum-view-action-btn:hover{background:#f1f4f8}
    .forum-view-media{margin-top:18px;border-radius:20px;overflow:hidden;background:#000}
    .forum-view-media img,.forum-view-media video{width:100%;max-height:420px;object-fit:contain;display:block;background:#000}
    .forum-view-external-list{display:flex;flex-direction:column;gap:16px;margin-top:18px}
    .forum-view-external{border-radius:22px;overflow:hidden;background:#000;border:1px solid #e7ebf0;box-shadow:0 12px 28px rgba(15,23,42,.10)}
    .forum-view-external iframe{width:100%;height:520px;border:0;display:block;background:#000}
    .forum-view-external img{width:100%;max-height:520px;object-fit:contain;display:block;background:#000}
    .forum-view-external.youtube iframe{height:460px}
    .forum-view-external.tiktok iframe,.forum-view-external.instagram iframe{height:650px;background:#fff}
    .forum-view-external.facebook{background:#1877f2;border:none}
    .forum-facebook-card{display:flex;align-items:center;justify-content:space-between;gap:16px;min-height:150px;padding:22px;background:linear-gradient(135deg,#1877f2,#0b5ed7);color:#fff;text-decoration:none}
    .forum-facebook-card strong{display:block;font-size:1.35rem;margin-bottom:6px;color:#fff}
    .forum-facebook-card span{color:rgba(255,255,255,.88);font-weight:800}
    .forum-facebook-icon{width:62px;height:62px;border-radius:20px;background:#fff;color:#1877f2;display:flex;align-items:center;justify-content:center;font-size:2.5rem;font-weight:950;flex-shrink:0}
    .forum-view-external-link{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 18px;background:#f8fafc;color:#142738;text-decoration:none;font-weight:900;word-break:break-word}
    .forum-view-external-link:hover{background:#eef2f7}
    body.dark .forum-view-external-link{background:#10263b;color:#fff}
    .forum-original-card{display:none;margin-top:18px;border:1px solid #e7ebf0;border-radius:22px;background:#f8fafc;padding:18px;color:#1b2d45}
    .forum-original-card.show{display:block}
    .forum-original-label{font-weight:950;color:#EE5828;margin-bottom:10px;font-size:.98rem}
    .forum-original-meta{color:#64748b;font-weight:800;margin-bottom:8px;font-size:.92rem}
    .forum-original-title{font-size:1.35rem;font-weight:950;margin-bottom:8px;color:#142738}
    .forum-original-content{white-space:pre-wrap;line-height:1.6;color:#1b2d45}
    .forum-original-emoji{font-size:24px;margin-top:10px}
    .forum-original-media{margin-top:12px;border-radius:18px;overflow:hidden;background:#000}
    .forum-original-media img,.forum-original-media video{width:100%;max-height:320px;object-fit:contain;display:block;background:#000}
    body.dark .forum-original-card{background:#f8fafc;color:#1b2d45}
    @media(max-width:720px){.forum-view-external iframe{height:420px}.forum-view-external.tiktok iframe,.forum-view-external.instagram iframe{height:560px}}
    body.dark .forum-post-modal-box{background:#fff}
    body.dark .forum-post-modal-head h2,body.dark .forum-view-title,body.dark .forum-view-content{color:#1b2d45}
    body.dark .forum-view-meta{color:#6a7484}
    .forum-form-modal-box{width:min(980px,100%);max-height:92vh;background:#fff;border:1px solid #e7ebf0;border-radius:32px;overflow:hidden;box-shadow:0 24px 60px rgba(20,39,56,.18);display:flex;flex-direction:column}
    .forum-form-modal-head{display:flex;justify-content:space-between;align-items:center;gap:16px;padding:20px 24px;border-bottom:1px solid #eef2f7;flex-shrink:0}
    .forum-form-modal-head h2{margin:0;color:#1b2d45;font-size:2.15rem;font-weight:900}
    .forum-form-modal-close{width:54px;height:54px;border:none;border-radius:50%;background:#f1f3f6;color:#111;font-size:2rem;cursor:pointer;flex-shrink:0}
    .forum-form-modal-body{padding:20px 22px 24px;overflow-y:auto}
    .forum-form-top-user{display:flex;align-items:center;gap:14px;margin-bottom:18px}
    .forum-form-top-user strong{font-size:1.05rem;color:#1b2d45}
    .forum-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
    .forum-form-grid .full{grid-column:1 / -1}
    .forum-field{display:flex;flex-direction:column;gap:8px}
    .forum-field label{display:none}
    .forum-field input,.forum-field select,.forum-field textarea{width:100%;min-height:56px;border-radius:22px;border:1px solid #d9dee5;padding:0 18px;background:#fff;color:#24364b;outline:none;font-size:1rem;box-sizing:border-box}
    .forum-field textarea{min-height:150px;padding:18px;resize:vertical;font-family:inherit}
    .forum-field input::placeholder,.forum-field textarea::placeholder{color:#7f8895}
    .forum-error{min-height:16px;color:#dc2626;font-size:.88rem;padding-left:4px}
    .field-invalid{border-color:#dc2626 !important}
    .field-valid-input{border-color:#16a34a !important}
    .forum-form-tools{margin-top:18px;padding:16px 18px;border:1px solid #d9dee5;border-radius:22px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap}
    .forum-form-tools > div:first-child{color:#1f3144;font-size:1rem}
    .forum-form-tool-icons{display:flex;gap:18px;align-items:center}
    .forum-tool-btn{border:none;background:transparent;color:#1f3144;font-size:2.1rem;cursor:pointer;line-height:1;display:flex;align-items:center;justify-content:center;min-width:34px;min-height:34px;opacity:1}
    .forum-tool-btn:hover{transform:scale(1.06)}
    .forum-form-preview,.forum-form-video-preview,.forum-current-image,.forum-current-video{margin-top:14px;border-radius:22px;overflow:hidden;border:1px solid #d9dee5;display:none;background:#000}
    .forum-form-preview.show,.forum-form-video-preview.show,.forum-current-image.show,.forum-current-video.show{display:block}
    .forum-form-preview img,.forum-current-image img{width:100%;max-height:300px;object-fit:contain;display:block;background:#000}
    .forum-form-video-preview video,.forum-current-video video{width:100%;max-height:320px;object-fit:contain;display:block;background:#000}
    .forum-form-actions{display:flex;justify-content:flex-start;gap:14px;flex-wrap:wrap;margin-top:20px}
    .forum-form-actions .ghost-btn,.forum-form-actions .solid-btn{min-height:56px;padding:0 24px;border-radius:999px;font-size:1rem;font-weight:800}
    body.dark .forum-form-modal-box{background:#10263b;border:1px solid rgba(255,255,255,.08)}
    body.dark .forum-form-modal-head{border-bottom:1px solid rgba(255,255,255,.08)}
    body.dark .forum-form-modal-head h2{color:#fff}
    body.dark .forum-form-modal-close{background:rgba(255,255,255,.08);color:#fff}
    body.dark .forum-form-top-user strong{color:#fff}
    body.dark .forum-field input,body.dark .forum-field select,body.dark .forum-field textarea{background:#0b1d2d;color:#fff;border:1px solid rgba(255,255,255,.08)}
    body.dark .forum-field input::placeholder,body.dark .forum-field textarea::placeholder{color:#b9c7d6}
    body.dark .forum-form-tools{border:1px solid rgba(255,255,255,.08);background:#10263b}
    body.dark .forum-form-tools > div:first-child{color:#fff}
    body.dark .forum-tool-btn{color:#fff}
    body.dark .forum-form-preview,body.dark .forum-form-video-preview,body.dark .forum-current-image,body.dark .forum-current-video{border:1px solid rgba(255,255,255,.08)}

    /* ---- GIF BUTTON STYLE comme front ---- */
    .gif-media-btn{width:48px;height:48px;border:none;background:transparent;border-radius:14px;display:inline-flex;align-items:center;justify-content:center;cursor:pointer;transition:.2s ease;padding:0;min-width:48px;min-height:48px}
    .gif-media-btn:hover{background:rgba(0,0,0,.06);transform:translateY(-2px)}
    body.dark .gif-media-btn:hover{background:rgba(255,255,255,.08)}
    .gif-text{font-size:22px;font-weight:900;letter-spacing:1px;line-height:1;background:linear-gradient(135deg,#111827,#2563eb,#ec4899,#f97316);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
    body.dark .gif-text{background:linear-gradient(135deg,#ffffff,#60a5fa,#f472b6,#fb923c);-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
    .gif-selected-preview{margin-top:14px;border-radius:22px;overflow:hidden;border:1px solid #d9dee5;display:none;background:#000;position:relative}
    .gif-selected-preview.show{display:block}
    .gif-selected-preview img{width:100%;max-height:300px;object-fit:contain;background:#000;display:block}
    .gif-clear-btn{position:absolute;right:12px;top:12px;width:36px;height:36px;border:none;border-radius:50%;background:rgba(0,0,0,.55);color:#fff;font-size:20px;cursor:pointer;z-index:2}
    .gif-modal{position:fixed;inset:0;background:rgba(15,23,42,.55);display:none;align-items:center;justify-content:center;z-index:1300001;padding:18px}
    .gif-modal.show{display:flex}
    .gif-box{width:min(720px,100%);max-height:86vh;background:#fff;color:#142738;border:1px solid #e7ebf0;border-radius:24px;box-shadow:0 30px 80px rgba(0,0,0,.35);overflow:hidden;display:flex;flex-direction:column}
    .gif-header{padding:14px;display:flex;gap:10px;border-bottom:1px solid #eef2f7;align-items:center}
    .gif-header input{flex:1;height:48px;border-radius:999px;border:1px solid #d9dee5;background:#fff;color:#142738;padding:0 16px;outline:none;font:inherit}
    .gif-header button{width:44px;height:44px;border:none;border-radius:50%;background:#f1f3f6;color:#111;font-size:20px;cursor:pointer}
    .gif-results{padding:14px;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;overflow-y:auto;max-height:62vh}
    .gif-results img{width:100%;height:150px;object-fit:cover;border-radius:16px;background:#000;cursor:pointer;transition:.2s ease}
    .gif-results img:hover{transform:translateY(-2px) scale(1.02);box-shadow:0 12px 24px rgba(238,88,40,.22)}
    .gif-empty{padding:22px;text-align:center;color:#64748b;font-weight:800;grid-column:1/-1}
    body.dark .gif-box{background:#10263b;color:#fff;border-color:rgba(255,255,255,.08)}
    body.dark .gif-header{border-bottom-color:rgba(255,255,255,.08)}
    body.dark .gif-header input{background:#0b1d2d;color:#fff;border-color:rgba(255,255,255,.08)}
    body.dark .gif-header button{background:rgba(255,255,255,.08);color:#fff}
    body.dark .gif-selected-preview{border-color:rgba(255,255,255,.08)}
    @media(max-width:700px){.gif-results{grid-template-columns:repeat(2,minmax(0,1fr));}.gif-results img{height:135px}}

    .forum-emoji-picker{position:fixed;width:320px;max-width:calc(100vw - 24px);background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:22px;box-shadow:0 24px 60px rgba(15,23,42,.20);display:none;z-index:1300000;overflow:hidden}
    .forum-emoji-picker.show{display:block}
    .forum-emoji-head{padding:14px 16px;border-bottom:1px solid rgba(15,23,42,.08);font-weight:800;color:#17283f;background:#fff}
    .forum-emoji-body{max-height:260px;overflow-y:auto;padding:12px;display:grid;grid-template-columns:repeat(7,1fr);gap:8px}
    .forum-emoji-btn{border:none;background:#fff;border-radius:12px;height:40px;font-size:22px;cursor:pointer}
    .forum-emoji-btn:hover{background:#f1f4f8;transform:scale(1.08)}

    .forum-comments-modal-box{width:min(1320px,calc(100vw - 40px));max-height:94vh;background:#fff;border:1px solid #e7ebf0;border-radius:28px;overflow:hidden;box-shadow:0 24px 60px rgba(20,39,56,.18);display:flex;flex-direction:column}
    .forum-comments-title{display:flex;flex-direction:column;gap:4px}
    .forum-comments-title h2{margin:0;color:#1b2d45;font-size:2rem}
    .forum-comments-title span{color:#6a7484;font-weight:700}
    .forum-comments-modal-body{padding:22px;overflow:auto}
    .forum-comments-table{width:100%;border-collapse:collapse;min-width:1180px}
    .forum-comments-table th,.forum-comments-table td{padding:14px 12px;border-bottom:1px solid #eef2f7;text-align:left;vertical-align:top;color:#1b2d45}
    .forum-comments-table th{font-weight:900;background:#f8fafc}
    .forum-comments-table th:last-child,
    .forum-comments-table td:last-child{min-width:260px;white-space:normal}
    .forum-comment-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
    .forum-comment-actions form{display:inline-flex;margin:0}
    .forum-comment-content-cell{line-height:1.55;max-width:360px;white-space:pre-wrap;word-break:break-word}
    .forum-comment-image-admin{max-width:120px;max-height:90px;object-fit:contain;border-radius:12px;background:#000;display:block}
    .forum-comment-parent-badge{display:inline-flex;align-items:center;justify-content:center;min-height:30px;padding:0 10px;border-radius:999px;background:rgba(238,88,40,.12);color:#EE5828;font-size:.82rem;font-weight:800;white-space:nowrap}
    body.dark .forum-comments-modal-box{background:#fff}
    @media (max-width:1100px){.forum-admin-stats{grid-template-columns:repeat(2,1fr)}}
    @media (max-width:900px){.forum-form-grid{grid-template-columns:1fr}.forum-form-grid .full{grid-column:auto}.forum-admin-filter-line{align-items:stretch}.forum-admin-search-grow{min-width:100%}}
    @media (max-width:700px){.forum-admin-stats{grid-template-columns:1fr}}
.forum-help{margin-top:8px;color:#64748b;font-weight:700;font-size:14px}
    .forum-comments-table{min-width:1160px}
    .forum-comment-actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
    .forum-comment-actions form{display:inline;margin:0}
    .forum-comment-action-btn{min-height:38px;padding:0 13px;border-radius:999px;border:1px solid #e3e8ef;background:#fff;color:#1b2d45;font-weight:800;cursor:pointer;font-size:.86rem}
    .forum-comment-action-btn:hover{background:#f8fafc}
    .forum-comment-action-main{background:linear-gradient(135deg,#EE5828,#cf4218);border-color:transparent;color:#fff;box-shadow:0 10px 22px rgba(238,88,40,.22)}
    .forum-comment-action-danger{background:linear-gradient(135deg,#dc2626,#991b1b);border-color:transparent;color:#fff;box-shadow:0 10px 22px rgba(220,38,38,.18)}
    .forum-comment-reported{background:rgba(238,88,40,.12);color:#EE5828}
    .forum-edit-comment-modal{position:fixed;inset:0;background:rgba(20,39,56,.45);display:none;align-items:center;justify-content:center;z-index:1250000;padding:16px}
    .forum-edit-comment-modal.show{display:flex}
    .forum-edit-comment-box{width:min(620px,100%);background:#fff;border-radius:28px;box-shadow:0 24px 60px rgba(20,39,56,.18);overflow:hidden}
    .forum-edit-comment-head{display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid #eef2f7}
    .forum-edit-comment-head h2{margin:0;color:#1b2d45;font-size:1.8rem}
    .forum-edit-comment-close{width:44px;height:44px;border:none;border-radius:50%;background:#f1f3f6;font-size:1.8rem;cursor:pointer}
    .forum-edit-comment-body{padding:22px}
    .forum-edit-comment-body textarea,.forum-edit-comment-body input[type=text]{width:100%;box-sizing:border-box;border:1px solid #d9dee5;border-radius:18px;padding:14px 16px;font:inherit;color:#1b2d45;outline:none}
    .forum-edit-comment-body textarea{min-height:130px;resize:vertical;margin-bottom:12px}
    .forum-edit-comment-body input[type=text]{height:54px;font-size:22px;margin-bottom:14px}


    .forum-post-emoji-back{margin-top:6px;font-size:22px;line-height:1.2}
    .forum-view-emoji{margin-top:12px;font-size:28px;line-height:1.2;color:#1b2d45}
    .forum-report-detail-grid{display:grid;grid-template-columns:150px 1fr;gap:12px 16px;color:#1b2d45;font-size:1rem;line-height:1.55}
    .forum-report-detail-grid strong{font-weight:900;color:#142738}
    .forum-report-detail-box{padding:14px 16px;border:1px solid #e7ebf0;border-radius:16px;background:#f8fafc;white-space:pre-wrap;word-break:break-word}

/* ============================
   MENU 3 POINTS Ã¢â‚¬â€ FIXED
   ============================ */

/* Do NOT set overflow-y:visible here Ã¢â‚¬â€ combining overflow-x:auto with
   overflow-y:visible forces both to auto per the CSS spec, which creates
   a stacking context that clips position:fixed children. */
.forum-admin-table-wrap {
    overflow-x: auto;
    padding-right: 0;
}

.forum-actions-head {
    width: 52px;
    min-width: 52px;
    max-width: 52px;
    padding: 0 8px !important;
}

.forum-actions-cell {
    width: 52px;
    min-width: 52px;
    max-width: 52px;
    text-align: center !important;
    padding: 10px 8px !important;
}

/* The button lives inside the td Ã¢â‚¬â€ no wrapper div needed */
.forum-row-menu-btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    border: 1px solid var(--line, #e3e8ef);
    background: #ffffff;
    color: #142738;
    font-size: 22px;
    font-weight: 900;
    line-height: 1;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s, color 0.2s, transform 0.2s;
    padding: 0;
    flex-shrink: 0;
}

.forum-row-menu-btn:hover,
.forum-row-menu-btn.active {
    background: rgba(238, 88, 40, 0.12);
    color: #EE5828;
    transform: translateY(-2px);
}

/* Dropdowns are rendered directly on <body> via JS teleport,
   so position:fixed works perfectly with no clipping ancestor. */
.forum-row-dropdown {
    position: fixed;
    min-width: 210px;
    background: #ffffff;
    border: 1px solid rgba(15, 23, 42, 0.10);
    border-radius: 16px;
    box-shadow: 0 16px 40px rgba(15, 23, 42, 0.20);
    padding: 6px;
    z-index: 2147483647;
    display: none;
}

.forum-row-dropdown form {
    margin: 0;
}

.forum-row-action {
    width: 100%;
    min-height: 40px;
    border: none;
    background: transparent;
    color: #142738;
    border-radius: 10px;
    padding: 0 12px;
    font: inherit;
    font-size: 13.5px;
    font-weight: 700;
    text-align: left;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 7px;
    text-decoration: none;
    white-space: nowrap;
}

.forum-row-action:hover {
    background: #f1f4f8;
}

.forum-row-action.danger {
    color: #dc2626;
}

.forum-row-action.danger:hover {
    background: #fff1f1;
}

body.dark .forum-row-menu-btn {
    background: #10263b;
    color: #ffffff;
    border-color: rgba(255, 255, 255, 0.12);
}

body.dark .forum-row-menu-btn:hover,
body.dark .forum-row-menu-btn.active {
    background: rgba(238, 88, 40, 0.16);
    color: #ff8b62;
}

body.dark .forum-row-dropdown {
    background: #132d46;
    border-color: rgba(255, 255, 255, 0.10);
}

body.dark .forum-row-action {
    color: #e8f0f9;
}

body.dark .forum-row-action:hover {
    background: rgba(255, 255, 255, 0.07);
}

body.dark .forum-row-action.danger {
    color: #ff8f81;
}
/* FIX TABLE WITHOUT HORIZONTAL SCROLL */
.forum-admin-table-wrap {
    overflow-x: visible !important;
    width: 100% !important;
    padding-right: 0 !important;
}

.forum-admin-table {
    min-width: 0 !important;
    width: 100% !important;
    table-layout: auto !important;
}
/* ANIMATION CLICK SUR COLONNE POST */
.forum-post-open-zone {
    cursor: pointer;
    border-radius: 16px;
    padding: 10px 12px;
    margin: -10px -12px;
    transition: 
        transform 0.18s ease,
        background 0.18s ease,
        box-shadow 0.18s ease;
}

.forum-post-open-zone:hover {
    background: rgba(238, 88, 40, 0.08);
    transform: translateX(4px);
    box-shadow: 0 10px 25px rgba(238, 88, 40, 0.08);
}

.forum-post-open-zone:active {
    transform: scale(0.98);
}

body.dark .forum-post-open-zone:hover {
    background: rgba(255, 255, 255, 0.07);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.22);
}
/* MENU 3 POINTS POUR TABLEAU COMMENTAIRES */
.forum-comment-actions-head {
    width: 52px !important;
    min-width: 52px !important;
    max-width: 52px !important;
    padding: 0 8px !important;
}

.forum-comment-actions-cell {
    width: 52px !important;
    min-width: 52px !important;
    max-width: 52px !important;
    text-align: center !important;
    padding: 10px 8px !important;
}

.forum-comments-table th:last-child,
.forum-comments-table td:last-child {
    min-width: 52px !important;
    width: 52px !important;
    max-width: 52px !important;
    white-space: nowrap !important;
}

.forum-comments-table .forum-row-menu-btn {
    width: 40px;
    height: 40px;
}


/* ============================
   FORUM BACK STATS POPUP - SCHEMAS ANIMES
   ============================ */
.forum-stats-modal{position:fixed;inset:0;background:rgba(6,14,24,.66);display:none;align-items:center;justify-content:center;z-index:1400000;padding:18px;backdrop-filter:blur(8px)}
.forum-stats-modal.show{display:flex}
.forum-stats-box{width:min(1180px,calc(100vw - 34px));max-height:92vh;background:#fff;border:1px solid #e7ebf0;border-radius:34px;box-shadow:0 32px 90px rgba(0,0,0,.28);display:flex;flex-direction:column;overflow:hidden;animation:statsPop .32s ease both}
@keyframes statsPop{from{opacity:0;transform:translateY(18px) scale(.96)}to{opacity:1;transform:translateY(0) scale(1)}}
.forum-stats-head{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:22px 26px;border-bottom:1px solid #eef2f7;background:radial-gradient(circle at 15% 20%,rgba(238,88,40,.18),transparent 32%),radial-gradient(circle at 90% 10%,rgba(76,175,80,.18),transparent 34%),linear-gradient(135deg,#fff,#f8fafc)}
.forum-stats-head h2{margin:0;color:#142738;font-size:2rem;font-weight:950;letter-spacing:-.02em}
.forum-stats-head p{margin:4px 0 0;color:#64748b;font-weight:800}
.forum-stats-close{width:48px;height:48px;border:none;border-radius:50%;background:#f1f3f6;color:#111;font-size:2rem;cursor:pointer;line-height:1;transition:.2s ease}.forum-stats-close:hover{transform:rotate(90deg) scale(1.05)}
.forum-stats-body{padding:22px;overflow:auto;background:#f8fafc}
.forum-stat-kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:18px}
.forum-stat-kpi{background:#fff;border:1px solid #e7ebf0;border-radius:26px;padding:18px;box-shadow:0 14px 30px rgba(15,23,42,.07);min-height:118px;position:relative;overflow:hidden;animation:statRise .45s ease both}.forum-stat-kpi:nth-child(2){animation-delay:.06s}.forum-stat-kpi:nth-child(3){animation-delay:.12s}.forum-stat-kpi:nth-child(4){animation-delay:.18s}
@keyframes statRise{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:translateY(0)}}
.forum-stat-kpi:after{display:none!important;content:""!important;}.forum-stat-kpi:nth-child(2):after{animation-delay:.2s}.forum-stat-kpi:nth-child(3):after{animation-delay:.4s}.forum-stat-kpi:nth-child(4):after{animation-delay:.6s}
@keyframes floatIcon{0%,100%{transform:translateY(0)}50%{transform:translateY(-6px)}}
.forum-stat-kpi strong{display:block;font-size:2.25rem;color:#142738;line-height:1;margin-bottom:8px}.forum-stat-kpi span{display:block;color:#64748b;font-weight:900}
.forum-stat-grid{display:grid;grid-template-columns:1.05fr .95fr;gap:18px}.forum-stat-card{background:#fff;border:1px solid #e7ebf0;border-radius:28px;padding:20px;box-shadow:0 14px 30px rgba(15,23,42,.07);animation:statRise .45s ease both}.forum-stat-card h3{margin:0 0 16px;color:#142738;font-size:1.18rem;font-weight:950}
.forum-donut-layout{display:grid;grid-template-columns:220px 1fr;gap:18px;align-items:center}.forum-donut{width:210px;height:210px;border-radius:50%;background:conic-gradient(#4CAF50 0 var(--approved), #f59e0b var(--approved) calc(var(--approved) + var(--pending)), #ef4444 calc(var(--approved) + var(--pending)) 100%);display:flex;align-items:center;justify-content:center;position:relative;margin:auto;box-shadow:inset 0 0 0 1px rgba(15,23,42,.08);animation:donutSpin .9s ease both}@keyframes donutSpin{from{transform:rotate(-90deg) scale(.86);filter:blur(2px)}to{transform:rotate(0) scale(1);filter:blur(0)}}
.forum-donut:after{content:"";width:124px;height:124px;background:#fff;border-radius:50%;position:absolute;box-shadow:0 0 0 1px rgba(15,23,42,.06)}.forum-donut-center{position:relative;z-index:2;text-align:center;color:#142738;font-weight:950}.forum-donut-center strong{display:block;font-size:2rem;line-height:1}.forum-donut-center span{font-size:.82rem}
.forum-legend{display:flex;flex-direction:column;gap:10px}.forum-legend-item{display:flex;align-items:center;justify-content:space-between;gap:12px;color:#142738;font-weight:900;background:#f8fafc;border:1px solid #eef2f7;border-radius:16px;padding:12px 14px;transform-origin:left;animation:legendIn .45s ease both}.forum-legend-item:nth-child(2){animation-delay:.08s}.forum-legend-item:nth-child(3){animation-delay:.16s}@keyframes legendIn{from{opacity:0;transform:scaleX(.86)}to{opacity:1;transform:scaleX(1)}}
.forum-legend-left{display:flex;align-items:center;gap:9px}.forum-dot{width:13px;height:13px;border-radius:50%;display:inline-flex}.forum-dot.green{background:#4CAF50}.forum-dot.orange{background:#f59e0b}.forum-dot.red{background:#ef4444}
.forum-bar-row{display:grid;grid-template-columns:105px 1fr 44px;align-items:center;gap:12px;margin:12px 0;color:#142738;font-weight:900}.forum-bar-track{height:22px;background:#eef2f7;border-radius:999px;overflow:hidden;position:relative}.forum-bar-fill{height:100%;border-radius:999px;background:linear-gradient(135deg,#EE5828,#4CAF50);min-width:8px;width:0;animation:growBar .9s ease forwards;box-shadow:0 8px 18px rgba(238,88,40,.22)}@keyframes growBar{from{width:0}to{width:var(--w)}}
.forum-pulse-zone{display:grid;grid-template-columns:repeat(3,1fr);gap:16px}.forum-pulse-card{min-height:165px;border-radius:28px;border:1px solid #e7ebf0;background:linear-gradient(180deg,#fff,#f8fafc);display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden}.forum-pulse-card:before,.forum-pulse-card:after{content:"";position:absolute;width:120px;height:120px;border-radius:50%;background:rgba(238,88,40,.10);animation:pulseCircle 2.2s ease-in-out infinite}.forum-pulse-card:after{width:78px;height:78px;background:rgba(76,175,80,.14);animation-delay:.5s}@keyframes pulseCircle{0%,100%{transform:scale(.75);opacity:.7}50%{transform:scale(1.25);opacity:.2}}
.forum-pulse-value{position:relative;z-index:2;text-align:center;color:#142738;font-weight:950}.forum-pulse-value strong{display:block;font-size:2.5rem;line-height:1}.forum-pulse-value span{display:block;margin-top:8px;color:#64748b;font-weight:900;font-size:.9rem}
.forum-bubble-stage{min-height:210px;display:flex;align-items:center;justify-content:center;gap:14px;flex-wrap:wrap}.forum-bubble{width:var(--size);height:var(--size);min-width:72px;min-height:72px;max-width:150px;max-height:150px;border-radius:50%;background:linear-gradient(135deg,#EE5828,#4CAF50);display:flex;flex-direction:column;align-items:center;justify-content:center;color:#fff;text-align:center;box-shadow:0 16px 30px rgba(238,88,40,.20);animation:bubbleIn .55s ease both, bubbleFloat 3s ease-in-out infinite}.forum-bubble:nth-child(2){animation-delay:.08s}.forum-bubble:nth-child(3){animation-delay:.16s}.forum-bubble:nth-child(4){animation-delay:.24s}.forum-bubble:nth-child(5){animation-delay:.32s}@keyframes bubbleIn{from{opacity:0;transform:scale(.3)}to{opacity:1;transform:scale(1)}}@keyframes bubbleFloat{0%,100%{transform:translateY(0)}50%{transform:translateY(-8px)}}
.forum-bubble strong{font-size:1.3rem;line-height:1}.forum-bubble span{font-size:.72rem;font-weight:900;max-width:110px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;padding:0 8px;margin-top:6px}
.forum-report-radar{display:grid;grid-template-columns:repeat(5,1fr);gap:10px;align-items:end;min-height:210px;padding-top:10px}.forum-report-tower{display:flex;flex-direction:column;align-items:center;gap:8px}.forum-report-bar{width:46px;height:var(--h);min-height:10px;border-radius:999px 999px 10px 10px;background:linear-gradient(180deg,#ef4444,#f59e0b);box-shadow:0 10px 22px rgba(239,68,68,.22);animation:towerGrow .9s ease forwards;transform-origin:bottom}@keyframes towerGrow{from{transform:scaleY(0)}to{transform:scaleY(1)}}.forum-report-tower strong{color:#142738;font-size:1rem}.forum-report-tower span{max-width:105px;color:#64748b;font-size:.72rem;font-weight:900;text-align:center;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.forum-stat-empty{color:#64748b;font-weight:900;padding:18px;background:#f8fafc;border-radius:18px;text-align:center}.forum-mini-note{margin-top:14px;color:#64748b;font-weight:900;text-align:center;font-size:.9rem}
body.dark .forum-stats-box{background:#10263b;border-color:rgba(255,255,255,.08)}body.dark .forum-stats-head{border-bottom-color:rgba(255,255,255,.08);background:radial-gradient(circle at 15% 20%,rgba(238,88,40,.20),transparent 32%),radial-gradient(circle at 90% 10%,rgba(76,175,80,.18),transparent 34%),linear-gradient(135deg,#10263b,#0b1d2d)}body.dark .forum-stats-head h2,body.dark .forum-stat-kpi strong,body.dark .forum-stat-card h3,body.dark .forum-bar-row,body.dark .forum-donut-center,body.dark .forum-legend-item,body.dark .forum-pulse-value,body.dark .forum-report-tower strong{color:#fff}body.dark .forum-stats-head p,body.dark .forum-stat-kpi span,body.dark .forum-stat-empty,body.dark .forum-pulse-value span,body.dark .forum-report-tower span,body.dark .forum-mini-note{color:#c7d3e0}body.dark .forum-stats-body{background:#0b1d2d}body.dark .forum-stat-kpi,body.dark .forum-stat-card,body.dark .forum-pulse-card{background:#10263b;border-color:rgba(255,255,255,.08);box-shadow:0 12px 26px rgba(0,0,0,.22)}body.dark .forum-stats-close{background:rgba(255,255,255,.08);color:#fff}body.dark .forum-bar-track,body.dark .forum-legend-item,body.dark .forum-stat-empty{background:#0b1d2d;border-color:rgba(255,255,255,.08)}body.dark .forum-donut:after{background:#10263b}
@media(max-width:950px){.forum-stat-kpi-grid,.forum-stat-grid,.forum-donut-layout,.forum-pulse-zone{grid-template-columns:1fr}.forum-report-radar{grid-template-columns:repeat(2,1fr)}.forum-donut{width:190px;height:190px}.forum-donut:after{width:112px;height:112px}.forum-bar-row{grid-template-columns:90px 1fr 40px}}


</style>

<div class="forum-admin-page">
    <?php if (isset($_GET['created'])): ?><div class="forum-admin-success reveal">Post ajouté avec succès.</div><?php endif; ?>
    <?php if (isset($_GET['updated'])): ?><div class="forum-admin-success reveal">Post mis à jour avec succès.</div><?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?><div class="forum-admin-success reveal">Post supprimé avec succès.</div><?php endif; ?>
    <?php if (isset($_GET['approved'])): ?><div class="forum-admin-success reveal">Post approuvé avec succès.</div><?php endif; ?>
    <?php if (isset($_GET['rejected'])): ?><div class="forum-admin-success reveal">Post rejeté avec succès.</div><?php endif; ?>
    <?php if (isset($_GET['comment_status'])): ?><div class="forum-admin-success reveal">Statut du commentaire modifié avec succès.</div><?php endif; ?>
    <?php if (isset($_GET['comment_deleted'])): ?><div class="forum-admin-success reveal">Commentaire supprimé avec succès.</div><?php endif; ?>
    <?php if (isset($_GET['comment_updated'])): ?><div class="forum-admin-success reveal">Commentaire modifié avec succès.</div><?php endif; ?>

    <section class="action-bar reveal">
        <form method="GET" action="<?php echo e(forumBackUrl()); ?>" class="forum-admin-filter-line">
            <input type="hidden" name="page" value="forum">
            <input type="hidden" name="filter" value="<?php echo e($filter); ?>">

            <div class="forum-admin-search-grow">
                <input type="text" name="search" placeholder="Rechercher un post ou un commentaire..." value="<?php echo e($search); ?>">
            </div>

            <select name="sort" onchange="this.form.submit()">
                <option value="date" <?php echo $sort === 'date' ? 'selected' : ''; ?>>Date</option>
                <option value="popularite" <?php echo $sort === 'popularite' ? 'selected' : ''; ?>>Popularite</option>
                <option value="signalements" <?php echo $sort === 'signalements' ? 'selected' : ''; ?>>Signalements</option>
            </select>

            <div class="forum-admin-btns">
                <button type="button" class="outline-btn" id="openForumStatsModal" onclick="document.getElementById('forumStatsModal').classList.add('show');document.body.style.overflow='hidden';">Statistiques</button>
                <button type="button" class="outline-btn" onclick="exportForumTableOnly()">Exporter</button>
                <button type="button" class="solid-btn" id="openCreatePostModal" onclick="openPostFormModalFn(); return false;">+ Nouveau post</button>
            </div>
        </form>
    </section>


    <section class="admin-stats reveal forum-admin-stats">
        <article class="admin-stat forum-admin-stat-card"><strong><?php echo $totalPosts; ?></strong><span>Posts</span></article>
        <article class="admin-stat forum-admin-stat-card"><strong><?php echo $totalComments; ?></strong><span>Commentaires</span></article>
        <article class="admin-stat forum-admin-stat-card"><strong><?php echo $totalReports; ?></strong><span>Signalements</span></article>
        <article class="admin-stat forum-admin-stat-card"><strong><?php echo $totalRejected; ?></strong><span>Rejetés</span></article>
    </section>

    <div class="forum-stats-modal" id="forumStatsModal">
        <div class="forum-stats-box">
            <div class="forum-stats-head">
                <div>
                    <h2>Statistiques</h2>
                    <p>Schémas dynamiques du forum</p>
                </div>
                <button type="button" class="forum-stats-close" id="closeForumStatsModal" onclick="document.getElementById('forumStatsModal').classList.remove('show');document.body.style.overflow='';">Ã—</button>
            </div>
            <div class="forum-stats-body">
                <div class="forum-stat-kpi-grid">
                    <div class="forum-stat-kpi" data-icon=""><strong><?php echo (int)$totalPosts; ?></strong><span>Posts</span></div>
                    <div class="forum-stat-kpi" data-icon=""><strong><?php echo (int)$totalComments; ?></strong><span>Commentaires</span></div>
                    <div class="forum-stat-kpi" data-icon=""><strong><?php echo (int)$totalReports; ?></strong><span>Signalements</span></div>
                    <div class="forum-stat-kpi" data-icon=""><strong><?php echo e($forumEngagementScore); ?></strong><span>Interaction/post</span></div>
                </div>

                <div class="forum-stat-grid">
                    <div class="forum-stat-card">
                        <h3>Statuts</h3>
                        <?php
                            $approvedDeg = ($forumApprovedCount / $forumModerationTotal) * 100;
                            $pendingDeg = ($forumPendingCount / $forumModerationTotal) * 100;
                        ?>
                        <div class="forum-donut-layout">
                                <div class="forum-donut" style="--approved: <?php echo e($approvedDeg); ?>%; --pending: <?php echo e($pendingDeg); ?>%;">
                                <div class="forum-donut-center"><strong><?php echo $forumApprovalRate; ?>%</strong><span>Approuvés</span></div>
                            </div>
                            <div class="forum-legend">
                                <div class="forum-legend-item"><span class="forum-legend-left"><i class="forum-dot green"></i> Approuvés</span><strong><?php echo $forumApprovedCount; ?></strong></div>
                                <div class="forum-legend-item"><span class="forum-legend-left"><i class="forum-dot orange"></i> En attente</span><strong><?php echo $forumPendingCount; ?></strong></div>
                                <div class="forum-legend-item"><span class="forum-legend-left"><i class="forum-dot red"></i> Rejetés</span><strong><?php echo $forumRejectedCount; ?></strong></div>
                            </div>
                        </div>
                    </div>

                    <div class="forum-stat-card">
                        <h3>Types</h3>
                        <?php if (empty($forumTypeStats)): ?>
                            <div class="forum-stat-empty">Aucune donnée</div>
                        <?php else: ?>
                            <?php $maxTypeCount = max(1, max($forumTypeStats)); ?>
                            <?php foreach ($forumTypeStats as $typeName => $typeCount): ?>
                                <div class="forum-bar-row">
                                    <span><?php echo e($typeName); ?></span>
                                    <div class="forum-bar-track"><div class="forum-bar-fill" style="--w:<?php echo e(round(($typeCount / $maxTypeCount) * 100)); ?>%"></div></div>
                                    <strong><?php echo (int)$typeCount; ?></strong>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="forum-stat-card">
                        <h3>Activité</h3>
                        <div class="forum-pulse-zone">
                            <div class="forum-pulse-card"><div class="forum-pulse-value"><strong><?php echo (int)$totalPosts; ?></strong><span>posts</span></div></div>
                            <div class="forum-pulse-card"><div class="forum-pulse-value"><strong><?php echo (int)$totalComments; ?></strong><span>commentaires</span></div></div>
                            <div class="forum-pulse-card"><div class="forum-pulse-value"><strong><?php echo (int)$totalReports; ?></strong><span>alertes</span></div></div>
                        </div>
                    </div>

                    <div class="forum-stat-card">
                        <h3>Top commentaires</h3>
                        <?php if (empty($forumTopPostsByComments)): ?>
                            <div class="forum-stat-empty">Aucun post</div>
                        <?php else: ?>
                            <?php $maxCommentBubble = max(1, (int)($forumTopPostsByComments[0]['comments_count'] ?? 0)); ?>
                            <div class="forum-bubble-stage">
                                <?php foreach ($forumTopPostsByComments as $topPost): ?>
                                    <?php
                                        $bubbleCount = (int)($topPost['comments_count'] ?? 0);
                                        $bubbleSize = 74 + round(($bubbleCount / $maxCommentBubble) * 74);
                                    ?>
                                    <div class="forum-bubble" style="--size:<?php echo (int)$bubbleSize; ?>px">
                                        <strong><?php echo $bubbleCount; ?></strong>
                                        <span><?php echo e(mb_strimwidth($topPost['titre'] ?? 'Post', 0, 18, '...')); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="forum-stat-card" style="grid-column:1 / -1;">
                        <h3>Signalements</h3>
                        <?php if (empty($forumTopPostsByReports) || $totalReports === 0): ?>
                            <div class="forum-stat-empty">Aucun signalement</div>
                        <?php else: ?>
                            <?php $maxReportTower = max(1, (int)($forumTopPostsByReports[0]['reports_count'] ?? 0)); ?>
                            <div class="forum-report-radar">
                                <?php foreach ($forumTopPostsByReports as $topReportPost): ?>
                                    <?php $reportCount = (int)($topReportPost['reports_count'] ?? 0); if ($reportCount <= 0) continue; ?>
                                    <?php $towerHeight = 36 + round(($reportCount / $maxReportTower) * 138); ?>
                                    <div class="forum-report-tower">
                                        <div class="forum-report-bar" style="--h:<?php echo (int)$towerHeight; ?>px"></div>
                                        <strong><?php echo $reportCount; ?></strong>
                                        <span><?php echo e(mb_strimwidth($topReportPost['titre'] ?? 'Post', 0, 20, '...')); ?></span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <div class="forum-mini-note">Mise Ã  jour automatique aprÃ¨s chaque action.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <section class="admin-panel reveal">
        <div class="forum-admin-head-row">
            <span class="section-badge">Modération des publications</span>

            <div class="forum-mini-filters">
                <a href="<?php echo e(forumBackUrl(['filter' => 'Tous', 'search' => $search, 'sort' => $sort])); ?>" class="forum-mini-filter <?php echo $filter === 'Tous' ? 'active' : ''; ?>">Tous</a>
                <a href="<?php echo e(forumBackUrl(['filter' => 'Signalés', 'search' => $search, 'sort' => $sort])); ?>" class="forum-mini-filter <?php echo $filter === 'Signalés' ? 'active' : ''; ?>">Signalés</a>
                <a href="<?php echo e(forumBackUrl(['filter' => 'Approuvés', 'search' => $search, 'sort' => $sort])); ?>" class="forum-mini-filter <?php echo $filter === 'Approuvés' ? 'active' : ''; ?>">Approuvés</a>
                <a href="<?php echo e(forumBackUrl(['filter' => 'Rejetés', 'search' => $search, 'sort' => $sort])); ?>" class="forum-mini-filter <?php echo $filter === 'Rejetés' ? 'active' : ''; ?>">Rejetés</a>
                <a href="<?php echo e(forumBackUrl(['filter' => 'En attente', 'search' => $search, 'sort' => $sort])); ?>" class="forum-mini-filter <?php echo $filter === 'En attente' ? 'active' : ''; ?>">En attente</a>
            </div>
        </div>

        <div class="forum-admin-table-wrap">
            <table class="module-table forum-admin-table" id="forumExportTable">
                <thead>
                    <tr>
                        <th>Post</th>
                        <th>Auteur</th>
                        <th>Type</th>
                        <th>Statut</th>
                        <th>Signalé</th>
                        <th>Commentaires</th>
                        <th class="forum-actions-head"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($paginatedPosts)): ?>
                        <tr><td colspan="7" class="back-comment-empty">Aucun post trouvé.</td></tr>
                    <?php else: ?>
                        <?php foreach ($paginatedPosts as $post): ?>
                            <?php
                                $postId = (int)($post['id_post'] ?? 0);
                                $title = $post['titre'] ?? 'Post sans titre';
                                $content = $post['contenu'] ?? '';
                                $emojiPost = $post['emoji_post'] ?? '';
                                $author = $post['author_name'] ?? 'Utilisateur';
                                $type = $post['type_post'] ?? 'Discussion';
                                $status = $post['statut_post'] ?? 'Visible';
                                $reports = (int)($post['reports_count'] ?? 0);
                                $imageUrl = !empty($post['image']) ? forumMediaUrl($post['image']) : '';
                                $videoUrl = !empty($post['video']) ? forumMediaUrl($post['video']) : '';
                                $gifUrl = !empty($post['gif_post']) ? forumMediaUrl($post['gif_post']) : '';
                                $originalPayload = forumOriginalPostPayloadBack($post);
                            ?>
                            <tr>
     <td>
    <div class="forum-post-open-zone open-view-post" onclick="if(event.target.closest('button,a,form,input,select,textarea,.forum-row-dropdown')){return;} openForumPostPreview(this);"
        data-title="<?php echo e($title); ?>"
        data-content="<?php echo e($content); ?>"
        data-author="<?php echo e($author); ?>"
        data-type="<?php echo e($type); ?>"
        data-status="<?php echo e($status); ?>"
        data-emoji="<?php echo e($post['emoji_post'] ?? ''); ?>"
        data-image="<?php echo e($imageUrl); ?>"
        data-video="<?php echo e($videoUrl); ?>"
        data-gif="<?php echo e($gifUrl); ?>"
        data-original="<?php echo forumJsonAttr($originalPayload); ?>"
        data-post-id="<?php echo $postId; ?>"
    >
        <div class="forum-post-title"><?php echo e($title); ?></div>
        <div class="forum-post-sub"><?php echo e(mb_strimwidth($content, 0, 75, '...')); ?></div>

        <?php if (!empty($emojiPost)): ?>
            <div class="forum-post-emoji-back"><?php echo e($emojiPost); ?></div>
        <?php endif; ?>
    </div>
</td>
                                <td><?php echo e($author); ?></td>
                                <td><span class="forum-admin-badge forum-admin-type"><?php echo e($type); ?></span></td>
                                <td>
                                    <?php if ($status === 'Rejeté'): ?>
                                        <span class="forum-admin-badge forum-admin-hidden">Rejeté</span>
                                    <?php elseif ($status === 'Approuvé'): ?>
                                        <span class="forum-admin-badge forum-admin-visible">Approuvé</span>
                                    <?php else: ?>
                                        <span class="forum-admin-badge forum-admin-reported">En attente</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($reports > 0): ?>
                                        <span class="forum-admin-badge forum-admin-reported">Oui (<?php echo $reports; ?>)</span>
                                    <?php else: ?>
                                        Non
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php $postCommentsCount = count($commentsByPost[$postId] ?? []); ?>
                                    <button type="button" class="forum-admin-view-btn open-comments-modal" data-post-id="<?php echo $postId; ?>">
                                        Commentaires (<?php echo $postCommentsCount; ?>)
                                    </button>
                                </td>
                               <td class="forum-actions-cell">
    <?php
        $postReportsMain = $postReportsByPost[$postId] ?? [];
        $firstPostReportMain = $postReportsMain[0] ?? [];
        $postReportReasonMain = $firstPostReportMain['reason'] ?? 'Contenu signale';
        $postReportUserMain = !empty($firstPostReportMain['id_user']) ? ('Utilisateur #' . (int)$firstPostReportMain['id_user']) : 'Utilisateur';
        $postReportDateMain = !empty($firstPostReportMain['date_report']) ? date('d/m/Y H:i', strtotime($firstPostReportMain['date_report'])) : '-';
        $postReportPayloadMain = [
            'title'  => 'Signalement du post',
            'target' => $title,
            'type'   => 'Post',
            'reason' => $postReportReasonMain,
            'user'   => $postReportUserMain,
            'date'   => $postReportDateMain,
            'count'  => count($postReportsMain)
        ];
    ?>
    <button
    type="button"
    class="forum-row-menu-btn"
    aria-label="Options"
    data-menu-target="frm-<?php echo $postId; ?>"
    onclick="return toggleForumRowDropdown(this, event);">...</button>
</td>
                               
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <table id="forumExportFullTable" style="display:none;">
            <thead>
                <tr>
                    <th>Post</th>
                    <th>Auteur</th>
                    <th>Type</th>
                    <th>Statut</th>
                        <th>Signalé</th>
                    <th>Commentaires</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($filteredPosts)): ?>
                    <tr><td colspan="6">Aucun post trouvé.</td></tr>
                <?php else: ?>
                    <?php foreach ($filteredPosts as $exportPost): ?>
                        <?php
                            $exportPostId = (int)($exportPost['id_post'] ?? 0);
                            $exportTitle = $exportPost['titre'] ?? 'Post sans titre';
                            $exportContent = $exportPost['contenu'] ?? '';
                            $exportEmoji = $exportPost['emoji_post'] ?? '';
                            $exportAuthor = $exportPost['author_name'] ?? 'Utilisateur';
                            $exportType = $exportPost['type_post'] ?? 'Discussion';
                            $exportStatus = $exportPost['statut_post'] ?? 'En attente';
                            $exportReports = (int)($exportPost['reports_count'] ?? 0);
                            $exportComments = count($commentsByPost[$exportPostId] ?? []);
                        ?>
                        <tr>
                            <td><strong><?php echo e($exportTitle); ?></strong><br><span><?php echo e(mb_strimwidth($exportContent, 0, 110, '...')); ?></span><?php if (!empty($exportEmoji)): ?><br><span><?php echo e($exportEmoji); ?></span><?php endif; ?></td>
                            <td><?php echo e($exportAuthor); ?></td>
                            <td><?php echo e($exportType); ?></td>
                            <td><?php echo e($exportStatus); ?></td>
                            <td><?php echo $exportReports > 0 ? 'Oui (' . $exportReports . ')' : 'Non'; ?></td>
                            <td><?php echo (int)$exportComments; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <?php if ($forumTotalPages > 1): ?>
            <div class="forum-pagination">
                <?php if ($forumCurrentPage > 1): ?>
                    <a class="forum-page-link" href="<?php echo e(forumBackUrl(['pg' => $forumCurrentPage - 1])); ?>">â€¹</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $forumTotalPages; $i++): ?>
                    <?php if ($i === 1 || $i === $forumTotalPages || abs($i - $forumCurrentPage) <= 1): ?>
                        <a class="forum-page-link <?php echo $i === $forumCurrentPage ? 'active' : ''; ?>" href="<?php echo e(forumBackUrl(['pg' => $i])); ?>"><?php echo $i; ?></a>
                    <?php elseif ($i === 2 || $i === $forumTotalPages - 1): ?>
                        <span class="forum-page-dots">...</span>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($forumCurrentPage < $forumTotalPages): ?>
                    <a class="forum-page-link" href="<?php echo e(forumBackUrl(['pg' => $forumCurrentPage + 1])); ?>">â€º</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>

    <?php foreach ($paginatedPosts as $postForComments): ?>
        <?php
            $modalPostId = (int)($postForComments['id_post'] ?? 0);
            $modalPostTitle = $postForComments['titre'] ?? 'Post';
            $modalComments = $commentsByPost[$modalPostId] ?? [];
        ?>
        <div class="forum-post-modal comments-post-modal" id="comments-modal-<?php echo $modalPostId; ?>">
            <div class="forum-comments-modal-box">
                <div class="forum-post-modal-head">
                    <div class="forum-comments-title">
                        <h2>Commentaires</h2>
                        <span>Post : <?php echo e($modalPostTitle); ?></span>
                    </div>
                    <button type="button" class="forum-post-modal-close close-comments-modal" data-post-id="<?php echo $modalPostId; ?>">&times;</button>
                </div>

                <div class="forum-comments-modal-body">
                    <div class="forum-admin-table-wrap">
                        <table class="forum-comments-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Auteur</th>
                                    <th>Commentaire</th>
                                    <th>Emoji</th>
                                    <th>Image</th>
                                    <th>Type</th>
                                    <th>Statut</th>
                                    <th>Signalé</th>
                                    <th>Date</th>
                                    <th class="forum-comment-actions-head"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($modalComments)): ?>
                                    <tr><td colspan="10" class="back-comment-empty">Aucun commentaire pour ce post.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($modalComments as $comment): ?>
                                        <?php
                                            $commentId = (int)($comment['id_commentaire'] ?? 0);
                                            $commentAuthor = trim(($comment['prenom'] ?? '') . ' ' . ($comment['nom'] ?? ''));
                                            if ($commentAuthor === '') $commentAuthor = 'Utilisateur #' . (int)($comment['id_user'] ?? 0);
                                            $commentContent = $comment['contenu_commentaire'] ?? '';
                                            $commentEmoji = $comment['emoji_commentaire'] ?? '';
                                            $commentImage = !empty($comment['image_commentaire']) ? forumMediaUrl($comment['image_commentaire']) : '';
                                            $parentId = (int)($comment['id_parent_commentaire'] ?? 0);
                                            $commentDate = !empty($comment['date_commentaire']) ? date('d/m/Y H:i', strtotime($comment['date_commentaire'])) : '-';
                                            $commentStatus = forumCommentStatus($comment);
                                            $commentReports = forumListCommentReportsBack($commentId);
                                            $commentReported = ((int)($comment['signale_commentaire'] ?? 0) === 1 || !empty($commentReports)) ? 1 : 0;
                                            $firstCommentReport = $commentReports[0] ?? [];
                                            $commentReportPayload = [
                                                'title' => 'Signalement du commentaire',
                                                'target' => $commentContent,
                                                'type' => $parentId > 0 ? 'Réponse' : 'Commentaire',
                                                'reason' => $firstCommentReport['reason'] ?? 'Commentaire signalé',
                                                'details' => $firstCommentReport['details'] ?? '',
                                                'user' => !empty($firstCommentReport['id_user']) ? ('Utilisateur #' . (int)$firstCommentReport['id_user']) : 'Utilisateur',
                                                'date' => !empty($firstCommentReport['date_report']) ? date('d/m/Y H:i', strtotime($firstCommentReport['date_report'])) : '-',
                                                'count' => count($commentReports)
                                            ];
                                        ?>
                                        <tr>
                                            <td>#<?php echo $commentId; ?></td>
                                            <td><?php echo e($commentAuthor); ?></td>
                                            <td class="forum-comment-content-cell"><?php echo e($commentContent); ?></td>
                                            <td><?php echo $commentEmoji !== '' ? e($commentEmoji) : '-'; ?></td>
                                            <td>
                                                <?php if ($commentImage): ?>
                                                    <img src="<?php echo e($commentImage); ?>" class="forum-comment-image-admin" alt="Image commentaire">
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($parentId > 0): ?>
                                                    <span class="forum-comment-parent-badge">Réponse ÃƒÂ  #<?php echo $parentId; ?></span>
                                                <?php else: ?>
                                                    <span class="forum-admin-badge forum-admin-type">Commentaire</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($commentStatus === 'Approuvé'): ?>
                                                    <span class="forum-admin-badge forum-admin-visible">Approuvé</span>
                                                <?php elseif ($commentStatus === 'Rejeté'): ?>
                                                    <span class="forum-admin-badge forum-admin-hidden">Rejeté</span>
                                                <?php else: ?>
                                                    <span class="forum-admin-badge forum-admin-reported">En attente</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($commentReported === 1): ?>
                                                    <span class="forum-admin-badge forum-comment-reported">Oui</span>
                                                <?php else: ?>
                                                    Non
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo e($commentDate); ?></td>
                                            <td class="forum-comment-actions-cell">
    <button
        type="button"
        class="forum-row-menu-btn"
        aria-label="Options commentaire"
        data-menu-target="cmt-<?php echo $commentId; ?>"
    onclick="return toggleForumRowDropdown(this, event);">...</button>

    <div class="forum-row-dropdown" id="cmt-<?php echo $commentId; ?>">

        <form method="POST" style="margin:0">
            <input type="hidden" name="comment_id" value="<?php echo $commentId; ?>">
            <input type="hidden" name="post_id" value="<?php echo $modalPostId; ?>">
            <input type="hidden" name="new_status" value="<?php echo $commentStatus === 'Approuvé' ? 'Rejeté' : 'Approuvé'; ?>">

            <button type="submit" name="toggle_comment_status" class="forum-row-action">
                <?php echo $commentStatus === 'Approuvé' ? 'Rejeter' : 'Approuver'; ?>
            </button>
        </form>

        <button
            type="button"
            class="forum-row-action open-edit-comment-modal"
            data-comment-id="<?php echo $commentId; ?>"
            data-post-id="<?php echo $modalPostId; ?>"
            data-content="<?php echo e($commentContent); ?>"
            data-emoji="<?php echo e($commentEmoji); ?>"
        >
            âœï¸ Modifier
        </button>

        <?php if ($commentReported === 1): ?>
            <button
                type="button"
                class="forum-row-action open-report-detail"
                data-report="<?php echo forumJsonAttr($commentReportPayload); ?>"
            >
                Traiter signalement
            </button>
        <?php endif; ?>

        <form method="POST" style="margin:0" onsubmit="return confirm('Supprimer ce commentaire ?');">
            <input type="hidden" name="comment_id" value="<?php echo $commentId; ?>">
            <input type="hidden" name="post_id" value="<?php echo $modalPostId; ?>">

            <button type="submit" name="delete_comment_back" class="forum-row-action danger">
                Supprimer
            </button>
        </form>

    </div>
</td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    </div><!-- end forum-admin-table-wrap -->

        <?php foreach ($paginatedPosts as $_dp):
            $_id      = (int)($_dp['id_post'] ?? 0);
            $_title   = $_dp['titre'] ?? 'Post sans titre';
            $_content = $_dp['contenu'] ?? '';
            $_author  = $_dp['author_name'] ?? 'Utilisateur';
            $_type    = $_dp['type_post'] ?? 'Discussion';
            $_status  = $_dp['statut_post'] ?? 'Visible';
            $_reports = (int)($_dp['reports_count'] ?? 0);
            $_imgUrl  = !empty($_dp['image']) ? forumMediaUrl($_dp['image']) : '';
            $_vidUrl  = !empty($_dp['video']) ? forumMediaUrl($_dp['video']) : '';
            $_reps    = $postReportsByPost[$_id] ?? [];
            $_frep    = $_reps[0] ?? [];
            $_rpay    = [
                'title'  => 'Signalement du post',
                'target' => $_title,
                'type'   => 'Post',
                'reason' => $_frep['reason'] ?? 'Contenu signalé',
                'user'   => !empty($_frep['id_user']) ? ('Utilisateur #' . (int)$_frep['id_user']) : 'Utilisateur',
                'date'   => !empty($_frep['date_report']) ? date('d/m/Y H:i', strtotime($_frep['date_report'])) : '-',
                'count'  => count($_reps)
            ];
        ?>
        <div class="forum-row-dropdown" id="frm-<?php echo $_id; ?>">

            <!-- Approve / Reject first -->
            <form method="POST" style="margin:0">
                <input type="hidden" name="post_id" value="<?php echo $_id; ?>">
                <button type="submit"
                    name="<?php echo $_status === 'Approuvé' ? 'reject_post' : 'approve_post'; ?>"
                    class="forum-row-action"
                ><?php echo $_status === 'Approuvé' ? 'Rejeter' : 'Approuver'; ?></button>
            </form>

            <!-- Modify second -->
            <a class="forum-row-action" href="<?php echo e(forumBackUrl(['edit' => $_id])); ?>">✏️ Modifier</a>

            <!-- Report handling third -->
            <?php if ($_reports > 0): ?>
            <button type="button"
                class="forum-row-action open-report-detail"
                data-report="<?php echo forumJsonAttr($_rpay); ?>"
            >Traiter signalement</button>
            <?php endif; ?>

            <!-- Delete last -->
            <form method="POST" style="margin:0" onsubmit="return confirm('Supprimer ce post ?');">
                <input type="hidden" name="post_id" value="<?php echo $_id; ?>">
                <button type="submit" name="delete_post" class="forum-row-action danger">Supprimer</button>
            </form>

        </div>
        <?php endforeach; ?>
    </section><!-- end admin-panel -->
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>




</div>

<div class="forum-post-modal" id="viewPostModal">
    <div class="forum-post-modal-box">
        <div class="forum-post-modal-head">
            <h2>Voir le post</h2>
            <button type="button" class="forum-post-modal-close" id="closeViewPostModal">&times;</button>
        </div>
        <div class="forum-post-modal-body">
            <div class="forum-view-meta" id="viewPostMeta"></div>
            <div class="forum-view-title" id="viewPostTitle"></div>
            <div class="forum-view-content" id="viewPostContent"></div>
            <div class="forum-view-content" id="viewPostEmoji" style="font-size:26px;margin-top:10px;"></div>
            <div class="forum-view-media" id="viewPostImageWrap" style="display:none;"><img id="viewPostImage" src="" alt="Image post"></div>
            <div class="forum-view-media" id="viewPostVideoWrap" style="display:none;"><video id="viewPostVideo" controls></video></div>
            <div class="forum-view-external-list" id="viewPostExternalList"></div>
            <div class="forum-view-actions" id="viewPostActions">
                <form method="POST" style="margin:0;display:inline-flex;">
                    <input type="hidden" name="post_id" id="viewApprovePostId" value="">
                    <button type="submit" name="approve_post" class="forum-row-action forum-view-action-btn">Approuver</button>
                </form>
                <form method="POST" style="margin:0;display:inline-flex;">
                    <input type="hidden" name="post_id" id="viewRejectPostId" value="">
                    <button type="submit" name="reject_post" class="forum-row-action forum-view-action-btn">Rejeter</button>
                </form>
                <a class="forum-row-action forum-view-action-btn" id="viewEditPostLink" href="#">Modifier</a>
                <form method="POST" style="margin:0;display:inline-flex;" onsubmit="return confirm('Supprimer ce post ?');">
                    <input type="hidden" name="post_id" id="viewDeletePostId" value="">
                    <button type="submit" name="delete_post" class="forum-row-action forum-view-action-btn danger">Supprimer</button>
                </form>
            </div>
            <div class="forum-original-card" id="viewOriginalPostCard">
                <div class="forum-original-label">Publication originale</div>
                <div class="forum-original-meta" id="viewOriginalMeta"></div>
                <div class="forum-original-title" id="viewOriginalTitle"></div>
                <div class="forum-original-content" id="viewOriginalContent"></div>
                <div class="forum-original-emoji" id="viewOriginalEmoji"></div>
                <div class="forum-original-media" id="viewOriginalImageWrap" style="display:none;"><img id="viewOriginalImage" src="" alt="Image originale"></div>
                <div class="forum-original-media" id="viewOriginalVideoWrap" style="display:none;"><video id="viewOriginalVideo" controls></video></div>
                <div class="forum-view-external-list" id="viewOriginalExternalList"></div>
            </div>
        </div>
    </div>
</div>


<div class="forum-post-modal" id="reportDetailModal">
    <div class="forum-post-modal-box" style="width:min(720px,100%);">
        <div class="forum-post-modal-head">
            <h2 id="reportDetailTitle">Détails du signalement</h2>
            <button type="button" class="forum-post-modal-close" id="closeReportDetailModal">&times;</button>
        </div>
        <div class="forum-post-modal-body">
            <div class="forum-report-detail-grid">
                <strong>Élément</strong><div id="reportDetailTarget" class="forum-report-detail-box"></div>
                <strong>Type</strong><div id="reportDetailType"></div>
                <strong>Motif</strong><div id="reportDetailReason"></div>
                <strong>SignalÃ© par</strong><div id="reportDetailUser"></div>
                <strong>Date</strong><div id="reportDetailDate"></div>
                <strong>Total</strong><div id="reportDetailCount"></div>
            </div>
        </div>
    </div>
</div>

<div class="forum-form-modal <?php echo ($isEditMode || array_filter($errors)) ? 'show' : ''; ?>" id="postFormModal">
    


<div class="forum-form-modal-box">
        


<div class="forum-form-modal-head">
            <h2><?php echo $isEditMode ? 'Modifier la publication' : 'CrÃ©er une publication'; ?></h2>
            <button type="button" class="forum-form-modal-close" id="closePostFormModal">&times;</button>
        </div>

        


<div class="forum-form-modal-body">
            <div class="forum-form-top-user">
                <div class="mini-avatar"><?php echo htmlspecialchars(strtoupper(mb_substr($_SESSION['user_name'] ?? ($_SESSION['prenom'] ?? 'U'), 0, 1, 'UTF-8'))); ?></div>
                <strong><?php echo htmlspecialchars($_SESSION['user_name'] ?? trim(($_SESSION['prenom'] ?? '') . ' ' . ($_SESSION['nom'] ?? ''))); ?></strong>
            </div>

            <form method="POST" enctype="multipart/form-data" id="forumBackPostForm">
                <?php if ($isEditMode): ?>
                    <input type="hidden" name="edit_id" value="<?php echo (int)$editId; ?>">
                <?php endif; ?>

                <div class="forum-form-grid">
                    <div class="forum-field full">
                        <input type="text" name="titre" id="back_titre" placeholder="Titre du post" value="<?php echo e($old['titre']); ?>" class="<?php echo invalidClass($errors['titre']); ?>">
                        <div class="forum-error"><?php echo e($errors['titre']); ?></div>
                    </div>

                    <div class="forum-field">
                        <select name="type_post" id="back_type_post" class="<?php echo invalidClass($errors['type_post']); ?>">
                            <option value="">Type de post</option>
                            <option value="Discussion" <?php echo $old['type_post'] === 'Discussion' ? 'selected' : ''; ?>>Discussion</option>
                            <option value="Question" <?php echo $old['type_post'] === 'Question' ? 'selected' : ''; ?>>Question</option>
                            <option value="Conseil" <?php echo $old['type_post'] === 'Conseil' ? 'selected' : ''; ?>>Conseil</option>
                        </select>
                        <div class="forum-error"><?php echo e($errors['type_post']); ?></div>
                    </div>

                    <div class="forum-field full">
                        <textarea name="contenu" id="back_contenu"placeholder="Description" class="<?php echo invalidClass($errors['contenu']); ?>"><?php echo e($old['contenu']); ?></textarea>
                        <div class="forum-error"><?php echo e($errors['contenu']); ?></div>
                    </div>

                    <div class="forum-field full">
                        <input type="text" name="emoji_post" id="back_emoji_post" placeholder="Emoji du post" value="<?php echo e($old['emoji_post'] ?? ''); ?>">
                    </div>

                    <div class="forum-field full">
                        <input type="hidden" name="gif_post" id="back_gif_post" value="<?php echo e($old['gif_post'] ?? ''); ?>">
                        <div class="gif-selected-preview <?php echo !empty($old['gif_post']) ? 'show' : ''; ?>" id="backGifPreviewWrap">
                            <button type="button" class="gif-clear-btn" id="clearBackGifBtn">&times;</button>
                            <img id="backGifPreview" src="<?php echo !empty($old['gif_post']) ? e(forumMediaUrl($old['gif_post'])) : ''; ?>" alt="Selected GIF">
                        </div>
                        <div class="forum-error"><?php echo e($errors['gif_post'] ?? ''); ?></div>
                    </div>
                </div>

                <div class="forum-form-tools">
                    <div>Ajouter Ã  votre publication</div>
                    <div class="forum-form-tool-icons">
                        <button type="button" class="forum-tool-btn" id="triggerBackImage" aria-label="Image">ðŸ“·</button>
                        <button type="button" class="forum-tool-btn" id="triggerBackVideo" aria-label="Video">ðŸŽ¥</button>
                        <button type="button" class="gif-media-btn" id="triggerBackGif" aria-label="GIF" title="Choisir un GIF"><span class="gif-text">GIF</span></button>
                        <button type="button" class="forum-tool-btn" id="triggerBackEmoji" aria-label="Emoji">ðŸ˜Š</button>
                    </div>
                </div>

                <div class="forum-field full">
                    <input type="file" name="image" id="back_image" accept=".jpg,.jpeg,.png,.webp,.gif" style="display:none;">
                    <div class="forum-error"><?php echo e($errors['image']); ?></div>
                    <div class="forum-form-preview" id="backImagePreviewWrap"><img id="backImagePreview" src="" alt="Image preview"></div>

                    <?php if ($isEditMode && $editPost && !empty($editPost['image'])): ?>
                        <div class="forum-current-image show" id="backCurrentImage">
                            <img src="<?php echo e(forumMediaUrl($editPost['image'])); ?>" alt="Image actuelle">
                        </div>
                    <?php else: ?>
                        <div class="forum-current-image" id="backCurrentImage"></div>
                    <?php endif; ?>
                </div>

                <div class="forum-field full">
                    <input type="file" name="video" id="back_video" accept=".mp4,.webm,.ogg" style="display:none;">
                    <div class="forum-error"><?php echo e($errors['video']); ?></div>
                    <div class="forum-form-video-preview" id="backVideoPreviewWrap"><video id="backVideoPreview" controls autoplay muted loop playsinline></video></div>

                    <?php if ($isEditMode && $editPost && !empty($editPost['video'])): ?>
                        <div class="forum-current-video show" id="backCurrentVideo">
                            <video controls autoplay muted loop playsinline><source src="<?php echo e(forumMediaUrl($editPost['video'])); ?>"></video>
                        </div>
                    <?php else: ?>
                        <div class="forum-current-video" id="backCurrentVideo"></div>
                    <?php endif; ?>
                </div>

                <div class="forum-form-actions">
                    <button type="button" class="ghost-btn" id="cancelPostFormModal">Annuler</button>
                    <?php if ($isEditMode): ?>
                        <button type="submit" name="update_post" class="solid-btn">Mettre Ã  jour</button>
                    <?php else: ?>
                        <button type="submit" name="save_post" class="solid-btn">Publier</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="forum-edit-comment-modal" id="editCommentModal">
    <div class="forum-edit-comment-box">
        <div class="forum-edit-comment-head"><h2>Modifier le commentaire</h2><button type="button" class="forum-edit-comment-close" id="closeEditCommentModal">&times;</button></div>
        <div class="forum-edit-comment-body">
            <form method="POST" enctype="multipart/form-data" id="editCommentBackForm">
                <input type="hidden" name="update_comment_back" value="1">
                <input type="hidden" name="comment_id" id="editCommentId" value="">
                <input type="hidden" name="post_id" id="editCommentPostId" value="">
                <textarea name="comment_content" id="editCommentContent" placeholder="Commentaire"></textarea>
                <input type="text" name="comment_emoji" id="editCommentEmoji" placeholder="Emoji">
                <label class="forum-edit-image-label">Changer image
                    <input type="file" name="comment_image" id="editCommentImage" accept=".jpg,.jpeg,.png,.webp,.gif" style="display:none;">
                </label>
                <div class="forum-edit-image-name" id="editCommentImageName"></div>
                <div class="forum-form-actions"><button type="button" class="ghost-btn" id="cancelEditCommentModal">Annuler</button><button type="submit" class="solid-btn">Enregistrer</button><button type="button" class="forum-tool-btn" id="triggerEditCommentEmoji" style="font-size:1.7rem;"></button></div>
            </form>
        </div>
    </div>
</div>


<div class="gif-modal" id="backGifModal">
    <div class="gif-box">
        <div class="gif-header">
            <input type="text" id="backGifSearch" placeholder="Rechercher un GIF...">
            <button type="button" id="closeBackGifModal">&times;</button>
        </div>
        <div class="gif-results" id="backGifResults">
            <div class="gif-empty">Chargement des GIFs...</div>
        </div>
    </div>
</div>

<div class="forum-emoji-picker" id="backEmojiPicker">
    <div class="forum-emoji-head">Choisir un emoji</div>
    <div class="forum-emoji-body" id="backEmojiBody"></div>
</div>

<script>
document.addEventListener("DOMContentLoaded",function(){document.querySelectorAll("textarea, input[type=text]").forEach(function(el){el.setAttribute("spellcheck","false");el.setAttribute("autocomplete","off");});});
const postFormModal = document.getElementById('postFormModal');
const openCreatePostModal = document.getElementById('openCreatePostModal');
const closePostFormModal = document.getElementById('closePostFormModal');
const cancelPostFormModal = document.getElementById('cancelPostFormModal');

const viewPostModal = document.getElementById('viewPostModal');
const closeViewPostModal = document.getElementById('closeViewPostModal');

const triggerBackImage = document.getElementById('triggerBackImage');
const triggerBackVideo = document.getElementById('triggerBackVideo');
const triggerBackEmoji = document.getElementById('triggerBackEmoji');
const triggerBackGif = document.getElementById('triggerBackGif');

const backImageInput = document.getElementById('back_image');
const backVideoInput = document.getElementById('back_video');
const backImagePreviewWrap = document.getElementById('backImagePreviewWrap');
const backImagePreview = document.getElementById('backImagePreview');
const backVideoPreviewWrap = document.getElementById('backVideoPreviewWrap');
const backVideoPreview = document.getElementById('backVideoPreview');
const backCurrentImage = document.getElementById('backCurrentImage');
const backCurrentVideo = document.getElementById('backCurrentVideo');

const backEmojiPicker = document.getElementById('backEmojiPicker');
const backEmojiBody = document.getElementById('backEmojiBody');
const backContenu = document.getElementById('back_contenu');
const backGifModal = document.getElementById('backGifModal');
const backGifSearch = document.getElementById('backGifSearch');
const backGifResults = document.getElementById('backGifResults');
const closeBackGifModal = document.getElementById('closeBackGifModal');
const backGifInput = document.getElementById('back_gif_post');
const backGifPreviewWrap = document.getElementById('backGifPreviewWrap');
const backGifPreview = document.getElementById('backGifPreview');
const clearBackGifBtn = document.getElementById('clearBackGifBtn');
const editCommentModal = document.getElementById('editCommentModal');
const closeEditCommentModal = document.getElementById('closeEditCommentModal');
const cancelEditCommentModal = document.getElementById('cancelEditCommentModal');
const editCommentId = document.getElementById('editCommentId');
const editCommentPostId = document.getElementById('editCommentPostId');
const editCommentContent = document.getElementById('editCommentContent');
const editCommentEmoji = document.getElementById('editCommentEmoji');
const editCommentImage = document.getElementById('editCommentImage');
const editCommentImageName = document.getElementById('editCommentImageName');

let activeEmojiTarget = null;

const emojiList = ['\u{1F600}','\u{1F601}','\u{1F602}','\u{1F603}','\u{1F604}','\u{1F609}','\u{1F60D}','\u{1F44D}','\u{1F44F}','\u{1F64F}','\u2764\uFE0F','\u{1F525}','\u2728','\u{1F389}','\u{1F4AC}','\u{1F4E2}','\u2705','\u26A0\uFE0F','\u{1F3B5}','\u{1F4F7}','\u{1F3A5}'];

function openPostFormModalFn() {
    if (postFormModal) {
        postFormModal.classList.add('show');
        document.body.style.overflow = 'hidden';
    }
}

function closePostFormModalFn() {
    if (postFormModal) {
        postFormModal.classList.remove('show');
        document.body.style.overflow = '';
        window.location.href = "<?php echo e(forumBackUrl()); ?>";
    }
}

if (openCreatePostModal) openCreatePostModal.addEventListener('click', openPostFormModalFn);
if (closePostFormModal) closePostFormModal.addEventListener('click', closePostFormModalFn);
if (cancelPostFormModal) cancelPostFormModal.addEventListener('click', closePostFormModalFn);

if (postFormModal) {
    postFormModal.addEventListener('click', function(e) {
        if (e.target === postFormModal) closePostFormModalFn();
    });
}


function forumExtractUrls(text) {
    const matches = (text || '').match(/https?:\/\/[^\s<>"]+/gi) || [];
    return [...new Set(matches.map(url => url.replace(/[),.;!?]+$/g, '')))];
}

function forumYoutubeEmbed(url) {
    try {
        const u = new URL(url);
        let id = '';
        if (u.hostname.includes('youtu.be')) id = u.pathname.split('/').filter(Boolean)[0] || '';
        if (u.hostname.includes('youtube.com')) {
            if (u.pathname.includes('/shorts/')) id = u.pathname.split('/shorts/')[1]?.split('/')[0] || '';
            if (u.pathname.includes('/embed/')) id = u.pathname.split('/embed/')[1]?.split('/')[0] || '';
            if (!id) id = u.searchParams.get('v') || '';
        }
        return id ? 'https://www.youtube.com/embed/' + encodeURIComponent(id) : '';
    } catch(e) { return ''; }
}

function forumTikTokEmbed(url) {
    const m = String(url).match(/\/video\/(\d+)/);
    return m ? 'https://www.tiktok.com/embed/v2/' + m[1] : '';
}

function forumInstagramEmbed(url) {
    try {
        const u = new URL(url);
        const parts = u.pathname.split('/').filter(Boolean);
        const type = parts[0];
        const code = parts[1];
        if (['p','reel','tv'].includes(type) && code) {
            return 'https://www.instagram.com/' + type + '/' + encodeURIComponent(code) + '/embed';
        }
    } catch(e) {}
    return '';
}

function forumFacebookEmbed(url) {
    if (!/facebook\.com|fb\.watch/i.test(url)) return '';
    return String(url || '').trim();
}

function forumDirectImageUrl(url) {
    const clean = String(url || '').split('?')[0].toLowerCase();
    return /\.(gif|jpg|jpeg|png|webp)$/i.test(clean);
}

function forumTwitterEmbed(url) {
    const m = String(url).match(/(?:twitter\.com|x\.com)\/[^\/]+\/status\/(\d+)/i);
    return m ? 'https://platform.twitter.com/embed/Tweet.html?id=' + encodeURIComponent(m[1]) : '';
}

function forumBuildExternalMedia(url) {
    const clean = String(url || '').trim();
    const safe = clean.replace(/"/g,'&quot;');

    if (forumDirectImageUrl(clean)) {
        return {type:'image-gif', html:'<img src="'+safe+'" alt="MÃƒÂ©dia du post" loading="lazy">'};
    }

    const youtube = forumYoutubeEmbed(clean);
    if (youtube) return {type:'youtube', html:'<iframe src="'+youtube+'" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen loading="lazy"></iframe>'};
    const tiktok = forumTikTokEmbed(clean);
if (tiktok) return {type:'tiktok', html:'<iframe src="'+tiktok+'" allow="encrypted-media" allowfullscreen loading="lazy"></iframe>'};

const facebook = forumFacebookEmbed(clean);
if (facebook) return {
    type:'facebook',
    html:'<iframe src="https://www.facebook.com/plugins/video.php?href='+encodeURIComponent(facebook)+'&show_text=false&width=700" width="100%" height="500" style="border:none;overflow:hidden;border-radius:18px;" scrolling="no" frameborder="0" allowfullscreen="true" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"></iframe>'
};

const instagram = forumInstagramEmbed(clean);
if (instagram) return {type:'instagram', html:'<iframe src="'+instagram+'" allowtransparency="true" allowfullscreen loading="lazy"></iframe>'};
    const twitter = forumTwitterEmbed(clean);
    if (twitter) return {type:'twitter', html:'<iframe src="'+twitter+'" allowfullscreen loading="lazy"></iframe>'};
    return {type:'link', html:'<a class="forum-view-external-link" href="'+safe+'" target="_blank" rel="noopener">Open external link <span>-></span></a>'};
}

function forumRenderExternalMedia(text) {
    const list = document.getElementById('viewPostExternalList');
    if (!list) return;
    list.innerHTML = '';
    forumExtractUrls(text).forEach(url => {
        const media = forumBuildExternalMedia(url);
        const box = document.createElement('div');
        box.className = 'forum-view-external ' + media.type;
        box.innerHTML = media.html;
        list.appendChild(box);
    });
}

function forumRenderExternalMediaInto(listId, text) {
    const list = document.getElementById(listId);
    if (!list) return;
    list.innerHTML = '';
    forumExtractUrls(text).forEach(url => {
        const media = forumBuildExternalMedia(url);
        const box = document.createElement('div');
        box.className = 'forum-view-external ' + media.type;
        box.innerHTML = media.html;
        list.appendChild(box);
    });
}

function forumRenderOriginalPost(raw) {
    const card = document.getElementById('viewOriginalPostCard');
    if (!card) return;
    let data = {};
    try { data = raw ? JSON.parse(raw) : {}; } catch(e) { data = {}; }
    if (!data || !data.title) {
        card.classList.remove('show');
        return;
    }
    card.classList.add('show');
    document.getElementById('viewOriginalMeta').textContent = (data.author || '') + (data.type ? ' - ' + data.type : '') + (data.status ? ' - ' + data.status : '');
    document.getElementById('viewOriginalTitle').textContent = data.title || 'Post original';
    document.getElementById('viewOriginalContent').textContent = data.content || '';
    document.getElementById('viewOriginalEmoji').textContent = data.emoji || '';

    const imgWrap = document.getElementById('viewOriginalImageWrap');
    const img = document.getElementById('viewOriginalImage');
    const vidWrap = document.getElementById('viewOriginalVideoWrap');
    const vid = document.getElementById('viewOriginalVideo');
    imgWrap.style.display = 'none';
    vidWrap.style.display = 'none';
    img.src = '';
    vid.src = '';

    if (data.image) {
        img.src = data.image;
        imgWrap.style.display = 'block';
    }
    if (data.video) {
        vid.src = data.video;
        vidWrap.style.display = 'block';
    }
    forumRenderExternalMediaInto('viewOriginalExternalList', (data.content || '') + '\n' + (data.gif || ''));
}

function openForumPostPreview(source) {
    if (!source || !viewPostModal) return;
    const postId = source.dataset.postId || '';
    document.getElementById('viewPostMeta').textContent = (source.dataset.author || '') + ' - ' + (source.dataset.type || '') + ' - ' + (source.dataset.status || '');
    document.getElementById('viewPostTitle').textContent = source.dataset.title || '';
    document.getElementById('viewPostContent').textContent = source.dataset.content || '';
    forumRenderExternalMedia((source.dataset.content || '') + '\n' + (source.dataset.gif || ''));
    forumRenderOriginalPost(source.dataset.original || '');

    const viewEmoji = document.getElementById('viewPostEmoji');
    if (viewEmoji) viewEmoji.textContent = source.dataset.emoji || '';

    const imgWrap = document.getElementById('viewPostImageWrap');
    const img = document.getElementById('viewPostImage');
    const vidWrap = document.getElementById('viewPostVideoWrap');
    const vid = document.getElementById('viewPostVideo');
    imgWrap.style.display = 'none';
    vidWrap.style.display = 'none';
    img.src = '';
    vid.src = '';

    if (source.dataset.image) {
        img.src = source.dataset.image;
        imgWrap.style.display = 'block';
    }
    if (source.dataset.video) {
        vid.src = source.dataset.video;
        vidWrap.style.display = 'block';
    }

    ['viewApprovePostId','viewRejectPostId','viewDeletePostId'].forEach(function(id){
        const el = document.getElementById(id);
        if (el) el.value = postId;
    });
    const editLink = document.getElementById('viewEditPostLink');
    if (editLink) editLink.href = postId ? ('<?php echo e(forumBackUrl()); ?>' + '&edit=' + encodeURIComponent(postId)) : '#';

    viewPostModal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

document.querySelectorAll('.open-view-post').forEach(btn => {
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        openForumPostPreview(this);
    });
});

function closeViewModalFn() {
    if (viewPostModal) {
        viewPostModal.classList.remove('show');
        if (!postFormModal.classList.contains('show')) {
            document.body.style.overflow = '';
        }
    }
}

if (closeViewPostModal) closeViewPostModal.addEventListener('click', closeViewModalFn);

if (viewPostModal) {
    viewPostModal.addEventListener('click', function(e) {
        if (e.target === viewPostModal) closeViewModalFn();
    });
}


document.querySelectorAll('.open-comments-modal').forEach(btn => {
    btn.addEventListener('click', function() {
        const modal = document.getElementById('comments-modal-' + this.dataset.postId);
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
        }
    });
});

document.querySelectorAll('.close-comments-modal').forEach(btn => {
    btn.addEventListener('click', function() {
        const modal = document.getElementById('comments-modal-' + this.dataset.postId);
        if (modal) {
            modal.classList.remove('show');
            if (!postFormModal.classList.contains('show') && !viewPostModal.classList.contains('show')) {
                document.body.style.overflow = '';
            }
        }
    });
});

document.querySelectorAll('.comments-post-modal').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.remove('show');
            if (!postFormModal.classList.contains('show') && !viewPostModal.classList.contains('show')) {
                document.body.style.overflow = '';
            }
        }
    });
});

document.querySelectorAll('.open-edit-comment-modal').forEach(btn => {
    btn.addEventListener('click', function() {
        if (!editCommentModal) return;
        editCommentId.value = this.dataset.commentId || '';
        editCommentPostId.value = this.dataset.postId || '';
        editCommentContent.value = this.dataset.content || '';
        editCommentEmoji.value = this.dataset.emoji || '';
        if (editCommentImage) editCommentImage.value = '';
        if (editCommentImageName) editCommentImageName.textContent = '';
        editCommentModal.classList.add('show');
        document.body.style.overflow = 'hidden';
        setTimeout(() => editCommentContent.focus(), 100);
    });
});
function closeEditCommentModalFn() {
    if (!editCommentModal) return;
    editCommentModal.classList.remove('show');
    if (backEmojiPicker) backEmojiPicker.classList.remove('show');
    if (!postFormModal.classList.contains('show') && !viewPostModal.classList.contains('show')) document.body.style.overflow = '';
}
if (closeEditCommentModal) closeEditCommentModal.addEventListener('click', closeEditCommentModalFn);
if (cancelEditCommentModal) cancelEditCommentModal.addEventListener('click', closeEditCommentModalFn);
if (editCommentModal) editCommentModal.addEventListener('click', e => { if (e.target === editCommentModal) closeEditCommentModalFn(); });
if (editCommentImage) {
    editCommentImage.addEventListener('change', function() {
        if (editCommentImageName) {
            editCommentImageName.textContent = this.files && this.files[0] ? 'Image sÃ©lectionnÃ©e : ' + this.files[0].name : '';
        }
    });
}

if (document.getElementById('editCommentBackForm')) document.getElementById('editCommentBackForm').addEventListener('submit', function(e){ const txt=editCommentContent.value.trim(); if(txt==='' || txt.replace(/[^a-zA-ZÃƒâ‚¬-ÃƒÂ¿]/gu,'').length<2){e.preventDefault(); alert('Le commentaire doit contenir au moins 2 lettres.');}});


const reportDetailModal = document.getElementById('reportDetailModal');
const closeReportDetailModal = document.getElementById('closeReportDetailModal');

function openReportDetailModal(data) {
    if (!reportDetailModal) return;
    document.getElementById('reportDetailTitle').textContent = data.title || 'DÃ©tails du signalement';
    document.getElementById('reportDetailTarget').textContent = data.target || '-';
    document.getElementById('reportDetailType').textContent = data.type || '-';
    document.getElementById('reportDetailReason').textContent = data.reason || '-';
    document.getElementById('reportDetailUser').textContent = data.user || '-';
    document.getElementById('reportDetailDate').textContent = data.date || '-';
    document.getElementById('reportDetailCount').textContent = data.count ? (data.count + ' signalement(s)') : '1 signalement';
    reportDetailModal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeReportDetailModalFn() {
    if (!reportDetailModal) return;
    reportDetailModal.classList.remove('show');
    if (!postFormModal.classList.contains('show') && !viewPostModal.classList.contains('show') && !document.querySelector('.comments-post-modal.show')) {
        document.body.style.overflow = '';
    }
}

document.querySelectorAll('.open-report-detail').forEach(btn => {
    btn.addEventListener('click', function() {
        try { openReportDetailModal(JSON.parse(this.dataset.report || '{}')); }
        catch(e) { openReportDetailModal({title:'DÃ©tails du signalement'}); }
    });
});

if (closeReportDetailModal) closeReportDetailModal.addEventListener('click', closeReportDetailModalFn);
if (reportDetailModal) reportDetailModal.addEventListener('click', function(e){ if (e.target === reportDetailModal) closeReportDetailModalFn(); });

const triggerEditCommentEmoji = document.getElementById('triggerEditCommentEmoji');
if (triggerEditCommentEmoji) {
    triggerEditCommentEmoji.addEventListener('click', function(e) {
        e.stopPropagation();
        activeEmojiTarget = document.getElementById('editCommentEmoji');
        renderBackEmojis();
        const rect = e.currentTarget.getBoundingClientRect();
        let left = Math.min(rect.left, window.innerWidth - 340);
        if (left < 12) left = 12;
        let top = rect.bottom + 10;
        if (top + 360 > window.innerHeight) top = Math.max(12, rect.top - 340);
        backEmojiPicker.style.top = top + 'px';
        backEmojiPicker.style.left = left + 'px';
        backEmojiPicker.classList.add('show');
    });
}

if (triggerBackImage) triggerBackImage.addEventListener('click', () => backImageInput.click());
if (triggerBackVideo) triggerBackVideo.addEventListener('click', () => backVideoInput.click());

if (triggerBackEmoji) {
    triggerBackEmoji.addEventListener('click', function(e) {
        activeEmojiTarget = document.getElementById('back_emoji_post');
        renderBackEmojis();

        const rect = e.currentTarget.getBoundingClientRect();
        backEmojiPicker.style.top = (rect.bottom + 10) + 'px';
        backEmojiPicker.style.left = rect.left + 'px';
        backEmojiPicker.classList.add('show');
    });
}

function renderBackEmojis() {
    if (!backEmojiBody) return;
    backEmojiBody.innerHTML = '';

    emojiList.forEach(emoji => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'forum-emoji-btn';
        btn.textContent = emoji;
        btn.addEventListener('click', function() {
            if (!activeEmojiTarget) return;

            const start = activeEmojiTarget.selectionStart ?? activeEmojiTarget.value.length;
            const end = activeEmojiTarget.selectionEnd ?? activeEmojiTarget.value.length;
            const text = activeEmojiTarget.value;

            activeEmojiTarget.value = text.substring(0, start) + emoji + text.substring(end);
            activeEmojiTarget.focus();
            activeEmojiTarget.selectionStart = activeEmojiTarget.selectionEnd = start + emoji.length;
        });

        backEmojiBody.appendChild(btn);
    });
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('#backEmojiPicker') && !e.target.closest('#triggerBackEmoji') && !e.target.closest('#triggerEditCommentEmoji')) {
        backEmojiPicker.classList.remove('show');
    }
});

if (backImageInput) {
    backImageInput.addEventListener('change', function() {
        const file = this.files[0];
        if (!file) {
            backImagePreviewWrap.classList.remove('show');
            backImagePreview.src = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(ev) {
            backImagePreview.src = ev.target.result;
            backImagePreviewWrap.classList.add('show');
            if (backCurrentImage) backCurrentImage.classList.remove('show');
        };
        reader.readAsDataURL(file);
    });
}

if (backVideoInput) {
    backVideoInput.addEventListener('change', function() {
        const file = this.files[0];
        if (!file) {
            backVideoPreviewWrap.classList.remove('show');
            backVideoPreview.src = '';
            return;
        }

        const url = URL.createObjectURL(file);
        backVideoPreview.src = url;
        backVideoPreview.load();
        backVideoPreview.play().catch(() => {});
        backVideoPreviewWrap.classList.add('show');
        if (backCurrentVideo) backCurrentVideo.classList.remove('show');
    });
}


/* ============================
   GIF PICKER BACK OFFICE - meme style que front
   ============================ */
const BACK_GIPHY_API_KEY = 'J8Isus6qXyh0ajM2mYYmHvKF5IZhlorl';
let backGifSearchTimer = null;
function openBackGifModal(){
    if(!backGifModal) return;
    backGifModal.classList.add('show');
    document.body.style.overflow = 'hidden';
    loadBackTrendingGifs();
    setTimeout(() => { if(backGifSearch) backGifSearch.focus(); }, 80);
}
function closeBackGifModalFn(){
    if(!backGifModal) return;
    backGifModal.classList.remove('show');
    if(!postFormModal.classList.contains('show') && !viewPostModal.classList.contains('show')) document.body.style.overflow = '';
}
function setBackGifMessage(msg){ if(backGifResults) backGifResults.innerHTML = '<div class="gif-empty">'+msg+'</div>'; }
async function loadBackTrendingGifs(){
    if(!backGifResults) return;
    setBackGifMessage('Chargement des GIFs...');
    try{
        const r = await fetch('https://api.giphy.com/v1/gifs/trending?api_key='+encodeURIComponent(BACK_GIPHY_API_KEY)+'&limit=24&rating=g');
        const d = await r.json();
        renderBackGifs(d.data || []);
    }catch(e){ setBackGifMessage('Impossible de charger les GIFs.'); }
}
async function searchBackGifs(q){
    if(!q || q.trim().length < 2){ loadBackTrendingGifs(); return; }
    setBackGifMessage('Recherche...');
    try{
        const r = await fetch('https://api.giphy.com/v1/gifs/search?api_key='+encodeURIComponent(BACK_GIPHY_API_KEY)+'&q='+encodeURIComponent(q)+'&limit=24&rating=g&lang=fr');
        const d = await r.json();
        renderBackGifs(d.data || []);
    }catch(e){ setBackGifMessage('Recherche GIF impossible.'); }
}
function renderBackGifs(gifs){
    if(!backGifResults) return;
    backGifResults.innerHTML = '';
    if(!gifs.length){ setBackGifMessage('Aucun GIF trouvÃ©.'); return; }
    gifs.forEach(gif => {
        const fixed = (gif.images && gif.images.fixed_height && gif.images.fixed_height.url) || '';
        const original = (gif.images && gif.images.original && gif.images.original.url) || fixed;
        if(!fixed || !original) return;
        const img = document.createElement('img');
        img.src = fixed;
        img.alt = gif.title || 'GIF';
        img.loading = 'lazy';
        img.addEventListener('click', () => selectBackGif(original));
        backGifResults.appendChild(img);
    });
}
function selectBackGif(url){
    if(backGifInput) backGifInput.value = url;
    if(backGifPreview) backGifPreview.src = url;
    if(backGifPreviewWrap) backGifPreviewWrap.classList.add('show');
    closeBackGifModalFn();
}
function clearBackGif(){
    if(backGifInput) backGifInput.value = '';
    if(backGifPreview) backGifPreview.src = '';
    if(backGifPreviewWrap) backGifPreviewWrap.classList.remove('show');
}
if(triggerBackGif) triggerBackGif.addEventListener('click', openBackGifModal);
if(closeBackGifModal) closeBackGifModal.addEventListener('click', closeBackGifModalFn);
if(backGifModal) backGifModal.addEventListener('click', e => { if(e.target === backGifModal) closeBackGifModalFn(); });
if(backGifSearch) backGifSearch.addEventListener('input', () => { clearTimeout(backGifSearchTimer); backGifSearchTimer = setTimeout(() => searchBackGifs(backGifSearch.value), 350); });
if(clearBackGifBtn) clearBackGifBtn.addEventListener('click', clearBackGif);

const forumBackPostForm = document.getElementById('forumBackPostForm');
const backTitreField = document.getElementById('back_titre');
const backTypePostField = document.getElementById('back_type_post');
const backStatutPostField = document.getElementById('back_statut_post');
const backContenuField = document.getElementById('back_contenu');

function getLettersAndSpacesCountJS(text) {
    const cleaned = text.replace(/[^a-zA-ZÃƒâ‚¬-ÃƒÂ¿\s]/gu, '');
    return cleaned.trim().length;
}

function hasOnlyLettersAndSpaces(text) {
    return /^[a-zA-ZÃƒâ‚¬-ÃƒÂ¿\s]*$/.test(text);
}

const backRules = {
    back_titre: {
        validate: value => hasOnlyLettersAndSpaces(value) && getLettersAndSpacesCountJS(value) >= 3,
        message: 'Titre valide.',
        error: 'Le titre doit contenir au moins 3 caractÃƒÂ¨res.'
    },
    back_type_post: {
        validate: value => value !== '',
        message: 'Type valide.',
        error: 'Veuillez choisir le type du post.'
    },
    back_statut_post: {
        validate: value => value !== '',
        message: 'Statut valide.',
        error: 'Veuillez choisir le statut.'
    },
    back_contenu: {
        validate: value => getLettersAndSpacesCountJS(value) >= 5,
        message: 'Description valide.',
        error: 'La description doit contenir au moins 5 lettres. Les liens externes sont acceptÃƒÂ©s.'
    }
};

function backSetError(field, message) {
    field.classList.add('field-invalid');
    field.classList.remove('field-valid-input');
    const errorBox = field.parentElement.querySelector('.forum-error');
    if (errorBox) {
        errorBox.textContent = message;
        errorBox.style.color = '#dc2626';
    }
}

function backSetValid(field, message) {
    field.classList.remove('field-invalid');
    field.classList.add('field-valid-input');
    const errorBox = field.parentElement.querySelector('.forum-error');
    if (errorBox) {
        errorBox.textContent = message;
        errorBox.style.color = '#22a559';
    }
}

function backValidateField(field) {
    const rule = backRules[field.id];
    if (!rule) return true;

    const value = field.value.trim();

    if (value === '') {
        backSetError(field, rule.error);
        return false;
    }

    if (!rule.validate(field.value)) {
        backSetError(field, rule.error);
        return false;
    }

    backSetValid(field, rule.message);
    return true;
}

if (backTitreField) {
    backTitreField.addEventListener('input', () => backValidateField(backTitreField));
    backTitreField.addEventListener('change', () => backValidateField(backTitreField));
    backTitreField.addEventListener('blur', () => backValidateField(backTitreField));
}

if (backTypePostField) {
    backTypePostField.addEventListener('input', () => backValidateField(backTypePostField));
    backTypePostField.addEventListener('change', () => backValidateField(backTypePostField));
    backTypePostField.addEventListener('blur', () => backValidateField(backTypePostField));
}

if (backStatutPostField) {
    backStatutPostField.addEventListener('input', () => backValidateField(backStatutPostField));
    backStatutPostField.addEventListener('change', () => backValidateField(backStatutPostField));
    backStatutPostField.addEventListener('blur', () => backValidateField(backStatutPostField));
}

if (backContenuField) {
    backContenuField.addEventListener('input', () => backValidateField(backContenuField));
    backContenuField.addEventListener('change', () => backValidateField(backContenuField));
    backContenuField.addEventListener('blur', () => backValidateField(backContenuField));
}

if (forumBackPostForm) {
    forumBackPostForm.addEventListener('submit', function(e) {
        let isValid = true;

        if (backTitreField && !backValidateField(backTitreField)) isValid = false;
        if (backTypePostField && !backValidateField(backTypePostField)) isValid = false;
        if (backStatutPostField && !backValidateField(backStatutPostField)) isValid = false;
        if (backContenuField && !backValidateField(backContenuField)) isValid = false;

        if (isValid) {
            setTimeout(() => {
                if (postFormModal) postFormModal.classList.remove('show');
            }, 100);
        } else {
            e.preventDefault();
            openPostFormModalFn();
        }
    });
}
/* ============================
   MENU 3 POINTS Ã¢â‚¬â€ FIXED
   ============================ */

// On first use, teleport all .forum-row-dropdown elements to <body>
// so they are never clipped by overflow:auto on the table wrapper.
(function teleportDropdowns() {
    function doTeleport() {
        document.querySelectorAll('.forum-row-dropdown').forEach(function (el) {
            if (el.parentElement !== document.body) {
                document.body.appendChild(el);
            }
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', doTeleport);
    } else {
        doTeleport();
    }
})();

function closeAllForumRowDropdowns() {
    document.querySelectorAll('.forum-row-dropdown').forEach(function (menu) {
        menu.style.display = 'none';
    });
    document.querySelectorAll('.forum-row-menu-btn.active').forEach(function (btn) {
        btn.classList.remove('active');
    });
}

function toggleForumRowDropdown(button, event) {
    event.preventDefault();
    event.stopPropagation();

    var menuId   = button.getAttribute('data-menu-target');
    var dropdown = menuId ? document.getElementById(menuId) : null;

    if (!dropdown) return false;

    var isOpen = dropdown.style.display === 'block';

    closeAllForumRowDropdowns();

    if (isOpen) return false;

    // Position with fixed coords from button's viewport rect
    var rect          = button.getBoundingClientRect();
    var dropdownWidth = 210;

    dropdown.style.display = 'block';

    var dropdownHeight = dropdown.offsetHeight || 200;

    var left = rect.right - dropdownWidth;
    var top  = rect.bottom + 6;

    if (left < 8) left = 8;
    if (left + dropdownWidth > window.innerWidth - 8) {
        left = window.innerWidth - dropdownWidth - 8;
    }
    if (top + dropdownHeight > window.innerHeight - 8) {
        top = rect.top - dropdownHeight - 6;
    }
    if (top < 8) top = 8;

    dropdown.style.left = left + 'px';
    dropdown.style.top  = top + 'px';

    button.classList.add('active');
    return false;
}

// Close on any outside click
document.addEventListener('click', function (e) {
    if (!e.target.closest('.forum-row-dropdown') &&
        !e.target.closest('.forum-row-menu-btn')) {
        closeAllForumRowDropdowns();
    }
}, true); // useCapture:true so it fires even if a child called stopPropagation



(function forumAdminClickFallback(){
    document.addEventListener('click', function(e){
        const menuButton = e.target.closest('.forum-row-menu-btn');
        if (menuButton) {
            e.preventDefault();
            e.stopPropagation();
            toggleForumRowDropdown(menuButton, e);
            return;
        }
        const preview = e.target.closest('.open-view-post');
        if (preview && !e.target.closest('button,a,form,input,select,textarea,.forum-row-dropdown')) {
            e.preventDefault();
            openForumPostPreview(preview);
        }
    }, false);
})();
/* ============================
   STATS MODAL BACK OFFICE
   ============================ */
const forumStatsModal = document.getElementById('forumStatsModal');
const openForumStatsModal = document.getElementById('openForumStatsModal');
const closeForumStatsModal = document.getElementById('closeForumStatsModal');
function openForumStatsModalFn(){
    if(!forumStatsModal) return;
    forumStatsModal.classList.add('show');
    document.body.style.overflow='hidden';
}
function closeForumStatsModalFn(){
    if(!forumStatsModal) return;
    forumStatsModal.classList.remove('show');
    if(!document.querySelector('.forum-post-modal.show') && !document.querySelector('.forum-form-modal.show') && !document.querySelector('.forum-edit-comment-modal.show')){
        document.body.style.overflow='';
    }
}
if(openForumStatsModal) openForumStatsModal.addEventListener('click', openForumStatsModalFn);
if(closeForumStatsModal) closeForumStatsModal.addEventListener('click', closeForumStatsModalFn);
if(forumStatsModal){
    forumStatsModal.addEventListener('click', function(e){
        if(e.target === forumStatsModal) closeForumStatsModalFn();
    });
}




</script>




<style>
.forum-pagination{display:flex;gap:6px;align-items:center;margin-top:16px;justify-content:flex-start;}
.forum-page-link{width:32px!important;height:32px!important;min-width:32px!important;border-radius:11px!important;font-size:13px!important;padding:0!important;display:inline-flex!important;align-items:center!important;justify-content:center!important;text-decoration:none!important;font-weight:900!important;}
.forum-page-link.active{background:linear-gradient(135deg,#EE5828 0%,#1f3144 65%,#4CAF50 100%)!important;color:#fff!important;}
.forum-print-only{display:none;}
@media print{
    @page{size:landscape;margin:12mm;}
    html,body{background:#fff!important;background-image:none!important;-webkit-print-color-adjust:exact!important;print-color-adjust:exact!important;}
    body *{visibility:hidden!important;}
    #forumPrintOnly,#forumPrintOnly *{visibility:visible!important;}
    #forumPrintOnly{display:block!important;position:absolute!important;left:0!important;top:0!important;width:100%!important;background:#fff!important;background-image:none!important;color:#142738!important;padding:0!important;font-family:Arial,DejaVu Sans,sans-serif!important;}
    #forumPrintOnly .print-head{display:flex!important;align-items:center!important;justify-content:space-between!important;border-bottom:3px solid #EE5828!important;padding-bottom:14px!important;margin-bottom:22px!important;background:#fff!important;background-image:none!important;}
    #forumPrintOnly .print-brand{display:flex!important;align-items:center!important;gap:18px!important;}
    #forumPrintOnly .print-brand img{width:220px!important;height:auto!important;display:block!important;}
    #forumPrintOnly .print-title{font-size:28px!important;font-weight:900!important;color:#142738!important;margin:0!important;}
    #forumPrintOnly .print-meta{text-align:right!important;color:#607089!important;font-size:13px!important;line-height:1.6!important;font-weight:700!important;}
    #forumPrintOnly table{width:100%!important;border-collapse:collapse!important;font-size:12px!important;background:#fff!important;background-image:none!important;}
    #forumPrintOnly thead,#forumPrintOnly tbody,#forumPrintOnly tr,#forumPrintOnly th,#forumPrintOnly td{box-shadow:none!important;background-image:none!important;}
    #forumPrintOnly th{background:#142738!important;color:#fff!important;text-align:left!important;padding:11px 10px!important;font-weight:900!important;}
    #forumPrintOnly td{border-bottom:1px solid #dfe6ee!important;padding:10px!important;vertical-align:top!important;color:#142738!important;background:#fff!important;}
    #forumPrintOnly td strong{font-weight:900!important;color:#142738!important;}
    #forumPrintOnly td span{color:#607089!important;font-size:11px!important;line-height:1.45!important;}
    #forumPrintOnly a,#forumPrintOnly a:visited{color:#142738!important;text-decoration:none!important;}
    #forumPrintOnly a[href]::after{content:""!important;}
}
</style>
<script>
function exportForumTableOnly(){
    const exportTable = document.getElementById('forumExportFullTable') || document.getElementById('forumExportTable');
    if(!exportTable){
        alert('Tableau introuvable.');
        return;
    }

    const oldPrint = document.getElementById('forumPrintOnly');
    if(oldPrint) oldPrint.remove();

    const clonedTable = exportTable.cloneNode(true);
    clonedTable.removeAttribute('style');
    clonedTable.id = 'forumPdfTable';
    clonedTable.querySelectorAll('.forum-actions-head,.forum-actions-cell,button,a').forEach(el => el.remove());

    const now = new Date().toLocaleString('fr-FR');

    const printBox = document.createElement('div');
    printBox.id = 'forumPrintOnly';
    printBox.className = 'forum-print-only';
    printBox.innerHTML = `
        <div class="print-head">
            <div class="print-brand">
                <img src="<?php echo e(forumBackAppRoot() . '/assets/images/logo.png'); ?>" alt="GoService">
                <div>
                    <h1 class="print-title">ModÃ©ration du forum</h1>
                </div>
            </div>
            <div class="print-meta">${now}</div>
        </div>
        ${clonedTable.outerHTML}
    `;
    document.body.appendChild(printBox);

    setTimeout(() => {
        window.print();
    }, 120);
}
</script>





<script>
document.addEventListener('DOMContentLoaded', function () {
    ['.back-ig-lang-zone', '#google_translate_element', '.goog-te-banner-frame', '.goog-te-balloon-frame', 'iframe.skiptranslate', '.skiptranslate', '.goog-te-gadget'].forEach(function (selector) {
        document.querySelectorAll(selector).forEach(function (node) { node.remove(); });
    });
});
</script>
<script id="finalForumStatsBinder">
document.addEventListener('DOMContentLoaded', function () {
  const modal = document.getElementById('forumStatsModal');
  const open = document.getElementById('openForumStatsModal');
  const close = document.getElementById('closeForumStatsModal');
  if (open && modal) open.addEventListener('click', function (event) { event.preventDefault(); modal.classList.add('show'); document.body.style.overflow = 'hidden'; });
  if (close && modal) close.addEventListener('click', function () { modal.classList.remove('show'); document.body.style.overflow = ''; });
  if (modal) modal.addEventListener('click', function (event) { if (event.target === modal) { modal.classList.remove('show'); document.body.style.overflow = ''; } });
});
</script>

