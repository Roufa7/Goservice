<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Africa/Tunis');

require_once __DIR__ . '/../../../controller/PostController.php';
require_once __DIR__ . '/../../../model/Post.php';

$postController = new PostController();

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

function forumBackUrl(array $extra = []): string
{
    $base = ['page' => 'forum'];
    $params = array_merge($base, $_GET, $extra);
    return '/GoService/view/back/index.php?' . http_build_query($params);
}

function uploadImageFile(array $file, array &$errors, ?string $oldPath = null): ?string
{
    if (empty($file['name'])) return $oldPath;

    $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions, true)) {
        $errors['image'] = 'Formats image autorisés : JPG, JPEG, PNG, WEBP.';
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

$errors = [
    'titre' => '',
    'contenu' => '',
    'type_post' => '',
    'statut_post' => '',
    'image' => '',
    'video' => ''
];

$old = [
    'titre' => '',
    'contenu' => '',
    'type_post' => '',
    'statut_post' => 'En attente'
];

$isEditMode = false;
$editId = 0;
$editPost = null;

$search = trim($_GET['search'] ?? '');
$filter = trim($_GET['filter'] ?? 'Tous');
$sort   = trim($_GET['sort'] ?? 'date');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['delete_post'])) {
        $id = (int)($_POST['post_id'] ?? 0);
        if ($id > 0) {
            $postController->deletePost($id);
        }
        header('Location: ' . forumBackUrl(['deleted' => 1]));
        exit;
    }

    if (isset($_POST['approve_post'])) {
        $id = (int)($_POST['post_id'] ?? 0);
        if ($id > 0) {
            $post = $postController->getPostById($id);
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
                $postController->updatePost($updatedPost);
            }
        }
        header('Location: ' . forumBackUrl(['approved' => 1]));
        exit;
    }

    if (isset($_POST['reject_post'])) {
        $id = (int)($_POST['post_id'] ?? 0);
        if ($id > 0) {
            $post = $postController->getPostById($id);
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
                $postController->updatePost($updatedPost);
            }
        }
        header('Location: ' . forumBackUrl(['rejected' => 1]));
        exit;
    }

    if (isset($_POST['save_post']) || isset($_POST['update_post'])) {
        $old['titre'] = trim($_POST['titre'] ?? '');
        $old['contenu'] = trim($_POST['contenu'] ?? '');
        $old['type_post'] = trim($_POST['type_post'] ?? '');
        $old['statut_post'] = trim($_POST['statut_post'] ?? '');

        if ($old['titre'] === '') {
            $errors['titre'] = 'Le titre est obligatoire.';
        } elseif (getLettersCount($old['titre']) < 3) {
            $errors['titre'] = 'Le titre doit contenir au moins 3 lettres.';
        }

        if ($old['type_post'] === '') {
            $errors['type_post'] = 'Veuillez choisir le type du post.';
        }

        if ($old['statut_post'] === '') {
            $errors['statut_post'] = 'Veuillez choisir le statut.';
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
                    $old['statut_post'],
                    1
                );
                $postController->addPost($newPost);
                header('Location: ' . forumBackUrl(['created' => 1]));
                exit;
            }
        }

        if (isset($_POST['update_post'])) {
            $editId = (int)($_POST['edit_id'] ?? 0);
            $current = $postController->getPostById($editId);

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
                        $old['statut_post'],
                        $current['id_user'] ?? 1
                    );
                    $postController->updatePost($updatedPost);
                    header('Location: ' . forumBackUrl(['updated' => 1]));
                    exit;
                }
            }
        }
    }
}

if (isset($_GET['edit']) && ctype_digit($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $editPost = $postController->getPostById($editId);

    if ($editPost) {
        $isEditMode = true;
        $old['titre'] = $editPost['titre'] ?? '';
        $old['contenu'] = $editPost['contenu'] ?? '';
        $old['type_post'] = $editPost['type_post'] ?? '';
        $old['statut_post'] = $editPost['statut_post'] ?? 'En attente';
    }
}

$rawPosts = $postController->listPosts();
if ($rawPosts instanceof PDOStatement) {
    $posts = $rawPosts->fetchAll(PDO::FETCH_ASSOC);
} else {
    $posts = is_array($rawPosts) ? $rawPosts : [];
}

foreach ($posts as &$post) {
    $post['author_name'] = trim(($post['prenom'] ?? '') . ' ' . ($post['nom'] ?? ''));
    if ($post['author_name'] === '') {
        $post['author_name'] = $post['auteur'] ?? 'Utilisateur';
    }

    $post['comments_count'] = (int)($post['comments_count'] ?? $post['nb_comments'] ?? $post['total_comments'] ?? 0);
    $post['reports_count'] = (int)($post['reports_count'] ?? $post['nb_signalements'] ?? $post['signalements_count'] ?? 0);
}
unset($post);

$totalPosts = count($posts);
$totalComments = array_sum(array_column($posts, 'comments_count'));
$totalReports = array_sum(array_column($posts, 'reports_count'));
$totalRejected = count(array_filter($posts, fn($p) => (($p['statut_post'] ?? '') === 'Rejeté')));

$reportedPosts = array_values(array_filter($posts, fn($p) => (($p['reports_count'] ?? 0) > 0)));

$commentsRows = [];
if (method_exists($postController, 'listCommentsForAdmin')) {
    $rawComments = $postController->listCommentsForAdmin();
    if ($rawComments instanceof PDOStatement) {
        $commentsRows = $rawComments->fetchAll(PDO::FETCH_ASSOC);
    } elseif (is_array($rawComments)) {
        $commentsRows = $rawComments;
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
    .forum-admin-stats{display:grid;grid-template-columns:repeat(4,1fr);gap:20px}
    .forum-admin-stat-card{background:var(--card);border:1px solid var(--line);box-shadow:var(--shadow);border-radius:28px;padding:28px 24px;min-height:150px;display:flex;flex-direction:column;justify-content:center;position:relative;overflow:hidden}
    .forum-admin-stat-card::before{position:absolute;top:14px;right:14px;font-size:3.8rem;opacity:1;filter:saturate(1.25);text-shadow:0 2px 8px rgba(0,0,0,.15)}
    .forum-admin-stat-card:nth-child(1)::before{content:"📝"}
    .forum-admin-stat-card:nth-child(2)::before{content:"💬"}
    .forum-admin-stat-card:nth-child(3)::before{content:"🚩"}
    .forum-admin-stat-card:nth-child(4)::before{content:"🙈"}
    .forum-admin-stat-card strong{font-size:3rem;line-height:1;margin-bottom:10px;color:var(--text)}
    .forum-admin-stat-card span{color:var(--muted);font-weight:700;font-size:1.02rem}
    body.dark .forum-admin-stat-card strong{color:#fff}
    body.dark .forum-admin-stat-card span{color:#d7dfe8}
    .forum-admin-head-row{display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:14px}
    .forum-mini-filters{display:flex;gap:8px;flex-wrap:wrap}
    .forum-mini-filter{display:inline-flex;align-items:center;justify-content:center;min-height:36px;padding:0 14px;border-radius:999px;text-decoration:none;font-size:.9rem;font-weight:700;background:rgba(76,175,80,.14);color:#2f9d4a;border:1px solid rgba(76,175,80,.20);transition:.2s ease}
    .forum-mini-filter:hover,.forum-mini-filter.active{background:#4CAF50;color:#fff;border-color:#4CAF50}
    .forum-admin-table-wrap{overflow-x:auto;width:100%}
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
    .forum-view-meta{color:#6a7484;margin-bottom:12px}
    .forum-view-title{font-size:1.8rem;font-weight:900;color:#1b2d45;margin-bottom:10px}
    .forum-view-content{color:#1b2d45;line-height:1.75;white-space:pre-wrap}
    .forum-view-media{margin-top:18px;border-radius:20px;overflow:hidden;background:#000}
    .forum-view-media img,.forum-view-media video{width:100%;max-height:420px;object-fit:contain;display:block;background:#000}
    body.dark .forum-post-modal-box{background:#fff}
    body.dark .forum-post-modal-head h2,body.dark .forum-view-title,body.dark .forum-view-content{color:#1b2d45}
    body.dark .forum-view-meta{color:#6a7484}
    .forum-form-modal-box{width:min(820px,100%);max-height:92vh;background:#fff;border:1px solid #e7ebf0;border-radius:32px;overflow:hidden;box-shadow:0 24px 60px rgba(20,39,56,.18);display:flex;flex-direction:column}
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
    .forum-emoji-picker{position:fixed;width:320px;max-width:calc(100vw - 24px);background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:22px;box-shadow:0 24px 60px rgba(15,23,42,.20);display:none;z-index:1100000;overflow:hidden}
    .forum-emoji-picker.show{display:block}
    .forum-emoji-head{padding:14px 16px;border-bottom:1px solid rgba(15,23,42,.08);font-weight:800;color:#17283f;background:#fff}
    .forum-emoji-body{max-height:260px;overflow-y:auto;padding:12px;display:grid;grid-template-columns:repeat(7,1fr);gap:8px}
    .forum-emoji-btn{border:none;background:#fff;border-radius:12px;height:40px;font-size:22px;cursor:pointer}
    .forum-emoji-btn:hover{background:#f1f4f8;transform:scale(1.08)}
    @media (max-width:1100px){.forum-admin-stats{grid-template-columns:repeat(2,1fr)}}
    @media (max-width:900px){.forum-form-grid{grid-template-columns:1fr}.forum-form-grid .full{grid-column:auto}.forum-admin-filter-line{align-items:stretch}.forum-admin-search-grow{min-width:100%}}
    @media (max-width:700px){.forum-admin-stats{grid-template-columns:1fr}}
</style>

<div class="forum-admin-page">
    <?php if (isset($_GET['created'])): ?><div class="forum-admin-success reveal">Post ajouté avec succès.</div><?php endif; ?>
    <?php if (isset($_GET['updated'])): ?><div class="forum-admin-success reveal">Post mis à jour avec succès.</div><?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?><div class="forum-admin-success reveal">Post supprimé avec succès.</div><?php endif; ?>
    <?php if (isset($_GET['approved'])): ?><div class="forum-admin-success reveal">Post approuvé avec succès.</div><?php endif; ?>
    <?php if (isset($_GET['rejected'])): ?><div class="forum-admin-success reveal">Post rejeté avec succès.</div><?php endif; ?>

    <section class="action-bar reveal">
        <form method="GET" action="/GoService/view/back/index.php" class="forum-admin-filter-line">
            <input type="hidden" name="page" value="forum">
            <input type="hidden" name="filter" value="<?php echo e($filter); ?>">

            <div class="forum-admin-search-grow">
                <input type="text" name="search" placeholder="Rechercher un post ou un commentaire..." value="<?php echo e($search); ?>">
            </div>

            <select name="sort" onchange="this.form.submit()">
                <option value="date" <?php echo $sort === 'date' ? 'selected' : ''; ?>>Date</option>
                <option value="popularite" <?php echo $sort === 'popularite' ? 'selected' : ''; ?>>Popularité</option>
                <option value="signalements" <?php echo $sort === 'signalements' ? 'selected' : ''; ?>>Signalements</option>
            </select>

            <div class="forum-admin-btns">
                <button type="button" class="outline-btn" onclick="window.print()">Exporter</button>
                <button type="button" class="solid-btn" id="openCreatePostModal">+ Nouveau post</button>
            </div>
        </form>
    </section>

    <section class="admin-stats reveal forum-admin-stats">
        <article class="admin-stat forum-admin-stat-card"><strong><?php echo $totalPosts; ?></strong><span>Posts</span></article>
        <article class="admin-stat forum-admin-stat-card"><strong><?php echo $totalComments; ?></strong><span>Commentaires</span></article>
        <article class="admin-stat forum-admin-stat-card"><strong><?php echo $totalReports; ?></strong><span>Signalements</span></article>
        <article class="admin-stat forum-admin-stat-card"><strong><?php echo $totalRejected; ?></strong><span>Rejetés</span></article>
    </section>

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
            <table class="module-table forum-admin-table">
                <thead>
                    <tr>
                        <th>Post</th>
                        <th>Auteur</th>
                        <th>Type</th>
                        <th>Statut</th>
                        <th>Signalé</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($filteredPosts)): ?>
                        <tr><td colspan="6" class="back-comment-empty">Aucun post trouvé.</td></tr>
                    <?php else: ?>
                        <?php foreach ($filteredPosts as $post): ?>
                            <?php
                                $postId = (int)($post['id_post'] ?? 0);
                                $title = $post['titre'] ?? 'Post sans titre';
                                $content = $post['contenu'] ?? '';
                                $author = $post['author_name'] ?? 'Utilisateur';
                                $type = $post['type_post'] ?? 'Discussion';
                                $status = $post['statut_post'] ?? 'Visible';
                                $reports = (int)($post['reports_count'] ?? 0);
                                $imageUrl = !empty($post['image']) ? '/GoService/' . ltrim($post['image'], '/') : '';
                                $videoUrl = !empty($post['video']) ? '/GoService/' . ltrim($post['video'], '/') : '';
                            ?>
                            <tr>
                                <td>
                                    <div class="forum-post-title"><?php echo e($title); ?></div>
                                    <div class="forum-post-sub"><?php echo e(mb_strimwidth($content, 0, 75, '...')); ?></div>
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
                                <td class="admin-tools forum-admin-tools">
                                    <button type="button" class="forum-admin-view-btn open-view-post"
                                        data-title="<?php echo e($title); ?>"
                                        data-content="<?php echo e($content); ?>"
                                        data-author="<?php echo e($author); ?>"
                                        data-type="<?php echo e($type); ?>"
                                        data-status="<?php echo e($status); ?>"
                                        data-image="<?php echo e($imageUrl); ?>"
                                        data-video="<?php echo e($videoUrl); ?>"
                                    >Voir</button>

                                    <a class="small-btn" href="<?php echo e(forumBackUrl(['edit' => $postId])); ?>">Modifier</a>

                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="post_id" value="<?php echo $postId; ?>">
                                        <button type="submit" name="<?php echo $status === 'Approuvé' ? 'reject_post' : 'approve_post'; ?>" class="outline-btn">
                                            <?php echo $status === 'Approuvé' ? 'Rejeter' : 'Approuver'; ?>
                                        </button>
                                    </form>

                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ce post ?');">
                                        <input type="hidden" name="post_id" value="<?php echo $postId; ?>">
                                        <button type="submit" name="delete_post" class="danger-btn">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="admin-panel reveal" style="margin-top:22px;">
        <span class="section-badge">Posts signalés</span>
        <div class="forum-admin-table-wrap">
            <table class="module-table forum-admin-table">
                <thead>
                    <tr>
                        <th>Post</th>
                        <th>Auteur</th>
                        <th>Motif</th>
                        <th>Signalé par</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($reportedPosts)): ?>
                        <tr><td colspan="6" class="back-comment-empty">Aucun post signalé.</td></tr>
                    <?php else: ?>
                        <?php foreach ($reportedPosts as $post): ?>
                            <?php
                                $postId = (int)($post['id_post'] ?? 0);
                                $title = $post['titre'] ?? '';
                                $author = $post['author_name'] ?? 'Utilisateur';
                                $date = !empty($post['date_publication']) ? date('d/m/Y', strtotime($post['date_publication'])) : '-';
                            ?>
                            <tr>
                                <td><?php echo e($title); ?></td>
                                <td><?php echo e($author); ?></td>
                                <td>Contenu signalé</td>
                                <td>Utilisateur</td>
                                <td><?php echo e($date); ?></td>
                                <td class="admin-tools forum-admin-tools">
                                    <a class="small-btn" href="<?php echo e(forumBackUrl(['edit' => $postId])); ?>">Traiter</a>

                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="post_id" value="<?php echo $postId; ?>">
                                        <button type="submit" name="toggle_visibility" class="outline-btn">
                                            <?php echo (($post['statut_post'] ?? '') === 'Masqué') ? 'Afficher' : 'Masquer'; ?>
                                        </button>
                                    </form>

                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Supprimer ce post ?');">
                                        <input type="hidden" name="post_id" value="<?php echo $postId; ?>">
                                        <button type="submit" name="delete_post" class="danger-btn">Supprimer</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="admin-panel reveal" style="margin-top:22px;">
        <span class="section-badge">Commentaires</span>
        <div class="forum-admin-table-wrap">
            <table class="module-table forum-admin-table">
                <thead>
                    <tr>
                        <th>Auteur</th>
                        <th>Commentaire</th>
                        <th>Post lié</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($commentsRows)): ?>
                        <tr><td colspan="4" class="back-comment-empty">Aucun commentaire disponible pour le moment.</td></tr>
                    <?php else: ?>
                        <?php foreach ($commentsRows as $comment): ?>
                            <tr>
                                <td><?php echo e($comment['auteur'] ?? $comment['author_name'] ?? 'Utilisateur'); ?></td>
                                <td><?php echo e($comment['contenu'] ?? $comment['commentaire'] ?? ''); ?></td>
                                <td><?php echo e($comment['post_titre'] ?? $comment['titre_post'] ?? 'Post'); ?></td>
                                <td class="admin-tools forum-admin-tools">
                                    <button class="forum-admin-view-btn" type="button">Voir</button>
                                    <button class="danger-btn" type="button">Supprimer</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<div class="forum-post-modal" id="viewPostModal">
    <div class="forum-post-modal-box">
        <div class="forum-post-modal-head">
            <h2>Voir le post</h2>
            <button type="button" class="forum-post-modal-close" id="closeViewPostModal">×</button>
        </div>
        <div class="forum-post-modal-body">
            <div class="forum-view-meta" id="viewPostMeta"></div>
            <div class="forum-view-title" id="viewPostTitle"></div>
            <div class="forum-view-content" id="viewPostContent"></div>
            <div class="forum-view-media" id="viewPostImageWrap" style="display:none;"><img id="viewPostImage" src="" alt="Image post"></div>
            <div class="forum-view-media" id="viewPostVideoWrap" style="display:none;"><video id="viewPostVideo" controls></video></div>
        </div>
    </div>
</div>

<div class="forum-form-modal <?php echo ($isEditMode || array_filter($errors)) ? 'show' : ''; ?>" id="postFormModal">
    <div class="forum-form-modal-box">
        <div class="forum-form-modal-head">
            <h2><?php echo $isEditMode ? 'Modifier la publication' : 'Créer une publication'; ?></h2>
            <button type="button" class="forum-form-modal-close" id="closePostFormModal">×</button>
        </div>

        <div class="forum-form-modal-body">
            <div class="forum-form-top-user">
                <div class="mini-avatar">E</div>
                <strong>emma jlassi</strong>
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

                    <div class="forum-field">
                        <select name="statut_post" id="back_statut_post" class="<?php echo invalidClass($errors['statut_post']); ?>">
                            <option value="">Statut</option>
                            <option value="En attente" <?php echo $old['statut_post'] === 'En attente' ? 'selected' : ''; ?>>En attente</option>
                            <option value="Approuvé" <?php echo $old['statut_post'] === 'Approuvé' ? 'selected' : ''; ?>>Approuvé</option>
                            <option value="Rejeté" <?php echo $old['statut_post'] === 'Rejeté' ? 'selected' : ''; ?>>Rejeté</option>
                        </select>
                        <div class="forum-error"><?php echo e($errors['statut_post']); ?></div>
                    </div>

                    <div class="forum-field full">
                        <textarea name="contenu" id="back_contenu" placeholder="Description" class="<?php echo invalidClass($errors['contenu']); ?>"><?php echo e($old['contenu']); ?></textarea>
                        <div class="forum-error"><?php echo e($errors['contenu']); ?></div>
                    </div>
                </div>

                <div class="forum-form-tools">
                    <div>Ajouter à votre publication</div>
                    <div class="forum-form-tool-icons">
                        <button type="button" class="forum-tool-btn" id="triggerBackImage" aria-label="Image">🖼️</button>
                        <button type="button" class="forum-tool-btn" id="triggerBackVideo" aria-label="Vidéo">🎥</button>
                        <button type="button" class="forum-tool-btn" id="triggerBackEmoji" aria-label="Emoji">😊</button>
                    </div>
                </div>

                <div class="forum-field full">
                    <input type="file" name="image" id="back_image" accept=".jpg,.jpeg,.png,.webp" style="display:none;">
                    <div class="forum-error"><?php echo e($errors['image']); ?></div>
                    <div class="forum-form-preview" id="backImagePreviewWrap"><img id="backImagePreview" src="" alt="Prévisualisation image"></div>

                    <?php if ($isEditMode && $editPost && !empty($editPost['image'])): ?>
                        <div class="forum-current-image show" id="backCurrentImage">
                            <img src="/GoService/<?php echo e($editPost['image']); ?>" alt="Image actuelle">
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
                            <video controls autoplay muted loop playsinline><source src="/GoService/<?php echo e($editPost['video']); ?>"></video>
                        </div>
                    <?php else: ?>
                        <div class="forum-current-video" id="backCurrentVideo"></div>
                    <?php endif; ?>
                </div>

                <div class="forum-form-actions">
                    <button type="button" class="ghost-btn" id="cancelPostFormModal">Annuler</button>
                    <?php if ($isEditMode): ?>
                        <button type="submit" name="update_post" class="solid-btn">Mettre à jour</button>
                    <?php else: ?>
                        <button type="submit" name="save_post" class="solid-btn">Publier</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="forum-emoji-picker" id="backEmojiPicker">
    <div class="forum-emoji-head">Choisir un emoji</div>
    <div class="forum-emoji-body" id="backEmojiBody"></div>
</div>

<script>
const postFormModal = document.getElementById('postFormModal');
const openCreatePostModal = document.getElementById('openCreatePostModal');
const closePostFormModal = document.getElementById('closePostFormModal');
const cancelPostFormModal = document.getElementById('cancelPostFormModal');

const viewPostModal = document.getElementById('viewPostModal');
const closeViewPostModal = document.getElementById('closeViewPostModal');

const triggerBackImage = document.getElementById('triggerBackImage');
const triggerBackVideo = document.getElementById('triggerBackVideo');
const triggerBackEmoji = document.getElementById('triggerBackEmoji');

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

let activeEmojiTarget = null;

const emojiList = ['😀','😁','😂','🤣','😃','😄','😅','😆','😉','😊','🙂','🙃','😍','🥰','😘','😎','🤩','🥳','😢','😭','😡','🤔','👍','👏','🙌','💪','🙏','❤️','🧡','💛','💚','💙','💜','🖤','🔥','✨','🎉','🎊','💬','📢','🚀','✅','⚠️','🍀','🌟','🌈','🎵','📷','🎥'];

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
        window.location.href = '/GoService/view/back/index.php?page=forum';
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

document.querySelectorAll('.open-view-post').forEach(btn => {
    btn.addEventListener('click', function() {
        document.getElementById('viewPostMeta').textContent = (this.dataset.author || '') + ' • ' + (this.dataset.type || '') + ' • ' + (this.dataset.status || '');
        document.getElementById('viewPostTitle').textContent = this.dataset.title || '';
        document.getElementById('viewPostContent').textContent = this.dataset.content || '';

        const imgWrap = document.getElementById('viewPostImageWrap');
        const img = document.getElementById('viewPostImage');
        const vidWrap = document.getElementById('viewPostVideoWrap');
        const vid = document.getElementById('viewPostVideo');

        imgWrap.style.display = 'none';
        vidWrap.style.display = 'none';
        img.src = '';
        vid.src = '';

        if (this.dataset.image) {
            img.src = this.dataset.image;
            imgWrap.style.display = 'block';
        }

        if (this.dataset.video) {
            vid.src = this.dataset.video;
            vidWrap.style.display = 'block';
        }

        viewPostModal.classList.add('show');
        document.body.style.overflow = 'hidden';
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

if (triggerBackImage) triggerBackImage.addEventListener('click', () => backImageInput.click());
if (triggerBackVideo) triggerBackVideo.addEventListener('click', () => backVideoInput.click());

if (triggerBackEmoji) {
    triggerBackEmoji.addEventListener('click', function(e) {
        activeEmojiTarget = backContenu;
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
    if (!e.target.closest('#backEmojiPicker') && !e.target.closest('#triggerBackEmoji')) {
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

const forumBackPostForm = document.getElementById('forumBackPostForm');
const backTitreField = document.getElementById('back_titre');
const backTypePostField = document.getElementById('back_type_post');
const backStatutPostField = document.getElementById('back_statut_post');
const backContenuField = document.getElementById('back_contenu');

function getLettersAndSpacesCountJS(text) {
    const cleaned = text.replace(/[^a-zA-ZÀ-ÿ\s]/gu, '');
    return cleaned.trim().length;
}

function hasOnlyLettersAndSpaces(text) {
    return /^[a-zA-ZÀ-ÿ\s]*$/.test(text);
}

const backRules = {
    back_titre: {
        validate: value => hasOnlyLettersAndSpaces(value) && getLettersAndSpacesCountJS(value) >= 3,
        message: 'Titre valide.',
        error: 'Le titre doit contenir au moins 3 caractères.'
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
        validate: value => hasOnlyLettersAndSpaces(value) && getLettersAndSpacesCountJS(value) >= 5,
        message: 'Description valide.',
        error: 'La description doit contenir au moins 5 caractères.'
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

</script>
