<?php
require_once __DIR__ . '/../sys-backend/session_config.php';
require_once __DIR__ . '/../sys-backend/config.php';


$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// Uprawnienia i status konta pobieramy z bazy przy każdym żądaniu (nie ufamy wartościom zapisanym w sesji
// przy logowaniu) - dzięki temu zablokowanie konta lub zmiana roli działa natychmiast, a nie po wygaśnięciu sesji.
if (!empty($_SESSION['logged_in']) && !empty($_SESSION['user_id'])) {
    $sessStmt = $conn->prepare('SELECT user_login, user_permissions, user_status FROM users WHERE user_id = ?');
    if ($sessStmt) {
        $sessUserId = (int)$_SESSION['user_id'];
        $sessStmt->bind_param('i', $sessUserId);
        $sessStmt->execute();
        $sessRow = $sessStmt->get_result()->fetch_assoc();
        $sessStmt->close();

        if (!$sessRow || (int)$sessRow['user_status'] !== 1) {
            // konto usunięte lub zablokowane - kończymy sesję
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $cp = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $cp['path'], $cp['domain'], $cp['secure'], $cp['httponly']);
            }
            session_destroy();
            header('Location: ../index.php');
            exit;
        }

        $_SESSION['user_permissions'] = (int)$sessRow['user_permissions'];
        $_SESSION['name'] = $sessRow['user_login'];
        $user_system_permissions = $_SESSION['user_permissions'];
        $userName = $_SESSION['name'];
    }
}

?>