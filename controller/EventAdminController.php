<?php

require_once __DIR__ . '/AbstractEventController.php';

class EventAdminController extends AbstractEventController
{
    public function handleViewActions(array $query): void
    {
        if (($query['download'] ?? '') !== 'calendar') {
            return;
        }

        $eventId = (int) ($query['manage_event'] ?? $query['event_id'] ?? 0);
        $event = $eventId > 0 ? $this->eventRepository->findById($eventId) : null;

        if (!$event) {
            $this->flash('error', 'L\'evenement demande pour le calendrier est introuvable.', 'admin');
            $this->redirect($this->buildEventUrl());
        }

        $this->calendarService->streamDownload($this->normalizeEvent($event));
    }

    public function handleRequest(): void
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            return;
        }

        $action = trim((string) ($_POST['event_action'] ?? ''));
        $returnTo = (string) ($_POST['return_to'] ?? '');
        if ($action === '') {
            return;
        }

        if (!$this->validateCsrf($_POST['csrf_token'] ?? null)) {
            $this->flash('error', 'La session de securite a expire. Reessayez.', 'admin');
            $this->redirect($this->buildReturnUrl($returnTo));
        }

        switch ($action) {
            case 'create_event':
                $this->createEvent($returnTo);
                break;

            case 'update_event':
                $this->updateEvent($returnTo);
                break;

            case 'delete_event':
                $this->deleteEvent($returnTo);
                break;

            case 'update_participation':
                $this->updateParticipation($returnTo);
                break;

            case 'delete_participation':
                $this->deleteParticipation($returnTo);
                break;

            case 'set_participation_status':
                $this->setParticipationStatus($returnTo);
                break;

            case 'bulk_participation_status':
                $this->bulkParticipationStatus($returnTo);
                break;

            case 'send_participation_email':
                $this->sendParticipationEmail($returnTo);
                break;

            case 'ai_improve_description':
                $this->assistEventDraft($returnTo, 'improve_description');
                break;

            case 'ai_generate_promo':
                $this->assistEventDraft($returnTo, 'generate_promo');
                break;

            case 'ai_suggest_title':
                $this->assistEventDraft($returnTo, 'suggest_title');
                break;

            case 'ai_analyze_event':
                $this->assistEventDraft($returnTo, 'analyze_event');
                break;
        }
    }

    public function getPageData(array $query): array
    {
        $eventFilters = [
            'search' => trim((string) ($query['search'] ?? '')),
            'type' => trim((string) ($query['type'] ?? '')),
            'status' => trim((string) ($query['status'] ?? '')),
            'availability' => trim((string) ($query['availability'] ?? '')),
            'date_from' => trim((string) ($query['date_from'] ?? '')),
            'date_to' => trim((string) ($query['date_to'] ?? '')),
            'sort' => trim((string) ($query['sort'] ?? 'date_asc')),
        ];

        $participationFilters = [
            'search' => trim((string) ($query['participant_search'] ?? '')),
            'status' => trim((string) ($query['participant_status'] ?? '')),
            'date_from' => trim((string) ($query['participant_date_from'] ?? '')),
            'date_to' => trim((string) ($query['participant_date_to'] ?? '')),
            'sort' => trim((string) ($query['participant_sort'] ?? 'registered_desc')),
        ];

        $allEvents = $this->normalizeEvents($this->eventRepository->findAll());
        $manageEventId = (int) ($query['manage_event'] ?? $query['participant_event_id'] ?? 0);
        $editParticipationId = (int) ($query['edit_participation'] ?? 0);
        $participationBeingEdited = $editParticipationId > 0 ? $this->participationRepository->findById($editParticipationId) : null;
        if ($participationBeingEdited) {
            $participationBeingEdited = $this->normalizeParticipation($participationBeingEdited);
            if ($manageEventId <= 0) {
                $manageEventId = (int) ($participationBeingEdited['id_evenement'] ?? 0);
            }
        }

        if ($manageEventId <= 0 && !empty($allEvents)) {
            $manageEventId = (int) ($allEvents[0]['id_evenement'] ?? 0);
        }

        $selectedManagementEvent = null;
        foreach ($allEvents as $event) {
            if ((int) ($event['id_evenement'] ?? 0) === $manageEventId) {
                $selectedManagementEvent = $event;
                break;
            }
        }

        if (!$selectedManagementEvent && !empty($allEvents)) {
            $selectedManagementEvent = $allEvents[0];
            $manageEventId = (int) ($selectedManagementEvent['id_evenement'] ?? 0);
        }

        $participationFilters['event_id'] = $manageEventId > 0 ? (string) $manageEventId : '';

        if (($query['export'] ?? '') === 'participations_csv') {
            $this->exportParticipationsCsv($participationFilters, $selectedManagementEvent);
        }

        if (($query['print'] ?? '') === 'participations') {
            $this->renderParticipationPrintView($participationFilters, $selectedManagementEvent);
        }

        $filteredEvents = $this->normalizeEvents($this->eventRepository->findAll($eventFilters));
        $eventPagination = $this->paginateItems($filteredEvents, $this->resolvePage($query['event_page'] ?? 1), 7);
        $events = $eventPagination['items'];

        $allSelectedEventParticipations = $manageEventId > 0
            ? $this->normalizeParticipations($this->participationRepository->findAll($participationFilters))
            : [];
        $participationPagination = $this->paginateItems($allSelectedEventParticipations, $this->resolvePage($query['participant_page'] ?? 1), 8);
        $participations = $participationPagination['items'];
        $pendingParticipations = $this->normalizeParticipations($this->participationRepository->findAll([
            'status' => 'en attente',
            'sort' => 'registered_desc',
        ]));

        $eventFormState = $this->pullForm('admin_event_form');
        $participationFormState = $this->pullForm('admin_participation_form');

        $editEventId = (int) ($query['edit_event'] ?? 0);

        $eventBeingEdited = $editEventId > 0 ? $this->eventRepository->findById($editEventId) : null;
        if ($eventBeingEdited) {
            $eventBeingEdited = $this->normalizeEvent($eventBeingEdited);
        }

        $eventStats = $this->eventRepository->getStats();
        $participationStats = $this->participationRepository->getStats();
        $occupiedPlaces = array_sum(array_map(static fn(array $event): int => (int) ($event['participants_count'] ?? 0), $allEvents));
        $totalPlaces = (int) ($eventStats['total_places'] ?? 0);
        $capacityRate = $totalPlaces > 0 ? (int) round(($occupiedPlaces / $totalPlaces) * 100) : 0;

        usort($allEvents, static function (array $left, array $right): int {
            return strcmp((string) ($left['date_debut'] ?? ''), (string) ($right['date_debut'] ?? ''));
        });

        $openEvents = count(array_filter($filteredEvents, static fn(array $event): bool => (bool) ($event['is_registration_open'] ?? false)));
        $fullEvents = count(array_filter($filteredEvents, static fn(array $event): bool => (bool) ($event['is_full'] ?? false)));
        $nextUpcomingEvent = $this->pickNextUpcomingEvent($allEvents);
        $mostPopularEvent = $this->pickEventByMetric($allEvents, 'participants_count');
        $highestOccupancyEvent = $this->pickEventByMetric($allEvents, 'fill_rate');
        $mostAvailableEvent = $this->pickEventByMetric($allEvents, 'remaining_places');
        $pendingByEvent = $this->buildPendingByEvent($allEvents, $pendingParticipations);
        $highDemandEvents = array_values(array_filter($allEvents, static fn(array $event): bool => (bool) ($event['is_almost_full'] ?? false)));
        $silentEvents = array_values(array_filter($allEvents, static fn(array $event): bool => (int) ($event['participants_count'] ?? 0) === 0));
        $selectedEventParticipationStats = $manageEventId > 0
            ? $this->participationRepository->getStatsByEventId($manageEventId)
            : [
                'total_participations' => 0,
                'confirmed_participations' => 0,
                'pending_participations' => 0,
                'cancelled_participations' => 0,
                'latest_registration' => null,
            ];

        $eventLifecycleChart = $this->buildDonutChart([
            ['label' => 'A venir', 'value' => count(array_filter($allEvents, static fn(array $event): bool => (bool) ($event['is_upcoming'] ?? false))), 'color' => '#EE5828'],
            ['label' => 'En cours', 'value' => count(array_filter($allEvents, static fn(array $event): bool => (bool) ($event['is_in_progress'] ?? false))), 'color' => '#142738'],
            ['label' => 'Termines', 'value' => count(array_filter($allEvents, static fn(array $event): bool => (bool) ($event['is_finished'] ?? false))), 'color' => '#4CAF50'],
            ['label' => 'Annules', 'value' => count(array_filter($allEvents, static fn(array $event): bool => (bool) ($event['is_cancelled'] ?? false))), 'color' => '#8AA4B8'],
        ], (string) ($eventStats['total_events'] ?? 0), 'Evenements');

        $participationStatusChart = $this->buildDonutChart([
            ['label' => 'Confirmees', 'value' => (int) ($participationStats['confirmed_participations'] ?? 0), 'color' => '#4CAF50'],
            ['label' => 'En attente', 'value' => (int) ($participationStats['pending_participations'] ?? 0), 'color' => '#EE5828'],
            ['label' => 'Annulees', 'value' => (int) ($participationStats['cancelled_participations'] ?? 0), 'color' => '#8AA4B8'],
        ], (string) ($participationStats['total_participations'] ?? 0), 'Inscriptions');

        $selectedEventCapacityChart = $selectedManagementEvent
            ? $this->buildDonutChart([
                ['label' => 'Places occupees', 'value' => (int) ($selectedManagementEvent['participants_count'] ?? 0), 'color' => '#142738'],
                ['label' => 'Places restantes', 'value' => (int) ($selectedManagementEvent['remaining_places'] ?? 0), 'color' => '#4CAF50'],
            ], (string) ($selectedManagementEvent['fill_rate'] ?? 0) . '%', 'Occupation')
            : $this->buildDonutChart([], '0%', 'Occupation');

        $selectedEventParticipationChart = $selectedManagementEvent
            ? $this->buildDonutChart([
                ['label' => 'Confirmees', 'value' => (int) ($selectedEventParticipationStats['confirmed_participations'] ?? 0), 'color' => '#4CAF50'],
                ['label' => 'En attente', 'value' => (int) ($selectedEventParticipationStats['pending_participations'] ?? 0), 'color' => '#EE5828'],
                ['label' => 'Annulees', 'value' => (int) ($selectedEventParticipationStats['cancelled_participations'] ?? 0), 'color' => '#8AA4B8'],
            ], (string) ($selectedEventParticipationStats['total_participations'] ?? 0), 'Demandes')
            : $this->buildDonutChart([], '0', 'Demandes');

        $participationRecordEvent = null;
        if ($participationBeingEdited) {
            $participationRecordEvent = $this->eventRepository->findById((int) ($participationBeingEdited['id_evenement'] ?? 0));
            $participationRecordEvent = $participationRecordEvent ? $this->normalizeEvent($participationRecordEvent) : null;
        }

        return [
            'filters' => $eventFilters,
            'participationFilters' => $participationFilters,
            'events' => $events,
            'allEvents' => $allEvents,
            'filteredEvents' => $filteredEvents,
            'participations' => $participations,
            'flashes' => $this->pullFlashes('admin'),
            'eventForm' => [
                'mode' => $eventBeingEdited ? 'edit' : 'create',
                'record' => $eventBeingEdited,
                'values' => $eventFormState['values'],
                'errors' => $eventFormState['errors'],
            ],
            'participationForm' => [
                'record' => $participationBeingEdited,
                'values' => $participationFormState['values'],
                'errors' => $participationFormState['errors'],
            ],
            'csrfToken' => $this->getCsrfToken(),
            'eventTypes' => $this->eventTypes,
            'eventStatuses' => $this->eventStatuses,
            'participationStatuses' => $this->participationStatuses,
            'eventSortOptions' => $this->eventSortOptions,
            'availabilityOptions' => $this->eventAvailabilityOptions,
            'participationSortOptions' => $this->participationSortOptions,
            'activeEventFilterCount' => $this->countActiveFilters($eventFilters, ['sort']),
            'activeParticipationFilterCount' => $this->countActiveFilters($participationFilters, ['sort', 'event_id']),
            'pendingByEvent' => $pendingByEvent,
            'selectedManagementEvent' => $selectedManagementEvent,
            'selectedEventCalendarUrl' => $selectedManagementEvent ? $this->buildCalendarDownloadUrl((int) $selectedManagementEvent['id_evenement']) : '',
            'selectedEventMapUrl' => $selectedManagementEvent && trim((string) ($selectedManagementEvent['lieu'] ?? '')) !== '' ? $this->buildMapUrl((string) $selectedManagementEvent['lieu']) : '',
            'selectedEventMapEmbedUrl' => $selectedManagementEvent && trim((string) ($selectedManagementEvent['lieu'] ?? '')) !== '' ? $this->buildMapEmbedUrl((string) $selectedManagementEvent['lieu']) : '',
            'selectedEventFrontUrl' => $selectedManagementEvent ? $this->buildFrontEventsUrl((int) $selectedManagementEvent['id_evenement'], '#event-focus') : $this->buildFrontEventsUrl(),
            'selectedEventParticipationStats' => $selectedEventParticipationStats,
            'eventPagination' => $eventPagination['meta'],
            'participationPagination' => $participationPagination['meta'],
            'participationRecordQr' => $this->buildParticipationQr($participationBeingEdited, $participationRecordEvent),
            'participationRecordQrReference' => $this->buildParticipationQrReference($participationBeingEdited, $participationRecordEvent),
            'mailConfigured' => $this->emailService->canSend(),
            'ollamaModel' => $this->ollamaAssistant->model(),
            'charts' => [
                'eventLifecycle' => $eventLifecycleChart,
                'participationStatus' => $participationStatusChart,
                'selectedEventCapacity' => $selectedEventCapacityChart,
                'selectedEventParticipation' => $selectedEventParticipationChart,
            ],
            'insights' => [
                'nextUpcomingEvent' => $nextUpcomingEvent,
                'mostPopularEvent' => $mostPopularEvent,
                'highestOccupancyEvent' => $highestOccupancyEvent,
                'mostAvailableEvent' => $mostAvailableEvent,
                'highDemandEvents' => array_slice($highDemandEvents, 0, 3),
                'silentEvents' => array_slice($silentEvents, 0, 3),
            ],
            'stats' => [
                'total_events' => (int) ($eventStats['total_events'] ?? 0),
                'upcoming_events' => (int) ($eventStats['upcoming_events'] ?? 0),
                'live_events' => (int) ($eventStats['live_events'] ?? 0),
                'cancelled_events' => (int) ($eventStats['cancelled_events'] ?? 0),
                'total_places' => $totalPlaces,
                'occupied_places' => $occupiedPlaces,
                'capacity_rate' => max(0, min(100, $capacityRate)),
                'total_participations' => (int) ($participationStats['total_participations'] ?? 0),
                'pending_participations' => (int) ($participationStats['pending_participations'] ?? 0),
                'confirmed_participations' => (int) ($participationStats['confirmed_participations'] ?? 0),
                'open_events_in_view' => $openEvents,
                'full_events_in_view' => $fullEvents,
                'high_demand_events' => count($highDemandEvents),
                'events_without_participation' => count($silentEvents),
            ],
            'highlights' => array_slice($allEvents, 0, 4),
        ];
    }

    private function createEvent(string $returnTo): void
    {
        $validation = $this->validateEventInput($_POST, $_FILES);
        if ($validation['errors']) {
            $this->rememberForm('admin_event_form', $_POST, $validation['errors']);
            $this->flash('error', 'Veuillez corriger le formulaire d\'evenement.', 'admin');
            $this->redirect($this->buildReturnUrl($returnTo, [], '#event-form'));
        }

        $this->eventRepository->create($validation['data']);
        $this->flash('success', 'L\'evenement a ete cree avec succes.', 'admin');
        $this->redirect($this->buildReturnUrl($returnTo, [], '#events-table'));
    }

    private function updateEvent(string $returnTo): void
    {
        $eventId = (int) ($_POST['id_evenement'] ?? 0);
        $existing = $eventId > 0 ? $this->eventRepository->findById($eventId) : null;

        if (!$existing) {
            $this->flash('error', 'L\'evenement a modifier est introuvable.', 'admin');
            $this->redirect($this->buildReturnUrl($returnTo));
        }

        $validation = $this->validateEventInput($_POST, $_FILES, $existing);
        if ($validation['errors']) {
            $this->rememberForm('admin_event_form', $_POST, $validation['errors']);
            $this->flash('error', 'Veuillez corriger le formulaire d\'evenement.', 'admin');
            $this->redirect($this->buildReturnUrl($returnTo, ['edit_event' => $eventId], '#event-form'));
        }

        $this->eventRepository->update($eventId, $validation['data']);
        $this->flash('success', 'L\'evenement a ete mis a jour.', 'admin');
        $this->redirect($this->buildReturnUrl($returnTo, ['edit_event' => $eventId], '#event-form'));
    }

    private function deleteEvent(string $returnTo): void
    {
        $eventId = (int) ($_POST['id_evenement'] ?? 0);
        $existing = $eventId > 0 ? $this->eventRepository->findById($eventId) : null;

        if (!$existing) {
            $this->flash('error', 'L\'evenement a supprimer est introuvable.', 'admin');
            $this->redirect($this->buildReturnUrl($returnTo));
        }

        $this->participationRepository->deleteByEventId($eventId);
        $this->eventRepository->delete($eventId);
        $this->flash('success', 'L\'evenement et ses participations ont ete supprimes.', 'admin');
        $this->redirect($this->buildReturnUrl($returnTo, ['edit_event' => null], '#events-table'));
    }

    private function updateParticipation(string $returnTo): void
    {
        $participationId = (int) ($_POST['id_participation'] ?? 0);
        $existing = $participationId > 0 ? $this->participationRepository->findById($participationId) : null;

        if (!$existing) {
            $this->flash('error', 'La participation a modifier est introuvable.', 'admin');
            $this->redirect($this->buildReturnUrl($returnTo, [], '#participations-table'));
        }

        $validation = $this->validateParticipationInput($_POST, $existing, false);
        if (!$this->eventRepository->findById((int) ($validation['data']['id_evenement'] ?? 0))) {
            $validation['errors']['id_evenement'] = 'Selectionnez un evenement existant.';
        }

        if ($this->participationRepository->existsForEventAndEmail(
            (int) ($validation['data']['id_evenement'] ?? 0),
            (string) ($validation['data']['email_participant'] ?? ''),
            $participationId
        )) {
            $validation['errors']['email_participant'] = 'Cette adresse email est deja inscrite pour cet evenement.';
        }

        if ($validation['errors']) {
            $this->rememberForm('admin_participation_form', $_POST, $validation['errors']);
            $this->flash('error', 'Veuillez corriger le formulaire de participation.', 'admin');
            $this->redirect($this->buildReturnUrl($returnTo, ['edit_participation' => $participationId], '#participation-form'));
        }

        $this->participationRepository->update($participationId, $validation['data']);
        $this->flash('success', 'La participation a ete mise a jour.', 'admin');
        $this->redirect($this->buildReturnUrl($returnTo, ['edit_participation' => $participationId], '#participation-form'));
    }

    private function deleteParticipation(string $returnTo): void
    {
        $participationId = (int) ($_POST['id_participation'] ?? 0);
        if (!$this->participationRepository->delete($participationId)) {
            $this->flash('error', 'La participation a supprimer est introuvable.', 'admin');
            $this->redirect($this->buildReturnUrl($returnTo, [], '#participations-table'));
        }

        $this->flash('success', 'La participation a ete supprimee.', 'admin');
        $this->redirect($this->buildReturnUrl($returnTo, ['edit_participation' => null], '#participations-table'));
    }

    private function setParticipationStatus(string $returnTo): void
    {
        $participationId = (int) ($_POST['id_participation'] ?? 0);
        $newStatus = trim((string) ($_POST['statut_participation'] ?? ''));
        $existing = $participationId > 0 ? $this->participationRepository->findById($participationId) : null;

        if (!$existing) {
            $this->flash('error', 'La participation est introuvable.', 'admin');
            $this->redirect($this->buildReturnUrl($returnTo, [], '#participations-table'));
        }

        if (!array_key_exists($newStatus, $this->participationStatuses)) {
            $this->flash('error', 'Le nouveau statut de participation est invalide.', 'admin');
            $this->redirect($this->buildReturnUrl($returnTo, [], '#participations-table'));
        }

        $payload = [
            'id_evenement' => (int) $existing['id_evenement'],
            'nom_participant' => (string) $existing['nom_participant'],
            'email_participant' => (string) $existing['email_participant'],
            'telephone' => (string) $existing['telephone'],
            'statut_participation' => $newStatus,
        ];

        $this->participationRepository->update($participationId, $payload);
        $this->flash('success', 'Le statut de la participation a ete mis a jour.', 'admin');
        $this->redirect($this->buildReturnUrl($returnTo, [], '#participations-table'));
    }

    private function bulkParticipationStatus(string $returnTo): void
    {
        $eventId = (int) ($_POST['bulk_event_id'] ?? 0);
        $newStatus = trim((string) ($_POST['bulk_status'] ?? ''));

        if ($eventId <= 0 || !$this->eventRepository->findById($eventId)) {
            $this->flash('error', 'Selectionnez un evenement valide pour l\'action groupee.', 'admin');
            $this->redirect($this->buildReturnUrl($returnTo, [], '#participations-table'));
        }

        if (!array_key_exists($newStatus, $this->participationStatuses)) {
            $this->flash('error', 'Le statut groupe demande est invalide.', 'admin');
            $this->redirect($this->buildReturnUrl($returnTo, [], '#participations-table'));
        }

        $affected = $this->participationRepository->updateStatusForEvent($eventId, $newStatus, ['en attente']);

        if ($affected <= 0) {
            $this->flash('error', 'Aucune participation en attente n\'a ete trouvee pour cet evenement.', 'admin');
            $this->redirect($this->buildReturnUrl($returnTo, ['participant_event_id' => $eventId], '#participations-table'));
        }

        $label = $this->participationStatuses[$newStatus] ?? $newStatus;
        $this->flash('success', $affected . ' participation(s) en attente ont ete basculees vers "' . $label . '".', 'admin');
        $this->redirect($this->buildReturnUrl($returnTo, ['manage_event' => $eventId, 'participant_event_id' => $eventId], '#participations-table'));
    }

    private function sendParticipationEmail(string $returnTo): void
    {
        $participationId = (int) ($_POST['id_participation'] ?? 0);
        $participation = $participationId > 0 ? $this->participationRepository->findById($participationId) : null;

        if (!$participation) {
            $this->flash('error', 'La participation ciblee pour l\'email est introuvable.', 'admin');
            $this->redirect($this->buildReturnUrl($returnTo, [], '#participations-table'));
        }

        $participation = $this->normalizeParticipation($participation);
        $event = $this->eventRepository->findById((int) ($participation['id_evenement'] ?? 0));

        if (!$event) {
            $this->flash('error', 'L\'evenement associe a cette participation est introuvable.', 'admin');
            $this->redirect($this->buildReturnUrl($returnTo, [], '#participations-table'));
        }

        $event = $this->normalizeEvent($event);
        $delivery = $this->emailService->sendConfirmation($participation, $event);
        $type = !empty($delivery['sent'])
            ? 'success'
            : (!empty($delivery['configured']) ? 'error' : 'info');
        $this->flash($type, (string) ($delivery['message'] ?? 'Action email terminee.'), 'admin');
        $this->redirect($this->buildReturnUrl($returnTo, [], '#participations-table'));
    }

    private function assistEventDraft(string $returnTo, string $mode): void
    {
        $draft = $this->extractEventDraft($_POST);
        $result = $this->ollamaAssistant->generate($mode, $draft);

        if (!$result['success']) {
            $flashType = !empty($result['available']) ? 'error' : 'info';
            $this->rememberForm('admin_event_form', $draft, []);
            $this->flash($flashType, (string) ($result['message'] ?? 'L\'assistant IA n\'est pas disponible.'), 'admin');
            $this->redirect($this->buildReturnUrl($returnTo, $this->draftEditContext($draft), '#event-form'));
        }

        if ($mode === 'suggest_title') {
            $draft['titre'] = (string) $result['text'];
            $message = 'Le titre a ete propose par l\'assistant IA et injecte dans le formulaire.';
        } elseif ($mode === 'analyze_event') {
            $draft['ai_feedback'] = (string) $result['text'];
            $message = 'Une analyse rapide avec recommandations a ete ajoutee au formulaire.';
        } else {
            $draft['description'] = (string) $result['text'];
            $message = match ($mode) {
                'generate_promo' => 'Une version promotionnelle de la description a ete generee.',

                default => 'La description a ete amelioree par l\'assistant IA.',
            };
        }

        $this->rememberForm('admin_event_form', $draft, []);
        $this->flash('success', $message, 'admin');
        $this->redirect($this->buildReturnUrl($returnTo, $this->draftEditContext($draft), '#event-form'));
    }

    private function extractEventDraft(array $input): array
    {
        return [
            'id_evenement' => (int) ($input['id_evenement'] ?? 0),
            'titre' => trim((string) ($input['titre'] ?? '')),
            'description' => trim((string) ($input['description'] ?? '')),
            'lieu' => trim((string) ($input['lieu'] ?? '')),
            'date_debut' => trim((string) ($input['date_debut'] ?? '')),
            'date_fin' => trim((string) ($input['date_fin'] ?? '')),
            'type_evenement' => trim((string) ($input['type_evenement'] ?? '')),
            'statut' => trim((string) ($input['statut'] ?? '')),
            'nb_places' => trim((string) ($input['nb_places'] ?? '')),
            'ai_feedback' => trim((string) ($input['ai_feedback'] ?? '')),
        ];
    }

    private function draftEditContext(array $draft): array
    {
        $eventId = (int) ($draft['id_evenement'] ?? 0);

        return $eventId > 0 ? ['edit_event' => $eventId] : [];
    }

    private function pickNextUpcomingEvent(array $events): ?array
    {
        foreach ($events as $event) {
            if (($event['is_upcoming'] ?? false) === true) {
                return $event;
            }
        }

        return null;
    }

    private function pickEventByMetric(array $events, string $metric): ?array
    {
        $best = null;
        $bestValue = PHP_INT_MIN;

        foreach ($events as $event) {
            $value = (int) ($event[$metric] ?? 0);
            if ($value > $bestValue) {
                $bestValue = $value;
                $best = $event;
            }
        }

        return $best;
    }

    private function buildPendingByEvent(array $events, array $pendingParticipations): array
    {
        $map = [];

        foreach ($events as $event) {
            $map[(int) $event['id_evenement']] = [
                'id_evenement' => (int) $event['id_evenement'],
                'titre' => (string) $event['titre'],
                'count' => 0,
            ];
        }

        foreach ($pendingParticipations as $participation) {
            $eventId = (int) ($participation['id_evenement'] ?? 0);
            if (isset($map[$eventId])) {
                $map[$eventId]['count']++;
            }
        }

        $items = array_values(array_filter($map, static fn(array $item): bool => $item['count'] > 0));
        usort($items, static fn(array $left, array $right): int => $right['count'] <=> $left['count']);

        return array_slice($items, 0, 5);
    }

    private function exportParticipationsCsv(array $filters, ?array $event): void
    {
        $rows = $this->normalizeParticipations($this->participationRepository->findAll($filters));

        header('Content-Type: text/csv; charset=UTF-8');
        $suffix = $event ? '-' . preg_replace('/[^a-z0-9]+/i', '-', strtolower((string) ($event['titre'] ?? 'evenement'))) : '';
        header('Content-Disposition: attachment; filename="participations-evenements' . $suffix . '.csv"');

        $output = fopen('php://output', 'wb');
        if ($output === false) {
            exit;
        }

        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, ['Participant', 'Email', 'Telephone', 'Evenement', 'Date evenement', 'Inscription', 'Statut'], ';');

        foreach ($rows as $row) {
            fputcsv($output, [
                $row['nom_participant'] ?? '',
                $row['email_participant'] ?? '',
                $row['telephone'] ?? '',
                $row['evenement_titre'] ?? '',
                $row['evenement_date_debut'] ?? '',
                $row['registered_display'] ?? '',
                $row['status_label'] ?? '',
            ], ';');
        }

        fclose($output);
        exit;
    }

    private function renderParticipationPrintView(array $filters, ?array $event): void
    {
        $rows = $this->normalizeParticipations($this->participationRepository->findAll($filters));
        $title = $event
            ? 'Liste des participations - ' . (string) ($event['titre'] ?? '')
            : 'Liste des participations';

        header('Content-Type: text/html; charset=UTF-8');

        echo '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">';
        echo '<title>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</title>';
        echo '<style>
                body{font-family:Arial,sans-serif;padding:24px;color:#142738}
                h1{margin-bottom:8px}
                p{color:#4c5f73}
                table{width:100%;border-collapse:collapse;margin-top:18px}
                th,td{padding:12px;border:1px solid #d7dee7;text-align:left}
                th{background:#f4f6fa}
                .meta{display:flex;gap:18px;flex-wrap:wrap;margin-bottom:12px}
                @media print{body{padding:0} .print-btn{display:none}}
              </style></head><body>';
        echo '<button class="print-btn" onclick="window.print()">Imprimer</button>';
        echo '<h1>' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '</h1>';
        echo '<p>Edition generee depuis le module evenements.</p>';
        echo '<div class="meta"><span>Total: ' . count($rows) . '</span><span>Date: ' . date('d/m/Y H:i') . '</span></div>';
        echo '<table><thead><tr><th>Participant</th><th>Email</th><th>Telephone</th><th>Evenement</th><th>Inscription</th><th>Statut</th></tr></thead><tbody>';

        if ($rows) {
            foreach ($rows as $row) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars((string) ($row['nom_participant'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td>' . htmlspecialchars((string) ($row['email_participant'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td>' . htmlspecialchars((string) ($row['telephone'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td>' . htmlspecialchars((string) ($row['evenement_titre'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td>' . htmlspecialchars((string) ($row['registered_display'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
                echo '<td>' . htmlspecialchars((string) ($row['status_label'] ?? ''), ENT_QUOTES, 'UTF-8') . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="6">Aucune participation a imprimer.</td></tr>';
        }

        echo '</tbody></table></body></html>';
        exit;
    }
}


