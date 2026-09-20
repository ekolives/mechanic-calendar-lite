<?php
require_once __DIR__ . '/../sys-backend/session_config.php';
require_once __DIR__ . '/../sys-backend/db_connect.php';

if ($user_system_permissions != 1) {
    require_once __DIR__ . '/no_access.php';
    exit;
}


/** @var string $lang_mechanic */
/** @var string $lang_sort */
/** @var string $lang_color */
/** @var string $lang_work_start */
/** @var string $lang_work_end */
/** @var string $lang_change */

echo "<table>
    <tr>
        <th>$lang_mechanic</th>
        <th>$lang_sort </th>
        <th>$lang_color</th>
        <th>$lang_work_start</th>
        <th>$lang_work_end</th>
        <th>$lang_change <a href='add_worker.php' class='button-green'> + </a></th>
    </tr>";

   

    $result = $conn->query("
    SELECT mechanic_id, mechanic_name, mechanic_sort, color, work_start_time, work_end_time, font_color
    FROM mechanics
    ORDER BY mechanic_sort ASC, mechanic_name ASC
");

    if ($result === false) {
        echo "<tr><td colspan='2'>Wystąpił błąd podczas pobierania danych.</td></tr>";
    } else {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['mechanic_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['mechanic_sort']) . "</td>";
            echo "<td style='background: {$row['color']}; width: 120px; text-align:center;'>
        <span style='color: {$row['font_color']}; font-weight:bold;'>text</span>
      </td>";

            

            echo "<td>" . htmlspecialchars($row['work_start_time']) . "</td>";
            echo "<td>" . htmlspecialchars($row['work_end_time']) . "</td>";
            echo "<td><a href='edit_worker.php?id=" . urlencode($row['mechanic_id']) . "' class='button'>$lang_change</a></td>";
            echo "</tr>";
        }
    }

    ?>

</table>
