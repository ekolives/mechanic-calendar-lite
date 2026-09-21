<?php
require_once __DIR__ . '/../sys-backend/session_config.php';
require_once __DIR__ . '/../sys-backend/db_connect.php';


/** @var string $lang_WebTitle */
/** @var string $lang_login */
/** @var string $lang_show */
/** @var string $lang_prev_week */
/** @var string $lang_next_week */
/** @var string $lang_prev_day */
/** @var string $lang_next_day */
/** @var string $lang_settings */
/** @var string $lang_daily_view */
/** @var string $lang_weekly_view */
/** @var string $lang_reservation_list */
/** @var string $lang_date */
/** @var string $lang_time_start */
/** @var string $lang_time_end */
/** @var string $lang_title */
/** @var string $lang_description */
/** @var string $lang_phone */
/** @var string $lang_email */
/** @var string $lang_vin */
/** @var string $lang_plate */
/** @var string $lang_new_reservation_for */
/** @var string $lang_edit_reservation */
/** @var string $lang_save_reservation */
/** @var string $lang_save_changes */
/** @var string $lang_delete */
/** @var string $lang_creation_date */
/** @var string $lang_updated_date */
/** @var string $lang_created_by */
/** @var string $lang_updated_by */
/** @var string $lang_yes */
/** @var string $lang_no */
/** @var string $lang_cancelled */
/** @var string $lang_slot_name */







$errorMessage = '';
if (!empty($_SESSION['reservation_error'])) {
    $errorMessage = $_SESSION['reservation_error'];
    unset($_SESSION['reservation_error']);
}
$successMessage = '';
if (!empty($_SESSION['reservation_success'])) {
    $successMessage = $_SESSION['reservation_success'];
    unset($_SESSION['reservation_success']);
}

$view = $_GET['view'] ?? 'weekly';
if ($view !== 'daily' && $view !== 'weekly') {
    $view = 'weekly';
}

$dateInput = $_GET['date'] ?? date('Y-m-d');
$selectedDate = DateTime::createFromFormat('Y-m-d', $dateInput);
if (!$selectedDate) {
    $selectedDate = new DateTime();
    $dateInput = $selectedDate->format('Y-m-d');
}


if (isset($_GET['week'])) {
    $selectedDate = new DateTime($dateInput);
    if ($_GET['week'] === 'prev') {
        $selectedDate->modify('-7 days');
    } elseif ($_GET['week'] === 'next') {
        $selectedDate->modify('+7 days');
    }
    $dateInput = $selectedDate->format('Y-m-d');
}

if (isset($_GET['day'])) {
    $selectedDate = new DateTime($dateInput);
    if ($_GET['day'] === 'prev') {
        $selectedDate->modify('-1 day');
    } elseif ($_GET['day'] === 'next') {
        $selectedDate->modify('+1 day');
    }
    $dateInput = $selectedDate->format('Y-m-d');
}


$previousWeekDate = (clone $selectedDate)->modify('-7 days')->format('Y-m-d');
$nextWeekDate = (clone $selectedDate)->modify('+7 days')->format('Y-m-d');


if ($view === 'daily') {
    $days = [$selectedDate];
} else {
    $weekStart = clone $selectedDate;
    $weekStart->modify('monday this week');

    $days = [];
    for ($i = 0; $i < 5; $i++) {
        $day = clone $weekStart;
        $day->modify("+{$i} days");
        $days[] = $day;
    }
}

$slots = [];
$mechanics = [];
$mechanicColors = [];
$mechanicFontColors = [];
$mechanicWorkStart = [];
$mechanicWorkEnd = [];
$globalStart = null;
$globalEnd = null;


$userId = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT m.mechanic_id,
           m.mechanic_name,
           m.color,
           m.font_color,
           m.work_start_time,
           m.work_end_time
    FROM mechanics m
    INNER JOIN permissions p
        ON p.mechanic_id = m.mechanic_id
    WHERE p.user_id = ?
      AND p.status = 1
    ORDER BY m.mechanic_sort ASC, m.mechanic_name ASC
");

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $mechanicId = (int)$row['mechanic_id'];
        $mechanics[$mechanicId] = $row['mechanic_name'];
        $mechanicColors[$mechanicId] = trim((string)$row['color']) ?: '#a855f7';
        $mechanicFontColors[$mechanicId] = trim((string)$row['font_color']) ?: '#000000';
        $mechanicWorkStart[$mechanicId] = $row['work_start_time'] ?: '08:00:00';
        $mechanicWorkEnd[$mechanicId] = $row['work_end_time'] ?: '16:00:00';

        $startTime = DateTime::createFromFormat('H:i:s', $mechanicWorkStart[$mechanicId]);
        $endTime = DateTime::createFromFormat('H:i:s', $mechanicWorkEnd[$mechanicId]);
        if (!$startTime) {
            $startTime = DateTime::createFromFormat('H:i', substr($mechanicWorkStart[$mechanicId], 0, 5));
        }
        if (!$endTime) {
            $endTime = DateTime::createFromFormat('H:i', substr($mechanicWorkEnd[$mechanicId], 0, 5));
        }

        if (!$globalStart || $startTime < $globalStart) {
            $globalStart = clone $startTime;
        }
        if (!$globalEnd || $endTime > $globalEnd) {
            $globalEnd = clone $endTime;
        }
    }
    $result->free();
}

$userLogins = [];
$userResult = $conn->query('SELECT user_id, user_login FROM users');
if ($userResult) {
    while ($userRow = $userResult->fetch_assoc()) {
        $userLogins[(int)$userRow['user_id']] = $userRow['user_login'];
    }
    $userResult->free();
}

if (!$globalStart) {
    $globalStart = new DateTime('08:00');
}
if (!$globalEnd) {
    $globalEnd = new DateTime('16:00');
}

$slot = clone $globalStart;
while ($slot < $globalEnd) {
    $slots[] = $slot->format('H:i');
    $slot->modify('+30 minutes');
}



// Ładowanie danych dla widoku
$rangeStart = $view === 'daily' ? $dateInput : $weekStart->format('Y-m-d');
$rangeEnd = $view === 'daily' ? $dateInput : $days[count($days) - 1]->format('Y-m-d');

$entries = [];
$stmt = $conn->prepare(
    'SELECT slot_id, mechanic_id, slot_date, slot_time_start, slot_time_end, reservation_title, reservation_description, reservation_phone, reservation_vin, reservation_plate,
            sys_createdate, sys_submiter, sys_updatedate, sys_updatedby, reservation_state 
     FROM calendar_slots
     WHERE slot_date BETWEEN ? AND ?'
);
$stmt->bind_param('ss', $rangeStart, $rangeEnd);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $row['sys_submiter'] = $userLogins[(int)($row['sys_submiter'] ?? 0)] ?? $row['sys_submiter'];
    $row['sys_updatedby'] = $userLogins[(int)($row['sys_updatedby'] ?? 0)] ?? $row['sys_updatedby'];

    $mechanicId = (int)$row['mechanic_id'];
    $date = $row['slot_date'];
    $start = new DateTime($row['slot_time_start']);

    if (!empty($row['slot_time_end'])) {
        $end = new DateTime($row['slot_time_end']);
    } else {
        $end = clone $start;
        $end->modify('+30 minutes');
    }

    while ($start < $end) {
        $timeKey = $start->format('H:i');
        $entries[$mechanicId][$date][$timeKey] = $row;
        $start->modify('+30 minutes');
    }
}
$stmt->close();

?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl" lang="pl">

<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../newstyle.css" />
</head>

<body>

    <div id="header">
        <?php require_once "../sys-backend/lang.php"; ?>
        <div id="logo">
            <h3><?php echo "$lang_WebTitle - $userName"; ?></h3>
        </div>
    </div>

    <div id="wrapper">
        <div id="content">
            <table>
                <tr>
                    <td>
                        <form method="get">
                            <input type="hidden" name="view" value="<?php echo htmlspecialchars($view); ?>">
                    <td><label><?php echo $lang_date; ?>:
                    <td><input type="date" name="date" value="<?php echo htmlspecialchars($dateInput); ?>"> </label>
                    <td> <button type="submit"><?php echo $lang_show; ?></button>


                        <?php

                        if ($view == 'weekly') {
                            echo '<td><button type="submit" name="week" value="prev">' . $lang_prev_week . '</button>';
                            echo '<td><button type="submit" name="week" value="next">' . $lang_next_week . '</button>';
                        } else {
                            echo '<td><button type="submit" name="day" value="prev">' . $lang_prev_day . '</button>';
                            echo '<td><button type="submit" name="day" value="next">' . $lang_next_day . '</button>';
                        }
                        ?>

                        </form>
                    <td><a href="../settings/index.php" style="margin-left: auto;" class="button-green"><?php echo $lang_settings; ?></a></td>
                    <td><a href="index.php?view=daily" style="margin-left: auto;" class="button-orange"><?php echo $lang_daily_view; ?></a></td>
                    <td><a href="index.php?view=weekly" style="margin-left: auto;" class="button-orange"><?php echo $lang_weekly_view; ?></a>
                    <td><a href="reservation_list.php" style="margin-left: auto;" class="button-green"><?php echo $lang_reservation_list; ?></a>

                </tr>
            </table>



            <?php if (!empty($errorMessage)): ?>
                <div id="reservationError" class="error-message"><?php echo htmlspecialchars($errorMessage); ?></div>
            <?php endif; ?>


            <?php if (!empty($successMessage)): ?>
                <div id="reservationSuccess" class="success-message"><?php echo htmlspecialchars($successMessage); ?></div>
            <?php endif; ?>


            <?php if (empty($mechanics)): ?>
                <p>Brak mechaników w bazie. Lub nie masz uprawnień do żadnego mechanika</p>
            <?php elseif ($view === 'daily'): ?>
                <?php include 'daily.php'; ?>
            <?php else: ?>
                <?php include 'weekly.php'; ?>
            <?php endif; ?>



            <!-- formlarz dodawawania nowej rezerwacji -->
            <div id="reservationModal" class="modal-backdrop">
                <div class="modal">
                    <button type="button" id="closeModal" class="modal-close">×</button>
                    <h2><?php echo $lang_new_reservation_for; ?> - <span id="resMechanicName"></span></h2>
                    <form method="post" action="backend/add_reservation.php">
                        <input type="hidden" name="reservation[mechanic_id]" id="resMechanicId">
                        <label><?php echo $lang_date; ?>:
                            <input type="date" name="reservation[slot_date]" id="resSlotDateInput" required>
                        </label>
                        <div class="edit-time-row">
                            <label><?php echo $lang_time_start; ?>:
                                <input type="time" name="reservation[slot_start]" id="resSlotStart" step="1800" required>
                            </label>

                            <label><?php echo $lang_time_end; ?>:
                                <input type="time" name="reservation[slot_time_end]" id="resSlotEnd" step="1800" required>
                            </label>
                        </div>
                        <label><?php echo $lang_title; ?>:
                            <input type="text" name="reservation[title]" required>
                        </label>
                        <label><?php echo $lang_description; ?>:
                            <textarea name="reservation[reservation_description]" rows="3"></textarea>
                        </label>
                        <label><?php echo $lang_phone; ?>:
                            <input type="text" name="reservation[phone]">
                        </label>
                        <label><?php echo $lang_vin; ?>:
                            <input type="text" name="reservation[vin]">
                        </label>
                        <label><?php echo $lang_plate; ?>:
                            <input type="text" name="reservation[plate]">
                        </label>
                        <button type="submit" class="button-green"><?php echo $lang_save_reservation; ?></button>
                    </form>
                </div>
            </div>




            <!-- *******************************************  formlarz zmiany rezerwacji ******************************************* -->
            <div id="editModal" class="modal-backdrop">
                <div class="modal">
                    <button type="button" id="closeEditModal" class="modal-close">×</button>
                    <h2><?php echo $lang_edit_reservation; ?> - <a id="editReservationNumber" href="#" target="_blank" style="color: inherit; text-decoration: none;"></a></h2>
                    <form method="post" action="backend/update_reservation.php" id="editReservationForm">
                        <input type="hidden" name="reservation[slot_id]" id="editSlotId">

                        <div class="edit-time-row">
                            <label><?php echo $lang_date; ?>:
                                <input type="date" name="reservation[slot_date]" id="editSlotDate" required>
                            </label>

                            <label><?php echo $lang_slot_name; ?>:
                                <select name="reservation[mechanic_id]" id="editMechanicId">

                                    <?php foreach ($mechanics as $mechanicId => $mechanicName): ?>
                                        <option value="<?php echo htmlspecialchars($mechanicId); ?>"><?php echo htmlspecialchars($mechanicName); ?></option>
                                    <?php endforeach; ?>

                                </select>
                            </label>
                        </div>
                        <div class="edit-time-row">
                            <label>
                                <?php echo $lang_time_start; ?>:
                                <input type="time"
                                    name="reservation[slot_start]"
                                    id="editSlotStart"
                                    step="1800"
                                    required>
                            </label>

                            <label>
                                <?php echo $lang_time_end; ?>:
                                <input type="time"
                                    name="reservation[slot_time_end]"
                                    id="editSlotEnd"
                                    step="1800"
                                    required>
                            </label>
                        </div>

                        <label><?php echo $lang_title; ?>:
                            <input type="text" name="reservation[title]" id="editTitle" required>
                        </label>

                        <label><?php echo $lang_description; ?>:
                            <textarea name="reservation[reservation_description]" id="editDescription" rows="4"></textarea>
                        </label>

                        <label><?php echo $lang_phone; ?>:
                            <input type="text" name="reservation[phone]" id="editPhone">
                        </label>

                        <label><?php echo $lang_vin; ?>:
                            <input type="text" name="reservation[vin]" id="editVin">
                        </label>


                        <div class="edit-time-row">
                            <label><?php echo $lang_plate; ?>:
                                <input type="text" name="reservation[plate]" id="editPlate" style="width: 200px;">
                            </label>
                            <?php echo $lang_cancelled; ?>
                            <input type="radio" name="reservation[state]" id="editStateTak" value="1"><?php echo $lang_yes; ?>
                            <input type="radio" name="reservation[state]" id="editStateNie" value="0"><?php echo $lang_no; ?>
                        </div>

                        <div class="sys-box">
                            <div class="edit-meta-row">
                                <div><small><?php echo $lang_creation_date; ?>: <span id="editCreationDate"></span></small></div>
                                <div><small><?php echo $lang_created_by; ?>: <span id="editCreatedBy"></span></small></div>
                            </div>
                            <div class="edit-meta-row">
                                <div><small><?php echo $lang_updated_date; ?>: <span id="editUpdatedDate"></span></small></div>
                                <div><small><?php echo $lang_updated_by; ?>: <span id="editUpdatedBy"></span></small></div>
                            </div>

                            <input type="hidden" id="editReservationDate">
                            <input type="hidden" id="editReservationStart">
                        </div>

                        <div id="editButtons" style="display: flex; gap: 10px; margin-top: 10px;">
                            <button type="submit" style="flex: 1" class="button-green"><?php echo $lang_save_changes ?></button>
                            <button type="button" id="deleteBtn" style="flex: 1; background: #dc2626;"><?php echo $lang_delete; ?></button>
                        </div>
                        <div id="editDisabledMessage" class="button-orange" style="display: none; margin-top: 10px;"></div>
                </div>
                </form>
            </div>
        </div>


        <!-- *******************************************  END ******************************************* -->


        <script>
            let firstCell = null;
            const isAdmin = <?php echo ($user_system_permissions == 1 ? 'true' : 'false'); ?>;

            function updateEditModalActions(date, slotStart) {
                const editButtons = document.getElementById('editButtons');
                const editDisabledMessage = document.getElementById('editDisabledMessage');
                const reservationDateTime = new Date(date + 'T' + slotStart + ':00');
                const now = new Date();
                const editable = isAdmin || reservationDateTime > now;

                if (editable) {
                    editButtons.style.display = 'flex';
                    editDisabledMessage.style.display = 'none';
                    editDisabledMessage.textContent = '';
                } else {
                    editButtons.style.display = 'none';
                    editDisabledMessage.style.display = 'block';
                    editDisabledMessage.textContent = 'Mineła data rozpoczęcia i nie można edytować';
                }
            }

            function openReservationModal(mechanicId, mechanicName, date, start, end) {
                document.getElementById('resMechanicId').value = mechanicId;
                document.getElementById('resMechanicName').textContent = mechanicName;
                document.getElementById('resSlotDateInput').value = date;
                document.getElementById('resSlotStart').value = start;
                let endDate = new Date('2000-01-01T' + end + ':00');
                endDate.setMinutes(endDate.getMinutes() + 30);
                document.getElementById('resSlotEnd').value = endDate.toTimeString().substring(0, 5);
                document.getElementById('reservationModal').style.display = 'block';
                document.querySelectorAll('.slot-cell').forEach(c => c.classList.remove('selected'));
                firstCell = null;
            }

            function openEditModal(slotId, mechanicId, title, description, phone, vin, plate, state, slotStart, slotEnd, date, createdDate, createdBy, updatedDate, updatedBy) {
                document.getElementById('editSlotId').value = slotId;
                document.getElementById('editReservationNumber').textContent = 'REQ' + String(slotId).padStart(7, '0');
                document.getElementById('editReservationNumber').href = 'reservation_details.php?slot_id=' + slotId;
                document.getElementById('editMechanicId').value = mechanicId;
                document.getElementById('editTitle').value = title;
                document.getElementById('editDescription').value = description;
                document.getElementById('editPhone').value = phone;
                document.getElementById('editVin').value = vin;
                document.getElementById('editPlate').value = plate;
                document.getElementById('editSlotStart').value = slotStart;
                document.getElementById('editSlotDate').value = date;
                document.getElementById('editSlotEnd').value = slotEnd;
                document.getElementById('editCreationDate').textContent = createdDate || '-';
                document.getElementById('editCreatedBy').textContent = createdBy || '-';
                document.getElementById('editUpdatedDate').textContent = updatedDate || '-';
                document.getElementById('editUpdatedBy').textContent = updatedBy || '-';
                document.getElementById('editReservationDate').value = date;
                document.getElementById('editReservationStart').value = slotStart;
                updateEditModalActions(date, slotStart);
                document.getElementById('editModal').style.display = 'block';

                if (state == '1' || state == 1) {
                    document.getElementById('editStateTak').checked = true;
                } else {
                    document.getElementById('editStateNie').checked = true;
                }
            }



            document.querySelectorAll('.slot-cell').forEach(cell => {
                cell.addEventListener('click', () => {
                    const mechanicId = cell.dataset.mechanicId;
                    if (cell.classList.contains('disabled')) {
                        return;
                    }
                    const mechanicName = cell.dataset.mechanicName;
                    const date = cell.dataset.date;
                    const time = cell.dataset.time;
                    const slotId = cell.dataset.slotId;
                    const hasReservation = cell.dataset.slotId;

                    // Jeśli komórka ma rezerwację - otwórz edycję
                    if (hasReservation) {
                        const title = cell.dataset.title || '';
                        const description = cell.dataset.description || '';
                        const phone = cell.dataset.phone || '';
                        const vin = cell.dataset.vin || '';
                        const plate = cell.dataset.plate || '';
                        const slotStart = cell.dataset.slotStart || '';
                        const slotEnd = cell.dataset.slotEnd || '';
                        const createdDate = cell.dataset.createdDate || '';
                        const createdBy = cell.dataset.createdBy || '';
                        const updatedDate = cell.dataset.updatedDate || '';
                        const updatedBy = cell.dataset.updatedBy || '';
                        const state = cell.dataset.state || '';
                        const mechanicId = cell.dataset.mechanicId || '';

                        openEditModal(slotId, mechanicId, title, description, phone, vin, plate, state, slotStart, slotEnd, date, createdDate, createdBy, updatedDate, updatedBy);
                        return;
                    }

                    // Jeśli pusta - zaznaczaj zakres
                    if (!firstCell) {
                        firstCell = cell;
                        cell.classList.add('selected');
                        return;
                    }

                    if (firstCell.dataset.mechanicId !== mechanicId || firstCell.dataset.date !== date) {
                        document.querySelectorAll('.slot-cell').forEach(c => c.classList.remove('selected'));
                        firstCell = cell;
                        cell.classList.add('selected');
                        return;
                    }

                    const start = firstCell.dataset.time < time ? firstCell.dataset.time : time;
                    const end = firstCell.dataset.time < time ? time : firstCell.dataset.time;

                    openReservationModal(mechanicId, mechanicName, date, start, end);
                });
            });

            document.getElementById('closeModal').addEventListener('click', () => {
                document.getElementById('reservationModal').style.display = 'none';
                firstCell = null;
                document.querySelectorAll('.slot-cell').forEach(c => c.classList.remove('selected'));
            });

            document.getElementById('closeEditModal').addEventListener('click', () => {
                document.getElementById('editModal').style.display = 'none';
            });

            document.getElementById('deleteBtn').addEventListener('click', () => {
                if (confirm('Jesteś pewny, że chcesz usunąć tę rezerwację?')) {
                    const slotId = document.getElementById('editSlotId').value;
                    const form = document.createElement('form');
                    form.method = 'post';
                    form.action = 'backend/del_reservation.php';
                    form.innerHTML = '<input type="hidden" name="delete_slot_id" value="' + slotId + '">';
                    document.body.appendChild(form);
                    form.submit();
                }
            });

            const reservationErrorElement = document.getElementById('reservationError');
            if (reservationErrorElement) {
                setTimeout(() => {
                    reservationErrorElement.style.transition = 'opacity 0.5s ease';
                    reservationErrorElement.style.opacity = '0';
                    setTimeout(() => {
                        if (reservationErrorElement.parentNode) {
                            reservationErrorElement.parentNode.removeChild(reservationErrorElement);
                        }
                    }, 500);
                }, 5000);
            }

            const reservationSuccessElement = document.getElementById('reservationSuccess');
            if (reservationSuccessElement) {
                setTimeout(() => {
                    reservationSuccessElement.style.transition = 'opacity 0.5s ease';
                    reservationSuccessElement.style.opacity = '0';
                    setTimeout(() => {
                        if (reservationSuccessElement.parentNode) {
                            reservationSuccessElement.parentNode.removeChild(reservationSuccessElement);
                        }
                    }, 500);
                }, 5000);
            }


            document.addEventListener('DOMContentLoaded', function() {
                const today = document.getElementById('today-day');

                if (today) {
                    today.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        </script>

    </div>
    </div>
</body>
<div id="footer">
    <p><a href="https://itws.pl" style="text-decoration: none; color: #fff;">ekolives (ITWS) &copy; </a> <?php echo date("Y"); ?></p>
</div>

</html>