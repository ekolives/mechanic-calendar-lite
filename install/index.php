<?php
/**
 * Instalator aplikacji (jednorazowy).
 *
 * Zabezpieczenia:
 *  - jeśli sys-backend/config.php istnieje, instalator jest zablokowany (niezależnie od stanu bazy),
 *  - config.php jest tworzony atomowo (tryb "x"), więc dwa równoległe żądania nie nadpiszą się,
 *  - wartości trafiają do config.php przez var_export(), więc nie da się wstrzyknąć kodu PHP,
 *  - hasło administratora ustawia osoba instalująca (brak domyślnego admin/admin),
 *  - klucz szyfrowania SMTP jest generowany losowo.
 *
 * Po instalacji najlepiej usunąć cały katalog install/ z serwera.
 */

$configPath = __DIR__ . '/../sys-backend/config.php';
$locked = file_exists($configPath);

$h = static fn($v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');

/** Zawartość config.php z bezpiecznie zapisanymi wartościami. */
function build_config_php(string $host, string $user, string $pass, string $db, string $smtpKey): string
{
    return "<?php\n"
        . '$servername = ' . var_export($host, true) . ";\n"
        . '$username = ' . var_export($user, true) . ";\n"
        . '$password = ' . var_export($pass, true) . ";\n"
        . '$dbname = ' . var_export($db, true) . ";\n"
        . "define('SMTP_ENCRYPTION_KEY', " . var_export($smtpKey, true) . ");\n";
}

$errors = [];
$done = false;

if (!$locked && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $dbHost = trim((string)($_POST['db_host'] ?? ''));
    $dbUser = (string)($_POST['db_user'] ?? '');
    $dbPass = (string)($_POST['db_pass'] ?? '');
    $dbName = trim((string)($_POST['db_name'] ?? ''));
    $adminPass = (string)($_POST['admin_pass'] ?? '');
    $adminPass2 = (string)($_POST['admin_pass2'] ?? '');

    if (!preg_match('/^[A-Za-z0-9._-]{1,255}$/', $dbHost)) {
        $errors[] = 'Nieprawidłowy host bazy danych.';
    }
    if ($dbUser === '' || strlen($dbUser) > 128) {
        $errors[] = 'Podaj użytkownika bazy danych.';
    }
    // nazwa bazy trafia do zapytania w backtickach - dopuszczamy tylko bezpieczne znaki
    if (!preg_match('/^[A-Za-z0-9_]{1,64}$/', $dbName)) {
        $errors[] = 'Nazwa bazy może zawierać tylko litery, cyfry i znak _ (maks. 64 znaki).';
    }
    if (strlen($adminPass) < 8) {
        $errors[] = 'Hasło administratora musi mieć co najmniej 8 znaków.';
    } elseif ($adminPass !== $adminPass2) {
        $errors[] = 'Hasła administratora nie są takie same.';
    }
    if (!is_writable(dirname($configPath))) {
        $errors[] = 'Brak uprawnień zapisu do katalogu sys-backend/ - nie można utworzyć config.php.';
    }
    $structureSQL = @file_get_contents(__DIR__ . '/structure.sql');
    if ($structureSQL === false) {
        $errors[] = 'Nie znaleziono pliku structure.sql.';
    }

    if (!$errors) {
        try {
            $pdo = new PDO("mysql:host=$dbHost;charset=utf8mb4", $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            $pdo->exec("USE `$dbName`");
            $pdo->exec($structureSQL);
            unset($pdo);

            // osobne połączenie - po wieloinstrukcyjnym exec() poprzednie mogłoby mieć niezwolnione wyniki
            $pdo2 = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $stmt = $pdo2->prepare("UPDATE users SET user_password = ?, sys_updatedate = NOW() WHERE user_login = 'admin'");
            $stmt->execute([password_hash($adminPass, PASSWORD_DEFAULT)]);

            // config.php na końcu; tryb "x" = tylko jeśli plik jeszcze nie istnieje (atomowo)
            $fh = @fopen($configPath, 'x');
            if ($fh === false) {
                throw new RuntimeException('Nie udało się utworzyć config.php (plik już istnieje lub brak uprawnień).');
            }
            fwrite($fh, build_config_php($dbHost, $dbUser, $dbPass, $dbName, bin2hex(random_bytes(32))));
            fclose($fh);
            @chmod($configPath, 0640);

            $done = true;
        } catch (Throwable $e) {
            $errors[] = 'Błąd instalacji: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pl">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Instalacja</title>
    <link rel="stylesheet" href="../newstyle.css?v=<?php echo filemtime(__DIR__ . '/../newstyle.css'); ?>" />
</head>

<body class="login-page">
    <main class="login-card">
        <div class="login-brand">
            <span class="login-brand-dot"></span>
            <span class="login-brand-name">Kalendarz mechanika</span>
        </div>
        <h1>Instalacja</h1>

        <?php if ($done): ?>
            <div class="settings-alert settings-alert-success" role="status">Instalacja zakończona pomyślnie.</div>
            <p>Zaloguj się jako <b>admin</b> hasłem, które właśnie ustawiłeś(-aś).</p>
            <p><b>Ze względów bezpieczeństwa usuń teraz katalog <code>install/</code> z serwera.</b></p>
            <p><a href="../index.php" class="login-submit" style="display:inline-block; text-decoration:none;">Przejdź do logowania</a></p>

        <?php elseif ($locked): ?>
            <div class="settings-alert settings-alert-success" role="status">🔒 System jest już zainstalowany.</div>
            <p>Instalator jest zablokowany, bo istnieje plik <code>sys-backend/config.php</code>.</p>
            <p>Aby zainstalować od nowa, usuń ten plik ręcznie na serwerze (oraz starą bazę danych).</p>
            <p><a href="../index.php">← Wróć do logowania</a></p>

        <?php else: ?>
            <?php foreach ($errors as $err): ?>
                <div class="login-error" role="alert"><?php echo $h($err); ?></div>
            <?php endforeach; ?>

            <form method="post" class="login-form" autocomplete="off">
                <label for="db_host">Host bazy danych</label>
                <input id="db_host" type="text" name="db_host" value="<?php echo $h($_POST['db_host'] ?? 'localhost'); ?>" required>

                <label for="db_user">Użytkownik bazy</label>
                <input id="db_user" type="text" name="db_user" value="<?php echo $h($_POST['db_user'] ?? 'root'); ?>" required>

                <label for="db_pass">Hasło bazy</label>
                <input id="db_pass" type="password" name="db_pass" autocomplete="new-password">

                <label for="db_name">Nazwa bazy</label>
                <input id="db_name" type="text" name="db_name" value="<?php echo $h($_POST['db_name'] ?? 'db_moto'); ?>" required>

                <label for="admin_pass">Hasło administratora (min. 8 znaków)</label>
                <input id="admin_pass" type="password" name="admin_pass" minlength="8" autocomplete="new-password" required>

                <label for="admin_pass2">Powtórz hasło administratora</label>
                <input id="admin_pass2" type="password" name="admin_pass2" minlength="8" autocomplete="new-password" required>

                <button type="submit" class="login-submit">Zainstaluj</button>
            </form>
        <?php endif; ?>
    </main>
</body>

</html>
