<?php
require_once __DIR__ . '/../sys-backend/session_config.php';
require_once __DIR__ . '/../sys-backend/db_connect.php';

/** @var mysqli $conn */

if (!isset($_GET['slot_id'])) {
    die("Brak ID rezerwacji");
}

$reservationId = (int)$_GET['slot_id'];

$stmt = $conn->prepare("SELECT * FROM calendar_slots WHERE slot_id = ?");
$stmt->bind_param("i", $reservationId);
$stmt->execute();
$slot = $stmt->get_result()->fetch_assoc();

if (!$slot) {
    die("Nie znaleziono rezerwacji");
}

$reservationNumber = 'REQ' . str_pad($slot['slot_id'], 7, '0', STR_PAD_LEFT);


?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="utf-8" />
    <link rel="stylesheet" href="../newstyle.css" />
</head>

<body>

    <div id="header">
        <?php include "../sys-backend/lang.php"; ?>
        <div id="logo">
            <h3>Edycja rezerwacji</h3>
        </div>
    </div>

    <div id="wrapper">
        <div id="content">

            <?php if (!empty($_SESSION['reservation_error'])): ?>
                <div style="color:red; font-weight:bold;">
                    <?php
                    echo $_SESSION['reservation_error'];
                    unset($_SESSION['reservation_error']);
                    ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($_SESSION['reservation_success'])): ?>
                <div style="color:green; font-weight:bold;">
                    <?php
                    echo $_SESSION['reservation_success'];
                    unset($_SESSION['reservation_success']);
                    ?>
                </div>
            <?php endif; ?>




            <form method="post" action="backend/update_reservation.php">

                <input type="hidden" name="reservation[slot_id]" value="<?php echo $slot['slot_id']; ?>">

                <table>

                    <tr>
                        <th>Numer rezerwacji</th>
                        <td>
                            <input type="text" name="reservation[slot_id]" value="<?php echo $reservationNumber; ?>" readonly>
                        </td>
                    </tr>



                    <tr>
                        <th>Data</th>
                        <td>
                            <input type="date" name="reservation[slot_date]"
                                value="<?php echo $slot['slot_date']; ?>" required>
                        </td>
                    </tr>

                    <tr>
                        <th>Godzina start</th>
                        <td>
                            <input type="time" name="reservation[slot_start]"
                                value="<?php echo substr($slot['slot_time_start'], 0, 5); ?>" required>
                        </td>
                    </tr>

                    <tr>
                        <th>Godzina koniec</th>
                        <td>
                            <input type="time" name="reservation[slot_time_end]"
                                value="<?php echo substr($slot['slot_time_end'], 0, 5); ?>" required>
                        </td>
                    </tr>


                    <tr>
                        <th>Tytuł</th>
                        <td>
                            <input type="text" name="reservation[title]"
                                value="<?php echo $slot['reservation_title']; ?>">
                        </td>
                    </tr>

                    <tr>
                        <th>Opis</th>
                        <td>
                            <textarea name="reservation[reservation_description]" rows="4"><?php echo $slot['reservation_description']; ?></textarea>
                        </td>
                    </tr>

                    <tr>
                        <th>Telefon</th>
                        <td>
                            <input type="text" name="reservation[phone]"
                                value="<?php echo $slot['reservation_phone']; ?>">
                        </td>
                    </tr>

                    <tr>
                        <th>VIN</th>
                        <td>
                            <input type="text" name="reservation[vin]"
                                value="<?php echo $slot['reservation_vin']; ?>">
                        </td>
                    </tr>

                    <tr>
                        <th>Rejestracja</th>
                        <td>
                            <input type="text" name="reservation[plate]"
                                value="<?php echo $slot['reservation_plate']; ?>">
                        </td>
                    </tr>

                    <tr>
                        <th>Status</th>
                        <td>
                            <select name="reservation[state]">
                                <option value="0" <?php if ($slot['reservation_state'] == 0) echo 'selected'; ?>>Aktywna</option>
                                <option value="1" <?php if ($slot['reservation_state'] == 1) echo 'selected'; ?>>Anulowana</option>
                                <option value="2" <?php if ($slot['reservation_state'] == 2) echo 'selected'; ?>>Zakończona</option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th>Mechanik</th>
                        <td>
                            <select name="reservation[mechanic_id]" required>
                                <option value="">-- wybierz mechanika --</option>

                                <?php
                                $mechStmt = $conn->query("SELECT mechanic_id, mechanic_name FROM mechanics ORDER BY mechanic_name");
                                while ($m = $mechStmt->fetch_assoc()) {
                                    $sel = ($m['mechanic_id'] == $slot['mechanic_id']) ? 'selected' : '';
                                    echo "<option value='{$m['mechanic_id']}' $sel>{$m['mechanic_name']}</option>";
                                }
                                ?>
                            </select>
                        </td>
                    </tr>



                    <td><a href="reservation_list.php" class="button">Powrót do Listy</a></td>
                

                    <td><button type="submit" class="button-green">Zapisz zmiany</button>
                <a href="index.php" class="button">Powrót do Kalendarza</a></td>
                </table>
            </form>

            <small>Utworzono: <?php echo $slot['sys_createdate']; ?></small><br>
            <?php
            // get info who created and updated
            $sys_submiter = $slot['sys_submiter'];
            $stmt = $conn->prepare("SELECT * 
                    FROM users 
                    WHERE user_id = ?");
            $stmt->bind_param("i", $sys_submiter);
            $stmt->execute();
            $submiter = $stmt->get_result();
            $submiter = $submiter->fetch_assoc();
            $submiter_name = $submiter['user_login'];

            echo "<small>Utworzony przez: $submiter_name</small><br>";
            ?>

            <small>Ostatnia aktualizacja: <?php echo $slot['sys_updatedate']; ?></small><br>


            <?php
            // get info who  updated
            $sys_updater = $slot['sys_updatedby'];
            $stmt = $conn->prepare("SELECT * 
                    FROM users 
                    WHERE user_id = ?");
            $stmt->bind_param("i", $sys_updater);
            $stmt->execute();
            $updater = $stmt->get_result();
            $updater = $updater->fetch_assoc();
            $updater_name = $updater['user_login'];

            echo "<small>Utworzony przez: $updater_name</small><br>";
            ?>

        </div>
    </div>

</body>

</html>