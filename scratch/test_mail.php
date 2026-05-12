<?php
require_once 'model/MailService.php';

echo "Tentative d'envoi de mail de test...\n";

$testData = [
    'id_user' => 999,
    'subject' => 'TEST DEBUG MAILING',
    'description' => 'Ceci est un test de debug pour vérifier la connexion Brevo.'
];

$result = MailService::sendAdminNotification($testData);

if ($result) {
    echo "SUCCÈS : Le mail a été envoyé au serveur SMTP.\n";
} else {
    echo "ÉCHEC : Le mail n'a pas pu être envoyé. Vérifiez les logs ou les identifiants.\n";
}
