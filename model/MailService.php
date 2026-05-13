<?php

use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

$mailAutoload = dirname(__DIR__) . '/vendor/autoload.php';
if (file_exists($mailAutoload)) {
    require_once $mailAutoload;
}
require_once dirname(__DIR__) . '/config.php';

class MailService
{
    public static function sendAdminNotification(array $reclamation): bool
    {
        if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
            return false;
        }

        if (!method_exists('config', 'hasMailConfiguration') || !config::hasMailConfiguration()) {
            return false;
        }

        $recipient = (string) config::env('MAIL_ADMIN_TO', config::env('MAIL_FROM', ''));
        if ($recipient === '') {
            return false;
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
            $mail->SMTPSecure = $mail->Port === 465
                ? PHPMailer::ENCRYPTION_SMTPS
                : PHPMailer::ENCRYPTION_STARTTLS;

            $mail->setFrom(
                (string) config::env('MAIL_FROM'),
                (string) config::env('MAIL_FROM_NAME', config::appName())
            );
            $mail->addAddress($recipient, (string) config::env('MAIL_ADMIN_NAME', 'Administration GoService'));

            $subject = trim((string) ($reclamation['subject'] ?? 'Réclamation'));
            $description = trim((string) ($reclamation['description'] ?? ''));
            $userId = (int) ($reclamation['id_user'] ?? 0);

            $mail->isHTML(true);
            $mail->Subject = 'Nouvelle réclamation - ' . ($subject !== '' ? $subject : 'Sans objet');
            $mail->Body = '
                <div style="font-family:Arial,sans-serif;color:#142738;line-height:1.6">
                    <h2 style="color:#EE5828;margin-bottom:8px">Nouvelle réclamation reçue</h2>
                    <p><strong>Utilisateur :</strong> #' . $userId . '</p>
                    <p><strong>Sujet :</strong> ' . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') . '</p>
                    <p><strong>Message :</strong><br>' . nl2br(htmlspecialchars($description, ENT_QUOTES, 'UTF-8')) . '</p>
                    <hr>
                    <p>Consultez le back-office GoService pour traiter cette demande.</p>
                </div>';
            $mail->AltBody = "Nouvelle réclamation\nUtilisateur #{$userId}\nSujet: {$subject}\n\n{$description}";

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('MailService admin notification failed: ' . $mail->ErrorInfo);
            return false;
        }
    }
}
