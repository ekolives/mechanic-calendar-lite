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
$stmt = $conn->prepare("SELECT mechanic_id, mechanic_name, mechanic_sort, color, font_color, work_start_time, work_end_time FROM mechanics WHERE mechanic_id = ?");
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
            <h1>Edytuj pracownika</h1>
    
<table>
    <form method="post" action="backend/update_worker.php">
    <input type="hidden" name="id" value="<?php echo htmlspecialchars($worker['mechanic_id']); ?>">
<tr>
<td><label>Nazwa:</label></td><td>
<input type="text" name="mechanic_name" value="<?php echo htmlspecialchars($worker['mechanic_name']); ?>">
</td><td></td>
</tr>
<tr>
<td><label>Sortowanie:</label></td><td>
<input type="number" name="mechanic_sort" value="<?php echo htmlspecialchars($worker['mechanic_sort']); ?>">
</td><td></td>
</tr>
<tr>
<td><label>Kolor Tła:</label></td><td>
<input type="color" name="color" value="<?php echo htmlspecialchars($worker['color']); ?>">
</td><td></td>
</tr>

<tr>
<td><label>Kolor Czcionki:</label></td><td>
<input type="color" name="font_color" value="<?php echo htmlspecialchars($worker['font_color']); ?>">
</td><td></td>
</tr>

<tr>
    <td><label>Godzina rozpoczęcia pracy:</label></td>
    <td>
        <input type="time"
            name="work_start_time"
            value="<?php echo !empty($worker['work_start_time'])
                ? date('H:i', strtotime($worker['work_start_time']))
                : '08:00'; ?>"
            required>
    </td><td></td>
</tr>

<tr>
    <td><label>Godzina zakończenia pracy:</label></td>
    <td>
        <input type="time"
            name="work_end_time"
            value="<?php echo !empty($worker['work_end_time'])
                ? date('H:i', strtotime($worker['work_end_time']))
                : '16:00'; ?>"
            required>
    </td><td></td>
</tr>


<tr>
    <td><a href="index.php" class="button">Powrót</a></td>
<td><input type="submit" value="Zapisz" class="button-orange"></td>
    <td><a href="backend/delete_worker.php?id=<?php echo $worker['mechanic_id']; ?>" class="button-red" onclick="return confirm('Czy na pewno chcesz usunąć tego pracownika?');">Usuń</a></td>


</tr>
</table>
</form>

</div></div></div>
    <div id="footer">
        <p>ekolives &copy; <?php echo date("Y"); ?> Kalendarz Rezerwacji</p>
    </div>