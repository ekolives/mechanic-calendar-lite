<?php
require_once __DIR__ . '/../../sys-backend/session_config.php';
require_once __DIR__ . '/../../sys-backend/db_connect.php';
require_admin();
require_post();

$mechanic_id = (int)($_POST['id'] ?? 0);
if ($mechanic_id <= 0) {
    fail_redirect('Nieprawidłowy identyfikator mechanika.', '../index.php?tab=1');
}
$back = '../edit_worker.php?id=' . $mechanic_id;

$mechanic_name = trim((string)($_POST['mechanic_name'] ?? ''));
$mechanic_sort = (int)($_POST['mechanic_sort'] ?? 0);
$color = (string)($_POST['color'] ?? '');
$font_color = (string)($_POST['font_color'] ?? '');
$work_start_raw = (string)($_POST['work_start_time'] ?? '');
$work_end_raw = (string)($_POST['work_end_time'] ?? '');

if ($mechanic_name === '' || mb_strlen($mechanic_name) > 100) {
    fail_redirect('Nazwa jest wymagana (maks. 100 znaków).', $back);
}
if (!is_valid_color($color) || !is_valid_color($font_color)) {
    fail_redirect('Nieprawidłowy kolor.', $back);
}
if (!is_valid_time($work_start_raw) || !is_valid_time($work_end_raw) || substr($work_start_raw, 0, 5) >= substr($work_end_raw, 0, 5)) {
    fail_redirect('Godzina zakończenia pracy musi być późniejsza niż rozpoczęcia.', $back);
}

$work_start_time = substr($work_start_raw, 0, 5) . ':00';
$work_end_time = substr($work_end_raw, 0, 5) . ':00';

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
    header("Location: ../index.php?tab=1");
    exit();
}
error_log('update_worker: ' . $stmt->error);
$stmt->close();
fail_redirect('Nie udało się zapisać zmian.', $back);
