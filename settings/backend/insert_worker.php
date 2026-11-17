<?php
require_once __DIR__ . '/../../sys-backend/session_config.php';
require_once __DIR__ . '/../../sys-backend/db_connect.php';
require_admin();
require_post();

if (isset($_POST['name'], $_POST['work_start'], $_POST['work_end'], $_POST['color'])) {
    $name = trim((string)$_POST['name']);
    $work_start = (string)$_POST['work_start'];
    $work_end = (string)$_POST['work_end'];
    $color = (string)$_POST['color'];
    $font_color = (string)($_POST['font_color'] ?? '#000000');
    $back = '../add_worker.php';

    if ($name === '' || mb_strlen($name) > 100) {
        fail_redirect('Imię jest wymagane (maks. 100 znaków).', $back);
    }
    if (!is_valid_time($work_start) || !is_valid_time($work_end) || substr($work_start, 0, 5) >= substr($work_end, 0, 5)) {
        fail_redirect('Godzina zakończenia pracy musi być późniejsza niż rozpoczęcia.', $back);
    }
    if (!is_valid_color($color) || !is_valid_color($font_color)) {
        fail_redirect('Nieprawidłowy kolor.', $back);
    }

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
    // font_color to tekst (#rrggbb) - wcześniej był wiązany jako liczba i zapisywał się jako 0
    $stmt->bind_param("sssssii", $name, $work_start, $work_end, $color, $font_color, $userId, $userId);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    header('Location: ../index.php?tab=1');
    exit();
}
