<?php
require_once __DIR__ . '/../../sys-backend/session_config.php';
require_once __DIR__ . '/../../sys-backend/db_connect.php';

// check db_connect.php

if (isset($_POST['id'])) {

    $id = (int)$_POST['id'];
    $user_login = $_POST['user_login'];
    $user_password = $_POST['user_password'];
    $user_permissions = $_POST['permissions'];
    $status = $_POST['status'];
    $mechanics = $_POST['mechanics'] ?? [];

    // aktualizacja użytkownika
    if ($user_password !== '') {

        $passwordHash = password_hash($user_password, PASSWORD_DEFAULT);

        $sql = "UPDATE users
            SET user_login = ?,
                user_password = ?,
                user_permissions = ?,
                user_status = ?,
                sys_updatedate = NOW(),
                sys_updatedby = ?
            WHERE user_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "ssiiii",
            $user_login,
            $passwordHash,
            $user_permissions,
            $status,
            $userId,
            $id
        );
    } else {

        $sql = "UPDATE users
            SET user_login = ?,
                user_permissions = ?,
                user_status = ?,
                sys_updatedate = NOW(),
                sys_updatedby = ?
            WHERE user_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            "siiii",
            $user_login,
            $user_permissions,
            $status,
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

    // dodaj nowe
    $stmt = $conn->prepare(
        "INSERT INTO permissions (user_id, mechanic_id, status, sys_submitter, sys_createdate, sys_updatedby, sys_updatedate)
         VALUES (?, ?, 1, ?, NOW(), ?, NOW())"
    );

    foreach ($mechanics as $mechanicId) {
        $mechanicId = (int)$mechanicId;
        $stmt->bind_param("iiii", $id, $mechanicId, $userId, $userId);
        $stmt->execute();
    }

    $stmt->close();
    $conn->close();

    header('Location: ../index.php');
    exit();
}
