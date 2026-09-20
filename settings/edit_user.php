<?php
require_once __DIR__ . '/../sys-backend/session_config.php';
require_once __DIR__ . '/../sys-backend/db_connect.php';

if ($user_system_permissions != 1) {
    require_once __DIR__ . '/no_access.php';
    exit;
}

$workerId = $_GET['id'] ?? null;
if (!$workerId) {
    die("Nie podano ID pracownika.");
}
$stmt = $conn->prepare("SELECT user_id, user_login , user_permissions, user_status FROM users WHERE user_id = ?");
$stmt->bind_param("i", $workerId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("Nie znaleziono pracownika o podanym ID.");
}


$worker = $result->fetch_assoc();
$result->free();
$stmt->close();
?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl" lang="pl">

<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../newstyle.css" />
    <title>Kalendarz</title>

</head>

<body>

    <div id="header">
        <?php include "../sys-backend/lang.php"; ?>
        <div id="logo">
            <h3>Kalendarz</h3>
        </div>
    </div>

    <div id="wrapper">
        <div id="content">
            <h1>Edytuj użytkownika</h1>

            <table>
                <form method="post" action="backend/update_user.php">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($worker['user_id']); ?>">
                    <tr>
                        <td><label>Login:</label></td>
                        <td>
                            <input type="text" name="user_login" value="<?php echo htmlspecialchars($worker['user_login']); ?>">
                        </td>
                    </tr>

                    <tr>
                        <td><label>Hasło:</label></td>
                        <td>
                            <input type="password" name="user_password">
                        </td>
                    </tr>


                    <tr>
                        <td><label>Typ:</label></td>
                        <td>
                            <select name="permissions">
                                <option value="1" <?php echo $worker['user_permissions'] ? 'selected' : ''; ?>>Administrator</option>
                                <option value="2" <?php echo $worker['user_permissions'] === 2 ? 'selected' : ''; ?>>Użytkownik</option>
                                <option value="3" <?php echo $worker['user_permissions'] === 3 ? 'selected' : ''; ?>>Współpracownik</option>
                            </select>
                        </td>
                    </tr>


<tr>
    <td><label>Uprawnienia do:</label></td>
    <td>
        <div class="mechanics-list">
            <?php
            $stmt = $conn->prepare("SELECT mechanic_id, mechanic_name FROM mechanics");
            $stmt->execute();
            $mechanics = $stmt->get_result();
            $stmt->close();

            while ($mechanic = $mechanics->fetch_assoc()) {

                $mechanicid = $mechanic['mechanic_id'];
                $mechanic_name = $mechanic['mechanic_name'];

                $stmt = $conn->prepare("
                    SELECT user_id, mechanic_id, status
                    FROM permissions
                    WHERE user_id = ? AND mechanic_id = ?
                ");
                $stmt->bind_param("ii", $workerId, $mechanicid);
                $stmt->execute();
                $result = $stmt->get_result();
                $stmt->close();

                $checked = $result->num_rows > 0 ? 'checked' : '';

                echo '
                <label class="mechanic-item">
                    <input type="checkbox" name="mechanics[]" value="' . $mechanicid . '" ' . $checked . '>
                    ' . htmlspecialchars($mechanic_name) . '
                </label>';
            }

            $mechanics->free();
            ?>
        </div>
    </td>
</tr>


                    <tr>
                        <td><label>Status:</label></td>
                        <td>
                            <select name="status">
                                <option value="0" <?php echo !$worker['user_status'] ? 'selected' : ''; ?>>Nieaktywny</option>
                                <option value="1" <?php echo $worker['user_status'] ? 'selected' : ''; ?>>Aktywny</option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <td><a href="index.php" class="button">Powrót</a></td>
                        <td><input type="submit" value="Zapisz" class="button-orange"></td>

                    </tr>
            </table>
            </form>