<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function authCaptchaCode(): string
{
    if (empty($_SESSION['auth_captcha_code']) || !is_string($_SESSION['auth_captcha_code'])) {
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';

        for ($index = 0; $index < 6; $index++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }

        $_SESSION['auth_captcha_code'] = $code;
    }

    return (string) $_SESSION['auth_captcha_code'];
}

function authCaptchaValidate(?string $input): bool
{
    $expected = strtoupper(trim((string) ($_SESSION['auth_captcha_code'] ?? '')));
    $given = strtoupper(trim((string) $input));

    if ($expected === '' || $given === '') {
        return false;
    }

    $isValid = hash_equals($expected, $given);

    if ($isValid) {
        unset($_SESSION['auth_captcha_code']);
    }

    return $isValid;
}

function authCaptchaReset(): string
{
    unset($_SESSION['auth_captcha_code']);
    return authCaptchaCode();
}