<?php
require_once __DIR__ . '/../../sys-backend/session_config.php';
require_once __DIR__ . '/../../sys-backend/db_connect.php';
require_admin();
require_post();

// check db_connect.php

if (isset($_POST['id'])) {

    $id = (int)$_POST['id'];
    $user_login = trim((string)($_POST['user_login'] ?? ''));
    $user_password = (string)($_POST['user_password'] ?? '');
    $user_permissions = (int)($_POST['permissions'] ?? 0);
    $status = (int)($_POST['status'] ?? -1);
    $mechanics = is_array($_POST['mechanics'] ?? null) ? $_POST['mechanics'] : [];
    $user_email = trim((string)($_POST['user_email'] ?? ''));
    $notifications = array_map('intval', is_array($_POST['notifications'] ?? null) ? $_POST['notifications'] : []);
    $back = '../edit_user.php?id=' . $id;

    if ($id <= 0) {
        fail_redirect('Nieprawidłowy identyfikator użytkownika.', '../index.php?tab=2');
    }
    if ($user_login === '' || strlen($user_login) > 100) {
        fail_redirect('Login jest wymagany (maks. 100 znaków).', $back);
    }
    if ($user_password !== '' && strlen($user_password) < 8) {
        fail_redirect('Nowe hasło musi mieć co najmniej 8 znaków.', $back);
    }
    if (!in_array($user_permissions, [1, 2, 3], true)) {
        fail_redirect('Nieprawidłowy typ uprawnień.', $back);
    }
    if (!in_array($status, [0, 1], true)) {
        fail_redirect('Nieprawidłowy status.', $back);
    }
    if ($user_email !== '' && !filter_var($user_email, FILTER_VALIDATE_EMAIL)) {
        fail_redirect('Nieprawidłowy adres e-mail.', $back);
    }
    $user_email = $user_email === '' ? null : $user_email;

    // login musi pozostać unikalny
    $chk = $conn->prepare('SELECT 1 FROM users WHERE user_login = ? AND user_id <> ? LIMIT 1');
    $chk->bind_param('si', $user_login, $id);
    $chk->execute();
    $taken = $chk->get_result()->num_rows > 0;
    $chk->close();
    if ($taken) {
        fail_redirect('Użytkownik o takim loginie już istnieje.', $back);
    }

    // nie pozwalamy zostać bez żadnego aktywnego administratora (np. przez zablokowanie lub degradację ostatniego)
    if (($user_permissions !== 1 || $status !== 1) && count_active_admins($conn, $id) === 0) {
        fail_redirect('W systemie musi pozostać co najmniej jeden aktywny administrator.', $back);
    }

    // aktualizacja użytkownika
    if ($user_password !== '') {

        $passwordHash = password_hash($user_password, PASSWORD_DEFAULT);

        $sql = "UPDATE users
            SET user_login = ?,
                user_password = ?,
                user_permissions = ?,
                user_status = ?,
                user_email = ?,
                sys_updatedate = NOW(),
                sys_updatedby = ?
            WHERE user_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssiisii",
            $user_login,
            $passwordHash,
            $user_permissions,
            $status,
            $user_email,
            $userId,
            $id
        );
    } else {

        $sql = "UPDATE users
            SET user_login = ?,
                user_permissions = ?,
                user_status = ?,
                user_email = ?,
                sys_updatedate = NOW(),
                sys_updatedby = ?
            WHERE user_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "siisii",
            $user_login,
            $user_permissions,
            $status,
            $user_email,
            $userId,
            $id
        );
    }

    $stmt->execute();
    $stmt->close();




// usuń stare uprawnienia
$stmt = $conn->prepare("DELETE FROM permissions WHERE user_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();
// dodaj nowe wraz z flagą notification
$stmt = $conn->prepare(
    "INSERT INTO permissions (user_id, mechanic_id, status, notification, sys_submitter, sys_createdate, sys_updatedby, sys_updatedate)
     VALUES (?, ?, 1, ?, ?, NOW(), ?, NOW())"
);
$validMechanicIds = [];
$mres = $conn->query('SELECT mechanic_id FROM mechanics');
while ($mres && ($mrow = $mres->fetch_assoc())) {
    $validMechanicIds[(int)$mrow['mechanic_id']] = true;
}
foreach ($mechanics as $mechanicId) {
    $mechanicId = (int)$mechanicId;
    if (!isset($validMechanicIds[$mechanicId])) {
        continue; // pomijamy nieistniejących mechaników
    }
    $notify = in_array($mechanicId, $notifications, true) ? 1 : 0;
    $stmt->bind_param("iiiii", $id, $mechanicId, $notify, $userId, $userId);
    $stmt->execute();
}
$stmt->close();
    $conn->close();

    header('Location: ../index.php');
    exit();
}
