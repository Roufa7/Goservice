<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once dirname(__DIR__) . '/vendor/autoload.php';

class MailService
{
    public static function sendAdminNotification($reclamation)
    {
        $mail = new PHPMailer(true);

        try {
            // Configuration Serveur (À personnaliser par l'utilisateur)
            $mail->isSMTP();
            $mail->Host = 'smtp-relay.brevo.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'YOUR_BREVO_EMAIL';
            $mail->Password = 'YOUR_BREVO_SMTP_KEY';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Destinataires
            $mail->setFrom('go.serviicee@gmail.com', 'GoService System');
            $mail->addAddress('baha.zouari@esprit.tn', 'Admin Baha');

            // Contenu
            $mail->isHTML(true);
            $mail->Subject = 'Nouvelle Reclamation: ' . $reclamation['subject'];

            $bodyContent = "
                <h2>Nouvelle Réclamation Reçue</h2>
                <p><strong>Client:</strong> Client #{$reclamation['id_user']}</p>
                <p><strong>Sujet:</strong> {$reclamation['subject']}</p>
                <p><strong>Message:</strong><br>{$reclamation['description']}</p>
                <hr>
                <p>Connectez-vous au back-office pour répondre à cette demande.</p>
            ";

            $mail->Body = $bodyContent;
            $mail->AltBody = strip_tags($bodyContent);

            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }
}
