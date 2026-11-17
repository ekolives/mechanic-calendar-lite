<?php
// włącz raportowanie błędów (na czas debugowania)
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);

require_once __DIR__ . '/sys-backend/security.php';
if (session_status() === PHP_SESSION_NONE) {
    secure_session_cookie_params(2 * 24 * 60 * 60);
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
    <link rel=\"stylesheet\" href=\"newstyle.css?v=" . filemtime(__DIR__ . "/newstyle.css") . "\" />
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
    <link rel=\"stylesheet\" href=\"newstyle.css?v=" . filemtime(__DIR__ . "/newstyle.css") . "\" />
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
if (isset($_POST['user_login'], $_POST['user_password']) && is_string($_POST['user_login']) && is_string($_POST['user_password'])) {
    $loginInput = trim($_POST['user_login']);
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    // jeden komunikat dla złego loginu i złego hasła - nie ujawniamy, które loginy istnieją
    $genericError = "Nieprawidłowy login lub hasło.";

    if (login_is_throttled($conn, $loginInput, $ip)) {
        audit_log($conn, $loginInput, 'logowanie', 'throttled|' . $ip);
        $login_error = "Zbyt wiele nieudanych prób logowania. Spróbuj ponownie za " . LOGIN_WINDOW_MINUTES . " minut.";
    } elseif ($stmt = $conn->prepare('SELECT `user_id`, `user_login`, `user_password`, `user_permissions`, `user_status`
                                FROM `users` WHERE user_login = ?')) {
        $stmt->bind_param('s', $loginInput);
        $stmt->execute();
        $stmt->store_result();

        // Dla nieistniejącego użytkownika też wykonujemy password_verify, żeby czas odpowiedzi nie zdradzał, czy login istnieje.
        $dummyHash = '$2y$10$pOLxgcBJFkVHq8/wWKaaFeAm.HyCc2D8TE5WUltaNfKJdJiNjf7Lm';
        $found = $stmt->num_rows > 0;
        if ($found) {
            $stmt->bind_result($id, $dbLogin, $password, $usertype, $status);
            $stmt->fetch();
        }
        $passwordOk = password_verify($_POST['user_password'], $found ? $password : $dummyHash) && $found;

        if (!$passwordOk) {
            audit_log($conn, $loginInput, 'logowanie', ($found ? 'invalid_password|' : 'invalid_user|') . $ip);
            $login_error = $genericError;
        } elseif ((int)$status === 0) {
            audit_log($conn, $loginInput, 'logowanie', 'account_blocked|' . $ip);
            $login_error = "Konto jest zablokowane.";
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $id;
            $_SESSION['name'] = $dbLogin;
            $_SESSION['user_permissions'] = $usertype;
            $_SESSION['logged_in'] = true;

            // przy okazji odnawiamy hash, jeśli algorytm domyślny się zmienił
            if (password_needs_rehash($password, PASSWORD_DEFAULT)) {
                $newHash = password_hash($_POST['user_password'], PASSWORD_DEFAULT);
                $upd = $conn->prepare('UPDATE `users` SET `user_password` = ? WHERE `user_id` = ?');
                if ($upd) {
                    $upd->bind_param('si', $newHash, $id);
                    $upd->execute();
                    $upd->close();
                }
            }

            audit_log($conn, $dbLogin, 'logowanie', 'success|' . $ip);
            header("Location: table/");
            exit;
        }
        $stmt->close();
    } else {
        // jeśli prepare() się wywali – log do error_log
        error_log("Błąd prepare(): " . $conn->error);
        $login_error = "Wewnętrzny błąd systemu";
    }
}

?>


<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo htmlspecialchars($lang_WebTitle ?? 'Kalendarz mechanika', ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="newstyle.css?v=<?php echo filemtime(__DIR__ . '/newstyle.css'); ?>" />
</head>

<body class="login-page">
    <div class="login-lang">
        <?php require_once "sys-backend/lang.php"; ?>
    </div>

    <main class="login-card">
        <div class="login-brand">
            <span class="login-brand-dot"></span>
            <span class="login-brand-name"><?php echo htmlspecialchars($lang_WebTitle, ENT_QUOTES, 'UTF-8'); ?></span>
        </div>

        <h1><?php echo htmlspecialchars($lang_login, ENT_QUOTES, 'UTF-8'); ?></h1>

        <?php if (!empty($login_error)) : ?>
            <div class="login-error" role="alert"><?php echo htmlspecialchars($login_error, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <form action="index.php" method="post" class="login-form">
            <label for="user_login"><?php echo htmlspecialchars($lang_LoginName, ENT_QUOTES, 'UTF-8'); ?></label>
            <input id="user_login" type="text" name="user_login" autocomplete="username" autofocus required>

            <label for="user_password"><?php echo htmlspecialchars($lang_password, ENT_QUOTES, 'UTF-8'); ?></label>
            <input id="user_password" type="password" name="user_password" autocomplete="current-password" required>

            <button type="submit" class="login-submit"><?php echo htmlspecialchars($lang_submit, ENT_QUOTES, 'UTF-8'); ?></button>
        </form>

        <p class="credits">ekolives &#x24D2; <?php echo date("Y"); ?> v <?php echo htmlspecialchars(trim((string)@file_get_contents(__DIR__ . '/VERSION')), ENT_QUOTES, 'UTF-8'); ?></p>
    </main>
</body>

</html>
