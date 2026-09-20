<?php
require_once __DIR__ . '/../../sys-backend/session_config.php';
require_once __DIR__ . '/../../sys-backend/db_connect.php';
require_once  __DIR__ . '/../../sys-backend/audit.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$slotId = isset($_POST['slot_id']) ? (int)$_POST['slot_id'] : 0;

    // Usunięcie rezerwacji
    if (isset($_POST['delete_slot_id'])) {
        $slotId = (int)$_POST['delete_slot_id'];
        $stmt = $conn->prepare('DELETE FROM calendar_slots WHERE slot_id = ?');
        $stmt->bind_param('i', $slotId);
        audit_log($conn, $userName, 'Delete reservation',  'Success - Delete reservation, slotId => ' . $slotId);
        $stmt->execute();
        $stmt->close();
    }
}
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;
?>