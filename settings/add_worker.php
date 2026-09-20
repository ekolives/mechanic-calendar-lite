<?php
require_once __DIR__ . '/../sys-backend/session_config.php';
require_once __DIR__ . '/../sys-backend/db_connect.php';

echo $user_system_permissions;

if ($user_system_permissions != 1) {
    require_once __DIR__ . '/no_access.php';
    exit;
}
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
            <h1>Dodaj pracownika</h1>
    
<table>
    <tr>
        <th>Imię</th>
        <th>Godzina rozpoczęcia pracy</th>
        <th>Godzina zakończenia pracy</th>
        <th>Kolor Tła</th>
        <th>Kolor Czcionki</th>
        <th colspan="2">Akcje</th>
    </tr>
    <tr>
        <form method="POST" action="backend/insert_worker.php">
            <td><input type="text" name="name" required></td>
            <td><input type="time" name="work_start" required></td>
            <td><input type="time" name="work_end" required></td>
            <td><input type="color" name="color" ></td>
            <td><input type="color" name="font_color" ></td>
            <td><input type="submit" value="Dodaj"></td>
            <td><a href="index.php" class="button">Powrót</a></td>
        </form>
    </tr>
</table>

        </div>
    </div>