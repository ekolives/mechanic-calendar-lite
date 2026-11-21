<?php
// Sprawdza, czy sesja jest nadal ważna. ?touch=1 dodatkowo odnawia czas bezczynności (użytkownik pracuje na stronie).
define('SESSION_JSON', true);
if (($_GET['touch'] ?? '') !== '1') {
    define('SESSION_NO_TOUCH', true);
}
require_once __DIR__ . '/../../sys-backend/session_config.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');
echo '{"active":true}';
