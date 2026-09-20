<?php
// Sprawdzenie czy system jest już zainstalowany
$isInstalled = false;
$installationMessage = '';
/** @var string $servername */
/** @var string $username */
/** @var string $password */
/** @var string $dbname */

if (file_exists('../sys-backend/config.php')) {
    try {
        include '../sys-backend/config.php';

        // Połączenie z bazą (@ tłumi warning PHP przy błędach sieciowych/DNS, obsługiwane niżej)
        $checkConn = @new mysqli($servername, $username, $password, $dbname);

        if ($checkConn->connect_error) {
            $installationMessage = "⚠️ Nie udało się połączyć z bazą. Spróbuj zainstalować ponownie.";
        } else {
            // Sprawdzenie czy tabela users istnieje i ma rekordy
            $result = $checkConn->query("SELECT COUNT(*) as count FROM users");

            if ($result) {
                $row = $result->fetch_assoc();
                if ($row['count'] > 0) {
                    $isInstalled = true;
                    $installationMessage = "✅ System jest już zainstalowany!";
                }
            }

            $checkConn->close();
        }
    } catch (Exception $e) {
        // config.php istnieje ale coś poszło nie tak
        $installationMessage = "⚠️ Błąd podczas sprawdzenia: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sprawdzenie ochronne - jeśli system już istnieje, nie pozwalaj na reinstalację
    if ($isInstalled) {
        echo "<p style='color: red; font-weight: bold;'>❌ BŁĄD: System jest już zainstalowany! Nie można dokonać reinstalacji.</p>";
        echo "<p>Jeśli chcesz zainstalować od nowa, usuń bazę danych i spróbuj ponownie.</p>";
        exit;
    }

    $dbHost = $_POST['db_host'];
    $dbUser = $_POST['db_user'];
    $dbPass = $_POST['db_pass'];
    $dbName = $_POST['db_name'];

    try {
        $pdo = new PDO("mysql:host=$dbHost", $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $pdo->exec("USE `$dbName`");

        $structureSQL = file_get_contents('structure.sql');
        $pdo->exec($structureSQL);

        $configContent = "<?php\n"
            . "\$servername = \"$dbHost\";\n"
            . "\$username = \"$dbUser\";\n"
            . "\$password = \"$dbPass\";\n"
            . "\$dbname = \"$dbName\";\n"
            . "?>";

        file_put_contents('../sys-backend/config.php', $configContent);



        echo "<p style='color:green'>✅ Instalacja zakończona pomyślnie.</p>";
        echo "<p style='color:black'>Pierwsze logowanie: login: <b>admin</b>, hasło: <b>admin</b>.</p>";
        echo "<p style='color:black'>Zachwile zostaniesz przeniesiony do strony głównej.</p>";
        echo "<script>setTimeout(function(){ window.location.href = '../index.php'; }, 8000);</script>";
    } catch (PDOException $e) {
        echo "<p style='color:red'>❌ Błąd: " . $e->getMessage() . "</p>";
    }

    exit;
}
?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd" W>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl" lang="pl">

<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="../newstyle.css" />

</head>

<body>
    <div id="wrapper">
        <div id="content">
            <section class="container">
                <h1>Install</h1>

                <?php
                if ($isInstalled) {
                    // System już zainstalowany
                    echo "<div style='background-color: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; padding: 15px; margin: 20px 0; color: #155724;'>";
                    echo "<h2>🔒 System Już Zainstalowany</h2>";
                    echo "<p>Baza danych zawiera już dane - system został wcześniej zainstalowany.</p>";
                    echo "<p>Jeśli chcesz ponownie zainstalować system, usuń najpierw bazę danych:</p>";
                    echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 4px;'>DROP DATABASE db_moto;</pre>";
                    echo "<p style='margin-top: 15px;'><a href='../index.php' style='text-decoration: none; color: #155724; font-weight: bold;'>← Wróć do logowania</a></p>";
                    echo "</div>";
                } else {
                    // Formularz instalacji
                    if (!empty($installationMessage)) {
                        echo "<p style='color: #ff9800; background: #fff3cd; padding: 10px; border-radius: 4px; border: 1px solid #ffc107; margin-bottom: 20px;'>" . htmlspecialchars($installationMessage) . "</p>";
                    }
                ?>
                    <form method="post">
                        <label>Host bazy danych: <input type="text" name="db_host" value="localhost" required></label><br><br>
                        <label>Użytkownik bazy: <input type="text" name="db_user" value="root" required></label><br><br>
                        <label>Hasło bazy: <input type="password" name="db_pass" required></label><br><br>
                        <label>Nazwa bazy: <input type="text" name="db_name" value="db_moto" required></label><br><br>
                        <input type="submit" value="Zainstaluj">
                    </form>
                <?php
                }
                ?>
            </section>
        </div>
    </div>
</body>

</html>