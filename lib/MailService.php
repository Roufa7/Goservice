<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';
require_once __DIR__ . '/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

class MailService {
    public static function sendPasswordResetEmail($toEmail, $resetLink) {
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = config::MAIL_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = config::MAIL_USERNAME;
            $mail->Password   = config::MAIL_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = config::MAIL_PORT;
            $mail->CharSet    = 'UTF-8';

            // Recipients
            $mail->setFrom(config::MAIL_FROM, config::MAIL_FROM_NAME);
            $mail->addAddress($toEmail);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Réinitialisation de votre mot de passe - GoService';
            
            $body = "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee;'>
                    <h2 style='color: #2c3e50;'>Bonjour,</h2>
                    <p>Nous avons reçu une demande de réinitialisation de mot de passe pour votre compte GoService.</p>
                    <p>Cliquez sur le bouton ci-dessous pour définir un nouveau mot de passe. Ce lien est valide pendant 1 heure.</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='$resetLink' style='background-color: #3498db; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Réinitialiser mon mot de passe</a>
                    </div>
                    <p>Si vous n'avez pas demandé cette réinitialisation, vous pouvez ignorer cet email.</p>
                    <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                    <p style='font-size: 12px; color: #7f8c8d;'>Ceci est un message automatique, merci de ne pas y répondre.</p>
                </div>
            ";

            $mail->Body = $body;
            $mail->AltBody = "Bonjour, cliquez sur le lien suivant pour réinitialiser votre mot de passe : $resetLink";

            return $mail->send();
        } catch (Exception $e) {
            error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
            return false;
        }
    }
}
