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
?>

<table>
    <tr>
        <th>Sort</th>
        <th>Nazwa</th>
        <th>Wartość</th>
        <th>Action <a href="#" class="button">+</a></th>
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
                        
            echo "<td><a href=\"edit_value.php?id=" . $row['sys_id'] . "\" class=\"button\">$lang_change</a></td>";
            echo "</tr>";
        }
    }
    ?>
</table>

<h2 style="margin-top: 30px;">Ustawienia poczty wychodzącej (SMTP)</h2>

<?php if (!empty($_SESSION['smtp_message_success'])): ?>
    <div style="background-color: #d4edda; color: #155724; padding: 12px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #c3e6cb; font-weight: bold;">
        <?php
        echo htmlspecialchars($_SESSION['smtp_message_success']);
        unset($_SESSION['smtp_message_success']);
        ?>
    </div>
<?php endif; ?>

<?php if (!empty($_SESSION['smtp_message_error'])): ?>
    <div style="background-color: #f8d7da; color: #721c24; padding: 12px; border-radius: 4px; margin-bottom: 15px; border: 1px solid #f5c6cb; font-weight: bold;">
        <?php
        echo htmlspecialchars($_SESSION['smtp_message_error']);
        unset($_SESSION['smtp_message_error']);
        ?>
    </div>
<?php endif; ?>

<form method="post" action="backend/update_smtp.php">
    <table>
        <tr>
            <td style="width: 260px;"><label>Wysyłka powiadomień e-mail:</label></td>
            <td>
                <select name="smtp_enabled">
                    <option value="1" <?php echo ((int)$smtpData['smtp_enabled'] === 1) ? 'selected' : ''; ?>>Włączona</option>
                    <option value="0" <?php echo ((int)$smtpData['smtp_enabled'] === 0) ? 'selected' : ''; ?>>Wyłączona</option>
                </select>
            </td>
        </tr>

        <tr>
            <td><label>Serwer SMTP (Host):</label></td>
            <td>
                <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($smtpData['smtp_host']); ?>" placeholder="np. smtp.gmail.com" required>
            </td>
        </tr>

        <tr>
            <td><label>Port SMTP:</label></td>
            <td>
                <input type="number" name="smtp_port" value="<?php echo (int)$smtpData['smtp_port']; ?>" placeholder="587" required>
            </td>
        </tr>

        <tr>
            <td><label>Szyfrowanie połączenia:</label></td>
            <td>
                <select name="smtp_encryption">
                    <option value="tls" <?php echo ($smtpData['smtp_encryption'] === 'tls') ? 'selected' : ''; ?>>TLS (STARTTLS - port 587)</option>
                    <option value="ssl" <?php echo ($smtpData['smtp_encryption'] === 'ssl') ? 'selected' : ''; ?>>SSL (SMTPS - port 465)</option>
                    <option value="" <?php echo ($smtpData['smtp_encryption'] === '') ? 'selected' : ''; ?>>Brak</option>
                </select>
            </td>
        </tr>

        <tr>
            <td><label>Wymagaj autoryzacji SMTP:</label></td>
            <td>
                <select name="smtp_auth">
                    <option value="1" <?php echo ((int)$smtpData['smtp_auth'] === 1) ? 'selected' : ''; ?>>Tak</option>
                    <option value="0" <?php echo ((int)$smtpData['smtp_auth'] === 0) ? 'selected' : ''; ?>>Nie</option>
                </select>
            </td>
        </tr>

        <tr>
            <td><label>Login SMTP (Użytkownik):</label></td>
            <td>
                <input type="text" name="smtp_username" value="<?php echo htmlspecialchars($smtpData['smtp_username']); ?>" placeholder="np. powiadomienia@twojadomena.pl">
            </td>
        </tr>

        <tr>
            <td><label>Hasło SMTP:</label></td>
            <td>
                <input type="password" name="smtp_password" value="" placeholder="<?php echo $hasEncryptedPassword ? 'Hasło jest zapisane (wpisz nowe, aby zmienić)' : 'Wprowadź hasło do skrzynki'; ?>">
                <?php if ($hasEncryptedPassword): ?>
                    <small style="color: green; display: block; margin-top: 4px;">✔ Zaszyfrowane hasło znajduje się w bazie. Pozostaw puste, jeśli nie chcesz go zmieniać.</small>
                <?php endif; ?>
            </td>
        </tr>

        <tr>
            <td><label>Adres e-mail nadawcy (Od):</label></td>
            <td>
                <input type="email" name="from_email" value="<?php echo htmlspecialchars($smtpData['from_email']); ?>" placeholder="np. powiadomienia@twojadomena.pl" required>
            </td>
        </tr>

        <tr>
            <td><label>Nazwa nadawcy:</label></td>
            <td>
                <input type="text" name="from_name" value="<?php echo htmlspecialchars($smtpData['from_name']); ?>" placeholder="Kalendarz Mechanika">
            </td>
        </tr>

        <tr>
            <td><label>Adres Reply-To (opcjonalnie):</label></td>
            <td>
                <input type="email" name="reply_to_email" value="<?php echo htmlspecialchars((string)$smtpData['reply_to_email']); ?>" placeholder="np. biuro@twojadomena.pl">
            </td>
        </tr>

        <tr>
            <td><label>Adres do testu wysyłki (opcjonalnie):</label></td>
            <td>
                <input type="email" name="test_email" value="" placeholder="Wpisz e-mail, aby przetestować wysyłkę">
                <button type="submit" name="send_test" value="1" class="button" style="margin-left: 8px;">Zapisz i wyślij e-mail testowy</button>
            </td>
        </tr>

        <tr>
            <td></td>
            <td>
                <input type="submit" value="Zapisz ustawienia SMTP" class="button-orange">
            </td>
        </tr>
    </table>
</form>
