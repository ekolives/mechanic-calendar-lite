<?php
require_once __DIR__ . '/../sys-backend/session_config.php';
require_once __DIR__ . '/../sys-backend/db_connect.php';

if ($user_system_permissions != 1) {
    require_once __DIR__ . '/no_access.php';
    exit;
}

$pageTitle = 'Dodaj tłumaczenie';
$backTab = '4';
include __DIR__ . '/_header.php';
?>
<form action="backend/add_translation.php" method="post" class="settings-form">
    <?php echo csrf_field(); ?>

    <div class="settings-grid">
        <div class="settings-field settings-field-wide">
            <label for="sys">Klucz</label>
            <input type="text" name="sys" id="sys" placeholder="lang_przyklad" pattern="lang_[A-Za-z0-9_]+" title="Klucz musi zaczynać się od lang_ i zawierać tylko litery, cyfry i _" required autofocus>
        </div>

        <div class="settings-field">
            <label for="locale">Locale</label>
            <select name="locale" id="locale">
                <option value="pl">pl</option>
                <option value="en">en</option>
                <option value="ua">ua</option>
            </select>
        </div>

        <div class="settings-field settings-field-wide">
            <label for="name">Tłumaczenie</label>
            <input type="text" name="name" id="name" required>
        </div>
    </div>

    <div class="settings-form-actions settings-form-actions-split">
        <a href="index.php?tab=4" class="settings-btn">Powrót</a>
        <button type="submit" class="settings-btn settings-btn-primary">Zapisz</button>
    </div>
</form>
<?php include __DIR__ . '/_footer.php'; ?>
