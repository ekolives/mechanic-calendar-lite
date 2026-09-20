<?php
require_once __DIR__ . '/../sys-backend/session_config.php';
require_once __DIR__ . '/../sys-backend/db_connect.php';

if ($user_system_permissions != 1) {
    require_once __DIR__ . '/no_access.php';
    exit;
}

/** @var string $lang_LoginName */
/** @var string $lang_permissions */
/** @var string $lang_status */
/** @var string $lang_action */
/** @var string $lang_change */

/** @var string $lang_admin */
/** @var string $lang_user */
/** @var string $lang_collaborator */
/** @var string $lang_acive */
/** @var string $lang_inacive */


echo "
<table>
    <tr>
        <th>$lang_LoginName</th>
        <th>$lang_permissions </th>
        <th>$lang_status</th>
        <th>$lang_action <a href='add_user.php' class='button-green'> + </a></th>
    </tr>
";

    $result = $conn->query("
    SELECT user_id, user_login , user_permissions, user_status
    FROM users
    ORDER BY user_id ASC
");

    if ($result === false) {
        echo "<tr><td colspan='2'>Wystąpił błąd podczas pobierania danych.</td></tr>";
    } else {
        while ($row = $result->fetch_assoc()) {
            $user_permissions = $row['user_permissions'];

            switch ($user_permissions) {
                case 1:
                    $permissionsText = $lang_admin;
                    break;
                case 2:
                    $permissionsText = $lang_user;
                    break;
                default:
                    $permissionsText = $lang_collaborator;
            }

            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['user_login']) . "</td>";
            echo "<td>" . htmlspecialchars($permissionsText) . "</td>";
            echo "<td>" . ($row['user_status'] ? $lang_acive : $lang_inacive) . "</td>";
            echo "<td><a href='edit_user.php?id=" . urlencode($row['user_id']) . "' class='button'>$lang_change</a></td>";
            echo "</tr>";
        }
    }

    ?>
</table>