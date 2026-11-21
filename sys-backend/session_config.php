<?php
require_once __DIR__ . '/security.php';

// Czas życia sesji (ciasteczko + garbage collector) - dotyczy administratora, który nie jest wylogowywany
// z powodu bezczynności. Pozostałe grupy są wylogowywane po $idle_timeout sekundach bez aktywności (patrz niżej).
$session_lifetime = 3 * 24 * 60 * 60; // 3 dni w sekundach
$idle_timeout = 15 * 60;               // 15 minut bezczynności dla grup innych niż administrator
$admin_permission_id = 1;              // users.user_permissions = 1 -> Administrator

// Brak ważnej sesji: dla skryptu sprawdzającego sesję (SESSION_JSON) zwracamy JSON 401, w pozostałych przypadkach przekierowanie
function session_expired_response(): void
{
    if (defined('SESSION_JSON')) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo '{"active":false}';
    } else {
        header("Location: ../index.php");
    }
    exit();
}

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

// Wygaszanie sesji po bezczynności - nie dotyczy administratora
if (!empty($_SESSION['logged_in'])) {
    $isAdminSession = (int)($_SESSION['user_permissions'] ?? 0) === $admin_permission_id;
    $lastActivity = (int)($_SESSION['last_activity'] ?? 0);

    if (!$isAdminSession && $lastActivity > 0 && (time() - $lastActivity) > $idle_timeout) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $cp = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $cp['path'], $cp['domain'], $cp['secure'], $cp['httponly']);
        }
        session_destroy();
        session_expired_response();
    }
    // Zapytania sprawdzające sesję (SESSION_NO_TOUCH) nie przedłużają jej - tylko faktyczna aktywność użytkownika
    if (!defined('SESSION_NO_TOUCH')) {
        $_SESSION['last_activity'] = time();
    }
}

// Sprawdzenie, czy użytkownik jest zalogowany
if (empty($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    session_expired_response();
}

// Ochrona CSRF dla każdego żądania POST w zalogowanej części aplikacji
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
}

?>
