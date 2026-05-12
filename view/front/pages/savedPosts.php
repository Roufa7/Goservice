<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Africa/Tunis');

require_once __DIR__ . '/../../../controller/SaveController.php';

$saveController = new SaveController();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function savedSessionValue(array $keys, $default = null) {
    foreach ($keys as $key) {
        if (isset($_SESSION[$key]) && $_SESSION[$key] !== '') {
            return $_SESSION[$key];
        }
    }
    foreach (['user', 'auth_user', 'current_user'] as $container) {
        if (!empty($_SESSION[$container]) && is_array($_SESSION[$container])) {
            foreach ($keys as $key) {
                if (isset($_SESSION[$container][$key]) && $_SESSION[$container][$key] !== '') {
                    return $_SESSION[$container][$key];
                }
            }
        }
    }
    return $default;
}

function savedAppRoot(): string {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $root = dirname($script, 3);
    if ($root === '/' || $root === '\\') {
        return '';
    }
    return rtrim($root, '/');
}

function savedAppUrl(string $path = ''): string {
    return savedAppRoot() . '/' . ltrim($path, '/');
}

function savedMediaUrl(?string $path): string {
    $path = trim((string) $path);
    if ($path === '') {
        return '';
    }
    if (preg_match('~^https?://~i', $path)) {
        return $path;
    }
    return savedAppUrl($path);
}

function e($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function savedTimeAgo($datetime): string {
    if (empty($datetime)) {
        return '';
    }
    try {
        $now = new DateTime('now', new DateTimeZone('Africa/Tunis'));
        $date = new DateTime($datetime, new DateTimeZone('Africa/Tunis'));
        $diff = $now->getTimestamp() - $date->getTimestamp();
        if ($diff <= 0) return "a l'instant";
        if ($diff < 60) return "a l'instant";
        if ($diff < 3600) return floor($diff / 60) . ' min';
        if ($diff < 86400) return floor($diff / 3600) . ' h';
        if ($diff < 604800) return floor($diff / 86400) . ' j';
        if ($diff < 2592000) return floor($diff / 604800) . ' sem';
        return floor($diff / 2592000) . ' mois';
    } catch (Throwable $e) {
        return '';
    }
}

$currentUserId = (int) savedSessionValue(['id_user', 'user_id', 'id'], 0);
if ($currentUserId <= 0 && class_exists('config')) {
    try {
        $db = config::getConnexion();
        $firstUser = $db->query('SELECT id_user FROM `user` ORDER BY id_user ASC LIMIT 1')->fetchColumn();
        if ($firstUser) {
            $currentUserId = (int) $firstUser;
        }
    } catch (Throwable $e) {
    }
}
if ($currentUserId <= 0) {
    $currentUserId = 1;
}

$currentPage = max(1, (int) ($_GET['p'] ?? 1));
$postsPerPage = 6;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_saved'])) {
    $postId = (int) ($_POST['post_id'] ?? 0);
    if ($postId > 0) {
        $saveController->removeSave($postId, $currentUserId);
    }
    header('Location: ' . savedAppUrl('view/front/index.php?page=savedPosts&p=' . $currentPage));
    exit;
}

$savedPosts = $saveController->getSavedPostsByUser($currentUserId);
$totalPosts = count($savedPosts);
$totalPages = max(1, (int) ceil($totalPosts / $postsPerPage));
$currentPage = min($currentPage, $totalPages);
$offset = ($currentPage - 1) * $postsPerPage;
$postsToShow = array_slice($savedPosts, $offset, $postsPerPage);
?>

<style>
.saved-page{width:min(1380px,calc(100% - 36px));margin:0 auto;padding:28px 0 60px;}
.saved-hero{border-radius:34px;padding:56px 28px;margin-bottom:28px;background:linear-gradient(135deg,#0b1f34,#18314b);border:1px solid rgba(255,255,255,.08);box-shadow:0 18px 40px rgba(0,0,0,.16);}
.saved-title{margin:0 0 14px;color:#fff;font-size:48px;line-height:1.08;font-weight:900;}
.saved-intro{margin:0;max-width:760px;color:#d7e0ea;font-size:18px;line-height:1.55;}
.saved-topbar{display:flex;align-items:center;justify-content:space-between;gap:18px;margin-bottom:24px;flex-wrap:wrap;}
.saved-page-title{margin:0;color:#fff;font-size:34px;font-weight:900;}
.saved-count{margin:6px 0 0;color:#c8d3df;font-size:16px;font-weight:600;}
.saved-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:22px;}
.saved-card{background:rgba(19,40,61,.96);border:1px solid rgba(255,255,255,.08);border-radius:24px;overflow:hidden;box-shadow:0 14px 30px rgba(0,0,0,.16);display:flex;flex-direction:column;}
.saved-thumb{width:100%;height:215px;background:#d9dde4;position:relative;overflow:hidden;}
.saved-thumb img,.saved-thumb video{width:100%;height:100%;object-fit:cover;display:block;background:#0b0b0b;}
.saved-thumb-placeholder{width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#d2d9e3 0%,#edf2f7 100%);font-size:54px;}
.saved-content{padding:16px;display:flex;flex-direction:column;gap:10px;flex:1;}
.saved-post-type{display:inline-flex;align-items:center;width:max-content;padding:6px 12px;border-radius:999px;font-size:12px;font-weight:800;background:rgba(238,88,40,.10);color:#ee5828;border:1px solid rgba(238,88,40,.15);}
.saved-post-title{margin:0;color:#fff;font-size:20px;font-weight:900;line-height:1.25;}
.saved-post-text{margin:0;color:#c8d3df;font-size:14px;line-height:1.45;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden;}
.saved-meta{display:flex;align-items:center;gap:12px;margin-top:8px;}
.saved-avatar{width:42px;height:42px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:16px;color:#fff;background:linear-gradient(135deg,#EE5828 0%,#1f3144 65%,#4CAF50 100%);flex-shrink:0;}
.saved-author{color:#fff;font-weight:800;font-size:14px;}
.saved-date{color:#c8d3df;font-size:13px;}
.saved-actions{padding:0 16px 16px;display:flex;gap:10px;}
.saved-open-btn,.saved-remove-btn{flex:1;height:44px;border:none;border-radius:14px;font-weight:800;font-size:14px;cursor:pointer;text-decoration:none;display:flex;align-items:center;justify-content:center;}
.saved-open-btn{background:linear-gradient(135deg,#EE5828 0%,#c9471d 38%,#1f3144 72%,#4CAF50 100%);color:#fff;}
.saved-remove-btn{background:rgba(255,255,255,.05);color:#ff9f94;border:1px solid rgba(255,255,255,.08);}
.saved-empty{background:rgba(19,40,61,.96);border:1px solid rgba(255,255,255,.08);border-radius:24px;padding:44px 24px;text-align:center;box-shadow:0 14px 30px rgba(0,0,0,.16);}
.saved-empty h3{margin:0 0 10px;color:#fff;font-size:26px;}
.saved-empty p{margin:0 0 22px;color:#c8d3df;font-size:16px;}
.saved-pagination{display:flex;align-items:center;justify-content:center;gap:10px;margin-top:32px;flex-wrap:wrap;}
.saved-page-link{min-width:44px;height:44px;padding:0 15px;border-radius:14px;background:rgba(19,40,61,.96);border:1px solid rgba(255,255,255,.08);color:#fff;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;font-weight:900;}
.saved-page-link.active{background:#ee5828;border-color:#ee5828;}
@media (max-width:1100px){.saved-grid{grid-template-columns:repeat(2,minmax(0,1fr));}}
@media (max-width:760px){.saved-grid{grid-template-columns:1fr;}.saved-title{font-size:38px;}.saved-page-title{font-size:28px;}}
</style>

<main class="saved-page">
    <section class="saved-hero">
        <span class="section-badge">Forum social</span>
        <h1 class="saved-title">My saved posts</h1>
        <p class="saved-intro">
            Find here the posts you saved from the forum. This page now runs inside the merged application instead of loading a separate standalone template.
        </p>
    </section>

    <div class="saved-topbar">
        <div>
            <h2 class="saved-page-title">Saved posts</h2>
            <p class="saved-count"><?php echo $totalPosts; ?> saved post<?php echo $totalPosts === 1 ? '' : 's'; ?></p>
        </div>

        <a href="<?php echo e(savedAppUrl('view/front/index.php?page=forum')); ?>" class="solid-btn">Back to forum</a>
    </div>

    <?php if (empty($savedPosts)): ?>
        <div class="saved-empty">
            <h3>No saved posts</h3>
            <p>When you save a forum post, it will appear here.</p>
            <a href="<?php echo e(savedAppUrl('view/front/index.php?page=forum')); ?>" class="solid-btn">Open forum</a>
        </div>
    <?php else: ?>
        <div class="saved-grid">
            <?php foreach ($postsToShow as $post): ?>
                <?php
                $title = $post['titre'] ?? 'Untitled post';
                $content = trim((string) ($post['contenu'] ?? ''));
                $imageUrl = !empty($post['image']) ? savedMediaUrl($post['image']) : '';
                $videoUrl = !empty($post['video']) ? savedMediaUrl($post['video']) : '';
                $fullname = trim((string) (($post['prenom'] ?? '') . ' ' . ($post['nom'] ?? '')));
                if ($fullname === '') {
                    $fullname = 'Utilisateur';
                }
                $avatarLetter = strtoupper(substr($fullname, 0, 1));
                $postId = (int) ($post['id_post'] ?? 0);
                ?>
                <article class="saved-card" id="saved-post-<?php echo $postId; ?>">
                    <div class="saved-thumb">
                        <?php if ($videoUrl): ?>
                            <video muted autoplay loop playsinline preload="metadata">
                                <source src="<?php echo e($videoUrl); ?>">
                            </video>
                        <?php elseif ($imageUrl): ?>
                            <img src="<?php echo e($imageUrl); ?>" alt="Saved post image">
                        <?php else: ?>
                            <div class="saved-thumb-placeholder">Post</div>
                        <?php endif; ?>
                    </div>

                    <div class="saved-content">
                        <span class="saved-post-type"><?php echo e($post['type_post'] ?? 'Discussion'); ?></span>
                        <h3 class="saved-post-title"><?php echo e($title); ?></h3>
                        <?php if ($content !== ''): ?>
                            <p class="saved-post-text"><?php echo e($content); ?></p>
                        <?php endif; ?>

                        <div class="saved-meta">
                            <div class="saved-avatar"><?php echo e($avatarLetter); ?></div>
                            <div>
                                <div class="saved-author"><?php echo e($fullname); ?></div>
                                <div class="saved-date"><?php echo e(savedTimeAgo($post['date_saved'] ?? $post['date_publication'] ?? '')); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="saved-actions">
                        <a class="saved-open-btn" href="<?php echo e(savedAppUrl('view/front/index.php?page=forum&open_post=' . $postId)); ?>#post-<?php echo $postId; ?>">Open</a>
                        <form method="POST" style="flex:1; margin:0;">
                            <input type="hidden" name="remove_saved" value="1">
                            <input type="hidden" name="post_id" value="<?php echo $postId; ?>">
                            <button type="submit" class="saved-remove-btn">Remove</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="saved-pagination">
                <?php if ($currentPage > 1): ?>
                    <a class="saved-page-link" href="<?php echo e(savedAppUrl('view/front/index.php?page=savedPosts&p=' . ($currentPage - 1))); ?>">Prev</a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a class="saved-page-link <?php echo $i === $currentPage ? 'active' : ''; ?>" href="<?php echo e(savedAppUrl('view/front/index.php?page=savedPosts&p=' . $i)); ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a class="saved-page-link" href="<?php echo e(savedAppUrl('view/front/index.php?page=savedPosts&p=' . ($currentPage + 1))); ?>">Next</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</main>
