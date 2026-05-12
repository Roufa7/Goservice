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
require_once __DIR__ . '/../../../service/OllamaSentimentService.php';

$postController   = new PostController();
$commentController= new CommentController();
$likeController   = new LikeController();
$shareController  = new ShareController();
$reportController = new ReportController();
$saveController   = new SaveController();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function forumSessionValue(array $keys, $default = null) {
    foreach ($keys as $key) {
        if (isset($_SESSION[$key]) && $_SESSION[$key] !== '') return $_SESSION[$key];
    }
    foreach (['user', 'auth_user', 'current_user'] as $container) {
        if (!empty($_SESSION[$container]) && is_array($_SESSION[$container])) {
            foreach ($keys as $key) {
                if (isset($_SESSION[$container][$key]) && $_SESSION[$container][$key] !== '') return $_SESSION[$container][$key];
            }
        }
    }
    return $default;
}

function forumResolveCurrentUser(): array {
    $id = (int) forumSessionValue(['id_user', 'user_id', 'id'], 0);
    $nom = trim((string) forumSessionValue(['nom', 'last_name', 'lastname'], ''));
    $prenom = trim((string) forumSessionValue(['prenom', 'first_name', 'firstname'], ''));
    $role = (string) forumSessionValue(['role'], 'user');

    if ($id > 0 && class_exists('config')) {
        try {
            $db = config::getConnexion();
            $q = $db->prepare("SELECT u.id_user, COALESCE(pr.nom, SUBSTRING_INDEX(u.email, '@', 1)) AS nom, COALESCE(pr.prenom, '') AS prenom, u.role FROM `user` u LEFT JOIN profile pr ON pr.id_user = u.id_user WHERE u.id_user = :id LIMIT 1");
            $q->execute(['id' => $id]);
            $u = $q->fetch(PDO::FETCH_ASSOC);
            if ($u) {
                $nom = trim((string)($u['nom'] ?? $nom));
                $prenom = trim((string)($u['prenom'] ?? $prenom));
                $role = (string)($u['role'] ?? $role);
            }
        } catch (Throwable $e) {}
    }

    // Fallback pour tester le module forum avant l'intégration du module User.
    if ($id <= 0 && class_exists('config')) {
        try {
            $db = config::getConnexion();
            $u = $db->query("SELECT u.id_user, COALESCE(pr.nom, SUBSTRING_INDEX(u.email, '@', 1)) AS nom, COALESCE(pr.prenom, '') AS prenom, u.role FROM `user` u LEFT JOIN profile pr ON pr.id_user = u.id_user ORDER BY u.id_user ASC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
            if ($u) {
                $id = (int)$u['id_user'];
                $nom = trim((string)($u['nom'] ?? ''));
                $prenom = trim((string)($u['prenom'] ?? ''));
                $role = (string)($u['role'] ?? 'user');
            }
        } catch (Throwable $e) {}
    }

    if ($id <= 0) $id = 1;
    $full = trim($prenom . ' ' . $nom);
    if ($full === '') $full = 'Utilisateur #' . $id;

    return [
        'id' => $id,
        'nom' => $nom,
        'prenom' => $prenom,
        'name' => $full,
        'role' => $role,
        'avatar' => strtoupper(mb_substr($full, 0, 1))
    ];
}

$currentUser = forumResolveCurrentUser();
$currentUserId = (int)$currentUser['id'];
$currentUserName = $currentUser['name'];
$currentUserAvatarLetter = $currentUser['avatar'];
$currentUserRole = $currentUser['role'];
$isForumAdmin = ($currentUserRole === 'admin');
$isForumAuthenticated = ($currentUserId > 0);

function forumRequireAuth(): void {
    if (!empty($_SESSION['user_id']) || !empty($_SESSION['id_user']) || !empty($_SESSION['id'])) {
        return;
    }
    header('Location: index.php?page=login&error=' . urlencode('Veuillez vous connecter pour effectuer cette action.'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $forumProtectedActions = ['publish_post', 'update_post', 'add_comment', 'report_post', 'report_comment', 'update_comment', 'delete_comment', 'delete_post'];
    foreach ($forumProtectedActions as $forumProtectedAction) {
        if (isset($_POST[$forumProtectedAction])) {
            forumRequireAuth();
            break;
        }
    }
}

$errors = ['titre'=>'','type_post'=>'','contenu'=>'','emoji_post'=>'','image'=>'','video'=>'','gif'=>'','comment'=>''];
$old    = ['titre'=>'','type_post'=>'','statut_post'=>'En attente','contenu'=>'','emoji_post'=>''];

$isEditMode = false;
$isEditShareMode = false;
$editSharedOriginalId = 0;
$editId     = null;
$editPost   = null;

$search = trim($_GET['search'] ?? '');
$filter = trim($_GET['filter'] ?? 'Tous');
$sort   = trim($_GET['sort']   ?? 'recent');
$mine   = (($_GET['mine'] ?? '') === '1');

function e($value){ return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function invalidClass($error){ return !empty($error) ? 'field-invalid' : ''; }
function getLettersCount($text): int { $c=preg_replace('/[^a-zA-ZÀ-ÿ]/u','',$text); return mb_strlen($c); }
function isEmojiOnlyForum(string $text): bool {
    $text=trim($text);
    if($text==='') return true;
    if(preg_match('/[\s\p{L}\p{N}]/u',$text)) return false;
    return preg_match('/^[\x{1F300}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}\x{200D}]+$/u',$text)===1;
}
function typeBadgeClass($type){
    switch(mb_strtolower($type)){case 'question':return 'type-question';case 'conseil':return 'type-conseil';default:return 'type-discussion';}
}
function timeAgo($datetime){
    if(empty($datetime)) return '';
    try{
        $now=new DateTime('now',new DateTimeZone('Africa/Tunis'));
        $date=new DateTime($datetime,new DateTimeZone('Africa/Tunis'));
        $diff=$now->getTimestamp()-$date->getTimestamp();
        if($diff<=0) return "à l'instant";
        if($diff<60) return "à l'instant";
        if($diff<3600) return floor($diff/60).' min ago';
        if($diff<86400) return floor($diff/3600).' h ago';
        if($diff<604800) return floor($diff/86400).' day ago';
        if($diff<2592000) return floor($diff/604800).' week ago';
        return floor($diff/2592000).' month ago';
    }catch(Exception $e){ return ''; }
}
function dateOnly($datetime){ if(empty($datetime)) return ''; $t=strtotime($datetime); if(!$t) return ''; return date('Y-m-d',$t); }
function countHtml(int $count): string { return $count>0?'<span class="reaction-count">'.(int)$count.'</span>':''; }
function forumExtractFirstUrl(string $text): string { if(preg_match('~https?://[^\s<>"\']+~i',$text,$m)) return trim($m[0]); return ''; }
function forumRemoveUrlFromText(string $text,string $url): string { if($url==='') return $text; return trim(str_replace($url,'',$text)); }
function forumYoutubeEmbedUrl(string $url): string {
    $p=parse_url($url); if(empty($p['host'])) return '';
    $h=strtolower($p['host']); $path=$p['path']??''; $q=$p['query']??''; $id='';
    if(str_contains($h,'youtu.be')) $id=trim($path,'/');
    if(str_contains($h,'youtube.com')){ parse_str($q,$params); if(!empty($params['v'])) $id=$params['v']; elseif(preg_match('~/(shorts|embed)/([^/?]+)~',$path,$m)) $id=$m[2]; }
    if($id==='') return ''; $id=preg_replace('/[^a-zA-Z0-9_-]/','', $id);
    return 'https://www.youtube.com/embed/'.$id;
}
function forumVimeoEmbedUrl(string $url): string {
    $p=parse_url($url); if(empty($p['host'])) return '';
    $h=strtolower($p['host']); $path=trim($p['path']??'','/');
    if(!str_contains($h,'vimeo.com')) return '';
    if(!preg_match('/^\d+$/',$path)) return '';
    return 'https://player.vimeo.com/video/'.$path;
}
function forumDailymotionEmbedUrl(string $url): string {
    $p=parse_url($url); if(empty($p['host'])) return '';
    $h=strtolower($p['host']); $path=$p['path']??''; $id='';
    if(str_contains($h,'dai.ly')) $id=trim($path,'/');
    elseif(str_contains($h,'dailymotion.com')&&preg_match('~/video/([^_/?]+)~',$path,$m)) $id=$m[1];
    if($id==='') return ''; $id=preg_replace('/[^a-zA-Z0-9]/','', $id);
    return 'https://www.dailymotion.com/embed/video/'.$id;
}
function forumTikTokEmbedUrl(string $url): string {
    $p=parse_url($url); if(empty($p['host'])) return '';
    $h=strtolower($p['host']); $path=$p['path']??'';
    if(!str_contains($h,'tiktok.com')) return '';
    if(preg_match('~/video/(\d+)~',$path,$m)) return 'https://www.tiktok.com/embed/v2/'.$m[1];
    return '';
}
function forumFacebookEmbedUrl(string $url): string {
    $p=parse_url($url); if(empty($p['host'])) return '';
    $h=strtolower($p['host']);
    if(!str_contains($h,'facebook.com')&&!str_contains($h,'fb.watch')) return '';
    return 'https://www.facebook.com/plugins/video.php?href='.urlencode($url).'&show_text=false&width=1000&autoplay=true&mute=true';
}
function forumInstagramEmbedUrl(string $url): string {
    $p=parse_url($url); if(empty($p['host'])) return '';
    $h=strtolower($p['host']); $path=trim($p['path']??'','/');
    if(!str_contains($h,'instagram.com')) return '';
    if(str_starts_with($path,'reel/')||str_starts_with($path,'p/')||str_starts_with($path,'tv/'))
        return 'https://www.instagram.com/'.$path.'/embed';
    return '';
}
function forumTwitterStatusId(string $url): string {
    $p=parse_url($url); $path=$p['path']??'';
    if(preg_match('~/status/(\d+)~',$path,$m)) return $m[1];
    return '';
}
function forumTwitterEmbedUrl(string $url): string {
    $p=parse_url($url); if(empty($p['host'])) return '';
    $h=strtolower($p['host']);
    if(!str_contains($h,'twitter.com')&&!str_contains($h,'x.com')) return '';
    return 'https://platform.twitter.com/embed/Tweet.html?id='.forumTwitterStatusId($url);
}
function forumIsDirectVideoUrl(string $url): bool { return preg_match('~\.(mp4|webm|ogg)(\?.*)?$~i',$url)===1; }
function forumVideoEmbedData(string $text): array {
    $url=forumExtractFirstUrl($text);
    if($url==='') return ['url'=>'','type'=>'','embed'=>'','provider'=>'','clean_text'=>$text];
    $yt=forumYoutubeEmbedUrl($url); if($yt!=='') return ['url'=>$url,'type'=>'iframe','embed'=>$yt,'provider'=>'YouTube','clean_text'=>forumRemoveUrlFromText($text,$url)];
    $vi=forumVimeoEmbedUrl($url); if($vi!=='') return ['url'=>$url,'type'=>'iframe','embed'=>$vi,'provider'=>'Vimeo','clean_text'=>forumRemoveUrlFromText($text,$url)];
    $dm=forumDailymotionEmbedUrl($url); if($dm!=='') return ['url'=>$url,'type'=>'iframe','embed'=>$dm,'provider'=>'Dailymotion','clean_text'=>forumRemoveUrlFromText($text,$url)];
    $tt=forumTikTokEmbedUrl($url); if($tt!=='') return ['url'=>$url,'type'=>'iframe','embed'=>$tt,'provider'=>'TikTok','clean_text'=>forumRemoveUrlFromText($text,$url)];
    $ig=forumInstagramEmbedUrl($url); if($ig!=='') return ['url'=>$url,'type'=>'iframe','embed'=>$ig,'provider'=>'Instagram','clean_text'=>forumRemoveUrlFromText($text,$url)];
    $twId=forumTwitterStatusId($url); if($twId!=='') return ['url'=>$url,'type'=>'iframe','embed'=>'https://platform.twitter.com/embed/Tweet.html?id='.$twId,'provider'=>'Twitter','clean_text'=>forumRemoveUrlFromText($text,$url)];
    $fb=forumFacebookEmbedUrl($url); if($fb!=='') return ['url'=>$url,'type'=>'iframe','embed'=>$fb,'provider'=>'Facebook','clean_text'=>forumRemoveUrlFromText($text,$url)];
    if(forumIsDirectVideoUrl($url)) return ['url'=>$url,'type'=>'video','embed'=>$url,'provider'=>'Vidéo','clean_text'=>forumRemoveUrlFromText($text,$url)];
    return ['url'=>$url,'type'=>'link','embed'=>'','provider'=>'Lien','clean_text'=>forumRemoveUrlFromText($text,$url)];
}
function forumUrl(array $extra=[]): string {
    $allowed=['page','search','filter','sort','mine']; $base=['page'=>'forum'];
    foreach($allowed as $key){ if(isset($_GET[$key])) $base[$key]=$_GET[$key]; }
    return forumAppUrl('view/front/index.php?'.http_build_query(array_merge($base,$extra)));
}
function forumAppRoot(): string {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $root = dirname($script, 3);
    if ($root === '/' || $root === '\\') {
        return '';
    }
    return rtrim($root, '/');
}
function forumAppUrl(string $path = ''): string {
    return forumAppRoot() . '/' . ltrim($path, '/');
}
function uploadImageFile(array $file,array &$errors,?string $oldPath=null): ?string {
    if(empty($file['name'])) return null;
    $ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
    if(!in_array($ext,['jpg','jpeg','png','webp','gif'])){ $errors['image']='Formats image autorisés : JPG, JPEG, PNG, WEBP, GIF.'; return null; }
    if($file['size']>5*1024*1024){ $errors['image']="L'image ne doit pas dépasser 5 Mo."; return null; }
    $dir=__DIR__.'/../../../uploads/posts/'; if(!is_dir($dir)) mkdir($dir,0777,true);
    $name=uniqid('post_img_',true).'.'.$ext;
    if(move_uploaded_file($file['tmp_name'],$dir.$name)){
        if(!empty($oldPath)){ $old=__DIR__.'/../../../'.ltrim($oldPath,'/'); if(file_exists($old)) @unlink($old); }
        return 'uploads/posts/'.$name;
    }
    $errors['image']="Erreur lors de l'upload de l'image."; return null;
}
function uploadVideoFile(array $file,array &$errors,?string $oldPath=null): ?string {
    if(empty($file['name'])) return null;
    $ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
    if(!in_array($ext,['mp4','webm','ogg'])){ $errors['video']='Formats vidéo autorisés : MP4, WEBM, OGG.'; return null; }
    if($file['size']>25*1024*1024){ $errors['video']="La vidéo ne doit pas dépasser 25 Mo."; return null; }
    $dir=__DIR__.'/../../../uploads/posts/'; if(!is_dir($dir)) mkdir($dir,0777,true);
    $name=uniqid('post_vid_',true).'.'.$ext;
    if(move_uploaded_file($file['tmp_name'],$dir.$name)){
        if(!empty($oldPath)){ $old=__DIR__.'/../../../'.ltrim($oldPath,'/'); if(file_exists($old)) @unlink($old); }
        return 'uploads/posts/'.$name;
    }
    $errors['video']="Erreur lors de l'upload de la vidéo."; return null;
}
function uploadCommentImageFile(array $file,array &$errors,?string $oldPath=null): ?string {
    if(empty($file['name'])) return null;
    $ext=strtolower(pathinfo($file['name'],PATHINFO_EXTENSION));
    if(!in_array($ext,['jpg','jpeg','png','webp','gif'],true)){ $errors['comment']='Formats image autorisés : JPG, JPEG, PNG, WEBP, GIF.'; return null; }
    if($file['size']>3*1024*1024){ $errors['comment']="L'image du commentaire ne doit pas dépasser 3 Mo."; return null; }
    $dir=__DIR__.'/../../../uploads/comments/'; if(!is_dir($dir)) mkdir($dir,0777,true);
    $name=uniqid('comment_img_',true).'.'.$ext;
    if(move_uploaded_file($file['tmp_name'],$dir.$name)){
        if(!empty($oldPath)){ $old=__DIR__.'/../../../'.ltrim($oldPath,'/'); if(file_exists($old)) @unlink($old); }
        return 'uploads/comments/'.$name;
    }
    $errors['comment']="Erreur lors de l'upload de l'image du commentaire."; return null;
}
function getCommentByIdForum(int $commentId): ?array {
    if(!class_exists('config')) return null;
    $db=config::getConnexion();
    $q=$db->prepare("SELECT * FROM commentaire WHERE id_commentaire=:id");
    $q->execute(['id'=>$commentId]); $r=$q->fetch(PDO::FETCH_ASSOC); return $r?:null;
}
function updateCommentForum(int $commentId,string $content,?string $imagePath,string $emojiContent): bool {
    if(!class_exists('config')) return false;
    $db=config::getConnexion();
    $q=$db->prepare("UPDATE commentaire SET contenu_commentaire=:c,image_commentaire=:i,emoji_commentaire=:e WHERE id_commentaire=:id");
    return $q->execute(['c'=>$content,'i'=>$imagePath,'e'=>$emojiContent,'id'=>$commentId]);
}
function forumFrontColumnExists(PDO $db,string $table,string $column): bool {
    try{ $s=$db->prepare("SHOW COLUMNS FROM `$table` LIKE :col"); $s->execute(['col'=>$column]); return (bool)$s->fetch(PDO::FETCH_ASSOC); }catch(Throwable $e){ return false; }
}
function ensureReportCommentTableFront(): bool {
    if(!class_exists('config')) return false;
    $db=config::getConnexion();
    try{ $db->exec("CREATE TABLE IF NOT EXISTS report_comment (id_report_comment INT AUTO_INCREMENT PRIMARY KEY,id_commentaire INT NOT NULL,id_user INT NOT NULL,reason VARCHAR(255) NOT NULL,date_report DATETIME DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"); return true; }catch(Throwable $e){ return false; }
}
function insertCommentReportForumFront(int $commentId,int $postId,int $userId,string $reason,string $details): bool {
    if($commentId<=0||$reason===''||!ensureReportCommentTableFront()) return false;
    $db=config::getConnexion();
    try{
        $hD=forumFrontColumnExists($db,'report_comment','details');
        $hP=forumFrontColumnExists($db,'report_comment','id_post');
        if(!$hD&&$details!=='') $reason=$reason.': '.$details;
        $cols=['id_commentaire','id_user','reason']; $vals=[':id_commentaire',':id_user',':reason'];
        $params=['id_commentaire'=>$commentId,'id_user'=>$userId,'reason'=>$reason];
        if($hP){ $cols[]='id_post'; $vals[]=':id_post'; $params['id_post']=$postId; }
        if($hD){ $cols[]='details'; $vals[]=':details'; $params['details']=$details; }
        $sql='INSERT INTO report_comment (`'.implode('`,`',$cols).'`) VALUES ('.implode(',',$vals).')';
        return $db->prepare($sql)->execute($params);
    }catch(Throwable $e){ return false; }
}
function signalCommentForumFront(int $commentId): bool {
    if(!class_exists('config')||$commentId<=0) return false;
    $db=config::getConnexion();
    try{ $db->exec("ALTER TABLE commentaire ADD COLUMN IF NOT EXISTS signale_commentaire TINYINT(1) NOT NULL DEFAULT 0"); }catch(Throwable $e){}
    try{ return $db->prepare("UPDATE commentaire SET signale_commentaire=1 WHERE id_commentaire=:id")->execute(['id'=>$commentId]); }catch(Throwable $e){ return false; }
}
function forumFindLastPostId(int $userId,string $titre,string $contenu): int {
    if(!class_exists('config')) return 0;
    $db=config::getConnexion();
    $q=$db->prepare("SELECT id_post FROM post WHERE id_user=:u AND titre=:t AND contenu=:c ORDER BY id_post DESC LIMIT 1");
    $q->execute(['u'=>$userId,'t'=>$titre,'c'=>$contenu]); $id=$q->fetchColumn(); return $id?(int)$id:0;
}
function updatePostEmojiForum(int $postId,string $emojiPost): bool {
    if(!class_exists('config')||$postId<=0) return false;
    $db=config::getConnexion();
    return $db->prepare("UPDATE post SET emoji_post=:e WHERE id_post=:id")->execute(['e'=>$emojiPost,'id'=>$postId]);
}

function forumEnsurePostGifColumn(): bool {
    if(!class_exists('config')) return false;
    try{
        $db=config::getConnexion();
        if(!forumFrontColumnExists($db,'post','gif_post')){
            $db->exec("ALTER TABLE post ADD gif_post TEXT NULL");
        }
        return true;
    }catch(Throwable $e){ return false; }
}
function updatePostGifForum(int $postId,string $gifUrl): bool {
    if(!class_exists('config')||$postId<=0) return false;
    $gifUrl=trim($gifUrl);
    if($gifUrl!=='' && !preg_match('~^https?://~i',$gifUrl)) return false;
    forumEnsurePostGifColumn();
    try{
        $db=config::getConnexion();
        return $db->prepare("UPDATE post SET gif_post=:g WHERE id_post=:id")->execute(['g'=>$gifUrl!==''?$gifUrl:null,'id'=>$postId]);
    }catch(Throwable $e){ return false; }
}
function forumMediaUrl(?string $path): string {
    $path=trim((string)$path);
    if($path==='') return '';
    if(preg_match('~^https?://~i',$path)) return $path;
    return forumAppUrl($path);
}
function updatePostSentimentForum(int $postId, string $titre, string $contenu): bool {
    if(!class_exists('config') || $postId <= 0) return false;

    try{
        $db = config::getConnexion();

        foreach([
            'sentiment_post' => "ALTER TABLE post ADD sentiment_post VARCHAR(20) DEFAULT 'neutre'",
            'sentiment_score' => "ALTER TABLE post ADD sentiment_score FLOAT DEFAULT 0",
            'toxicite_post' => "ALTER TABLE post ADD toxicite_post TINYINT(1) DEFAULT 0",
            'sentiment_raison' => "ALTER TABLE post ADD sentiment_raison TEXT NULL"
        ] as $col => $sql){
            $check = $db->prepare("SHOW COLUMNS FROM post LIKE :col");
            $check->execute(['col'=>$col]);
            if(!$check->fetch(PDO::FETCH_ASSOC)) $db->exec($sql);
        }

        $ollama = new OllamaSentimentService();
$texteAnalyse = trim($titre . ' ' . strip_tags($contenu));

file_put_contents(
    __DIR__.'/../../../debug_ai.txt',
    $texteAnalyse
);

$analyse = $ollama->analyser($texteAnalyse);
        $q = $db->prepare("
            UPDATE post SET
                sentiment_post = :sentiment,
                sentiment_score = :score,
                toxicite_post = :toxicite,
                sentiment_raison = :raison
            WHERE id_post = :id
        ");

        return $q->execute([
            'sentiment' => $analyse['sentiment'] ?? 'neutre',
            'score' => $analyse['score'] ?? 0,
            'toxicite' => $analyse['toxicite'] ?? 0,
            'raison' => $analyse['raison'] ?? 'Analyse automatique',
            'id' => $postId
        ]);
    }catch(Throwable $e){
        return false;
    }
}
function forumEnsureCommentStatusColumnFront(): bool {
    if(!class_exists('config')) return false;
    try{ $db=config::getConnexion(); $c=$db->prepare("SHOW COLUMNS FROM commentaire LIKE 'statut_commentaire'"); $c->execute(); if(!$c->fetch(PDO::FETCH_ASSOC)) $db->exec("ALTER TABLE commentaire ADD statut_commentaire VARCHAR(30) NOT NULL DEFAULT 'En attente'"); return true; }catch(Throwable $e){ return false; }
}
function forumFindLastCommentIdFront(int $postId,int $userId,string $content,?int $parentId): int {
    if(!class_exists('config')) return 0;
    try{
        $db=config::getConnexion();
        if($parentId===null){ $sql="SELECT id_commentaire FROM commentaire WHERE id_post=:p AND id_user=:u AND contenu_commentaire=:c AND (id_parent_commentaire IS NULL OR id_parent_commentaire=0) ORDER BY id_commentaire DESC LIMIT 1"; $q=$db->prepare($sql); $q->execute(['p'=>$postId,'u'=>$userId,'c'=>$content]); }
        else{ $sql="SELECT id_commentaire FROM commentaire WHERE id_post=:p AND id_user=:u AND contenu_commentaire=:c AND id_parent_commentaire=:par ORDER BY id_commentaire DESC LIMIT 1"; $q=$db->prepare($sql); $q->execute(['p'=>$postId,'u'=>$userId,'c'=>$content,'par'=>$parentId]); }
        $id=$q->fetchColumn(); return $id?(int)$id:0;
    }catch(Throwable $e){ return 0; }
}
function forumSetCommentStatusFront(int $commentId,string $status='En attente'): bool {
    if($commentId<=0||!class_exists('config')) return false;
    forumEnsureCommentStatusColumnFront();
    try{ $db=config::getConnexion(); return $db->prepare("UPDATE commentaire SET statut_commentaire=:s WHERE id_commentaire=:id")->execute(['s'=>$status,'id'=>$commentId]); }catch(Throwable $e){ return false; }
}
function forumGetRootParentCommentId(?int $parentId): ?int {
    if($parentId===null||$parentId<=0||!class_exists('config')) return $parentId;
    try{
        $db=config::getConnexion();
        $s=$db->prepare("SELECT id_commentaire,id_parent_commentaire FROM commentaire WHERE id_commentaire=:id LIMIT 1");
        $s->execute(['id'=>$parentId]); $c=$s->fetch(PDO::FETCH_ASSOC); if(!$c) return $parentId;
        $pp=(int)($c['id_parent_commentaire']??0); return $pp>0?$pp:(int)$c['id_commentaire'];
    }catch(Throwable $e){ return $parentId; }
}

function forumEnsureSharePostExtraColumns(): void {
    if(!class_exists('config')) return;
    try{
        $db=config::getConnexion();
        if(!forumFrontColumnExists($db,'share_post','description_share')){
            $db->exec("ALTER TABLE share_post ADD description_share TEXT NULL AFTER id_user");
        }
        if(!forumFrontColumnExists($db,'share_post','emoji_share')){
            $db->exec("ALTER TABLE share_post ADD emoji_share VARCHAR(255) NULL AFTER description_share");
        }
    }catch(Throwable $e){}
}

function forumAddShareRow(int $postId,int $userId,string $descriptionShare='',string $emojiShare=''): bool {
    if(!class_exists('config')||$postId<=0) return false;
    forumEnsureSharePostExtraColumns();
    try{
        $db=config::getConnexion();
        $hasDesc=forumFrontColumnExists($db,'share_post','description_share');
        $hasEmoji=forumFrontColumnExists($db,'share_post','emoji_share');
        $cols=['id_post','id_user'];
        $vals=[':id_post',':id_user'];
        $params=['id_post'=>$postId,'id_user'=>$userId];
        if($hasDesc){$cols[]='description_share';$vals[]=':description_share';$params['description_share']=$descriptionShare;}
        if($hasEmoji){$cols[]='emoji_share';$vals[]=':emoji_share';$params['emoji_share']=$emojiShare;}
        $sql='INSERT INTO share_post (`'.implode('`,`',$cols).'`) VALUES ('.implode(',',$vals).')';
        return $db->prepare($sql)->execute($params);
    }catch(Throwable $e){ return false; }
}


function forumEnsureSharedPostColumns(): void {
    if(!class_exists('config')) return;
    try{
        $db=config::getConnexion();
        if(!forumFrontColumnExists($db,'post','shared_original_post_id')){
            $db->exec("ALTER TABLE post ADD shared_original_post_id INT NULL DEFAULT NULL");
        }
    }catch(Throwable $e){}
}

function forumMarkPostAsShare(int $newPostId,int $originalPostId): bool {
    if(!class_exists('config')||$newPostId<=0||$originalPostId<=0) return false;
    forumEnsureSharedPostColumns();
    try{
        $db=config::getConnexion();
        return $db->prepare("UPDATE post SET shared_original_post_id=:orig WHERE id_post=:id")->execute(['orig'=>$originalPostId,'id'=>$newPostId]);
    }catch(Throwable $e){ return false; }
}

function forumGetPostWithUserById(int $postId): ?array {
    if(!class_exists('config')||$postId<=0) return null;
    try{
        $db=config::getConnexion();
        $sharedCol=forumFrontColumnExists($db,'post','shared_original_post_id')?'p.shared_original_post_id,':'NULL AS shared_original_post_id,';
        $sql="SELECT p.*, $sharedCol 
                     COALESCE(pr.nom, SUBSTRING_INDEX(u.email, '@', 1)) AS nom,
                     COALESCE(pr.prenom, '') AS prenom
              FROM post p
              LEFT JOIN `user` u ON u.id_user=p.id_user
              LEFT JOIN profile pr ON pr.id_user = u.id_user
              WHERE p.id_post=:id
              LIMIT 1";
        $q=$db->prepare($sql);
        $q->execute(['id'=>$postId]);
        $r=$q->fetch(PDO::FETCH_ASSOC);
        return $r?:null;
    }catch(Throwable $e){ return null; }
}

function forumRenderSharedOriginalBox(?array $original): string {
    if(!$original) return '';
    $originalName=trim(($original['prenom']??'').' '.($original['nom']??''));
    if($originalName==='') $originalName='Utilisateur';
    $avatar=strtoupper(substr($originalName,0,1));
    $imageUrl=!empty($original['image'])?forumAppUrl($original['image']):'';
    $videoUrl=!empty($original['video'])?forumAppUrl($original['video']):'';
    $gifUrl=!empty($original['gif_post'])?forumMediaUrl($original['gif_post']):'';
    $embed=forumVideoEmbedData($original['contenu']??'');
    $clean=$embed['clean_text']??($original['contenu']??'');
    ob_start();
    ?>
    <div class="shared-original-box">
        <?php if(!empty($imageUrl)): ?>
            <div class="shared-original-media"><img src="<?php echo e($imageUrl); ?>" alt="Image du post original" onerror="this.style.display='none';"></div>
        <?php elseif(!empty($gifUrl)): ?>
    <div class="shared-original-media">
        <img src="<?php echo e($gifUrl); ?>" alt="GIF du post original" onerror="this.style.display='none';">
    </div>
        <?php elseif(!empty($videoUrl)): ?>
            <div class="shared-original-media"><video autoplay muted loop playsinline preload="metadata"><source src="<?php echo e($videoUrl); ?>"></video></div>
        <?php elseif(!empty($embed['embed'])): ?>
            <?php if(($embed['type']??'')==='iframe'): ?>
                <div class="shared-original-media shared-original-embed"><iframe src="<?php echo e($embed['embed']); ?>" allow="accelerometer;autoplay;clipboard-write;encrypted-media;gyroscope;picture-in-picture;web-share" allowfullscreen scrolling="no" loading="lazy"></iframe></div>
            <?php elseif(($embed['type']??'')==='video'): ?>
                <div class="shared-original-media"><video controls playsinline preload="metadata"><source src="<?php echo e($embed['embed']); ?>"></video></div>
            <?php endif; ?>
        <?php endif; ?>
        <div class="shared-original-info">
            <div class="shared-original-user">
                <div class="mini-avatar shared-mini-avatar"><?php echo e($avatar); ?></div>
                <div>
                    <strong><?php echo e($originalName); ?></strong>
                    <div class="shared-original-meta"><?php echo e(timeAgo($original['date_publication']??'')); ?> · 🌐</div>
                </div>
            </div>
            <div class="shared-original-title"><?php echo e($original['titre']??'Publication originale'); ?></div>
            <?php if(trim($clean)!==''): ?><div class="shared-original-text"><?php echo nl2br(e($clean)); ?></div><?php endif; ?>
            <?php if(!empty($original['emoji_post'])): ?><div class="shared-original-emoji"><?php echo e($original['emoji_post']); ?></div><?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function forumUpdateSharedPostText(int $postId,string $content,string $emojiPost): bool {
    if(!class_exists('config')||$postId<=0) return false;
    forumEnsureSharedPostColumns();
    try{
        $db=config::getConnexion();
        $sql="UPDATE post
              SET contenu=:contenu, emoji_post=:emoji
              WHERE id_post=:id AND shared_original_post_id IS NOT NULL";
        return $db->prepare($sql)->execute([
            'contenu'=>$content,
            'emoji'=>$emojiPost,
            'id'=>$postId
        ]);
    }catch(Throwable $e){ return false; }
}

function forumShareInsideGoService(PostController $postController,int $originalPostId,int $userId,string $descriptionShare,string $emojiShare): int {
    if($originalPostId<=0) return 0;
    $original=$postController->getPostById($originalPostId);
    if(!$original) return 0;

    forumAddShareRow($originalPostId,$userId,$descriptionShare,$emojiShare);

    $title='Publication partagée';
    $shareText=trim($descriptionShare);
    $finalContent=$shareText!==''?$shareText:'A partagé une publication.';
    $typePost=$original['type_post'] ?? 'Discussion';

    try{
        // Comme Facebook : le nouveau post contient seulement le texte du partage,
        // et le post original reste dans une carte imbriquée avec ses données.
        $newPost=new Post(null,$title,$finalContent,null,null,$typePost,'Approuvé',$userId);
        $postController->addPost($newPost);
       $newId=forumFindLastPostId($userId,$title,$finalContent);
if($newId>0){
    updatePostSentimentForum($newId,$title,$finalContent);
    if($emojiShare!=='') updatePostEmojiForum($newId,$emojiShare);
    forumMarkPostAsShare($newId,$originalPostId);
}
        return $newId;
    }catch(Throwable $e){ return 0; }
}

/* ---- Actions POST ---- */
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['delete_post'])){
    $deleteId=(int)($_POST['post_id']??0);
    if($deleteId>0){
        $targetPost=forumGetPostWithUserById($deleteId);
        if($targetPost && ($isForumAdmin || (int)($targetPost['id_user']??0)===$currentUserId)){
            $postController->deletePost($deleteId);
            header('Location: '.forumUrl(['deleted'=>1])); exit;
        }
    }
    header('Location: '.forumUrl(['error'=>'unauthorized'])); exit;
}
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['delete_comment'])){
    $commentId=(int)($_POST['comment_id']??0); $postId=(int)($_POST['post_id']??0); $parentId=(int)($_POST['parent_id']??0);
    $oldComment=$commentId>0?getCommentByIdForum($commentId):null;
    if($oldComment && ($isForumAdmin || (int)($oldComment['id_user']??0)===$currentUserId)){
        if($parentId===0){
            if(method_exists($commentController,'deleteRepliesByComment')) $commentController->deleteRepliesByComment($commentId);
            else{ $all=$commentController->listCommentsByPost($postId); if(is_array($all)) foreach($all as $c) if((int)($c['id_parent_commentaire']??0)===$commentId) $commentController->deleteComment((int)$c['id_commentaire']); }
        }
        $commentController->deleteComment($commentId);
        header('Location: '.forumUrl(['open_post'=>$postId,'comment_deleted'=>1])); exit;
    }
    header('Location: '.forumUrl(['open_post'=>$postId,'error'=>'unauthorized'])); exit;
}
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['update_comment'])){ $commentId=(int)($_POST['comment_id']??0); $postId=(int)($_POST['post_id']??0); $newContent=trim($_POST['comment_content']??''); $emojiContent=trim($_POST['emoji_content']??''); if($commentId>0){ if($newContent==='') $errors['comment']='Le commentaire est obligatoire.'; elseif(getLettersCount($newContent)<5) $errors['comment']='Le commentaire doit contenir au moins 5 lettres.'; elseif(!isEmojiOnlyForum($emojiContent)) $errors['comment']='Le champ emoji accepte uniquement des emojis.'; $oldComment=getCommentByIdForum($commentId); $imagePath=$oldComment['image_commentaire']??null; if(empty($errors['comment'])&&!empty($_FILES['comment_image']['name'])){ $ni=uploadCommentImageFile($_FILES['comment_image'],$errors,$imagePath); if(empty($errors['comment'])&&$ni!==null) $imagePath=$ni; } if(empty($errors['comment']) && $oldComment && ($isForumAdmin || (int)($oldComment['id_user']??0)===$currentUserId)) updateCommentForum($commentId,$newContent,$imagePath,$emojiContent); } header('Location: '.forumUrl(['open_post'=>$postId,'comment_updated'=>1])); exit; }
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['report_comment'])){ $commentId=(int)($_POST['comment_id']??0); $postId=(int)($_POST['post_id']??0); $reason=trim($_POST['report_reason']??''); $details=trim($_POST['report_details']??''); if($commentId>0&&$reason!==''){ signalCommentForumFront($commentId); insertCommentReportForumFront($commentId,$postId,$currentUserId,$reason,$details); } header('Location: '.forumUrl(['open_post'=>$postId,'comment_reported'=>1])); exit; }

if(isset($_GET['edit'])&&ctype_digit($_GET['edit'])){
    $editId=(int)$_GET['edit'];
    $editPost=forumGetPostWithUserById($editId);
    if(!$editPost) $editPost=$postController->getPostById($editId);
    if($editPost && ($isForumAdmin || (int)($editPost['id_user']??0)===$currentUserId)){
        $isEditMode=true;
        $editSharedOriginalId=(int)($editPost['shared_original_post_id']??0);
        $isEditShareMode=$editSharedOriginalId>0;
        $old['titre']=$editPost['titre']??'';
        $old['type_post']=$editPost['type_post']??'';
        $old['statut_post']=$editPost['statut_post']??'';
        $old['contenu']=$editPost['contenu']??'';
        $old['emoji_post']=$editPost['emoji_post']??'';
    }
}

if($_SERVER['REQUEST_METHOD']==='POST'&&(isset($_POST['publish_post'])||isset($_POST['update_post']))){
    $old['titre']=trim($_POST['titre']??'');
    $old['type_post']=trim($_POST['type_post']??'');
    $old['contenu']=trim($_POST['contenu']??'');
    $old['emoji_post']=trim($_POST['emoji_post']??'');
    $gifPost=trim($_POST['gif_post']??'');

    if(isset($_POST['update_post'])){
        $editId=(int)($_POST['edit_id']??0);
        $editPost=forumGetPostWithUserById($editId);
        if(!$editPost) $editPost=$postController->getPostById($editId);
        if(!$editPost || (!$isForumAdmin && (int)($editPost['id_user']??0)!==$currentUserId)){
            header('Location: '.forumUrl(['error'=>'unauthorized'])); exit;
        }
        $isSharedUpdate=$editPost && (int)($editPost['shared_original_post_id']??0)>0;

        if($isSharedUpdate){
            $isEditMode=true;
            $isEditShareMode=true;
            $editSharedOriginalId=(int)($editPost['shared_original_post_id']??0);
            $old['titre']=$editPost['titre']??'Publication partagée';
            $old['type_post']=$editPost['type_post']??'Discussion';
            $old['statut_post']=$editPost['statut_post']??'Approuvé';

            if($old['contenu']==='') $errors['contenu']='Le contenu du partage est obligatoire.';
            elseif(mb_strlen($old['contenu'])<2) $errors['contenu']='Le contenu du partage doit contenir au moins 2 caractères.';
            if(!isEmojiOnlyForum($old['emoji_post'])) $errors['emoji_post']='Le champ emoji accepte uniquement des emojis.';

            $hasErrors=false; foreach($errors as $err){ if(!empty($err)){ $hasErrors=true; break; } }
            if(!$hasErrors){
                forumUpdateSharedPostText($editId,$old['contenu'],$old['emoji_post']);
                header('Location: '.forumUrl(['updated'=>1,'open_post'=>$editId])); exit;
            }
        }
    }

    if($old['titre']==='') $errors['titre']='Le titre est obligatoire.'; elseif(getLettersCount($old['titre'])<3) $errors['titre']='Le titre doit contenir au moins 3 lettres.';
    if($old['type_post']==='') $errors['type_post']='Veuillez choisir le type du post.';
    if($old['contenu']==='') $errors['contenu']='La description est obligatoire.'; elseif(mb_strlen($old['contenu'])<5) $errors['contenu']='La description doit contenir au moins 5 caractères.';
    if(!isEmojiOnlyForum($old['emoji_post'])) $errors['emoji_post']='Le champ emoji accepte uniquement des emojis.';
    if($gifPost!=='' && !preg_match('~^https?://~i',$gifPost)) $errors['gif']='GIF invalide.';
    $hasErrors=false; foreach($errors as $err){ if(!empty($err)){ $hasErrors=true; break; } }

    if(isset($_POST['publish_post'])){
        $imagePath=null;
        $videoPath=null;
        if(!$hasErrors&&!empty($_FILES['image']['name'])){
            $imagePath=uploadImageFile($_FILES['image'],$errors);
            if(!empty($errors['image'])) $hasErrors=true;
        }
        if(!$hasErrors&&!empty($_FILES['video']['name'])){
            $videoPath=uploadVideoFile($_FILES['video'],$errors);
            if(!empty($errors['video'])) $hasErrors=true;
        }
        if(!$hasErrors){
            $post=new Post(null,$old['titre'],$old['contenu'],$imagePath,$videoPath,$old['type_post'],$old['statut_post'],$currentUserId);
            $postController->addPost($post);
            $nid = forumFindLastPostId($currentUserId, $old['titre'], $old['contenu']);
            if($nid>0){
                updatePostEmojiForum($nid, $old['emoji_post']);
                updatePostGifForum($nid, $gifPost);
                updatePostSentimentForum($nid, $old['titre'], $old['contenu']);
            }
            header('Location: '.forumUrl(['published'=>1]));
            exit;
        }
    }

    if(isset($_POST['update_post'])){
        $editId=(int)($_POST['edit_id']??0);
        $editPost=forumGetPostWithUserById($editId);
        if(!$editPost) $editPost=$postController->getPostById($editId);
        if($editPost && ($isForumAdmin || (int)($editPost['id_user']??0)===$currentUserId)){
            $isEditMode=true;
            $imagePath=$editPost['image']??null;
            $videoPath=$editPost['video']??null;
            if(!$hasErrors&&!empty($_FILES['image']['name'])){
                $imagePath=uploadImageFile($_FILES['image'],$errors,$editPost['image']??null);
                if(!empty($errors['image'])) $hasErrors=true;
            }
            if(!$hasErrors&&!empty($_FILES['video']['name'])){
                $videoPath=uploadVideoFile($_FILES['video'],$errors,$editPost['video']??null);
                if(!empty($errors['video'])) $hasErrors=true;
            }
            if(!$hasErrors){
                $post=new Post($editId,$old['titre'],$old['contenu'],$imagePath,$videoPath,$old['type_post'],$old['statut_post'],$currentUserId);
                $postController->updatePost($post);
                updatePostEmojiForum($editId,$old['emoji_post']);
                updatePostGifForum($editId,$gifPost);
                updatePostSentimentForum($editId,$old['titre'],$old['contenu']);
                header('Location: '.forumUrl(['updated'=>1,'open_post'=>$editId])); exit;
            }
        }else{
            header('Location: '.forumUrl(['error'=>'unauthorized'])); exit;
        }
    }
}

if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['add_comment'])){ $commentContent=trim($_POST['comment_content']??''); $postId=(int)($_POST['post_id']??0); $parentId=!empty($_POST['parent_id'])?(int)$_POST['parent_id']:null; $parentId=forumGetRootParentCommentId($parentId); $emojiContent=trim($_POST['emoji_content']??''); if($commentContent==='') $errors['comment']='Le commentaire est obligatoire.'; elseif(!isEmojiOnlyForum($emojiContent)) $errors['comment']='Le champ emoji accepte uniquement des emojis.'; $imageCommentPath=null; if(!empty($_FILES['comment_image']['name'])){ $ext=strtolower(pathinfo($_FILES['comment_image']['name'],PATHINFO_EXTENSION)); if(in_array($ext,['jpg','jpeg','png','webp','gif'],true)&&$_FILES['comment_image']['size']<=3*1024*1024){ $dir=__DIR__.'/../../../uploads/comments/'; if(!is_dir($dir)) mkdir($dir,0777,true); $nm=uniqid('comment_img_',true).'.'.$ext; if(move_uploaded_file($_FILES['comment_image']['tmp_name'],$dir.$nm)) $imageCommentPath='uploads/comments/'.$nm; } } if(empty($errors['comment'])){ $comment=new Comment(null,$commentContent,null,$postId,$currentUserId,$parentId,$imageCommentPath,$emojiContent); $commentController->addComment($comment); $nid=forumFindLastCommentIdFront($postId,$currentUserId,$commentContent,$parentId); forumSetCommentStatusFront($nid,'En attente'); header('Location: '.forumUrl(['commented'=>1,'open_post'=>$postId,'open_comment'=>$parentId?:0])); exit; } }
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['toggle_like'])){ $postId=(int)($_POST['post_id']??0); if($likeController->isLiked($postId,$currentUserId)) $likeController->removeLike($postId,$currentUserId); else { $like=new Like(null,$postId,$currentUserId); $likeController->addLike($like); } header('Location: '.forumUrl(['open_post'=>$postId])); exit; }
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['share_post_ajax'])){
    header('Content-Type: application/json; charset=utf-8');
    $postId=(int)($_POST['post_id']??0);
    $shareMode=trim($_POST['share_mode']??'external');
    $descriptionShare=trim($_POST['description_share']??'');
    $emojiShare=trim($_POST['emoji_share']??'');

    if($postId>0){
        if($shareMode==='internal'){
            $newSharedPostId=forumShareInsideGoService($postController,$postId,$currentUserId,$descriptionShare,$emojiShare);
            echo json_encode([
                'success'=>$newSharedPostId>0,
                'new_post_id'=>$newSharedPostId,
                'shares_count'=>(int)$shareController->countShares($postId)
            ]);
        }else{
            forumAddShareRow($postId,$currentUserId,'','');
            echo json_encode(['success'=>true,'shares_count'=>(int)$shareController->countShares($postId)]);
        }
    }else echo json_encode(['success'=>false]);
    exit;
}
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['share_post'])){ $postId=(int)($_POST['post_id']??0); $share=new Share(null,$postId,$currentUserId); $shareController->addShare($share); header('Location: '.forumUrl(['open_post'=>$postId])); exit; }
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['report_post'])){ $postId=(int)($_POST['post_id']??0); $reason=trim($_POST['report_reason']??''); $details=trim($_POST['report_details']??''); $report=new Report(null,$postId,$currentUserId,$reason.($details?': '.$details:'')); $reportController->addReport($report); header('Location: '.forumUrl(['reported'=>1,'open_post'=>$postId])); exit; }
if($_SERVER['REQUEST_METHOD']==='POST'&&isset($_POST['toggle_save'])){ $postId=(int)($_POST['post_id']??0); if($postId>0){ if($saveController->isSaved($postId,$currentUserId)) $saveController->removeSave($postId,$currentUserId); else { $save=new Save(null,$postId,$currentUserId); $saveController->addSave($save); } } header('Location: '.forumUrl(['open_post'=>$postId])); exit; }

$rawPosts=$postController->listPosts();
if($rawPosts instanceof PDOStatement) $allPosts=$rawPosts->fetchAll(PDO::FETCH_ASSOC);
else $allPosts=is_array($rawPosts)?$rawPosts:[];

$posts=array_values(array_filter($allPosts,function($post) use($search,$filter,$mine,$currentUserId){
    $ok=(($post['statut_post']??'')==='Approuvé');
    if($mine) $ok=$ok&&((int)($post['id_user']??0)===$currentUserId);
    if($filter!==''&&$filter!=='Tous') $ok=$ok&&(($post['type_post']??'')===$filter);
    if($search!==''){$needle=mb_strtolower($search);$hay=mb_strtolower(($post['titre']??'').' '.($post['contenu']??''));$ok=$ok&&(mb_strpos($hay,$needle)!==false);}
    return $ok;
}));
if($sort==='recent') usort($posts,fn($a,$b)=>strtotime($b['date_publication']??'now')<=>strtotime($a['date_publication']??'now'));
elseif($sort==='liked') usort($posts,fn($a,$b)=>((int)($b['likes_count']??$b['nb_likes']??0))<=>((int)($a['likes_count']??$a['nb_likes']??0)));
elseif($sort==='commented') usort($posts,fn($a,$b)=>((int)($b['comments_count']??$b['nb_comments']??0))<=>((int)($a['comments_count']??$a['nb_comments']??0)));

foreach($posts as &$post){
    $comments=$commentController->listCommentsByPost($post['id_post']);
    $post['comments']=is_array($comments)?array_values(array_filter($comments,function($c){ return(($c['statut_commentaire']??'En attente')==='Approuvé'); })):[];
    $post['likes_count']=(int)$likeController->countLikes($post['id_post']);
    $post['shares_count']=(int)$shareController->countShares($post['id_post']);
    $post['reports_count']=(int)$reportController->countReports($post['id_post']);
    $post['saves_count']=(int)$saveController->countSaves($post['id_post']);
    $post['is_liked']=$likeController->isLiked($post['id_post'],$currentUserId);
    $post['is_saved']=$saveController->isSaved($post['id_post'],$currentUserId);
    $post['comments_count']=count($post['comments']);
}
unset($post);

$topContributors=method_exists($postController,'getTopContributors')?$postController->getTopContributors(5):[];
?>
<style>
:root{
    --forum-orange:#EE5828;--forum-orange-dark:#c9471d;--forum-navy:#142738;--forum-navy-2:#0f2236;--forum-navy-3:#173552;--forum-green:#4CAF50;--forum-white:#FFFFFF;
    --forum-bg-card:#ffffff;--forum-bg-soft:#f5f7fb;--forum-bg-input:#ffffff;--forum-bg-panel:#ffffff;--forum-bg-item:#ffffff;
    --forum-text:#17283f;--forum-text-soft:#607089;--forum-border:rgba(15,23,42,.08);
    --forum-btn-bg:linear-gradient(135deg,#EE5828 0%,#c9471d 38%,#1f3144 72%,#4CAF50 100%);
    --forum-btn-text:#ffffff;--forum-shadow:0 12px 30px rgba(15,23,42,.08);
}
body.dark,body.dark-mode,body[data-theme="dark"],body.theme-dark{
    --forum-bg-card:rgba(16,38,59,0.96);--forum-bg-soft:#0f2236;--forum-bg-input:#071523;--forum-bg-panel:rgba(16,38,59,0.96);--forum-bg-item:#0c2033;
    --forum-text:#ffffff;--forum-text-soft:#c7d3e0;--forum-border:rgba(255,255,255,.08);--forum-shadow:0 12px 30px rgba(0,0,0,.18);
}
.forum-page{display:flex;flex-direction:column;gap:28px;width:100%;}
.forum-hero-classic{position:relative;overflow:hidden;border-radius:32px;}
.forum-hero-classic::before{content:"";position:absolute;inset:0;background:url('<?php echo e(forumAppUrl('assets/images/forum-hero.png')); ?>') center/cover no-repeat;opacity:1;z-index:0;pointer-events:none;}
.forum-hero-classic::after{content:"";position:absolute;inset:0;background:linear-gradient(90deg,rgba(255,255,255,.78) 0%,rgba(255,255,255,.62) 34%,rgba(255,255,255,.18) 68%,rgba(255,255,255,.06) 100%);z-index:0;pointer-events:none;}
body.dark .forum-hero-classic::after,body.dark-mode .forum-hero-classic::after,body[data-theme="dark"] .forum-hero-classic::after,body.theme-dark .forum-hero-classic::after{background:linear-gradient(90deg,rgba(8,18,30,.84) 0%,rgba(8,18,30,.70) 38%,rgba(8,18,30,.34) 70%,rgba(8,18,30,.14) 100%);}
.forum-hero-classic>*{position:relative;z-index:1;}
.success-message{background:#eef8f1;color:#237c48;border:1px solid #d6eddc;border-radius:18px;padding:14px 18px;font-weight:700;}
.forum-action-bar{display:flex;flex-direction:column;gap:16px;max-width:1380px;width:100%;margin:0 auto;}
.forum-search-layout{display:flex;flex-direction:column;gap:14px;width:100%;}
.forum-search-main{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:14px;align-items:center;}
.forum-search-box input{width:100%;height:58px;border:1px solid var(--forum-border);border-radius:18px;padding:0 18px;background:var(--forum-bg-input);font:inherit;color:var(--forum-text);outline:none;box-sizing:border-box;}
.forum-search-box input::placeholder{color:var(--forum-text-soft);}
.forum-search-box input:focus,.forum-sort-select:focus{border-color:rgba(238,88,40,.32);box-shadow:0 0 0 4px rgba(238,88,40,.10);}
.forum-top-actions{display:flex;gap:12px;flex-wrap:wrap;}
.forum-bottom-actions{display:grid;grid-template-columns:1fr 220px;gap:14px;align-items:center;}
.forum-filter-row{display:flex;gap:10px;flex-wrap:wrap;}
.forum-filter-btn{display:inline-flex;align-items:center;justify-content:center;min-width:118px;padding:12px 20px;border-radius:999px;background:var(--forum-btn-bg);color:var(--forum-btn-text);text-decoration:none;font-weight:800;font-size:15px;box-shadow:0 10px 22px rgba(238,88,40,.16);transition:.25s ease;}
.forum-filter-btn:hover,.forum-filter-btn.active{transform:translateY(-1px);filter:brightness(1.03);}
.forum-sort-wrap{display:flex;justify-content:flex-end;}
.forum-sort-select{width:100%;height:56px;border:1px solid transparent;border-radius:999px;padding:0 18px;background:var(--forum-btn-bg);color:#fff;font:inherit;font-weight:800;outline:none;box-sizing:border-box;appearance:none;-webkit-appearance:none;-moz-appearance:none;color-scheme:dark;}
.forum-sort-select option{background:#ffffff;color:#17283f;}
body.dark .forum-sort-select option,body.dark-mode .forum-sort-select option,body[data-theme="dark"] .forum-sort-select option,body.theme-dark .forum-sort-select option{background:#142738!important;color:#ffffff!important;}
.forum-main-layout{display:grid;grid-template-columns:minmax(0,980px) 330px;gap:24px;align-items:start;justify-content:center;width:100%;max-width:1380px;margin:0 auto;}
.forum-feed{display:flex;flex-direction:column;gap:22px;width:100%;min-width:0;}
.composer-card{padding:16px 18px;width:100%;border-radius:24px;background:var(--forum-bg-card);border:1px solid var(--forum-border);box-shadow:var(--forum-shadow);}
.composer-top{display:flex;align-items:center;gap:12px;}
.composer-open-btn{flex:1;height:54px;border:none;border-radius:999px;background:var(--forum-bg-input);color:var(--forum-text-soft);font:inherit;font-size:18px;text-align:left;padding:0 18px;cursor:pointer;}
.composer-open-btn:hover{filter:brightness(.98);}
.composer-icons{display:flex;align-items:center;gap:10px;}
.composer-icon-btn{border:none;background:transparent;font-size:24px;cursor:pointer;color:var(--forum-text);}
.modal-overlay{position:fixed;inset:0;background:rgba(15,23,42,.42);display:none;align-items:center;justify-content:center;z-index:9999;padding:20px;}
.modal-overlay.show{display:flex;}
.forum-modal{width:min(620px,100%);max-height:88vh;background:#fff;border-radius:24px;box-shadow:0 30px 80px rgba(15,23,42,.25);overflow:hidden;display:flex;flex-direction:column;}
.forum-modal-head{padding:16px 18px;border-bottom:1px solid rgba(15,23,42,.08);display:flex;align-items:center;justify-content:space-between;flex-shrink:0;}
.forum-modal-title{font-size:28px;font-weight:800;color:#17283f;}
.forum-modal-close{width:42px;height:42px;border:none;border-radius:50%;background:#f2f4f8;font-size:24px;cursor:pointer;}
.forum-modal-body{padding:18px;overflow-y:auto;}
.forum-modal-user{display:flex;align-items:center;gap:12px;margin-bottom:14px;}
.forum-modal-name{font-weight:800;color:#17283f;}
.forum-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:12px;}
.forum-form-grid .full-width{grid-column:1/-1;}
.forum-form-field{display:flex;flex-direction:column;min-width:0;}
.forum-form-field input[type="text"],.forum-form-field input[type="file"],.forum-form-field select,.forum-form-field textarea{width:100%;box-sizing:border-box;border:1px solid rgba(15,23,42,.10);border-radius:18px;background:#fff;font:inherit;color:#203047;outline:none;}
.forum-form-field input[type="text"],.forum-form-field input[type="file"],.forum-form-field select{height:54px;padding:0 14px;}
.forum-form-field input[type="file"]{padding:12px 14px;}
.forum-form-field textarea{min-height:120px;padding:14px;resize:vertical;}
.forum-form-field input:focus,.forum-form-field select:focus,.forum-form-field textarea:focus{border-color:rgba(238,88,40,.25);box-shadow:0 0 0 4px rgba(238,88,40,.08);}
.forum-upload-preview,.forum-video-preview,.forum-current-image,.forum-current-video{margin-top:10px;border-radius:16px;overflow:hidden;border:1px solid rgba(15,23,42,.08);display:none;background:#0b0b0b;}
.forum-upload-preview.show,.forum-video-preview.show,.forum-current-image.show,.forum-current-video.show{display:block;}
.forum-upload-preview img,.forum-current-image img{width:100%;max-height:220px;object-fit:contain;display:block;background:#0b0b0b;}
.forum-video-preview video,.forum-current-video video{width:100%;max-height:240px;object-fit:contain;display:block;background:#000;}
.forum-emoji-input,.comment-emoji-input{width:100%;box-sizing:border-box;border:1px solid rgba(15,23,42,.10);border-radius:18px;background:#fff;font:inherit;color:#203047;outline:none;min-height:54px;padding:0 14px;margin-top:12px;font-size:22px;}
.comment-emoji-input{width:100%;max-width:100%;min-height:54px;height:54px;margin-top:10px;margin-bottom:12px;padding:0 16px;border-radius:20px;font-size:22px;text-align:left;display:block;box-sizing:border-box;}
.forum-emoji-input:focus,.comment-emoji-input:focus{border-color:rgba(238,88,40,.25);box-shadow:0 0 0 4px rgba(238,88,40,.08);}
.forum-modal-tools{margin-top:14px;padding:12px 14px;border:1px solid rgba(15,23,42,.08);border-radius:18px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;}
.forum-tool-icons{display:flex;gap:12px;align-items:center;}
.tool-trigger{border:none;background:transparent;font-size:26px;cursor:pointer;}
.forum-form-actions{display:flex;gap:12px;flex-wrap:wrap;margin-top:16px;}
.field-error{display:block;min-height:18px;margin-top:8px;color:#ea5a5a;font-size:13px;padding-left:4px;}
.field-valid{display:block;min-height:18px;margin-top:8px;color:#1f9d55;font-size:13px;padding-left:4px;}
.field-invalid{border:1.8px solid #ea5a5a!important;box-shadow:0 0 0 4px rgba(234,90,90,.08)!important;}
.field-valid-input{border:1.8px solid #22a559!important;box-shadow:0 0 0 4px rgba(34,165,89,.08)!important;}
.forum-posts-list{display:flex;flex-direction:column;gap:22px;}
.post-card{border:1px solid var(--forum-border);box-shadow:var(--forum-shadow);position:relative;width:100%;border-radius:24px;overflow:hidden;background:var(--forum-bg-card);padding:22px;}
.post-header-row{display:flex;justify-content:space-between;align-items:flex-start;gap:16px;}
.post-user{display:flex;align-items:center;gap:14px;min-width:0;}
.post-user strong,.post-title{color:var(--forum-text);}
.post-meta-line{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-top:6px;}
.post-type-badge{display:inline-flex;align-items:center;padding:6px 12px;border-radius:999px;font-size:13px;font-weight:700;background:rgba(238,88,40,.12);color:#e67a52;border:1px solid rgba(238,88,40,.18);}
.post-date-exact,.post-time-top,.post-content{color:var(--forum-text-soft);}
.post-time-top{font-weight:600;white-space:nowrap;}
.post-menu-wrap{display:flex;align-items:center;gap:10px;position:relative;}
.post-menu-btn{width:52px;height:52px;border:none;border-radius:50%;background:#fff;box-shadow:0 8px 18px rgba(15,23,42,.08);cursor:pointer;font-size:22px;color:#203047;font-weight:900;}
.post-dropdown{position:absolute;top:60px;right:0;min-width:200px;background:#fff;border-radius:18px;box-shadow:0 18px 38px rgba(15,23,42,.12);padding:10px;display:none;z-index:20;}
.post-dropdown.show{display:block;}
.post-dropdown a,.post-dropdown button{width:100%;display:flex;align-items:center;gap:10px;padding:12px 14px;border:none;background:#fff;border-radius:12px;cursor:pointer;text-decoration:none;font:inherit;color:#111!important;text-align:left;}
.post-dropdown a:hover,.post-dropdown button:hover{background:#f7f8fb;}
.post-title{margin-top:18px;margin-bottom:10px;font-size:24px;line-height:1.25;font-weight:800;}
.post-content{line-height:1.7;font-size:16px;margin:0;}
.sentiment-zone{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    margin-top:10px;
    margin-bottom:10px;
}
.sentiment-badge{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:6px 12px;
    border-radius:999px;
    font-size:13px;
    font-weight:800;
}
.sentiment-badge.positive{background:#dcfce7;color:#166534;}
.sentiment-badge.negative{background:#fee2e2;color:#991b1b;}
.sentiment-badge.neutral{background:#e5e7eb;color:#374151;}
.sentiment-badge.toxic{background:#ffedd5;color:#c2410c;}

.shared-original-box{margin-top:16px;border:1px solid var(--forum-border);border-radius:18px;overflow:hidden;background:var(--forum-bg-card);box-shadow:0 8px 18px rgba(15,23,42,.04);}
.shared-original-media{width:100%;background:#000;display:flex;align-items:center;justify-content:center;max-height:520px;overflow:hidden;}
.shared-original-media img{width:100%;max-height:520px;object-fit:cover;display:block;}
.shared-original-media video{width:100%;max-height:520px;object-fit:contain;background:#000;display:block;}
.shared-original-embed{height:520px;}
.shared-original-embed iframe{width:100%;height:520px;border:0;background:#000;display:block;}
.shared-original-info{padding:14px 16px;background:var(--forum-bg-card);}
.shared-original-user{display:flex;align-items:center;gap:10px;margin-bottom:10px;color:var(--forum-text);}
.shared-original-user strong{color:var(--forum-text);font-size:17px;}
.shared-original-meta{color:var(--forum-text-soft);font-size:13px;margin-top:2px;}
.shared-mini-avatar{width:42px!important;height:42px!important;font-size:16px!important;}
.shared-original-title{font-weight:900;color:var(--forum-text);font-size:20px;line-height:1.3;margin-bottom:6px;}
.shared-original-text{color:var(--forum-text);font-size:16px;line-height:1.55;word-break:break-word;}
.shared-original-emoji{font-size:22px;margin-top:8px;line-height:1.2;}
.post-media-frame,.post-url-video-frame{width:100%;margin-top:18px;border-radius:24px;overflow:hidden;background:#000;position:relative;display:flex;align-items:center;justify-content:center;}
.post-media-frame img{width:100%;max-height:640px;object-fit:contain;background:#000;display:block;}
.post-gif-frame{width:100%;margin-top:18px;border-radius:24px;overflow:hidden;background:#000;position:relative;display:flex;align-items:center;justify-content:center;box-shadow:0 12px 26px rgba(15,23,42,.08);}
.post-gif-frame img{width:100%;max-height:520px;object-fit:contain;background:#000;display:block;}
.gif-selected-preview{margin-top:12px;border-radius:18px;overflow:hidden;background:#000;border:1px solid var(--forum-border);display:none;position:relative;}
.gif-selected-preview.show{display:block;}
.gif-selected-preview img{width:100%;max-height:260px;object-fit:contain;background:#000;display:block;}
.gif-clear-btn{position:absolute;right:12px;top:12px;width:36px;height:36px;border:none;border-radius:50%;background:rgba(0,0,0,.55);color:#fff;font-size:20px;cursor:pointer;z-index:2;}
.post-media-frame video{width:100%;height:620px;object-fit:contain;background:#000;display:block;}
.post-url-video-frame iframe{border:0;display:block;background:#000;}
.post-provider-youtube,.post-provider-vimeo,.post-provider-dailymotion,.post-provider-video{height:620px;}
.post-provider-youtube iframe,.post-provider-vimeo iframe,.post-provider-dailymotion iframe,.post-provider-video iframe{width:100%;height:620px!important;}
.post-provider-facebook{height:620px;background:#000;}
.post-provider-facebook iframe{width:100%;height:620px!important;background:#000;}
.post-provider-instagram{height:720px;background:#000;}
.post-provider-instagram iframe{width:min(520px,100%);height:940px!important;margin:0 auto;background:#000;transform:translateY(-95px);}
.post-provider-tiktok{height:760px;background:#000;}
.post-provider-tiktok iframe{width:min(430px,100%);height:980px!important;margin:0 auto;background:#000;transform:translateY(-105px);}
.post-provider-twitter{height:560px;background:#000;}
.post-provider-twitter iframe{width:min(560px,100%);height:700px!important;margin:0 auto;background:#000;transform:translateY(-55px);}
.post-media-overlay{position:absolute;right:16px;bottom:16px;background:rgba(0,0,0,.55);color:#fff;padding:8px 12px;border-radius:999px;font-size:13px;font-weight:700;pointer-events:none;}
.forum-reactions-bar{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px;margin-top:18px;padding-top:12px;border-top:1px solid var(--forum-border);align-items:stretch;}
.forum-reactions-bar form,.forum-reactions-bar>*{width:100%;min-width:0;margin:0;}
.post-reaction-btn{width:100%;height:52px;display:flex;align-items:center;justify-content:center;gap:8px;padding:0 14px;border:none;border-radius:18px;cursor:pointer;font-weight:800;font-size:14px;background:var(--forum-btn-bg);color:#fff;transition:all .2s ease;box-sizing:border-box;box-shadow:0 10px 22px rgba(238,88,40,.16);white-space:nowrap;}
.post-reaction-btn:hover{transform:translateY(-1px);filter:brightness(1.05);}
.comment-box{display:block;margin-top:18px;}
.comment-box.hidden{display:none;}
.comment-box h4{color:var(--forum-text);}
.comment-area{width:100%;min-height:120px;border:1px solid var(--forum-border);border-radius:20px;padding:16px;box-sizing:border-box;resize:vertical;font:inherit;outline:none;background:var(--forum-bg-input);color:var(--forum-text);}
.comment-area::placeholder{color:var(--forum-text-soft);}
.comment-area:focus{border-color:rgba(238,88,40,.22);box-shadow:0 0 0 4px rgba(238,88,40,.08);}
.comment-tools{display:flex;align-items:center;gap:12px;margin-top:12px;flex-wrap:wrap;}
.comment-tool-btn{border:none;background:var(--forum-bg-input);color:var(--forum-text);width:44px;height:44px;border-radius:14px;font-size:22px;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;box-shadow:0 8px 18px rgba(15,23,42,.08);transition:.2s ease;}
.comment-tool-btn:hover{transform:translateY(-1px);filter:brightness(.98);}
.comment-hidden-input{display:none;}
.comment-form-panel{background:var(--forum-bg-card);border:1px solid var(--forum-border);border-radius:22px;}
.comment-form,.reply-form{margin-top:14px;}
.reply-tool-btn{width:38px;height:38px;font-size:18px;border-radius:12px;}
.reply-submit-btn{min-height:38px;padding:0 16px;font-size:13px;}
.comments-list{display:flex;flex-direction:column;gap:16px;}
.comment-thread{border:1px solid var(--forum-border);border-radius:22px;background:rgba(255,255,255,.02);padding:16px;}
.comment-item,.reply-item{display:flex;align-items:flex-start;gap:12px;}
.comment-body{flex:1;min-width:0;}
.comment-bubble{background:var(--forum-bg-input);border:1px solid var(--forum-border);border-radius:20px;padding:14px 16px;}
.reply-bubble{background:rgba(255,255,255,.03);}
.comment-author{display:block;color:var(--forum-text);font-size:18px;margin-bottom:6px;}
.comment-text{color:var(--forum-text);line-height:1.65;word-break:break-word;}
.comment-emoji-line{margin-top:8px;font-size:22px;line-height:1.2;}
.comment-image-wrap{margin-top:10px;}
.comment-image{max-width:220px;border-radius:14px;display:block;}
.comment-meta-row{display:flex;align-items:center;gap:16px;margin-top:10px;padding-left:4px;flex-wrap:wrap;}
.comment-time{color:var(--forum-text-soft);font-size:14px;}
.reply-btn{border:none;background:none;color:#2b7cff;cursor:pointer;font-size:15px;font-weight:800;padding:0;}
.reply-btn:hover{text-decoration:underline;color:#63a1ff;}
.reply-box{margin-top:12px;padding:14px;border:1px solid var(--forum-border);border-radius:18px;background:rgba(255,255,255,.03);}
.reply-area{min-height:88px;border-radius:16px;}
.replies-list{margin-top:14px;margin-left:18px;padding-left:18px;border-left:2px solid rgba(255,255,255,.06);display:flex;flex-direction:column;gap:12px;}
.reply-item .comment-author{font-size:16px;}
.comment-header-row,.reply-header-row{display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:6px;}
.comment-menu-wrap{position:relative;flex-shrink:0;}
.comment-menu-btn{width:32px;height:32px;border:none;border-radius:50%;background:transparent;cursor:pointer;font-size:18px;color:var(--forum-text-soft);display:flex;align-items:center;justify-content:center;transition:.18s ease;font-weight:900;line-height:1;}
.comment-menu-btn:hover{background:var(--forum-bg-soft);color:var(--forum-text);}
.comment-dropdown{position:absolute;top:36px;right:0;min-width:180px;background:#fff;border-radius:16px;box-shadow:0 18px 38px rgba(15,23,42,.14);padding:8px;display:none;z-index:100;border:1px solid rgba(15,23,42,.06);}
.comment-dropdown.show{display:block;}
.comment-dropdown button{width:100%;display:flex;align-items:center;gap:10px;padding:10px 12px;border:none;background:#fff;border-radius:10px;cursor:pointer;font:inherit;font-size:14px;font-weight:600;color:#17283f;text-align:left;transition:.15s ease;}
.comment-dropdown button:hover{background:#f5f7fb;}
.comment-dropdown button.danger{color:#dc2626;}
.comment-dropdown button.danger:hover{background:#fff5f5;}
.comment-edit-area{width:100%;min-height:80px;border:1.5px solid rgba(238,88,40,.3);border-radius:14px;padding:12px;box-sizing:border-box;resize:vertical;font:inherit;outline:none;background:var(--forum-bg-input);color:var(--forum-text);margin-top:8px;}
.comment-edit-area:focus{border-color:rgba(238,88,40,.5);box-shadow:0 0 0 4px rgba(238,88,40,.08);}
.comment-edit-actions{display:flex;gap:8px;margin-top:8px;flex-wrap:wrap;}
.comment-edit-save-btn{padding:8px 18px;border:none;border-radius:10px;background:var(--forum-btn-bg);color:#fff;font:inherit;font-weight:700;font-size:13px;cursor:pointer;}
.comment-edit-cancel-btn{padding:8px 18px;border:1px solid var(--forum-border);border-radius:10px;background:transparent;color:var(--forum-text-soft);font:inherit;font-weight:700;font-size:13px;cursor:pointer;}
#reportCommentModal{position:fixed;inset:0;background:rgba(20,39,56,.45);z-index:999999;align-items:center;justify-content:center;display:none;}
#reportCommentModal.show{display:flex;}
.forum-right-panel{display:flex;flex-direction:column;gap:20px;position:sticky;top:18px;}
.forum-right-panel .panel{padding:20px;border-radius:24px;background:var(--forum-bg-panel);border:1px solid var(--forum-border);box-shadow:var(--forum-shadow);}
.top-contributors-list,.recent-posts-list{display:flex;flex-direction:column;gap:14px;margin-top:14px;}
.top-contributor-item,.recent-post-item{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:16px 18px;border-radius:20px;background:var(--forum-bg-item);border:1px solid var(--forum-border);box-shadow:0 10px 24px rgba(15,23,42,.05);}
.top-contributor-left,.recent-post-left{display:flex;align-items:center;gap:12px;min-width:0;}
.top-contributor-count{font-size:22px;font-weight:800;color:var(--forum-text);}
.recent-post-left strong,.top-contributor-left strong{display:block;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:200px;color:var(--forum-text);}
.recent-post-left span,.top-contributor-left span{color:var(--forum-text-soft);}
.media-viewer-overlay{position:fixed;inset:0;background:#000;display:none;z-index:10000;}
.media-viewer-overlay.show{display:block;}
.media-viewer-close{position:absolute;top:18px;left:18px;width:54px;height:54px;border:none;border-radius:50%;background:rgba(255,255,255,.14);color:#fff;font-size:28px;cursor:pointer;z-index:12;}
.media-viewer-content{width:100%;height:100%;display:none;}
.media-viewer-content.show{display:block;}
.media-viewer-image-layout{width:100%;height:100%;display:grid;grid-template-columns:minmax(0,1fr) 420px;}
.media-viewer-image-main{display:flex;align-items:center;justify-content:center;padding:24px;min-width:0;}
.media-viewer-image-main img{max-width:100%;max-height:92vh;object-fit:contain;display:block;}
.media-viewer-image-side{height:100%;overflow-y:auto;display:flex;flex-direction:column;border-left:1px solid rgba(255,255,255,.08);color:#fff;}
.viewer-post-head{padding:18px;border-bottom:1px solid rgba(255,255,255,.08);display:flex;align-items:flex-start;justify-content:space-between;gap:12px;}
.viewer-user{display:flex;gap:12px;align-items:center;min-width:0;}
.viewer-user-meta strong{display:block;color:#fff;}
.viewer-user-meta span{color:#c7d3e0;font-size:14px;}
.viewer-post-body{padding:18px;color:#f5f8fc;line-height:1.65;border-bottom:1px solid rgba(255,255,255,.08);}
.viewer-post-title{font-size:20px;font-weight:800;color:#fff;margin-bottom:10px;}
.viewer-stats{display:flex;gap:18px;align-items:center;padding:14px 18px;border-bottom:1px solid rgba(255,255,255,.08);color:#fff;font-weight:700;flex-wrap:wrap;}
.viewer-actions{display:grid;grid-template-columns:repeat(4,1fr);gap:12px;padding:16px 18px;border-bottom:1px solid rgba(255,255,255,.08);}
.viewer-actions form{margin:0;}
.viewer-action-btn{width:100%;border:none;background:var(--forum-btn-bg);border-radius:16px;padding:12px 10px;font:inherit;font-weight:800;color:#fff;cursor:pointer;}
.viewer-comments{padding:18px;display:flex;flex-direction:column;gap:14px;max-height:380px;overflow-y:auto;}
.viewer-comment-item{display:flex;gap:10px;align-items:flex-start;}
.viewer-comment-content{flex:1;min-width:0;}
.viewer-comment-bubble{padding:12px 14px;border-radius:18px;line-height:1.5;color:#fff;max-width:100%;}
.viewer-comment-author{font-weight:800;display:block;margin-bottom:4px;}
.viewer-comment-meta{font-size:13px;color:#c7d3e0;margin-top:6px;display:flex;gap:12px;align-items:center;flex-wrap:wrap;}
.viewer-reply-btn{background:none;border:none;color:#c7d3e0;cursor:pointer;font-weight:700;padding:0;}
.viewer-replies{margin-top:10px;margin-left:34px;display:flex;flex-direction:column;gap:10px;}
.viewer-reply-item{display:flex;gap:10px;align-items:flex-start;}
.viewer-reply-bubble{padding:10px 12px;border-radius:16px;color:#fff;line-height:1.5;max-width:100%;}
.media-viewer-video-layout{width:100%;height:100%;display:grid;grid-template-columns:minmax(0,1fr) 110px;align-items:center;}
.media-viewer-video-main{width:100%;height:100%;display:flex;align-items:center;justify-content:center;padding:20px 10px 20px 72px;box-sizing:border-box;}
.media-viewer-video-main video{max-width:100%;max-height:94vh;object-fit:contain;display:block;border-radius:14px;background:#000;}
.media-viewer-video-side{display:flex;flex-direction:column;align-items:center;gap:26px;color:#fff;font-size:18px;padding-right:18px;}
.media-viewer-action{display:flex;flex-direction:column;align-items:center;gap:6px;color:#fff;font-weight:700;}
.viewer-side-icon{width:54px;height:54px;border-radius:50%;background:var(--forum-btn-bg);display:flex;align-items:center;justify-content:center;font-size:24px;}
.emoji-picker{position:fixed;width:320px;max-width:calc(100vw - 24px);background:#fff;border:1px solid rgba(15,23,42,.08);border-radius:22px;box-shadow:0 24px 60px rgba(15,23,42,.20);display:none;z-index:1000005;overflow:hidden;}
.emoji-picker.show{display:block;}
.emoji-picker-head{padding:14px 16px;border-bottom:1px solid rgba(15,23,42,.08);font-weight:800;color:#17283f;background:#fff;}
.emoji-picker-tabs{display:flex;gap:8px;padding:10px 12px;border-bottom:1px solid rgba(15,23,42,.08);overflow-x:auto;background:#fafbfc;}
.emoji-tab{border:none;background:#fff;border-radius:999px;padding:8px 12px;cursor:pointer;font-size:14px;white-space:nowrap;}
.emoji-tab.active{background:#1f3144;color:#fff;}
.emoji-picker-body{max-height:260px;overflow-y:auto;padding:12px;display:grid;grid-template-columns:repeat(7,1fr);gap:8px;}
.emoji-btn{border:none;background:#fff;border-radius:12px;height:40px;font-size:22px;cursor:pointer;transition:.18s ease;}
.emoji-btn:hover{background:#f1f4f8;transform:scale(1.08);}
:root{--viewer-side-bg:#ffffff;--viewer-side-card:#ffffff;--viewer-side-text:#142738;--viewer-side-muted:#607089;--viewer-side-border:rgba(15,23,42,.10);--viewer-side-input:#ffffff;--viewer-side-soft:#f6f8fb;--viewer-side-shadow:0 18px 45px rgba(15,23,42,.08);}
body.dark,body.dark-mode,body[data-theme="dark"],body.theme-dark{--viewer-side-bg:#10263b;--viewer-side-card:#132d46;--viewer-side-text:#ffffff;--viewer-side-muted:#c7d3e0;--viewer-side-border:rgba(255,255,255,.10);--viewer-side-input:#0b2034;--viewer-side-soft:#0f2236;--viewer-side-shadow:0 18px 45px rgba(0,0,0,.24);}
.media-viewer-image-side{background:var(--viewer-side-bg)!important;color:var(--viewer-side-text)!important;border-left:1px solid var(--viewer-side-border)!important;}
.viewer-comment-bubble{background:var(--viewer-side-card)!important;color:var(--viewer-side-text)!important;border:1px solid var(--viewer-side-border);}
.viewer-reply-bubble{background:var(--viewer-side-soft)!important;color:var(--viewer-side-text)!important;border:1px solid var(--viewer-side-border);}
.viewer-comment-form-wrap.viewer-compact-wrap{padding:12px 14px!important;background:var(--viewer-side-bg)!important;border-top:1px solid var(--viewer-side-border)!important;}
.viewer-comment-mini{width:100%;min-height:48px;border-radius:999px;border:1px solid var(--viewer-side-border);background:var(--viewer-side-input);color:var(--viewer-side-muted);display:flex;align-items:center;padding:0 18px;font-size:15px;cursor:pointer;box-sizing:border-box;transition:.2s ease;}
.viewer-comment-mini:hover{border-color:rgba(238,88,40,.30);box-shadow:0 0 0 4px rgba(238,88,40,.08);}
.viewer-comment-expanded{display:none;background:var(--viewer-side-card);border:1px solid var(--viewer-side-border);border-radius:22px;padding:14px;margin-top:10px;box-shadow:var(--viewer-side-shadow);}
.viewer-comment-expanded.show{display:block;}
.viewer-comment-input{width:100%;min-height:82px;max-height:120px;border-radius:18px;border:1px solid var(--viewer-side-border);background:var(--viewer-side-input);color:var(--viewer-side-text);padding:14px 16px;font:inherit;font-size:15px;line-height:1.45;resize:vertical;outline:none;box-sizing:border-box;transition:.2s ease;}
.viewer-comment-input::placeholder{color:var(--viewer-side-muted);}
.viewer-comment-input:focus{border-color:rgba(238,88,40,.35);box-shadow:0 0 0 4px rgba(238,88,40,.10);}
.viewer-emoji-input{width:100%;height:46px;border-radius:999px;border:1px solid var(--viewer-side-border);background:var(--viewer-side-input);color:var(--viewer-side-text);padding:0 16px;margin-top:10px;font-size:20px;outline:none;box-sizing:border-box;}
.viewer-emoji-input::placeholder{color:var(--viewer-side-muted);font-size:15px;}
.viewer-comment-actions{display:flex;align-items:center;gap:10px;margin-top:12px;flex-wrap:wrap;}
.viewer-square-btn{width:44px;height:44px;border:none;border-radius:14px;background:var(--viewer-side-soft);color:var(--viewer-side-text);display:inline-flex;align-items:center;justify-content:center;font-size:21px;cursor:pointer;box-shadow:0 8px 18px rgba(15,23,42,.06);transition:.2s ease;}
.viewer-square-btn:hover{transform:translateY(-2px);}
.viewer-publish-btn{height:44px;padding:0 24px;border:none;border-radius:999px;background:linear-gradient(135deg,#EE5828 0%,#b43d18 100%);color:#ffffff;font:inherit;font-weight:900;cursor:pointer;box-shadow:0 12px 24px rgba(238,88,40,.25);}
.viewer-cancel-comment-btn{height:44px;padding:0 18px;border-radius:999px;border:1px solid var(--viewer-side-border);background:transparent;color:var(--viewer-side-muted);font:inherit;font-weight:800;cursor:pointer;}
.comment-image-preview{display:none;margin-top:12px;border-radius:16px;overflow:hidden;border:1px solid var(--viewer-side-border);background:var(--viewer-side-soft);}
.comment-image-preview.show{display:block;}
.comment-image-preview img{width:100%;max-height:160px;object-fit:contain;display:block;}
#err-viewerCommentContent{display:block;margin-top:7px;color:#ef4444;font-size:12px;font-weight:700;}
.viewer-comment-head-row{display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:6px;}
.viewer-comment-menu-wrap{position:relative;flex-shrink:0;}
.viewer-comment-menu-btn{width:30px;height:30px;border:none;border-radius:50%;background:transparent;color:var(--viewer-side-muted,#607089);cursor:pointer;font-size:18px;font-weight:900;display:flex;align-items:center;justify-content:center;}
.viewer-comment-menu-btn:hover{background:var(--viewer-side-soft,#f6f8fb);color:var(--viewer-side-text,#142738);}
.viewer-comment-dropdown{position:absolute;top:34px;right:0;min-width:170px;background:#ffffff;border:1px solid rgba(15,23,42,.08);border-radius:16px;box-shadow:0 18px 38px rgba(15,23,42,.16);padding:8px;display:none;z-index:99999;}
.viewer-comment-dropdown.show{display:block;}
.viewer-comment-dropdown button{width:100%;border:none;background:transparent;color:#17283f;padding:10px 12px;border-radius:10px;text-align:left;cursor:pointer;font:inherit;font-size:14px;font-weight:700;}
.viewer-comment-dropdown button:hover{background:#f5f7fb;}
.viewer-comment-dropdown button.danger{color:#dc2626;}
.viewer-comment-dropdown button.danger:hover{background:#fff5f5;}
body.dark .viewer-comment-dropdown,body.dark-mode .viewer-comment-dropdown,body[data-theme="dark"] .viewer-comment-dropdown,body.theme-dark .viewer-comment-dropdown{background:#132d46;border-color:rgba(255,255,255,.10);}
body.dark .viewer-comment-dropdown button,body.dark-mode .viewer-comment-dropdown button,body[data-theme="dark"] .viewer-comment-dropdown button,body.theme-dark .viewer-comment-dropdown button{color:#ffffff;}
body.dark .viewer-comment-dropdown button:hover,body.dark-mode .viewer-comment-dropdown button:hover,body[data-theme="dark"] .viewer-comment-dropdown button:hover,body.theme-dark .viewer-comment-dropdown button:hover{background:rgba(255,255,255,.08);}
body.dark .viewer-comment-dropdown button.danger,body.dark-mode .viewer-comment-dropdown button.danger,body[data-theme="dark"] .viewer-comment-dropdown button.danger,body.theme-dark .viewer-comment-dropdown button.danger{color:#ff8f81;}
.inline-reply-holder{margin-top:10px;}
.inline-reply-holder .reply-box{margin-top:10px;margin-left:0;}
.reply-item .inline-reply-holder{margin-left:0;width:100%;}
.ig-lang-zone{width:100%;max-width:1380px;margin:0 auto;padding:0 36px 38px;box-sizing:border-box;display:none;justify-content:flex-end;align-items:center;color:#737373;font-size:14px;position:relative;z-index:9999;}
.ig-lang-zone.ig-lang-ready{display:flex;}
.ig-lang-wrap{position:relative;display:inline-flex;align-items:center;}
.ig-lang-btn{border:none;background:transparent;color:#737373;font:inherit;cursor:pointer;padding:0;display:inline-flex;align-items:center;gap:4px;}
.ig-lang-btn:hover{text-decoration:underline;}
.ig-lang-menu{position:absolute;right:0;left:auto;bottom:24px;width:210px;max-height:260px;overflow-y:auto;display:none;background:#ffffff;border:1px solid rgba(0,0,0,.12);border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.14);padding:6px 0;z-index:999999;}
.ig-lang-menu.show{display:block;}
.ig-lang-menu button{width:100%;border:none;background:transparent;color:#262626;font:inherit;text-align:left;cursor:pointer;padding:10px 14px;}
.ig-lang-menu button:hover{background:#f5f5f5;}
#google_translate_element{display:none!important;height:0!important;overflow:hidden!important;}
.goog-te-banner-frame,.goog-te-balloon-frame,iframe.goog-te-banner-frame,iframe.skiptranslate,#goog-gt-tt,.goog-logo-link,.goog-te-gadget-icon,.goog-te-gadget span{display:none!important;visibility:hidden!important;height:0!important;}
.goog-te-gadget{height:0!important;overflow:hidden!important;font-size:0!important;line-height:0!important;}
html{margin-top:0!important;}
body{top:0!important;position:static!important;}
body>.skiptranslate{display:none!important;visibility:hidden!important;height:0!important;}
body.dark .ig-lang-menu,body.dark-mode .ig-lang-menu,body[data-theme="dark"] .ig-lang-menu,body.theme-dark .ig-lang-menu{background:#132d46;border-color:rgba(255,255,255,.12);}
body.dark .ig-lang-menu button,body.dark-mode .ig-lang-menu button,body[data-theme="dark"] .ig-lang-menu button,body.theme-dark .ig-lang-menu button{color:#ffffff;}
body.dark .ig-lang-menu button:hover,body.dark-mode .ig-lang-menu button:hover,body[data-theme="dark"] .ig-lang-menu button:hover,body.theme-dark .ig-lang-menu button:hover{background:rgba(255,255,255,.08);}
.forum-modal,#reportModal .forum-post-modal-box,#reportCommentModal>div{background:var(--forum-bg-card)!important;color:var(--forum-text)!important;border:1px solid var(--forum-border)!important;box-shadow:var(--forum-shadow)!important;}
.forum-modal-head,.forum-modal-body{background:var(--forum-bg-card)!important;color:var(--forum-text)!important;border-color:var(--forum-border)!important;}
.forum-modal-title,.forum-modal-name{color:var(--forum-text)!important;}
.forum-modal-close{background:var(--forum-bg-soft)!important;color:var(--forum-text)!important;border:1px solid var(--forum-border)!important;}
.forum-form-field input[type="text"],.forum-form-field input[type="file"],.forum-form-field select,.forum-form-field textarea,.forum-emoji-input,.comment-emoji-input,#reportModal select,#reportModal textarea,#reportCommentModal select,#reportCommentModal textarea{background:var(--forum-bg-input)!important;color:var(--forum-text)!important;border:1px solid var(--forum-border)!important;}
.forum-form-field input::placeholder,.forum-form-field textarea::placeholder,.forum-emoji-input::placeholder,.comment-emoji-input::placeholder,#reportModal textarea::placeholder,#reportCommentModal textarea::placeholder{color:var(--forum-text-soft)!important;}
.forum-form-field select option,#reportModal select option,#reportCommentModal select option{background:var(--forum-bg-card)!important;color:var(--forum-text)!important;}
.forum-modal-tools{background:var(--forum-bg-card)!important;color:var(--forum-text)!important;border-color:var(--forum-border)!important;}
.tool-trigger,.composer-icon-btn{color:var(--forum-text)!important;}
.post-menu-btn{background:var(--forum-bg-input)!important;color:var(--forum-text)!important;border:1px solid var(--forum-border)!important;box-shadow:var(--forum-shadow)!important;}
.post-menu-btn:hover{background:var(--forum-bg-soft)!important;}
.post-dropdown,.comment-dropdown,.viewer-comment-dropdown,.emoji-picker,.emoji-picker-head,.emoji-picker-tabs{background:var(--forum-bg-card)!important;color:var(--forum-text)!important;border:1px solid var(--forum-border)!important;box-shadow:var(--forum-shadow)!important;}
.post-dropdown a,.post-dropdown button,.comment-dropdown button,.viewer-comment-dropdown button,.emoji-btn,.emoji-tab{background:transparent!important;color:var(--forum-text)!important;}
.post-dropdown a:hover,.post-dropdown button:hover,.comment-dropdown button:hover,.viewer-comment-dropdown button:hover,.emoji-btn:hover,.emoji-tab:hover{background:var(--forum-bg-soft)!important;color:var(--forum-text)!important;}
.comment-dropdown button.danger,.viewer-comment-dropdown button.danger{color:#ff6b5a!important;}
.comment-dropdown button.danger:hover,.viewer-comment-dropdown button.danger:hover{background:rgba(238,88,40,.12)!important;}
.emoji-tab.active{background:var(--forum-btn-bg)!important;color:#ffffff!important;}
#reportModal .ghost-btn,#reportCommentModal button[type="button"]{background:var(--forum-bg-soft)!important;color:var(--forum-text)!important;border:1px solid var(--forum-border)!important;}
body.dark #reportModal,body.dark-mode #reportModal,body[data-theme="dark"] #reportModal,body.theme-dark #reportModal,body.dark #reportCommentModal,body.dark-mode #reportCommentModal,body[data-theme="dark"] #reportCommentModal,body.theme-dark #reportCommentModal,body.dark .modal-overlay,body.dark-mode .modal-overlay,body[data-theme="dark"] .modal-overlay,body.theme-dark .modal-overlay{background:rgba(0,0,0,.62)!important;}


/* ---- GIF BUTTON STYLE ---- */
.gif-media-btn{
    width:48px;
    height:48px;
    border:none;
    background:transparent;
    border-radius:14px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    cursor:pointer;
    transition:.2s ease;
    padding:0;
}
.gif-media-btn:hover{
    background:rgba(0,0,0,.06);
    transform:translateY(-2px);
}
body.dark .gif-media-btn:hover,
body.dark-mode .gif-media-btn:hover,
body[data-theme="dark"] .gif-media-btn:hover,
body.theme-dark .gif-media-btn:hover{
    background:rgba(255,255,255,.08);
}
.gif-text{
    font-size:22px;
    font-weight:900;
    letter-spacing:1px;
    line-height:1;
    background:linear-gradient(135deg,#111827,#2563eb,#ec4899,#f97316);
    -webkit-background-clip:text;
    background-clip:text;
    -webkit-text-fill-color:transparent;
}
body.dark .gif-text,
body.dark-mode .gif-text,
body[data-theme="dark"] .gif-text,
body.theme-dark .gif-text{
    background:linear-gradient(135deg,#ffffff,#60a5fa,#f472b6,#fb923c);
    -webkit-background-clip:text;
    background-clip:text;
    -webkit-text-fill-color:transparent;
}

/* ---- GIF PICKER MODAL ---- */
.gif-modal{position:fixed;inset:0;background:rgba(15,23,42,.55);display:none;align-items:center;justify-content:center;z-index:1000001;padding:18px;}
.gif-modal.show{display:flex;}
.gif-box{width:min(720px,100%);max-height:86vh;background:var(--forum-bg-card);color:var(--forum-text);border:1px solid var(--forum-border);border-radius:24px;box-shadow:0 30px 80px rgba(0,0,0,.35);overflow:hidden;display:flex;flex-direction:column;}
.gif-header{padding:14px;display:flex;gap:10px;border-bottom:1px solid var(--forum-border);align-items:center;}
.gif-header input{flex:1;height:48px;border-radius:999px;border:1px solid var(--forum-border);background:var(--forum-bg-input);color:var(--forum-text);padding:0 16px;outline:none;font:inherit;}
.gif-header input:focus{border-color:rgba(238,88,40,.35);box-shadow:0 0 0 4px rgba(238,88,40,.10);}
.gif-header button{width:44px;height:44px;border:none;border-radius:50%;background:var(--forum-bg-soft);color:var(--forum-text);font-size:20px;cursor:pointer;}
.gif-results{padding:14px;display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;overflow-y:auto;max-height:62vh;}
.gif-results img{width:100%;height:150px;object-fit:cover;border-radius:16px;background:#000;cursor:pointer;transition:.2s ease;}
.gif-results img:hover{transform:translateY(-2px) scale(1.02);box-shadow:0 12px 24px rgba(238,88,40,.22);}
.gif-empty{padding:22px;text-align:center;color:var(--forum-text-soft);font-weight:800;grid-column:1/-1;}
@media(max-width:700px){.gif-results{grid-template-columns:repeat(2,minmax(0,1fr));}.gif-results img{height:135px;}}

/* ---- ADVANCED SHARE MODAL ---- */
.advanced-share-modal{position:fixed;inset:0;display:none;align-items:center;justify-content:center;padding:18px;background:rgba(0,0,0,.62);z-index:1000000;}
.advanced-share-modal.show{display:flex;}
.advanced-share-box{width:min(620px,100%);max-height:92vh;overflow:hidden;display:flex;flex-direction:column;background:var(--forum-bg-card);color:var(--forum-text);border:1px solid var(--forum-border);border-radius:22px;box-shadow:0 30px 80px rgba(0,0,0,.38);}
.advanced-share-head{min-height:64px;display:flex;align-items:center;justify-content:center;position:relative;border-bottom:1px solid var(--forum-border);padding:0 58px;}
.advanced-share-head h2{margin:0;font-size:24px;color:var(--forum-text);font-weight:900;}
.advanced-share-close{position:absolute;right:16px;top:50%;transform:translateY(-50%);width:42px;height:42px;border:none;border-radius:50%;background:var(--forum-bg-input);color:var(--forum-text);font-size:30px;line-height:1;cursor:pointer;}
.advanced-share-body{overflow-y:auto;padding:18px 20px 22px;}
.advanced-share-user-row{display:flex;align-items:center;gap:12px;margin-bottom:14px;}
.advanced-share-user-row strong{color:var(--forum-text);font-weight:900;}
.advanced-share-caption{width:100%;min-height:76px;border:none;outline:none;resize:none;background:transparent;color:var(--forum-text);font:inherit;font-size:18px;padding:8px 0 12px;}
.advanced-share-caption::placeholder{color:var(--forum-text-soft);}
.advanced-share-preview{border:1px solid var(--forum-border);border-radius:16px;overflow:hidden;background:var(--forum-bg-input);margin-bottom:18px;}
.advanced-share-preview-media{min-height:160px;background:#000;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;text-align:center;font-size:18px;}
.advanced-share-preview-media img{width:100%;max-height:240px;object-fit:cover;display:block;}
.advanced-share-preview-media video{width:100%;max-height:240px;object-fit:cover;display:block;background:#000;}
.advanced-share-preview-media .thumb-wrapper{position:relative;width:100%;max-height:240px;overflow:hidden;background:#000;}
.advanced-share-preview-media .thumb-wrapper img{width:100%;max-height:240px;object-fit:cover;display:block;opacity:.85;}
.advanced-share-preview-media .thumb-play{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-size:52px;pointer-events:none;}
.advanced-share-preview-info{padding:12px 14px;}
.advanced-share-preview-site{color:var(--forum-text-soft);font-size:12px;text-transform:uppercase;margin-bottom:5px;}
.advanced-share-preview-title{color:var(--forum-text);font-weight:900;font-size:17px;line-height:1.35;}
.advanced-share-preview-desc{color:var(--forum-text-soft);margin-top:5px;font-size:14px;line-height:1.4;}
.advanced-share-primary{width:100%;min-height:52px;border:none;border-radius:14px;background:var(--forum-btn-bg);color:#fff;font:inherit;font-weight:900;cursor:pointer;margin-bottom:18px;box-shadow:0 14px 28px rgba(238,88,40,.24);}
.advanced-share-label{margin:0 0 14px;color:var(--forum-text);font-weight:900;font-size:18px;}
.advanced-share-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;}
.advanced-share-option{border:none;background:transparent;color:var(--forum-text);cursor:pointer;font:inherit;display:flex;flex-direction:column;align-items:center;gap:8px;text-align:center;font-size:13px;min-width:0;}
.advanced-share-icon{width:58px;height:58px;border-radius:50%;display:flex;align-items:center;justify-content:center;background:var(--forum-bg-input);border:1px solid var(--forum-border);font-size:24px;transition:.2s ease;}
.advanced-share-option:hover .advanced-share-icon{transform:translateY(-2px);box-shadow:0 10px 22px rgba(238,88,40,.16);}
.advanced-share-option span:last-child{font-weight:700;}
.advanced-share-note{margin:12px 0 0;color:var(--forum-text-soft);font-size:13px;line-height:1.4;}
.advanced-share-toast{position:fixed;left:50%;bottom:28px;transform:translateX(-50%) translateY(20px);min-width:180px;max-width:calc(100vw - 40px);padding:13px 18px;border-radius:999px;background:var(--forum-btn-bg);color:#fff;font-weight:900;text-align:center;box-shadow:0 18px 40px rgba(238,88,40,.32);opacity:0;pointer-events:none;z-index:1000002;transition:.25s ease;}
.advanced-share-toast.show{opacity:1;transform:translateX(-50%) translateY(0);}
#reportModal{background:rgba(0,0,0,.58)!important;padding:20px!important;}
#reportModal .forum-post-modal-box{width:min(600px,100%)!important;background:var(--forum-bg-card)!important;color:var(--forum-text)!important;border:1px solid var(--forum-border)!important;border-radius:28px!important;padding:28px!important;}
#reportModal h2,#reportModal label{color:var(--forum-text)!important;}
#reportModal select,#reportModal textarea{background:var(--forum-bg-input)!important;color:var(--forum-text)!important;border:1px solid var(--forum-border)!important;}
#reportModal .ghost-btn{color:var(--forum-text)!important;border:1px solid var(--forum-border)!important;background:transparent!important;}

.advanced-share-grid.share-grid-3{grid-template-columns:repeat(3,1fr);}
.advanced-share-emoji-row{display:grid;grid-template-columns:minmax(0,1fr) 50px;gap:10px;align-items:center;margin-bottom:14px;}
.advanced-share-emoji-btn{width:50px;height:46px;border:none;border-radius:14px;background:var(--forum-btn-bg);color:#fff;font-size:22px;cursor:pointer;box-shadow:0 10px 22px rgba(238,88,40,.16);}
.advanced-share-emoji-btn:hover{filter:brightness(1.05);transform:translateY(-1px);}
@media(max-width:1450px){.forum-main-layout{max-width:1240px;grid-template-columns:minmax(0,1fr) 320px;}}
@media(max-width:1300px){.forum-main-layout{grid-template-columns:1fr;}.forum-right-panel{position:static;}}
@media(max-width:1100px){.media-viewer-image-layout{grid-template-columns:1fr;}.media-viewer-image-side{display:flex!important;height:auto;min-height:100vh;}.media-viewer-image-main{min-height:55vh;}}
@media(max-width:980px){.forum-search-main,.forum-bottom-actions,.forum-form-grid,.forum-reactions-bar{grid-template-columns:1fr;}.forum-form-grid .full-width{grid-column:auto;}.forum-sort-wrap{justify-content:flex-start;}.media-viewer-video-layout{grid-template-columns:1fr;}.media-viewer-video-side{display:none;}.post-card{padding:18px;}}
@media(max-width:700px){.advanced-share-grid{grid-template-columns:repeat(3,1fr);}.ig-lang-zone{justify-content:flex-start;padding:0 22px 32px;}.ig-lang-menu{left:0;right:auto;}}

/* ===== Correctifs front media viewer / logique session ===== */
.viewer-user-meta strong,.viewer-post-title,.viewer-post-body,.viewer-stats,.viewer-comment-author,.viewer-comment-meta{color:var(--viewer-side-text)!important;}
.viewer-user-meta span,.viewer-comment-meta,.viewer-reply-btn{color:var(--viewer-side-muted)!important;}
.media-viewer-image-side *{box-sizing:border-box;}
.viewer-post-head,.viewer-post-body,.viewer-stats,.viewer-actions{border-color:var(--viewer-side-border)!important;}
.viewer-action-btn{color:#fff!important;}
.reel-action-btn{border:0;background:transparent;cursor:pointer;font:inherit;padding:0;}
.reel-action-icon{border:0;cursor:pointer;color:#fff;}
.video-more-wrap{position:relative;}
.video-more-dropdown{position:absolute;right:74px;bottom:0;min-width:180px;background:#fff;border:1px solid rgba(15,23,42,.10);border-radius:16px;box-shadow:0 20px 45px rgba(0,0,0,.25);padding:8px;display:none;z-index:120;}
.video-more-dropdown.show{display:block;}
.video-more-dropdown a,.video-more-dropdown button{width:100%;display:block;text-align:left;border:0;background:transparent;color:#17283f;text-decoration:none;padding:10px 12px;border-radius:10px;font:inherit;font-weight:800;cursor:pointer;}
.video-more-dropdown a:hover,.video-more-dropdown button:hover{background:#f5f7fb;}
.video-more-dropdown button.danger{color:#dc2626;}
.media-viewer-video-panel{position:absolute;right:122px;top:50%;transform:translateY(-50%);width:min(420px,calc(100vw - 160px));max-height:82vh;background:var(--viewer-side-bg);color:var(--viewer-side-text);border:1px solid var(--viewer-side-border);border-radius:26px;box-shadow:0 28px 80px rgba(0,0,0,.35);display:none;z-index:80;overflow:hidden;}
.media-viewer-video-panel.show{display:flex;flex-direction:column;}
.video-panel-head{display:flex;justify-content:space-between;align-items:center;padding:16px 18px;border-bottom:1px solid var(--viewer-side-border);font-size:20px;color:var(--viewer-side-text);}
.video-panel-head button{width:36px;height:36px;border:0;border-radius:50%;background:var(--viewer-side-soft);color:var(--viewer-side-text);font-size:22px;cursor:pointer;}
.video-panel-comments{padding:16px;overflow-y:auto;display:flex;flex-direction:column;gap:12px;min-height:160px;max-height:42vh;}
.video-comment-form{padding:14px;border-top:1px solid var(--viewer-side-border);background:var(--viewer-side-bg);}
.video-comment-form .viewer-comment-input{min-height:70px;}
body.dark .video-more-dropdown,body.dark-mode .video-more-dropdown,body[data-theme="dark"] .video-more-dropdown,body.theme-dark .video-more-dropdown{background:#132d46;border-color:rgba(255,255,255,.12);}
body.dark .video-more-dropdown a,body.dark .video-more-dropdown button,body.dark-mode .video-more-dropdown a,body.dark-mode .video-more-dropdown button,body[data-theme="dark"] .video-more-dropdown a,body[data-theme="dark"] .video-more-dropdown button,body.theme-dark .video-more-dropdown a,body.theme-dark .video-more-dropdown button{color:#fff;}
body.dark .video-more-dropdown a:hover,body.dark .video-more-dropdown button:hover,body.dark-mode .video-more-dropdown a:hover,body.dark-mode .video-more-dropdown button:hover,body[data-theme="dark"] .video-more-dropdown a:hover,body[data-theme="dark"] .video-more-dropdown button:hover,body.theme-dark .video-more-dropdown a:hover,body.theme-dark .video-more-dropdown button:hover{background:rgba(255,255,255,.08);}
@media(max-width:900px){.media-viewer-image-layout{grid-template-columns:1fr;}.media-viewer-image-side{position:absolute;right:0;top:0;width:min(430px,92vw);background:var(--viewer-side-bg);}.media-viewer-video-panel{right:16px;width:calc(100vw - 32px);}.media-viewer-video-layout{grid-template-columns:1fr 88px;}.media-viewer-video-main{padding-left:20px;}}

</style>

<div class="forum-page">
    <section class="page-hero reveal forum-hero-classic">
        <span class="section-badge">Forum social</span>
        <h1 class="page-title">Forum & échanges</h1>
        <p class="page-intro">Publiez, partagez des images ou vidéos, commentez, aimez et suivez les discussions dans une interface moderne inspirée des réseaux sociaux.</p>
    </section>

    <section class="action-bar reveal forum-action-bar">
        <form method="GET" action="<?php echo e(forumAppUrl('view/front/index.php')); ?>" class="forum-search-layout">
            <input type="hidden" name="page" value="forum">
            <?php if($mine): ?><input type="hidden" name="mine" value="1"><?php endif; ?>
            <div class="forum-search-main">
                <div class="search-box forum-search-box">
                    <input type="text" name="search" placeholder="Rechercher un post..." value="<?php echo e($search); ?>">
                </div>
                <div class="forum-top-actions">
                    <button class="solid-btn" type="submit">Rechercher</button>
                    <?php if($mine): ?>
                        <a class="solid-btn" href="<?php echo e(forumUrl(['filter'=>$filter,'search'=>$search,'sort'=>$sort])); ?>">Tous les posts</a>
                    <?php else: ?>
                        <a class="solid-btn" href="<?php echo e(forumUrl(['mine'=>1,'filter'=>$filter,'search'=>$search,'sort'=>$sort])); ?>">Mes posts</a>
                    <?php endif; ?>
                    <a class="solid-btn" href="<?php echo e(forumAppUrl('view/front/index.php?page=savedPosts')); ?>">Posts enregistrés</a>
                </div>
            </div>
            <div class="forum-bottom-actions">
                <div class="forum-filter-row">
                    <?php foreach(['Tous','Question','Conseil','Discussion'] as $f): $active=($filter===$f)||($filter==='Tous'&&$f==='Tous'); $link=forumUrl(['mine'=>$mine?1:null,'filter'=>$f,'search'=>$search,'sort'=>$sort]); ?>
                    <a href="<?php echo e($link); ?>" class="forum-filter-btn <?php echo $active?'active':''; ?>"><?php echo $f==='Tous'?'Tous':$f.'s'; ?></a>
                    <?php endforeach; ?>
                </div>
                <div class="forum-sort-wrap">
                    <select name="sort" class="forum-sort-select" onchange="this.form.submit()">
                        <option value="recent" <?php echo $sort==='recent'?'selected':''; ?>>Plus récents</option>
                        <option value="liked" <?php echo $sort==='liked'?'selected':''; ?>>Plus aimés</option>
                        <option value="commented" <?php echo $sort==='commented'?'selected':''; ?>>Plus commentés</option>
                    </select>
                </div>
            </div>
        </form>
    </section>

    <?php if(isset($_GET['published'])): ?><div class="success-message" style="max-width:1380px;margin:0 auto;width:100%;">Votre post a été envoyé pour révision par l'administrateur.</div><?php endif; ?>
    <?php if(isset($_GET['updated'])): ?><div class="success-message" style="max-width:1380px;margin:0 auto;width:100%;">Post mis à jour avec succès.</div><?php endif; ?>
    <?php if(isset($_GET['deleted'])): ?><div class="success-message" style="max-width:1380px;margin:0 auto;width:100%;">Post supprimé avec succès.</div><?php endif; ?>
    <?php if(isset($_GET['reported'])): ?><div class="success-message" style="max-width:1380px;margin:0 auto;width:100%;">Post signalé avec succès.</div><?php endif; ?>
    <?php if(($_GET['error'] ?? '') === 'unauthorized'): ?><div class="success-message" style="max-width:1380px;margin:0 auto;width:100%;background:#fff3f3;color:#a33;border-color:#ffd0d0;">Action refusée : vous pouvez modifier ou supprimer seulement vos propres publications.</div><?php endif; ?>
    <?php if(isset($_GET['comment_deleted'])): ?><div class="success-message" style="max-width:1380px;margin:0 auto;width:100%;">Commentaire supprimé avec succès.</div><?php endif; ?>
    <?php if(isset($_GET['comment_updated'])): ?><div class="success-message" style="max-width:1380px;margin:0 auto;width:100%;">Commentaire modifié avec succès.</div><?php endif; ?>
    <?php if(isset($_GET['comment_reported'])): ?><div class="success-message" style="max-width:1380px;margin:0 auto;width:100%;">Commentaire signalé avec succès.</div><?php endif; ?>

    <section class="forum-main-layout">
        <div class="forum-feed">
            <article class="panel composer-card">
                <div class="composer-top">
                    <div class="mini-avatar"><?php echo e($currentUserAvatarLetter); ?></div>
                    <button type="button" class="composer-open-btn" id="openCreateModalBtn">Publier un post...</button>
                    <div class="composer-icons">
                        <button type="button" class="composer-icon-btn" id="openPhotoBtn">🖼️</button>
                        <button type="button" class="composer-icon-btn" id="openVideoBtn">🎥</button>
                        <button type="button" class="composer-icon-btn gif-media-btn" id="openGifBtn" title="Choisir un GIF ou un sticker"><span class="gif-text">GIF</span></button>
                        <button type="button" class="composer-icon-btn" id="openEmojiBtn">😊</button>
                    </div>
                </div>
            </article>

            <div class="forum-posts-list">
                <?php if(empty($posts)): ?>
                <article class="panel"><span class="section-badge">Aucun résultat</span><p style="margin-top:14px;">Aucun post trouvé.</p></article>
                <?php else: ?>
                <?php foreach($posts as $post):
                    $fullname=trim(($post['prenom']??'').' '.($post['nom']??'')); if($fullname==='') $fullname='Utilisateur';
                    $isOwner = ((int)($post['id_user']??0) === $currentUserId);
                    $avatarLetter=strtoupper(substr($fullname,0,1));
                    $imageUrl=!empty($post['image'])?forumAppUrl($post['image']):'';
                    $videoUrl=!empty($post['video'])?forumAppUrl($post['video']):'';
                    $gifUrl=!empty($post['gif_post'])?forumMediaUrl($post['gif_post']):'';
                    $type=$post['type_post']??'Discussion';
                    $videoEmbedData=forumVideoEmbedData($post['contenu']??'');
                    $cleanPostContent=$videoEmbedData['clean_text'];
                    $sharedOriginalId=(int)($post['shared_original_post_id']??0);
                    $sharedOriginal=$sharedOriginalId>0?forumGetPostWithUserById($sharedOriginalId):null;
                    $likeCount=(int)($post['likes_count']??$post['nb_likes']??0);
                    $commentCount=(int)($post['comments_count']??$post['nb_comments']??0);
                    $shareCount=(int)($post['shares_count']??$post['nb_shares']??0);
                    $saveCount=(int)($post['saves_count']??$post['nb_saves']??0);
                    $viewerPayload=['id'=>(int)($post['id_post']??0),'type'=>!empty($videoUrl)?'video':'image','src'=>!empty($videoUrl)?$videoUrl:$imageUrl,'title'=>$post['titre']??'','content'=>$post['contenu']??'','user'=>$fullname,'time'=>timeAgo($post['date_publication']??''),'date'=>dateOnly($post['date_publication']??''),'image'=>$imageUrl,'video'=>$videoUrl,'likes'=>$likeCount,'comments'=>$commentCount,'shares'=>$shareCount,'saves'=>(int)($saveCount??0),'is_liked'=>!empty($post['is_liked']),'is_saved'=>!empty($post['is_saved']),'is_owner'=>$isOwner,'current_user_id'=>$currentUserId,'comments_data'=>$post['comments']??[]];
                ?>
                <article class="post-card" id="post-<?php echo (int)$post['id_post']; ?>">
                    <div class="post-header-row">
                        <div class="post-user">
                            <div class="mini-avatar"><?php echo e($avatarLetter); ?></div>
                            <div>
                                <strong><?php echo e($fullname); ?></strong>
                                <div class="post-meta-line">
                                    <span class="post-type-badge <?php echo typeBadgeClass($type); ?>"><?php echo e($type); ?></span>
                                    <span class="post-date-exact"><?php echo e(dateOnly($post['date_publication']??'')); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="post-menu-wrap">
                            <span class="post-time-top"><?php echo e(timeAgo($post['date_publication']??'')); ?></span>
                            <button class="post-menu-btn" type="button" onclick="togglePostMenu(<?php echo (int)$post['id_post']; ?>)">⋯</button>
                            <div class="post-dropdown" id="post-menu-<?php echo (int)$post['id_post']; ?>">
                                <?php if($isOwner): ?>
                                    <a href="<?php echo e(forumUrl(['edit'=>(int)$post['id_post']])); ?>" onclick="localStorage.setItem('openForumModal','1')">✏️ Modifier</a>
                                    <form method="POST" action="" onsubmit="return confirm('Supprimer ce post ?');">
                                        <input type="hidden" name="post_id" value="<?php echo (int)$post['id_post']; ?>">
                                        <button type="submit" name="delete_post" style="width:100%;text-align:left;display:block;padding:10px;">🗑 Supprimer</button>
                                    </form>
                                <?php else: ?>
                                    <button type="button" onclick="openReportModal(<?php echo (int)$post['id_post']; ?>)">🚩 Signaler</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <h3 class="post-title"><?php echo e($post['titre']??''); ?></h3>
                    <div class="sentiment-zone">
<?php if (($post['sentiment_post'] ?? '') === 'positif'): ?>
            <span class="sentiment-badge positive">🟢 Positif</span>
<?php elseif (($post['sentiment_post'] ?? '') === 'negatif'): ?>
            <span class="sentiment-badge negative">🔴 Négatif</span>
    <?php else: ?>
        <span class="sentiment-badge neutral">⚪ Neutre</span>
    <?php endif; ?>

    <?php if (($post['toxicite_post'] ?? 0) == 1): ?>
        <span class="sentiment-badge toxic">⚠️ Toxique</span>
    <?php endif; ?>
</div>
                    <?php if(trim($cleanPostContent)!==''): ?><p class="post-content"><?php echo nl2br(e($cleanPostContent)); ?></p><?php endif; ?>
                    <?php if(!empty($post['emoji_post'])): ?><div class="comment-emoji-line" style="margin-top:10px;"><?php echo e($post['emoji_post']); ?></div><?php endif; ?>
                    <?php if(!empty($gifUrl)): ?>
                    <div class="post-gif-frame">
                        <img src="<?php echo e($gifUrl); ?>" alt="GIF post" loading="lazy" onerror="this.closest('.post-gif-frame').style.display='none';">
                        <div class="post-media-overlay">GIF</div>
                    </div>
                    <?php endif; ?>
                    <?php if(!empty($sharedOriginal)): ?><?php echo forumRenderSharedOriginalBox($sharedOriginal); ?><?php endif; ?>

                    <?php if(empty($sharedOriginal)&&!empty($videoEmbedData['embed'])): ?>
                        <?php if($videoEmbedData['type']==='iframe'): $pc=preg_replace('/[^a-z0-9]/','',strtolower($videoEmbedData['provider']??'video')); ?>
                        <div class="post-media-frame post-url-video-frame post-provider-<?php echo e($pc); ?>">
                            <iframe src="<?php echo e($videoEmbedData['embed']); ?>" title="Vidéo <?php echo e($videoEmbedData['provider']??''); ?>" allow="accelerometer;autoplay;clipboard-write;encrypted-media;gyroscope;picture-in-picture;web-share" allowfullscreen scrolling="no" loading="lazy"></iframe>
                            <div class="post-media-overlay"><?php echo e($videoEmbedData['provider']??'Vidéo'); ?></div>
                        </div>
                        <?php elseif($videoEmbedData['type']==='video'): ?>
                        <div class="post-media-frame"><video controls playsinline preload="metadata"><source src="<?php echo e($videoEmbedData['embed']); ?>"></video><div class="post-media-overlay">Vidéo</div></div>
                        <?php endif; ?>
                    <?php elseif(empty($sharedOriginal)&&($videoEmbedData['type']??'')==='link'&&!empty($videoEmbedData['url'])): ?>
                    <a href="<?php echo e($videoEmbedData['url']); ?>" target="_blank" class="post-link-card">🔗 Ouvrir le lien</a>
                    <?php endif; ?>

                    <?php if(empty($sharedOriginal)&&!empty($imageUrl)): ?>
                    <div class="post-media-frame" onclick='openMediaViewer(<?php echo json_encode($viewerPayload,JSON_HEX_APOS|JSON_HEX_QUOT); ?>)'>
                        <img src="<?php echo e($imageUrl); ?>" alt="Image post" onerror="this.style.display='none';">
                    </div>
                    <?php endif; ?>
                    <?php if(empty($sharedOriginal)&&!empty($videoUrl)): ?>
                    <div class="post-media-frame" onclick='openMediaViewer(<?php echo json_encode($viewerPayload,JSON_HEX_APOS|JSON_HEX_QUOT); ?>)'>
                        <video class="auto-feed-video" autoplay muted loop playsinline preload="metadata"><source src="<?php echo e($videoUrl); ?>"></video>
                        <div class="post-media-overlay">Vidéo</div>
                    </div>
                    <?php endif; ?>

                    <div class="forum-reactions-bar">
                        <form method="POST" action="">
                            <input type="hidden" name="toggle_like" value="1">
                            <input type="hidden" name="post_id" value="<?php echo (int)$post['id_post']; ?>">
                            <button type="submit" class="post-reaction-btn"><span class="reaction-label"><?php echo !empty($post['is_liked'])?'❤️ Aimer':'👍 Aimer'; ?></span><span class="reaction-count"><?php echo (int)($post['likes_count']??0); ?></span></button>
                        </form>
                        <button class="post-reaction-btn" type="button" onclick="toggleCommentBox(<?php echo (int)$post['id_post']; ?>)"><span class="reaction-label">💬 Commenter</span><span class="reaction-count"><?php echo (int)($post['comments_count']??count($post['comments']??[])); ?></span></button>
                        <button class="post-reaction-btn advanced-share-open" type="button" data-post-id="<?php echo (int)$post['id_post']; ?>" data-post-title="<?php echo e($post['titre']??'Post GoService'); ?>" data-post-content="<?php echo e(strip_tags($cleanPostContent??($post['contenu']??''))); ?>" data-post-user="<?php echo e($fullname); ?>" data-post-image="<?php echo e($imageUrl); ?>" data-post-video="<?php echo e($videoUrl); ?>" onclick="openAdvancedShareModalFromButton(this)"><span class="reaction-label">🔁 Partager</span><span class="reaction-count" id="share-count-<?php echo (int)$post['id_post']; ?>"><?php echo (int)($post['shares_count']??0); ?></span></button>
                        <form method="POST" action="">
                            <input type="hidden" name="toggle_save" value="1">
                            <input type="hidden" name="post_id" value="<?php echo (int)$post['id_post']; ?>">
                            <button type="submit" class="post-reaction-btn"><span class="reaction-label"><?php echo !empty($post['is_saved'])?'📌 Enregistré':'🔖 Enregistrer'; ?></span><span class="reaction-count"><?php echo (int)($post['saves_count']??0); ?></span></button>
                        </form>
                    </div>

                    <div class="comment-box <?php echo (isset($_GET['open_post'])&&(int)$_GET['open_post']===(int)$post['id_post'])?"show":"hidden"; ?>" id="comment-box-<?php echo (int)$post['id_post']; ?>">
                        <h4>Commentaires</h4>
                        <div class="panel comment-form-panel" style="margin-top:16px;">
                            <span class="section-badge">Ajouter un commentaire</span>
                            <form method="POST" action="" enctype="multipart/form-data" novalidate class="comment-form">
                                <input type="hidden" name="add_comment" value="1">
                                <input type="hidden" name="post_id" value="<?php echo (int)$post['id_post']; ?>">
                                <textarea name="comment_content" id="comment-content-<?php echo (int)$post['id_post']; ?>" class="comment-area comment-emoji-target" placeholder="Écrire un commentaire..." required></textarea>
                                <input type="text" name="emoji_content" id="emoji-hidden-<?php echo (int)$post['id_post']; ?>" value="" class="comment-emoji-input comment-emoji-target" placeholder="😊">
                                <span class="field-error" id="err-comment-content-<?php echo (int)$post['id_post']; ?>"></span>
                                <div class="comment-tools">
                                    <label class="comment-tool-btn" title="Ajouter une image">🖼️<input type="file" name="comment_image" accept=".jpg,.jpeg,.png,.webp,.gif" class="comment-hidden-input" id="comment-img-<?php echo (int)$post['id_post']; ?>"></label>
                                    <button type="button" class="comment-tool-btn comment-emoji-btn" data-target="emoji-hidden-<?php echo (int)$post['id_post']; ?>" title="Ajouter un emoji">😊</button>
                                    <button type="submit" name="add_comment" class="solid-btn">Publier</button>
                                </div>
                            </form>
                        </div>

                        <?php if(!empty($post['comments'])):
                            $rootComments=[]; $replyMap=[];
                            foreach(($post['comments']??[]) as $ci){ $pk=(int)($ci['id_parent_commentaire']??0); if($pk>0){ if(!isset($replyMap[$pk])) $replyMap[$pk]=[]; $replyMap[$pk][]=$ci; }else $rootComments[]=$ci; }
                        ?>
                        <div class="comments-list" style="margin-top:20px;">
                        <?php foreach($rootComments as $comment):
                            $commentId=isset($comment['id_commentaire'])?(int)$comment['id_commentaire']:0;
                            $commentAuthor=trim(($comment['prenom']??'').' '.($comment['nom']??'')); if($commentAuthor==='') $commentAuthor='Utilisateur';
                            $commentImageUrl=!empty($comment['image_commentaire'])?forumAppUrl($comment['image_commentaire']):'';
                            $commentReplies=$replyMap[$commentId]??[];
                            $isCommentOwner=((int)($comment['id_user']??0)===$currentUserId);
                        ?>
                        <div class="comment-thread" id="comment-thread-<?php echo $commentId; ?>">
                            <div class="comment-item comment-main-item">
                                <div class="comment-avatar-wrap"><div class="mini-avatar"><?php echo e(strtoupper(substr($commentAuthor,0,1))); ?></div></div>
                                <div class="comment-body">
                                    <div class="comment-bubble" id="comment-bubble-<?php echo $commentId; ?>">
                                        <div class="comment-header-row">
                                            <strong class="comment-author"><?php echo e($commentAuthor); ?></strong>
                                            <div class="comment-menu-wrap">
                                                <button type="button" class="comment-menu-btn" onclick="toggleCommentMenu('cmenu-<?php echo $commentId; ?>')">⋯</button>
                                                <div class="comment-dropdown" id="cmenu-<?php echo $commentId; ?>">
                                                    <?php if($isCommentOwner): ?>
                                                        <button type="button" onclick="startEditComment(<?php echo $commentId; ?>,<?php echo (int)$post['id_post']; ?>)">✏️ Modifier</button>
                                                        <button type="button" class="danger" onclick="deleteComment(<?php echo $commentId; ?>,<?php echo (int)$post['id_post']; ?>,0)">🗑 Supprimer</button>
                                                    <?php else: ?>
                                                        <button type="button" onclick="openReportCommentModal(<?php echo $commentId; ?>,<?php echo (int)$post['id_post']; ?>)">🚩 Signaler</button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="comment-text-<?php echo $commentId; ?>">
                                            <?php if(!empty($comment['contenu_commentaire'])): ?><div class="comment-text"><?php echo nl2br(e($comment['contenu_commentaire'])); ?></div><?php endif; ?>
                                            <?php if(!empty($comment['emoji_commentaire'])): ?><div class="comment-emoji-line"><?php echo e($comment['emoji_commentaire']); ?></div><?php endif; ?>
                                            <?php if(!empty($commentImageUrl)): ?><div class="comment-image-wrap"><img src="<?php echo e($commentImageUrl); ?>" alt="Image commentaire" class="comment-image"></div><?php endif; ?>
                                        </div>
                                        <form method="POST" action="" enctype="multipart/form-data" id="comment-edit-zone-<?php echo $commentId; ?>" style="display:none;" class="comment-edit-form">
                                            <input type="hidden" name="update_comment" value="1"><input type="hidden" name="comment_id" value="<?php echo $commentId; ?>"><input type="hidden" name="post_id" value="<?php echo (int)$post['id_post']; ?>">
                                            <textarea name="comment_content" class="comment-edit-area comment-emoji-target" id="comment-edit-input-<?php echo $commentId; ?>"><?php echo e($comment['contenu_commentaire']??''); ?></textarea>
                                            <input type="text" name="emoji_content" id="edit-emoji-<?php echo $commentId; ?>" value="<?php echo e($comment['emoji_commentaire']??''); ?>" class="comment-emoji-input comment-emoji-target" placeholder="😊">
                                            <span class="field-error" id="err-edit-comment-<?php echo $commentId; ?>"></span>
                                            <div class="comment-tools">
                                                <label class="comment-tool-btn" title="Modifier l'image">🖼️<input type="file" name="comment_image" accept=".jpg,.jpeg,.png,.webp,.gif" class="comment-hidden-input"></label>
                                                <button type="button" class="comment-tool-btn comment-emoji-btn" data-target="edit-emoji-<?php echo $commentId; ?>">😊</button>
                                                <button type="submit" class="comment-edit-save-btn">Enregistrer</button>
                                                <button type="button" class="comment-edit-cancel-btn" onclick="cancelEditComment(<?php echo $commentId; ?>)">Annuler</button>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="comment-meta-row">
                                        <span class="comment-time"><?php echo e(timeAgo($comment['date_commentaire']??'')); ?></span>
                                        <button type="button" class="reply-btn" data-root-id="<?php echo $commentId; ?>" data-author="<?php echo e($commentAuthor); ?>" data-holder-id="reply-holder-comment-<?php echo $commentId; ?>">Répondre</button>
                                    </div>
                                    <div class="inline-reply-holder" id="reply-holder-comment-<?php echo $commentId; ?>"></div>
                                    <div class="reply-box" id="reply-box-<?php echo $commentId; ?>" style="display:none;">
                                        <form method="POST" action="" enctype="multipart/form-data" novalidate class="reply-form">
                                            <input type="hidden" name="add_comment" value="1"><input type="hidden" name="post_id" value="<?php echo (int)$post['id_post']; ?>"><input type="hidden" name="parent_id" value="<?php echo $commentId; ?>">
                                            <textarea name="comment_content" id="reply-content-<?php echo $commentId; ?>" class="comment-area reply-area comment-emoji-target" placeholder="Votre réponse..." required></textarea>
                                            <input type="text" name="emoji_content" id="emoji-reply-<?php echo $commentId; ?>" value="" class="comment-emoji-input comment-emoji-target" placeholder="😊">
                                            <span id="err-reply-content-<?php echo $commentId; ?>" class="field-error"></span>
                                            <div class="comment-tools">
                                                <label class="comment-tool-btn reply-tool-btn">🖼️<input type="file" name="comment_image" accept=".jpg,.jpeg,.png,.webp,.gif" class="comment-hidden-input" id="reply-img-<?php echo $commentId; ?>"></label>
                                                <button type="button" class="comment-tool-btn reply-tool-btn comment-emoji-btn" data-target="emoji-reply-<?php echo $commentId; ?>">😊</button>
                                                <button type="submit" name="add_comment" class="solid-btn reply-submit-btn">Répondre</button>
                                            </div>
                                        </form>
                                    </div>
                                    <?php if(!empty($commentReplies)): ?>
                                    <div class="replies-list" id="replies-list-<?php echo $commentId; ?>">
                                        <?php foreach($commentReplies as $reply):
                                            $replyId=(int)($reply['id_commentaire']??0);
                                            $replyAuthor=trim(($reply['prenom']??'').' '.($reply['nom']??'')); if($replyAuthor==='') $replyAuthor='Utilisateur';
                                            $replyImageUrl=!empty($reply['image_commentaire'])?forumAppUrl($reply['image_commentaire']):'';
                                            $isReplyOwner=((int)($reply['id_user']??0)===$currentUserId);
                                        ?>
                                        <div class="reply-item" id="reply-thread-<?php echo $replyId; ?>">
                                            <div class="comment-avatar-wrap"><div class="mini-avatar"><?php echo e(strtoupper(substr($replyAuthor,0,1))); ?></div></div>
                                            <div class="comment-body">
                                                <div class="comment-bubble reply-bubble" id="reply-bubble-<?php echo $replyId; ?>">
                                                    <div class="reply-header-row">
                                                        <strong class="comment-author"><?php echo e($replyAuthor); ?></strong>
                                                        <div class="comment-menu-wrap">
                                                            <button type="button" class="comment-menu-btn" onclick="toggleCommentMenu('cmenu-reply-<?php echo $replyId; ?>')">⋯</button>
                                                            <div class="comment-dropdown" id="cmenu-reply-<?php echo $replyId; ?>">
                                                                <?php if($isReplyOwner): ?>
                                                                    <button type="button" onclick="startEditComment(<?php echo $replyId; ?>,<?php echo (int)$post['id_post']; ?>)">✏️ Modifier</button>
                                                                    <button type="button" class="danger" onclick="deleteComment(<?php echo $replyId; ?>,<?php echo (int)$post['id_post']; ?>,<?php echo $commentId; ?>)">🗑 Supprimer</button>
                                                                <?php else: ?>
                                                                    <button type="button" onclick="openReportCommentModal(<?php echo $replyId; ?>,<?php echo (int)$post['id_post']; ?>)">🚩 Signaler</button>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div id="comment-text-<?php echo $replyId; ?>">
                                                        <?php if(!empty($reply['contenu_commentaire'])): ?><div class="comment-text"><?php echo nl2br(e($reply['contenu_commentaire'])); ?></div><?php endif; ?>
                                                        <?php if(!empty($reply['emoji_commentaire'])): ?><div class="comment-emoji-line"><?php echo e($reply['emoji_commentaire']); ?></div><?php endif; ?>
                                                        <?php if(!empty($replyImageUrl)): ?><div class="comment-image-wrap"><img src="<?php echo e($replyImageUrl); ?>" alt="Image réponse" class="comment-image"></div><?php endif; ?>
                                                    </div>
                                                    <form method="POST" action="" enctype="multipart/form-data" id="comment-edit-zone-<?php echo $replyId; ?>" style="display:none;" class="comment-edit-form">
                                                        <input type="hidden" name="update_comment" value="1"><input type="hidden" name="comment_id" value="<?php echo $replyId; ?>"><input type="hidden" name="post_id" value="<?php echo (int)$post['id_post']; ?>">
                                                        <textarea name="comment_content" class="comment-edit-area comment-emoji-target" id="comment-edit-input-<?php echo $replyId; ?>"><?php echo e($reply['contenu_commentaire']??''); ?></textarea>
                                                        <input type="text" name="emoji_content" id="edit-emoji-<?php echo $replyId; ?>" value="<?php echo e($reply['emoji_commentaire']??''); ?>" class="comment-emoji-input comment-emoji-target" placeholder="😊">
                                                        <span class="field-error" id="err-edit-comment-<?php echo $replyId; ?>"></span>
                                                        <div class="comment-tools">
                                                            <label class="comment-tool-btn reply-tool-btn">🖼️<input type="file" name="comment_image" accept=".jpg,.jpeg,.png,.webp,.gif" class="comment-hidden-input"></label>
                                                            <button type="button" class="comment-tool-btn reply-tool-btn comment-emoji-btn" data-target="edit-emoji-<?php echo $replyId; ?>">😊</button>
                                                            <button type="submit" class="comment-edit-save-btn">Enregistrer</button>
                                                            <button type="button" class="comment-edit-cancel-btn" onclick="cancelEditComment(<?php echo $replyId; ?>)">Annuler</button>
                                                        </div>
                                                    </form>
                                                </div>
                                                <div class="comment-meta-row">
                                                    <span class="comment-time"><?php echo e(timeAgo($reply['date_commentaire']??'')); ?></span>
                                                    <button type="button" class="reply-btn" data-root-id="<?php echo $commentId; ?>" data-author="<?php echo e($replyAuthor); ?>" data-holder-id="reply-holder-reply-<?php echo $replyId; ?>">Répondre</button>
                                                </div>
                                                <div class="inline-reply-holder" id="reply-holder-reply-<?php echo $replyId; ?>"></div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php else: ?><div class="replies-list" id="replies-list-<?php echo $commentId; ?>"></div><?php endif; ?>
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
                    <?php if(empty($topContributors)): ?>
                    <div class="top-contributor-item"><div class="top-contributor-left"><div class="mini-avatar">U</div><div><strong>Aucun utilisateur</strong><span>0 posts</span></div></div><div class="top-contributor-count">0</div></div>
                    <?php else: ?>
                    <?php foreach($topContributors as $contributor): $fn=trim(($contributor['prenom']??'').' '.($contributor['nom']??'')); if($fn==='') $fn='Utilisateur'; ?>
                    <div class="top-contributor-item"><div class="top-contributor-left"><div class="mini-avatar"><?php echo e(strtoupper(substr($fn,0,1))); ?></div><div><strong><?php echo e($fn); ?></strong><span><?php echo (int)($contributor['total_posts']??0); ?> posts</span></div></div><div class="top-contributor-count"><?php echo (int)($contributor['total_posts']??0); ?></div></div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </article>
            <article class="panel">
                <span class="section-badge">Posts récents</span>
                <div class="recent-posts-list">
                    <?php foreach($allPosts as $rpIndex=>$rp): if($rpIndex<5): ?>
                    <div class="recent-post-item"><div class="recent-post-left"><div class="mini-avatar"><?php echo strtoupper(substr($rp['titre']??'P',0,1)); ?></div><div><strong><?php echo e($rp['titre']??'Post'); ?></strong><span><?php echo e(timeAgo($rp['date_publication']??'')); ?></span></div></div></div>
                    <?php endif; endforeach; ?>
                </div>
            </article>
        </div>
    </section>
</div>

<div class="ig-lang-zone notranslate" translate="no">
    <div class="ig-lang-wrap">
        <button type="button" class="ig-lang-btn" onclick="toggleIgLangMenu()">Langue ▾</button>
        <div class="ig-lang-menu" id="igLangMenu">
            <button type="button" onclick="changeForumLang('fr')">Français</button>
            <button type="button" onclick="changeForumLang('en')">English</button>
            <button type="button" onclick="changeForumLang('ar')">العربية</button>
            <button type="button" onclick="changeForumLang('es')">Español</button>
            <button type="button" onclick="changeForumLang('it')">Italiano</button>
            <button type="button" onclick="changeForumLang('de')">Deutsch</button>
            <button type="button" onclick="changeForumLang('tr')">Türkçe</button>
            <button type="button" onclick="changeForumLang('pt')">Português</button>
            <button type="button" onclick="changeForumLang('ru')">Русский</button>
            <button type="button" onclick="changeForumLang('zh-CN')">中文</button>
            <button type="button" onclick="changeForumLang('ja')">日本語</button>
            <button type="button" onclick="changeForumLang('ko')">한국어</button>
        </div>
    </div>
    <div id="google_translate_element"></div>
</div>

<!-- MODAL CRÉER/MODIFIER POST -->
<div class="modal-overlay" id="forumModal">
    <div class="forum-modal">
        <div class="forum-modal-head">
            <div class="forum-modal-title"><?php echo $isEditShareMode?'Modifier le partage':($isEditMode?'Modifier la publication':'Créer une publication'); ?></div>
            <button type="button" class="forum-modal-close" id="closeForumModal">×</button>
        </div>
        <div class="forum-modal-body">
            <div class="forum-modal-user"><div class="mini-avatar"><?php echo e($currentUserAvatarLetter); ?></div><div class="forum-modal-name"><?php echo e($currentUserName); ?></div></div>
            <form method="POST" enctype="multipart/form-data" id="postForm" novalidate>
                <?php if($isEditMode): ?><input type="hidden" name="edit_id" value="<?php echo (int)$editId; ?>"><?php endif; ?>

                <?php if($isEditShareMode): ?>
                    <div class="forum-form-grid">
                        <div class="forum-form-field full-width">
                            <textarea name="contenu" id="contenu" placeholder="Contenu du partage" class="post-emoji-target <?php echo invalidClass($errors['contenu']); ?>"><?php echo e($old['contenu']??''); ?></textarea>
                            <span class="field-error" id="err-contenu"><?php echo e($errors['contenu']); ?></span>
                        </div>
                        <div class="forum-form-field full-width">
                            <div style="display:grid;grid-template-columns:minmax(0,1fr) 58px;gap:12px;align-items:center;">
                                <input type="text" name="emoji_post" id="emoji_post" placeholder="Emoji du partage" value="<?php echo e($old['emoji_post']??''); ?>" class="forum-emoji-input <?php echo invalidClass($errors['emoji_post']??''); ?>" style="margin-top:0;">
                                <button type="button" class="tool-trigger emoji-open-btn" data-target="emoji_post" id="emojiTrigger" style="height:54px;border-radius:16px;background:var(--forum-btn-bg);color:#fff;">😊</button>
                            </div>
                            <span class="field-error emoji-error" id="err-emoji_post"><?php echo e($errors['emoji_post']??''); ?></span>
                        </div>
                    </div>
                    <div class="forum-form-actions">
                        <button type="button" class="outline-btn" id="cancelForumModal">Annuler</button>
                        <button class="solid-btn" type="submit" name="update_post">Mettre à jour le partage</button>
                    </div>
                <?php else: ?>
                    <div class="forum-form-grid">
                        <div class="forum-form-field full-width"><input type="text" name="titre" id="titre" placeholder="Titre du post" value="<?php echo e($old['titre']??''); ?>" class="<?php echo invalidClass($errors['titre']); ?>"><span class="field-error" id="err-titre"><?php echo e($errors['titre']); ?></span></div>
                        <div class="forum-form-field"><select name="type_post" id="type_post" class="<?php echo invalidClass($errors['type_post']); ?>"><option value="">Type de post</option><option value="Discussion" <?php echo ($old['type_post']??'')==='Discussion'?'selected':''; ?>>Discussion</option><option value="Conseil" <?php echo ($old['type_post']??'')==='Conseil'?'selected':''; ?>>Conseil</option><option value="Question" <?php echo ($old['type_post']??'')==='Question'?'selected':''; ?>>Question</option></select><span class="field-error" id="err-type_post"><?php echo e($errors['type_post']); ?></span></div>
                        <div class="forum-form-field full-width"><textarea name="contenu" id="contenu" placeholder="Description" class="post-emoji-target <?php echo invalidClass($errors['contenu']); ?>"><?php echo e($old['contenu']??''); ?></textarea><span class="field-error" id="err-contenu"><?php echo e($errors['contenu']); ?></span></div>
                        <div class="forum-form-field full-width"><input type="text" name="emoji_post" id="emoji_post" placeholder="Emoji du post" value="<?php echo e($old['emoji_post']??''); ?>" class="forum-emoji-input <?php echo invalidClass($errors['emoji_post']??''); ?>"><span class="field-error emoji-error" id="err-emoji_post"><?php echo e($errors['emoji_post']??''); ?></span></div>
                        <input type="hidden" name="gif_post" id="gif_post" value="<?php echo e($editPost['gif_post']??''); ?>">
                        <div class="forum-form-field full-width">
                            <div class="gif-selected-preview <?php echo (!empty($editPost['gif_post'])?'show':''); ?>" id="gifSelectedPreview">
                                <button type="button" class="gif-clear-btn" id="clearGifBtn">×</button>
                                <img id="gifSelectedImg" src="<?php echo !empty($editPost['gif_post'])?e(forumMediaUrl($editPost['gif_post'])):''; ?>" alt="GIF sélectionné">
                            </div>
                            <span class="field-error" id="err-gif"><?php echo e($errors['gif']??''); ?></span>
                        </div>
                    </div>
                    <div class="forum-modal-tools">
                        <div>Ajouter à votre publication</div>
                        <div class="forum-tool-icons">
                            <button type="button" class="tool-trigger" id="photoTrigger">🖼️</button>
                            <button type="button" class="tool-trigger" id="videoTrigger">🎥</button>
                            <button type="button" class="tool-trigger gif-media-btn" id="gifTrigger" title="Choisir un GIF ou un sticker"><span class="gif-text">GIF</span></button>
                            <button type="button" class="tool-trigger emoji-open-btn" data-target="emoji_post" id="emojiTrigger">😊</button>
                        </div>
                    </div>
                    <div class="forum-form-field full-width" style="margin-top:14px;">
                        <input type="file" name="image" id="image" accept=".jpg,.jpeg,.png,.webp,.gif" class="<?php echo invalidClass($errors['image']); ?>" style="display:none;">
                        <span class="field-error" id="err-image"><?php echo e($errors['image']); ?></span>
                        <div class="forum-upload-preview" id="forumUploadPreview"><img id="forumPreviewImg" src="" alt="Prévisualisation image"></div>
                        <?php if($isEditMode&&$editPost&&!empty($editPost['image'])): ?><div class="forum-current-image show" id="forumCurrentImage"><img src="<?php echo e(forumAppUrl($editPost['image'])); ?>" alt="Image actuelle"></div><?php else: ?><div class="forum-current-image" id="forumCurrentImage"></div><?php endif; ?>
                    </div>
                    <div class="forum-form-field full-width" style="margin-top:14px;">
                        <input type="file" name="video" id="video" accept=".mp4,.webm,.ogg" class="<?php echo invalidClass($errors['video']); ?>" style="display:none;">
                        <span class="field-error" id="err-video"><?php echo e($errors['video']); ?></span>
                        <div class="forum-video-preview" id="forumVideoPreview"><video id="forumPreviewVideo" controls autoplay muted loop playsinline></video></div>
                        <?php if($isEditMode&&$editPost&&!empty($editPost['video'])): ?><div class="forum-current-video show" id="forumCurrentVideo"><video controls autoplay muted loop playsinline><source src="<?php echo e(forumAppUrl($editPost['video'])); ?>"></video></div><?php else: ?><div class="forum-current-video" id="forumCurrentVideo"></div><?php endif; ?>
                    </div>
                    <div class="forum-form-actions">
                        <button type="button" class="outline-btn" id="cancelForumModal">Annuler</button>
                        <?php if($isEditMode): ?><button class="solid-btn" type="submit" name="update_post">Mettre à jour</button><?php else: ?><button class="solid-btn" type="submit" name="publish_post">Publier</button><?php endif; ?>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<!-- MEDIA VIEWER -->
<div class="media-viewer-overlay" id="mediaViewer">
    <button type="button" class="media-viewer-close" id="closeMediaViewer">×</button>
    <div class="media-viewer-content" id="imageViewerContent">
        <div class="media-viewer-image-layout">
            <div class="media-viewer-image-main" id="imageViewerMain"></div>
            <div class="media-viewer-image-side">
                <div class="viewer-post-head"><div class="viewer-user"><div class="mini-avatar" id="viewerUserAvatar">U</div><div class="viewer-user-meta"><strong id="viewerUserName">Utilisateur</strong><span id="viewerPostTime">à l'instant</span></div></div></div>
                <div class="viewer-post-body"><div class="viewer-post-title" id="viewerPostTitle"></div><div id="viewerPostContent"></div></div>
                <div class="viewer-stats" id="viewerStatsRow"><span id="viewerLikesWrap" style="display:none;">👍 <span id="viewerLikesCount">0</span></span><span id="viewerCommentsWrap" style="display:none;">💬 <span id="viewerCommentsCount">0</span></span><span id="viewerSharesWrap" style="display:none;">🔁 <span id="viewerSharesCount">0</span></span></div>
                <div class="viewer-actions">
                    <form method="POST" action="" style="margin:0;"><input type="hidden" name="toggle_like" value="1"><input type="hidden" name="post_id" id="viewerLikePostId" value=""><button class="viewer-action-btn" type="submit" id="viewerLikeBtn">👍</button></form>
                    <button class="viewer-action-btn" type="button" id="viewerCommentBtn">💬</button>
                    <button class="viewer-action-btn" type="button" id="viewerShareBtn">🔁</button>
                    <form method="POST" action="" style="margin:0;"><input type="hidden" name="toggle_save" value="1"><input type="hidden" name="post_id" id="viewerSavePostId" value=""><button class="viewer-action-btn" type="submit" id="viewerSaveBtn">🔖</button></form>
                </div>
                <div class="viewer-comments" id="viewerComments"></div>
                <div class="viewer-comment-form-wrap viewer-compact-wrap">
                    <form method="POST" action="" enctype="multipart/form-data" id="viewerCommentForm" novalidate>
                        <input type="hidden" name="add_comment" value="1"><input type="hidden" name="post_id" id="viewerPostId" value=""><input type="hidden" name="parent_id" id="viewerParentId" value="">
                        <div class="viewer-comment-mini" id="viewerCommentMini"><span>Écrire un commentaire...</span></div>
                        <div class="viewer-comment-expanded" id="viewerCommentExpanded">
                            <textarea name="comment_content" id="viewerCommentContent" class="viewer-comment-input" placeholder="Écrire un commentaire..."></textarea>
                            <input type="text" name="emoji_content" id="viewerEmojiHidden" value="" class="viewer-emoji-input comment-emoji-target" placeholder="Emoji">
                            <span class="field-error" id="err-viewerCommentContent"></span>
                            <div class="viewer-comment-actions">
                                <label class="viewer-square-btn">🖼️<input type="file" name="comment_image" accept=".jpg,.jpeg,.png,.webp,.gif" class="comment-hidden-input" id="viewerCommentImage"></label>
                                <button type="button" class="viewer-square-btn comment-emoji-btn" data-target="viewerEmojiHidden">😊</button>
                                <button type="submit" class="viewer-publish-btn">Publier</button>
                                <button type="button" class="viewer-cancel-comment-btn" id="viewerCancelCommentBtn">Annuler</button>
                            </div>
                            <div class="comment-image-preview" id="viewerCommentPreview"><img alt="Prévisualisation"></div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="media-viewer-content" id="videoViewerContent">
        <div class="media-viewer-video-layout">
            <div class="media-viewer-video-main" id="videoViewerMain"></div>
            <div class="media-viewer-video-side">
                <div class="media-viewer-action" id="reelLikesWrap" style="display:none;"><div class="viewer-side-icon">👍</div><span id="reelLikesCount">0</span></div>
                <button type="button" class="media-viewer-action reel-action-btn" id="reelCommentBtn"><div class="viewer-side-icon">💬</div><span id="reelCommentsCount">0</span></button>
                <button type="button" class="media-viewer-action reel-action-btn" id="reelShareBtn"><div class="viewer-side-icon">🔁</div><span id="reelSharesCount">0</span></button>
                <div class="media-viewer-action video-more-wrap">
                    <button type="button" class="viewer-side-icon reel-action-icon" id="reelMoreBtn">⋯</button>
                    <div class="video-more-dropdown" id="reelMoreDropdown"></div>
                </div>
            </div>
            <div class="media-viewer-video-panel" id="videoCommentsPanel">
                <div class="video-panel-head">
                    <strong>Commentaires</strong>
                    <button type="button" id="closeVideoCommentsPanel">×</button>
                </div>
                <div class="video-panel-comments" id="videoViewerComments"></div>
                <form method="POST" action="" enctype="multipart/form-data" id="videoCommentForm" class="video-comment-form" novalidate>
                    <input type="hidden" name="add_comment" value="1"><input type="hidden" name="post_id" id="videoCommentPostId" value=""><input type="hidden" name="parent_id" id="videoParentId" value="">
                    <textarea name="comment_content" id="videoCommentContent" class="viewer-comment-input" placeholder="Écrire un commentaire..."></textarea>
                    <input type="text" name="emoji_content" id="videoEmojiHidden" value="" class="viewer-emoji-input comment-emoji-target" placeholder="Emoji">
                    <span class="field-error" id="err-videoCommentContent"></span>
                    <div class="viewer-comment-actions">
                        <label class="viewer-square-btn">🖼️<input type="file" name="comment_image" accept=".jpg,.jpeg,.png,.webp,.gif" class="comment-hidden-input" id="videoCommentImage"></label>
                        <button type="button" class="viewer-square-btn comment-emoji-btn" data-target="videoEmojiHidden">😊</button>
                        <button type="submit" class="viewer-publish-btn" id="videoCommentSubmitBtn">Publier</button>
                        <button type="button" class="viewer-cancel-comment-btn" id="videoCancelCommentBtn">Annuler</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- EMOJI PICKER -->
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

<!-- GIF PICKER -->
<div class="gif-modal" id="gifModal">
    <div class="gif-box">
        <div class="gif-header">
            <input type="text" id="gifSearch" placeholder="Rechercher un GIF...">
            <button type="button" id="closeGifModal">×</button>
        </div>
        <div class="gif-results" id="gifResults">
            <div class="gif-empty">Chargement des GIFs...</div>
        </div>
    </div>
</div>

<!-- MODAL SIGNALER POST -->
<div class="modal-overlay" id="reportModal" style="display:none;position:fixed;inset:0;background:rgba(20,39,56,.45);z-index:999999;align-items:center;justify-content:center;">
    <div class="forum-post-modal-box" style="width:min(600px,100%);">
        <div class="forum-post-modal-head"><h2>Signaler ce post</h2></div>
        <div class="forum-post-modal-body">
            <form method="POST" action="">
                <input type="hidden" name="post_id" id="report-post-id" value=""><input type="hidden" name="report_post" value="1">
                <div style="margin-bottom:16px;"><label style="display:block;font-weight:600;margin-bottom:8px;">Raison du signalement</label><select name="report_reason" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:8px;font-size:14px;" required><option value="">Choisir une raison</option><option value="spam">Spam</option><option value="inappropriate">Contenu inapproprié</option><option value="offensive">Contenu offensant</option><option value="misinformation">Désinformation</option><option value="other">Autre</option></select></div>
                <div style="margin-bottom:16px;"><label style="display:block;font-weight:600;margin-bottom:8px;">Détails supplémentaires (optionnel)</label><textarea name="report_details" placeholder="Expliquez pourquoi vous signalez ce post..." style="width:100%;padding:10px;border:1px solid #ddd;border-radius:8px;font-size:14px;min-height:100px;font-family:inherit;resize:vertical;"></textarea></div>
                <div style="display:flex;gap:10px;justify-content:flex-end;"><button type="button" onclick="closeReportModal()" class="ghost-btn">Annuler</button><button type="submit" class="solid-btn">Signaler</button></div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL SIGNALER COMMENTAIRE -->
<div id="reportCommentModal">
    <div style="background:#fff;border-radius:24px;padding:28px;width:min(520px,100%);box-shadow:0 30px 80px rgba(15,23,42,.25);">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;"><h2 style="margin:0;font-size:22px;">Signaler ce commentaire</h2><button type="button" onclick="closeReportCommentModal()" style="width:38px;height:38px;border:none;border-radius:50%;background:#f2f4f8;font-size:20px;cursor:pointer;">×</button></div>
        <form method="POST" action="" id="reportCommentForm">
            <input type="hidden" name="report_comment" value="1"><input type="hidden" name="comment_id" id="report-comment-id" value=""><input type="hidden" name="post_id" id="report-comment-post-id" value="">
            <div style="margin-bottom:16px;"><label style="display:block;font-weight:600;margin-bottom:8px;">Raison du signalement</label><select name="report_reason" style="width:100%;padding:10px;border:1px solid #ddd;border-radius:8px;font-size:14px;" required><option value="">Choisir une raison</option><option value="spam">Spam</option><option value="inappropriate">Contenu inapproprié</option><option value="offensive">Contenu offensant</option><option value="misinformation">Désinformation</option><option value="other">Autre</option></select></div>
            <div style="margin-bottom:20px;"><label style="display:block;font-weight:600;margin-bottom:8px;">Détails (optionnel)</label><textarea name="report_details" placeholder="Expliquez pourquoi..." style="width:100%;padding:10px;border:1px solid #ddd;border-radius:8px;font-size:14px;min-height:90px;font-family:inherit;resize:vertical;box-sizing:border-box;"></textarea></div>
            <div style="display:flex;gap:10px;justify-content:flex-end;"><button type="button" onclick="closeReportCommentModal()" style="padding:10px 20px;border:1px solid #ddd;border-radius:10px;background:#fff;cursor:pointer;font-weight:600;">Annuler</button><button type="submit" class="solid-btn">Signaler</button></div>
        </form>
    </div>
</div>

<!-- MODAL PARTAGE AVANCÉ -->
<div class="advanced-share-modal" id="advancedShareModal">
    <div class="advanced-share-box">
        <div class="advanced-share-head"><h2>Partager</h2><button type="button" class="advanced-share-close" onclick="closeAdvancedShareModal()">×</button></div>
        <div class="advanced-share-body">
            <div class="advanced-share-user-row"><div class="mini-avatar" id="advancedShareAvatar">U</div><div><strong id="advancedShareUser">Utilisateur</strong></div></div>
            <textarea class="advanced-share-caption" id="advancedShareCaption" placeholder="Écrire une description pour votre partage..."></textarea>
            <div class="advanced-share-emoji-row">
                <input type="text" class="advanced-share-caption forum-emoji-input" id="advancedShareEmoji" placeholder="Ajouter des emojis 😊" style="min-height:46px;height:46px;border:1px solid var(--forum-border);border-radius:14px;background:var(--forum-bg-input);padding:0 14px;box-sizing:border-box;margin:0;">
                <button type="button" class="advanced-share-emoji-btn emoji-open-btn" data-target="advancedShareEmoji" title="Choisir un emoji">😊</button>
            </div>
            <div class="advanced-share-preview">
                <div class="advanced-share-preview-media" id="advancedSharePreviewMedia">GoService Forum</div>
                <div class="advanced-share-preview-info"><div class="advanced-share-preview-site">GoService</div><div class="advanced-share-preview-title" id="advancedSharePreviewTitle">Post GoService</div><div class="advanced-share-preview-desc" id="advancedSharePreviewDesc"></div></div>
            </div>
            <div class="advanced-share-label">Partager sur</div>
            <div class="advanced-share-grid">
                <button type="button" class="advanced-share-option" onclick="sharePostAdvanced('facebook')"><span class="advanced-share-icon">📘</span><span>Facebook</span></button>
                <button type="button" class="advanced-share-option" onclick="sharePostAdvanced('whatsapp')"><span class="advanced-share-icon">🟢</span><span>WhatsApp</span></button>
                <button type="button" class="advanced-share-option" onclick="sharePostAdvanced('internal')"><span class="advanced-share-icon">🚀</span><span>GoService</span></button>
                <button type="button" class="advanced-share-option" onclick="sharePostAdvanced('copy')"><span class="advanced-share-icon">🔗</span><span>Copier le lien</span></button>
            </div>
            <p class="advanced-share-note" id="advancedShareNote"></p>
        </div>
    </div>
</div>
<div class="advanced-share-toast" id="advancedShareToast">Lien copié ✅</div>

<!-- FORMULAIRES CACHÉS -->
<form method="POST" action="" id="deletePostViewerForm" style="display:none;"><input type="hidden" name="delete_post" value="1"><input type="hidden" name="post_id" id="deletePostViewerId" value=""></form>
<form method="POST" action="" id="deleteCommentForm" style="display:none;"><input type="hidden" name="delete_comment" value="1"><input type="hidden" name="comment_id" id="deleteCommentId" value=""><input type="hidden" name="post_id" id="deleteCommentPostId" value=""><input type="hidden" name="parent_id" id="deleteCommentParentId" value=""></form>
<form method="POST" action="" id="updateCommentForm" style="display:none;"><input type="hidden" name="update_comment" value="1"><input type="hidden" name="comment_id" id="updateCommentId" value=""><input type="hidden" name="post_id" id="updateCommentPostId" value=""><input type="hidden" name="comment_content" id="updateCommentContent" value=""></form>

<script>
/* ============================================================ VARIABLES GLOBALES */
const forumModal=document.getElementById('forumModal'),openCreateModalBtn=document.getElementById('openCreateModalBtn'),closeForumModal=document.getElementById('closeForumModal'),cancelForumModal=document.getElementById('cancelForumModal');
const photoTrigger=document.getElementById('photoTrigger'),videoTrigger=document.getElementById('videoTrigger'),gifTrigger=document.getElementById('gifTrigger'),openPhotoBtn=document.getElementById('openPhotoBtn'),openVideoBtn=document.getElementById('openVideoBtn'),openGifBtn=document.getElementById('openGifBtn'),openEmojiBtn=document.getElementById('openEmojiBtn');
const imageField=document.getElementById('image'),videoField=document.getElementById('video'),previewBox=document.getElementById('forumUploadPreview'),previewImg=document.getElementById('forumPreviewImg'),videoPreviewBox=document.getElementById('forumVideoPreview'),previewVideo=document.getElementById('forumPreviewVideo'),currentImageBox=document.getElementById('forumCurrentImage'),currentVideoBox=document.getElementById('forumCurrentVideo');
const postForm=document.getElementById('postForm'),mediaViewer=document.getElementById('mediaViewer'),closeMediaViewer=document.getElementById('closeMediaViewer'),imageViewerContent=document.getElementById('imageViewerContent'),videoViewerContent=document.getElementById('videoViewerContent'),imageViewerMain=document.getElementById('imageViewerMain'),videoViewerMain=document.getElementById('videoViewerMain'),emojiPicker=document.getElementById('emojiPicker'),emojiPickerBody=document.getElementById('emojiPickerBody'),emojiTabs=document.getElementById('emojiTabs');
let activeEmojiTarget=null,lastEmojiTrigger=null;
const gifModal=document.getElementById('gifModal'),gifSearch=document.getElementById('gifSearch'),gifResults=document.getElementById('gifResults'),closeGifModal=document.getElementById('closeGifModal'),gifInput=document.getElementById('gif_post'),gifSelectedPreview=document.getElementById('gifSelectedPreview'),gifSelectedImg=document.getElementById('gifSelectedImg'),clearGifBtn=document.getElementById('clearGifBtn');
const GIPHY_API_KEY='J8Isus6qXyh0ajM2mYYmHvKF5IZhlorl';
const CURRENT_FORUM_USER_ID=<?php echo (int)$currentUserId; ?>;
const FORUM_APP_ROOT=<?php echo json_encode(forumAppRoot(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
const FORUM_INDEX_URL=<?php echo json_encode(forumAppUrl('view/front/index.php?page=forum'), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE); ?>;
let currentViewerPostData=null;

/* ============================================================ EMOJIS */
const emojiGroups={smileys:['😀','😁','😂','🤣','😃','😄','😅','😆','😉','😊','🙂','🙃','😍','🥰','😘','😗','😙','😚','😋','😛','😜','🤪','😝','🫠','🤗','🤭','🫢','🤫','🤔','🫡','😐','😑','😶','🫥','😏','😒','🙄','😬','🤥','😌','😔','😪','🤤','😴','😷','🤒','🤕','🤢','🤮','🥵','🥶','🥴','😵','🤯','😎','🤩','🥳','😤','😭','😢','😡','🤬','😱','😨','😰','😥','😓','😳','🥹','😇'],people:['👋','🤚','🖐️','✋','🫱','🫲','👌','🤌','🤏','✌️','🤞','🫰','🤟','🤘','👏','🙌','🫶','🤝','🙏','💪','🫵','👀','🧠','👶','🧒','👦','👧','🧑','👨','👩','🧔','👱','👴','👵','🙍','🙎','🙅','🙆','💁','🙋','🧏','🙇','🤦','🤷','👮','🧑‍💻','👨‍💻','👩‍💻','🧑‍🎓','👨‍🎓','👩‍🎓'],animals:['🐶','🐱','🐭','🐹','🐰','🦊','🐻','🐼','🐨','🐯','🦁','🐮','🐷','🐸','🐵','🙈','🙉','🙊','🐔','🐧','🐦','🐤','🦆','🦅','🦉','🦇','🐺','🐗','🐴','🦄','🐝','🪲','🐞','🦋','🐌','🐢','🐍','🦎','🦂','🦀','🐙','🦑','🐬','🐳','🦈'],food:['🍏','🍎','🍐','🍊','🍋','🍌','🍉','🍇','🍓','🫐','🍈','🍒','🍑','🥭','🍍','🥥','🥝','🍅','🍆','🥑','🥦','🥬','🥒','🌶️','🫑','🌽','🥕','🫒','🧄','🧅','🥔','🍠','🥐','🍞','🥖','🧀','🍗','🍖','🍔','🍟','🍕','🌭','🥪','🌮','🌯','🥗','🍝','🍜','🍣','🍩','🍪','🎂','🍫','🍿','☕','🧃'],travel:['🚗','🚕','🚙','🚌','🚎','🏎️','🚓','🚑','🚒','🚚','🚜','🏍️','🚲','✈️','🛫','🛬','🚀','🛸','🚁','⛵','🚤','🛳️','🚂','🚆','🚇','🚝','🗺️','🧭','🏖️','🏝️','🏜️','🏕️','🏔️','⛰️','🌋','🗽','🗼','🏰','🏟️','🎡','🎢'],objects:['⌚','📱','💻','⌨️','🖥️','🖨️','🖱️','📷','📹','🎥','☎️','📞','📺','📻','🎙️','🎧','📢','💡','🔦','🕯️','🪫','🔋','🔌','💰','💳','🧾','📦','📌','✂️','🖊️','🖋️','📝','📚','🧸','🎁','🏆','⚽','🏀','🎮','🛒','🛠️','🔧','🔨'],symbols:['❤️','🩷','🧡','💛','💚','🩵','💙','💜','🖤','🤍','🤎','💔','❣️','💕','💞','💓','💗','💖','💘','💝','💯','✅','✔️','✖️','❌','⚠️','🚫','⭐','🌟','✨','🔥','💥','🎉','🎊','🔔','📣','🔴','🟠','🟡','🟢','🔵','🟣','⚫','⚪']};

/* ============================================================ MODAL POST */
function openModal(){forumModal.classList.add('show');document.body.style.overflow='hidden';}
function closeModal(goClean=false){forumModal.classList.remove('show');hideEmojiPicker();document.body.style.overflow='';if(goClean) window.location.href=FORUM_INDEX_URL;}

/* ============================================================ MEDIA VIEWER */
function hideAllViewerModes(){imageViewerContent.classList.remove('show');videoViewerContent.classList.remove('show');imageViewerMain.innerHTML='';videoViewerMain.innerHTML='';const vp=document.getElementById('videoCommentsPanel');if(vp)vp.classList.remove('show');toggleVideoMoreMenu(false);}
function setCountVisibility(wId,val,cId){const w=document.getElementById(wId),c=document.getElementById(cId);if(!w||!c) return;if(Number(val)>0){w.style.display='';c.textContent=val;}else{w.style.display='none';c.textContent=0;}}
function escapeHtml(t){const d=document.createElement('div');d.textContent=t||'';return d.innerHTML;}

function openMediaViewer(data){
    if(!mediaViewer) return;
    currentViewerPostData=data||{};
    hideAllViewerModes();
    const safeTitle=data.title||'',safeContent=data.content||'',safeUser=data.user||'Utilisateur',safeTime=data.time||"à l'instant",likes=Number(data.likes||0),comments=Number(data.comments||0),shares=Number(data.shares||0);
    const vPostId=document.getElementById('viewerPostId'),vLikePostId=document.getElementById('viewerLikePostId'),vSavePostId=document.getElementById('viewerSavePostId'),vLikeBtn=document.getElementById('viewerLikeBtn'),vSaveBtn=document.getElementById('viewerSaveBtn'),vComments=document.getElementById('viewerComments'),vShareBtn=document.getElementById('viewerShareBtn');
    const videoPostId=document.getElementById('videoCommentPostId'),videoComments=document.getElementById('videoViewerComments');
    if(vPostId) vPostId.value=data.id||'';
    if(videoPostId) videoPostId.value=data.id||'';
    if(vLikePostId) vLikePostId.value=data.id||'';
    if(vSavePostId) vSavePostId.value=data.id||'';
    const vParentId=document.getElementById('viewerParentId'),vCommentContent=document.getElementById('viewerCommentContent');
    if(vParentId) vParentId.value='';
    if(vCommentContent){vCommentContent.value='';vCommentContent.placeholder='Écrire un commentaire...';}
    const vImgInput=document.getElementById('viewerCommentImage'),vImgPreview=document.getElementById('viewerCommentPreview'),vEmojiH=document.getElementById('viewerEmojiHidden'),vErr=document.getElementById('err-viewerCommentContent'),vExpanded=document.getElementById('viewerCommentExpanded'),vMini=document.getElementById('viewerCommentMini');
    if(vImgInput) vImgInput.value='';
    if(vImgPreview) vImgPreview.classList.remove('show');
    if(vEmojiH) vEmojiH.value='';
    if(vErr) vErr.textContent='';
    if(vExpanded) vExpanded.classList.remove('show');
    if(vMini) vMini.style.display='flex';
    closeVideoCommentBox(false);
    videoSetAddMode();
    if(vLikeBtn) vLikeBtn.textContent=data.is_liked?'❤️':'👍';
    if(vSaveBtn) vSaveBtn.textContent=data.is_saved?'📌':'🔖';
    if(vShareBtn) vShareBtn.onclick=function(){openAdvancedShareModal({id:data.id,title:safeTitle,content:safeContent,user:safeUser,image:data.image||'',video:data.video||''});};
    const commentsHtml=formatViewerComments(Array.isArray(data.comments_data)?data.comments_data:[]);
    const emptyComments='<div class="viewer-comment-item"><div class="mini-avatar">U</div><div class="viewer-comment-bubble">Aucun commentaire pour le moment.</div></div>';
    if(vComments) vComments.innerHTML=commentsHtml||emptyComments;
    if(videoComments) videoComments.innerHTML=commentsHtml||emptyComments;
    renderVideoMoreMenu(data);
    if(data.type==='video'){
        const v=document.createElement('video');v.src=data.src;v.controls=true;v.autoplay=true;v.loop=true;v.playsInline=true;videoViewerMain.appendChild(v);
        setCountVisibility('reelLikesWrap',likes,'reelLikesCount');
        const rc=document.getElementById('reelCommentsCount'),rs=document.getElementById('reelSharesCount'); if(rc) rc.textContent=comments; if(rs) rs.textContent=shares;
        const cbtn=document.getElementById('reelCommentBtn'),sbtn=document.getElementById('reelShareBtn'),mbtn=document.getElementById('reelMoreBtn');
        if(cbtn) cbtn.onclick=function(){toggleVideoCommentsPanel();};
        if(sbtn) sbtn.onclick=function(){openAdvancedShareModal({id:data.id,title:safeTitle,content:safeContent,user:safeUser,image:data.image||'',video:data.video||''});};
        if(mbtn) mbtn.onclick=function(e){e.stopPropagation();toggleVideoMoreMenu();};
        videoViewerContent.classList.add('show');
    } else {
        const img=document.createElement('img');img.src=data.src;img.alt=safeTitle;imageViewerMain.appendChild(img);
        document.getElementById('viewerUserAvatar').textContent=safeUser.charAt(0).toUpperCase();
        document.getElementById('viewerUserName').textContent=safeUser;
        document.getElementById('viewerPostTime').textContent=safeTime;
        document.getElementById('viewerPostTitle').textContent=safeTitle;
        document.getElementById('viewerPostContent').innerHTML=escapeHtml(safeContent).replace(/\n/g,'<br>');
        setCountVisibility('viewerLikesWrap',likes,'viewerLikesCount');setCountVisibility('viewerCommentsWrap',comments,'viewerCommentsCount');setCountVisibility('viewerSharesWrap',shares,'viewerSharesCount');imageViewerContent.classList.add('show');
    }
    mediaViewer.classList.add('show');document.body.style.overflow='hidden';
}

function renderVideoMoreMenu(data){
    const menu=document.getElementById('reelMoreDropdown');
    if(!menu) return;
    const postId=Number(data.id||0);
    if(data.is_owner){
        menu.innerHTML=`<a href="${FORUM_INDEX_URL}&edit=${postId}" onclick="localStorage.setItem('openForumModal','1')">✏️ Modifier</a><button type="button" class="danger" onclick="viewerDeletePost(${postId})">🗑 Supprimer</button>`;
    }else{
        menu.innerHTML=`<button type="button" onclick="openReportModal(${postId});toggleVideoMoreMenu(false);">🚩 Signaler</button>`;
    }
}
function toggleVideoMoreMenu(force){const m=document.getElementById('reelMoreDropdown');if(!m) return;if(force===false)m.classList.remove('show');else m.classList.toggle('show');}
function viewerDeletePost(postId){toggleVideoMoreMenu(false);if(!confirm('Supprimer ce post ?')) return;const i=document.getElementById('deletePostViewerId'),f=document.getElementById('deletePostViewerForm');if(i&&f){i.value=postId;f.submit();}}
function toggleVideoCommentsPanel(){const p=document.getElementById('videoCommentsPanel');if(p) p.classList.toggle('show');}
function closeVideoCommentBox(hidePanel=true){const p=document.getElementById('videoCommentsPanel'),f=document.getElementById('videoCommentForm'),ta=document.getElementById('videoCommentContent'),em=document.getElementById('videoEmojiHidden'),par=document.getElementById('videoParentId'),err=document.getElementById('err-videoCommentContent');if(hidePanel&&p)p.classList.remove('show');if(ta){ta.value='';ta.placeholder='Écrire un commentaire...';}if(em)em.value='';if(par)par.value='';if(err)err.textContent='';videoSetAddMode();}
function videoSetAddMode(){const f=document.getElementById('videoCommentForm');if(!f)return;const upd=f.querySelector('input[name="update_comment"]');if(upd)upd.remove();const cid=f.querySelector('#videoEditCommentId');if(cid)cid.remove();let add=f.querySelector('input[name="add_comment"]');if(!add){add=document.createElement('input');add.type='hidden';add.name='add_comment';add.value='1';f.prepend(add);}const sb=document.getElementById('videoCommentSubmitBtn');if(sb)sb.textContent='Publier';}
function videoSetEditMode(commentId){const f=document.getElementById('videoCommentForm');if(!f)return;const add=f.querySelector('input[name="add_comment"]');if(add)add.remove();let upd=f.querySelector('input[name="update_comment"]');if(!upd){upd=document.createElement('input');upd.type='hidden';upd.name='update_comment';upd.value='1';f.prepend(upd);}let cid=f.querySelector('#videoEditCommentId');if(!cid){cid=document.createElement('input');cid.type='hidden';cid.name='comment_id';cid.id='videoEditCommentId';f.appendChild(cid);}cid.value=commentId;const sb=document.getElementById('videoCommentSubmitBtn');if(sb)sb.textContent='Mettre à jour';}
function isVideoCommentsOpen(){const p=document.getElementById('videoCommentsPanel');return !!(p&&p.classList.contains('show'));}
function setVideoReplyTarget(commentId,authorName){const p=document.getElementById('videoCommentsPanel'),pf=document.getElementById('videoParentId'),ta=document.getElementById('videoCommentContent');if(p)p.classList.add('show');videoSetAddMode();if(pf)pf.value=commentId;if(ta){ta.value='@'+authorName+' ';ta.placeholder='@'+authorName+', votre réponse...';ta.focus();ta.setSelectionRange(ta.value.length,ta.value.length);}}
function startVideoEditComment(commentId,postId,content,emoji){const p=document.getElementById('videoCommentsPanel'),pid=document.getElementById('videoCommentPostId'),pf=document.getElementById('videoParentId'),ta=document.getElementById('videoCommentContent'),em=document.getElementById('videoEmojiHidden');if(p)p.classList.add('show');videoSetEditMode(commentId);if(pid)pid.value=postId;if(pf)pf.value='';if(ta){ta.value=content||'';ta.placeholder='Modifier votre commentaire...';ta.focus();ta.setSelectionRange(ta.value.length,ta.value.length);}if(em)em.value=emoji||'';document.querySelectorAll('.viewer-comment-dropdown').forEach(m=>m.classList.remove('show'));}
document.addEventListener('click',function(e){if(!e.target.closest('#reelMoreBtn')&&!e.target.closest('#reelMoreDropdown')) toggleVideoMoreMenu(false);});
document.addEventListener('DOMContentLoaded',function(){const c=document.getElementById('closeVideoCommentsPanel'),x=document.getElementById('videoCancelCommentBtn');if(c)c.addEventListener('click',()=>{const p=document.getElementById('videoCommentsPanel');if(p)p.classList.remove('show');});if(x)x.addEventListener('click',()=>closeVideoCommentBox(false));});

function closeViewer(){mediaViewer.classList.remove('show');hideAllViewerModes();if(!forumModal.classList.contains('show')) document.body.style.overflow='';}
if(closeMediaViewer) closeMediaViewer.addEventListener('click',closeViewer);
if(mediaViewer) mediaViewer.addEventListener('click',function(e){if(e.target===mediaViewer) closeViewer();});

/* ============================================================ BOUTONS MODAL */
if(openCreateModalBtn) openCreateModalBtn.addEventListener('click',()=>openModal());
if(openPhotoBtn) openPhotoBtn.addEventListener('click',()=>{openModal();if(imageField) imageField.click();});
if(openVideoBtn) openVideoBtn.addEventListener('click',()=>{openModal();if(videoField) videoField.click();});
if(openGifBtn) openGifBtn.addEventListener('click',()=>{openModal();openGifPicker();});
if(openEmojiBtn) openEmojiBtn.addEventListener('click',(e)=>{openModal();showEmojiPickerFor('emoji_post',e.currentTarget);});
if(photoTrigger) photoTrigger.addEventListener('click',()=>{if(imageField) imageField.click();});
if(videoTrigger) videoTrigger.addEventListener('click',()=>{if(videoField) videoField.click();});
if(gifTrigger) gifTrigger.addEventListener('click',()=>openGifPicker());
if(closeForumModal) closeForumModal.addEventListener('click',()=>closeModal(true));
if(cancelForumModal) cancelForumModal.addEventListener('click',()=>closeModal(true));
forumModal.addEventListener('click',function(e){if(e.target===forumModal) closeModal(true);});

/* ============================================================ EMOJI PICKER */
function insertEmojiIntoTarget(target,emoji){if(!target) return;const s=target.selectionStart??target.value.length,end=target.selectionEnd??target.value.length,t=target.value;target.value=t.substring(0,s)+emoji+t.substring(end);target.focus();target.selectionStart=target.selectionEnd=s+emoji.length;target.dispatchEvent(new Event('input'));}
function renderEmojiGroup(group){if(!emojiPickerBody||!emojiGroups[group]) return;emojiPickerBody.innerHTML='';emojiGroups[group].forEach(emoji=>{const btn=document.createElement('button');btn.type='button';btn.className='emoji-btn';btn.textContent=emoji;btn.addEventListener('click',()=>{if(activeEmojiTarget) insertEmojiIntoTarget(activeEmojiTarget,emoji);});emojiPickerBody.appendChild(btn);});document.querySelectorAll('.emoji-tab').forEach(tab=>tab.classList.toggle('active',tab.dataset.group===group));}
function showEmojiPickerFor(targetId,triggerEl){const target=document.getElementById(targetId);if(!target||!emojiPicker) return;activeEmojiTarget=target;lastEmojiTrigger=triggerEl;renderEmojiGroup('smileys');const rect=triggerEl.getBoundingClientRect();let top=rect.bottom+10,left=rect.left;if(left+320>window.innerWidth-12) left=window.innerWidth-332;if(left<12) left=12;if(top+390>window.innerHeight-12) top=rect.top-400;if(top<12) top=12;emojiPicker.style.top=top+'px';emojiPicker.style.left=left+'px';emojiPicker.classList.add('show');}
function hideEmojiPicker(){if(emojiPicker) emojiPicker.classList.remove('show');}
document.addEventListener('click',function(e){const btn=e.target.closest('.emoji-open-btn,.comment-emoji-btn');if(btn){showEmojiPickerFor(btn.dataset.target,btn);return;}if(!e.target.closest('#emojiPicker')&&!e.target.closest('.emoji-open-btn')&&!e.target.closest('.comment-emoji-btn')) hideEmojiPicker();});
if(emojiTabs) emojiTabs.addEventListener('click',function(e){const tab=e.target.closest('.emoji-tab');if(!tab) return;renderEmojiGroup(tab.dataset.group);});


/* ============================================================ GIF PICKER */
function openGifPicker(){
    if(!gifModal) return;
    gifModal.classList.add('show');
    if(gifSearch) gifSearch.focus();
    loadTrendingGifs();
}
function closeGifPicker(){ if(gifModal) gifModal.classList.remove('show'); }
function setGifMessage(message){ if(gifResults) gifResults.innerHTML='<div class="gif-empty">'+message+'</div>'; }
function giphyReady(){ return GIPHY_API_KEY && GIPHY_API_KEY!=='PASTE_YOUR_GIPHY_API_KEY_HERE'; }
async function loadTrendingGifs(){
    if(!gifResults) return;
    if(!giphyReady()){ setGifMessage('Ajoute ta clé GIPHY dans GIPHY_API_KEY.'); return; }
    setGifMessage('Chargement des GIFs...');
    try{
        const response=await fetch('https://api.giphy.com/v1/gifs/trending?api_key='+encodeURIComponent(GIPHY_API_KEY)+'&limit=24&rating=g');
        const data=await response.json();
        renderGifs(data.data||[]);
    }catch(e){ setGifMessage('Impossible de charger les GIFs.'); }
}
let gifSearchTimer=null;
async function searchGifs(query){
    if(!giphyReady()){ setGifMessage('Ajoute ta clé GIPHY dans GIPHY_API_KEY.'); return; }
    if(query.trim().length<2){ loadTrendingGifs(); return; }
    setGifMessage('Recherche...');
    try{
        const response=await fetch('https://api.giphy.com/v1/gifs/search?api_key='+encodeURIComponent(GIPHY_API_KEY)+'&q='+encodeURIComponent(query)+'&limit=24&rating=g&lang=fr');
        const data=await response.json();
        renderGifs(data.data||[]);
    }catch(e){ setGifMessage('Recherche GIF impossible.'); }
}
function renderGifs(gifs){
    if(!gifResults) return;
    gifResults.innerHTML='';
    if(!gifs.length){ setGifMessage('Aucun GIF trouvé.'); return; }
    gifs.forEach(gif=>{
        const fixed=(gif.images&&gif.images.fixed_height&&gif.images.fixed_height.url)||'';
        const original=(gif.images&&gif.images.original&&gif.images.original.url)||fixed;
        if(!fixed||!original) return;
        const img=document.createElement('img');
        img.src=fixed;
        img.alt=gif.title||'GIF';
        img.loading='lazy';
        img.addEventListener('click',()=>selectGif(original));
        gifResults.appendChild(img);
    });
}
function selectGif(url){
    if(gifInput) gifInput.value=url;
    if(gifSelectedImg) gifSelectedImg.src=url;
    if(gifSelectedPreview) gifSelectedPreview.classList.add('show');
    closeGifPicker();
}
function clearSelectedGif(){
    if(gifInput) gifInput.value='';
    if(gifSelectedImg) gifSelectedImg.src='';
    if(gifSelectedPreview) gifSelectedPreview.classList.remove('show');
}
if(closeGifModal) closeGifModal.addEventListener('click',closeGifPicker);
if(gifModal) gifModal.addEventListener('click',e=>{if(e.target===gifModal) closeGifPicker();});
if(gifSearch) gifSearch.addEventListener('input',()=>{clearTimeout(gifSearchTimer);gifSearchTimer=setTimeout(()=>searchGifs(gifSearch.value),350);});
if(clearGifBtn) clearGifBtn.addEventListener('click',clearSelectedGif);

/* ============================================================ VALIDATION POST */
function getLettersCountJS(text){return text.replace(/[^a-zA-ZÀ-ÿ]/gu,'').length;}
const rules={titre:{validate:v=>v.trim()!==''&&getLettersCountJS(v)>=3,message:'Titre valide.',error:'Le titre doit contenir au moins 3 lettres.'},type_post:{validate:v=>v!=='',message:'Type valide.',error:'Veuillez choisir le type du post.'},contenu:{validate:v=>v.trim().length>=5,message:'Description valide.',error:'La description doit contenir au moins 5 caractères.'}};
function setError(field,msg){field.classList.add('field-invalid');field.classList.remove('field-valid-input');const eb=document.getElementById('err-'+field.id);if(eb){eb.textContent=msg;eb.style.color='#dc2626';eb.className='field-error';}}
function setValid(field,msg){field.classList.remove('field-invalid');field.classList.add('field-valid-input');const eb=document.getElementById('err-'+field.id);if(eb){eb.textContent=msg;eb.style.color='#22a559';eb.className='field-valid';}}
function validateField(field){const rule=rules[field.id];if(!rule) return true;const v=field.value.trim();if(v===''){setError(field,rule.error);return false;}if(!rule.validate(field.value)){setError(field,rule.error);return false;}setValid(field,rule.message);return true;}
Object.keys(rules).forEach(id=>{const f=document.getElementById(id);if(!f) return;f.addEventListener('input',()=>validateField(f));f.addEventListener('change',()=>validateField(f));f.addEventListener('blur',()=>validateField(f));});

if(imageField){imageField.addEventListener('change',function(){const eb=document.getElementById('err-image');this.classList.remove('field-invalid');eb.textContent='';const file=this.files[0];if(!file){previewBox.classList.remove('show');previewImg.src='';return;}if(!['image/jpeg','image/png','image/webp','image/gif'].includes(file.type)){this.classList.add('field-invalid');eb.textContent='Formats image autorisés : JPG, JPEG, PNG, WEBP, GIF.';previewBox.classList.remove('show');previewImg.src='';return;}if(file.size>5*1024*1024){this.classList.add('field-invalid');eb.textContent="L'image ne doit pas dépasser 5 Mo.";previewBox.classList.remove('show');previewImg.src='';return;}const reader=new FileReader();reader.onload=function(e){previewImg.src=e.target.result;previewBox.classList.add('show');if(currentImageBox) currentImageBox.classList.remove('show');eb.textContent='Image valide.';eb.className='field-valid';};reader.readAsDataURL(file);});}
if(videoField){videoField.addEventListener('change',function(){const eb=document.getElementById('err-video');this.classList.remove('field-invalid');eb.textContent='';const file=this.files[0];if(!file){videoPreviewBox.classList.remove('show');previewVideo.src='';return;}if(!['video/mp4','video/webm','video/ogg'].includes(file.type)){this.classList.add('field-invalid');eb.textContent='Formats vidéo autorisés : MP4, WEBM, OGG.';videoPreviewBox.classList.remove('show');previewVideo.src='';return;}if(file.size>25*1024*1024){this.classList.add('field-invalid');eb.textContent="La vidéo ne doit pas dépasser 25 Mo.";videoPreviewBox.classList.remove('show');previewVideo.src='';return;}const url=URL.createObjectURL(file);previewVideo.src=url;previewVideo.load();previewVideo.play().catch(()=>{});videoPreviewBox.classList.add('show');if(currentVideoBox) currentVideoBox.classList.remove('show');eb.textContent='Vidéo valide.';eb.className='field-valid';});}
if(postForm){postForm.addEventListener('submit',function(e){let ok=true;Object.keys(rules).forEach(id=>{const f=document.getElementById(id);if(f&&!validateField(f)) ok=false;});if(!ok){e.preventDefault();openModal();}});}

/* ============================================================ MENUS 3 POINTS POST */
function togglePostMenu(postId){document.querySelectorAll('.post-dropdown').forEach(m=>{if(m.id!=='post-menu-'+postId) m.classList.remove('show');});const t=document.getElementById('post-menu-'+postId);if(t) t.classList.toggle('show');}
document.addEventListener('click',function(e){if(!e.target.closest('.post-menu-wrap')&&!e.target.closest('.comment-menu-wrap')){document.querySelectorAll('.post-dropdown').forEach(m=>m.classList.remove('show'));document.querySelectorAll('.comment-dropdown').forEach(m=>m.classList.remove('show'));}});

/* ============================================================ MENUS COMMENTAIRE */
function toggleCommentMenu(menuId){document.querySelectorAll('.comment-dropdown').forEach(m=>{if(m.id!==menuId) m.classList.remove('show');});const m=document.getElementById(menuId);if(m) m.classList.toggle('show');}
function startEditComment(commentId,postId){document.querySelectorAll('.comment-dropdown').forEach(m=>m.classList.remove('show'));const td=document.getElementById('comment-text-'+commentId),ez=document.getElementById('comment-edit-zone-'+commentId);if(td) td.style.display='none';if(ez) ez.style.display='block';const inp=document.getElementById('comment-edit-input-'+commentId);if(inp){inp.focus();inp.setSelectionRange(inp.value.length,inp.value.length);}}
function cancelEditComment(commentId){const td=document.getElementById('comment-text-'+commentId),ez=document.getElementById('comment-edit-zone-'+commentId);if(td) td.style.display='';if(ez) ez.style.display='none';}
function deleteComment(commentId,postId,parentId){document.querySelectorAll('.comment-dropdown').forEach(m=>m.classList.remove('show'));const msg=parentId===0?'Supprimer ce commentaire et toutes ses réponses ?':'Supprimer cette réponse ?';if(!confirm(msg)) return;document.getElementById('deleteCommentId').value=commentId;document.getElementById('deleteCommentPostId').value=postId;document.getElementById('deleteCommentParentId').value=parentId;document.getElementById('deleteCommentForm').submit();}

/* ============================================================ MODAL SIGNALER COMMENTAIRE */
function openReportCommentModal(commentId,postId){document.querySelectorAll('.comment-dropdown').forEach(m=>m.classList.remove('show'));document.getElementById('report-comment-id').value=commentId;document.getElementById('report-comment-post-id').value=postId;document.getElementById('reportCommentModal').classList.add('show');}
function closeReportCommentModal(){document.getElementById('reportCommentModal').classList.remove('show');const f=document.getElementById('reportCommentForm');if(f) f.reset();}
document.getElementById('reportCommentModal').addEventListener('click',function(e){if(e.target===this) closeReportCommentModal();});

/* ============================================================ TOGGLE COMMENT BOX */
function toggleCommentBox(postId){const box=document.getElementById('comment-box-'+postId);if(box) box.classList.toggle('hidden');}

/* ============================================================ MODAL SIGNALER POST */
function openReportModal(postId){document.getElementById('report-post-id').value=postId;document.getElementById('reportModal').style.display='flex';}
function closeReportModal(){document.getElementById('reportModal').style.display='none';const f=document.getElementById('reportModal').querySelector('form');if(f) f.reset();}

/* ============================================================ VIEWER COMMENT FORM */
const viewerCommentForm=document.getElementById('viewerCommentForm');
if(viewerCommentForm){viewerCommentForm.addEventListener('submit',function(e){const pf=document.getElementById('viewerPostId'),cf=document.getElementById('viewerCommentContent'),eb=document.getElementById('err-viewerCommentContent'),postId=pf?pf.value.trim():'',content=cf?cf.value.trim():'';if(!postId){e.preventDefault();if(eb) eb.textContent='Post introuvable.';return;}if(content===''){ e.preventDefault();if(eb) eb.textContent='Le commentaire est obligatoire.';return;}if(content!==''&&content.replace(/[^a-zA-ZÀ-ÿ]/gu,'').length<5){e.preventDefault();if(eb) eb.textContent='Le commentaire doit contenir au moins 5 lettres.';return;}if(eb) eb.textContent='';});}
const vci=document.getElementById('viewerCommentImage');
if(vci){vci.addEventListener('change',function(){const preview=document.getElementById('viewerCommentPreview'),img=preview?preview.querySelector('img'):null,eb=document.getElementById('err-viewerCommentContent'),file=this.files[0];if(!preview||!img) return;if(!file){preview.classList.remove('show');img.src='';return;}if(!['image/jpeg','image/png','image/webp','image/gif'].includes(file.type)){if(eb) eb.textContent='Formats image commentaire autorisés : JPG, JPEG, PNG, WEBP.';this.value='';preview.classList.remove('show');img.src='';return;}if(file.size>3*1024*1024){if(eb) eb.textContent="L'image du commentaire ne doit pas dépasser 3 Mo.";this.value='';preview.classList.remove('show');img.src='';return;}const reader=new FileReader();reader.onload=function(ev){img.src=ev.target.result;preview.classList.add('show');if(eb&&eb.textContent.includes('image')) eb.textContent='';};reader.readAsDataURL(file);});}
const vCBtn=document.getElementById('viewerCommentBtn');if(vCBtn) vCBtn.addEventListener('click',function(){const f=document.getElementById('viewerCommentContent');if(f) f.focus();});

/* ============================================================ VALIDATION COMMENTAIRES */
function validateComment(textareaId,errorId){const ta=document.getElementById(textareaId),eb=document.getElementById(errorId);if(!ta) return true;const text=ta.value.trim(),letters=text.replace(/[^a-zA-ZÀ-ÿ]/gu,'');if(text===''){if(eb){eb.textContent='Le commentaire est obligatoire.';eb.style.display='block';} return false;}if(letters.length<5){if(eb){eb.textContent='Le commentaire doit contenir au moins 5 lettres.';eb.style.display='block';} return false;}if(eb){eb.textContent='';eb.style.display='none';} return true;}
function validateEditCommentForm(form){const ta=form.querySelector('textarea[name="comment_content"]'),eb=form.querySelector('.field-error'),imgInp=form.querySelector('input[name="comment_image"]');if(!ta) return true;const text=ta.value.trim(),letters=text.replace(/[^a-zA-ZÀ-ÿ]/gu,'');if(text===''){if(eb){eb.textContent='Le commentaire est obligatoire.';eb.style.display='block';eb.style.color='#dc2626';}ta.classList.add('field-invalid');ta.classList.remove('field-valid-input');return false;}if(letters.length<5){if(eb){eb.textContent='Le commentaire doit contenir au moins 5 lettres.';eb.style.display='block';eb.style.color='#dc2626';}ta.classList.add('field-invalid');ta.classList.remove('field-valid-input');return false;}if(imgInp&&imgInp.files&&imgInp.files.length>0){const f=imgInp.files[0];if(!['image/jpeg','image/png','image/webp','image/gif'].includes(f.type)){if(eb){eb.textContent='Formats image autorisés : JPG, JPEG, PNG, WEBP, GIF.';eb.style.display='block';eb.style.color='#dc2626';} return false;}if(f.size>3*1024*1024){if(eb){eb.textContent="L'image ne doit pas dépasser 3 Mo.";eb.style.display='block';eb.style.color='#dc2626';} return false;}}if(eb){eb.textContent='Commentaire valide.';eb.style.display='block';eb.style.color='#22a559';}ta.classList.remove('field-invalid');ta.classList.add('field-valid-input');return true;}
document.addEventListener('input',function(e){const ta=e.target.closest('.comment-edit-form textarea[name="comment_content"]');if(!ta) return;validateEditCommentForm(ta.closest('.comment-edit-form'));});
document.addEventListener('change',function(e){const ii=e.target.closest('.comment-edit-form input[name="comment_image"]');if(!ii) return;validateEditCommentForm(ii.closest('.comment-edit-form'));});
document.addEventListener('submit',function(e){const form=e.target.closest('.comment-edit-form');if(!form) return;if(!validateEditCommentForm(form)) e.preventDefault();});
document.addEventListener('DOMContentLoaded',()=>{document.querySelectorAll('form[method="POST"]').forEach(form=>{const ta=form.querySelector('textarea[name="comment_content"]');if(!ta) return;const eid=ta.id?'err-'+ta.id:null;if(ta.id&&eid){ta.addEventListener('blur',()=>validateComment(ta.id,eid));ta.addEventListener('keyup',()=>validateComment(ta.id,eid));}form.addEventListener('submit',event=>{if(!ta||!ta.id||!eid) return;if(!validateComment(ta.id,eid)) event.preventDefault();});});});

/* Boutons Répondre */
document.addEventListener('click',function(e){const btn=e.target.closest('.reply-btn');if(!btn) return;const rootId=btn.dataset.rootId,holderId=btn.dataset.holderId,author=btn.dataset.author||'Utilisateur';if(!rootId||!holderId) return;const box=document.getElementById('reply-box-'+rootId),holder=document.getElementById(holderId);if(!box||!holder) return;const ta=document.getElementById('reply-content-'+rootId),emojiInp=document.getElementById('emoji-reply-'+rootId),imgInp=document.getElementById('reply-img-'+rootId),eb=document.getElementById('err-reply-content-'+rootId);const already=box.parentElement===holder&&box.style.display==='block';document.querySelectorAll('.reply-box').forEach(b=>b.style.display='none');if(already){box.style.display='none';return;}holder.appendChild(box);box.style.display='block';if(ta){ta.value='@'+author+' ';ta.focus();ta.setSelectionRange(ta.value.length,ta.value.length);}if(emojiInp){emojiInp.value='';emojiInp.classList.remove('field-invalid','field-valid-input');}if(imgInp) imgInp.value='';if(eb){eb.textContent='';eb.style.display='none';}});

/* ============================================================ AUTO-OPEN */
<?php if($isEditMode||array_filter($errors)): ?>openModal();<?php endif; ?>
if(localStorage.getItem('openForumModal')==='1'){openModal();localStorage.removeItem('openForumModal');}
<?php if(isset($_GET['open_post'])&&ctype_digit((string)$_GET['open_post'])): ?>
window.addEventListener('load',function(){const postId=<?php echo (int)$_GET['open_post']; ?>;const box=document.getElementById('comment-box-'+postId),pc=document.getElementById('post-'+postId);if(box){box.classList.remove('hidden');box.classList.add('show');}if(pc) pc.scrollIntoView({behavior:'smooth',block:'start'});<?php if(isset($_GET['open_comment'])&&ctype_digit((string)$_GET['open_comment'])&&(int)$_GET['open_comment']>0): ?>const rb=document.getElementById('reply-box-<?php echo (int)$_GET['open_comment']; ?>');if(rb) rb.style.display='block';<?php endif; ?>});
<?php endif; ?>

/* ============================================================ POPUP COMPACT COMMENT */
function openViewerCommentBox(){const expanded=document.getElementById('viewerCommentExpanded'),mini=document.getElementById('viewerCommentMini'),ta=document.getElementById('viewerCommentContent');if(expanded) expanded.classList.add('show');if(mini) mini.style.display='none';if(ta) setTimeout(()=>ta.focus(),80);}
function closeViewerCommentBox(){const mini=document.getElementById('viewerCommentMini'),expanded=document.getElementById('viewerCommentExpanded'),ta=document.getElementById('viewerCommentContent'),emoji=document.getElementById('viewerEmojiHidden'),img=document.getElementById('viewerCommentImage'),preview=document.getElementById('viewerCommentPreview'),error=document.getElementById('err-viewerCommentContent'),pf=document.getElementById('viewerParentId');viewerSetAddMode();if(expanded) expanded.classList.remove('show');if(mini){mini.style.display='flex';const sp=mini.querySelector('span');if(sp) sp.textContent='Écrire un commentaire...';}if(ta){ta.value='';ta.placeholder='Écrire un commentaire...';}if(emoji) emoji.value='';if(img) img.value='';if(preview) preview.classList.remove('show');if(error) error.textContent='';if(pf) pf.value='';}
function setViewerReplyTarget(commentId,authorName){if(isVideoCommentsOpen()) return setVideoReplyTarget(commentId,authorName);const pf=document.getElementById('viewerParentId'),ta=document.getElementById('viewerCommentContent'),mini=document.getElementById('viewerCommentMini');viewerSetAddMode();if(pf) pf.value=commentId;openViewerCommentBox();if(ta){ta.value='@'+authorName+' ';ta.placeholder='@'+authorName+', votre réponse...';ta.focus();ta.setSelectionRange(ta.value.length,ta.value.length);}if(mini&&mini.querySelector('span')) mini.querySelector('span').textContent='Répondre à @'+authorName;}
function viewerSetAddMode(){const form=document.getElementById('viewerCommentForm');if(!form) return;const ai=form.querySelector('input[name="update_comment"]');if(ai) ai.remove();const ci=form.querySelector('#viewerEditCommentId');if(ci) ci.remove();let ad=form.querySelector('input[name="add_comment"]');if(!ad){ad=document.createElement('input');ad.type='hidden';ad.name='add_comment';ad.value='1';form.prepend(ad);}const sb=form.querySelector('.viewer-publish-btn');if(sb) sb.textContent='Publier';}
function viewerSetEditMode(commentId){const form=document.getElementById('viewerCommentForm');if(!form) return;const ad=form.querySelector('input[name="add_comment"]');if(ad) ad.remove();let ai=form.querySelector('input[name="update_comment"]');if(!ai){ai=document.createElement('input');ai.type='hidden';ai.name='update_comment';ai.value='1';form.prepend(ai);}let ci=form.querySelector('#viewerEditCommentId');if(!ci){ci=document.createElement('input');ci.type='hidden';ci.name='comment_id';ci.id='viewerEditCommentId';form.appendChild(ci);}ci.value=commentId;const sb=form.querySelector('.viewer-publish-btn');if(sb) sb.textContent='Mettre à jour';}
function startViewerEditComment(commentId,postId,content,emoji){if(isVideoCommentsOpen()) return startVideoEditComment(commentId,postId,content,emoji);const pf=document.getElementById('viewerPostId'),ta=document.getElementById('viewerCommentContent'),ei=document.getElementById('viewerEmojiHidden'),mini=document.getElementById('viewerCommentMini');viewerSetEditMode(commentId);if(pf) pf.value=postId;document.getElementById('viewerParentId').value='';openViewerCommentBox();if(ta){ta.value=content||'';ta.placeholder='Modifier votre commentaire...';ta.focus();ta.setSelectionRange(ta.value.length,ta.value.length);}if(ei) ei.value=emoji||'';if(mini&&mini.querySelector('span')) mini.querySelector('span').textContent='Modifier le commentaire';document.querySelectorAll('.viewer-comment-dropdown').forEach(m=>m.classList.remove('show'));}
function toggleViewerCommentMenu(menuId){document.querySelectorAll('.viewer-comment-dropdown').forEach(m=>{if(m.id!==menuId) m.classList.remove('show');});const m=document.getElementById(menuId);if(m) m.classList.toggle('show');}
function viewerDeleteComment(commentId,postId,parentId){document.querySelectorAll('.viewer-comment-dropdown').forEach(m=>m.classList.remove('show'));if(!confirm(parentId===0?'Supprimer ce commentaire et toutes ses réponses ?':'Supprimer cette réponse ?')) return;document.getElementById('deleteCommentId').value=commentId;document.getElementById('deleteCommentPostId').value=postId;document.getElementById('deleteCommentParentId').value=parentId;document.getElementById('deleteCommentForm').submit();}
function viewerReportComment(commentId,postId){document.querySelectorAll('.viewer-comment-dropdown').forEach(m=>m.classList.remove('show'));openReportCommentModal(commentId,postId);}
document.addEventListener('click',function(e){if(!e.target.closest('.viewer-comment-menu-btn')&&!e.target.closest('.viewer-comment-dropdown')) document.querySelectorAll('.viewer-comment-dropdown').forEach(m=>m.classList.remove('show'));});
document.addEventListener('DOMContentLoaded',function(){const mini=document.getElementById('viewerCommentMini'),cancelBtn=document.getElementById('viewerCancelCommentBtn');if(mini) mini.addEventListener('click',function(){viewerSetAddMode();openViewerCommentBox();});if(cancelBtn) cancelBtn.addEventListener('click',closeViewerCommentBox);});

/* ============================================================ FORMAT VIEWER COMMENTS */
function viewerEscapeHtml(t){const d=document.createElement('div');d.textContent=t||'';return d.innerHTML;}
function viewerEscapeJs(t){return String(t||'').replace(/\\/g,'\\\\').replace(/'/g,"\\'").replace(/"/g,'&quot;').replace(/\n/g,'\\n').replace(/\r/g,'');}
function viewerCommentMenuHtml(id,postId,parentId,isOwner,content,emoji){
    const menuId=(parentId>0?'viewer-cmenu-reply-':'viewer-cmenu-')+id;
    if(isOwner){
        return `<div class="viewer-comment-menu-wrap"><button type="button" class="viewer-comment-menu-btn" onclick="toggleViewerCommentMenu('${menuId}')">⋯</button><div class="viewer-comment-dropdown" id="${menuId}"><button type="button" onclick="startViewerEditComment(${id},${postId},'${content}','${emoji}')">✏️ Modifier</button><button type="button" class="danger" onclick="viewerDeleteComment(${id},${postId},${parentId})">🗑 Supprimer</button></div></div>`;
    }
    return `<div class="viewer-comment-menu-wrap"><button type="button" class="viewer-comment-menu-btn" onclick="toggleViewerCommentMenu('${menuId}')">⋯</button><div class="viewer-comment-dropdown" id="${menuId}"><button type="button" onclick="viewerReportComment(${id},${postId})">🚩 Signaler</button></div></div>`;
}
function formatViewerComments(comments){
    const rootComments=[],repliesByParent={},currentPostId=document.getElementById('viewerPostId')?Number(document.getElementById('viewerPostId').value||0):Number((currentViewerPostData&&currentViewerPostData.id)||0);
    (Array.isArray(comments)?comments:[]).forEach(c=>{const pid=c.id_parent_commentaire?Number(c.id_parent_commentaire):0;if(pid>0){if(!repliesByParent[pid]) repliesByParent[pid]=[];repliesByParent[pid].push(c);}else rootComments.push(c);});
    return rootComments.map(comment=>{
        const cid=Number(comment.id_commentaire||0),author=`${comment.prenom||''} ${comment.nom||''}`.trim()||'Utilisateur',aL=author.charAt(0).toUpperCase(),content=viewerEscapeHtml(comment.contenu_commentaire||''),rawContent=viewerEscapeJs(comment.contenu_commentaire||''),emoji=viewerEscapeHtml(comment.emoji_commentaire||''),rawEmoji=viewerEscapeJs(comment.emoji_commentaire||''),time=viewerEscapeHtml(comment.date_commentaire||''),img=comment.image_commentaire?`${FORUM_APP_ROOT}/${String(comment.image_commentaire).replace(/^\/+/,'')}`:'',safeAJ=viewerEscapeJs(author),replies=repliesByParent[cid]||[],isOwner=Number(comment.id_user||0)===CURRENT_FORUM_USER_ID;
        const menuHtml=viewerCommentMenuHtml(cid,currentPostId,0,isOwner,rawContent,rawEmoji);
        const repliesHtml=replies.map(reply=>{const rid=Number(reply.id_commentaire||0),ra=`${reply.prenom||''} ${reply.nom||''}`.trim()||'Utilisateur',rL=ra.charAt(0).toUpperCase(),rc=viewerEscapeHtml(reply.contenu_commentaire||''),rrc=viewerEscapeJs(reply.contenu_commentaire||''),re=viewerEscapeHtml(reply.emoji_commentaire||''),rre=viewerEscapeJs(reply.emoji_commentaire||''),ri=reply.image_commentaire?`${FORUM_APP_ROOT}/${String(reply.image_commentaire).replace(/^\/+/,'')}`:'',sraJ=viewerEscapeJs(ra),rOwner=Number(reply.id_user||0)===CURRENT_FORUM_USER_ID,rMenu=viewerCommentMenuHtml(rid,currentPostId,cid,rOwner,rrc,rre);return `<div class="viewer-reply-item"><div class="mini-avatar">${rL}</div><div class="viewer-comment-content"><div class="viewer-reply-bubble viewer-comment-bubble"><div class="viewer-comment-head-row"><span class="viewer-comment-author">${viewerEscapeHtml(ra)}</span>${rMenu}</div>${rc?`<div>${rc}</div>`:''}${re?`<div style="margin-top:6px;">${re}</div>`:''}${ri?`<div style="margin-top:8px;"><img src="${ri}" style="max-width:180px;border-radius:10px;"></div>`:''}</div><div class="viewer-comment-meta"><span>${viewerEscapeHtml(reply.date_commentaire||'')}</span><button type="button" class="viewer-reply-btn" onclick="setViewerReplyTarget(${cid},'${sraJ}')">Répondre</button></div></div></div>`;}).join('');
        return `<div class="viewer-comment-item"><div class="mini-avatar">${aL}</div><div class="viewer-comment-content"><div class="viewer-comment-bubble"><div class="viewer-comment-head-row"><span class="viewer-comment-author">${viewerEscapeHtml(author)}</span>${menuHtml}</div>${content?`<div>${content}</div>`:''}${emoji?`<div style="margin-top:6px;">${emoji}</div>`:''}${img?`<div style="margin-top:8px;"><img src="${img}" style="max-width:220px;border-radius:10px;"></div>`:''}</div><div class="viewer-comment-meta"><span>${time}</span><button type="button" class="viewer-reply-btn" onclick="setViewerReplyTarget(${cid},'${safeAJ}')">Répondre</button></div>${replies.length?`<div class="viewer-replies">${repliesHtml}</div>`:''}</div></div>`;
    }).join('');
}

/* ============================================================ EMOJI ONLY VALIDATION */
function isEmojiOnly(value){const t=value.trim();if(t==='') return true;const w=t.replace(/[\p{Emoji_Presentation}\p{Extended_Pictographic}\uFE0F\u200D]/gu,'');return w==='';}
function validateEmojiOnlyInput(input){if(!input) return true;input.value=input.value.replace(/\s+/g,'');let eb=null;if(input.id==='emoji_post') eb=document.getElementById('err-emoji_post');else{const form=input.closest('form');if(form) eb=form.querySelector('.emoji-error');}if(!eb&&input.parentElement) eb=input.parentElement.querySelector('.emoji-error');if(!eb){eb=document.createElement('span');eb.className='field-error emoji-error';input.insertAdjacentElement('afterend',eb);}if(input.value.trim()!==''&&!isEmojiOnly(input.value)){input.classList.add('field-invalid');input.classList.remove('field-valid-input');eb.textContent='Uniquement des emojis.';eb.style.display='block';eb.style.color='#dc2626';return false;}input.classList.remove('field-invalid');if(input.value.trim()!==''){input.classList.add('field-valid-input');eb.textContent='Emoji valide.';eb.style.display='block';eb.style.color='#22a559';}else{input.classList.remove('field-valid-input');eb.textContent='';eb.style.display='none';}return true;}
document.addEventListener('input',function(e){const inp=e.target.closest('#emoji_post,input[name="emoji_content"],.viewer-emoji-input,.comment-emoji-input,.forum-emoji-input');if(!inp) return;validateEmojiOnlyInput(inp);});
document.addEventListener('paste',function(e){const inp=e.target.closest('#emoji_post,input[name="emoji_content"],.viewer-emoji-input,.comment-emoji-input,.forum-emoji-input');if(!inp) return;setTimeout(()=>validateEmojiOnlyInput(inp),10);});
document.addEventListener('submit',function(e){const inputs=e.target.querySelectorAll('#emoji_post,input[name="emoji_content"],.viewer-emoji-input,.comment-emoji-input,.forum-emoji-input');let ok=true;inputs.forEach(inp=>{if(!validateEmojiOnlyInput(inp)) ok=false;});if(!ok) e.preventDefault();});

/* ============================================================ AUTO PLAY VIDEOS */
const autoFeedVideos=document.querySelectorAll('.auto-feed-video');
if('IntersectionObserver' in window){const obs=new IntersectionObserver(function(entries){entries.forEach(function(entry){const v=entry.target;if(entry.isIntersecting){v.muted=true;v.play().catch(()=>{});}else v.pause();});},{threshold:0.35});autoFeedVideos.forEach(function(v){v.muted=true;v.playsInline=true;obs.observe(v);});}

/* ============================================================ GOOGLE TRANSLATE */
function googleTranslateElementInit(){new google.translate.TranslateElement({pageLanguage:'fr',includedLanguages:'fr,en,ar,es,it,de,tr,pt,ru,zh-CN,ja,ko',autoDisplay:false},'google_translate_element');hideGoogleTranslateBar();}
function toggleIgLangMenu(){const m=document.getElementById('igLangMenu');if(m) m.classList.toggle('show');}
function setGoogleTranslateCookie(lang){const v='/fr/'+lang;document.cookie='googtrans='+v+';path=/';document.cookie='googtrans='+v+';domain='+location.hostname+';path=/';}
function hideGoogleTranslateBar(){document.documentElement.style.marginTop='0px';document.body.style.top='0px';document.body.style.position='static';document.querySelectorAll('iframe.goog-te-banner-frame,iframe.skiptranslate,.goog-te-banner-frame,body>.skiptranslate').forEach(function(el){el.style.display='none';el.style.visibility='hidden';el.style.height='0px';});}
function changeForumLang(lang){setGoogleTranslateCookie(lang);const m=document.getElementById('igLangMenu');if(m) m.classList.remove('show');let tries=0;const t=setInterval(function(){hideGoogleTranslateBar();const sel=document.querySelector('.goog-te-combo');if(sel){sel.value=lang;sel.dispatchEvent(new Event('change'));clearInterval(t);setTimeout(hideGoogleTranslateBar,400);setTimeout(hideGoogleTranslateBar,1000);}tries++;if(tries>20){clearInterval(t);window.location.reload();}},200);}
document.addEventListener('click',function(e){if(!e.target.closest('.ig-lang-wrap')){const m=document.getElementById('igLangMenu');if(m) m.classList.remove('show');}});
window.addEventListener('load',hideGoogleTranslateBar);
setInterval(hideGoogleTranslateBar,700);
function placeLanguageButtonUnderFooter(){const zone=document.querySelector('.ig-lang-zone');if(!zone) return;const footer=document.querySelector('.site-footer');if(footer&&zone.previousElementSibling!==footer) footer.insertAdjacentElement('afterend',zone);else if(!footer&&document.body&&zone.parentElement!==document.body) document.body.appendChild(zone);zone.classList.add('ig-lang-ready');}
document.addEventListener('DOMContentLoaded',placeLanguageButtonUnderFooter);
window.addEventListener('load',placeLanguageButtonUnderFooter);

/* ============================================================ ADVANCED SHARE - CORRIGÉ */
let currentSharePostId=0,currentSharePostTitle='Post GoService',currentSharePostContent='',currentSharePostUser='Utilisateur',currentSharePostUrl='',currentSharePostImage='',currentSharePostVideo='',currentShareThumbUrl='';

function buildForumPostUrl(postId){
    const PUBLIC_BASE = 'https://operate-pronto-jogging.ngrok-free.dev';
    return FORUM_INDEX_URL + '&open_post='
        + postId
        + '&v=' + Date.now()
        + '#post-' + postId;
}
function showAdvancedShareToast(message){const toast=document.getElementById('advancedShareToast');if(!toast) return;toast.textContent=message||'OK';toast.classList.add('show');clearTimeout(window.__astt);window.__astt=setTimeout(()=>toast.classList.remove('show'),2500);}

function shortText(text,max){max=max||130;text=String(text||'').replace(/\s+/g,' ').trim();return text.length>max?text.slice(0,max-3)+'...':text;}

function openAdvancedShareModalFromButton(btn){openAdvancedShareModal({id:btn.dataset.postId,title:btn.dataset.postTitle,content:btn.dataset.postContent,user:btn.dataset.postUser,image:btn.dataset.postImage,video:btn.dataset.postVideo});}

function openAdvancedShareModal(data){
    data=data||{};
    currentSharePostId=Number(data.id||0);currentSharePostTitle=data.title||'Post GoService';currentSharePostContent=data.content||'';currentSharePostUser=data.user||'Utilisateur';currentSharePostImage=data.image||'';currentSharePostVideo=data.video||'';currentShareThumbUrl='';
    currentSharePostUrl=buildForumPostUrl(currentSharePostId);
    const modal=document.getElementById('advancedShareModal'),title=document.getElementById('advancedSharePreviewTitle'),desc=document.getElementById('advancedSharePreviewDesc'),user=document.getElementById('advancedShareUser'),avatar=document.getElementById('advancedShareAvatar'),media=document.getElementById('advancedSharePreviewMedia'),caption=document.getElementById('advancedShareCaption'),note=document.getElementById('advancedShareNote');
    if(title) title.textContent=currentSharePostTitle;
    if(desc) desc.textContent=shortText(currentSharePostContent);
    if(user) user.textContent=currentSharePostUser;
    if(avatar) avatar.textContent=(currentSharePostUser||'U').charAt(0).toUpperCase();
    if(caption) caption.value='';
    const emojiInput=document.getElementById('advancedShareEmoji');
    if(emojiInput) emojiInput.value='';
    if(note) note.textContent='';
    if(media){
        media.innerHTML='';
        if(currentSharePostImage){
            const img=document.createElement('img');img.src=currentSharePostImage;img.alt=currentSharePostTitle;media.appendChild(img);currentShareThumbUrl=currentSharePostImage;
        }else if(currentSharePostVideo){
            /* Capture miniature vidéo via canvas */
            const tmpV=document.createElement('video');tmpV.src=currentSharePostVideo;tmpV.crossOrigin='anonymous';tmpV.muted=true;tmpV.playsInline=true;tmpV.style.display='none';document.body.appendChild(tmpV);
            tmpV.addEventListener('loadeddata',()=>{tmpV.currentTime=0.5;});
            tmpV.addEventListener('seeked',()=>{const canvas=document.createElement('canvas');canvas.width=tmpV.videoWidth||640;canvas.height=tmpV.videoHeight||360;canvas.getContext('2d').drawImage(tmpV,0,0,canvas.width,canvas.height);const thumbUrl=canvas.toDataURL('image/jpeg',0.85);document.body.removeChild(tmpV);currentShareThumbUrl=thumbUrl;
                const wrapper=document.createElement('div');wrapper.className='thumb-wrapper';const ti=document.createElement('img');ti.src=thumbUrl;wrapper.appendChild(ti);const pi=document.createElement('div');pi.className='thumb-play';pi.textContent='▶';wrapper.appendChild(pi);media.appendChild(wrapper);});
            tmpV.addEventListener('error',()=>{try{document.body.removeChild(tmpV);}catch(ex){}media.textContent='🎥 Vidéo';});
            tmpV.load();
        }else{media.textContent='GoService Forum';}
    }
    if(modal) modal.classList.add('show');
}

function closeAdvancedShareModal(){const m=document.getElementById('advancedShareModal');if(m) m.classList.remove('show');}

async function registerAdvancedShare(mode){
    if(!currentSharePostId) return {success:false, skipped:true};
    try{
        const caption=document.getElementById('advancedShareCaption');
        const emojiInput=document.getElementById('advancedShareEmoji');
        const body=new URLSearchParams();
        body.append('share_post_ajax','1');
        body.append('post_id',String(currentSharePostId));
        body.append('share_mode',mode||'external');
        body.append('description_share',caption&&caption.value.trim()?caption.value.trim():'');
        body.append('emoji_share',emojiInput&&emojiInput.value.trim()?emojiInput.value.trim():'');
        const resp=await fetch(window.location.href,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:body.toString()});
        const raw=await resp.text();
        let data=null;
        try{data=JSON.parse(raw);}catch(parseErr){data={success:resp.ok, parse_error:true};}
        if(data&&typeof data.shares_count!=='undefined'){
            const cnt=document.getElementById('share-count-'+currentSharePostId);
            if(cnt) cnt.textContent=data.shares_count;
        }
        return data||{success:resp.ok};
    }catch(e){
        return {success:false, network_error:true};
    }
}


async function copyToClipboard(text){try{await navigator.clipboard.writeText(text);return true;}catch(e){const tmp=document.createElement('textarea');tmp.value=text;tmp.style.cssText='position:fixed;left:-9999px;top:-9999px;';document.body.appendChild(tmp);tmp.focus();tmp.select();const ok=document.execCommand('copy');document.body.removeChild(tmp);return ok;}}

function buildShareText(withUrl){
    const caption=document.getElementById('advancedShareCaption');
    const extra=caption&&caption.value.trim()?caption.value.trim()+'\n\n':'';
    const lines=['*'+currentSharePostUser+'*','GoService Forum',''];
    if(currentSharePostTitle) lines.push('*'+currentSharePostTitle+'*');
    if(currentSharePostContent) lines.push(shortText(currentSharePostContent,200));
    if(withUrl!==false){lines.push('');lines.push(currentSharePostUrl);}
    return extra+lines.join('\n');
}

async function sharePostAdvanced(platform){
    if(!currentSharePostId) return;
    const note=document.getElementById('advancedShareNote');
    if(note) note.textContent='';

    if(platform==='internal'){
        const data=await registerAdvancedShare('internal');
        closeAdvancedShareModal();
        if(data&&data.success){
            showAdvancedShareToast('Post partagé sur GoService ✅');
            const newId=data.new_post_id||currentSharePostId;
            setTimeout(()=>{window.location.href=FORUM_INDEX_URL+'&open_post='+encodeURIComponent(newId)+'#post-'+encodeURIComponent(newId);},650);
        }else{
            showAdvancedShareToast('Partage GoService impossible');
        }
        return;
    }

    // On enregistre le partage, mais on ne bloque jamais Facebook/WhatsApp/Copie
    // si l'enregistrement AJAX échoue. Cela supprime le faux message d'erreur.
    await registerAdvancedShare('external');

    if(platform==='whatsapp'){
        const txt=buildShareText(true);
        window.open('https://wa.me/?text='+encodeURIComponent(txt),'_blank');
        closeAdvancedShareModal();
        showAdvancedShareToast('Partage WhatsApp ouvert ✅');
        return;
    }

    if(platform==='facebook'){
        const fbUrl=buildForumPostUrl(currentSharePostId);
        window.open('https://www.facebook.com/sharer/sharer.php?u='+encodeURIComponent(fbUrl),'_blank','width=900,height=700,scrollbars=yes');
        closeAdvancedShareModal();
        showAdvancedShareToast('Partage Facebook ouvert ✅');
        return;
    }

    if(platform==='copy'){
        const ok=await copyToClipboard(currentSharePostUrl);
        closeAdvancedShareModal();
        showAdvancedShareToast(ok?'Lien copié ✅':'Copie impossible');
        return;
    }
}

document.addEventListener('click',function(e){const m=document.getElementById('advancedShareModal');if(m&&e.target===m) closeAdvancedShareModal();});

</script>
<button type="button" class="goservice-chatbot-btn" id="chatbotBtn">🤖</button>

<div class="goservice-chatbot-box" id="chatbotBox">
    <div class="chatbot-head">
        <span>Assistant GoService</span>
        <button type="button" id="chatbotClose">×</button>
    </div>

    <div class="chatbot-messages" id="chatbotMessages">
        <div class="chat-msg bot">Bonjour 👋 Je peux vous aider sur GoService.</div>
    </div>

    <form class="chatbot-form" id="chatbotForm">
        <input type="text" id="chatbotInput" placeholder="Écrire un message...">
        <button type="submit">➤</button>
    </form>
</div>

<style>
.goservice-chatbot-btn{
    position:fixed;
    right:28px;
    bottom:28px;
    width:68px;
    height:68px;
    border-radius:50%;
    border:none;
    cursor:pointer;
    z-index:999999;
    background:linear-gradient(135deg,#EE5828,#142738,#4CAF50);
    color:#fff;
    font-size:30px;
    box-shadow:0 18px 40px rgba(0,0,0,.25);

    display:flex;
    align-items:center;
    justify-content:center;

    animation:chatbotFloat 2.5s ease-in-out infinite;
    transition:
        transform .25s ease,
        box-shadow .25s ease,
        filter .25s ease;
    overflow:visible;
}

.goservice-chatbot-btn::before{
    content:"";
    position:absolute;
    inset:-6px;
    border-radius:50%;
    border:2px solid rgba(238,88,40,.45);
    animation:chatbotPulse 1.8s infinite;
}

.goservice-chatbot-btn:hover{
    transform:scale(1.1);
    filter:brightness(1.12);
    box-shadow:0 24px 60px rgba(238,88,40,.45);
}

.goservice-chatbot-btn:hover::before{
    border-color:rgba(255,255,255,.7);
}

@keyframes chatbotFloat{
    0%,100%{
        transform:translateY(0px);
    }
    50%{
        transform:translateY(-12px);
    }
}

@keyframes chatbotPulse{
    0%{
        transform:scale(.9);
        opacity:.8;
    }
    100%{
        transform:scale(1.35);
        opacity:0;
    }
}

.goservice-chatbot-box{
    position:fixed;
    right:28px;
    bottom:110px;
    width:360px;
    max-width:calc(100vw - 40px);
    height:500px;

    display:none;
    flex-direction:column;

    background:var(--forum-bg-card,#fff);
    color:var(--forum-text,#17283f);

    border:1px solid var(--forum-border,rgba(15,23,42,.08));
    border-radius:26px;
    overflow:hidden;

    box-shadow:0 24px 70px rgba(0,0,0,.28);

    z-index:999999;

    backdrop-filter:blur(14px);

    animation:chatbotOpen .25s ease;
}

.goservice-chatbot-box.show{
    display:flex;
}

@keyframes chatbotOpen{
    from{
        opacity:0;
        transform:translateY(20px) scale(.95);
    }
    to{
        opacity:1;
        transform:translateY(0) scale(1);
    }
}

.chatbot-head{
    padding:16px 18px;
    background:linear-gradient(135deg,#EE5828,#142738);
    color:white;

    display:flex;
    justify-content:space-between;
    align-items:center;

    font-weight:900;
    font-size:18px;
}

#chatbotClose{
    border:none;
    background:rgba(255,255,255,.18);
    color:white;

    width:38px;
    height:38px;

    border-radius:50%;
    cursor:pointer;

    font-size:22px;

    transition:.2s;
}

#chatbotClose:hover{
    background:rgba(255,255,255,.3);
    transform:rotate(90deg);
}

.chatbot-messages{
    flex:1;
    padding:16px;
    overflow-y:auto;

    display:flex;
    flex-direction:column;
    gap:12px;

    background:
        linear-gradient(
            to bottom,
            rgba(255,255,255,.02),
            rgba(0,0,0,.02)
        );
}

.chatbot-messages::-webkit-scrollbar{
    width:6px;
}

.chatbot-messages::-webkit-scrollbar-thumb{
    background:#EE5828;
    border-radius:999px;
}

.chat-msg{
    max-width:82%;
    padding:12px 15px;
    border-radius:18px;

    line-height:1.5;
    font-size:14px;

    animation:messageAppear .25s ease;
}

@keyframes messageAppear{
    from{
        opacity:0;
        transform:translateY(10px);
    }
    to{
        opacity:1;
        transform:translateY(0);
    }
}

.chat-msg.user{
    align-self:flex-end;

    background:linear-gradient(135deg,#EE5828,#ff7b42);

    color:white;

    border-bottom-right-radius:6px;

    box-shadow:0 8px 20px rgba(238,88,40,.25);
}

.chat-msg.bot{
    align-self:flex-start;

    background:var(--forum-bg-input,#f3f5f8);
    color:var(--forum-text,#17283f);

    border-bottom-left-radius:6px;

    box-shadow:0 8px 20px rgba(0,0,0,.06);
}

.chatbot-form{
    display:flex;
    gap:10px;

    padding:14px;

    border-top:1px solid var(--forum-border,rgba(15,23,42,.08));

    background:rgba(255,255,255,.6);
    backdrop-filter:blur(10px);
}

.chatbot-form input{
    flex:1;

    height:48px;

    border-radius:999px;

    border:1px solid var(--forum-border,rgba(15,23,42,.08));

    padding:0 16px;

    outline:none;

    background:var(--forum-bg-input,#fff);

    color:var(--forum-text,#17283f);

    transition:.2s;
}

.chatbot-form input:focus{
    border-color:#EE5828;
    box-shadow:0 0 0 4px rgba(238,88,40,.12);
}

.chatbot-form button{
    width:50px;
    height:48px;

    border-radius:50%;

    border:none;

    background:linear-gradient(135deg,#EE5828,#ff7b42);

    color:white;

    cursor:pointer;

    font-size:18px;

    transition:.25s;
}

.chatbot-form button:hover{
    transform:scale(1.08) rotate(-10deg);
    box-shadow:0 10px 24px rgba(238,88,40,.35);
}
.goservice-chatbot-btn::before{
    border:3px solid rgba(238,88,40,.75);
    box-shadow:
        0 0 0 8px rgba(238,88,40,.12),
        0 0 28px rgba(238,88,40,.45);
}

.goservice-chatbot-btn::after{
    content:"";
    position:absolute;
    inset:-14px;
    border-radius:50%;
    background:rgba(238,88,40,.12);
    z-index:-1;
    animation:chatbotGlow 2s ease-in-out infinite;
}

@keyframes chatbotGlow{
    0%,100%{
        transform:scale(.85);
        opacity:.45;
    }
    50%{
        transform:scale(1.15);
        opacity:.9;
    }
}
</style>
<script>
const chatbotBtn = document.getElementById('chatbotBtn');
const chatbotBox = document.getElementById('chatbotBox');
const chatbotClose = document.getElementById('chatbotClose');
const chatbotForm = document.getElementById('chatbotForm');
const chatbotInput = document.getElementById('chatbotInput');
const chatbotMessages = document.getElementById('chatbotMessages');

chatbotBtn.onclick = () => chatbotBox.classList.toggle('show');
chatbotClose.onclick = () => chatbotBox.classList.remove('show');

function addMsg(text, type){
    const div = document.createElement('div');
    div.className = 'chat-msg ' + type;
    div.textContent = text;
    chatbotMessages.appendChild(div);
    chatbotMessages.scrollTop = chatbotMessages.scrollHeight;
    return div;
}

let chatbotHistory = [];

chatbotForm.addEventListener('submit', async function(e){
    e.preventDefault();

    const message = chatbotInput.value.trim();
    if(message === '') return;

    addMsg(message, 'user');
    chatbotInput.value = '';

    chatbotHistory.push({
        role: 'user',
        content: message
    });

    const loading = addMsg('Assistant écrit...', 'bot');

    try{
        const formData = new FormData();
        formData.append('messages', JSON.stringify(chatbotHistory));

        const res = await fetch(FORUM_APP_ROOT + '/view/front/pages/chatbot_ollama.php', {
            method: 'POST',
            body: formData
        });

        const data = await res.json();
        const reply = data.reply || 'Erreur.';

        loading.textContent = reply;

        chatbotHistory.push({
            role: 'assistant',
            content: reply
        });

    }catch(err){
        loading.textContent = "Erreur : Ollama ne répond pas.";
    }
});
</script>
<script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>


