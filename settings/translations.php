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
        <th>Key</th>
        <th>Locale</th>
        <th>Translation </th>
        <th>Action <a href="add_translation.php" class="button">+</a></th>
    </tr>

    <?php
    $result = $conn->query("
    SELECT *
    FROM language
    ORDER BY sys ASC
    ");

    if ($result === false) {
        echo "<tr><td colspan='2'>Wystąpił błąd podczas pobierania danych.</td></tr>";
    } else {
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['sys']) . "</td>";
            echo "<td>" . htmlspecialchars($row['locale']) . "</td>";
            echo "<td>" . htmlspecialchars($row['name']) . "</td>";
            echo "<td><a href=\"edit_translation.php?id=" . $row['id'] . "\" class=\"button\">$lang_change</a></td>";
            echo "</tr>";
        }
    }
    ?>
