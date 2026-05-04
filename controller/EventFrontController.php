<?php

require_once __DIR__ . '/AbstractEventController.php';

class EventFrontController extends AbstractEventController
{
    public function handleRequest(): void
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return;
        }

        $action = trim((string) ($_POST['event_action'] ?? ''));
        $returnTo = (string) ($_POST['return_to'] ?? '');
        if ($action !== 'create_participation') {
            return;
        }

        if (!$this->validateCsrf($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'La session de securite a expire. Reessayez.', 'front');
            $this->redirect($this->buildReturnUrl($returnTo));
        }

        $validation = $this->validateParticipationInput($_POST, null, true);
        $eventId = (int) ($validation['data']['id_evenement'] ?? 0);
        $event = $eventId > 0 ? $this->eventRepository->findById($eventId) : null;

        if (!$event) {
            $this->flash('error', 'L\'evenement selectionne est introuvable.', 'front');
            $this->redirect($this->buildReturnUrl($returnTo));
        }

        $event = $this->normalizeEvent($event);

        if ($this->participationRepository->existsForEventAndEmail($eventId, $validation['data']['email_participant'] ?? '')) {
            $validation['errors']['email_participant'] = 'Cette adresse email est deja inscrite a cet evenement. Utilisez une autre adresse ou contactez l\'administration.';
            $this->flash('error', 'Cette adresse email est deja utilisee pour "' . $event['titre'] . '".', 'front');
        }

        if (!$event['is_registration_open']) {
            $validation['errors']['id_evenement'] = 'Les inscriptions sont fermees pour cet evenement.';
            $this->flash('error', $event['registration_message'], 'front');
        }

        if ($validation['errors']) {
            $this->rememberForm('front_participation', $validation['data'], $validation['errors']);
            if (!$this->hasErrorFlash('front')) {
                $this->flash('error', 'Veuillez corriger le formulaire de participation.', 'front');
            }
            $this->redirect($this->buildReturnUrl($returnTo, ['event_id' => $eventId], '#participation-form'));
        }

        $validation['data']['statut_participation'] = 'en attente';
        $this->participationRepository->create($validation['data']);
        $this->rememberTransientData('front_registration_receipt', [
            'titre' => $event['titre'],
            'lieu' => $event['lieu'],
            'start_display' => $event['start_display'],
            'status_label' => 'En attente',
        ]);

        $this->flash('success', 'Votre demande d\'inscription a bien ete envoyee.', 'front');
        $this->redirect($this->buildReturnUrl($returnTo, ['event_id' => $eventId], '#participation-form'));
    }

    public function getPageData(array $query): array
    {
        $filters = [
            'search' => trim((string) ($query['search'] ?? '')),
            'type' => trim((string) ($query['type'] ?? '')),
            'status' => trim((string) ($query['status'] ?? '')),
            'availability' => trim((string) ($query['availability'] ?? '')),
            'date_from' => trim((string) ($query['date_from'] ?? '')),
            'date_to' => trim((string) ($query['date_to'] ?? '')),
            'sort' => trim((string) ($query['sort'] ?? 'priority')),
        ];

        $allFilteredEvents = $this->normalizeEvents($this->eventRepository->findAll($filters));
        $selectedEventId = (int) ($query['event_id'] ?? 0);
        $selectedEvent = null;

        if ($selectedEventId > 0) {
            foreach ($allFilteredEvents as $event) {
                if ((int) $event['id_evenement'] === $selectedEventId) {
                    $selectedEvent = $event;
                    break;
                }
            }
        }

        if (!$selectedEvent && !empty($allFilteredEvents)) {
            $selectedEvent = $allFilteredEvents[0];
            $selectedEventId = (int) $selectedEvent['id_evenement'];
        }

        $featuredEvent = $this->chooseFeaturedEvent($allFilteredEvents);
        $catalogPagination = $this->paginateItems($allFilteredEvents, $this->resolvePage($query['event_page'] ?? 1), 6);
        $events = $catalogPagination['items'];

        $eventStats = $this->eventRepository->getStats();
        $participationStats = $this->participationRepository->getStats();
        $activeParticipations = (int) ($participationStats['confirmed_participations'] ?? 0)
            + (int) ($participationStats['pending_participations'] ?? 0);
        $occupancyRate = (int) (
            ((int) ($eventStats['total_places'] ?? 0)) > 0
                ? round(($activeParticipations / (int) $eventStats['total_places']) * 100)
                : 0
        );

        $typeHighlights = $this->buildTypeHighlights($allFilteredEvents);
        $formState = $this->pullForm('front_participation');
        $openEvents = count(array_filter($allFilteredEvents, static fn(array $event): bool => (bool) ($event['is_registration_open'] ?? false)));
        $fullEvents = count(array_filter($allFilteredEvents, static fn(array $event): bool => (bool) ($event['is_full'] ?? false)));
        $timelineGroups = $this->buildTimelineGroups(array_values(array_filter(
            $allFilteredEvents,
            static fn(array $event): bool => !($event['is_finished'] ?? false) && !($event['is_cancelled'] ?? false)
        )));
        $timelineSummary = $this->buildTimelineSummary($allFilteredEvents);

        $selectedEventParticipationStats = $selectedEvent
            ? $this->participationRepository->getStatsByEventId((int) $selectedEvent['id_evenement'])
            : [
                'total_participations' => 0,
                'confirmed_participations' => 0,
                'pending_participations' => 0,
                'cancelled_participations' => 0,
                'latest_registration' => null,
            ];

        $selectedEventSummaryChart = $selectedEvent
            ? $this->buildDonutChart([
                ['label' => 'Confirmees', 'value' => (int) ($selectedEventParticipationStats['confirmed_participations'] ?? 0), 'color' => '#4CAF50'],
                ['label' => 'En attente', 'value' => (int) ($selectedEventParticipationStats['pending_participations'] ?? 0), 'color' => '#EE5828'],
                ['label' => 'Annulees', 'value' => (int) ($selectedEventParticipationStats['cancelled_participations'] ?? 0), 'color' => '#8AA4B8'],
            ], (string) ($selectedEventParticipationStats['total_participations'] ?? 0), 'Demandes')
            : $this->buildDonutChart([], '0', 'Demandes');

        $selectedCapacityChart = $selectedEvent
            ? $this->buildDonutChart([
                ['label' => 'Places occupees', 'value' => (int) ($selectedEvent['participants_count'] ?? 0), 'color' => '#142738'],
                ['label' => 'Places restantes', 'value' => (int) ($selectedEvent['remaining_places'] ?? 0), 'color' => '#4CAF50'],
            ], (string) ($selectedEvent['fill_rate'] ?? 0) . '%', 'Occupation')
            : $this->buildDonutChart([], '0%', 'Occupation');

        return [
            'filters' => $filters,
            'events' => $events,
            'allFilteredEvents' => $allFilteredEvents,
            'selectedEvent' => $selectedEvent,
            'selectedEventId' => $selectedEventId,
            'featuredEvent' => $featuredEvent,
            'flashes' => $this->pullFlashes('front'),
            'formValues' => $formState['values'],
            'formErrors' => $formState['errors'],
            'registrationReceipt' => $this->pullTransientData('front_registration_receipt'),
            'csrfToken' => $this->getCsrfToken(),
            'eventTypes' => $this->eventTypes,
            'eventStatuses' => $this->eventStatuses,
            'eventSortOptions' => $this->eventSortOptions,
            'availabilityOptions' => $this->eventAvailabilityOptions,
            'typeHighlights' => $typeHighlights,
            'filterChips' => $this->buildEventFilterChips($filters),
            'timelineGroups' => $timelineGroups,
            'timelineSummary' => $timelineSummary,
            'activeFilterCount' => $this->countActiveFilters($filters, ['sort']),
            'pagination' => $catalogPagination['meta'],
            'selectedEventParticipationStats' => $selectedEventParticipationStats,
            'selectedEventHighlights' => $selectedEvent
                ? $this->buildAnonymizedParticipantHighlights($selectedEvent, $selectedEventParticipationStats)
                : [],
            'selectedEventSummaryChart' => $selectedEventSummaryChart,
            'selectedCapacityChart' => $selectedCapacityChart,
            'stats' => [
                'total_events' => (int) ($eventStats['total_events'] ?? 0),
                'upcoming_events' => (int) ($eventStats['upcoming_events'] ?? 0),
                'total_places' => (int) ($eventStats['total_places'] ?? 0),
                'total_participations' => (int) ($participationStats['total_participations'] ?? 0),
                'pending_participations' => (int) ($participationStats['pending_participations'] ?? 0),
                'occupancy_rate' => max(0, min(100, $occupancyRate)),
                'open_events_in_view' => $openEvents,
                'full_events_in_view' => $fullEvents,
            ],
        ];
    }

    private function hasErrorFlash(string $scope): bool
    {
        $flashes = $_SESSION['event_module_flashes'][$scope] ?? [];

        foreach ($flashes as $flash) {
            if (($flash['type'] ?? '') === 'error') {
                return true;
            }
        }

        return false;
    }

    private function buildTypeHighlights(array $events): array
    {
        $counts = [];

        foreach ($this->eventTypes as $typeKey => $label) {
            $counts[$typeKey] = [
                'label' => $label,
                'count' => 0,
            ];
        }

        foreach ($events as $event) {
            $type = (string) ($event['type_evenement'] ?? '');
            if (isset($counts[$type])) {
                $counts[$type]['count']++;
            }
        }

        return array_values($counts);
    }
}
