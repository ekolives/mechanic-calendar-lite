<?php
require_once __DIR__ . '/../sys-backend/session_config.php';
require_once __DIR__ . '/../sys-backend/db_connect.php';

if ($user_system_permissions != 1) {
    require_once __DIR__ . '/no_access.php';
    exit;
}

$translationId = $_GET['id'] ?? null;
if (!$translationId) {
    die("Nie podano ID tłumaczenia.");
}

$stmt = $conn->prepare("SELECT * FROM settings WHERE sys_id = ?");
$stmt->bind_param("i", $translationId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("Nie znaleziono tłumaczenia o podanym ID.");
}
$translation = $result->fetch_assoc();
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
            <h1>Edytuj Zmienną</h1>
            <form action="backend/update_value.php" method="post">
                <input type="hidden" name="id" value="<?php echo $translation['sys_id']; ?>">
                 <label for="setting_sort">Sortowanie:</label>
                <input type="text" name="setting_sort" id="setting_sort" value="<?php echo $translation['setting_sort']; ?>">

                <label for="setting_name">Nazwa:</label>
                <input type="text" name="setting_name" id="setting_name" value="<?php echo $translation['setting_name']; ?>" readonly>

                <label for="setting_values">Wartość:</label>
                <input type="text" name="setting_values" id="setting_values" value="<?php echo $translation['setting_values']; ?>">

                <label for="setting_status">Status:</label>
                <select name="setting_status" id="setting_status">
                    <option value="0" <?php echo $translation['status'] == 0 ? 'selected' : ''; ?>>Nieaktywny</option>
                    <option value="1" <?php echo $translation['status'] == 1 ? 'selected' : ''; ?>>Aktywny</option>
                </select>


                <input type="submit" value="Zapisz">
            </form>
        </div>
    </div>