<?php
require_once __DIR__ . '/../../sys-backend/session_config.php';
require_once __DIR__ . '/../../sys-backend/db_connect.php';
require_once  __DIR__ . '/../../sys-backend/audit.php';
require_once __DIR__ . '/../../sys-backend/mailer.php';

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
        $nip = trim($r['nip'] ?? '');
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

        if ($mechanicId > 0 && !user_can_access_mechanic($conn, $userId, $mechanicId)) {
            $_SESSION['reservation_error'] = 'Nie masz uprawnień do rezerwacji u tego mechanika.';
        } elseif ($slotDate !== '' && !is_valid_date($slotDate)) {
            $_SESSION['reservation_error'] = 'Nieprawidłowa data rezerwacji.';
        } elseif ($mechanicId > 0 && $mechanic && $slotDate && $slotStart && $slotEnd) {
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
                            nip,
                            sys_updatedby,
                            sys_submiter,
                            sys_createdate,
                            sys_updatedate)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
                    );
                    $stmt->bind_param(
                        'issssssssisii',
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
                        $nip,
                        $userId,
                        $userId
                    );

                    if ($stmt->execute()) {
                        $_SESSION['reservation_success'] = 'Rezerwacja została pomyślnie dodana.';

                        $audit_log_data ="mechanicId = $mechanicId,slotDate = $slotDate,slotStart = $slotStart,slotEnd = $slotEnd,title = $title,reservation_description = $reservation_description,phone = $phone,nip = $nip,vin = $vin,plate = $plate";
                        audit_log($conn, $userName, 'Create reservation',  $audit_log_data);

                        // powiadomienie o stworzonej rezerwacji
                        $notifyStmt = $conn->prepare(
                            "SELECT u.user_login, u.user_email
                             FROM permissions p
                             INNER JOIN users u ON u.user_id = p.user_id
                             WHERE p.mechanic_id = ?
                               AND p.status = 1
                               AND p.notification = 1
                               AND u.user_status = 1
                               AND u.user_email IS NOT NULL
                               AND u.user_email != ''"
                        );
                        $notifyStmt->bind_param("i", $mechanicId);
                        $notifyStmt->execute();
                        $notifyResult = $notifyStmt->get_result();

                        $mechanicNameSafe = htmlspecialchars($mechanic['mechanic_name'] ?? '', ENT_QUOTES, 'UTF-8');
                        $slotDateSafe = htmlspecialchars($slotDate, ENT_QUOTES, 'UTF-8');
                        $slotStartSafe = htmlspecialchars($slotStart, ENT_QUOTES, 'UTF-8');
                        $slotEndSafe = htmlspecialchars($slotEnd, ENT_QUOTES, 'UTF-8');
                        $titleSafe = htmlspecialchars($title ?: '-', ENT_QUOTES, 'UTF-8');
                        $descSafe = nl2br(htmlspecialchars($reservation_description ?: '-', ENT_QUOTES, 'UTF-8'));
                        $phoneSafe = htmlspecialchars($phone ?: '-', ENT_QUOTES, 'UTF-8');
                        $plateSafe = htmlspecialchars($plate ?: '-', ENT_QUOTES, 'UTF-8');
                        $vinSafe = htmlspecialchars($vin ?: '-', ENT_QUOTES, 'UTF-8');
                        $authorSafe = htmlspecialchars($userName ?? '-', ENT_QUOTES, 'UTF-8');

                        $emailSubject = "Nowa rezerwacja: {$mechanic['mechanic_name']} ({$slotDate} {$slotStart}-{$slotEnd})";
                        $emailHtmlBody = "
                            <h3>Utworzono nową rezerwację w kalendarzu</h3>
                            <table border='1' cellpadding='6' cellspacing='0' style='border-collapse: collapse; font-family: Arial, sans-serif;'>
                                <tr><th align='left' style='background:#f2f2f2;'>Mechanik / Slot</th><td>{$mechanicNameSafe}</td></tr>
                                <tr><th align='left' style='background:#f2f2f2;'>Data</th><td>{$slotDateSafe}</td></tr>
                                <tr><th align='left' style='background:#f2f2f2;'>Godziny</th><td>{$slotStartSafe} – {$slotEndSafe}</td></tr>
                                <tr><th align='left' style='background:#f2f2f2;'>Tytuł</th><td>{$titleSafe}</td></tr>
                                <tr><th align='left' style='background:#f2f2f2;'>Opis</th><td>{$descSafe}</td></tr>
                                <tr><th align='left' style='background:#f2f2f2;'>Telefon</th><td>{$phoneSafe}</td></tr>
                                <tr><th align='left' style='background:#f2f2f2;'>Nr rejestracyjny</th><td>{$plateSafe}</td></tr>
                                <tr><th align='left' style='background:#f2f2f2;'>VIN</th><td>{$vinSafe}</td></tr>
                                <tr><th align='left' style='background:#f2f2f2;'>Utworzył(a)</th><td>{$authorSafe}</td></tr>
                            </table>
                        ";

                        while ($notifyRow = $notifyResult->fetch_assoc()) {
                            $userEmail = $notifyRow['user_email'];
                            $userLogin = $notifyRow['user_login'];
                            // tutaj możesz wysłać powiadomienie e-mail do $userEmail
                            sendEmailNotification($conn, $userEmail, $userLogin, $emailSubject, $emailHtmlBody);
                            audit_log($conn, $userName, 'Notification sent to ' . $userEmail,  $audit_log_data   );
                            }

                        $notifyStmt->close();
                    } else {
                        $_SESSION['reservation_error'] = 'Nie udało się zapisać rezerwacji.';
                    }

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
safe_referer_redirect('../index.php');
?>
