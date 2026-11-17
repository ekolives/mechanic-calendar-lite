<?php
require_once __DIR__ . '/../sys-backend/session_config.php';
require_once __DIR__ . '/../sys-backend/db_connect.php';

if ($user_system_permissions != 1) {
    require_once __DIR__ . '/no_access.php';
    exit;
}

$translationId = (int) ($_GET['id'] ?? 0);
if (!$translationId) {
    die("Nie podano ID tłumaczenia.");
}

$stmt = $conn->prepare("SELECT * FROM language WHERE id = ?");
$stmt->bind_param("i", $translationId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("Nie znaleziono tłumaczenia o podanym ID.");
}
$translation = $result->fetch_assoc();
$result->free();
$stmt->close();

$pageTitle = 'Edytuj tłumaczenie';
$backTab = '4';
include __DIR__ . '/_header.php';
?>
<form action="backend/update_translation.php" method="post" class="settings-form">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="id" value="<?php echo (int) $translation['id']; ?>">

    <div class="settings-grid">
        <div class="settings-field">
            <label for="sys">Klucz</label>
            <input type="text" name="sys" id="sys" value="<?php echo $h($translation['sys']); ?>" readonly>
        </div>

        <div class="settings-field">
            <label for="locale">Locale</label>
            <input type="text" name="locale" id="locale" value="<?php echo $h($translation['locale']); ?>" readonly>
        </div>

        <div class="settings-field settings-field-wide">
            <label for="name">Tłumaczenie</label>
            <input type="text" name="name" id="name" value="<?php echo $h($translation['name']); ?>" required autofocus>
        </div>
    </div>

    <div class="settings-form-actions settings-form-actions-split">
        <a href="index.php?tab=4" class="settings-btn">Powrót</a>
        <button type="submit" class="settings-btn settings-btn-primary">Zapisz</button>
    </div>
</form>
<?php include __DIR__ . '/_footer.php'; ?>
