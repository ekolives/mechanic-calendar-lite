<?php
require_once __DIR__ . '/../sys-backend/session_config.php';
require_once __DIR__ . '/../sys-backend/db_connect.php';

if ($user_system_permissions != 1) {
    require_once __DIR__ . '/no_access.php';
    exit;
}

/** @var string $lang_mechanic */
/** @var string $lang_sort */
/** @var string $lang_color */
/** @var string $lang_work_start */
/** @var string $lang_work_end */
/** @var string $lang_change */

/** Zwraca kolor tylko jeśli jest poprawnym zapisem #rrggbb, inaczej wartość domyślną. */
function settings_safe_color($value, string $default): string
{
    return (is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value)) ? $value : $default;
}

$h = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$result = $conn->query("
    SELECT mechanic_id, mechanic_name, mechanic_sort, color, work_start_time, work_end_time, font_color
    FROM mechanics
    ORDER BY mechanic_sort ASC, mechanic_name ASC
");
?>
<div class="settings-page">
    <section class="settings-card">
        <div class="settings-card-head settings-card-head-row">
            <h2><?php echo $h($lang_mechanic); ?></h2>
            <a href="add_worker.php" class="settings-btn settings-btn-primary">+ Dodaj</a>
        </div>

        <div class="settings-table-wrap">
            <table class="settings-table">
                <tr>
                    <th><?php echo $h($lang_mechanic); ?></th>
                    <th><?php echo $h($lang_sort); ?></th>
                    <th><?php echo $h($lang_color); ?></th>
                    <th><?php echo $h($lang_work_start); ?></th>
                    <th><?php echo $h($lang_work_end); ?></th>
                    <th></th>
                </tr>
                <?php if ($result === false): ?>
                    <tr><td colspan="6">Wystąpił błąd podczas pobierania danych.</td></tr>
                <?php else: ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><strong><?php echo $h($row['mechanic_name']); ?></strong></td>
                            <td><?php echo $h($row['mechanic_sort']); ?></td>
                            <td>
                                <span class="settings-swatch"
                                    style="background: <?php echo $h(settings_safe_color($row['color'], '#e2e8f0')); ?>; color: <?php echo $h(settings_safe_color($row['font_color'], '#000000')); ?>;">text</span>
                            </td>
                            <td><?php echo $h(substr((string) $row['work_start_time'], 0, 5)); ?></td>
                            <td><?php echo $h(substr((string) $row['work_end_time'], 0, 5)); ?></td>
                            <td class="settings-actions">
                                <a href="edit_worker.php?id=<?php echo urlencode((string) $row['mechanic_id']); ?>" class="settings-btn"><?php echo $h($lang_change); ?></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </table>
        </div>
    </section>
</div>
