<?php
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// If functions are already defined (embedded in a page), do nothing to avoid redeclaration.
if (function_exists('app_text')) {
    return;
}

function app_supported_languages(): array {
    return ['fr', 'en', 'ar'];
}

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
    // ensure we always have a valid language in session
    if (!in_array($current, app_supported_languages(), true)) {
        $_SESSION['app_lang'] = 'fr';
    }
}

function app_lang(): string {
    return $_SESSION['app_lang'] ?? 'fr';
}

function app_is_rtl(): bool {
    return app_lang() === 'ar';
}

/**
 * app_text($fr, $en [, $ar])
 * Returns the appropriate translation based on current language.
 */
function app_text(string $fr, string $en, ?string $ar = null): string {
    $lang = app_lang();
    if ($lang === 'en') return $en;
    if ($lang === 'ar') return $ar ?? $fr;
    return $fr;
}

function app_url(array $extra = []): string {
    $params = $_GET;
    foreach ($extra as $k => $v) {
        $params[$k] = $v;
    }
    $script = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : 'index.php';
    return $script . (count($params) ? ('?' . http_build_query($params)) : '');
}

function app_lang_url(string $lang): string {
    $params = $_GET;
    $params['lang'] = $lang;
    $script = isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : 'index.php';
    return $script . '?' . http_build_query($params);
}
