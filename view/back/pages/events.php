<?php
$escape = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

$data = $eventAdminData ?? [];
$filters = $data['filters'] ?? ['search' => '', 'type' => '', 'status' => '', 'availability' => '', 'date_from' => '', 'date_to' => '', 'sort' => 'date_asc'];
$participationFilters = $data['participationFilters'] ?? ['search' => '', 'status' => '', 'date_from' => '', 'date_to' => '', 'sort' => 'registered_desc', 'event_id' => ''];
$events = $data['events'] ?? [];
$allEvents = $data['allEvents'] ?? [];
$participations = $data['participations'] ?? [];
$selectedManagementEvent = $data['selectedManagementEvent'] ?? null;
$selectedEventParticipationStats = $data['selectedEventParticipationStats'] ?? [];
$flashes = $data['flashes'] ?? [];
$stats = $data['stats'] ?? [];
$eventTypes = $data['eventTypes'] ?? [];
$eventStatuses = $data['eventStatuses'] ?? [];
$participationStatuses = $data['participationStatuses'] ?? [];
$eventSortOptions = $data['eventSortOptions'] ?? [];
$availabilityOptions = $data['availabilityOptions'] ?? [];
$participationSortOptions = $data['participationSortOptions'] ?? [];
$highlights = $data['highlights'] ?? [];
$pendingByEvent = $data['pendingByEvent'] ?? [];
$insights = $data['insights'] ?? [];
$charts = $data['charts'] ?? [];
$eventForm = $data['eventForm'] ?? ['mode' => 'create', 'record' => null, 'values' => [], 'errors' => []];
$participationForm = $data['participationForm'] ?? ['record' => null, 'values' => [], 'errors' => []];
$csrfToken = $data['csrfToken'] ?? '';
$activeEventFilterCount = (int) ($data['activeEventFilterCount'] ?? 0);
$activeParticipationFilterCount = (int) ($data['activeParticipationFilterCount'] ?? 0);
$eventPagination = $data['eventPagination'] ?? ['page' => 1, 'total_pages' => 1, 'has_previous' => false, 'has_next' => false, 'from' => 0, 'to' => 0, 'total_items' => 0];
$participationPagination = $data['participationPagination'] ?? ['page' => 1, 'total_pages' => 1, 'has_previous' => false, 'has_next' => false, 'from' => 0, 'to' => 0, 'total_items' => 0];

$eventRecord = $eventForm['record'] ?? null;
$eventValues = $eventForm['values'] ?? [];
$eventErrors = $eventForm['errors'] ?? [];
$eventMode = $eventForm['mode'] ?? 'create';

$participationRecord = $participationForm['record'] ?? null;
$participationValues = $participationForm['values'] ?? [];
$participationErrors = $participationForm['errors'] ?? [];

$eventValue = static function (string $key, string $default = '') use ($eventValues, $eventRecord): string {
    if (array_key_exists($key, $eventValues)) {
        return (string) $eventValues[$key];
    }

    if ($eventRecord && array_key_exists($key, $eventRecord)) {
        return (string) $eventRecord[$key];
    }

    return $default;
};

$participationValue = static function (string $key, string $default = '') use ($participationValues, $participationRecord): string {
    if (array_key_exists($key, $participationValues)) {
        return (string) $participationValues[$key];
    }

    if ($participationRecord && array_key_exists($key, $participationRecord)) {
        return (string) $participationRecord[$key];
    }

    return $default;
};

$eventError = static fn(string $key): ?string => isset($eventErrors[$key]) ? (string) $eventErrors[$key] : null;
$participationError = static fn(string $key): ?string => isset($participationErrors[$key]) ? (string) $participationErrors[$key] : null;

$eventStartValue = $eventValue('date_debut');
if ($eventStartValue !== '' && str_contains($eventStartValue, ' ')) {
    $eventStartValue = date('Y-m-d\TH:i', strtotime($eventStartValue));
}

$eventEndValue = $eventValue('date_fin');
if ($eventEndValue !== '' && str_contains($eventEndValue, ' ')) {
    $eventEndValue = date('Y-m-d\TH:i', strtotime($eventEndValue));
}

$selectedManagementEventId = (int) ($selectedManagementEvent['id_evenement'] ?? 0);

$buildAdminUrl = static function (array $params = [], string $hash = '') use ($filters, $participationFilters, $selectedManagementEventId): string {
    $query = ['page' => 'events'];

    foreach (['search', 'type', 'status', 'availability', 'date_from', 'date_to', 'sort', 'event_page'] as $key) {
        $value = $filters[$key] ?? '';
        if ($value !== '' && !($key === 'sort' && $value === 'date_asc') && !($key === 'event_page' && (string) $value === '1')) {
            $query[$key] = $value;
        }
    }

    if ($selectedManagementEventId > 0) {
        $query['manage_event'] = $selectedManagementEventId;
    }

    $participationMap = [
        'search' => 'participant_search',
        'status' => 'participant_status',
        'date_from' => 'participant_date_from',
        'date_to' => 'participant_date_to',
        'sort' => 'participant_sort',
        'page' => 'participant_page',
    ];

    foreach ($participationMap as $sourceKey => $targetKey) {
        $value = $participationFilters[$sourceKey] ?? '';
        if ($value !== '' && !($sourceKey === 'sort' && $value === 'registered_desc') && !($sourceKey === 'page' && (string) $value === '1')) {
            $query[$targetKey] = $value;
        }
    }

    foreach ($params as $key => $value) {
        if ($value === null || $value === '' || ($key === 'sort' && $value === 'date_asc') || ($key === 'event_page' && (string) $value === '1') || ($key === 'participant_sort' && $value === 'registered_desc') || ($key === 'participant_page' && (string) $value === '1')) {
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
};

$renderPagination = static function (array $meta, string $pageKey, callable $urlBuilder, string $hash = '') use ($escape): void {
    $totalPages = (int) ($meta['total_pages'] ?? 1);
    $page = (int) ($meta['page'] ?? 1);

    if ($totalPages <= 1) {
        return;
    }

    $start = max(1, $page - 2);
    $end = min($totalPages, $page + 2);

    echo '<nav class="event-pagination" aria-label="Pagination">';
    echo '<div class="event-pagination-links">';

    if (!empty($meta['has_previous'])) {
        echo '<a class="small-btn" href="' . $escape($urlBuilder([$pageKey => $page - 1], $hash)) . '">Precedent</a>';
    }

    for ($index = $start; $index <= $end; $index++) {
        $class = $index === $page ? 'small-btn small-btn-active' : 'small-btn';
        echo '<a class="' . $class . '" href="' . $escape($urlBuilder([$pageKey => $index], $hash)) . '">' . $escape($index) . '</a>';
    }

    if (!empty($meta['has_next'])) {
        echo '<a class="small-btn" href="' . $escape($urlBuilder([$pageKey => $page + 1], $hash)) . '">Suivant</a>';
    }

    echo '</div></nav>';
};

$renderSummaryBar = static function (array $chart, string $title, string $subtitle) use ($escape): void {
    $segments = $chart['segments'] ?? [];
    echo '<article class="event-analytics-card">';
    echo '<div class="event-analytics-head"><span class="section-badge">' . $escape($title) . '</span><h4>' . $escape($subtitle) . '</h4></div>';
    echo '<div class="event-analytics-kpi"><strong>' . $escape($chart['center_value'] ?? '0') . '</strong><span>' . $escape($chart['center_label'] ?? '') . '</span></div>';

    if ($segments) {
        echo '<div class="event-analytics-bar">';
        foreach ($segments as $segment) {
            $percentage = max(0, min(100, (int) ($segment['percentage'] ?? 0)));
            if ($percentage <= 0) {
                continue;
            }
            echo '<span style="width:' . $escape($percentage) . '%;background:' . $escape($segment['color'] ?? '#142738') . ';"></span>';
        }
        echo '</div>';
    }
    echo '<div class="event-analytics-legend">';
    if ($segments) {
        foreach ($segments as $segment) {
            echo '<div class="event-analytics-legend-item">';
            echo '<span class="event-analytics-dot" style="background:' . $escape($segment['color'] ?? '#142738') . ';"></span>';
            echo '<div><strong>' . $escape($segment['label'] ?? '') . '</strong><span>' . $escape($segment['display_value'] ?? '0') . ' - ' . $escape($segment['percentage'] ?? 0) . '%</span></div>';
            echo '</div>';
        }
    } else {
        echo '<p class="event-analytics-empty">Aucune donnee disponible pour le moment.</p>';
    }
    echo '</div></article>';
};

$spotlightCard = static function (?array $event, string $label) use ($escape, $buildAdminUrl): string {
    if (!$event) {
        return '<article class="event-insight-card"><span class="section-badge">' . $escape($label) . '</span><p>Aucune donnee disponible.</p></article>';
    }

    $badges = '';
    foreach (($event['smart_badges'] ?? []) as $badge) {
        $badges .= '<span class="status-badge ' . $escape($badge['class'] ?? 'status-neutral') . '">' . $escape($badge['label'] ?? '') . '</span>';
    }

    return '<article class="event-insight-card">
                <span class="section-badge">' . $escape($label) . '</span>
                <h4>' . $escape($event['titre'] ?? '') . '</h4>
                <p>' . $escape($event['countdown_text'] ?? '') . '</p>
                <div class="event-badge-row">' . $badges . '</div>
                <a class="small-btn" href="' . $escape($buildAdminUrl(['edit_event' => $event['id_evenement'] ?? null], '#event-form')) . '">Ouvrir</a>
            </article>';
};

$selectedLatestRegistration = '-';
if (!empty($selectedEventParticipationStats['latest_registration'])) {
    $timestamp = strtotime((string) $selectedEventParticipationStats['latest_registration']);
    if ($timestamp !== false) {
        $selectedLatestRegistration = date('d/m/Y H:i', $timestamp);
    }
}
?>

<div class="event-page-loader" id="eventPageLoader" hidden aria-hidden="true">
    <div class="event-page-loader-card">
        <span class="event-page-loader-spinner"></span>
        <strong>Chargement du back office</strong>
        <span>Preparation des evenements, participations et statistiques.</span>
    </div>
</div>

<?php if ($flashes): ?>
    <section class="events-flash-stack reveal">
        <?php foreach ($flashes as $flash): ?>
            <article class="event-flash event-flash-<?php echo $escape($flash['type'] ?? 'info'); ?>">
                <strong><?php echo ($flash['type'] ?? '') === 'success' ? 'Succes' : 'Information'; ?></strong>
                <span><?php echo $escape($flash['message'] ?? ''); ?></span>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<section class="action-bar reveal">
    <form class="search-box event-filter-form" method="get" action="index.php">
        <input type="hidden" name="page" value="events">
        <?php if ($selectedManagementEventId > 0): ?>
            <input type="hidden" name="manage_event" value="<?php echo $escape($selectedManagementEventId); ?>">
        <?php endif; ?>
        <input type="hidden" name="participant_search" value="<?php echo $escape($participationFilters['search'] ?? ''); ?>">
        <input type="hidden" name="participant_status" value="<?php echo $escape($participationFilters['status'] ?? ''); ?>">
        <input type="hidden" name="participant_date_from" value="<?php echo $escape($participationFilters['date_from'] ?? ''); ?>">
        <input type="hidden" name="participant_date_to" value="<?php echo $escape($participationFilters['date_to'] ?? ''); ?>">
        <input type="hidden" name="participant_sort" value="<?php echo $escape($participationFilters['sort'] ?? 'registered_desc'); ?>">
        <input type="text" name="search" value="<?php echo $escape($filters['search'] ?? ''); ?>" placeholder="Rechercher un evenement...">
        <input type="date" name="date_from" value="<?php echo $escape($filters['date_from'] ?? ''); ?>" aria-label="Date minimale">
        <input type="date" name="date_to" value="<?php echo $escape($filters['date_to'] ?? ''); ?>" aria-label="Date maximale">

        <select name="type">
            <option value="">Tous les types</option>
            <?php foreach ($eventTypes as $typeKey => $typeLabel): ?>
                <option value="<?php echo $escape($typeKey); ?>" <?php echo ($filters['type'] ?? '') === $typeKey ? 'selected' : ''; ?>>
                    <?php echo $escape($typeLabel); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="status">
            <option value="">Tous les statuts</option>
            <?php foreach ($eventStatuses as $statusKey => $statusLabel): ?>
                <option value="<?php echo $escape($statusKey); ?>" <?php echo ($filters['status'] ?? '') === $statusKey ? 'selected' : ''; ?>>
                    <?php echo $escape($statusLabel); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="availability">
            <option value="">Toutes les disponibilites</option>
            <?php foreach ($availabilityOptions as $availabilityKey => $availabilityLabel): ?>
                <option value="<?php echo $escape($availabilityKey); ?>" <?php echo ($filters['availability'] ?? '') === $availabilityKey ? 'selected' : ''; ?>>
                    <?php echo $escape($availabilityLabel); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="sort">
            <?php foreach ($eventSortOptions as $sortKey => $sortLabel): ?>
                <option value="<?php echo $escape($sortKey); ?>" <?php echo ($filters['sort'] ?? 'date_asc') === $sortKey ? 'selected' : ''; ?>>
                    Tri : <?php echo $escape($sortLabel); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div class="icon-actions">
            <button type="submit" class="solid-btn">Filtrer</button>
            <a class="outline-btn" href="index.php?page=events">Tout afficher</a>
        </div>
    </form>
</section>

<section class="section reveal event-admin-shortcuts">
    <div class="icon-actions">
        <a class="small-btn" href="<?php echo $escape($buildAdminUrl(['sort' => 'priority', 'event_page' => null], '#events-table')); ?>">Vue prioritaire</a>
        <a class="small-btn" href="<?php echo $escape($buildAdminUrl(['availability' => 'open', 'event_page' => null], '#events-table')); ?>">Evenements ouverts</a>
        <a class="small-btn" href="<?php echo $escape($buildAdminUrl(['availability' => 'full', 'event_page' => null], '#events-table')); ?>">Complets</a>
        <a class="small-btn" href="<?php echo $escape($buildAdminUrl(['availability' => 'almost_full', 'event_page' => null], '#events-table')); ?>">Presque complets</a>
        <a class="small-btn" href="<?php echo $escape($buildAdminUrl(['status' => 'annule', 'event_page' => null], '#events-table')); ?>">Annul&eacute;s</a>
        <a class="small-btn" href="<?php echo $escape($buildAdminUrl(['participant_status' => 'en attente', 'participant_page' => null], '#participations-table')); ?>">Participations en attente</a>
        <a class="small-btn" href="<?php echo $escape($buildAdminUrl(['participant_status' => 'confirme', 'participant_page' => null], '#participations-table')); ?>">Participations confirmees</a>
    </div>
</section>

<section class="admin-stats reveal">
    <article class="admin-stat">
        <strong><?php echo $escape($stats['total_events'] ?? 0); ?></strong>
        <span>Evenements</span>
    </article>
    <article class="admin-stat">
        <strong><?php echo $escape($stats['upcoming_events'] ?? 0); ?></strong>
        <span>&Agrave; venir</span>
    </article>
    <article class="admin-stat">
        <strong><?php echo $escape($stats['total_participations'] ?? 0); ?></strong>
        <span>Participations</span>
    </article>
    <article class="admin-stat">
        <strong><?php echo $escape($stats['capacity_rate'] ?? 0); ?>%</strong>
        <span>Occupation</span>
    </article>
    <article class="admin-stat">
        <strong><?php echo $escape($stats['high_demand_events'] ?? 0); ?></strong>
        <span>Forte demande</span>
    </article>
    <article class="admin-stat">
        <strong><?php echo $escape($stats['events_without_participation'] ?? 0); ?></strong>
        <span>Sans inscription</span>
    </article>
</section>

<section class="section reveal">
    <div class="section-head">
        <div>
            <span class="section-badge">Statistiques</span>
            <h2>Pilotage visuel du module evenements</h2>
        </div>
    </div>
    <div class="event-analytics-grid event-analytics-grid-admin">
        <?php $renderSummaryBar($charts['eventLifecycle'] ?? [], 'Cycle', 'Etat du portefeuille'); ?>
        <?php $renderSummaryBar($charts['participationStatus'] ?? [], 'Demandes', 'Repartition globale'); ?>
        <?php $renderSummaryBar($charts['selectedEventCapacity'] ?? [], 'Capacite', 'Evenement selectionne'); ?>
        <?php $renderSummaryBar($charts['selectedEventParticipation'] ?? [], 'Participants', 'Statuts sur l\'evenement'); ?>
    </div>
</section>

<section class="admin-grid reveal">
    <article class="admin-panel event-admin-form-panel" id="event-form">
        <span class="section-badge"><?php echo $eventMode === 'edit' ? 'Mise a jour' : 'Creation'; ?></span>
        <h3><?php echo $eventMode === 'edit' ? 'Modifier un &eacute;v&eacute;nement' : 'Ajouter un nouvel &eacute;v&eacute;nement'; ?></h3>
        <p>
            Gere l'entite <strong>evenement</strong> avec image, dates, capacite, statut
            et presentation front-office harmonisee avec le reste du projet.
        </p>

        <form method="post" action="<?php echo $escape($buildAdminUrl([], '#event-form')); ?>" enctype="multipart/form-data" class="event-admin-form-grid">
            <input type="hidden" name="csrf_token" value="<?php echo $escape($csrfToken); ?>">
            <input type="hidden" name="event_action" value="<?php echo $eventMode === 'edit' ? 'update_event' : 'create_event'; ?>">
            <input type="hidden" name="return_to" value="<?php echo $escape($buildAdminUrl($eventMode === 'edit' && $eventRecord ? ['edit_event' => $eventRecord['id_evenement']] : [])); ?>">
            <?php if ($eventMode === 'edit' && $eventRecord): ?>
                <input type="hidden" name="id_evenement" value="<?php echo $escape($eventRecord['id_evenement']); ?>">
            <?php endif; ?>

            <div class="field-block<?php echo $eventError('titre') ? ' field-block-error' : ''; ?>">
                <label for="titre">Titre</label>
                <input id="titre" type="text" name="titre" value="<?php echo $escape($eventValue('titre')); ?>">
                <?php if ($eventError('titre')): ?><small class="event-field-error"><?php echo $escape($eventError('titre')); ?></small><?php endif; ?>
            </div>

            <div class="field-block<?php echo $eventError('lieu') ? ' field-block-error' : ''; ?>">
                <label for="lieu">Lieu</label>
                <input id="lieu" type="text" name="lieu" value="<?php echo $escape($eventValue('lieu')); ?>">
                <?php if ($eventError('lieu')): ?><small class="event-field-error"><?php echo $escape($eventError('lieu')); ?></small><?php endif; ?>
            </div>

            <div class="field-block field-span-2<?php echo $eventError('description') ? ' field-block-error' : ''; ?>">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="5"><?php echo $escape($eventValue('description')); ?></textarea>
                <?php if ($eventError('description')): ?><small class="event-field-error"><?php echo $escape($eventError('description')); ?></small><?php endif; ?>
            </div>

            <div class="field-block<?php echo $eventError('date_debut') ? ' field-block-error' : ''; ?>">
                <label for="date_debut">Date de debut</label>
                <input id="date_debut" type="datetime-local" name="date_debut" value="<?php echo $escape($eventStartValue); ?>">
                <?php if ($eventError('date_debut')): ?><small class="event-field-error"><?php echo $escape($eventError('date_debut')); ?></small><?php endif; ?>
            </div>

            <div class="field-block<?php echo $eventError('date_fin') ? ' field-block-error' : ''; ?>">
                <label for="date_fin">Date de fin</label>
                <input id="date_fin" type="datetime-local" name="date_fin" value="<?php echo $escape($eventEndValue); ?>">
                <?php if ($eventError('date_fin')): ?><small class="event-field-error"><?php echo $escape($eventError('date_fin')); ?></small><?php endif; ?>
            </div>

            <div class="field-block<?php echo $eventError('type_evenement') ? ' field-block-error' : ''; ?>">
                <label for="type_evenement">Type d'evenement</label>
                <select id="type_evenement" name="type_evenement">
                    <option value="">S&eacute;lectionner</option>
                    <?php foreach ($eventTypes as $typeKey => $typeLabel): ?>
                        <option value="<?php echo $escape($typeKey); ?>" <?php echo $eventValue('type_evenement') === (string) $typeKey ? 'selected' : ''; ?>>
                            <?php echo $escape($typeLabel); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($eventError('type_evenement')): ?><small class="event-field-error"><?php echo $escape($eventError('type_evenement')); ?></small><?php endif; ?>
            </div>

            <div class="field-block<?php echo $eventError('statut') ? ' field-block-error' : ''; ?>">
                <label for="statut">Statut</label>
                <select id="statut" name="statut">
                    <option value="">S&eacute;lectionner</option>
                    <?php foreach ($eventStatuses as $statusKey => $statusLabel): ?>
                        <option value="<?php echo $escape($statusKey); ?>" <?php echo $eventValue('statut') === (string) $statusKey ? 'selected' : ''; ?>>
                            <?php echo $escape($statusLabel); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($eventError('statut')): ?><small class="event-field-error"><?php echo $escape($eventError('statut')); ?></small><?php endif; ?>
            </div>

            <div class="field-block<?php echo $eventError('nb_places') ? ' field-block-error' : ''; ?>">
                <label for="nb_places">Nombre de places</label>
                <input id="nb_places" type="number" min="1" name="nb_places" value="<?php echo $escape($eventValue('nb_places')); ?>">
                <?php if ($eventError('nb_places')): ?><small class="event-field-error"><?php echo $escape($eventError('nb_places')); ?></small><?php endif; ?>
            </div>

            <div class="field-block<?php echo $eventError('image') ? ' field-block-error' : ''; ?>">
                <label for="image">Image</label>
                <input id="image" type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
                <?php if ($eventError('image')): ?><small class="event-field-error"><?php echo $escape($eventError('image')); ?></small><?php endif; ?>
            </div>

            <?php if ($eventRecord && !empty($eventRecord['image_path'])): ?>
                <div class="field-block field-span-2">
                    <span class="event-inline-label">Image actuelle</span>
                    <div class="event-inline-image">
                        <img src="<?php echo $escape($eventRecord['image_path']); ?>" alt="<?php echo $escape($eventRecord['titre']); ?>">
                    </div>
                </div>
            <?php endif; ?>

            <div class="icon-actions field-span-2">
                <button type="submit" class="solid-btn"><?php echo $eventMode === 'edit' ? 'Mettre &agrave; jour' : 'Cr&eacute;er l\'&eacute;v&eacute;nement'; ?></button>
                <a class="outline-btn" href="<?php echo $escape($buildAdminUrl(['edit_event' => null], '#event-form')); ?>">Nouveau formulaire</a>
                <?php if ($eventMode === 'edit' && $eventRecord): ?>
                    <a class="outline-btn" href="../front/index.php?page=events&amp;event_id=<?php echo $escape($eventRecord['id_evenement']); ?>#event-focus">Voir en front</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($eventMode === 'edit' && $eventRecord): ?>
            <form method="post" action="<?php echo $escape($buildAdminUrl([], '#events-table')); ?>" class="event-delete-form" onsubmit="return confirm('Supprimer cet evenement et toutes ses participations ?');">
                <input type="hidden" name="csrf_token" value="<?php echo $escape($csrfToken); ?>">
                <input type="hidden" name="event_action" value="delete_event">
                <input type="hidden" name="id_evenement" value="<?php echo $escape($eventRecord['id_evenement']); ?>">
                <input type="hidden" name="return_to" value="<?php echo $escape($buildAdminUrl(['edit_event' => $eventRecord['id_evenement']])); ?>">
                <button type="submit" class="danger-btn">Supprimer l'&eacute;v&eacute;nement</button>
            </form>
        <?php endif; ?>
    </article>

    <article class="admin-panel event-admin-insights">
        <div class="section-head">
            <div>
                <span class="section-badge">Insights</span>
                <h3>Lecture rapide des signaux importants</h3>
            </div>
            <span><?php echo $escape($activeEventFilterCount); ?> filtre(s) actif(s)</span>
        </div>

        <div class="event-insight-grid">
            <?php echo $spotlightCard($insights['nextUpcomingEvent'] ?? null, 'Prochain evenement'); ?>
            <?php echo $spotlightCard($insights['mostPopularEvent'] ?? null, 'Le plus demande'); ?>
            <?php echo $spotlightCard($insights['highestOccupancyEvent'] ?? null, 'Occupation maximale'); ?>
            <?php echo $spotlightCard($insights['mostAvailableEvent'] ?? null, 'Plus de places libres'); ?>
        </div>

        <div class="event-progress-summary">
            <div class="section-head event-mini-head">
                <div>
                    <span class="section-badge">Evenement observe</span>
                    <h4><?php echo $selectedManagementEvent ? $escape($selectedManagementEvent['titre']) : 'Aucun evenement'; ?></h4>
                </div>
                <?php if ($selectedManagementEvent): ?>
                    <span class="status-badge <?php echo $escape($selectedManagementEvent['status_class']); ?>"><?php echo $escape($selectedManagementEvent['status_label']); ?></span>
                <?php endif; ?>
            </div>

            <?php if ($selectedManagementEvent): ?>
                <div class="event-facts">
                    <div>
                        <strong>Demarrage</strong>
                        <span><?php echo $escape($selectedManagementEvent['start_display']); ?></span>
                    </div>
                    <div>
                        <strong>Capacite</strong>
                        <span><?php echo $escape($selectedManagementEvent['participants_count']); ?> / <?php echo $escape($selectedManagementEvent['nb_places']); ?></span>
                    </div>
                </div>
                <div class="event-badge-row event-badge-row-compact">
                    <?php foreach (($selectedManagementEvent['smart_badges'] ?? []) as $badge): ?>
                        <span class="status-badge <?php echo $escape($badge['class']); ?>"><?php echo $escape($badge['label']); ?></span>
                    <?php endforeach; ?>
                    <?php if ((int) ($selectedManagementEvent['participants_count'] ?? 0) === 0): ?>
                        <span class="status-badge status-neutral">Aucune inscription</span>
                    <?php endif; ?>
                </div>
                <div class="event-progress-bar event-progress-bar-large">
                    <span style="width: <?php echo $escape($selectedManagementEvent['fill_rate'] ?? 0); ?>%;"></span>
                </div>
                <div class="feature-list">
                    <div class="feature-item">Derniere inscription : <?php echo $escape($selectedLatestRegistration); ?></div>
                    <div class="feature-item">Demandes en attente : <?php echo $escape($selectedEventParticipationStats['pending_participations'] ?? 0); ?></div>
                    <div class="feature-item">Places restantes : <?php echo $escape($selectedManagementEvent['remaining_places'] ?? 0); ?></div>
                </div>
            <?php else: ?>
                <p>Aucun evenement disponible pour le suivi detaille.</p>
            <?php endif; ?>
        </div>

        <?php if ($pendingByEvent): ?>
            <div class="event-highlight-list">
                <?php foreach ($pendingByEvent as $pendingItem): ?>
                    <a class="event-highlight-row" href="<?php echo $escape($buildAdminUrl(['manage_event' => $pendingItem['id_evenement'], 'participant_page' => null], '#participations-table')); ?>">
                        <div>
                            <strong><?php echo $escape($pendingItem['titre']); ?></strong>
                            <span>Demandes en attente</span>
                        </div>
                        <span><?php echo $escape($pendingItem['count']); ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </article>
</section>

<section class="admin-panel reveal" id="events-table">
    <div class="section-head">
        <div>
            <span class="section-badge">CRUD &eacute;v&eacute;nement</span>
            <h3>Catalogue admin des evenements</h3>
        </div>
        <div class="meta-row">
            <span><?php echo $escape($eventPagination['total_items'] ?? count($events)); ?> resultat(s)</span>
            <span><?php echo $escape($activeEventFilterCount); ?> filtre(s) actif(s)</span>
        </div>
    </div>

    <div class="table-wrap">
        <table class="module-table">
            <thead>
                <tr>
                    <th>Evenement</th>
                    <th>Debut</th>
                    <th>Fin</th>
                    <th>Lieu</th>
                    <th>Type</th>
                    <th>Occupation</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($events): ?>
                    <?php foreach ($events as $event): ?>
                        <tr class="<?php echo (int) ($event['id_evenement'] ?? 0) === $selectedManagementEventId ? 'event-table-selected' : ''; ?>">
                            <td>
                                <div class="event-table-title">
                                    <strong><?php echo $escape($event['titre']); ?></strong>
                                    <span><?php echo $escape($event['countdown_text']); ?></span>
                                </div>
                                <div class="event-badge-row event-badge-row-compact">
                                    <?php foreach (($event['smart_badges'] ?? []) as $badge): ?>
                                        <span class="status-badge <?php echo $escape($badge['class']); ?>"><?php echo $escape($badge['label']); ?></span>
                                    <?php endforeach; ?>
                                    <?php if ((int) ($event['participants_count'] ?? 0) === 0): ?>
                                        <span class="status-badge status-neutral">Aucune inscription</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?php echo $escape($event['start_display']); ?></td>
                            <td><?php echo $escape($event['end_display']); ?></td>
                            <td><?php echo $escape($event['lieu']); ?></td>
                            <td><?php echo $escape($event['type_label']); ?></td>
                            <td>
                                <div><?php echo $escape($event['participants_count']); ?> / <?php echo $escape($event['nb_places']); ?></div>
                                <div class="event-progress-bar">
                                    <span style="width: <?php echo $escape($event['fill_rate']); ?>%;"></span>
                                </div>
                                <small><?php echo $escape($event['demand_text']); ?></small>
                            </td>
                            <td><span class="status-badge <?php echo $escape($event['status_class']); ?>"><?php echo $escape($event['status_label']); ?></span></td>
                            <td class="admin-tools">
                                <a class="small-btn" href="<?php echo $escape($buildAdminUrl(['edit_event' => $event['id_evenement']], '#event-form')); ?>">Modifier</a>
                                <a class="small-btn" href="<?php echo $escape($buildAdminUrl(['manage_event' => $event['id_evenement'], 'participant_page' => null], '#participations-table')); ?>">Participations</a>
                                <a class="small-btn" href="../front/index.php?page=events&amp;event_id=<?php echo $escape($event['id_evenement']); ?>#event-focus">Voir</a>
                                <form method="post" action="<?php echo $escape($buildAdminUrl([], '#events-table')); ?>" onsubmit="return confirm('Supprimer cet evenement ?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $escape($csrfToken); ?>">
                                    <input type="hidden" name="event_action" value="delete_event">
                                    <input type="hidden" name="id_evenement" value="<?php echo $escape($event['id_evenement']); ?>">
                                    <input type="hidden" name="return_to" value="<?php echo $escape($buildAdminUrl(['edit_event' => $event['id_evenement']])); ?>">
                                    <button type="submit" class="danger-btn">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8">Aucun evenement trouve avec ces filtres.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php $renderPagination($eventPagination, 'event_page', $buildAdminUrl, '#events-table'); ?>
</section>

<section class="admin-panel reveal" id="participations-table">
    <div class="section-head">
        <div>
            <span class="section-badge">Jointure evenement - participation</span>
            <h3>
                <?php if ($selectedManagementEvent): ?>
                    Participants de <?php echo $escape($selectedManagementEvent['titre']); ?>
                <?php else: ?>
                    Suivi des inscriptions
                <?php endif; ?>
            </h3>
        </div>
        <div class="meta-row">
            <span><?php echo $escape($participationPagination['total_items'] ?? count($participations)); ?> resultat(s)</span>
            <span><?php echo $escape($activeParticipationFilterCount); ?> filtre(s) actif(s)</span>
        </div>
    </div>

    <?php if ($selectedManagementEvent): ?>
        <article class="event-selected-summary">
            <div>
                <strong><?php echo $escape($selectedManagementEvent['titre']); ?></strong>
                <span><?php echo $escape($selectedManagementEvent['start_display']); ?> - <?php echo $escape($selectedManagementEvent['lieu']); ?></span>
            </div>
            <div class="event-selected-summary-stats">
                <span><?php echo $escape($selectedManagementEvent['participants_count']); ?> active(s)</span>
                <span><?php echo $escape($selectedEventParticipationStats['pending_participations'] ?? 0); ?> en attente</span>
                <span><?php echo $escape($selectedManagementEvent['remaining_places'] ?? 0); ?> place(s) libre(s)</span>
            </div>
        </article>
    <?php endif; ?>

    <div class="icon-actions event-admin-table-actions">
        <a class="small-btn" href="<?php echo $escape($buildAdminUrl(['export' => 'participations_csv'])); ?>">Exporter CSV</a>
        <a class="small-btn" href="<?php echo $escape($buildAdminUrl(['print' => 'participations'])); ?>" target="_blank" rel="noopener noreferrer">Version imprimable</a>
        <a class="small-btn" href="<?php echo $escape($buildAdminUrl(['participant_status' => 'en attente', 'participant_page' => null], '#participations-table')); ?>">Voir seulement en attente</a>
        <a class="small-btn" href="<?php echo $escape($buildAdminUrl(['participant_status' => 'confirme', 'participant_page' => null], '#participations-table')); ?>">Voir seulement confirme</a>
        <a class="small-btn" href="<?php echo $escape($buildAdminUrl(['participant_status' => null, 'participant_search' => null, 'participant_date_from' => null, 'participant_date_to' => null, 'participant_page' => null], '#participations-table')); ?>">Voir tout</a>
    </div>

    <form class="search-box event-filter-form event-filter-form-secondary" method="get" action="index.php">
        <input type="hidden" name="page" value="events">
        <?php if ($selectedManagementEventId > 0): ?>
            <input type="hidden" name="manage_event" value="<?php echo $escape($selectedManagementEventId); ?>">
        <?php endif; ?>
        <input type="hidden" name="search" value="<?php echo $escape($filters['search'] ?? ''); ?>">
        <input type="hidden" name="type" value="<?php echo $escape($filters['type'] ?? ''); ?>">
        <input type="hidden" name="status" value="<?php echo $escape($filters['status'] ?? ''); ?>">
        <input type="hidden" name="availability" value="<?php echo $escape($filters['availability'] ?? ''); ?>">
        <input type="hidden" name="date_from" value="<?php echo $escape($filters['date_from'] ?? ''); ?>">
        <input type="hidden" name="date_to" value="<?php echo $escape($filters['date_to'] ?? ''); ?>">
        <input type="hidden" name="sort" value="<?php echo $escape($filters['sort'] ?? 'date_asc'); ?>">

        <input type="text" name="participant_search" value="<?php echo $escape($participationFilters['search'] ?? ''); ?>" placeholder="Rechercher un participant, un email ou un telephone...">
        <input type="date" name="participant_date_from" value="<?php echo $escape($participationFilters['date_from'] ?? ''); ?>" aria-label="Date minimale d inscription">
        <input type="date" name="participant_date_to" value="<?php echo $escape($participationFilters['date_to'] ?? ''); ?>" aria-label="Date maximale d inscription">

        <select name="participant_status">
            <option value="">Tous les statuts</option>
            <?php foreach ($participationStatuses as $statusKey => $statusLabel): ?>
                <option value="<?php echo $escape($statusKey); ?>" <?php echo ($participationFilters['status'] ?? '') === $statusKey ? 'selected' : ''; ?>>
                    <?php echo $escape($statusLabel); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="manage_event">
            <option value="">Choisir l'&eacute;v&eacute;nement</option>
            <?php foreach ($allEvents as $event): ?>
                <option value="<?php echo $escape($event['id_evenement']); ?>" <?php echo (int) $selectedManagementEventId === (int) $event['id_evenement'] ? 'selected' : ''; ?>>
                    <?php echo $escape($event['titre']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="participant_sort">
            <?php foreach ($participationSortOptions as $sortKey => $sortLabel): ?>
                <option value="<?php echo $escape($sortKey); ?>" <?php echo ($participationFilters['sort'] ?? 'registered_desc') === $sortKey ? 'selected' : ''; ?>>
                    Tri : <?php echo $escape($sortLabel); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div class="icon-actions">
            <button type="submit" class="solid-btn">Filtrer</button>
            <a class="outline-btn" href="<?php echo $escape($buildAdminUrl([
                'participant_search' => null,
                'participant_status' => null,
                'participant_date_from' => null,
                'participant_date_to' => null,
                'participant_sort' => null,
                'participant_page' => null,
            ], '#participations-table')); ?>">Effacer</a>
        </div>
    </form>

    <?php if ($selectedManagementEvent): ?>
        <form class="event-bulk-form" method="post" action="<?php echo $escape($buildAdminUrl([], '#participations-table')); ?>">
            <input type="hidden" name="csrf_token" value="<?php echo $escape($csrfToken); ?>">
            <input type="hidden" name="event_action" value="bulk_participation_status">
            <input type="hidden" name="bulk_event_id" value="<?php echo $escape($selectedManagementEvent['id_evenement']); ?>">
            <input type="hidden" name="return_to" value="<?php echo $escape($buildAdminUrl(['manage_event' => $selectedManagementEvent['id_evenement']])); ?>">
            <div class="field-block">
                <label>Evenement cible</label>
                <input type="text" value="<?php echo $escape($selectedManagementEvent['titre']); ?>" readonly>
            </div>
            <div class="field-block">
                <label for="bulk_status">Basculer toutes les participations en attente vers</label>
                <select id="bulk_status" name="bulk_status">
                    <option value="confirme">Confirme</option>
                    <option value="annule">Annule</option>
                </select>
            </div>
            <div class="icon-actions">
                <button type="submit" class="solid-btn">Ex&eacute;cuter l'action</button>
            </div>
        </form>
    <?php endif; ?>

    <div class="table-wrap">
        <table class="module-table">
            <thead>
                <tr>
                    <th>Participant</th>
                    <th>Evenement</th>
                    <th>Telephone</th>
                    <th>Inscription</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($participations): ?>
                    <?php foreach ($participations as $participation): ?>
                        <tr>
                            <td>
                                <div class="event-table-title">
                                    <strong><?php echo $escape($participation['nom_participant']); ?></strong>
                                    <span><?php echo $escape($participation['email_participant']); ?></span>
                                </div>
                            </td>
                            <td><?php echo $escape($participation['evenement_titre']); ?></td>
                            <td><?php echo $escape($participation['telephone']); ?></td>
                            <td><?php echo $escape($participation['registered_display']); ?></td>
                            <td><span class="status-badge <?php echo $escape($participation['status_class']); ?>"><?php echo $escape($participation['status_label']); ?></span></td>
                            <td class="admin-tools">
                                <a class="small-btn" href="<?php echo $escape($buildAdminUrl(['edit_participation' => $participation['id_participation']], '#participation-form')); ?>">Modifier</a>

                                <?php if (($participation['statut_participation'] ?? '') !== 'confirme'): ?>
                                    <form method="post" action="<?php echo $escape($buildAdminUrl([], '#participations-table')); ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $escape($csrfToken); ?>">
                                        <input type="hidden" name="event_action" value="set_participation_status">
                                        <input type="hidden" name="id_participation" value="<?php echo $escape($participation['id_participation']); ?>">
                                        <input type="hidden" name="statut_participation" value="confirme">
                                        <input type="hidden" name="return_to" value="<?php echo $escape($buildAdminUrl(['manage_event' => $selectedManagementEventId, 'edit_participation' => $participation['id_participation']])); ?>">
                                        <button type="submit" class="success-btn">Confirmer</button>
                                    </form>
                                <?php endif; ?>

                                <?php if (($participation['statut_participation'] ?? '') !== 'annule'): ?>
                                    <form method="post" action="<?php echo $escape($buildAdminUrl([], '#participations-table')); ?>">
                                        <input type="hidden" name="csrf_token" value="<?php echo $escape($csrfToken); ?>">
                                        <input type="hidden" name="event_action" value="set_participation_status">
                                        <input type="hidden" name="id_participation" value="<?php echo $escape($participation['id_participation']); ?>">
                                        <input type="hidden" name="statut_participation" value="annule">
                                        <input type="hidden" name="return_to" value="<?php echo $escape($buildAdminUrl(['manage_event' => $selectedManagementEventId, 'edit_participation' => $participation['id_participation']])); ?>">
                                        <button type="submit" class="outline-btn">Annuler</button>
                                    </form>
                                <?php endif; ?>

                                <form method="post" action="<?php echo $escape($buildAdminUrl([], '#participations-table')); ?>" onsubmit="return confirm('Supprimer cette participation ?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo $escape($csrfToken); ?>">
                                    <input type="hidden" name="event_action" value="delete_participation">
                                    <input type="hidden" name="id_participation" value="<?php echo $escape($participation['id_participation']); ?>">
                                    <input type="hidden" name="return_to" value="<?php echo $escape($buildAdminUrl(['manage_event' => $selectedManagementEventId, 'edit_participation' => $participation['id_participation']])); ?>">
                                    <button type="submit" class="danger-btn">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6">
                            <?php if ($selectedManagementEvent): ?>
                                Aucune participation trouvee pour cet evenement avec ces filtres.
                            <?php else: ?>
                                S&eacute;lectionnez un &eacute;v&eacute;nement pour afficher ses participations.
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php $renderPagination($participationPagination, 'participant_page', $buildAdminUrl, '#participations-table'); ?>
</section>

<section class="admin-panel reveal" id="participation-form">
    <span class="section-badge"><?php echo $participationRecord ? 'Edition' : 'Aide'; ?></span>
    <h3><?php echo $participationRecord ? 'Modifier une participation' : 'S&eacute;lectionnez une participation &agrave; modifier'; ?></h3>
    <p>
        Cette zone permet d'ajuster manuellement le statut, le telephone, l'evenement associe
        ou les informations du participant sans perdre le contexte de navigation courant.
    </p>

    <?php if ($participationRecord): ?>
        <form method="post" action="<?php echo $escape($buildAdminUrl([], '#participation-form')); ?>" class="event-admin-form-grid">
            <input type="hidden" name="csrf_token" value="<?php echo $escape($csrfToken); ?>">
            <input type="hidden" name="event_action" value="update_participation">
            <input type="hidden" name="id_participation" value="<?php echo $escape($participationRecord['id_participation']); ?>">
            <input type="hidden" name="return_to" value="<?php echo $escape($buildAdminUrl(['manage_event' => $selectedManagementEventId, 'edit_participation' => $participationRecord['id_participation']])); ?>">

            <div class="field-block<?php echo $participationError('id_evenement') ? ' field-block-error' : ''; ?>">
                <label for="id_evenement">Evenement</label>
                <select id="id_evenement" name="id_evenement">
                    <option value="">S&eacute;lectionner</option>
                    <?php foreach ($allEvents as $event): ?>
                        <option value="<?php echo $escape($event['id_evenement']); ?>" <?php echo $participationValue('id_evenement') === (string) $event['id_evenement'] ? 'selected' : ''; ?>>
                            <?php echo $escape($event['titre']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($participationError('id_evenement')): ?><small class="event-field-error"><?php echo $escape($participationError('id_evenement')); ?></small><?php endif; ?>
            </div>

            <div class="field-block<?php echo $participationError('statut_participation') ? ' field-block-error' : ''; ?>">
                <label for="statut_participation">Statut participation</label>
                <select id="statut_participation" name="statut_participation">
                    <option value="">S&eacute;lectionner</option>
                    <?php foreach ($participationStatuses as $statusKey => $statusLabel): ?>
                        <option value="<?php echo $escape($statusKey); ?>" <?php echo $participationValue('statut_participation') === (string) $statusKey ? 'selected' : ''; ?>>
                            <?php echo $escape($statusLabel); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($participationError('statut_participation')): ?><small class="event-field-error"><?php echo $escape($participationError('statut_participation')); ?></small><?php endif; ?>
            </div>

            <div class="field-block<?php echo $participationError('nom_participant') ? ' field-block-error' : ''; ?>">
                <label for="nom_participant">Nom</label>
                <input id="nom_participant" type="text" name="nom_participant" value="<?php echo $escape($participationValue('nom_participant')); ?>">
                <?php if ($participationError('nom_participant')): ?><small class="event-field-error"><?php echo $escape($participationError('nom_participant')); ?></small><?php endif; ?>
            </div>

            <div class="field-block<?php echo $participationError('email_participant') ? ' field-block-error' : ''; ?>">
                <label for="email_participant">Email</label>
                <input id="email_participant" type="email" name="email_participant" value="<?php echo $escape($participationValue('email_participant')); ?>">
                <?php if ($participationError('email_participant')): ?><small class="event-field-error"><?php echo $escape($participationError('email_participant')); ?></small><?php endif; ?>
            </div>

            <div class="field-block field-span-2<?php echo $participationError('telephone') ? ' field-block-error' : ''; ?>">
                <label for="telephone">Telephone</label>
                <input id="telephone" type="text" name="telephone" value="<?php echo $escape($participationValue('telephone')); ?>">
                <?php if ($participationError('telephone')): ?><small class="event-field-error"><?php echo $escape($participationError('telephone')); ?></small><?php endif; ?>
            </div>

            <div class="icon-actions field-span-2">
                <button type="submit" class="solid-btn">Enregistrer les changements</button>
                <a class="outline-btn" href="<?php echo $escape($buildAdminUrl(['edit_participation' => null], '#participations-table')); ?>">Fermer l'&eacute;dition</a>
            </div>
        </form>
    <?php else: ?>
        <div class="feature-list">
            <div class="feature-item">Cliquez sur "Participations" depuis un evenement pour afficher la jointure de maniere logique.</div>
            <div class="feature-item">Le tableau ne montre que les participants de l'&eacute;v&eacute;nement s&eacute;lectionn&eacute;.</div>
            <div class="feature-item">La version imprimable et l'export CSV reprennent exactement ces m&ecirc;mes filtres.</div>
        </div>
    <?php endif; ?>
</section>
