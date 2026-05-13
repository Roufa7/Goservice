<?php

$autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

if (class_exists(\Dotenv\Dotenv::class) && file_exists(__DIR__ . '/.env')) {
    \Dotenv\Dotenv::createImmutable(__DIR__)->safeLoad();
}

if (!class_exists('config')) {
    class config
    {
    public static function getConnexion()
    {
        try {
            $pdo = new PDO(
                'mysql:host=localhost;dbname=goservice;charset=utf8mb4',
                'root',
                ''
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec("SET NAMES utf8mb4");
            $pdo->exec("SET CHARACTER SET utf8mb4");
            return $pdo;
        } catch (Exception $e) {
            die('Erreur de connexion : ' . $e->getMessage());
        }
    }

    public static function env(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        return (string) $value;
    }

    public static function hasMailConfiguration(): bool
    {
        foreach (['MAIL_HOST', 'MAIL_PORT', 'MAIL_USERNAME', 'MAIL_PASSWORD', 'MAIL_FROM'] as $key) {
            if (self::env($key) === null) {
                return false;
            }
        }

        return true;
    }

    public static function appName(): string
    {
        return self::env('APP_NAME', 'GoService Events') ?? 'GoService Events';
    }

    // Mail configuration reads from .env, with fallback to constants for backwards compatibility
    public static function getMailConfig(string $key): ?string
    {
        $envValue = self::env('MAIL_' . $key);
        if ($envValue !== null) {
            return $envValue;
        }
        
        $fallbacks = [
            'HOST' => 'sandbox.smtp.mailtrap.io',
            'PORT' => '587',
            'USERNAME' => '',
            'PASSWORD' => '',
            'FROM' => '',
            'FROM_NAME' => 'GoService Events',
        ];
        
        return $fallbacks[$key] ?? null;
    }

    const MAIL_HOST = 'sandbox.smtp.mailtrap.io';
    const MAIL_PORT = 587;
    const MAIL_USERNAME = '';
    const MAIL_PASSWORD = '';
    const MAIL_FROM = '';
    const MAIL_FROM_NAME = 'GoService';
    }
}
?>
