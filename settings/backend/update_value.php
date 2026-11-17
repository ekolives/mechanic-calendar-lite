<?php
require_once __DIR__ . '/../../sys-backend/session_config.php';
require_once __DIR__ . '/../../sys-backend/db_connect.php';
require_admin();
require_post();

if (isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $setting_sort = (int)($_POST['setting_sort'] ?? 0);
    $setting_values = trim((string)($_POST['setting_values'] ?? ''));
    $setting_status = (int)($_POST['setting_status'] ?? 0);
    $back = '../edit_value.php?id=' . $id;

    if ($id <= 0) {
        fail_redirect('Nieprawidłowy identyfikator ustawienia.', '../index.php?tab=5');
    }
    if (!in_array($setting_status, [0, 1], true)) {
        fail_redirect('Nieprawidłowy status.', $back);
    }
    if (mb_strlen($setting_values) > 255) {
        fail_redirect('Wartość jest za długa (maks. 255 znaków).', $back);
    }

    // ustawienie "external_link" trafia do atrybutu href - dopuszczamy tylko http(s), bez javascript: itp.
    $nameStmt = $conn->prepare("SELECT setting_name FROM settings WHERE sys_id = ?");
    $nameStmt->bind_param("i", $id);
    $nameStmt->execute();
    $settingRow = $nameStmt->get_result()->fetch_assoc();
    $nameStmt->close();
    if (!$settingRow) {
        fail_redirect('Nie znaleziono ustawienia.', '../index.php?tab=5');
    }
    if ($settingRow['setting_name'] === 'external_link' && $setting_values !== '' && !preg_match('#^https?://#i', $setting_values)) {
        fail_redirect('Link zewnętrzny musi zaczynać się od http:// lub https://.', $back);
    }

    $sql = "UPDATE settings SET setting_sort = ?, setting_values = ?, status = ? WHERE sys_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isii", $setting_sort, $setting_values, $setting_status, $id);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    header('Location: ../index.php?tab=5');
    exit();
}
