<?php
require_once __DIR__ . '/../../sys-backend/session_config.php';
require_once __DIR__ . '/../../sys-backend/db_connect.php';
require_admin();
require_post();

if (isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $name = (string)($_POST['name'] ?? '');

    if ($id <= 0) {
        fail_redirect('Nieprawidłowy identyfikator tłumaczenia.', '../index.php?tab=4');
    }
    if ($name === '' || mb_strlen($name) > 1000) {
        fail_redirect('Tłumaczenie jest wymagane (maks. 1000 znaków).', '../edit_translation.php?id=' . $id);
    }

    $sql = "UPDATE language SET name = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $name, $id);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    header('Location: ../index.php?tab=4');
    exit();
}
