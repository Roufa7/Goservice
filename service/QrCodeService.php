<?php

/**
 * QrCodeService — VERSION SVG (Composer + endroid/qr-code, sans GD)
 *
 * Utilise le SvgWriter d'endroid/qr-code qui ne requiert PAS l'extension GD.
 * Compatible PHP >= 7.4, XAMPP sans configuration supplémentaire.
 *
 * Placement : GoService_v3/service/QrCodeService.php
 * Prérequis : endroid/qr-code ^4.8 (déjà installé via Composer)
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\SvgWriter;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel\ErrorCorrectionLevelHigh;
use Endroid\QrCode\RoundBlockSizeMode\RoundBlockSizeModeMargin;

class QrCodeService
{
    /**
     * Génère le QR Code en SVG inline (data URI) — aucune extension GD requise.
     *
     * @param  int    $serviceId
     * @param  string $baseUrl   ex: "http://localhost/GoService_v3/view/front/index.php"
     * @return string            data:image/svg+xml;base64,...
     */
    public static function generateDataUri(int $serviceId, string $baseUrl): string
    {
        $serviceUrl = self::getServiceUrl($serviceId, $baseUrl);

        $qrCode = QrCode::create($serviceUrl)
            ->setEncoding(new Encoding('UTF-8'))
            ->setErrorCorrectionLevel(new ErrorCorrectionLevelHigh())
            ->setSize(280)
            ->setMargin(14)
            ->setRoundBlockSizeMode(new RoundBlockSizeModeMargin())
            ->setForegroundColor(new Color(14, 41, 65))
            ->setBackgroundColor(new Color(255, 255, 255));

        $writer = new SvgWriter();
        $result = $writer->write($qrCode);

        return 'data:image/svg+xml;base64,' . base64_encode($result->getString());
    }

    /**
     * Retourne l'URL du service encodée dans le QR Code.
     *
     * @param  int    $serviceId
     * @param  string $baseUrl
     * @return string
     */
    public static function getServiceUrl(int $serviceId, string $baseUrl): string
    {
        return rtrim($baseUrl, '/') . '?page=serviceDetails&id=' . $serviceId;
    }
}