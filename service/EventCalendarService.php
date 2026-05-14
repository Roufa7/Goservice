<?php

use Eluceo\iCal\Component\Calendar;
use Eluceo\iCal\Component\Event;

class EventCalendarService
{
    private DateTimeZone $timezone;

    public function __construct(?DateTimeZone $timezone = null)
    {
        $this->timezone = $timezone ?? new DateTimeZone('Africa/Tunis');
    }

    public function buildCalendarContent(array $event): string
    {
        $calendar = new Calendar('-//GoService//Evenements//FR');
        $calendar
            ->setName(config::appName())
            ->setDescription('Evenement GoService')
            ->setMethod('PUBLISH')
            ->setTimezone($this->timezone->getName());

        $calendarEvent = new Event($this->buildUniqueId($event));
        $calendarEvent
            ->setDtStamp(new DateTime('now', new DateTimeZone('UTC')))
            ->setDtStart($this->eventDate($event['date_debut'] ?? 'now'))
            ->setDtEnd($this->eventDate($event['date_fin'] ?? 'now'))
            ->setSummary((string) ($event['titre'] ?? 'Evenement GoService'))
            ->setDescription((string) ($event['description'] ?? ''))
            ->setLocation((string) ($event['lieu'] ?? ''))
            ->setStatus($this->calendarStatus((string) ($event['statut'] ?? 'prevu')))
            ->setUseTimezone(true)
            ->setTimezoneString($this->timezone->getName());

        $calendar->addEvent($calendarEvent);

        return $calendar->render();
    }

    public function fileName(array $event): string
    {
        $slug = preg_replace('/[^a-z0-9]+/i', '-', strtolower((string) ($event['titre'] ?? 'evenement')));
        $slug = trim((string) $slug, '-');

        return ($slug !== '' ? $slug : 'evenement') . '.ics';
    }


    public function streamDownload(array $event): void
    {
        $content = $this->buildCalendarContent($event);
        header('Content-Type: text/calendar; charset=UTF-8; method=PUBLISH');
        header('Content-Disposition: attachment; filename="' . $this->fileName($event) . '"');
        header('Content-Transfer-Encoding: 8bit');
        header('X-Content-Type-Options: nosniff');
        echo $content;
        exit;
    }

    private function eventDate(string $value): DateTime
    {
        return new DateTime($value, $this->timezone);
    }

    private function buildUniqueId(array $event): string
    {
        $eventId = (int) ($event['id_evenement'] ?? 0);
        return 'event-' . $eventId . '@goservice.local';
    }

    private function calendarStatus(string $status): string
    {
        return match ($status) {
            'annule' => Event::STATUS_CANCELLED,
            'termine' => Event::STATUS_CONFIRMED,
            default => Event::STATUS_TENTATIVE,
        };
    }
}



