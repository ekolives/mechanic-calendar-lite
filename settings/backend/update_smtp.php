<?php
require_once __DIR__ . '/../../sys-backend/session_config.php';
require_once __DIR__ . '/../../sys-backend/db_connect.php';
require_once __DIR__ . '/../../sys-backend/mailer.php';
require_once __DIR__ . '/../../sys-backend/audit.php';

if ($user_system_permissions != 1) {
    require_once __DIR__ . '/../no_access.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $smtp_enabled = isset($_POST['smtp_enabled']) ? (int)$_POST['smtp_enabled'] : 0;
    $smtp_host = trim($_POST['smtp_host'] ?? '');
    $smtp_port = (int)($_POST['smtp_port'] ?? 587);
    $smtp_encryption = trim($_POST['smtp_encryption'] ?? 'tls');
    $smtp_auth = isset($_POST['smtp_auth']) ? (int)$_POST['smtp_auth'] : 1;
    $smtp_username = trim($_POST['smtp_username'] ?? '');
    $smtp_password_plain = $_POST['smtp_password'] ?? '';
    $from_email = trim($_POST['from_email'] ?? '');
    $from_name = trim($_POST['from_name'] ?? 'Kalendarz Mechanika');
    $reply_to_email = trim($_POST['reply_to_email'] ?? '');
    $test_email = trim($_POST['test_email'] ?? '');

    $_SESSION['active_tab'] = '5';

    // Sprawdź czy w tabeli smtp_settings istnieje już rekord
    $existing = null;
    $res = @$conn->query("SELECT * FROM smtp_settings ORDER BY id ASC LIMIT 1");
    if ($res && $res->num_rows > 0) {
        $existing = $res->fetch_assoc();
        $res->free();
    }

    // Jeśli podano nowe hasło -> zaszyfruj je; w przeciwnym razie zachowaj dotychczasowe
    if ($smtp_password_plain !== '') {
        $encryptedPassword = encryptSmtpPassword($smtp_password_plain);
    } else {
        $encryptedPassword = $existing['smtp_password'] ?? '';
    }

    $updatedBy = (string)($userName ?? $userId ?? 'admin');

    if ($existing) {
        $id = (int)$existing['id'];
        $stmt = $conn->prepare(
            "UPDATE smtp_settings
             SET smtp_enabled = ?,
                 smtp_host = ?,
                 smtp_port = ?,
                 smtp_encryption = ?,
                 smtp_auth = ?,
                 smtp_username = ?,
                 smtp_password = ?,
                 from_email = ?,
                 from_name = ?,
                 reply_to_email = ?,
                 sys_updatedby = ?,
                 sys_updatedate = NOW()
             WHERE id = ?"
        );
        $stmt->bind_param(
            "isisissssssi",
            $smtp_enabled,
            $smtp_host,
            $smtp_port,
            $smtp_encryption,
            $smtp_auth,
            $smtp_username,
            $encryptedPassword,
            $from_email,
            $from_name,
            $reply_to_email,
            $updatedBy,
            $id
        );
        $saved = $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare(
            "INSERT INTO smtp_settings
                (smtp_enabled, smtp_host, smtp_port, smtp_encryption, smtp_auth,
                 smtp_username, smtp_password, from_email, from_name, reply_to_email,
                 sys_updatedby, sys_updatedate)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->bind_param(
            "isisissssss",
            $smtp_enabled,
            $smtp_host,
            $smtp_port,
            $smtp_encryption,
            $smtp_auth,
            $smtp_username,
            $encryptedPassword,
            $from_email,
            $from_name,
            $reply_to_email,
            $updatedBy
        );
        $saved = $stmt->execute();
        $stmt->close();
    }

    if ($saved) {
        audit_log($conn, $updatedBy, 'Update SMTP settings', "host={$smtp_host}, port={$smtp_port}, user={$smtp_username}");
        $_SESSION['smtp_message_success'] = 'Ustawienia SMTP zostały zapisane.';

        if (!empty($_POST['send_test']) && $test_email !== '') {
            $sent = sendEmailNotification(
                $conn,
                $test_email,
                $updatedBy,
                'Test konfiguracji SMTP - Kalendarz Mechanika',
                '<h3>Test połączenia SMTP</h3><p>Wiadomość testowa z systemu <strong>Kalendarz Mechanika</strong> została wysłana pomyślnie.</p>'
            );
            if ($sent) {
                $_SESSION['smtp_message_success'] = 'Ustawienia SMTP zapisane. Wiadomość testowa została wysłana na adres: ' . $test_email;
            } else {
                unset($_SESSION['smtp_message_success']);
                $_SESSION['smtp_message_error'] = 'Ustawienia zapisane, ale nie udało się wysłać wiadomości testowej na adres: ' . $test_email . '. Sprawdź dane logowania, port lub włącz wysyłkę SMTP.';
            }
        }
    } else {
        $_SESSION['smtp_message_error'] = 'Błąd zapisu ustawień SMTP: ' . $conn->error;
    }

    $conn->close();
    header('Location: ../index.php?tab=5');
    exit;
}
