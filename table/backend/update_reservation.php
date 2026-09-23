<?php
require_once __DIR__ . '/../../sys-backend/session_config.php';
require_once __DIR__ . '/../../sys-backend/db_connect.php';
require_once  __DIR__ . '/../../sys-backend/audit.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

/** @var mysqli $conn */
/** @var string $userName */
/** @var string audit_log */




// Edycja rezerwacji
if ($_SERVER['REQUEST_METHOD'] === 'POST') {


    if (isset($_POST['reservation']) && is_array($_POST['reservation']) && !empty($_POST['reservation']['slot_id'])) {
        $r = $_POST['reservation'];
        $slotId = trim($r['slot_id']);
        $title = trim($r['title'] ?? '');
        $reservation_description = trim($r['reservation_description'] ?? '');
        $phone = trim($r['phone'] ?? '');
        $vin = trim($r['vin'] ?? '');
        $plate = trim($r['plate'] ?? '');
        $slotStart = $r['slot_start'] ?? '';
        $slotEnd = $r['slot_time_end'] ?? '';
        $dateInput = $r['slot_date'] ?? '';
        $state = isset($r['state']) ? (int)$r['state'] : 0;
        $new_mechanic_id = isset($r['mechanic_id']) ? (int)$r['mechanic_id'] : 0;
        $receipt_or_invoice = trim($r['receipt_or_invoice'] ?? 'paragon');
        $nip = trim($r['nip'] ?? '');

        $selectStmt = $conn->prepare('SELECT mechanic_id FROM calendar_slots WHERE slot_id = ?');
        $selectStmt->bind_param('s', $slotId);
        $selectStmt->execute();
        $selectResult = $selectStmt->get_result();
        $mechanicRow = $selectResult->fetch_assoc();
        $selectStmt->close();

        if ($mechanicRow) {
            $mechanicId = (int)$mechanicRow['mechanic_id'];

            if ($new_mechanic_id <= 0) {
                $_SESSION['reservation_error'] = 'Wybierz mechanika.';
            } else {
                $mechanicStmt = $conn->prepare('SELECT work_start_time, work_end_time FROM mechanics WHERE mechanic_id = ?');
                $mechanicStmt->bind_param('i', $new_mechanic_id);
                $mechanicStmt->execute();
                $mechanicResult = $mechanicStmt->get_result();
                $mechanicData = $mechanicResult->fetch_assoc();
                $mechanicStmt->close();

                if (!$mechanicData) {
                    $_SESSION['reservation_error'] = 'Wybrany mechanik nie istnieje.';
                } else {
                    $workStart = DateTime::createFromFormat('H:i:s', $mechanicData['work_start_time'] ?? '08:00:00') ?: DateTime::createFromFormat('H:i', substr($mechanicData['work_start_time'] ?? '08:00:00', 0, 5));
                    $workEnd = DateTime::createFromFormat('H:i:s', $mechanicData['work_end_time'] ?? '16:00:00') ?: DateTime::createFromFormat('H:i', substr($mechanicData['work_end_time'] ?? '16:00:00', 0, 5));
                    $slotStartTime = DateTime::createFromFormat('H:i', $slotStart);
                    $slotEndTime = DateTime::createFromFormat('H:i', $slotEnd);

                    if ($slotStartTime && $slotEndTime && $slotStartTime < $slotEndTime && $slotStartTime >= $workStart && $slotEndTime <= $workEnd) {
                        $conflictStmt = $conn->prepare(
                            'SELECT COUNT(*) AS conflict_count
                             FROM calendar_slots
                             WHERE mechanic_id = ?
                               AND slot_date = ?
                               AND slot_id != ?
                               AND slot_time_start < ?
                               AND slot_time_end > ?'
                        );
                        $conflictStmt->bind_param('issss', $new_mechanic_id, $dateInput, $slotId, $slotEnd, $slotStart);
                        $conflictStmt->execute();
                        $conflictResult = $conflictStmt->get_result();
                        $conflictRow = $conflictResult->fetch_assoc();
                        $conflictStmt->close();

                        if (!empty($conflictRow['conflict_count'])) {
                            $_SESSION['reservation_error'] = 'Zaktualizowany termin nachodzi na inną rezerwację tego mechanika.';
                        } else {
                            $stmt = $conn->prepare(
        'UPDATE calendar_slots 
         SET 
         slot_time_start = ?, 
         slot_time_end = ?, 
         reservation_title = ?, 
         reservation_description = ?, 
         reservation_phone = ?, 
         reservation_vin = ?, 
         reservation_plate = ?, 
         reservation_state = ?,
         slot_date = ?, 
         mechanic_id = ?,
         sys_updatedby = ?, 
         sys_updatedate = NOW(), 
         nip = ?, 
         receipt_or_invoice = ?

         WHERE slot_id = ?'
                            );
                            $stmt->bind_param(
                                'sssssssisiisss',
                                $slotStart,
                                $slotEnd,
                                $title,
                                $reservation_description,
                                $phone,
                                $vin,
                                $plate,
                                $state,
                                $dateInput,
                                $new_mechanic_id,
                                $userId,
                                $nip,
                                $receipt_or_invoice,
                                $slotId
                            );
                            if ($stmt->execute()) {
                                $_SESSION['reservation_success'] = 'Rezerwacja została pomyślnie zaktualizowana.';
                                $audit_log_data ="oldMechanicId = $mechanicId, newMechanicId = $new_mechanic_id, slotStart = $slotStart, slotEnd = $slotEnd, title = $title, reservation_description = $reservation_description, phone = $phone, vin = $vin, plate = $plate, state = $state";
                                audit_log($conn, $userName, 'Modify reservation', 'Slot ID: ' . $slotId . ' + ' . $audit_log_data);
                            } else {
                                $_SESSION['reservation_error'] = 'Nie udało się zaktualizować rezerwacji: ' . $stmt->error;
                            }
                            $stmt->close();
                        }
                    } else {
                        $_SESSION['reservation_error'] = 'Zaktualizowana godzina musi mieścić się w godzinach pracy mechanika.';
                    }
                }
            }
        } else {
            $_SESSION['reservation_error'] = 'Nie znaleziono rezerwacji o wskazanym identyfikatorze.';
        }
    }
}
//echo json_encode([
//    'error' => $_SESSION['reservation_error'] ?? null,
//    'success' => $_SESSION['reservation_success'] ?? null,
//]);


// przekaz $errorMessage na stronę przez sesję
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;
