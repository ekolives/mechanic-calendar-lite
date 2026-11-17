<?php
require_once __DIR__ . '/../sys-backend/session_config.php';
require_once __DIR__ . '/../sys-backend/db_connect.php';

if ($user_system_permissions != 1) {
    require_once __DIR__ . '/no_access.php';
    exit;
}

$workerId = (int) ($_GET['id'] ?? 0);
if (!$workerId) {
    die("Nie podano ID pracownika.");
}
$stmt = $conn->prepare("SELECT mechanic_id, mechanic_name, mechanic_sort, color, font_color, work_start_time, work_end_time FROM mechanics WHERE mechanic_id = ?");
$stmt->bind_param("i", $workerId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("Nie znaleziono pracownika o podanym ID.");
}
$worker = $result->fetch_assoc();
$result->free();
$stmt->close();

$pageTitle = 'Edytuj pracownika';
$backTab = '1';
include __DIR__ . '/_header.php';

$colorOrDefault = static fn($v, string $d): string => (is_string($v) && preg_match('/^#[0-9a-fA-F]{6}$/', $v)) ? $v : $d;
?>
<form method="post" action="backend/update_worker.php" class="settings-form">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="id" value="<?php echo $h($worker['mechanic_id']); ?>">

    <div class="settings-grid">
        <div class="settings-field">
            <label for="mechanic_name">Nazwa</label>
            <input id="mechanic_name" type="text" name="mechanic_name" value="<?php echo $h($worker['mechanic_name']); ?>" required>
        </div>

        <div class="settings-field">
            <label for="mechanic_sort">Sortowanie</label>
            <input id="mechanic_sort" type="number" name="mechanic_sort" value="<?php echo $h($worker['mechanic_sort']); ?>">
        </div>

        <div class="settings-field">
            <label for="color">Kolor tła</label>
            <input id="color" type="color" name="color" value="<?php echo $h($colorOrDefault($worker['color'], '#2563eb')); ?>">
        </div>

        <div class="settings-field">
            <label for="font_color">Kolor czcionki</label>
            <input id="font_color" type="color" name="font_color" value="<?php echo $h($colorOrDefault($worker['font_color'], '#ffffff')); ?>">
        </div>

        <div class="settings-field">
            <label for="work_start_time">Godzina rozpoczęcia pracy</label>
            <input id="work_start_time" type="time" name="work_start_time"
                value="<?php echo !empty($worker['work_start_time']) ? $h(date('H:i', strtotime($worker['work_start_time']))) : '08:00'; ?>" required>
        </div>

        <div class="settings-field">
            <label for="work_end_time">Godzina zakończenia pracy</label>
            <input id="work_end_time" type="time" name="work_end_time"
                value="<?php echo !empty($worker['work_end_time']) ? $h(date('H:i', strtotime($worker['work_end_time']))) : '16:00'; ?>" required>
        </div>
    </div>

    <div class="settings-form-actions settings-form-actions-split">
        <a href="index.php?tab=1" class="settings-btn">Powrót</a>
        <div class="settings-form-actions-group">
            <button type="submit" formaction="backend/delete_worker.php" formmethod="post" formnovalidate
                class="settings-btn settings-btn-danger"
                onclick="return confirm('Czy na pewno chcesz usunąć tego pracownika?');">Usuń</button>
            <button type="submit" class="settings-btn settings-btn-primary">Zapisz</button>
        </div>
    </div>
</form>
<?php include __DIR__ . '/_footer.php'; ?>
