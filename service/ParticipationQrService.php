<?php

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class ParticipationQrService
{
    public function renderDataUri(array $participation, array $event): string
    {
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'eccLevel' => QRCode::ECC_Q,
            'scale' => 5,
            'imageBase64' => true,
            'svgAddXmlHeader' => false,
        ]);

        return (new QRCode($options))->render($this->buildPayload($participation, $event));
    }

    public function buildPayload(array $participation, array $event): string
    {
        $reference = $this->buildReferenceCode($participation, $event);
        $status = strtoupper(str_replace(' ', '_', (string) ($participation['statut_participation'] ?? 'en attente')));

        $lines = [
            'GS_EVENT_PASS',
            'REF=' . $reference,
            'EVENT_ID=' . (int) ($event['id_evenement'] ?? 0),
            'PARTICIPATION_ID=' . (int) ($participation['id_participation'] ?? 0),
            'TITLE=' . $this->sanitizeToken((string) ($event['titre'] ?? ''), 60),
            'START=' . $this->compactDate((string) ($event['date_debut'] ?? '')),
            'LOCATION=' . $this->sanitizeToken((string) ($event['lieu'] ?? ''), 50),
            'STATUS=' . $status,
        ];

        return implode("\n", $lines);
    }

    public function buildReferenceCode(array $participation, array $event): string
    {
        return sprintf(
            'GSE-%05d-E%04d',
            (int) ($participation['id_participation'] ?? 0),
            (int) ($event['id_evenement'] ?? 0)
        );
    }

    private function compactDate(string $value): string
    {
        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return 'UNKNOWN';
        }

        return date('YmdHi', $timestamp);
    }

    private function sanitizeToken(string $value, int $limit): string
    {
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? $value);
        $value = preg_replace('/[^\p{L}\p{N}\s\-_,.:]/u', '', $value) ?? $value;

        if ($value === '') {
            return 'N/A';
        }

        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $limit);
        }

        return substr($value, 0, $limit);
    }
}