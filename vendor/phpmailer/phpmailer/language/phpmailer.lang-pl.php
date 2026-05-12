<?php

/**
 * Polish PHPMailer language file: refer to English translation for definitive list
 * @package PHPMailer
 */

<<<<<<< HEAD
$PHPMAILER_LANG['authenticate']         = 'Błąd SMTP: Nie można przeprowadzić uwierzytelnienia.';
$PHPMAILER_LANG['buggy_php']            = 'Twoja wersja PHP zawiera błąd, który może powodować uszkodzenie wiadomości. Aby go naprawić, przełącz się na wysyłanie za pomocą SMTP, wyłącz opcję mail.add_x_header w php.ini, przełącz się na MacOS lub Linux lub zaktualizuj PHP do wersji 7.0.17+ lub 7.1.3+.';
$PHPMAILER_LANG['connect_host']         = 'Błąd SMTP: Nie można połączyć się z wybranym hostem.';
$PHPMAILER_LANG['data_not_accepted']    = 'Błąd SMTP: Dane nie zostały przyjęte.';
$PHPMAILER_LANG['empty_message']        = 'Wiadomość jest pusta.';
$PHPMAILER_LANG['encoding']             = 'Błędny sposób kodowania znaków: ';
$PHPMAILER_LANG['execute']              = 'Nie można uruchomić: ';
$PHPMAILER_LANG['extension_missing']    = 'Brakujące rozszerzenie: ';
$PHPMAILER_LANG['file_access']          = 'Brak dostępu do pliku: ';
$PHPMAILER_LANG['file_open']            = 'Nie można otworzyć pliku: ';
$PHPMAILER_LANG['from_failed']          = 'Następujący adres nadawcy jest nieprawidłowy lub nie istnieje: ';
$PHPMAILER_LANG['instantiate']          = 'Nie można wywołać funkcji mail(). Sprawdź konfigurację serwera.';
$PHPMAILER_LANG['invalid_address']      = 'Nie można wysłać wiadomości, ' . 'następujący adres odbiorcy jest nieprawidłowy lub nie istnieje: ';
$PHPMAILER_LANG['invalid_header']       = 'Nieprawidłowa nazwa lub wartość nagłówka';
$PHPMAILER_LANG['invalid_hostentry']    = 'Nieprawidłowy wpis hosta: ';
$PHPMAILER_LANG['invalid_host']         = 'Nieprawidłowy host: ';
$PHPMAILER_LANG['provide_address']      = 'Należy podać prawidłowy adres email odbiorcy.';
$PHPMAILER_LANG['mailer_not_supported'] = 'Wybrana metoda wysyłki wiadomości nie jest obsługiwana.';
$PHPMAILER_LANG['recipients_failed']    = 'Błąd SMTP: Następujący odbiorcy są nieprawidłowi lub nie istnieją: ';
$PHPMAILER_LANG['signing']              = 'Błąd podpisywania wiadomości: ';
$PHPMAILER_LANG['smtp_code']            = 'Kod SMTP: ';
$PHPMAILER_LANG['smtp_code_ex']         = 'Dodatkowe informacje SMTP: ';
$PHPMAILER_LANG['smtp_connect_failed']  = 'Wywołanie funkcji SMTP Connect() zostało zakończone niepowodzeniem.';
$PHPMAILER_LANG['smtp_detail']          = 'Szczegóły: ';
$PHPMAILER_LANG['smtp_error']           = 'Błąd SMTP: ';
=======
$PHPMAILER_LANG['authenticate']         = 'Błąd SMTP: nie udało się przeprowadzić uwierzytelnienia.';
$PHPMAILER_LANG['buggy_php']            = 'Używana wersja PHP zawiera błąd, który może powodować uszkodzenie wiadomości. Aby temu zapobiec, użyj wysyłki przez SMTP, wyłącz opcję mail.add_x_header w php.ini, przejdź na macOS lub Linux, lub zaktualizuj PHP do wersji 7.0.17+ albo 7.1.3+.';
$PHPMAILER_LANG['connect_host']         = 'Błąd SMTP: nie udało się połączyć z serwerem (hostem).';
$PHPMAILER_LANG['data_not_accepted']    = 'Błąd SMTP: dane wiadomości nie zostały przyjęte przez serwer.';
$PHPMAILER_LANG['empty_message']        = 'Nie można wysłać pustej wiadomości.';
$PHPMAILER_LANG['encoding']             = 'Nieobsługiwane kodowanie znaków: ';
$PHPMAILER_LANG['execute']              = 'Nie udało się uruchomić polecenia: ';
$PHPMAILER_LANG['extension_missing']    = 'Brak wymaganego rozszerzenia PHP: ';
$PHPMAILER_LANG['file_access']          = 'Brak dostępu do pliku: ';
$PHPMAILER_LANG['file_open']            = 'Nie udało się otworzyć pliku: ';
$PHPMAILER_LANG['from_failed']          = 'Nieprawidłowy adres nadawcy: ';
$PHPMAILER_LANG['instantiate']          = 'Nie można zainicjować funkcji mail(). Sprawdź konfigurację serwera.';
$PHPMAILER_LANG['invalid_address']      = 'Nie można wysłać wiadomości. Nieprawidłowy adres odbiorcy: ';
$PHPMAILER_LANG['invalid_header']       = 'Nieprawidłowa nazwa lub wartość nagłówka.';
$PHPMAILER_LANG['invalid_hostentry']    = 'Nieprawidłowy wpis hosta: ';
$PHPMAILER_LANG['invalid_host']         = 'Nieprawidłowa nazwa hosta: ';
$PHPMAILER_LANG['provide_address']      = 'Musisz podać co najmniej jeden prawidłowy adres e-mail odbiorcy.';
$PHPMAILER_LANG['mailer_not_supported'] = 'Wybrana metoda wysyłki wiadomości nie jest obsługiwana.';
$PHPMAILER_LANG['recipients_failed']    = 'Błąd SMTP: nie udało się wysłać do następujących odbiorców: ';
$PHPMAILER_LANG['signing']              = 'Błąd podpisywania wiadomości cyfrowo: ';
$PHPMAILER_LANG['smtp_code']            = 'Kod odpowiedzi SMTP: ';
$PHPMAILER_LANG['smtp_code_ex']         = 'Dodatkowe informacje serwera SMTP: ';
$PHPMAILER_LANG['smtp_connect_failed']  = 'Nie udało się nawiązać połączenia za pomocą SMTP Connect().';
$PHPMAILER_LANG['smtp_detail']          = 'Szczegóły błędu: ';
$PHPMAILER_LANG['smtp_error']           = 'Błąd serwera SMTP: ';
>>>>>>> origin/evenements
$PHPMAILER_LANG['variable_set']         = 'Nie można ustawić lub zmodyfikować zmiennej: ';
