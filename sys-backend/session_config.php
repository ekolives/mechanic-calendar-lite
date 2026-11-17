<?php
require_once __DIR__ . '/security.php';

// Konfiguracja czasu życia sesji na 2 dni
$session_lifetime = 2 * 24 * 60 * 60; // 2 dni w sekundach

if (session_status() === PHP_SESSION_NONE) {
    // Ustawienia sesji (tylko przed startem sesji!)
    ini_set('session.gc_maxlifetime', $session_lifetime);
    secure_session_cookie_params($session_lifetime);

    // Uruchomienie sesji
    session_start();

    $userId = $_SESSION['user_id'] ?? null;
    $userName = $_SESSION['name'] ?? null;
    $user_system_permissions = $_SESSION['user_permissions']?? null;

}

// Sprawdzenie, czy użytkownik jest zalogowany
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: ../index.php");
    exit();
}

// Ochrona CSRF dla każdego żądania POST w zalogowanej części aplikacji
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
}

?>
