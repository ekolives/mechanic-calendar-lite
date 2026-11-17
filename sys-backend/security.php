<?php
/**
 * Wspólne funkcje bezpieczeństwa: ciasteczko sesji, CSRF, wymóg uprawnień admina.
 */

/**
 * Ustawia bezpieczne parametry ciasteczka sesji. Wywołać PRZED session_start().
 */
function secure_session_cookie_params(int $lifetime = 0): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_set_cookie_params([
        'lifetime' => $lifetime,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
}

/** Zwraca token CSRF powiązany z sesją. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Ukryte pole formularza z tokenem CSRF. */
function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

/** Sprawdza token CSRF (pole POST lub nagłówek X-CSRF-Token). Przerywa żądanie przy błędzie. */
function csrf_verify(): void
{
    $sent = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $expected = $_SESSION['csrf_token'] ?? '';
    if (!is_string($sent) || $expected === '' || !hash_equals($expected, $sent)) {
        http_response_code(403);
        echo 'Nieprawidłowy token bezpieczeństwa (CSRF). Odśwież stronę i spróbuj ponownie.';
        exit;
    }
}

/** Wymaga roli administratora (user_permissions == 1), inaczej kończy żądanie z 403. */
function require_admin(): void
{
    if ((int)($_SESSION['user_permissions'] ?? 0) !== 1) {
        http_response_code(403);
        echo 'Brak dostępu.';
        exit;
    }
}

/** Wymaga metody POST. */
function require_post(): void
{
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        exit;
    }
}

/** Bezpieczny powrót na poprzednią stronę (tylko ten sam host), z fallbackiem. */
function safe_referer_redirect(string $fallback): void
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $host = parse_url($ref, PHP_URL_HOST);
    $ownHost = parse_url('//' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
    if ($ref !== '' && $host !== null && $host === $ownHost && !preg_match('/[\r\n]/', $ref)) {
        header('Location: ' . $ref);
    } else {
        header('Location: ' . $fallback);
    }
    exit;
}

/** Skrót do escapowania wartości wypisywanych w HTML (w tym w atrybutach). */
function esc($value): string
{
    // ENT_SUBSTITUTE: niepoprawne bajty UTF-8 zamieniane są na znak zastępczy, zamiast zwracać pusty tekst
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Czy użytkownik ma dostęp do danego mechanika (wpis w `permissions` ze status = 1).
 * To ta sama reguła, według której kalendarz decyduje, których mechaników pokazać.
 */
function user_can_access_mechanic(mysqli $conn, $userId, $mechanicId): bool
{
    $userId = (int)$userId;
    $mechanicId = (int)$mechanicId;
    if ($userId <= 0 || $mechanicId <= 0) {
        return false;
    }
    $stmt = $conn->prepare('SELECT 1 FROM permissions WHERE user_id = ? AND mechanic_id = ? AND status = 1 LIMIT 1');
    $stmt->bind_param('ii', $userId, $mechanicId);
    $stmt->execute();
    $ok = $stmt->get_result()->num_rows > 0;
    $stmt->close();
    return $ok;
}

/** Lista identyfikatorów mechaników dostępnych dla użytkownika. */
function permitted_mechanic_ids(mysqli $conn, $userId): array
{
    $userId = (int)$userId;
    $ids = [];
    $stmt = $conn->prepare('SELECT DISTINCT mechanic_id FROM permissions WHERE user_id = ? AND status = 1');
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $ids[] = (int)$row['mechanic_id'];
    }
    $stmt->close();
    return $ids;
}

/** Czy tekst jest poprawną datą Y-m-d. */
function is_valid_date(string $value): bool
{
    $d = DateTime::createFromFormat('!Y-m-d', $value);
    return $d !== false && $d->format('Y-m-d') === $value;
}

/**
 * Nagłówki bezpieczeństwa. Aplikacja używa inline <script>/<style>, więc CSP dopuszcza 'unsafe-inline',
 * ale blokuje zasoby zewnętrzne, osadzanie w ramkach, zmianę <base>, wysyłanie formularzy na obce domeny
 * oraz wtyczki. Wywołać przed jakimkolwiek outputem.
 */
function send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; "
        . "img-src 'self' data:; font-src 'self' data:; connect-src 'self'; object-src 'none'; "
        . "base-uri 'self'; form-action 'self'; frame-ancestors 'self'");
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    if ($https) {
        header('Strict-Transport-Security: max-age=15552000');
    }
}

/** Komunikat jednorazowy (flash) zapisywany w sesji. */
function flash_set(string $key, string $message): void
{
    $_SESSION['flash_' . $key] = $message;
}

function flash_pop(string $key): ?string
{
    $name = 'flash_' . $key;
    if (!isset($_SESSION[$name])) {
        return null;
    }
    $message = (string)$_SESSION[$name];
    unset($_SESSION[$name]);
    return $message;
}

/** Zapisuje komunikat błędu ustawień i przekierowuje pod wskazany adres. */
function fail_redirect(string $message, string $url): void
{
    flash_set('settings_error', $message);
    header('Location: ' . $url);
    exit;
}

/** Limity nieudanych prób logowania w oknie czasowym. */
const LOGIN_WINDOW_MINUTES = 15;
const LOGIN_MAX_FAILS_PER_LOGIN = 5;
const LOGIN_MAX_FAILS_PER_IP = 20;

/**
 * Czy logowanie jest czasowo zablokowane (za dużo nieudanych prób dla loginu lub adresu IP).
 * Korzysta z tabeli audit (wpisy 'logowanie' / 'invalid_*|<ip>'), więc nie wymaga zmian w bazie.
 */
function login_is_throttled(mysqli $conn, string $login, string $ip): bool
{
    $minutes = LOGIN_WINDOW_MINUTES;
    $stmt = $conn->prepare(
        "SELECT COALESCE(SUM(sys_submiter = ?), 0) AS by_login,
                COALESCE(SUM(SUBSTRING_INDEX(`value`, '|', -1) = ?), 0) AS by_ip
         FROM audit
         WHERE process = 'logowanie'
           AND `value` LIKE 'invalid\_%'
           AND sys_createdate > (NOW() - INTERVAL {$minutes} MINUTE)"
    );
    if (!$stmt) {
        return false; // błąd bazy nie powinien blokować logowania wszystkich
    }
    $stmt->bind_param('ss', $login, $ip);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($row['by_login'] ?? 0) >= LOGIN_MAX_FAILS_PER_LOGIN
        || (int)($row['by_ip'] ?? 0) >= LOGIN_MAX_FAILS_PER_IP;
}

// Wysyłamy nagłówki od razu - security.php jest dołączany na początku każdej strony.
send_security_headers();

/** Poprawny kolor #rrggbb? */
function is_valid_color($value): bool
{
    return is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1;
}

/** Poprawna godzina HH:MM lub HH:MM:SS? */
function is_valid_time($value): bool
{
    return is_string($value) && preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $value) === 1;
}

/** Ile aktywnych administratorów jest w systemie (opcjonalnie z pominięciem jednego użytkownika). */
function count_active_admins(mysqli $conn, int $excludeUserId = 0): int
{
    $stmt = $conn->prepare('SELECT COUNT(*) AS c FROM users WHERE user_permissions = 1 AND user_status = 1 AND user_id <> ?');
    $stmt->bind_param('i', $excludeUserId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($row['c'] ?? 0);
}
