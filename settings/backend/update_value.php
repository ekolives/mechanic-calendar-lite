<?php 
require_once __DIR__ . '/../../sys-backend/session_config.php';
require_once __DIR__ . '/../../sys-backend/db_connect.php';

// check db_connect.php

if (isset($_POST['id'])) {
    $id = $_POST['id'];
    $setting_sort = $_POST['setting_sort'];
    $setting_values = $_POST['setting_values'];
    $setting_status = $_POST['setting_status'];

    $sql = "UPDATE settings SET setting_sort = ?, setting_values = ?, status = ? WHERE sys_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("issi", $setting_sort, $setting_values, $setting_status, $id);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    header('Location: ../index.php');
    exit();
}