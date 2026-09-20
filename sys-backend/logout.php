<?php
// start sesji - musi być przed jakimkolwiek outputem
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// wyczyść wszystkie dane sesji
$_SESSION = [];

// usuń cookie sesji (jeśli istnieje)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// zniszcz sesję
session_destroy();

// przekierowanie na stronę logowania
header("Location: ../index.php");
exit;
