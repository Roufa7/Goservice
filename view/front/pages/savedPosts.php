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

$currentUserId = (int) savedSessionValue(['id_user', 'user_id', 'id'], 0);

if ($currentUserId <= 0 && class_exists('config')) {
    try {
        $db = config::getConnexion();
        $firstUser = $db->query('SELECT id_user FROM `user` ORDER BY id_user ASC LIMIT 1')->fetchColumn();
        if ($firstUser) $currentUserId = (int)$firstUser;
    } catch (Throwable $e) {}
}

if ($currentUserId <= 0) $currentUserId = 1;

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


function savedExtractFirstUrl(string $text): string
{
    if (preg_match('~https?://[^\s<>"\']+~i', $text, $matches)) {
        return rtrim(trim($matches[0]), '.,;');
    }
    return '';
}

function savedRemoveUrlFromText(string $text, string $url): string
{
    if ($url === '') return $text;
    return trim(str_replace($url, '', $text));
}

function savedYoutubeEmbedUrl(string $url): string
{
    $parts = parse_url($url);
    if (empty($parts['host'])) return '';
    $host = strtolower($parts['host']);
    $path = $parts['path'] ?? '';
    $query = $parts['query'] ?? '';
    $videoId = '';
    if (str_contains($host, 'youtu.be')) $videoId = trim($path, '/');
    if (str_contains($host, 'youtube.com')) {
        parse_str($query, $params);
        if (!empty($params['v'])) $videoId = $params['v'];
        elseif (preg_match('~/(shorts|embed)/([^/?]+)~', $path, $m)) $videoId = $m[2];
    }
    if ($videoId === '') return '';
    $videoId = preg_replace('/[^a-zA-Z0-9_-]/', '', $videoId);
    return 'https://www.youtube.com/embed/' . $videoId . '?autoplay=1&mute=1&loop=1&playlist=' . $videoId . '&controls=1&rel=0&playsinline=1';
}

function savedTikTokEmbedUrl(string $url): string
{
    $parts = parse_url($url);
    if (empty($parts['host'])) return '';
    $host = strtolower($parts['host']);
    $path = $parts['path'] ?? '';
    if (!str_contains($host, 'tiktok.com')) return '';
    if (preg_match('~/video/(\d+)~', $path, $m)) return 'https://www.tiktok.com/embed/v2/' . $m[1];
    return '';
}

function savedFacebookEmbedUrl(string $url): string
{
    $parts = parse_url($url);
    if (empty($parts['host'])) return '';
    $host = strtolower($parts['host']);
    if (!str_contains($host, 'facebook.com') && !str_contains($host, 'fb.watch')) return '';
    return 'https://www.facebook.com/plugins/video.php?href=' . urlencode($url) . '&show_text=false&width=700&autoplay=true&mute=true';
}

function savedInstagramEmbedUrl(string $url): string
{
    $parts = parse_url($url);
    if (empty($parts['host'])) return '';
    $host = strtolower($parts['host']);
    $path = trim($parts['path'] ?? '', '/');
    if (!str_contains($host, 'instagram.com')) return '';
    if (str_starts_with($path, 'reel/') || str_starts_with($path, 'p/') || str_starts_with($path, 'tv/')) return 'https://www.instagram.com/' . $path . '/embed';
    return '';
}

function savedTwitterStatusId(string $url): string
{
    $parts = parse_url($url);
    $path = $parts['path'] ?? '';
    if (preg_match('~/status/(\d+)~', $path, $m)) return $m[1];
    return '';
}

function savedTwitterEmbedUrl(string $url): string
{
    $parts = parse_url($url);
    if (empty($parts['host'])) return '';
    $host = strtolower($parts['host']);
    if (!str_contains($host, 'twitter.com') && !str_contains($host, 'x.com')) return '';
    $id = savedTwitterStatusId($url);
    if ($id === '') return '';
    return 'https://platform.twitter.com/embed/Tweet.html?id=' . $id;
}

function savedVimeoEmbedUrl(string $url): string
{
    $parts = parse_url($url);
    if (empty($parts['host'])) return '';
    $host = strtolower($parts['host']);
    $path = trim($parts['path'] ?? '', '/');
    if (!str_contains($host, 'vimeo.com')) return '';
    if (!preg_match('/^\d+$/', $path)) return '';
    return 'https://player.vimeo.com/video/' . $path . '?autoplay=1&muted=1&loop=1';
}

function savedIsDirectVideoUrl(string $url): bool
{
    return preg_match('~\.(mp4|webm|ogg)(\?.*)?$~i', $url) === 1;
}

function savedVideoEmbedData(string $text): array
{
    $url = savedExtractFirstUrl($text);
    if ($url === '') return ['url'=>'','type'=>'','embed'=>'','provider'=>'','clean_text'=>$text];
    $providers = [
        'YouTube' => savedYoutubeEmbedUrl($url),
        'TikTok' => savedTikTokEmbedUrl($url),
        'Instagram' => savedInstagramEmbedUrl($url),
        'Facebook' => savedFacebookEmbedUrl($url),
        'Twitter' => savedTwitterEmbedUrl($url),
        'Vimeo' => savedVimeoEmbedUrl($url),
    ];
    foreach ($providers as $provider => $embed) {
        if ($embed !== '') return ['url'=>$url,'type'=>'iframe','embed'=>$embed,'provider'=>$provider,'clean_text'=>savedRemoveUrlFromText($text,$url)];
    }
    if (savedIsDirectVideoUrl($url)) return ['url'=>$url,'type'=>'video','embed'=>$url,'provider'=>'Vidéo','clean_text'=>savedRemoveUrlFromText($text,$url)];
    return ['url'=>$url,'type'=>'link','embed'=>'','provider'=>'Lien','clean_text'=>$text];
}

$currentPage = isset($_GET['p']) ? (int)$_GET['p'] : 1;
if ($currentPage < 1) {
    $currentPage = 1;
}

$postsPerPage = 6;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_saved'])) {
    $postId = (int)($_POST['post_id'] ?? 0);

    if ($postId > 0) {
        $saveController->removeSave($postId, $currentUserId);
    }

    header('Location: /GoService/view/front/pages/savedPosts.php?p=' . $currentPage);
    exit;
}

$savedPosts = $saveController->getSavedPostsByUser($currentUserId);

$totalPosts = count($savedPosts);
$totalPages = (int)ceil($totalPosts / $postsPerPage);

if ($totalPages > 0 && $currentPage > $totalPages) {
    $currentPage = $totalPages;
}

$offset = ($currentPage - 1) * $postsPerPage;
$postsToShow = array_slice($savedPosts, $offset, $postsPerPage);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes posts enregistrés - GoService</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="/GoService/assets/css/style.css">

    <style>
        :root {
            --saved-orange: #EE5828;
            --saved-navy: #142738;
            --saved-green: #4CAF50;
            --saved-card: #ffffff;
            --saved-text: #17283f;
            --saved-text-soft: #607089;
            --saved-border: rgba(15, 23, 42, .08);
            --saved-shadow: 0 12px 28px rgba(15, 23, 42, .08);
            --saved-btn-bg: linear-gradient(135deg, #EE5828 0%, #c9471d 38%, #1f3144 72%, #4CAF50 100%);
        }

        body.dark {
            --saved-card: #13283d;
            --saved-text: #ffffff;
            --saved-text-soft: #c8d3df;
            --saved-border: rgba(255, 255, 255, .08);
            --saved-shadow: 0 14px 32px rgba(0, 0, 0, .22);
        }

        .saved-page {
            width: min(1380px, calc(100% - 36px));
            margin: 0 auto;
            padding: 28px 0 60px;
        }

        .forum-hero-classic {
            position: relative;
            overflow: hidden;
            border-radius: 34px;
            padding: 60px 28px;
            margin-bottom: 28px;
            background: var(--saved-card);
            border: 1px solid var(--saved-border);
            box-shadow: var(--saved-shadow);
        }

        .forum-hero-classic::before {
            content: "";
            position: absolute;
            inset: 0;
            background: url('/GoService/assets/images/forum-hero.png') center/cover no-repeat;
            z-index: 0;
            pointer-events: none;
        }

        .forum-hero-classic::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(
                90deg,
                rgba(255, 255, 255, .78) 0%,
                rgba(255, 255, 255, .62) 34%,
                rgba(255, 255, 255, .18) 68%,
                rgba(255, 255, 255, .06) 100%
            );
            z-index: 0;
            pointer-events: none;
        }

        body.dark .forum-hero-classic::after {
            background: linear-gradient(
                90deg,
                rgba(8, 18, 30, .84) 0%,
                rgba(8, 18, 30, .70) 38%,
                rgba(8, 18, 30, .34) 70%,
                rgba(8, 18, 30, .14) 100%
            );
        }

        .forum-hero-classic > * {
            position: relative;
            z-index: 1;
        }

        .section-badge {
            display: inline-flex;
            align-items: center;
            width: max-content;
            padding: 8px 16px;
            border-radius: 999px;
            background: rgba(238, 88, 40, .10);
            color: var(--saved-orange);
            font-weight: 800;
            font-size: 14px;
            margin-bottom: 12px;
        }

        .page-title {
            margin: 0 0 16px;
            color: var(--saved-text);
            font-size: 48px;
            line-height: 1.1;
            font-weight: 900;
        }

        .page-intro {
            margin: 0;
            color: var(--saved-text-soft);
            font-size: 18px;
            line-height: 1.6;
            max-width: 650px;
        }

        .saved-topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 24px;
            flex-wrap: wrap;
        }

        .saved-title-box {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .saved-page-title {
            margin: 0;
            color: var(--saved-text);
            font-size: 34px;
            line-height: 1.2;
            font-weight: 900;
        }

        .saved-count {
            margin: 0;
            color: var(--saved-text-soft);
            font-size: 16px;
            font-weight: 600;
        }

        .saved-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 22px;
        }

        .saved-card {
            background: var(--saved-card);
            border: 1px solid var(--saved-border);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: var(--saved-shadow);
            display: flex;
            flex-direction: column;
            min-width: 0;
            transition: .25s ease;
        }

        .saved-card:hover {
            transform: translateY(-4px);
        }

        .saved-thumb {
            width: 100%;
            height: 215px;
            background: #d9dde4;
            position: relative;
            overflow: hidden;
        }

        .saved-thumb img,
        .saved-thumb video {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            background: #0b0b0b;
        }



        .saved-external-frame {
            width: 100%;
            height: 100%;
            border: 0;
            display: block;
            background: #000;
        }

        .saved-thumb.saved-provider-youtube iframe,
        .saved-thumb.saved-provider-facebook iframe,
        .saved-thumb.saved-provider-vimeo iframe {
            width: 100%;
            height: 100% !important;
        }

        .saved-thumb.saved-provider-tiktok,
        .saved-thumb.saved-provider-instagram,
        .saved-thumb.saved-provider-twitter {
            background: #000;
        }

        .saved-thumb.saved-provider-tiktok iframe,
        .saved-thumb.saved-provider-instagram iframe {
            width: 100%;
            height: 360px !important;
            transform: translateY(-45px);
            margin: 0 auto;
        }

        .saved-thumb.saved-provider-twitter iframe {
            width: 100%;
            height: 330px !important;
            transform: translateY(-35px);
        }

        .saved-video-badge {
            position: absolute;
            right: 12px;
            bottom: 10px;
            z-index: 2;
            padding: 6px 10px;
            border-radius: 999px;
            background: rgba(0, 0, 0, .55);
            color: #fff;
            font-size: 12px;
            font-weight: 800;
            pointer-events: none;
        }

        .saved-thumb-placeholder {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #d2d9e3 0%, #edf2f7 100%);
            font-size: 54px;
        }

        .saved-content {
            padding: 16px 16px 14px;
            display: flex;
            flex-direction: column;
            gap: 8px;
            flex: 1;
        }

        .saved-post-type {
            display: inline-flex;
            align-items: center;
            width: max-content;
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 800;
            background: rgba(238, 88, 40, .10);
            color: var(--saved-orange);
            border: 1px solid rgba(238, 88, 40, .15);
            margin-bottom: 6px;
        }

        .saved-post-title {
            margin: 0;
            color: var(--saved-text);
            font-size: 20px;
            font-weight: 900;
            line-height: 1.25;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .saved-post-text {
            margin: 2px 0 0;
            color: var(--saved-text-soft);
            font-size: 14px;
            line-height: 1.45;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .saved-emoji-row {
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
            min-height: 0;
            margin-top: 4px;
            margin-bottom: 2px;
        }

        .saved-emoji-item {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            line-height: 1;
        }

        .saved-meta {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 8px;
        }

        .saved-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 16px;
            color: #fff;
            background: linear-gradient(135deg, #EE5828 0%, #1f3144 65%, #4CAF50 100%);
            flex-shrink: 0;
        }

        .saved-meta-text {
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .saved-author {
            color: var(--saved-text);
            font-weight: 800;
            font-size: 14px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .saved-date {
            color: var(--saved-text-soft);
            font-size: 13px;
        }

        .saved-actions {
            padding: 0 16px 16px;
            display: flex;
            gap: 10px;
        }

        .saved-open-btn,
        .saved-remove-btn {
            flex: 1;
            height: 44px;
            border: none;
            border-radius: 14px;
            font-weight: 800;
            font-size: 14px;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .saved-open-btn {
            background: var(--saved-btn-bg);
            color: #fff;
        }

        .saved-remove-btn {
            background: #f3f5f8;
            color: #c03b2a;
            border: 1px solid rgba(192, 59, 42, .12);
        }

        body.dark .saved-remove-btn {
            background: rgba(255, 255, 255, .05);
            color: #ff8f81;
            border: 1px solid rgba(255, 255, 255, .08);
        }

        .saved-empty {
            background: var(--saved-card);
            border: 1px solid var(--saved-border);
            border-radius: 24px;
            padding: 44px 24px;
            text-align: center;
            box-shadow: var(--saved-shadow);
        }

        .saved-empty-icon {
            font-size: 54px;
            margin-bottom: 12px;
        }

        .saved-empty h3 {
            margin: 0 0 10px;
            color: var(--saved-text);
            font-size: 26px;
        }

        .saved-empty p {
            margin: 0 0 22px;
            color: var(--saved-text-soft);
            font-size: 16px;
        }

        .saved-pagination {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-top: 32px;
            flex-wrap: wrap;
        }

        .saved-page-link {
            min-width: 44px;
            height: 44px;
            padding: 0 15px;
            border-radius: 14px;
            background: var(--saved-card);
            border: 1px solid var(--saved-border);
            color: var(--saved-text);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-weight: 900;
            box-shadow: 0 8px 18px rgba(15, 23, 42, .06);
            transition: .2s ease;
        }

        .saved-page-link:hover {
            transform: translateY(-2px);
            color: var(--saved-orange);
        }

        .saved-page-link.active {
            background: var(--saved-btn-bg);
            color: #fff;
            border-color: transparent;
        }

        .saved-page-dots {
            color: var(--saved-text-soft);
            font-weight: 900;
            padding: 0 4px;
        }

        @media (max-width: 1100px) {
            .saved-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 700px) {
            .saved-grid {
                grid-template-columns: 1fr;
            }

            .page-title {
                font-size: 34px;
            }

            .saved-page-title {
                font-size: 27px;
            }
        }

    .goservice-footer {
        width: 100%;
        margin-top: 60px;
        padding: 58px 30px 70px;
        background:
            radial-gradient(circle at 20% 20%, rgba(76, 175, 80, 0.08), transparent 28%),
            radial-gradient(circle at 85% 15%, rgba(76, 175, 80, 0.08), transparent 30%),
            #f6fbf8;
        border-top: 1px solid rgba(15, 23, 42, 0.06);
    }

    .goservice-footer-inner {
        max-width: 1380px;
        margin: 0 auto;
        display: grid;
        grid-template-columns: 1.4fr 1fr 1fr;
        gap: 80px;
        align-items: flex-start;
    }

    .goservice-footer-col h3 {
        margin: 0 0 24px;
        color: #142738;
        font-size: 22px;
        font-weight: 900;
    }

    .goservice-footer-col p {
        margin: 0;
        max-width: 520px;
        color: #607089;
        font-size: 18px;
        line-height: 1.7;
        font-weight: 500;
    }

    .goservice-footer-col ul {
        list-style: none;
        padding: 0;
        margin: 0;
        display: flex;
        flex-direction: column;
        gap: 18px;
    }

    .goservice-footer-col a {
        color: #607089;
        text-decoration: none;
        font-size: 18px;
        font-weight: 500;
        transition: 0.2s ease;
    }

    .goservice-footer-col a:hover {
        color: #EE5828;
        padding-left: 4px;
    }

    body.dark .goservice-footer,
    body.dark-mode .goservice-footer,
    body[data-theme="dark"] .goservice-footer,
    body.theme-dark .goservice-footer {
        background:
            radial-gradient(circle at 20% 20%, rgba(76, 175, 80, 0.10), transparent 28%),
            radial-gradient(circle at 85% 15%, rgba(76, 175, 80, 0.10), transparent 30%),
            #0f2236;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
    }

    body.dark .goservice-footer-col h3,
    body.dark-mode .goservice-footer-col h3,
    body[data-theme="dark"] .goservice-footer-col h3,
    body.theme-dark .goservice-footer-col h3 {
        color: #ffffff;
    }

    body.dark .goservice-footer-col p,
    body.dark-mode .goservice-footer-col p,
    body[data-theme="dark"] .goservice-footer-col p,
    body.theme-dark .goservice-footer-col p,
    body.dark .goservice-footer-col a,
    body.dark-mode .goservice-footer-col a,
    body[data-theme="dark"] .goservice-footer-col a,
    body.theme-dark .goservice-footer-col a {
        color: #c7d3e0;
    }

    body.dark .goservice-footer-col a:hover,
    body.dark-mode .goservice-footer-col a:hover,
    body[data-theme="dark"] .goservice-footer-col a:hover,
    body.theme-dark .goservice-footer-col a:hover {
        color: #EE5828;
    }

    @media (max-width: 900px) {
        .goservice-footer-inner {
            grid-template-columns: 1fr;
            gap: 36px;
        }

        .goservice-footer {
            padding: 45px 22px 55px;
        }
    }


    /* ============================================================
       LANGUE STYLE INSTAGRAM + GOOGLE TRANSLATE CLEAN
       ============================================================ */
    .ig-lang-zone{
        width: min(1380px, calc(100% - 36px));
        margin: 22px auto 0;
        padding: 0 8px;
        box-sizing: border-box;
        display: flex;
        justify-content: flex-end;
        align-items: center;
        color: #737373;
        font-size: 14px;
        position: relative;
        z-index: 9999;
    }

    .ig-lang-wrap{
        position: relative;
        display: inline-flex;
        align-items: center;
    }

    .ig-lang-btn{
        border: none;
        background: transparent;
        color: #737373;
        font: inherit;
        cursor: pointer;
        padding: 0;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }

    .ig-lang-btn:hover{
        text-decoration: underline;
    }

    .ig-lang-menu{
        position: absolute;
        right: 0;
        bottom: 26px;
        width: 220px;
        max-height: 280px;
        overflow-y: auto;
        display: none;
        background: #ffffff;
        border: 1px solid rgba(0,0,0,.12);
        border-radius: 10px;
        box-shadow: 0 8px 24px rgba(0,0,0,.14);
        padding: 6px 0;
        z-index: 999999;
    }

    .ig-lang-menu.show{
        display: block;
    }

    .ig-lang-menu button{
        width: 100%;
        border: none;
        background: transparent;
        color: #262626;
        font: inherit;
        text-align: left;
        cursor: pointer;
        padding: 10px 14px;
    }

    .ig-lang-menu button:hover{
        background: #f5f5f5;
    }

    #google_translate_element{
        display: none !important;
        height: 0 !important;
        overflow: hidden !important;
    }

    .goog-te-banner-frame,
    .goog-te-banner-frame.skiptranslate,
    .goog-te-balloon-frame,
    iframe.goog-te-banner-frame,
    iframe.skiptranslate,
    #goog-gt-tt,
    .goog-logo-link,
    .goog-te-gadget-icon,
    .goog-te-gadget span{
        display: none !important;
        visibility: hidden !important;
        height: 0 !important;
    }

    .goog-te-gadget{
        height: 0 !important;
        overflow: hidden !important;
        font-size: 0 !important;
        line-height: 0 !important;
    }

    html{
        margin-top: 0 !important;
    }

    body{
        top: 0 !important;
        position: static !important;
    }

    body > .skiptranslate{
        display: none !important;
        visibility: hidden !important;
        height: 0 !important;
    }

    body.dark .ig-lang-menu{
        background: #132d46;
        border-color: rgba(255,255,255,.12);
    }

    body.dark .ig-lang-menu button{
        color: #ffffff;
    }

    body.dark .ig-lang-menu button:hover{
        background: rgba(255,255,255,.08);
    }

    </style>
</head>

<body>

<div class="bg-orb orb-1"></div>
<div class="bg-orb orb-2"></div>
<div class="bg-orb orb-3"></div>

<header class="site-header">
    <div class="container nav-wrap">

        <a href="/GoService/view/front/index.php?page=home" class="brand">
            <img id="siteLogo"
                 src="/GoService/assets/images/logo.png"
                 data-light="/GoService/assets/images/logo.png"
                 data-dark="/GoService/assets/images/logo-white.png"
                 alt="GoService">
        </a>

        <nav class="main-nav">
            <a href="/GoService/view/front/index.php?page=home">Accueil</a>
            <a href="/GoService/view/front/index.php?page=services">Services & Catégories</a>
            <a href="/GoService/view/front/index.php?page=offres">Offres</a>
            <a href="/GoService/view/front/index.php?page=forum" class="active">Forum</a>
            <a href="/GoService/view/front/index.php?page=reclamation">Réclamations</a>
            <a href="/GoService/view/front/index.php?page=events">Événements</a>
            <a href="/GoService/view/front/index.php?page=profile">Profil</a>
        </nav>

        <div class="nav-actions">
            <button type="button" class="theme-btn" id="themeToggle">☾</button>
            <a href="/GoService/view/front/index.php?page=login" class="ghost-btn">Connexion</a>
            <a href="/GoService/view/front/index.php?page=register" class="solid-btn">S’inscrire</a>
        </div>

    </div>
</header>

<main class="saved-page">

    <section class="forum-hero-classic">
        <span class="section-badge">Forum social</span>
        <h1 class="page-title">Mes posts enregistrés</h1>
        <p class="page-intro">
            Retrouvez ici les posts que vous avez enregistrés depuis le forum.
            Chaque page contient 6 posts affichés en 3 colonnes et 2 lignes.
        </p>
    </section>

    <div class="saved-topbar">
        <div class="saved-title-box">
            <h2 class="saved-page-title">Posts sauvegardés</h2>
            <p class="saved-count">
                <?php echo $totalPosts; ?> post<?php echo $totalPosts > 1 ? 's' : ''; ?> enregistré<?php echo $totalPosts > 1 ? 's' : ''; ?>
            </p>
        </div>

        <a href="/GoService/view/front/index.php?page=forum" class="solid-btn">
            ← Retour au forum
        </a>
    </div>

    <?php if (empty($savedPosts)): ?>

        <div class="saved-empty">
            <div class="saved-empty-icon">🔖</div>
            <h3>Aucun post enregistré</h3>
            <p>Quand vous enregistrez un post depuis le forum, il apparaîtra ici.</p>

            <a href="/GoService/view/front/index.php?page=forum" class="solid-btn">
                Retour au forum
            </a>
        </div>

    <?php else: ?>

        <div class="saved-grid">
            <?php foreach ($postsToShow as $post): ?>
                <?php
                    $title = $post['titre'] ?? 'Post sans titre';
                    $content = $post['contenu'] ?? '';
                    $videoEmbedData = savedVideoEmbedData($content);
                    $cleanContent = $videoEmbedData['clean_text'] ?? $content;
                    $emojiPost = trim($post['emoji_post'] ?? '');

                    $imageUrl = !empty($post['image'])
                        ? '/GoService/' . ltrim($post['image'], '/')
                        : '';

                    $videoUrl = !empty($post['video'])
                        ? '/GoService/' . ltrim($post['video'], '/')
                        : '';

                    $gifUrl = '';
                    if (!empty($post['gif_post'])) {
                        $gifUrl = preg_match('~^https?://~i', $post['gif_post'])
                            ? $post['gif_post']
                            : '/GoService/' . ltrim($post['gif_post'], '/');
                    }

                    $type = $post['type_post'] ?? 'Discussion';

                    $fullname = trim(($post['prenom'] ?? '') . ' ' . ($post['nom'] ?? ''));
                    if ($fullname === '') {
                        $fullname = 'Utilisateur';
                    }

                    $avatarLetter = strtoupper(substr($fullname, 0, 1));
                    $postId = (int)($post['id_post'] ?? 0);
                ?>

                <article class="saved-card" id="saved-post-<?php echo $postId; ?>">

                    <?php
                        $providerClass = strtolower($videoEmbedData['provider'] ?? 'video');
                        $providerClass = preg_replace('/[^a-z0-9]/', '', $providerClass);
                    ?>
                    <div class="saved-thumb <?php echo !empty($videoEmbedData['embed']) ? 'saved-provider-' . e($providerClass) : ''; ?>">
                        <?php if (!empty($videoEmbedData['embed']) && ($videoEmbedData['type'] ?? '') === 'iframe'): ?>

                            <iframe
                                class="saved-external-frame"
                                src="<?php echo e($videoEmbedData['embed']); ?>"
                                title="Vidéo <?php echo e($videoEmbedData['provider'] ?? 'intégrée'); ?>"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                allowfullscreen
                                scrolling="no"
                                loading="lazy">
                            </iframe>
                            <div class="saved-video-badge"><?php echo e($videoEmbedData['provider'] ?? 'Vidéo'); ?></div>

                        <?php elseif (!empty($videoEmbedData['embed']) && ($videoEmbedData['type'] ?? '') === 'video'): ?>

                            <video muted autoplay loop playsinline preload="metadata">
                                <source src="<?php echo e($videoEmbedData['embed']); ?>">
                            </video>
                            <div class="saved-video-badge">Vidéo</div>

                        <?php elseif ($videoUrl): ?>

                            <?php
                                $videoExtension = strtolower(pathinfo($videoUrl, PATHINFO_EXTENSION));
                                $videoMime = 'video/mp4';

                                if ($videoExtension === 'webm') {
                                    $videoMime = 'video/webm';
                                } elseif ($videoExtension === 'ogg') {
                                    $videoMime = 'video/ogg';
                                }
                            ?>

                            <video muted autoplay loop playsinline preload="metadata">
                                <source src="<?php echo e($videoUrl); ?>" type="<?php echo e($videoMime); ?>">
                                Votre navigateur ne supporte pas la vidéo.
                            </video>
                            <div class="saved-video-badge">Vidéo</div>

                        <?php elseif ($gifUrl): ?>

                            <img src="<?php echo e($gifUrl); ?>" alt="GIF post" loading="lazy">
                            <div class="saved-video-badge">GIF</div>

                        <?php elseif ($imageUrl): ?>

                            <img src="<?php echo e($imageUrl); ?>" alt="Image post">

                        <?php else: ?>

                            <div class="saved-thumb-placeholder">📝</div>

                        <?php endif; ?>
                    </div>

                    <div class="saved-content">

                        <span class="saved-post-type">
                            <?php echo e($type); ?>
                        </span>

                        <h3 class="saved-post-title">
                            <?php echo e($title); ?>
                        </h3>

                        <?php if (!empty($cleanContent)): ?>
                            <p class="saved-post-text">
                                <?php echo e($cleanContent); ?>
                            </p>
                        <?php endif; ?>

                        <?php if (!empty($emojiPost)): ?>
                            <div class="saved-emoji-row">
                                <?php
                                    $emojis = preg_split('/\s+/u', $emojiPost, -1, PREG_SPLIT_NO_EMPTY);
                                    foreach ($emojis as $emoji):
                                ?>
                                    <span class="saved-emoji-item"><?php echo e($emoji); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="saved-meta">
                            <div class="saved-avatar">
                                <?php echo e($avatarLetter); ?>
                            </div>

                            <div class="saved-meta-text">
                                <span class="saved-author">
                                    <?php echo e($fullname); ?>
                                </span>

                                <span class="saved-date">
                                    <?php echo e(timeAgo($post['date_saved'] ?? $post['date_publication'] ?? '')); ?>
                                </span>
                            </div>
                        </div>

                    </div>

                    <div class="saved-actions">

                        <a class="saved-open-btn"
                           href="/GoService/view/front/index.php?page=forum&open_post=<?php echo $postId; ?>#post-<?php echo $postId; ?>">
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

        <?php if ($totalPages > 1): ?>
            <div class="saved-pagination">

                <?php if ($currentPage > 1): ?>
                    <a class="saved-page-link" href="/GoService/view/front/pages/savedPosts.php?p=<?php echo $currentPage - 1; ?>">
                        ‹
                    </a>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $totalPages; $i++): ?>

                    <?php
                        $showPage = false;

                        if ($i == 1 || $i == $totalPages) {
                            $showPage = true;
                        }

                        if ($i >= $currentPage - 1 && $i <= $currentPage + 1) {
                            $showPage = true;
                        }
                    ?>

                    <?php if ($showPage): ?>

                        <a class="saved-page-link <?php echo $i === $currentPage ? 'active' : ''; ?>"
                           href="/GoService/view/front/pages/savedPosts.php?p=<?php echo $i; ?>">
                            <?php echo $i; ?>
                        </a>

                    <?php elseif ($i === 2 || $i === $totalPages - 1): ?>

                        <span class="saved-page-dots">...</span>

                    <?php endif; ?>

                <?php endfor; ?>

                <?php if ($currentPage < $totalPages): ?>
                    <a class="saved-page-link" href="/GoService/view/front/pages/savedPosts.php?p=<?php echo $currentPage + 1; ?>">
                        ›
                    </a>
                <?php endif; ?>

            </div>
        <?php endif; ?>

    <?php endif; ?>

</main>

<script>
    const themeToggle = document.getElementById('themeToggle');
    const siteLogo = document.getElementById('siteLogo');

    function updateLogoByTheme() {
        if (!siteLogo) return;

        const lightLogo = siteLogo.getAttribute('data-light');
        const darkLogo = siteLogo.getAttribute('data-dark');

        if (document.body.classList.contains('dark')) {
            siteLogo.src = darkLogo;
        } else {
            siteLogo.src = lightLogo;
        }
    }

    if (themeToggle) {
        const savedTheme = localStorage.getItem('goservice-theme');

        if (savedTheme === 'dark') {
            document.body.classList.add('dark');
            themeToggle.textContent = '☀';
        } else {
            document.body.classList.remove('dark');
            themeToggle.textContent = '☾';
        }

        updateLogoByTheme();

        themeToggle.addEventListener('click', function () {
            document.body.classList.toggle('dark');

            if (document.body.classList.contains('dark')) {
                localStorage.setItem('goservice-theme', 'dark');
                themeToggle.textContent = '☀';
            } else {
                localStorage.setItem('goservice-theme', 'light');
                themeToggle.textContent = '☾';
            }

            updateLogoByTheme();
        });
    }
</script>

<!-- ================= FOOTER ================= -->
<footer class="site-footer">
    <div class="container footer-grid">

        <div>
            <h3>Plateforme digitale</h3>
            <p>
                Services, offres, forum, réclamations, événements et administration
                dans une expérience cohérente.
            </p>
        </div>

        <div>
            <h3>Navigation</h3>
            <a href="/GoService/view/front/index.php?page=home">Accueil</a>
            <a href="/GoService/view/front/index.php?page=services">Services &amp; Catégories</a>
            <a href="/GoService/view/front/index.php?page=offres">Offres</a>
            <a href="/GoService/view/front/index.php?page=forum">Forum</a>
        </div>

        <div>
            <h3>Espaces</h3>
            <a href="/GoService/view/front/index.php?page=profile">Profil</a>
            <a href="/GoService/view/back/index.php?page=dashboard">Back Office</a>
        </div>

    </div>

    <!-- LANGUE STYLE INSTAGRAM -->
    <div class="ig-lang-zone notranslate" translate="no">
        <div class="ig-lang-wrap">
            <button type="button" class="ig-lang-btn" onclick="toggleIgLangMenu()">
                Langue ▾
            </button>

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

</footer>


<script>
function toggleIgLangMenu() {
    const menu = document.getElementById('igLangMenu');
    if (menu) menu.classList.toggle('show');
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.ig-lang-wrap')) {
        const menu = document.getElementById('igLangMenu');
        if (menu) menu.classList.remove('show');
    }
});

function googleTranslateElementInit() {
    new google.translate.TranslateElement({
        pageLanguage: 'fr',
        includedLanguages: 'fr,en,ar,es,it,de,tr,pt,ru,zh-CN,ja,ko',
        autoDisplay: false
    }, 'google_translate_element');
}

function changeForumLang(lang) {
    const menu = document.getElementById('igLangMenu');
    if (menu) menu.classList.remove('show');

    const interval = setInterval(function () {
        const select = document.querySelector('.goog-te-combo');
        if (select) {
            select.value = lang;
            select.dispatchEvent(new Event('change'));
            clearInterval(interval);
            setTimeout(cleanGoogleTranslateBar, 250);
            setTimeout(cleanGoogleTranslateBar, 800);
        }
    }, 250);
}

function cleanGoogleTranslateBar() {
    document.documentElement.style.marginTop = '0px';
    document.body.style.top = '0px';
    document.body.style.position = 'static';

    document.querySelectorAll('iframe.goog-te-banner-frame, iframe.skiptranslate, .goog-te-banner-frame, .goog-te-balloon-frame, body > .skiptranslate').forEach(function(el){
        el.style.display = 'none';
        el.style.visibility = 'hidden';
        el.style.height = '0px';
    });
}

setInterval(cleanGoogleTranslateBar, 700);
</script>
<script src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>

</body>
</html>