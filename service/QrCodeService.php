<?php

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class QrCodeService
{
    public static function generateDataUri(int $serviceId, string $baseUrl): string
    {
        $serviceUrl = self::getServiceUrl($serviceId, $baseUrl);

        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'eccLevel' => QRCode::ECC_Q,
            'scale' => 6,
            'outputBase64' => true,
            'svgAddXmlHeader' => false,
        ]);

        return (new QRCode($options))->render($serviceUrl);
    }

    public static function getServiceUrl(int $serviceId, string $baseUrl): string
    {
        return rtrim($baseUrl, '/') . '?page=serviceDetails&id=' . $serviceId;
    }
}
