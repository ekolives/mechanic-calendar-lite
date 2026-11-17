<?php
require_once __DIR__ . '/../sys-backend/session_config.php';
require_once __DIR__ . '/../sys-backend/db_connect.php';

if ($user_system_permissions != 1) {
    require_once __DIR__ . '/no_access.php';
    exit;
}

$pageTitle = 'Dodaj pracownika';
$backTab = '1';
include __DIR__ . '/_header.php';
?>
<form method="post" action="backend/insert_worker.php" class="settings-form">
    <?php echo csrf_field(); ?>

    <div class="settings-grid">
        <div class="settings-field settings-field-wide">
            <label for="name">Imię</label>
            <input id="name" type="text" name="name" required autofocus>
        </div>

        <div class="settings-field">
            <label for="work_start">Godzina rozpoczęcia pracy</label>
            <input id="work_start" type="time" name="work_start" value="08:00" required>
        </div>

        <div class="settings-field">
            <label for="work_end">Godzina zakończenia pracy</label>
            <input id="work_end" type="time" name="work_end" value="16:00" required>
        </div>

        <div class="settings-field">
            <label for="color">Kolor tła</label>
            <input id="color" type="color" name="color" value="#2563eb">
        </div>

        <div class="settings-field">
            <label for="font_color">Kolor czcionki</label>
            <input id="font_color" type="color" name="font_color" value="#ffffff">
        </div>
    </div>

    <div class="settings-form-actions settings-form-actions-split">
        <a href="index.php?tab=1" class="settings-btn">Powrót</a>
        <button type="submit" class="settings-btn settings-btn-primary">Dodaj</button>
    </div>
</form>
<?php include __DIR__ . '/_footer.php'; ?>
