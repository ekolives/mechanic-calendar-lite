<?php
require_once __DIR__ . '/../../sys-backend/session_config.php';
require_once __DIR__ . '/../../sys-backend/db_connect.php';
require_once  __DIR__ . '/../../sys-backend/audit.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
$slotId = isset($_POST['slot_id']) ? (int)$_POST['slot_id'] : 0;

    // Usunięcie rezerwacji
    if (isset($_POST['delete_slot_id'])) {
        $slotId = (int)$_POST['delete_slot_id'];

        // sprawdź, czy rezerwacja istnieje i czy użytkownik ma dostęp do jej mechanika
        $lookup = $conn->prepare('SELECT mechanic_id FROM calendar_slots WHERE slot_id = ?');
        $lookup->bind_param('i', $slotId);
        $lookup->execute();
        $slotRow = $lookup->get_result()->fetch_assoc();
        $lookup->close();

        if (!$slotRow) {
            $_SESSION['reservation_error'] = 'Nie znaleziono rezerwacji.';
        } elseif (!user_can_access_mechanic($conn, $userId, $slotRow['mechanic_id'])) {
            $_SESSION['reservation_error'] = 'Nie masz uprawnień do usunięcia tej rezerwacji.';
        } else {
            $stmt = $conn->prepare('DELETE FROM calendar_slots WHERE slot_id = ?');
            $stmt->bind_param('i', $slotId);
            if ($stmt->execute()) {
                audit_log($conn, $userName, 'Delete reservation', 'Success - Delete reservation, slotId => ' . $slotId);
            }
            $stmt->close();
        }
    }
}
safe_referer_redirect('../index.php');
?>