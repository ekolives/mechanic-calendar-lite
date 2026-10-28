<?php 
require_once __DIR__ . '/../../sys-backend/session_config.php';
require_once __DIR__ . '/../../sys-backend/db_connect.php';

// check db_connect.php

if (isset($_POST['user_login'], $_POST['user_password'], $_POST['permissions'], $_POST['status'])) {
    $user_login = $_POST['user_login'];
    $user_password = $_POST['user_password'];
    $user_permissions = $_POST['permissions'];
    $status = $_POST['status'];
    $user_email = $_POST['user_email'];


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
    $stmt->bind_param("sssisii", $user_login, $passwordHash, $user_permissions, $status, $user_email, $userId, $userId);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    header('Location: ../index.php');
    exit();
}