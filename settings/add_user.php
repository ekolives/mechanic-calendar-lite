<?php
require_once __DIR__ . '/../sys-backend/session_config.php';
require_once __DIR__ . '/../sys-backend/db_connect.php';


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
            <h1>Dodaj użytkownika</h1>
    
<table>
    <form method="post" action="backend/insert_user.php">
<tr>
<td><label>Login:</label></td><td>
<input type="text" name="user_login" value="">
</td>
</tr>

<tr>
<td><label>Hasło:</label></td><td>
<input type="password" name="user_password" >
</td>
</tr>


<tr>
<td><label>Uprawnienie:</label></td><td>
        <select name="permissions">
            <option value="1">Administrator</option>
            <option value="2">Użytkownik</option>
            <option value="3">Współpracownik</option>
        </select></td>
</tr>


<tr>
    <td><label>Status:</label></td>
    <td>
        <select name="status">
            <option value="0">Nieaktywny</option>
            <option value="1">Aktywny</option>
        </select>
    </td>
</tr>

<tr>
    <td><a href="index.php" class="button">Powrót</a></td>
<td><input type="submit" value="Zapisz" class="button-orange"></td>

</tr>
</table>
</form>