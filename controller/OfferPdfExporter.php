<?php

if (!class_exists('SimplePdfDocument')) {
    class SimplePdfDocument {
        private float $pageWidth;
        private float $pageHeight;
        private array $pages = [];
        private array $currentCommands = [];
        private string $currentFont = 'F1';
        private float $currentFontSize = 10.0;

        public function __construct(bool $landscape = true) {
            if ($landscape) {
                $this->pageWidth = 841.89;
                $this->pageHeight = 595.28;
            } else {
                $this->pageWidth = 595.28;
                $this->pageHeight = 841.89;
            }
        }

        public function getPageWidth(): float {
            return $this->pageWidth;
        }

        public function getPageHeight(): float {
            return $this->pageHeight;
        }

        public function startPage(): void {
            if (!empty($this->currentCommands)) {
                $this->pages[] = implode("\n", $this->currentCommands);
            }

            $this->currentCommands = [];
            $this->currentFont = 'F1';
            $this->currentFontSize = 10.0;
        }

        public function setFont(string $font, float $size): void {
            $this->currentFont = in_array($font, ['F1', 'F2'], true) ? $font : 'F1';
            $this->currentFontSize = $size;
        }

        public function text(float $x, float $y, string $text): void {
            $pdfY = $this->pageHeight - $y;
            $safeText = $this->escapeText($this->normalizeText($text));
            $this->currentCommands[] = sprintf(
                'BT /%s %.2F Tf 1 0 0 1 %.2F %.2F Tm (%s) Tj ET',
                $this->currentFont,
                $this->currentFontSize,
                $x,
                $pdfY,
                $safeText
            );
        }

        public function line(float $x1, float $y1, float $x2, float $y2): void {
            $pdfY1 = $this->pageHeight - $y1;
            $pdfY2 = $this->pageHeight - $y2;
            $this->currentCommands[] = sprintf('%.2F %.2F m %.2F %.2F l S', $x1, $pdfY1, $x2, $pdfY2);
        }

        public function rect(float $x, float $y, float $width, float $height, bool $fill = false): void {
            $pdfY = $this->pageHeight - $y - $height;
            $this->currentCommands[] = sprintf('%.2F %.2F %.2F %.2F re %s', $x, $pdfY, $width, $height, $fill ? 'f' : 'S');
        }

        public function setLineWidth(float $width): void {
            $this->currentCommands[] = sprintf('%.2F w', $width);
        }

        public function setStrokeGray(float $gray): void {
            $gray = max(0.0, min(1.0, $gray));
            $this->currentCommands[] = sprintf('%.3F G', $gray);
        }

        public function setFillGray(float $gray): void {
            $gray = max(0.0, min(1.0, $gray));
            $this->currentCommands[] = sprintf('%.3F g', $gray);
        }

        public function output(): string {
            $eol = "\r\n";

            if (!empty($this->currentCommands)) {
                $this->pages[] = implode($eol, $this->currentCommands);
            }

            $pageCount = count($this->pages);
            $objects = [];
            $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
            $objects[2] = '<< /Type /Pages /Kids [';
            $fontRegularObject = 3;
            $fontBoldObject = 4;
            $objects[$fontRegularObject] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
            $objects[$fontBoldObject] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';

            $contentObjectNumbers = [];
            $pageObjectNumbers = [];
            $nextObjectNumber = 5;

            foreach ($this->pages as $pageIndex => $pageContent) {
                $contentObjectNumber = $nextObjectNumber++;
                $pageObjectNumber = $nextObjectNumber++;
                $contentObjectNumbers[] = $contentObjectNumber;
                $pageObjectNumbers[] = $pageObjectNumber;

                $objects[$contentObjectNumber] = '<< /Length ' . strlen($pageContent) . " >>" . $eol . 'stream' . $eol . $pageContent . $eol . 'endstream';
                $objects[$pageObjectNumber] = sprintf(
                    '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 %d 0 R /F2 %d 0 R >> >> /Contents %d 0 R >>',
                    $this->pageWidth,
                    $this->pageHeight,
                    $fontRegularObject,
                    $fontBoldObject,
                    $contentObjectNumber
                );
            }

            $kids = array_map(static fn (int $pageObjectNumber): string => $pageObjectNumber . ' 0 R', $pageObjectNumbers);
            $objects[2] .= implode(' ', $kids) . '] /Count ' . $pageCount . ' >>';

            $pdf = "%PDF-1.4" . $eol . "%\xE2\xE3\xCF\xD3" . $eol;
            $offsets = [0 => 0];
            $maxObjectNumber = max(array_keys($objects));

            for ($objectNumber = 1; $objectNumber <= $maxObjectNumber; $objectNumber++) {
                if (!isset($objects[$objectNumber])) {
                    continue;
                }

                $offsets[$objectNumber] = strlen($pdf);
                $pdf .= $objectNumber . ' 0 obj' . $eol . $objects[$objectNumber] . $eol . 'endobj' . $eol;
            }

            $xrefPosition = strlen($pdf);
            $pdf .= 'xref' . $eol . '0 ' . ($maxObjectNumber + 1) . $eol;
            $pdf .= '0000000000 65535 f' . $eol;

            for ($objectNumber = 1; $objectNumber <= $maxObjectNumber; $objectNumber++) {
                $offset = $offsets[$objectNumber] ?? 0;
                $pdf .= sprintf('%010d 00000 n', $offset) . $eol;
            }

            $pdf .= 'trailer' . $eol . '<< /Size ' . ($maxObjectNumber + 1) . ' /Root 1 0 R >>' . $eol . 'startxref' . $eol . $xrefPosition . $eol . '%%EOF';

            return $pdf;
        }

        private function normalizeText(string $text): string {
            $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            if (function_exists('iconv')) {
                $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
                if ($converted !== false) {
                    return $converted;
                }
            }

            return preg_replace('/[^\x20-\x7E]/', '?', $text) ?? $text;
        }

        private function escapeText(string $text): string {
            return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
        }
    }
}

if (!class_exists('OfferPdfExporter')) {
    class OfferPdfExporter {
        public static function download(array $offers, array $context = []): void {
            if (function_exists('ob_get_length') && ob_get_length() !== false) {
                while (ob_get_level() > 0) {
                    ob_end_clean();
                }
            }

            $generatedAt = $context['generatedAt'] ?? new DateTimeImmutable('now');
            if (!$generatedAt instanceof DateTimeInterface) {
                $generatedAt = new DateTimeImmutable('now');
            }

            $searchLabel = trim((string) ($context['searchLabel'] ?? ''));
            $sortLabel = trim((string) ($context['sortLabel'] ?? ''));
            $pageTitle = trim((string) ($context['title'] ?? 'Gestion des offres'));
            $pdf = new SimplePdfDocument(true);
            $pageWidth = $pdf->getPageWidth();
            $pageHeight = $pdf->getPageHeight();
            $marginX = 34.0;
            $topTitleY = 26.0;
            $topDateY = 26.0;
            $footerY = $pageHeight - 22.0;
            $tableStartY = 112.0;
            $tableHeaderHeight = 24.0;
            $rowHeight = 20.0;
            $usableWidth = $pageWidth - ($marginX * 2);
            $bottomLimit = $pageHeight - 66.0;
            $rowsPerPage = max(1, (int) floor(($bottomLimit - ($tableStartY + $tableHeaderHeight)) / $rowHeight));
            $pageCount = max(1, (int) ceil(max(1, count($offers)) / $rowsPerPage));

            $columns = [
                ['key' => 'titre', 'label' => 'Titre', 'width' => 160],
                ['key' => 'type_service', 'label' => 'Type', 'width' => 92],
                ['key' => 'localisation', 'label' => 'Localisation', 'width' => 92],
                ['key' => 'date_publication', 'label' => 'Publication', 'width' => 88],
                ['key' => 'date_expiration', 'label' => 'Expiration', 'width' => 88],
                ['key' => 'statut', 'label' => 'Statut', 'width' => 70],
                ['key' => 'prix', 'label' => 'Prix', 'width' => 70],
                ['key' => 'admin', 'label' => 'Admin', 'width' => 115],
            ];

            $totalColumnWidth = array_sum(array_column($columns, 'width'));
            if ($totalColumnWidth < $usableWidth) {
                $columns[count($columns) - 1]['width'] += $usableWidth - $totalColumnWidth;
            }

            $renderPageChrome = function (SimplePdfDocument $document, int $pageNumber, int $pageCount) use ($generatedAt, $pageTitle, $searchLabel, $sortLabel, $marginX, $topTitleY, $topDateY, $footerY, $pageWidth): void {
                $dateText = 'Date de generation : ' . $generatedAt->format('d/m/Y H:i');
                $pageText = 'Page ' . $pageNumber . ' / ' . $pageCount;

                $document->setFont('F2', 18);
                $document->text($marginX, $topTitleY, $pageTitle);

                $document->setFont('F1', 10);
                $document->text($marginX, $topTitleY + 18, $searchLabel !== '' ? 'Recherche : ' . $searchLabel : 'Toutes les offres');
                if ($sortLabel !== '') {
                    $document->text($marginX, $topTitleY + 32, 'Tri : ' . $sortLabel);
                }

                $dateWidth = strlen($dateText) * 4.8;
                $document->text(max($marginX, $pageWidth - $marginX - $dateWidth), $topDateY, $dateText);
                $document->setStrokeGray(0.78);
                $document->setLineWidth(0.8);
                $document->line($marginX, 60.0, $pageWidth - $marginX, 60.0);

                $document->setFont('F1', 9.5);
                $document->text($marginX, $footerY, 'GoService • Gestion des offres');
                $pageWidthText = strlen($pageText) * 4.6;
                $document->text(($pageWidth - $pageWidthText) / 2, $footerY, $pageText);
                $document->text(max($marginX, $pageWidth - $marginX - $dateWidth), $footerY, $dateText);
                $document->line($marginX, $footerY - 6, $pageWidth - $marginX, $footerY - 6);
            };

            $renderTableHeader = function (SimplePdfDocument $document, array $columns, float $startX, float $startY): float {
                $tableWidth = array_sum(array_column($columns, 'width'));
                $document->setFont('F2', 10);
                $document->setFillGray(0.92);
                $document->rect($startX, $startY - 2, $tableWidth, 24, true);
                $document->setFillGray(0.0);
                $document->setStrokeGray(0.72);
                $document->setLineWidth(0.7);
                $document->line($startX, $startY + 22, $startX + $tableWidth, $startY + 22);

                $x = $startX;
                foreach ($columns as $column) {
                    $document->text($x + 5, $startY + 13, $column['label']);
                    $x += $column['width'];
                }

                return $startY + 24;
            };

            $renderRow = function (SimplePdfDocument $document, array $columns, array $offer, float $startX, float $startY, float $rowHeight): void {
                $tableWidth = array_sum(array_column($columns, 'width'));
                $document->setFont('F1', 9.25);
                $document->setStrokeGray(0.85);
                $document->setLineWidth(0.45);
                $document->line($startX, $startY + $rowHeight - 1, $startX + $tableWidth, $startY + $rowHeight - 1);

                $values = [
                    'titre' => self::truncate((string) ($offer['titre'] ?? ''), 28),
                    'type_service' => self::truncate((string) ($offer['type_service'] ?? 'N/A'), 16),
                    'localisation' => self::truncate((string) ($offer['localisation'] ?? 'N/A'), 18),
                    'date_publication' => self::formatDate((string) ($offer['date_publication'] ?? '')),
                    'date_expiration' => self::formatDate((string) ($offer['date_expiration'] ?? '')),
                    'statut' => self::truncate(ucfirst((string) ($offer['statut'] ?? '')), 10),
                    'prix' => self::formatPrice($offer['prix'] ?? null),
                    'admin' => self::truncate(self::buildAdminLabel($offer), 20),
                ];

                $x = $startX;
                foreach ($columns as $column) {
                    $document->text($x + 5, $startY + 12, $values[$column['key']] ?? '');
                    $x += $column['width'];
                }
            };

            $pdf->startPage();
            $renderPageChrome($pdf, 1, $pageCount);
            $currentY = $tableStartY;
            $currentY = $renderTableHeader($pdf, $columns, $marginX, $currentY);

            if (empty($offers)) {
                $pdf->setFont('F1', 11);
                $pdf->text($marginX, $currentY + 20, 'Aucune offre a exporter.');
            } else {
                $pageNumber = 1;
                foreach ($offers as $offer) {
                    if (($currentY + $rowHeight) > $bottomLimit) {
                        $pageNumber++;
                        $pdf->startPage();
                        $renderPageChrome($pdf, $pageNumber, $pageCount);
                        $currentY = $tableStartY;
                        $currentY = $renderTableHeader($pdf, $columns, $marginX, $currentY);
                    }

                    $renderRow($pdf, $columns, $offer, $marginX, $currentY, $rowHeight);
                    $currentY += $rowHeight;
                }
            }

            $filename = 'offres_' . $generatedAt->format('Ymd_His') . '.pdf';

            $pdfContent = $pdf->output();

            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $filename . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . strlen($pdfContent));

            echo $pdfContent;
            exit;
        }

        private static function truncate(string $value, int $length): string {
            $value = trim(preg_replace('/\s+/', ' ', $value) ?? $value);

            if (function_exists('mb_strlen') && function_exists('mb_substr')) {
                if (mb_strlen($value) <= $length) {
                    return $value;
                }

                return rtrim(mb_substr($value, 0, max(1, $length - 1))) . '…';
            }

            if (strlen($value) <= $length) {
                return $value;
            }

            return rtrim(substr($value, 0, max(1, $length - 1))) . '...';
        }

        private static function formatDate(?string $value): string {
            if ($value === null || trim($value) === '') {
                return 'N/A';
            }

            $timestamp = strtotime($value);
            return $timestamp ? date('d/m/Y', $timestamp) : 'N/A';
        }

        private static function formatPrice(mixed $value): string {
            if ($value === null || $value === '') {
                return 'N/A';
            }

            return number_format((float) $value, 2, ',', ' ') . ' DT';
        }

        private static function buildAdminLabel(array $offer): string {
            $parts = [];

            if (!empty($offer['admin_prenom'])) {
                $parts[] = (string) $offer['admin_prenom'];
            }

            if (!empty($offer['admin_nom'])) {
                $parts[] = (string) $offer['admin_nom'];
            }

            $label = trim(implode(' ', $parts));

            if ($label !== '') {
                return $label;
            }

            return !empty($offer['id_admin']) ? 'Admin #' . (string) $offer['id_admin'] : 'N/A';
        }
    }
}