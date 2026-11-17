<?php
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/config.php';

$docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$appRoot = realpath(__DIR__ . '/..');
$baseUrl = '';
if ($docRoot && $appRoot && strpos($appRoot, $docRoot) === 0) {
    $baseUrl = str_replace('\\', '/', substr($appRoot, strlen($docRoot)));
    $baseUrl = '/' . trim($baseUrl, '/');
    if ($baseUrl === '/') {
        $baseUrl = '';
    }
}
$logoutUrl = $baseUrl . '/sys-backend/logout.php';
$logoutImg = $baseUrl . '/sys-backend/logout.png';


echo "
<div class=\"header-logout-control\">
<a href=\"{$logoutUrl}\" aria-label=\"Logout\"><img src=\"{$logoutImg}\" height=\"27\" width=\"27\" alt=\"\"/></a></div>

<div class=\"header-language-control\">
<form name=\"lang\" class=\"header-language-form\" action=\"\" method=\"POST\">
        " . csrf_field() . "
        <div><input type=\"submit\" value=\"pl\" name=\"lang\" onclick=\"pl\" />
        <input type=\"submit\" value=\"en\" name=\"lang\" onclick=\"en\" /></div>
    </form>
</div>";


$defaultLang = 'pl';

if (!empty($_POST["lang"])) {
    switch (strtolower($_POST["lang"])) {
        case "pl":
            $_SESSION['lang'] = 'pl';
            break;
        case "ua":
            $_SESSION['lang'] = 'ua';
            break;
        case "en":
            $_SESSION['lang'] = 'en';
            break;
        default:
            $_SESSION['lang'] = $defaultLang;
            break;
    }
}

if (empty($_SESSION["lang"])) {
    $_SESSION["lang"] = $defaultLang;
}
$locale = $_SESSION['lang'];
$langStmt = $conn->prepare("SELECT `sys`, `name` FROM `language` WHERE `locale` = ?");
if ($langStmt) {
    $langStmt->bind_param('s', $locale);
    $langStmt->execute();
    $result = $langStmt->get_result();
    while ($result && ($r = $result->fetch_assoc())) {
        // Tylko klucze lang_* o bezpiecznej nazwie - nie pozwalamy nadpisać innych zmiennych aplikacji
        if (preg_match('/^lang_[A-Za-z0-9_]+$/', $r['sys'])) {
            ${$r['sys']} = $r['name'];
        }
    }
    $langStmt->close();
}

function localizedWeekdayName(DateTime $date): string
{
    global $conn;
    
    $englishDay = strtolower($date->format('l'));
    $varName = 'lang_' . $englishDay;
    $locale = $_SESSION['lang'] ?? 'pl';
    
    // Najpierw sprawdź czy jest załadane w GLOBALS (z poprzedniego zapytania)
    if (isset($GLOBALS[$varName]) && $GLOBALS[$varName] !== '') {
        return $GLOBALS[$varName];
    }
    
    // Jeśli nie, spróbuj pobrać z bazy z prepared statement
    $sql = "SELECT `name` FROM `language` WHERE `sys` = ? AND `locale` = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param('ss', $varName, $locale);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['name'];
        }
        $stmt->close();
    }
    
    // Fallback na wypadek braku danych w bazie
    $fallback = [
        'monday' => 'Poniedziałek',
        'tuesday' => 'Wtorek',
        'wednesday' => 'Środa',
        'thursday' => 'Czwartek',
        'friday' => 'Piątek',
        'saturday' => 'Sobota',
        'sunday' => 'Niedziela',
    ];
    
    return $fallback[$englishDay] ?? ucfirst($englishDay);
}

?>