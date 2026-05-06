<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../model/EventRepository.php';
require_once __DIR__ . '/../model/ParticipationRepository.php';
require_once __DIR__ . '/../service/EventCalendarService.php';
require_once __DIR__ . '/../service/ParticipationQrService.php';
require_once __DIR__ . '/../service/ParticipationEmailService.php';
require_once __DIR__ . '/../service/OllamaEventAssistantService.php';

abstract class AbstractEventController
{
    protected EventRepository $eventRepository;
    protected ParticipationRepository $participationRepository;
    protected EventCalendarService $calendarService;
    protected ParticipationQrService $qrService;
    protected ParticipationEmailService $emailService;
    protected OllamaEventAssistantService $ollamaAssistant;

    protected array $eventTypes = [
        'atelier' => 'Atelier',
        'formation' => 'Formation',
        'promotion' => 'Promotion',
        'service' => 'Service',
    ];

    protected array $eventStatuses = [
        'prevu' => 'Prevu',
        'en cours' => 'En cours',
        'termine' => 'Termine',
        'annule' => 'Annule',
    ];

    protected array $participationStatuses = [
        'confirme' => 'Confirme',
        'en attente' => 'En attente',
        'annule' => 'Annule',
    ];

    protected array $eventSortOptions = [
        'priority' => 'Vue prioritaire',
        'date_asc' => 'Date croissante',
        'date_desc' => 'Date decroissante',
        'popular_desc' => 'Plus demandes',
        'places_desc' => 'Plus de places',
        'title_asc' => 'Titre A-Z',
        'created_desc' => 'Ajoutes recemment',
    ];

    protected array $eventAvailabilityOptions = [
        'open' => 'Inscriptions ouvertes',
        'full' => 'Complet',
        'almost_full' => 'Presque complet',
        'closed' => 'Cloture ou annule',
    ];

    protected array $participationSortOptions = [
        'registered_desc' => 'Plus recentes',
        'registered_asc' => 'Plus anciennes',
        'name_asc' => 'Nom A-Z',
        'name_desc' => 'Nom Z-A',
        'event_asc' => 'Evenement A-Z',
        'status_asc' => 'Statut A-Z',
    ];

    public function __construct()
    {
        $this->bootSession();

        $pdo = config::getConnexion();
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        $this->eventRepository = new EventRepository($pdo);
        $this->participationRepository = new ParticipationRepository($pdo);
        $this->calendarService = new EventCalendarService();
        $this->qrService = new ParticipationQrService();
        $this->emailService = new ParticipationEmailService($this->calendarService);
        $this->ollamaAssistant = new OllamaEventAssistantService();

        $this->ensureUploadDirectory();
    }

    protected function bootSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    protected function ensureUploadDirectory(): void
    {
        $directory = $this->uploadDirectory();

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
    }

    protected function uploadDirectory(): string
    {
        return dirname(__DIR__) . '/assets/uploads/events';
    }

    public function getCsrfToken(): string
    {
        if (empty($_SESSION['event_module_csrf'])) {
            $_SESSION['event_module_csrf'] = bin2hex(random_bytes(32));
        }

        return (string) $_SESSION['event_module_csrf'];
    }

    protected function validateCsrf(?string $token): bool
    {
        return is_string($token)
            && !empty($_SESSION['event_module_csrf'])
            && hash_equals((string) $_SESSION['event_module_csrf'], $token);
    }

    protected function flash(string $type, string $message, string $scope): void
    {
        $_SESSION['event_module_flashes'][$scope][] = [
            'type' => $type,
            'message' => $message,
        ];
    }

    protected function pullFlashes(string $scope): array
    {
        $flashes = $_SESSION['event_module_flashes'][$scope] ?? [];
        unset($_SESSION['event_module_flashes'][$scope]);

        return is_array($flashes) ? $flashes : [];
    }

    protected function rememberForm(string $scope, array $formData, array $errors): void
    {
        $_SESSION['event_module_forms'][$scope] = [
            'values' => $formData,
            'errors' => $errors,
        ];
    }

    protected function pullForm(string $scope): array
    {
        $state = $_SESSION['event_module_forms'][$scope] ?? [
            'values' => [],
            'errors' => [],
        ];
        unset($_SESSION['event_module_forms'][$scope]);

        return [
            'values' => is_array($state['values'] ?? null) ? $state['values'] : [],
            'errors' => is_array($state['errors'] ?? null) ? $state['errors'] : [],
        ];
    }

    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    protected function buildEventUrl(array $params = []): string
    {
        $query = array_merge(['page' => 'events'], $params);
        return 'index.php?' . http_build_query($query);
    }

    protected function buildCalendarDownloadUrl(int $eventId): string
    {
        return $this->buildEventUrl([
            'event_id' => $eventId,
            'download' => 'calendar',
        ]);
    }

    protected function buildAdminEventsUrl(?int $eventId = null): string
    {
        $query = ['page' => 'events'];
        if ($eventId !== null && $eventId > 0) {
            $query['manage_event'] = $eventId;
        }

        return '../back/index.php?' . http_build_query($query);
    }

    protected function buildFrontEventsUrl(?int $eventId = null, string $hash = ''): string
    {
        $query = ['page' => 'events'];
        if ($eventId !== null && $eventId > 0) {
            $query['event_id'] = $eventId;
        }

        $url = '../front/index.php?' . http_build_query($query);
        if ($hash !== '') {
            $url .= str_starts_with($hash, '#') ? $hash : '#' . $hash;
        }

        return $url;
    }

    protected function buildMapUrl(string $location): string
    {
        return 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode(trim($location));
    }

    protected function buildReturnUrl(?string $returnTo, array $overrides = [], string $hash = ''): string
    {
        $query = ['page' => 'events'];

        if (is_string($returnTo) && trim($returnTo) !== '') {
            $parsed = parse_url($returnTo);
            $path = (string) ($parsed['path'] ?? '');
            $candidate = [];
            parse_str((string) ($parsed['query'] ?? ''), $candidate);

            if (($path === '' || basename($path) === 'index.php') && ($candidate['page'] ?? '') === 'events') {
                $query = array_merge($query, $candidate);
            }
        }

        foreach ($overrides as $key => $value) {
            if ($value === null || $value === '') {
                unset($query[$key]);
                continue;
            }

            $query[$key] = $value;
        }

        $url = 'index.php?' . http_build_query($query);
        if ($hash !== '') {
            $url .= str_starts_with($hash, '#') ? $hash : '#' . $hash;
        }

        return $url;
    }

    protected function countActiveFilters(array $filters, array $ignoredKeys = []): int
    {
        $count = 0;

        foreach ($filters as $key => $value) {
            if (in_array($key, $ignoredKeys, true)) {
                continue;
            }

            if (is_string($value) && trim($value) !== '') {
                $count++;
            } elseif (is_numeric($value) && (string) $value !== '0') {
                $count++;
            }
        }

        return $count;
    }

    protected function normalizeEvent(array $event): array
    {
        $now = new DateTimeImmutable();
        $startDate = $this->safeDate($event['date_debut'] ?? null);
        $endDate = $this->safeDate($event['date_fin'] ?? null);

        $event['participants_count'] = (int) ($event['participants_count'] ?? 0);
        $event['nb_places'] = max(0, (int) ($event['nb_places'] ?? 0));
        $event['remaining_places'] = max(0, $event['nb_places'] - $event['participants_count']);
        $event['fill_rate'] = $event['nb_places'] > 0
            ? min(100, (int) round(($event['participants_count'] / $event['nb_places']) * 100))
            : 0;
        $event['type_label'] = $this->eventTypes[$event['type_evenement'] ?? ''] ?? ucfirst((string) ($event['type_evenement'] ?? ''));
        $event['status_label'] = $this->eventStatuses[$event['statut'] ?? ''] ?? ucfirst((string) ($event['statut'] ?? ''));
        $event['status_class'] = $this->eventStatusClass((string) ($event['statut'] ?? ''));
        $event['image_path'] = $this->eventImagePath($event['image'] ?? '');
        $event['start_display'] = $this->formatDateTime($event['date_debut'] ?? null);
        $event['end_display'] = $this->formatDateTime($event['date_fin'] ?? null);
        $event['date_chip'] = $this->formatShortDate($event['date_debut'] ?? null);
        $event['is_registration_open'] = $this->isRegistrationOpen($event);
        $event['is_cancelled'] = (string) ($event['statut'] ?? '') === 'annule';
        $event['has_started'] = $startDate ? $startDate <= $now : false;
        $event['has_ended'] = $endDate ? $endDate < $now : false;
        $event['is_live_window'] = $startDate && $endDate ? ($startDate <= $now && $endDate >= $now) : false;
        $event['is_full'] = $event['remaining_places'] <= 0;
        $event['is_closed'] = !$event['is_registration_open'];
        $event['is_almost_full'] = !$event['is_full'] && (
            $event['fill_rate'] >= 80
            || ($event['nb_places'] > 0 && $event['remaining_places'] <= 3)
        );
        $event['is_upcoming'] = !$event['is_cancelled'] && !$event['has_started'];
        $event['is_in_progress'] = !$event['is_cancelled'] && $event['is_live_window'];
        $event['is_finished'] = !$event['is_cancelled'] && $event['has_ended'];
        $event['days_until_start'] = $this->daysUntil($startDate, $now);
        $event['days_until_end'] = $this->daysUntil($endDate, $now);
        $event['month_key'] = $startDate ? $startDate->format('Y-m') : 'indetermine';
        $event['month_label'] = $startDate ? $this->formatMonthLabel($startDate) : 'A planifier';
        $event['timeline_bucket'] = $this->timelineBucket($startDate, $now, $event);
        $event['countdown_text'] = $this->eventCountdownText($event, $startDate, $endDate, $now);
        $event['demand_text'] = $this->eventDemandText($event);
        $event['registration_title'] = $this->registrationStateTitle($event);
        $event['registration_message'] = $this->registrationStateMessage($event);
        $event['smart_badges'] = $this->buildSmartBadges($event);
        $event['availability_text'] = $event['remaining_places'] > 0
            ? $event['remaining_places'] . ' place(s) restante(s)'
            : 'Complet';
        $event['priority_score'] = $this->priorityScore($event);

        return $event;
    }

    protected function normalizeEvents(array $events): array
    {
        return array_map(fn(array $event): array => $this->normalizeEvent($event), $events);
    }

    protected function normalizeParticipation(array $participation): array
    {
        $participation['status_label'] = $this->participationStatuses[$participation['statut_participation'] ?? '']
            ?? ucfirst((string) ($participation['statut_participation'] ?? ''));
        $participation['status_class'] = $this->eventStatusClass((string) ($participation['statut_participation'] ?? ''));
        $participation['registered_display'] = $this->formatDateTime($participation['date_inscription'] ?? null);

        return $participation;
    }

    protected function normalizeParticipations(array $participations): array
    {
        return array_map(fn(array $participation): array => $this->normalizeParticipation($participation), $participations);
    }

    protected function eventStatusClass(string $status): string
    {
        return match ($status) {
            'prevu' => 'status-pending',
            'en cours' => 'status-open',
            'termine', 'confirme' => 'status-done',
            'annule' => 'status-cancelled',
            'en attente' => 'status-pending',
            default => 'status-neutral',
        };
    }

    protected function isRegistrationOpen(array $event): bool
    {
        if (($event['statut'] ?? '') === 'annule') {
            return false;
        }

        if ((int) ($event['remaining_places'] ?? 0) <= 0) {
            return false;
        }

        $end = strtotime((string) ($event['date_fin'] ?? ''));
        if ($end === false) {
            return true;
        }

        return $end >= time();
    }

    protected function eventImagePath(?string $path): string
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }

        $normalizedPath = ltrim(str_replace('\\', '/', $path), '/');
        $absolutePath = dirname(__DIR__) . '/' . $normalizedPath;

        if (!is_file($absolutePath)) {
            return '';
        }

        return '../../' . $normalizedPath;
    }

    protected function formatDateTime(?string $value): string
    {
        if (!$value) {
            return '-';
        }

        try {
            $date = new DateTimeImmutable($value);
            return $date->format('d/m/Y H:i');
        } catch (Throwable) {
            return (string) $value;
        }
    }

    protected function formatShortDate(?string $value): string
    {
        if (!$value) {
            return '-';
        }

        try {
            $date = new DateTimeImmutable($value);
            return $date->format('d M');
        } catch (Throwable) {
            return (string) $value;
        }
    }

    protected function safeDate(?string $value): ?DateTimeImmutable
    {
        if (!$value) {
            return null;
        }

        try {
            return new DateTimeImmutable($value);
        } catch (Throwable) {
            return null;
        }
    }

    protected function daysUntil(?DateTimeImmutable $target, DateTimeImmutable $now): ?int
    {
        if (!$target) {
            return null;
        }

        $seconds = $target->getTimestamp() - $now->getTimestamp();
        return (int) floor($seconds / 86400);
    }

    protected function formatMonthLabel(DateTimeImmutable $date): string
    {
        $months = [
            '01' => 'Janvier',
            '02' => 'Fevrier',
            '03' => 'Mars',
            '04' => 'Avril',
            '05' => 'Mai',
            '06' => 'Juin',
            '07' => 'Juillet',
            '08' => 'Aout',
            '09' => 'Septembre',
            '10' => 'Octobre',
            '11' => 'Novembre',
            '12' => 'Decembre',
        ];

        $month = $months[$date->format('m')] ?? $date->format('m');

        return $month . ' ' . $date->format('Y');
    }

    protected function timelineBucket(?DateTimeImmutable $startDate, DateTimeImmutable $now, array $event): string
    {
        if (($event['is_cancelled'] ?? false) || ($event['is_finished'] ?? false)) {
            return 'archives';
        }

        if (($event['is_in_progress'] ?? false) === true) {
            return 'en_cours';
        }

        if (!$startDate) {
            return 'a_planifier';
        }

        $days = $this->daysUntil($startDate, $now);
        if ($days !== null && $days <= 7) {
            return 'this_week';
        }

        if ($startDate->format('Y-m') === $now->format('Y-m')) {
            return 'this_month';
        }

        return 'upcoming';
    }

    protected function eventCountdownText(array $event, ?DateTimeImmutable $startDate, ?DateTimeImmutable $endDate, DateTimeImmutable $now): string
    {
        if (($event['is_cancelled'] ?? false) === true) {
            return 'Evenement annule';
        }

        if (($event['is_in_progress'] ?? false) === true && $endDate) {
            $hoursLeft = max(1, (int) ceil(($endDate->getTimestamp() - $now->getTimestamp()) / 3600));
            return 'En cours - se termine dans ' . $hoursLeft . ' h';
        }

        if (($event['is_finished'] ?? false) === true) {
            return 'Evenement termine';
        }

        if ($startDate) {
            $daysUntilStart = $this->daysUntil($startDate, $now);
            if ($daysUntilStart !== null) {
                if ($daysUntilStart <= 0) {
                    return 'Demarre aujourd\'hui';
                }

                if ($daysUntilStart === 1) {
                    return 'Demarre demain';
                }

                return 'Demarre dans ' . $daysUntilStart . ' jours';
            }
        }

        return 'Date a confirmer';
    }

    protected function eventDemandText(array $event): string
    {
        if (($event['is_full'] ?? false) === true) {
            return 'Complet';
        }

        if (($event['is_almost_full'] ?? false) === true) {
            return 'Forte demande';
        }

        if (($event['participants_count'] ?? 0) <= 0) {
            return 'Aucune inscription pour le moment';
        }

        return 'Encore ' . (int) ($event['remaining_places'] ?? 0) . ' place(s) libre(s)';
    }

    protected function registrationStateTitle(array $event): string
    {
        if (($event['is_cancelled'] ?? false) === true) {
            return 'Inscriptions indisponibles';
        }

        if (($event['is_full'] ?? false) === true) {
            return 'Evenement complet';
        }

        if (($event['is_finished'] ?? false) === true) {
            return 'Inscriptions cloturees';
        }

        if (($event['is_in_progress'] ?? false) === true) {
            return 'Evenement en cours';
        }

        return 'Inscriptions ouvertes';
    }

    protected function registrationStateMessage(array $event): string
    {
        if (($event['is_cancelled'] ?? false) === true) {
            return 'Cet evenement a ete annule. Les inscriptions ne sont plus possibles.';
        }

        if (($event['is_full'] ?? false) === true) {
            return 'Toutes les places sont prises pour cet evenement.';
        }

        if (($event['is_finished'] ?? false) === true) {
            return 'La date de fin est depassee, les inscriptions sont donc fermees.';
        }

        if (($event['is_in_progress'] ?? false) === true) {
            return 'L\'evenement est en cours et il ne reste que peu de temps pour intervenir.';
        }

        if (($event['is_almost_full'] ?? false) === true) {
            return 'Les inscriptions sont ouvertes mais il reste tres peu de places.';
        }

        return 'Les inscriptions sont ouvertes et votre demande sera enregistree en attente de validation.';
    }

    protected function buildSmartBadges(array $event): array
    {
        $badges = [];

        if (($event['is_in_progress'] ?? false) === true) {
            $badges[] = ['label' => 'En cours', 'class' => 'status-open'];
        } elseif (($event['is_upcoming'] ?? false) === true) {
            $badges[] = ['label' => 'A venir', 'class' => 'status-pending'];
        }

        if (($event['is_cancelled'] ?? false) === true) {
            $badges[] = ['label' => 'Annule', 'class' => 'status-cancelled'];
        } elseif (($event['is_full'] ?? false) === true) {
            $badges[] = ['label' => 'Complet', 'class' => 'status-cancelled'];
        } elseif (($event['is_almost_full'] ?? false) === true) {
            $badges[] = ['label' => 'Presque complet', 'class' => 'status-pending'];
        } elseif (($event['is_registration_open'] ?? false) === true) {
            $badges[] = ['label' => 'Ouvert', 'class' => 'status-done'];
        }

        if (($event['days_until_start'] ?? null) !== null && $event['days_until_start'] >= 0 && $event['days_until_start'] <= 3) {
            $badges[] = ['label' => 'Bientot', 'class' => 'status-open'];
        }

        return $badges;
    }

    protected function priorityScore(array $event): int
    {
        $score = 0;

        if (($event['is_registration_open'] ?? false) === true) {
            $score += 300;
        }

        if (($event['is_upcoming'] ?? false) === true) {
            $score += 150;
        }

        if (($event['is_in_progress'] ?? false) === true) {
            $score += 120;
        }

        if (($event['is_almost_full'] ?? false) === true) {
            $score += 40;
        }

        $score += (int) ($event['participants_count'] ?? 0);

        return $score;
    }

    protected function buildEventFilterChips(array $filters): array
    {
        $chips = [];

        if (!empty($filters['search'])) {
            $chips[] = ['label' => 'Recherche', 'value' => trim((string) $filters['search'])];
        }

        if (!empty($filters['type']) && isset($this->eventTypes[$filters['type']])) {
            $chips[] = ['label' => 'Type', 'value' => $this->eventTypes[$filters['type']]];
        }

        if (!empty($filters['status']) && isset($this->eventStatuses[$filters['status']])) {
            $chips[] = ['label' => 'Statut', 'value' => $this->eventStatuses[$filters['status']]];
        }

        if (!empty($filters['availability']) && isset($this->eventAvailabilityOptions[$filters['availability']])) {
            $chips[] = ['label' => 'Disponibilite', 'value' => $this->eventAvailabilityOptions[$filters['availability']]];
        }

        if (!empty($filters['date_from'])) {
            $chips[] = ['label' => 'A partir du', 'value' => $this->formatShortDate($filters['date_from'])];
        }

        if (!empty($filters['date_to'])) {
            $chips[] = ['label' => 'Jusqu\'au', 'value' => $this->formatShortDate($filters['date_to'])];
        }

        return $chips;
    }

    protected function buildTimelineGroups(array $events): array
    {
        usort($events, static function (array $left, array $right): int {
            return strcmp((string) ($left['date_debut'] ?? ''), (string) ($right['date_debut'] ?? ''));
        });

        $groups = [];

        foreach ($events as $event) {
            $monthKey = (string) ($event['month_key'] ?? 'indetermine');

            if (!isset($groups[$monthKey])) {
                $groups[$monthKey] = [
                    'label' => (string) ($event['month_label'] ?? 'A planifier'),
                    'events' => [],
                ];
            }

            $groups[$monthKey]['events'][] = $event;
        }

        return array_values($groups);
    }

    protected function buildTimelineSummary(array $events): array
    {
        $summary = [
            'this_week' => 0,
            'this_month' => 0,
            'upcoming' => 0,
            'en_cours' => 0,
        ];

        foreach ($events as $event) {
            $bucket = (string) ($event['timeline_bucket'] ?? '');
            if (array_key_exists($bucket, $summary)) {
                $summary[$bucket]++;
            }
        }

        return $summary;
    }

    protected function chooseFeaturedEvent(array $events): ?array
    {
        if (!$events) {
            return null;
        }

        $featured = null;
        $bestScore = PHP_INT_MIN;

        foreach ($events as $event) {
            $score = (int) ($event['priority_score'] ?? 0);

            if (($event['is_registration_open'] ?? false) === true) {
                $days = (int) ($event['days_until_start'] ?? 999);
                $score += max(0, 60 - max(0, $days));
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $featured = $event;
            }
        }

        return $featured;
    }

    protected function resolvePage(mixed $value): int
    {
        $page = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        return $page !== false ? (int) $page : 1;
    }

    protected function paginateItems(array $items, int $page, int $perPage): array
    {
        $perPage = max(1, $perPage);
        $totalItems = count($items);
        $totalPages = max(1, (int) ceil($totalItems / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        return [
            'items' => array_slice($items, $offset, $perPage),
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total_items' => $totalItems,
                'total_pages' => $totalPages,
                'has_previous' => $page > 1,
                'has_next' => $page < $totalPages,
                'from' => $totalItems > 0 ? $offset + 1 : 0,
                'to' => min($offset + $perPage, $totalItems),
            ],
        ];
    }

    protected function buildDonutChart(array $segments, string $centerValue, string $centerLabel): array
    {
        $palette = ['#EE5828', '#142738', '#4CAF50', '#F4A261', '#8AA4B8'];
        $legend = [];
        $gradientParts = [];
        $total = 0.0;

        foreach ($segments as $index => $segment) {
            $value = max(0, (float) ($segment['value'] ?? 0));
            if ($value <= 0) {
                continue;
            }

            $color = (string) ($segment['color'] ?? $palette[$index % count($palette)]);
            $legend[] = [
                'label' => (string) ($segment['label'] ?? 'Segment'),
                'value' => $value,
                'display_value' => $this->formatNumericChartValue($value),
                'color' => $color,
            ];
            $total += $value;
        }

        if ($total <= 0) {
            return [
                'total' => 0,
                'center_value' => $centerValue,
                'center_label' => $centerLabel,
                'gradient' => 'conic-gradient(rgba(20,39,56,0.12) 0deg 360deg)',
                'segments' => [],
                'is_empty' => true,
            ];
        }

        $currentAngle = 0.0;
        foreach ($legend as $index => $segment) {
            $slice = ($segment['value'] / $total) * 360;
            $endAngle = $currentAngle + $slice;
            $legend[$index]['percentage'] = (int) round(($segment['value'] / $total) * 100);
            $gradientParts[] = sprintf(
                '%s %.2fdeg %.2fdeg',
                $segment['color'],
                $currentAngle,
                $endAngle
            );
            $currentAngle = $endAngle;
        }

        return [
            'total' => $total,
            'center_value' => $centerValue,
            'center_label' => $centerLabel,
            'gradient' => 'conic-gradient(' . implode(', ', $gradientParts) . ')',
            'segments' => $legend,
            'is_empty' => false,
        ];
    }

    protected function buildAnonymizedParticipantHighlights(array $event, array $stats): array
    {
        $confirmed = (int) ($stats['confirmed_participations'] ?? 0);
        $pending = (int) ($stats['pending_participations'] ?? 0);
        $cancelled = (int) ($stats['cancelled_participations'] ?? 0);
        $total = (int) ($stats['total_participations'] ?? 0);

        $highlights = [
            [
                'label' => 'Demandes recues',
                'value' => $total,
                'description' => 'Toutes les inscriptions restees privees sont centralisees par l\'administration.',
            ],
            [
                'label' => 'Confirmees',
                'value' => $confirmed,
                'description' => 'Places validees pour cet evenement.',
            ],
            [
                'label' => 'En attente',
                'value' => $pending,
                'description' => 'Demandes encore en cours de traitement.',
            ],
        ];

        if ($cancelled > 0) {
            $highlights[] = [
                'label' => 'Annulees',
                'value' => $cancelled,
                'description' => 'Demandes non retenues ou retirees.',
            ];
        }

        if (($event['is_registration_open'] ?? false) === true) {
            $highlights[] = [
                'label' => 'Places restantes',
                'value' => (int) ($event['remaining_places'] ?? 0),
                'description' => 'Encore disponibles sans exposer l\'identite des participants.',
            ];
        }

        return $highlights;
    }

    protected function formatNumericChartValue(float $value): string
    {
        if (fmod($value, 1.0) === 0.0) {
            return (string) (int) $value;
        }

        return number_format($value, 1, ',', ' ');
    }

    protected function rememberTransientData(string $scope, array $payload): void
    {
        $_SESSION['event_module_payloads'][$scope] = $payload;
    }

    protected function pullTransientData(string $scope): ?array
    {
        $payload = $_SESSION['event_module_payloads'][$scope] ?? null;
        unset($_SESSION['event_module_payloads'][$scope]);

        return is_array($payload) ? $payload : null;
    }

    protected function buildParticipationQr(?array $participation, ?array $event): ?string
    {
        if (!$participation || !$event) {
            return null;
        }

        return $this->qrService->renderDataUri($participation, $event);
    }

    protected function buildParticipationQrReference(?array $participation, ?array $event): ?string
    {
        if (!$participation || !$event) {
            return null;
        }

        return $this->qrService->buildReferenceCode($participation, $event);
    }

    protected function validateEventInput(array $input, array $files, ?array $existingEvent = null): array
    {
        $data = [
            'titre' => trim((string) ($input['titre'] ?? '')),
            'description' => trim((string) ($input['description'] ?? '')),
            'date_debut' => trim((string) ($input['date_debut'] ?? '')),
            'date_fin' => trim((string) ($input['date_fin'] ?? '')),
            'lieu' => trim((string) ($input['lieu'] ?? '')),
            'type_evenement' => trim((string) ($input['type_evenement'] ?? '')),
            'nb_places' => trim((string) ($input['nb_places'] ?? '')),
            'statut' => trim((string) ($input['statut'] ?? '')),
            'image' => $existingEvent['image'] ?? '',
            'date_creation' => $existingEvent['date_creation'] ?? date('Y-m-d H:i:s'),
        ];

        $errors = [];

        if ($data['titre'] === '') {
            $errors['titre'] = 'Le titre est obligatoire.';
        } elseif (mb_strlen($data['titre']) > 150) {
            $errors['titre'] = 'Le titre ne doit pas depasser 150 caracteres.';
        }

        if ($data['description'] === '') {
            $errors['description'] = 'La description est obligatoire.';
        }

        if ($data['lieu'] === '') {
            $errors['lieu'] = 'Le lieu est obligatoire.';
        } elseif (mb_strlen($data['lieu']) > 150) {
            $errors['lieu'] = 'Le lieu ne doit pas depasser 150 caracteres.';
        }

        if ($data['type_evenement'] === '' || !array_key_exists($data['type_evenement'], $this->eventTypes)) {
            $errors['type_evenement'] = 'Choisissez un type d\'evenement valide.';
        }

        if ($data['statut'] === '' || !array_key_exists($data['statut'], $this->eventStatuses)) {
            $errors['statut'] = 'Choisissez un statut valide.';
        }

        if ($data['nb_places'] === '') {
            $errors['nb_places'] = 'Le nombre de places est obligatoire.';
        } elseif (filter_var($data['nb_places'], FILTER_VALIDATE_INT) === false || (int) $data['nb_places'] <= 0) {
            $errors['nb_places'] = 'Le nombre de places doit etre un entier positif.';
        } else {
            $data['nb_places'] = (int) $data['nb_places'];
        }

        $start = strtotime($data['date_debut']);
        if ($data['date_debut'] === '' || $start === false) {
            $errors['date_debut'] = 'La date de debut est invalide.';
        }

        $end = strtotime($data['date_fin']);
        if ($data['date_fin'] === '' || $end === false) {
            $errors['date_fin'] = 'La date de fin est invalide.';
        } elseif ($start !== false && $end <= $start) {
            $errors['date_fin'] = 'La date de fin doit etre apres la date de debut.';
        }

        if ($start !== false) {
            $data['date_debut'] = date('Y-m-d H:i:s', $start);
        }

        if ($end !== false) {
            $data['date_fin'] = date('Y-m-d H:i:s', $end);
        }

        $imageResult = $this->handleImageUpload($files['image'] ?? null, $existingEvent['image'] ?? '');
        if (isset($imageResult['error'])) {
            $errors['image'] = $imageResult['error'];
        } else {
            $data['image'] = $imageResult['path'] ?? ($existingEvent['image'] ?? '');
        }

        return [
            'data' => $data,
            'errors' => $errors,
        ];
    }

    protected function validateParticipationInput(array $input, ?array $existingParticipation = null, bool $isFrontFlow = false): array
    {
        $data = [
            'id_evenement' => (int) ($input['id_evenement'] ?? 0),
            'nom_participant' => trim((string) ($input['nom_participant'] ?? '')),
            'email_participant' => trim((string) ($input['email_participant'] ?? '')),
            'telephone' => trim((string) ($input['telephone'] ?? '')),
            'statut_participation' => trim((string) ($input['statut_participation'] ?? ($existingParticipation['statut_participation'] ?? ($isFrontFlow ? 'en attente' : 'confirme')))),
            'date_inscription' => $existingParticipation['date_inscription'] ?? date('Y-m-d H:i:s'),
        ];

        $errors = [];

        if ($data['id_evenement'] <= 0) {
            $errors['id_evenement'] = 'Selectionnez un evenement.';
        }

        if ($data['nom_participant'] === '') {
            $errors['nom_participant'] = 'Le nom du participant est obligatoire.';
        } elseif (mb_strlen($data['nom_participant']) > 100) {
            $errors['nom_participant'] = 'Le nom ne doit pas depasser 100 caracteres.';
        }

        if ($data['email_participant'] === '') {
            $errors['email_participant'] = 'L\'email est obligatoire.';
        } elseif (!filter_var($data['email_participant'], FILTER_VALIDATE_EMAIL)) {
            $errors['email_participant'] = 'Entrez une adresse email valide.';
        }

        if ($data['telephone'] === '') {
            $errors['telephone'] = 'Le telephone est obligatoire.';
        } elseif (mb_strlen($data['telephone']) > 20) {
            $errors['telephone'] = 'Le telephone ne doit pas depasser 20 caracteres.';
        }

        if ($data['statut_participation'] === '' || !array_key_exists($data['statut_participation'], $this->participationStatuses)) {
            $errors['statut_participation'] = 'Choisissez un statut de participation valide.';
        }

        return [
            'data' => $data,
            'errors' => $errors,
        ];
    }

    protected function handleImageUpload(?array $file, string $currentPath = ''): array
    {
        if (!$file || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['path' => $currentPath];
        }

        if ((int) ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return ['error' => 'Impossible d\'envoyer l\'image.'];
        }

        if ((int) ($file['size'] ?? 0) > 5 * 1024 * 1024) {
            return ['error' => 'L\'image depasse la taille maximale autorisee (5 Mo).'];
        }

        $extension = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($extension, $allowedExtensions, true)) {
            return ['error' => 'Formats acceptes : JPG, PNG ou WEBP.'];
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $mimeType = $tmpName !== '' && is_file($tmpName) ? (string) mime_content_type($tmpName) : '';
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            return ['error' => 'Le fichier image selectionne est invalide.'];
        }

        $fileName = 'event_' . date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
        $destination = $this->uploadDirectory() . '/' . $fileName;

        if (!move_uploaded_file($tmpName, $destination)) {
            return ['error' => 'Le televersement de l\'image a echoue.'];
        }

        return ['path' => 'assets/uploads/events/' . $fileName];
    }
}



