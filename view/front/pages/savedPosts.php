<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Africa/Tunis');

require_once __DIR__ . '/../../../controller/SaveController.php';

$saveController = new SaveController();

$currentUserId = 1;

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
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
        if ($diff < 3600) return floor($diff / 60) . ' min';
        if ($diff < 86400) return floor($diff / 3600) . ' h';
        if ($diff < 604800) return floor($diff / 86400) . ' j';
        if ($diff < 2592000) return floor($diff / 604800) . ' sem';
        return floor($diff / 2592000) . ' mois';
    } catch (Exception $e) {
        return '';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_saved'])) {
    $postId = (int)($_POST['post_id'] ?? 0);
    if ($postId > 0) {
        $saveController->removeSave($postId, $currentUserId);
    }
    header('Location: /GoService/view/front/index.php?page=savedPosts');
    exit;
}

$savedPosts = $saveController->getSavedPostsByUser($currentUserId);
?>

<style>
:root{
    --saved-orange:#EE5828;
    --saved-orange-dark:#c9471d;
    --saved-navy:#142738;
    --saved-navy-2:#0f2236;
    --saved-green:#4CAF50;
    --saved-white:#ffffff;
    --saved-bg:#f7f7fb;
    --saved-card:#ffffff;
    --saved-text:#17283f;
    --saved-text-soft:#607089;
    --saved-border:rgba(15,23,42,.08);
    --saved-shadow:0 12px 28px rgba(15,23,42,.08);
    --saved-btn-bg:linear-gradient(135deg,#EE5828 0%,#c9471d 38%,#1f3144 72%,#4CAF50 100%);
}

body.dark,
body.dark-mode,
body[data-theme="dark"],
body.theme-dark{
    --saved-bg:#0d1a28;
    --saved-card:#13283d;
    --saved-text:#ffffff;
    --saved-text-soft:#c8d3df;
    --saved-border:rgba(255,255,255,.08);
    --saved-shadow:0 14px 32px rgba(0,0,0,.22);
}

.saved-page{
    width:100%;
    max-width:1400px;
    margin:0 auto;
    padding:12px 0 26px;
}

.saved-topbar{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
    margin-bottom:22px;
    flex-wrap:wrap;
}

.saved-title-wrap{
    display:flex;
    flex-direction:column;
    gap:8px;
}

.saved-badge{
    display:inline-flex;
    align-items:center;
    width:max-content;
    padding:8px 16px;
    border-radius:999px;
    background:rgba(238,88,40,.10);
    color:var(--saved-orange);
    font-weight:800;
    font-size:14px;
}

.saved-title{
    margin:0;
    color:var(--saved-text);
    font-size:40px;
    line-height:1.1;
    font-weight:900;
}

.saved-subtitle{
    margin:0;
    color:var(--saved-text-soft);
    font-size:17px;
    line-height:1.6;
}

.saved-back-btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    min-width:170px;
    padding:14px 22px;
    border-radius:999px;
    background:var(--saved-btn-bg);
    color:#fff;
    text-decoration:none;
    font-weight:800;
    box-shadow:0 12px 24px rgba(238,88,40,.16);
}

.saved-grid{
    display:grid;
    grid-template-columns:repeat(4, minmax(0, 1fr));
    gap:22px;
}

.saved-card{
    background:var(--saved-card);
    border:1px solid var(--saved-border);
    border-radius:22px;
    overflow:hidden;
    box-shadow:var(--saved-shadow);
    display:flex;
    flex-direction:column;
    min-width:0;
    transition:.22s ease;
}

.saved-card:hover{
    transform:translateY(-3px);
}

.saved-thumb{
    width:100%;
    height:180px;
    background:#d9dde4;
    position:relative;
    overflow:hidden;
}

.saved-thumb img,
.saved-thumb video{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
    background:#0b0b0b;
}

.saved-thumb-placeholder{
    width:100%;
    height:100%;
    display:flex;
    align-items:center;
    justify-content:center;
    background:linear-gradient(135deg,#d2d9e3 0%, #edf2f7 100%);
    font-size:54px;
}

.saved-content{
    padding:16px 16px 14px;
    display:flex;
    flex-direction:column;
    gap:12px;
    flex:1;
}

.saved-post-type{
    display:inline-flex;
    align-items:center;
    width:max-content;
    padding:6px 12px;
    border-radius:999px;
    font-size:12px;
    font-weight:800;
    background:rgba(238,88,40,.10);
    color:var(--saved-orange);
    border:1px solid rgba(238,88,40,.15);
}

.saved-post-title{
    margin:0;
    color:var(--saved-text);
    font-size:19px;
    font-weight:900;
    line-height:1.3;
    display:-webkit-box;
    -webkit-line-clamp:2;
    -webkit-box-orient:vertical;
    overflow:hidden;
    min-height:50px;
}

.saved-post-text{
    margin:0;
    color:var(--saved-text-soft);
    font-size:14px;
    line-height:1.6;
    display:-webkit-box;
    -webkit-line-clamp:3;
    -webkit-box-orient:vertical;
    overflow:hidden;
    min-height:67px;
}

.saved-meta{
    display:flex;
    align-items:center;
    gap:12px;
    margin-top:auto;
}

.saved-avatar{
    width:42px;
    height:42px;
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    font-weight:800;
    font-size:16px;
    color:#fff;
    background:linear-gradient(135deg,#EE5828 0%,#1f3144 65%,#4CAF50 100%);
    flex-shrink:0;
}

.saved-meta-text{
    min-width:0;
    display:flex;
    flex-direction:column;
    gap:4px;
}

.saved-author{
    color:var(--saved-text);
    font-weight:800;
    font-size:14px;
    white-space:nowrap;
    overflow:hidden;
    text-overflow:ellipsis;
}

.saved-date{
    color:var(--saved-text-soft);
    font-size:13px;
}

.saved-actions{
    padding:0 16px 16px;
    display:flex;
    gap:10px;
}

.saved-open-btn,
.saved-remove-btn{
    flex:1;
    height:44px;
    border:none;
    border-radius:14px;
    font-weight:800;
    font-size:14px;
    cursor:pointer;
    text-decoration:none;
    display:flex;
    align-items:center;
    justify-content:center;
}

.saved-open-btn{
    background:var(--saved-btn-bg);
    color:#fff;
}

.saved-remove-btn{
    background:#f3f5f8;
    color:#c03b2a;
    border:1px solid rgba(192,59,42,.12);
}

body.dark .saved-remove-btn,
body.dark-mode .saved-remove-btn,
body[data-theme="dark"] .saved-remove-btn,
body.theme-dark .saved-remove-btn{
    background:rgba(255,255,255,.05);
    color:#ff8f81;
    border:1px solid rgba(255,255,255,.08);
}

.saved-empty{
    background:var(--saved-card);
    border:1px solid var(--saved-border);
    border-radius:24px;
    padding:40px 24px;
    text-align:center;
    box-shadow:var(--saved-shadow);
}

.saved-empty-icon{
    font-size:54px;
    margin-bottom:12px;
}

.saved-empty h3{
    margin:0 0 10px;
    color:var(--saved-text);
    font-size:26px;
}

.saved-empty p{
    margin:0;
    color:var(--saved-text-soft);
    font-size:16px;
}

@media (max-width:1200px){
    .saved-grid{
        grid-template-columns:repeat(3, minmax(0, 1fr));
    }
}

@media (max-width:900px){
    .saved-grid{
        grid-template-columns:repeat(2, minmax(0, 1fr));
    }

    .saved-title{
        font-size:32px;
    }
}

@media (max-width:620px){
    .saved-grid{
        grid-template-columns:1fr;
    }
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

.page-hero{
    padding:60px 24px;
    margin-bottom:32px;
}

.page-title{
    margin:0 0 16px;
    color:var(--saved-text);
    font-size:48px;
    line-height:1.1;
    font-weight:900;
}

.page-intro{
    margin:0;
    color:var(--saved-text-soft);
    font-size:18px;
    line-height:1.6;
    max-width:600px;
}

.section-badge{
    display:inline-flex;
    align-items:center;
    width:max-content;
    padding:8px 16px;
    border-radius:999px;
    background:rgba(238,88,40,.10);
    color:var(--saved-orange);
    font-weight:800;
    font-size:14px;
    margin-bottom:12px;
}

.reveal{
    animation:reveal .6s ease-out;
}

@keyframes reveal{
    from{
        opacity:0;
        transform:translateY(20px);
    }
    to{
        opacity:1;
        transform:translateY(0);
    }
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

.page-hero{
    padding:60px 24px;
    margin-bottom:32px;
}

.page-title{
    margin:0 0 16px;
    color:var(--saved-text);
    font-size:48px;
    line-height:1.1;
    font-weight:900;
}

.page-intro{
    margin:0;
    color:var(--saved-text-soft);
    font-size:18px;
    line-height:1.6;
    max-width:600px;
}

.section-badge{
    display:inline-flex;
    align-items:center;
    width:max-content;
    padding:8px 16px;
    border-radius:999px;
    background:rgba(238,88,40,.10);
    color:var(--saved-orange);
    font-weight:800;
    font-size:14px;
    margin-bottom:12px;
}

.reveal{
    animation:reveal .6s ease-out;
}

@keyframes reveal{
    from{
        opacity:0;
        transform:translateY(20px);
    }
    to{
        opacity:1;
        transform:translateY(0);
    }
}
</style>

<div class="saved-page">
    <section class="page-hero reveal forum-hero-classic">
        <span class="section-badge">Forum social</span>
        <h1 class="page-title">Forum & échanges</h1>
        <p class="page-intro">
            Publiez, partagez des images ou vidéos, commentez, aimez et suivez les discussions dans une interface moderne inspirée des réseaux sociaux.
        </p>
    </section>

    <?php if (empty($savedPosts)): ?>
        <div class="saved-empty">
            <div class="saved-empty-icon">🔖</div>
            <h3>Aucun post enregistré</h3>
            <p>Quand vous enregistrez un post depuis le forum, il apparaîtra ici.</p>
        </div>
    <?php else: ?>
        <div class="saved-grid">
            <?php foreach ($savedPosts as $post): ?>
                <?php
                    $title = $post['titre'] ?? 'Post sans titre';
                    $content = $post['contenu'] ?? '';
                    $imageUrl = !empty($post['image']) ? '/GoService/' . ltrim($post['image'], '/') : '';
                    $videoUrl = !empty($post['video']) ? '/GoService/' . ltrim($post['video'], '/') : '';
                    $type = $post['type_post'] ?? 'Discussion';
                    $fullname = trim(($post['prenom'] ?? '') . ' ' . ($post['nom'] ?? ''));
                    if ($fullname === '') {
                        $fullname = 'Utilisateur';
                    }
                    $avatarLetter = strtoupper(substr($fullname, 0, 1));
                    $postId = (int)($post['id_post'] ?? 0);
                ?>

                <article class="saved-card">
                    <div class="saved-thumb">
                        <?php if ($imageUrl): ?>
                            <img src="<?php echo e($imageUrl); ?>" alt="Image post">
                        <?php elseif ($videoUrl): ?>
    <?php
        $videoExtension = strtolower(pathinfo($videoUrl, PATHINFO_EXTENSION));
        $videoMime = 'video/mp4';
        if ($videoExtension === 'webm') $videoMime = 'video/webm';
        if ($videoExtension === 'ogg') $videoMime = 'video/ogg';
    ?>
    <video muted autoplay loop playsinline preload="metadata">
        <source src="<?php echo e($videoUrl); ?>" type="<?php echo e($videoMime); ?>">
        Votre navigateur ne supporte pas la vidéo.
    </video>
<?php else: ?>
                            <div class="saved-thumb-placeholder">📝</div>
                        <?php endif; ?>
                    </div>

                    <div class="saved-content">
                        <span class="saved-post-type"><?php echo e($type); ?></span>

                        <h3 class="saved-post-title"><?php echo e($title); ?></h3>

                        <p class="saved-post-text"><?php echo e($content); ?></p>

                        <div class="saved-meta">
                            <div class="saved-avatar"><?php echo e($avatarLetter); ?></div>
                            <div class="saved-meta-text">
                                <span class="saved-author"><?php echo e($fullname); ?></span>
                                <span class="saved-date"><?php echo e(timeAgo($post['date_saved'] ?? $post['date_publication'] ?? '')); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="saved-actions">
                        <a class="saved-open-btn" href="/GoService/view/front/index.php?page=forum&open_post=<?php echo $postId; ?>#post-<?php echo $postId; ?>">
    Ouvrir
</a>

                        <form method="POST" style="flex:1; margin:0;">
                            <input type="hidden" name="remove_saved" value="1">
                            <input type="hidden" name="post_id" value="<?php echo $postId; ?>">
                            <button type="submit" class="saved-remove-btn">Retirer</button>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>