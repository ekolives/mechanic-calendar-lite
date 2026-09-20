<?php
require_once __DIR__ . '/../../sys-backend/session_config.php';
require_once __DIR__ . '/../../sys-backend/db_connect.php';
require_once  __DIR__ . '/../../sys-backend/audit.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Nowa rezerwacja

    if (isset($_POST['reservation']) && is_array($_POST['reservation']) && empty($_POST['reservation']['slot_id'])) {
        $r = $_POST['reservation'];
        $mechanicId = (int)($r['mechanic_id'] ?? 0);
        $slotDate = $r['slot_date'] ?? '';
        $slotStart = $r['slot_start'] ?? '';
        $slotEnd = $r['slot_time_end'] ?? '';
        $title = trim($r['title'] ?? '');
        $reservation_description = trim($r['reservation_description'] ?? '');
        $phone = trim($r['phone'] ?? '');
        $vin = trim($r['vin'] ?? '');
        $plate = trim($r['plate'] ?? '');

        //debug
        //error_log("Received reservation data: " . print_r($r, true));
        //print_r($r);


        $mechanic = null;
        if ($mechanicId > 0) {
            $stmt = $conn->prepare('SELECT mechanic_name, work_start_time, work_end_time FROM mechanics WHERE mechanic_id = ?');
            $stmt->bind_param('i', $mechanicId);
            $stmt->execute();
            $result = $stmt->get_result();
            $mechanic = $result->fetch_assoc();
            $stmt->close();
        }

        if ($mechanicId > 0 && $mechanic && $slotDate && $slotStart && $slotEnd) {
            $workStart = DateTime::createFromFormat('H:i:s', $mechanic['work_start_time'] ?? '08:00:00') ?: DateTime::createFromFormat('H:i', substr($mechanic['work_start_time'] ?? '08:00:00', 0, 5));
            $workEnd = DateTime::createFromFormat('H:i:s', $mechanic['work_end_time'] ?? '16:00:00') ?: DateTime::createFromFormat('H:i', substr($mechanic['work_end_time'] ?? '16:00:00', 0, 5));
            $slotStartTime = DateTime::createFromFormat('H:i', $slotStart);
            $slotEndTime = DateTime::createFromFormat('H:i', $slotEnd);
            $state = 0;

            //debug
            //error_log("Mechanic ID: $mechanicId, Work Start: " . $workStart->format('H:i') . ", Work End: " . $workEnd->format('H:i') . ", Slot Start: " . $slotStartTime->format('H:i') . ", Slot End: " . $slotEndTime->format('H:i'));

            if ($slotStartTime && $slotEndTime && $slotStartTime < $slotEndTime && $slotStartTime >= $workStart && $slotEndTime <= $workEnd) {
                $conflictStmt = $conn->prepare(
                    'SELECT COUNT(*) AS conflict_count
                     FROM calendar_slots
                     WHERE mechanic_id = ?
                       AND slot_date = ?
                       AND slot_time_start < ?
                       AND slot_time_end > ?'
                );
                $conflictStmt->bind_param('isss', $mechanicId, $slotDate, $slotEnd, $slotStart);
                $conflictStmt->execute();
                $conflictResult = $conflictStmt->get_result();
                $conflictRow = $conflictResult->fetch_assoc();
                $conflictStmt->close();

                if (!empty($conflictRow['conflict_count'])) {
                    $_SESSION['reservation_error'] = 'Wybrany termin jest już zajęty dla tego mechanika.';
                } else {
                    //error_log("Slot time is valid and free, proceeding to insert reservation.");
                    $stmt = $conn->prepare(
                        'INSERT INTO calendar_slots
                            (mechanic_id, 
                            slot_date, 
                            slot_time_start, 
                            slot_time_end, 
                            reservation_title, 
                            reservation_description, 
                            reservation_phone, 
                            reservation_vin, 
                            reservation_plate, 
                            reservation_state,
                            sys_updatedby, 
                            sys_submiter,
                            sys_createdate,
                            sys_updatedate)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
                    );
                    $stmt->bind_param(
                        'issssssssiii',
                        $mechanicId,
                        $slotDate,
                        $slotStart,
                        $slotEnd,
                        $title,
                        $reservation_description,
                        $phone,
                        $vin,
                        $plate,
                        $state,
                        $userId,
                        $userId
                    );
                    $_SESSION['reservation_success'] = 'Rezerwacja została pomyślnie dodana.';

                    $audit_log_data ="mechanicId = $mechanicId,slotDate = $slotDate,slotStart = $slotStart,slotEnd = $slotEnd,title = $title,reservation_description = $reservation_description,phone = $phone,vin = $vin,plate = $plate";
                    audit_log($conn, $userName, 'Create reservation',  $audit_log_data);

                    $stmt->execute();
                    $stmt->close();
                }
            } else {
                $_SESSION['reservation_error'] = 'Godzina rezerwacji musi mieścić się w godzinach pracy wybranego mechanika.';
            }
        } else {
            $_SESSION['reservation_error'] = 'Nieprawidłowe dane rezerwacji. Upewnij się, że wszystkie wymagane pola są wypełnione i poprawne.';
        }
    }
}
//echo "<br>Error Message: " . ($_SESSION['reservation_error'] ?? 'No errors');
header('Location: ' . $_SERVER['HTTP_REFERER']);
exit;
?>