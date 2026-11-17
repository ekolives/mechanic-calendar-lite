<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/phpmailer/Exception.php';
require_once __DIR__ . '/phpmailer/PHPMailer.php';
require_once __DIR__ . '/phpmailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Szyfruje hasło SMTP algorytmem AES-256-GCM (szyfrowanie z uwierzytelnieniem) kluczem SMTP_ENCRYPTION_KEY.
 * Format: "v2:" + base64(iv[12] . tag[16] . szyfrogram).
 */
function encryptSmtpPassword(string $plainPassword): string
{
    if ($plainPassword === '' || !defined('SMTP_ENCRYPTION_KEY')) {
        return '';
    }
    $key = hash('sha256', SMTP_ENCRYPTION_KEY, true);
    $iv = random_bytes(12);
    $tag = '';
    $encrypted = openssl_encrypt($plainPassword, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
    if ($encrypted === false) {
        return '';
    }

    return 'v2:' . base64_encode($iv . $tag . $encrypted);
}

/**
 * Odszyfrowuje hasło SMTP zapisane w bazie danych.
 * Obsługuje nowy format (v2, GCM) oraz stary (AES-256-CBC bez prefiksu) - stare hasła nadal działają
 * i zostaną zapisane w nowym formacie przy najbliższej zmianie ustawień SMTP.
 */
function decryptSmtpPassword(string $encrypted): string
{
    if ($encrypted === '' || !defined('SMTP_ENCRYPTION_KEY')) {
        return '';
    }
    $key = hash('sha256', SMTP_ENCRYPTION_KEY, true);

    if (strncmp($encrypted, 'v2:', 3) === 0) {
        $data = base64_decode(substr($encrypted, 3), true);
        if ($data === false || strlen($data) <= 28) {
            return '';
        }
        $iv = substr($data, 0, 12);
        $tag = substr($data, 12, 16);
        $cipher = substr($data, 28);
        $plain = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);

        return $plain !== false ? $plain : '';
    }

    // format dawny: AES-256-CBC
    $data = base64_decode($encrypted, true);
    if ($data === false) {
        return '';
    }
    $ivLength = openssl_cipher_iv_length('AES-256-CBC');
    if (strlen($data) <= $ivLength) {
        return '';
    }
    $iv = substr($data, 0, $ivLength);
    $cipher = substr($data, $ivLength);
    $plain = openssl_decrypt($cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

    return $plain !== false ? $plain : '';
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
