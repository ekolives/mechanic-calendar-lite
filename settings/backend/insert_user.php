<?php 
require_once __DIR__ . '/../../sys-backend/session_config.php';
require_once __DIR__ . '/../../sys-backend/db_connect.php';
require_admin();
require_post();

// check db_connect.php

if (isset($_POST['user_login'], $_POST['user_password'], $_POST['permissions'], $_POST['status'])) {
    $user_login = trim((string)$_POST['user_login']);
    $user_password = (string)$_POST['user_password'];
    $user_permissions = (int)$_POST['permissions'];
    $status = (int)$_POST['status'];
    $user_email = trim((string)($_POST['user_email'] ?? ''));
    $back = '../add_user.php';

    if ($user_login === '' || strlen($user_login) > 100) {
        fail_redirect('Login jest wymagany (maks. 100 znaków).', $back);
    }
    if (strlen($user_password) < 8) {
        fail_redirect('Hasło musi mieć co najmniej 8 znaków.', $back);
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

    // login musi być unikalny
    $chk = $conn->prepare('SELECT 1 FROM users WHERE user_login = ? LIMIT 1');
    $chk->bind_param('s', $user_login);
    $chk->execute();
    $exists = $chk->get_result()->num_rows > 0;
    $chk->close();
    if ($exists) {
        fail_redirect('Użytkownik o takim loginie już istnieje.', $back);
    }

    $passwordHash = password_hash($user_password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO users (
        user_login, 
        user_password, 
        user_permissions, 
        user_status,
        user_email,
        sys_submiter,
        sys_createdate,
        sys_updatedby,
        sys_updatedate
        ) 
    
    VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssiisii", $user_login, $passwordHash, $user_permissions, $status, $user_email, $userId, $userId);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    header('Location: ../index.php');
    exit();
}