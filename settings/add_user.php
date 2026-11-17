<?php
require_once __DIR__ . '/../sys-backend/session_config.php';
require_once __DIR__ . '/../sys-backend/db_connect.php';

if ($user_system_permissions != 1) {
    require_once __DIR__ . '/no_access.php';
    exit;
}

$pageTitle = 'Dodaj użytkownika';
$backTab = '2';
include __DIR__ . '/_header.php';
?>
<form method="post" action="backend/insert_user.php" class="settings-form">
    <?php echo csrf_field(); ?>

    <div class="settings-grid">
        <div class="settings-field">
            <label for="user_login">Login</label>
            <input id="user_login" type="text" name="user_login" required autofocus autocomplete="off">
        </div>

        <div class="settings-field">
            <label for="user_password">Hasło</label>
            <input id="user_password" type="password" name="user_password" required autocomplete="new-password">
        </div>

        <div class="settings-field settings-field-wide">
            <label for="user_email">Email</label>
            <input id="user_email" type="email" name="user_email" autocomplete="off">
        </div>

        <div class="settings-field">
            <label for="permissions">Uprawnienie</label>
            <select id="permissions" name="permissions">
                <option value="1">Administrator</option>
                <option value="2" selected>Użytkownik</option>
                <option value="3">Współpracownik</option>
            </select>
        </div>

        <div class="settings-field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="0">Nieaktywny</option>
                <option value="1" selected>Aktywny</option>
            </select>
        </div>
    </div>

    <div class="settings-form-actions settings-form-actions-split">
        <a href="index.php?tab=2" class="settings-btn">Powrót</a>
        <button type="submit" class="settings-btn settings-btn-primary">Zapisz</button>
    </div>
</form>
<?php include __DIR__ . '/_footer.php'; ?>
