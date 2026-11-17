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
$stmt = $conn->prepare("SELECT user_id, user_login , user_permissions, user_status, user_email FROM users WHERE user_id = ?");
$stmt->bind_param("i", $workerId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    die("Nie znaleziono pracownika o podanym ID.");
}
$worker = $result->fetch_assoc();
$result->free();
$stmt->close();

// uprawnienia użytkownika do mechaników: mechanic_id => notification
$userPerms = [];
$stmt = $conn->prepare("SELECT mechanic_id, notification FROM permissions WHERE user_id = ?");
$stmt->bind_param("i", $workerId);
$stmt->execute();
$permResult = $stmt->get_result();
while ($p = $permResult->fetch_assoc()) {
    $userPerms[(int) $p['mechanic_id']] = !empty($p['notification']);
}
$stmt->close();

$mechanicsResult = $conn->query("SELECT mechanic_id, mechanic_name FROM mechanics ORDER BY mechanic_sort ASC, mechanic_name ASC");

$pageTitle = 'Edytuj użytkownika';
$backTab = '2';
include __DIR__ . '/_header.php';

$perm = (int) $worker['user_permissions'];
?>
<form method="post" action="backend/update_user.php" class="settings-form">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="id" value="<?php echo $h($worker['user_id']); ?>">

    <div class="settings-grid">
        <div class="settings-field">
            <label for="user_login">Login</label>
            <input id="user_login" type="text" name="user_login" value="<?php echo $h($worker['user_login']); ?>" required autocomplete="off">
        </div>

        <div class="settings-field">
            <label for="user_password">Hasło</label>
            <input id="user_password" type="password" name="user_password" autocomplete="new-password" placeholder="Pozostaw puste, aby nie zmieniać">
        </div>

        <div class="settings-field settings-field-wide">
            <label for="user_email">Email</label>
            <input id="user_email" type="email" name="user_email" value="<?php echo $h($worker['user_email']); ?>" autocomplete="off">
        </div>

        <div class="settings-field">
            <label for="permissions">Typ</label>
            <select id="permissions" name="permissions">
                <option value="1" <?php echo $perm === 1 ? 'selected' : ''; ?>>Administrator</option>
                <option value="2" <?php echo $perm === 2 ? 'selected' : ''; ?>>Użytkownik</option>
                <option value="3" <?php echo $perm === 3 ? 'selected' : ''; ?>>Współpracownik</option>
            </select>
        </div>

        <div class="settings-field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="0" <?php echo !$worker['user_status'] ? 'selected' : ''; ?>>Nieaktywny</option>
                <option value="1" <?php echo $worker['user_status'] ? 'selected' : ''; ?>>Aktywny</option>
            </select>
        </div>

        <div class="settings-field settings-field-wide">
            <label>Uprawnienia do</label>
            <div class="settings-check-list">
                <?php if ($mechanicsResult): ?>
                    <?php while ($mechanic = $mechanicsResult->fetch_assoc()): ?>
                        <?php
                        $mid = (int) $mechanic['mechanic_id'];
                        $hasAccess = array_key_exists($mid, $userPerms);
                        $notify = $hasAccess && $userPerms[$mid];
                        ?>
                        <div class="settings-check-row">
                            <label class="settings-check">
                                <input type="checkbox" name="mechanics[]" value="<?php echo $mid; ?>" <?php echo $hasAccess ? 'checked' : ''; ?>>
                                <span><?php echo $h($mechanic['mechanic_name']); ?></span>
                            </label>
                            <label class="settings-check settings-check-muted">
                                <input type="checkbox" name="notifications[]" value="<?php echo $mid; ?>" <?php echo $notify ? 'checked' : ''; ?>>
                                <span>wysyłaj powiadomienie</span>
                            </label>
                        </div>
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="settings-form-actions settings-form-actions-split">
        <a href="index.php?tab=2" class="settings-btn">Powrót</a>
        <button type="submit" class="settings-btn settings-btn-primary">Zapisz</button>
    </div>
</form>
<?php include __DIR__ . '/_footer.php'; ?>
