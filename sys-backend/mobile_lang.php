<?php
require_once __DIR__ . '/../sys-backend/config.php';

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
$sql = "SELECT * FROM `language` WHERE `locale` =  '$locale'";
$result = $conn->query($sql);
if ($result->num_rows > 0) {

    while ($r = $result->fetch_assoc()) {
        $id = $r['id'];
        $sys = $r['sys'];
        $locale = $r['locale'];
        $name = $r['name'];
        ${$sys} = $name;
    }
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