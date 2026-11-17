<?php
require_once __DIR__ . '/../../sys-backend/session_config.php';
require_once __DIR__ . '/../../sys-backend/db_connect.php';
require_admin();
require_post();

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    fail_redirect('Nieprawidłowy identyfikator mechanika.', '../index.php?tab=1');
}

// nie usuwamy mechanika, który ma rezerwacje - zniknęłyby one z kalendarza i listy
$chk = $conn->prepare("SELECT COUNT(*) AS c FROM calendar_slots WHERE mechanic_id = ?");
$chk->bind_param("i", $id);
$chk->execute();
$count = (int)($chk->get_result()->fetch_assoc()['c'] ?? 0);
$chk->close();
if ($count > 0) {
    fail_redirect("Nie można usunąć mechanika, który ma rezerwacje ($count). Zmień mu najpierw rezerwacje lub zmień nazwę.", '../edit_worker.php?id=' . $id);
}

$stmt = $conn->prepare("DELETE FROM mechanics WHERE mechanic_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

// usuń także jego wpisy uprawnień
$stmt = $conn->prepare("DELETE FROM permissions WHERE mechanic_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();
$conn->close();

header('Location: ../index.php?tab=1');
exit();
