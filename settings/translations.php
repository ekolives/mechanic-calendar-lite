<?php
require_once __DIR__ . '/../sys-backend/session_config.php';
require_once __DIR__ . '/../sys-backend/db_connect.php';

if ($user_system_permissions != 1) {
    require_once __DIR__ . '/no_access.php';
    exit;
}
/** @var string $lang_change */

$h = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$result = $conn->query("
    SELECT *
    FROM language
    ORDER BY sys ASC
");
?>
<div class="settings-page">
    <section class="settings-card">
        <div class="settings-card-head settings-card-head-row">
            <h2><?php echo $h($lang_slot_translations ?? 'Tłumaczenia'); ?></h2>
            <a href="add_translation.php" class="settings-btn settings-btn-primary">+ Dodaj</a>
        </div>

        <div class="settings-table-wrap">
            <table class="settings-table">
                <tr>
                    <th>Key</th>
                    <th>Locale</th>
                    <th>Translation</th>
                    <th></th>
                </tr>
                <?php if ($result === false): ?>
                    <tr><td colspan="4">Wystąpił błąd podczas pobierania danych.</td></tr>
                <?php else: ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><code class="settings-code"><?php echo $h($row['sys']); ?></code></td>
                            <td><span class="settings-badge settings-badge-muted"><?php echo $h($row['locale']); ?></span></td>
                            <td><?php echo $h($row['name']); ?></td>
                            <td class="settings-actions">
                                <a href="edit_translation.php?id=<?php echo (int) $row['id']; ?>" class="settings-btn"><?php echo $h($lang_change); ?></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </table>
        </div>
    </section>
</div>
