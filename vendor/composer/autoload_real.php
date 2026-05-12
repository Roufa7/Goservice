<?php

// Minimal merged autoloader used to keep the integrated project running
// after branch histories introduced incompatible generated Composer files.

class ComposerAutoloaderInitMergedGoService
{
    private static $loader;

    public static function loadClassLoader($class): void
    {
        if ($class === 'Composer\\Autoload\\ClassLoader') {
            require __DIR__ . '/ClassLoader.php';
        }
    }

    public static function getLoader(): \Composer\Autoload\ClassLoader
    {
        if (self::$loader !== null) {
            return self::$loader;
        }

        spl_autoload_register([self::class, 'loadClassLoader'], true, true);
        $loader = new \Composer\Autoload\ClassLoader(dirname(__DIR__));
        spl_autoload_unregister([self::class, 'loadClassLoader']);

        $vendorDir = dirname(__DIR__);
        $baseDir = dirname($vendorDir);

        $prefixes = [
            'GoService\\' => [$baseDir . '/src'],
            'Geocoder\\' => [$vendorDir . '/willdurand/geocoder'],
            'Clue\\StreamFilter\\' => [$vendorDir . '/clue/stream-filter/src'],
            'Http\\Client\\Curl\\' => [$vendorDir . '/php-http/curl-client/src'],
            'Http\\Client\\' => [$vendorDir . '/php-http/httplug/src'],
            'Http\\Discovery\\' => [$vendorDir . '/php-http/discovery/src'],
            'Http\\Message\\' => [$vendorDir . '/php-http/message/src'],
            'Http\\Message\\MultipartStream\\' => [$vendorDir . '/php-http/multipart-stream-builder/src'],
            'Http\\Promise\\' => [$vendorDir . '/php-http/promise/src'],
            'Nyholm\\Psr7\\' => [$vendorDir . '/nyholm/psr7/src'],
            'Psr\\Http\\Client\\' => [$vendorDir . '/psr/http-client/src'],
            'Psr\\Http\\Factory\\' => [$vendorDir . '/psr/http-factory/src'],
            'Psr\\Http\\Message\\' => [$vendorDir . '/psr/http-message/src'],
            'PHPMailer\\PHPMailer\\' => [$vendorDir . '/phpmailer/phpmailer/src'],
            'Eluceo\\iCal\\' => [$vendorDir . '/eluceo/ical/src'],
            'OpenAI\\' => [$vendorDir . '/openai-php/client/src'],
            'GuzzleHttp\\' => [$vendorDir . '/guzzlehttp/guzzle/src'],
            'GuzzleHttp\\Promise\\' => [$vendorDir . '/guzzlehttp/promises/src'],
            'GuzzleHttp\\Psr7\\' => [$vendorDir . '/guzzlehttp/psr7/src'],
            'GrahamCampbell\\ResultType\\' => [$vendorDir . '/graham-campbell/result-type/src'],
            'Dotenv\\' => [$vendorDir . '/vlucas/phpdotenv/src'],
            'Dompdf\\' => [$vendorDir . '/dompdf/dompdf/src'],
            'Svg\\' => [$vendorDir . '/dompdf/php-svg-lib/src/Svg'],
            'FontLib\\' => [$vendorDir . '/dompdf/php-font-lib/src/FontLib'],
            'Sabberworm\\CSS\\' => [$vendorDir . '/sabberworm/php-css-parser/src'],
            'Masterminds\\' => [$vendorDir . '/masterminds/html5/src'],
            'PhpOption\\' => [$vendorDir . '/phpoption/phpoption/src/PhpOption'],
            'chillerlan\\Settings\\' => [$vendorDir . '/chillerlan/php-settings-container/src'],
            'chillerlan\\QRCode\\' => [$vendorDir . '/chillerlan/php-qrcode/src'],
            'Symfony\\Polyfill\\Ctype\\' => [$vendorDir . '/symfony/polyfill-ctype'],
            'Symfony\\Polyfill\\Mbstring\\' => [$vendorDir . '/symfony/polyfill-mbstring'],
            'Symfony\\Polyfill\\Php80\\' => [$vendorDir . '/symfony/polyfill-php80'],
            'Symfony\\Component\\OptionsResolver\\' => [$vendorDir . '/symfony/options-resolver'],
        ];

        foreach ($prefixes as $prefix => $paths) {
            $loader->setPsr4($prefix, $paths);
        }

        $files = [
            $vendorDir . '/clue/stream-filter/src/functions_include.php',
            $vendorDir . '/php-http/message/src/filters.php',
        ];

        foreach ($files as $file) {
            if (is_file($file)) {
                require_once $file;
            }
        }

        $loader->register(true);
        self::$loader = $loader;

        return $loader;
    }
}
