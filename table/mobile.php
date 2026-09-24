<?php
require_once __DIR__ . '/../sys-backend/session_config.php';
require_once __DIR__ . '/../sys-backend/db_connect.php';

/** @var string $lang_WebTitle */
/** @var string $lang_show */
/** @var string $lang_prev_day */
/** @var string $lang_next_day */
/** @var string $lang_settings */
/** @var string $lang_daily_view */
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

$dateInput = $_GET['date'] ?? date('Y-m-d');
$selectedDate = DateTime::createFromFormat('Y-m-d', $dateInput);

if (!$selectedDate) {
    $selectedDate = new DateTime();
    $dateInput = $selectedDate->format('Y-m-d');
}

$previousDayDate = (clone $selectedDate)->modify('-1 day')->format('Y-m-d');
$nextDayDate = (clone $selectedDate)->modify('+1 day')->format('Y-m-d');

$mechanics = [];
$mechanicColors = [];
$mechanicFontColors = [];
$mechanicWorkStart = [];
$mechanicWorkEnd = [];

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

/*
 * Load reservations for the selected day.
 */
$entries = [];

$stmt = $conn->prepare(
    'SELECT slot_id, mechanic_id, slot_date, slot_time_start, slot_time_end,
            reservation_title, reservation_description, reservation_phone,
            reservation_vin, reservation_plate, sys_createdate, sys_submiter,
            sys_updatedate, sys_updatedby, reservation_state, nip,
            receipt_or_invoice, comment
     FROM calendar_slots
     WHERE slot_date = ?'
);

$stmt->bind_param('s', $dateInput);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $row['sys_submiter'] = $userLogins[(int)($row['sys_submiter'] ?? 0)] ?? $row['sys_submiter'];
    $row['sys_updatedby'] = $userLogins[(int)($row['sys_updatedby'] ?? 0)] ?? $row['sys_updatedby'];

    $mechanicId = (int)$row['mechanic_id'];
    $start = new DateTime($row['slot_time_start']);
    $end = !empty($row['slot_time_end'])
        ? new DateTime($row['slot_time_end'])
        : (clone $start);

    if (empty($row['slot_time_end'])) {
        $end->modify('+30 minutes');
    }

    while ($start < $end) {
        $timeKey = $start->format('H:i');
        $entries[$mechanicId][$dateInput][$timeKey] = $row;
        $start->modify('+30 minutes');
    }
}

$stmt->close();

require_once "../sys-backend/mobile_lang.php";


function mobile_h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function mobile_time(string $time): string
{
    return substr($time, 0, 5);
}

function mobile_is_reservation_start(array $reservation, string $time): bool
{
    return mobile_time($reservation['slot_time_start']) === $time;
}

function mobile_weekday_name(DateTimeInterface $date): string
{
    $days = [
        0 => 'Niedziela',
        1 => 'Poniedziałek',
        2 => 'Wtorek',
        3 => 'Środa',
        4 => 'Czwartek',
        5 => 'Piątek',
        6 => 'Sobota',
    ];

    return $days[(int)$date->format('w')];
}
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?php echo mobile_h($lang_WebTitle); ?></title>
    <link rel="stylesheet" href="../newstyle.css">
    <link rel="stylesheet" href="../mobile.css?v=2">
</head>

<body class="mobile-calendar-page">

<header class="mobile-header">
    <div class="mobile-header-row">
        <div class="mobile-header-title">
            <h1><?php echo mobile_h($lang_WebTitle); ?></h1>
        </div>

        <a href="../sys-backend/logout.php" class="mobile-logout-button" aria-label="Logout">
            <img src="../sys-backend/logout.png" alt="Wyloguj" height="27" width="27">
        </a>
    </div>
</header>





<main class="mobile-content">

    <nav class="mobile-toolbar">
        <a class="mobile-nav-button"
           href="index.php?date=<?php echo mobile_h($previousDayDate); ?>"
           aria-label="<?php echo mobile_h($lang_prev_day); ?>">‹</a>

        <form method="get" class="mobile-date-form">
            <label>
                <span><?php echo mobile_h($lang_date); ?></span>
                <input type="date" name="date" value="<?php echo mobile_h($dateInput); ?>">
            </label>
            <button type="submit"><?php echo mobile_h($lang_show); ?></button>
        </form>

        <a class="mobile-nav-button"
           href="index.php?date=<?php echo mobile_h($nextDayDate); ?>"
           aria-label="<?php echo mobile_h($lang_next_day); ?>">›</a>
    </nav>

    <div class="mobile-current-date">
        <?php echo mobile_h(mobile_weekday_name($selectedDate) . ', ' . $selectedDate->format('d.m.Y')); ?>
    </div>


    <?php if (!empty($errorMessage)): ?>
        <div id="reservationError" class="mobile-message mobile-message-error">
            <?php echo mobile_h($errorMessage); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($successMessage)): ?>
        <div id="reservationSuccess" class="mobile-message mobile-message-success">
            <?php echo mobile_h($successMessage); ?>
        </div>
    <?php endif; ?>

    <?php if (empty($mechanics)): ?>

        <div class="mobile-empty">
            Brak mechaników w bazie. Lub nie masz uprawnień do żadnego mechanika.
        </div>

    <?php else: ?>

        <section class="mobile-calendar">

            <?php foreach ($mechanics as $mechanicId => $mechanicName): ?>

                <?php
                $workStart = mobile_time($mechanicWorkStart[$mechanicId]);
                $workEnd = mobile_time($mechanicWorkEnd[$mechanicId]);

                $start = DateTime::createFromFormat('H:i', $workStart);
                $end = DateTime::createFromFormat('H:i', $workEnd);

                if (!$start || !$end) {
                    continue;
                }

                $mechanicEntries = $entries[$mechanicId][$dateInput] ?? [];

                $timeSlots = [];
                $slot = clone $start;

                while ($slot < $end) {
                    $timeSlots[] = $slot->format('H:i');
                    $slot->modify('+30 minutes');
                }
                ?>

                <section class="mechanic-card">

                    <div class="mechanic-card-header"
                         style="background: <?php echo mobile_h($mechanicColors[$mechanicId]); ?>;
                                color: <?php echo mobile_h($mechanicFontColors[$mechanicId]); ?>;">
                        <div>
                            <strong><?php echo mobile_h($mechanicName); ?></strong>
                            <small><?php echo mobile_h($workStart); ?> – <?php echo mobile_h($workEnd); ?></small>
                        </div>
                    </div>

                    <div class="mechanic-slots">

                        <?php foreach ($timeSlots as $time): ?>

                            <?php
                            $reservation = $mechanicEntries[$time] ?? null;

                            if ($reservation):
                                if (!mobile_is_reservation_start($reservation, $time)) {
                                    continue;
                                }

                                $slotId = (int)$reservation['slot_id'];
                                $slotStart = mobile_time($reservation['slot_time_start']);
                                $slotEnd = !empty($reservation['slot_time_end'])
                                    ? mobile_time($reservation['slot_time_end'])
                                    : date('H:i', strtotime($reservation['slot_time_start'] . ' +30 minutes'));

                                $state = (int)($reservation['reservation_state'] ?? 0);
                                $title = $reservation['reservation_title'] ?? '';
                                $description = $reservation['reservation_description'] ?? '';
                                $phone = $reservation['reservation_phone'] ?? '';
                                $vin = $reservation['reservation_vin'] ?? '';
                                $plate = $reservation['reservation_plate'] ?? '';
                                $createdDate = $reservation['sys_createdate'] ?? '';
                                $createdBy = $reservation['sys_submiter'] ?? '';
                                $updatedDate = $reservation['sys_updatedate'] ?? '';
                                $updatedBy = $reservation['sys_updatedby'] ?? '';
                                $nip = $reservation['nip'] ?? '';
                                $receiptOrInvoice = $reservation['receipt_or_invoice'] ?? '0';
                                $comment = $reservation['comment'] ?? '';
                            ?>

                                <button type="button"
                                        class="mobile-reservation <?php echo $state ? 'is-cancelled' : ''; ?>"
                                        data-slot-id="<?php echo $slotId; ?>"
                                        data-mechanic-id="<?php echo (int)$mechanicId; ?>"
                                        data-mechanic-name="<?php echo mobile_h($mechanicName); ?>"
                                        data-date="<?php echo mobile_h($dateInput); ?>"
                                        data-title="<?php echo mobile_h($title); ?>"
                                        data-description="<?php echo mobile_h($description); ?>"
                                        data-phone="<?php echo mobile_h($phone); ?>"
                                        data-vin="<?php echo mobile_h($vin); ?>"
                                        data-plate="<?php echo mobile_h($plate); ?>"
                                        data-slot-start="<?php echo mobile_h($slotStart); ?>"
                                        data-slot-end="<?php echo mobile_h($slotEnd); ?>"
                                        data-created-date="<?php echo mobile_h($createdDate); ?>"
                                        data-created-by="<?php echo mobile_h($createdBy); ?>"
                                        data-updated-date="<?php echo mobile_h($updatedDate); ?>"
                                        data-updated-by="<?php echo mobile_h($updatedBy); ?>"
                                        data-state="<?php echo $state; ?>"
                                        data-nip="<?php echo mobile_h($nip); ?>"
                                        data-receipt-or-invoice="<?php echo mobile_h($receiptOrInvoice); ?>"
                                        data-comment="<?php echo mobile_h($comment); ?>">

                                    <span class="reservation-time">
                                        <?php echo mobile_h($slotStart); ?>–<?php echo mobile_h($slotEnd); ?>
                                    </span>

                                    <span class="reservation-main">
                                        <strong><?php echo mobile_h($title); ?></strong>

                                        <?php if ($plate !== ''): ?>
                                            <span><?php echo mobile_h($plate); ?></span>
                                        <?php endif; ?>

                                        <?php if ($vin !== ''): ?>
                                            <span>VIN: <?php echo mobile_h($vin); ?></span>
                                        <?php endif; ?>
                                    </span>

                                    <span class="reservation-arrow">›</span>
                                </button>
                            <?php else: ?>
                                <button type="button"
                                        class="mobile-free-slot"
                                        data-mechanic-id="<?php echo (int)$mechanicId; ?>"
                                        data-mechanic-name="<?php echo mobile_h($mechanicName); ?>"
                                        data-date="<?php echo mobile_h($dateInput); ?>"
                                        data-time="<?php echo mobile_h($time); ?>">
                                    <span><?php echo mobile_h($time); ?></span>
                                    <span>＋ <?php echo mobile_h($lang_new_reservation_for); ?></span>
                                </button>
                            <?php endif; ?>

                        <?php endforeach; ?>

                    </div>
                </section>

            <?php endforeach; ?>

        </section>

    <?php endif; ?>

</main>

<!-- New reservation modal -->
<div id="reservationModal" class="mobile-modal-backdrop">
    <div class="mobile-modal">
        <button type="button" id="closeModal" class="mobile-modal-close">×</button>

        <h2>
            <?php echo mobile_h($lang_new_reservation_for); ?>
            <span id="resMechanicName"></span>
        </h2>

        <form method="post" action="backend/add_reservation.php">

            <input type="hidden" name="reservation[mechanic_id]" id="resMechanicId">

            <label>
                <?php echo mobile_h($lang_date); ?>
                <input type="date" name="reservation[slot_date]" id="resSlotDateInput" required>
            </label>

            <div class="mobile-form-row">
                <label>
                    <?php echo mobile_h($lang_time_start); ?>
                    <input type="time" name="reservation[slot_start]" id="resSlotStart" step="1800" required>
                </label>

                <label>
                    <?php echo mobile_h($lang_time_end); ?>
                    <input type="time" name="reservation[slot_time_end]" id="resSlotEnd" step="1800" required>
                </label>
            </div>

            <label>
                <?php echo mobile_h($lang_title); ?>
                <input type="text" name="reservation[title]" required>
            </label>

            <label>
                <?php echo mobile_h($lang_description); ?>
                <textarea name="reservation[reservation_description]" rows="3"></textarea>
            </label>

            <label>
                <?php echo mobile_h($lang_phone); ?>
                <input type="text" name="reservation[phone]">
            </label>

            <label>
                <?php echo mobile_h($lang_vin); ?>
                <input type="text"
                       name="reservation[vin]"
                       minlength="17"
                       maxlength="17"
                       pattern="[A-Za-z0-9]{17}">
            </label>

            <label>
                <?php echo mobile_h($lang_plate); ?>
                <input type="text" name="reservation[plate]">
            </label>

            <button type="submit" class="mobile-submit-button">
                <?php echo mobile_h($lang_save_reservation); ?>
            </button>

        </form>
    </div>
</div>

<!-- Edit reservation modal -->
<div id="editModal" class="mobile-modal-backdrop">
    <div class="mobile-modal">

        <button type="button" id="closeEditModal" class="mobile-modal-close">×</button>

        <h2>
            <?php echo mobile_h($lang_edit_reservation); ?>
            <a id="editReservationNumber" href="#" target="_blank"></a>
        </h2>

        <form method="post" action="backend/update_reservation.php" id="editReservationForm">

            <input type="hidden" name="reservation[slot_id]" id="editSlotId">
            <input type="hidden" name="reservation[nip]" id="editNip">
            <input type="hidden" name="reservation[receipt_or_invoice]" id="editReceiptOrInvoice">
            <input type="hidden" name="reservation[comment]" id="editComment">

            <label>
                <?php echo mobile_h($lang_date); ?>
                <input type="date" name="reservation[slot_date]" id="editSlotDate" required>
            </label>

            <label>
                <?php echo mobile_h($lang_slot_name); ?>
                <select name="reservation[mechanic_id]" id="editMechanicId">
                    <?php foreach ($mechanics as $mechanicId => $mechanicName): ?>
                        <option value="<?php echo (int)$mechanicId; ?>">
                            <?php echo mobile_h($mechanicName); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <div class="mobile-form-row">
                <label>
                    <?php echo mobile_h($lang_time_start); ?>
                    <input type="time"
                           name="reservation[slot_start]"
                           id="editSlotStart"
                           step="1800"
                           required>
                </label>

                <label>
                    <?php echo mobile_h($lang_time_end); ?>
                    <input type="time"
                           name="reservation[slot_time_end]"
                           id="editSlotEnd"
                           step="1800"
                           required>
                </label>
            </div>

            <label>
                <?php echo mobile_h($lang_title); ?>
                <input type="text" name="reservation[title]" id="editTitle" required>
            </label>

            <label>
                <?php echo mobile_h($lang_description); ?>
                <textarea name="reservation[reservation_description]" id="editDescription" rows="4"></textarea>
            </label>

            <label>
                <?php echo mobile_h($lang_phone); ?>
                <input type="text" name="reservation[phone]" id="editPhone">
            </label>

            <label>
                <?php echo mobile_h($lang_vin); ?>
                <input type="text"
                       name="reservation[vin]"
                       id="editVin"
                       minlength="17"
                       maxlength="17"
                       pattern="[A-Za-z0-9]{17}">
            </label>

            <label>
                <?php echo mobile_h($lang_plate); ?>
                <input type="text" name="reservation[plate]" id="editPlate">
            </label>

            <div class="mobile-cancelled-row">
                <span><?php echo mobile_h($lang_cancelled); ?></span>
                <label>
                    <input type="radio" name="reservation[state]" id="editStateTak" value="1">
                    <?php echo mobile_h($lang_yes); ?>
                </label>
                <label>
                    <input type="radio" name="reservation[state]" id="editStateNie" value="0">
                    <?php echo mobile_h($lang_no); ?>
                </label>
            </div>

            <div class="mobile-meta">
                <div>
                    <span><?php echo mobile_h($lang_creation_date); ?></span>
                    <strong id="editCreationDate">-</strong>
                </div>
                <div>
                    <span><?php echo mobile_h($lang_created_by); ?></span>
                    <strong id="editCreatedBy">-</strong>
                </div>
                <div>
                    <span><?php echo mobile_h($lang_updated_date); ?></span>
                    <strong id="editUpdatedDate">-</strong>
                </div>
                <div>
                    <span><?php echo mobile_h($lang_updated_by); ?></span>
                    <strong id="editUpdatedBy">-</strong>
                </div>
            </div>

            <input type="hidden" id="editReservationDate">
            <input type="hidden" id="editReservationStart">

            <div id="editButtons" class="mobile-edit-buttons">
                <button type="submit" class="mobile-submit-button">
                    <?php echo mobile_h($lang_save_changes); ?>
                </button>

                <button type="button" id="deleteBtn" class="mobile-delete-button">
                    <?php echo mobile_h($lang_delete); ?>
                </button>
            </div>

            <div id="editDisabledMessage" class="mobile-disabled-message"></div>

        </form>
    </div>
</div>

<script>
    const isAdmin = <?php echo ($user_system_permissions == 1 ? 'true' : 'false'); ?>;

    function esc(value) {
        return value == null ? '' : String(value);
    }

    function openReservationModal(button) {
        const mechanicId = button.dataset.mechanicId;
        const mechanicName = button.dataset.mechanicName;
        const date = button.dataset.date;
        const start = button.dataset.time;

        document.getElementById('resMechanicId').value = mechanicId;
        document.getElementById('resMechanicName').textContent = ' - ' + mechanicName;
        document.getElementById('resSlotDateInput').value = date;
        document.getElementById('resSlotStart').value = start;

        const endDate = new Date('2000-01-01T' + start + ':00');
        endDate.setMinutes(endDate.getMinutes() + 30);
        document.getElementById('resSlotEnd').value = endDate.toTimeString().substring(0, 5);

        document.getElementById('reservationModal').classList.add('is-open');
    }

    function updateEditModalActions(date, slotStart) {
        const editButtons = document.getElementById('editButtons');
        const disabledMessage = document.getElementById('editDisabledMessage');

        const reservationDateTime = new Date(date + 'T' + slotStart + ':00');
        const now = new Date();
        const editable = isAdmin || reservationDateTime > now;

        if (editable) {
            editButtons.style.display = 'grid';
            disabledMessage.style.display = 'none';
            disabledMessage.textContent = '';
        } else {
            editButtons.style.display = 'none';
            disabledMessage.style.display = 'block';
            disabledMessage.textContent = 'Minęła data rozpoczęcia i nie można edytować.';
        }
    }

    function openEditModal(button) {
        const d = button.dataset;

        document.getElementById('editSlotId').value = d.slotId;
        document.getElementById('editReservationNumber').textContent =
            'REQ' + String(d.slotId).padStart(7, '0');
        document.getElementById('editReservationNumber').href =
            'reservation_details.php?slot_id=' + encodeURIComponent(d.slotId);

        document.getElementById('editMechanicId').value = d.mechanicId;
        document.getElementById('editTitle').value = esc(d.title);
        document.getElementById('editDescription').value = esc(d.description);
        document.getElementById('editPhone').value = esc(d.phone);
        document.getElementById('editVin').value = esc(d.vin);
        document.getElementById('editPlate').value = esc(d.plate);
        document.getElementById('editSlotStart').value = d.slotStart;
        document.getElementById('editSlotDate').value = d.date;
        document.getElementById('editSlotEnd').value = d.slotEnd;

        document.getElementById('editCreationDate').textContent = d.createdDate || '-';
        document.getElementById('editCreatedBy').textContent = d.createdBy || '-';
        document.getElementById('editUpdatedDate').textContent = d.updatedDate || '-';
        document.getElementById('editUpdatedBy').textContent = d.updatedBy || '-';

        document.getElementById('editReservationDate').value = d.date;
        document.getElementById('editReservationStart').value = d.slotStart;
        document.getElementById('editNip').value = d.nip || '';
        document.getElementById('editReceiptOrInvoice').value = d.receiptOrInvoice || '0';
        document.getElementById('editComment').value = d.comment || '';

        if (d.state === '1') {
            document.getElementById('editStateTak').checked = true;
        } else {
            document.getElementById('editStateNie').checked = true;
        }

        updateEditModalActions(d.date, d.slotStart);

        document.getElementById('editModal').classList.add('is-open');
    }

    function closeModal(id) {
        document.getElementById(id).classList.remove('is-open');
    }

    document.querySelectorAll('.mobile-free-slot').forEach(button => {
        button.addEventListener('click', () => openReservationModal(button));
    });

    document.querySelectorAll('.mobile-reservation').forEach(button => {
        button.addEventListener('click', () => openEditModal(button));
    });

    document.getElementById('closeModal').addEventListener('click', () => {
        closeModal('reservationModal');
    });

    document.getElementById('closeEditModal').addEventListener('click', () => {
        closeModal('editModal');
    });

    document.querySelectorAll('.mobile-modal-backdrop').forEach(backdrop => {
        backdrop.addEventListener('click', event => {
            if (event.target === backdrop) {
                backdrop.classList.remove('is-open');
            }
        });
    });

    document.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
            document.querySelectorAll('.mobile-modal-backdrop.is-open').forEach(modal => {
                modal.classList.remove('is-open');
            });
        }
    });

    document.getElementById('deleteBtn').addEventListener('click', () => {
        if (!confirm('Jesteś pewny, że chcesz usunąć tę rezerwację?')) {
            return;
        }

        const slotId = document.getElementById('editSlotId').value;
        const form = document.createElement('form');

        form.method = 'post';
        form.action = 'backend/del_reservation.php';
        form.innerHTML =
            '<input type="hidden" name="delete_slot_id" value="' +
            encodeURIComponent(slotId) +
            '">';

        document.body.appendChild(form);
        form.submit();
    });

    function autoHideMessage(id) {
        const element = document.getElementById(id);

        if (!element) {
            return;
        }

        setTimeout(() => {
            element.classList.add('is-hidden');

            setTimeout(() => {
                element.remove();
            }, 400);
        }, 5000);
    }

    autoHideMessage('reservationError');
    autoHideMessage('reservationSuccess');
</script>

</body>
</html>
