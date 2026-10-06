<?php
require_once __DIR__ . '/../sys-backend/session_config.php';
require_once __DIR__ . '/../sys-backend/db_connect.php';

if ($user_system_permissions != 1) {
    require_once __DIR__ . '/no_access.php';
    exit;
}
/** @var string $lang_change */
?>

<table>
    <tr>
        <th>Sort</th>
        <th>Nazwa</th>
        <th>Wartość </th>
        <th>Action <a href="#" class="button">+</a></th>
    </tr>

    <?php
    $result = $conn->query("
    SELECT *
    FROM settings
    ORDER BY setting_sort ASC
    ");

    if ($result === false) {
        echo "<tr><td colspan='4'>Wystąpił błąd podczas pobierania danych.</td></tr>";
    } else {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['setting_sort']) . "</td>";
            echo "<td>" . htmlspecialchars($row['setting_name']) . "</td>";
            echo "<td>" . htmlspecialchars($row['setting_values']) . "</td>";
                        
            echo "<td><a href=\"edit_value.php?id=" . $row['sys_id'] . "\" class=\"button\">$lang_change</a></td>";
            echo "</tr>";
        }
    }
    ?>
</table>
