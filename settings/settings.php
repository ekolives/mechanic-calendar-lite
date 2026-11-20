<?php
require_once __DIR__ . '/../sys-backend/session_config.php';
require_once __DIR__ . '/../sys-backend/db_connect.php';

if ($user_system_permissions != 1) {
    require_once __DIR__ . '/no_access.php';
    exit;
}
/** @var string $lang_change */

$smtpData = [
    'smtp_enabled'    => 1,
    'smtp_host'       => '',
    'smtp_port'       => 587,
    'smtp_encryption' => 'tls',
    'smtp_auth'       => 1,
    'smtp_username'   => '',
    'smtp_password'   => '',
    'from_email'      => '',
    'from_name'       => 'Kalendarz Mechanika',
    'reply_to_email'  => '',
];

$smtpResult = @$conn->query("SELECT * FROM smtp_settings ORDER BY id ASC LIMIT 1");
if ($smtpResult && $smtpResult->num_rows > 0) {
    $smtpData = array_merge($smtpData, $smtpResult->fetch_assoc());
    $smtpResult->free();
}

$hasEncryptedPassword = !empty($smtpData['smtp_password']);

$calendarView = $_GET['view'] ?? 'weekly';
if (!in_array($calendarView, ['daily', 'weekly'], true)) {
    $calendarView = 'weekly';
}

$calendarDate = date('Y-m-d');
if (isset($_GET['date']) && is_string($_GET['date'])) {
    $parsedCalendarDate = DateTime::createFromFormat('!Y-m-d', $_GET['date']);
    if ($parsedCalendarDate && $parsedCalendarDate->format('Y-m-d') === $_GET['date']) {
        $calendarDate = $_GET['date'];
    }
}
?>

<div class="settings-page">

<section class="calendar-settings-switch">
    <div>
        <h2>Widok kalendarza</h2>
        <p>Wybierz widok i wróć do kalendarza.</p>
    </div>
    <div class="calendar-settings-options">
        <a href="../table/index.php?view=daily&amp;date=<?php echo rawurlencode($calendarDate); ?>"
            class="calendar-settings-option<?php echo $calendarView === 'daily' ? ' is-active' : ''; ?>"
            <?php echo $calendarView === 'daily' ? 'aria-current="page"' : ''; ?>>
            📅 <?php echo htmlspecialchars($lang_daily_view, ENT_QUOTES, 'UTF-8'); ?>
        </a>
        <a href="../table/index.php?view=weekly&amp;date=<?php echo rawurlencode($calendarDate); ?>"
            class="calendar-settings-option<?php echo $calendarView === 'weekly' ? ' is-active' : ''; ?>"
            <?php echo $calendarView === 'weekly' ? 'aria-current="page"' : ''; ?>>
            <?php echo htmlspecialchars($lang_weekly_view, ENT_QUOTES, 'UTF-8'); ?> 📅
        </a>
    </div>
</section>

<section class="settings-card">
    <div class="settings-card-head">
        <h2>Ustawienia ogólne</h2>
    </div>
    <div class="settings-table-wrap">
        <table class="settings-table">
            <tr>
                <th>Sort</th>
                <th>Nazwa</th>
                <th>Wartość</th>
                <th></th>
            </tr>

            <?php
            $result = $conn->query("
            SELECT *
            FROM settings
            ORDER BY setting_sort ASC
            ");

            if ($result === false) {
                echo "<tr><td colspan='4'>Wystąpił błąd podczas pobierania danych.</td></tr>";
            } else {
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row['setting_sort']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['setting_name']) . "</td>";
                    echo "<td>" . htmlspecialchars($row['setting_values']) . "</td>";
                    echo "<td class=\"settings-actions\"><a href=\"edit_value.php?id=" . (int)$row['sys_id'] . "\" class=\"settings-btn\">" . htmlspecialchars($lang_change, ENT_QUOTES, 'UTF-8') . "</a></td>";
                    echo "</tr>";
                }
            }
            ?>
        </table>
    </div>
</section>

<section class="settings-card">
    <div class="settings-card-head">
        <h2>Ustawienia poczty wychodzącej (SMTP)</h2>
        <p>Konfiguracja wysyłki powiadomień e-mail o nowych rezerwacjach.</p>
    </div>

    <?php if (!empty($_SESSION['smtp_message_success'])): ?>
        <div class="settings-alert settings-alert-success" role="status">
            <?php
            echo htmlspecialchars($_SESSION['smtp_message_success']);
            unset($_SESSION['smtp_message_success']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['smtp_message_error'])): ?>
        <div class="settings-alert settings-alert-error" role="alert">
            <?php
            echo htmlspecialchars($_SESSION['smtp_message_error']);
            unset($_SESSION['smtp_message_error']);
            ?>
        </div>
    <?php endif; ?>

    <form method="post" action="backend/update_smtp.php" class="settings-form">
        <?php echo csrf_field(); ?>

        <div class="settings-grid">
            <div class="settings-field">
                <label for="smtp_enabled">Wysyłka powiadomień e-mail</label>
                <select id="smtp_enabled" name="smtp_enabled">
                    <option value="1" <?php echo ((int)$smtpData['smtp_enabled'] === 1) ? 'selected' : ''; ?>>Włączona</option>
                    <option value="0" <?php echo ((int)$smtpData['smtp_enabled'] === 0) ? 'selected' : ''; ?>>Wyłączona</option>
                </select>
            </div>

            <div class="settings-field">
                <label for="smtp_host">Serwer SMTP (Host)</label>
                <input id="smtp_host" type="text" name="smtp_host" value="<?php echo htmlspecialchars($smtpData['smtp_host']); ?>" placeholder="np. smtp.gmail.com" required>
            </div>

            <div class="settings-field">
                <label for="smtp_port">Port SMTP</label>
                <input id="smtp_port" type="number" name="smtp_port" value="<?php echo (int)$smtpData['smtp_port']; ?>" placeholder="587" required>
            </div>

            <div class="settings-field">
                <label for="smtp_encryption">Szyfrowanie połączenia</label>
                <select id="smtp_encryption" name="smtp_encryption">
                    <option value="tls" <?php echo ($smtpData['smtp_encryption'] === 'tls') ? 'selected' : ''; ?>>TLS (STARTTLS - port 587)</option>
                    <option value="ssl" <?php echo ($smtpData['smtp_encryption'] === 'ssl') ? 'selected' : ''; ?>>SSL (SMTPS - port 465)</option>
                    <option value="" <?php echo ($smtpData['smtp_encryption'] === '') ? 'selected' : ''; ?>>Brak</option>
                </select>
            </div>

            <div class="settings-field">
                <label for="smtp_auth">Wymagaj autoryzacji SMTP</label>
                <select id="smtp_auth" name="smtp_auth">
                    <option value="1" <?php echo ((int)$smtpData['smtp_auth'] === 1) ? 'selected' : ''; ?>>Tak</option>
                    <option value="0" <?php echo ((int)$smtpData['smtp_auth'] === 0) ? 'selected' : ''; ?>>Nie</option>
                </select>
            </div>

            <div class="settings-field">
                <label for="smtp_username">Login SMTP (Użytkownik)</label>
                <input id="smtp_username" type="text" name="smtp_username" value="<?php echo htmlspecialchars($smtpData['smtp_username']); ?>" placeholder="np. powiadomienia@twojadomena.pl" autocomplete="off">
            </div>

            <div class="settings-field settings-field-wide">
                <label for="smtp_password">Hasło SMTP</label>
                <input id="smtp_password" type="password" name="smtp_password" value="" autocomplete="new-password" placeholder="<?php echo $hasEncryptedPassword ? 'Hasło jest zapisane (wpisz nowe, aby zmienić)' : 'Wprowadź hasło do skrzynki'; ?>">
                <?php if ($hasEncryptedPassword): ?>
                    <small class="settings-hint settings-hint-ok">✔ Zaszyfrowane hasło znajduje się w bazie. Pozostaw puste, jeśli nie chcesz go zmieniać.</small>
                <?php endif; ?>
            </div>

            <div class="settings-field">
                <label for="from_email">Adres e-mail nadawcy (Od)</label>
                <input id="from_email" type="email" name="from_email" value="<?php echo htmlspecialchars($smtpData['from_email']); ?>" placeholder="np. powiadomienia@twojadomena.pl" required>
            </div>

            <div class="settings-field">
                <label for="from_name">Nazwa nadawcy</label>
                <input id="from_name" type="text" name="from_name" value="<?php echo htmlspecialchars($smtpData['from_name']); ?>" placeholder="Kalendarz Mechanika">
            </div>

            <div class="settings-field settings-field-wide">
                <label for="reply_to_email">Adres Reply-To (opcjonalnie)</label>
                <input id="reply_to_email" type="email" name="reply_to_email" value="<?php echo htmlspecialchars((string)$smtpData['reply_to_email']); ?>" placeholder="np. biuro@twojadomena.pl">
            </div>
        </div>

        <div class="settings-test">
            <div class="settings-field">
                <label for="test_email">Adres do testu wysyłki (opcjonalnie)</label>
                <input id="test_email" type="email" name="test_email" value="" placeholder="Wpisz e-mail, aby przetestować wysyłkę">
            </div>
            <button type="submit" name="send_test" value="1" class="settings-btn">Zapisz i wyślij e-mail testowy</button>
        </div>

        <div class="settings-form-actions">
            <button type="submit" class="settings-btn settings-btn-primary">Zapisz ustawienia SMTP</button>
        </div>
    </form>
    <script>
    (function () {
        var enabled = document.getElementById('smtp_enabled');
        var fields = ['smtp_host', 'smtp_port', 'from_email'].map(function (id) {
            return document.getElementById(id);
        });
        function sync() {
            var on = enabled.value === '1';
            fields.forEach(function (f) { f.required = on; });
        }
        enabled.addEventListener('change', sync);
        sync();
    })();
    </script>
</section>

</div>
