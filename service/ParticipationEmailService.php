<?php

use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

class ParticipationEmailService
{
    public function __construct(private EventCalendarService $calendarService)
    {
    }

    public function canSend(): bool
    {
        return config::hasMailConfiguration();
    }

    public function sendConfirmation(array $participation, array $event): array
    {
        if (!$this->canSend()) {
            return [
                'sent' => false,
                'configured' => false,
                'message' => 'La confirmation email est prete, mais le SMTP n\'est pas encore configure sur cette machine.',
            ];
        }

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = (string) config::env('MAIL_HOST');
            $mail->SMTPAuth = true;
            $mail->Username = (string) config::env('MAIL_USERNAME');
            $mail->Password = (string) config::env('MAIL_PASSWORD');
            $mail->Port = (int) config::env('MAIL_PORT', '587');
            $mail->CharSet = 'UTF-8';

            if ($mail->Port === 465) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            }

            $mail->setFrom(
                (string) config::env('MAIL_FROM'),
                (string) config::env('MAIL_FROM_NAME', config::appName())
            );
            $mail->addAddress(
                (string) ($participation['email_participant'] ?? ''),
                (string) ($participation['nom_participant'] ?? '')
            );

            $mail->isHTML(true);
            $mail->Subject = 'Confirmation d\'inscription - ' . (string) ($event['titre'] ?? 'Evenement');
            $mail->Body = $this->htmlBody($participation, $event);
            $mail->AltBody = $this->plainBody($participation, $event);
            $mail->addStringAttachment(
                $this->calendarService->buildCalendarContent($event),
                $this->calendarService->fileName($event),
                PHPMailer::ENCODING_8BIT,
                'text/calendar; charset=UTF-8'
            );

            $mail->send();

            return [
                'sent' => true,
                'configured' => true,
                'message' => 'Un email de confirmation a ete envoye a ' . (string) ($participation['email_participant'] ?? '') . '.',
            ];
        } catch (PHPMailerException) {
            return [
                'sent' => false,
                'configured' => true,
                'message' => 'L\'inscription a ete enregistree, mais l\'email de confirmation n\'a pas pu etre envoye.',
            ];
        }
    }

    private function htmlBody(array $participation, array $event): string
    {
        $participant = htmlspecialchars((string) ($participation['nom_participant'] ?? ''), ENT_QUOTES, 'UTF-8');
        $title = htmlspecialchars((string) ($event['titre'] ?? ''), ENT_QUOTES, 'UTF-8');
        $location = htmlspecialchars((string) ($event['lieu'] ?? ''), ENT_QUOTES, 'UTF-8');
        $date = htmlspecialchars((string) ($event['start_display'] ?? $event['date_debut'] ?? ''), ENT_QUOTES, 'UTF-8');
        $status = htmlspecialchars((string) ($participation['status_label'] ?? 'En attente'), ENT_QUOTES, 'UTF-8');

        return '
            <div style="font-family:Arial,sans-serif;color:#142738;line-height:1.6">
                <h2 style="color:#EE5828;margin-bottom:8px">Confirmation d\'inscription</h2>
                <p>Bonjour <strong>' . $participant . '</strong>,</p>
                <p>Votre inscription a bien ete prise en compte pour l\'evenement <strong>' . $title . '</strong>.</p>
                <ul style="padding-left:18px">
                    <li><strong>Statut :</strong> ' . $status . '</li>
                    <li><strong>Date :</strong> ' . $date . '</li>
                    <li><strong>Lieu :</strong> ' . $location . '</li>
                </ul>
                <p>Un fichier calendrier est joint a cet email pour ajouter facilement l\'evenement a votre agenda.</p>
                <p style="margin-top:18px">Equipe GoService</p>
            </div>
        ';
    }

    private function plainBody(array $participation, array $event): string
    {
        return implode("\n", [
            'Confirmation d\'inscription',
            'Bonjour ' . (string) ($participation['nom_participant'] ?? '') . ',',
            'Votre inscription a bien ete prise en compte pour l\'evenement "' . (string) ($event['titre'] ?? '') . '".',
            'Statut : ' . (string) ($participation['status_label'] ?? 'En attente'),
            'Date : ' . (string) ($event['start_display'] ?? $event['date_debut'] ?? ''),
            'Lieu : ' . (string) ($event['lieu'] ?? ''),
            'Le fichier calendrier est joint a cet email.',
            'Equipe GoService',
        ]);
    }
}
