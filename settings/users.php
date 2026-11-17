<?php
require_once __DIR__ . '/../sys-backend/session_config.php';
require_once __DIR__ . '/../sys-backend/db_connect.php';

if ($user_system_permissions != 1) {
    require_once __DIR__ . '/no_access.php';
    exit;
}

/** @var string $lang_LoginName */
/** @var string $lang_permissions */
/** @var string $lang_status */
/** @var string $lang_action */
/** @var string $lang_change */

/** @var string $lang_admin */
/** @var string $lang_user */
/** @var string $lang_collaborator */
/** @var string $lang_acive */
/** @var string $lang_inacive */

$h = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$result = $conn->query("
    SELECT user_id, user_login , user_permissions, user_status
    FROM users
    ORDER BY user_id ASC
");
?>
<div class="settings-page">
    <section class="settings-card">
        <div class="settings-card-head settings-card-head-row">
            <h2><?php echo $h($lang_users ?? 'Użytkownicy'); ?></h2>
            <a href="add_user.php" class="settings-btn settings-btn-primary">+ Dodaj</a>
        </div>

        <div class="settings-table-wrap">
            <table class="settings-table">
                <tr>
                    <th><?php echo $h($lang_LoginName); ?></th>
                    <th><?php echo $h($lang_permissions); ?></th>
                    <th><?php echo $h($lang_status); ?></th>
                    <th></th>
                </tr>
                <?php if ($result === false): ?>
                    <tr><td colspan="4">Wystąpił błąd podczas pobierania danych.</td></tr>
                <?php else: ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <?php
                        switch ((int) $row['user_permissions']) {
                            case 1:
                                $permissionsText = $lang_admin;
                                $permissionsClass = 'settings-badge-admin';
                                break;
                            case 2:
                                $permissionsText = $lang_user;
                                $permissionsClass = 'settings-badge-info';
                                break;
                            default:
                                $permissionsText = $lang_collaborator;
                                $permissionsClass = 'settings-badge-muted';
                        }
                        $isActive = (bool) $row['user_status'];
                        ?>
                        <tr>
                            <td><strong><?php echo $h($row['user_login']); ?></strong></td>
                            <td><span class="settings-badge <?php echo $permissionsClass; ?>"><?php echo $h($permissionsText); ?></span></td>
                            <td>
                                <span class="settings-badge <?php echo $isActive ? 'settings-badge-ok' : 'settings-badge-off'; ?>">
                                    <?php echo $h($isActive ? $lang_acive : $lang_inacive); ?>
                                </span>
                            </td>
                            <td class="settings-actions">
                                <a href="edit_user.php?id=<?php echo urlencode((string) $row['user_id']); ?>" class="settings-btn"><?php echo $h($lang_change); ?></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </table>
        </div>
    </section>
</div>
