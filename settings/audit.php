<?php
require_once __DIR__ . '/../sys-backend/session_config.php';
require_once __DIR__ . '/../sys-backend/db_connect.php';

// tylko administrator
if ((int)$user_system_permissions !== 1) {
    require_once __DIR__ . '/no_access.php';
    exit;
}

/** @var mysqli $conn */

/**
 * Zamienia surowy wpis audytu na czytelny opis.
 * Zwraca: label (nazwa zdarzenia), class (kolor plakietki), detail (szczegóły), group (kategoria filtra).
 */
function audit_describe(string $process, string $value): array
{
    if ($process === 'logowanie') {
        $parts = explode('|', $value, 2);
        $status = $parts[0];
        $ip = $parts[1] ?? '';
        $map = [
            'success'          => ['Zalogowano', 'ok'],
            'invalid_password' => ['Błędne hasło', 'off'],
            'invalid_user'     => ['Nieznany login', 'off'],
            'account_blocked'  => ['Konto zablokowane', 'warn'],
            'throttled'        => ['Blokada limitu prób', 'warn'],
        ];
        [$label, $class] = $map[$status] ?? ['Logowanie (' . $status . ')', 'muted'];
        return [
            'label'  => $label,
            'class'  => $class,
            'detail' => $ip !== '' ? 'IP: ' . $ip : '',
            'group'  => $status === 'success' ? 'login_ok' : 'login_fail',
        ];
    }

    switch ($process) {
        case 'Create reservation':
            return ['label' => 'Nowa rezerwacja', 'class' => 'info', 'detail' => $value, 'group' => 'reservation'];
        case 'Modify reservation':
            return ['label' => 'Zmiana rezerwacji', 'class' => 'warn', 'detail' => $value, 'group' => 'reservation'];
        case 'Delete reservation':
            return ['label' => 'Usunięcie rezerwacji', 'class' => 'off', 'detail' => $value, 'group' => 'reservation'];
        case 'Update SMTP settings':
            return ['label' => 'Zmiana ustawień SMTP', 'class' => 'purple', 'detail' => $value, 'group' => 'settings'];
    }

    if (strncmp($process, 'Notification sent to ', 21) === 0) {
        return [
            'label'  => 'Powiadomienie e-mail',
            'class'  => 'muted',
            'detail' => 'Do: ' . substr($process, 21),
            'group'  => 'notification',
        ];
    }

    return ['label' => $process, 'class' => 'muted', 'detail' => $value, 'group' => 'other'];
}

function audit_parse_reservation_create(string $value): ?array
{
    $pattern = '/\AmechanicId[ \t]*=[ \t]*(.*?),[ \t]*slotDate[ \t]*=[ \t]*(.*?),[ \t]*slotStart[ \t]*=[ \t]*(.*?),[ \t]*slotEnd[ \t]*=[ \t]*(.*?),[ \t]*title[ \t]*=[ \t]*(.*?),[ \t]*reservation_description[ \t]*=[ \t]*(.*?),[ \t]*phone[ \t]*=[ \t]*(.*?),[ \t]*nip[ \t]*=[ \t]*(.*?),[ \t]*vin[ \t]*=[ \t]*(.*?),[ \t]*plate[ \t]*=[ \t]*(.*?)\z/s';
    if (!preg_match($pattern, $value, $matches)) {
        return null;
    }

    return array_combine(
        ['mechanic', 'date', 'start', 'end', 'title', 'description', 'phone', 'nip', 'vin', 'plate'],
        array_map('trim', array_slice($matches, 1))
    );
}

/**
 * Rozbija zapis zmiany rezerwacji na pola do porównania Old/New.
 * Obsługuje zarówno dotychczasowy format, jak i zapis zawierający stare i nowe wartości.
 */
function audit_parse_reservation_change(string $value): array
{
    $get = static function (string $pattern, string $text): string {
        return preg_match($pattern, $text, $matches) ? trim($matches[1]) : '';
    };

    $slotId = $get('/Slot ID:\s*([^+\s,]+)/', $value);
    $oldMechanic = $get('/oldMechanicId\s*=\s*([^,\r\n]+)/', $value);
    $newMechanic = $get('/newMechanicId\s*=\s*([^,\r\n]+)/', $value);

    $slotStartOld = $get('/oldSlotStart\s*=\s*([^,\r\n]+)/', $value);
    $slotStartNew = $get('/newSlotStart\s*=\s*([^,\r\n]+)/', $value);
    if ($slotStartNew === '') {
        $slotStartNew = $get('/slotStart\s*=\s*([^,\r\n]+)/', $value);
    }
    $slotEndOld = $get('/oldSlotEnd\s*=\s*([^,\r\n]+)/', $value);
    $slotEndNew = $get('/newSlotEnd\s*=\s*([^,\r\n]+)/', $value);
    if ($slotEndNew === '') {
        $slotEndNew = $get('/slotEnd\s*=\s*([^,\r\n]+)/', $value);
    }
    $slotDateOld = $get('/oldSlotDate\s*=\s*([^,\r\n]+)/', $value);
    $slotDateNew = $get('/newSlotDate\s*=\s*([^,\r\n]+)/', $value);

    $titleOld = $get('/oldTitle\s*=\s*(.*?)(?=,\s*newTitle\s*=|$)/s', $value);
    $titleNew = $get('/newTitle\s*=\s*(.*?)(?=,\s*(?:old|new)?[A-Za-z_]+\s*=|$)/s', $value);
    if ($titleNew === '') {
        $titleNew = $get('/\btitle\s*=\s*(.*?)(?=,\s*(?:reservation_description|phone|vin|plate|state)\s*=|$)/s', $value);
    }

    $descriptionOld = $get('/oldReservationDescription\s*=\s*(.*?)(?=,\s*newReservationDescription\s*=|$)/s', $value);
    $descriptionNew = $get('/newReservationDescription\s*=\s*(.*?)(?=,\s*(?:old|new)?[A-Za-z_]+\s*=|$)/s', $value);
    if ($descriptionNew === '') {
        $descriptionNew = $get('/reservation_description\s*=\s*(.*?)(?=,\s*(?:phone|vin|plate|state)\s*=|$)/s', $value);
    }

    $phoneOld = $get('/oldPhone\s*=\s*([^,\r\n]+)/', $value);
    $phoneNew = $get('/newPhone\s*=\s*([^,\r\n]+)/', $value);
    if ($phoneNew === '') {
        $phoneNew = $get('/\bphone\s*=\s*([^,\r\n]+)/', $value);
    }
    $vinOld = $get('/oldVin\s*=\s*([^,\r\n]+)/', $value);
    $vinNew = $get('/newVin\s*=\s*([^,\r\n]+)/', $value);
    if ($vinNew === '') {
        $vinNew = $get('/\bvin\s*=\s*([^,\r\n]+)/', $value);
    }
    $plateOld = $get('/oldPlate\s*=\s*([^,\r\n]+)/', $value);
    $plateNew = $get('/newPlate\s*=\s*([^,\r\n]+)/', $value);
    if ($plateNew === '') {
        $plateNew = $get('/\bplate\s*=\s*([^,\r\n]+)/', $value);
    }
    $stateOld = $get('/oldState\s*=\s*([^,\r\n]+)/', $value);
    $stateNew = $get('/newState\s*=\s*([^,\r\n]+)/', $value);
    if ($stateNew === '') {
        $stateNew = $get('/\bstate\s*=\s*([^,\r\n]+)/', $value);
    }
    $nipOld = $get('/oldNip\s*=\s*([^,\r\n]+)/', $value);
    $nipNew = $get('/newNip\s*=\s*([^,\r\n]+)/', $value);
    $receiptOld = $get('/oldReceiptOrInvoice\s*=\s*([^,\r\n]+)/', $value);
    $receiptNew = $get('/newReceiptOrInvoice\s*=\s*([^,\r\n]+)/', $value);
    $commentOld = $get('/oldComment\s*=\s*(.*?)(?=,\s*newComment\s*=|$)/s', $value);
    $commentNew = $get('/newComment\s*=\s*(.*?)(?=,\s*(?:old|new)?[A-Za-z_]+\s*=|$)/s', $value);
    $odometerOld = $get('/oldOdometer\s*=\s*([^,\r\n]+)/', $value);
    $odometerNew = $get('/newOdometer\s*=\s*([^,\r\n]+)/', $value);

    $slotIdOld = $get('/oldSlotId\s*=\s*([^,\r\n]+)/', $value);

    return [
        'slotIdOld' => $slotIdOld,
        'slotIdNew' => $slotId,
        'mechanicOld' => $oldMechanic,
        'mechanicNew' => $newMechanic,
        'slotStartOld' => $slotStartOld,
        'slotStartNew' => $slotStartNew,
        'slotEndOld' => $slotEndOld,
        'slotEndNew' => $slotEndNew,
        'slotDateOld' => $slotDateOld,
        'slotDateNew' => $slotDateNew,
        'titleOld' => $titleOld,
        'titleNew' => $titleNew,
        'descriptionOld' => $descriptionOld,
        'descriptionNew' => $descriptionNew,
        'phoneOld' => $phoneOld,
        'phoneNew' => $phoneNew,
        'vinOld' => $vinOld,
        'vinNew' => $vinNew,
        'plateOld' => $plateOld,
        'plateNew' => $plateNew,
        'stateOld' => $stateOld,
        'stateNew' => $stateNew,
        'nipOld' => $nipOld,
        'nipNew' => $nipNew,
        'receiptOld' => $receiptOld,
        'receiptNew' => $receiptNew,
        'commentOld' => $commentOld,
        'commentNew' => $commentNew,
        'odometerOld' => $odometerOld,
        'odometerNew' => $odometerNew,
    ];
}

/** Warunki SQL dla filtra "typ zdarzenia". */
$typeConditions = [
    'login_ok'     => "(process = 'logowanie' AND `value` LIKE 'success%')",
    'login_fail'   => "(process = 'logowanie' AND `value` NOT LIKE 'success%')",
    'reservation'  => "process IN ('Create reservation', 'Modify reservation', 'Delete reservation')",
    'notification' => "process LIKE 'Notification sent to %'",
    'settings'     => "process = 'Update SMTP settings'",
];
$typeLabels = [
    'login_ok'     => 'Udane logowania',
    'login_fail'   => 'Nieudane logowania',
    'reservation'  => 'Rezerwacje',
    'notification' => 'Powiadomienia e-mail',
    'settings'     => 'Zmiany ustawień',
];

// ---------- filtry (GET) ----------
$fUser = (isset($_GET['audit_user']) && is_string($_GET['audit_user'])) ? trim($_GET['audit_user']) : '';
$fType = (isset($_GET['audit_type']) && is_string($_GET['audit_type']) && isset($typeConditions[$_GET['audit_type']])) ? $_GET['audit_type'] : '';
$fFrom = (isset($_GET['audit_from']) && is_string($_GET['audit_from']) && is_valid_date($_GET['audit_from'])) ? $_GET['audit_from'] : '';
$fTo   = (isset($_GET['audit_to']) && is_string($_GET['audit_to']) && is_valid_date($_GET['audit_to'])) ? $_GET['audit_to'] : '';
$fText = (isset($_GET['audit_q']) && is_string($_GET['audit_q'])) ? trim($_GET['audit_q']) : '';
if (mb_strlen($fText) > 100) {
    $fText = mb_substr($fText, 0, 100);
}

$where = [];
$params = [];
$types = '';

if ($fUser !== '') {
    $where[] = 'sys_submiter = ?';
    $params[] = $fUser;
    $types .= 's';
}
if ($fType !== '') {
    $where[] = $typeConditions[$fType];
}
if ($fFrom !== '') {
    $where[] = 'sys_createdate >= ?';
    $params[] = $fFrom . ' 00:00:00';
    $types .= 's';
}
if ($fTo !== '') {
    $where[] = 'sys_createdate < DATE_ADD(?, INTERVAL 1 DAY)';
    $params[] = $fTo . ' 00:00:00';
    $types .= 's';
}
if ($fText !== '') {
    $like = '%' . addcslashes($fText, '%_\\') . '%';
    $where[] = '(sys_submiter LIKE ? OR process LIKE ? OR `value` LIKE ?)';
    array_push($params, $like, $like, $like);
    $types .= 'sss';
}
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';
$hasFilters = $where !== [];

// ---------- statystyki ----------
$stats = ['today' => 0, 'logins_today' => 0, 'failed_24h' => 0, 'reservations_today' => 0];
$statsRes = $conn->query(
    "SELECT
        COALESCE(SUM(sys_createdate >= CURDATE()), 0) AS today,
        COALESCE(SUM(process = 'logowanie' AND `value` LIKE 'success%' AND sys_createdate >= CURDATE()), 0) AS logins_today,
        COALESCE(SUM(process = 'logowanie' AND `value` NOT LIKE 'success%' AND sys_createdate >= (NOW() - INTERVAL 1 DAY)), 0) AS failed_24h,
        COALESCE(SUM(process IN ('Create reservation', 'Modify reservation', 'Delete reservation') AND sys_createdate >= CURDATE()), 0) AS reservations_today
     FROM audit"
);
if ($statsRes) {
    $stats = array_map('intval', $statsRes->fetch_assoc() ?: $stats);
    $statsRes->free();
}

// ---------- lista użytkowników do filtra ----------
$auditUsers = [];
$usersRes = $conn->query('SELECT DISTINCT sys_submiter FROM audit ORDER BY sys_submiter ASC LIMIT 300');
while ($usersRes && ($u = $usersRes->fetch_assoc())) {
    if ($u['sys_submiter'] !== '') {
        $auditUsers[] = $u['sys_submiter'];
    }
}

$mechanicNames = [];
$mechanicsRes = $conn->query('SELECT mechanic_id, mechanic_name FROM mechanics');
while ($mechanicsRes && ($mechanic = $mechanicsRes->fetch_assoc())) {
    $mechanicNames[(int)$mechanic['mechanic_id']] = (string)$mechanic['mechanic_name'];
}

// ---------- liczba wyników i strona ----------
$perPage = 25;
$countStmt = $conn->prepare('SELECT COUNT(*) AS c FROM audit' . $whereSql);
if ($types !== '') {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$total = (int)($countStmt->get_result()->fetch_assoc()['c'] ?? 0);
$countStmt->close();

$totalPages = max(1, (int)ceil($total / $perPage));
$page = isset($_GET['audit_page']) ? (int)$_GET['audit_page'] : 1;
$page = min(max(1, $page), $totalPages);
$offset = ($page - 1) * $perPage;

$listStmt = $conn->prepare(
    'SELECT sys_id, sys_submiter, sys_createdate, process, `value` FROM audit' . $whereSql
    . ' ORDER BY sys_id DESC LIMIT ' . (int)$perPage . ' OFFSET ' . (int)$offset
);
if ($types !== '') {
    $listStmt->bind_param($types, ...$params);
}
$listStmt->execute();
$rows = $listStmt->get_result();

/** Adres strony zakładki audytu z zachowaniem filtrów. */
$auditLink = static function (int $p) use ($fUser, $fType, $fFrom, $fTo, $fText): string {
    $q = ['tab' => 6];
    if ($fUser !== '') $q['audit_user'] = $fUser;
    if ($fType !== '') $q['audit_type'] = $fType;
    if ($fFrom !== '') $q['audit_from'] = $fFrom;
    if ($fTo !== '')   $q['audit_to'] = $fTo;
    if ($fText !== '') $q['audit_q'] = $fText;
    if ($p > 1)        $q['audit_page'] = $p;
    return 'index.php?' . http_build_query($q);
};
?>
<div class="settings-page audit-page">

    <div class="audit-stats">
        <div class="audit-stat">
            <span class="audit-stat-value"><?php echo $stats['today']; ?></span>
            <span class="audit-stat-label">Zdarzeń dzisiaj</span>
        </div>
        <div class="audit-stat audit-stat-ok">
            <span class="audit-stat-value"><?php echo $stats['logins_today']; ?></span>
            <span class="audit-stat-label">Logowań dzisiaj</span>
        </div>
        <div class="audit-stat audit-stat-off">
            <span class="audit-stat-value"><?php echo $stats['failed_24h']; ?></span>
            <span class="audit-stat-label">Nieudanych logowań (24 h)</span>
        </div>
        <div class="audit-stat audit-stat-info">
            <span class="audit-stat-value"><?php echo $stats['reservations_today']; ?></span>
            <span class="audit-stat-label">Zmian w rezerwacjach dzisiaj</span>
        </div>
    </div>

    <section class="settings-card">
        <div class="settings-card-head">
            <h2>Dziennik zdarzeń (audyt)</h2>
            <p>Kto, kiedy i co zrobił w systemie. Najnowsze wpisy na górze.</p>
        </div>

        <form method="get" action="index.php" class="audit-filters">
            <input type="hidden" name="tab" value="6">

            <div class="settings-field">
                <label for="audit_user">Użytkownik</label>
                <select id="audit_user" name="audit_user">
                    <option value="">Wszyscy</option>
                    <?php foreach ($auditUsers as $au): ?>
                        <option value="<?php echo esc($au); ?>" <?php echo $au === $fUser ? 'selected' : ''; ?>><?php echo esc($au); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="settings-field">
                <label for="audit_type">Typ zdarzenia</label>
                <select id="audit_type" name="audit_type">
                    <option value="">Wszystkie</option>
                    <?php foreach ($typeLabels as $key => $label): ?>
                        <option value="<?php echo esc($key); ?>" <?php echo $key === $fType ? 'selected' : ''; ?>><?php echo esc($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="settings-field">
                <label for="audit_from">Od</label>
                <input id="audit_from" type="date" name="audit_from" value="<?php echo esc($fFrom); ?>">
            </div>

            <div class="settings-field">
                <label for="audit_to">Do</label>
                <input id="audit_to" type="date" name="audit_to" value="<?php echo esc($fTo); ?>">
            </div>

            <div class="settings-field audit-filter-text">
                <label for="audit_q">Szukaj</label>
                <input id="audit_q" type="text" name="audit_q" value="<?php echo esc($fText); ?>" placeholder="login, telefon, VIN, IP…" maxlength="100">
            </div>

            <div class="audit-filter-actions">
                <button type="submit" class="settings-btn settings-btn-primary">Filtruj</button>
                <?php if ($hasFilters): ?>
                    <a href="index.php?tab=6" class="settings-btn">Wyczyść</a>
                <?php endif; ?>
            </div>
        </form>

        <div class="audit-summary">
            <?php if ($total === 0): ?>
                Brak wpisów<?php echo $hasFilters ? ' dla wybranych filtrów' : ''; ?>.
            <?php else: ?>
                Wpisy <strong><?php echo $offset + 1; ?>–<?php echo min($offset + $perPage, $total); ?></strong>
                z <strong><?php echo $total; ?></strong>
                <?php echo $hasFilters ? '(po filtrowaniu)' : ''; ?>
            <?php endif; ?>
        </div>

        <div class="settings-table-wrap">
            <table class="settings-table audit-table">
                <tr>
                    <th>Czas</th>
                    <th>Użytkownik</th>
                    <th>Zdarzenie</th>
                    <th>Szczegóły</th>
                </tr>
                <?php if ($total === 0): ?>
                    <tr><td colspan="4" class="audit-empty">Nic do pokazania.</td></tr>
                <?php else: ?>
                    <?php while ($row = $rows->fetch_assoc()): ?>
                        <?php
                        $info = audit_describe((string)$row['process'], (string)$row['value']);
                        $ts = strtotime((string)$row['sys_createdate']);
                        $detail = (string)$info['detail'];
                        $user = (string)$row['sys_submiter'];
                        $createdReservation = $row['process'] === 'Create reservation' ? audit_parse_reservation_create($detail) : null;
                        ?>
                        <tr>
                            <td class="audit-time">
                                <?php if ($ts): ?>
                                    <span class="audit-date"><?php echo esc(date('d.m.Y', $ts)); ?></span>
                                    <span class="audit-hour"><?php echo esc(date('H:i:s', $ts)); ?></span>
                                <?php else: ?>
                                    <?php echo esc($row['sys_createdate']); ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="audit-user">
                                    <span class="audit-avatar"><?php echo esc(mb_strtoupper(mb_substr($user !== '' ? $user : '?', 0, 1))); ?></span>
                                    <?php echo esc($user !== '' ? $user : '—'); ?>
                                </span>
                            </td>
                            <td><span class="settings-badge settings-badge-<?php echo esc($info['class']); ?>"><?php echo esc($info['label']); ?></span></td>
                            <td class="audit-detail">
                                <?php if ($detail === ''): ?>
                                    <span class="audit-none">—</span>
                                <?php elseif ($createdReservation !== null): ?>
                                    <details class="audit-change">
                                        <summary><?php echo esc(mb_substr($detail, 0, 110)); ?><?php echo mb_strlen($detail) > 110 ? '…' : ''; ?></summary>
                                        <table class="audit-change-table audit-create-table">
                                            <thead><tr><th>Szczegóły rezerwacji</th></tr></thead>
                                            <tbody>
                                                <?php
                                                $fields = [
                                                    'Mechanik' => $mechanicNames[(int)$createdReservation['mechanic']] ?? $createdReservation['mechanic'],
                                                    'Data' => $createdReservation['date'],
                                                    'Początek' => $createdReservation['start'],
                                                    'Koniec' => $createdReservation['end'],
                                                    'Tytuł' => $createdReservation['title'],
                                                    'Opis' => $createdReservation['description'],
                                                    'Telefon' => $createdReservation['phone'],
                                                    'NIP' => $createdReservation['nip'],
                                                    'VIN' => $createdReservation['vin'],
                                                    'Rejestracja' => $createdReservation['plate'],
                                                ];
                                                foreach ($fields as $field => $fieldValue):
                                                ?>
                                                    <tr><td><strong><?php echo esc($field); ?></strong><br><?php echo $fieldValue === '' ? '<span class="audit-none">—</span>' : esc($fieldValue); ?></td></tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </details>
                                <?php elseif ($row['process'] === 'Modify reservation'): ?>
                                    <?php $change = audit_parse_reservation_change($detail); ?>
                                    <details class="audit-change">
                                        <summary>Zobacz zmiany</summary>
                                        <table class="audit-change-table">
                                            <thead>
                                                <tr>
                                                    <th>Stara wartość</th>
                                                    <th>Nowa wartość</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $rowsToShow = [
                                                    'ID slota' => [$change['slotIdOld'], $change['slotIdNew']],
                                                    'Mechanik' => [
                                                        isset($mechanicNames[(int)$change['mechanicOld']]) ? $mechanicNames[(int)$change['mechanicOld']] : $change['mechanicOld'],
                                                        isset($mechanicNames[(int)$change['mechanicNew']]) ? $mechanicNames[(int)$change['mechanicNew']] : $change['mechanicNew'],
                                                    ],
                                                    'Początek' => [$change['slotStartOld'], $change['slotStartNew']],
                                                    'Koniec' => [$change['slotEndOld'], $change['slotEndNew']],
                                                    'Data' => [$change['slotDateOld'], $change['slotDateNew']],
                                                    'Tytuł' => [$change['titleOld'], $change['titleNew']],
                                                    'Opis' => [$change['descriptionOld'], $change['descriptionNew']],
                                                    'Telefon' => [$change['phoneOld'], $change['phoneNew']],
                                                    'VIN' => [$change['vinOld'], $change['vinNew']],
                                                    'Rejestracja' => [$change['plateOld'], $change['plateNew']],
                                                    'Stan' => [$change['stateOld'], $change['stateNew']],
                                                    'NIP' => [$change['nipOld'], $change['nipNew']],
                                                    'Dokument' => [$change['receiptOld'], $change['receiptNew']],
                                                    'Komentarz' => [$change['commentOld'], $change['commentNew']],
                                                    'Przebieg' => [$change['odometerOld'], $change['odometerNew']],
                                                ];
                                                foreach ($rowsToShow as $field => [$oldValue, $newValue]):
                                                    if ($oldValue === '' && $newValue === '') continue;
                                                ?>
                                                    <tr>
                                                        <td>
                                                            <strong><?php echo esc($field); ?></strong><br>
                                                            <?php echo $oldValue === '' ? '<span class="audit-none">—</span>' : esc($oldValue); ?>
                                                        </td>
                                                        <td>
                                                            <strong><?php echo esc($field); ?></strong><br>
                                                            <?php echo $newValue === '' ? '<span class="audit-none">—</span>' : esc($newValue); ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </details>
                                <?php elseif (mb_strlen($detail) > 110): ?>
                                    <details>
                                        <summary><?php echo esc(mb_substr($detail, 0, 110)); ?>…</summary>
                                        <div class="audit-full"><?php echo esc($detail); ?></div>
                                    </details>
                                <?php else: ?>
                                    <?php echo esc($detail); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="audit-pager" aria-label="Strony audytu">
                <?php if ($page > 1): ?>
                    <a class="settings-btn" href="<?php echo esc($auditLink(1)); ?>">« Pierwsza</a>
                    <a class="settings-btn" href="<?php echo esc($auditLink($page - 1)); ?>">‹ Nowsze</a>
                <?php endif; ?>

                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <?php if ($i === $page): ?>
                        <span class="settings-btn settings-btn-primary audit-page-current"><?php echo $i; ?></span>
                    <?php else: ?>
                        <a class="settings-btn" href="<?php echo esc($auditLink($i)); ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>

                <?php if ($page < $totalPages): ?>
                    <a class="settings-btn" href="<?php echo esc($auditLink($page + 1)); ?>">Starsze ›</a>
                    <a class="settings-btn" href="<?php echo esc($auditLink($totalPages)); ?>">Ostatnia »</a>
                <?php endif; ?>
            </nav>
        <?php endif; ?>
    </section>
</div>
<?php $listStmt->close(); ?>
