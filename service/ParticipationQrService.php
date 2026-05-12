<?php

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class ParticipationQrService
{
    public function renderDataUri(string $payload): string
    {
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'eccLevel' => QRCode::ECC_Q,
            'scale' => 5,
            'imageBase64' => true,
            'svgAddXmlHeader' => false,
        ]);

        return (new QRCode($options))->render($payload);
    }

    public function buildReferenceCode(array $participation, array $event): string
    {
        return sprintf(
            'GSE-%05d-E%04d',
            (int) ($participation['id_participation'] ?? 0),
            (int) ($event['id_evenement'] ?? 0)
        );
    }
}
