<?php
require_once __DIR__ . '/../sys-backend/session_config.php';
require_once __DIR__ . '/../sys-backend/db_connect.php';

/** @var mysqli $conn */
/** @var string $userName */

if (!isset($_GET['slot_id'])) {
    die("Brak ID rezerwacji");
}

$reservationId = (int)$_GET['slot_id'];

$stmt = $conn->prepare("SELECT * FROM calendar_slots WHERE slot_id = ?");
$stmt->bind_param("i", $reservationId);
$stmt->execute();
$slot = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$slot || !user_can_access_mechanic($conn, $userId, $slot['mechanic_id'])) {
    // ten sam komunikat dla "brak" i "brak dostępu" - nie ujawniamy, czy rezerwacja istnieje
    http_response_code(404);
    die("Nie znaleziono rezerwacji");
}

$h = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

$reservationNumber = 'REQ' . str_pad($slot['slot_id'], 7, '0', STR_PAD_LEFT);

// kto utworzył / zmienił rezerwację
$userLogin = static function (mysqli $conn, $id): string {
    $stmt = $conn->prepare("SELECT user_login FROM users WHERE user_id = ?");
    $id = (int)$id;
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row['user_login'] ?? '-';
};
$submiterName = $userLogin($conn, $slot['sys_submiter']);
$updaterName = $userLogin($conn, $slot['sys_updatedby']);

$mechStmt = $conn->prepare("SELECT m.mechanic_id, m.mechanic_name FROM mechanics m
    INNER JOIN permissions p ON p.mechanic_id = m.mechanic_id AND p.user_id = ? AND p.status = 1
    GROUP BY m.mechanic_id, m.mechanic_name ORDER BY m.mechanic_name");
$mechStmt->bind_param('i', $userId);
$mechStmt->execute();
$mechanicsResult = $mechStmt->get_result();

ob_start();
include __DIR__ . '/../sys-backend/lang.php';
$languageControls = ob_get_clean();

$receipt = (int)($slot['receipt_or_invoice'] ?? 0);
$state = (int)$slot['reservation_state'];
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../newstyle.css?v=<?php echo filemtime(__DIR__ . '/../newstyle.css'); ?>" />
    <title><?php echo $h($reservationNumber); ?> - <?php echo $h($lang_edit_reservation ?? 'Edycja rezerwacji'); ?></title>
</head>

<body>

    <div id="header" class="calendar-header">
        <div class="calendar-toolbar">
            <div class="calendar-toolbar-row calendar-toolbar-primary">
                <h3 class="desktop-toolbar-title">
                    <span class="brand-name"><?php echo $h($lang_WebTitle ?? 'Kalendarz'); ?></span>
                    <span class="brand-user"><?php echo $h($userName); ?></span>
                </h3>

                <div class="desktop-toolbar-actions">
                    <a href="reservation_list.php" class="button desktop-toolbar-external-link">← <?php echo $h($lang_reservation_list ?? 'Lista rezerwacji'); ?></a>
                    <a href="index.php" class="button desktop-toolbar-external-link"><?php echo $h($lang_calendar ?? 'Kalendarz'); ?></a>
                </div>

                <div class="calendar-header-tools">
                    <?php echo $languageControls; ?>
                </div>
            </div>
        </div>
    </div>

    <div id="wrapper">
        <div id="content">
            <div class="settings-page details-page">

                <?php if (!empty($_SESSION['reservation_error'])): ?>
                    <div class="settings-alert settings-alert-error" role="alert">
                        <?php
                        echo $h($_SESSION['reservation_error']);
                        unset($_SESSION['reservation_error']);
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($_SESSION['reservation_success'])): ?>
                    <div class="settings-alert settings-alert-success" role="status">
                        <?php
                        echo $h($_SESSION['reservation_success']);
                        unset($_SESSION['reservation_success']);
                        ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="backend/update_reservation.php" class="settings-form">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="reservation[slot_id]" value="<?php echo (int)$slot['slot_id']; ?>">

                    <section class="settings-card">
                        <div class="settings-card-head">
                            <h2><?php echo $h($lang_edit_reservation ?? 'Edycja rezerwacji'); ?> <?php echo $h($reservationNumber); ?></h2>
                        </div>

                        <div class="details-columns">
                            <!-- lewa kolumna: termin -->
                            <div class="settings-grid details-col">
                                <div class="settings-field settings-field-wide">
                                    <label for="slot_number"><?php echo $h($lang_reservation_number ?? 'Numer rezerwacji'); ?></label>
                                    <input id="slot_number" type="text" name="reservation[slot_number]" value="<?php echo $h($reservationNumber); ?>" readonly>
                                </div>

                                <div class="settings-field settings-field-wide">
                                    <label for="slot_date"><?php echo $h($lang_date ?? 'Data'); ?></label>
                                    <input id="slot_date" type="date" name="reservation[slot_date]" value="<?php echo $h($slot['slot_date']); ?>" required>
                                </div>

                                <div class="settings-field">
                                    <label for="slot_start"><?php echo $h($lang_time_start ?? 'Godzina start'); ?></label>
                                    <input id="slot_start" type="time" name="reservation[slot_start]" value="<?php echo $h(substr($slot['slot_time_start'], 0, 5)); ?>" required>
                                </div>

                                <div class="settings-field">
                                    <label for="slot_end"><?php echo $h($lang_time_end ?? 'Godzina koniec'); ?></label>
                                    <input id="slot_end" type="time" name="reservation[slot_time_end]" value="<?php echo $h(substr($slot['slot_time_end'], 0, 5)); ?>" required>
                                </div>

                                <div class="settings-field settings-field-wide">
                                    <label for="mechanic_id"><?php echo $h($lang_mechanic ?? 'Mechanik'); ?></label>
                                    <select id="mechanic_id" name="reservation[mechanic_id]" required>
                                        <option value=""><?php echo $h($lang_choose_mechanic ?? '-- wybierz mechanika --'); ?></option>
                                        <?php if ($mechanicsResult): ?>
                                            <?php while ($m = $mechanicsResult->fetch_assoc()): ?>
                                                <option value="<?php echo (int)$m['mechanic_id']; ?>" <?php echo ((int)$m['mechanic_id'] === (int)$slot['mechanic_id']) ? 'selected' : ''; ?>>
                                                    <?php echo $h($m['mechanic_name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>

                                <div class="settings-field settings-field-wide">
                                    <label for="nip">NIP</label>
                                    <input id="nip" type="text" name="reservation[nip]" value="<?php echo $h($slot['nip'] ?? ''); ?>">
                                    <div class="details-radios">
                                        <label class="settings-check">
                                            <input type="radio" name="reservation[receipt_or_invoice]" value="0" <?php echo $receipt === 0 ? 'checked' : ''; ?>>
                                            <span><?php echo $h($lang_receipt ?? 'Paragon'); ?></span>
                                        </label>
                                        <label class="settings-check">
                                            <input type="radio" name="reservation[receipt_or_invoice]" value="1" <?php echo $receipt === 1 ? 'checked' : ''; ?>>
                                            <span><?php echo $h($lang_invoice ?? 'Faktura'); ?></span>
                                        </label>
                                    </div>
                                </div>

                                <div class="settings-field settings-field-wide">
                                    <label for="state"><?php echo $h($lang_status ?? 'Status'); ?></label>
                                    <select id="state" name="reservation[state]">
                                        <option value="0" <?php echo $state === 0 ? 'selected' : ''; ?>><?php echo $h($lang_state_active ?? 'Aktywna'); ?></option>
                                        <option value="1" <?php echo $state === 1 ? 'selected' : ''; ?>><?php echo $h($lang_state_cancelled ?? 'Anulowana'); ?></option>
                                        <option value="2" <?php echo $state === 2 ? 'selected' : ''; ?>><?php echo $h($lang_state_finished ?? 'Zakończona'); ?></option>
                                    </select>
                                </div>
                            </div>

                            <!-- prawa kolumna: opis i dane pojazdu -->
                            <div class="settings-grid details-col">
                                <div class="settings-field settings-field-wide">
                                    <label for="title"><?php echo $h($lang_title ?? 'Tytuł'); ?></label>
                                    <input id="title" type="text" name="reservation[title]" value="<?php echo $h($slot['reservation_title']); ?>">
                                </div>

                                <div class="settings-field settings-field-wide">
                                    <label for="description"><?php echo $h($lang_description ?? 'Opis'); ?></label>
                                    <textarea id="description" name="reservation[reservation_description]" rows="5"><?php echo $h($slot['reservation_description']); ?></textarea>
                                </div>

                                <div class="settings-field settings-field-wide">
                                    <label for="phone"><?php echo $h($lang_phone ?? 'Telefon'); ?></label>
                                    <input id="phone" type="text" name="reservation[phone]" value="<?php echo $h($slot['reservation_phone']); ?>">
                                </div>

                                <div class="settings-field settings-field-wide">
                                    <label for="vin"><?php echo $h($lang_vin ?? 'VIN'); ?></label>
                                    <input id="vin" type="text" name="reservation[vin]" value="<?php echo $h($slot['reservation_vin']); ?>" maxlength="17">
                                </div>

                                <div class="settings-field">
                                    <label for="plate"><?php echo $h($lang_plate ?? 'Rejestracja'); ?></label>
                                    <input id="plate" type="text" name="reservation[plate]" value="<?php echo $h($slot['reservation_plate']); ?>">
                                </div>

                                <div class="settings-field">
                                    <label for="odometer"><?php echo $h($lang_odometer ?? 'Przebieg auta'); ?></label>
                                    <input id="odometer" type="text" name="reservation[odometer]" value="<?php echo $h($slot['odometer'] ?? ''); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="settings-field details-comment">
                            <label for="comment"><?php echo $h($lang_mechanic_comment ?? 'Komentarz mechanika'); ?></label>
                            <textarea id="comment" name="reservation[comment]" rows="3"><?php echo $h($slot['comment'] ?? ''); ?></textarea>
                        </div>

                        <div class="settings-form-actions settings-form-actions-split">
                            <a href="reservation_list.php" class="settings-btn"><?php echo $h($lang_back_to_list ?? 'Powrót do listy'); ?></a>
                            <button type="submit" class="settings-btn settings-btn-primary"><?php echo $h($lang_save_changes ?? 'Zapisz zmiany'); ?></button>
                        </div>
                    </section>
                </form>

                <div class="details-meta">
                    <div><?php echo $h($lang_created ?? 'Utworzono'); ?>: <strong><?php echo $h($slot['sys_createdate']); ?></strong> <?php echo $h($lang_by ?? 'przez'); ?> <strong><?php echo $h($submiterName); ?></strong></div>
                    <div><?php echo $h($lang_last_update ?? 'Ostatnia aktualizacja'); ?>: <strong><?php echo $h($slot['sys_updatedate']); ?></strong> <?php echo $h($lang_by ?? 'przez'); ?> <strong><?php echo $h($updaterName); ?></strong></div>
                </div>

            </div>
        </div>
    </div>

</body>

</html>
