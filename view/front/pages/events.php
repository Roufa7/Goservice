<?php
$escape = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

$data = $eventFrontData ?? [];
$events = $data['events'] ?? [];
$selectedEvent = $data['selectedEvent'] ?? null;
$selectedEventId = (int) ($data['selectedEventId'] ?? 0);
$featuredEvent = $data['featuredEvent'] ?? null;
$filters = $data['filters'] ?? ['search' => '', 'type' => '', 'status' => '', 'availability' => '', 'date_from' => '', 'date_to' => '', 'sort' => 'priority'];
$flashes = $data['flashes'] ?? [];
$formValues = $data['formValues'] ?? [];
$formErrors = $data['formErrors'] ?? [];
$registrationReceipt = $data['registrationReceipt'] ?? null;
$csrfToken = $data['csrfToken'] ?? '';
$stats = $data['stats'] ?? [];
$typeHighlights = $data['typeHighlights'] ?? [];
$eventTypes = $data['eventTypes'] ?? [];
$eventStatuses = $data['eventStatuses'] ?? [];
$eventSortOptions = $data['eventSortOptions'] ?? [];
$availabilityOptions = $data['availabilityOptions'] ?? [];
$filterChips = $data['filterChips'] ?? [];
$timelineGroups = $data['timelineGroups'] ?? [];
$timelineSummary = $data['timelineSummary'] ?? [];
$activeFilterCount = (int) ($data['activeFilterCount'] ?? 0);
$pagination = $data['pagination'] ?? ['page' => 1, 'per_page' => 6, 'total_items' => 0, 'total_pages' => 1, 'has_previous' => false, 'has_next' => false, 'from' => 0, 'to' => 0];
$selectedEventParticipationStats = $data['selectedEventParticipationStats'] ?? [];
$selectedEventHighlights = $data['selectedEventHighlights'] ?? [];
$selectedEventSummaryChart = $data['selectedEventSummaryChart'] ?? [];
$selectedCapacityChart = $data['selectedCapacityChart'] ?? [];
$selectedEventCalendarUrl = (string) ($data['selectedEventCalendarUrl'] ?? '');
$selectedEventMapUrl = (string) ($data['selectedEventMapUrl'] ?? '');
$selectedEventMapEmbedUrl = (string) ($data['selectedEventMapEmbedUrl'] ?? '');
$registrationQr = (string) ($data['registrationQr'] ?? '');
$registrationQrReference = (string) ($data['registrationQrReference'] ?? '');
$registrationPassUrl = (string) ($data['registrationPassUrl'] ?? '');
$publicPass = $data['publicPass'] ?? null;

$oldValue = static function (string $key, string $default = '') use ($formValues): string {
    return (string) ($formValues[$key] ?? $default);
};

$fieldError = static function (string $key) use ($formErrors): ?string {
    return isset($formErrors[$key]) ? (string) $formErrors[$key] : null;
};

$buildFrontUrl = static function (array $params = [], string $hash = '') use ($filters): string {
    $query = ['page' => 'events'];

    foreach (['search', 'type', 'status', 'availability', 'date_from', 'date_to', 'sort', 'event_page'] as $key) {
        $value = $filters[$key] ?? '';
        if ($value !== '' && !($key === 'sort' && $value === 'priority') && !($key === 'event_page' && (string) $value === '1')) {
            $query[$key] = $value;
        }
    }

    foreach ($params as $key => $value) {
        if ($value === null || $value === '' || ($key === 'sort' && $value === 'priority') || ($key === 'event_page' && (string) $value === '1')) {
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
?>

<div class="event-page-loader" id="eventPageLoader" hidden aria-hidden="true">
    <div class="event-page-loader-card">
        <span class="event-page-loader-spinner"></span>
        <strong>Chargement du module evenements</strong>
        <span>Preparation de la page et des statistiques.</span>
    </div>
</div>

<section class="page-hero reveal event-page-hero">
    <div class="event-hero-copy">
        <span class="section-badge">&Eacute;v&eacute;nements</span>
        <h1 class="page-title">Decouvrez, reservez et suivez les moments forts de la plateforme.</h1>
        <p class="page-intro">
            Une vitrine claire pour les ateliers, formations, promotions et rendez-vous de service,
            avec une inscription fluide et une confidentialite totale sur les autres participants.
        </p>
        <div class="hero-actions">
            <a class="solid-btn" href="#events-catalog">Explorer les evenements</a>
            <a class="outline-btn" href="#timeline-board">Voir la timeline</a>
        </div>
    </div>

    <div class="event-kpi-grid">
        <article class="glass-card event-kpi-card">
            <strong><?php echo $escape($stats['total_events'] ?? 0); ?></strong>
            <span>Evenements publies</span>
        </article>
        <article class="glass-card event-kpi-card">
            <strong><?php echo $escape($stats['upcoming_events'] ?? 0); ?></strong>
            <span>&Agrave; venir</span>
        </article>
        <article class="glass-card event-kpi-card">
            <strong><?php echo $escape($stats['total_participations'] ?? 0); ?></strong>
            <span>Demandes recues</span>
        </article>
        <article class="glass-card event-kpi-card">
            <strong><?php echo $escape($stats['occupancy_rate'] ?? 0); ?>%</strong>
            <span>Taux d'occupation</span>
        </article>
    </div>
</section>

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

<?php if ($publicPass): ?>
    <section class="section reveal" id="event-pass">
        <article class="panel event-pass-card">
            <div class="section-head">
                <div>
                    <span class="section-badge">Pass QR</span>
                    <h2>Pass de participation pret a etre presente</h2>
                </div>
                <span class="event-qr-reference">Reference : <?php echo $escape($publicPass['reference'] ?? ''); ?></span>
            </div>
            <p class="event-pass-copy">
                Ce QR ouvre directement une fiche de participation lisible sur mobile. Il confirme l'inscription sans afficher les autres participants.
            </p>
            <div class="event-detail-grid">
                <div class="event-detail-card">
                    <strong>Participant</strong>
                    <span><?php echo $escape($publicPass['holder_label'] ?? 'Participant confirme'); ?></span>
                </div>
                <div class="event-detail-card">
                    <strong>Evenement</strong>
                    <span><?php echo $escape($publicPass['event']['titre'] ?? ''); ?></span>
                </div>
                <div class="event-detail-card">
                    <strong>Statut</strong>
                    <span><?php echo $escape($publicPass['participation']['status_label'] ?? ''); ?></span>
                </div>
                <div class="event-detail-card">
                    <strong>Inscription</strong>
                    <span><?php echo $escape($publicPass['participation']['registered_display'] ?? ''); ?></span>
                </div>
            </div>
            <div class="icon-actions event-map-actions">
                <?php if (!empty($publicPass['calendar_url'])): ?>
                    <a class="outline-btn" data-no-loader="true" href="<?php echo $escape($publicPass['calendar_url']); ?>">Telecharger le calendrier (.ics)</a>
                <?php endif; ?>
                <?php if (!empty($publicPass['map_url'])): ?>
                    <a class="outline-btn" data-no-loader="true" target="_blank" rel="noopener noreferrer" href="<?php echo $escape($publicPass['map_url']); ?>">Ouvrir le lieu</a>
                <?php endif; ?>
            </div>
        </article>
    </section>
<?php endif; ?>
<?php if ($featuredEvent): ?>
    <section class="section reveal">
        <article class="panel event-featured-card">
            <div class="event-featured-media">
                <div class="event-featured-surface<?php echo !empty($featuredEvent['image_path']) ? ' has-image' : ''; ?>"<?php echo !empty($featuredEvent['image_path']) ? ' style="background-image:url(\'' . $escape($featuredEvent['image_path']) . '\');"' : ''; ?>>
                    <div class="event-badge-row event-badge-row-featured">
                        <?php foreach (array_slice($featuredEvent['smart_badges'], 0, 3) as $badge): ?>
                            <span class="status-badge <?php echo $escape($badge['class']); ?>"><?php echo $escape($badge['label']); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <div class="event-surface-copy">
                        <span class="event-surface-type"><?php echo $escape($featuredEvent['type_label']); ?></span>
                        <strong><?php echo $escape($featuredEvent['titre']); ?></strong>
                        <span><?php echo $escape($featuredEvent['countdown_text']); ?></span>
                    </div>
                </div>
            </div>
            <div class="event-featured-body">
                <div class="meta-row">
                    <span class="section-badge">Evenement a la une</span>
                    <span class="event-featured-countdown"><?php echo $escape($featuredEvent['availability_text']); ?></span>
                </div>
                <h2><?php echo $escape($featuredEvent['titre']); ?></h2>
                <p><?php echo $escape($featuredEvent['description']); ?></p>

                <div class="event-facts event-facts-large">
                    <div>
                        <strong>Date</strong>
                        <span><?php echo $escape($featuredEvent['start_display']); ?></span>
                    </div>
                    <div>
                        <strong>Lieu</strong>
                        <span><?php echo $escape($featuredEvent['lieu']); ?></span>
                    </div>
                    <div>
                        <strong>Places</strong>
                        <span><?php echo $escape($featuredEvent['participants_count']); ?> / <?php echo $escape($featuredEvent['nb_places']); ?></span>
                    </div>
                    <div>
                        <strong>Dynamique</strong>
                        <span><?php echo $escape($featuredEvent['demand_text']); ?></span>
                    </div>
                </div>

                <div class="icon-actions">
                    <a class="solid-btn" href="<?php echo $escape($buildFrontUrl(['event_id' => $featuredEvent['id_evenement']], '#event-focus')); ?>">Voir la fiche</a>
                    <a class="outline-btn" href="<?php echo $escape($buildFrontUrl(['event_id' => $featuredEvent['id_evenement']], '#participation-form')); ?>">S'inscrire</a>
                </div>
            </div>
        </article>
    </section>
<?php endif; ?>

<section class="action-bar reveal">
    <form class="search-box event-filter-form" method="get" action="index.php">
        <input type="hidden" name="page" value="events">
        <input type="text" name="search" value="<?php echo $escape($filters['search'] ?? ''); ?>" placeholder="Rechercher un titre, un lieu ou un mot-cle...">
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
                <option value="<?php echo $escape($sortKey); ?>" <?php echo ($filters['sort'] ?? 'priority') === $sortKey ? 'selected' : ''; ?>>
                    Tri : <?php echo $escape($sortLabel); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div class="icon-actions">
            <button type="submit" class="solid-btn">Filtrer</button>
            <a class="outline-btn" href="index.php?page=events">R&eacute;initialiser</a>
        </div>
    </form>
</section>

<?php if ($filterChips): ?>
    <section class="section reveal event-filter-summary">
        <div class="section-head">
            <div>
                <span class="section-badge">Filtres actifs</span>
                <h2>Vue personnalisee de vos evenements</h2>
            </div>
            <span><?php echo $escape($activeFilterCount); ?> filtre(s)</span>
        </div>
        <div class="event-chip-summary">
            <?php foreach ($filterChips as $chip): ?>
                <span class="event-filter-chip">
                    <strong><?php echo $escape($chip['label']); ?> :</strong>
                    <?php echo $escape($chip['value']); ?>
                </span>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<section class="section reveal" id="timeline-board">
    <div class="section-head">
        <div>
            <span class="section-badge">Timeline</span>
            <h2>Cette semaine, ce mois-ci et la suite</h2>
        </div>
    </div>

    <div class="grid-4 event-type-grid event-timeline-summary">
        <article class="card event-type-card">
            <strong><?php echo $escape($timelineSummary['this_week'] ?? 0); ?></strong>
            <span>Cette semaine</span>
        </article>
        <article class="card event-type-card">
            <strong><?php echo $escape($timelineSummary['this_month'] ?? 0); ?></strong>
            <span>Ce mois-ci</span>
        </article>
        <article class="card event-type-card">
            <strong><?php echo $escape($timelineSummary['upcoming'] ?? 0); ?></strong>
            <span>&Agrave; venir</span>
        </article>
        <article class="card event-type-card">
            <strong><?php echo $escape($timelineSummary['en_cours'] ?? 0); ?></strong>
            <span>En cours</span>
        </article>
    </div>

    <?php if ($timelineGroups): ?>
        <div class="event-timeline-groups">
            <?php foreach ($timelineGroups as $group): ?>
                <article class="panel event-timeline-group">
                    <div class="section-head event-mini-head">
                        <div>
                            <span class="section-badge"><?php echo $escape($group['label']); ?></span>
                            <h4><?php echo $escape(count($group['events'] ?? [])); ?> evenement(s)</h4>
                        </div>
                    </div>
                    <div class="event-timeline-list">
                        <?php foreach (($group['events'] ?? []) as $timelineEvent): ?>
                            <a class="event-timeline-item" href="<?php echo $escape($buildFrontUrl(['event_id' => $timelineEvent['id_evenement']], '#event-focus')); ?>">
                                <div>
                                    <strong><?php echo $escape($timelineEvent['titre']); ?></strong>
                                    <span><?php echo $escape($timelineEvent['start_display']); ?> - <?php echo $escape($timelineEvent['lieu']); ?></span>
                                </div>
                                <span><?php echo $escape($timelineEvent['countdown_text']); ?></span>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <article class="panel event-empty-state">
            <span class="section-badge">Timeline vide</span>
            <h3>Aucun evenement a afficher dans la timeline actuelle.</h3>
            <p>Essayez d'elargir les filtres ou ajoutez de nouveaux evenements depuis le back office.</p>
        </article>
    <?php endif; ?>
</section>

<section class="section reveal" id="events-catalog">
    <div class="section-head">
        <div>
            <span class="section-badge">Catalogue</span>
            <h2>S&eacute;lection d'&eacute;v&eacute;nements disponible &agrave; la r&eacute;servation</h2>
        </div>
        <div class="meta-row">
            <span><?php echo $escape($pagination['total_items'] ?? count($events)); ?> resultat(s)</span>
            <span><?php echo $escape($stats['open_events_in_view'] ?? 0); ?> inscription(s) ouverte(s)</span>
            <span><?php echo $escape($stats['full_events_in_view'] ?? 0); ?> complet(s)</span>
        </div>
    </div>

    <?php if ($events): ?>
        <div class="grid-3 event-card-grid">
            <?php foreach ($events as $event): ?>
                <?php $isSelected = (int) $event['id_evenement'] === $selectedEventId; ?>
                <article class="card event-crud-card<?php echo $isSelected ? ' event-crud-card-active' : ''; ?>">
                    <div class="event-card-banner<?php echo !empty($event['image_path']) ? ' has-image' : ''; ?>"<?php echo !empty($event['image_path']) ? ' style="background-image:url(\'' . $escape($event['image_path']) . '\');"' : ''; ?>>
                        <div class="event-card-banner-top">
                            <span class="event-chip"><?php echo $escape($event['type_label']); ?></span>
                            <span class="event-card-date"><?php echo $escape($event['date_chip']); ?></span>
                        </div>
                        <div class="event-badge-row event-badge-row-card">
                            <span class="status-badge <?php echo $escape($event['status_class']); ?>"><?php echo $escape($event['status_label']); ?></span>
                            <?php foreach (array_slice($event['smart_badges'], 0, 2) as $badge): ?>
                                <span class="status-badge <?php echo $escape($badge['class']); ?>"><?php echo $escape($badge['label']); ?></span>
                            <?php endforeach; ?>
                        </div>
                        <div class="event-card-banner-copy">
                            <strong><?php echo $escape($event['titre']); ?></strong>
                            <span><?php echo $escape($event['countdown_text']); ?></span>
                        </div>
                    </div>

                    <div class="event-card-content">
                        <p><?php echo $escape($event['description']); ?></p>

                        <div class="event-facts">
                            <div>
                                <strong>Debut</strong>
                                <span><?php echo $escape($event['start_display']); ?></span>
                            </div>
                            <div>
                                <strong>Lieu</strong>
                                <span><?php echo $escape($event['lieu']); ?></span>
                            </div>
                        </div>

                        <div class="event-card-callout">
                            <strong><?php echo $escape($event['demand_text']); ?></strong>
                            <span><?php echo $escape($event['availability_text']); ?></span>
                        </div>

                        <div class="event-progress-block">
                            <div class="meta-row">
                                <span><?php echo $escape($event['participants_count']); ?> / <?php echo $escape($event['nb_places']); ?> places occupees</span>
                                <span><?php echo $escape($event['fill_rate']); ?>%</span>
                            </div>
                            <div class="event-progress-bar">
                                <span style="width: <?php echo $escape($event['fill_rate']); ?>%;"></span>
                            </div>
                        </div>

                        <div class="icon-actions">
                            <a class="solid-btn" href="<?php echo $escape($buildFrontUrl(['event_id' => $event['id_evenement']], '#event-focus')); ?>">Voir details</a>
                            <a class="outline-btn" href="<?php echo $escape($buildFrontUrl(['event_id' => $event['id_evenement']], '#participation-form')); ?>">Participer</a>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?php $renderPagination($pagination, 'event_page', $buildFrontUrl, '#events-catalog'); ?>
    <?php else: ?>
        <article class="panel event-empty-state">
            <span class="section-badge">Aucun resultat</span>
            <h3>Aucun evenement ne correspond a votre recherche.</h3>
            <p>Essayez un autre mot-cle, un autre type, ou revenez sur la vue prioritaire pour retrouver les evenements ouverts.</p>
        </article>
    <?php endif; ?>
</section>

<?php if ($selectedEvent): ?>
    <section class="events-focus-grid reveal" id="event-focus">
        <article class="panel event-focus-card">
            <div class="event-focus-surface<?php echo !empty($selectedEvent['image_path']) ? ' has-image' : ''; ?>"<?php echo !empty($selectedEvent['image_path']) ? ' style="background-image:url(\'' . $escape($selectedEvent['image_path']) . '\');"' : ''; ?>>
                <div class="event-badge-row">
                    <span class="status-badge <?php echo $escape($selectedEvent['status_class']); ?>"><?php echo $escape($selectedEvent['status_label']); ?></span>
                    <span class="event-chip"><?php echo $escape($selectedEvent['type_label']); ?></span>
                    <?php foreach (array_slice($selectedEvent['smart_badges'], 0, 3) as $badge): ?>
                        <span class="status-badge <?php echo $escape($badge['class']); ?>"><?php echo $escape($badge['label']); ?></span>
                    <?php endforeach; ?>
                </div>
                <div class="event-surface-copy">
                    <span class="event-surface-type"><?php echo $escape($selectedEvent['countdown_text']); ?></span>
                    <strong><?php echo $escape($selectedEvent['titre']); ?></strong>
                    <span><?php echo $escape($selectedEvent['lieu']); ?></span>
                </div>
            </div>

            <div class="event-focus-body">
                <p><?php echo $escape($selectedEvent['description']); ?></p>

                <div class="event-detail-grid">
                    <div class="event-detail-card">
                        <strong>Date de debut</strong>
                        <span><?php echo $escape($selectedEvent['start_display']); ?></span>
                    </div>
                    <div class="event-detail-card">
                        <strong>Date de fin</strong>
                        <span><?php echo $escape($selectedEvent['end_display']); ?></span>
                    </div>
                    <div class="event-detail-card">
                        <strong>Lieu</strong>
                        <span><?php echo $escape($selectedEvent['lieu']); ?></span>
                    </div>
                    <div class="event-detail-card">
                        <strong>Capacite</strong>
                        <span><?php echo $escape($selectedEvent['nb_places']); ?> places</span>
                    </div>
                </div>

                <div class="event-progress-block">
                    <div class="meta-row">
                        <span><?php echo $escape($selectedEvent['participants_count']); ?> inscription(s) actives</span>
                        <span><?php echo $escape($selectedEvent['remaining_places']); ?> place(s) disponibles</span>
                    </div>
                    <div class="event-progress-bar event-progress-bar-large">
                        <span style="width: <?php echo $escape($selectedEvent['fill_rate']); ?>%;"></span>
                    </div>
                </div>

                <div class="feature-list">
                    <div class="feature-item"><?php echo $escape($selectedEvent['registration_title']); ?> : <?php echo $escape($selectedEvent['registration_message']); ?></div>
                    <div class="feature-item">Urgence : <?php echo $escape($selectedEvent['countdown_text']); ?></div>
                    <div class="feature-item">Dynamique : <?php echo $escape($selectedEvent['demand_text']); ?></div>
                </div>

                <?php if ($selectedEventCalendarUrl !== '' || $selectedEventMapUrl !== ''): ?>
                    <div class="icon-actions event-map-actions">
                        <?php if ($selectedEventCalendarUrl !== ''): ?>
                            <a class="outline-btn" data-no-loader="true" href="<?php echo $escape($selectedEventCalendarUrl); ?>">T&eacute;l&eacute;charger le calendrier (.ics)</a>
                        <?php endif; ?>
                        <?php if ($selectedEventMapUrl !== ''): ?>
                            <a class="outline-btn" data-no-loader="true" target="_blank" rel="noopener noreferrer" href="<?php echo $escape($selectedEventMapUrl); ?>">Ouvrir dans Google Maps</a>
                        <?php endif; ?>
                    </div>
                    <?php if ($selectedEventCalendarUrl !== ''): ?>
                        <small class="event-export-note">Sous Windows, ce fichier peut s'ouvrir dans Outlook. Basculez ensuite vers l'onglet Calendrier pour finaliser l'ajout.</small>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </article>

        <?php if ($selectedEventMapEmbedUrl !== ''): ?>
            <article class="event-map-panel">
                <div class="section-head event-mini-head">
                    <div>
                        <span class="section-badge">Localisation</span>
                        <h4>Voir l'emplacement sans quitter la page</h4>
                    </div>
                </div>
                <iframe src="<?php echo $escape($selectedEventMapEmbedUrl); ?>" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen title="Carte de l'evenement <?php echo $escape($selectedEvent['titre']); ?>"></iframe>
            </article>
        <?php endif; ?>

        <article class="panel event-registration-card" id="participation-form">
            <span class="section-badge">Participation</span>
            <h3>Inscrivez-vous a cet evenement</h3>
            <p>
                Votre inscription sera enregistree avec le statut
                <strong>en attente</strong> puis traitee par l'administration.
            </p>

            <?php if ($registrationReceipt): ?>
                <article class="event-receipt-card">
                    <strong>Inscription enregistree</strong>
                    <span><?php echo $escape($registrationReceipt['titre'] ?? ''); ?></span>
                    <small><?php echo $escape($registrationReceipt['start_display'] ?? ''); ?> - <?php echo $escape($registrationReceipt['lieu'] ?? ''); ?></small>
                    <small>Statut initial : <?php echo $escape($registrationReceipt['status_label'] ?? 'En attente'); ?></small>
                    <?php if (!empty($registrationReceipt['email_message'])): ?>
                        <small><?php echo $escape($registrationReceipt['email_message']); ?></small>
                    <?php endif; ?>
                    <?php if ($registrationQr !== ''): ?>
                        <div class="event-receipt-qr">
                            <img src="<?php echo $escape($registrationQr); ?>" alt="QR code de participation">
                        </div>
                        <?php if ($registrationQrReference !== ''): ?>
                            <small class="event-qr-reference">R&eacute;f&eacute;rence QR : <?php echo $escape($registrationQrReference); ?></small>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if ($selectedEventCalendarUrl !== '' || $selectedEventMapUrl !== ''): ?>
                        <div class="icon-actions">
                            <?php if ($registrationPassUrl !== ''): ?>
                                <a class="outline-btn" data-no-loader="true" href="<?php echo $escape($registrationPassUrl); ?>">Ouvrir le pass</a>
                            <?php endif; ?>
                            <?php if ($selectedEventCalendarUrl !== ''): ?>
                                <a class="outline-btn" data-no-loader="true" href="<?php echo $escape($selectedEventCalendarUrl); ?>">T&eacute;l&eacute;charger le rappel calendrier (.ics)</a>
                            <?php endif; ?>
                        </div>
                        <?php if ($selectedEventCalendarUrl !== ''): ?>
                            <small class="event-export-note">Le rappel calendrier peut s'ouvrir dans Outlook selon votre configuration Windows.</small>
                        <?php endif; ?>
                    <?php endif; ?>
                </article>
            <?php endif; ?>

            <article class="event-registration-state">
                <strong><?php echo $escape($selectedEvent['registration_title']); ?></strong>
                <p><?php echo $escape($selectedEvent['registration_message']); ?></p>
            </article>

            <?php if ($selectedEvent['is_registration_open']): ?>
                <form class="event-form-grid" method="post" action="<?php echo $escape($buildFrontUrl(['event_id' => $selectedEvent['id_evenement']], '#participation-form')); ?>">
                    <input type="hidden" name="event_action" value="create_participation">
                    <input type="hidden" name="csrf_token" value="<?php echo $escape($csrfToken); ?>">
                    <input type="hidden" name="id_evenement" value="<?php echo $escape($selectedEvent['id_evenement']); ?>">
                    <input type="hidden" name="return_to" value="<?php echo $escape($buildFrontUrl(['event_id' => $selectedEvent['id_evenement']])); ?>">

                    <div class="field-block<?php echo $fieldError('nom_participant') ? ' field-block-error' : ''; ?>">
                        <label for="nom_participant">Nom complet</label>
                        <input id="nom_participant" type="text" name="nom_participant" value="<?php echo $escape($oldValue('nom_participant')); ?>">
                        <?php if ($fieldError('nom_participant')): ?><small class="event-field-error"><?php echo $escape($fieldError('nom_participant')); ?></small><?php endif; ?>
                    </div>

                    <div class="field-block<?php echo $fieldError('email_participant') ? ' field-block-error' : ''; ?>">
                        <label for="email_participant">Email</label>
                        <input id="email_participant" type="email" name="email_participant" value="<?php echo $escape($oldValue('email_participant')); ?>">
                        <?php if ($fieldError('email_participant')): ?><small class="event-field-error"><?php echo $escape($fieldError('email_participant')); ?></small><?php endif; ?>
                    </div>

                    <div class="field-block<?php echo $fieldError('telephone') ? ' field-block-error' : ''; ?>">
                        <label for="telephone">Telephone</label>
                        <input id="telephone" type="text" name="telephone" value="<?php echo $escape($oldValue('telephone')); ?>">
                        <?php if ($fieldError('telephone')): ?><small class="event-field-error"><?php echo $escape($fieldError('telephone')); ?></small><?php endif; ?>
                    </div>

                    <div class="field-block<?php echo $fieldError('id_evenement') ? ' field-block-error' : ''; ?>">
                        <label for="preview_status">Statut initial</label>
                        <input id="preview_status" type="text" value="En attente" readonly>
                        <?php if ($fieldError('id_evenement')): ?><small class="event-field-error"><?php echo $escape($fieldError('id_evenement')); ?></small><?php endif; ?>
                    </div>

                    <div class="icon-actions event-form-actions field-span-2">
                        <button type="submit" class="solid-btn">Envoyer ma demande</button>
                        <a class="outline-btn" href="#events-catalog">Choisir un autre &eacute;v&eacute;nement</a>
                    </div>
                </form>
            <?php else: ?>
                <div class="event-closed-box">
                    <strong><?php echo $escape($selectedEvent['registration_title']); ?></strong>
                    <p><?php echo $escape($selectedEvent['registration_message']); ?></p>
                </div>
            <?php endif; ?>

            <div class="event-private-summary">
                <div class="section-head event-mini-head">
                    <div>
                        <span class="section-badge">Confidentialite</span>
                        <h4>Suivi anonyme des inscriptions</h4>
                    </div>
                    <span>Sans details personnels</span>
                </div>

                <article class="event-privacy-note">
                    <strong>Les participants restent prives.</strong>
                    <p>Le client suit l'&eacute;volution de l'&eacute;v&eacute;nement et les places restantes, mais ne voit jamais les noms, emails ou t&eacute;l&eacute;phones des autres inscrits.</p>
                </article>

                <div class="event-analytics-grid">
                    <?php $renderSummaryBar($selectedCapacityChart, 'Capacite', 'Occupation des places'); ?>
                    <?php $renderSummaryBar($selectedEventSummaryChart, 'Demandes', 'Etat des inscriptions'); ?>
                </div>

                <div class="event-summary-strip">
                    <?php foreach ($selectedEventHighlights as $highlight): ?>
                        <article class="event-summary-pill">
                            <strong><?php echo $escape($highlight['value'] ?? 0); ?></strong>
                            <span><?php echo $escape($highlight['label'] ?? ''); ?></span>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </article>
    </section>
<?php else: ?>
    <section class="section reveal">
        <article class="panel event-empty-state">
            <span class="section-badge">Aucune fiche s&eacute;lectionn&eacute;e</span>
            <h3>Choisissez un evenement dans le catalogue pour afficher ses details.</h3>
            <p>La fiche detaillee, la timeline et le formulaire de participation se mettront a jour automatiquement.</p>
        </article>
    </section>
<?php endif; ?>

<section class="section reveal">
    <div class="section-head">
        <div>
            <span class="section-badge">Vue rapide</span>
            <h2>R&eacute;partition par type d'&eacute;v&eacute;nement</h2>
        </div>
    </div>

    <div class="grid-4 event-type-grid">
        <?php foreach ($typeHighlights as $highlight): ?>
            <article class="card event-type-card">
                <strong><?php echo $escape($highlight['count'] ?? 0); ?></strong>
                <span><?php echo $escape($highlight['label'] ?? ''); ?></span>
            </article>
        <?php endforeach; ?>
    </div>
</section>







