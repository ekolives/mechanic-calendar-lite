<?php
// włącz raportowanie błędów (na czas debugowania)
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// połączenie z DB
$configFile = __DIR__ . '/sys-backend/config.php';
require_once  __DIR__ . '/sys-backend/audit.php';

if (!is_readable($configFile)) {
    $installLink = 'install/index.php';
    echo "<!DOCTYPE html>
<html lang=\"pl\">
<head>
    <meta charset=\"utf-8\" />
    <title>Brak konfiguracji</title>
    <link rel=\"stylesheet\" href=\"newstyle.css\" />
</head>
<body>
    <div id=\"wrapper\">
        <div id=\"content\">
            <h1>Brak konfiguracji aplikacji</h1>
            <p>Nie znaleziono pliku konfiguracyjnego lub pliku instalacji.</p>
            <p>Przejdź do instalatora, aby skonfigurować aplikację:</p>
            <p><a href=\"{$installLink}\">Uruchom instalator</a></p>
        </div>
    </div>
</body>
</html>";
    exit;
}

require_once $configFile;

/** @var string $servername */
/** @var string $username */
/** @var string $password */
/** @var string $dbname */
/** @var string $lang_WebTitle */
/** @var string $lang_login */
/** @var string $lang_LoginName */
/** @var string $lang_password */
/** @var string $lang_submit */


$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    $installLink = 'install/index.php';
    echo "<!DOCTYPE html>
<html lang=\"pl\">
<head>
    <meta charset=\"utf-8\" />
    <title>Brak połączenia z bazą</title>
    <link rel=\"stylesheet\" href=\"newstyle.css\" />
</head>
<body>
    <div id=\"wrapper\">
        <div id=\"content\">
            <h1>Brak połączenia z bazą danych</h1>
            <p>Nie można nawiązać połączenia z bazą danych.</p>
            <p>Przejdź do instalatora, aby skonfigurować aplikację:</p>
            <p><a href=\"{$installLink}\">Uruchom instalator</a></p>
        </div>
    </div>
</body>
</html>";
    exit;
}

// jeśli już zalogowany → przekieruj
if (isset($_SESSION['user_id'])) {
    header("Location: table/");
    exit;
}

// obsługa logowania
if (isset($_POST['user_login'], $_POST['user_password'])) {
    $passwordHash = password_hash($_POST['user_password'], PASSWORD_DEFAULT);

    if ($stmt = $conn->prepare('SELECT `user_id`, `user_password`, `user_permissions`, `user_status` 
                                FROM `users` WHERE user_login = ?')) {
        $stmt->bind_param('s', $_POST['user_login']);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $password, $usertype, $status);
            $stmt->fetch();

            if (password_verify($_POST['user_password'], $password)) {
                if ($status == 0) {
                    audit_log($conn, $_POST['user_login'], 'logowanie', 'account_blocked');
                    $login_error = "Konto jest zablokowane.";
                } else {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $id;
                    $_SESSION['name'] = $_POST['user_login'];
                    $_SESSION['user_permissions'] = $usertype;
                    $_SESSION['logged_in'] = true;

                    audit_log($conn, $_POST['user_login'], 'logowanie', 'success');
                    header("Location: table/");
                    exit;
                }
            } else {
                audit_log($conn, $_POST['user_login'], 'logowanie', 'invalid_password');
                $login_error = "Błędne hasło";
            }
        } else {
            audit_log($conn, $_POST['user_login'], 'logowanie', 'invalid_user');
            $login_error = "Nieprawidłowy użytkownik";
        }
        $stmt->close();
    } else {
        // jeśli prepare() się wywali – log do error_log
        error_log("Błąd prepare(): " . $conn->error);
        $login_error = "Wewnętrzny błąd systemu";
    }
}

?>


<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl" lang="pl">

<head>
    <meta charset="utf-8" />
    <link rel="stylesheet" href="newstyle.css" />
</head>

<body>
    <div id="header">
        <?php require_once "sys-backend/lang.php"; ?>
        <div id="logo">
            <h3><?php echo $lang_WebTitle; ?></h3>
        </div>
    </div>
    <div id="wrapper">
        <div id="content">
            <h1><?php echo $lang_login; ?></h1>

            <?php if (!empty($login_error)) echo "<p style='color:red'>$login_error</p>"; ?>

            <section class="container">
                <form action="index.php" method="post">
                    <label for="user_login"><?php echo $lang_LoginName; ?>:</label>
                    <input id="user_login" type="text" name="user_login" required><br><br>

                    <label for="user_password"><?php echo $lang_password; ?>:</label>
                    <input id="user_password" type="password" name="user_password" required><br><br>

                    <input type="submit" value="<?php echo $lang_submit; ?>">
                </form>
            </section>
            <p class='credits'>ekolives &#x24D2; <?php echo date("Y"); ?> v 1.1.2</p>
        </div>

    </div>

</body>

</html>