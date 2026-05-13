<?php
/**
 * MailService.php — GoService v3
 * Envoi d'emails réels via PHPMailer + Gmail SMTP
 * Chemin : GoService_v3/service/MailService.php
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

class MailService
{
    private const SMTP_HOST  = 'smtp.gmail.com';
    private const SMTP_PORT  = 587;
    private const SMTP_USER  = 'aminabouafif117@gmail.com';
    private const SMTP_PASS  = 'ewsk wghm tlus jqiv';
    private const FROM_NAME  = 'GoService';
    private const FROM_EMAIL = 'aminabouafif117@gmail.com';

    // ── Créer l'instance PHPMailer configurée ────────────────
    private static function mailer(): PHPMailer
{
    $mail = new PHPMailer(true);   // true = active les exceptions
    $mail->isSMTP();               // utiliser le protocole SMTP
    $mail->Host       = self::SMTP_HOST;
    $mail->SMTPAuth   = true;      // obliger l'authentification
    $mail->Username   = self::SMTP_USER;
    $mail->Password   = self::SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = self::SMTP_PORT;
    $mail->CharSet    = 'UTF-8';
    $mail->setFrom(self::FROM_EMAIL, self::FROM_NAME);
    return $mail;
}

    // ════════════════════════════════════════════════════════
    //  EMAIL — Confirmation réservation → CLIENT
    //  Déclenché dans reserver.php après addReservation()
    // ════════════════════════════════════════════════════════
    public static function sendConfirmationReservation(array $d): bool
    {
        try {
            $mail = self::mailer();
            $mail->addAddress($d['email_client'], trim($d['prenom_client'] . ' ' . $d['nom_client']));
            $mail->Subject = '✅ Confirmation réservation #' . $d['id_reservation'] . ' — GoService';
            $mail->isHTML(true);
            $mail->Body    = self::htmlReservation($d);
            $mail->AltBody = "Bonjour {$d['prenom_client']}, votre réservation #{$d['id_reservation']} pour \"{$d['titre_service']}\" a bien été reçue. Statut : En attente.";
            $mail->send();
            return true;
        } catch (Exception $e) {
            error_log('[GoService][Mail] Réservation : ' . $e->getMessage());
            return false;
        }
    }

    // ════════════════════════════════════════════════════════
    //  TEMPLATE HTML — Confirmation réservation
    // ════════════════════════════════════════════════════════
    private static function htmlReservation(array $d): string
    {
        $date   = !empty($d['date_souhaitee']) ? date('d/m/Y', strtotime($d['date_souhaitee'])) : '—';
        $prenom = htmlspecialchars($d['prenom_client']);
        $nom    = htmlspecialchars($d['nom_client']);
        $titre  = htmlspecialchars($d['titre_service']);
        $cat    = htmlspecialchars($d['categorie'] ?? 'Service');
        $prix   = htmlspecialchars($d['prix']);
        $ref    = (int)$d['id_reservation'];

        return "<!DOCTYPE html><html lang='fr'><head><meta charset='UTF-8'></head>
<body style='margin:0;padding:0;background:#f0f4f8;font-family:Arial,sans-serif;'>
<table width='100%' cellpadding='0' cellspacing='0' style='background:#f0f4f8;padding:30px 0;'>
<tr><td align='center'>
<table width='600' cellpadding='0' cellspacing='0' style='background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);'>

  <tr><td style='background:linear-gradient(135deg,#0e2941,#1a3a57);padding:32px 40px;text-align:center;'>
    <div style='font-size:28px;font-weight:900;color:#fff;'>Go<span style='color:#ee5828;'>Service</span></div>
    <div style='color:rgba(255,255,255,.6);font-size:13px;margin-top:6px;'>Plateforme de services professionnels</div>
  </td></tr>

  <tr><td style='padding:36px 40px 0;text-align:center;'>
    <div style='font-size:52px;'>✅</div>
    <h1 style='margin:12px 0 0;font-size:22px;font-weight:800;color:#0e2941;'>Réservation confirmée !</h1>
    <p style='color:#617084;font-size:14px;margin:8px 0 0;'>Bonjour <strong>{$prenom} {$nom}</strong>, votre demande a bien été enregistrée.</p>
  </td></tr>

  <tr><td style='padding:28px 40px;'>
    <table width='100%' cellpadding='0' cellspacing='0' style='background:#f8fafc;border-radius:12px;overflow:hidden;'>
      <tr><td colspan='2' style='padding:14px 20px;background:#0e2941;color:#fff;font-weight:800;font-size:13px;'>
        Réservation <span style='color:#ee5828;'>#{$ref}</span>
      </td></tr>
      <tr style='border-bottom:1px solid #e8edf2;'>
        <td style='padding:13px 20px;color:#617084;font-size:13px;'>🛠️ Service</td>
        <td style='padding:13px 20px;color:#0e2941;font-weight:700;'>{$titre}</td>
      </tr>
      <tr style='border-bottom:1px solid #e8edf2;background:#fff;'>
        <td style='padding:13px 20px;color:#617084;font-size:13px;'>📂 Catégorie</td>
        <td style='padding:13px 20px;color:#0e2941;'>{$cat}</td>
      </tr>
      <tr style='border-bottom:1px solid #e8edf2;'>
        <td style='padding:13px 20px;color:#617084;font-size:13px;'>💶 Prix</td>
        <td style='padding:13px 20px;color:#ee5828;font-weight:800;font-size:15px;'>{$prix} €</td>
      </tr>
      <tr style='border-bottom:1px solid #e8edf2;background:#fff;'>
        <td style='padding:13px 20px;color:#617084;font-size:13px;'>📅 Date souhaitée</td>
        <td style='padding:13px 20px;color:#0e2941;font-weight:700;'>{$date}</td>
      </tr>
      <tr>
        <td style='padding:13px 20px;color:#617084;font-size:13px;'>📊 Statut</td>
        <td style='padding:13px 20px;'>
          <span style='background:rgba(255,208,77,.15);color:#b8860b;padding:4px 12px;border-radius:99px;font-size:12px;font-weight:700;'>⏳ En attente</span>
        </td>
      </tr>
    </table>
  </td></tr>

  <tr><td style='padding:0 40px 28px;'>
    <div style='background:rgba(238,88,40,.06);border-left:4px solid #ee5828;border-radius:8px;padding:16px 20px;'>
      <p style='margin:0;color:#617084;font-size:13px;line-height:1.6;'>📞 Un prestataire vous contactera prochainement pour confirmer le rendez-vous.</p>
    </div>
  </td></tr>

  <tr><td style='background:#f8fafc;padding:20px 40px;text-align:center;border-top:1px solid #e8edf2;'>
    <p style='margin:0;color:#a0aec0;font-size:12px;'>© 2025 GoService — Tous droits réservés</p>
  </td></tr>

</table></td></tr></table>
</body></html>";
    }
}
?>
