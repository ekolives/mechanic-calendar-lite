<?php 
require_once __DIR__ . '/../../sys-backend/session_config.php';
require_once __DIR__ . '/../../sys-backend/db_connect.php';

// check db_connect.php

if (isset($_POST['name'], $_POST['work_start'], $_POST['work_end'], $_POST['color'])) {
    $name = $_POST['name'];
    $work_start = $_POST['work_start'];
    $work_end = $_POST['work_end'];
    $color = $_POST['color'];
    $font_color = $_POST['font_color'];

    $sql = "INSERT INTO mechanics (
        mechanic_name, 
        work_start_time, 
        work_end_time, 
        color,
        font_color,
        sys_submitter,
        sys_createdate,
        sys_updatedby,
        sys_updatedate
        ) 
    
    VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssiis", $name, $work_start, $work_end, $color, $font_color, $userId, $userId);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    header('Location: ../index.php');
    exit();
}