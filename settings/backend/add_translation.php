<?php
require_once __DIR__ . '/../../sys-backend/session_config.php';
require_once __DIR__ . '/../../sys-backend/db_connect.php';
require_admin();
require_post();

if (isset($_POST['sys']) && isset($_POST['locale']) && isset($_POST['name'])) {
    $sys = trim((string)$_POST['sys']);
    $locale = trim((string)$_POST['locale']);
    $name = (string)$_POST['name'];
    $back = '../add_translation.php';

    // klucz musi pasować do filtra w lang.php (tylko lang_*)
    if (!preg_match('/^lang_[A-Za-z0-9_]{1,100}$/', $sys)) {
        fail_redirect('Klucz musi zaczynać się od "lang_" i zawierać tylko litery, cyfry i znak _.', $back);
    }
    if (!in_array($locale, ['pl', 'en', 'ua'], true)) {
        fail_redirect('Nieprawidłowy język.', $back);
    }
    if ($name === '' || mb_strlen($name) > 1000) {
        fail_redirect('Tłumaczenie jest wymagane (maks. 1000 znaków).', $back);
    }

    $chk = $conn->prepare("SELECT 1 FROM language WHERE sys = ? AND locale = ? LIMIT 1");
    $chk->bind_param("ss", $sys, $locale);
    $chk->execute();
    $exists = $chk->get_result()->num_rows > 0;
    $chk->close();
    if ($exists) {
        fail_redirect('Takie tłumaczenie już istnieje - edytuj istniejące.', $back);
    }

    $sql = "insert into language (sys, locale, name) values (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $sys, $locale, $name);
    $stmt->execute();
    $stmt->close();
    $conn->close();

    header('Location: ../index.php?tab=4');
    exit();
}
