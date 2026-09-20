<?php
require_once __DIR__ . '/../sys-backend/session_config.php';
require_once __DIR__ . '/../sys-backend/db_connect.php';

// last mont date definition
$date_from = new DateTime();
$date_from->modify('-1 month');
$date_to = new DateTime();
$date_to->modify('+1 month');

if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $date_from_input = DateTime::createFromFormat('Y-m-d', $_GET['date_from']);
    if ($date_from_input) {
        $date_from = $date_from_input;
    }
}
if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $date_to_input = DateTime::createFromFormat('Y-m-d', $_GET['date_to']);
    if ($date_to_input) {
        $date_to = $date_to_input;
    }
}
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
        <?php include "../sys-backend/lang.php"; ?>
        <div id="logo">
            <h3>Lista rezerwacji</h3>
        </div>
    </div>
    <div id="wrapper">
        <form method="get" action="reservation_list.php">
            <table>
                <tr>
                    <th>Slot</th>

                    <td>
<?php
// get available slots from SELECT * FROM `mechanics`
$stmt = $conn->prepare("SELECT mechanic_id, mechanic_name FROM mechanics");
$stmt->execute();
$mechanics = $stmt->get_result();
$stmt->close();

// ostatnio wybrany mechanik
$selectedMechanic = isset($_GET['slot_id']) ? $_GET['slot_id'] : '';

echo '<select name="slot_id">';
echo '<option value="">Wszyscy</option>';

while ($mechanic = $mechanics->fetch_assoc()) {

    $isSelected = ($selectedMechanic == $mechanic['mechanic_id']) ? 'selected' : '';

    echo '<option value="' . $mechanic['mechanic_id'] . '" ' . $isSelected . '>'
        . $mechanic['mechanic_name'] .
        '</option>';
}

echo '</select>';
?>

                    </td>

                    <th> Data od </th>
                    <td><input type="date" name="date_from" value="<?php echo $date_from->format('Y-m-d'); ?>"></td>
                    <th> Data do </th>
                    <td><input type="date" name="date_to" value="<?php echo $date_to->format('Y-m-d'); ?>"></td>
                    <td><button class="button-green">Filtruj</button></td>
                    <td style="text-align: center;"><a href="reservation_list.php" class="button-red">Wyczyść filtry</a></td>
                    <td><a href="index.php" class="button">Powrót do Kalendarza</a></td>
                    <td></td>
                </tr>

                <tr>
                    <th>Telefon</th>
                    <td><input type="text" name="reservation_phone" value="<?php echo htmlspecialchars($_GET['reservation_phone'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" /></td>
                    <th>VIN</th>
                    <td><input type="text" name="reservation_vin" value="<?php echo htmlspecialchars($_GET['reservation_vin'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" /></td>
                    <th>Rejestracja</th>
                    <td><input type="text" name="reservation_plate" value="<?php echo htmlspecialchars($_GET['reservation_plate'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" /></td>
                    <th>Odwołana?</th>
                    <td style="text-align: center; "><input type="checkbox" name="reservation_rejected" value="1" <?php echo isset($_GET['reservation_rejected']) ? 'checked' : ''; ?>>Tak</td>
                    </td>

                </tr>
            </table>


            <div id="content">

                <table>
                    <tr>
                        <th>Data</th>
                        <th>Nazwa Slotu</th>
                        <th>Start</th>
                        <th style="white-space:nowrap;">Koniec</th>
                        <th>Tytuł</th>
                        <th>Opis</th>
                        <th style="white-space:nowrap;">Telefon</th>
                        <th style="white-space:nowrap;">VIN</th>
                        <th style="white-space:nowrap;">Rejestracja</th>
                    </tr>
                    <?php


                    $date_from_val = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
                    $date_to_val = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
                    $reservation_phone = isset($_GET['reservation_phone']) ? trim($_GET['reservation_phone']) : '';
                    $reservation_vin = isset($_GET['reservation_vin']) ? trim($_GET['reservation_vin']) : '';
                    $reservation_plate = isset($_GET['reservation_plate']) ? trim($_GET['reservation_plate']) : '';
                    $reservation_rejected = isset($_GET['reservation_rejected']) ? trim($_GET['reservation_rejected']) : '';
                    $slot_id = isset($_GET['slot_id']) ? trim($_GET['slot_id']) : '';


                    $conditions = [];
                    if ($date_from_val && $date_to_val) {
                        $from_safe = $conn->real_escape_string($date_from_val);
                        $to_safe = $conn->real_escape_string($date_to_val);
                        $conditions[] = "slot_date BETWEEN '$from_safe' AND '$to_safe'";
                    } elseif ($date_from_val) {
                        $from_safe = $conn->real_escape_string($date_from_val);
                        $conditions[] = "slot_date >= '$from_safe'";
                    } elseif ($date_to_val) {
                        $to_safe = $conn->real_escape_string($date_to_val);
                        $conditions[] = "slot_date <= '$to_safe'";
                    }

                    if ($reservation_phone !== '') {
                        $phone_safe = $conn->real_escape_string($reservation_phone);
                        $conditions[] = "reservation_phone LIKE '%$phone_safe%'";
                    }
                    if ($reservation_vin !== '') {
                        $vin_safe = $conn->real_escape_string($reservation_vin);
                        $conditions[] = "`calendar_slots`.`reservation_vin` LIKE '%$vin_safe%'";
                    }
                    if ($reservation_plate !== '') {
                        $plate_safe = $conn->real_escape_string($reservation_plate);
                        $conditions[] = "reservation_plate LIKE '%$plate_safe%'";
                    }

                    if ($reservation_rejected === '1') {
                        $conditions[] = "reservation_state = 1";
                    }

                    if ($slot_id !== '') {
                        $conditions[] = "`calendar_slots`.`mechanic_id` = '$slot_id'";
                    }



                    // PAGINACJA
                    $limit = 10;
                    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
                    $offset = ($page - 1) * $limit;

                    // policz wszystkie rekordy
                    $sql_count = "SELECT COUNT(*) AS total FROM calendar_slots";
                    if (!empty($conditions)) {
                        $sql_count .= " WHERE " . implode(' AND ', $conditions);
                    }
                    $result_count = $conn->query($sql_count);
                    $total_rows = $result_count->fetch_assoc()['total'];
                    $total_pages = ceil($total_rows / $limit);


                    $sql = "SELECT  *
                    FROM calendar_slots
                    LEFT JOIN mechanics
                    ON calendar_slots.mechanic_id = mechanics.mechanic_id";




                    if (!empty($conditions)) {
                        $sql .= " WHERE " . implode(' AND ', $conditions);
                    }
                    $sql .= " ORDER BY slot_date DESC LIMIT $limit OFFSET $offset";


                    $result = $conn->query($sql);
                    while ($row = $result->fetch_assoc()) {
                        $slot_id = $row['slot_id'];
                        $mechanic_id = $row['mechanic_id'];
                        $mechanic_name = $row['mechanic_name'];
                        $slot_date = $row['slot_date'];
                        $slot_time = $row['slot_time_start'];
                        $slot_end = $row['slot_time_end'];
                        $reservation_title = $row['reservation_title'];
                        $reservation_description = $row['reservation_description'];
                        $reservation_phone = $row['reservation_phone'];
                        $reservation_vin = $row['reservation_vin'];
                        $reservation_plate = $row['reservation_plate'];




                        echo "<tr>
        <td><a href='reservation_details.php?slot_id=" . $slot_id . "' class='button'>" . $slot_date . "</a></td>
        <td>" . htmlspecialchars($mechanic_name) . "</td>
        <td>" . substr($slot_time, 0, 5) . "</td>
        <td>" . substr($slot_end, 0, 5) . "</td>
        <td>" . htmlspecialchars($reservation_title) . "</td>
        <td>" . htmlspecialchars($reservation_description) . "</td>
        <td>" . htmlspecialchars($reservation_phone) . "</td>
        <td>" . htmlspecialchars($reservation_vin) . "</td>
        <td>" . htmlspecialchars($reservation_plate) . "</td>
    </tr>";
                    }
                    ?>
                </table>


                <?php
                // generowanie linków paginacji z zachowaniem filtrów
                $query_string = $_GET;
                unset($query_string['page']); // usuwamy page, dodamy nowe

                function build_page_link($page, $query_string)
                {
                    $query_string['page'] = $page;
                    return "reservation_list.php?" . http_build_query($query_string);
                }

                echo '<div style="margin-top:20px; text-align:center; font-size:16px;">';

                // Pierwsza
                if ($page > 1) {
                    echo '<a href="' . build_page_link(1, $query_string) . '" class="button">Pierwsza</a> - ';
                }

                // numery stron
                $start = max(1, $page - 3);
                $end = min($total_pages, $page + 3);

                if ($start > 1) echo "... ";

                for ($i = $start; $i <= $end; $i++) {
                    if ($i == $page) {
                        echo "<strong>$i</strong> ";
                    } else {
                        echo '<a href="' . build_page_link($i, $query_string) . '" class="button">' . $i . '</a> ';
                    }
                }

                if ($end < $total_pages) echo "... ";

                // Ostatnia
                if ($page < $total_pages) {
                    echo '- <a href="' . build_page_link($total_pages, $query_string) . '" class="button">Ostatnia</a>';
                }

                echo '</div>';
                ?>



            </div>
    </div>
    <div id="footer">
        <p>ekolives &copy; <?php echo date("Y"); ?> Kalendarz Rezerwacji</p>
    </div>
</body>