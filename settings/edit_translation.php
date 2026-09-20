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

$stmt = $conn->prepare("SELECT * FROM language WHERE id = ?");
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
            <h1>Edytuj tłumaczenie</h1>
            <form action="backend/update_translation.php" method="post">
                <input type="hidden" name="id" value="<?php echo $translation['id']; ?>">
                <label for="key">Klucz:</label>
                <input type="text" name="sys" id="sys" value="<?php echo $translation['sys']; ?>" readonly>

                <label for="key">Locale:</label>
                <input type="text" name="locale" id="locale" value="<?php echo $translation['locale']; ?>" readonly>

                <label for="value">Tłumaczenie:</label>
                <input type="text" name="name" id="name" value="<?php echo $translation['name']; ?>">
                <input type="submit" value="Zapisz">
            </form>
        </div>
    </div>