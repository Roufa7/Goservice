<?php
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

if (defined('GS_I18N_LOADED')) {
    return;
}
define('GS_I18N_LOADED', true);

if (!function_exists('app_supported_languages')) {
    function app_supported_languages(): array {
        return ['fr', 'en', 'ar'];
    }
}

if (!function_exists('app_set_language_from_request')) {
    function app_set_language_from_request(): void {
        $requested = $_GET['lang'] ?? null;
        $current = $_SESSION['app_lang'] ?? 'fr';
        if ($requested !== null) {
            $requested = (string) $requested;
            if (in_array($requested, app_supported_languages(), true)) {
                $_SESSION['app_lang'] = $requested;
                return;
            }
        }
        if (!in_array($current, app_supported_languages(), true)) {
            $_SESSION['app_lang'] = 'fr';
        }
    }
}

if (!function_exists('app_lang')) {
    function app_lang(): string {
        return $_SESSION['app_lang'] ?? 'fr';
    }
}

if (!function_exists('app_is_rtl')) {
    function app_is_rtl(): bool {
        return app_lang() === 'ar';
    }
}

if (!function_exists('app_text')) {
    function app_text(string $fr, string $en, ?string $ar = null): string {
        $lang = app_lang();
        if ($lang === 'en') return $en;
        if ($lang === 'ar') return $ar ?? $fr;
        return $fr;
    }
}

if (!function_exists('app_url')) {
    function app_url(array $extra = []): string {
        $params = $_GET;
        foreach ($extra as $k => $v) {
            $params[$k] = $v;
        }
        $script = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : 'index.php';
        return $script . (count($params) ? ('?' . http_build_query($params)) : '');
    }
}

if (!function_exists('app_lang_url')) {
    function app_lang_url(string $lang): string {
        $params = $_GET;
        $params['lang'] = $lang;
        $script = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : 'index.php';
        return $script . '?' . http_build_query($params);
    }
}

if (!function_exists('app_lang_label')) {
    function app_lang_label(string $lang): string {
        return match ($lang) {
            'en' => 'EN',
            'ar' => 'AR',
            default => 'FR',
        };
    }
}

if (!function_exists('app_fix_mojibake')) {
    function app_fix_mojibake(string $text): string {
        $map = [
        'Réclamations' => 'Réclamations',
        'réclamations' => 'réclamations',
        'Événements' => 'Événements',
        'événements' => 'événements',
        'Catégories' => 'Catégories',
        'catégories' => 'catégories',
        'Réservations' => 'Réservations',
        'Français' => 'Français',
        'Déconnexion' => 'Déconnexion',
        'Forum & échanges' => 'Forum & échanges',
        'Posts enregistrés' => 'Posts enregistrés',
        'Posts récents' => 'Posts récents',
        'Plus récents' => 'Plus récents',
        'Aucun résultat' => 'Aucun résultat',
        'Aucun post trouvé.' => 'Aucun post trouvé.',
        'Aucun service trouvé.' => 'Aucun service trouvé.',
        'Retrouvez ici les posts que vous avez sauvegardés depuis le forum.' => 'Retrouvez ici les posts que vous avez sauvegardés depuis le forum.',
        'Modération' => 'Modération',
        'modération' => 'modération',
        'Activité' => 'Activité',
        '?volution des inscriptions' => 'Évolution des inscriptions',
        'Contr?les' => 'Contrôles',
        'Activit? r?cente' => 'Activité récente',
        'V?rifier les nouveaux services publi?s' => 'Vérifier les nouveaux services publiés',
        'Consulter les r?clamations urgentes' => 'Consulter les réclamations urgentes',
        'Mod?rer les derniers posts du forum' => 'Modérer les derniers posts du forum',
        'Suivre les ?v?nements ? venir' => 'Suivre les événements à venir',
        'La mod?ration du forum reste reli?e au flux en direct' => 'La modération du forum reste reliée au flux en direct',
        'Les participations aux ?v?nements envoient encore les confirmations si le mail est configur?' => 'Les participations aux événements envoient encore les confirmations si le mail est configuré',
        'Les r?clamations et r?servations restent accessibles depuis le menu gauche' => 'Les réclamations et réservations restent accessibles depuis le menu gauche',
        'r?clamations' => 'réclamations',
        'r?servations' => 'réservations',
        'publi?s' => 'publiés',
        'reli?e' => 'reliée',
        'configur?' => 'configuré',
        'Signalé' => 'Signalé',
        'Signalés' => 'Signalés',
        'Approuvé' => 'Approuvé',
        'Approuvé' => 'Approuvé',
        'Rejeté' => 'Rejeté',
        'Rejeté' => 'Rejeté',
        'Contenu inapproprié' => 'Contenu inapproprié',
        'Désinformation' => 'Désinformation',
        'Détails du signalement' => 'Détails du signalement',
        'Commentaire signalé' => 'Commentaire signalé',
        'Contenu signalé' => 'Contenu signalé',
        'Détails supplémentaires' => 'Détails supplémentaires',
        'inspirée des réseaux sociaux' => 'inspirée des réseaux sociaux',
        'Changer le thème' => 'Changer le thème',
        'Je réfléchis...' => 'Je réfléchis...',
        'Je rÃ©flÃ©chis...' => 'Je réfléchis...',
        'Ã—' => '×',
        'â˜€' => '☀',
        'â˜¾' => '☾',
        'ðŸ¤–' => '🤖',
        'ðŸ“·' => '📷',
        'ðŸš©' => '🚩',
        'ðŸ‘' => '👍',
        'ðŸ’¬' => '💬',
        'ðŸ”' => '🔁',
        'ðŸ”–' => '🔖',
        'âœ…' => '✅',
        'ðŸ™ˆ' => '🙈'
    ];
    // Additional common mojibake sequences
    $extra = [
        'ApprouvÃ©' => 'Approuvé',
        'CrÃ©er' => 'Créer',
        'CrÃ¨er' => 'Créer',
        'Ajouter Ã ' => 'Ajouter à ',
        'Ajouter Ã' => 'Ajouter à',
        'Mise Ã ' => 'Mise à ',
        'Mise Ã' => 'Mise à',
        'Ã©' => 'é',
        'Ã¨' => 'è',
        'Ãª' => 'ê',
        'Ã¢' => 'â',
        'Ã€' => 'À',
        'Ã ' => 'à'
    ];
    // Emoji mojibake sequences -> real emojis
    $emoji_fix = [
        'ðŸŽ¥' => '🎥',
        'ðŸ˜Š' => '😊',
        'ðŸ“·' => '📷',
        'ðŸ–¼ï¸' => '🖼️',
        'ðŸ–¼' => '🖼️',
        'ðŸ”–' => '🔖',
        'ðŸ”' => '🔁',
        'ðŸ’¬' => '💬',
        'ðŸ‘' => '👍',
        'ðŸš©' => '🚩',
        'ðŸ™ˆ' => '🙈'
    ];
    $map = array_merge($emoji_fix, $extra, $map);
    return str_replace(array_keys($map), array_values($map), $text);
    }
}
?>