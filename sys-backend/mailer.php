<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Szyfruje hasło SMTP za pomocą AES-256-CBC i klucza SMTP_ENCRYPTION_KEY.
 */
function encryptSmtpPassword(string $plainPassword): string
{
    if ($plainPassword === '' || !defined('SMTP_ENCRYPTION_KEY')) {
        return '';
    }
    $key = hash('sha256', SMTP_ENCRYPTION_KEY, true);
    $ivLength = openssl_cipher_iv_length('AES-256-CBC');
    $iv = openssl_random_pseudo_bytes($ivLength);
    $encrypted = openssl_encrypt($plainPassword, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

    return base64_encode($iv . $encrypted);
}

/**
 * Odszyfrowuje hasło SMTP zapisane w bazie danych.
 */
function decryptSmtpPassword(string $encryptedBase64): string
{
    if ($encryptedBase64 === '' || !defined('SMTP_ENCRYPTION_KEY')) {
        return '';
    }
    $data = base64_decode($encryptedBase64, true);
    if ($data === false) {
        return '';
    }
    $key = hash('sha256', SMTP_ENCRYPTION_KEY, true);
    $ivLength = openssl_cipher_iv_length('AES-256-CBC');
    if (strlen($data) <= $ivLength) {
        return '';
    }
    $iv = substr($data, 0, $ivLength);
    $encrypted = substr($data, $ivLength);
    $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

    return $decrypted !== false ? $decrypted : '';
}

/**
 * Wysyła wiadomość e-mail korzystając z konfiguracji w tabeli smtp_settings.
 */
function sendEmailNotification(
    mysqli $conn,
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody,
    string $altBody = ''
): bool {
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $result = @$conn->query("SELECT * FROM smtp_settings WHERE smtp_enabled = 1 ORDER BY id ASC LIMIT 1");
    if (!$result || $result->num_rows === 0) {
        return false;
    }

    $smtp = $result->fetch_assoc();
    $result->free();

    if (empty($smtp['smtp_host']) || empty($smtp['from_email'])) {
        return false;
    }

    $password = decryptSmtpPassword((string)($smtp['smtp_password'] ?? ''));

    $mail = new PHPMailer(true);

    try {
        $mail->CharSet = PHPMailer::CHARSET_UTF8;
        $mail->isSMTP();
        $mail->Host = $smtp['smtp_host'];
        $mail->Port = (int)($smtp['smtp_port'] ?: 587);
        $mail->SMTPAuth = !empty($smtp['smtp_auth']);
        $mail->Username = (string)($smtp['smtp_username'] ?? '');
        $mail->Password = $password;

        $encryption = strtolower(trim((string)($smtp['smtp_encryption'] ?? 'tls')));
        if ($encryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($encryption === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } else {
            $mail->SMTPSecure = '';
            $mail->SMTPAutoTLS = false;
        }

        $fromName = !empty($smtp['from_name']) ? $smtp['from_name'] : 'Kalendarz Mechanika';
        $mail->setFrom($smtp['from_email'], $fromName);

        if (!empty($smtp['reply_to_email']) && filter_var($smtp['reply_to_email'], FILTER_VALIDATE_EMAIL)) {
            $mail->addReplyTo($smtp['reply_to_email'], $fromName);
        }

        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $altBody !== '' ? $altBody : strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</tr>'], "\n", $htmlBody));

        return $mail->send();
    } catch (Exception $e) {
        error_log('SMTP Mailer Error (' . $toEmail . '): ' . $mail->ErrorInfo);
        return false;
    }
}
