<?php 
require_once __DIR__ . '/../../sys-backend/session_config.php';
require_once __DIR__ . '/../../sys-backend/db_connect.php';
// check db_connect.php

$mechanic_id = null;
$mechanic_name = null;
$mechanic_sort = null;
$color = null;
$work_start_time = null;
$work_end_time = null;


if (isset($_POST['id'])) {
    $mechanic_id = $_POST['id'];
    $mechanic_name = $_POST['mechanic_name'];
    $mechanic_sort = $_POST['mechanic_sort'];
    $color = $_POST['color'];
    $work_start_time = $_POST['work_start_time'];
    $work_end_time = $_POST['work_end_time'];
    $font_color = $_POST['font_color'];
}

$work_start_time = !empty($_POST['work_start_time'])
    ? $_POST['work_start_time'] . ':00'
    : null;

$work_end_time = !empty($_POST['work_end_time'])
    ? $_POST['work_end_time'] . ':00'
    : null;

$stmt = $conn->prepare("
    UPDATE mechanics
    SET mechanic_name = ?,
        mechanic_sort = ?,
        color = ?,
        work_start_time = ?,
        work_end_time = ?,
        font_color = ?
    WHERE mechanic_id = ?
");

$stmt->bind_param(
    "sissssi",
    $mechanic_name,
    $mechanic_sort,
    $color,
    $work_start_time,
    $work_end_time,
    $font_color,
    $mechanic_id
);
if ($stmt->execute()) {
    header("Location: ../index.php");
    exit();
} else {
    echo "Błąd aktualizacji danych: " . $stmt->error;
}
$stmt->close();
$conn->close();