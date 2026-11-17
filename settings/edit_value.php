<?php
require_once __DIR__ . '/../sys-backend/session_config.php';
require_once __DIR__ . '/../sys-backend/db_connect.php';

if ($user_system_permissions != 1) {
    require_once __DIR__ . '/no_access.php';
    exit;
}

$settingId = (int) ($_GET['id'] ?? 0);
if (!$settingId) {
    die("Nie podano ID ustawienia.");
}

$stmt = $conn->prepare("SELECT * FROM settings WHERE sys_id = ?");
$stmt->bind_param("i", $settingId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("Nie znaleziono ustawienia o podanym ID.");
}
$setting = $result->fetch_assoc();
$result->free();
$stmt->close();

$pageTitle = 'Edytuj zmienną';
$backTab = '5';
include __DIR__ . '/_header.php';
?>
<form action="backend/update_value.php" method="post" class="settings-form">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="id" value="<?php echo (int) $setting['sys_id']; ?>">

    <div class="settings-grid">
        <div class="settings-field">
            <label for="setting_name">Nazwa</label>
            <input type="text" name="setting_name" id="setting_name" value="<?php echo $h($setting['setting_name']); ?>" readonly>
        </div>

        <div class="settings-field">
            <label for="setting_sort">Sortowanie</label>
            <input type="text" name="setting_sort" id="setting_sort" value="<?php echo $h($setting['setting_sort']); ?>">
        </div>

        <div class="settings-field settings-field-wide">
            <label for="setting_values">Wartość</label>
            <input type="text" name="setting_values" id="setting_values" value="<?php echo $h($setting['setting_values']); ?>" autofocus>
        </div>

        <div class="settings-field">
            <label for="setting_status">Status</label>
            <select name="setting_status" id="setting_status">
                <option value="0" <?php echo (int) $setting['status'] === 0 ? 'selected' : ''; ?>>Nieaktywny</option>
                <option value="1" <?php echo (int) $setting['status'] === 1 ? 'selected' : ''; ?>>Aktywny</option>
            </select>
        </div>
    </div>

    <div class="settings-form-actions settings-form-actions-split">
        <a href="index.php?tab=5" class="settings-btn">Powrót</a>
        <button type="submit" class="settings-btn settings-btn-primary">Zapisz</button>
    </div>
</form>
<?php include __DIR__ . '/_footer.php'; ?>
