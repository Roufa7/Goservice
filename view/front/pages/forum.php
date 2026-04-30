<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Africa/Tunis');

require_once __DIR__ . '/../../../controller/PostController.php';
require_once __DIR__ . '/../../../controller/CommentController.php';
require_once __DIR__ . '/../../../controller/LikeController.php';
require_once __DIR__ . '/../../../controller/ShareController.php';
require_once __DIR__ . '/../../../controller/ReportController.php';
require_once __DIR__ . '/../../../controller/SaveController.php';
require_once __DIR__ . '/../../../model/Post.php';
require_once __DIR__ . '/../../../model/Save.php';
require_once __DIR__ . '/../../../model/Comment.php';
require_once __DIR__ . '/../../../model/Like.php';
require_once __DIR__ . '/../../../model/Share.php';
require_once __DIR__ . '/../../../model/Report.php';

$postController = new PostController();
$commentController = new CommentController();
$likeController = new LikeController();
$shareController = new ShareController();
$reportController = new ReportController();
$saveController = new SaveController();

$currentUserId = 1;
$currentUserName = 'emma jlassi';
$currentUserAvatarLetter = 'E';

$errors = [
    'titre' => '',
    'type_post' => '',
    'contenu' => '',
    'emoji_post' => '',
    'image' => '',
    'video' => '',
    'comment' => ''
];

$old = [
    'titre' => '',
    'type_post' => '',
    'statut_post' => 'En attente',
    'contenu' => '',
    'emoji_post' => ''
];

$isEditMode = false;
$editId = null;
$editPost = null;

$search = trim($_GET['search'] ?? '');
$filter = trim($_GET['filter'] ?? 'Tous');
$sort   = trim($_GET['sort'] ?? 'recent');

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
    $cleaned = preg_replace('/[^a-zA-ZÀ-ÿ]/u', '', $text);
    return mb_strlen($cleaned);
}

function typeBadgeClass($type)
{
    switch (mb_strtolower($type)) {
        case 'question':
            return 'type-question';
        case 'conseil':
            return 'type-conseil';
        default:
            return 'type-discussion';
    }
}

function timeAgo($datetime)
{
    if (empty($datetime)) return '';

    try {
        $now = new DateTime('now', new DateTimeZone('Africa/Tunis'));
        $date = new DateTime($datetime, new DateTimeZone('Africa/Tunis'));
        $diff = $now->getTimestamp() - $date->getTimestamp();

        if ($diff <= 0) return 'à l\'instant';
        if ($diff < 60) return 'à l\'instant';
        if ($diff < 3600) return floor($diff / 60) . ' min ago';
        if ($diff < 86400) return floor($diff / 3600) . ' h ago';
        if ($diff < 604800) return floor($diff / 86400) . ' day ago';
        if ($diff < 2592000) return floor($diff / 604800) . ' week ago';
        return floor($diff / 2592000) . ' month ago';
    } catch (Exception $e) {
        return '';
    }
}

function dateOnly($datetime)
{
    if (empty($datetime)) return '';
    $timestamp = strtotime($datetime);
    if (!$timestamp) return '';
    return date('Y-m-d', $timestamp);
}

function countHtml(int $count): string
{
    return $count > 0 ? '<span class="reaction-count">' . (int)$count . '</span>' : '';
}

function forumUrl(array $extra = []): string
{
    $allowed = ['page', 'search', 'filter', 'sort'];
    $base = ['page' => 'forum'];

    foreach ($allowed as $key) {
        if (isset($_GET[$key])) {
            $base[$key] = $_GET[$key];
        }
    }

    $params = array_merge($base, $extra);
    return '/GoService/view/front/index.php?' . http_build_query($params);
}

function uploadImageFile(array $file, array &$errors, ?string $oldPath = null): ?string
{
    if (empty($file['name'])) return null;

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions)) {
        $errors['image'] = 'Formats image autorisés : JPG, JPEG, PNG, WEBP.';
        return null;
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        $errors['image'] = "L'image ne doit pas dépasser 5 Mo.";
        return null;
    }

    $uploadDir = __DIR__ . '/../../../uploads/posts/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $newName = uniqid('post_img_', true) . '.' . $extension;
    $destination = $uploadDir . $newName;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        if (!empty($oldPath)) {
            $oldFile = __DIR__ . '/../../../' . ltrim($oldPath, '/');
            if (file_exists($oldFile)) @unlink($oldFile);
        }
        return 'uploads/posts/' . $newName;
    }

    $errors['image'] = "Erreur lors de l'upload de l'image.";
    return null;
}

function uploadVideoFile(array $file, array &$errors, ?string $oldPath = null): ?string
{
    if (empty($file['name'])) return null;

    $allowedExtensions = ['mp4', 'webm', 'ogg'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions)) {
        $errors['video'] = 'Formats vidéo autorisés : MP4, WEBM, OGG.';
        return null;
    }

    if ($file['size'] > 25 * 1024 * 1024) {
        $errors['video'] = "La vidéo ne doit pas dépasser 25 Mo.";
        return null;
    }

    $uploadDir = __DIR__ . '/../../../uploads/posts/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $newName = uniqid('post_vid_', true) . '.' . $extension;
    $destination = $uploadDir . $newName;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        if (!empty($oldPath)) {
            $oldFile = __DIR__ . '/../../../' . ltrim($oldPath, '/');
            if (file_exists($oldFile)) @unlink($oldFile);
        }
        return 'uploads/posts/' . $newName;
    }

    $errors['video'] = "Erreur lors de l'upload de la vidéo.";
    return null;
}


function uploadCommentImageFile(array $file, array &$errors, ?string $oldPath = null): ?string
{
    if (empty($file['name'])) return null;

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions, true)) {
        $errors['comment'] = 'Formats image autorisés : JPG, JPEG, PNG, WEBP.';
        return null;
    }

    if ($file['size'] > 3 * 1024 * 1024) {
        $errors['comment'] = "L'image du commentaire ne doit pas dépasser 3 Mo.";
        return null;
    }

    $uploadDir = __DIR__ . '/../../../uploads/comments/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $newName = uniqid('comment_img_', true) . '.' . $extension;
    $destination = $uploadDir . $newName;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        if (!empty($oldPath)) {
            $oldFile = __DIR__ . '/../../../' . ltrim($oldPath, '/');
            if (file_exists($oldFile)) @unlink($oldFile);
        }
        return 'uploads/comments/' . $newName;
    }

    $errors['comment'] = "Erreur lors de l'upload de l'image du commentaire.";
    return null;
}

function getCommentByIdForum(int $commentId): ?array
{
    if (!class_exists('config')) return null;
    $db = config::getConnexion();
    $sql = "SELECT * FROM commentaire WHERE id_commentaire = :id";
    $query = $db->prepare($sql);
    $query->execute(['id' => $commentId]);
    $row = $query->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function updateCommentForum(int $commentId, string $content, ?string $imagePath, string $emojiContent): bool
{
    if (!class_exists('config')) return false;
    $db = config::getConnexion();
    $sql = "UPDATE commentaire
            SET contenu_commentaire = :contenu,
                image_commentaire = :image,
                emoji_commentaire = :emoji
            WHERE id_commentaire = :id";
    $query = $db->prepare($sql);
    return $query->execute([
        'contenu' => $content,
        'image' => $imagePath,
        'emoji' => $emojiContent,
        'id' => $commentId
    ]);
}


function forumFrontColumnExists(PDO $db, string $table, string $column): bool
{
    try {
        $stmt = $db->prepare("SHOW COLUMNS FROM `$table` LIKE :col");
        $stmt->execute(["col" => $column]);
        return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable $e) { return false; }
}

function ensureReportCommentTableFront(): bool
{
    if (!class_exists("config")) return false;
    $db = config::getConnexion();
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

function insertCommentReportForumFront(int $commentId, int $postId, int $userId, string $reason, string $details): bool
{
    if ($commentId <= 0 || $reason === "" || !ensureReportCommentTableFront()) return false;
    $db = config::getConnexion();
    try {
        $hasDetails = forumFrontColumnExists($db, "report_comment", "details");
        $hasPostId  = forumFrontColumnExists($db, "report_comment", "id_post");
        if (!$hasDetails && $details !== "") { $reason = $reason . ": " . $details; }
        $columns = ["id_commentaire", "id_user", "reason"];
        $values  = [":id_commentaire", ":id_user", ":reason"];
        $params  = ["id_commentaire" => $commentId, "id_user" => $userId, "reason" => $reason];
        if ($hasPostId) { $columns[] = "id_post"; $values[] = ":id_post"; $params["id_post"] = $postId; }
        if ($hasDetails) { $columns[] = "details"; $values[] = ":details"; $params["details"] = $details; }
        $sql = "INSERT INTO report_comment (`" . implode("`,`", $columns) . "`) VALUES (" . implode(",", $values) . ")";
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    } catch (Throwable $e) { return false; }
}

function signalCommentForumFront(int $commentId): bool
{
    if (!class_exists('config') || $commentId <= 0) return false;
    $db = config::getConnexion();
    try {
        $db->exec("ALTER TABLE commentaire ADD COLUMN IF NOT EXISTS signale_commentaire TINYINT(1) NOT NULL DEFAULT 0");
    } catch (Throwable $e) {
        try {
            $check = $db->prepare("SHOW COLUMNS FROM commentaire LIKE 'signale_commentaire'");
            $check->execute();
            if (!$check->fetch(PDO::FETCH_ASSOC)) {
                $db->exec("ALTER TABLE commentaire ADD signale_commentaire TINYINT(1) NOT NULL DEFAULT 0");
            }
        } catch (Throwable $e2) {}
    }
    try {
        $query = $db->prepare("UPDATE commentaire SET signale_commentaire = 1 WHERE id_commentaire = :id");
        return $query->execute(['id' => $commentId]);
    } catch (Throwable $e) { return false; }
}


function forumFindLastPostId(int $userId, string $titre, string $contenu): int
{
    if (!class_exists('config')) return 0;
    $db = config::getConnexion();
    $sql = "SELECT id_post FROM post WHERE id_user = :user_id AND titre = :titre AND contenu = :contenu ORDER BY id_post DESC LIMIT 1";
    $query = $db->prepare($sql);
    $query->execute(['user_id' => $userId, 'titre' => $titre, 'contenu' => $contenu]);
    $id = $query->fetchColumn();
    return $id ? (int)$id : 0;
}

function updatePostEmojiForum(int $postId, string $emojiPost): bool
{
    if (!class_exists('config') || $postId <= 0) return false;
    $db = config::getConnexion();
    $sql = "UPDATE post SET emoji_post = :emoji_post WHERE id_post = :id";
    $query = $db->prepare($sql);
    return $query->execute(['emoji_post' => $emojiPost, 'id' => $postId]);
}


function forumEnsureCommentStatusColumnFront(): bool
{
    if (!class_exists('config')) return false;
    try {
        $db = config::getConnexion();
        $check = $db->prepare("SHOW COLUMNS FROM commentaire LIKE 'statut_commentaire'");
        $check->execute();
        if (!$check->fetch(PDO::FETCH_ASSOC)) {
            $db->exec("ALTER TABLE commentaire ADD statut_commentaire VARCHAR(30) NOT NULL DEFAULT 'En attente'");
        }
        return true;
    } catch (Throwable $e) {
        return false;
    }
}

function forumFindLastCommentIdFront(int $postId, int $userId, string $content, ?int $parentId): int
{
    if (!class_exists('config')) return 0;
    try {
        $db = config::getConnexion();
        if ($parentId === null) {
            $sql = "SELECT id_commentaire
                    FROM commentaire
                    WHERE id_post = :post_id
                      AND id_user = :user_id
                      AND contenu_commentaire = :content
                      AND (id_parent_commentaire IS NULL OR id_parent_commentaire = 0)
                    ORDER BY id_commentaire DESC
                    LIMIT 1";
            $query = $db->prepare($sql);
            $query->execute([
                'post_id' => $postId,
                'user_id' => $userId,
                'content' => $content
            ]);
        } else {
            $sql = "SELECT id_commentaire
                    FROM commentaire
                    WHERE id_post = :post_id
                      AND id_user = :user_id
                      AND contenu_commentaire = :content
                      AND id_parent_commentaire = :parent_id
                    ORDER BY id_commentaire DESC
                    LIMIT 1";
            $query = $db->prepare($sql);
            $query->execute([
                'post_id' => $postId,
                'user_id' => $userId,
                'content' => $content,
                'parent_id' => $parentId
            ]);
        }
        $id = $query->fetchColumn();
        return $id ? (int)$id : 0;
    } catch (Throwable $e) {
        return 0;
    }
}

function forumSetCommentStatusFront(int $commentId, string $status = 'En attente'): bool
{
    if ($commentId <= 0 || !class_exists('config')) return false;
    forumEnsureCommentStatusColumnFront();
    try {
        $db = config::getConnexion();
        $query = $db->prepare("UPDATE commentaire SET statut_commentaire = :status WHERE id_commentaire = :id");
        return $query->execute(['status' => $status, 'id' => $commentId]);
    } catch (Throwable $e) {
        return false;
    }
}

/* delete post */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    $deleteId = (int)($_POST['post_id'] ?? 0);
    if ($deleteId > 0) {
        $postController->deletePost($deleteId);
        header('Location: ' . forumUrl(['deleted' => 1]));
        exit;
    }
}

/* ============================================================
   DELETE COMMENT
   - Si commentaire racine (parent_id = 0 ou null) => supprimer
     le commentaire ET toutes ses réponses (enfants)
   - Si réponse => supprimer seulement cette réponse
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment'])) {
    $commentId = (int)($_POST['comment_id'] ?? 0);
    $postId    = (int)($_POST['post_id'] ?? 0);
    $parentId  = (int)($_POST['parent_id'] ?? 0);

    if ($commentId > 0) {
        if ($parentId === 0) {
            // Commentaire racine : supprimer toutes les réponses d'abord
            // On suppose que CommentController a une méthode deleteRepliesByComment
            // Sinon on boucle sur les commentaires existants et on supprime les enfants
            if (method_exists($commentController, 'deleteRepliesByComment')) {
                $commentController->deleteRepliesByComment($commentId);
            } else {
                // Fallback: récupérer tous les commentaires du post et supprimer les réponses
                $allComments = $commentController->listCommentsByPost($postId);
                if (is_array($allComments)) {
                    foreach ($allComments as $c) {
                        if ((int)($c['id_parent_commentaire'] ?? 0) === $commentId) {
                            $commentController->deleteComment((int)$c['id_commentaire']);
                        }
                    }
                }
            }
        }
        // Supprimer le commentaire lui-même (racine ou réponse)
        $commentController->deleteComment($commentId);
    }

    header('Location: ' . forumUrl(['open_post' => $postId, 'comment_deleted' => 1]));
    exit;
}

/* ============================================================
   UPDATE COMMENT / REPONSE AVEC IMAGE + EMOJI + CONTROLE
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_comment'])) {
    $commentId    = (int)($_POST['comment_id'] ?? 0);
    $postId       = (int)($_POST['post_id'] ?? 0);
    $newContent   = trim($_POST['comment_content'] ?? '');
    $emojiContent = trim($_POST['emoji_content'] ?? '');

    if ($commentId > 0) {
        if ($newContent === '') {
            $errors['comment'] = 'Le commentaire est obligatoire.';
        } elseif (getLettersCount($newContent) < 5) {
            $errors['comment'] = 'Le commentaire doit contenir au moins 5 lettres.';
        }

        $oldComment = getCommentByIdForum($commentId);
        $imageCommentPath = $oldComment['image_commentaire'] ?? null;

        if (empty($errors['comment']) && !empty($_FILES['comment_image']['name'])) {
            $newImage = uploadCommentImageFile($_FILES['comment_image'], $errors, $imageCommentPath);
            if (empty($errors['comment']) && $newImage !== null) {
                $imageCommentPath = $newImage;
            }
        }

        if (empty($errors['comment'])) {
            updateCommentForum($commentId, $newContent, $imageCommentPath, $emojiContent);
        }
    }

    header('Location: ' . forumUrl(['open_post' => $postId, 'comment_updated' => 1]));
    exit;
}

/* ============================================================
   REPORT COMMENT
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_comment'])) {
    $commentId = (int)($_POST['comment_id'] ?? 0);
    $postId    = (int)($_POST['post_id'] ?? 0);
    $reason    = trim($_POST['report_reason'] ?? '');
    $details   = trim($_POST['report_details'] ?? '');

    if ($commentId > 0 && $reason !== "") {
        signalCommentForumFront($commentId);
        insertCommentReportForumFront($commentId, $postId, $currentUserId, $reason, $details);
    }

    header('Location: ' . forumUrl(['open_post' => $postId, 'comment_reported' => 1]));
    exit;
}

/* edit mode */
if (isset($_GET['edit']) && ctype_digit($_GET['edit'])) {
    $editId = (int)$_GET['edit'];
    $editPost = $postController->getPostById($editId);

    if ($editPost) {
        $isEditMode = true;
        $old['titre'] = $editPost['titre'] ?? '';
        $old['type_post'] = $editPost['type_post'] ?? '';
        $old['statut_post'] = $editPost['statut_post'] ?? '';
        $old['contenu'] = $editPost['contenu'] ?? '';
        $old['emoji_post'] = $editPost['emoji_post'] ?? '';
    }
}

/* add / update post */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['publish_post']) || isset($_POST['update_post']))) {
    $old['titre'] = trim($_POST['titre'] ?? '');
    $old['type_post'] = trim($_POST['type_post'] ?? '');
    $old['contenu'] = trim($_POST['contenu'] ?? '');
    $old['emoji_post'] = trim($_POST['emoji_post'] ?? '');

    if ($old['titre'] === '') {
        $errors['titre'] = 'Le titre est obligatoire.';
    } elseif (getLettersCount($old['titre']) < 3) {
        $errors['titre'] = 'Le titre doit contenir au moins 3 lettres.';
    }

    if ($old['type_post'] === '') {
        $errors['type_post'] = 'Veuillez choisir le type du post.';
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

    if (isset($_POST['publish_post'])) {
        $imagePath = null;
        $videoPath = null;

        if (!$hasErrors && !empty($_FILES['image']['name'])) {
            $imagePath = uploadImageFile($_FILES['image'], $errors);
            if (!empty($errors['image'])) $hasErrors = true;
        }

        if (!$hasErrors && !empty($_FILES['video']['name'])) {
            $videoPath = uploadVideoFile($_FILES['video'], $errors);
            if (!empty($errors['video'])) $hasErrors = true;
        }

        if (!$hasErrors) {
            $post = new Post(
                null,
                $old['titre'],
                $old['contenu'],
                $imagePath,
                $videoPath,
                $old['type_post'],
                $old['statut_post'],
                $currentUserId
            );

            $postController->addPost($post);
            $newPostId = forumFindLastPostId($currentUserId, $old['titre'], $old['contenu']);
            updatePostEmojiForum($newPostId, $old['emoji_post']);
            header('Location: ' . forumUrl(['published' => 1]));
            exit;
        }
    }

    if (isset($_POST['update_post'])) {
        $editId = (int)($_POST['edit_id'] ?? 0);
        $editPost = $postController->getPostById($editId);

        if ($editPost) {
            $isEditMode = true;

            $imagePath = $editPost['image'] ?? null;
            $videoPath = $editPost['video'] ?? null;

            if (!$hasErrors && !empty($_FILES['image']['name'])) {
                $imagePath = uploadImageFile($_FILES['image'], $errors, $editPost['image'] ?? null);
                if (!empty($errors['image'])) $hasErrors = true;
            }

            if (!$hasErrors && !empty($_FILES['video']['name'])) {
                $videoPath = uploadVideoFile($_FILES['video'], $errors, $editPost['video'] ?? null);
                if (!empty($errors['video'])) $hasErrors = true;
            }

            if (!$hasErrors) {
                $post = new Post(
                    $editId,
                    $old['titre'],
                    $old['contenu'],
                    $imagePath,
                    $videoPath,
                    $old['type_post'],
                    $old['statut_post'],
                    1
                );

                $postController->updatePost($post);
                updatePostEmojiForum($editId, $old['emoji_post']);
                header('Location: ' . forumUrl(['updated' => 1]));
                exit;
            }
        }
    }
}

/* add comment */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $commentContent = trim($_POST['comment_content'] ?? '');
    $postId = (int)($_POST['post_id'] ?? 0);
    $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $emojiContent = trim($_POST['emoji_content'] ?? '');

    if ($commentContent === '') {
        $errors['comment'] = 'Le commentaire est obligatoire.';
    }

    $imageCommentPath = null;
    if (!empty($_FILES['comment_image']['name'])) {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        $extension = strtolower(pathinfo($_FILES['comment_image']['name'], PATHINFO_EXTENSION));

        if (in_array($extension, $allowedExtensions, true) && $_FILES['comment_image']['size'] <= 3 * 1024 * 1024) {
            $uploadDir = __DIR__ . '/../../../uploads/comments/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $newName = uniqid('comment_img_', true) . '.' . $extension;
            if (move_uploaded_file($_FILES['comment_image']['tmp_name'], $uploadDir . $newName)) {
                $imageCommentPath = 'uploads/comments/' . $newName;
            }
        }
    }

    if (empty($errors['comment'])) {
        $comment = new Comment(
            null,
            $commentContent,
            null,
            $postId,
            $currentUserId,
            $parentId,
            $imageCommentPath,
            $emojiContent
        );
        $commentController->addComment($comment);

        // Nouveau commentaire/réponse ajouté depuis le front : il reste en attente
        // et ne s'affiche qu'après approbation de l'admin dans le back office.
        $newCommentId = forumFindLastCommentIdFront($postId, $currentUserId, $commentContent, $parentId);
        forumSetCommentStatusFront($newCommentId, 'En attente');

        header('Location: ' . forumUrl(['commented' => 1, 'open_post' => $postId, 'open_comment' => $parentId ?: 0]));
        exit;
    }
}

/* handle like */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_like'])) {
    $postId = (int)($_POST['post_id'] ?? 0);
    $userId = $currentUserId;
    if ($likeController->isLiked($postId, $userId)) {
        $likeController->removeLike($postId, $userId);
    } else {
        $like = new Like(null, $postId, $userId);
        $likeController->addLike($like);
    }
    header('Location: ' . forumUrl(['open_post' => $postId]));
    exit;
}

/* handle share */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['share_post'])) {
    $postId = (int)($_POST['post_id'] ?? 0);
    $userId = $currentUserId;
    $share = new Share(null, $postId, $userId);
    $shareController->addShare($share);
    header('Location: ' . forumUrl(['open_post' => $postId]));
    exit;
}

/* handle report */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['report_post'])) {
    $postId = (int)($_POST['post_id'] ?? 0);
    $userId = $currentUserId;
    $reason = trim($_POST['report_reason'] ?? '');
    $details = trim($_POST['report_details'] ?? '');
    $fullReason = $reason . ($details ? ': ' . $details : '');

    $report = new Report(null, $postId, $userId, $fullReason);
    $reportController->addReport($report);
    header('Location: ' . forumUrl(['reported' => 1, 'open_post' => $postId]));
    exit;
}

/* handle save */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_save'])) {
    $postId = (int)($_POST['post_id'] ?? 0);

    if ($postId > 0) {
        if ($saveController->isSaved($postId, $currentUserId)) {
            $saveController->removeSave($postId, $currentUserId);
        } else {
            $save = new Save(null, $postId, $currentUserId);
            $saveController->addSave($save);
        }
    }

    header('Location: ' . forumUrl(['open_post' => $postId]));
    exit;
}

/* normalize posts */
$rawPosts = $postController->listPosts();
if ($rawPosts instanceof PDOStatement) {
    $allPosts = $rawPosts->fetchAll(PDO::FETCH_ASSOC);
} else {
    $allPosts = is_array($rawPosts) ? $rawPosts : [];
}

$posts = array_values(array_filter($allPosts, function ($post) use ($search, $filter) {
    $ok = true;

    // Only show approved posts
    $ok = $ok && (($post['statut_post'] ?? '') === 'Approuvé');

    if ($filter !== '' && $filter !== 'Tous') {
        $ok = $ok && (($post['type_post'] ?? '') === $filter);
    }

    if ($search !== '') {
        $needle = mb_strtolower($search);
        $haystack = mb_strtolower(($post['titre'] ?? '') . ' ' . ($post['contenu'] ?? ''));
        $ok = $ok && (mb_strpos($haystack, $needle) !== false);
    }

    return $ok;
}));

if ($sort === 'recent') {
    usort($posts, fn($a, $b) => strtotime($b['date_publication'] ?? 'now') <=> strtotime($a['date_publication'] ?? 'now'));
} elseif ($sort === 'liked') {
    usort($posts, fn($a, $b) => ((int)($b['likes_count'] ?? $b['nb_likes'] ?? 0)) <=> ((int)($a['likes_count'] ?? $a['nb_likes'] ?? 0)));
} elseif ($sort === 'commented') {
    usort($posts, fn($a, $b) => ((int)($b['comments_count'] ?? $b['nb_comments'] ?? 0)) <=> ((int)($a['comments_count'] ?? $a['nb_comments'] ?? 0)));
}

// Load comments for posts
foreach ($posts as &$post) {
    $comments = $commentController->listCommentsByPost($post['id_post']);
    $post['comments'] = is_array($comments) ? array_values(array_filter($comments, function($c) {
        return (($c['statut_commentaire'] ?? 'En attente') === 'Approuvé');
    })) : [];
    $post['likes_count'] = (int)$likeController->countLikes($post['id_post']);
    $post['shares_count'] = (int)$shareController->countShares($post['id_post']);
    $post['reports_count'] = (int)$reportController->countReports($post['id_post']);
    $post['saves_count'] = (int)$saveController->countSaves($post['id_post']);
    $post['is_liked'] = $likeController->isLiked($post['id_post'], $currentUserId);
    $post['is_saved'] = $saveController->isSaved($post['id_post'], $currentUserId);
    $post['comments_count'] = count($post['comments']);
}
unset($post);

$topContributors = method_exists($postController, 'getTopContributors')
    ? $postController->getTopContributors(5)
    : [];
?>

<style>
    :root{
        --forum-orange:#EE5828;
        --forum-orange-dark:#c9471d;
        --forum-navy:#142738;
        --forum-navy-2:#0f2236;
        --forum-navy-3:#173552;
        --forum-green:#4CAF50;
        --forum-white:#FFFFFF;

        --forum-bg-card:#ffffff;
        --forum-bg-soft:#f5f7fb;
        --forum-bg-input:#ffffff;
        --forum-bg-panel:#ffffff;
        --forum-bg-item:#ffffff;

        --forum-text:#17283f;
        --forum-text-soft:#607089;
        --forum-border:rgba(15,23,42,.08);

        --forum-btn-bg:linear-gradient(135deg,#EE5828 0%,#c9471d 38%,#1f3144 72%,#4CAF50 100%);
        --forum-btn-text:#ffffff;

        --forum-shadow:0 12px 30px rgba(15,23,42,.08);
    }

    body.dark,
    body.dark-mode,
    body[data-theme="dark"],
    body.theme-dark{
        --forum-bg-card:rgba(16,38,59,0.96);
        --forum-bg-soft:#0f2236;
        --forum-bg-input:#071523;
        --forum-bg-panel:rgba(16,38,59,0.96);
        --forum-bg-item:#0c2033;

        --forum-text:#ffffff;
        --forum-text-soft:#c7d3e0;
        --forum-border:rgba(255,255,255,.08);

        --forum-shadow:0 12px 30px rgba(0,0,0,.18);
    }

    .forum-page{
        display:flex;
        flex-direction:column;
        gap:28px;
        width:100%;
    }

    .forum-hero-classic{
        position:relative;
        overflow:hidden;
        border-radius:32px;
    }

    .forum-hero-classic::before{
        content:"";
        position:absolute;
        inset:0;
        background:url('/GoService/assets/images/forum-hero.png') center/cover no-repeat;
        opacity:1;
        z-index:0;
        pointer-events:none;
    }

    .forum-hero-classic::after{
        content:"";
        position:absolute;
        inset:0;
        background:linear-gradient(90deg, rgba(255,255,255,.78) 0%, rgba(255,255,255,.62) 34%, rgba(255,255,255,.18) 68%, rgba(255,255,255,.06) 100%);
        z-index:0;
        pointer-events:none;
    }

    body.dark .forum-hero-classic::after,
    body.dark-mode .forum-hero-classic::after,
    body[data-theme="dark"] .forum-hero-classic::after,
    body.theme-dark .forum-hero-classic::after{
        background:linear-gradient(90deg, rgba(8,18,30,.84) 0%, rgba(8,18,30,.70) 38%, rgba(8,18,30,.34) 70%, rgba(8,18,30,.14) 100%);
    }

    .forum-hero-classic > *{
        position:relative;
        z-index:1;
    }

    .success-message{
        background:#eef8f1;
        color:#237c48;
        border:1px solid #d6eddc;
        border-radius:18px;
        padding:14px 18px;
        font-weight:700;
    }

    .forum-action-bar{
        display:flex;
        flex-direction:column;
        gap:16px;
        max-width:1380px;
        width:100%;
        margin:0 auto;
    }

    .forum-search-layout{
        display:flex;
        flex-direction:column;
        gap:14px;
        width:100%;
    }

    .forum-search-main{
        display:grid;
        grid-template-columns:minmax(0,1fr) auto;
        gap:14px;
        align-items:center;
    }

    .forum-search-box input{
        width:100%;
        height:58px;
        border:1px solid var(--forum-border);
        border-radius:18px;
        padding:0 18px;
        background:var(--forum-bg-input);
        font:inherit;
        color:var(--forum-text);
        outline:none;
        box-sizing:border-box;
    }

    .forum-search-box input::placeholder{
        color:var(--forum-text-soft);
    }

    .forum-search-box input:focus,
    .forum-sort-select:focus{
        border-color:rgba(238,88,40,.32);
        box-shadow:0 0 0 4px rgba(238,88,40,.10);
    }

    .forum-top-actions{
        display:flex;
        gap:12px;
        flex-wrap:wrap;
    }

    .forum-bottom-actions{
        display:grid;
        grid-template-columns:1fr 220px;
        gap:14px;
        align-items:center;
    }

    .forum-filter-row{
        display:flex;
        gap:10px;
        flex-wrap:wrap;
    }

    .forum-filter-btn{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        min-width:118px;
        padding:12px 20px;
        border-radius:999px;
        background:var(--forum-btn-bg);
        color:var(--forum-btn-text);
        text-decoration:none;
        font-weight:800;
        font-size:15px;
        box-shadow:0 10px 22px rgba(238,88,40,.16);
        transition:.25s ease;
    }

    .forum-filter-btn:hover,
    .forum-filter-btn.active{
        transform:translateY(-1px);
        filter:brightness(1.03);
    }

    .forum-sort-wrap{
        display:flex;
        justify-content:flex-end;
    }

    .forum-sort-select{
        width:100%;
        height:56px;
        border:1px solid transparent;
        border-radius:999px;
        padding:0 18px;
        background:var(--forum-btn-bg);
        color:#fff;
        font:inherit;
        font-weight:800;
        outline:none;
        box-sizing:border-box;
        appearance:none;
        -webkit-appearance:none;
        -moz-appearance:none;
        color-scheme:dark;
    }

    .forum-sort-select option{
        background:#ffffff;
        color:#17283f;
    }

    body.dark .forum-sort-select option,
    body.dark-mode .forum-sort-select option,
    body[data-theme="dark"] .forum-sort-select option,
    body.theme-dark .forum-sort-select option{
        background:#142738 !important;
        color:#ffffff !important;
    }

    .forum-main-layout{
        display:grid;
        grid-template-columns:minmax(0,980px) 330px;
        gap:24px;
        align-items:start;
        justify-content:center;
        width:100%;
        max-width:1380px;
        margin:0 auto;
    }

    .forum-feed{
        display:flex;
        flex-direction:column;
        gap:22px;
        width:100%;
        min-width:0;
    }

    .composer-card{
        padding:16px 18px;
        width:100%;
        border-radius:24px;
        background:var(--forum-bg-card);
        border:1px solid var(--forum-border);
        box-shadow:var(--forum-shadow);
    }

    .composer-top{
        display:flex;
        align-items:center;
        gap:12px;
    }

    .composer-open-btn{
        flex:1;
        height:54px;
        border:none;
        border-radius:999px;
        background:var(--forum-bg-input);
        color:var(--forum-text-soft);
        font:inherit;
        font-size:18px;
        text-align:left;
        padding:0 18px;
        cursor:pointer;
    }

    .composer-open-btn:hover{
        filter:brightness(.98);
    }

    .composer-icons{
        display:flex;
        align-items:center;
        gap:10px;
    }

    .composer-icon-btn{
        border:none;
        background:transparent;
        font-size:24px;
        cursor:pointer;
        color:var(--forum-text);
    }

    .modal-overlay{
        position:fixed;
        inset:0;
        background:rgba(15,23,42,.42);
        display:none;
        align-items:center;
        justify-content:center;
        z-index:9999;
        padding:20px;
    }

    .modal-overlay.show{
        display:flex;
    }

    .forum-modal{
        width:min(620px,100%);
        max-height:88vh;
        background:#fff;
        border-radius:24px;
        box-shadow:0 30px 80px rgba(15,23,42,.25);
        overflow:hidden;
        display:flex;
        flex-direction:column;
    }

    .forum-modal-head{
        padding:16px 18px;
        border-bottom:1px solid rgba(15,23,42,.08);
        display:flex;
        align-items:center;
        justify-content:space-between;
        flex-shrink:0;
    }

    .forum-modal-title{
        font-size:28px;
        font-weight:800;
        color:#17283f;
    }

    .forum-modal-close{
        width:42px;
        height:42px;
        border:none;
        border-radius:50%;
        background:#f2f4f8;
        font-size:24px;
        cursor:pointer;
    }

    .forum-modal-body{
        padding:18px;
        overflow-y:auto;
    }

    .forum-modal-user{
        display:flex;
        align-items:center;
        gap:12px;
        margin-bottom:14px;
    }

    .forum-modal-name{
        font-weight:800;
        color:#17283f;
    }

    .forum-form-grid{
        display:grid;
        grid-template-columns:1fr 1fr;
        gap:14px;
        margin-top:12px;
    }

    .forum-form-grid .full-width{
        grid-column:1/-1;
    }

    .forum-form-field{
        display:flex;
        flex-direction:column;
        min-width:0;
    }

    .forum-form-field input[type="text"],
    .forum-form-field input[type="file"],
    .forum-form-field select,
    .forum-form-field textarea{
        width:100%;
        box-sizing:border-box;
        border:1px solid rgba(15,23,42,.10);
        border-radius:18px;
        background:#fff;
        font:inherit;
        color:#203047;
        outline:none;
    }

    .forum-form-field input[type="text"],
    .forum-form-field input[type="file"],
    .forum-form-field select{
        height:54px;
        padding:0 14px;
    }

    .forum-form-field input[type="file"]{
        padding:12px 14px;
    }

    .forum-form-field textarea{
        min-height:120px;
        padding:14px;
        resize:vertical;
    }

    .forum-form-field input:focus,
    .forum-form-field select:focus,
    .forum-form-field textarea:focus{
        border-color:rgba(238,88,40,.25);
        box-shadow:0 0 0 4px rgba(238,88,40,.08);
    }

    .forum-upload-preview,
    .forum-video-preview,
    .forum-current-image,
    .forum-current-video{
        margin-top:10px;
        border-radius:16px;
        overflow:hidden;
        border:1px solid rgba(15,23,42,.08);
        display:none;
        background:#0b0b0b;
    }

    .forum-upload-preview.show,
    .forum-video-preview.show,
    .forum-current-image.show,
    .forum-current-video.show{
        display:block;
    }

    .forum-upload-preview img,
    .forum-current-image img{
        width:100%;
        max-height:220px;
        object-fit:contain;
        display:block;
        background:#0b0b0b;
    }

    .forum-video-preview video,
    .forum-current-video video{
        width:100%;
        max-height:240px;
        object-fit:contain;
        display:block;
        background:#000;
    }


    .forum-emoji-input,
    .comment-emoji-input{
        width:100%;
        box-sizing:border-box;
        border:1px solid rgba(15,23,42,.10);
        border-radius:18px;
        background:#fff;
        font:inherit;
        color:#203047;
        outline:none;
        min-height:54px;
        padding:0 14px;
        margin-top:12px;
        font-size:22px;
    }

    .comment-emoji-input{
        width:100%;
        max-width:100%;
        min-height:54px;
        height:54px;
        margin-top:10px;
        margin-bottom:12px;
        padding:0 16px;
        border-radius:20px;
        font-size:22px;
        text-align:left;
        display:block;
        box-sizing:border-box;
    }

    .forum-emoji-input:focus,
    .comment-emoji-input:focus{
        border-color:rgba(238,88,40,.25);
        box-shadow:0 0 0 4px rgba(238,88,40,.08);
    }

    .forum-modal-tools{
        margin-top:14px;
        padding:12px 14px;
        border:1px solid rgba(15,23,42,.08);
        border-radius:18px;
        display:flex;
        align-items:center;
        justify-content:space-between;
        gap:12px;
        flex-wrap:wrap;
    }

    .forum-tool-icons{
        display:flex;
        gap:12px;
        align-items:center;
    }

    .tool-trigger{
        border:none;
        background:transparent;
        font-size:26px;
        cursor:pointer;
    }

    .forum-form-actions{
        display:flex;
        gap:12px;
        flex-wrap:wrap;
        margin-top:16px;
    }

    .field-error{
        display:block;
        min-height:18px;
        margin-top:8px;
        color:#ea5a5a;
        font-size:13px;
        padding-left:4px;
    }

    .field-valid{
        display:block;
        min-height:18px;
        margin-top:8px;
        color:#1f9d55;
        font-size:13px;
        padding-left:4px;
    }

    .field-invalid{
        border:1.8px solid #ea5a5a !important;
        box-shadow:0 0 0 4px rgba(234,90,90,.08) !important;
    }

    .field-valid-input{
        border:1.8px solid #22a559 !important;
        box-shadow:0 0 0 4px rgba(34,165,89,.08) !important;
    }

    .forum-posts-list{
        display:flex;
        flex-direction:column;
        gap:22px;
    }

    .post-card{
        border:1px solid var(--forum-border);
        box-shadow:var(--forum-shadow);
        position:relative;
        width:100%;
        border-radius:24px;
        overflow:hidden;
        background:var(--forum-bg-card);
        padding:22px;
    }

    .post-header-row{
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:16px;
    }

    .post-user{
        display:flex;
        align-items:center;
        gap:14px;
        min-width:0;
    }

    .post-user strong,
    .post-title{
        color:var(--forum-text);
    }

    .post-meta-line{
        display:flex;
        align-items:center;
        gap:10px;
        flex-wrap:wrap;
        margin-top:6px;
    }

    .post-type-badge{
        display:inline-flex;
        align-items:center;
        padding:6px 12px;
        border-radius:999px;
        font-size:13px;
        font-weight:700;
        background:rgba(238,88,40,.12);
        color:#e67a52;
        border:1px solid rgba(238,88,40,.18);
    }

    .post-date-exact,
    .post-time-top,
    .post-content{
        color:var(--forum-text-soft);
    }

    .post-time-top{
        font-weight:600;
        white-space:nowrap;
    }

    .post-menu-wrap{
        display:flex;
        align-items:center;
        gap:10px;
        position:relative;
    }

    .post-menu-btn{
        width:52px;
        height:52px;
        border:none;
        border-radius:50%;
        background:#fff;
        box-shadow:0 8px 18px rgba(15,23,42,.08);
        cursor:pointer;
        font-size:22px;
        color:#203047;
        font-weight:900;
    }

    .post-dropdown{
        position:absolute;
        top:60px;
        right:0;
        min-width:200px;
        background:#fff;
        border-radius:18px;
        box-shadow:0 18px 38px rgba(15,23,42,.12);
        padding:10px;
        display:none;
        z-index:20;
    }

    .post-dropdown.show{
        display:block;
    }

    .post-dropdown a,
    .post-dropdown button{
        width:100%;
        display:flex;
        align-items:center;
        gap:10px;
        padding:12px 14px;
        border:none;
        background:#fff;
        border-radius:12px;
        cursor:pointer;
        text-decoration:none;
        font:inherit;
        color:#111 !important;
        text-align:left;
    }

    .post-dropdown a:hover,
    .post-dropdown button:hover{
        background:#f7f8fb;
    }

    .post-title{
        margin-top:18px;
        margin-bottom:10px;
        font-size:24px;
        line-height:1.25;
        font-weight:800;
    }

    .post-content{
        line-height:1.7;
        font-size:16px;
        margin:0;
    }

    .post-media-frame{
        width:100%;
        margin-top:18px;
        border-radius:24px;
        overflow:hidden;
        background:#0b0b0b;
        display:flex;
        align-items:center;
        justify-content:center;
        cursor:pointer;
        position:relative;
    }

    .post-media-frame img{
        width:100%;
        max-height:640px;
        object-fit:contain;
        display:block;
        background:#0b0b0b;
    }

    .post-media-frame video{
        width:100%;
        max-height:680px;
        object-fit:contain;
        display:block;
        background:#000;
    }

    .post-media-overlay{
        position:absolute;
        inset:auto 16px 16px auto;
        background:rgba(0,0,0,.45);
        color:#fff;
        padding:8px 12px;
        border-radius:999px;
        font-size:13px;
        font-weight:700;
        pointer-events:none;
    }

    .forum-reactions-bar{
        display:grid;
        grid-template-columns:repeat(4, minmax(0,1fr));
        gap:12px;
        margin-top:18px;
        padding-top:12px;
        border-top:1px solid var(--forum-border);
        align-items:stretch;
    }

    .forum-reactions-bar form,
    .forum-reactions-bar > *{
        width:100%;
        min-width:0;
        margin:0;
    }

    .post-reaction-btn{
        width:100%;
        height:52px;
        display:flex;
        align-items:center;
        justify-content:center;
        gap:8px;
        padding:0 14px;
        border:none;
        border-radius:18px;
        cursor:pointer;
        font-weight:800;
        font-size:14px;
        background:var(--forum-btn-bg);
        color:#fff;
        transition:all .2s ease;
        box-sizing:border-box;
        box-shadow:0 10px 22px rgba(238,88,40,.16);
        white-space:nowrap;
    }

    .post-reaction-btn:hover{
        transform:translateY(-1px);
        filter:brightness(1.05);
    }

    .post-reaction-btn .reaction-label,
    .post-reaction-btn .reaction-count{
        color:inherit;
    }

    .comment-box{
        display:block;
        margin-top:18px;
    }

    .comment-box.hidden{
        display:none;
    }

    .comment-box h4{
        color:var(--forum-text);
    }

    .comment-area{
        width:100%;
        min-height:120px;
        border:1px solid var(--forum-border);
        border-radius:20px;
        padding:16px;
        box-sizing:border-box;
        resize:vertical;
        font:inherit;
        outline:none;
        background:var(--forum-bg-input);
        color:var(--forum-text);
    }

    .comment-area::placeholder{
        color:var(--forum-text-soft);
    }

    .comment-area:focus{
        border-color:rgba(238,88,40,.22);
        box-shadow:0 0 0 4px rgba(238,88,40,.08);
    }

    .comment-tools{
        display:flex;
        align-items:center;
        gap:12px;
        margin-top:12px;
        flex-wrap:wrap;
    }

    .comment-tool-btn{
        border:none;
        background:var(--forum-bg-input);
        color:var(--forum-text);
        width:44px;
        height:44px;
        border-radius:14px;
        font-size:22px;
        cursor:pointer;
    }

    .comment-hidden-input{
        display:none;
    }

    .comment-form-panel{
        background:var(--forum-bg-card);
        border:1px solid var(--forum-border);
        border-radius:22px;
    }

    .comment-form,
    .reply-form{
        margin-top:14px;
    }

    .comment-tool-btn{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        box-shadow:0 8px 18px rgba(15,23,42,.08);
        transition:.2s ease;
    }

    .comment-tool-btn:hover{
        transform:translateY(-1px);
        filter:brightness(.98);
    }

    .reply-tool-btn{
        width:38px;
        height:38px;
        font-size:18px;
        border-radius:12px;
    }

    .reply-submit-btn{
        min-height:38px;
        padding:0 16px;
        font-size:13px;
    }

    .comments-list{
        display:flex;
        flex-direction:column;
        gap:16px;
    }

    .comment-thread{
        border:1px solid var(--forum-border);
        border-radius:22px;
        background:rgba(255,255,255,.02);
        padding:16px;
    }

    .comment-item,
    .reply-item{
        display:flex;
        align-items:flex-start;
        gap:12px;
    }

    .comment-body{
        flex:1;
        min-width:0;
    }

    .comment-bubble{
        background:var(--forum-bg-input);
        border:1px solid var(--forum-border);
        border-radius:20px;
        padding:14px 16px;
    }

    .reply-bubble{
        background:rgba(255,255,255,.03);
    }

    .comment-author{
        display:block;
        color:var(--forum-text);
        font-size:18px;
        margin-bottom:6px;
    }

    .comment-text{
        color:var(--forum-text);
        line-height:1.65;
        word-break:break-word;
    }

    .comment-emoji-line{
        margin-top:8px;
        font-size:22px;
        line-height:1.2;
    }

    .comment-image-wrap{
        margin-top:10px;
    }

    .comment-image{
        max-width:220px;
        border-radius:14px;
        display:block;
    }

    .comment-meta-row{
        display:flex;
        align-items:center;
        gap:16px;
        margin-top:10px;
        padding-left:4px;
        flex-wrap:wrap;
    }

    .comment-time{
        color:var(--forum-text-soft);
        font-size:14px;
    }

    .reply-btn{
        border:none;
        background:none;
        color:#2b7cff;
        cursor:pointer;
        font-size:15px;
        font-weight:800;
        padding:0;
    }

    .reply-btn:hover{
        text-decoration:underline;
        color:#63a1ff;
    }

    .reply-box{
        margin-top:12px;
        padding:14px;
        border:1px solid var(--forum-border);
        border-radius:18px;
        background:rgba(255,255,255,.03);
    }

    .reply-area{
        min-height:88px;
        border-radius:16px;
    }

    .replies-list{
        margin-top:14px;
        margin-left:18px;
        padding-left:18px;
        border-left:2px solid rgba(255,255,255,.06);
        display:flex;
        flex-direction:column;
        gap:12px;
    }

    .reply-item .comment-author{
        font-size:16px;
    }

    /* ============================================================
       COMMENT MENU (3 points) — nouveau style
       ============================================================ */
    .comment-header-row{
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:8px;
        margin-bottom:6px;
    }

    .comment-menu-wrap{
        position:relative;
        flex-shrink:0;
    }

    .comment-menu-btn{
        width:32px;
        height:32px;
        border:none;
        border-radius:50%;
        background:transparent;
        cursor:pointer;
        font-size:18px;
        color:var(--forum-text-soft);
        display:flex;
        align-items:center;
        justify-content:center;
        transition:.18s ease;
        font-weight:900;
        line-height:1;
    }

    .comment-menu-btn:hover{
        background:var(--forum-bg-soft);
        color:var(--forum-text);
    }

    .comment-dropdown{
        position:absolute;
        top:36px;
        right:0;
        min-width:180px;
        background:#fff;
        border-radius:16px;
        box-shadow:0 18px 38px rgba(15,23,42,.14);
        padding:8px;
        display:none;
        z-index:100;
        border:1px solid rgba(15,23,42,.06);
    }

    .comment-dropdown.show{
        display:block;
    }

    .comment-dropdown button{
        width:100%;
        display:flex;
        align-items:center;
        gap:10px;
        padding:10px 12px;
        border:none;
        background:#fff;
        border-radius:10px;
        cursor:pointer;
        font:inherit;
        font-size:14px;
        font-weight:600;
        color:#17283f;
        text-align:left;
        transition:.15s ease;
    }

    .comment-dropdown button:hover{
        background:#f5f7fb;
    }

    .comment-dropdown button.danger{
        color:#dc2626;
    }

    .comment-dropdown button.danger:hover{
        background:#fff5f5;
    }

    /* Edit inline area */
    .comment-edit-area{
        width:100%;
        min-height:80px;
        border:1.5px solid rgba(238,88,40,.3);
        border-radius:14px;
        padding:12px;
        box-sizing:border-box;
        resize:vertical;
        font:inherit;
        outline:none;
        background:var(--forum-bg-input);
        color:var(--forum-text);
        margin-top:8px;
    }

    .comment-edit-area:focus{
        border-color:rgba(238,88,40,.5);
        box-shadow:0 0 0 4px rgba(238,88,40,.08);
    }

    .comment-edit-actions{
        display:flex;
        gap:8px;
        margin-top:8px;
        flex-wrap:wrap;
    }

    .comment-edit-save-btn{
        padding:8px 18px;
        border:none;
        border-radius:10px;
        background:var(--forum-btn-bg);
        color:#fff;
        font:inherit;
        font-weight:700;
        font-size:13px;
        cursor:pointer;
    }

    .comment-edit-cancel-btn{
        padding:8px 18px;
        border:1px solid var(--forum-border);
        border-radius:10px;
        background:transparent;
        color:var(--forum-text-soft);
        font:inherit;
        font-weight:700;
        font-size:13px;
        cursor:pointer;
    }

    /* Reply menu (3 dots sur les réponses) */
    .reply-header-row{
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:8px;
        margin-bottom:6px;
    }

    /* ============================================================
       Modal report commentaire
       ============================================================ */
    #reportCommentModal{
        position:fixed;
        inset:0;
        background:rgba(20,39,56,.45);
        z-index:999999;
        align-items:center;
        justify-content:center;
        display:none;
    }

    #reportCommentModal.show{
        display:flex;
    }

    /* ============================================================
       Reste des styles (inchangés)
       ============================================================ */
    .forum-right-panel{
        display:flex;
        flex-direction:column;
        gap:20px;
        position:sticky;
        top:18px;
    }

    .forum-right-panel .panel{
        padding:20px;
        border-radius:24px;
        background:var(--forum-bg-panel);
        border:1px solid var(--forum-border);
        box-shadow:var(--forum-shadow);
    }

    .top-contributors-list,
    .recent-posts-list{
        display:flex;
        flex-direction:column;
        gap:14px;
        margin-top:14px;
    }

    .top-contributor-item,
    .recent-post-item{
        display:flex;
        justify-content:space-between;
        align-items:center;
        gap:12px;
        padding:16px 18px;
        border-radius:20px;
        background:var(--forum-bg-item);
        border:1px solid var(--forum-border);
        box-shadow:0 10px 24px rgba(15,23,42,.05);
    }

    .top-contributor-left,
    .recent-post-left{
        display:flex;
        align-items:center;
        gap:12px;
        min-width:0;
    }

    .top-contributor-count{
        font-size:22px;
        font-weight:800;
        color:var(--forum-text);
    }

    .recent-post-left div,
    .top-contributor-left div{
        min-width:0;
    }

    .recent-post-left strong,
    .top-contributor-left strong{
        display:block;
        white-space:nowrap;
        overflow:hidden;
        text-overflow:ellipsis;
        max-width:200px;
        color:var(--forum-text);
    }

    .recent-post-left span,
    .top-contributor-left span{
        color:var(--forum-text-soft);
    }

    .media-viewer-overlay{
        position:fixed;
        inset:0;
        background:#000;
        display:none;
        z-index:10000;
    }

    .media-viewer-overlay.show{
        display:block;
    }

    .media-viewer-close{
        position:absolute;
        top:18px;
        left:18px;
        width:54px;
        height:54px;
        border:none;
        border-radius:50%;
        background:rgba(255,255,255,.14);
        color:#fff;
        font-size:28px;
        cursor:pointer;
        z-index:12;
    }

    .media-viewer-content{
        width:100%;
        height:100%;
        display:none;
    }

    .media-viewer-content.show{
        display:block;
    }

    .media-viewer-image-layout{
        width:100%;
        height:100%;
        display:grid;
        grid-template-columns:minmax(0,1fr) 420px;
    }

    .media-viewer-image-main{
        display:flex;
        align-items:center;
        justify-content:center;
        padding:24px;
        min-width:0;
    }

    .media-viewer-image-main img{
        max-width:100%;
        max-height:92vh;
        object-fit:contain;
        display:block;
    }

    .media-viewer-image-side{
        background:var(--forum-navy-2);
        height:100%;
        overflow-y:auto;
        display:flex;
        flex-direction:column;
        border-left:1px solid rgba(255,255,255,.08);
        color:#fff;
    }

    .viewer-post-head{
        padding:18px;
        border-bottom:1px solid rgba(255,255,255,.08);
        display:flex;
        align-items:flex-start;
        justify-content:space-between;
        gap:12px;
    }

    .viewer-user{
        display:flex;
        gap:12px;
        align-items:center;
        min-width:0;
    }

    .viewer-user-meta strong{
        display:block;
        color:#fff;
    }

    .viewer-user-meta span{
        color:#c7d3e0;
        font-size:14px;
    }

    .viewer-post-body{
        padding:18px;
        color:#f5f8fc;
        line-height:1.65;
        border-bottom:1px solid rgba(255,255,255,.08);
    }

    .viewer-post-title{
        font-size:20px;
        font-weight:800;
        color:#fff;
        margin-bottom:10px;
    }

    .viewer-stats{
        display:flex;
        gap:18px;
        align-items:center;
        padding:14px 18px;
        border-bottom:1px solid rgba(255,255,255,.08);
        color:#fff;
        font-weight:700;
        flex-wrap:wrap;
    }

    .viewer-actions{
        display:grid;
        grid-template-columns:repeat(4,1fr);
        gap:12px;
        padding:16px 18px;
        border-bottom:1px solid rgba(255,255,255,.08);
    }

    .viewer-actions form{
        margin:0;
    }

    .viewer-action-btn{
        width:100%;
        border:none;
        background:var(--forum-btn-bg);
        border-radius:16px;
        padding:12px 10px;
        font:inherit;
        font-weight:800;
        color:#fff;
        cursor:pointer;
    }

    .viewer-comments{
        padding:18px;
        display:flex;
        flex-direction:column;
        gap:14px;
        max-height:380px;
        overflow-y:auto;
    }

    .viewer-comment-item{
        display:flex;
        gap:10px;
        align-items:flex-start;
    }

    .viewer-comment-content{
        flex:1;
        min-width:0;
    }

    .viewer-comment{
        display:flex;
        gap:10px;
        align-items:flex-start;
    }

    .viewer-comment-bubble{
        background:var(--forum-navy-3);
        padding:12px 14px;
        border-radius:18px;
        line-height:1.5;
        color:#fff;
        max-width:100%;
    }

    .viewer-comment-author{
        font-weight:800;
        display:block;
        margin-bottom:4px;
    }

    .viewer-comment-meta{
        font-size:13px;
        color:#c7d3e0;
        margin-top:6px;
        display:flex;
        gap:12px;
        align-items:center;
        flex-wrap:wrap;
    }

    .viewer-reply-btn{
        background:none;
        border:none;
        color:#c7d3e0;
        cursor:pointer;
        font-weight:700;
        padding:0;
    }

    .viewer-replies{
        margin-top:10px;
        margin-left:34px;
        display:flex;
        flex-direction:column;
        gap:10px;
    }

    .viewer-reply-item{
        display:flex;
        gap:10px;
        align-items:flex-start;
    }

    .viewer-reply-bubble{
        background:#18344f;
        padding:10px 12px;
        border-radius:16px;
        color:#fff;
        line-height:1.5;
        max-width:100%;
    }

    .media-viewer-video-layout{
        width:100%;
        height:100%;
        display:grid;
        grid-template-columns:minmax(0,1fr) 110px;
        align-items:center;
    }

    .media-viewer-video-main{
        width:100%;
        height:100%;
        display:flex;
        align-items:center;
        justify-content:center;
        padding:20px 10px 20px 72px;
        box-sizing:border-box;
    }

    .media-viewer-video-main video{
        max-width:100%;
        max-height:94vh;
        object-fit:contain;
        display:block;
        border-radius:14px;
        background:#000;
    }

    .media-viewer-video-side{
        display:flex;
        flex-direction:column;
        align-items:center;
        gap:26px;
        color:#fff;
        font-size:18px;
        padding-right:18px;
    }

    .media-viewer-action{
        display:flex;
        flex-direction:column;
        align-items:center;
        gap:6px;
        color:#fff;
        font-weight:700;
    }

    .viewer-side-icon{
        width:54px;
        height:54px;
        border-radius:50%;
        background:var(--forum-btn-bg);
        display:flex;
        align-items:center;
        justify-content:center;
        font-size:24px;
    }
    
    .emoji-picker{
        position:fixed;
        width:320px;
        max-width:calc(100vw - 24px);
        background:#fff;
        border:1px solid rgba(15,23,42,.08);
        border-radius:22px;
        box-shadow:0 24px 60px rgba(15,23,42,.20);
        display:none;
        z-index:11000;
        overflow:hidden;
    }

    .emoji-picker.show{
        display:block;
    }

    .emoji-picker-head{
        padding:14px 16px;
        border-bottom:1px solid rgba(15,23,42,.08);
        font-weight:800;
        color:#17283f;
        background:#fff;
    }

    .emoji-picker-tabs{
        display:flex;
        gap:8px;
        padding:10px 12px;
        border-bottom:1px solid rgba(15,23,42,.08);
        overflow-x:auto;
        background:#fafbfc;
    }

    .emoji-tab{
        border:none;
        background:#fff;
        border-radius:999px;
        padding:8px 12px;
        cursor:pointer;
        font-size:14px;
        white-space:nowrap;
    }

    .emoji-tab.active{
        background:#1f3144;
        color:#fff;
    }

    .emoji-picker-body{
        max-height:260px;
        overflow-y:auto;
        padding:12px;
        display:grid;
        grid-template-columns:repeat(7, 1fr);
        gap:8px;
    }

    .emoji-btn{
        border:none;
        background:#fff;
        border-radius:12px;
        height:40px;
        font-size:22px;
        cursor:pointer;
        transition:.18s ease;
    }

    .emoji-btn:hover{
        background:#f1f4f8;
        transform:scale(1.08);
    }

    @media (max-width:1450px){
        .forum-main-layout{
            max-width:1240px;
            grid-template-columns:minmax(0,1fr) 320px;
        }
    }

    @media (max-width:1300px){
        .forum-main-layout{
            grid-template-columns:1fr;
        }

        .forum-right-panel{
            position:static;
        }
    }

    @media (max-width:1100px){
        .media-viewer-image-layout{
            grid-template-columns:1fr;
        }

        .media-viewer-image-side{
            display:none;
        }
    }

    @media (max-width:980px){
        .forum-search-main,
        .forum-bottom-actions,
        .forum-form-grid,
        .forum-reactions-bar{
            grid-template-columns:1fr;
        }

        .forum-form-grid .full-width{
            grid-column:auto;
        }

        .forum-sort-wrap{
            justify-content:flex-start;
        }

        .media-viewer-video-layout{
            grid-template-columns:1fr;
        }

        .media-viewer-video-side{
            display:none;
        }

        .post-card{
            padding:18px;
        }
    }
</style>

<div class="forum-page">

    <section class="page-hero reveal forum-hero-classic">
        <span class="section-badge">Forum social</span>
        <h1 class="page-title">Forum & échanges</h1>
        <p class="page-intro">
            Publiez, partagez des images ou vidéos, commentez, aimez et suivez les discussions dans une interface moderne inspirée des réseaux sociaux.
        </p>
    </section>

    <section class="action-bar reveal forum-action-bar">
        <form method="GET" action="/GoService/view/front/index.php" class="forum-search-layout">
            <input type="hidden" name="page" value="forum">

            <div class="forum-search-main">
                <div class="search-box forum-search-box">
                    <input type="text" name="search" placeholder="Rechercher un post..." value="<?php echo e($search); ?>">
                </div>

                <div class="forum-top-actions">
                    <button class="solid-btn" type="submit">Mes posts</button>
                    <a class="solid-btn" href="/GoService/view/front/pages/savedPosts.php">Posts enregistrés</a>
                </div>
            </div>

            <div class="forum-bottom-actions">
                <div class="forum-filter-row">
                    <?php
                    $filters = ['Tous', 'Question', 'Conseil', 'Discussion'];
                    foreach ($filters as $f):
                        $active = ($filter === $f) || ($filter === 'Tous' && $f === 'Tous');
                        $link = '/GoService/view/front/index.php?page=forum&filter=' . urlencode($f) . '&search=' . urlencode($search) . '&sort=' . urlencode($sort);
                    ?>
                        <a href="<?php echo e($link); ?>" class="forum-filter-btn <?php echo $active ? 'active' : ''; ?>">
                            <?php echo $f === 'Tous' ? 'Tous' : $f . 's'; ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="forum-sort-wrap">
                    <select name="sort" class="forum-sort-select" onchange="this.form.submit()">
                        <option value="recent" <?php echo $sort === 'recent' ? 'selected' : ''; ?>>Plus récents</option>
                        <option value="liked" <?php echo $sort === 'liked' ? 'selected' : ''; ?>>Plus aimés</option>
                        <option value="commented" <?php echo $sort === 'commented' ? 'selected' : ''; ?>>Plus commentés</option>
                    </select>
                </div>
            </div>
        </form>
    </section>

    <?php if (isset($_GET['published'])): ?>
        <div class="success-message" style="max-width:1380px;margin:0 auto;width:100%;">Votre post a été envoyé pour révision par l'administrateur.</div>
    <?php endif; ?>

    <?php if (isset($_GET['updated'])): ?>
        <div class="success-message" style="max-width:1380px;margin:0 auto;width:100%;">Post mis à jour avec succès.</div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="success-message" style="max-width:1380px;margin:0 auto;width:100%;">Post supprimé avec succès.</div>
    <?php endif; ?>

    <?php if (isset($_GET['reported'])): ?>
        <div class="success-message" style="max-width:1380px;margin:0 auto;width:100%;">Post signalé avec succès. Notre équipe examinera le signalement.</div>
    <?php endif; ?>

    <?php if (isset($_GET['comment_deleted'])): ?>
        <div class="success-message" style="max-width:1380px;margin:0 auto;width:100%;">Commentaire supprimé avec succès.</div>
    <?php endif; ?>

    <?php if (isset($_GET['comment_updated'])): ?>
        <div class="success-message" style="max-width:1380px;margin:0 auto;width:100%;">Commentaire modifié avec succès.</div>
    <?php endif; ?>

    <?php if (isset($_GET['comment_reported'])): ?>
        <div class="success-message" style="max-width:1380px;margin:0 auto;width:100%;">Commentaire signalé avec succès.</div>
    <?php endif; ?>

    <section class="forum-main-layout">
        <div class="forum-feed">

            <article class="panel composer-card">
                <div class="composer-top">
                    <div class="mini-avatar"><?php echo e($currentUserAvatarLetter); ?></div>

                    <button type="button" class="composer-open-btn" id="openCreateModalBtn">
                        Rechercher un post...
                    </button>

                    <div class="composer-icons">
                        <button type="button" class="composer-icon-btn" id="openPhotoBtn">🖼️</button>
                        <button type="button" class="composer-icon-btn" id="openVideoBtn">🎥</button>
                        <button type="button" class="composer-icon-btn" id="openEmojiBtn">😊</button>
                    </div>
                </div>
            </article>

            <div class="forum-posts-list">
                <?php if (empty($posts)): ?>
                    <article class="panel">
                        <span class="section-badge">Aucun résultat</span>
                        <p style="margin-top:14px;">Aucun post trouvé.</p>
                    </article>
                <?php else: ?>
                    <?php foreach ($posts as $post): ?>
                        <?php
                            $fullname = trim(($post['prenom'] ?? '') . ' ' . ($post['nom'] ?? ''));
                            if ($fullname === '') {
                                $fullname = 'Utilisateur';
                            }

                            $avatarLetter = strtoupper(substr($fullname, 0, 1));
                            $imageUrl = !empty($post['image']) ? '/GoService/' . ltrim($post['image'], '/') : '';
                            $videoUrl = !empty($post['video']) ? '/GoService/' . ltrim($post['video'], '/') : '';
                            $type = $post['type_post'] ?? 'Discussion';

                            $likeCount = (int)($post['likes_count'] ?? $post['nb_likes'] ?? $post['total_likes'] ?? 0);
                            $commentCount = (int)($post['comments_count'] ?? $post['nb_comments'] ?? $post['total_comments'] ?? 0);
                            $shareCount = (int)($post['shares_count'] ?? $post['nb_shares'] ?? $post['total_shares'] ?? 0);
                            $saveCount = (int)($post['saves_count'] ?? $post['nb_saves'] ?? $post['total_saves'] ?? 0);

                            $viewerPayload = [
                                'id' => (int)($post['id_post'] ?? 0),
                                'type' => !empty($videoUrl) ? 'video' : 'image',
                                'src' => !empty($videoUrl) ? $videoUrl : $imageUrl,
                                'title' => $post['titre'] ?? '',
                                'content' => $post['contenu'] ?? '',
                                'user' => $fullname,
                                'time' => timeAgo($post['date_publication'] ?? ''),
                                'date' => dateOnly($post['date_publication'] ?? ''),
                                'image' => $imageUrl,
                                'video' => $videoUrl,
                                'likes' => $likeCount,
                                'comments' => $commentCount,
                                'shares' => $shareCount,
                                'saves' => (int)($saveCount ?? 0),
                                'is_liked' => !empty($post['is_liked']),
                                'is_saved' => !empty($post['is_saved']),
                                'comments_data' => $post['comments'] ?? []
                            ];
                        ?>
                        <article class="post-card" id="post-<?php echo (int)$post['id_post']; ?>">
                            <div class="post-header-row">
                                <div class="post-user">
                                    <div class="mini-avatar"><?php echo e($avatarLetter); ?></div>

                                    <div>
                                        <strong><?php echo e($fullname); ?></strong>

                                        <div class="post-meta-line">
                                            <span class="post-type-badge <?php echo typeBadgeClass($type); ?>">
                                                <?php echo e($type); ?>
                                            </span>
                                            <span class="post-date-exact"><?php echo e(dateOnly($post['date_publication'] ?? '')); ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="post-menu-wrap">
                                    <span class="post-time-top"><?php echo e(timeAgo($post['date_publication'] ?? '')); ?></span>
                                    <button class="post-menu-btn" type="button" onclick="togglePostMenu(<?php echo (int)$post['id_post']; ?>)">⋯</button>

                                    <div class="post-dropdown" id="post-menu-<?php echo (int)$post['id_post']; ?>">
                                        <a href="<?php echo e(forumUrl(['edit' => (int)$post['id_post']])); ?>" onclick="localStorage.setItem('openForumModal','1')">
                                            ✏️ Modifier
                                        </a>

                                        <button type="button" onclick="openReportModal(<?php echo (int)$post['id_post']; ?>)" style="display:block; width:100%; text-align:left; padding:10px; border:none; background:none; cursor:pointer;">
                                            🚩 Signaler
                                        </button>

                                        <form method="POST" action="" onsubmit="return confirm('Supprimer ce post ?');">
                                            <input type="hidden" name="post_id" value="<?php echo (int)$post['id_post']; ?>">
                                            <button type="submit" name="delete_post" class="danger-action" style="width:100%; text-align:left; display:block; padding:10px;">
                                                🗑 Supprimer
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <h3 class="post-title"><?php echo e($post['titre'] ?? ''); ?></h3>
                            <p class="post-content"><?php echo nl2br(e($post['contenu'] ?? '')); ?></p>

                            <?php if (!empty($post['emoji_post'])): ?>
                                <div class="comment-emoji-line" style="margin-top:10px;"><?php echo e($post['emoji_post']); ?></div>
                            <?php endif; ?>

                            <?php if (!empty($imageUrl)): ?>
                                <div class="post-media-frame" onclick='openMediaViewer(<?php echo json_encode($viewerPayload, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                    <img src="<?php echo e($imageUrl); ?>" alt="Image post" onerror="this.style.display='none';">
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($videoUrl)): ?>
                                <div class="post-media-frame" onclick='openMediaViewer(<?php echo json_encode($viewerPayload, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                    <video
                                        autoplay
                                        muted
                                        loop
                                        playsinline
                                        preload="metadata"
                                        onloadeddata="this.play().catch(() => {})"
                                    >
                                        <source src="<?php echo e($videoUrl); ?>">
                                    </video>
                                    <div class="post-media-overlay">Vidéo</div>
                                </div>
                            <?php endif; ?>

                            <div class="forum-reactions-bar">
                                <form method="POST" action="">
                                    <input type="hidden" name="toggle_like" value="1">
                                    <input type="hidden" name="post_id" value="<?php echo (int)$post['id_post']; ?>">
                                    <button type="submit" class="post-reaction-btn" title="Aimer">
                                        <span class="reaction-label"><?php echo !empty($post['is_liked']) ? '❤️ Aimer' : '👍 Aimer'; ?></span>
                                        <span class="reaction-count"><?php echo (int)($post['likes_count'] ?? 0); ?></span>
                                    </button>
                                </form>

                                <button class="post-reaction-btn" type="button" onclick="toggleCommentBox(<?php echo (int)$post['id_post']; ?>)" title="Commenter">
                                    <span class="reaction-label">💬 Commenter</span>
                                    <span class="reaction-count"><?php echo (int)($post['comments_count'] ?? count($post['comments'] ?? [])); ?></span>
                                </button>

                                <form method="POST" action="">
                                    <input type="hidden" name="share_post" value="1">
                                    <input type="hidden" name="post_id" value="<?php echo (int)$post['id_post']; ?>">
                                    <button type="submit" class="post-reaction-btn" title="Partager">
                                        <span class="reaction-label">🔁 Partager</span>
                                        <span class="reaction-count"><?php echo (int)($post['shares_count'] ?? 0); ?></span>
                                    </button>
                                </form>

                                <form method="POST" action="">
                                    <input type="hidden" name="toggle_save" value="1">
                                    <input type="hidden" name="post_id" value="<?php echo (int)$post['id_post']; ?>">
                                    <button type="submit" class="post-reaction-btn" title="Enregistrer">
                                        <span class="reaction-label"><?php echo !empty($post['is_saved']) ? '📌 Enregistré' : '🔖 Enregistrer'; ?></span>
                                        <span class="reaction-count"><?php echo (int)($post['saves_count'] ?? 0); ?></span>
                                    </button>
                                </form>
                            </div>

                            <div class="comment-box <?php echo (isset($_GET['open_post']) && (int)$_GET['open_post'] === (int)$post['id_post']) ? "show" : "hidden"; ?>" id="comment-box-<?php echo (int)$post['id_post']; ?>">
                                <h4>Commentaires</h4>

                                <div class="panel comment-form-panel" style="margin-top:16px;">
                                    <span class="section-badge">Ajouter un commentaire</span>

                                    <form method="POST" action="" enctype="multipart/form-data" novalidate class="comment-form">
                                        <input type="hidden" name="add_comment" value="1">
                                        <input type="hidden" name="post_id" value="<?php echo (int)$post['id_post']; ?>">
                                        <textarea
                                            name="comment_content"
                                            id="comment-content-<?php echo (int)$post['id_post']; ?>"
                                            class="comment-area comment-emoji-target"
                                            placeholder="Écrire un commentaire..."
                                            required
                                        ></textarea>

                                        <input type="text" name="emoji_content" id="emoji-hidden-<?php echo (int)$post['id_post']; ?>" value="" class="comment-emoji-input comment-emoji-target" placeholder="😊">

                                        <span class="field-error" id="err-comment-content-<?php echo (int)$post['id_post']; ?>" style="color:#dc2626; font-size:12px; display:block; margin-top:4px;"></span>

                                        <div class="comment-tools">
                                            <label class="comment-tool-btn" title="Ajouter une image">
                                                🖼️
                                                <input type="file" name="comment_image" accept=".jpg,.jpeg,.png,.webp" class="comment-hidden-input" id="comment-img-<?php echo (int)$post['id_post']; ?>">
                                            </label>

                                            <button
                                                type="button"
                                                class="comment-tool-btn comment-emoji-btn"
                                                data-target="emoji-hidden-<?php echo (int)$post['id_post']; ?>"
                                                title="Ajouter un emoji"
                                            >😊</button>

                                            <button type="submit" name="add_comment" class="solid-btn">Publier</button>
                                        </div>
                                    </form>
                                </div>

                                <?php if (!empty($post['comments'])): ?>
                                    <div class="comments-list" style="margin-top:20px;">
                                        <?php
                                            $rootComments = [];
                                            $replyMap = [];

                                            foreach (($post['comments'] ?? []) as $commentItem) {
                                                $parentKey = (int)($commentItem['id_parent_commentaire'] ?? 0);
                                                if ($parentKey > 0) {
                                                    if (!isset($replyMap[$parentKey])) {
                                                        $replyMap[$parentKey] = [];
                                                    }
                                                    $replyMap[$parentKey][] = $commentItem;
                                                } else {
                                                    $rootComments[] = $commentItem;
                                                }
                                            }
                                        ?>

                                        <?php foreach ($rootComments as $comment): ?>
                                            <?php
                                                $commentId = isset($comment['id_commentaire']) ? (int)$comment['id_commentaire'] : 0;
                                                $commentAuthor = trim(($comment['prenom'] ?? '') . ' ' . ($comment['nom'] ?? ''));
                                                if ($commentAuthor === '') $commentAuthor = 'Utilisateur';
                                                $commentImageUrl = !empty($comment['image_commentaire']) ? '/GoService/' . ltrim($comment['image_commentaire'], '/') : '';
                                                $commentReplies = $replyMap[$commentId] ?? [];
                                            ?>
                                            <div class="comment-thread" id="comment-thread-<?php echo $commentId; ?>">
                                                <div class="comment-item comment-main-item">
                                                    <div class="comment-avatar-wrap">
                                                        <div class="mini-avatar"><?php echo e(strtoupper(substr($commentAuthor, 0, 1))); ?></div>
                                                    </div>

                                                    <div class="comment-body">
                                                        <div class="comment-bubble" id="comment-bubble-<?php echo $commentId; ?>">

                                                            <!-- En-tête avec auteur + menu 3 points -->
                                                            <div class="comment-header-row">
                                                                <strong class="comment-author"><?php echo e($commentAuthor); ?></strong>

                                                                <div class="comment-menu-wrap">
                                                                    <button
                                                                        type="button"
                                                                        class="comment-menu-btn"
                                                                        onclick="toggleCommentMenu('cmenu-<?php echo $commentId; ?>')"
                                                                        title="Options"
                                                                    >⋯</button>

                                                                    <div class="comment-dropdown" id="cmenu-<?php echo $commentId; ?>">
                                                                        <button type="button" onclick="startEditComment(<?php echo $commentId; ?>, <?php echo (int)$post['id_post']; ?>)">
                                                                            ✏️ Modifier
                                                                        </button>
                                                                        <button type="button" onclick="openReportCommentModal(<?php echo $commentId; ?>, <?php echo (int)$post['id_post']; ?>)">
                                                                            🚩 Signaler
                                                                        </button>
                                                                        <button
                                                                            type="button"
                                                                            class="danger"
                                                                            onclick="deleteComment(<?php echo $commentId; ?>, <?php echo (int)$post['id_post']; ?>, 0)"
                                                                        >
                                                                            🗑 Supprimer
                                                                        </button>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <!-- Fin en-tête -->

                                                            <!-- Contenu affiché -->
                                                            <div id="comment-text-<?php echo $commentId; ?>">
                                                                <?php if (!empty($comment['contenu_commentaire'])): ?>
                                                                    <div class="comment-text"><?php echo nl2br(e($comment['contenu_commentaire'])); ?></div>
                                                                <?php endif; ?>

                                                                <?php if (!empty($comment['emoji_commentaire'])): ?>
                                                                    <div class="comment-emoji-line"><?php echo e($comment['emoji_commentaire']); ?></div>
                                                                <?php endif; ?>

                                                                <?php if (!empty($commentImageUrl)): ?>
                                                                    <div class="comment-image-wrap">
                                                                        <img src="<?php echo e($commentImageUrl); ?>" alt="Image commentaire" class="comment-image">
                                                                    </div>
                                                                <?php endif; ?>
                                                            </div>

                                                            <!-- Zone d'édition (cachée par défaut) -->
                                                            <form method="POST" action="" enctype="multipart/form-data" id="comment-edit-zone-<?php echo $commentId; ?>" style="display:none;" class="comment-edit-form">
                                                                <input type="hidden" name="update_comment" value="1">
                                                                <input type="hidden" name="comment_id" value="<?php echo $commentId; ?>">
                                                                <input type="hidden" name="post_id" value="<?php echo (int)$post['id_post']; ?>">
                                                                <textarea
                                                                    name="comment_content"
                                                                    class="comment-edit-area comment-emoji-target"
                                                                    id="comment-edit-input-<?php echo $commentId; ?>"
                                                                ><?php echo e($comment['contenu_commentaire'] ?? ''); ?></textarea>

                                                                <input type="text" name="emoji_content" id="edit-emoji-<?php echo $commentId; ?>" value="<?php echo e($comment['emoji_commentaire'] ?? ''); ?>" class="comment-emoji-input comment-emoji-target" placeholder="😊">

                                                                <span class="field-error" id="err-edit-comment-<?php echo $commentId; ?>"></span>

                                                                <div class="comment-tools">
                                                                    <label class="comment-tool-btn" title="Modifier l'image">
                                                                        🖼️
                                                                        <input type="file" name="comment_image" accept=".jpg,.jpeg,.png,.webp" class="comment-hidden-input">
                                                                    </label>

                                                                    <button
                                                                        type="button"
                                                                        class="comment-tool-btn comment-emoji-btn"
                                                                        data-target="edit-emoji-<?php echo $commentId; ?>"
                                                                        title="Ajouter un emoji"
                                                                    >😊</button>

                                                                    <button type="submit" class="comment-edit-save-btn">Enregistrer</button>
                                                                    <button type="button" class="comment-edit-cancel-btn" onclick="cancelEditComment(<?php echo $commentId; ?>)">Annuler</button>
                                                                </div>
                                                            </form>
                                                        </div>

                                                        <div class="comment-meta-row">
                                                            <span class="comment-time"><?php echo e(timeAgo($comment['date_commentaire'] ?? '')); ?></span>
                                                            <button
                                                                type="button"
                                                                class="reply-btn"
                                                                data-comment-id="<?php echo $commentId; ?>"
                                                                data-author="<?php echo e($commentAuthor); ?>"
                                                            >Répondre</button>
                                                        </div>

                                                        <div class="reply-box" id="reply-box-<?php echo $commentId; ?>" style="display:none;">
                                                            <form method="POST" action="" enctype="multipart/form-data" novalidate class="reply-form">
                                                                <input type="hidden" name="add_comment" value="1">
                                                                <input type="hidden" name="post_id" value="<?php echo (int)$post['id_post']; ?>">
                                                                <input type="hidden" name="parent_id" value="<?php echo $commentId; ?>">
                                                                <textarea
                                                                    name="comment_content"
                                                                    id="reply-content-<?php echo $commentId; ?>"
                                                                    class="comment-area reply-area comment-emoji-target"
                                                                    placeholder="Votre réponse..."
                                                                    required
                                                                ></textarea>

                                                                <input type="text" name="emoji_content" id="emoji-reply-<?php echo $commentId; ?>" value="" class="comment-emoji-input comment-emoji-target" placeholder="😊">

                                                                <span id="err-reply-content-<?php echo $commentId; ?>" class="field-error" style="color:#dc2626; font-size:11px; display:block; margin-top:4px;"></span>

                                                                <div class="comment-tools">
                                                                    <label class="comment-tool-btn reply-tool-btn" title="Ajouter une image">
                                                                        🖼️
                                                                        <input type="file" name="comment_image" accept=".jpg,.jpeg,.png,.webp" class="comment-hidden-input" id="reply-img-<?php echo $commentId; ?>">
                                                                    </label>

                                                                    <button
                                                                        type="button"
                                                                        class="comment-tool-btn reply-tool-btn comment-emoji-btn"
                                                                        data-target="emoji-reply-<?php echo $commentId; ?>"
                                                                        title="Ajouter un emoji"
                                                                    >😊</button>

                                                                    <button type="submit" name="add_comment" class="solid-btn reply-submit-btn">Répondre</button>
                                                                </div>
                                                            </form>
                                                        </div>

                                                        <?php if (!empty($commentReplies)): ?>
                                                            <div class="replies-list" id="replies-list-<?php echo $commentId; ?>">
                                                                <?php foreach ($commentReplies as $reply): ?>
                                                                    <?php
                                                                        $replyId = (int)($reply['id_commentaire'] ?? 0);
                                                                        $replyAuthor = trim(($reply['prenom'] ?? '') . ' ' . ($reply['nom'] ?? ''));
                                                                        if ($replyAuthor === '') $replyAuthor = 'Utilisateur';
                                                                        $replyImageUrl = !empty($reply['image_commentaire']) ? '/GoService/' . ltrim($reply['image_commentaire'], '/') : '';
                                                                    ?>
                                                                    <div class="reply-item" id="reply-thread-<?php echo $replyId; ?>">
                                                                        <div class="comment-avatar-wrap">
                                                                            <div class="mini-avatar"><?php echo e(strtoupper(substr($replyAuthor, 0, 1))); ?></div>
                                                                        </div>

                                                                        <div class="comment-body">
                                                                            <div class="comment-bubble reply-bubble" id="reply-bubble-<?php echo $replyId; ?>">

                                                                                <!-- En-tête réponse avec menu 3 points -->
                                                                                <div class="reply-header-row">
                                                                                    <strong class="comment-author"><?php echo e($replyAuthor); ?></strong>

                                                                                    <div class="comment-menu-wrap">
                                                                                        <button
                                                                                            type="button"
                                                                                            class="comment-menu-btn"
                                                                                            onclick="toggleCommentMenu('cmenu-reply-<?php echo $replyId; ?>')"
                                                                                            title="Options"
                                                                                        >⋯</button>

                                                                                        <div class="comment-dropdown" id="cmenu-reply-<?php echo $replyId; ?>">
                                                                                            <button type="button" onclick="startEditComment(<?php echo $replyId; ?>, <?php echo (int)$post['id_post']; ?>)">
                                                                                                ✏️ Modifier
                                                                                            </button>
                                                                                            <button type="button" onclick="openReportCommentModal(<?php echo $replyId; ?>, <?php echo (int)$post['id_post']; ?>)">
                                                                                                🚩 Signaler
                                                                                            </button>
                                                                                            <button
                                                                                                type="button"
                                                                                                class="danger"
                                                                                                onclick="deleteComment(<?php echo $replyId; ?>, <?php echo (int)$post['id_post']; ?>, <?php echo $commentId; ?>)"
                                                                                            >
                                                                                                🗑 Supprimer
                                                                                            </button>
                                                                                        </div>
                                                                                    </div>
                                                                                </div>
                                                                                <!-- Fin en-tête réponse -->

                                                                                <!-- Contenu réponse affiché -->
                                                                                <div id="comment-text-<?php echo $replyId; ?>">
                                                                                    <?php if (!empty($reply['contenu_commentaire'])): ?>
                                                                                        <div class="comment-text"><?php echo nl2br(e($reply['contenu_commentaire'])); ?></div>
                                                                                    <?php endif; ?>

                                                                                    <?php if (!empty($reply['emoji_commentaire'])): ?>
                                                                                        <div class="comment-emoji-line"><?php echo e($reply['emoji_commentaire']); ?></div>
                                                                                    <?php endif; ?>

                                                                                    <?php if (!empty($replyImageUrl)): ?>
                                                                                        <div class="comment-image-wrap">
                                                                                            <img src="<?php echo e($replyImageUrl); ?>" alt="Image réponse" class="comment-image">
                                                                                        </div>
                                                                                    <?php endif; ?>
                                                                                </div>

                                                                                <!-- Zone d'édition réponse (cachée) -->
                                                                                <form method="POST" action="" enctype="multipart/form-data" id="comment-edit-zone-<?php echo $replyId; ?>" style="display:none;" class="comment-edit-form">
                                                                                    <input type="hidden" name="update_comment" value="1">
                                                                                    <input type="hidden" name="comment_id" value="<?php echo $replyId; ?>">
                                                                                    <input type="hidden" name="post_id" value="<?php echo (int)$post['id_post']; ?>">
                                                                                    <textarea
                                                                                        name="comment_content"
                                                                                        class="comment-edit-area comment-emoji-target"
                                                                                        id="comment-edit-input-<?php echo $replyId; ?>"
                                                                                    ><?php echo e($reply['contenu_commentaire'] ?? ''); ?></textarea>

                                                                                    <input type="text" name="emoji_content" id="edit-emoji-<?php echo $replyId; ?>" value="<?php echo e($reply['emoji_commentaire'] ?? ''); ?>" class="comment-emoji-input comment-emoji-target" placeholder="😊">

                                                                                    <span class="field-error" id="err-edit-comment-<?php echo $replyId; ?>"></span>

                                                                                    <div class="comment-tools">
                                                                                        <label class="comment-tool-btn reply-tool-btn" title="Modifier l'image">
                                                                                            🖼️
                                                                                            <input type="file" name="comment_image" accept=".jpg,.jpeg,.png,.webp" class="comment-hidden-input">
                                                                                        </label>

                                                                                        <button
                                                                                            type="button"
                                                                                            class="comment-tool-btn reply-tool-btn comment-emoji-btn"
                                                                                            data-target="edit-emoji-<?php echo $replyId; ?>"
                                                                                            title="Ajouter un emoji"
                                                                                        >😊</button>

                                                                                        <button type="submit" class="comment-edit-save-btn">Enregistrer</button>
                                                                                        <button type="button" class="comment-edit-cancel-btn" onclick="cancelEditComment(<?php echo $replyId; ?>)">Annuler</button>
                                                                                    </div>
                                                                                </form>
                                                                            </div>

                                                                            <div class="comment-meta-row">
                                                                                <span class="comment-time"><?php echo e(timeAgo($reply['date_commentaire'] ?? '')); ?></span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <div class="replies-list" id="replies-list-<?php echo $commentId; ?>"></div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="forum-right-panel">
            <article class="panel">
                <span class="section-badge">Top contributeurs</span>

                <div class="top-contributors-list">
                    <?php if (empty($topContributors)): ?>
                        <div class="top-contributor-item">
                            <div class="top-contributor-left">
                                <div class="mini-avatar">U</div>
                                <div>
                                    <strong>Aucun utilisateur</strong>
                                    <span>0 posts</span>
                                </div>
                            </div>
                            <div class="top-contributor-count">0</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($topContributors as $contributor): ?>
                            <?php
                                $fullName = trim(($contributor['prenom'] ?? '') . ' ' . ($contributor['nom'] ?? ''));
                                if ($fullName === '') $fullName = 'Utilisateur';
                                $letter = strtoupper(substr($fullName, 0, 1));
                            ?>
                            <div class="top-contributor-item">
                                <div class="top-contributor-left">
                                    <div class="mini-avatar"><?php echo e($letter); ?></div>
                                    <div>
                                        <strong><?php echo e($fullName); ?></strong>
                                        <span><?php echo (int)($contributor['total_posts'] ?? 0); ?> posts</span>
                                    </div>
                                </div>
                                <div class="top-contributor-count"><?php echo (int)($contributor['total_posts'] ?? 0); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </article>

            <article class="panel">
                <span class="section-badge">Posts récents</span>

                <div class="recent-posts-list">
                    <?php foreach ($allPosts as $rpIndex => $rp): ?>
                        <?php if ($rpIndex < 5): ?>
                            <div class="recent-post-item">
                                <div class="recent-post-left">
                                    <div class="mini-avatar"><?php echo strtoupper(substr($rp['titre'] ?? 'P', 0, 1)); ?></div>
                                    <div>
                                        <strong><?php echo e($rp['titre'] ?? 'Post'); ?></strong>
                                        <span><?php echo e(timeAgo($rp['date_publication'] ?? '')); ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </article>
        </div>
    </section>
</div>

<!-- ============================================================
     MODAL CRÉER / MODIFIER POST
     ============================================================ -->
<div class="modal-overlay" id="forumModal">
    <div class="forum-modal">
        <div class="forum-modal-head">
            <div class="forum-modal-title"><?php echo $isEditMode ? 'Modifier la publication' : 'Créer une publication'; ?></div>
            <button type="button" class="forum-modal-close" id="closeForumModal">×</button>
        </div>

        <div class="forum-modal-body">
            <div class="forum-modal-user">
                <div class="mini-avatar"><?php echo e($currentUserAvatarLetter); ?></div>
                <div class="forum-modal-name"><?php echo e($currentUserName); ?></div>
            </div>

            <form method="POST" enctype="multipart/form-data" id="postForm" novalidate>
                <?php if ($isEditMode): ?>
                    <input type="hidden" name="edit_id" value="<?php echo (int)$editId; ?>">
                <?php endif; ?>

                <div class="forum-form-grid">
                    <div class="forum-form-field full-width">
                        <input
                            type="text"
                            name="titre"
                            id="titre"
                            placeholder="Titre du post"
                            value="<?php echo e($old['titre']); ?>"
                            class="<?php echo invalidClass($errors['titre']); ?>"
                        >
                        <span class="field-error" id="err-titre"><?php echo e($errors['titre']); ?></span>
                    </div>

                    <div class="forum-form-field">
                        <select
                            name="type_post"
                            id="type_post"
                            class="<?php echo invalidClass($errors['type_post']); ?>"
                        >
                            <option value="">Type de post</option>
                            <option value="Discussion" <?php echo $old['type_post'] === 'Discussion' ? 'selected' : ''; ?>>Discussion</option>
                            <option value="Conseil" <?php echo $old['type_post'] === 'Conseil' ? 'selected' : ''; ?>>Conseil</option>
                            <option value="Question" <?php echo $old['type_post'] === 'Question' ? 'selected' : ''; ?>>Question</option>
                        </select>
                        <span class="field-error" id="err-type_post"><?php echo e($errors['type_post']); ?></span>
                    </div>

                    <div class="forum-form-field full-width">
                        <textarea
                            name="contenu"
                            id="contenu"
                            placeholder="Description"
                            class="post-emoji-target <?php echo invalidClass($errors['contenu']); ?>"
                        ><?php echo e($old['contenu']); ?></textarea>
                        <span class="field-error" id="err-contenu"><?php echo e($errors['contenu']); ?></span>
                    </div>

                    <div class="forum-form-field full-width">
                        <input
                            type="text"
                            name="emoji_post"
                            id="emoji_post"
                            placeholder="Emoji du post"
                            value="<?php echo e($old['emoji_post'] ?? ''); ?>"
                            class="forum-emoji-input"
                           
                        >
                    </div>
                </div>

                <div class="forum-modal-tools">
                    <div>Ajouter à votre publication</div>

                    <div class="forum-tool-icons">
                        <button type="button" class="tool-trigger" id="photoTrigger">🖼️</button>
                        <button type="button" class="tool-trigger" id="videoTrigger">🎥</button>
                        <button type="button" class="tool-trigger emoji-open-btn" data-target="emoji_post" id="emojiTrigger">😊</button>
                    </div>
                </div>

                <div class="forum-form-field full-width" style="margin-top:14px;">
                    <input type="file" name="image" id="image" accept=".jpg,.jpeg,.png,.webp" class="<?php echo invalidClass($errors['image']); ?>" style="display:none;">
                    <span class="field-error" id="err-image"><?php echo e($errors['image']); ?></span>

                    <div class="forum-upload-preview" id="forumUploadPreview">
                        <img id="forumPreviewImg" src="" alt="Prévisualisation image">
                    </div>

                    <?php if ($isEditMode && $editPost && !empty($editPost['image'])): ?>
                        <div class="forum-current-image show" id="forumCurrentImage">
                            <img src="/GoService/<?php echo e($editPost['image']); ?>" alt="Image actuelle">
                        </div>
                    <?php else: ?>
                        <div class="forum-current-image" id="forumCurrentImage"></div>
                    <?php endif; ?>
                </div>

                <div class="forum-form-field full-width" style="margin-top:14px;">
                    <input type="file" name="video" id="video" accept=".mp4,.webm,.ogg" class="<?php echo invalidClass($errors['video']); ?>" style="display:none;">
                    <span class="field-error" id="err-video"><?php echo e($errors['video']); ?></span>

                    <div class="forum-video-preview" id="forumVideoPreview">
                        <video id="forumPreviewVideo" controls autoplay muted loop playsinline></video>
                    </div>

                    <?php if ($isEditMode && $editPost && !empty($editPost['video'])): ?>
                        <div class="forum-current-video show" id="forumCurrentVideo">
                            <video controls autoplay muted loop playsinline>
                                <source src="/GoService/<?php echo e($editPost['video']); ?>">
                            </video>
                        </div>
                    <?php else: ?>
                        <div class="forum-current-video" id="forumCurrentVideo"></div>
                    <?php endif; ?>
                </div>

                <div class="forum-form-actions">
                    <button type="button" class="outline-btn" id="cancelForumModal">Annuler</button>

                    <?php if ($isEditMode): ?>
                        <button class="solid-btn" type="submit" name="update_post">Mettre à jour</button>
                    <?php else: ?>
                        <button class="solid-btn" type="submit" name="publish_post">Publier</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================
     MEDIA VIEWER
     ============================================================ -->
<div class="media-viewer-overlay" id="mediaViewer">
    <button type="button" class="media-viewer-close" id="closeMediaViewer">×</button>

    <div class="media-viewer-content" id="imageViewerContent">
        <div class="media-viewer-image-layout">
            <div class="media-viewer-image-main" id="imageViewerMain"></div>

            <div class="media-viewer-image-side">
                <div class="viewer-post-head">
                    <div class="viewer-user">
                        <div class="mini-avatar" id="viewerUserAvatar">U</div>
                        <div class="viewer-user-meta">
                            <strong id="viewerUserName">Utilisateur</strong>
                            <span id="viewerPostTime">à l'instant</span>
                        </div>
                    </div>
                </div>

                <div class="viewer-post-body">
                    <div class="viewer-post-title" id="viewerPostTitle"></div>
                    <div id="viewerPostContent"></div>
                </div>

                <div class="viewer-stats" id="viewerStatsRow">
                    <span id="viewerLikesWrap" style="display:none;">👍 <span id="viewerLikesCount">0</span></span>
                    <span id="viewerCommentsWrap" style="display:none;">💬 <span id="viewerCommentsCount">0</span></span>
                    <span id="viewerSharesWrap" style="display:none;">🔁 <span id="viewerSharesCount">0</span></span>
                </div>

                <div class="viewer-actions">
                    <form method="POST" action="" style="margin:0;">
                        <input type="hidden" name="toggle_like" value="1">
                        <input type="hidden" name="post_id" id="viewerLikePostId" value="">
                        <button class="viewer-action-btn" type="submit" id="viewerLikeBtn" title="Aimer">👍</button>
                    </form>
                    <button class="viewer-action-btn" type="button" id="viewerCommentBtn" title="Commenter">💬</button>
                    <form method="POST" action="" style="margin:0;">
                        <input type="hidden" name="share_post" value="1">
                        <input type="hidden" name="post_id" id="viewerSharePostId" value="">
                        <button class="viewer-action-btn" type="submit" title="Partager">🔁</button>
                    </form>
                    <form method="POST" action="" style="margin:0;">
                        <input type="hidden" name="toggle_save" value="1">
                        <input type="hidden" name="post_id" id="viewerSavePostId" value="">
                        <button class="viewer-action-btn" type="submit" id="viewerSaveBtn" title="Enregistrer">🔖</button>
                    </form>
                </div>

                <div class="viewer-comments" id="viewerComments"></div>

                <div class="viewer-comment-form-wrap" style="padding:18px; border-top:1px solid rgba(255,255,255,.08);">
                    <form method="POST" action="" enctype="multipart/form-data" id="viewerCommentForm">
                        <input type="hidden" name="add_comment" value="1">
                        <input type="hidden" name="post_id" id="viewerPostId" value="">
                        <input type="hidden" name="parent_id" id="viewerParentId" value="">
                        <input type="text" name="emoji_content" id="viewerEmojiHidden" value="" class="comment-emoji-input comment-emoji-target" placeholder="Emoji">

                        <textarea
                            name="comment_content"
                            id="viewerCommentContent"
                            class="viewer-comment-input"
                            placeholder="Écrire un commentaire..."
                        ></textarea>
                        <span class="field-error" id="err-viewerCommentContent"></span>

                        <div class="viewer-comment-actions">
                            <label class="viewer-square-btn" title="Ajouter une image">🖼️
                                <input type="file" name="comment_image" accept=".jpg,.jpeg,.png,.webp" class="comment-hidden-input" id="viewerCommentImage">
                            </label>

                            <button type="button" class="viewer-square-btn comment-emoji-btn" data-target="viewerEmojiHidden" title="Ajouter un emoji">😊</button>

                            <button type="submit" class="viewer-publish-btn">Publier</button>
                        </div>
                        <div class="comment-image-preview" id="viewerCommentPreview"><img alt="Prévisualisation image commentaire popup"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="media-viewer-content" id="videoViewerContent">
        <div class="media-viewer-video-layout">
            <div class="media-viewer-video-main" id="videoViewerMain"></div>

            <div class="media-viewer-video-side">
                <div class="media-viewer-action" id="reelLikesWrap" style="display:none;">
                    <div class="viewer-side-icon">👍</div>
                    <span id="reelLikesCount">0</span>
                </div>
                <div class="media-viewer-action" id="reelCommentsWrap" style="display:none;">
                    <div class="viewer-side-icon">💬</div>
                    <span id="reelCommentsCount">0</span>
                </div>
                <div class="media-viewer-action" id="reelSharesWrap" style="display:none;">
                    <div class="viewer-side-icon">🔁</div>
                    <span id="reelSharesCount">0</span>
                </div>
                <div class="media-viewer-action">
                    <div class="viewer-side-icon">⋯</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ============================================================
     EMOJI PICKER
     ============================================================ -->
<div class="emoji-picker" id="emojiPicker">
    <div class="emoji-picker-head">Choisir un emoji</div>

    <div class="emoji-picker-tabs" id="emojiTabs">
        <button type="button" class="emoji-tab active" data-group="smileys">Smileys</button>
        <button type="button" class="emoji-tab" data-group="people">People</button>
        <button type="button" class="emoji-tab" data-group="animals">Animals</button>
        <button type="button" class="emoji-tab" data-group="food">Food</button>
        <button type="button" class="emoji-tab" data-group="travel">Travel</button>
        <button type="button" class="emoji-tab" data-group="objects">Objects</button>
        <button type="button" class="emoji-tab" data-group="symbols">Symbols</button>
    </div>

    <div class="emoji-picker-body" id="emojiPickerBody"></div>
</div>

<!-- ============================================================
     MODAL SIGNALER POST
     ============================================================ -->
<div class="modal-overlay" id="reportModal" style="display:none; position:fixed; inset:0; background:rgba(20,39,56,.45); z-index:999999; align-items:center; justify-content:center;">
    <div class="forum-post-modal-box" style="width:min(600px,100%);">
        <div class="forum-post-modal-head">
            <h2>Signaler ce post</h2>
            <button type="button" class="forum-post-modal-close" onclick="closeReportModal()">×</button>
        </div>
        <div class="forum-post-modal-body">
            <form method="POST" action="">
                <input type="hidden" name="post_id" id="report-post-id" value="">
                <input type="hidden" name="report_post" value="1">
                <div style="margin-bottom:16px;">
                    <label style="display:block; font-weight:600; margin-bottom:8px; color:#333;">Raison du signalement</label>
                    <select name="report_reason" id="report-reason" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; font-size:14px;" required>
                        <option value="">Choisir une raison</option>
                        <option value="spam">Spam</option>
                        <option value="inappropriate">Contenu inapproprié</option>
                        <option value="offensive">Contenu offensant</option>
                        <option value="misinformation">Désinformation</option>
                        <option value="other">Autre</option>
                    </select>
                </div>
                <div style="margin-bottom:16px;">
                    <label style="display:block; font-weight:600; margin-bottom:8px; color:#333;">Détails supplémentaires (optionnel)</label>
                    <textarea name="report_details" placeholder="Expliquez pourquoi vous signalez ce post..." style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; font-size:14px; min-height:100px; font-family:inherit; resize:vertical;"></textarea>
                </div>
                <div style="display:flex; gap:10px; justify-content:flex-end;">
                    <button type="button" onclick="closeReportModal()" class="ghost-btn">Annuler</button>
                    <button type="submit" class="solid-btn">Signaler</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================
     MODAL SIGNALER COMMENTAIRE
     ============================================================ -->
<div id="reportCommentModal">
    <div style="background:#fff; border-radius:24px; padding:28px; width:min(520px,100%); box-shadow:0 30px 80px rgba(15,23,42,.25);">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
            <h2 style="margin:0; font-size:22px; color:#17283f;">Signaler ce commentaire</h2>
            <button type="button" onclick="closeReportCommentModal()" style="width:38px; height:38px; border:none; border-radius:50%; background:#f2f4f8; font-size:20px; cursor:pointer;">×</button>
        </div>
        <form method="POST" action="" id="reportCommentForm">
            <input type="hidden" name="report_comment" value="1">
            <input type="hidden" name="comment_id" id="report-comment-id" value="">
            <input type="hidden" name="post_id" id="report-comment-post-id" value="">

            <div style="margin-bottom:16px;">
                <label style="display:block; font-weight:600; margin-bottom:8px; color:#333;">Raison du signalement</label>
                <select name="report_reason" style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; font-size:14px;" required>
                    <option value="">Choisir une raison</option>
                    <option value="spam">Spam</option>
                    <option value="inappropriate">Contenu inapproprié</option>
                    <option value="offensive">Contenu offensant</option>
                    <option value="misinformation">Désinformation</option>
                    <option value="other">Autre</option>
                </select>
            </div>
            <div style="margin-bottom:20px;">
                <label style="display:block; font-weight:600; margin-bottom:8px; color:#333;">Détails (optionnel)</label>
                <textarea name="report_details" placeholder="Expliquez pourquoi..." style="width:100%; padding:10px; border:1px solid #ddd; border-radius:8px; font-size:14px; min-height:90px; font-family:inherit; resize:vertical; box-sizing:border-box;"></textarea>
            </div>
            <div style="display:flex; gap:10px; justify-content:flex-end;">
                <button type="button" onclick="closeReportCommentModal()" style="padding:10px 20px; border:1px solid #ddd; border-radius:10px; background:#fff; cursor:pointer; font-weight:600;">Annuler</button>
                <button type="submit" class="solid-btn">Signaler</button>
            </div>
        </form>
    </div>
</div>

<!-- ============================================================
     FORMULAIRE CACHÉ POUR DELETE / UPDATE COMMENTAIRE
     (soumis via JS)
     ============================================================ -->
<form method="POST" action="" id="deleteCommentForm" style="display:none;">
    <input type="hidden" name="delete_comment" value="1">
    <input type="hidden" name="comment_id" id="deleteCommentId" value="">
    <input type="hidden" name="post_id" id="deleteCommentPostId" value="">
    <input type="hidden" name="parent_id" id="deleteCommentParentId" value="">
</form>

<form method="POST" action="" id="updateCommentForm" style="display:none;">
    <input type="hidden" name="update_comment" value="1">
    <input type="hidden" name="comment_id" id="updateCommentId" value="">
    <input type="hidden" name="post_id" id="updateCommentPostId" value="">
    <input type="hidden" name="comment_content" id="updateCommentContent" value="">
</form>

<script>
/* ============================================================
   Variables globales
   ============================================================ */
const forumModal = document.getElementById('forumModal');
const openCreateModalBtn = document.getElementById('openCreateModalBtn');
const closeForumModal = document.getElementById('closeForumModal');
const cancelForumModal = document.getElementById('cancelForumModal');
const photoTrigger = document.getElementById('photoTrigger');
const videoTrigger = document.getElementById('videoTrigger');
const openPhotoBtn = document.getElementById('openPhotoBtn');
const openVideoBtn = document.getElementById('openVideoBtn');
const openEmojiBtn = document.getElementById('openEmojiBtn');

const imageField = document.getElementById('image');
const videoField = document.getElementById('video');
const previewBox = document.getElementById('forumUploadPreview');
const previewImg = document.getElementById('forumPreviewImg');
const videoPreviewBox = document.getElementById('forumVideoPreview');
const previewVideo = document.getElementById('forumPreviewVideo');
const currentImageBox = document.getElementById('forumCurrentImage');
const currentVideoBox = document.getElementById('forumCurrentVideo');

const postForm = document.getElementById('postForm');
const contenuField = document.getElementById('contenu');

const mediaViewer = document.getElementById('mediaViewer');
const closeMediaViewer = document.getElementById('closeMediaViewer');

const imageViewerContent = document.getElementById('imageViewerContent');
const videoViewerContent = document.getElementById('videoViewerContent');
const imageViewerMain = document.getElementById('imageViewerMain');
const videoViewerMain = document.getElementById('videoViewerMain');

const emojiPicker = document.getElementById('emojiPicker');
const emojiPickerBody = document.getElementById('emojiPickerBody');
const emojiTabs = document.getElementById('emojiTabs');

let activeEmojiTarget = null;
let lastEmojiTrigger = null;

/* ============================================================
   Emojis
   ============================================================ */
const emojiGroups = {
    smileys: ['😀','😁','😂','🤣','😃','😄','😅','😆','😉','😊','🙂','🙃','😍','🥰','😘','😗','😙','😚','😋','😛','😜','🤪','😝','🫠','🤗','🤭','🫢','🤫','🤔','🫡','😐','😑','😶','🫥','😏','😒','🙄','😬','🤥','😌','😔','😪','🤤','😴','😷','🤒','🤕','🤢','🤮','🥵','🥶','🥴','😵','🤯','😎','🤩','🥳','😤','😭','😢','😡','🤬','😱','😨','😰','😥','😓','😳','🥹','😇'],
    people: ['👋','🤚','🖐️','✋','🫱','🫲','👌','🤌','🤏','✌️','🤞','🫰','🤟','🤘','👏','🙌','🫶','🤝','🙏','💪','🫵','👀','🧠','👶','🧒','👦','👧','🧑','👨','👩','🧔','👱','👴','👵','🙍','🙎','🙅','🙆','💁','🙋','🧏','🙇','🤦','🤷','👮','🧑‍💻','👨‍💻','👩‍💻','🧑‍🎓','👨‍🎓','👩‍🎓','🧑‍🔧','👨‍🔧','👩‍🔧'],
    animals: ['🐶','🐱','🐭','🐹','🐰','🦊','🐻','🐼','🐨','🐯','🦁','🐮','🐷','🐸','🐵','🙈','🙉','🙊','🐔','🐧','🐦','🐤','🦆','🦅','🦉','🦇','🐺','🐗','🐴','🦄','🐝','🪲','🐞','🦋','🐌','🐢','🐍','🦎','🦂','🦀','🐙','🦑','🐬','🐳','🦈'],
    food: ['🍏','🍎','🍐','🍊','🍋','🍌','🍉','🍇','🍓','🫐','🍈','🍒','🍑','🥭','🍍','🥥','🥝','🍅','🍆','🥑','🥦','🥬','🥒','🌶️','🫑','🌽','🥕','🫒','🧄','🧅','🥔','🍠','🥐','🍞','🥖','🧀','🍗','🍖','🍔','🍟','🍕','🌭','🥪','🌮','🌯','🥗','🍝','🍜','🍣','🍩','🍪','🎂','🍫','🍿','☕','🧃'],
    travel: ['🚗','🚕','🚙','🚌','🚎','🏎️','🚓','🚑','🚒','🚚','🚜','🏍️','🚲','✈️','🛫','🛬','🚀','🛸','🚁','⛵','🚤','🛳️','🚂','🚆','🚇','🚝','🗺️','🧭','🏖️','🏝️','🏜️','🏕️','🏔️','⛰️','🌋','🗽','🗼','🏰','🏟️','🎡','🎢'],
    objects: ['⌚','📱','💻','⌨️','🖥️','🖨️','🖱️','📷','📹','🎥','☎️','📞','📺','📻','🎙️','🎧','📢','💡','🔦','🕯️','🪫','🔋','🔌','💰','💳','🧾','📦','📌','✂️','🖊️','🖋️','📝','📚','🧸','🎁','🏆','⚽','🏀','🎮','🛒','🛠️','🔧','🔨'],
    symbols: ['❤️','🩷','🧡','💛','💚','🩵','💙','💜','🖤','🤍','🤎','💔','❣️','💕','💞','💓','💗','💖','💘','💝','💯','✅','✔️','✖️','❌','⚠️','🚫','⭐','🌟','✨','🔥','💥','🎉','🎊','🔔','📣','🔴','🟠','🟡','🟢','🔵','🟣','⚫','⚪']
};

/* ============================================================
   MODAL POST
   ============================================================ */
function openModal() {
    forumModal.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeModal(goClean = false) {
    forumModal.classList.remove('show');
    hideEmojiPicker();
    document.body.style.overflow = '';

    if (goClean) {
        window.location.href = '/GoService/view/front/index.php?page=forum';
    }
}

/* ============================================================
   MEDIA VIEWER
   ============================================================ */
function hideAllViewerModes() {
    imageViewerContent.classList.remove('show');
    videoViewerContent.classList.remove('show');
    imageViewerMain.innerHTML = '';
    videoViewerMain.innerHTML = '';
}

function setCountVisibility(wrapperId, value, countId) {
    const wrapper = document.getElementById(wrapperId);
    const countEl = document.getElementById(countId);
    if (!wrapper || !countEl) return;

    if (Number(value) > 0) {
        wrapper.style.display = '';
        countEl.textContent = value;
    } else {
        wrapper.style.display = 'none';
        countEl.textContent = 0;
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text || '';
    return div.innerHTML;
}

function formatViewerComments(comments) {
    const rootComments = [];
    const repliesByParent = {};

    (Array.isArray(comments) ? comments : []).forEach(comment => {
        const parentId = comment.id_parent_commentaire ? Number(comment.id_parent_commentaire) : 0;
        if (parentId > 0) {
            if (!repliesByParent[parentId]) repliesByParent[parentId] = [];
            repliesByParent[parentId].push(comment);
        } else {
            rootComments.push(comment);
        }
    });

    return rootComments.map(comment => {
        const commentId = Number(comment.id_commentaire || 0);
        const author = `${comment.prenom || ''} ${comment.nom || ''}`.trim() || 'Utilisateur';
        const authorLetter = author.charAt(0).toUpperCase();
        const content = escapeHtml(comment.contenu_commentaire || '');
        const emoji = escapeHtml(comment.emoji_commentaire || '');
        const time = escapeHtml(comment.date_commentaire || '');
        const image = comment.image_commentaire ? `/GoService/${String(comment.image_commentaire).replace(/^\/+/, '')}` : '';
        const safeAuthorJs = author.replace(/'/g, "\\'");

        const replies = repliesByParent[commentId] || [];
        const repliesHtml = replies.map(reply => {
            const replyAuthor = `${reply.prenom || ''} ${reply.nom || ''}`.trim() || 'Utilisateur';
            const replyLetter = replyAuthor.charAt(0).toUpperCase();
            const replyContent = escapeHtml(reply.contenu_commentaire || '');
            const replyEmoji = escapeHtml(reply.emoji_commentaire || '');
            const replyImage = reply.image_commentaire ? `/GoService/${String(reply.image_commentaire).replace(/^\/+/, '')}` : '';

            return `
                <div class="viewer-reply-item">
                    <div class="mini-avatar">${replyLetter}</div>
                    <div>
                        <div class="viewer-reply-bubble">
                            <span class="viewer-comment-author">${escapeHtml(replyAuthor)}</span>
                            ${replyContent ? `<div>${replyContent}</div>` : ''}
                            ${replyEmoji ? `<div style="margin-top:6px;">${replyEmoji}</div>` : ''}
                            ${replyImage ? `<div style="margin-top:8px;"><img src="${replyImage}" style="max-width:180px;border-radius:10px;"></div>` : ''}
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        return `
            <div class="viewer-comment-item">
                <div class="mini-avatar">${authorLetter}</div>
                <div class="viewer-comment-content">
                    <div class="viewer-comment-bubble">
                        <span class="viewer-comment-author">${escapeHtml(author)}</span>
                        ${content ? `<div>${content}</div>` : ''}
                        ${emoji ? `<div style="margin-top:6px;">${emoji}</div>` : ''}
                        ${image ? `<div style="margin-top:8px;"><img src="${image}" style="max-width:220px;border-radius:10px;"></div>` : ''}
                    </div>
                    <div class="viewer-comment-meta">
                        <span>${time}</span>
                        <button type="button" class="viewer-reply-btn" onclick="setViewerReplyTarget(${commentId}, '${safeAuthorJs}')">Répondre</button>
                    </div>
                    ${replies.length ? `<div class="viewer-replies">${repliesHtml}</div>` : ''}
                </div>
            </div>
        `;
    }).join('');
}

function setViewerReplyTarget(commentId, authorName) {
    const parentField = document.getElementById('viewerParentId');
    const textarea = document.getElementById('viewerCommentContent');
    if (parentField) parentField.value = commentId;
    if (textarea) {
        textarea.focus();
        textarea.placeholder = '@' + authorName + ', votre réponse...';
    }
}

function openMediaViewer(data) {
    if (!mediaViewer) return;

    hideAllViewerModes();

    const safeTitle = data.title || '';
    const safeContent = data.content || '';
    const safeUser = data.user || 'Utilisateur';
    const safeTime = data.time || 'à l\'instant';
    const likes = Number(data.likes || 0);
    const comments = Number(data.comments || 0);
    const shares = Number(data.shares || 0);

    const viewerComments = document.getElementById('viewerComments');
    const viewerPostId = document.getElementById('viewerPostId');
    const viewerLikePostId = document.getElementById('viewerLikePostId');
    const viewerSharePostId = document.getElementById('viewerSharePostId');
    const viewerSavePostId = document.getElementById('viewerSavePostId');
    const viewerLikeBtn = document.getElementById('viewerLikeBtn');
    const viewerSaveBtn = document.getElementById('viewerSaveBtn');

    if (viewerPostId) viewerPostId.value = data.id || '';
    if (viewerLikePostId) viewerLikePostId.value = data.id || '';
    if (viewerSharePostId) viewerSharePostId.value = data.id || '';
    if (viewerSavePostId) viewerSavePostId.value = data.id || '';
    const viewerParentId = document.getElementById('viewerParentId');
    const viewerCommentContent = document.getElementById('viewerCommentContent');
    if (viewerParentId) viewerParentId.value = '';
    if (viewerCommentContent) {
        viewerCommentContent.value = '';
        viewerCommentContent.placeholder = 'Écrire un commentaire...';
    }
    const viewerImageInput = document.getElementById('viewerCommentImage');
    const viewerImagePreview = document.getElementById('viewerCommentPreview');
    const viewerEmojiHidden = document.getElementById('viewerEmojiHidden');
    const viewerError = document.getElementById('err-viewerCommentContent');
    if (viewerImageInput) viewerImageInput.value = '';
    if (viewerImagePreview) viewerImagePreview.classList.remove('show');
    if (viewerEmojiHidden) viewerEmojiHidden.value = '';
    if (viewerError) viewerError.textContent = '';

    if (viewerLikeBtn) {
        viewerLikeBtn.textContent = data.is_liked ? '❤️' : '👍';
    }
    if (viewerSaveBtn) {
        viewerSaveBtn.textContent = data.is_saved ? '📌' : '🔖';
    }

    if (viewerComments) {
        const commentsHtml = formatViewerComments(Array.isArray(data.comments_data) ? data.comments_data : []);
        viewerComments.innerHTML = commentsHtml || `
            <div class="viewer-comment-item">
                <div class="mini-avatar">U</div>
                <div class="viewer-comment-bubble">Aucun commentaire pour le moment.</div>
            </div>
        `;
    }

    if (data.type === 'video') {
        const video = document.createElement('video');
        video.src = data.src;
        video.controls = true;
        video.autoplay = true;
        video.loop = true;
        video.playsInline = true;
        videoViewerMain.appendChild(video);

        setCountVisibility('reelLikesWrap', likes, 'reelLikesCount');
        setCountVisibility('reelCommentsWrap', comments, 'reelCommentsCount');
        setCountVisibility('reelSharesWrap', shares, 'reelSharesCount');

        videoViewerContent.classList.add('show');
    } else {
        const img = document.createElement('img');
        img.src = data.src;
        img.alt = safeTitle || 'Media';
        imageViewerMain.appendChild(img);

        document.getElementById('viewerUserAvatar').textContent = safeUser.charAt(0).toUpperCase();
        document.getElementById('viewerUserName').textContent = safeUser;
        document.getElementById('viewerPostTime').textContent = safeTime;
        document.getElementById('viewerPostTitle').textContent = safeTitle;
        document.getElementById('viewerPostContent').innerHTML = safeContent.replace(/\n/g, '<br>');

        setCountVisibility('viewerLikesWrap', likes, 'viewerLikesCount');
        setCountVisibility('viewerCommentsWrap', comments, 'viewerCommentsCount');
        setCountVisibility('viewerSharesWrap', shares, 'viewerSharesCount');

        imageViewerContent.classList.add('show');
    }

    mediaViewer.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeViewer() {
    mediaViewer.classList.remove('show');
    hideAllViewerModes();
    if (!forumModal.classList.contains('show')) {
        document.body.style.overflow = '';
    }
}

if (closeMediaViewer) {
    closeMediaViewer.addEventListener('click', closeViewer);
}

if (mediaViewer) {
    mediaViewer.addEventListener('click', function(e) {
        if (e.target === mediaViewer) {
            closeViewer();
        }
    });
}

/* ============================================================
   Boutons ouverture modal post
   ============================================================ */
if (openCreateModalBtn) openCreateModalBtn.addEventListener('click', () => openModal());
if (openPhotoBtn) openPhotoBtn.addEventListener('click', () => { openModal(); imageField.click(); });
if (openVideoBtn) openVideoBtn.addEventListener('click', () => { openModal(); videoField.click(); });
if (openEmojiBtn) openEmojiBtn.addEventListener('click', (e) => { openModal(); showEmojiPickerFor('emoji_post', e.currentTarget); });
if (photoTrigger) photoTrigger.addEventListener('click', () => imageField.click());
if (videoTrigger) videoTrigger.addEventListener('click', () => videoField.click());
if (closeForumModal) closeForumModal.addEventListener('click', () => closeModal(true));
if (cancelForumModal) cancelForumModal.addEventListener('click', () => closeModal(true));
forumModal.addEventListener('click', function(e) { if (e.target === forumModal) closeModal(true); });

/* ============================================================
   EMOJI PICKER
   ============================================================ */
function insertEmojiIntoTarget(target, emoji) {
    if (!target) return;
    const start = target.selectionStart ?? target.value.length;
    const end = target.selectionEnd ?? target.value.length;
    const text = target.value;
    target.value = text.substring(0, start) + emoji + text.substring(end);
    target.focus();
    target.selectionStart = target.selectionEnd = start + emoji.length;
    target.dispatchEvent(new Event('input'));
}

function renderEmojiGroup(group) {
    if (!emojiPickerBody || !emojiGroups[group]) return;
    emojiPickerBody.innerHTML = '';
    emojiGroups[group].forEach(emoji => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'emoji-btn';
        btn.textContent = emoji;
        btn.addEventListener('click', () => {
            if (activeEmojiTarget) insertEmojiIntoTarget(activeEmojiTarget, emoji);
        });
        emojiPickerBody.appendChild(btn);
    });
    document.querySelectorAll('.emoji-tab').forEach(tab => {
        tab.classList.toggle('active', tab.dataset.group === group);
    });
}

function showEmojiPickerFor(targetId, triggerEl) {
    const target = document.getElementById(targetId);
    if (!target || !emojiPicker) return;
    activeEmojiTarget = target;
    lastEmojiTrigger = triggerEl;
    renderEmojiGroup('smileys');

    const rect = triggerEl.getBoundingClientRect();
    let top = rect.bottom + 10;
    let left = rect.left;
    if (left + 320 > window.innerWidth - 12) left = window.innerWidth - 332;
    if (left < 12) left = 12;
    if (top + 390 > window.innerHeight - 12) top = rect.top - 400;
    if (top < 12) top = 12;

    emojiPicker.style.top = top + 'px';
    emojiPicker.style.left = left + 'px';
    emojiPicker.classList.add('show');
}

function hideEmojiPicker() {
    if (emojiPicker) emojiPicker.classList.remove('show');
}

document.addEventListener('click', function(e) {
    const emojiBtn = e.target.closest('.emoji-open-btn, .comment-emoji-btn');
    if (emojiBtn) {
        const targetId = emojiBtn.dataset.target;
        showEmojiPickerFor(targetId, emojiBtn);
        return;
    }
    if (!e.target.closest('#emojiPicker')) {
        if (!e.target.closest('.emoji-open-btn') && !e.target.closest('.comment-emoji-btn')) {
            hideEmojiPicker();
        }
    }
});

if (emojiTabs) {
    emojiTabs.addEventListener('click', function(e) {
        const tab = e.target.closest('.emoji-tab');
        if (!tab) return;
        renderEmojiGroup(tab.dataset.group);
    });
}

/* ============================================================
   VALIDATION FORMULAIRE POST
   ============================================================ */
function getLettersCountJS(text) {
    return text.replace(/[^a-zA-ZÀ-ÿ]/gu, '').length;
}

function hasOnlyLettersAndSpaces(text) {
    return /^[a-zA-ZÀ-ÿ\s]*$/.test(text);
}

const rules = {
    titre: {
        validate: value => hasOnlyLettersAndSpaces(value) && getLettersCountJS(value) >= 3,
        message: 'Titre valide.',
        error: 'Le titre doit contenir au moins 3 lettres.'
    },
    type_post: {
        validate: value => value !== '',
        message: 'Type valide.',
        error: 'Veuillez choisir le type du post.'
    },
    contenu: {
        validate: value => hasOnlyLettersAndSpaces(value) && getLettersCountJS(value) >= 5,
        message: 'Description valide.',
        error: 'La description doit contenir au moins 5 lettres.'
    }
};

function setError(field, message) {
    field.classList.add('field-invalid');
    field.classList.remove('field-valid-input');
    const errorBox = document.getElementById('err-' + field.id);
    if (errorBox) { errorBox.textContent = message; errorBox.style.color = '#dc2626'; errorBox.className = 'field-error'; }
}

function setValid(field, message) {
    field.classList.remove('field-invalid');
    field.classList.add('field-valid-input');
    const errorBox = document.getElementById('err-' + field.id);
    if (errorBox) { errorBox.textContent = message; errorBox.style.color = '#22a559'; errorBox.className = 'field-valid'; }
}

function validateField(field) {
    const rule = rules[field.id];
    if (!rule) return true;
    const value = field.value.trim();
    if (value === '') { setError(field, rule.error); return false; }
    if (!rule.validate(field.value)) { setError(field, rule.error); return false; }
    setValid(field, rule.message);
    return true;
}

Object.keys(rules).forEach(id => {
    const field = document.getElementById(id);
    if (!field) return;
    field.addEventListener('input', () => validateField(field));
    field.addEventListener('change', () => validateField(field));
    field.addEventListener('blur', () => validateField(field));
});

if (imageField) {
    imageField.addEventListener('change', function () {
        const errorBox = document.getElementById('err-image');
        this.classList.remove('field-invalid');
        errorBox.textContent = '';
        const file = this.files[0];
        if (!file) { previewBox.classList.remove('show'); previewImg.src = ''; return; }
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            this.classList.add('field-invalid');
            errorBox.textContent = 'Formats image autorisés : JPG, JPEG, PNG, WEBP.';
            previewBox.classList.remove('show'); previewImg.src = ''; return;
        }
        if (file.size > 5 * 1024 * 1024) {
            this.classList.add('field-invalid');
            errorBox.textContent = "L'image ne doit pas dépasser 5 Mo.";
            previewBox.classList.remove('show'); previewImg.src = ''; return;
        }
        const reader = new FileReader();
        reader.onload = function (e) {
            previewImg.src = e.target.result;
            previewBox.classList.add('show');
            if (currentImageBox) currentImageBox.classList.remove('show');
            errorBox.textContent = 'Image valide.';
            errorBox.className = 'field-valid';
        };
        reader.readAsDataURL(file);
    });
}

if (videoField) {
    videoField.addEventListener('change', function () {
        const errorBox = document.getElementById('err-video');
        this.classList.remove('field-invalid');
        errorBox.textContent = '';
        const file = this.files[0];
        if (!file) { videoPreviewBox.classList.remove('show'); previewVideo.src = ''; return; }
        const allowedTypes = ['video/mp4', 'video/webm', 'video/ogg'];
        if (!allowedTypes.includes(file.type)) {
            this.classList.add('field-invalid');
            errorBox.textContent = 'Formats vidéo autorisés : MP4, WEBM, OGG.';
            videoPreviewBox.classList.remove('show'); previewVideo.src = ''; return;
        }
        if (file.size > 25 * 1024 * 1024) {
            this.classList.add('field-invalid');
            errorBox.textContent = "La vidéo ne doit pas dépasser 25 Mo.";
            videoPreviewBox.classList.remove('show'); previewVideo.src = ''; return;
        }
        const url = URL.createObjectURL(file);
        previewVideo.src = url;
        previewVideo.load();
        previewVideo.play().catch(() => {});
        videoPreviewBox.classList.add('show');
        if (currentVideoBox) currentVideoBox.classList.remove('show');
        errorBox.textContent = 'Vidéo valide.';
        errorBox.className = 'field-valid';
    });
}

if (postForm) {
    postForm.addEventListener('submit', function (e) {
        let isValid = true;
        Object.keys(rules).forEach(id => {
            const field = document.getElementById(id);
            if (field && !validateField(field)) isValid = false;
        });
        if (!isValid) { e.preventDefault(); openModal(); }
    });
}

/* ============================================================
   MENUS POST (3 points)
   ============================================================ */
function togglePostMenu(postId) {
    document.querySelectorAll('.post-dropdown').forEach(menu => {
        if (menu.id !== 'post-menu-' + postId) menu.classList.remove('show');
    });
    const target = document.getElementById('post-menu-' + postId);
    if (target) target.classList.toggle('show');
}

document.addEventListener('click', function (e) {
    if (!e.target.closest('.post-menu-wrap') && !e.target.closest('.comment-menu-wrap')) {
        document.querySelectorAll('.post-dropdown').forEach(m => m.classList.remove('show'));
        document.querySelectorAll('.comment-dropdown').forEach(m => m.classList.remove('show'));
    }
});

/* ============================================================
   MENUS COMMENTAIRE (3 points) — NOUVEAU
   ============================================================ */

/**
 * Ouvre/ferme le dropdown d'un commentaire
 * @param {string} menuId  ex: 'cmenu-42' ou 'cmenu-reply-99'
 */
function toggleCommentMenu(menuId) {
    // Fermer tous les autres menus de commentaires
    document.querySelectorAll('.comment-dropdown').forEach(m => {
        if (m.id !== menuId) m.classList.remove('show');
    });
    const menu = document.getElementById(menuId);
    if (menu) menu.classList.toggle('show');
}

/**
 * Affiche la zone d'édition inline et masque le texte affiché
 * @param {number} commentId
 * @param {number} postId
 */
function startEditComment(commentId, postId) {
    // Fermer le menu
    document.querySelectorAll('.comment-dropdown').forEach(m => m.classList.remove('show'));

    const textDiv = document.getElementById('comment-text-' + commentId);
    const editZone = document.getElementById('comment-edit-zone-' + commentId);

    if (textDiv) textDiv.style.display = 'none';
    if (editZone) editZone.style.display = 'block';

    const input = document.getElementById('comment-edit-input-' + commentId);
    if (input) {
        input.focus();
        input.setSelectionRange(input.value.length, input.value.length);
    }
}

/**
 * Annule l'édition et reaffiche le texte
 */
function cancelEditComment(commentId) {
    const textDiv = document.getElementById('comment-text-' + commentId);
    const editZone = document.getElementById('comment-edit-zone-' + commentId);

    if (textDiv) textDiv.style.display = '';
    if (editZone) editZone.style.display = 'none';
}

/**
 * Sauvegarde la modification via form caché
 */
function saveEditComment(commentId, postId) {
    const input = document.getElementById('comment-edit-input-' + commentId);
    if (!input) return;

    const newContent = input.value.trim();
    if (newContent === '') {
        alert('Le commentaire ne peut pas être vide.');
        return;
    }
    if (newContent.replace(/[^a-zA-ZÀ-ÿ]/gu, '').length < 2) {
        alert('Le commentaire doit contenir au moins 2 lettres.');
        return;
    }

    document.getElementById('updateCommentId').value = commentId;
    document.getElementById('updateCommentPostId').value = postId;
    document.getElementById('updateCommentContent').value = newContent;
    document.getElementById('updateCommentForm').submit();
}

/**
 * Supprime un commentaire via form caché
 * @param {number} commentId   ID du commentaire à supprimer
 * @param {number} postId      ID du post parent
 * @param {number} parentId    0 si racine, sinon ID du commentaire parent (réponse)
 */
function deleteComment(commentId, postId, parentId) {
    // Fermer le menu
    document.querySelectorAll('.comment-dropdown').forEach(m => m.classList.remove('show'));

    const msg = parentId === 0
        ? 'Supprimer ce commentaire et toutes ses réponses ?'
        : 'Supprimer cette réponse ?';

    if (!confirm(msg)) return;

    document.getElementById('deleteCommentId').value = commentId;
    document.getElementById('deleteCommentPostId').value = postId;
    document.getElementById('deleteCommentParentId').value = parentId;
    document.getElementById('deleteCommentForm').submit();
}

/* ============================================================
   MODAL SIGNALER COMMENTAIRE — NOUVEAU
   ============================================================ */
function openReportCommentModal(commentId, postId) {
    document.querySelectorAll('.comment-dropdown').forEach(m => m.classList.remove('show'));
    document.getElementById('report-comment-id').value = commentId;
    document.getElementById('report-comment-post-id').value = postId;
    document.getElementById('reportCommentModal').classList.add('show');
}

function closeReportCommentModal() {
    document.getElementById('reportCommentModal').classList.remove('show');
    const form = document.getElementById('reportCommentForm');
    if (form) form.reset();
}

document.getElementById('reportCommentModal').addEventListener('click', function(e) {
    if (e.target === this) closeReportCommentModal();
});

/* ============================================================
   BOITE DE COMMENTAIRE (toggle)
   ============================================================ */
function toggleCommentBox(postId) {
    const box = document.getElementById('comment-box-' + postId);
    if (box) box.classList.toggle('hidden');
}

/* ============================================================
   MODAL SIGNALER POST
   ============================================================ */
function openReportModal(postId) {
    document.getElementById('report-post-id').value = postId;
    document.getElementById('reportModal').style.display = 'flex';
}

function closeReportModal() {
    document.getElementById('reportModal').style.display = 'none';
    const form = document.getElementById('reportModal').querySelector('form');
    if (form) form.reset();
}

/* ============================================================
   VIEWER COMMENTAIRE FORM
   ============================================================ */
const viewerCommentForm = document.getElementById('viewerCommentForm');
if (viewerCommentForm) {
    viewerCommentForm.addEventListener('submit', function (e) {
        const postIdField = document.getElementById('viewerPostId');
        const parentIdField = document.getElementById('viewerParentId');
        const contentField = document.getElementById('viewerCommentContent');
        const imageField = document.getElementById('viewerCommentImage');
        const emojiField = document.getElementById('viewerEmojiHidden');
        const errorBox = document.getElementById('err-viewerCommentContent');

        const postId = postIdField ? postIdField.value.trim() : '';
        const content = contentField ? contentField.value.trim() : '';
        const hasImage = imageField && imageField.files && imageField.files.length > 0;
        const hasEmoji = emojiField && emojiField.value.trim() !== '';

        if (!postId) { e.preventDefault(); if (errorBox) errorBox.textContent = 'Post introuvable.'; return; }
        if (content === '' && !hasImage && !hasEmoji) { e.preventDefault(); if (errorBox) errorBox.textContent = 'Ajoutez un texte, une image ou un emoji.'; return; }
        if (content !== '' && content.replace(/[^a-zA-ZÀ-ÿ]/gu, '').length < 2) { e.preventDefault(); if (errorBox) errorBox.textContent = 'Le commentaire doit contenir au moins 2 lettres.'; return; }
        if (errorBox) errorBox.textContent = '';
        if (parentIdField && !parentIdField.value) parentIdField.value = '';
    });
}

const viewerCommentImage = document.getElementById('viewerCommentImage');
if (viewerCommentImage) {
    viewerCommentImage.addEventListener('change', function () {
        const preview = document.getElementById('viewerCommentPreview');
        const img = preview ? preview.querySelector('img') : null;
        const errorBox = document.getElementById('err-viewerCommentContent');
        const file = this.files[0];
        if (!preview || !img) return;
        if (!file) { preview.classList.remove('show'); img.src = ''; return; }
        const allowedTypes = ['image/jpeg','image/png','image/webp'];
        if (!allowedTypes.includes(file.type)) {
            if (errorBox) errorBox.textContent = 'Formats image commentaire autorisés : JPG, JPEG, PNG, WEBP.';
            this.value = ''; preview.classList.remove('show'); img.src = ''; return;
        }
        if (file.size > 3 * 1024 * 1024) {
            if (errorBox) errorBox.textContent = "L'image du commentaire ne doit pas dépasser 3 Mo.";
            this.value = ''; preview.classList.remove('show'); img.src = ''; return;
        }
        const reader = new FileReader();
        reader.onload = function (ev) {
            img.src = ev.target.result;
            preview.classList.add('show');
            if (errorBox && errorBox.textContent.includes('image')) errorBox.textContent = '';
        };
        reader.readAsDataURL(file);
    });
}

const viewerCommentBtn = document.getElementById('viewerCommentBtn');
if (viewerCommentBtn) {
    viewerCommentBtn.addEventListener('click', function () {
        const field = document.getElementById('viewerCommentContent');
        if (field) field.focus();
    });
}

/* ============================================================
   VALIDATION COMMENTAIRES
   ============================================================ */
function validateComment(textareaId, errorId) {
    const textarea = document.getElementById(textareaId);
    const errorBox = document.getElementById(errorId);
    if (!textarea) return true;
    const text = textarea.value.trim();
    const letters = text.replace(/[^a-zA-ZÀ-ÿ]/gu, '');
    if (text === '') {
        if (errorBox) { errorBox.textContent = 'Le commentaire est obligatoire.'; errorBox.style.display = 'block'; }
        return false;
    }
    if (letters.length < 5) {
        if (errorBox) { errorBox.textContent = 'Le commentaire doit contenir au moins 5 lettres.'; errorBox.style.display = 'block'; }
        return false;
    }
    if (errorBox) { errorBox.textContent = ''; errorBox.style.display = 'none'; }
    return true;
}

function validateEditCommentForm(form) {
    const textarea = form.querySelector('textarea[name="comment_content"]');
    const errorBox = form.querySelector('.field-error');
    const imageInput = form.querySelector('input[name="comment_image"]');
    if (!textarea) return true;

    const text = textarea.value.trim();
    const letters = text.replace(/[^a-zA-ZÀ-ÿ]/gu, '');

    if (text === '') {
        if (errorBox) { errorBox.textContent = 'Le commentaire est obligatoire.'; errorBox.style.display = 'block'; errorBox.style.color = '#dc2626'; }
        textarea.classList.add('field-invalid');
        textarea.classList.remove('field-valid-input');
        return false;
    }

    if (letters.length < 5) {
        if (errorBox) { errorBox.textContent = 'Le commentaire doit contenir au moins 5 lettres.'; errorBox.style.display = 'block'; errorBox.style.color = '#dc2626'; }
        textarea.classList.add('field-invalid');
        textarea.classList.remove('field-valid-input');
        return false;
    }

    if (imageInput && imageInput.files && imageInput.files.length > 0) {
        const file = imageInput.files[0];
        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!allowedTypes.includes(file.type)) {
            if (errorBox) { errorBox.textContent = 'Formats image autorisés : JPG, JPEG, PNG, WEBP.'; errorBox.style.display = 'block'; errorBox.style.color = '#dc2626'; }
            return false;
        }
        if (file.size > 3 * 1024 * 1024) {
            if (errorBox) { errorBox.textContent = "L'image ne doit pas dépasser 3 Mo."; errorBox.style.display = 'block'; errorBox.style.color = '#dc2626'; }
            return false;
        }
    }

    if (errorBox) { errorBox.textContent = 'Commentaire valide.'; errorBox.style.display = 'block'; errorBox.style.color = '#22a559'; }
    textarea.classList.remove('field-invalid');
    textarea.classList.add('field-valid-input');
    return true;
}

document.addEventListener('input', function(e) {
    const textarea = e.target.closest('.comment-edit-form textarea[name="comment_content"]');
    if (!textarea) return;
    validateEditCommentForm(textarea.closest('.comment-edit-form'));
});

document.addEventListener('change', function(e) {
    const imageInput = e.target.closest('.comment-edit-form input[name="comment_image"]');
    if (!imageInput) return;
    validateEditCommentForm(imageInput.closest('.comment-edit-form'));
});

document.addEventListener('submit', function(e) {
    const form = e.target.closest('.comment-edit-form');
    if (!form) return;
    if (!validateEditCommentForm(form)) e.preventDefault();
});

document.addEventListener('DOMContentLoaded', () => {
    const commentForms = document.querySelectorAll('form[method="POST"]');
    commentForms.forEach(form => {
        const textarea = form.querySelector('textarea[name="comment_content"]');
        if (!textarea) return;
        const errorId = textarea.id ? 'err-' + textarea.id : null;
        if (textarea.id && errorId) {
            textarea.addEventListener('blur', () => validateComment(textarea.id, errorId));
            textarea.addEventListener('keyup', () => validateComment(textarea.id, errorId));
        }
        form.addEventListener('submit', event => {
            if (!textarea || !textarea.id || !errorId) return;
            if (!validateComment(textarea.id, errorId)) event.preventDefault();
        });
    });
});

/* ============================================================
   RÉPONSES (toggle reply box)
   ============================================================ */
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.reply-btn');
    if (!btn) return;
    const commentId = btn.dataset.commentId;
    const author = btn.dataset.author || 'Utilisateur';
    const box = document.getElementById('reply-box-' + commentId);
    if (box) {
        const isHidden = box.style.display === 'none' || box.style.display === '';
        box.style.display = isHidden ? 'block' : 'none';
        if (isHidden) {
            const textarea = document.getElementById('reply-content-' + commentId);
            if (textarea) {
                textarea.value = '@' + author + ' ';
                textarea.focus();
                textarea.setSelectionRange(textarea.value.length, textarea.value.length);
            }
        }
    }
});

/* ============================================================
   AUTO-OPEN MODAL / POST
   ============================================================ */
<?php if ($isEditMode || array_filter($errors)): ?>
openModal();
<?php endif; ?>

if (localStorage.getItem('openForumModal') === '1') {
    openModal();
    localStorage.removeItem('openForumModal');
}

<?php if (isset($_GET['open_post']) && ctype_digit((string)$_GET['open_post'])): ?>
window.addEventListener('load', function(){
    const postId = <?php echo (int)$_GET['open_post']; ?>;
    const box = document.getElementById('comment-box-' + postId);
    const postCard = document.getElementById('post-' + postId);
    if (box) { box.classList.remove('hidden'); box.classList.add('show'); }
    if (postCard) postCard.scrollIntoView({behavior:'smooth', block:'start'});
    <?php if (isset($_GET['open_comment']) && ctype_digit((string)$_GET['open_comment']) && (int)$_GET['open_comment'] > 0): ?>
    const replyBox = document.getElementById('reply-box-<?php echo (int)$_GET['open_comment']; ?>');
    if (replyBox) replyBox.style.display = 'block';
    <?php endif; ?>
});
<?php endif; ?>
</script>
