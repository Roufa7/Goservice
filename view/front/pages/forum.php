<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Africa/Tunis');

require_once __DIR__ . '/../../../controller/PostController.php';
require_once __DIR__ . '/../../../model/Post.php';

$postController = new PostController();

$errors = [
    'titre' => '',
    'type_post' => '',
    'statut_post' => '',
    'contenu' => '',
    'image' => '',
    'video' => ''
];

$old = [
    'titre' => '',
    'type_post' => '',
    'statut_post' => '',
    'contenu' => ''
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

function getLettersAndSpacesCount($text): int
{
    $cleaned = preg_replace('/[^a-zA-ZÀ-ÿ\s]/u', '', $text);
    return mb_strlen(trim($cleaned));
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

        if ($diff <= 0) return 'à l’instant';
        if ($diff < 60) return 'à l’instant';
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

/* delete */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_post'])) {
    $deleteId = (int)($_POST['post_id'] ?? 0);
    if ($deleteId > 0) {
        $postController->deletePost($deleteId);
        header('Location: ' . forumUrl(['deleted' => 1]));
        exit;
    }
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
    }
}

/* add / update */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['publish_post']) || isset($_POST['update_post']))) {
    $old['titre'] = trim($_POST['titre'] ?? '');
    $old['type_post'] = trim($_POST['type_post'] ?? '');
    $old['statut_post'] = trim($_POST['statut_post'] ?? '');
    $old['contenu'] = trim($_POST['contenu'] ?? '');

    if ($old['titre'] === '') {
        $errors['titre'] = 'Le titre est obligatoire.';
    } elseif (getLettersAndSpacesCount($old['titre']) < 3) {
        $errors['titre'] = 'Le titre doit contenir au moins 3 caractères.';
    }

    if ($old['type_post'] === '') {
        $errors['type_post'] = 'Veuillez choisir le type du post.';
    }

    if ($old['statut_post'] === '') {
        $errors['statut_post'] = 'Veuillez choisir le statut.';
    }

    if ($old['contenu'] === '') {
        $errors['contenu'] = 'La description est obligatoire.';
    } elseif (getLettersAndSpacesCount($old['contenu']) < 5) {
        $errors['contenu'] = 'La description doit contenir au moins 5 caractères.';
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
                1
            );

            $postController->addPost($post);
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
                header('Location: ' . forumUrl(['updated' => 1]));
                exit;
            }
        }
    }
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
    }

    .post-reaction-btn{
        display:inline-flex;
        align-items:center;
        justify-content:center;
        gap:10px;
        padding:14px 16px;
        border:none;
        border-radius:18px;
        cursor:pointer;
        font-weight:800;
        font-size:16px;
        color:#fff;
        background:var(--forum-btn-bg);
        transition:.25s ease;
        box-shadow:0 12px 24px rgba(238,88,40,.16);
    }

    .post-reaction-btn:hover{
        transform:translateY(-2px);
        filter:brightness(1.03);
    }

    .reaction-count{
        color:#fff;
        font-weight:900;
    }

    .comment-box{
        display:none;
        margin-top:18px;
    }

    .comment-box.show{
        display:block;
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
        grid-template-columns:repeat(3,1fr);
        gap:12px;
        padding:16px 18px;
        border-bottom:1px solid rgba(255,255,255,.08);
    }

    .viewer-action-btn{
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
                    <button class="solid-btn" type="button">Posts enregistrés</button>
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
        <div class="success-message" style="max-width:1380px;margin:0 auto;width:100%;">Post publié avec succès.</div>
    <?php endif; ?>

    <?php if (isset($_GET['updated'])): ?>
        <div class="success-message" style="max-width:1380px;margin:0 auto;width:100%;">Post mis à jour avec succès.</div>
    <?php endif; ?>

    <?php if (isset($_GET['deleted'])): ?>
        <div class="success-message" style="max-width:1380px;margin:0 auto;width:100%;">Post supprimé avec succès.</div>
    <?php endif; ?>

    <section class="forum-main-layout">
        <div class="forum-feed">

            <article class="panel composer-card">
                <div class="composer-top">
                    <div class="mini-avatar">E</div>

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
                                'shares' => $shareCount
                            ];
                        ?>
                        <article class="post-card">
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

                                        <button type="button" onclick="reportPost(<?php echo (int)$post['id_post']; ?>)">
                                            🚩 Signaler
                                        </button>

                                        <form method="POST" onsubmit="return confirm('Supprimer ce post ?');">
                                            <input type="hidden" name="post_id" value="<?php echo (int)$post['id_post']; ?>">
                                            <button type="submit" name="delete_post" class="danger-action">
                                                🗑 Supprimer
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <h3 class="post-title"><?php echo e($post['titre'] ?? ''); ?></h3>
                            <p class="post-content"><?php echo nl2br(e($post['contenu'] ?? '')); ?></p>

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
    <button class="post-reaction-btn" type="button">
        <span>👍 </span>
        <?php echo countHtml($likeCount); ?>
    </button>

    <button class="post-reaction-btn" type="button" onclick="toggleCommentBox(<?php echo (int)$post['id_post']; ?>)">
        <span>💬 </span>
        <?php echo countHtml($commentCount); ?>
    </button>

    <button class="post-reaction-btn" type="button">
        <span>🔁 </span>
        <?php echo countHtml($shareCount); ?>
    </button>

    <button class="post-reaction-btn" type="button">
        <span>🔖 </span>
        <?php echo countHtml($saveCount); ?>
    </button>
</div>

                            <div class="comment-box" id="comment-box-<?php echo (int)$post['id_post']; ?>">
                                <h4>Commentaires</h4>

                                <div class="panel" style="margin-top:16px;">
                                    <span class="section-badge">Ajouter un commentaire</span>
                                    <textarea class="comment-area comment-emoji-target" id="comment-text-<?php echo (int)$post['id_post']; ?>" placeholder="Écrire un commentaire..."></textarea>

                                    <div class="comment-tools">
                                        <button type="button" class="comment-tool-btn" onclick="document.getElementById('comment-image-<?php echo (int)$post['id_post']; ?>').click()">🖼️</button>
                                        <button type="button" class="comment-tool-btn comment-emoji-btn" data-target="comment-text-<?php echo (int)$post['id_post']; ?>">😊</button>
                                        <input type="file" class="comment-hidden-input" id="comment-image-<?php echo (int)$post['id_post']; ?>" accept=".jpg,.jpeg,.png,.webp">
                                    </div>

                                    <div class="icon-actions" style="margin-top:14px;">
                                        <button class="solid-btn" type="button">Publier commentaire</button>
                                    </div>
                                </div>
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

<div class="modal-overlay" id="forumModal">
    <div class="forum-modal">
        <div class="forum-modal-head">
            <div class="forum-modal-title"><?php echo $isEditMode ? 'Modifier la publication' : 'Créer une publication'; ?></div>
            <button type="button" class="forum-modal-close" id="closeForumModal">×</button>
        </div>

        <div class="forum-modal-body">
            <div class="forum-modal-user">
                <div class="mini-avatar">E</div>
                <div class="forum-modal-name">emma jlassi</div>
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

                    <div class="forum-form-field">
                        <select
                            name="statut_post"
                            id="statut_post"
                            class="<?php echo invalidClass($errors['statut_post']); ?>"
                        >
                            <option value="">Statut</option>
                            <option value="Visible" <?php echo $old['statut_post'] === 'Visible' ? 'selected' : ''; ?>>Visible</option>
                            <option value="Brouillon" <?php echo $old['statut_post'] === 'Brouillon' ? 'selected' : ''; ?>>Brouillon</option>
                        </select>
                        <span class="field-error" id="err-statut_post"><?php echo e($errors['statut_post']); ?></span>
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
                </div>

                <div class="forum-modal-tools">
                    <div>Ajouter à votre publication</div>

                    <div class="forum-tool-icons">
                        <button type="button" class="tool-trigger" id="photoTrigger">🖼️</button>
                        <button type="button" class="tool-trigger" id="videoTrigger">🎥</button>
                        <button type="button" class="tool-trigger emoji-open-btn" data-target="contenu" id="emojiTrigger">😊</button>
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
                            <span id="viewerPostTime">à l’instant</span>
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
    <button class="viewer-action-btn" type="button">👍 </button>
    <button class="viewer-action-btn" type="button">💬 </button>
    <button class="viewer-action-btn" type="button">🔁 </button>
</div>

                <div class="viewer-comments">
                    <div class="viewer-comment">
                        <div class="mini-avatar">U</div>
                        <div class="viewer-comment-bubble">
                            Les commentaires apparaîtront ici.
                        </div>
                    </div>
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

<script>
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

const emojiGroups = {
    smileys: ['😀','😁','😂','🤣','😃','😄','😅','😆','😉','😊','🙂','🙃','😍','🥰','😘','😗','😙','😚','😋','😛','😜','🤪','😝','🫠','🤗','🤭','🫢','🤫','🤔','🫡','😐','😑','😶','🫥','😏','😒','🙄','😬','🤥','😌','😔','😪','🤤','😴','😷','🤒','🤕','🤢','🤮','🥵','🥶','🥴','😵','🤯','😎','🤩','🥳','😤','😭','😢','😡','🤬','😱','😨','😰','😥','😓','😳','🥹','😇'],
    people: ['👋','🤚','🖐️','✋','🫱','🫲','👌','🤌','🤏','✌️','🤞','🫰','🤟','🤘','👏','🙌','🫶','🤝','🙏','💪','🫵','👀','🧠','👶','🧒','👦','👧','🧑','👨','👩','🧔','👱','👴','👵','🙍','🙎','🙅','🙆','💁','🙋','🧏','🙇','🤦','🤷','👮','🧑‍💻','👨‍💻','👩‍💻','🧑‍🎓','👨‍🎓','👩‍🎓','🧑‍🔧','👨‍🔧','👩‍🔧'],
    animals: ['🐶','🐱','🐭','🐹','🐰','🦊','🐻','🐼','🐨','🐯','🦁','🐮','🐷','🐸','🐵','🙈','🙉','🙊','🐔','🐧','🐦','🐤','🦆','🦅','🦉','🦇','🐺','🐗','🐴','🦄','🐝','🪲','🐞','🦋','🐌','🐢','🐍','🦎','🦂','🦀','🐙','🦑','🐬','🐳','🦈'],
    food: ['🍏','🍎','🍐','🍊','🍋','🍌','🍉','🍇','🍓','🫐','🍈','🍒','🍑','🥭','🍍','🥥','🥝','🍅','🍆','🥑','🥦','🥬','🥒','🌶️','🫑','🌽','🥕','🫒','🧄','🧅','🥔','🍠','🥐','🍞','🥖','🧀','🍗','🍖','🍔','🍟','🍕','🌭','🥪','🌮','🌯','🥗','🍝','🍜','🍣','🍩','🍪','🎂','🍫','🍿','☕','🧃'],
    travel: ['🚗','🚕','🚙','🚌','🚎','🏎️','🚓','🚑','🚒','🚚','🚜','🏍️','🚲','✈️','🛫','🛬','🚀','🛸','🚁','⛵','🚤','🛳️','🚂','🚆','🚇','🚝','🗺️','🧭','🏖️','🏝️','🏜️','🏕️','🏔️','⛰️','🌋','🗽','🗼','🏰','🏟️','🎡','🎢'],
    objects: ['⌚','📱','💻','⌨️','🖥️','🖨️','🖱️','📷','📹','🎥','☎️','📞','📺','📻','🎙️','🎧','📢','💡','🔦','🕯️','🪫','🔋','🔌','💰','💳','🧾','📦','📌','✂️','🖊️','🖋️','📝','📚','🧸','🎁','🏆','⚽','🏀','🎮','🛒','🛠️','🔧','🔨'],
    symbols: ['❤️','🩷','🧡','💛','💚','🩵','💙','💜','🖤','🤍','🤎','💔','❣️','💕','💞','💓','💗','💖','💘','💝','💯','✅','✔️','✖️','❌','⚠️','🚫','⭐','🌟','✨','🔥','💥','🎉','🎊','🔔','📣','🔴','🟠','🟡','🟢','🔵','🟣','⚫','⚪']
};

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

function openMediaViewer(data) {
    if (!mediaViewer) return;

    hideAllViewerModes();

    const safeTitle = data.title || '';
    const safeContent = data.content || '';
    const safeUser = data.user || 'Utilisateur';
    const safeTime = data.time || 'à l’instant';
    const likes = Number(data.likes || 0);
    const comments = Number(data.comments || 0);
    const shares = Number(data.shares || 0);

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

if (openCreateModalBtn) {
    openCreateModalBtn.addEventListener('click', () => openModal());
}

if (openPhotoBtn) {
    openPhotoBtn.addEventListener('click', () => {
        openModal();
        imageField.click();
    });
}

if (openVideoBtn) {
    openVideoBtn.addEventListener('click', () => {
        openModal();
        videoField.click();
    });
}

if (openEmojiBtn) {
    openEmojiBtn.addEventListener('click', (e) => {
        openModal();
        showEmojiPickerFor('contenu', e.currentTarget);
    });
}

if (photoTrigger) {
    photoTrigger.addEventListener('click', () => imageField.click());
}

if (videoTrigger) {
    videoTrigger.addEventListener('click', () => videoField.click());
}

if (closeForumModal) {
    closeForumModal.addEventListener('click', () => closeModal(true));
}

if (cancelForumModal) {
    cancelForumModal.addEventListener('click', () => closeModal(true));
}

forumModal.addEventListener('click', function(e) {
    if (e.target === forumModal) {
        closeModal(true);
    }
});

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
            if (activeEmojiTarget) {
                insertEmojiIntoTarget(activeEmojiTarget, emoji);
            }
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

    if (left + 320 > window.innerWidth - 12) {
        left = window.innerWidth - 332;
    }
    if (left < 12) left = 12;

    if (top + 390 > window.innerHeight - 12) {
        top = rect.top - 400;
    }
    if (top < 12) top = 12;

    emojiPicker.style.top = top + 'px';
    emojiPicker.style.left = left + 'px';
    emojiPicker.classList.add('show');
}

function hideEmojiPicker() {
    if (emojiPicker) {
        emojiPicker.classList.remove('show');
    }
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

function getLettersAndSpacesCountJS(text) {
    const cleaned = text.replace(/[^a-zA-ZÀ-ÿ\s]/gu, '');
    return cleaned.trim().length;
}

function hasOnlyLettersAndSpaces(text) {
    return /^[a-zA-ZÀ-ÿ\s]*$/.test(text);
}

const rules = {
    titre: {
        validate: value => hasOnlyLettersAndSpaces(value) && getLettersAndSpacesCountJS(value) >= 3,
        message: 'Titre valide.',
        error: 'Le titre doit contenir au moins 3 caractères.'
    },
    type_post: {
        validate: value => value !== '',
        message: 'Type valide.',
        error: 'Veuillez choisir le type du post.'
    },
    statut_post: {
        validate: value => value !== '',
        message: 'Statut valide.',
        error: 'Veuillez choisir le statut.'
    },
    contenu: {
        validate: value => hasOnlyLettersAndSpaces(value) && getLettersAndSpacesCountJS(value) >= 5,
        message: 'Description valide.',
        error: 'La description doit contenir au moins 5 caractères.'
    }
};

function setError(field, message) {
    field.classList.add('field-invalid');
    field.classList.remove('field-valid-input');
    const errorBox = document.getElementById('err-' + field.id);
    if (errorBox) {
        errorBox.textContent = message;
        errorBox.style.color = '#dc2626';
        errorBox.className = 'field-error';
    }
}

function setValid(field, message) {
    field.classList.remove('field-invalid');
    field.classList.add('field-valid-input');
    const errorBox = document.getElementById('err-' + field.id);
    if (errorBox) {
        errorBox.textContent = message;
        errorBox.style.color = '#22a559';
        errorBox.className = 'field-valid';
    }
}

function validateField(field) {
    const rule = rules[field.id];
    if (!rule) return true;

    const value = field.value.trim();

    if (value === '') {
        setError(field, rule.error);
        return false;
    }

    if (!rule.validate(field.value)) {
        setError(field, rule.error);
        return false;
    }

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
        errorBox.className = 'field-error';

        const file = this.files[0];
        if (!file) {
            previewBox.classList.remove('show');
            previewImg.src = '';
            return;
        }

        const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
        const maxSize = 5 * 1024 * 1024;

        if (!allowedTypes.includes(file.type)) {
            this.classList.add('field-invalid');
            errorBox.textContent = 'Formats image autorisés : JPG, JPEG, PNG, WEBP.';
            previewBox.classList.remove('show');
            previewImg.src = '';
            return;
        }

        if (file.size > maxSize) {
            this.classList.add('field-invalid');
            errorBox.textContent = "L'image ne doit pas dépasser 5 Mo.";
            previewBox.classList.remove('show');
            previewImg.src = '';
            return;
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
        errorBox.className = 'field-error';

        const file = this.files[0];
        if (!file) {
            videoPreviewBox.classList.remove('show');
            previewVideo.src = '';
            return;
        }

        const allowedTypes = ['video/mp4', 'video/webm', 'video/ogg'];
        const maxSize = 25 * 1024 * 1024;

        if (!allowedTypes.includes(file.type)) {
            this.classList.add('field-invalid');
            errorBox.textContent = 'Formats vidéo autorisés : MP4, WEBM, OGG.';
            videoPreviewBox.classList.remove('show');
            previewVideo.src = '';
            return;
        }

        if (file.size > maxSize) {
            this.classList.add('field-invalid');
            errorBox.textContent = "La vidéo ne doit pas dépasser 25 Mo.";
            videoPreviewBox.classList.remove('show');
            previewVideo.src = '';
            return;
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
            if (field && !validateField(field)) {
                isValid = false;
            }
        });

        if (imageField && imageField.files.length > 0) {
            const file = imageField.files[0];
            const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            const maxSize = 5 * 1024 * 1024;
            const errorBox = document.getElementById('err-image');

            if (!allowedTypes.includes(file.type)) {
                imageField.classList.add('field-invalid');
                errorBox.textContent = 'Formats image autorisés : JPG, JPEG, PNG, WEBP.';
                errorBox.style.color = '#dc2626';
                errorBox.className = 'field-error';
                isValid = false;
            } else if (file.size > maxSize) {
                imageField.classList.add('field-invalid');
                errorBox.textContent = "L'image ne doit pas dépasser 5 Mo.";
                errorBox.style.color = '#dc2626';
                errorBox.className = 'field-error';
                isValid = false;
            }
        }

        if (videoField && videoField.files.length > 0) {
            const file = videoField.files[0];
            const allowedTypes = ['video/mp4', 'video/webm', 'video/ogg'];
            const maxSize = 25 * 1024 * 1024;
            const errorBox = document.getElementById('err-video');

            if (!allowedTypes.includes(file.type)) {
                videoField.classList.add('field-invalid');
                errorBox.textContent = 'Formats vidéo autorisés : MP4, WEBM, OGG.';
                errorBox.style.color = '#dc2626';
                errorBox.className = 'field-error';
                isValid = false;
            } else if (file.size > maxSize) {
                videoField.classList.add('field-invalid');
                errorBox.textContent = "La vidéo ne doit pas dépasser 25 Mo.";
                errorBox.style.color = '#dc2626';
                errorBox.className = 'field-error';
                isValid = false;
            }
        }

        if (isValid) {
            setTimeout(() => {
                if (forumModal) forumModal.classList.remove('show');
            }, 100);
        } else {
            e.preventDefault();
            openModal();
        }
    });
}

function togglePostMenu(postId) {
    document.querySelectorAll('.post-dropdown').forEach(menu => {
        if (menu.id !== 'post-menu-' + postId) {
            menu.classList.remove('show');
        }
    });

    const target = document.getElementById('post-menu-' + postId);
    if (target) {
        target.classList.toggle('show');
    }
}

document.addEventListener('click', function (e) {
    if (!e.target.closest('.post-menu-wrap')) {
        document.querySelectorAll('.post-dropdown').forEach(menu => {
            menu.classList.remove('show');
        });
    }
});

function toggleCommentBox(postId) {
    const box = document.getElementById('comment-box-' + postId);
    if (box) {
        box.classList.toggle('show');
    }
}

function reportPost(postId) {
    alert('Post signalé : #' + postId);
}

<?php if ($isEditMode || array_filter($errors)): ?>
openModal();
<?php endif; ?>

if (localStorage.getItem('openForumModal') === '1') {
    openModal();
    localStorage.removeItem('openForumModal');
}
</script>